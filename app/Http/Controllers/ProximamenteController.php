<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Respuesta temporal para las rutas de los módulos que aún no se construyen.
 * Así ningún enlace lleva a un 404: los GET muestran "Próximamente" y las escrituras
 * vuelven atrás con un mensaje de error, sin guardar nada.
 * Cada fase cambia sus rutas en routes/web.php para que apunten a su controlador real.
 */
class ProximamenteController extends Controller
{
    public function mostrar(Request $request)
    {
        return view('proximamente', ['modulo' => $this->modulo($request)]);
    }

    public function guardar(Request $request)
    {
        return back(fallback: route('home'))
            ->with('error', "El módulo de {$this->modulo($request)} todavía no está disponible. No se guardó nada.");
    }

    // Nombre del módulo a partir del nombre de la ruta: "usuarios.create" → "Usuarios"
    private function modulo(Request $request): string
    {
        return Str::ucfirst(Str::before($request->route()->getName(), '.'));
    }
}
