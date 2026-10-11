<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

// Fase 1: layout, componentes y pantallas base
class InterfazTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_esta_en_espanol_y_tiene_etiquetas(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<html lang="es">', false)
            ->assertSee('<label for="email"', false)
            ->assertSee('<label for="password"', false)
            ->assertSee('Correo electrónico')
            ->assertSee('Entrar')
            ->assertDontSee('Route::resource');
    }

    public function test_el_error_de_credenciales_se_muestra_junto_al_campo(): void
    {
        $this->from('/login')->post('/login', ['email' => 'nadie@test.com', 'password' => 'mal'])
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertSee('Estas credenciales no coinciden con nuestros registros.')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_el_admin_ve_todas_las_tarjetas_de_acceso(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/home')
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('href="/productos"', false)
            ->assertSee('href="/movimientos"', false)
            ->assertSee('href="/categorias"', false)
            ->assertSee('href="/usuarios"', false)
            ->assertSee('href="/reportes"', false);
    }

    public function test_el_encargado_solo_ve_productos_y_movimientos(): void
    {
        $encargado = User::factory()->encargado()->create();

        $this->actingAs($encargado)->get('/home')
            ->assertOk()
            ->assertSee('Encargado de inventario')
            ->assertSee('href="/productos"', false)
            ->assertSee('href="/movimientos"', false)
            ->assertDontSee('href="/categorias"', false)
            ->assertDontSee('href="/usuarios"', false)
            ->assertDontSee('href="/reportes"', false);
    }

    public function test_la_navegacion_marca_la_pagina_activa(): void
    {
        $usuario = User::factory()->create();

        $html = $this->actingAs($usuario)->get('/dashboard')
            ->assertOk()
            ->assertSee('Sin alertas por ahora')
            ->getContent();

        // Solo el enlace de Dashboard lleva aria-current="page"
        $this->assertMatchesRegularExpression('/href="\/dashboard"[^>]*aria-current="page"/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_el_circulo_activo_de_la_barra_se_ubica_segun_la_pagina(): void
    {
        $usuario = User::factory()->admin()->create();

        // Cada ítem está 74 px más a la derecha (50 px de botón + 24 px de separación)
        foreach (['/home' => 0, '/dashboard' => 74, '/search' => 148] as $url => $desplazamiento) {
            $html = $this->actingAs($usuario)->get($url)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'vt-nav-activo'), "Debe haber un solo círculo activo en {$url}");
            $this->assertStringContainsString("translateX({$desplazamiento}px)", $html);
            $this->assertStringContainsString('vt-encabezado', $html);
            $this->assertStringContainsString('vt-contenido', $html);
            $this->assertStringContainsString('vt-barra', $html);
        }
    }

    public function test_la_regla_que_activa_las_transiciones_va_inline_en_ambos_layouts(): void
    {
        // Inline (no en app.css) para que el navegador la vea antes de cargar los estilos
        $this->get('/login')->assertSee('@view-transition { navigation: auto; }', false);

        $this->actingAs(User::factory()->create())->get('/home')
            ->assertSee('@view-transition { navigation: auto; }', false)
            ->assertSee('@view-transition { navigation: none; }', false);
    }

    public function test_una_pagina_fuera_de_la_barra_no_tiene_circulo_activo(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/categorias')
            ->assertOk()
            ->assertDontSee('vt-nav-activo')
            ->assertDontSee('aria-current="page"', false);
    }

    public function test_el_layout_muestra_los_mensajes_flash(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->withSession(['success' => 'Categoría creada'])
            ->get('/home')
            ->assertSee('role="status"', false)
            ->assertSee('Categoría creada');
    }

    public function test_la_busqueda_conserva_el_texto_y_filtra_opciones_por_rol(): void
    {
        $encargado = User::factory()->encargado()->create();

        $this->actingAs($encargado)->get('/search?q=maiz')
            ->assertOk()
            ->assertSee('value="maiz"', false)
            ->assertSee('No encontramos coincidencias para «maiz».')
            ->assertSee('Productos')
            ->assertDontSee('Usuarios');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/search')
            ->assertSee('Usuarios')
            ->assertSee('Escribe para buscar');
    }

    public function test_la_paginacion_usa_la_vista_del_proyecto_en_espanol(): void
    {
        $paginador = new LengthAwarePaginator(range(1, 10), 30, 10, 2, ['path' => '/productos']);

        $html = $paginador->links()->toHtml();

        $this->assertStringContainsString('aria-label="Paginación"', $html);
        $this->assertStringContainsString('Página siguiente', $html);
        $this->assertStringContainsString('Página 2 de 3', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }
}
