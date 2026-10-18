<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Usuarios (CU-07), solo administrador. Mismo patrón de hoja que categorías y productos.
 * No hay eliminar: una cuenta se desactiva (RN-03) y el middleware 'activo' cierra la
 * sesión de quien la tenga abierta en su siguiente acción.
 */
class UsuarioController extends Controller
{
    // Filtros del listado que se conservan al abrir y cerrar las hojas
    private const FILTROS = ['q', 'page'];

    public function index(Request $request)
    {
        return view('usuarios.index', $this->listado($request));
    }

    public function create(Request $request)
    {
        return view('usuarios.create', $this->listado($request));
    }

    public function store(UsuarioRequest $request)
    {
        // La contraseña se guarda cifrada sola: el modelo User tiene el cast 'password' => 'hashed'
        $usuario = User::create($request->validated() + ['activo' => true]);

        return redirect()->route('usuarios.index', $this->filtros($request))
            ->with('success', "Usuario «{$usuario->name}» creado.");
    }

    public function edit(Request $request, User $usuario)
    {
        return view('usuarios.edit', $this->listado($request) + [
            'usuario' => $usuario,
            'esSuPropiaCuenta' => $usuario->is($request->user()),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario)
    {
        $datos = $request->validated();

        // Contraseña vacía al editar = se queda la que tenía
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index', $this->filtros($request))
            ->with('success', "Usuario «{$usuario->name}» actualizado.");
    }

    public function cambiarEstado(Request $request, User $usuario)
    {
        $request->validate(['activo' => 'required|boolean']);
        $activo = $request->boolean('activo');

        // RN-07: nadie desactiva su propia cuenta; así siempre queda al menos un administrador activo
        if (! $activo && $usuario->is($request->user())) {
            return redirect()->route('usuarios.index', $this->filtros($request))
                ->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $usuario->update(['activo' => $activo]);

        $mensaje = $activo
            ? "Usuario «{$usuario->name}» activado."
            : "Usuario «{$usuario->name}» desactivado: ya no puede iniciar sesión y, si la tenía abierta, se cerrará en su siguiente acción.";

        return redirect()->route('usuarios.index', $this->filtros($request))->with('success', $mensaje);
    }

    // Listado paginado (activos primero), con búsqueda por nombre o correo
    private function listado(Request $request): array
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        $usuarios = User::query()
            ->when($q !== '', fn ($consulta) => $consulta->where(fn ($donde) => $donde
                ->whereContiene('name', $q)
                ->orWhereContiene('email', $q)))
            ->orderByDesc('activo')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return [
            'usuarios' => $usuarios,
            'q' => $q,
            'filtros' => $this->filtros($request),
        ];
    }

    // Filtros activos de la URL, para armar los enlaces: ['q' => 'ana', 'page' => '2']
    private function filtros(Request $request): array
    {
        return array_filter(
            Arr::only($request->query(), self::FILTROS),
            fn ($valor) => is_string($valor) && $valor !== ''
        );
    }
}
