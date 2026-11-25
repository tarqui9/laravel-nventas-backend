<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agrupado extends Model
{
    use HasFactory;

    public function scopeSnro($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'numero',$value);
        }
        return $query;
    }

    public function scopeSsucursal($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'sucursal_id',$value);
        }
        return $query;
    }

    public function scopeSuser($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'user_id',$value);
        }
        return $query;
    }

    public function scopeSfechas($query,$ini,$fin,$prefijo = null) {
        if ($ini && $fin) {
            return $query->whereDate($prefijo.'fecha','>=',$ini)
                        ->whereDate($prefijo.'fecha','<=',$fin);
        }
        return $query;
    }

    public function sucursal() {
        return $this->belongsTo(Sucursal::class,'sucursal_id');
    }
    public function usuario() {
        return $this->belongsTo(User::class,'user_id');
    }
    public function pedidos() {
        return $this->belongsToMany(Pedido::class,'pedido_agrupados','grupo_id','pedido_id')->with(['usuario','cliente','tipopago']);
        //     return $this->hasMany(PedidoAgrupado::class,'grupo_id')->with(['pedido']);
    }
    public function detalle() {
        return $this->hasMany(AgrupadoDetalle::class,'grupo_id')->with(['suministro']);
    }
    public function salidas() {
        return $this->hasMany(AgrupadoSalida::class,'grupo_id')->with(['dtingreso']);
    }
}

