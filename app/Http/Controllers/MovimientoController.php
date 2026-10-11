<?php

namespace App\Http\Controllers;

use App\Http\Requests\MovimientoRequest;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * Movimientos de inventario (CU-04, CU-05). Los dos roles registran entradas y salidas.
 * Los movimientos son inmutables (RN-09): no hay editar ni eliminar.
 * El formulario es una hoja encima del historial, como en categorías y productos.
 * Los filtros del historial llegan en la Fase 6.
 */
class MovimientoController extends Controller
{
    public function index()
    {
        return view('movimientos.index', ['movimientos' => $this->historial()]);
    }

    public function create(Request $request)
    {
        $tipo = $request->query('tipo');

        return view('movimientos.create', [
            'movimientos' => $this->historial(),
            // Solo productos activos: los inactivos no admiten movimientos
            'productos' => Producto::with('categoria')->where('activo', true)->orderBy('nombre')->get(),
            // Desde el listado de productos llega elegido (?producto_id=5&desde=productos)
            'productoElegido' => $request->integer('producto_id') ?: null,
            'tipo' => in_array($tipo, ['entrada', 'salida'], true) ? $tipo : null,
            'desdeProductos' => $request->query('desde') === 'productos',
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
        $destino = $request->input('desde') === 'productos' ? route('productos.index') : route('movimientos.index');

        $respuesta = redirect($destino)->with('success',
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

    // Historial del más reciente al más antiguo, con producto y usuario en 2 consultas (sin N+1)
    private function historial()
    {
        return MovimientoInventario::with(['producto', 'usuario'])
            ->latest('fecha')
            ->latest('id')
            ->paginate(20);
    }
}
