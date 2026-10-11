<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Intentos fallidos permitidos antes del bloqueo y duración del bloqueo (CU-01)
    private const MAX_INTENTOS = 3;

    private const SEGUNDOS_BLOQUEO = 60;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // El bloqueo es por correo + IP: equivocarse con una cuenta no bloquea a las demás
        $clave = Str::transliterate(Str::lower($credenciales['email']).'|'.$request->ip());

        // Los errores vuelven siempre a la ruta del login (no a "la página anterior", que sale
        // del encabezado Referer y podría apuntar a otro sitio)
        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);

            return redirect()->route('login')
                ->withErrors(['email' => __('auth.throttle', ['seconds' => $segundos])])
                ->onlyInput('email');
        }

        // validate() revisa correo y contraseña sin iniciar la sesión todavía
        if (! Auth::validate($credenciales)) {
            RateLimiter::hit($clave, self::SEGUNDOS_BLOQUEO);

            return redirect()->route('login')
                ->withErrors(['email' => __('auth.failed')])
                ->onlyInput('email');
        }

        $usuario = Auth::getLastAttempted();

        if (! $usuario->activo) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta está desactivada. Habla con un administrador.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($clave);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
