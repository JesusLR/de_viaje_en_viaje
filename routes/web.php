<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Catalogos\ClienteController;
use App\Http\Middleware\EnsurePasswordIsChanged;

// Landing Page Pública de la Agencia de Viajes ("De Viaje")
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Rutas Públicas de Autenticación (Middleware 'guest' evita que usuarios logueados accedan al login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Rutas Privadas Protegidas (Middleware 'auth' + EnsurePasswordIsChanged)
Route::middleware(['auth', EnsurePasswordIsChanged::class])->group(function () {
    // Cambio Obligatorio de Contraseña (Accesible cuando lCambiarPassword = 1)
    Route::get('/password/cambiar', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/update', [PasswordController::class, 'updatePassword'])->name('password.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo Categorías (CRUD AJAX)
    Route::prefix('categoria')->name('categoria.')->group(function () {
        Route::get('/', [CategoriaController::class, 'index'])->name('index');
        Route::get('/gridData', [CategoriaController::class, 'gridData'])->name('gridData');
        Route::get('/getData/{id}', [CategoriaController::class, 'getData'])->name('getData');
        Route::post('/saveData', [CategoriaController::class, 'saveData'])->name('saveData');
        Route::post('/deleteData', [CategoriaController::class, 'deleteData'])->name('deleteData');
    });

    // Módulo Administración
    Route::prefix('admin')->name('admin.')->group(function () {
        // Submódulo Usuarios
        Route::prefix('usuarios')->name('usuarios.')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('index');
            Route::get('/gridData', [UsuarioController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [UsuarioController::class, 'getData'])->name('getData');
            Route::post('/saveData', [UsuarioController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [UsuarioController::class, 'deleteData'])->name('deleteData');
        });
    });

    // Módulo Catálogos
    Route::prefix('catalogos')->name('catalogos.')->group(function () {
        // Submódulo Clientes
        Route::prefix('clientes')->name('clientes.')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->name('index');
            Route::get('/gridData', [ClienteController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [ClienteController::class, 'getData'])->name('getData');
            Route::post('/saveData', [ClienteController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [ClienteController::class, 'deleteData'])->name('deleteData');
        });
    });
});
