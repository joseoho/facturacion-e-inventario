<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Factura;

echo "=== DEBUG VENTAS SEMANA ===" . PHP_EOL . PHP_EOL;

$desde  = now()->subDays(6)->startOfDay();
$manana = now()->startOfDay()->addDay();

echo "Rango: [{$desde}] a [{$manana}]" . PHP_EOL . PHP_EOL;

$ventas = Factura::query()
    ->selectRaw('DATE(fecha_emision) as dia, SUM(total) as total')
    ->where('estado', Factura::ESTADO_PAGADA)
    ->where('fecha_emision', '>=', $desde)
    ->where('fecha_emision', '<', $manana)
    ->groupBy('dia')
    ->pluck('total', 'dia');

echo "Colección completa (var_dump):" . PHP_EOL;
var_dump($ventas->toArray());
echo PHP_EOL;

echo "Claves:" . PHP_EOL;
foreach ($ventas as $clave => $valor) {
    echo "  - '{$clave}' => {$valor} (tipo clave: " . gettype($clave) . ")" . PHP_EOL;
}
echo PHP_EOL;

// Lo que el código espera
for ($i = 6; $i >= 0; $i--) {
    $fecha = now()->subDays($i);
    $clave = $fecha->toDateString();
    $encontrado = $ventas[$clave] ?? 'NO ENCONTRADO';
    echo "Buscando '{$clave}': {$encontrado}" . PHP_EOL;
}