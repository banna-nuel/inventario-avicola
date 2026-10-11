<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Matriz rol × ruta de toda la app (routes/web.php). Se actualiza en cada fase.
 * - invitado: toda ruta protegida lo manda al login
 * - encargado: en una ruta solo de admin recibe 403
 * - rol autorizado: nunca recibe 403, 404 ni 500
 * - las URLs que no deben existir responden 404 o 405
 */
class RutasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $encargado;

    private Categoria $categoria;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->encargado = User::factory()->encargado()->create();
        $this->categoria = Categoria::create(['nombre' => 'Alimento']);
        $this->producto = Producto::create([
            'nombre' => 'Maíz amarillo',
            'categoria_id' => $this->categoria->id,
            'unidad_medida' => 'kg',
            'stock_minimo' => 10,
        ]);
    }

    /**
     * Todas las rutas protegidas: nombre => [método, parámetros, roles que la pueden usar].
     */
    private function matriz(): array
    {
        $ambos = ['admin', 'encargado'];
        $admin = ['admin'];

        return [
            'home' => ['GET', [], $ambos],
            'dashboard' => ['GET', [], $ambos],
            'search' => ['GET', [], $ambos],

            'productos.index' => ['GET', [], $ambos],
            'productos.create' => ['GET', [], $admin],
            'productos.store' => ['POST', [], $admin],
            'productos.edit' => ['GET', [$this->producto], $admin],
            'productos.update' => ['PUT', [$this->producto], $admin],
            'productos.cambiar-estado' => ['PATCH', [$this->producto], $admin],

            'movimientos.index' => ['GET', [], $ambos],
            'movimientos.create' => ['GET', [], $ambos],
            'movimientos.store' => ['POST', [], $ambos],

            'categorias.index' => ['GET', [], $admin],
            'categorias.create' => ['GET', [], $admin],
            'categorias.store' => ['POST', [], $admin],
            'categorias.edit' => ['GET', [$this->categoria], $admin],
            'categorias.update' => ['PUT', [$this->categoria], $admin],
            'categorias.destroy' => ['DELETE', [$this->categoria], $admin],

            'usuarios.index' => ['GET', [], $admin],
            'usuarios.create' => ['GET', [], $admin],
            'usuarios.store' => ['POST', [], $admin],
            'usuarios.edit' => ['GET', [$this->encargado], $admin],
            'usuarios.update' => ['PUT', [$this->encargado], $admin],
            'usuarios.cambiar-estado' => ['PATCH', [$this->encargado], $admin],

            'reportes.index' => ['GET', [], $admin],
            'reportes.stock-por-categoria' => ['GET', [], $admin],
            'reportes.stock-bajo' => ['GET', [], $admin],
            'reportes.mas-movidos' => ['GET', [], $admin],
        ];
    }

    // Rutas públicas o de sesión, fuera de la matriz de roles
    private const RUTAS_DE_ACCESO = ['raiz', 'login', 'login.entrar', 'logout'];

    public function test_toda_ruta_de_la_app_tiene_nombre_y_esta_en_la_matriz(): void
    {
        $nombres = [];

        foreach (Route::getRoutes() as $ruta) {
            // Rutas que agrega el framework (archivos de storage y /up): no son de la app
            if (str_starts_with($ruta->uri(), 'storage/') || $ruta->uri() === 'up') {
                continue;
            }

            $this->assertNotNull($ruta->getName(), "La ruta /{$ruta->uri()} no tiene nombre");
            $nombres[] = $ruta->getName();
        }

        $esperados = array_merge(array_keys($this->matriz()), self::RUTAS_DE_ACCESO);
        sort($nombres);
        sort($esperados);

        // Si falla: agregaste o quitaste una ruta; actualiza la matriz de este test
        $this->assertSame($esperados, $nombres);
    }

    public function test_un_invitado_va_al_login_desde_cualquier_ruta_protegida(): void
    {
        foreach ($this->matriz() as $nombre => [$metodo, $parametros]) {
            $this->call($metodo, route($nombre, $parametros))
                ->assertRedirect(route('login'));
        }
    }

    public function test_el_encargado_recibe_403_en_las_rutas_de_admin(): void
    {
        $this->actingAs($this->encargado);

        foreach ($this->matriz() as $nombre => [$metodo, $parametros, $roles]) {
            if (! in_array('encargado', $roles)) {
                $this->call($metodo, route($nombre, $parametros))
                    ->assertForbidden();
            }
        }
    }

    public function test_el_rol_autorizado_nunca_recibe_403_404_ni_500(): void
    {
        foreach (['admin' => $this->admin, 'encargado' => $this->encargado] as $rol => $usuario) {
            $this->actingAs($usuario);

            foreach ($this->matriz() as $nombre => [$metodo, $parametros, $roles]) {
                if (! in_array($rol, $roles)) {
                    continue;
                }

                $respuesta = $this->call($metodo, route($nombre, $parametros));

                // Las pantallas se ven (200); las escrituras siempre vuelven con un redirect
                $esperado = $metodo === 'GET' ? 200 : 302;
                $this->assertSame($esperado, $respuesta->getStatusCode(), "{$rol} → {$metodo} {$nombre}");
            }
        }
    }

    public function test_las_escrituras_de_modulos_pendientes_avisan_con_un_flash_de_error(): void
    {
        $this->actingAs($this->admin)
            ->from(route('usuarios.index'))
            ->post(route('usuarios.store'), ['name' => 'Ana'])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error', 'El módulo de Usuarios todavía no está disponible. No se guardó nada.');

        $this->assertDatabaseMissing('users', ['name' => 'Ana']);
    }

    public function test_los_get_de_modulos_pendientes_muestran_proximamente(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Reportes')
            ->assertSee('Próximamente');
    }

    public function test_las_urls_que_no_deben_existir_responden_404_o_405(): void
    {
        $p = $this->producto->id;
        $c = $this->categoria->id;
        $u = $this->encargado->id;

        $prohibidas = [
            // show en ningún recurso
            ['GET', "/productos/{$p}"],
            ['GET', "/categorias/{$c}"],
            ['GET', "/usuarios/{$u}"],
            ['GET', '/movimientos/1'],
            // productos y usuarios se desactivan, no se borran (RN-03)
            ['DELETE', "/productos/{$p}"],
            ['DELETE', "/usuarios/{$u}"],
            // los movimientos son inmutables (RN-09)
            ['GET', '/movimientos/1/edit'],
            ['PUT', '/movimientos/1'],
            ['PATCH', '/movimientos/1'],
            ['DELETE', '/movimientos/1'],
        ];

        $this->actingAs($this->admin);

        foreach ($prohibidas as [$metodo, $url]) {
            $estado = $this->call($metodo, $url)->getStatusCode();
            $this->assertContains($estado, [404, 405], "{$metodo} {$url} respondió {$estado}");
        }

        // Y nada se borró
        $this->assertModelExists($this->producto);
        $this->assertModelExists($this->encargado);
    }

    public function test_ningun_enlace_visible_lleva_a_403_ni_404(): void
    {
        foreach ([$this->admin, $this->encargado] as $usuario) {
            $this->actingAs($usuario);

            // Recorre la app desde Inicio siguiendo los enlaces internos (máximo 60 páginas)
            $pendientes = [route('home')];
            $visitadas = [];

            while ($pendientes && count($visitadas) < 60) {
                $url = array_shift($pendientes);
                if (isset($visitadas[$url])) {
                    continue;
                }

                $respuesta = $this->get($url);
                $this->assertSame(200, $respuesta->getStatusCode(), "{$usuario->rol} → {$url}");
                $visitadas[$url] = true;

                preg_match_all('/<a\s[^>]*href="([^"#]+)"/', $respuesta->getContent(), $enlaces);
                foreach ($enlaces[1] as $enlace) {
                    $enlace = html_entity_decode($enlace);
                    if (str_starts_with($enlace, url('/')) && ! isset($visitadas[$enlace])) {
                        $pendientes[] = $enlace;
                    }
                }
            }

            $this->assertGreaterThan(3, count($visitadas));
        }
    }
}
