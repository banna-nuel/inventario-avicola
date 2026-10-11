<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Fase 7: gestión de usuarios (CU-07, solo admin) y RN-07
class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Laura Gómez', 'email' => 'laura@finca.com']);
        $this->encargado = User::factory()->encargado()->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@granja.com',
            'password' => 'clave-original',
        ]);
    }

    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'name' => 'Carlos Ruiz',
            'email' => 'carlos@granja.com',
            'rol' => 'encargado',
            'password' => 'secreta123',
        ];
    }

    // ---- Listado ----

    public function test_el_listado_muestra_rol_estado_y_marca_tu_cuenta(): void
    {
        User::factory()->encargado()->create(['name' => 'Pedro Inactivo', 'activo' => false]);

        $this->actingAs($this->admin)->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('3 usuarios')
            ->assertSeeInOrder(['Ana Pérez', 'Laura Gómez', '(tú)', 'Pedro Inactivo'])   // activos primero
            ->assertSee('ana@granja.com')
            ->assertSee('Administrador')
            ->assertSee('Inactivo');
    }

    // ---- Crear ----

    public function test_crear_un_usuario_con_contrasena_inicial(): void
    {
        $this->actingAs($this->admin)
            ->post(route('usuarios.store'), $this->datos(['email' => '  Carlos@Granja.com ']))
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success', 'Usuario «Carlos Ruiz» creado.');

        $carlos = User::firstWhere('email', 'carlos@granja.com');   // se guarda en minúsculas
        $this->assertSame('encargado', $carlos->rol);
        $this->assertTrue($carlos->activo);
        $this->assertTrue(Hash::check('secreta123', $carlos->password));   // cifrada, no en texto plano

        // Y puede iniciar sesión
        $this->post(route('logout'));
        $this->post(route('login.entrar'), ['email' => 'carlos@granja.com', 'password' => 'secreta123'])
            ->assertRedirect(route('home'));
    }

    public function test_validaciones_en_espanol(): void
    {
        $this->actingAs($this->admin)
            ->post(route('usuarios.store'), [])
            ->assertSessionHasErrors([
                'name' => 'Escribe el nombre.',
                'email' => 'Escribe el correo.',
                'rol' => 'Elige un rol.',
                'password' => 'Escribe una contraseña inicial.',
            ]);

        $this->post(route('usuarios.store'), $this->datos(['email' => 'no-es-correo', 'rol' => 'jefe', 'password' => 'corta']))
            ->assertSessionHasErrors([
                'email' => 'Escribe un correo válido, por ejemplo ana@granja.com.',
                'rol' => 'Elige un rol de la lista.',
                'password' => 'La contraseña debe tener al menos 8 caracteres.',
            ]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_el_correo_es_unico_sin_importar_mayusculas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('usuarios.store'), $this->datos(['email' => 'ANA@granja.com']))
            ->assertSessionHasErrors(['email' => 'Ya existe un usuario con ese correo.']);

        // Al editar, su propio correo sí vale
        $this->put(route('usuarios.update', $this->encargado), $this->datos(['name' => 'Ana Pérez', 'email' => 'ana@granja.com', 'password' => '']))
            ->assertSessionHasNoErrors();

        // Pero no el de otra persona
        $this->put(route('usuarios.update', $this->encargado), $this->datos(['email' => 'laura@finca.com', 'password' => '']))
            ->assertSessionHasErrors(['email' => 'Ya existe un usuario con ese correo.']);
    }

    // ---- Editar ----

    public function test_al_editar_la_contrasena_vacia_no_la_cambia(): void
    {
        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $this->encargado), $this->datos(['name' => 'Ana María Pérez', 'email' => 'ana@granja.com', 'password' => '']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Usuario «Ana María Pérez» actualizado.');

        $ana = $this->encargado->fresh();
        $this->assertSame('Ana María Pérez', $ana->name);
        $this->assertTrue(Hash::check('clave-original', $ana->password));
    }

    public function test_al_editar_con_contrasena_nueva_se_cambia_y_se_puede_entrar_con_ella(): void
    {
        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $this->encargado), $this->datos(['email' => 'ana@granja.com', 'password' => 'nueva-clave-1']))
            ->assertSessionHasNoErrors();

        // Cifrada una sola vez (el cast 'hashed' del modelo; sin Hash::make a mano: evita el doble hash)
        $this->assertTrue(Hash::check('nueva-clave-1', $this->encargado->fresh()->password));

        // Inicio de sesión real: la nueva sirve y la anterior ya no
        $this->post(route('logout'));
        $this->post(route('login.entrar'), ['email' => 'ana@granja.com', 'password' => 'clave-original'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.entrar'), ['email' => 'ana@granja.com', 'password' => 'nueva-clave-1'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->encargado->fresh());
    }

    public function test_el_admin_puede_cambiar_el_rol_de_otra_persona(): void
    {
        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $this->encargado), $this->datos(['email' => 'ana@granja.com', 'rol' => 'admin', 'password' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('admin', $this->encargado->fresh()->rol);
    }

    // ---- RN-07 y autoprotección ----

    public function test_rn07_el_unico_admin_no_puede_quitarse_el_rol(): void
    {
        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $this->admin), $this->datos(['name' => 'Laura Gómez', 'email' => 'laura@finca.com', 'rol' => 'encargado', 'password' => '']))
            ->assertSessionHasErrors(['rol' => 'No puedes quitarte el rol de administrador.']);

        $this->assertSame('admin', $this->admin->fresh()->rol);
    }

    public function test_rn07_el_unico_admin_no_puede_desactivarse(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('usuarios.cambiar-estado', $this->admin), ['activo' => '0'])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($this->admin->fresh()->activo);
    }

    public function test_aunque_haya_otro_admin_nadie_se_desactiva_ni_se_quita_el_rol_a_si_mismo(): void
    {
        $otroAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->patch(route('usuarios.cambiar-estado', $this->admin), ['activo' => '0'])
            ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');

        $this->put(route('usuarios.update', $this->admin), $this->datos(['email' => 'laura@finca.com', 'rol' => 'encargado', 'password' => '']))
            ->assertSessionHasErrors('rol');

        // Pero sí puede desactivar a otro administrador
        $this->patch(route('usuarios.cambiar-estado', $otroAdmin), ['activo' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($otroAdmin->fresh()->activo);
        $this->assertTrue($this->admin->fresh()->activo);
    }

    public function test_la_hoja_de_tu_propia_cuenta_no_ofrece_cambiar_rol_ni_desactivar(): void
    {
        $this->actingAs($this->admin)->get(route('usuarios.edit', $this->admin))
            ->assertOk()
            ->assertSee('Es tu cuenta: no puedes quitarte el rol de administrador.')
            ->assertSee('Es tu cuenta: no puedes desactivarla.')
            ->assertSee('<input type="hidden" name="rol" value="admin">', false)
            ->assertDontSee('<select id="rol"', false)
            ->assertDontSee('Desactivar cuenta');
    }

    // ---- Desactivar ----

    public function test_desactivar_pide_confirmacion_y_expulsa_la_sesion_abierta(): void
    {
        $this->actingAs($this->admin)->get(route('usuarios.edit', $this->encargado))
            ->assertSee('Desactivar cuenta')
            ->assertSee('data-confirmar="¿Desactivar a «Ana Pérez»?', false);

        $this->patch(route('usuarios.cambiar-estado', $this->encargado), ['activo' => '0'])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $this->assertFalse($this->encargado->fresh()->activo);

        // Si Ana tenía la sesión abierta, su siguiente petición la saca (middleware 'activo')
        $this->actingAs($this->encargado->fresh())->get(route('home'))
            ->assertRedirect(route('login'));

        // Y se puede volver a activar
        $this->actingAs($this->admin)->patch(route('usuarios.cambiar-estado', $this->encargado), ['activo' => '1']);
        $this->assertTrue($this->encargado->fresh()->activo);
    }

    // ---- Permisos ----

    public function test_el_encargado_recibe_403_en_todo_usuarios(): void
    {
        $this->actingAs($this->encargado);

        $this->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('usuarios.create'))->assertForbidden();
        $this->post(route('usuarios.store'), $this->datos())->assertForbidden();
        $this->get(route('usuarios.edit', $this->admin))->assertForbidden();
        $this->put(route('usuarios.update', $this->admin), $this->datos(['rol' => 'encargado']))->assertForbidden();
        $this->patch(route('usuarios.cambiar-estado', $this->admin), ['activo' => '0'])->assertForbidden();

        // Nada cambió
        $this->assertDatabaseMissing('users', ['email' => 'carlos@granja.com']);
        $this->assertSame('admin', $this->admin->fresh()->rol);
        $this->assertTrue($this->admin->fresh()->activo);
    }
}
