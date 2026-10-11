<?php

namespace Tests\Feature;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Fase 6: Dashboard (RF-03) y la línea de stock bajo en Inicio
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Categoria $alimento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->alimento = Categoria::create(['nombre' => 'Alimento']);
    }

    // Producto con stock (entra con un movimiento, como en la app)
    private function producto(string $nombre, float $stock, float $minimo, bool $activo = true): Producto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'categoria_id' => $this->alimento->id,
            'unidad_medida' => UnidadMedida::Kilogramos,
            'stock_minimo' => $minimo,
        ]);

        if ($stock > 0) {
            MovimientoInventario::registrar($producto, 'entrada', $stock, $this->admin);
        }
        if (! $activo) {
            $producto->update(['activo' => false]);
        }

        return $producto->fresh();
    }

    public function test_los_indicadores_son_iguales_para_los_dos_roles(): void
    {
        $this->producto('Maíz', stock: 100, minimo: 10);
        $this->producto('Soya', stock: 5, minimo: 10);          // bajo
        $this->producto('Sorgo', stock: 0, minimo: 50);         // bajo (sin movimientos)
        $this->producto('Trigo viejo', stock: 0, minimo: 10, activo: false);   // inactivo: no cuenta

        $esperado = ['activos' => 3, 'stockBajo' => 2, 'movimientosHoy' => 2];

        foreach ([$this->admin, User::factory()->encargado()->create()] as $usuario) {
            $this->actingAs($usuario)->get(route('dashboard'))
                ->assertOk()
                ->assertViewHas('indicadores', $esperado)
                ->assertSee('Activos en stock bajo')
                ->assertDontSee('Trigo viejo');
        }
    }

    public function test_movimientos_de_hoy_usa_el_dia_calendario_de_bogota(): void
    {
        $producto = $this->producto('Maíz', stock: 0, minimo: 0);
        $registrarA = function (string $hora) use ($producto) {
            $this->travelTo(Carbon::parse($hora, 'America/Bogota'));
            MovimientoInventario::registrar($producto, 'entrada', 1, $this->admin);
        };

        $registrarA('2026-10-10 23:59:59');   // ayer, por un segundo
        $registrarA('2026-10-11 00:00:00');   // hoy, justo a la medianoche
        $registrarA('2026-10-11 23:59:59');   // hoy, último segundo
        $registrarA('2026-10-12 00:00:00');   // mañana

        // A las 7 p. m. de Bogotá ya es el día siguiente en UTC: no debe contar el de mañana ni el de ayer
        $this->travelTo(Carbon::parse('2026-10-11 19:30:00', 'America/Bogota'));

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertViewHas('indicadores', fn ($indicadores) => $indicadores['movimientosHoy'] === 2);
    }

    public function test_las_alertas_van_del_mayor_al_menor_faltante_con_acceso_para_registrar_una_entrada(): void
    {
        $this->producto('Soya', stock: 5, minimo: 10);      // faltan 5
        $sorgo = $this->producto('Sorgo', stock: 0, minimo: 50);   // faltan 50
        $this->producto('Avena', stock: 10, minimo: 30);    // faltan 20
        $this->producto('Maíz', stock: 100, minimo: 10);    // sin alerta

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertViewHas('alertas', fn ($alertas) => $alertas->pluck('nombre')->all() === ['Sorgo', 'Avena', 'Soya'])
            ->assertSeeInOrder(['Sorgo', 'Faltan 50 kilogramos', 'Avena', 'Faltan 20 kilogramos', 'Soya', 'Faltan 5 kilogramos'])
            ->assertSee(e(route('movimientos.create', ['producto_id' => $sorgo->id, 'tipo' => 'entrada', 'volver' => 'dashboard'])), false)
            ->assertSee(e(route('movimientos.index', ['producto' => $sorgo->id])), false);
    }

    public function test_desde_el_dashboard_la_hoja_llega_con_entrada_y_vuelve_al_dashboard(): void
    {
        $sorgo = $this->producto('Sorgo', stock: 0, minimo: 50);

        $html = $this->actingAs($this->admin)
            ->get(route('movimientos.create', ['producto_id' => $sorgo->id, 'tipo' => 'entrada', 'volver' => 'dashboard']))
            ->assertOk()
            ->assertSee('name="volver" value="dashboard"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/value="entrada"[^>]*\schecked/', $html);

        $this->post(route('movimientos.store'), [
            'tipo' => 'entrada', 'producto_id' => $sorgo->id, 'cantidad' => '20', 'volver' => 'dashboard',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHas('aviso', '«Sorgo» sigue en stock bajo: 20 kilogramos (mínimo 50 kilogramos).');
    }

    public function test_sin_alertas_muestra_el_estado_vacio(): void
    {
        $this->producto('Maíz', stock: 100, minimo: 10);

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertViewHas('alertas', fn ($alertas) => $alertas->isEmpty())
            ->assertSee('Sin alertas por ahora');
    }

    public function test_el_dashboard_no_hace_una_consulta_por_producto(): void
    {
        $this->actingAs($this->admin);
        $this->producto('Uno', stock: 1, minimo: 10);

        $consultas = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('dashboard'))->assertOk();

            return count(DB::getQueryLog());
        };

        $conUno = $consultas();
        foreach (range(1, 8) as $numero) {
            $this->producto("Otro {$numero}", stock: 1, minimo: 10);
        }

        $this->assertSame($conUno, $consultas());
    }

    public function test_inicio_muestra_una_linea_discreta_con_los_productos_en_stock_bajo(): void
    {
        $this->producto('Maíz', stock: 100, minimo: 10);

        $this->actingAs($this->admin)->get(route('home'))->assertDontSee('en stock bajo');

        $this->producto('Soya', stock: 5, minimo: 10);
        $this->producto('Sorgo', stock: 0, minimo: 10);

        $this->get(route('home'))
            ->assertSee('2 productos en stock bajo')
            ->assertSee(e(route('productos.index', ['estado' => 'stock-bajo'])), false);
    }
}
