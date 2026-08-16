<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Categoria::query();

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Ordenar por nombre por defecto
        $query->orderBy('nombre');

        $categorias = $query->paginate(10)->withQueryString();

        return view('categorias.index', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categorias.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('categorias.create')
                ->withErrors($validator)
                ->withInput();
        }

        $categoria = Categoria::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->has('activo') ? true : false,
        ]);

        return redirect()->route('categorias.index')
            ->with('success', "Categoría '{$categoria->nombre}' creada exitosamente.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Categoria $categoria)
    {
        // Cargar los productos relacionados con paginación
        $productos = $categoria->productos()->paginate(10);
        
        return view('categorias.show', compact('categoria', 'productos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:categorias,nombre,' . $categoria->id,
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('categorias.edit', $categoria)
                ->withErrors($validator)
                ->withInput();
        }

        $categoria->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->has('activo') ? true : false,
        ]);

        return redirect()->route('categorias.index')
            ->with('success', "Categoría '{$categoria->nombre}' actualizada exitosamente.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        // Verificar si la categoría tiene productos asociados
        if ($categoria->productos()->count() > 0) {
            return redirect()->route('categorias.index')
                ->with('error', "No se puede eliminar la categoría '{$categoria->nombre}' porque tiene productos asociados.");
        }

        $nombre = $categoria->nombre;
        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', "Categoría '{$nombre}' eliminada exitosamente.");
    }
}