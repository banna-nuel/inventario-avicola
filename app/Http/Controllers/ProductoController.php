<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Productos (CU-02, CU-03 y CU-06). El listado lo ven los dos roles; crear, editar y
 * activar/desactivar son solo del administrador (routes/web.php).
 * Como en categorías, crear y editar se muestran en una hoja encima del listado, así que
 * create() y edit() también envían el listado, con los mismos filtros que tenía.
 */
class ProductoController extends Controller
{
    // Filtros del listado que se conservan al abrir y cerrar las hojas
    private const FILTROS = ['q', 'categoria', 'estado', 'page'];

    // Opciones del filtro por estado ('' es el de por defecto: solo activos)
    private const ESTADOS = ['' => 'Activos', 'stock-bajo' => 'Stock bajo', 'inactivos' => 'Inactivos', 'todos' => 'Todos'];

    public function index(Request $request)
    {
        return view('productos.index', $this->listado($request));
    }

    public function create(Request $request)
    {
        return view('productos.create', $this->listado($request));
    }

    public function store(ProductoRequest $request)
    {
        $datos = $request->validated();

        // RN-04: el stock inicial no se guarda directo. Entra como un movimiento de entrada,
        // en la misma transacción, para que el stock siempre sea la suma del historial.
        $producto = DB::transaction(function () use ($datos, $request) {
            $producto = Producto::create(Arr::except($datos, 'stock_inicial'));

            if ((float) ($datos['stock_inicial'] ?? 0) > 0) {
                MovimientoInventario::registrar($producto, 'entrada', $datos['stock_inicial'], $request->user());
            }

            return $producto;
        });

        return redirect()->route('productos.index', $this->filtros($request))
            ->with('success', "Producto «{$producto->nombre}» creado.");
    }

    public function edit(Request $request, Producto $producto)
    {
        $movimientos = $producto->movimientos()->count();

        return view('productos.edit', $this->listado($request) + [
            'producto' => $producto,
            'movimientos' => $movimientos,
            'tieneMovimientos' => $movimientos > 0,
        ]);
    }

    public function update(ProductoRequest $request, Producto $producto)
    {
        $producto->update($request->validated());

        return redirect()->route('productos.index', $this->filtros($request))
            ->with('success', "Producto «{$producto->nombre}» actualizado.");
    }

    // RN-03: un producto no se elimina; se desactiva y su historial se conserva
    public function cambiarEstado(Request $request, Producto $producto)
    {
        $request->validate(['activo' => 'required|boolean']);

        $producto->update(['activo' => $request->boolean('activo')]);

        $mensaje = $producto->activo
            ? "Producto «{$producto->nombre}» activado."
            : "Producto «{$producto->nombre}» desactivado: ya no admite movimientos y su historial se conserva.";

        return redirect()->route('productos.index', $this->filtros($request))->with('success', $mensaje);
    }

    // Listado paginado con búsqueda por nombre y filtros por categoría y estado
    private function listado(Request $request): array
    {
        // Si un filtro llega mal escrito en la URL, se ignora
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $categoria = $request->integer('categoria');   // 0 = todas
        $estado = $request->query('estado');
        $estado = is_string($estado) && isset(self::ESTADOS[$estado]) ? $estado : '';

        $productos = Producto::with('categoria')   // with(): la categoría de todos en 1 consulta (sin N+1)
            // Por nombre del producto o de su categoría (whereContiene: AppServiceProvider)
            ->when($q !== '', fn ($consulta) => $consulta->where(fn ($donde) => $donde
                ->whereContiene('nombre', $q)
                ->orWhereHas('categoria', fn ($categoria) => $categoria->whereContiene('nombre', $q))))
            ->when($categoria, fn ($consulta) => $consulta->where('categoria_id', $categoria))
            ->when($estado === '', fn ($consulta) => $consulta->where('activo', true))
            ->when($estado === 'stock-bajo', fn ($consulta) => $consulta->where('activo', true)->stockBajo())
            ->when($estado === 'inactivos', fn ($consulta) => $consulta->where('activo', false))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'q' => $q,
            'categoria' => $categoria,
            'estado' => $estado,
            'estados' => self::ESTADOS,
            'filtros' => $this->filtros($request),
        ];
    }

    // Filtros activos de la URL, para armar los enlaces: ['q' => 'maíz', 'page' => '2']
    private function filtros(Request $request): array
    {
        return array_filter(
            Arr::only($request->query(), self::FILTROS),
            fn ($valor) => is_string($valor) && $valor !== ''
        );
    }
}
