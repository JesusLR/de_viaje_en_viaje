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
Route::get('/pagina/imagen/{image}', [LandingController::class, 'image'])->name('landing.image');
Route::get('/catalogo', [\App\Http\Controllers\PublicCatalogController::class, 'index'])->name('catalogo.publico.index');
Route::get('/catalogo/{servicio}', [\App\Http\Controllers\PublicCatalogController::class, 'show'])->whereNumber('servicio')->name('catalogo.publico.show');
Route::get('/catalogo/{servicio}/itinerario.pdf', [\App\Http\Controllers\PublicCatalogController::class, 'itinerario'])->whereNumber('servicio')->name('catalogo.publico.itinerario');

Route::get('/cotizar', [\App\Http\Controllers\CotizacionController::class, 'formulario'])->name('cotizacion.formulario');
Route::post('/cotizar', [\App\Http\Controllers\CotizacionController::class, 'enviar'])->middleware('throttle:5,1')->name('cotizacion.enviar');

// Rutas Públicas de Autenticación (Middleware 'guest' evita que usuarios logueados accedan al login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Rutas Privadas Protegidas (Middleware 'auth' + EnsurePasswordIsChanged)
Route::middleware(['auth', EnsurePasswordIsChanged::class, \App\Http\Middleware\CheckModulePermissions::class])->group(function () {
    Route::get('/notificaciones', [\App\Http\Controllers\NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/leidas', [\App\Http\Controllers\NotificacionController::class, 'leerTodas'])->name('notificaciones.leerTodas');
    Route::post('/notificaciones/{notificacion}/leer', [\App\Http\Controllers\NotificacionController::class, 'leer'])->whereUuid('notificacion')->name('notificaciones.leer');

    Route::prefix('atencion-al-cliente/cotizaciones')->name('atencion.cotizaciones.')->controller(\App\Http\Controllers\CotizacionServicioController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/nueva', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{cotizacion}/pdf', 'pdf')->name('pdf');
        Route::get('/{cotizacion}', 'show')->name('show');
    });

    // Cambio Obligatorio de Contraseña (Accesible cuando lCambiarPassword = 1)
    Route::get('/password/cambiar', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/update', [PasswordController::class, 'updatePassword'])->name('password.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/perfil', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [\App\Http\Controllers\ProfileController::class, 'update'])->name('perfil.update');
    Route::get('/perfil/foto', [\App\Http\Controllers\ProfileController::class, 'photo'])->name('perfil.photo');

    Route::get('/agencia/cotizaciones', [\App\Http\Controllers\CotizacionController::class, 'index'])->name('cotizaciones.index');
    Route::get('/agencia/cotizaciones/{cotizacion}', [\App\Http\Controllers\CotizacionController::class, 'show'])->name('cotizaciones.show');
    Route::put('/agencia/cotizaciones/{cotizacion}/seguimiento', [\App\Http\Controllers\CotizacionController::class, 'seguimiento'])->name('cotizaciones.seguimiento');

    // Dashboard
    Route::get('/servicios/itinerarios', [\App\Http\Controllers\Servicios\ItinerarioController::class, 'index'])->name('itinerarios.index');
    Route::get('/servicios/itinerarios/nuevo', [\App\Http\Controllers\Servicios\ItinerarioController::class, 'create'])->name('itinerarios.create');
    Route::post('/servicios/itinerarios', [\App\Http\Controllers\Servicios\ItinerarioController::class, 'store'])->name('itinerarios.store');
    Route::get('/servicios/itinerarios/{itinerario}', [\App\Http\Controllers\Servicios\ItinerarioController::class, 'edit'])->name('itinerarios.edit');
    Route::put('/servicios/itinerarios/{itinerario}', [\App\Http\Controllers\Servicios\ItinerarioController::class, 'update'])->name('itinerarios.update');
    Route::get('/estadisticas', [\App\Http\Controllers\EstadisticasController::class, 'index'])->name('estadisticas.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/configuracion/variables-entorno', [\App\Http\Controllers\Configuracion\VariablesEntornoController::class, 'edit'])->name('configuracion.variables.edit');
    Route::put('/configuracion/variables-entorno', [\App\Http\Controllers\Configuracion\VariablesEntornoController::class, 'update'])->name('configuracion.variables.update');
    Route::get('/configuracion/pagina-inicio', [\App\Http\Controllers\Configuracion\LandingSettingsController::class, 'edit'])->name('configuracion.landing.edit');
    Route::put('/configuracion/pagina-inicio', [\App\Http\Controllers\Configuracion\LandingSettingsController::class, 'update'])->name('configuracion.landing.update');

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
        Route::prefix('permisos')->name('permisos.')->controller(\App\Http\Controllers\Admin\PermissionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/roles', 'store')->name('store');
            Route::put('/roles/{role}', 'update')->name('update');
            Route::delete('/roles/{role}', 'destroy')->name('destroy');
            Route::put('/usuarios/{user}', 'assign')->name('assign');
        });
        // Submódulo Usuarios
        Route::prefix('usuarios')->name('usuarios.')->group(function () {
            Route::post('/activar', [UsuarioController::class, 'activar'])->name('activar');
            Route::get('/{user}/foto', [UsuarioController::class, 'foto'])->name('foto');
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

    Route::get('agencia/vender', [\App\Http\Controllers\Agencia\VentaPaqueteController::class, 'vender'])->name('agencia.vender.index');
    Route::get('agencia/apartados', [\App\Http\Controllers\Agencia\VentaPaqueteController::class, 'apartados'])->name('agencia.apartados.index');
    Route::prefix('agencia/finanzas')->name('agencia.finanzas.')->controller(\App\Http\Controllers\Agencia\FinanzasController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'exportar')->name('exportar');
        Route::post('/', 'store')->name('store');
        Route::post('/{movimiento}/anular', 'anular')->name('anular');
    });
    Route::prefix('agencia/ventas')->name('agencia.ventas.')->controller(\App\Http\Controllers\Agencia\VentaPaqueteController::class)->group(function () {
        Route::delete('/{venta}', 'destroy')->name('destroy');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{venta}', 'show')->name('show');
        Route::get('/{venta}/comprobante', 'comprobante')->name('comprobante');
        Route::get('/{venta}/itinerario.pdf', 'itinerarioPdf')->name('itinerarioPdf');
        Route::get('/{venta}/paquetes/{detalle}/itinerario.pdf', 'itinerarioArchivo')->name('itinerarioArchivo');
        Route::get('/{venta}/compra-saldada', 'comprobanteCompra')->name('comprobanteCompra');
        Route::post('/{venta}/pagos', 'pago')->name('pago');
        Route::post('/{venta}/cancelar', 'cancelar')->name('cancelar');
        Route::post('/{venta}/finalizar', 'finalizar')->name('finalizar');
        Route::post('/{venta}/nombre', 'nombre')->name('nombre');
        Route::post('/{venta}/paquetes', 'agregarPaquete')->name('agregarPaquete');
        Route::post('/{venta}/pagos/{pago}/anular', 'anularPago')->name('anularPago');
        Route::post('/{venta}/pagos/{pago}/fecha', 'fechaPago')->name('fechaPago');
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
            Route::get('/{servicio}/itinerario.pdf', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'itinerarioPdf'])->name('itinerarioPdf');
            Route::get('/{servicio}/itinerario-archivo', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'itinerarioArchivo'])->name('itinerarioArchivo');
            Route::get('/getData/{id}', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'getData'])->name('getData');
            Route::post('/saveData', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'saveData'])->name('saveData');
            Route::post('/deleteData', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'deleteData'])->name('deleteData');
            Route::post('/simularPrecios', [\App\Http\Controllers\Servicios\ServicioTuristicoController::class, 'simularPrecios'])->name('simularPrecios');
        });
    });
});
