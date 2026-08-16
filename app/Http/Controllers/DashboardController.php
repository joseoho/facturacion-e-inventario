<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard con métricas y gráficos
     */
    public function index()
    {
        // Inicializar todas las variables con valores por defecto
        $metricas = $this->getMetricasDefault();
        $alertasStock = collect();
        $ventasSemana = ['labels' => [], 'data' => []];
        $productosTop = collect();
        $ventasCategoria = collect();
        $facturasRecientes = collect();

        try {
            // Métricas principales
            $metricas = $this->getMetricasPrincipales();
        } catch (\Exception $e) {
            Log::error('Error en getMetricasPrincipales: ' . $e->getMessage());
        }

        try {
            // Alertas de stock mínimo (< 5.000 Kg)
            $alertasStock = $this->getAlertasStock();
        } catch (\Exception $e) {
            Log::error('Error en getAlertasStock: ' . $e->getMessage());
        }

        try {
            // Datos para gráfico de ventas de la semana
            $ventasSemana = $this->getVentasSemana();
        } catch (\Exception $e) {
            Log::error('Error en getVentasSemana: ' . $e->getMessage());
        }

        try {
            // Productos más vendidos del mes
            $productosTop = $this->getProductosTop();
        } catch (\Exception $e) {
            Log::error('Error en getProductosTop: ' . $e->getMessage());
        }

        try {
            // Ventas por categoría
            $ventasCategoria = $this->getVentasCategoria();
        } catch (\Exception $e) {
            Log::error('Error en getVentasCategoria: ' . $e->getMessage());
        }

        try {
            // Facturas recientes
            $facturasRecientes = Factura::with(['cliente', 'moneda'])
                ->latest()
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al obtener facturas recientes: ' . $e->getMessage());
        }

        // Verificar que todas las variables existan antes de pasar a la vista
        return view('dashboard.index', [
            'metricas' => $metricas,
            'alertasStock' => $alertasStock,
            'ventasSemana' => $ventasSemana,
            'productosTop' => $productosTop,
            'ventasCategoria' => $ventasCategoria,
            'facturasRecientes' => $facturasRecientes
        ]);
    }

    /**
     * Obtener métricas principales
     */
    private function getMetricasPrincipales()
    {
        // Ventas del día
        $ventasHoy = Factura::whereDate('fecha_emision', today())
            ->where('estado', 'pagada')
            ->sum('total');

        // Ventas del mes
        $ventasMes = Factura::whereMonth('fecha_emision', now()->month)
            ->whereYear('fecha_emision', now()->year)
            ->where('estado', 'pagada')
            ->sum('total');

        // Facturas hoy
        $facturasHoy = Factura::whereDate('fecha_emision', today())->count();

        // Clientes nuevos hoy
        $clientesNuevos = Cliente::whereDate('created_at', today())->count();

        // Productos sin stock
        $productosSinStock = Producto::where('stock_kg', '<=', 0)->count();

        // Productos con stock bajo (< 5 Kg)
        $productosStockBajo = Producto::where('stock_kg', '>', 0)
            ->where('stock_kg', '<=', 5)
            ->count();

        // Total de productos activos
        $totalProductos = Producto::where('activo', true)->count();

        // Clientes totales
        $totalClientes = Cliente::where('activo', true)->count();

        // Facturas pendientes
        $facturasPendientes = Factura::where('estado', 'pendiente')->count();

        // Crecimiento de ventas vs mes anterior
        $ventasMesAnterior = Factura::whereMonth('fecha_emision', now()->subMonth()->month)
            ->whereYear('fecha_emision', now()->subMonth()->year)
            ->where('estado', 'pagada')
            ->sum('total');

        $crecimiento = $ventasMesAnterior > 0 
            ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100 
            : 0;

        return [
            'ventas_hoy' => $ventasHoy,
            'ventas_mes' => $ventasMes,
            'facturas_hoy' => $facturasHoy,
            'clientes_nuevos' => $clientesNuevos,
            'productos_sin_stock' => $productosSinStock,
            'productos_stock_bajo' => $productosStockBajo,
            'total_productos' => $totalProductos,
            'total_clientes' => $totalClientes,
            'facturas_pendientes' => $facturasPendientes,
            'crecimiento' => round($crecimiento, 1),
        ];
    }

    /**
     * Métricas por defecto en caso de error
     */
    private function getMetricasDefault()
    {
        return [
            'ventas_hoy' => 0,
            'ventas_mes' => 0,
            'facturas_hoy' => 0,
            'clientes_nuevos' => 0,
            'productos_sin_stock' => 0,
            'productos_stock_bajo' => 0,
            'total_productos' => 0,
            'total_clientes' => 0,
            'facturas_pendientes' => 0,
            'crecimiento' => 0,
        ];
    }

    /**
     * Obtener alertas de stock mínimo (menos de 5 Kg)
     */
    private function getAlertasStock()
    {
        $productos = Producto::where('activo', true)
            ->where('stock_kg', '<=', 5)
            ->where('stock_kg', '>', 0)
            ->orderBy('stock_kg', 'asc')
            ->select('id', 'nombre', 'sku', 'stock_kg', 'stock_minimo')
            ->get();

        return $productos->map(function ($producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'sku' => $producto->sku,
                'stock_kg' => $producto->stock_kg,
                'stock_minimo' => $producto->stock_minimo,
                'nivel' => $this->getNivelAlerta($producto->stock_kg),
                'color' => $this->getColorAlerta($producto->stock_kg)
            ];
        });
    }

    /**
     * Obtener nivel de alerta según el stock
     */
    private function getNivelAlerta($stock)
    {
        if ($stock <= 0) return 'Sin Stock';
        if ($stock <= 1) return 'Crítico';
        if ($stock <= 3) return 'Bajo';
        return 'Alerta';
    }

    /**
     * Obtener color de alerta según el stock
     */
    private function getColorAlerta($stock)
    {
        if ($stock <= 0) return 'danger';
        if ($stock <= 1) return 'danger';
        if ($stock <= 3) return 'warning';
        return 'info';
    }

    /**
     * Obtener datos de ventas de la semana para gráfico
     */
    private function getVentasSemana()
    {
        $labels = [];
        $data = [];

        // Últimos 7 días
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i);
            $labels[] = $fecha->format('d/m');
            
            $total = Factura::whereDate('fecha_emision', $fecha)
                ->where('estado', 'pagada')
                ->sum('total');
            
            $data[] = round($total, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Obtener productos más vendidos del mes
     */
    private function getProductosTop()
    {
        return DB::table('factura_lineas')
            ->join('productos', 'factura_lineas.producto_id', '=', 'productos.id')
            ->join('facturas', 'factura_lineas.factura_id', '=', 'facturas.id')
            ->where('facturas.estado', 'pagada')
            ->whereMonth('facturas.fecha_emision', now()->month)
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.sku',
                DB::raw('SUM(factura_lineas.cantidad_kg) as total_kg'),
                DB::raw('SUM(factura_lineas.total) as total_ventas'),
                DB::raw('COUNT(factura_lineas.id) as veces_vendido')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.sku')
            ->orderBy('total_kg', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Obtener ventas por categoría
     */
    private function getVentasCategoria()
    {
        // Verificar si la tabla categorías existe
        try {
            $categorias = DB::table('factura_lineas')
                ->join('productos', 'factura_lineas.producto_id', '=', 'productos.id')
                ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
                ->join('facturas', 'factura_lineas.factura_id', '=', 'facturas.id')
                ->where('facturas.estado', 'pagada')
                ->whereMonth('facturas.fecha_emision', now()->month)
                ->select(
                    'categorias.id',
                    'categorias.nombre',
                    DB::raw('SUM(factura_lineas.total) as total_ventas')
                )
                ->groupBy('categorias.id', 'categorias.nombre')
                ->orderBy('total_ventas', 'desc')
                ->get();
                
            return $categorias;
        } catch (\Exception $e) {
            // Si la tabla categorías no existe, retornar colección vacía
            return collect();
        }
    }
}