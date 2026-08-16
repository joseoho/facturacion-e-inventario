<?php

namespace Database\Seeders;

use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener datos necesarios
        $clientes = Cliente::where('activo', true)->get();
        $productos = Producto::where('activo', true)->get();
        $monedaBs = Moneda::where('codigo', 'BS')->first();
        $monedaUsd = Moneda::where('codigo', 'USD')->first();
        $vendedor = User::where('role', 'vendedor')->first();
        
        if (!$vendedor) {
            $vendedor = User::first();
        }

        // Obtener tasas de cambio
        $tasaBs = TasaCambio::where('moneda_id', $monedaBs->id)
            ->latest('fecha')
            ->first();

        // Crear facturas de ejemplo
        for ($i = 0; $i < 15; $i++) {
            $fecha = now()->subDays(rand(1, 30));
            $cliente = $clientes->random();
            $moneda = rand(0, 1) ? $monedaBs : $monedaUsd;
            $tasa = $moneda->es_base ? null : TasaCambio::where('moneda_id', $moneda->id)
                ->where('fecha', '<=', $fecha)
                ->latest('fecha')
                ->first();

            // Crear factura
            $factura = Factura::create([
                'cliente_id' => $cliente->id,
                'user_id' => $vendedor->id,
                'moneda_id' => $moneda->id,
                'tasa_cambio_id' => $tasa ? $tasa->id : null,
                'subtotal_neto' => 0,
                'total_impuesto' => 0,
                'total' => 0,
                'estado' => $this->getEstadoAleatorio(),
                'fecha_emision' => $fecha,
            ]);

            // Agregar líneas
            $numLineas = rand(1, 5);
            $subtotal = 0;
            $impuesto = 0;
            $total = 0;

            for ($j = 0; $j < $numLineas; $j++) {
                $producto = $productos->random();
                $cantidad = rand(1, 30) / 10; // 0.1 a 3.0 Kg
                
                // Obtener precio en la moneda de la factura
                $precioKg = $producto->precios()
                    ->where('moneda_id', $moneda->id)
                    ->where('tasa_cambio_id', $tasa ? $tasa->id : null)
                    ->latest()
                    ->first();

                $precio = $precioKg ? $precioKg->precio_kg : $producto->precio_kg_usd;
                
                $neto = $precio * $cantidad;
                $ivaPorcentaje = $producto->iva_porcentaje;
                $ivaMonto = $neto * ($ivaPorcentaje / 100);
                $totalLinea = $neto + $ivaMonto;

                FacturaLinea::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto->id,
                    'cantidad_kg' => $cantidad,
                    'precio_kg' => $precio,
                    'neto' => $neto,
                    'impuesto_porcentaje' => $ivaPorcentaje,
                    'impuesto_monto' => $ivaMonto,
                    'total' => $totalLinea
                ]);

                $subtotal += $neto;
                $impuesto += $ivaMonto;
                $total += $totalLinea;

                // Reducir stock
                $producto->reducirStock($cantidad);
            }

            // Actualizar totales de la factura
            $factura->update([
                'subtotal_neto' => $subtotal,
                'total_impuesto' => $impuesto,
                'total' => $total
            ]);

            // Si la factura está pagada, marcar como tal
            if ($factura->estado === 'pagada') {
                $factura->pagar();
            }
        }

        $this->command->info('✅ Facturas de ejemplo creadas correctamente.');
    }

    /**
     * Obtener estado aleatorio
     */
    private function getEstadoAleatorio()
    {
        $estados = ['pendiente', 'pagada', 'anulada'];
        $pesos = [40, 50, 10]; // 40% pendiente, 50% pagada, 10% anulada
        
        $suma = array_sum($pesos);
        $random = rand(1, $suma);
        
        $acumulado = 0;
        foreach ($estados as $index => $estado) {
            $acumulado += $pesos[$index];
            if ($random <= $acumulado) {
                return $estado;
            }
        }
        
        return 'pendiente';
    }
}