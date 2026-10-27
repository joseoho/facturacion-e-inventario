<?php

namespace Tests\Feature;

use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_rechaza_precio_duplicado_por_moneda(): void
    {
        $admin = User::factory()->admin()->create();
        $producto = Producto::factory()->create();
        $moneda = Moneda::factory()->create();
        $tasa = TasaCambio::factory()->create(['moneda_id' => $moneda->id]);

        $payload = [
            'moneda_id' => $moneda->id,
            'tasa_cambio_id' => $tasa->id,
            'precio_kg' => 10,
        ];

        $this->actingAs($admin)
            ->post(route('productos.precios.store', $producto), $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('productos.precios.store', $producto), $payload)
            ->assertSessionHasErrors('moneda_id');
    }

    public function test_rechaza_tasa_que_no_pertenece_a_la_moneda(): void
    {
        $admin = User::factory()->admin()->create();
        $producto = Producto::factory()->create();

        $moneda = Moneda::factory()->create();
        $otraMoneda = Moneda::factory()->create();
        $tasaDeOtra = TasaCambio::factory()->create(['moneda_id' => $otraMoneda->id]);

        $this->actingAs($admin)
            ->post(route('productos.precios.store', $producto), [
                'moneda_id' => $moneda->id,
                'tasa_cambio_id' => $tasaDeOtra->id,
                'precio_kg' => 10,
            ])
            ->assertSessionHasErrors('tasa_cambio_id');
    }
}
