<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Respuesta temporal para las rutas de los módulos que aún no se construyen (hoy: reportes).
 * Así ningún enlace lleva a un 404: muestran la pantalla "Próximamente".
 * Cada fase cambia sus rutas en routes/web.php para que apunten a su controlador real.
 * (Las escrituras pendientes respondían con un flash de error; desde la Fase 7 ya no queda ninguna.)
 */
class ProximamenteController extends Controller
{
    public function mostrar(Request $request)
    {
        return view('proximamente', ['modulo' => $this->modulo($request)]);
    }

    // Nombre del módulo a partir del nombre de la ruta: "reportes.stock-bajo" → "Reportes"
    private function modulo(Request $request): string
    {
        return Str::ucfirst(Str::before($request->route()->getName(), '.'));
    }
}
