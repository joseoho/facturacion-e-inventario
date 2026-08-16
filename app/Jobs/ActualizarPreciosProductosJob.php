<?php

namespace App\Jobs;

use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\ActualizacionPrecio;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ActualizarPreciosProductosJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TasaCambio $tasaCambio,
        public int $logId
    ) {}

    public function handle(): void
    {
        // Buscar el registro de auditoría en la tabla actualizaciones_precios
        $log = ActualizacionPrecio::find($this->logId);
        $totalProcesados = 0;

        try {
            // Recorremos los productos activos en lotes de 200
            Producto::where('activo', true)->chunk(200, function ($productos) use (&$totalProcesados) {
                foreach ($productos as $producto) {
                    // Cálculo del nuevo precio local respecto a la tasa
                    $precioLocal = round($producto->precio_kg_usd * $this->tasaCambio->tasa, 4);

                    // Insertar o actualizar el registro en precios_productos
                    DB::table('precios_productos')->updateOrInsert(
                        [
                            'producto_id' => $producto->id,
                            'moneda_id'   => $this->tasaCambio->moneda_id,
                        ],
                        [
                            'precio_kg_local' => $precioLocal,
                            'updated_at'      => now(),
                        ]
                    );

                    $totalProcesados++;
                }
            });

            // Actualizar estado del log de auditoría
            if ($log) {
                $log->update([
                    'total_productos_afectados' => $totalProcesados,
                    'status'                    => 'completado',
                ]);
            }

        } catch (Exception $e) {
            Log::error("Error en ActualizarPreciosProductosJob: " . $e->getMessage());

            if ($log) {
                $log->update(['status' => 'fallido']);
            }
        }
    }
}