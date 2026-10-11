<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrosHistorialRequest;
use App\Http\Requests\MovimientoRequest;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Carbon;

/**
 * Movimientos de inventario (CU-04, CU-05, CU-06). Los dos roles registran entradas y salidas
 * y consultan el historial con filtros. Los movimientos son inmutables (RN-09).
 * El formulario es una hoja encima del historial, como en categorías y productos.
 */
class MovimientoController extends Controller
{
    // A dónde vuelve la hoja si se abrió desde otra pantalla (?volver=productos)
    private const VOLVER = ['productos' => 'productos.index', 'dashboard' => 'dashboard'];

    public function index(FiltrosHistorialRequest $request)
    {
        return view('movimientos.index', $this->historial($request));
    }

    public function create(FiltrosHistorialRequest $request)
    {
        $historial = $this->historial($request);
        $volver = $this->volver($request->query('volver'));

        return view('movimientos.create', $historial + [
            // Solo productos activos: los inactivos no admiten movimientos
            'productos' => Producto::with('categoria')->where('activo', true)->orderBy('nombre')->get(),
            // Producto elegido: el del enlace (?producto_id=5) o, si no, el del filtro del historial
            'productoElegido' => $request->integer('producto_id') ?: ($historial['filtros']['producto'] ?? null),
            'tipo' => $historial['filtros']['tipo'] ?? null,
            'volver' => $volver,
            'cerrar' => $volver ? route(self::VOLVER[$volver]) : route('movimientos.index', $historial['filtros']),
        ]);
    }

    public function store(MovimientoRequest $request)
    {
        $datos = $request->validated();
        $producto = Producto::findOrFail($datos['producto_id']);

        // RN-04 / RN-01: transacción con bloqueo y validación de stock (ver el modelo)
        $movimiento = MovimientoInventario::registrar($producto, $datos['tipo'], $datos['cantidad'], $request->user());
        $producto->refresh();

        $unidad = $producto->unidad_medida;
        $volver = $this->volver($request->input('volver'));

        $respuesta = redirect()->route($volver ? self::VOLVER[$volver] : 'movimientos.index')->with('success',
            ucfirst($movimiento->tipo)." de {$unidad->cantidad($movimiento->cantidad)} de «{$producto->nombre}» registrada. "
            ."Stock: {$unidad->cantidad($producto->stock_actual)}."
        );

        // Alerta visual si el producto queda por debajo de su mínimo (CU-05, RN-02)
        if ($producto->enStockBajo()) {
            $respuesta->with('aviso',
                "«{$producto->nombre}» ".($movimiento->tipo === 'salida' ? 'quedó' : 'sigue')
                ." en stock bajo: {$unidad->cantidad($producto->stock_actual)} (mínimo {$unidad->cantidad($producto->stock_minimo)})."
            );
        }

        return $respuesta;
    }

    // Historial filtrado, del más reciente al más antiguo
    private function historial(FiltrosHistorialRequest $request): array
    {
        $filtros = $request->filtros();

        $movimientos = MovimientoInventario::with(['producto', 'usuario'])   // sin N+1
            ->when($filtros['producto'] ?? null, fn ($consulta, $id) => $consulta->where('producto_id', $id))
            ->when($filtros['tipo'] ?? null, fn ($consulta, $tipo) => $consulta->where('tipo', $tipo))
            // Fechas inclusivas, en la zona de la app (America/Bogota): desde las 00:00 de "desde"
            // hasta antes de las 00:00 del día siguiente a "hasta"
            ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->where('fecha', '>=', Carbon::parse($desde)->startOfDay()))
            ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->where('fecha', '<', Carbon::parse($hasta)->addDay()->startOfDay()))
            ->latest('fecha')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();   // la paginación conserva los filtros

        return [
            'movimientos' => $movimientos,
            'filtros' => $filtros,
            // Para el select del filtro: todos los productos, también los inactivos (tienen historial)
            'productosFiltro' => Producto::orderBy('nombre')->pluck('nombre', 'id'),
        ];
    }

    private function volver(mixed $valor): ?string
    {
        return is_string($valor) && isset(self::VOLVER[$valor]) ? $valor : null;
    }
}
