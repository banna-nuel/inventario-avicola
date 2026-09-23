<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm']);
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
});

