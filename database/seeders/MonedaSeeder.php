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
        $monedas = [
            ['codigo' => 'USD', 'nombre' => 'Dólar Americano', 'simbolo' => '$', 'es_base' => true],
            ['codigo' => 'BS', 'nombre' => 'Bolívar', 'simbolo' => 'Bs.', 'es_base' => false],
            ['codigo' => 'COP', 'nombre' => 'Peso Colombiano', 'simbolo' => '$', 'es_base' => false],
            ['codigo' => 'MXN', 'nombre' => 'Peso Mexicano', 'simbolo' => '$', 'es_base' => false],
            ['codigo' => 'EUR', 'nombre' => 'Euro', 'simbolo' => '€', 'es_base' => false],
        ];

        foreach ($monedas as $moneda) {
            Moneda::firstOrCreate(['codigo' => $moneda['codigo']], $moneda);
        }
    }
}
