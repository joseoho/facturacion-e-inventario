<?php

namespace App\Http\Controllers;

use App\Models\Moneda;
use Illuminate\Support\Facades\Auth;
use App\Models\TasaCambio;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\ActualizacionPrecio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class TasaCambioController extends Controller
{
    /**
     * Mostrar la vista de tasas de cambio con paginación
     */
    public function index(Request $request)
    {
        // Configurar paginación
        $perPage = $request->input('per_page', 20);
        $sort = $request->input('sort', 'fecha');
        $direction = $request->input('direction', 'desc');
        
        // Validar campos de ordenamiento permitidos
        $allowedSorts = ['id', 'moneda_id', 'tasa', 'fecha', 'user_id'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'fecha';
        }
        
        // Construir consulta base
        $query = TasaCambio::with(['moneda', 'user']);

        // Aplicar filtros
        $this->applyFilters($query, $request);

        // Aplicar ordenamiento
        $query->orderBy($sort, $direction);

        // Paginar
        $tasasCambio = $query->paginate($perPage)
            ->appends($request->except(['page']));

        // Datos adicionales
        $monedas = Moneda::all();
        $tasasRecientes = TasaCambio::with(['moneda', 'user'])
            ->latest('fecha')
            ->limit(20)
            ->get();

        $ultimaActualizacion = ActualizacionPrecio::with('usuario')
            ->latest('fecha_ejecucion')
            ->first();

        // Estadísticas mejoradas - SIN EL CAMPO 'activa'
        $stats = $this->getStatistics();

        return view('tasas-cambio.index', compact(
            'tasasCambio',
            'monedas',
            'tasasRecientes',
            'ultimaActualizacion',
            'stats',
            'perPage'
        ));
    }

    /**
     * Aplicar filtros a la consulta
     */
    private function applyFilters($query, Request $request)
    {
        // Filtro por moneda
        if ($request->filled('moneda_id')) {
            $query->where('moneda_id', $request->moneda_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        // Filtro por rango de tasas
        if ($request->filled('tasa_min')) {
            $query->where('tasa', '>=', $request->tasa_min);
        }

        if ($request->filled('tasa_max')) {
            $query->where('tasa', '<=', $request->tasa_max);
        }

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('moneda', function($sub) use ($search) {
                    $sub->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('codigo', 'LIKE', "%{$search}%");
                })
                ->orWhere('tasa', 'LIKE', "%{$search}%")
                ->orWhereHas('user', function($sub) use ($search) {
                    $sub->where('name', 'LIKE', "%{$search}%");
                });
            });
        }
    }

    /**
     * Obtener estadísticas - CORREGIDO sin campo 'activa'
     */
    private function getStatistics()
    {
        $ultimaActualizacion = ActualizacionPrecio::latest('fecha_ejecucion')->first();
        
        return [
            'total' => TasaCambio::count(),
            'ultima_fecha' => TasaCambio::latest('fecha')->first()?->fecha,
            'tasa_promedio' => TasaCambio::avg('tasa'),
            'tasa_max' => TasaCambio::max('tasa'),
            'tasa_min' => TasaCambio::min('tasa'),
            'total_productos' => \App\Models\Producto::count(),
            'total_precios' => \App\Models\PrecioProducto::count(),
            'monedas_activas' => Moneda::count(), // 👈 Cambiado: contar todas las monedas
            'ultima_actualizacion' => $ultimaActualizacion 
                ? $ultimaActualizacion->fecha_ejecucion->format('d/m/Y H:i') 
                : 'Nunca',
        ];
    }

    /**
     * Mostrar formulario para crear nueva tasa
     */
    public function create()
    {
        $monedas = Moneda::all();
        $tasaCambio = new TasaCambio();
        return view('tasas-cambio.create', compact('monedas', 'tasaCambio'));
    }

    /**
     * Almacenar una nueva tasa de cambio
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'moneda_id' => 'required|exists:monedas,id',
            'tasa' => 'required|numeric|min:0.000001',
            'fecha' => 'required|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $existente = TasaCambio::where('moneda_id', $request->moneda_id)
                ->whereDate('fecha', $request->fecha)
                ->first();

            if ($existente) {
                $existente->update([
                    'tasa' => $request->tasa,
                    'user_id' => Auth::id()
                ]);
                $mensaje = 'Tasa de cambio actualizada correctamente.';
            } else {
                TasaCambio::create([
                    'moneda_id' => $request->moneda_id,
                    'tasa' => $request->tasa,
                    'fecha' => $request->fecha,
                    'user_id' => Auth::id()
                ]);
                $mensaje = 'Tasa de cambio registrada correctamente.';
            }

            DB::commit();

            return redirect()
                ->route('tasas.index', $request->except(['_token']))
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar tasa de cambio: ' . $e->getMessage());
            
            return back()
                ->with('error', 'Error al guardar la tasa de cambio: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar una tasa específica
     */
    public function show($id)
    {
        $tasaCambio = TasaCambio::with(['moneda', 'user'])->findOrFail($id);
        return view('tasas-cambio.show', compact('tasaCambio'));
    }

    /**
     * Mostrar formulario para editar tasa
     */
    public function edit($id)
    {
        $tasaCambio = TasaCambio::findOrFail($id);
        $monedas = Moneda::all();
        return view('tasas-cambio.edit', compact('tasaCambio', 'monedas'));
    }

    /**
     * Actualizar una tasa específica
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'moneda_id' => 'required|exists:monedas,id',
            'tasa' => 'required|numeric|min:0.000001',
            'fecha' => 'required|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $tasaCambio = TasaCambio::findOrFail($id);
            $tasaCambio->update([
                'moneda_id' => $request->moneda_id,
                'tasa' => $request->tasa,
                'fecha' => $request->fecha,
                'user_id' => Auth::id()
            ]);

            return redirect()
                ->route('tasas.index')
                ->with('success', 'Tasa de cambio actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error al actualizar tasa: ' . $e->getMessage());
            return back()
                ->with('error', 'Error al actualizar la tasa: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar una tasa de cambio
     */
    public function destroy($id)
    {
        try {
            $tasaCambio = TasaCambio::findOrFail($id);
            
            // Verificar si tiene relaciones
            if (method_exists($tasaCambio, 'preciosProductos') && $tasaCambio->preciosProductos()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar esta tasa porque tiene precios de productos asociados.'
                ], 422);
            }
            
            if (method_exists($tasaCambio, 'facturas') && $tasaCambio->facturas()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar esta tasa porque tiene facturas asociadas.'
                ], 422);
            }
            
            $tasaCambio->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tasa de cambio eliminada exitosamente.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar tasa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tasa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar una tasa
     */
    public function duplicate($id)
    {
        try {
            $tasaOriginal = TasaCambio::findOrFail($id);
            
            $nuevaTasa = $tasaOriginal->replicate();
            $nuevaTasa->fecha = now()->toDateString();
            $nuevaTasa->user_id = Auth::id();
            $nuevaTasa->save();

            return redirect()
                ->route('tasas.index')
                ->with('success', 'Tasa duplicada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error al duplicar tasa: ' . $e->getMessage());
            return back()
                ->with('error', 'Error al duplicar la tasa: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar historial de tasas de cambio
     */
    public function historial(Request $request)
    {
        $query = TasaCambio::with(['moneda', 'user']);

        if ($request->filled('moneda_id')) {
            $query->where('moneda_id', $request->moneda_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        $historial = $query->latest('fecha')->paginate(50);
        $monedas = Moneda::all();

        return view('tasas-cambio.historial', compact('historial', 'monedas'));
    }

    /**
     * Ejecutar actualización masiva de precios
     */
    // // public function actualizarPrecios(Request $request)
    // // {
    // //     $validator = Validator::make($request->all(), [
    // //         'tasa_cambio_id' => 'required|exists:tasas_cambio,id',
    // //     ]);

    // //     if ($validator->fails()) {
    // //         return response()->json([
    // //             'success' => false,
    // //             'errors' => $validator->errors()
    // //         ], 422);
    // //     }

    // //     try {
    // //         $tasa = TasaCambio::findOrFail($request->tasa_cambio_id);

    // //         if ($tasa->moneda->es_base) {
    // //             return response()->json([
    // //                 'success' => false,
    // //                 'message' => 'No se puede actualizar precios con la moneda base.'
    // //             ], 400);
    // //         }

    // //         // Despachar el job
    // //         $job = new ActualizarPreciosProductosJob($tasa, Auth::id());
    // //         dispatch($job);

    // //         // Registrar la actualización
    // //         ActualizacionPrecio::create([
    // //             'fecha_ejecucion' => now(),
    // //             'usuario_id' => Auth::id(),
    // //             'tasa_cambio_id' => $tasa->id,
    // //             'estado' => 'en_proceso',
    // //             'detalles' => json_encode([
    // //                 'moneda' => $tasa->moneda->codigo,
    // //                 'tasa' => $tasa->tasa,
    // //                 'iniciado_por' => Auth::user()->name
    // //             ])
    // //         ]);

    // //         Log::info('Actualización de precios iniciada', [
    // //             'user_id' => Auth::id(),
    // //             'tasa_id' => $tasa->id,
    // //             'moneda' => $tasa->moneda->codigo,
    // //             'tasa_valor' => $tasa->tasa
    // //         ]);

    // //         return response()->json([
    // //             'success' => true,
    // //             'message' => 'Actualización de precios iniciada correctamente. Los precios se actualizarán en segundo plano.',
    // //             'tasa_id' => $tasa->id
    // //         ]);

    // //     } catch (\Exception $e) {
    // //         Log::error('Error al iniciar actualización de precios: ' . $e->getMessage());
            
    // //         return response()->json([
    // //             'success' => false,
    // //             'message' => 'Error al iniciar la actualización: ' . $e->getMessage()
    // //         ], 500);
    // //     }
    // }

    /**
     * Obtener las últimas tasas de cambio para todas las monedas - CORREGIDO
     */
    public function ultimasTasas()
    {
        try {
            // Obtener todas las monedas (sin filtro de 'activa')
            $monedas = Moneda::all();
            $tasas = [];
            
            foreach ($monedas as $moneda) {
                $tasa = TasaCambio::where('moneda_id', $moneda->id)
                    ->latest('fecha')
                    ->first();
                    
                if ($tasa) {
                    $tasas[] = [
                        'id' => $tasa->id,
                        'moneda_id' => $moneda->id,
                        'codigo' => $moneda->codigo,
                        'nombre' => $moneda->nombre,
                        'tasa' => $tasa->tasa,
                        'fecha' => $tasa->fecha->format('Y-m-d H:i:s'),
                        'es_base' => $moneda->es_base
                    ];
                }
            }
            
            return response()->json($tasas);
        } catch (\Exception $e) {
            Log::error('Error al obtener últimas tasas: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener las tasas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar tasas a CSV
     */
    public function export(Request $request)
    {
        $query = TasaCambio::with(['moneda', 'user']);

        if ($request->filled('moneda_id')) {
            $query->where('moneda_id', $request->moneda_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $tasas = $query->latest('fecha')->get();

        $filename = 'tasas_cambio_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        fputcsv($handle, ['ID', 'Moneda', 'Código', 'Tasa', 'Fecha', 'Registrado por']);
        
        foreach ($tasas as $tasa) {
            fputcsv($handle, [
                $tasa->id,
                $tasa->moneda->nombre,
                $tasa->moneda->codigo,
                number_format($tasa->tasa, 6),
                $tasa->fecha->format('Y-m-d H:i:s'),
                $tasa->user ? $tasa->user->name : 'N/A'
            ]);
        }
        
        fclose($handle);
        exit;
    }

public function actualizarPrecios(Request $request)
{
    try {
        DB::beginTransaction();

        // 1. Validar
        $validator = Validator::make($request->all(), [
            'tasa_cambio_id' => 'required|exists:tasas_cambio,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Obtener la tasa seleccionada
        $tasaSeleccionada = TasaCambio::with('moneda')->find($request->tasa_cambio_id);
        
        if (!$tasaSeleccionada || $tasaSeleccionada->moneda->es_base || $tasaSeleccionada->tasa <= 0) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Tasa de cambio inválida o es la moneda base.'
            ], 400);
        }

        // 3. Obtener tasa base (si no existe, usar 1)
        $tasaBaseValor = TasaCambio::whereHas('moneda', function($q) {
            $q->where('es_base', true);
        })->latest('fecha')->value('tasa') ?? 1.000000;

        // 4. Obtener ID del usuario
        $userId = Auth::id() ?? DB::table('users')->value('id') ?? 1;

        // 5. ACTUALIZACIÓN MASIVA CON SQL DIRECTO - FÓRMULA CORREGIDA
        // 5.1 Eliminar precios antiguos
        DB::table('precios_productos')
            ->where('moneda_id', $tasaSeleccionada->moneda_id)
            ->delete();

        // 5.2 Insertar nuevos precios en UNA sola consulta
        // ✅ FÓRMULA CORRECTA: Precio en moneda local = (Precio USD × Tasa Base) × Tasa de la moneda
        $insertados = DB::statement("
            INSERT INTO precios_productos (producto_id, moneda_id, tasa_cambio_id, precio_kg, created_at, updated_at)
            SELECT 
                p.id as producto_id,
                ? as moneda_id,
                ? as tasa_cambio_id,
                ROUND((p.precio_kg_usd * ?) * ?, 4) as precio_kg,
                NOW() as created_at,
                NOW() as updated_at
            FROM productos p
            WHERE p.activo = 1 
            AND p.precio_kg_usd > 0
        ", [
            $tasaSeleccionada->moneda_id,
            $tasaSeleccionada->id,
            $tasaBaseValor,      // Tasa base (normalmente 1.00)
            $tasaSeleccionada->tasa  // Tasa de la moneda destino (ej: 764.35)
        ]);

        // 6. Contar productos actualizados
        $totalProductos = DB::table('precios_productos')
            ->where('moneda_id', $tasaSeleccionada->moneda_id)
            ->count();

        // 7. Guardar registro de actualización
        try {
            ActualizacionPrecio::create([
                'user_id' => $userId,
                'fecha_ejecucion' => now(),
                'monedas_actualizadas' => json_encode([
                    'moneda_id' => $tasaSeleccionada->moneda_id,
                    'moneda_codigo' => $tasaSeleccionada->moneda->codigo,
                    'moneda_nombre' => $tasaSeleccionada->moneda->nombre,
                    'tasa_usada' => $tasaSeleccionada->tasa,
                    'tasa_base' => $tasaBaseValor,
                    'formula_usada' => '(Precio_USD × Tasa_Base) × Tasa_Moneda'
                ]),
                'cantidad_productos' => $totalProductos,
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo guardar registro: ' . $e->getMessage());
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "✅ ¡$totalProductos precios actualizados en " . $tasaSeleccionada->moneda->codigo . "!",
            'productos_actualizados' => $totalProductos,
            'moneda' => $tasaSeleccionada->moneda->codigo,
            'tasa_usada' => $tasaSeleccionada->tasa,
            'ejemplo_calculo' => [
                'precio_usd' => '8.75',
                'tasa_base' => $tasaBaseValor,
                'tasa_moneda' => $tasaSeleccionada->tasa,
                'resultado' => round(8.75 * $tasaBaseValor * $tasaSeleccionada->tasa, 4)
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error en actualizarPrecios: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => '❌ Error: ' . $e->getMessage()
        ], 500);
    }
}
}