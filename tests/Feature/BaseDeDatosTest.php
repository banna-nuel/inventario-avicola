<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Fase 9: datos de demostración (DatabaseSeeder) e índices
class BaseDeDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_una_granja_de_demostracion_coherente(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('rol', 'admin')->count());
        $this->assertSame(1, User::where('rol', 'encargado')->count());
        $this->assertSame(5, Categoria::count());
        $this->assertSame(10, Producto::count());
        $this->assertGreaterThanOrEqual(30, MovimientoInventario::count());

        // Unidades variadas del enum, algunos en stock bajo y uno inactivo
        $this->assertSame(7, Producto::distinct()->count('unidad_medida'));
        $this->assertSame(4, Producto::enAlerta()->count());
        $this->assertSame(1, Producto::where('activo', false)->count());

        // Movimientos de los últimos 14 días, ninguno en el futuro
        $this->assertSame(0, MovimientoInventario::where('fecha', '<', today()->subDays(13))->count());
        $this->assertSame(0, MovimientoInventario::where('fecha', '>', now())->count());

        // stock_actual = entradas − salidas en cada producto
        foreach (Producto::all() as $producto) {
            $entradas = (float) $producto->movimientos()->where('tipo', 'entrada')->sum('cantidad');
            $salidas = (float) $producto->movimientos()->where('tipo', 'salida')->sum('cantidad');
            $this->assertEqualsWithDelta($entradas - $salidas, (float) $producto->stock_actual, 0.001, $producto->nombre);
        }
    }

    public function test_las_cuentas_de_demostracion_inician_sesion(): void
    {
        $this->seed();

        foreach (['admin@test.com', 'encargado@test.com'] as $correo) {
            $this->post(route('login.entrar'), ['email' => $correo, 'password' => '12345678'])
                ->assertRedirect(route('home'));
            $this->post(route('logout'));
        }
    }

    public function test_los_movimientos_tienen_indice_por_fecha(): void
    {
        $this->assertTrue(Schema::hasIndex('movimientos_inventario', ['fecha']));
    }
}
