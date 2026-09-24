<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\TipoPago;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

class ClienteController extends Controller
{
    public function index(Request $request) {
        $data = Cliente::with(['tipopagos'])->latest()->get();
        return response()->json($data, 200);
    }
    public function show($id) {
        $cliente = Cliente::find($id);
        $data = Cliente::find($id);
        
        $tipopagos = TipoPago::get();
        foreach ($tipopagos as $tipo) {
            $tipo->selected = $cliente->hasTipo([$tipo->nombre]);
        }
        $data->tipopagos = $tipopagos;
        return response()->json($data, 200);
    }
    public function store(Request $request) {
        $rol_id = $request->get('rol_id');
        if (!$rol_id) {
            return response()->json('Envie el rol_id en el formulario', 409);
        }
        $newItem = new Cliente;
        $newItem->contacto = $request->contacto;
        $newItem->razon_social = $request->razon_social;
        $newItem->telefono = $request->telefono;
        $newItem->celular = $request->celular;
        $newItem->nit_ci = $request->nit_ci;
        $newItem->direccion = $request->direccion;
        // $newItem->ubicacion = $request->ubicacion;
        $newItem->sucursal_id = true;
        $newItem->user_id = Auth::user()->id;
        $newItem->save();
        
        if ($rol_id == 1) {
            foreach ($request->tipopagos as $rol) {
                if ($rol['selected']) {
                    $newItem->tipopagos()->attach($rol['id']);
                }
            }
        } else {
            $tipopagos = TipoPago::where('descuento',0)->get();
            foreach ($tipopagos as $key => $value) {
                $newItem->tipopagos()->attach($value->id);
            }
        }
        $data = [
            'element' => $newItem,
            'success' => 'Operacion realizada correctamente.'
        ];
        return response()->json($data, 200);
    }
    public function update(Request $request, $id) {
        $rol_id = $request->get('rol_id');
        if (!$rol_id) {
            return response()->json('Envie el rol_id en el formulario', 409);
        }
        $newItem = Cliente::find($id);
        if (!$newItem) {
            return response()->json('No encontrado.', 409);
        }
        $newItem->contacto = $request->contacto;
        $newItem->razon_social = $request->razon_social;
        $newItem->telefono = $request->telefono;
        $newItem->celular = $request->celular;
        $newItem->nit_ci = $request->nit_ci;
        $newItem->direccion = $request->direccion;
        // $newItem->ubicacion = $request->ubicacion;
        $newItem->save();
        
        $formasListCollection = new Collection;
        if ($rol_id == 1) {
            foreach ($request->get('tipopagos') as $rol) {
                if ($rol['selected']) {
                    $formasListCollection->push(['id'=>$rol['id']]);
                }
            }
        } else {
            $tipopagos = TipoPago::where('descuento',0)->get();
            foreach ($tipopagos as $key => $value) {
                $formasListCollection->push(['id'=>$value->id]);
            }
        }
        $newItem->tipopagos()->sync($formasListCollection);
        $data = [
            'success' => 'Registro actualizado correctamente.'
        ];
        return response()->json($data, 200);
    }
    public function destroy($id) {
        $model = Cliente::find($id);
        if (!$model) {
            return response()->json('Elemento no encontrado', 409);
        }
        $query = Pedido::where('cliente_id',$id)->first();
        if ($query) {
            return response()->json('No puede ser eliminado', 409);
        }
        $model->delete();

        $data = ['success' => 'Eliminado'];
        return response()->json($data, 200);
    }
}
