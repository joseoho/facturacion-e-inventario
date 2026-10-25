<?php

namespace Tests\Feature;

use App\Models\Moneda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonedaTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_moneda_inactiva_respetando_el_flag(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('monedas.store'), [
            'codigo' => 'eur',
            'nombre' => 'Euro',
            'simbolo' => '€',
            'es_base' => 0,
            'activo' => 0,
        ])->assertRedirect(route('monedas.index'));

        $this->assertDatabaseHas('monedas', [
            'codigo' => 'EUR',
            'activo' => 0,
            'es_base' => 0,
        ]);
    }

    public function test_filtro_por_estado_no_rompe(): void
    {
        $admin = User::factory()->admin()->create();
        Moneda::factory()->create(['activo' => false]);

        $this->actingAs($admin)
            ->get(route('monedas.index', ['activo' => 0]))
            ->assertOk();
    }

    public function test_editar_moneda_muestra_su_estado_real(): void
    {
        $admin = User::factory()->admin()->create();
        $moneda = Moneda::factory()->create(['activo' => true]);

        $this->actingAs($admin)
            ->get(route('monedas.edit', $moneda))
            ->assertOk();
    }

    public function test_no_permite_desactivar_la_unica_moneda_base(): void
    {
        $admin = User::factory()->admin()->create();
        $base = Moneda::factory()->base()->create(['activo' => true]);

        $this->actingAs($admin)->put(route('monedas.update', $base), [
            'codigo' => $base->codigo,
            'nombre' => $base->nombre,
            'simbolo' => $base->simbolo,
            'es_base' => 0,
            'activo' => 1,
        ])->assertSessionHasErrors('es_base');
    }
}
