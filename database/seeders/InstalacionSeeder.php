<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

/**
 * Seeder de instalación: idempotente.
 *
 * A diferencia de DatabaseSeeder (que trunca y reconstruye), este seeder
 * se puede ejecutar varias veces: crea lo que falte y no borra nada.
 */
class InstalacionSeeder extends Seeder
{
    public function run(): void
    {
        // Configuración base: siempre debe existir.
        $this->call([
            UserSeeder::class,
            MonedaSeeder::class,
            CategoriaEgresoSeeder::class,
        ]);

        // Datos de ejemplo: solo si la tabla está vacía.
        if (Producto::query()->count() === 0) {
            $this->call([
                CategoriaSeeder::class,
                ProductoSeeder::class,
            ]);
        }

        if (Cliente::query()->count() === 0) {
            $this->call(ClienteSeeder::class);
        }

        if (TasaCambio::query()->count() === 0) {
            $this->call(TasaCambioSeeder::class);
        }
    }
}
