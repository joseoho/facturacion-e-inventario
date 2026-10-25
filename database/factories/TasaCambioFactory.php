<?php

namespace Database\Factories;

use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TasaCambio>
 */
class TasaCambioFactory extends Factory
{
    protected $model = TasaCambio::class;

    public function definition(): array
    {
        return [
            'moneda_id' => Moneda::factory(),
            'tasa' => fake()->randomFloat(6, 1, 500),
            'fecha' => fake()->unique()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'user_id' => User::factory(),
        ];
    }
}
