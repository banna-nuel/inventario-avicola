<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
       'nombre',
       'categoria_id',
       'unidad_medida',
       'stock_actual',
       'stock_minimo',
       'activo'
    ];

    public function categoria(){
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos(){
        return $this->hasMany(MovimientoInventario::class);
    }
}
