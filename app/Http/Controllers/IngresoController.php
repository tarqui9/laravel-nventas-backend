<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Stock;
use App\Models\Ingreso;
use App\Models\Suministro;
use Illuminate\Http\Request;
use App\Models\IngresoDetalle;

class IngresoController extends Controller
{
    public function index(Request $request) {
        $numero = $request->input('numero');
        $proveedor_id = $request->input('proveedor_id');
        $data = Ingreso::latest()
        ->snro($numero)
        ->sproveedor($proveedor_id)
        ->with(['proveedor','usuario'])->get();
        // foreach ($data as $ingreso) {
        //     foreach ($ingreso->detalle as $value) {
        //         $product = Suministro::find($value->sumnistro_id);
        //         $product->stock += $value->cantidad + 0;
        //         $product->save();
        //     }
        // }
        return response()->json($data, 200);
    }
    public function show($id) {
        $data = Ingreso::with(['proveedor','usuario','detalle'])->find($id);
        return response()->json($data, 200);
    }
    public function store(Request $request) {
        $sucursal_id = 1;
        $detalle = $request->input('detalle');
        foreach ($detalle as $key => $value) {
            // if ($value['cantidad'] <= 0) {
            //     return response()->json('La cantidad debe ser mayor a cero.', 409);
            // }
            if (!ctype_digit(strval($value['cantidad'])) || $value['cantidad'] <= 0) {
                return response()->json('La cantidad debe ser un número entero mayor a cero.', 409);
            }
            if ($value['costo_unitario'] <= 0) {
                return response()->json('El costo unitario debe ser mayor a cero.', 409);
            }
        }
        $valid = Ingreso::where('numero',$request->input('numero'))->first();
        if ($valid) {
            return response()->json('El número de comprobante ya fue registrado', 409);
        }
        try {
            DB::beginTransaction();
            $model = new Ingreso;
            $model->numero = $request->input('numero');
            $model->fecha = $request->input('fecha');
            $model->fecha = date('Y-m-d');
            $model->fecha_registro = date('Y-m-d H:i:s');
            $model->proveedor_id = $request->input('proveedor_id');
            $model->sucursal_id = $sucursal_id;
            $model->user_id = Auth::user()->id;
            $model->save();
    
            foreach ($detalle as $key => $value) {
                $this->sumarStock($value['suministro_id'],$value['cantidad']*1,$sucursal_id);
                $fecha = preg_replace('/\.\d+Z$/', '', $value['fecha_vencimiento']); // Quita .000Z
                $fecha_formateada = date('Y-m-d', strtotime($fecha));
                $newDt = new IngresoDetalle;
                $newDt->lote = $value['lote'];
                $newDt->fecha_vencimiento = $fecha_formateada;
                // $newDt->fecha_vencimiento = date('Y-m-d',strtotime($value['fecha_vencimiento']));
                $newDt->cantidad = $value['cantidad'];
                $newDt->cantidad_stock = $value['cantidad'];
                $newDt->costo_unitario = $value['costo_unitario'];
                // $newDt->estado = $value['estado'];
                $newDt->suministro_id = $value['suministro_id'];
                $newDt->ingreso_id = $model->id;
                $newDt->save();
            }
            $this->sumarTotal($model->id);
            DB::commit();
    
            $data = ['success' => 'Registrado'];
            return response()->json($data, 201);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
    function sumarStock($suministro_id, $cantidad, $sucursal_id) {
        $query = Stock::where('suministro_id',$suministro_id)
        ->where('sucursal_id',$sucursal_id)->first();
        if (!$query) {
            $query = new Stock;
            $query->cantidad = $cantidad;
            $query->suministro_id = $suministro_id;
            $query->sucursal_id = $sucursal_id;
        } else {
            $query->cantidad += $cantidad;
        }
        $query->save();
    }
    public function update(Request $request, $id) {
        $valid = Ingreso::where('numero',$request->input('numero'))
        ->where('id','!=',$id)->first();
        if ($valid) {
            return response()->json('El número de comprobante ya fue registrado', 409);
        }
        try {
            DB::beginTransaction();
            // Recuperar todos los registros del modelo asociado con el controlador actual
            $model = Ingreso::find($id);
            $sucursal_id = $model->sucursal_id;
            $model->numero = $request->input('numero');
            // $model->fecha_registro = date('Y-m-d H:i:s');
            $model->proveedor_id = $request->input('proveedor_id');
            $model->save();
            foreach ($model->detalle as $detalle) {
                if ($detalle->cantidad_stock != $detalle->cantidad) {
                    return response()->json('Se han realizado operaciones, no puede ser modificado', 409);
                }
                $this->sumarStock($detalle->suministro_id,-$detalle->cantidad,$sucursal_id);
                $detalle->delete();
            }


            $detalle = $request->input('detalle');
            foreach ($detalle as $key => $value) {
                // if ($value['cantidad'] <= 0) {
                //     return response()->json('La cantidad debe ser mayor a cero.', 409);
                // }
                if (!ctype_digit(strval($value['cantidad'])) || $value['cantidad'] <= 0) {
                    return response()->json('La cantidad debe ser un número entero mayor a cero.', 409);
                }
                if ($value['costo_unitario'] <= 0) {
                    return response()->json('El costo unitario debe ser mayor a cero.', 409);
                }
            }
            foreach ($detalle as $key => $value) {
                $this->sumarStock($value['suministro_id'],$value['cantidad']*1,$sucursal_id);
                // $newDt = IngresoDetalle::find($value['id']);
                $newDt = null;
                $fecha = preg_replace('/\.\d+Z$/', '', $value['fecha_vencimiento']); // Quita .000Z
                $fecha_formateada = date('Y-m-d', strtotime($fecha));
                if ($newDt) {
                    $suministro = Suministro::find($value['suministro_id']);
                    $newDt->lote = $value['lote'];
                    $newDt->fecha_vencimiento = $fecha_formateada;
                    // $newDt->fecha_vencimiento = date('Y-m-d',strtotime($value['fecha_vencimiento']));
                    $newDt->cantidad = $value['cantidad'];
                    $newDt->cantidad_stock = $value['cantidad'];
                    $newDt->costo_unitario = $value['costo_unitario'];
                    // $newDt->estado = $value['estado'];
                    // $newDt->suministro_id = $value['suministro_id'];
                    $newDt->save();
                } else {
                    $newDt = new IngresoDetalle;
                    $newDt->lote = $value['lote'];
                    $newDt->fecha_vencimiento = $fecha_formateada;
                    // $newDt->fecha_vencimiento = date('Y-m-d',strtotime($value['fecha_vencimiento']));
                    $newDt->cantidad = $value['cantidad'];
                    $newDt->cantidad_stock = $value['cantidad'];
                    $newDt->costo_unitario = $value['costo_unitario'];
                    // $newDt->estado = $value['estado'];
                    $newDt->suministro_id = $value['suministro_id'];
                    $newDt->ingreso_id = $model->id;
                    $newDt->save();
                }
            }
            $this->sumarTotal($model->id);
            DB::commit();

            $data = ['success' => 'Actualizado'];
            // Retornar respuesta
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
    function sumarTotal($id) {
        $data = Ingreso::find($id);
        if ($data) {
            $total = 0;
            foreach ($data->detalle as $value) {
                $total += ($value->cantidad * $value->costo_unitario);
            }
            $data->total = $total;
            $data->save();
        }
    }
    public function eliminardetalle($id) {
        $model = IngresoDetalle::find($id);
        if (!$model) {
            return response()->json('No encontrado', 409);
        }
        
        $suministro = Suministro::find($model->suministro_id);
        if ($model->cantidad_stock < $model->cantidad) {
            return response()->json('Hay cambios en el lote de ' . $suministro->nombre . ' (Pedidos despachados)', 409);
        }
        $suministro->save();
        // descontar stock
        $stock = Stock::where('sucursal_id',$model->ingreso->sucursal_id)
        ->where('suministro_id',$model->suministro_id)->first();
        $stock->cantidad -= $model->cantidad;
        $stock->save();
        $model->delete();

        $data = [
            'success' => 'Eliminado'
        ];
        return response()->json($data, 200);
    }
    public function destroy($id) {
        try {
            DB::beginTransaction();
            $model = Ingreso::find($id);
            if (!$model) {
                return response()->json('Elemento no encontrado', 409);
            }
            foreach ($model->detalle as $key => $value) {
                $suministro = Suministro::find($value->suministro_id);
                if ($value->cantidad != $value->cantidad_stock) {
                    return response()->json('No se puede eliminar ' . $suministro->nombre, 409);
                }
                $this->sumarStock($value->suministro_id,-$value->cantidad,$model->sucursal_id);
                if ($suministro->stock < 0) {
                    DB::rollback();
                    return response()->json('No se puede eliminar ' . $suministro->nombre, 409);
                }
            }
            $model->delete();
            DB::commit();
            $data = ['success' => 'Eliminado'];
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            DB::rollback();
            //throw $th;
            return response()->json($th, 409);
        }
    }
}

