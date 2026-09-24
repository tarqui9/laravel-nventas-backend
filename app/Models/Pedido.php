<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    use HasFactory;

    protected $appends = ['dias_transcurridos'];

    public function getDiasTranscurridosAttribute()
    {
        if ($this->estado === 'ENTREGADO' && $this->fecha_entrega) {
            // $dias = Carbon::parse($this->fecha_entrega)->diffInDays(Carbon::now());
            $fechaEntrega = Carbon::parse($this->fecha_entrega)->startOfDay();
            $hoy = Carbon::now()->startOfDay();
            // $dias = $fechaEntrega->diffInDays($hoy);
            // if (!$dias) {
            //     return 'Hoy';
            // }
            return $fechaEntrega->diffInDays($hoy);
            // return $dias;
        }
        return null; // Si no está entregado, no calculamos nada
    }

    public function scopeScliente($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'cliente_id',$value);
        }
        return $query;
    }

    public function scopeSnro($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'numero',$value);
        }
        return $query;
    }
    
    public function scopeSestado($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'estado',$value);
        }
        return $query;
    }
    
    public function scopeStipo($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'tipo',$value);
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
    
    public function scopeSuser($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'user_id',$value);
        }
        return $query;
    }
    
    public function sucursal() {
        return $this->belongsTo(Sucursal::class,'sucursal_id');
    }
    public function cliente() {
        return $this->belongsTo(Cliente::class,'cliente_id')->with(['tipopagos']);
    }
    public function usuario() {
        return $this->belongsTo(User::class,'user_id');
    }
    public function tipopago() {
        return $this->belongsTo(TipoPago::class,'tipo_pago_id');
    }
    public function detalle() {
        return $this->hasMany(PedidoDetalle::class)->with(['suministro']);
    }
    public function dtsalida() {
        return $this->hasMany(PedidoSalida::class);
    }
    public function grupo() {
        return $this->belongsToMany(Agrupado::class,'pedido_agrupados','pedido_id','grupo_id');
        //     return $this->hasMany(PedidoAgrupado::class,'grupo_id')->with(['pedido']);
    }
    public function list_pagos() {
        return $this->hasMany(Pago::class,'pedido_id')->visible()->with(['usuario']);
    }
}
