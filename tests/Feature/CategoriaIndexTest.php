<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_listado_muestra_las_categorias_creadas(): void
    {
        $user = User::factory()->create(['rol' => 'admin']);
        Categoria::create(['nombre' => 'Alimento']);
        Categoria::create(['nombre' => 'Medicamentos']);

        $this->actingAs($user)
            ->get('/categorias')
            ->assertOk()
            ->assertSee('Alimento')
            ->assertSee('Medicamentos');
    }

    public function test_crear_una_categoria_redirige_al_listado_y_la_muestra(): void
    {
        $user = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($user)
            ->post('/categorias', ['nombre' => 'Vacunas'])
            ->assertRedirect('/categorias');

        $this->actingAs($user)
            ->get('/categorias')
            ->assertSee('Vacunas');
    }

    public function test_los_mensajes_de_validacion_salen_en_espanol(): void
    {
        $user = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($user)
            ->post('/categorias', ['nombre' => ''])
            ->assertSessionHasErrors(['nombre' => 'El campo nombre es obligatorio.']);
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get('/categorias')->assertRedirect('/login');
    }
}
