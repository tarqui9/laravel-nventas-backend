<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\TipoPago;
use Illuminate\Http\Request;

class TipoPagoController extends Controller
{
    public function store(Request $request) {
        $valid = TipoPago::where('nombre',$request->nombre)->first();
        if ($valid) {
            return response()->json('Nombre repetido', 409);
        }
        $model = new TipoPago;
        $model->nombre = $request->input('nombre');
        $model->descuento = $request->input('descuento');
        $model->habilitado = true;
        $model->save();

        $data = [
            'element' => $model,
            'success' => 'Registrado'
        ];
        return response()->json($data, 201);
    }
    public function update(Request $request, $id) {
        $valid = TipoPago::where('nombre',$request->nombre)
        ->where('id','!=',$id)->first();
        if ($valid) {
            return response()->json('Nombre repetido', 409);
        }
        // Recuperar todos los registros del modelo asociado con el controlador actual
        $model = TipoPago::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $model->nombre = $request->input('nombre');
        $model->descuento  = $request->input('descuento');
        $model->save();

        $data = ['success' => 'Actualizado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
    public function destroy($id) {
        $model = TipoPago::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $query = Pedido::where('tipo_pago_id',$id)->first();
        if ($query) {
            return response()->json('No puede ser eliminado', 409);
        }
        $model->delete();

        $data = ['success' => 'Eliminado'];
        return response()->json($data, 200);
    }
}
