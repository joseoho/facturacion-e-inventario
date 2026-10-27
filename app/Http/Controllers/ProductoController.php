<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Moneda;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\TasaCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Producto::class);
        $query = Producto::with('categoria');

        // Búsqueda por nombre, sku o descripción
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por estado
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Filtro por stock bajo
        if ($request->filled('stock_bajo')) {
            $query->whereColumn('stock_kg', '<', 'stock_minimo');
        }

        $productos = $query->orderBy('nombre')->paginate(15)->withQueryString();
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Producto::class);
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();
        $monedas = Moneda::where('activo', true)->get();

        return view('productos.create', compact('categorias', 'monedas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Producto::class);
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:productos,sku',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'precio_kg_usd' => 'required|numeric|min:0|max:99999999999.9999',
            'stock_kg' => 'required|numeric|min:0|max:999999999999.999',
            'categoria_id' => 'required|exists:categorias,id',
            'iva_porcentaje' => 'required|numeric|min:0|max:100',
            'stock_minimo' => 'nullable|numeric|min:0|max:999999999999.999',
            'activo' => 'boolean',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'moneda_id' => 'nullable|exists:monedas,id',
            'precio_kg_local' => 'nullable|numeric|min:0|max:99999999999.9999',
            'tasa_cambio_id' => [
                'nullable',
                Rule::exists('tasas_cambio', 'id')->where(
                    fn ($query) => $query->where('moneda_id', $request->input('moneda_id'))
                ),
            ],
        ]);

        // Manejar la imagen
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $nombreImagen = Str::slug($validated['nombre']).'_'.time().'.'.$imagen->getClientOriginalExtension();
            // Guardar en storage/app/public/productos
            $path = $imagen->storeAs('productos', $nombreImagen, 'public');
            $validated['imagen'] = $path; // Esto guarda "productos/nombre_imagen.jpg"
        }

        $validated['activo'] = $request->has('activo');
        $validated['stock_minimo'] = $validated['stock_minimo'] ?? 0;

        $producto = Producto::create($validated);

        // Crear precio en moneda local si se envió la tripleta completa.
        // Si falta la tasa, no inventamos un id (antes se usaba `?? 1`,
        // que podía apuntar a una tasa de otra moneda).
        if (
            $request->filled('moneda_id')
            && $request->filled('precio_kg_local')
            && $request->filled('tasa_cambio_id')
        ) {
            PrecioProducto::create([
                'producto_id' => $producto->id,
                'moneda_id' => $request->integer('moneda_id'),
                'tasa_cambio_id' => $request->integer('tasa_cambio_id'),
                'precio_kg' => $request->input('precio_kg_local'),
            ]);
        }

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto)
    {
        $this->authorize('view', $producto);
        $producto->load(['categoria', 'preciosProductos.moneda', 'preciosProductos.tasaCambio']);

        return view('productos.show', compact('producto'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        $this->authorize('update', $producto);
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();
        $monedas = Moneda::where('activo', true)->get();
        $producto->load('preciosProductos');

        return view('productos.edit', compact('producto', 'categorias', 'monedas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        $this->authorize('update', $producto);
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:productos,sku,'.$producto->id,
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'precio_kg_usd' => 'required|numeric|min:0|max:99999999999.9999',
            'stock_kg' => 'required|numeric|min:0|max:999999999999.999',
            'categoria_id' => 'required|exists:categorias,id',
            'iva_porcentaje' => 'required|numeric|min:0|max:100',
            'stock_minimo' => 'nullable|numeric|min:0|max:999999999999.999',
            'activo' => 'boolean',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'moneda_id' => 'nullable|exists:monedas,id',
            'precio_kg_local' => 'nullable|numeric|min:0|max:99999999999.9999',
            'tasa_cambio_id' => [
                'nullable',
                Rule::exists('tasas_cambio', 'id')->where(
                    fn ($query) => $query->where('moneda_id', $request->input('moneda_id'))
                ),
            ],
        ]);

        // Manejar la imagen
        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $imagen = $request->file('imagen');
            $nombreImagen = Str::slug($validated['nombre']).'_'.time().'.'.$imagen->getClientOriginalExtension();
            $path = $imagen->storeAs('productos', $nombreImagen, 'public');
            $validated['imagen'] = $path;
        }

        $validated['activo'] = $request->has('activo');
        $validated['stock_minimo'] = $validated['stock_minimo'] ?? 0;

        $producto->update($validated);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        $this->authorize('delete', $producto);
        try {
            // Eliminar imagen si existe
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $producto->delete();

            return redirect()->route('productos.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('productos.index')
                ->with('error', 'No se puede eliminar el producto porque tiene registros asociados.');
        }
    }

    /**
     * Show prices for a product.
     */
    public function precios(Producto $producto)
    {
        $this->authorize('gestionarPrecios', $producto);
        $producto->load('preciosProductos.moneda', 'preciosProductos.tasaCambio');
        $monedas = Moneda::where('activo', true)->get();
        // `tasa` es decimal, no booleano: antes `where('tasa', true)`
        // traducía a `tasa = 1` y dejaba el selector casi vacío.
        $tasasCambio = TasaCambio::with('moneda')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('productos.precios', compact('producto', 'monedas', 'tasasCambio'));
    }

    /**
     * Store a new price for a product.
     */
    public function storePrecio(Request $request, Producto $producto)
    {
        $this->authorize('gestionarPrecios', $producto);
        $validated = $request->validate([
            'moneda_id' => [
                'required',
                'exists:monedas,id',
                Rule::unique('precios_productos', 'moneda_id')
                    ->where('producto_id', $producto->id),
            ],
            'tasa_cambio_id' => [
                'required',
                Rule::exists('tasas_cambio', 'id')->where(
                    fn ($query) => $query->where('moneda_id', $request->input('moneda_id'))
                ),
            ],
            'precio_kg' => 'required|numeric|min:0|max:99999999999.9999',
        ], [
            'moneda_id.unique' => 'Ya existe un precio para esta moneda en este producto.',
            'tasa_cambio_id.exists' => 'La tasa seleccionada no pertenece a la moneda.',
        ]);

        $producto->preciosProductos()->create($validated);

        return redirect()->route('productos.precios', $producto)
            ->with('success', 'Precio agregado exitosamente.');
    }

    /**
     * Remove a price from a product.
     */
    public function destroyPrecio(PrecioProducto $precio)
    {
        $this->authorize('gestionarPrecios', $precio->producto);
        $productoId = $precio->producto_id;
        $precio->delete();

        return redirect()->route('productos.precios', $productoId)
            ->with('success', 'Precio eliminado exitosamente.');
    }
}
