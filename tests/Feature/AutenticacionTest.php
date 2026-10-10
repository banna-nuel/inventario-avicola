<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Fase 2: inicio de sesión, bloqueo por intentos fallidos (CU-01) y cuentas inactivas
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    // El factory crea los usuarios con la contraseña 'password'
    private function intentar(string $email, string $password, string $ip = '127.0.0.1')
    {
        return $this->from('/login')
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function test_inicia_sesion_y_va_a_home(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@test.com']);

        $this->intentar('ana@test.com', 'password')->assertRedirect('/home');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_error_conserva_el_correo_pero_no_la_contrasena(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        $this->intentar('ana@test.com', 'incorrecta')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Estas credenciales no coinciden con nuestros registros.'])
            ->assertSessionHasInput('email', 'ana@test.com');

        $this->assertNull(session()->getOldInput('password'));
        $this->assertGuest();

        // El formulario vuelve con el correo escrito
        $this->get('/login')->assertSee('value="ana@test.com"', false);
    }

    public function test_tras_tres_intentos_fallidos_se_bloquea_aunque_la_contrasena_sea_correcta(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->intentar('ana@test.com', 'incorrecta');
        }

        $this->intentar('ana@test.com', 'password')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', 'ana@test.com');

        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_el_bloqueo_vence_despues_de_un_minuto(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@test.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->intentar('ana@test.com', 'incorrecta');
        }

        $this->travel(61)->seconds();

        $this->intentar('ana@test.com', 'password')->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_bloqueo_es_por_correo_e_ip(): void
    {
        $ana = User::factory()->create(['email' => 'ana@test.com']);
        User::factory()->create(['email' => 'luis@test.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->intentar('ana@test.com', 'incorrecta', '10.0.0.1');
        }

        // Otra cuenta desde la misma IP no queda bloqueada
        $this->intentar('luis@test.com', 'password', '10.0.0.1')->assertRedirect('/home');
        $this->post('/logout');

        // La misma cuenta desde otra IP tampoco
        $this->intentar('ana@test.com', 'password', '10.0.0.2')->assertRedirect('/home');
        $this->assertAuthenticatedAs($ana);
    }

    public function test_un_inicio_de_sesion_correcto_reinicia_el_contador(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        $this->intentar('ana@test.com', 'incorrecta');
        $this->intentar('ana@test.com', 'incorrecta');
        $this->intentar('ana@test.com', 'password')->assertRedirect('/home');
        $this->post('/logout');

        // Si el contador no se reiniciara, este tercer error bloquearía la cuenta
        $this->intentar('ana@test.com', 'incorrecta');
        $this->intentar('ana@test.com', 'password')->assertRedirect('/home');
    }

    public function test_un_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        User::factory()->create(['email' => 'ana@test.com', 'activo' => false]);

        $this->intentar('ana@test.com', 'password')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada. Habla con un administrador.']);

        $this->assertGuest();
    }

    public function test_un_usuario_desactivado_con_la_sesion_abierta_es_expulsado(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario)->get('/home')->assertOk();

        $usuario->update(['activo' => false]);

        $this->get('/home')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_con_sesion_iniciada_el_login_redirige_a_home(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/home');
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_la_plantilla_del_env_fija_la_sesion_en_30_minutos(): void
    {
        // RNF-04: cada copia nueva del proyecto arranca con sesiones de 30 minutos
        $this->assertStringContainsString('SESSION_LIFETIME=30', file_get_contents(base_path('.env.example')));
    }
}
