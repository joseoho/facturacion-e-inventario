<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Producto;
use Carbon\Carbon;
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
                ->count(),
        ];

        return view('reportes.stock-bajo', compact('productos', 'estadisticas'));
    }

    /**
     * Reporte de facturación por rango de fechas.
     */
    public function ventasDiarias(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'agrupar' => ['nullable', 'in:dia,semana,mes'],
        ]);

        $inicioInput = $validated['fecha_inicio']
            ?? $validated['fecha']
            ?? today()->toDateString();

        $finInput = $validated['fecha_fin']
            ?? $validated['fecha']
            ?? $inicioInput;

        $inicio = Carbon::parse($inicioInput)->startOfDay();
        $fin = Carbon::parse($finInput)->endOfDay();
        $clienteId = isset($validated['cliente_id']) ? (int) $validated['cliente_id'] : null;
        $agrupar = $validated['agrupar'] ?? 'dia';

        if ($inicio->gt($fin)) {
            return back()
                ->withErrors(['fecha_fin' => 'La fecha final no puede ser anterior a la inicial.'])
                ->withInput();
        }

        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre']);
        $clienteSeleccionado = $clienteId
            ? $clientes->firstWhere('id', $clienteId)
            : null;

        $ventas = Factura::query()
            ->whereDate('fecha_emision', '>=', $inicio->toDateString())
            ->whereDate('fecha_emision', '<=', $fin->toDateString())
            ->where('estado', Factura::ESTADO_PAGADA)
            ->when($clienteId, fn ($query) => $query->where('cliente_id', $clienteId))
            ->with(['cliente', 'moneda', 'tasaCambio'])
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $totalUsd = fn (Factura $factura) => $factura->total_usd_calculado;
        $codigoMoneda = fn (Factura $factura) => $factura->moneda_pago
            ?: ($factura->moneda?->codigo ?? 'N/A');

        $porMoneda = $ventas
            ->groupBy($codigoMoneda)
            ->map(function ($grupo, $codigo) use ($totalUsd) {
                return [
                    'moneda' => $codigo,
                    'cantidad' => $grupo->count(),
                    'total' => (float) $grupo->sum(fn (Factura $factura) => (float) $factura->total),
                    'total_usd' => (float) $grupo->sum($totalUsd),
                ];
            })
            ->sortByDesc('total_usd')
            ->values();

        $codigos = $porMoneda->pluck('moneda')->all();

        $porPeriodo = $ventas
            ->groupBy(function (Factura $factura) use ($agrupar) {
                $fecha = $factura->fecha_emision;

                return match ($agrupar) {
                    'semana' => $fecha->copy()->startOfWeek()->format('Y-m-d'),
                    'mes' => $fecha->format('Y-m'),
                    default => $fecha->format('Y-m-d'),
                };
            })
            ->map(function ($grupo, $clave) use ($agrupar, $codigos, $totalUsd, $codigoMoneda) {
                $primera = $grupo->first()->fecha_emision->copy();

                if ($agrupar === 'semana') {
                    $inicioPeriodo = $primera->startOfWeek();
                    $finPeriodo = $inicioPeriodo->copy()->endOfWeek();
                    $etiqueta = 'Semana del '.$inicioPeriodo->format('d/m')
                        .' al '.$finPeriodo->format('d/m/Y');
                } elseif ($agrupar === 'mes') {
                    $inicioPeriodo = $primera->startOfMonth();
                    $finPeriodo = $inicioPeriodo->copy()->endOfMonth();
                    $meses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ];
                    $etiqueta = $meses[(int) $inicioPeriodo->format('n')].' '.$inicioPeriodo->format('Y');
                } else {
                    $inicioPeriodo = $primera->startOfDay();
                    $finPeriodo = $primera->copy()->endOfDay();
                    $etiqueta = $inicioPeriodo->format('d/m/Y');
                }

                $fila = [
                    'clave' => $clave,
                    'etiqueta' => $etiqueta,
                    'inicio' => $inicioPeriodo,
                    'fin' => $finPeriodo,
                    'cantidad' => $grupo->count(),
                    'total_usd' => (float) $grupo->sum($totalUsd),
                    'monedas' => [],
                ];

                foreach ($codigos as $codigo) {
                    $fila['monedas'][$codigo] = (float) $grupo
                        ->filter(fn (Factura $factura) => $codigoMoneda($factura) === $codigo)
                        ->sum(fn (Factura $factura) => (float) $factura->total);
                }

                return $fila;
            })
            ->sortKeys()
            ->values();

        $porCliente = $ventas
            ->groupBy('cliente_id')
            ->map(function ($grupo) use ($codigos, $totalUsd, $codigoMoneda) {
                $fila = [
                    'cliente' => $grupo->first()->cliente?->nombre ?? 'Sin cliente',
                    'cantidad' => $grupo->count(),
                    'total_usd' => (float) $grupo->sum($totalUsd),
                    'monedas' => [],
                ];

                foreach ($codigos as $codigo) {
                    $fila['monedas'][$codigo] = (float) $grupo
                        ->filter(fn (Factura $factura) => $codigoMoneda($factura) === $codigo)
                        ->sum(fn (Factura $factura) => (float) $factura->total);
                }

                return $fila;
            })
            ->sortByDesc('total_usd')
            ->values();

        $totalUsdGeneral = (float) $ventas->sum($totalUsd);
        $cantidad = $ventas->count();

        $totales = [
            'total_usd' => $totalUsdGeneral,
            'total_facturas' => $cantidad,
            'promedio_usd' => $cantidad > 0 ? $totalUsdGeneral / $cantidad : 0,
            'por_moneda' => $porMoneda->toArray(),
            'monedas_count' => $porMoneda->count(),
            'clientes_count' => $ventas->pluck('cliente_id')->filter()->unique()->count(),
            'agrupar' => $agrupar,
        ];

        return view('reportes.ventas-diarias', compact(
            'ventas',
            'totales',
            'inicio',
            'fin',
            'porPeriodo',
            'porCliente',
            'clientes',
            'clienteSeleccionado',
            'clienteId',
            'agrupar'
        ));
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
