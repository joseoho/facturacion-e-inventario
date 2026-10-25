<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Moneda;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Factura>
 */
class FacturaFactory extends Factory
{
    protected $model = Factura::class;

    public function definition(): array
    {
        return [
            'numero' => 'FACT-'.fake()->unique()->numerify('########'),
            'cliente_id' => Cliente::factory(),
            'user_id' => User::factory(),
            'moneda_id' => Moneda::factory()->base(),
            'tasa_cambio_id' => null,
            'moneda_pago' => 'USD',
            'subtotal_neto' => 100,
            'total_impuesto' => 16,
            'total' => 116,
            'estado' => Factura::ESTADO_PENDIENTE,
            'fecha_emision' => now()->toDateString(),
        ];
    }
}
