<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingreso extends Model
{
    use HasFactory;

    public function scopeSproveedor($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'proveedor_id',$value);
        }
        return $query;
    }

    public function scopeSnro($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'numero',$value);
        }
        return $query;
    }
    
    public function sucursal() {
        return $this->belongsTo(Sucursal::class,'sucursal_id');
    }
    public function proveedor() {
        return $this->belongsTo(Proveedor::class,'proveedor_id');
    }
    public function usuario() {
        return $this->belongsTo(User::class,'user_id');
    }
    public function detalle() {
        return $this->hasMany(IngresoDetalle::class)->with(['suministro']);
    }
}
