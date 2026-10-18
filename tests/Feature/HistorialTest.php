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

// Fase 6: historial de movimientos con filtros (producto, tipo, desde/hasta en hora de Bogotá)
class HistorialTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;

    private Producto $maiz;

    private Producto $soya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encargado = User::factory()->encargado()->create();
        $categoria = Categoria::create(['nombre' => 'Alimento']);
        [$this->maiz, $this->soya] = collect(['Maíz amarillo', 'Soya'])->map(fn ($nombre) => Producto::factory()->create([
            'nombre' => $nombre,
            'categoria_id' => $categoria->id,
            'unidad_medida' => UnidadMedida::Kilogramos,
        ]))->all();

        // Cuatro movimientos en momentos conocidos (hora de Bogotá)
        $this->registrarA('2026-10-01 08:00:00', $this->maiz, 'entrada', 100);
        $this->registrarA('2026-10-05 23:59:59', $this->maiz, 'salida', 10);
        $this->registrarA('2026-10-06 00:00:00', $this->soya, 'entrada', 50);
        $this->registrarA('2026-10-10 12:00:00', $this->soya, 'salida', 5);
        $this->travelBack();
    }

    private function registrarA(string $hora, Producto $producto, string $tipo, float $cantidad): void
    {
        $this->travelTo(Carbon::parse($hora, 'America/Bogota'));
        MovimientoInventario::registrar($producto, $tipo, $cantidad, $this->encargado);
    }

    // Cuántos movimientos muestra el historial con estos filtros
    private function total(array $filtros): int
    {
        $respuesta = $this->actingAs($this->encargado)->get(route('movimientos.index', $filtros))->assertOk();

        return $respuesta->viewData('movimientos')->total();
    }

    public function test_filtra_por_producto_y_por_tipo(): void
    {
        $this->assertSame(4, $this->total([]));
        $this->assertSame(2, $this->total(['producto' => $this->maiz->id]));
        $this->assertSame(2, $this->total(['tipo' => 'salida']));
        $this->assertSame(1, $this->total(['producto' => $this->soya->id, 'tipo' => 'entrada']));
    }

    public function test_las_fechas_son_inclusivas_en_hora_de_bogota(): void
    {
        $this->assertSame(2, $this->total(['desde' => '2026-10-06']));                       // desde las 00:00:00
        $this->assertSame(2, $this->total(['hasta' => '2026-10-05']));                       // hasta las 23:59:59
        $this->assertSame(1, $this->total(['desde' => '2026-10-05', 'hasta' => '2026-10-05']));
        $this->assertSame(0, $this->total(['desde' => '2026-10-11']));
        $this->assertSame(4, $this->total(['desde' => '2026-10-01', 'hasta' => '2026-10-10']));
    }

    public function test_filtros_invalidos_vuelven_al_historial_con_el_error(): void
    {
        $this->actingAs($this->encargado);

        $this->get(route('movimientos.index', ['desde' => '2026-02-30']))
            ->assertRedirect(route('movimientos.index'))
            ->assertSessionHasErrors(['desde' => 'Escribe una fecha válida.']);

        $this->get(route('movimientos.index', ['desde' => '2026-10-10', 'hasta' => '2026-10-01']))
            ->assertSessionHasErrors(['hasta' => '«Hasta» no puede ser anterior a «Desde».']);

        $this->get(route('movimientos.index', ['producto' => 999, 'tipo' => 'robo']))
            ->assertSessionHasErrors([
                'producto' => 'Elige un producto de la lista.',
                'tipo' => 'Elige entrada o salida.',
            ]);

        // Al volver, el panel de filtros está abierto y muestra el error junto al campo
        $this->get(route('movimientos.index', ['desde' => 'ayer']));
        $html = $this->get(route('movimientos.index'))
            ->assertSee('Escribe una fecha válida.')
            ->getContent();
        $this->assertMatchesRegularExpression('/<details[^>]*\sopen/', $html);
    }

    public function test_la_paginacion_conserva_los_filtros_y_hay_limpiar_filtros(): void
    {
        foreach (range(1, 25) as $numero) {
            MovimientoInventario::registrar($this->maiz, 'entrada', 1, $this->encargado);
        }

        $this->actingAs($this->encargado);

        $this->get(route('movimientos.index', ['producto' => $this->maiz->id]))
            ->assertSee('27 movimientos')
            ->assertSee('Historial de Maíz amarillo')
            ->assertSee(e(route('movimientos.index', ['producto' => $this->maiz->id, 'page' => 2])), false)
            ->assertSee('Limpiar filtros');

        $this->get(route('movimientos.index'))->assertDontSee('Limpiar filtros');
    }

    public function test_cada_producto_enlaza_a_su_historial(): void
    {
        $enlace = e(route('movimientos.index', ['producto' => $this->maiz->id]));

        $this->actingAs($this->encargado)->get(route('productos.index'))->assertSee($enlace, false);
        $this->get(route('movimientos.index'))->assertSee($enlace, false);
    }

    public function test_el_boton_mas_lleva_el_producto_del_filtro_a_la_hoja(): void
    {
        $this->actingAs($this->encargado)
            ->get(route('movimientos.index', ['producto' => $this->soya->id]))
            ->assertSee(e(route('movimientos.create', ['producto' => $this->soya->id])), false);

        $this->get(route('movimientos.create', ['producto' => $this->soya->id]))
            ->assertSee('<option value="'.$this->soya->id.'" selected', false)
            // al cerrar, vuelve al historial con el mismo filtro
            ->assertSee('href="'.e(route('movimientos.index', ['producto' => $this->soya->id])).'"', false);
    }

    public function test_el_historial_no_hace_una_consulta_por_movimiento(): void
    {
        $this->actingAs($this->encargado);

        $consultas = function (array $filtros) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('movimientos.index', $filtros))->assertOk();

            return count(DB::getQueryLog());
        };

        $antes = $consultas(['tipo' => 'entrada']);

        foreach (range(1, 10) as $numero) {
            $otro = Producto::factory()->create(['categoria_id' => $this->maiz->categoria_id]);
            MovimientoInventario::registrar($otro, 'entrada', 1, User::factory()->create());
        }

        $this->assertSame($antes, $consultas(['tipo' => 'entrada']));
    }
}
