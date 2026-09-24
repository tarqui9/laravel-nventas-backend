<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suministro extends Model
{
    use HasFactory;
    
    public function scopeSterm($query,$value,$prefijo = null) {
        $columns = ['nombre','codigo'];
        if ($value) {
            $words_search = explode(' ', $value);
            return $query->where(function ($query) use ($columns, $prefijo, $words_search) {
                foreach ($words_search as $word) {
                    $query = $query->where(function ($query) use ($columns, $prefijo, $word) {
                        foreach ($columns as $column) {
                            $query->orWhere($prefijo.$column,'LIKE','%'.$word.'%');
                        }
                    });
                }
            });
        }
        return $query;
    }

    public function scopeHabilitado($query,$prefijo = null) {
        return $query->where('habilitado',true);
    }

    public function scopeScategoria($query,$value,$prefijo = null) {
        if ($value) {
            return $query->where($prefijo.'categoria_id',$value);
        }
        return $query;
    }

    public function categoria() {
        return $this->belongsTo(Categoria::class,'categoria_id');
    }
    // public function unidades() {
    //     return $this->hasMany(ProductoUnidad::class,'suministro_id')->with(['unidad']);
    // }
    // public function unidad() {
    //     return $this->belongsTo(Unidad::class,'unidad_id');
    // }
    // public function lcaracteristicas() {
    //     return $this->belongsToMany(Caracteristica::class)->withPivot('valor');
    // }
    // public function otros() {
    //     return $this->belongsToMany(Caracteristica::class)->withPivot('valor');
    // }
}
