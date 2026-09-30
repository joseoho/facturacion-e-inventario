<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    DashboardController,
    TasaCambioController,
    ProductoController,
    FacturaController,
    ClienteController,
    CategoriaController,
    ReporteController,
    MonedaController,
    HomeController
};

// ============================================================
// Autenticación (login, register, reset password, etc.)
// ============================================================
Auth::routes();

// ============================================================
// /home redirige al dashboard real (compatibilidad laravel/ui)
// ============================================================
Route::get('/home', [HomeController::class, 'index'])->name('home');

// ============================================================
// RUTAS AUTENTICADAS
// ============================================================
Route::middleware(['auth'])->group(function () {

    // ------------------------------------------------------------
    // Dashboard (accesible por admin y vendedor)
    // ------------------------------------------------------------
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------------------------
    // FACTURAS (admin y vendedor)
    // ------------------------------------------------------------
    Route::prefix('facturas')->name('facturas.')->group(function () {
        // Ruta específica ANTES del resource para que no la capture {factura}
        Route::get('buscar-productos', [FacturaController::class, 'buscarProductos'])
            ->name('buscar-productos');

        Route::post('{factura}/anular', [FacturaController::class, 'anular'])
            ->name('anular');
        Route::post('{factura}/pagar', [FacturaController::class, 'pagar'])
            ->name('pagar');
        Route::get('{factura}/pdf', [FacturaController::class, 'pdf'])
            ->name('pdf');
        Route::get('{factura}/imprimir', [FacturaController::class, 'imprimir'])
            ->name('imprimir');

        Route::resource('/', FacturaController::class)
            ->parameters(['' => 'factura'])
            ->except(['show'])
            ->names([
                'index'   => 'index',
                'create'  => 'create',
                'store'   => 'store',
                'edit'    => 'edit',
                'update'  => 'update',
                'destroy' => 'destroy',
            ]);

        Route::get('{factura}', [FacturaController::class, 'show'])->name('show');
    });

    // ------------------------------------------------------------
    // CLIENTES (admin y vendedor)
    // ------------------------------------------------------------
    Route::resource('clientes', ClienteController::class);
    Route::get('clientes/{cliente}/facturas', [ClienteController::class, 'facturas'])
        ->name('clientes.facturas');

    // ------------------------------------------------------------
    // PRODUCTOS (admin y vendedor)
    // ------------------------------------------------------------
    Route::resource('productos', ProductoController::class);
    Route::get('productos/{producto}/precios', [ProductoController::class, 'precios'])
        ->name('productos.precios');
    Route::post('productos/{producto}/precios', [ProductoController::class, 'storePrecio'])
        ->name('productos.precios.store');
    Route::delete('productos/precios/{precio}', [ProductoController::class, 'destroyPrecio'])
        ->name('productos.precios.destroy');

    // ------------------------------------------------------------
    // SOLO ADMIN — Monedas
    // ------------------------------------------------------------
    Route::middleware('role:admin')->group(function () {
        Route::resource('monedas', MonedaController::class);
        Route::resource('categorias', CategoriaController::class);
    });

    // ------------------------------------------------------------
    // SOLO ADMIN — Tasas de cambio
    // ------------------------------------------------------------
    Route::prefix('tasas')->name('tasas.')->middleware('role:admin')->group(function () {
        Route::get('ultimas', [TasaCambioController::class, 'ultimasTasas'])
            ->name('ultimasTasas');
        Route::get('historial', [TasaCambioController::class, 'historial'])
            ->name('historial');
        Route::get('export', [TasaCambioController::class, 'export'])
            ->name('export');
        Route::post('actualizar-precios', [TasaCambioController::class, 'actualizarPrecios'])
            ->name('actualizar-precios');

        Route::get('/', [TasaCambioController::class, 'index'])->name('index');
        Route::get('create', [TasaCambioController::class, 'create'])->name('create');
        Route::post('/', [TasaCambioController::class, 'store'])->name('store');
        Route::get('{id}', [TasaCambioController::class, 'show'])->name('show');
        Route::get('{id}/edit', [TasaCambioController::class, 'edit'])->name('edit');
        Route::put('{id}', [TasaCambioController::class, 'update'])->name('update');
        Route::delete('{id}', [TasaCambioController::class, 'destroy'])->name('destroy');
        Route::get('{id}/duplicate', [TasaCambioController::class, 'duplicate'])
            ->name('duplicate');
    });

    // ------------------------------------------------------------
    // SOLO ADMIN — Reportes
    // ------------------------------------------------------------
    Route::prefix('reportes')->name('reportes.')->middleware('role:admin')->group(function () {
        Route::get('inventario', [ReporteController::class, 'inventario'])
            ->name('inventario');
        Route::get('inventario/pdf', [ReporteController::class, 'inventarioPDF'])
            ->name('inventario.pdf');
        Route::get('stock-bajo', [ReporteController::class, 'stockBajo'])
            ->name('stock-bajo');
        Route::get('ventas/diarias', [ReporteController::class, 'ventasDiarias'])
            ->name('ventas.diarias');
    });
});