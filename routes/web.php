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

        // Submódulo Categorías Turísticas
        Route::prefix('categorias')->name('categorias.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'index'])->name('index');
            Route::get('/gridData', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'deleteData'])->name('deleteData');
        });

        // Submódulo Destinos Turísticos
        Route::prefix('destinos')->name('destinos.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'index'])->name('index');
            Route::get('/gridData', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'deleteData'])->name('deleteData');
        });

        // Submódulo Proveedores Turísticos
        Route::prefix('proveedores')->name('proveedores.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Catalogos\ProveedorController::class, 'index'])->name('index');
            Route::get('/gridData', [\App\Http\Controllers\Catalogos\ProveedorController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [\App\Http\Controllers\Catalogos\ProveedorController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Catalogos\ProveedorController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Catalogos\ProveedorController::class, 'deleteData'])->name('deleteData');
        });
    });

    // Módulo Servicios y Paquetes Turísticos
    Route::prefix('servicios')->name('servicios.')->group(function () {
        // Alias de compatibilidad para Categorías y Destinos
        Route::prefix('categorias')->name('categorias.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'index'])->name('index');
            Route::get('/gridData', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Servicios\CategoriaTuristicaController::class, 'deleteData'])->name('deleteData');
        });

        Route::prefix('destinos')->name('destinos.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'index'])->name('index');
            Route::get('/gridData', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Servicios\DestinoTuristicoController::class, 'deleteData'])->name('deleteData');
        });

        // Servicios y Paquetes Turísticos
        Route::prefix('paquetes')->name('paquetes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'index'])->name('index');
            Route::get('/gridData', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'gridData'])->name('gridData');
            Route::get('/getData/{id}', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'deleteData'])->name('deleteData');
            Route::post('/simularPrecios', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'simularPrecios'])->name('simularPrecios');
        });
    });
});
