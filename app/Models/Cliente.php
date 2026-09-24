<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    public function hasTipo($tipopagos) {
        $flag = false;
        foreach ($tipopagos as $nombre) {
            foreach ($this->tipopagos as $key => $value) {
                if ($value->nombre == $nombre) {
                    $flag = true;
                }
            }
        }
        return $flag;
    }

    public function tipopagos(){
        return $this->belongsToMany(TipoPago::class, 'cliente_tipopagos', 'cliente_id', 'tipo_pago_id')->withTimestamps();
    }
}
