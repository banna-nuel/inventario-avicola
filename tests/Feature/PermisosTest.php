<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// Fase 2: matriz de permisos (RN-05/06) y páginas de error propias
class PermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambos_roles_entran_a_home_dashboard_y_search(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->encargado()->create()] as $usuario) {
            foreach (['/home', '/dashboard', '/search'] as $url) {
                $this->actingAs($usuario)->get($url)->assertOk();
            }
        }
    }

    public function test_el_admin_entra_a_categorias(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/categorias')
            ->assertOk();
    }

    public function test_el_encargado_recibe_403_en_todas_las_acciones_de_categorias(): void
    {
        $encargado = User::factory()->encargado()->create();
        $categoria = Categoria::create(['nombre' => 'Alimento']);

        $this->actingAs($encargado);
        $this->get('/categorias')->assertForbidden();
        $this->get('/categorias/create')->assertForbidden();
        $this->post('/categorias', ['nombre' => 'Vacunas'])->assertForbidden();
        $this->get("/categorias/{$categoria->id}/edit")->assertForbidden();
        $this->put("/categorias/{$categoria->id}", ['nombre' => 'Otro'])->assertForbidden();
        $this->delete("/categorias/{$categoria->id}")->assertForbidden();

        // Nada cambió en la base de datos
        $this->assertDatabaseCount('categorias', 1);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Alimento']);
    }

    public function test_el_403_tiene_vista_propia_en_espanol(): void
    {
        $this->actingAs(User::factory()->encargado()->create())
            ->get('/categorias')
            ->assertForbidden()
            ->assertSee('No tienes permiso')
            ->assertSee('Volver al inicio');
    }

    public function test_un_invitado_va_al_login_desde_cualquier_pagina_protegida(): void
    {
        foreach (['/home', '/dashboard', '/search', '/categorias'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_el_404_tiene_vista_propia_en_espanol(): void
    {
        $this->get('/esta-pagina-no-existe')
            ->assertNotFound()
            ->assertSee('No encontramos esta página');
    }

    public function test_el_419_tiene_vista_propia_en_espanol(): void
    {
        // En los tests la verificación CSRF está apagada: simulamos el token vencido
        Route::get('/prueba-419', fn () => throw new TokenMismatchException);

        $this->get('/prueba-419')
            ->assertStatus(419)
            ->assertSee('Tu sesión expiró')
            ->assertSee('href="/login"', false);
    }
}
