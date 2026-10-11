<?php

namespace Tests\Feature;

use App\Enums\UnidadMedida;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

// Fase 9: revisión final de seguridad (lo que se puede comprobar de forma automática)
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_todo_formulario_que_escribe_lleva_token_csrf(): void
    {
        $admin = User::factory()->admin()->create();
        $categoria = Categoria::create(['nombre' => 'Alimento']);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id, 'unidad_medida' => UnidadMedida::Kilogramos]);

        $paginas = [
            route('login') => null,
            route('home') => $admin,
            route('categorias.index') => $admin,          // eliminar
            route('categorias.create') => $admin,
            route('categorias.edit', $categoria) => $admin,
            route('productos.create') => $admin,
            route('productos.edit', $producto) => $admin, // editar y desactivar
            route('movimientos.create') => $admin,
            route('usuarios.create') => $admin,
            route('usuarios.edit', User::factory()->create()) => $admin,
        ];

        foreach ($paginas as $url => $usuario) {
            $usuario ? $this->actingAs($usuario) : auth()->logout();
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<form\b[^>]*method="POST"[^>]*>(.*?)<\/form>/s', $html, $formularios);
            $this->assertNotEmpty($formularios[1], "Sin formularios en {$url}");

            foreach ($formularios[1] as $formulario) {
                $this->assertStringContainsString('name="_token"', $formulario, "Formulario sin @csrf en {$url}");
            }
        }
    }

    public function test_ninguna_vista_imprime_html_sin_escapar(): void
    {
        // {!! !!} imprime sin escapar: con datos de un usuario permitiría inyectar HTML o JavaScript (XSS)
        foreach (File::allFiles(resource_path('views')) as $vista) {
            $this->assertStringNotContainsString('{!!', $vista->getContents(), $vista->getRelativePathname());
        }
    }

    public function test_un_nombre_con_html_se_muestra_como_texto(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Categoria::create(['nombre' => '<script>alert(1)</script>']);

        $this->get(route('categorias.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_el_login_con_error_vuelve_al_login_aunque_el_referer_sea_externo(): void
    {
        $this->withHeader('Referer', 'https://sitio-malicioso.com/robar')
            ->post(route('login.entrar'), ['email' => 'nadie@test.com', 'password' => 'mal'])
            ->assertRedirect(route('login'));
    }

    public function test_la_plantilla_env_trae_app_debug_apagado_y_sin_secretos(): void
    {
        $plantilla = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $plantilla);
        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $plantilla);
        $this->assertMatchesRegularExpression('/^DB_PASSWORD=$/m', $plantilla);
    }

    public function test_un_error_del_servidor_se_muestra_en_espanol_y_sin_detalles(): void
    {
        config(['app.debug' => false]);
        Route::get('/prueba-500', fn () => throw new RuntimeException('detalle interno secreto'));

        $this->get('/prueba-500')
            ->assertStatus(500)
            ->assertSee('Algo salió mal')
            ->assertDontSee('detalle interno secreto')
            ->assertDontSee('RuntimeException');
    }
}
