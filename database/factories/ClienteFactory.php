<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'documento' => fake()->unique()->numerify('J-########'),
            'tipo_documento' => 'J',
            'direccion' => fake()->address(),
            'telefono' => fake()->numerify('0412-#######'),
            'email' => fake()->unique()->safeEmail(),
            'contacto' => fake()->name(),
            'tipo_cliente' => 'minorista',
            'limite_credito' => 0,
            'dias_credito' => 0,
            'activo' => true,
        ];
    }
}
