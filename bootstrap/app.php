<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para usar en las rutas: ->middleware('role:admin') y ->middleware('activo')
        $middleware->alias([
            'role' => \App\Http\Middleware\VerificarRol::class,
            'activo' => \App\Http\Middleware\UsuarioActivo::class,
        ]);

        // Sin sesión se va al login; con sesión, /login lleva a Inicio.
        // Van dentro de fn () porque aquí las rutas todavía no están cargadas.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
