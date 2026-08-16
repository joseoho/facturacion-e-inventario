<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * Reporte de inventario
     */
    public function inventario(Request $request)
    {
        $query = Producto::where('activo', true)->with('categoria');

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por stock
        if ($request->filled('stock')) {
            if ($request->stock === 'bajo') {
                $query->where('stock_kg', '<=', 5)->where('stock_kg', '>', 0);
            } elseif ($request->stock === 'sin') {
                $query->where('stock_kg', '<=', 0);
            }
        }

        $productos = $query->orderBy('nombre')->paginate(20);

        $totales = [
            'total_productos' => Producto::where('activo', true)->count(),
            'cantidad_total' => Producto::where('activo', true)->sum('stock_kg'),
            'valor_total' => Producto::where('activo', true)->sum(DB::raw('precio_kg_usd * stock_kg')),
            'total_categorias' => Categoria::where('activo', true)->count(),
        ];

        $categorias = Categoria::where('activo', true)->get();

        return view('reportes.inventario', compact('productos', 'totales', 'categorias'));
    }

    /**
     * Reporte de productos con stock bajo
     */
    public function stockBajo(Request $request)
    {
        $productos = Producto::where('activo', true)
            ->where('stock_kg', '<=', 5)
            ->where('stock_kg', '>', 0)
            ->orderBy('stock_kg', 'asc')
            ->with('categoria')
            ->paginate(20);

        $estadisticas = [
            'total_productos_bajo' => Producto::where('stock_kg', '<=', 5)->where('stock_kg', '>', 0)->count(),
            'total_sin_stock' => Producto::where('stock_kg', '<=', 0)->count(),
            'total_productos' => Producto::where('activo', true)->count(),
            'categorias_afectadas' => Producto::where('stock_kg', '<=', 5)
                ->where('stock_kg', '>', 0)
                ->distinct('categoria_id')
                ->count()
        ];

        return view('reportes.stock-bajo', compact('productos', 'estadisticas'));
    }

    /**
     * Reporte de ventas diarias
     */
    public function ventasDiarias(Request $request)
    {
        $fecha = $request->get('fecha') 
            ? \Carbon\Carbon::parse($request->fecha) 
            : today();
        
        $ventas = Factura::whereDate('fecha_emision', $fecha)
            ->where('estado', 'pagada')
            ->with(['cliente', 'moneda'])
            ->get();

        $totales = [
            'total_ventas' => $ventas->sum('total'),
            'total_facturas' => $ventas->count(),
            'promedio' => $ventas->avg('total') ?? 0,
            'por_moneda' => $ventas->groupBy('moneda_id')->map(function($group) {
                return [
                    'moneda' => $group->first()->moneda->codigo,
                    'total' => $group->sum('total'),
                    'cantidad' => $group->count()
                ];
            })->values()->toArray()
        ];

        return view('reportes.ventas-diarias', compact('ventas', 'totales', 'fecha'));
    }

    /**
     * Exportar inventario a PDF (usando window.print en lugar de DomPDF)
     */
    public function inventarioPDF()
    {
        // Redirigir a la vista de inventario con parámetro para imprimir
        return redirect()->route('reportes.inventario', ['imprimir' => 'true']);
    }
}