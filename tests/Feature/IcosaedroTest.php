<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

// Fase 10: icosaedro 3D (RNF-08) con Three.js autoalojado y sin scripts externos
class IcosaedroTest extends TestCase
{
    use RefreshDatabase;

    private const VENDOR = ['three.module.js', 'three.core.js'];

    public function test_inicio_y_login_cargan_el_modulo_del_mismo_origen(): void
    {
        $paginas = [
            'login' => $this->get(route('login'))->assertOk()->getContent(),
            'home' => $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()->getContent(),
        ];

        foreach ($paginas as $pagina => $html) {
            $this->assertMatchesRegularExpression(
                '#<script type="module" src="'.preg_quote(asset('js/icosaedro.js'), '#').'\?v=\d+"></script>#',
                $html,
                "Falta el módulo del icosaedro en {$pagina}"
            );
            $this->assertSame(1, substr_count($html, 'js/icosaedro.js'), "El módulo se carga una sola vez en {$pagina}");
        }
    }

    public function test_el_componente_es_decorativo_y_tiene_su_tamano_reservado(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('home'))->getContent();

        preg_match('/<div[^>]*data-icosaedro[^>]*>.*?<\/div>/s', $html, $componente);
        $this->assertNotEmpty($componente);

        $this->assertStringContainsString('aria-hidden="true"', $componente[0]);
        $this->assertStringNotContainsString('tabindex', $componente[0]);
        $this->assertMatchesRegularExpression('/class="[^"]*size-\[176px\]/', $componente[0]);   // Inicio: compacto
        $this->assertSame(30, substr_count($componente[0], '<line '));                       // SVG estático: 30 aristas

        auth()->logout();   // /login es solo para invitados
        $this->get(route('login'))->assertOk()->assertSee('size-[216px]', false);            // login: grande
    }

    public function test_el_dashboard_no_tiene_icosaedro(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertDontSee('data-icosaedro', false)
            ->assertDontSee('icosaedro.js', false);
    }

    public function test_three_js_esta_autoalojado_y_coincide_con_third_party(): void
    {
        $registro = file_get_contents(public_path('js/vendor/THIRD_PARTY.md'));

        foreach (self::VENDOR as $archivo) {
            $ruta = public_path("js/vendor/{$archivo}");
            $this->assertFileExists($ruta);
            // El SHA-256 anotado en THIRD_PARTY.md es el del archivo que se sirve
            $this->assertStringContainsString(hash_file('sha256', $ruta), $registro, "SHA-256 de {$archivo}");
        }

        $this->assertStringContainsString('The MIT License', $registro);
        $this->assertStringContainsString('r186', $registro);

        // icosaedro.js importa Three.js con una ruta relativa del mismo origen
        $modulo = file_get_contents(public_path('js/icosaedro.js'));
        $this->assertStringContainsString("import('./vendor/three.module.js')", $modulo);
        $this->assertDoesNotMatchRegularExpression('#(import|from)\s*\(?\s*[\'"](https?:)?//#', $modulo);
    }

    public function test_ninguna_vista_carga_scripts_externos_ni_inline(): void
    {
        // Un <script> con URL externa (http://, https:// o //) o con código inline rompería una CSP estricta
        foreach (File::allFiles(resource_path('views')) as $vista) {
            preg_match_all('/<script\b[^>]*>/i', $vista->getContents(), $scripts);

            foreach ($scripts[0] as $script) {
                $this->assertDoesNotMatchRegularExpression('#src\s*=\s*["\']?(https?:)?//#i', $script,
                    "URL externa en {$vista->getRelativePathname()}: {$script}");
                $this->assertStringContainsString('src=', $script,
                    "Script inline en {$vista->getRelativePathname()}");
            }
        }
    }

    public function test_las_paginas_solo_piden_scripts_del_mismo_origen(): void
    {
        $usuario = User::factory()->admin()->create();
        $paginas = [route('login') => null, route('home') => $usuario, route('dashboard') => $usuario, route('productos.index') => $usuario];

        foreach ($paginas as $url => $quien) {
            $quien ? $this->actingAs($quien) : auth()->logout();
            preg_match_all('/<script\b[^>]*\bsrc="([^"]+)"/i', $this->get($url)->getContent(), $fuentes);

            $this->assertNotEmpty($fuentes[1]);
            foreach ($fuentes[1] as $fuente) {
                $this->assertStringStartsWith(url('/'), $fuente, "Script externo en {$url}");
            }
        }
    }
}
