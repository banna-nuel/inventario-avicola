<?php

namespace App\Http\Controllers;

use App\Http\Requests\RangoFechasRequest;
use App\Models\Categoria;
use App\Models\Producto;

/**
 * Reportes en pantalla (CU-08, solo administrador). Se pueden imprimir; no se exportan
 * a PDF ni a Excel en la v1. Todas las consultas son agregadas: sin N+1.
 */
class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }

    // Stock actual por categoría. Cada producto con su propia unidad: las cantidades de
    // productos distintos NUNCA se suman (podrían ser kilos y dosis); por categoría solo se cuentan.
    public function stockPorCategoria()
    {
        $categorias = Categoria::query()
            ->with(['productos' => fn ($consulta) => $consulta->where('activo', true)->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();

        return view('reportes.stock-por-categoria', ['categorias' => $categorias]);
    }

    // Productos activos en stock bajo, del mayor al menor faltante (el mismo scope del Dashboard)
    public function stockBajo()
    {
        return view('reportes.stock-bajo', [
            'productos' => Producto::with('categoria')->enAlerta()->get(),
        ]);
    }

    // Los 10 productos con más movimientos en el rango, con el total de entradas y de salidas
    public function masMovidos(RangoFechasRequest $request)
    {
        $desde = $request->desde();
        $hasta = $request->hasta();

        // Movimientos dentro del rango: desde las 00:00 de "desde" hasta antes del día siguiente a "hasta"
        $enRango = fn ($consulta) => $consulta
            ->where('fecha', '>=', $desde)
            ->where('fecha', '<', $hasta->copy()->addDay());

        // withCount y withSum calculan los totales en la misma consulta (subconsultas), sin N+1
        $productos = Producto::with('categoria')
            ->whereHas('movimientos', $enRango)
            ->withCount(['movimientos' => $enRango])
            ->withSum(['movimientos as entradas' => fn ($consulta) => $enRango($consulta)->where('tipo', 'entrada')], 'cantidad')
            ->withSum(['movimientos as salidas' => fn ($consulta) => $enRango($consulta)->where('tipo', 'salida')], 'cantidad')
            ->orderByDesc('movimientos_count')
            ->orderBy('nombre')
            ->limit(10)
            ->get();

        return view('reportes.mas-movidos', [
            'productos' => $productos,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }
}
