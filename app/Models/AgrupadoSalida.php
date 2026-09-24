<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgrupadoSalida extends Model
{
    use HasFactory;
    
    public function dtingreso() {
        return $this->belongsTo(IngresoDetalle::class, 'ingreso_detalle_id')->with(['suministro']);
    }
}
