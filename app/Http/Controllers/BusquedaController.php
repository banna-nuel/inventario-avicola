<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Search (GET ?q=&tipo=). El encargado busca solo productos (por nombre o categoría);
 * el administrador busca además categorías y usuarios. Resultados agrupados, máximo
 * 10 por grupo, con un enlace "Ver todos" al listado filtrado.
 */
class BusquedaController extends Controller
{
    private const MINIMO_CARACTERES = 2;

    private const POR_GRUPO = 10;

    public function __invoke(Request $request)
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        // Grupos que este rol puede buscar; el filtro ?tipo= elige uno solo
        $permitidos = $request->user()->esAdmin() ? ['productos', 'categorias', 'usuarios'] : ['productos'];
        $tipo = in_array($request->query('tipo'), $permitidos, true) ? $request->query('tipo') : '';

        $grupos = [];
        if (mb_strlen($q) >= self::MINIMO_CARACTERES) {
            foreach ($permitidos as $grupo) {
                if ($tipo === '' || $tipo === $grupo) {
                    $grupos[$grupo] = $this->{$grupo}($q);
                }
            }
        }

        return view('search', [
            'q' => $q,
            'tipo' => $tipo,
            'permitidos' => $permitidos,
            'muyCorto' => $q !== '' && mb_strlen($q) < self::MINIMO_CARACTERES,
            'grupos' => $grupos,
            'total' => array_sum(array_column($grupos, 'total')),
        ]);
    }

    // Cada grupo devuelve el total y los primeros 10 (dos consultas: count y limit)
    private function productos(string $q): array
    {
        $consulta = Producto::query()->where(fn ($donde) => $donde
            ->whereContiene('nombre', $q)
            ->orWhereHas('categoria', fn ($categoria) => $categoria->whereContiene('nombre', $q)));

        return [
            'total' => $consulta->count(),
            'items' => $consulta->with('categoria')->orderBy('nombre')->limit(self::POR_GRUPO)->get(),
            'verTodos' => route('productos.index', ['q' => $q, 'estado' => 'todos']),
        ];
    }

    private function categorias(string $q): array
    {
        $consulta = Categoria::query()->whereContiene('nombre', $q);

        return [
            'total' => $consulta->count(),
            'items' => $consulta->withCount('productos')->orderBy('nombre')->limit(self::POR_GRUPO)->get(),
            'verTodos' => route('categorias.index', ['q' => $q]),
        ];
    }

    private function usuarios(string $q): array
    {
        $consulta = User::query()->where(fn ($donde) => $donde
            ->whereContiene('name', $q)
            ->orWhereContiene('email', $q));

        return [
            'total' => $consulta->count(),
            'items' => $consulta->orderBy('name')->limit(self::POR_GRUPO)->get(),
            'verTodos' => route('usuarios.index', ['q' => $q]),
        ];
    }
}
