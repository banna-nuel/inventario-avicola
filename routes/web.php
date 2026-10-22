<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
 * Mapa de rutas de la v1. Toda ruta tiene nombre (recurso.accion) y en el código se usa
 * route('nombre'), nunca la URL escrita a mano. La matriz rol × ruta se prueba en
 * tests/Feature/RutasTest.php: si agregas o cambias una ruta, actualiza ese test.
 *
 * No existen a propósito (responden 404 o 405):
 * - show en ningún recurso
 * - destroy de productos y de usuarios: se desactivan (RN-03)
 * - edit, update y destroy de movimientos: son inmutables (RN-09)
 */

// La raíz lleva a Inicio; si no hay sesión, el middleware 'auth' manda al login
Route::get('/', fn () => redirect()->route('home'))->name('raiz');

// Solo para quien NO ha iniciado sesión ('guest'); con sesión, /login lleva a Inicio
Route::middleware('guest')->group(function () {
    // El middleware 'auth' redirige a la ruta llamada 'login' cuando no hay sesión
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.entrar');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Con sesión iniciada y cuenta activa (matriz de permisos: docs/uml/00-contexto-y-reglas.md,
// sección 3.3; la verifica RutasTest::matriz())
Route::middleware(['auth', 'activo'])->group(function () {

    // ---- Administrador y encargado ----
    // Controladores "invocables" (un solo método __invoke)
    Route::get('/home', InicioController::class)->name('home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', BusquedaController::class)->name('search');

    // Productos: el encargado solo ve el listado
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');

    // Movimientos: los dos roles registran entradas y salidas y ven el historial con filtros (inmutables: RN-09)
    Route::get('/movimientos', [MovimientoController::class, 'index'])->name('movimientos.index');
    Route::get('/movimientos/create', [MovimientoController::class, 'create'])->name('movimientos.create');
    Route::post('/movimientos', [MovimientoController::class, 'store'])->name('movimientos.store');

    // ---- Solo administrador ----
    Route::middleware('role:admin')->group(function () {
        Route::resource('categorias', CategoriaController::class)->except(['show']);

        // Productos: crear, editar y activar/desactivar (no hay destroy: RN-03)
        Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/productos/{producto}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{producto}/estado', [ProductoController::class, 'cambiarEstado'])->name('productos.cambiar-estado');

        // Usuarios: crear, editar y activar/desactivar (no hay destroy: RN-03)
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.cambiar-estado');

        // Reportes en pantalla (se pueden imprimir; sin exportar en la v1)
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/stock-por-categoria', [ReporteController::class, 'stockPorCategoria'])->name('reportes.stock-por-categoria');
        Route::get('/reportes/stock-bajo', [ReporteController::class, 'stockBajo'])->name('reportes.stock-bajo');
        Route::get('/reportes/mas-movidos', [ReporteController::class, 'masMovidos'])->name('reportes.mas-movidos');
    });
});
