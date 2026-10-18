<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si un administrador desactiva una cuenta mientras esa persona tiene la sesión abierta,
 * en su siguiente petición se cierra la sesión y vuelve al login.
 * El alias 'activo' se registra en bootstrap/app.php.
 */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta fue desactivada. Habla con un administrador.']);
        }

        return $next($request);
    }
}
