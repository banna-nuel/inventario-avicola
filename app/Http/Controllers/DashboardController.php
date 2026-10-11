<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;

/**
 * Dashboard (RF-03): los mismos indicadores para los dos roles.
 * Es un controlador "invocable": tiene un solo método, __invoke, y la ruta lo usa directo.
 */
class DashboardController extends Controller
{
    public function __invoke()
    {
        // Productos en alerta (RN-02), del mayor al menor faltante: scope Producto::enAlerta()
        $alertas = Producto::with('categoria')->enAlerta()->get();

        return view('dashboard', [
            'indicadores' => [
                'activos' => Producto::where('activo', true)->count(),
                'stockBajo' => $alertas->count(),
                // Día calendario en America/Bogota (la zona de la app, config/app.php):
                // desde hoy a las 00:00 hasta antes de mañana a las 00:00
                'movimientosHoy' => MovimientoInventario::where('fecha', '>=', today())
                    ->where('fecha', '<', today()->addDay())
                    ->count(),
            ],
            'alertas' => $alertas,
        ]);
    }
}
