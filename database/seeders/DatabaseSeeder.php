<?php

namespace Database\Seeders;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Datos de demostración de una granja avícola (solo para desarrollo):
 *   php artisan migrate:fresh --seed
 *
 * - 2 usuarios: admin@test.com (administrador) y encargado@test.com (encargado), contraseña 12345678
 * - 5 categorías y 10 productos con unidades distintas; 4 quedan en stock bajo y 1 inactivo
 * - 33 movimientos de los últimos 14 días. El stock NO se escribe a mano: cada movimiento pasa por
 *   MovimientoInventario::registrar(), así que stock_actual es la suma del historial (se verifica al final)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // La contraseña se cifra sola (cast 'hashed' del modelo User)
        $admin = User::create([
            'name' => 'Laura Gómez',
            'email' => 'admin@test.com',
            'password' => '12345678',
            'rol' => 'admin',
            'activo' => true,
        ]);

        $encargado = User::create([
            'name' => 'Andrés Rincón',
            'email' => 'encargado@test.com',
            'password' => '12345678',
            'rol' => 'encargado',
            'activo' => true,
        ]);

        $categorias = [];
        foreach (['Alimento balanceado', 'Vacunas', 'Medicamentos veterinarios', 'Desinfección y bioseguridad', 'Insumos de galpón'] as $nombre) {
            $categorias[$nombre] = Categoria::create(['nombre' => $nombre]);
        }

        // [producto, categoría, unidad, stock mínimo, movimientos: [hace N días, hora, tipo, cantidad]]
        // Las entradas (compras) las registra la administradora; las salidas (consumo), el encargado.
        $catalogo = [
            ['Concentrado inicio pollitos', 'Alimento balanceado', UnidadMedida::Bultos, 20, [
                [13, '07:30', 'entrada', 60], [11, '06:45', 'salida', 15], [7, '06:50', 'salida', 15],
                [3, '07:05', 'salida', 12], [1, '06:40', 'salida', 6],
            ]],   // queda en 12: stock bajo
            ['Concentrado engorde', 'Alimento balanceado', UnidadMedida::Bultos, 40, [
                [13, '08:00', 'entrada', 120], [9, '06:30', 'salida', 30], [5, '06:35', 'salida', 25],
                [2, '09:15', 'entrada', 40], [0, '06:30', 'salida', 20],
            ]],   // 85
            ['Maíz amarillo', 'Alimento balanceado', UnidadMedida::Kilogramos, 500, [
                [13, '10:00', 'entrada', 1500], [10, '07:00', 'salida', 400], [6, '07:10', 'salida', 350],
                [3, '07:20', 'salida', 420],
            ]],   // 330: stock bajo
            ['Vacuna Newcastle', 'Vacunas', UnidadMedida::Dosis, 1000, [
                [12, '09:00', 'entrada', 3000], [8, '05:30', 'salida', 1000], [1, '05:40', 'salida', 1000],
            ]],   // 1.000: justo en el mínimo (no es stock bajo)
            ['Vacuna Gumboro', 'Vacunas', UnidadMedida::Dosis, 500, [
                [12, '09:10', 'entrada', 1000], [4, '05:30', 'salida', 800],
            ]],   // 200: stock bajo
            ['Enrofloxacina 10 %', 'Medicamentos veterinarios', UnidadMedida::Litros, 4, [
                [10, '11:00', 'entrada', 10], [6, '08:00', 'salida', 2.5], [2, '08:10', 'salida', 1.25],
            ]],   // 6,25
            ['Vitaminas y electrolitos', 'Medicamentos veterinarios', UnidadMedida::Libras, 10, [
                [9, '11:30', 'entrada', 25], [5, '08:20', 'salida', 8], [0, '07:45', 'salida', 4.5],
            ]],   // 12,5
            ['Cal agrícola', 'Desinfección y bioseguridad', UnidadMedida::Cubetas, 8, [
                [13, '14:00', 'entrada', 12], [7, '15:00', 'salida', 3], [2, '15:10', 'salida', 4],
            ]],   // 5: stock bajo
            ['Bandejas para huevo', 'Insumos de galpón', UnidadMedida::Unidades, 300, [
                [11, '10:30', 'entrada', 600], [6, '16:00', 'salida', 180], [1, '16:10', 'salida', 100],
            ]],   // 320
            ['Coccidiostato', 'Medicamentos veterinarios', UnidadMedida::Kilogramos, 0, [
                [13, '11:00', 'entrada', 20], [8, '08:30', 'salida', 20],
            ]],   // 0: se desactiva abajo (conserva su historial, RN-03)
        ];

        foreach ($catalogo as [$nombre, $categoria, $unidad, $minimo, $movimientos]) {
            $producto = Producto::create([
                'nombre' => $nombre,
                'categoria_id' => $categorias[$categoria]->id,
                'unidad_medida' => $unidad,
                'stock_minimo' => $minimo,
            ]);

            foreach ($movimientos as [$dias, $hora, $tipo, $cantidad]) {
                // Hace N días a esa hora; si es de hoy y la hora aún no llega, se usa la hora actual
                $fecha = today()->subDays($dias)->setTimeFromTimeString($hora)->min(now());

                MovimientoInventario::registrar($producto, $tipo, $cantidad, $tipo === 'entrada' ? $admin : $encargado, $fecha);
            }
        }

        Producto::where('nombre', 'Coccidiostato')->update(['activo' => false]);

        $this->verificarStock();
    }

    // El stock de cada producto tiene que ser igual a su historial: entradas − salidas
    private function verificarStock(): void
    {
        $productos = Producto::query()
            ->withSum(['movimientos as entradas' => fn ($consulta) => $consulta->where('tipo', 'entrada')], 'cantidad')
            ->withSum(['movimientos as salidas' => fn ($consulta) => $consulta->where('tipo', 'salida')], 'cantidad')
            ->get();

        foreach ($productos as $producto) {
            $segunHistorial = round((float) $producto->entradas - (float) $producto->salidas, 2);

            if (abs($segunHistorial - (float) $producto->stock_actual) > 0.001) {
                throw new RuntimeException(
                    "Stock descuadrado en «{$producto->nombre}»: {$producto->stock_actual} en el producto y {$segunHistorial} según el historial."
                );
            }
        }

        $this->command?->info("Stock verificado: coincide con el historial en los {$productos->count()} productos.");
    }
}
