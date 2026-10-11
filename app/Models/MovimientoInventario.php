<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class MovimientoInventario extends Model
{
    // Sin esto, Laravel buscaría la tabla "movimiento_inventarios"
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'tipo',
        'cantidad',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    // RN-09: un movimiento no se edita ni se elimina; un error se corrige con un movimiento inverso
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los movimientos de inventario no se editan (RN-09).'));
        static::deleting(fn () => throw new LogicException('Los movimientos de inventario no se eliminan (RN-09).'));
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registra una entrada o una salida y actualiza el stock del producto (RN-04).
     * Es la ÚNICA forma de cambiar productos.stock_actual.
     *
     * Todo pasa dentro de una transacción que bloquea la fila del producto (lockForUpdate):
     * si dos personas registran a la vez, la segunda espera a que termine la primera y ve
     * el stock ya actualizado. Si algo falla, no se guarda nada (ni el movimiento ni el stock).
     *
     * Si el producto está inactivo, la cantidad no es mayor que 0 o una salida supera el
     * stock (RN-01), lanza ValidationException: el formulario muestra el error junto al campo.
     */
    public static function registrar(Producto $producto, string $tipo, string|int|float $cantidad, User $usuario): self
    {
        if (! in_array($tipo, ['entrada', 'salida'], true)) {
            throw new InvalidArgumentException("Tipo de movimiento desconocido: {$tipo}");
        }

        return DB::transaction(function () use ($producto, $tipo, $cantidad, $usuario) {
            // Se vuelve a leer el producto, ahora con su fila bloqueada hasta el final de la transacción
            $producto = Producto::lockForUpdate()->findOrFail($producto->id);

            if (! $producto->activo) {
                throw ValidationException::withMessages([
                    'producto_id' => "«{$producto->nombre}» está inactivo y no admite movimientos.",
                ]);
            }

            if ((float) $cantidad <= 0) {
                throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor que 0.']);
            }

            // RN-01: no se saca más de lo que hay
            if ($tipo === 'salida' && (float) $cantidad > (float) $producto->stock_actual) {
                throw ValidationException::withMessages([
                    'cantidad' => "No hay stock suficiente: «{$producto->nombre}» tiene "
                        .$producto->unidad_medida->cantidad($producto->stock_actual).'.',
                ]);
            }

            // Primero el stock y después el movimiento: si guardar el movimiento falla,
            // la transacción deshace también el cambio de stock
            if ($tipo === 'entrada') {
                $producto->increment('stock_actual', $cantidad);
            } else {
                $producto->decrement('stock_actual', $cantidad);
            }

            return self::create([
                'producto_id' => $producto->id,
                'usuario_id' => $usuario->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'fecha' => now(),
            ]);
        });
    }
}
