<?php

namespace Database\Seeders;

use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Database\Seeder;

class TasaCambioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el usuario administrador
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $admin = User::first();
        }

        // Obtener monedas
        $usd = Moneda::where('codigo', 'USD')->first();
        $bs = Moneda::where('codigo', 'BS')->first();
        $cop = Moneda::where('codigo', 'COP')->first();
        $mxn = Moneda::where('codigo', 'MXN')->first();
        $eur = Moneda::where('codigo', 'EUR')->first();

        // Fechas de ejemplo (últimos 30 días)
        $fechas = [];
        for ($i = 0; $i < 30; $i++) {
            $fechas[] = now()->subDays($i);
        }

        // Tasas de cambio históricas (simulando fluctuaciones)
        $tasas = [
            'BS' => [
                'base' => 755.90,
                'variacion' => 2.5,
                'tendencia' => 'creciente'
            ],
            'COP' => [
                'base' => 3200.00,
                'variacion' => 50,
                'tendencia' => 'creciente'
            ],
            
            'EUR' => [
                'base' => 872.90,
                'variacion' => 0.02,
                'tendencia' => 'estable'
            ]
        ];

        // Generar tasas para cada moneda no base
        foreach ($fechas as $index => $fecha) {
            // Tasa para BS
            if ($bs) {
                $variacion = rand(-30, 30) / 100; // -0.30 a 0.30
                $tasa = 755.90 + ($index * 2.5) + $variacion;
                TasaCambio::create([
                    'moneda_id' => $bs->id,
                    'tasa' => max(700, $tasa),
                    'fecha' => $fecha,
                    'user_id' => $admin->id
                ]);
            }

            // Tasa para COP
            if ($cop) {
                $variacion = rand(-200, 200) / 100; // -2.00 a 2.00
                $tasa = 3200 + ($index * 15) + $variacion;
                TasaCambio::create([
                    'moneda_id' => $cop->id,
                    'tasa' => max(3800, $tasa),
                    'fecha' => $fecha,
                    'user_id' => $admin->id
                ]);
            }

           
            // Tasa para EUR
            if ($eur) {
                $variacion = rand(-5, 5) / 1000; // -0.005 a 0.005
                $tasa = 872.90 + $variacion;
                TasaCambio::create([
                    'moneda_id' => $eur->id,
                    'tasa' => max(0.90, $tasa),
                    'fecha' => $fecha,
                    'user_id' => $admin->id
                ]);
            }
        }

        // Crear una tasa del día actual
        $hoy = now()->format('Y-m-d');
        if ($bs) {
            TasaCambio::updateOrCreate(
                ['moneda_id' => $bs->id, 'fecha' => $hoy],
                ['tasa' => 755.90, 'user_id' => $admin->id]
            );
        }
        if ($cop) {
            TasaCambio::updateOrCreate(
                ['moneda_id' => $cop->id, 'fecha' => $hoy],
                ['tasa' => 3200.00, 'user_id' => $admin->id]
            );
        }
    }
}