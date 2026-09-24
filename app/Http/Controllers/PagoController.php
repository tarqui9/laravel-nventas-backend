<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PedidoSalida;
use Illuminate\Http\Request;
use App\Models\AgrupadoSalida;

class PagoController extends Controller
{
    public function index(Request $request) {
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        $rol_id = $request->get('rol_id');
        if (!$ini || !$fin) {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $data = Pago::suser(Auth::user()->id)
        ->visible()
        ->sfechas($ini,$fin)
        ->latest()->with(['pedido'])->get();
        if ($rol_id == 1) {
            $data = Pago::visible()
            ->sfechas($ini,$fin)
            ->latest()->with(['pedido','usuario'])->get();
        }

        // Retornar los registros como respuesta
        return response()->json($data);
    }
    public function store(Request $request) {
        $pedido_id = $request->input('pedido_id');
        $monto = $request->input('monto');
        if (is_numeric($monto)) {
            // Convertir a número (entero o decimal según el valor)
            $monto = strpos($monto, '.') !== false ? floatval($monto) : intval($monto);
        } else {
            return response()->json('El monto ingresado no es un número válido', 400);
        }
        try {
            DB::beginTransaction();
            $pedido = Pedido::find($pedido_id);
            if (!$pedido) {
                return response()->json('Pedido no encontrado', 409);
            }
            // return response()->json($pedido->grupo, 409);
            if (!$pedido->grupo->count()) {
                return response()->json('El pedido no se ha preparado', 409);
            }
            if ($pedido->grupo[0]->estado == 'PREVIO') {
                return response()->json('El pedido no ha sido despachado', 409);
            }
            if (!$pedido->deuda) {
                return response()->json('El pedido no tiene deuda', 409);
            }
            if ($pedido->estado == 'ENTREGADO') {
                if ($pedido->tipo == 'Contado') {
                    $deuda = round($pedido->deuda,1,PHP_ROUND_HALF_DOWN);
                    if ($monto < $deuda) {
                        return response()->json('El pago al contado debe ser por el total ('. $deuda . ')', 409);
                    }
                } else {
                    if (!$monto) {
                        return response()->json('El pago debe ser por un valor mayor a 0.', 409);
                    }
                }
                // return response()->json('El pedido ya fue entregado anteriormente', 409);
            } else {
                return response()->json('El pedido no puede ser procesado. Marque como entregado el pedido.', 409);
            }
            if ($monto > 0) {
                $deuda = round($pedido->deuda,1,PHP_ROUND_HALF_DOWN);
                $model = new Pago;
                $model->fecha = date('Y-m-d H:i:s');
                $model->monto = $monto;
                $model->pedido_id = $pedido_id;
                $model->user_id = Auth::user()->id;
                $model->save();
                $pedido->pagos += $monto;
                $pedido->deuda -= $monto;
                // $data = [
                //     'deuda' => $deuda,
                //     'monto' => $monto,
                // ];
                // return response()->json($data, 200);
                if ($deuda <= $monto) {
                    $pedido->deuda = 0;
                }
                if ($pedido->deuda < 0) {
                    $pedido->deuda = 0;
                }
                $pedido->save();
            }
            DB::commit();
            $data = [
                'success' => 'Registrado'
            ];
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
    public function entrega($id) {
        $pedido = Pedido::find($id);
        if (!$pedido) {
            return response()->json('Pedido no encontrado', 409);
        }
        try {
            DB::beginTransaction();
            if ($pedido->estado == 'DEVUELTO') {
                return response()->json('Este pedido no puede ser entregado', 409);
            }
            if ($pedido->estado == 'ENTREGADO') {
                // ya se entrego
                return response()->json('Este pedido ya fue entregado', 409);
            } else {
                // registro de salida de este pedido
                $this->salida($id);
            }
            $pedido->estado = 'ENTREGADO';
            $pedido->fecha_entrega = date('Y-m-d H:i:s');
            $pedido->save();

            DB::commit();
            $data = [
                'success' => 'Pedido entregado'
            ];
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
    public function prueba() {
        
        // $salidas = AgrupadoSalida::from('agrupado_salidas as ags')
        // ->join('pedido_agrupados as pa','pa.grupo_id','ags.grupo_id')
        // ->join('ingreso_detalles as dt','dt.id','ags.ingreso_detalle_id')
        // ->where('pa.pedido_id',32)
        // ->where('dt.suministro_id',43)
        // ->select('ags.*')
        // ->get();
        // return $salidas;

        $pedidos = Pedido::where('estado','ENTREGADO')->get();
        $sum = 0;
        foreach ($pedidos as $key => $value) {
            $pedido_sal = PedidoSalida::where('pedido_id',$value->id)->first();
            if (!$pedido_sal) {
                $sum++;
                $value->estado = 'PEDIDO';
                // $value->save();
            }
        }
        return $sum;
        return $pedidos->count();
    }
    function salida($id) {
        // revisar que el pedido no tenga registrada su salida
        $verify = PedidoSalida::where('pedido_id',$id)->first();
        if ($verify) {
            // ya se registro la salida de este pedido
        } else {
            // registramos la salida de todo el detalle
            $pedido = Pedido::find($id);
            foreach ($pedido->detalle as $key => $value) {
                $salidas = AgrupadoSalida::from('agrupado_salidas as ags')
                ->join('pedido_agrupados as pa','pa.grupo_id','ags.grupo_id')
                ->join('ingreso_detalles as dt','dt.id','ags.ingreso_detalle_id')
                ->where('pa.pedido_id',$id)
                ->where('dt.suministro_id',$value->suministro_id)
                ->select('ags.*')
                ->get();
                $cantidad = $value->cantidad;
                foreach ($salidas as $dt_sal) {
                    if ($cantidad > 0) {
                        if ($dt_sal->disponible >= $cantidad) {
                            $dt_sal->disponible -= $cantidad;
                            $dt_sal->save();
                            $descuento = ($cantidad * $value->precio) * ($pedido->tipopago->descuento / 100);
                            $utilidad = $cantidad * $value->precio - ($cantidad * $dt_sal->dtingreso->costo_unitario) - $descuento;
                            $newItem = new PedidoSalida;
                            $newItem->cantidad = $cantidad;
                            $newItem->precio = $value->precio;
                            $newItem->utilidad = $utilidad;
                            $newItem->descuento = $descuento;
                            $newItem->pedido_id = $id;
                            $newItem->ingreso_detalle_id = $dt_sal->ingreso_detalle_id;
                            $newItem->save();
                            $cantidad = 0;
                        } else {
                            $descuento = ($dt_sal->disponible * $value->precio) * ($pedido->tipopago->descuento / 100);
                            $utilidad = $dt_sal->disponible * $value->precio - ($dt_sal->disponible * $dt_sal->dtingreso->costo_unitario) - $descuento;
                            $newItem = new PedidoSalida;
                            $newItem->cantidad = $dt_sal->disponible;
                            $newItem->precio = $value->precio;
                            $newItem->utilidad = $utilidad;
                            $newItem->descuento = $descuento;
                            $newItem->pedido_id = $id;
                            $newItem->ingreso_detalle_id = $dt_sal->ingreso_detalle_id;
                            $newItem->save();
                            $cantidad = $cantidad - $dt_sal->disponible;
                            $dt_sal->disponible = 0;
                            $dt_sal->save();
                        }
                    }
                }
            }
        }
    }
    public function destroy($id) {
        $model = Pago::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        if ($model->visible) {
            $deuda = $this->deudaMenos($id);
            $pedido = Pedido::find($model->pedido_id);
            $pedido->pagos -= $model->monto;
            $pedido->deuda = $deuda;
            $pedido->save();
        }

        $data = ['success' => 'Eliminado'];
        return response()->json($data, 200);
    }
    function deudaMenos($id) {
        $valor = 0;
        $model = Pago::find($id);
        $model->visible = false;
        $model->save();
        $pedido = Pedido::find($model->pedido_id);
        $valor = $pedido->total - $pedido->list_pagos->sum('monto');
        return $valor;
    }
}
