<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDetalle extends Model
{
    use HasFactory;
    
    public function pedido() {
        return $this->belongsTo(Pedido::class)->with(['sucursal','cliente','tipopago','usuario']);
    }
    public function suministro() {
        return $this->belongsTo(Suministro::class)->with(['categoria']);
    }
}
