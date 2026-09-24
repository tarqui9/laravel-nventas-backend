<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    
    public function suministro() {
        return $this->belongsTo(Suministro::class,'suministro_id')->with(['categoria']);
    }
    public function sucursal() {
        return $this->belongsTo(Sucursal::class,'sucursal_id');
    }
}
