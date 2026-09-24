<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngresoDetalle extends Model
{
    use HasFactory;
    
    public function ingreso() {
        return $this->belongsTo(Ingreso::class)->with(['usuario','proveedor']);
    }
    public function suministro() {
        return $this->belongsTo(Suministro::class)->with(['categoria']);
    }
}
