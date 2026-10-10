<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;

// La raíz lleva a /home; si no hay sesión, el middleware 'auth' manda al login
Route::redirect('/', '/home');

// Solo para quien NO ha iniciado sesión ('guest'); con sesión, /login redirige a /home
Route::middleware('guest')->group(function () {
    // El middleware 'auth' redirige a la ruta llamada 'login' cuando no hay sesión
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Con sesión iniciada y cuenta activa (matriz de permisos del PROMPT, sección 5)
Route::middleware(['auth', 'activo'])->group(function () {
    // Administrador y encargado
    Route::view('/home', 'home')->name('home');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/search', 'search')->name('search');

    // Solo administrador
    Route::middleware('role:admin')->group(function () {
        Route::resource('categorias', CategoriaController::class)->except(['show']);
    });
});
