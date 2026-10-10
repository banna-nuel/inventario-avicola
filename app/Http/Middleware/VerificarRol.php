<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a los roles indicados en la ruta. Uso: ->middleware('role:admin')
 * Se puede pasar más de uno: ->middleware('role:admin,encargado')
 * El alias 'role' se registra en bootstrap/app.php.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->rol, $roles, true)) {
            // Muestra resources/views/errors/403.blade.php
            abort(403);
        }

        return $next($request);
    }
}
