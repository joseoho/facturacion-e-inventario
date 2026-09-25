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
            $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();

            // ============================================================
            // Monedas activas — colección para el <select> de la vista
            // ============================================================
            $monedas = Moneda::where('activo', true)->orderBy('id')->get();

            // ============================================================
            // Array plano para Alpine (evita problemas con @json)
            // ============================================================
            $monedasJson = $monedas->map(function ($m) {
                return [
                    'id'      => $m->id,
                    'codigo'  => $m->codigo,
                    'nombre'  => $m->nombre,
                    'es_base' => (bool) $m->es_base,
                ];
            })->values()->all();

            // ============================================================
            // Tasas de cambio vigentes (solo para referencia en la vista,
            // NO se usan para calcular precios)
            // ============================================================
            $tasaCOP = TasaCambio::whereHas('moneda', function ($q) {
                $q->porCodigo(Moneda::CODIGO_COP);
            })->latest('fecha')->first();

            $tasaVES = TasaCambio::whereHas('moneda', function ($q) {
                $q->porCodigo(Moneda::CODIGO_VES);
            })->latest('fecha')->first();

            $tasaUSD = TasaCambio::whereHas('moneda', function ($q) {
                $q->porCodigo(Moneda::CODIGO_USD);
            })->latest('fecha')->first();

            // ============================================================
            // Productos activos con stock (solo referencia base en USD)
            // ============================================================
            $productos = Producto::where('activo', true)
                ->where('stock_kg', '>', 0)
                ->select('id', 'nombre', 'sku', 'stock_kg', 'iva_porcentaje', 'precio_kg_usd')
                ->get()
                ->map(function ($producto) {
                    return [
                        'id'             => $producto->id,
                        'nombre'         => $producto->nombre,
                        'sku'            => $producto->sku ?? 'N/A',
                        'stock_kg'       => (float) $producto->stock_kg,
                        'precio_kg_usd'  => (float) ($producto->precio_kg_usd ?? 0),
                        'iva_porcentaje' => (float) ($producto->iva_porcentaje ?? 0),
                    ];
                });

            // ============================================================
            // Siguiente número de factura (solo previsualización)
            // ============================================================
            $ultimaFactura = Factura::orderBy('id', 'desc')->first();
            $siguienteNumero = 'FACT-00000001';

            if ($ultimaFactura && $ultimaFactura->numero) {
                $numero = intval(substr($ultimaFactura->numero, -8)) + 1;
                $siguienteNumero = 'FACT-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
            }

            return view('facturas.create', compact(
                'clientes',
                'monedas',
                'monedasJson',
                'productos',
                'siguienteNumero',
                'tasaCOP',
                'tasaVES',
                'tasaUSD'
            ));

        } catch (Exception $e) {
            Log::error('Error en create: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('facturas.index')
                ->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
            'cliente_id'              => 'required|exists:clientes,id',
            'moneda_id'               => 'required|exists:monedas,id',
            'moneda_pago'             => 'required|string|in:USD,COP,BS,VES,EUR',
            'productos'               => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad_kg' => 'required|numeric|min:0.001|max:9999.999',
            'productos.*.precio_kg'   => 'required|numeric|min:0',
        ], [
            'moneda_pago.required' => 'Debes seleccionar la moneda de pago',
            'moneda_pago.in'       => 'Moneda de pago no válida',
            'productos.required'   => 'Debes agregar al menos un producto',
            'productos.min'        => 'Debes agregar al menos un producto',
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

            // ============================================================
            // VALIDAR MONEDA DE PAGO
            // ============================================================
            $monedaPago = Moneda::porCodigo($validated['moneda_pago'])
                ->where('activo', true)
                ->first();

            if (!$monedaPago) {
                throw new Exception(
                    "Moneda de pago '{$validated['moneda_pago']}' no existe o está inactiva."
                );
            }

            $esBase = $monedaPago->esBase();

            // ============================================================
            // RESOLVER TASA VIGENTE (una sola vez para toda la factura)
            // ============================================================
            // IMPORTANTE: la tasa se resuelve UNA VEZ al inicio de la
            // transacción y se aplica a TODAS las líneas. Así, si el admin
            // cambia la tasa a mitad de la facturación, no afecta a esta
            // factura (que ya está en curso).
            // ============================================================
            $tasaVigente = null;

            if (!$esBase) {
                $tasaVigente = TasaCambio::where('moneda_id', $monedaPago->id)
                    ->orderByDesc('fecha')
                    ->orderByDesc('id')
                    ->first();

                if (!$tasaVigente || (float) $tasaVigente->tasa <= 0) {
                    throw new Exception(
                        "No hay tasa de cambio vigente para {$monedaPago->codigo}. " .
                        "Registra una tasa antes de facturar en esta moneda."
                    );
                }
            }

            $tasaCambioIdUsada = $esBase ? null : $tasaVigente->id;
            $tasaValorUsada    = $esBase ? null : (float) $tasaVigente->tasa;

            // ============================================================
            // ORDENAR ITEMS POR producto_id PARA EVITAR DEADLOCKS
            // ============================================================
            $items = collect($validated['productos'])
                ->sortBy('producto_id')
                ->values()
                ->all();

            // ============================================================
            // GENERAR NÚMERO DE FACTURA (atómico)
            // ============================================================
            $numeroFactura = $this->generarNumeroFactura();

            // ============================================================
            // CREAR LA FACTURA CON TODOS LOS CAMPOS
            // ============================================================
            $factura = Factura::create([
                'numero'            => $numeroFactura,
                'cliente_id'        => $validated['cliente_id'],
                'user_id'           => Auth::id(),
                'moneda_id'         => $monedaPago->id,
                'moneda_pago'       => $monedaPago->codigo,
                'tasa_cambio_id'    => $tasaCambioIdUsada,
                'tasa_cambio_usada' => $tasaValorUsada,
                'subtotal_neto'     => 0,
                'total_impuesto'    => 0,
                'total'             => 0,
                'estado'            => Factura::ESTADO_PENDIENTE,
                'fecha_emision'     => now(),
            ]);

            $subtotal      = 0;
            $totalImpuesto = 0;
            $totalGeneral  = 0;

            // ============================================================
            // PROCESAR LÍNEAS
            // ============================================================
            foreach ($items as $item) {
                $producto = Producto::where('id', $item['producto_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$producto) {
                    throw new Exception("Producto no encontrado: ID {$item['producto_id']}");
                }
                if (!$producto->activo) {
                    throw new Exception("El producto '{$producto->nombre}' está inactivo.");
                }

                $precioBase = (float) ($producto->precio_kg_usd ?? 0);
                if ($precioBase <= 0) {
                    throw new Exception(
                        "El producto '{$producto->nombre}' no tiene precio base configurado."
                    );
                }

                // ========================================================
                // CALCULAR PRECIO EN LA MONEDA DE PAGO
                // ========================================================
                if ($esBase) {
                    $precioKg = round($precioBase, 4);
                } else {
                    $precioKg = round($precioBase * $tasaValorUsada, 4);
                }

                $cantidad = round((float) $item['cantidad_kg'], 3);
                if ($cantidad <= 0) {
                    throw new Exception("Cantidad inválida para '{$producto->nombre}'.");
                }

                // ========================================================
                // VALIDACIÓN DE SANIDAD
                // ========================================================
                // El frontend puede mandar un precio distinto por
                // manipulación o desincronización. Comparamos, logueamos
                // y SIEMPRE imponemos el precio oficial calculado aquí.
                // ========================================================
                $precioEnviado = (float) ($item['precio_kg'] ?? 0);
                if (abs($precioEnviado - $precioKg) > 0.01) {
                    Log::warning('Precio enviado difiere del calculado — se impone el oficial', [
                        'factura_numero' => $numeroFactura,
                        'producto_id'    => $producto->id,
                        'producto'       => $producto->nombre,
                        'precio_enviado' => $precioEnviado,
                        'precio_oficial' => $precioKg,
                        'moneda'         => $monedaPago->codigo,
                        'tasa_usada'     => $tasaValorUsada,
                    ]);
                }

                // Validar stock (fila bloqueada por lockForUpdate)
                if ((float) $producto->stock_kg < $cantidad) {
                    throw new Exception(
                        "Stock insuficiente para: {$producto->nombre}. " .
                        "Disponible: {$producto->stock_kg} Kg, Solicitado: {$cantidad} Kg"
                    );
                }

                $impuestoPorcentaje = (float) ($producto->iva_porcentaje ?? 0);
                $neto               = round($cantidad * $precioKg, 4);
                $impuestoMonto      = round($neto * ($impuestoPorcentaje / 100), 4);
                $totalLinea         = round($neto + $impuestoMonto, 4);

                FacturaLinea::create([
                    'factura_id'          => $factura->id,
                    'producto_id'         => $producto->id,
                    'cantidad_kg'         => $cantidad,
                    'precio_kg'           => $precioKg,
                    'neto'                => $neto,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'impuesto_monto'      => $impuestoMonto,
                    'total'               => $totalLinea,
                ]);

                $producto->update([
                    'stock_kg' => $producto->stock_kg - $cantidad,
                ]);

                $subtotal      += $neto;
                $totalImpuesto += $impuestoMonto;
                $totalGeneral  += $totalLinea;
            }

            // ============================================================
            // ACTUALIZAR TOTALES
            // ============================================================
            $factura->update([
                'subtotal_neto'  => round($subtotal, 4),
                'total_impuesto' => round($totalImpuesto, 4),
                'total'          => round($totalGeneral, 4),
            ]);

            Log::info('Factura creada', [
                'id'          => $factura->id,
                'numero'      => $factura->numero,
                'moneda_pago' => $monedaPago->codigo,
                'tasa_usada'  => $tasaValorUsada,
                'total'       => $factura->total,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => '¡Factura creada exitosamente!',
                'redirect' => route('facturas.show', $factura),
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

    // public function edit(Factura $factura)
    // {
    //     try {
    //         if ($factura->estado !== 'pendiente') {
    //             return redirect()->route('facturas.index')
    //                 ->with('error', 'Solo se pueden editar facturas pendientes');
    //         }

    //         $clientes = Cliente::orderBy('nombre')->get();
    //         $monedas = Moneda::where('activa', true)->get();
    //         $factura->load(['lineas.producto']);

    //         return view('facturas.edit', compact('factura', 'clientes', 'monedas'));
    //     } catch (Exception $e) {
    //         Log::error('Error en edit: ' . $e->getMessage());
    //         return redirect()->route('facturas.index')
    //             ->with('error', 'Error al cargar el formulario de edición');
    //     }
    // }

    // public function update(Request $request, Factura $factura)
    // {
    //     try {
    //         if ($factura->estado !== 'pendiente') {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Solo se pueden editar facturas pendientes'
    //             ], 422);
    //         }

    //         $validator = validator($request->all(), [
    //             'cliente_id' => 'required|exists:clientes,id',
    //             'moneda_id' => 'required|exists:monedas,id',
    //             'productos' => 'required|array|min:1',
    //             'productos.*.producto_id' => 'required|exists:productos,id',
    //             'productos.*.cantidad_kg' => 'required|numeric|min:0.001',
    //             'productos.*.precio_kg' => 'required|numeric|min:0',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Factura actualizada exitosamente'
    //         ]);

    //     } catch (Exception $e) {
    //         Log::error('Error en update: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al actualizar la factura'
    //         ], 500);
    //     }
    // }

    public function destroy(Factura $factura)
    {
        return $this->anular($factura);
    }

//     public function buscarProductos(Request $request)
// {
//     try {
//         $termino = $request->get('q', '');
        
//         if (strlen($termino) < 2) {
//             return response()->json([]);
//         }

//         // Obtener tasas de cambio de la base de datos
//         $tasaCOP = TasaCambio::whereHas('moneda', function($q) {
//             $q->where('codigo', 'COP');
//         })->latest('fecha')->first();
        
//         $tasaVES = TasaCambio::whereHas('moneda', function($q) {
//             $q->where('codigo', 'VES');
//         })->latest('fecha')->first();

//         $productos = Producto::where('activo', true)
//             ->where('stock_kg', '>', 0)
//             ->where(function($query) use ($termino) {
//                 $query->where('nombre', 'LIKE', "%{$termino}%")
//                       ->orWhere('sku', 'LIKE', "%{$termino}%")
//                       ->orWhere('descripcion', 'LIKE', "%{$termino}%");
//             })
//             ->limit(10)
//             ->get()
//             ->map(function($producto) use ($tasaCOP, $tasaVES) {
//                 $precioUsd = $producto->precio_kg_usd ?? 0;
                
//                 return [
//                     'id' => $producto->id,
//                     'nombre' => $producto->nombre,
//                     'sku' => $producto->sku ?? 'N/A',
//                     'stock_kg' => $producto->stock_kg,
//                     'precio_kg_usd' => $precioUsd,
//                     'precio_kg_cop' => $precioUsd * ($tasaCOP->tasa ?? 3800),
//                     'precio_kg_ves' => $precioUsd * ($tasaVES->tasa ?? 36),
//                     'iva_porcentaje' => $producto->iva_porcentaje ?? 0,
//                 ];
//             });

//         return response()->json($productos);

//     } catch (Exception $e) {
//         Log::error('Error en buscarProductos: ' . $e->getMessage());
//         return response()->json([]);
//     }
// }

    /**
     * Busca productos activos con stock y devuelve sus precios OFICIALES
     * (desde `precios_productos`) para la moneda de pago solicitada.
     *
     * Query params:
     *   - q: término de búsqueda (mín. 2 caracteres)
     *   - moneda_id: id de la moneda en la que se va a facturar
     */
        /**
     * Busca productos activos con stock y calcula su precio en la moneda
     * solicitada usando la ÚLTIMA tasa vigente registrada por el admin.
     *
     * Reglas:
     *   - USD (moneda base): precio = precio_kg_usd (tasa = 1)
     *   - BS / COP:          precio = precio_kg_usd × última tasa vigente
     *
     * Si no hay tasa para la moneda (solo si el admin nunca la registró),
     * se devuelve precio_disponible = false y el frontend bloquea la venta.
     *
     * Query params:
     *   - q: término de búsqueda (mín. 2 caracteres)
     *   - moneda_id: id de la moneda en la que se va a facturar
     */
    public function buscarProductos(Request $request)
    {
        try {
            $termino = trim((string) $request->get('q', ''));
            $monedaId = $request->get('moneda_id');

            if (mb_strlen($termino) < 2) {
                return response()->json([]);
            }

            // Sin moneda no podemos calcular precios.
            if (!$monedaId) {
                return response()->json([
                    'error' => 'Debes seleccionar una moneda de pago antes de buscar productos.',
                ], 422);
            }

            $moneda = Moneda::find($monedaId);
            if (!$moneda || !$moneda->activo) {
                return response()->json([
                    'error' => 'Moneda inválida o inactiva.',
                ], 422);
            }

            // ============================================================
            // RESOLVER LA TASA VIGENTE (una sola vez por request)
            // ============================================================
            // La "tasa vigente" es la ÚLTIMA registrada para esa moneda,
            // sin importar cuán antigua sea. Si el admin olvidó actualizar
            // hoy, se sigue usando la última que registró.
            // ============================================================
            $esBase = $moneda->esBase();
            $tasaVigente = null;

            if (!$esBase) {
                $tasaVigente = TasaCambio::where('moneda_id', $moneda->id)
                    ->orderByDesc('fecha')
                    ->orderByDesc('id')
                    ->first();

                // Si no hay tasa, todos los productos irán con precio_disponible = false
            }

            // ============================================================
            // BUSCAR PRODUCTOS
            // ============================================================
            $productos = Producto::where('activo', true)
                ->where('stock_kg', '>', 0)
                ->where(function ($query) use ($termino) {
                    $query->where('nombre', 'LIKE', "%{$termino}%")
                          ->orWhere('sku', 'LIKE', "%{$termino}%")
                          ->orWhere('descripcion', 'LIKE', "%{$termino}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($producto) use ($moneda, $esBase, $tasaVigente) {
                    $precioBase = (float) ($producto->precio_kg_usd ?? 0);
                    $disponible = true;
                    $precioKg   = null;

                    if ($esBase) {
                        // USD: tasa 1, precio base tal cual
                        $precioKg = round($precioBase, 4);
                    } elseif ($tasaVigente && (float) $tasaVigente->tasa > 0) {
                        // BS / COP: precio base × tasa vigente
                        $precioKg = round($precioBase * (float) $tasaVigente->tasa, 4);
                    } else {
                        // Sin tasa vigente: no se puede facturar en esta moneda
                        $disponible = false;
                    }

                    // Si el precio base es 0 o negativo, no facturable
                    if ($precioBase <= 0) {
                        $disponible = false;
                        $precioKg = null;
                    }

                    return [
                        'id'                => $producto->id,
                        'nombre'            => $producto->nombre,
                        'sku'               => $producto->sku ?? 'N/A',
                        'stock_kg'          => (float) $producto->stock_kg,
                        'precio_kg'         => $precioKg,
                        'moneda_id'         => $moneda->id,
                        'moneda_codigo'     => $moneda->codigo,
                        'precio_disponible' => $disponible,
                        'iva_porcentaje'    => (float) ($producto->iva_porcentaje ?? 0),
                    ];
                });

            return response()->json($productos);

        } catch (Exception $e) {
            Log::error('Error en buscarProductos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Error al buscar productos.',
            ], 500);
        }
    }
       /**
     * Anula una factura y restaura el stock de sus líneas.
     *
     * Garantías:
     *  - Atómico: todo dentro de DB::transaction().
     *  - Sin race conditions: usa lockForUpdate() sobre la factura
     *    y sobre cada producto, en orden estable por producto_id.
     *  - Idempotente: si ya está anulada, no hace nada (y lo reporta).
     *  - Trazable: registra en log quién, cuándo y cuántos Kg se restauraron.
     */
    public function anular(Factura $factura)
    {
        try {
            $resultado = DB::transaction(function () use ($factura) {

                // ============================================================
                // 1) BLOQUEAR LA FACTURA Y REVALIDAR ESTADO DENTRO DEL LOCK
                // ============================================================
                // El chequeo previo fuera de la transacción no sirve para
                // concurrencia: dos requests pueden leer 'pendiente' al mismo
                // tiempo. Aquí, con lockForUpdate, el segundo request espera
                // al COMMIT del primero y luego ve 'anulada'.
                $facturaBloqueada = Factura::where('id', $factura->id)
                    ->lockForUpdate()
                    ->first();

                if (!$facturaBloqueada) {
                    return [
                        'success' => false,
                        'message' => 'Factura no encontrada.',
                        'status'  => 404,
                    ];
                }

                if ($facturaBloqueada->estado === Factura::ESTADO_ANULADA) {
                    return [
                        'success' => false,
                        'message' => 'La factura ya está anulada.',
                        'status'  => 422,
                    ];
                }

                // ============================================================
                // 2) RESTAURAR STOCK CON LOCKS EN ORDEN ESTABLE
                // ============================================================
                // Ordenamos por producto_id para evitar deadlocks si otra
                // transacción está tocando los mismos productos en orden
                // distinto (ej. una factura nueva con los mismos productos).
                $lineas = $facturaBloqueada->lineas()
                    ->orderBy('producto_id')
                    ->get();

                $kgRestaurados = 0;
                $productosNoEncontrados = [];

                foreach ($lineas as $linea) {
                    $producto = Producto::where('id', $linea->producto_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$producto) {
                        // No lanzamos excepción: puede ser un producto
                        // eliminado legítimamente. Lo registramos para
                        // trazabilidad pero no abortamos la anulación.
                        $productosNoEncontrados[] = $linea->producto_id;
                        Log::warning('Anulación: producto no encontrado, no se restauró stock', [
                            'factura_id'  => $facturaBloqueada->id,
                            'producto_id' => $linea->producto_id,
                            'cantidad_kg' => $linea->cantidad_kg,
                        ]);
                        continue;
                    }

                    $producto->update([
                        'stock_kg' => $producto->stock_kg + $linea->cantidad_kg,
                    ]);

                    $kgRestaurados += (float) $linea->cantidad_kg;
                }

                // ============================================================
                // 3) MARCAR FACTURA COMO ANULADA
                // ============================================================
                $facturaBloqueada->update([
                    'estado' => Factura::ESTADO_ANULADA,
                ]);

                // ============================================================
                // 4) LOG DE AUDITORÍA
                // ============================================================
                Log::info('Factura anulada', [
                    'factura_id'              => $facturaBloqueada->id,
                    'numero'                  => $facturaBloqueada->numero,
                    'user_id'                 => Auth::id(),
                    'kg_restaurados'          => $kgRestaurados,
                    'productos_no_encontrados' => $productosNoEncontrados,
                ]);

                return [
                    'success' => true,
                    'message' => 'Factura anulada correctamente. Stock restaurado: '
                                 . number_format($kgRestaurados, 3) . ' Kg.',
                    'status'  => 200,
                ];
            });

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
            ], $resultado['status']);

        } catch (Exception $e) {
            Log::error('Error en anular: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al anular la factura. Intente nuevamente.',
            ], 500);
        }
    }

        /**
     * Marca una factura como pagada.
     *
     * Garantías:
     *  - Atómico con lock pesimista: evita doble pago concurrente.
     *  - Valida estado ('pendiente') y que la factura tenga líneas.
     *  - Fija fecha_pago solo si no existía (idempotente).
     */
    public function pagar(Factura $factura)
    {
        try {
            $resultado = DB::transaction(function () use ($factura) {

                // ============================================================
                // 1) LOCK + REVALIDACIÓN DENTRO DE LA TRANSACCIÓN
                // ============================================================
                $facturaBloqueada = Factura::where('id', $factura->id)
                    ->lockForUpdate()
                    ->first();

                if (!$facturaBloqueada) {
                    return [
                        'success' => false,
                        'message' => 'Factura no encontrada.',
                        'status'  => 404,
                    ];
                }

                if ($facturaBloqueada->estado === Factura::ESTADO_PAGADA) {
                    return [
                        'success' => false,
                        'message' => 'La factura ya está pagada.',
                        'status'  => 422,
                    ];
                }

                if ($facturaBloqueada->estado === Factura::ESTADO_ANULADA) {
                    return [
                        'success' => false,
                        'message' => 'No se puede pagar una factura anulada.',
                        'status'  => 422,
                    ];
                }

                // ============================================================
                // 2) VALIDACIÓN DE NEGOCIO: NO PAGAR FACTURA VACÍA
                // ============================================================
                // Una factura sin líneas no debería poder cobrarse.
                if (!$facturaBloqueada->lineas()->exists()) {
                    return [
                        'success' => false,
                        'message' => 'No se puede pagar una factura sin productos.',
                        'status'  => 422,
                    ];
                }

                // ============================================================
                // 3) MARCAR COMO PAGADA
                // ============================================================
                $facturaBloqueada->update([
                    'estado'     => Factura::ESTADO_PAGADA,
                    'fecha_pago' => $facturaBloqueada->fecha_pago ?? now(),
                ]);

                Log::info('Factura pagada', [
                    'factura_id' => $facturaBloqueada->id,
                    'numero'     => $facturaBloqueada->numero,
                    'user_id'    => Auth::id(),
                    'total'      => $facturaBloqueada->total,
                ]);

                return [
                    'success' => true,
                    'message' => 'Factura marcada como pagada.',
                    'status'  => 200,
                ];
            });

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
            ], $resultado['status']);

        } catch (Exception $e) {
            Log::error('Error en pagar: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago. Intente nuevamente.',
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

        /**
     * Genera el siguiente número de factura de forma ATÓMICA.
     *
     * ¿Por qué es atómico?
     * - Dentro de una transacción, bloquea la última fila de `facturas`
     *   con lockForUpdate(). Dos transacciones concurrentes se serializan
     *   aquí: la segunda espera a que la primera haga COMMIT.
     * - Combinado con el índice UNIQUE en `numero`, esto elimina la
     *   posibilidad de números duplicados.
     *
     * ⚠️ Debe llamarse SIEMPRE dentro de DB::transaction().
     */
    private function generarNumeroFactura(): string
    {
        // Bloquea la fila más reciente para que nadie más la lea
        // hasta que terminemos la transacción actual.
        $ultima = Factura::orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$ultima || !$ultima->numero) {
            return 'FACT-00000001';
        }

        // Extraer la parte numérica (asume formato FACT-XXXXXXXX)
        $numero = intval(substr($ultima->numero, -8)) + 1;

        // Límite de seguridad: 8 dígitos = 99.999.999 facturas
        if ($numero > 99999999) {
            throw new \RuntimeException(
                'Se alcanzó el límite máximo de numeración de facturas (99.999.999).'
            );
        }

        return 'FACT-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
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