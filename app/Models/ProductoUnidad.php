<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoUnidad extends Model
{
    use HasFactory;
    
    protected $hidden = [
        'created_at','updated_at',
        'habilitado',
    ];

    public function unidad() {
        return $this->belongsTo(Unidad::class,'unidad_id');
    }
}
