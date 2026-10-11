<?php

namespace Tests\Feature;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

// Fase 4: productos (CU-02, CU-03, CU-06). Los permisos por rol están también en RutasTest.
class ProductoTest extends TestCase
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

    // Producto con stock: el stock entra con un movimiento, como en la app
    private function producto(string $nombre, float $stock = 0, float $minimo = 10, array $extra = []): Producto
    {
        // $extra va primero: con "+" gana la clave de la izquierda
        $producto = Producto::factory()->create($extra + [
            'nombre' => $nombre,
            'categoria_id' => $this->alimento->id,
            'unidad_medida' => UnidadMedida::Kilogramos,
            'stock_minimo' => $minimo,
        ]);

        if ($stock > 0) {
            MovimientoInventario::registrar($producto, 'entrada', $stock, $this->admin);
        }

        return $producto->fresh();
    }

    private function datosValidos(array $cambios = []): array
    {
        return $cambios + [
            'nombre' => 'Maíz amarillo',
            'categoria_id' => $this->alimento->id,
            'unidad_medida' => 'kilogramos',
            'stock_minimo' => '50',
            'stock_inicial' => '',
        ];
    }

    // ---- Listado ----

    public function test_el_listado_muestra_stock_unidad_categoria_y_badge_de_stock_bajo(): void
    {
        $this->producto('Maíz amarillo', stock: 120.5, minimo: 50);
        $this->producto('Soya', stock: 8, minimo: 10);

        $this->actingAs($this->admin)->get(route('productos.index'))
            ->assertOk()
            ->assertSee('2 productos')
            ->assertSeeInOrder(['Maíz amarillo', '120,5', 'kilogramos', 'Soya', 'Stock bajo', '8', 'kilogramos'])
            ->assertSee('Alimento')
            ->assertSee('Editar Soya');
    }

    public function test_por_defecto_se_ocultan_los_inactivos(): void
    {
        $this->producto('Maíz');
        $this->producto('Sorgo', extra: ['activo' => false]);

        $this->actingAs($this->admin)->get(route('productos.index'))
            ->assertSee('Maíz')
            ->assertDontSee('Sorgo');

        $this->get(route('productos.index', ['estado' => 'inactivos']))
            ->assertSee('Sorgo')
            ->assertSee('Inactivo')
            ->assertDontSee('Maíz');

        $this->get(route('productos.index', ['estado' => 'todos']))
            ->assertSee('Maíz')
            ->assertSee('Sorgo');
    }

    public function test_filtro_de_stock_bajo_y_por_categoria_y_busqueda(): void
    {
        $vacunas = Categoria::create(['nombre' => 'Vacunas']);
        $this->producto('Maíz amarillo', stock: 100, minimo: 50);
        $this->producto('Maíz blanco', stock: 5, minimo: 50);
        $this->producto('Newcastle', extra: ['categoria_id' => $vacunas->id]);

        $this->actingAs($this->admin);

        $this->get(route('productos.index', ['estado' => 'stock-bajo']))
            ->assertSee('Maíz blanco')
            ->assertDontSee('Maíz amarillo');

        $this->get(route('productos.index', ['categoria' => $vacunas->id]))
            ->assertSee('Newcastle')
            ->assertDontSee('Maíz blanco');

        $this->get(route('productos.index', ['q' => 'maíz']))
            ->assertSee('value="maíz"', false)
            ->assertSee('Maíz amarillo')
            ->assertSee('Maíz blanco')
            ->assertDontSee('Newcastle');

        $this->get(route('productos.index', ['q' => 'trigo']))
            ->assertSee('Sin resultados')
            ->assertSee('Quitar filtros');
    }

    public function test_filtros_mal_escritos_en_la_url_se_ignoran(): void
    {
        $this->producto('Maíz');

        $this->actingAs($this->admin)
            ->get('/productos?estado[]=x&q[]=y&categoria=abc')
            ->assertOk()
            ->assertSee('Maíz');
    }

    public function test_el_listado_se_pagina_de_15_en_15(): void
    {
        foreach (range(1, 17) as $numero) {
            $this->producto(sprintf('Producto %02d', $numero));
        }

        $this->actingAs($this->admin);

        $this->get(route('productos.index'))
            ->assertSee('17 productos')
            ->assertSee('Producto 15')
            ->assertDontSee('Producto 16')
            ->assertSee('Página 1 de 2');

        $this->get(route('productos.index', ['page' => 2]))
            ->assertSee('Producto 17');
    }

    public function test_el_listado_no_hace_una_consulta_por_producto(): void
    {
        $this->actingAs($this->admin);
        $this->producto('Uno');

        $consultas = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('productos.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $conUno = $consultas();
        foreach (range(1, 10) as $numero) {
            $this->producto("Otro {$numero}");
        }

        $this->assertSame($conUno, $consultas());
    }

    public function test_el_encargado_ve_el_listado_sin_acciones_de_administrador(): void
    {
        $this->producto('Maíz', stock: 3, minimo: 10);

        $this->actingAs(User::factory()->encargado()->create())
            ->get(route('productos.index'))
            ->assertOk()
            ->assertSee('Maíz')
            ->assertSee('Stock bajo')
            ->assertDontSee('Nuevo producto')
            ->assertDontSee('Editar Maíz');
    }

    // ---- Crear (CU-02) ----

    public function test_crear_abre_la_hoja_con_los_selects_encima_del_listado(): void
    {
        $this->producto('Maíz');

        $this->actingAs($this->admin)->get(route('productos.create'))
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('Nuevo producto')
            ->assertSee('<select', false)
            ->assertSee('Dosis')
            ->assertSee('Maíz');   // el listado sigue detrás
    }

    public function test_crear_un_producto_sin_stock_inicial(): void
    {
        $this->actingAs($this->admin)
            ->post(route('productos.store'), $this->datosValidos())
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto «Maíz amarillo» creado.');

        $producto = Producto::firstWhere('nombre', 'Maíz amarillo');
        $this->assertSame(UnidadMedida::Kilogramos, $producto->unidad_medida);
        $this->assertSame('0.00', $producto->stock_actual);
        $this->assertSame('50.00', $producto->stock_minimo);
        $this->assertSame(0, $producto->movimientos()->count());
    }

    public function test_el_stock_inicial_entra_como_un_movimiento_de_entrada(): void
    {
        $this->actingAs($this->admin)
            ->post(route('productos.store'), $this->datosValidos(['stock_inicial' => '120,5']))
            ->assertSessionHasNoErrors();

        $producto = Producto::firstWhere('nombre', 'Maíz amarillo');
        $this->assertSame('120.50', $producto->stock_actual);

        $movimiento = $producto->movimientos()->sole();
        $this->assertSame('entrada', $movimiento->tipo);
        $this->assertSame('120.50', $movimiento->cantidad);
        $this->assertSame($this->admin->id, $movimiento->usuario_id);
    }

    public function test_si_falla_el_movimiento_inicial_no_se_crea_el_producto(): void
    {
        // Simulamos un error al guardar el movimiento: la transacción deshace todo
        MovimientoInventario::creating(fn () => throw new RuntimeException('falla simulada'));

        $this->actingAs($this->admin)
            ->post(route('productos.store'), $this->datosValidos(['stock_inicial' => '10']))
            ->assertServerError();

        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_no_se_puede_fijar_el_stock_actual_desde_el_formulario(): void
    {
        $this->actingAs($this->admin)
            ->post(route('productos.store'), $this->datosValidos(['stock_actual' => '999']));

        $this->assertSame('0.00', Producto::firstWhere('nombre', 'Maíz amarillo')->stock_actual);
    }

    public function test_validaciones_al_crear_en_espanol(): void
    {
        $this->producto('Maíz amarillo');

        $this->actingAs($this->admin)
            ->post(route('productos.store'), [
                'nombre' => 'Maíz amarillo',
                'categoria_id' => 999,
                'unidad_medida' => 'lb',
                'stock_minimo' => '-1',
                'stock_inicial' => '1,234',
            ])
            ->assertSessionHasErrors([
                'nombre' => 'Ya existe un producto con ese nombre.',
                'categoria_id' => 'Elige una categoría de la lista.',
                'unidad_medida' => 'Elige una unidad de la lista.',
                'stock_minimo' => 'No puede ser negativo.',
                'stock_inicial' => 'Usa máximo 2 decimales.',
            ]);

        $this->post(route('productos.store'), ['nombre' => '  ', 'stock_minimo' => ''])
            ->assertSessionHasErrors([
                'nombre' => 'Escribe el nombre del producto.',
                'categoria_id' => 'Elige una categoría.',
                'unidad_medida' => 'Elige la unidad de medida.',
                'stock_minimo' => 'Escribe el stock mínimo (puede ser 0).',
            ]);

        $this->assertDatabaseCount('productos', 1);
    }

    public function test_si_la_validacion_falla_la_hoja_conserva_lo_escrito(): void
    {
        $this->actingAs($this->admin)
            ->from(route('productos.create'))
            ->post(route('productos.store'), $this->datosValidos(['stock_minimo' => 'diez']))
            ->assertRedirect(route('productos.create'));

        $this->get(route('productos.create'))
            ->assertSee('>Maíz amarillo</textarea>', false)
            ->assertSee('<option value="kilogramos" selected', false)
            ->assertSee('Escribe un número, por ejemplo 12,5.');
    }

    // ---- Editar (CU-03) ----

    public function test_editar_muestra_el_stock_actual_como_solo_lectura(): void
    {
        $producto = $this->producto('Maíz', stock: 120);

        $this->actingAs($this->admin)->get(route('productos.edit', $producto))
            ->assertOk()
            ->assertSee('Editar producto')
            ->assertSee('Stock actual:')
            ->assertSee('120 kilogramos')
            ->assertDontSee('name="stock_actual"', false)
            ->assertDontSee('name="stock_inicial"', false);
    }

    public function test_actualizar_un_producto(): void
    {
        $producto = $this->producto('Maíz', stock: 20, minimo: 10);
        $vacunas = Categoria::create(['nombre' => 'Vacunas']);

        $this->actingAs($this->admin)
            ->put(route('productos.update', $producto), [
                'nombre' => 'Maíz blanco',
                'categoria_id' => $vacunas->id,
                'stock_minimo' => '25',
                'stock_actual' => '999',   // se ignora
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto «Maíz blanco» actualizado.');

        $producto->refresh();
        $this->assertSame('Maíz blanco', $producto->nombre);
        $this->assertSame($vacunas->id, $producto->categoria_id);
        $this->assertSame('25.00', $producto->stock_minimo);
        $this->assertSame('20.00', $producto->stock_actual);
    }

    public function test_al_editar_no_se_acepta_stock_inicial(): void
    {
        $producto = $this->producto('Maíz');

        $this->actingAs($this->admin)
            ->put(route('productos.update', $producto), $this->datosValidos(['nombre' => 'Maíz', 'stock_inicial' => '50']))
            ->assertSessionHasErrors(['stock_inicial' => 'El stock solo cambia con movimientos de entrada o salida.']);
    }

    public function test_con_movimientos_la_unidad_ya_no_cambia(): void
    {
        $conHistorial = $this->producto('Maíz', stock: 10);
        $sinHistorial = $this->producto('Soya');

        $this->actingAs($this->admin);

        $this->get(route('productos.edit', $conHistorial))
            ->assertSee('Unidad fija: el producto ya tiene movimientos')
            ->assertDontSee('name="unidad_medida"', false);

        $this->put(route('productos.update', $conHistorial), $this->datosValidos(['nombre' => 'Maíz', 'unidad_medida' => 'libras']))
            ->assertSessionHasErrors(['unidad_medida' => 'La unidad no se puede cambiar porque el producto ya tiene movimientos.']);

        $this->put(route('productos.update', $sinHistorial), $this->datosValidos(['nombre' => 'Soya', 'unidad_medida' => 'libras']))
            ->assertSessionHasNoErrors();

        $this->assertSame(UnidadMedida::Kilogramos, $conHistorial->fresh()->unidad_medida);
        $this->assertSame(UnidadMedida::Libras, $sinHistorial->fresh()->unidad_medida);
    }

    public function test_los_filtros_se_conservan_al_abrir_y_cerrar_la_hoja(): void
    {
        $producto = $this->producto('Maíz');

        $this->actingAs($this->admin)
            ->get(route('productos.index', ['q' => 'ma', 'estado' => 'todos']))
            // e(): en el HTML el "&" de la URL va escapado como "&amp;"
            ->assertSee('href="'.e(route('productos.edit', ['producto' => $producto, 'q' => 'ma', 'estado' => 'todos'])).'"', false);

        $this->put(route('productos.update', ['producto' => $producto, 'q' => 'ma', 'estado' => 'todos']), $this->datosValidos(['nombre' => 'Maíz']))
            ->assertRedirect(route('productos.index', ['q' => 'ma', 'estado' => 'todos']));
    }

    // ---- Desactivar (RN-03) ----

    public function test_desactivar_y_volver_a_activar_un_producto(): void
    {
        $producto = $this->producto('Maíz', stock: 10);

        $this->actingAs($this->admin)
            ->patch(route('productos.cambiar-estado', $producto), ['activo' => '0'])
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto «Maíz» desactivado: ya no admite movimientos y su historial se conserva.');

        $this->assertFalse($producto->fresh()->activo);
        $this->assertSame(1, $producto->movimientos()->count());   // el historial sigue ahí

        $this->patch(route('productos.cambiar-estado', $producto), ['activo' => '1'])
            ->assertSessionHas('success', 'Producto «Maíz» activado.');

        $this->assertTrue($producto->fresh()->activo);
    }

    public function test_la_hoja_de_editar_ofrece_desactivar_con_confirmacion(): void
    {
        $producto = $this->producto('Maíz');

        $this->actingAs($this->admin)->get(route('productos.edit', $producto))
            ->assertSee('Desactivar producto')
            ->assertSee('data-confirmar="¿Desactivar «Maíz»?', false);
    }

    // ---- RN-02 ----

    public function test_rn02_el_scope_y_el_metodo_de_stock_bajo_coinciden(): void
    {
        $casos = [
            'Bajo' => [5, 10, true],
            'Justo en el mínimo' => [10, 10, false],
            'Sobre el mínimo' => [11, 10, false],
            'Sin mínimo' => [0, 0, false],
            'Decimales' => [9.99, 10, true],
        ];

        foreach ($casos as $nombre => [$stock, $minimo, $esperado]) {
            $producto = $this->producto($nombre, $stock, $minimo);
            $this->assertSame($esperado, $producto->enStockBajo(), $nombre);
        }

        $this->assertEqualsCanonicalizing(
            ['Bajo', 'Decimales'],
            Producto::stockBajo()->pluck('nombre')->all()
        );
    }

    public function test_la_unidad_se_escribe_en_singular_o_plural(): void
    {
        $this->assertSame('1 libra', UnidadMedida::Libras->cantidad(1));
        $this->assertSame('1.250,5 kilogramos', UnidadMedida::Kilogramos->cantidad('1250.50'));
        $this->assertSame('0 dosis', UnidadMedida::Dosis->cantidad(0));
        $this->assertSame('3 bultos', UnidadMedida::Bultos->cantidad('3.00'));
    }
}
