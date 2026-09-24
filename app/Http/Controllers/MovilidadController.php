<?php

namespace App\Http\Controllers;

use App\Models\Movilidad;
use Illuminate\Http\Request;
use App\Models\MovilidadUser;

class MovilidadController extends Controller
{
    public function store(Request $request) {
        $name = null;
        $dir_folder = 'images/movilidades';
        if($request->hasFile('file')) {
            if (!is_dir(public_path().'/'.$dir_folder)) {
                $crearDir = mkdir(public_path().'/'.$dir_folder, 0777, true);
            }
            $file = $request->file('file');
            //concatena la hora y el nombre del archivo
            $name_file = time().$file->getClientOriginalName();
            $name = $dir_folder.'/'.$name_file;
            $file->move(public_path().'/'.$dir_folder,  $name_file);
        }
        $valid = Movilidad::where('placa',$request->placa)->first();
        if ($valid) {
            return response()->json('Placa repetida', 409);
        }
        $model = new Movilidad;
        if ($name) {
            $model->foto = $name;
        }
        $model->placa = $request->input('placa');
        $model->descripcion = $request->input('descripcion');
        // $model->marca = $request->input('marca');
        // $model->modelo = $request->input('modelo');
        $model->color = $request->input('color');
        $model->habilitado = true;
        $model->save();

        $data = [
            'element' => $model,
            'success' => 'Registrado'
        ];
        return response()->json($data, 201);
    }
    public function update(Request $request, $id) {
        $name = null;
        $dir_folder = 'images/movilidades';
        if($request->hasFile('file')) {
            if (!is_dir(public_path().'/'.$dir_folder)) {
                $crearDir = mkdir(public_path().'/'.$dir_folder, 0777, true);
            }
            $file = $request->file('file');
            //concatena la hora y el nombre del archivo
            $name_file = time().$file->getClientOriginalName();
            $name = $dir_folder.'/'.$name_file;
            $file->move(public_path().'/'.$dir_folder,  $name_file);
        }
        $valid = Movilidad::where('placa',$request->placa)
        ->where('id','!=',$id)->first();
        if ($valid) {
            return response()->json('Placa repetida', 409);
        }
        // Recuperar todos los registros del modelo asociado con el controlador actual
        $model = Movilidad::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        if ($name) {
            $model->foto = $name;
        }
        $model->placa = $request->input('placa');
        $model->descripcion = $request->input('descripcion');
        // $model->marca = $request->input('marca');
        // $model->modelo = $request->input('modelo');
        $model->color = $request->input('color');
        $model->save();

        $data = ['success' => 'Actualizado'];
        // Retornar respuesta
        return response()->json($data, 200);
    }
    public function asignar(Request $request) {
        $valid = MovilidadUser::where('activo',true)
        ->where('movilidad_id',$request->movilidad_id)->first();
        if ($valid) {
            if ($valid->user_id != $request->user_id) {
                return response()->json('La movilidad ya fue asignada a ' . $valid->user->nombre_completo, 409);
            } else {
                return response()->json('La movilidad ya esta asignada a este empleado', 409);
            }
        }
        $query = MovilidadUser::where('activo',true)
        ->where('user_id',$request->user_id)->get();
        foreach ($query as $key => $value) {
            $value->activo = false;
            $value->save();
        }
        $model = new MovilidadUser;
        $model->fecha = date('Y-m-d H:i:s');
        $model->activo = true;
        $model->movilidad_id = $request->input('movilidad_id');
        $model->user_id = $request->input('user_id');
        $model->save();

        $data = [
            'element' => $model,
            'success' => 'Registrado'
        ];
        return response()->json($data, 201);
    }
}
