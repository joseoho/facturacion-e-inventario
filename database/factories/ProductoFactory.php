<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion' => fake()->sentence(),
            'precio_kg_usd' => 10,
            'stock_kg' => 100,
            'stock_minimo' => 5,
            'categoria_id' => Categoria::factory(),
            'iva_porcentaje' => 16,
            'activo' => true,
        ];
    }
}
