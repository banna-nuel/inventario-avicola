<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Búsqueda por texto: ->whereContiene('nombre', $q) y ->orWhereContiene('email', $q).
         * Es un "macro": un método nuevo que se le agrega al constructor de consultas de Laravel.
         * Busca el texto tal cual: si alguien escribe "50%" o "a_b", el % y el _ no funcionan
         * como comodines del LIKE. Se escapan con "!", que sirve igual en MySQL y en SQLite.
         */
        Builder::macro('whereContiene', function (string $columna, string $texto, string $boolean = 'and') {
            $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto).'%';

            return $this->whereRaw($this->grammar->wrap($columna)." like ? escape '!'", [$patron], $boolean);
        });

        Builder::macro('orWhereContiene', function (string $columna, string $texto) {
            return $this->whereContiene($columna, $texto, 'or');
        });
    }
}
