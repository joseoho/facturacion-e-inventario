<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEgresoRequest;
use App\Models\CategoriaEgreso;
use App\Models\Egreso;
use App\Models\Moneda;
use App\Models\Producto;
use App\Services\EgresoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class EgresoController extends Controller
{
    public function __construct(private readonly EgresoService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Egreso::class);

        $query = Egreso::query()
            ->with(['categoria:id,nombre', 'moneda:id,codigo', 'producto:id,nombre', 'user:id,name']);

        if ($request->filled('q')) {
            $termino = $request->string('q');

            $query->where(function ($sub) use ($termino) {
                $sub->where('numero', 'like', "%{$termino}%")
                    ->orWhere('concepto', 'like', "%{$termino}%")
                    ->orWhere('referencia_pago', 'like', "%{$termino}%")
                    ->orWhere('proveedor_nombre', 'like', "%{$termino}%");
            });
        }

        if ($request->filled('categoria_egreso_id')) {
            $query->where('categoria_egreso_id', $request->integer('categoria_egreso_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('moneda_id')) {
            $query->where('moneda_id', $request->integer('moneda_id'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_egreso', '>=', $request->date('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_egreso', '<=', $request->date('hasta'));
        }

        $totales = (clone $query)
            ->reorder()
            ->where('estado', '!=', Egreso::ESTADO_ANULADO)
            ->selectRaw(
                'COUNT(*) as cantidad,
                 COALESCE(SUM(CASE WHEN tasa_cambio_usada > 0 THEN monto / tasa_cambio_usada ELSE monto END), 0) as total_usd'
            )
            ->first();

        $porCategoria = (clone $query)
            ->reorder()
            ->join('categorias_egreso', 'egresos.categoria_egreso_id', '=', 'categorias_egreso.id')
            ->where('egresos.estado', '!=', Egreso::ESTADO_ANULADO)
            ->selectRaw(
                'categorias_egreso.nombre as categoria,
                 COUNT(*) as cantidad,
                 COALESCE(SUM(CASE WHEN egresos.tasa_cambio_usada > 0 THEN egresos.monto / egresos.tasa_cambio_usada ELSE egresos.monto END), 0) as total_usd'
            )
            ->groupBy('categorias_egreso.nombre')
            ->orderByDesc('total_usd')
            ->get();

        $egresos = $query
            ->orderByDesc('fecha_egreso')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('egresos.index', [
            'egresos' => $egresos,
            'totales' => $totales,
            'porCategoria' => $porCategoria,
            'categorias' => CategoriaEgreso::orderBy('nombre')->get(['id', 'nombre']),
            'monedas' => Moneda::whereIn('codigo', Egreso::MONEDAS_PAGO)->orderBy('id')->get(['id', 'codigo']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Egreso::class);

        return view('egresos.create', [
            'categorias' => CategoriaEgreso::where('activo', true)->orderBy('nombre')->get(),
            'monedas' => Moneda::whereIn('codigo', Egreso::MONEDAS_PAGO)->where('activo', true)->orderBy('id')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'sku', 'stock_kg']),
            'metodosPago' => Egreso::METODOS_PAGO,
        ]);
    }

    public function store(StoreEgresoRequest $request): RedirectResponse
    {
        $this->authorize('create', Egreso::class);

        try {
            $egreso = $this->service->crear($request->validated(), $request->user());

            return redirect()
                ->route('egresos.show', $egreso)
                ->with('success', "Egreso {$egreso->numero} registrado correctamente.");
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Error al crear egreso: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->withInput()->with('error', 'No se pudo registrar el egreso.');
        }
    }

    public function show(Egreso $egreso): View
    {
        $this->authorize('view', $egreso);

        $egreso->load(['categoria', 'producto', 'moneda', 'tasaCambio', 'user']);

        return view('egresos.show', compact('egreso'));
    }

    public function pagar(Request $request, Egreso $egreso): RedirectResponse
    {
        $this->authorize('pagar', $egreso);

        $validated = $request->validate([
            'fecha_pago' => ['nullable', 'date'],
            'metodo_pago' => ['nullable', 'in:'.implode(',', array_keys(Egreso::METODOS_PAGO))],
            'referencia_pago' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->service->pagar($egreso, $validated, $request->user());

            return back()->with('success', 'Egreso marcado como pagado.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Error al pagar egreso: '.$e->getMessage());

            return back()->with('error', 'No se pudo registrar el pago.');
        }
    }

    public function anular(Request $request, Egreso $egreso): RedirectResponse
    {
        $this->authorize('anular', $egreso);

        try {
            $this->service->anular($egreso, $request->user());

            return back()->with('success', 'Egreso anulado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Error al anular egreso: '.$e->getMessage());

            return back()->with('error', 'No se pudo anular el egreso.');
        }
    }
}
