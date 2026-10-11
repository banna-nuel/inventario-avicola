<?php

namespace App\Http\Controllers;

use App\Models\Producto;

/**
 * Inicio (Home): saludo y accesos a los módulos (config/navegacion.php).
 * Solo consulta cuántos productos activos están en stock bajo, para la línea de aviso.
 */
class InicioController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'enStockBajo' => Producto::where('activo', true)->stockBajo()->count(),
        ]);
    }
}
