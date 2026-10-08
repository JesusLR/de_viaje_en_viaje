<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

// Redirección Raíz
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Rutas Públicas de Autenticación (Middleware 'guest' evita que usuarios logueados accedan)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Rutas Privadas Protegidas (Middleware 'auth' requiere inicio de sesión)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
