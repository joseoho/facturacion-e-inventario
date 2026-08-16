<?php

namespace Database\Seeders;

use App\Models\Moneda;
use Illuminate\Database\Seeder;

class MonedaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Moneda Base (USD)
        Moneda::create([
            'codigo' => 'USD',
            'nombre' => 'Dólar Americano',
            'simbolo' => '$',
            'es_base' => true
        ]);

        // Monedas no base
        Moneda::create([
            'codigo' => 'BS',
            'nombre' => 'Bolívar',
            'simbolo' => 'Bs.',
            'es_base' => false
        ]);

        Moneda::create([
            'codigo' => 'COP',
            'nombre' => 'Peso Colombiano',
            'simbolo' => '$',
            'es_base' => false
        ]);

        Moneda::create([
            'codigo' => 'MXN',
            'nombre' => 'Peso Mexicano',
            'simbolo' => '$',
            'es_base' => false
        ]);

        Moneda::create([
            'codigo' => 'EUR',
            'nombre' => 'Euro',
            'simbolo' => '€',
            'es_base' => false
        ]);
    }
}