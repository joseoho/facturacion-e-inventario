<?php

namespace Database\Factories;

use App\Models\CategoriaEgreso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaEgreso>
 */
class CategoriaEgresoFactory extends Factory
{
    protected $model = CategoriaEgreso::class;

    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion' => fake()->sentence(),
            'afecta_inventario' => false,
            'activo' => true,
        ];
    }

    public function conInventario(): static
    {
        return $this->state(fn () => ['afecta_inventario' => true]);
    }
}
