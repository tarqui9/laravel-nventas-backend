<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SucursalController extends Controller
{
    //
    public function show($id) {
        $data = Sucursal::find($id);
        return response()->json($data, 200);
    }
}
