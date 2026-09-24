<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;
    
    protected $hidden = [
        'visible',
    ];
    
    public function scopeVisible($query,$prefijo = null) {
        return $query->where($prefijo.'visible',true);
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
    
    public function usuario() {
        return $this->belongsTo(User::class,'user_id');
    }
    public function pedido() {
        return $this->belongsTo(Pedido::class,'pedido_id')->with(['cliente','tipopago']);
    }
}
