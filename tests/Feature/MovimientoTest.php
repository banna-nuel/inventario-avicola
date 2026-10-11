<?php

namespace Tests\Feature;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Tests\TestCase;

// Fase 5: movimientos de inventario (CU-04, CU-05) y reglas RN-01, RN-04 y RN-09
class MovimientoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $encargado;

    private Producto $maiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->encargado = User::factory()->encargado()->create(['name' => 'Ana Pérez']);
        $this->maiz = Producto::factory()->create([
            'nombre' => 'Maíz amarillo',
            'categoria_id' => Categoria::create(['nombre' => 'Alimento'])->id,
            'unidad_medida' => UnidadMedida::Kilogramos,
            'stock_minimo' => 50,
        ]);
        MovimientoInventario::registrar($this->maiz, 'entrada', 100, $this->admin);
    }

    private function registrar(string $tipo, string $cantidad, ?Producto $producto = null, ?User $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->encargado)
            ->from(route('movimientos.create'))
            ->post(route('movimientos.store'), [
                'tipo' => $tipo,
                'producto_id' => ($producto ?? $this->maiz)->id,
                'cantidad' => $cantidad,
            ]);
    }

    private function stock(): string
    {
        return $this->maiz->fresh()->stock_actual;
    }

    // ---- Registrar (CU-04 y CU-05) ----

    public function test_una_entrada_suma_al_stock_y_queda_en_el_historial(): void
    {
        $this->registrar('entrada', '20,5')
            ->assertRedirect(route('movimientos.index'))
            ->assertSessionHas('success', 'Entrada de 20,5 kilogramos de «Maíz amarillo» registrada. Stock: 120,5 kilogramos.');

        $this->assertSame('120.50', $this->stock());

        $movimiento = MovimientoInventario::latest('id')->first();
        $this->assertSame('entrada', $movimiento->tipo);
        $this->assertSame('20.50', $movimiento->cantidad);
        $this->assertSame($this->encargado->id, $movimiento->usuario_id);
        $this->assertNotNull($movimiento->fecha);

        $this->get(route('movimientos.index'))
            ->assertSeeInOrder(['Maíz amarillo', 'Entrada', 'Ana Pérez', '+', '20,5', 'kilogramos']);
    }

    public function test_una_salida_resta_del_stock(): void
    {
        $this->registrar('salida', '30')->assertSessionHasNoErrors();

        $this->assertSame('70.00', $this->stock());
    }

    public function test_se_puede_sacar_todo_el_stock(): void
    {
        $this->registrar('salida', '100')->assertSessionHasNoErrors();

        $this->assertSame('0.00', $this->stock());
    }

    // ---- RN-01: stock insuficiente ----

    public function test_rn01_no_se_registra_una_salida_mayor_al_stock(): void
    {
        $this->registrar('salida', '100.01')
            ->assertRedirect(route('movimientos.create'))
            ->assertSessionHasErrors(['cantidad' => 'No hay stock suficiente: «Maíz amarillo» tiene 100 kilogramos.']);

        $this->assertSame('100.00', $this->stock());
        $this->assertSame(1, MovimientoInventario::count());   // solo la entrada inicial
    }

    public function test_rn01_tambien_desde_el_modelo(): void
    {
        try {
            MovimientoInventario::registrar($this->maiz, 'salida', 500, $this->admin);
            $this->fail('Debía rechazar la salida');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('cantidad', $error->errors());
        }

        $this->assertSame('100.00', $this->stock());
    }

    // ---- Producto inactivo ----

    public function test_un_producto_inactivo_no_admite_movimientos(): void
    {
        $this->maiz->update(['activo' => false]);

        $this->registrar('entrada', '10')
            ->assertSessionHasErrors(['producto_id' => '«Maíz amarillo» está inactivo y no admite movimientos.']);

        $this->assertSame('100.00', $this->stock());
        $this->assertSame(1, MovimientoInventario::count());
    }

    public function test_la_hoja_solo_ofrece_productos_activos(): void
    {
        Producto::factory()->inactivo()->create(['nombre' => 'Sorgo descontinuado']);

        $html = $this->actingAs($this->encargado)->get(route('movimientos.create'))->assertOk()->getContent();

        // Solo el select de la hoja (el filtro del historial, detrás, sí lista los inactivos)
        preg_match('/<select\s+id="producto_id".*?<\/select>/s', $html, $select);
        $this->assertStringContainsString('Maíz amarillo', $select[0]);
        $this->assertStringNotContainsString('Sorgo descontinuado', $select[0]);
    }

    public function test_registrar_esta_deshabilitado_hasta_elegir_el_tipo(): void
    {
        // disabled="disabled" (ojo: las clases de Tailwind también contienen "disabled:")
        $botonDeshabilitado = '/<button[^>]*\sdisabled="disabled"[^>]*>\s*Registrar\s*</';

        $sinTipo = $this->actingAs($this->encargado)->get(route('movimientos.create'))->getContent();
        $this->assertMatchesRegularExpression($botonDeshabilitado, $sinTipo);
        $this->assertStringContainsString('Elige entrada o salida para registrar.', $sinTipo);

        // Con el tipo ya elegido (por ejemplo desde el dashboard) el botón está habilitado
        $conTipo = $this->get(route('movimientos.create', ['tipo' => 'entrada']))->getContent();
        $this->assertDoesNotMatchRegularExpression($botonDeshabilitado, $conTipo);
    }

    // ---- Validaciones ----

    public function test_validaciones_en_espanol(): void
    {
        $this->actingAs($this->encargado)
            ->post(route('movimientos.store'), ['tipo' => 'robo', 'producto_id' => 999, 'cantidad' => '0'])
            ->assertSessionHasErrors([
                'tipo' => 'Elige si es una entrada o una salida.',
                'producto_id' => 'Elige un producto de la lista.',
                'cantidad' => 'La cantidad debe ser mayor que 0.',
            ]);

        $this->post(route('movimientos.store'), [])
            ->assertSessionHasErrors([
                'tipo' => 'Elige si es una entrada o una salida.',
                'producto_id' => 'Elige un producto.',
                'cantidad' => 'Escribe la cantidad.',
            ]);

        $this->registrar('entrada', '-5')->assertSessionHasErrors(['cantidad' => 'La cantidad debe ser mayor que 0.']);
        $this->registrar('entrada', 'diez')->assertSessionHasErrors(['cantidad' => 'Escribe un número, por ejemplo 12,5.']);
        $this->registrar('entrada', '1,234')->assertSessionHasErrors(['cantidad' => 'Usa máximo 2 decimales.']);

        $this->assertSame(1, MovimientoInventario::count());
    }

    // ---- RN-04: transacción ----

    public function test_rn04_si_falla_guardar_el_movimiento_el_stock_no_cambia(): void
    {
        // El stock se actualiza ANTES de guardar el movimiento: si guardarlo falla,
        // solo la transacción puede devolver el stock a su valor anterior
        MovimientoInventario::creating(fn () => throw new RuntimeException('falla simulada'));

        try {
            MovimientoInventario::registrar($this->maiz, 'salida', 40, $this->admin);
            $this->fail('Debía fallar');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame('100.00', $this->stock());
        $this->assertSame(1, MovimientoInventario::count());
    }

    public function test_rn04_el_stock_siempre_es_la_suma_del_historial(): void
    {
        foreach ([['salida', '30'], ['entrada', '12,75'], ['salida', '50'], ['salida', '999'], ['entrada', '0,25']] as [$tipo, $cantidad]) {
            $this->registrar($tipo, $cantidad);   // la salida de 999 se rechaza (RN-01)
        }

        $entradas = MovimientoInventario::where('tipo', 'entrada')->sum('cantidad');
        $salidas = MovimientoInventario::where('tipo', 'salida')->sum('cantidad');

        $this->assertEquals(33.0, (float) $this->stock());
        $this->assertEquals((float) $this->stock(), round($entradas - $salidas, 2));
    }

    // ---- Alerta de stock bajo ----

    public function test_una_salida_que_deja_el_stock_bajo_el_minimo_muestra_la_alerta(): void
    {
        $this->registrar('salida', '60')
            ->assertSessionHas('aviso', '«Maíz amarillo» quedó en stock bajo: 40 kilogramos (mínimo 50 kilogramos).');

        $this->get(route('movimientos.index'))
            ->assertSee('role="alert"', false)
            ->assertSee('quedó en stock bajo');
    }

    public function test_sin_quedar_bajo_el_minimo_no_hay_alerta(): void
    {
        $this->registrar('salida', '50')->assertSessionMissing('aviso');   // queda justo en el mínimo
    }

    public function test_una_entrada_que_no_alcanza_el_minimo_avisa_que_sigue_bajo(): void
    {
        $this->registrar('salida', '90');
        $this->registrar('entrada', '10')
            ->assertSessionHas('aviso', '«Maíz amarillo» sigue en stock bajo: 20 kilogramos (mínimo 50 kilogramos).');
    }

    // ---- Hoja e historial ----

    public function test_desde_productos_la_hoja_llega_con_el_producto_elegido_y_vuelve_al_listado(): void
    {
        $this->actingAs($this->encargado)
            ->get(route('productos.index'))
            ->assertSee(e(route('movimientos.create', ['producto_id' => $this->maiz->id, 'volver' => 'productos'])), false);

        $html = $this->get(route('movimientos.create', ['producto_id' => $this->maiz->id, 'volver' => 'productos', 'tipo' => 'salida']))
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('<option value="'.$this->maiz->id.'" selected', false)
            ->assertSee('Stock actual: 100 kilogramos')
            ->assertSee('name="volver" value="productos"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/value="salida"[^>]*\schecked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="entrada"[^>]*\schecked/', $html);

        $this->post(route('movimientos.store'), [
            'tipo' => 'salida', 'producto_id' => $this->maiz->id, 'cantidad' => '5', 'volver' => 'productos',
        ])->assertRedirect(route('productos.index'));
    }

    public function test_volver_solo_acepta_claves_de_la_lista_y_nunca_una_url_externa(): void
    {
        $this->actingAs($this->encargado);

        foreach (['https://sitio-malicioso.com', '//sitio-malicioso.com', 'productos.index', 'home'] as $volver) {
            // Al guardar, un valor desconocido se ignora y vuelve al historial
            $this->post(route('movimientos.store'), [
                'tipo' => 'entrada', 'producto_id' => $this->maiz->id, 'cantidad' => '1', 'volver' => $volver,
            ])->assertRedirect(route('movimientos.index'));

            // Y la hoja no lo usa para el enlace de cerrar ni lo reenvía en el formulario
            $this->get(route('movimientos.create', ['volver' => $volver]))
                ->assertOk()
                ->assertDontSee('sitio-malicioso.com')
                ->assertDontSee('name="volver"', false);
        }
    }

    public function test_el_historial_va_del_mas_reciente_al_mas_antiguo_y_se_pagina(): void
    {
        foreach (range(1, 21) as $numero) {
            MovimientoInventario::registrar($this->maiz, 'entrada', $numero, $this->admin);
        }

        $this->actingAs($this->encargado);

        $this->get(route('movimientos.index'))
            ->assertSee('22 movimientos')
            ->assertSeeInOrder(['21', '20', '19'])
            ->assertSee('Página 1 de 2');

        $this->get(route('movimientos.index', ['page' => 2]))->assertSee('100');
    }

    public function test_sin_movimientos_muestra_el_estado_vacio(): void
    {
        MovimientoInventario::query()->delete();   // borrado masivo (sin eventos), solo para el test

        $this->actingAs($this->encargado)->get(route('movimientos.index'))
            ->assertSee('Aún no hay movimientos');
    }

    // ---- RN-09: inmutables ----

    public function test_rn09_un_movimiento_no_se_edita_ni_se_elimina(): void
    {
        $movimiento = MovimientoInventario::first();

        try {
            $movimiento->update(['cantidad' => 1]);
            $this->fail('No debía poder editarse');
        } catch (LogicException) {
        }

        try {
            $movimiento->delete();
            $this->fail('No debía poder eliminarse');
        } catch (LogicException) {
        }

        $this->assertSame('100.00', $movimiento->fresh()->cantidad);
    }

    // ---- Permisos ----

    public function test_los_dos_roles_registran_movimientos(): void
    {
        $this->registrar('salida', '10', usuario: $this->encargado)->assertSessionHasNoErrors();
        $this->registrar('salida', '10', usuario: $this->admin)->assertSessionHasNoErrors();

        $this->assertSame('80.00', $this->stock());
    }

    public function test_un_invitado_no_puede_registrar(): void
    {
        $this->post(route('movimientos.store'), ['tipo' => 'salida', 'producto_id' => $this->maiz->id, 'cantidad' => '10'])
            ->assertRedirect(route('login'));

        $this->assertSame('100.00', $this->stock());
    }

    public function test_una_cuenta_desactivada_no_puede_registrar(): void
    {
        $this->encargado->update(['activo' => false]);

        $this->registrar('salida', '10')->assertRedirect(route('login'));

        $this->assertSame('100.00', $this->stock());
    }
}
