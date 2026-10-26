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
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (! $admin) {
            return;
        }

        $bs = Moneda::where('codigo', 'BS')->first();
        $cop = Moneda::where('codigo', 'COP')->first();
        $eur = Moneda::where('codigo', 'EUR')->first();

        // Histórico de ejemplo de los últimos 30 días.
        for ($i = 0; $i < 30; $i++) {
            $fecha = now()->subDays($i)->toDateString();

            if ($bs) {
                $tasa = 755.90 + ($i * 2.5) + (rand(-30, 30) / 100);

                TasaCambio::updateOrCreate(
                    ['moneda_id' => $bs->id, 'fecha' => $fecha],
                    ['tasa' => max(700, $tasa), 'user_id' => $admin->id]
                );
            }

            if ($cop) {
                $tasa = 3200 + ($i * 15) + (rand(-200, 200) / 100);

                TasaCambio::updateOrCreate(
                    ['moneda_id' => $cop->id, 'fecha' => $fecha],
                    ['tasa' => max(3800, $tasa), 'user_id' => $admin->id]
                );
            }

            if ($eur) {
                $tasa = 872.90 + (rand(-5, 5) / 1000);

                TasaCambio::updateOrCreate(
                    ['moneda_id' => $eur->id, 'fecha' => $fecha],
                    ['tasa' => max(0.90, $tasa), 'user_id' => $admin->id]
                );
            }
        }

        // Tasa del día actual.
        $hoy = now()->toDateString();

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
