<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// La sesión se guarda en JSON (config/session.php) y en el servidor: la cookie solo lleva su identificador
class SesionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml usa el driver "array"; aquí se prueba el de la app: la tabla sessions
        config(['session.driver' => 'database']);
    }

    // Como al empezar una petición nueva: sin el usuario en memoria. También se olvida "auth.driver",
    // el guard del que DatabaseSessionHandler saca el user_id (en la app cada petición es un proceso nuevo)
    private function olvidarUsuario(): void
    {
        auth()->forgetGuards();
        app()->forgetInstance('auth.driver');
    }

    // Lo que guardó la base para una sesión (DatabaseSessionHandler lo guarda en base64)
    private function guardado(string $id): string
    {
        return base64_decode(DB::table('sessions')->where('id', $id)->value('payload'));
    }

    public function test_la_sesion_se_serializa_en_json(): void
    {
        $this->assertSame('json', config('session.serialization'));
    }

    public function test_la_sesion_se_guarda_y_se_lee_en_json(): void
    {
        $sesion = app('session')->driver();
        $sesion->start();
        $sesion->put('filtros', ['tipo' => 'entrada', 'pagina' => 2]);
        $sesion->flash('success', 'Producto «Maíz amarillo» creado.');
        $sesion->save();

        $guardado = $this->guardado($sesion->getId());
        $this->assertJson($guardado);
        $this->assertSame(['tipo' => 'entrada', 'pagina' => 2], json_decode($guardado, true)['filtros']);

        // La siguiente petición, con el mismo identificador, lee lo mismo desde la base
        app('session')->forgetDrivers();
        $leida = app('session')->driver();
        $leida->setId($sesion->getId());
        $leida->start();

        $this->assertSame(['tipo' => 'entrada', 'pagina' => 2], $leida->get('filtros'));
        $this->assertSame('Producto «Maíz amarillo» creado.', $leida->get('success'));
    }

    public function test_el_login_y_el_logout_funcionan_con_la_sesion_en_json(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@test.com']);

        $this->from('/login')->post('/login', ['email' => 'ana@test.com', 'password' => 'password'])
            ->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario);

        // La sesión del usuario quedó en la tabla, en JSON, con su identificador de inicio de sesión
        $id = session()->getId();
        $this->assertDatabaseHas('sessions', ['id' => $id, 'user_id' => $usuario->id]);
        $this->assertSame($usuario->id, json_decode($this->guardado($id), true)[auth()->guard()->getName()]);

        // Otra petición solo con la cookie: el usuario tiene que salir de la sesión guardada en la base,
        // no de la memoria del guard (las peticiones de prueba no llevan la cookie si no se la pasa)
        $this->olvidarUsuario();
        $this->withCookie(config('session.cookie'), $id)->get('/home')->assertOk();
        $this->assertAuthenticatedAs($usuario);

        // Al salir, esa sesión se borra y la nueva ya no tiene usuario
        $this->olvidarUsuario();
        $this->withCookie(config('session.cookie'), $id)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => $id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $usuario->id]);
    }

    public function test_la_cookie_solo_lleva_el_identificador_de_la_sesion(): void
    {
        User::factory()->create(['email' => 'ana@test.com']);

        $respuesta = $this->post('/login', ['email' => 'ana@test.com', 'password' => 'password']);

        // Con el driver cookie habría una cookie más, con todos los datos de la sesión
        $nombres = array_map(fn ($cookie) => $cookie->getName(), $respuesta->headers->getCookies());
        sort($nombres);
        $this->assertSame(['XSRF-TOKEN', config('session.cookie')], $nombres);

        $this->assertSame(session()->getId(), $respuesta->getCookie(config('session.cookie'))->getValue());
    }

    public function test_ningun_entorno_guarda_la_sesion_en_la_cookie(): void
    {
        // La demo de Vercel toma el driver del Dockerfile; la pila local, de .env.docker.app; XAMPP, de .env.
        // En la imagen de pruebas de Docker solo está .env.example (.dockerignore deja fuera los otros dos).
        $revisados = 0;
        foreach (['.env.example', '.env.docker.app.example', 'Dockerfile'] as $archivo) {
            if (! is_file(base_path($archivo))) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression('/^\s*SESSION_DRIVER=cookie\b/m',
                file_get_contents(base_path($archivo)), "$archivo guarda la sesión en la cookie");
            $revisados++;
        }

        $this->assertGreaterThan(0, $revisados);
    }
}
