<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientes = Cliente::query()
            ->when($request->search, function ($query, $search) {
                return $query->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('documento', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('tipo_documento', 'LIKE', "%{$search}%");
            })
            ->when($request->tipo_cliente, function ($query, $tipo) {
                return $query->where('tipo_documento', $tipo);
            })
            ->when($request->activo !== null, function ($query) use ($request) {
                return $query->where('activo', $request->activo);
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        // $tiposCliente = ['RIF', 'CI', 'EXTRANJERO'];
        $estados = ['activo' => 'Activos', 'inactivo' => 'Inactivos'];

        return view('clientes.index', compact('clientes', 'estados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cliente = new Cliente();
        $tiposCliente = ['minorista', 'mayorista', 'corporativo', 'otro'];
        $tiposDocumento = ['J','V','G','E','OTRO'];
        
        return view('clientes.create', compact('cliente', 'tiposCliente', 'tiposDocumento'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'documento' => 'required|string|max:50|unique:clientes,documento',
            'tipo_documento' => 'required|string|in:J,V,G,E,OTRO',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:clientes,email',
            'contacto' => 'nullable|string|max:255',
            'tipo_cliente' => 'required|string|in:minorista,mayorista,corporativo,otro',
            'limite_credito' => 'nullable|numeric|min:0|max:99999999.99',
            'dias_credito' => 'nullable|integer|min:0|max:365',
            'activo' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['activo'] = $request->has('activo');
        
        Cliente::create($validated);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        // Cargar facturas recientes
        $facturasRecientes = $cliente->facturas()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'total_facturas' => $cliente->facturas()->count(),
            'total_facturado' => $cliente->facturas()->sum('total'),
            'total_pagado' => $cliente->facturas()->where('estado', 'pagada')->sum('total'),
            'saldo_pendiente' => $cliente->facturas()->where('estado', '!=', 'pagada')->sum('total'),
        ];

        return view('clientes.show', compact('cliente', 'facturasRecientes', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente)
    {
        $tiposCliente = ['minorista', 'mayorista', 'corporativo', 'otro'];
        $tiposDocumento = ['J','V','G','E','OTRO'];
        
        return view('clientes.edit', compact('cliente', 'tiposCliente', 'tiposDocumento'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'documento' => 'required|string|max:50|unique:clientes,documento,' . $cliente->id,
            'tipo_documento' => 'required|string|in:J,V,G,E,OTRO',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:clientes,email,' . $cliente->id,
            'contacto' => 'nullable|string|max:255',
            'tipo_cliente' => 'required|string|in:minorista,mayorista,corporativo,otro',
            'limite_credito' => 'nullable|numeric|min:0|max:99999999.99',
            'dias_credito' => 'nullable|integer|min:0|max:365',
            'activo' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);

        $validated['activo'] = $request->has('activo');
        
        $cliente->update($validated);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        // Verificar si tiene facturas asociadas
        if ($cliente->facturas()->exists()) {
            return redirect()
                ->route('clientes.index')
                ->with('error', 'No se puede eliminar el cliente porque tiene facturas asociadas.');
        }

        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }

    /**
     * Display client's invoices.
     */
    public function facturas(Cliente $cliente)
    {
        $facturas = $cliente->facturas()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('clientes.facturas', compact('cliente', 'facturas'));
    }
}