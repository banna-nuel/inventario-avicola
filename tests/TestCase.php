<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Red de seguridad: las pruebas usan RefreshDatabase, que BORRA la base antes de cada test.
     * Por eso solo pueden correr con SQLite en memoria (lo normal, phpunit.xml) o con una base
     * cuyo nombre termine en "_test" (por ejemplo inventario_avicola_test). Nunca con la de desarrollo.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $conexion = $app['config']['database.default'];
        $base = (string) $app['config']["database.connections.{$conexion}.database"];

        if ($conexion !== 'sqlite' && ! str_ends_with($base, '_test')) {
            throw new RuntimeException("Las pruebas no pueden usar la base «{$base}»: usa una que termine en _test.");
        }

        return $app;
    }
}
