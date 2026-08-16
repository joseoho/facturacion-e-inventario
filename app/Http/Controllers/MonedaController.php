<?php

namespace App\Http\Controllers;

use App\Models\Moneda;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonedaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Moneda::query();

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('simbolo', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->filled('activa')) {
            $query->where('activa', $request->activa);
        }

        // Ordenar por ID descendente
        $monedas = $query->orderBy('id', 'desc')->paginate(10);

        return view('monedas.index', compact('monedas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('monedas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:3', 'unique:monedas,codigo'],
            'nombre' => ['required', 'string', 'max:100'],
            'simbolo' => ['required', 'string', 'max:10'],
            'es_base' => ['boolean'],
            'activa' => ['boolean'],
        ]);

        // Si se marca como base, desactivar otras bases
        if ($request->boolean('es_base')) {
            Moneda::where('es_base', true)->update(['es_base' => false]);
        }

        $moneda = Moneda::create([
            'codigo' => strtoupper($validated['codigo']),
            'nombre' => $validated['nombre'],
            'simbolo' => $validated['simbolo'],
            'es_base' => $request->boolean('es_base'),
            'activa' => $request->boolean('activa', true),
        ]);

        return redirect()
            ->route('monedas.index')
            ->with('success', "Moneda {$moneda->nombre} creada exitosamente.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Moneda $moneda)
    {
        // Cargar relaciones para mostrar información adicional
        $moneda->load(['tasasCambio' => function($query) {
            $query->latest('fecha')->limit(5);
        }]);

        $totalFacturas = $moneda->facturas()->count();
        $totalProductos = $moneda->preciosProductos()->count();

        return view('monedas.show', compact('moneda', 'totalFacturas', 'totalProductos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Moneda $moneda)
    {
        return view('monedas.edit', compact('moneda'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Moneda $moneda)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:3', Rule::unique('monedas')->ignore($moneda->id)],
            'nombre' => ['required', 'string', 'max:100'],
            'simbolo' => ['required', 'string', 'max:10'],
            'es_base' => ['boolean'],
            'activa' => ['boolean'],
        ]);

        // Validar que no se desactive la moneda base si es la única
        if ($moneda->es_base && !$request->boolean('es_base')) {
            $otherBaseExists = Moneda::where('es_base', true)
                ->where('id', '!=', $moneda->id)
                ->exists();

            if (!$otherBaseExists) {
                return back()
                    ->withErrors(['es_base' => 'Debe haber al menos una moneda base activa.'])
                    ->withInput();
            }
        }

        // Si se marca como base, desactivar otras bases
        if ($request->boolean('es_base')) {
            Moneda::where('es_base', true)
                ->where('id', '!=', $moneda->id)
                ->update(['es_base' => false]);
        }

        $moneda->update([
            'codigo' => strtoupper($validated['codigo']),
            'nombre' => $validated['nombre'],
            'simbolo' => $validated['simbolo'],
            'es_base' => $request->boolean('es_base'),
            'activa' => $request->boolean('activa'),
        ]);

        return redirect()
            ->route('monedas.index')
            ->with('success', "Moneda {$moneda->nombre} actualizada exitosamente.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Moneda $moneda)
    {
        // Verificar si la moneda tiene relaciones
        if ($moneda->facturas()->exists()) {
            return back()->with('error', 'No se puede eliminar la moneda porque tiene facturas asociadas.');
        }

        if ($moneda->preciosProductos()->exists()) {
            return back()->with('error', 'No se puede eliminar la moneda porque tiene precios de productos asociados.');
        }

        if ($moneda->es_base) {
            return back()->with('error', 'No se puede eliminar la moneda base del sistema.');
        }

        $nombre = $moneda->nombre;
        $moneda->delete();

        return redirect()
            ->route('monedas.index')
            ->with('success', "Moneda {$nombre} eliminada exitosamente.");
    }
}