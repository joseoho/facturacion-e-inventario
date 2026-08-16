<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Moneda;
use App\Models\TasaCambio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        // Obtener todos los clientes y monedas para los filtros
        $clientes = Cliente::orderBy('nombre')->get();
        $monedas = Moneda::where('es_base', true)->get();

        $query = Factura::with(['cliente', 'moneda', 'user'])->latest('fecha_emision');

        // Filtro por número de factura
        if ($request->filled('numero')) {
            $query->byNumero($request->numero);
        }

        // Filtro por cliente
        if ($request->filled('cliente_id')) {
            $query->byCliente($request->cliente_id);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->byEstado($request->estado);
        }

        // Filtro por moneda
        if ($request->filled('moneda_id')) {
            $query->byMoneda($request->moneda_id);
        }

        // Búsqueda general (opcional)
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('numero', 'like', "%{$busqueda}%")
                  ->orWhereHas('cliente', function($c) use ($busqueda) {
                      $c->where('nombre_razon_social', 'like', "%{$busqueda}%")
                        ->orWhere('documento', 'like', "%{$busqueda}%");
                  });
            });
        }

        $facturas = $query->paginate(15);
        
        // Mantener los parámetros del filtro en la paginación
        $facturas->appends($request->all());

        return view('facturas.index', compact('facturas', 'clientes', 'monedas'));
    }

    public function create()
    {
        // $tasas = TasaCambio::with('moneda');
            
        $clientes = Cliente::orderBy('nombre')->get();
        $monedas = Moneda::where('es_base', true)->get();
        $productos = Producto::with('preciosProductos')->where('activo', true)->get();

        return view('facturas.create', compact('clientes', 'monedas', 'productos'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id'         => 'required|exists:clientes,id',
                'moneda_id'          => 'required|exists:monedas,id',
                'moneda_calculo_id'  => 'nullable|exists:monedas,id',
                'tasa_dia'           => 'nullable|numeric|min:0',
                'fecha_emision'      => 'required|date',
                'items'              => 'required|array|min:1',
                'items.*.producto_id' => 'required|exists:productos,id',
                'items.*.cantidad_kg' => 'required|numeric|min:0.001',
                'items.*.precio_kg'   => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $moneda = Moneda::findOrFail($request->moneda_id);

            /* ─── Generar número de factura ─── */
            $ultima = Factura::orderBy('id', 'desc')->first();
            $siguiente = $ultima ? ((int) str_replace('FACT-', '', $ultima->numero_factura) + 1) : 1;
            $numeroFactura = 'FACT-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);

            /* ─── Tasa de cambio de la moneda de FACTURACIÓN ─── */
            $tasaId    = null;
            $tasaValor = 1;
            if (!$moneda->es_base) {
                $tasa = TasaCambio::where('moneda_id', $moneda->id)
                    ->whereDate('fecha', '<=', $request->fecha_emision)
                    ->orderBy('fecha', 'desc')
                    ->first();

                if ($tasa) {
                    $tasaId    = $tasa->id;
                    $tasaValor = $tasa->tasa;
                }
            }

            /* ─── Crear factura ─── */
            $factura = Factura::create([
                'numero'      => $numeroFactura,
                'cliente_id'          => $request->cliente_id,
                'user_id'             => Auth::id(),
                'moneda_id'           => $request->moneda_id,
                'moneda_calculo_id'   => $request->moneda_calculo_id,
                'tasa_cambio_id'      => $tasaId,
                'tasa_cambio_valor'   => $tasaValor,
                'tasa_dia_visual'     => $request->tasa_dia,
                'subtotal_neto'       => 0,
                'total_impuesto'      => 0,
                'total'               => 0,
                'estado'              => 'pendiente',
                'fecha_emision'       => $request->fecha_emision,
                // 'fecha_vencimiento'   => Carbon::parse($request->fecha_emision)->addDays(30),
            ]);

            $subtotalNeto  = 0;
            $totalImpuesto = 0;
            $totalGeneral  = 0;

            foreach ($request->items as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);

                if (!$producto->tieneStock($item['cantidad_kg'])) {
                    throw new \Exception(
                        "Stock insuficiente para: {$producto->nombre}. " .
                        "Disponible: " . number_format($producto->stock_kg, 3) . " Kg"
                    );
                }

                // Los precios vienen en USD (moneda base), convertir a moneda de facturación si es necesario
                $precioKg = $item['precio_kg'];
                if (!$moneda->es_base && $tasaValor > 0) {
                    $precioKg = $item['precio_kg'] * $tasaValor;
                }

                $neto              = $precioKg * $item['cantidad_kg'];
                $impuestoPorcentaje = $producto->iva_porcentaje ?? 0;
                $impuestoMonto      = $neto * ($impuestoPorcentaje / 100);
                $totalLinea         = $neto + $impuestoMonto;

                FacturaLinea::create([
                    'factura_id'          => $factura->id,
                    'producto_id'         => $producto->id,
                    'cantidad_kg'         => $item['cantidad_kg'],
                    'precio_kg'           => $precioKg,
                    'precio_kg_usd'       => $item['precio_kg'], // Guardar también el precio base
                    'neto'                => $neto,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'impuesto_monto'      => $impuestoMonto,
                    'total'               => $totalLinea,
                ]);

                $producto->reducirStock($item['cantidad_kg']);

                $subtotalNeto  += $neto;
                $totalImpuesto += $impuestoMonto;
                $totalGeneral  += $totalLinea;
            }

            $factura->update([
                'subtotal_neto'  => $subtotalNeto,
                'total_impuesto' => $totalImpuesto,
                'total'          => $totalGeneral,
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Factura creada correctamente.',
                'redirect' => route('facturas.show', $factura),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear factura: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la factura: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'moneda', 'user', 'lineas.producto']);
        return view('facturas.show', compact('factura'));
    }

    public function edit(Factura $factura)
    {
        // Solo permitir editar facturas pendientes
        if ($factura->estado !== 'pendiente') {
            return redirect()->route('facturas.index')
                ->with('error', 'Solo se pueden editar facturas pendientes.');
        }

        $clientes = Cliente::orderBy('nombre')->get();
        $monedas = Moneda::where('es_base', true)->get();
        $productos = Producto::with('preciosProductos')->where('activo', true)->get();
        
        $factura->load(['lineas.producto']);

        return view('facturas.edit', compact('factura', 'clientes', 'monedas', 'productos'));
    }

    public function update(Request $request, Factura $factura)
    {
        // Validar que la factura esté pendiente
        if ($factura->estado !== 'pendiente') {
            return redirect()->route('facturas.index')
                ->with('error', 'Solo se pueden editar facturas pendientes.');
        }

        // Lógica de actualización...
        // (Similar a store pero actualizando en lugar de crear)
        
        return redirect()->route('facturas.show', $factura->id)
            ->with('success', 'Factura actualizada con éxito.');
    }

    public function destroy(Factura $factura)
    {
        if ($factura->estado === 'anulada') {
            return back()->with('error', 'La factura ya se encuentra anulada.');
        }

        DB::transaction(function () use ($factura) {
            // Reincorporar stock de los productos vendidos
            foreach ($factura->lineas as $linea) {
                Producto::where('id', $linea->producto_id)
                    ->increment('stock_kg', $linea->cantidad_kg);
            }

            $factura->update(['estado' => 'anulada']);
        });

        return redirect()->route('facturas.index')
            ->with('success', 'Factura anulada correctamente y stock devuelto al inventario.');
    }

    // Métodos adicionales para acciones AJAX
    public function anular(Request $request, Factura $factura)
    {
        try {
            if ($factura->estado === 'anulada') {
                return response()->json(['success' => false, 'message' => 'La factura ya está anulada'], 400);
            }

            if ($factura->estado === 'pagada') {
                return response()->json(['success' => false, 'message' => 'No se puede anular una factura pagada'], 400);
            }

            DB::transaction(function () use ($factura) {
                foreach ($factura->lineas as $linea) {
                    Producto::where('id', $linea->producto_id)
                        ->increment('stock_kg', $linea->cantidad_kg);
                }

                $factura->update(['estado' => 'anulada']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Factura anulada correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular la factura: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pagar(Request $request, Factura $factura)
    {
        try {
            if ($factura->estado === 'pagada') {
                return response()->json(['success' => false, 'message' => 'La factura ya está pagada'], 400);
            }

            if ($factura->estado === 'anulada') {
                return response()->json(['success' => false, 'message' => 'No se puede pagar una factura anulada'], 400);
            }

            $factura->update([
                'estado' => 'pagada',
                'fecha_pago' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Factura marcada como pagada correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pdf(Factura $factura)
    {
        // Implementar generación de PDF
        return redirect()->back()->with('info', 'Función de PDF en desarrollo');
    }

    public function imprimir(Factura $factura)
    {
        // Implementar impresión
        return redirect()->back()->with('info', 'Función de impresión en desarrollo');
    }

   public function buscarProductos(Request $request)
    {
        try {
            $termino = $request->get('q', '');
            $monedaId = $request->get('moneda_id');
            
            // Si no hay término de búsqueda, devolver array vacío
            if (strlen($termino) < 2) {
                return response()->json([]);
            }
            
            // Buscar productos activos con stock
            $productos = Producto::where('activo', true)
                ->where(function($query) use ($termino) {
                    $query->where('nombre', 'LIKE', "%{$termino}%")
                          ->orWhere('sku', 'LIKE', "%{$termino}%")
                          ->orWhere('descripcion', 'LIKE', "%{$termino}%");
                })
                ->where('stock_kg', '>', 0)
                ->limit(20)
                ->get();

            $resultados = $productos->map(function ($producto) use ($monedaId) {
                // Obtener precio en la moneda seleccionada
                $precio = null;
                if ($monedaId) {
                    $precio = $producto->precios()
                        ->where('moneda_id', $monedaId)
                        ->latest()
                        ->first();
                }
                
                // Si no hay precio en la moneda seleccionada, usar el precio en USD
                $precioKg = $precio ? $precio->precio_kg : $producto->precio_kg_usd;

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'sku' => $producto->sku,
                    'stock_kg' => $producto->stock_kg,
                    'precio_kg_usd' => $producto->precio_kg_usd,
                    'precio_kg' => $precioKg,
                    'iva_porcentaje' => $producto->iva_porcentaje,
                    'imagen' => $producto->imagen_url
                ];
            });

            return response()->json($resultados);
            
        } catch (\Exception $e) {
            Log::error('Error en buscarProductos: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }
}