<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    AuthController,
    DashboardController,
    TasaCambioController,
    ProductoController,
    FacturaController,
    ClienteController,
    CategoriaController,
    ReporteController,
    MonedaController
};

Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Monedas - Solo administradores
   Route::resource('monedas', MonedaController::class);
    
    // Módulo de Tasas de Cambio - Solo administradores
 Route::prefix('tasas')->name('tasas.')->middleware('role:admin')->group(function () {
    // Rutas específicas - PRIMERO
    Route::get('/ultimas', [TasaCambioController::class, 'ultimasTasas'])->name('ultimasTasas');
    Route::get('/historial', [TasaCambioController::class, 'historial'])->name('historial');
    Route::get('/export', [TasaCambioController::class, 'export'])->name('export');
    Route::post('/actualizar-precios', [TasaCambioController::class, 'actualizarPrecios'])->name('actualizar-precios');
    
    // Rutas con parámetros - DESPUÉS
    Route::get('/', [TasaCambioController::class, 'index'])->name('index');
    Route::get('/create', [TasaCambioController::class, 'create'])->name('create');
    Route::post('/', [TasaCambioController::class, 'store'])->name('store');
    Route::get('/{id}', [TasaCambioController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [TasaCambioController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TasaCambioController::class, 'update'])->name('update');
    Route::delete('/{id}', [TasaCambioController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/duplicate', [TasaCambioController::class, 'duplicate'])->name('duplicate');
});
    
// Clientes
    // Route::resource('clientes', ClienteController::class);
    // Route::get('clientes/{cliente}/facturas', [ClienteController::class, 'facturas'])->name('clientes.facturas');
    
    // Módulo de Categorías - Solo administradores
  Route::resource('categorias', CategoriaController::class);
    
    // Productos
    Route::resource('productos', ProductoController::class);
    Route::get('productos/{producto}/precios', [ProductoController::class, 'precios'])->name('productos.precios');
    Route::post('productos/{producto}/precios', [ProductoController::class, 'storePrecio'])->name('productos.precios.store');
    Route::delete('productos/precios/{precio}', [ProductoController::class, 'destroyPrecio'])->name('productos.precios.destroy');
     // 🆕 Ruta para buscar productos (AJAX)
    Route::get('facturas/buscar-productos', [FacturaController::class, 'buscarProductos'])
        ->name('facturas.buscar-productos');    
    // Módulo de Clientes - Administradores y Vendedores
    Route::resource('clientes', ClienteController::class);
    Route::get('clientes/buscar', [ClienteController::class, 'buscar'])->name('clientes.buscar');
    Route::post('clientes/{cliente}/cambiar-estado', [ClienteController::class, 'cambiarEstado'])->name('clientes.cambiar-estado');
    Route::get('clientes/{cliente}/facturas', [ClienteController::class, 'facturas'])->name('clientes.facturas');
    // Módulo de Facturación - Administradores y Vendedores
    Route::resource('facturas', FacturaController::class);
     Route::get('productos/buscar', [ProductoController::class, 'buscar'])->name('productos.buscar');
    Route::post('facturas/{factura}/anular', [FacturaController::class, 'anular'])->name('facturas.anular');
    Route::post('facturas/{factura}/pagar', [FacturaController::class, 'pagar'])->name('facturas.pagar');
    Route::get('facturas/{factura}/pdf', [FacturaController::class, 'pdf'])->name('facturas.pdf');
    Route::get('facturas/{factura}/imprimir', [FacturaController::class, 'imprimir'])->name('facturas.imprimir');
    
    // Reportes - Solo administradores
    Route::prefix('reportes')->name('reportes.')->middleware('role:admin')->group(function () {
    // Reporte de Inventario
    Route::get('inventario', [ReporteController::class, 'inventario'])->name('inventario');
    Route::get('inventario/pdf', [ReporteController::class, 'inventarioPDF'])->name('inventario.pdf');
    
    // 👇 ESTA ES LA RUTA CORRECTA - Sin 'productos.'
    Route::get('stock-bajo', [ReporteController::class, 'stockBajo'])->name('stock-bajo');
    
    // Reporte de Ventas Diarias
    Route::get('ventas/diarias', [ReporteController::class, 'ventasDiarias'])->name('ventas.diarias');
    
    // Reporte de Facturas
    Route::get('facturas', [ReporteController::class, 'facturas'])->name('facturas');
    Route::get('facturas/excel', [ReporteController::class, 'facturasExcel'])->name('facturas.excel');
});
});

