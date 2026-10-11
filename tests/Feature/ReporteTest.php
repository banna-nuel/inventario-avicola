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

// Fase 8: reportes en pantalla (solo admin)
class ReporteTest extends TestCase
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

    private function producto(string $nombre, UnidadMedida $unidad, float $stock = 0, float $minimo = 10, ?Categoria $categoria = null): Producto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'categoria_id' => ($categoria ?? $this->alimento)->id,
            'unidad_medida' => $unidad,
            'stock_minimo' => $minimo,
        ]);

        if ($stock > 0) {
            MovimientoInventario::registrar($producto, 'entrada', $stock, $this->admin);
        }

        return $producto->fresh();
    }

    private function registrarA(string $hora, Producto $producto, string $tipo, float $cantidad): void
    {
        $this->travelTo(Carbon::parse($hora, 'America/Bogota'));
        MovimientoInventario::registrar($producto, $tipo, $cantidad, $this->admin);
    }

    private function contarConsultas(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();

        return count(DB::getQueryLog());
    }

    // ---- Permisos ----

    public function test_solo_el_admin_ve_los_reportes(): void
    {
        $rutas = ['reportes.index', 'reportes.stock-por-categoria', 'reportes.stock-bajo', 'reportes.mas-movidos'];

        $this->actingAs(User::factory()->encargado()->create());
        foreach ($rutas as $ruta) {
            $this->get(route($ruta))->assertForbidden();
        }

        $this->actingAs($this->admin);
        foreach ($rutas as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    public function test_el_indice_tiene_tres_tarjetas_de_acceso(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.index'))
            ->assertSee('href="'.route('reportes.stock-por-categoria').'"', false)
            ->assertSee('href="'.route('reportes.stock-bajo').'"', false)
            ->assertSee('href="'.route('reportes.mas-movidos').'"', false);
    }

    public function test_cada_reporte_tiene_imprimir_y_la_barra_no_se_imprime(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.stock-bajo'))
            ->assertSee('data-imprimir', false)
            ->assertSee('Imprimir')
            ->assertSee('Generado el');

        $html = $this->get(route('reportes.stock-bajo'))->getContent();
        $this->assertMatchesRegularExpression('/<nav aria-label="Navegación principal"[^>]*print:hidden/s', $html);
    }

    // ---- Stock por categoría ----

    public function test_stock_por_categoria_no_suma_unidades_distintas(): void
    {
        $this->producto('Maíz', UnidadMedida::Kilogramos, stock: 100, minimo: 10);
        $this->producto('Vitaminas', UnidadMedida::Libras, stock: 50, minimo: 60);   // en stock bajo
        $this->producto('Sorgo viejo', UnidadMedida::Kilogramos, stock: 30)->update(['activo' => false]);
        Categoria::create(['nombre' => 'Vacunas']);   // sin productos

        $respuesta = $this->actingAs($this->admin)->get(route('reportes.stock-por-categoria'))
            ->assertOk()
            ->assertSeeInOrder(['Alimento', '2 productos', 'Maíz', '100', 'kilogramos', 'Vitaminas', 'Stock bajo', '50', 'libras'])
            ->assertSeeInOrder(['Vacunas', '0 productos', 'Sin productos activos.'])
            ->assertDontSee('Sorgo viejo');   // solo productos activos

        // Ningún total que mezcle unidades: 100 kg + 50 lb (+ 30 kg del inactivo)
        foreach (['150', '180', '130'] as $sumaMezclada) {
            $respuesta->assertDontSee($sumaMezclada);
        }
    }

    public function test_stock_por_categoria_no_hace_una_consulta_por_categoria(): void
    {
        $this->actingAs($this->admin);
        $this->producto('Uno', UnidadMedida::Kilogramos, stock: 1);
        $antes = $this->contarConsultas(route('reportes.stock-por-categoria'));

        foreach (range(1, 6) as $numero) {
            $categoria = Categoria::create(['nombre' => "Categoría {$numero}"]);
            $this->producto("Producto {$numero}", UnidadMedida::Litros, stock: 1, categoria: $categoria);
        }

        $this->assertSame($antes, $this->contarConsultas(route('reportes.stock-por-categoria')));
    }

    // ---- Stock bajo ----

    public function test_stock_bajo_usa_la_misma_regla_y_orden_que_el_dashboard(): void
    {
        $this->producto('Soya', UnidadMedida::Kilogramos, stock: 5, minimo: 10);    // faltan 5
        $this->producto('Sorgo', UnidadMedida::Bultos, stock: 0, minimo: 50);       // faltan 50
        $this->producto('Avena', UnidadMedida::Libras, stock: 10, minimo: 30);      // faltan 20
        $this->producto('Maíz', UnidadMedida::Kilogramos, stock: 100, minimo: 10);  // sin alerta
        $this->producto('Trigo', UnidadMedida::Kilogramos, stock: 1, minimo: 99)->update(['activo' => false]);

        $this->actingAs($this->admin);

        $reporte = $this->get(route('reportes.stock-bajo'))
            ->assertSeeInOrder(['Sorgo', '50', 'bultos', 'Avena', '20', 'libras', 'Soya', '5', 'kilogramos'])
            ->assertDontSee('Trigo')
            ->assertDontSee('Maíz')
            ->viewData('productos')->pluck('nombre')->all();

        $dashboard = $this->get(route('dashboard'))->viewData('alertas')->pluck('nombre')->all();

        $this->assertSame(['Sorgo', 'Avena', 'Soya'], $reporte);
        $this->assertSame($dashboard, $reporte);
    }

    public function test_stock_bajo_vacio_y_sin_n_mas_1(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.stock-bajo'))->assertSee('Sin productos en stock bajo');

        $this->producto('Uno', UnidadMedida::Kilogramos, stock: 1, minimo: 10);
        $antes = $this->contarConsultas(route('reportes.stock-bajo'));
        foreach (range(1, 6) as $numero) {
            $this->producto("Otro {$numero}", UnidadMedida::Dosis, stock: 1, minimo: 10, categoria: Categoria::create(['nombre' => "C{$numero}"]));
        }

        $this->assertSame($antes, $this->contarConsultas(route('reportes.stock-bajo')));
    }

    // ---- Más movidos ----

    public function test_mas_movidos_cuenta_movimientos_y_suma_entradas_y_salidas_en_su_unidad(): void
    {
        $maiz = $this->producto('Maíz', UnidadMedida::Kilogramos);
        $vacuna = $this->producto('Vacuna', UnidadMedida::Dosis);

        $this->registrarA('2026-10-02 08:00:00', $maiz, 'entrada', 100);
        $this->registrarA('2026-10-03 08:00:00', $maiz, 'salida', 30);
        $this->registrarA('2026-10-04 08:00:00', $maiz, 'salida', 20.5);
        $this->registrarA('2026-10-05 08:00:00', $vacuna, 'entrada', 500);
        $this->travelTo(Carbon::parse('2026-10-11 10:00:00', 'America/Bogota'));

        $this->actingAs($this->admin)
            ->get(route('reportes.mas-movidos', ['desde' => '2026-10-01', 'hasta' => '2026-10-10']))
            ->assertOk()
            ->assertViewHas('productos', function ($productos) {
                $maiz = $productos->first();

                return $productos->pluck('nombre')->all() === ['Maíz', 'Vacuna']
                    && $maiz->movimientos_count === 3
                    && (float) $maiz->entradas === 100.0
                    && (float) $maiz->salidas === 50.5;
            })
            ->assertSeeInOrder(['Maíz', 'kilogramos', '3', '100', '50,5', 'Vacuna', 'dosis', '1', '500', '0']);
    }

    public function test_mas_movidos_por_defecto_son_los_ultimos_30_dias_en_hora_de_bogota(): void
    {
        $producto = $this->producto('Maíz', UnidadMedida::Kilogramos);

        $this->registrarA('2026-09-11 23:59:59', $producto, 'entrada', 1);   // hace 31 días: fuera
        $this->registrarA('2026-09-12 00:00:00', $producto, 'entrada', 1);   // primer instante del rango
        $this->registrarA('2026-10-11 23:59:59', $producto, 'entrada', 1);   // último instante de hoy
        $this->travelTo(Carbon::parse('2026-10-11 23:59:59', 'America/Bogota'));

        $this->actingAs($this->admin)->get(route('reportes.mas-movidos'))
            ->assertViewHas('desde', fn ($desde) => $desde->format('Y-m-d') === '2026-09-12')
            ->assertViewHas('hasta', fn ($hasta) => $hasta->format('Y-m-d') === '2026-10-11')
            ->assertViewHas('productos', fn ($productos) => $productos->first()->movimientos_count === 2);
    }

    public function test_mas_movidos_rango_inclusivo_en_ambos_bordes(): void
    {
        $producto = $this->producto('Maíz', UnidadMedida::Kilogramos);
        $this->registrarA('2026-10-04 23:59:59', $producto, 'entrada', 1);   // fuera (día anterior)
        $this->registrarA('2026-10-05 00:00:00', $producto, 'entrada', 1);   // dentro
        $this->registrarA('2026-10-06 23:59:59', $producto, 'entrada', 1);   // dentro
        $this->registrarA('2026-10-07 00:00:00', $producto, 'entrada', 1);   // fuera (día siguiente)

        $this->actingAs($this->admin)
            ->get(route('reportes.mas-movidos', ['desde' => '2026-10-05', 'hasta' => '2026-10-06']))
            ->assertViewHas('productos', fn ($productos) => $productos->first()->movimientos_count === 2);

        // Un solo día
        $this->get(route('reportes.mas-movidos', ['desde' => '2026-10-07', 'hasta' => '2026-10-07']))
            ->assertViewHas('productos', fn ($productos) => $productos->first()->movimientos_count === 1);
    }

    public function test_mas_movidos_incluye_inactivos_con_su_badge_y_enlaza_al_historial_del_rango(): void
    {
        $sorgo = $this->producto('Sorgo', UnidadMedida::Bultos);
        $this->registrarA('2026-10-05 08:00:00', $sorgo, 'entrada', 10);
        $this->registrarA('2026-10-06 08:00:00', $sorgo, 'salida', 10);
        $sorgo->update(['activo' => false]);

        $this->actingAs($this->admin)
            ->get(route('reportes.mas-movidos', ['desde' => '2026-10-01', 'hasta' => '2026-10-10']))
            ->assertSee('Sorgo')
            ->assertSee('Inactivo')
            ->assertSee(e(route('movimientos.index', ['producto' => $sorgo->id, 'desde' => '2026-10-01', 'hasta' => '2026-10-10'])), false);
    }

    public function test_mas_movidos_muestra_solo_el_top_10(): void
    {
        foreach (range(1, 12) as $numero) {
            $producto = $this->producto(sprintf('Producto %02d', $numero), UnidadMedida::Unidades);
            // El producto N tiene N movimientos: el top son del 12 al 3
            foreach (range(1, $numero) as $vez) {
                MovimientoInventario::registrar($producto, 'entrada', 1, $this->admin);
            }
        }

        $this->actingAs($this->admin)->get(route('reportes.mas-movidos'))
            ->assertViewHas('productos', fn ($productos) => $productos->count() === 10
                && $productos->first()->nombre === 'Producto 12'
                && $productos->last()->nombre === 'Producto 03')
            ->assertDontSee('Producto 02')
            ->assertDontSee('Producto 01');
    }

    public function test_mas_movidos_rango_invalido_vuelve_con_el_error(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('reportes.mas-movidos', ['desde' => '2026-10-10', 'hasta' => '2026-10-01']))
            ->assertRedirect(route('reportes.mas-movidos'))
            ->assertSessionHasErrors(['hasta' => '«Hasta» no puede ser anterior a «Desde».']);

        $this->get(route('reportes.mas-movidos', ['desde' => '2026-13-01']))
            ->assertSessionHasErrors(['desde' => 'Escribe una fecha válida.']);
    }

    public function test_mas_movidos_no_hace_una_consulta_por_producto(): void
    {
        $this->actingAs($this->admin);
        $uno = $this->producto('Uno', UnidadMedida::Kilogramos, stock: 5);
        MovimientoInventario::registrar($uno, 'salida', 1, $this->admin);
        $antes = $this->contarConsultas(route('reportes.mas-movidos'));

        foreach (range(1, 8) as $numero) {
            $otro = $this->producto("Otro {$numero}", UnidadMedida::Litros, stock: 5, categoria: Categoria::create(['nombre' => "C{$numero}"]));
            MovimientoInventario::registrar($otro, 'salida', 1, $this->admin);
        }

        $this->assertSame($antes, $this->contarConsultas(route('reportes.mas-movidos')));
    }
}
