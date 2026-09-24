<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgrupadoDetalle extends Model
{
    use HasFactory;
    
    public function grupo() {
        return $this->belongsTo(Agrupado::class,'grupo_id')->with(['sucursal','usuario']);
    }
    public function suministro() {
        return $this->belongsTo(Suministro::class)->with(['categoria']);
    }
}
