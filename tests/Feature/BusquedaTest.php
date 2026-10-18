<?php

namespace Tests\Feature;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Fase 6: Search por rol, mínimo de caracteres, comodines escapados y "Ver todos"
class BusquedaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Laura Gómez', 'email' => 'laura@finca.com']);
        $this->encargado = User::factory()->encargado()->create(['name' => 'Ana Pérez', 'email' => 'ana@granja.com']);

        $granos = Categoria::create(['nombre' => 'Granos']);
        $vacunas = Categoria::create(['nombre' => 'Vacunas']);

        $this->producto('Granulado inicio', $granos);
        $this->producto('Maíz amarillo', $granos);           // coincide con "gran" por su categoría
        $this->producto('Concentrado 50% proteína', $granos);
        $this->producto('Newcastle', $vacunas);
        $this->producto('Sorgo', $granos)->update(['activo' => false]);
    }

    private function producto(string $nombre, Categoria $categoria): Producto
    {
        return Producto::factory()->create([
            'nombre' => $nombre,
            'categoria_id' => $categoria->id,
            'unidad_medida' => UnidadMedida::Kilogramos,
        ]);
    }

    public function test_el_encargado_solo_busca_productos_por_nombre_o_categoria(): void
    {
        $this->actingAs($this->encargado)->get(route('search', ['q' => 'gran']))
            ->assertOk()
            ->assertViewHas('grupos', fn ($grupos) => array_keys($grupos) === ['productos'])
            ->assertSee('Granulado inicio')
            ->assertSee('Maíz amarillo')
            ->assertDontSee('Ana Pérez')
            ->assertDontSee('ana@granja.com')
            ->assertDontSee('Categorías');
    }

    public function test_el_encargado_no_puede_pedir_usuarios_ni_categorias_con_el_filtro(): void
    {
        foreach (['usuarios', 'categorias'] as $tipo) {
            $this->actingAs($this->encargado)->get(route('search', ['q' => 'gran', 'tipo' => $tipo]))
                ->assertOk()
                ->assertViewHas('grupos', fn ($grupos) => array_keys($grupos) === ['productos'])
                ->assertDontSee('ana@granja.com');
        }
    }

    public function test_el_admin_busca_productos_categorias_y_usuarios_agrupados(): void
    {
        $this->actingAs($this->admin)->get(route('search', ['q' => 'gran']))
            ->assertOk()
            ->assertViewHas('grupos', fn ($grupos) => array_keys($grupos) === ['productos', 'categorias', 'usuarios'])
            ->assertSeeInOrder(['Productos', 'Granulado inicio', 'Categorías', 'Granos', 'Usuarios', 'Ana Pérez'])
            ->assertSee('ana@granja.com')
            ->assertSee(e(route('productos.index', ['q' => 'gran', 'estado' => 'todos'])), false)
            ->assertSee(e(route('categorias.index', ['q' => 'gran'])), false)
            ->assertSee(e(route('usuarios.index', ['q' => 'gran'])), false);

        // El filtro elige un solo grupo
        $this->get(route('search', ['q' => 'gran', 'tipo' => 'usuarios']))
            ->assertViewHas('grupos', fn ($grupos) => array_keys($grupos) === ['usuarios'])
            ->assertDontSee('Granulado inicio');
    }

    public function test_hacen_falta_al_menos_2_caracteres(): void
    {
        $this->actingAs($this->admin)->get(route('search', ['q' => 'g']))
            ->assertOk()
            ->assertSee('Escribe al menos 2 caracteres')
            ->assertViewHas('grupos', []);

        $this->get(route('search', ['q' => '   ']))->assertSee('Escribe para buscar');
    }

    public function test_el_porcentaje_y_el_guion_bajo_se_buscan_como_texto(): void
    {
        $this->actingAs($this->admin);

        // "0%" solo coincide con el nombre que tiene "50%" escrito
        $this->get(route('search', ['q' => '0%']))
            ->assertViewHas('total', 1)
            ->assertSee('Concentrado 50% proteína');

        // Sin escapar, "_a" (cualquier letra + "a") encontraría "Maíz amarillo", "Granos"...
        $this->get(route('search', ['q' => '_a']))
            ->assertViewHas('total', 0)
            ->assertSee('Sin resultados');

        $this->get(route('search', ['q' => '%%']))->assertViewHas('total', 0);
    }

    public function test_maximo_10_por_grupo_y_ver_todos_lleva_al_listado_filtrado(): void
    {
        $categoria = Categoria::create(['nombre' => 'Lote']);
        foreach (range(1, 12) as $numero) {
            $this->producto(sprintf('Pollinaza %02d', $numero), $categoria);
        }

        $this->actingAs($this->encargado)->get(route('search', ['q' => 'pollinaza']))
            ->assertViewHas('grupos', fn ($grupos) => $grupos['productos']['total'] === 12 && $grupos['productos']['items']->count() === 10)
            ->assertSee('Pollinaza 10')
            ->assertDontSee('Pollinaza 11');

        $this->get(route('productos.index', ['q' => 'pollinaza', 'estado' => 'todos']))
            ->assertSee('12 productos')
            ->assertSee('Pollinaza 12');
    }

    public function test_los_productos_inactivos_aparecen_con_su_estado(): void
    {
        $this->actingAs($this->encargado)->get(route('search', ['q' => 'sorgo']))
            ->assertSee('Sorgo')
            ->assertSee('Inactivo');
    }

    public function test_ver_todos_de_categorias_y_usuarios_filtra_esos_listados(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('categorias.index', ['q' => 'gran']))
            ->assertSee('Granos')
            ->assertDontSee('Vacunas')
            ->assertSee('Categorías con «gran»');

        $this->get(route('usuarios.index', ['q' => 'granja']))
            ->assertSee('Ana Pérez')
            ->assertDontSee('Laura Gómez');
    }
}
