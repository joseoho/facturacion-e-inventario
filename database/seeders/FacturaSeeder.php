<?php

namespace Database\Seeders;

use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Models\User;
use App\Models\PrecioProducto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Validar datos mínimos necesarios
        $this->validarDatosNecesarios();

        // Obtener datos necesarios con optimización
        $clientes = Cliente::where('activo', true)->get();
        $productos = Producto::where('activo', true)->get();
        $monedas = $this->obtenerMonedas();
        $vendedor = $this->obtenerVendedor();
        $tasaBase = $this->obtenerTasaBase($monedas['bs']);

        // Obtener el último número de factura para continuar
        $ultimoNumero = $this->obtenerUltimoNumeroFactura();

        // Usar transacción para mejor rendimiento e integridad
        DB::transaction(function () use ($clientes, $productos, $monedas, $vendedor, $tasaBase, $ultimoNumero) {
            // Crear facturas de ejemplo
            for ($i = 0; $i < 15; $i++) {
                $fecha = $this->generarFechaAleatoria();
                $cliente = $clientes->random();
                $moneda = $this->seleccionarMoneda($monedas);
                $tasa = $this->obtenerTasaCambio($moneda, $fecha, $monedas['bs'], $tasaBase);
                
                // Crear factura con sus líneas
                $factura = $this->crearFacturaConLineas(
                    $i, 
                    $cliente, 
                    $vendedor, 
                    $moneda, 
                    $tasa, 
                    $fecha, 
                    $productos,
                    $ultimoNumero + $i + 1
                );
            }
        });

        $this->command->info('✅ Facturas de ejemplo creadas correctamente.');
        $this->mostrarResumen();
    }

    /**
     * Validar que existan datos necesarios en la base de datos
     */
    private function validarDatosNecesarios(): void
    {
        $errores = [];

        if (Cliente::where('activo', true)->count() === 0) {
            $errores[] = 'No hay clientes activos';
        }

        if (Producto::where('activo', true)->count() === 0) {
            $errores[] = 'No hay productos activos';
        }

        if (Moneda::count() === 0) {
            $errores[] = 'No hay monedas registradas';
        }

        if (TasaCambio::count() === 0) {
            $errores[] = 'No hay tasas de cambio registradas';
        }

        if (!empty($errores)) {
            $this->command->error('❌ Error: ' . implode(', ', $errores));
            $this->command->info('⚠️  Ejecuta primero los seeders de: Clientes, Productos, Monedas y Tasas de Cambio');
            throw new \Exception('Faltan datos necesarios para ejecutar el seeder');
        }
    }

    /**
     * Obtener el último número de factura
     */
    private function obtenerUltimoNumeroFactura(): int
    {
        $ultimaFactura = Factura::orderBy('id', 'desc')->first();
        
        if ($ultimaFactura) {
            $numero = (int) substr($ultimaFactura->numero, 1);
            return $numero;
        }
        
        return 0;
    }

    /**
     * Obtener monedas optimizado
     */
    private function obtenerMonedas(): array
    {
        $monedas = Moneda::whereIn('codigo', ['BS', 'USD'])->get()->keyBy('codigo');
        
        return [
            'bs' => $monedas->get('BS'),
            'usd' => $monedas->get('USD')
        ];
    }

    /**
     * Obtener vendedor
     */
    private function obtenerVendedor(): User
    {
        $vendedor = User::where('role', 'vendedor')->first();
        
        if (!$vendedor) {
            $vendedor = User::first();
            if (!$vendedor) {
                $vendedor = User::create([
                    'name' => 'Vendedor Default',
                    'email' => 'vendedor@default.com',
                    'password' => bcrypt('password'),
                    'role' => 'vendedor'
                ]);
            }
        }
        
        return $vendedor;
    }

    /**
     * Obtener tasa base
     */
    private function obtenerTasaBase($monedaBs): TasaCambio
    {
        $tasaBase = TasaCambio::where('moneda_id', $monedaBs->id)
            ->latest('fecha')
            ->first();

        if (!$tasaBase) {
            $tasaBase = TasaCambio::create([
                'moneda_id' => $monedaBs->id,
                'tasa' => 1.00,
                'fecha' => now(),
            ]);
        }

        return $tasaBase;
    }

    /**
     * Generar fecha aleatoria
     */
    private function generarFechaAleatoria(): Carbon
    {
        return now()->subDays(rand(1, 30));
    }

    /**
     * Seleccionar moneda aleatoria
     */
    private function seleccionarMoneda(array $monedas): Moneda
    {
        return rand(0, 1) ? $monedas['bs'] : $monedas['usd'];
    }

    /**
     * Obtener tasa de cambio
     */
    private function obtenerTasaCambio(Moneda $moneda, Carbon $fecha, $monedaBs, TasaCambio $tasaBase): ?TasaCambio
    {
        if ($moneda->id === $monedaBs->id) {
            return $tasaBase;
        }

        $tasa = TasaCambio::where('moneda_id', $moneda->id)
            ->where('fecha', '<=', $fecha)
            ->latest('fecha')
            ->first();

        if (!$tasa) {
            $tasa = TasaCambio::where('moneda_id', $moneda->id)
                ->latest('fecha')
                ->first();
        }

        return $tasa ?? $tasaBase;
    }

    /**
     * Crear factura con sus líneas
     */
    private function crearFacturaConLineas(
        int $index,
        Cliente $cliente,
        User $vendedor,
        Moneda $moneda,
        TasaCambio $tasa,
        Carbon $fecha,
        $productos,
        int $numeroSecuencial
    ): Factura {
        $estado = $this->getEstadoAleatorio();
        
        // Crear factura
        $factura = Factura::create([
            'numero' => $this->generarNumeroFactura($numeroSecuencial),
            'cliente_id' => $cliente->id,
            'user_id' => $vendedor->id,
            'moneda_id' => $moneda->id,
            'tasa_cambio_id' => $tasa->id,
            'subtotal_neto' => 0,
            'total_impuesto' => 0,
            'total' => 0,
            'estado' => $estado,
            'fecha_emision' => $fecha,
        ]);

        // Agregar líneas y calcular totales
        $totales = $this->agregarLineasFactura($factura, $productos, $moneda, $tasa);

        // Actualizar totales
        $factura->update($totales);

        // Si la factura está pagada, actualizar fecha de pago si existe el campo
        if ($estado === 'pagada') {
            $this->marcarFacturaComoPagada($factura);
        }

        return $factura;
    }

    /**
     * Marcar factura como pagada
     */
    private function marcarFacturaComoPagada(Factura $factura): void
    {
        try {
            // Intentar usar el método pagar() si existe
            if (method_exists($factura, 'pagar')) {
                $factura->pagar();
                return;
            }

            // Si no existe el método, actualizar manualmente
            // Verificar si existe el campo fecha_pago
            $columnas = DB::getSchemaBuilder()->getColumnListing('facturas');
            
            $dataToUpdate = [];
            
            // Si existe el campo fecha_pago, actualizarlo
            if (in_array('fecha_pago', $columnas)) {
                $dataToUpdate['fecha_pago'] = now();
            }
            
            // Si existe el campo estado, ya está establecido
            // Solo actualizar si hay datos adicionales
            if (!empty($dataToUpdate)) {
                $factura->update($dataToUpdate);
            }
            
        } catch (\Exception $e) {
            $this->command->warn("⚠️  No se pudo marcar la factura {$factura->numero} como pagada: {$e->getMessage()}");
        }
    }

    /**
     * Generar número de factura
     */
    private function generarNumeroFactura(int $numero): string
    {
        return 'F' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Agregar líneas a la factura y calcular totales
     */
    private function agregarLineasFactura(Factura $factura, $productos, Moneda $moneda, TasaCambio $tasa): array
    {
        $numLineas = rand(1, 5);
        $subtotal = 0;
        $impuesto = 0;
        $total = 0;

        // Obtener productos con sus precios
        $productosConPrecios = $this->prepararProductosConPrecios($productos, $moneda, $tasa);

        // Si no hay productos con precios, usar valores por defecto
        if ($productosConPrecios->isEmpty()) {
            $this->command->warn('⚠️  No se encontraron productos con precios. Usando valores por defecto.');
            $productosConPrecios = $this->prepararProductosConPreciosPorDefecto($productos);
        }

        // Limitar a productos disponibles
        $productosDisponibles = $productosConPrecios->filter(function ($producto) {
            return $producto['stock'] > 0;
        });

        // Si no hay productos con stock, usar todos
        if ($productosDisponibles->isEmpty()) {
            $productosDisponibles = $productosConPrecios;
        }

        for ($j = 0; $j < $numLineas; $j++) {
            $productoData = $productosDisponibles->random();
            
            // Calcular cantidad máxima disponible
            $stockDisponible = $productoData['stock'];
            $cantidadMaxima = min(30, $stockDisponible);
            $cantidad = $this->generarCantidad($cantidadMaxima);
            
            // Si no hay stock suficiente, saltar este producto
            if ($cantidad <= 0) {
                continue;
            }
            
            $precio = $productoData['precio'];
            
            $neto = $precio * $cantidad;
            $ivaPorcentaje = $productoData['iva_porcentaje'] ?? 16;
            $ivaMonto = $neto * ($ivaPorcentaje / 100);
            $totalLinea = $neto + $ivaMonto;

            // Crear línea
            FacturaLinea::create([
                'factura_id' => $factura->id,
                'producto_id' => $productoData['id'],
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
            $this->reducirStockProducto($productoData['model'], $cantidad);
        }

        return [
            'subtotal_neto' => $subtotal,
            'total_impuesto' => $impuesto,
            'total' => $total
        ];
    }

    /**
     * Preparar productos con sus precios desde la tabla precios_productos
     */
    private function prepararProductosConPrecios($productos, Moneda $moneda, TasaCambio $tasa)
    {
        return $productos->map(function ($producto) use ($moneda, $tasa) {
            // Buscar precio en precios_productos para esta moneda y tasa
            $precioProducto = PrecioProducto::where('producto_id', $producto->id)
                ->where('moneda_id', $moneda->id)
                ->where('tasa_cambio_id', $tasa->id)
                ->latest()
                ->first();

            // Si no hay precio para esta tasa, buscar solo por moneda
            if (!$precioProducto) {
                $precioProducto = PrecioProducto::where('producto_id', $producto->id)
                    ->where('moneda_id', $moneda->id)
                    ->latest()
                    ->first();
            }

            // Si aún no hay precio, usar el precio por defecto en USD
            $precio = $precioProducto 
                ? $precioProducto->precio_kg 
                : $producto->precio_kg_usd;

            return [
                'id' => $producto->id,
                'model' => $producto,
                'precio' => $precio,
                'stock' => $producto->stock_kg ?? 0,
                'iva_porcentaje' => $producto->iva_porcentaje ?? 16,
            ];
        })->filter(function ($producto) {
            return $producto['precio'] > 0;
        });
    }

    /**
     * Preparar productos con precios por defecto si no hay precios configurados
     */
    private function prepararProductosConPreciosPorDefecto($productos)
    {
        return $productos->map(function ($producto) {
            return [
                'id' => $producto->id,
                'model' => $producto,
                'precio' => $producto->precio_kg_usd ?? rand(50, 500) / 10,
                'stock' => $producto->stock_kg ?? 100,
                'iva_porcentaje' => $producto->iva_porcentaje ?? 16,
            ];
        });
    }

    /**
     * Generar cantidad aleatoria
     */
    private function generarCantidad(float $maximo = 30): float
    {
        $minimo = 0.1;
        $cantidad = round(rand(10, 300) / 10, 1);
        
        // Asegurar que no exceda el máximo
        if ($cantidad > $maximo) {
            $cantidad = round($maximo / 2, 1);
        }
        
        // Asegurar que sea al menos el mínimo
        if ($cantidad < $minimo) {
            $cantidad = $minimo;
        }
        
        return $cantidad;
    }

    /**
     * Reducir stock de producto
     */
    private function reducirStockProducto($producto, float $cantidad): void
    {
        try {
            // Intentar usar el método reducirStock si existe
            if (method_exists($producto, 'reducirStock')) {
                $producto->reducirStock($cantidad);
                return;
            }

            // Reducir stock manualmente usando stock_kg
            if (isset($producto->stock_kg)) {
                $nuevoStock = max(0, $producto->stock_kg - $cantidad);
                $producto->stock_kg = $nuevoStock;
                $producto->save();
            }
            
        } catch (\Exception $e) {
            $this->command->warn("⚠️  No se pudo reducir stock del producto {$producto->id}: {$e->getMessage()}");
        }
    }

    /**
     * Obtener estado aleatorio
     */
    private function getEstadoAleatorio(): string
    {
        $estados = ['pendiente', 'pagada', 'anulada'];
        $pesos = [40, 50, 10];
        
        $random = rand(1, 100);
        
        $acumulado = 0;
        foreach ($estados as $index => $estado) {
            $acumulado += $pesos[$index];
            if ($random <= $acumulado) {
                return $estado;
            }
        }
        
        return 'pendiente';
    }

    /**
     * Mostrar resumen de facturas creadas
     */
    private function mostrarResumen(): void
    {
        $totalFacturas = Factura::count();
        $facturasPendientes = Factura::where('estado', 'pendiente')->count();
        $facturasPagadas = Factura::where('estado', 'pagada')->count();
        $facturasAnuladas = Factura::where('estado', 'anulada')->count();
        
        $this->command->info('📊 Resumen:');
        $this->command->info("   Total facturas: {$totalFacturas}");
        $this->command->info("   Pendientes: {$facturasPendientes}");
        $this->command->info("   Pagadas: {$facturasPagadas}");
        $this->command->info("   Anuladas: {$facturasAnuladas}");
    }
}