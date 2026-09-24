<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoAgrupado extends Model
{
    use HasFactory;

    public function grupo() {
        return $this->belongsTo(Agrupado::class,'grupo_id')->with(['sucursal','usuario']);
    }
    public function pedido() {
        return $this->belongsTo(Pedido::class)->with(['usuario','cliente','tipopago']);
    }
}
