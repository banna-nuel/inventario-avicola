<?php

namespace Database\Factories;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Productos de prueba: Producto::factory()->create()
 * Se crean con stock 0: para darles stock, usa MovimientoInventario::registrar()
 * (así el stock siempre coincide con el historial).
 *
 * @extends Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'categoria_id' => Categoria::factory(),
            'unidad_medida' => fake()->randomElement(UnidadMedida::cases()),
            'stock_minimo' => 10,
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
