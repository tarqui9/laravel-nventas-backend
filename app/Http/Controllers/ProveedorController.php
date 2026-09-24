<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function store(Request $request) {
        $valid = Proveedor::where('razon_social',$request->razon_social)->first();
        if ($valid) {
            return response()->json('Razon social repetido', 409);
        }
        $valid = Proveedor::where('nit',$request->nit)->first();
        if ($valid) {
            return response()->json('NIT repetido', 409);
        }
        $model = new Proveedor;
        $model->nombre = $request->input('nombre');
        $model->razon_social = $request->input('razon_social');
        $model->nit = $request->input('nit');
        $model->ciudad = $request->input('ciudad');
        $model->pais = $request->input('pais');
        $model->direccion = $request->input('direccion');
        $model->telefono = $request->input('telefono');
        $model->habilitado = true;
        $model->save();

        $data = [
            'element' => $model,
            'success' => 'Registrado'
        ];
        return response()->json($data, 201);
    }
    public function update(Request $request, $id) {
        $valid = Proveedor::where('razon_social',$request->razon_social)
        ->where('id','!=',$id)->first();
        if ($valid) {
            return response()->json('Razon social repetido', 409);
        }
        $valid = Proveedor::where('nit',$request->nit)
        ->where('id','!=',$id)->first();
        if ($valid) {
            return response()->json('NIT repetido', 409);
        }
        // Recuperar todos los registros del modelo asociado con el controlador actual
        $model = Proveedor::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $model->nombre = $request->input('nombre');
        $model->razon_social  = $request->input('razon_social');
        $model->nit = $request->input('nit');
        $model->ciudad = $request->input('ciudad');
        $model->pais = $request->input('pais');
        $model->direccion = $request->input('direccion');
        $model->telefono = $request->input('telefono');
        $model->save();

        $data = ['success' => 'Actualizado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
    public function destroy($id) {
        $model = Proveedor::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $query = Ingreso::where('proveedor_id',$id)->first();
        if ($query) {
            return response()->json('No puede ser eliminado', 409);
        }
        $model->delete();

        $data = ['success' => 'Eliminado'];
        return response()->json($data, 200);
    }
}
