<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Fase 3: CRUD de categorías (solo admin). Los permisos del encargado están en PermisosTest.
class CategoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function crearProducto(Categoria $categoria, string $nombre = 'Maíz amarillo'): Producto
    {
        return Producto::create([
            'nombre' => $nombre,
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'kg',
            'stock_minimo' => 10,
        ]);
    }

    public function test_el_listado_muestra_las_categorias_en_orden_con_su_numero_de_productos(): void
    {
        $vacunas = Categoria::create(['nombre' => 'Vacunas']);
        $alimento = Categoria::create(['nombre' => 'Alimento']);
        $this->crearProducto($alimento, 'Maíz');
        $this->crearProducto($alimento, 'Soya');

        $this->actingAs($this->admin)->get('/categorias')
            ->assertOk()
            ->assertSeeInOrder(['Alimento', 'Vacunas'])
            ->assertSee('2 categorías')
            ->assertSee('Editar Alimento')
            ->assertSee('Eliminar Vacunas');

        $this->assertSame(2, Categoria::withCount('productos')->find($alimento->id)->productos_count);
        $this->assertSame(0, Categoria::withCount('productos')->find($vacunas->id)->productos_count);
    }

    public function test_sin_categorias_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->admin)->get('/categorias')
            ->assertOk()
            ->assertSee('Aún no hay categorías')
            ->assertSee('0 categorías');
    }

    public function test_crear_abre_la_hoja_encima_del_listado(): void
    {
        Categoria::create(['nombre' => 'Alimento']);

        $this->actingAs($this->admin)->get('/categorias/create')
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('Nueva categoría')
            ->assertSee('Alimento')                 // el listado sigue detrás
            ->assertSee('inert', false);            // y queda inactivo mientras la hoja está abierta
    }

    public function test_guardar_una_categoria_nueva(): void
    {
        $this->actingAs($this->admin)
            ->post('/categorias', ['nombre' => 'Vacunas'])
            ->assertRedirect('/categorias')
            ->assertSessionHas('success', 'Categoría «Vacunas» creada.');

        $this->assertDatabaseHas('categorias', ['nombre' => 'Vacunas']);
    }

    public function test_el_nombre_se_guarda_sin_saltos_de_linea_ni_espacios_de_mas(): void
    {
        $this->actingAs($this->admin)
            ->post('/categorias', ['nombre' => "  Alimento \n   balanceado  "]);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Alimento balanceado']);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        $this->actingAs($this->admin)
            ->from('/categorias/create')
            ->post('/categorias', ['nombre' => "   \n  "])
            ->assertRedirect('/categorias/create')
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre de la categoría.']);

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_el_nombre_no_puede_pasar_de_100_caracteres(): void
    {
        $this->actingAs($this->admin)
            ->post('/categorias', ['nombre' => str_repeat('a', 101)])
            ->assertSessionHasErrors(['nombre' => 'El nombre no puede tener más de 100 caracteres.']);
    }

    public function test_no_se_repiten_nombres(): void
    {
        Categoria::create(['nombre' => 'Alimento']);

        $this->actingAs($this->admin)
            ->post('/categorias', ['nombre' => 'Alimento'])
            ->assertSessionHasErrors(['nombre' => 'Ya existe una categoría con ese nombre.']);

        $this->assertDatabaseCount('categorias', 1);
    }

    public function test_si_la_validacion_falla_la_hoja_conserva_lo_escrito_y_muestra_el_error(): void
    {
        Categoria::create(['nombre' => 'Alimento']);

        $this->actingAs($this->admin)
            ->from('/categorias/create')
            ->post('/categorias', ['nombre' => 'Alimento']);

        $this->get('/categorias/create')
            ->assertSee('>Alimento</textarea>', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('Ya existe una categoría con ese nombre.');
    }

    public function test_editar_muestra_el_nombre_actual_y_cuantos_productos_la_usan(): void
    {
        $alimento = Categoria::create(['nombre' => 'Alimento']);
        $this->crearProducto($alimento);

        $this->actingAs($this->admin)->get("/categorias/{$alimento->id}/edit")
            ->assertOk()
            ->assertSee('Editar categoría')
            ->assertSee('>Alimento</textarea>', false)
            ->assertSee('1 producto usa esta categoría');
    }

    public function test_actualizar_cambia_el_nombre_sin_crear_otra_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Alimento']);

        $this->actingAs($this->admin)
            ->put("/categorias/{$categoria->id}", ['nombre' => 'Alimento balanceado'])
            ->assertRedirect('/categorias')
            ->assertSessionHas('success', 'Categoría «Alimento balanceado» actualizada.');

        $this->assertDatabaseCount('categorias', 1);
        $this->assertSame('Alimento balanceado', $categoria->fresh()->nombre);
    }

    public function test_actualizar_con_su_mismo_nombre_esta_permitido(): void
    {
        $categoria = Categoria::create(['nombre' => 'Alimento']);

        $this->actingAs($this->admin)
            ->put("/categorias/{$categoria->id}", ['nombre' => 'Alimento'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/categorias');
    }

    public function test_actualizar_con_el_nombre_de_otra_categoria_falla(): void
    {
        Categoria::create(['nombre' => 'Alimento']);
        $vacunas = Categoria::create(['nombre' => 'Vacunas']);

        $this->actingAs($this->admin)
            ->put("/categorias/{$vacunas->id}", ['nombre' => 'Alimento'])
            ->assertSessionHasErrors(['nombre' => 'Ya existe una categoría con ese nombre.']);

        $this->assertSame('Vacunas', $vacunas->fresh()->nombre);
    }

    public function test_eliminar_una_categoria_sin_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Vacunas']);

        $this->actingAs($this->admin)
            ->delete("/categorias/{$categoria->id}")
            ->assertRedirect('/categorias')
            ->assertSessionHas('success', 'Categoría «Vacunas» eliminada.');

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_rn03_no_se_elimina_una_categoria_con_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Alimento']);
        $this->crearProducto($categoria, 'Maíz');
        $inactivo = $this->crearProducto($categoria, 'Soya');
        $inactivo->update(['activo' => false]);   // los inactivos también cuentan

        $this->actingAs($this->admin)
            ->delete("/categorias/{$categoria->id}")
            ->assertRedirect('/categorias')
            ->assertSessionHas('error', 'No se puede eliminar «Alimento» porque tiene 2 productos. Primero cámbialos de categoría.');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);

        // El mensaje se muestra en el listado
        $this->get('/categorias')->assertSee('role="alert"', false)->assertSee('porque tiene 2 productos');
    }

    public function test_una_categoria_que_no_existe_da_404(): void
    {
        $this->actingAs($this->admin);

        $this->get('/categorias/999/edit')->assertNotFound();
        $this->put('/categorias/999', ['nombre' => 'X'])->assertNotFound();
        $this->delete('/categorias/999')->assertNotFound();
    }
}
