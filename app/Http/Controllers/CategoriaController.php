<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\Request;

/**
 * CRUD de categorías (solo administrador, ver routes/web.php).
 * Crear y editar se muestran como una hoja encima del listado, por eso
 * create() y edit() también envían el listado a la vista.
 */
class CategoriaController extends Controller
{
    // ?q= llega desde "Ver todos" en Search: filtra por nombre
    public function index(Request $request)
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        return view('categorias.index', ['categorias' => $this->listado($q), 'q' => $q]);
    }

    public function create()
    {
        return view('categorias.create', ['categorias' => $this->listado()]);
    }

    public function store(CategoriaRequest $request)
    {
        $categoria = Categoria::create($request->validated());

        return redirect()->route('categorias.index')
            ->with('success', "Categoría «{$categoria->nombre}» creada.");
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', [
            'categorias' => $this->listado(),
            'categoria' => $categoria,
        ]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());

        return redirect()->route('categorias.index')
            ->with('success', "Categoría «{$categoria->nombre}» actualizada.");
    }

    public function destroy(Categoria $categoria)
    {
        // RN-03: una categoría con productos (activos o no) no se puede eliminar
        $productos = $categoria->productos()->count();

        if ($productos > 0) {
            return redirect()->route('categorias.index')
                ->with('error', "No se puede eliminar «{$categoria->nombre}» porque tiene {$productos} "
                    .($productos === 1 ? 'producto' : 'productos').'. Primero cámbialos de categoría.');
        }

        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', "Categoría «{$categoria->nombre}» eliminada.");
    }

    // Categorías en orden alfabético con su número de productos (una sola consulta, sin N+1)
    private function listado(string $q = '')
    {
        return Categoria::withCount('productos')
            ->when($q !== '', fn ($consulta) => $consulta->whereContiene('nombre', $q))
            ->orderBy('nombre')
            ->get();
    }
}
