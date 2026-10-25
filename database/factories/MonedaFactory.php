<?php

namespace Database\Factories;

use App\Models\Moneda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Moneda>
 */
class MonedaFactory extends Factory
{
    protected $model = Moneda::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->lexify('???')),
            'nombre' => ucfirst(fake()->word()),
            'simbolo' => '$',
            'es_base' => false,
            'activo' => true,
        ];
    }

    public function base(): static
    {
        return $this->state(fn () => [
            'codigo' => 'USD',
            'nombre' => 'Dólar Americano',
            'simbolo' => '$',
            'es_base' => true,
        ]);
    }
}
