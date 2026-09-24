<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Pago;
use App\Models\Stock;
use App\Models\Pedido;
use App\Models\Agrupado;
use Illuminate\Http\Request;
use App\Models\AgrupadoSalida;
use App\Models\IngresoDetalle;
use App\Models\PedidoAgrupado;
use App\Models\AgrupadoDetalle;

class AgrupadoController extends Controller
{
    public function index(Request $request) {
        $data = [];
        $sucursal_id = 1;
        $rol_id = $request->get('rol_id');
        $user_id = $request->get('user_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if (!$ini || !$fin) {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $user = Auth::user();
        if (($rol_id == 1 || $rol_id == 4 || $rol_id == 5) && $user->hasRole(['administrador','almacen','carga'])) {
            $data = Agrupado::ssucursal($sucursal_id)->suser($user_id)
            ->with(['sucursal','usuario','pedidos'])->latest()->get();
        }
        if ($rol_id == 3 && $user->hasRole(['distribuidor'])) {
            $data = Agrupado::ssucursal($sucursal_id)->suser($user->id)
            ->with(['sucursal','usuario','pedidos'])->latest()->get();
        }
        foreach ($data as $key => $value) {
            $query = 0;
            foreach ($value->pedidos as $ped) {
                $query += Pago::from('pagos as p')
                ->join('pedidos as pd','pd.id','p.pedido_id')
                ->visible('p.')
                ->where('pd.id',$ped->id)
                ->where('p.user_id',$value->user_id)
                ->sum('p.monto');
            }
            $value->efectivo = $query;
        }

        return response()->json($data);
    }
    public function show($id) {
        $data = Agrupado::with(['sucursal','usuario','pedidos','detalle'])->find($id);
        foreach ($data->pedidos as $ped) {
            $query = Pago::from('pagos as p')
            ->join('pedidos as pd','pd.id','p.pedido_id')
            ->visible('p.')
            ->where('pd.id',$ped->id)
            ->where('p.user_id',$data->user_id)
            ->sum('p.monto');
            $ped->efectivo = $query;
        }

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
        $pedidos = $request->get('pedidos');
        foreach ($pedidos as $value) {
            // verificar que los pedidos no estan en otro grupo
            $valid = PedidoAgrupado::where('pedido_id',$value['pedido_id'])->first();
            if ($valid) {
                return response()->json('Este pedido ya esta registrado', 409);
            }
        }

        // Recuperar todos los registros del modelo asociado con el controlador actual
        $model = new Agrupado;
        $model->fecha = date('Y-m-d H:i:s');
        $model->estado = 'PREVIO';
        $model->sucursal_id = $sucursal_id;
        $model->user_id = $request->input('user_id');
        $model->save();

        $pedidos = $request->get('pedidos');
        foreach ($pedidos as $value) {
            if ($value['selected'] == 'true' || $value['selected'] == 1) {
                $pedido = Pedido::find($value['pedido_id']);
                // ->where('unidad_id',$value['unidad_id'])
                // ->first();
                $this->llenarDetalle($pedido->detalle,$model->id);
                $newItem = new PedidoAgrupado;
                $newItem->pedido_id = $value['pedido_id'];
                $newItem->grupo_id = $model->id;
                $newItem->save();
            }
        }

        $this->sumarTotal($model->id);

        $data = [
            'element' => $model,
            'success' => 'Registrado'
        ];
        // Retornar respuesta
        return response()->json($data, 201);
    }
    function llenarDetalle($detalles,$grupo_id) {
        foreach ($detalles as $key => $value) {
            $data = AgrupadoDetalle::where('grupo_id',$grupo_id)
            ->where('suministro_id',$value->suministro_id)->first();
            if ($data) {
                $data->cantidad += $value->cantidad;
                // $data->precio += $value->precio;
                $data->total += $value->total;
                $data->suministro_id = $value->suministro_id;
                $data->grupo_id = $grupo_id;
                $data->save();
            } else {
                $data = new AgrupadoDetalle;
                $data->cantidad = $value->cantidad;
                // $data->precio += $value->precio;
                $data->total = $value->total;
                $data->suministro_id = $value->suministro_id;
                $data->grupo_id = $grupo_id;
                $data->save();
            }
        }
    }
    function sumarTotal($id) {
        $data = Agrupado::find($id);
        if ($data) {
            $total = 0;
            foreach ($data->pedidos as $value) {
                $total += ($value->total);
                // $total += ($value->cantidad * $value->precio);
            }
            $data->total = $total;
            $data->numero = $data->id;
            $data->save();
        }
    }
    public function despachar($id) {
        $pedidoGroup = Agrupado::with(['detalle'])->find($id);
        if (!$pedidoGroup) {
            return response()->json('No se encontro el registro', 409);
        }
        if ($pedidoGroup->estado == 'DESPACHADO') {
            return response()->json('Ya fue despachado anteriormente', 409);
        }

        // revisar cantidades antes de descontar
        foreach ($pedidoGroup->detalle as $key => $value) {
            $query = Stock::where('sucursal_id',$pedidoGroup->sucursal_id)
            ->where('suministro_id',$value->suministro_id)
            ->first();
            if (!$query) {
                return response()->json('No se encontro el registro', 409);
            }
            if ($query->cantidad < $value->cantidad) {
                return response()->json('Cantidad de stock insuficiente ' . $query->suministro->nombre, 409);
            }
        }
        try {
            DB::beginTransaction();
            foreach ($pedidoGroup->detalle as $key => $value) {
                $query = Stock::where('sucursal_id',$pedidoGroup->sucursal_id)
                ->where('suministro_id',$value->suministro_id)
                ->first();
                $query->cantidad -= $value->cantidad;
                $query->save();
                $ingresos = IngresoDetalle::from('ingreso_detalles as dt')
                ->join('ingresos as i','i.id','dt.ingreso_id')
                ->where('i.sucursal_id',$pedidoGroup->sucursal_id)
                ->where('dt.suministro_id',$value->suministro_id)
                ->where('cantidad_stock','>',0)
                ->orderBy('fecha_vencimiento')
                ->select('dt.*')
                ->get();
                $cantidad = $value->cantidad;
                if ($ingresos->sum('cantidad_stock') < $cantidad) {
                    $message = 'Cantidad de stock insuficiente';
                    return response()->json($message, 409);
                }
                foreach ($ingresos as $ingreso) {
                    if ($cantidad > 0) {
                        if ($ingreso->cantidad_stock >= $cantidad) {
                            $ingreso->cantidad_stock -= $cantidad;
                            $ingreso->save();
                            $this->registrarSalidaStock($pedidoGroup->id,$ingreso->id,$cantidad);
                            $cantidad = 0;
                        } else {
                            $this->registrarSalidaStock($pedidoGroup->id,$ingreso->id,$ingreso->cantidad_stock);
                            $cantidad = $cantidad - $ingreso->cantidad_stock;
                            $ingreso->cantidad_stock = 0;
                            $ingreso->save();
                        }
                    }
                }
            }
            $pedidoGroup->estado = 'DESPACHADO';
            $pedidoGroup->save();
    
            DB::commit();
            $data = [
                // 'element' => $model,
                'success' => 'Registrado'
            ];
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
    function registrarSalidaStock($id,$dt_id,$cantidad) {
        $newDt = new AgrupadoSalida;
        $newDt->cantidad = $cantidad;
        $newDt->disponible = $cantidad;
        $newDt->grupo_id = $id;
        $newDt->ingreso_detalle_id = $dt_id;
        $newDt->save();
    }
    public function destroy($id) {
        // Recuperar todos los registros del modelo asociado con el controlador actual
        $model = Agrupado::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        if ($model->estado == 'DESPACHADO') {
            return response()->json('Ya fue despachado', 409);
        }
        $model->delete();

        $data = ['success' => 'Eliminado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
    public function quitar($id) {
        // Recuperar todos los registros del modelo asociado con el controlador actual
        // $model = AgrupadoDetalle::find($id);
        $model = PedidoAgrupado::where('pedido_id',$id)->first();
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        if ($model->grupo->estado == 'DESPACHADO') {
            return response()->json('Ya fue despachado', 409);
        }
        if ($model->pedido->estado == 'ENTREGADO') {
            return response()->json('Ya fue entregado este pedido', 409);
        }
        if ($model->pedido->estado == 'DEVUELTO') {
            return response()->json('Este pedido ha sido devuelto', 409);
        }
        $model->delete();
        $this->recontarAgrupado($model->grupo_id);
        $this->sumarTotal($model->grupo_id);

        $data = ['success' => 'Quitado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
    function recontarAgrupado($id) {
        $agrupado = Agrupado::find($id);
        foreach ($agrupado->detalle as $dt) {
            $dt->delete();
        }
        $pedidos = $agrupado->pedidos;
        foreach ($pedidos as $value) {
            if ($value) {
                $pedido = Pedido::find($value->pedido_id);
                $this->llenarDetalle($pedido->detalle,$id);
            }
        }
    }
    public function devolver($id) {
        $pedido = Pedido::find($id);
        if (!$pedido) {
            return response()->json('No se encontro el registro.', 409);
        }
        $agrupado = PedidoAgrupado::where('pedido_id',$id)->first();
        if (!$agrupado) {
            return response()->json('Error.', 409);
        }
        if ($agrupado->grupo->estado != 'DESPACHADO') {
            return response()->json('Este pedido no fue despachado', 409);
        }
        if ($pedido->estado == 'ENTREGADO') {
            return response()->json('Este pedido fue entregado', 409);
        }
        try {
            DB::beginTransaction();
            foreach ($pedido->detalle as $key => $value) {
                $query = AgrupadoSalida::from('agrupado_salidas as as')
                ->join('ingreso_detalles as dt','dt.id','as.ingreso_detalle_id')
                ->where('dt.suministro_id',$value->suministro_id)
                ->where('as.grupo_id',$agrupado->grupo_id)
                ->select('as.*')
                ->orderBy('as.id','DESC')
                ->get();
                        // return response()->json($query, 409);
                $cantidad = $value->cantidad;
                foreach ($query as $as) {
                    $query_st = Stock::where('sucursal_id',$pedido->sucursal_id)
                    ->where('suministro_id',$value->suministro_id)
                    ->first();
                        // return response()->json($query_st, 409);
                    if (!$query_st) {
                        DB::rollback();
                        return response()->json('Error stock.', 409);
                    }
                    if ($cantidad) {
                        if (($as->disponible) < $cantidad) {
                            $aux = $as->disponible;
                            $as->dtingreso->cantidad_stock += $as->disponible;
                            $as->dtingreso->save();
                            $as->devuelto += $as->disponible;
                            $as->disponible = 0;
                            $as->save();
    
                            $query_st->cantidad += $aux;
                            $query_st->save();
                            $cantidad -= $aux;
                            // return response()->json('Err.'.$cantidad, 409);
                        } else {
                            $as->devuelto += $cantidad;
                            $as->disponible -= $cantidad;
                            $as->save();
                            $as->dtingreso->cantidad_stock += $cantidad;
                            $as->dtingreso->save();
    
                            $query_st->cantidad += $cantidad;
                            $query_st->save();
                            $cantidad -= $cantidad;
                        }
                    }
                }
                if ($cantidad > 0) {
                    DB::rollback();
                    return response()->json('Error al devolver.', 409);
                }
            }
            $pedido->estado = 'DEVUELTO';
            $pedido->save();
            DB::commit();
            $data = [
                'success' => 'Devolución correcta'
            ];
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollback();
            return response()->json($th, 409);
        }
    }
}
