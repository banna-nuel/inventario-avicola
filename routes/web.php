<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;

Route::get('/', function () {
    return view('welcome');
});

// El middleware 'auth' redirige a la ruta llamada 'login' cuando no hay sesión
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    Route::get('/search', function () {
        return view('search');
    });

    Route::resource('categorias', CategoriaController::class)->except(['show']);
});

