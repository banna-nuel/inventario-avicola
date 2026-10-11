<?php

namespace App\Models;

use App\Enums\UnidadMedida;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    // stock_actual NO está aquí a propósito: solo cambia con movimientos (MovimientoInventario::registrar)
    protected $fillable = [
        'nombre',
        'categoria_id',
        'unidad_medida',
        'stock_minimo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'unidad_medida' => UnidadMedida::class,
            'stock_actual' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * RN-02: un producto está en stock bajo cuando stock_actual < stock_minimo
     * (cada producto tiene su propio mínimo). La regla vive solo en este modelo, en dos formas:
     * - en consultas: Producto::stockBajo()->get()
     * - para un producto ya cargado: $producto->enStockBajo()
     * ProductoTest comprueba que las dos den lo mismo.
     */
    public function scopeStockBajo(Builder $query): void
    {
        $query->whereColumn('stock_actual', '<', 'stock_minimo');
    }

    public function enStockBajo(): bool
    {
        return (float) $this->stock_actual < (float) $this->stock_minimo;
    }
}
