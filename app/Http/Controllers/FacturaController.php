<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Moneda;
use App\Models\TasaCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $clientes = Cliente::orderBy('nombre')->get();
            $monedas = Moneda::where('activo', true)->get();

            $query = Factura::with(['cliente', 'moneda', 'user'])
                ->orderBy('created_at', 'desc');

            if ($request->filled('numero')) {
                $query->where('numero', 'like', "%{$request->numero}%");
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('moneda_id')) {
                $query->where('moneda_id', $request->moneda_id);
            }

            $facturas = $query->paginate(15);
            $facturas->appends($request->all());

            return view('facturas.index', compact('facturas', 'clientes', 'monedas'));

        } catch (Exception $e) {
            Log::error('Error en index: ' . $e->getMessage());
            
            $clientes = Cliente::all();
            $monedas = Moneda::where('activo', true)->get();
            $facturas = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
            
            return view('facturas.index', compact('facturas', 'clientes', 'monedas'))
                ->with('error', 'Error al cargar las facturas: ' . $e->getMessage());
        }
    }

    public function create()
{
    try {
        $clientes = Cliente::orderBy('nombre')->get();
        $monedas = Moneda::where('activo', true)->get();
        
        // OBTENER TASAS DE CAMBIO DE LA BASE DE DATOS
        $tasaCOP = TasaCambio::whereHas('moneda', function($q) {
            $q->where('codigo', 'COP');
        })->latest('fecha')->first();
        
        $tasaVES = TasaCambio::whereHas('moneda', function($q) {
            $q->where('codigo', 'BS');
        })->latest('fecha')->first();
        
        // Tasa USD (si existe, sino 1)
        $tasaUSD = TasaCambio::whereHas('moneda', function($q) {
            $q->where('codigo', 'USD');
        })->latest('fecha')->first();
        
        // Obtener productos con sus precios en USD
        $productos = Producto::where('activo', true)
            ->where('stock_kg', '>', 0)
            ->get()
            ->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'sku' => $producto->sku ?? 'N/A',
                    'stock_kg' => $producto->stock_kg,
                    'precio_kg_usd' => $producto->precio_kg_usd ?? 0,
                    'iva_porcentaje' => $producto->iva_porcentaje ?? 0,
                ];
            });

        $ultimaFactura = Factura::orderBy('id', 'desc')->first();
        $siguienteNumero = 'FACT-00000001';
        
        if ($ultimaFactura && $ultimaFactura->numero) {
            $numero = intval(substr($ultimaFactura->numero, -8)) + 1;
            $siguienteNumero = 'FACT-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
        }

        return view('facturas.create', compact(
            'clientes', 
            'monedas', 
            'productos',
            'siguienteNumero',
            'tasaCOP',
            'tasaVES',
            'tasaUSD'
        ));

    } catch (Exception $e) {
        Log::error('Error en create: ' . $e->getMessage());
        return redirect()->route('facturas.index')
            ->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
    }
}

    public function store(Request $request)
    {
        try {
        $validator = validator($request->all(), [
            'cliente_id' => 'required|exists:clientes,id',
            'moneda_id' => 'required|exists:monedas,id',
            'moneda_pago' => 'required|in:USD,COP,VES',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad_kg' => 'required|numeric|min:0.001',
            'productos.*.precio_kg' => 'required|numeric|min:0',
        ], [
            'moneda_pago.required' => 'Debes seleccionar la moneda de pago',
            'moneda_pago.in' => 'Moneda de pago no válida',
            'productos.required' => 'Debes agregar al menos un producto',
            'productos.min' => 'Debes agregar al menos un producto',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, corrige los errores',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($validated) {
            // Obtener tasa de cambio según moneda de pago
            $tasaCambio = $this->obtenerTasaCambio($validated['moneda_pago']);
            
            // Buscar o crear tasa de cambio en la tabla
            $tasaCambioModel = TasaCambio::whereHas('moneda', function($q) use ($validated) {
                $q->where('codigo', $validated['moneda_pago']);
            })->latest('fecha')->first();
            
            // Generar número de factura
            $numeroFactura = $this->generarNumeroFactura();

            // Crear factura
            $factura = Factura::create([
                'numero' => $numeroFactura,
                'cliente_id' => $validated['cliente_id'],
                'user_id' => Auth::id(),
                'moneda_id' => $validated['moneda_id'],
                'tasa_cambio_id' => $tasaCambioModel ? $tasaCambioModel->id : null,
                'subtotal_neto' => 0,
                'total_impuesto' => 0,
                'total' => 0,
                'estado' => 'pendiente',
                'fecha_emision' => now(),
            ]);

            $subtotal = 0;
            $totalImpuesto = 0;
            $totalGeneral = 0;

            // Procesar productos
            foreach ($validated['productos'] as $item) {
                $producto = Producto::find($item['producto_id']);
                
                if (!$producto) {
                    throw new Exception("Producto no encontrado: ID {$item['producto_id']}");
                }
                
                if ($producto->stock_kg < $item['cantidad_kg']) {
                    throw new Exception("Stock insuficiente para: {$producto->nombre}. Disponible: {$producto->stock_kg} Kg");
                }

                // Calcular valores
                $cantidad = round($item['cantidad_kg'], 3);
                $precioKg = round($item['precio_kg'], 2); // Precio en moneda de pago
                $impuestoPorcentaje = $producto->iva_porcentaje ?? 0;
                
                // Calcular neto (subtotal sin impuesto)
                $neto = round($cantidad * $precioKg, 2);
                
                // Calcular impuesto
                $impuestoMonto = round($neto * ($impuestoPorcentaje / 100), 2);
                
                // Calcular total de la línea
                $totalLinea = round($neto + $impuestoMonto, 2);

                // Crear línea de factura con los campos correctos
                FacturaLinea::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto->id,
                    'cantidad_kg' => $cantidad,
                    'precio_kg' => $precioKg,
                    'neto' => $neto,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'impuesto_monto' => $impuestoMonto,
                    'total' => $totalLinea,
                ]);

                // Actualizar stock
                $producto->decrement('stock_kg', $cantidad);

                // Acumular totales
                $subtotal += $neto;
                $totalImpuesto += $impuestoMonto;
                $totalGeneral += $totalLinea;
            }

            // Actualizar totales de la factura
            $factura->update([
                'subtotal_neto' => round($subtotal, 2),
                'total_impuesto' => round($totalImpuesto, 2),
                'total' => round($totalGeneral, 2),
            ]);

            Log::info('Factura creada exitosamente:', [
                'id' => $factura->id,
                'numero' => $factura->numero,
                'moneda_pago' => $validated['moneda_pago'],
                'total' => $factura->total,
                'tasa' => $tasaCambio
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Factura creada exitosamente!',
                'redirect' => route('facturas.show', $factura)
            ]);

        });

    } catch (Exception $e) {
        Log::error('Error en store: ' . $e->getMessage());
        Log::error('Trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    
    }
    }
    public function show(Factura $factura)
    {
        try {
            $factura->load(['cliente', 'moneda', 'user', 'lineas.producto']);
            return view('facturas.show', compact('factura'));
        } catch (Exception $e) {
            Log::error('Error en show: ' . $e->getMessage());
            return redirect()->route('facturas.index')
                ->with('error', 'Error al cargar la factura');
        }
    }

    public function edit(Factura $factura)
    {
        try {
            if ($factura->estado !== 'pendiente') {
                return redirect()->route('facturas.index')
                    ->with('error', 'Solo se pueden editar facturas pendientes');
            }

            $clientes = Cliente::orderBy('nombre')->get();
            $monedas = Moneda::where('activa', true)->get();
            $factura->load(['lineas.producto']);

            return view('facturas.edit', compact('factura', 'clientes', 'monedas'));
        } catch (Exception $e) {
            Log::error('Error en edit: ' . $e->getMessage());
            return redirect()->route('facturas.index')
                ->with('error', 'Error al cargar el formulario de edición');
        }
    }

    public function update(Request $request, Factura $factura)
    {
        try {
            if ($factura->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar facturas pendientes'
                ], 422);
            }

            $validator = validator($request->all(), [
                'cliente_id' => 'required|exists:clientes,id',
                'moneda_id' => 'required|exists:monedas,id',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad_kg' => 'required|numeric|min:0.001',
                'productos.*.precio_kg' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Factura actualizada exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error('Error en update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la factura'
            ], 500);
        }
    }

    public function destroy(Factura $factura)
    {
        return $this->anular($factura);
    }

    public function buscarProductos(Request $request)
{
    try {
        $termino = $request->get('q', '');
        
        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        // Obtener tasas de cambio de la base de datos
        $tasaCOP = TasaCambio::whereHas('moneda', function($q) {
            $q->where('codigo', 'COP');
        })->latest('fecha')->first();
        
        $tasaVES = TasaCambio::whereHas('moneda', function($q) {
            $q->where('codigo', 'VES');
        })->latest('fecha')->first();

        $productos = Producto::where('activo', true)
            ->where('stock_kg', '>', 0)
            ->where(function($query) use ($termino) {
                $query->where('nombre', 'LIKE', "%{$termino}%")
                      ->orWhere('sku', 'LIKE', "%{$termino}%")
                      ->orWhere('descripcion', 'LIKE', "%{$termino}%");
            })
            ->limit(10)
            ->get()
            ->map(function($producto) use ($tasaCOP, $tasaVES) {
                $precioUsd = $producto->precio_kg_usd ?? 0;
                
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'sku' => $producto->sku ?? 'N/A',
                    'stock_kg' => $producto->stock_kg,
                    'precio_kg_usd' => $precioUsd,
                    'precio_kg_cop' => $precioUsd * ($tasaCOP->tasa ?? 3800),
                    'precio_kg_ves' => $precioUsd * ($tasaVES->tasa ?? 36),
                    'iva_porcentaje' => $producto->iva_porcentaje ?? 0,
                ];
            });

        return response()->json($productos);

    } catch (Exception $e) {
        Log::error('Error en buscarProductos: ' . $e->getMessage());
        return response()->json([]);
    }
}

    public function anular(Factura $factura)
    {
        try {
            if ($factura->estado === 'anulada') {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura ya está anulada'
                ]);
            }

            DB::transaction(function () use ($factura) {
                foreach ($factura->lineas as $linea) {
                    $producto = Producto::find($linea->producto_id);
                    if ($producto) {
                        $producto->increment('stock_kg', $linea->cantidad_kg);
                    }
                }

                $factura->update(['estado' => 'anulada']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Factura anulada correctamente'
            ]);

        } catch (Exception $e) {
            Log::error('Error en anular: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al anular la factura: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pagar(Factura $factura)
    {
        try {
            if ($factura->estado === 'pagada') {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura ya está pagada'
                ]);
            }

            if ($factura->estado === 'anulada') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede pagar una factura anulada'
                ]);
            }

            $factura->update([
                'estado' => 'pagada',
                'fecha_pago' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Factura marcada como pagada'
            ]);

        } catch (Exception $e) {
            Log::error('Error en pagar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pdf(Factura $factura)
    {
        try {
            $factura->load(['cliente', 'moneda', 'lineas.producto']);
            return redirect()->back()->with('info', 'Función de PDF en desarrollo');
        } catch (Exception $e) {
            Log::error('Error en pdf: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar PDF');
        }
    }

    public function imprimir(Factura $factura)
    {
        try {
            $factura->load(['cliente', 'moneda', 'lineas.producto']);
            return view('facturas.print', compact('factura'));
        } catch (Exception $e) {
            Log::error('Error en imprimir: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al imprimir');
        }
    }

    private function generarNumeroFactura()
    {
        $ultima = Factura::orderBy('id', 'desc')->first();
        if ($ultima && $ultima->numero) {
            $numero = intval(substr($ultima->numero, -8)) + 1;
            return 'FACT-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
        }
        return 'FACT-00000001';
    }
    // Método auxiliar para obtener tasa de cambio
private function obtenerTasaCambio($monedaPago)
{
    // Si es USD, tasa = 1
    if ($monedaPago === 'USD') {
        return 1;
    }
    
    // Buscar tasa de cambio para la moneda
    $tasa = TasaCambio::whereHas('moneda', function($q) use ($monedaPago) {
        $q->where('codigo', $monedaPago);
    })->latest('fecha')->first();
    
    return $tasa ? $tasa->tasa : 1;
}
}