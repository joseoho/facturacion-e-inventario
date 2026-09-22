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
        /**
     * Dashboard principal.
     *
     * Nota arquitectónica: cada método privado lanza excepciones si algo
     * falla. NO las atrapamos aquí — el handler global de Laravel las
     * registrará y mostrará la página de error. Es preferible que el
     * dashboard falle ruidosamente a que muestre "0 ventas" silenciosamente
     * porque una query tiene un typo.
     *
     * Si en el futuro se necesita resiliencia parcial (ej. dashboard se
     * muestra aunque falle 1 widget), se implementará con un patrón
     * explícito de "safe call" que registre la métrica fallida en un
     * array de warnings visible en la vista, NO con try/catch ciego.
     */
       public function index()
    {
        return view('dashboard.index', [
            'metricas'             => $this->getMetricasPrincipales(),
            'desgloseMesAnterior'  => $this->getDesgloseMesAnterior(),
            'alertasStock'         => $this->getAlertasStock(),
            'ventasSemana'         => $this->getVentasSemana(),
            'productosTop'         => $this->getProductosTop(),
            'ventasCategoria'      => $this->getVentasCategoria(),
            'facturasRecientes'    => Factura::with(['cliente:id,nombre', 'moneda:id,codigo,simbolo'])
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }
    /**
     * Obtener métricas principales
     */
      /**
     * Métricas principales del dashboard.
     *
     * Optimizaciones aplicadas:
     *  - whereMonth/whereYear → rangos indexados (usa índice de fecha_emision).
     *  - Se unifican queries del mismo tipo con selectRaw + sum(case when ...).
     *  - Se usan constantes Factura::ESTADO_* en vez de strings mágicos.
     */
    private function getMetricasPrincipales(): array
    {
        $hoy           = now()->startOfDay();
        $finHoy        = now()->endOfDay();
        $inicioMes     = now()->startOfMonth();
        $inicioMesAnt  = now()->subMonthNoOverflow()->startOfMonth();
        $finMesAnt     = now()->subMonthNoOverflow()->endOfMonth();

        // ============================================================
        // FACTURAS: ventas hoy, ventas mes, ventas mes anterior,
        // facturas hoy y pendientes. Todo en 1 query con CASE WHEN.
        // ============================================================
        $metricasFacturas = Factura::query()
            ->selectRaw('
                COALESCE(SUM(CASE WHEN estado = ? AND fecha_emision BETWEEN ? AND ? THEN total ELSE 0 END), 0) as ventas_hoy,
                COALESCE(SUM(CASE WHEN estado = ? AND fecha_emision BETWEEN ? AND ? THEN total ELSE 0 END), 0) as ventas_mes,
                COALESCE(SUM(CASE WHEN estado = ? AND fecha_emision BETWEEN ? AND ? THEN total ELSE 0 END), 0) as ventas_mes_anterior,
                COUNT(CASE WHEN fecha_emision BETWEEN ? AND ? THEN 1 END) as facturas_hoy,
                COUNT(CASE WHEN estado = ? THEN 1 END) as facturas_pendientes
            ', [
                Factura::ESTADO_PAGADA, $hoy, $finHoy,
                Factura::ESTADO_PAGADA, $inicioMes, $finHoy,
                Factura::ESTADO_PAGADA, $inicioMesAnt, $finMesAnt,
                $hoy, $finHoy,
                Factura::ESTADO_PENDIENTE,
            ])
            ->first();

        $ventasHoy          = (float) $metricasFacturas->ventas_hoy;
        $ventasMes          = (float) $metricasFacturas->ventas_mes;
        $ventasMesAnterior  = (float) $metricasFacturas->ventas_mes_anterior;

        // ============================================================
        // PRODUCTOS: sin stock, stock bajo, total activos. 1 query.
        // ============================================================
        $metricasProductos = Producto::query()
            ->selectRaw('
                COUNT(CASE WHEN stock_kg <= 0 THEN 1 END) as sin_stock,
                COUNT(CASE WHEN stock_kg > 0 AND stock_kg <= 5 THEN 1 END) as stock_bajo,
                COUNT(CASE WHEN activo = 1 THEN 1 END) as total_activos
            ')
            ->first();

        // ============================================================
        // CLIENTES: nuevos hoy y total activos. 1 query.
        // ============================================================
        $metricasClientes = Cliente::query()
            ->selectRaw('
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as nuevos_hoy,
                COUNT(CASE WHEN activo = 1 THEN 1 END) as total_activos
            ', [$hoy, $finHoy])
            ->first();

        // ============================================================
        // Crecimiento vs mes anterior
        // ============================================================
        $crecimiento = $ventasMesAnterior > 0
            ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100
            : 0;

        return [
            'ventas_hoy'           => $ventasHoy,
            'ventas_mes'           => $ventasMes,
            'facturas_hoy'         => (int) $metricasFacturas->facturas_hoy,
            'clientes_nuevos'      => (int) $metricasClientes->nuevos_hoy,
            'productos_sin_stock'  => (int) $metricasProductos->sin_stock,
            'productos_stock_bajo' => (int) $metricasProductos->stock_bajo,
            'total_productos'      => (int) $metricasProductos->total_activos,
            'total_clientes'       => (int) $metricasClientes->total_activos,
            'facturas_pendientes'  => (int) $metricasFacturas->facturas_pendientes,
            'crecimiento'          => round($crecimiento, 1),
        ];
    }

        /**
     * Desglose de facturación del mes anterior por estado.
     *
     * Devuelve un array con:
     *   - pagadas:    ['cantidad' => N, 'total' => X]
     *   - pendientes: ['cantidad' => N, 'total' => X]
     *   - anuladas:   ['cantidad' => N, 'total' => X]
     *
     * Esto permite mostrar al usuario un panorama completo del mes
     * cerrado, no solo las ventas efectivas.
     */
    private function getDesgloseMesAnterior(): array
    {
        $inicioMesAnt    = now()->subMonthNoOverflow()->startOfMonth();
        $inicioMesActual = now()->startOfMonth();

        // 1 query con CASE WHEN por estado → más eficiente que 3 queries
        $r = Factura::query()
            ->selectRaw('
                COUNT(CASE WHEN estado = ? THEN 1 END) as pag_cant,
                COALESCE(SUM(CASE WHEN estado = ? THEN total ELSE 0 END), 0) as pag_total,
                COUNT(CASE WHEN estado = ? THEN 1 END) as pen_cant,
                COALESCE(SUM(CASE WHEN estado = ? THEN total ELSE 0 END), 0) as pen_total,
                COUNT(CASE WHEN estado = ? THEN 1 END) as anu_cant,
                COALESCE(SUM(CASE WHEN estado = ? THEN total ELSE 0 END), 0) as anu_total
            ', [
                Factura::ESTADO_PAGADA,    Factura::ESTADO_PAGADA,
                Factura::ESTADO_PENDIENTE, Factura::ESTADO_PENDIENTE,
                Factura::ESTADO_ANULADA,   Factura::ESTADO_ANULADA,
            ])
            ->where('fecha_emision', '>=', $inicioMesAnt)
            ->where('fecha_emision', '<',  $inicioMesActual)
            ->first();

        return [
            'pagadas' => [
                'cantidad' => (int)   $r->pag_cant,
                'total'    => (float) $r->pag_total,
            ],
            'pendientes' => [
                'cantidad' => (int)   $r->pen_cant,
                'total'    => (float) $r->pen_total,
            ],
            'anuladas' => [
                'cantidad' => (int)   $r->anu_cant,
                'total'    => (float) $r->anu_total,
            ],
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
        /**
     * Ventas de los últimos 7 días para el gráfico.
     *
     * ANTES: 7 queries en loop (una por día).
     * AHORA: 1 query con GROUP BY, luego se rellena en PHP.
     *
     * Esto reduce 7 round-trips a MySQL a 1, y además permite que el
     * optimizador use el índice compuesto (estado, fecha_emision).
     */
    private function getVentasSemana(): array
    {
        $desde = now()->subDays(6)->startOfDay();
        $hasta = now()->endOfDay();

        // 1 sola query agrupada por día
        $ventasPorDia = Factura::query()
            ->selectRaw('DATE(fecha_emision) as dia, SUM(total) as total')
            ->where('estado', Factura::ESTADO_PAGADA)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->groupBy('dia')
            ->pluck('total', 'dia'); // colección: ['2026-09-15' => 123.45, ...]

        // Rellenar días sin ventas con 0 (para que el gráfico no tenga huecos)
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $fecha    = now()->subDays($i);
            $clave    = $fecha->toDateString(); // 'Y-m-d'
            $labels[] = $fecha->format('d/m');
            $data[]   = round((float) ($ventasPorDia[$clave] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'data'   => $data,
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