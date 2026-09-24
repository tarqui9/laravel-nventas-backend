<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Pago;
use App\Models\Stock;
use App\Models\Pedido;
use App\Models\Suministro;
use Illuminate\Http\Request;
use App\Models\PedidoDetalle;
use App\Models\PedidoAgrupado;
// use App\Models\ProductoUnidad;

class PedidoController extends Controller
{
    public function index(Request $request) {
        $agrupar = $request->get('agrupar');
        $estado = $request->get('estado');
        $rol_id = $request->get('rol_id');
        $cliente_id = $request->get('cliente_id');
        $tipo = $request->get('tipo');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if (!$ini || !$fin) {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $data = [];
        $user = Auth::user();
        if ($user->hasRole(['administrador']) && $rol_id == 1) {
            $data = Pedido::sestado($estado)
            ->sfechas($ini,$fin)
            ->scliente($cliente_id)
            ->stipo($tipo)
            ->with(['sucursal','cliente','tipopago','usuario'])->latest()->get();
            if ($agrupar) {
                $pedidosIds = PedidoAgrupado::pluck('pedido_id')->toArray();
                $data = Pedido::sestado($estado)
                ->whereNotIn('id', $pedidosIds)
                ->with(['sucursal','cliente','tipopago','usuario'])->latest()->get();
            }
        }
        if ($user->hasRole(['vendedor']) && $rol_id == 2) {
            $data = Pedido::sestado($estado)
            ->scliente($cliente_id)
            ->sfechas($ini,$fin)
            ->stipo($tipo)
            ->where('user_id',$user->id)
            ->with(['sucursal','cliente','tipopago','usuario'])->latest()->get();
        }
        if ($user->hasRole(['almacen']) && $rol_id == 4) {
            $data = Pedido::sestado($estado)
            ->scliente($cliente_id)
            ->with(['sucursal','cliente','tipopago','usuario'])->latest()->get();
            if ($agrupar) {
                $pedidosIds = PedidoAgrupado::pluck('pedido_id')->toArray();
                $data = Pedido::sestado($estado)
                ->whereNotIn('id', $pedidosIds)
                ->with(['sucursal','cliente','tipopago','usuario'])->latest()->get();
            }
        }
        foreach ($data as $key => $value) {
            $query = Pago::from('pagos as p')
            ->join('pedidos as pd','pd.id','p.pedido_id')
            ->visible('p.')
            ->where('pd.id',$value->id)
            ->where('p.user_id',$value->user_id)
            ->sum('p.monto');
            $value->efectivo = $query;
        }

        return response()->json($data);
    }
    public function show($id) {
        $data = Pedido::with(['sucursal','cliente','tipopago','usuario','detalle'])->find($id);

        // Retornar los registros como respuesta
        return response()->json($data);
    }
    public function store(Request $request) {
        $sucursal_id = $request->input('sucursal_id');
        $rol_id = $request->input('rol_id');
        if ($rol_id == 2) {
            $sucursal_id = 1;
        }
        $sucursal_id = 1;

        $cliente = $request->input('cliente_id');
        $detalles = $request->get('detalle');
        // Paso 1: Extraer los `suministro_id`
        $suministroIds = array_column($detalles, 'suministro_id');

        // Paso 2: Contar la cantidad de cada `suministro_id`
        $counts = array_count_values($suministroIds);

        // Paso 3: Filtrar los valores repetidos
        $duplicados = array_filter($counts, function ($count) {
            return $count > 1;
        });
        // Paso 4: Generar un mensaje si hay duplicados
        if (!empty($duplicados)) {
            $mensajes = null;
            foreach ($duplicados as $suministroId => $count) {
                $suministro = Suministro::find($suministroId);
                $mensajes .= "$suministro->nombre está repetido $count veces.\n";
            }
            return response()->json($mensajes, 409);
        }
        $pedidosDespachados = PedidoAgrupado::from('pedido_agrupados as pa')
        ->join('agrupados as g','g.id','pa.grupo_id')
        ->where('g.estado','DESPACHADO')
        ->pluck('pa.pedido_id');

        foreach ($detalles as $value) {
            if ($value['precio'] <= 0) {
                return response()->json('El precio debe ser mayor a 0', 409);
            }
            if (!filter_var($value['cantidad'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                return response()->json('La cantidad debe ser un número entero mayor a 0', 409);
            }
            $suministro = Suministro::find($value['suministro_id']);
            // validar las cantidades de stock
            $totalPedido = 0;
            $queryPedidos = PedidoDetalle::from('pedido_detalles as dt')
            ->join('pedidos as p','p.id','dt.pedido_id')
            ->whereNotIn('p.id', $pedidosDespachados)
            ->where('p.estado','PEDIDO')
            ->where('dt.suministro_id',$value['suministro_id'])
            ->select('dt.*')
            ->get();
            foreach ($queryPedidos as $dt_ped) {
                $totalPedido += $dt_ped->cantidad;
            }
            $productoSt = Stock::where('suministro_id',$value['suministro_id'])
            ->where('sucursal_id',$sucursal_id)->first();
            if (!$productoSt) {
                return response()->json('No disponible - ' . $suministro->codigo . ' ' .$suministro->nombre, 409);
            }
            $stock = $productoSt->cantidad - $totalPedido;
            if ($stock < $value['cantidad']) {
                $detail_txt = $productoSt->suministro->codigo . ' ' . $productoSt->suministro->nombre . ' ['.$stock.']';
                return response()->json('Stock insuficiente - ' . $detail_txt, 409);
            }
        }
        try {
            DB::beginTransaction();
            $model = new Pedido;
            $model->fecha = date('Y-m-d H:i:s');
            $model->estado = 'PEDIDO';
            $model->razon_social = $request->input('razon_social');
            $model->nit_ci = $request->input('nit_ci');
            $model->observacion = $request->input('observacion');
            $model->descuento = 0;
            $model->sucursal_id = $sucursal_id;
            $model->cliente_id = $cliente ? $cliente['id'] : null;
            $model->tipo = $request->input('tipo');
            $model->tipo_pago_id = $request->input('tipo_pago_id');
            $model->user_id = Auth::user()->id;
            $model->save();
    
            $detalles = $request->get('detalle');
            foreach ($detalles as $value) {
                // $unidadQuery = ProductoUnidad::where('suministro_id',$value['suministro_id'])
                // ->where('unidad_id',$value['unidad_id'])
                // ->first();
                $newItem = new PedidoDetalle;
                $newItem->cantidad = $value['cantidad'];
                $newItem->entregado = $value['cantidad'];
                $newItem->precio = $value['precio'];
                $newItem->total = $value['cantidad'] * $value['precio'];
                // $newItem->factor = $unidadQuery->factor;
                $newItem->suministro_id = $value['suministro_id'];
                // $newItem->unidad_id = $value['unidad_id'];
                $newItem->pedido_id = $model->id;
                $newItem->save();
            }
    
            $this->sumarTotal($model->id);
            DB::commit();
    
            $data = [
                'element' => $model,
                'success' => 'Registrado'
            ];
            // Retornar respuesta
            return response()->json($data, 201);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }

    function sumarTotal($id) {
        $data = Pedido::find($id);
        if ($data) {
            $total = 0;
            foreach ($data->detalle as $value) {
                $total += ($value->cantidad * $value->precio);
            }
            $descuento = (($total * $data->tipopago->descuento) / 100);
            $data->total = $total - $descuento;
            $data->descuento = $descuento;
            $data->subtotal = $total;
            $data->deuda = $data->total;
            $data->save();
        }
    }
    
    public function update(Request $request, $id) {
        $sucursal_id = 1;
        // $valid = Pedido::where('nombre',$request->input('nombre'))
        // ->where('id','!=',$id)->first();
        // if ($valid) {
        //     return response()->json('Nombre repetido', 409);
        // }
        // Recuperar todos los registros del modelo asociado con el controlador actual
        $cliente = $request->input('cliente_id');
        $detalles = $request->get('detalle');
        // Paso 1: Extraer los `suministro_id`
        $suministroIds = array_column($detalles, 'suministro_id');

        // Paso 2: Contar la cantidad de cada `suministro_id`
        $counts = array_count_values($suministroIds);

        // Paso 3: Filtrar los valores repetidos
        $duplicados = array_filter($counts, function ($count) {
            return $count > 1;
        });
        // Paso 4: Generar un mensaje si hay duplicados
        if (!empty($duplicados)) {
            $mensajes = null;
            foreach ($duplicados as $suministroId => $count) {
                $suministro = Suministro::find($suministroId);
                $mensajes .= "$suministro->nombre está repetido $count veces.\n";
            }
            return response()->json($mensajes, 409);
        }
        $pedidosDespachados = PedidoAgrupado::from('pedido_agrupados as pa')
        ->join('agrupados as g','g.id','pa.grupo_id')
        ->where('g.estado','DESPACHADO')
        ->pluck('pa.pedido_id');
        foreach ($detalles as $value) {
            if ($value['precio'] <= 0) {
                return response()->json('El precio debe ser mayor a 0', 409);
            }
            if (!filter_var($value['cantidad'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                return response()->json('La cantidad debe ser un número entero mayor a 0', 409);
            }
            $suministro = Suministro::find($value['suministro_id']);
            // validar las cantidades de stock
            $totalPedido = 0;
            $queryPedidos = PedidoDetalle::from('pedido_detalles as dt')
            ->join('pedidos as p','p.id','dt.pedido_id')
            ->whereNotIn('p.id', $pedidosDespachados)
            ->where('p.estado','PEDIDO')
            ->where('p.id','!=',$id)
            ->where('dt.suministro_id',$value['suministro_id'])
            ->select('dt.*')
            ->get();
            foreach ($queryPedidos as $dt_ped) {
                $totalPedido += $dt_ped->cantidad;
            }
            $productoSt = Stock::where('suministro_id',$value['suministro_id'])
            ->where('sucursal_id',$sucursal_id)->first();
            if (!$productoSt) {
                return response()->json('No disponible - ' . $suministro->codigo . ' ' .$suministro->nombre, 409);
            }
            $stock = $productoSt->cantidad - $totalPedido;
            if ($stock < $value['cantidad']) {
                $detail_txt = $productoSt->suministro->codigo . ' ' . $productoSt->suministro->nombre . ' ['.$stock.']';
                return response()->json('Stock insuficiente - ' . $detail_txt, 409);
            }
        }
        
        try {
            DB::beginTransaction();
            $model = Pedido::find($id);
            if (!$model) {
                return response()->json('Elemento no encontrado', 409);
            }
            $query = PedidoAgrupado::where('pedido_id',$id)->first();
            if ($query) {
                if ($query->grupo->estado == 'DESPACHADO') {
                    return response()->json('Ya fue despachado', 409);
                } else {
                    return response()->json('Este pedido esta siendo alistado para despachar', 409);
                }
            }
            if ($model->estado == 'DESPACHADO') {
                return response()->json('El pedido ya fue despachado', 409);
            }
            if ($model->estado == 'ENTREGADO') {
                return response()->json('El pedido ya fue entregado', 409);
            }
            $valid = PedidoAgrupado::where('pedido_id',$id)->first();
            if ($valid) {
                return response()->json('El pedido ya fue preparado en almacen', 409);
            }
            $model->razon_social = $request->input('razon_social');
            $model->nit_ci = $request->input('nit_ci');
            $model->observacion = $request->input('observacion');
            // $model->descuento = 0;
            // $model->sucursal_id = $sucursal_id;
            $model->cliente_id = $cliente ? $cliente['id'] : null;
            // $model->cliente_id = $request->input('cliente_id');
            $model->tipo = $request->input('tipo');
            $model->tipo_pago_id = $request->input('tipo_pago_id');
            // $model->user_id = Auth::user()->id;
            $model->save();
    
            foreach ($model->detalle as $key => $value) {
                $value->delete();
            }
    
            $detalles = $request->get('detalle');
            foreach ($detalles as $value) {
                // $unidadQuery = ProductoUnidad::where('suministro_id',$value['suministro_id'])
                // ->where('unidad_id',$value['unidad_id'])
                // ->first();
                $newItem = new PedidoDetalle;
                $newItem->cantidad = $value['cantidad'];
                $newItem->entregado = $value['cantidad'];
                $newItem->precio = $value['precio'];
                $newItem->total = $value['cantidad'] * $value['precio'];
                // $newItem->factor = $unidadQuery->factor;
                $newItem->suministro_id = $value['suministro_id'];
                // $newItem->unidad_id = $value['unidad_id'];
                $newItem->pedido_id = $model->id;
                $newItem->save();
            }
    
            $this->sumarTotal($model->id);
            DB::commit();
            $data = ['success' => 'Actualizado'];
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
    public function eliminardetalle($id) {
        $model = PedidoDetalle::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $query = PedidoAgrupado::where('pedido_id',$model->pedido_id)->first();
        if ($query) {
            if ($query->grupo->estado == 'DESPACHADO') {
                return response()->json('Ya fue despachado', 409);
            } else {
                return response()->json('Este pedido esta siendo alistado para despachar', 409);
            }
        }
        if ($model->pedido->estado == 'ENTREGADO') {
            return response()->json('No se puede modificar este pedido, ya fue entregado', 409);
        }
        $model->delete();
        $this->sumarTotal($model->pedido_id);

        $data = ['success' => 'Eliminado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
    public function destroy($id) {
        $model = Pedido::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $query = PedidoAgrupado::where('pedido_id',$id)->first();
        if ($query) {
            if ($query->grupo->estado == 'DESPACHADO') {
                return response()->json('Ya fue despachado', 409);
            } else {
                return response()->json('Este pedido esta siendo alistado para despachar', 409);
            }
        }
        if ($model->estado == 'DESPACHADO') {
            return response()->json('No se puede eliminar pedido despachado', 409);
        }
        if ($model->estado == 'ENTREGADO') {
            return response()->json('No se puede eliminar pedido entregado', 409);
        }
        $model->delete();

        $data = ['success' => 'Eliminado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
}
