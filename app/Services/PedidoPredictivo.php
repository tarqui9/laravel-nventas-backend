<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PedidoPredictivo
{
    public const PREFIX = '[ANALISIS-PREDICTIVO:';

    public function client(int $id, bool $lock = false)
    {
        $client = DB::table('clientes')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if (!$client) {
            abort(404, 'Cliente no encontrado.');
        }
        return $client;
    }

    public function availability(int $branch, array $productIds, ?int $except = null, bool $lock = false): array
    {
        $dispatched = DB::table('pedido_agrupados as pa')->join('agrupados as g', 'g.id_agrupado', '=', 'pa.grupo_id')
            ->where('g.estado', 'DESPACHADO')->pluck('pa.pedido_id');
        $reserved = DB::table('pedido_detalles as d')->join('pedidos as p', 'p.id', '=', 'd.pedido_id')
            ->where('p.sucursal_id', $branch)->where('p.estado', 'PEDIDO')
            ->whereNotIn('p.id', $dispatched)->whereIn('d.suministro_id', $productIds)
            ->when($except, fn ($q) => $q->where('p.id', '!=', $except))
            ->when($lock, fn ($q) => $q->lockForUpdate())->get(['d.suministro_id', 'd.cantidad'])
            ->groupBy('suministro_id')->map(fn ($rows) => $rows->sum('cantidad'));
        return DB::table('stocks')->where('sucursal_id', $branch)->whereIn('suministro_id', $productIds)
            ->when($lock, fn ($q) => $q->lockForUpdate())->get()->mapWithKeys(fn ($row) => [$row->suministro_id => max(0, (int) floor($row->cantidad - ($reserved[$row->suministro_id] ?? 0)))])->all();
    }

    public function detail(int $id): array
    {
        $client = $this->client($id);
        $history = DB::table('pedido_detalles as d')->join('pedidos as p', 'p.id', '=', 'd.pedido_id')
            ->join('suministros as s', 's.id', '=', 'd.suministro_id')
            ->where('p.cliente_id', $id)->where('p.sucursal_id', $client->sucursal_id)->where('p.estado', 'ENTREGADO')
            ->orderByDesc('p.fecha')->select('d.suministro_id', 'd.cantidad', 'p.fecha', 's.nombre')->get();
        $report = [];
        $analysis = null;
        $notice = $history->isEmpty() ? 'Este cliente no tiene compras entregadas. Todavía no se pueden generar sugerencias personales.' : '';
        if ($history->isNotEmpty()) {
            try {
                $response = Http::connectTimeout(3)->timeout(10)->get(config('predictivo.url').'/report');
                if ($response->successful()) {
                    $report = $response->json();
                    if (($report['fuente'] ?? '') === 'mysql_demo' && ($report['version_informe'] ?? 0) >= 3) {
                        $analysis = collect($report['clientes'])->firstWhere('id', $id);
                    }
                }
            } catch (\Illuminate\Http\Client\ConnectionException $error) {
                $notice = 'Python no responde. Se muestra únicamente el historial; no hay porcentajes predictivos disponibles.';
            }
        }
        if (!$analysis) {
            if (!$notice) {
                if (($report['version_informe'] ?? 0) < 3) {
                    $notice = 'El informe guardado es anterior a las predicciones por producto. Pulsa Entrenar análisis o ejecuta php artisan analisis:entrenar --local. No es necesario cargar más datos.';
                } elseif (($report['fuente'] ?? '') !== 'mysql_demo') {
                    $notice = 'El informe no se entrenó desde MySQL. Ejecuta php artisan analisis:entrenar --local para vincular los porcentajes con los productos de esta base.';
                } else {
                    $notice = 'Este cliente no pertenece al conjunto del último entrenamiento. Actualmente el modelo se entrena con la Sucursal DEMO 2025; aquí se muestra solo el historial de este cliente.';
                }
            }
            $analysis = ['id' => $id, 'nombre' => $client->razon_social, 'probabilidad' => null,
                'nivel' => 'Sin predicción', 'proxima_compra_dias' => null, 'proxima_compra_fecha' => null,
                'estimacion_disponible' => false, 'motivo_estimacion' => $notice,
                'productos' => $history->groupBy('suministro_id')->map(function ($rows, $pid) {
                    return ['suministro_id' => (int) $pid, 'nombre' => $rows->first()->nombre,
                        'probabilidad_producto' => null, 'cantidad_habitual' => max(1, (int) round($rows->take(3)->median('cantidad'))),
                        'unidades_historicas' => (int) $rows->sum('cantidad'),
                        'fechas_compra' => $rows->pluck('fecha')->map(fn ($date) => substr($date, 0, 10))->unique()->count(),
                        'ultima_compra' => substr($rows->first()->fecha, 0, 10)];
                })->values()->all()];
        } elseif (empty($report['productos_cliente']['disponible'])) {
            $notice = 'El entrenamiento no tuvo suficientes ejemplos de inclusión y ausencia de productos en próximos pedidos. Se muestra el historial sin porcentajes predictivos.';
        }
        $ids = array_column($analysis['productos'] ?? [], 'suministro_id');
        // El informe puede estar desactualizado: nunca incluir productos ajenos al historial actual.
        $ids = array_values(array_intersect($ids, $history->pluck('suministro_id')->unique()->all()));
        $analysis['productos'] = array_values(array_filter($analysis['productos'] ?? [], fn ($p) => in_array($p['suministro_id'], $ids)));
        $current = DB::table('suministros')->whereIn('id', $ids)->get()->keyBy('id');
        $available = $this->availability($client->sucursal_id, $ids);
        $products = collect($analysis['productos'] ?? [])->map(function ($row) use ($current, $available) {
            $product = $current[$row['suministro_id']] ?? null;
            $row['precio'] = $product ? (float) $product->precio : 0;
            $row['stock_disponible'] = $available[$row['suministro_id']] ?? 0;
            $row['habilitado'] = $product && $product->habilitado;
            return $row;
        })->all();
        $payments = DB::table('tipo_pagos as t')->join('cliente_tipopagos as c', 'c.tipo_pago_id', '=', 't.id')
            ->where('c.cliente_id', $id)->where('t.habilitado', 1)->select('t.id', 't.nombre', 't.descuento')->get();
        $orders = DB::table('pedidos')->where('cliente_id', $id)->where('sucursal_id', $client->sucursal_id)
            ->where('observacion', 'like', self::PREFIX.'%')->orderByDesc('id')->limit(10)->get();
        foreach ($orders as $order) {
            $order->detalle = DB::table('pedido_detalles')->where('pedido_id', $order->id)->get();
            $order->editable = $order->estado === 'PEDIDO'
                && !DB::table('pedido_agrupados')->where('pedido_id', $order->id)->exists()
                && !DB::table('pagos')->where('pedido_id', $order->id)->exists();
        }
        return ['cliente' => (array) $client, 'analisis' => $analysis, 'productos' => $products,
                'tipo_pagos' => $payments, 'pedidos' => $orders, 'mensaje' => $notice,
                'referencia' => $report['hasta'] ?? ($history->isNotEmpty() ? substr($history->first()->fecha, 0, 10) : now()->toDateString())];
    }

    /** Crea o edita un pedido pendiente; nunca descuenta inventario ni registra pagos. */
    public function save(int $clientId, array $input, int $userId, ?int $orderId = null): array
    {
        return DB::transaction(function () use ($clientId, $input, $userId, $orderId) {
            $client = $this->client($clientId, true);
            $marker = self::PREFIX.$input['clave'].']';
            if (!$orderId) {
                $existing = DB::table('pedidos')->where('cliente_id', $clientId)->where('user_id', $userId)
                    ->where('observacion', 'like', $marker.'%')->lockForUpdate()->first();
                if ($existing) {
                    return ['id' => $existing->id, 'total' => (float) $existing->total, 'reutilizado' => true];
                }
            } else {
                $order = DB::table('pedidos')->where('id', $orderId)->where('cliente_id', $clientId)
                    ->where('sucursal_id', $client->sucursal_id)->where('observacion', 'like', self::PREFIX.'%')
                    ->lockForUpdate()->first();
                if (!$order) {
                    abort(404, 'Pedido generado no encontrado.');
                }
                if ($order->estado !== 'PEDIDO' || DB::table('pedido_agrupados')->where('pedido_id', $orderId)->exists()
                    || DB::table('pagos')->where('pedido_id', $orderId)->exists()
                    || DB::table('pedido_salidas')->where('pedido_id', $orderId)->exists()) {
                    throw ValidationException::withMessages(['pedido' => 'Este pedido ya tiene movimientos o está preparado; no se puede editar aquí.']);
                }
                // Conserva la clave original para que un reintento de creación no duplique un pedido editado.
                $marker = substr($order->observacion, 0, strpos($order->observacion, ']') + 1);
            }
            $payment = DB::table('tipo_pagos as t')->join('cliente_tipopagos as c', 'c.tipo_pago_id', '=', 't.id')
                ->where('c.cliente_id', $clientId)->where('t.id', $input['tipo_pago_id'])->where('t.habilitado', 1)->select('t.*')->first();
            if (!$payment || $payment->descuento < 0 || $payment->descuento > 100) {
                throw ValidationException::withMessages(['tipo_pago_id' => 'Selecciona una forma de pago habilitada para este cliente.']);
            }
            $ids = array_column($input['detalle'], 'suministro_id');
            sort($ids);
            DB::table('stocks')->where('sucursal_id', $client->sucursal_id)->whereIn('suministro_id', $ids)
                ->orderBy('suministro_id')->lockForUpdate()->get();
            $available = $this->availability($client->sucursal_id, $ids, $orderId, true);
            $products = DB::table('suministros')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $bought = DB::table('pedido_detalles as d')->join('pedidos as p', 'p.id', '=', 'd.pedido_id')
                ->where('p.cliente_id', $clientId)->where('p.sucursal_id', $client->sucursal_id)->where('p.estado', 'ENTREGADO')
                ->whereIn('d.suministro_id', $ids)->distinct()->pluck('d.suministro_id')->all();
            $lines = [];
            $subtotalCents = 0;
            foreach ($input['detalle'] as $line) {
                $pid = $line['suministro_id'];
                $product = $products[$pid] ?? null;
                if (!$product || !$product->habilitado || !in_array($pid, $bought)) {
                    throw ValidationException::withMessages(['detalle' => 'Incluye solo productos habilitados del historial de este cliente.']);
                }
                if (($available[$pid] ?? 0) < $line['cantidad']) {
                    throw ValidationException::withMessages(['detalle' => 'Stock insuficiente para '.$product->nombre.'. Disponible: '.($available[$pid] ?? 0).' unidades.']);
                }
                $priceCents = (int) round($product->precio * 100);
                if ($priceCents <= 0) {
                    throw ValidationException::withMessages(['detalle' => 'El producto '.$product->nombre.' no tiene precio válido.']);
                }
                $totalCents = $priceCents * $line['cantidad'];
                $subtotalCents += $totalCents;
                $lines[] = ['suministro_id' => $pid, 'cantidad' => $line['cantidad'], 'entregado' => $line['cantidad'],
                            'precio' => $priceCents/100, 'total' => $totalCents/100];
            }
            $discountCents = (int) round($subtotalCents * $payment->descuento / 100);
            $now = now();
            $values = ['razon_social' => $client->razon_social, 'nit_ci' => $client->nit_ci, 'tipo' => 'Contado',
                       'tipo_pago_id' => $payment->id, 'subtotal' => $subtotalCents/100, 'descuento' => $discountCents/100,
                       'total' => ($subtotalCents-$discountCents)/100, 'deuda' => ($subtotalCents-$discountCents)/100,
                       'observacion' => $marker.' Propuesta predictiva; referencia '.$input['referencia'].'. '.($input['observacion'] ?? ''),
                       'updated_at' => $now];
            if ($orderId) {
                DB::table('pedidos')->where('id', $orderId)->update($values);
                DB::table('pedido_detalles')->where('pedido_id', $orderId)->delete();
            } else {
                $orderId = DB::table('pedidos')->insertGetId($values + ['fecha' => $now, 'estado' => 'PEDIDO',
                    'sucursal_id' => $client->sucursal_id, 'cliente_id' => $clientId, 'user_id' => $userId, 'created_at' => $now]);
            }
            foreach ($lines as $line) {
                DB::table('pedido_detalles')->insert($line + ['pedido_id' => $orderId, 'created_at' => $now, 'updated_at' => $now]);
            }
            return ['id' => $orderId, 'total' => $values['total'], 'reutilizado' => false];
        }, 3);
    }
}
