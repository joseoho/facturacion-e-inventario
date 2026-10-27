<?php

namespace Tests\Feature;

use App\Models\CategoriaEgreso;
use App\Models\Egreso;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EgresoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{moneda: Moneda, categoria: CategoriaEgreso, producto: Producto, payload: array}
     */
    private function escenario(bool $inventario = true): array
    {
        $moneda = Moneda::factory()->base()->create();
        $categoria = CategoriaEgreso::factory()->create(['afecta_inventario' => $inventario]);
        $producto = Producto::factory()->create(['stock_kg' => 5]);

        $payload = [
            'categoria_egreso_id' => $categoria->id,
            'proveedor_nombre' => 'Distribuidora XYZ',
            'moneda_id' => $moneda->id,
            'monto' => 100,
            'fecha_egreso' => now()->toDateString(),
            'estado' => Egreso::ESTADO_PENDIENTE,
            'metodo_pago' => 'transferencia',
            'referencia_pago' => 'TRF-1',
            'concepto' => 'Compra de 20 kg de café',
            'producto_id' => $inventario ? $producto->id : null,
            'cantidad_kg' => $inventario ? 2 : null,
        ];

        return compact('moneda', 'categoria', 'producto', 'payload');
    }

    public function test_crea_egreso_con_inventario_y_numero_correlativo(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $this->actingAs($admin)
            ->post(route('egresos.store'), $datos['payload'])
            ->assertRedirect();

        $egreso = Egreso::firstOrFail();

        $this->assertSame('EGR-00000001', $egreso->numero);
        $this->assertSame('USD', $egreso->moneda_pago);
        $this->assertEquals(100, (float) $egreso->monto);
        $this->assertSame(Egreso::ESTADO_PENDIENTE, $egreso->estado);
        $this->assertNull($egreso->fecha_pago);
        $this->assertEquals(7, (float) $datos['producto']->refresh()->stock_kg);
        $this->assertSame('Distribuidora XYZ', $egreso->proveedor_nombre);
    }

    public function test_crea_egreso_pagado_y_guarda_fecha_de_pago(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();
        $datos['payload']['estado'] = Egreso::ESTADO_PAGADO;

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload'])->assertRedirect();

        $egreso = Egreso::firstOrFail();

        $this->assertSame(Egreso::ESTADO_PAGADO, $egreso->estado);
        $this->assertNotNull($egreso->fecha_pago);
    }

    public function test_categoria_sin_inventario_no_mueve_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario(inventario: false);

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload'])->assertRedirect();

        $egreso = Egreso::firstOrFail();

        $this->assertNull($egreso->producto_id);
        $this->assertNull($egreso->cantidad_kg);
        $this->assertEquals(5, (float) $datos['producto']->refresh()->stock_kg);
    }

    public function test_categoria_con_inventario_exige_producto_y_cantidad(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();
        $datos['payload']['producto_id'] = null;
        $datos['payload']['cantidad_kg'] = null;

        $this->actingAs($admin)
            ->post(route('egresos.store'), $datos['payload'])
            ->assertSessionHasErrors(['producto_id', 'cantidad_kg']);

        $this->assertSame(0, Egreso::count());
        $this->assertEquals(5, (float) $datos['producto']->refresh()->stock_kg);
    }

    public function test_rechaza_monto_cero(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $datos['payload']['monto'] = 0;

        $this->actingAs($admin)
            ->post(route('egresos.store'), $datos['payload'])
            ->assertSessionHasErrors('monto');

        $this->assertSame(0, Egreso::count());
    }

    public function test_anular_revierte_el_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload']);
        $egreso = Egreso::firstOrFail();

        $this->actingAs($admin)
            ->post(route('egresos.anular', $egreso))
            ->assertSessionHas('success');

        $this->assertSame(Egreso::ESTADO_ANULADO, $egreso->refresh()->estado);
        $this->assertEquals(5, (float) $datos['producto']->refresh()->stock_kg);
    }

    public function test_no_anula_si_el_stock_ya_fue_vendido(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload']);
        $egreso = Egreso::firstOrFail();

        // Simulamos que se vendió casi todo: quedan 1 Kg de los 2 ingresados.
        $datos['producto']->update(['stock_kg' => 1]);

        $this->actingAs($admin)
            ->post(route('egresos.anular', $egreso))
            ->assertSessionHas('error');

        $this->assertSame(Egreso::ESTADO_PENDIENTE, $egreso->refresh()->estado);
        $this->assertEquals(1, (float) $datos['producto']->refresh()->stock_kg);
    }

    public function test_pagar_guarda_metodo_y_fecha(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload']);
        $egreso = Egreso::firstOrFail();

        $this->actingAs($admin)
            ->post(route('egresos.pagar', $egreso), ['metodo_pago' => 'zelle', 'referencia_pago' => 'Z-1'])
            ->assertSessionHas('success');

        $egreso->refresh();

        $this->assertSame(Egreso::ESTADO_PAGADO, $egreso->estado);
        $this->assertSame('zelle', $egreso->metodo_pago);
        $this->assertNotNull($egreso->fecha_pago);
    }

    public function test_no_paga_un_egreso_anulado(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload']);
        $egreso = Egreso::firstOrFail();

        $this->actingAs($admin)->post(route('egresos.anular', $egreso));
        $this->actingAs($admin)->post(route('egresos.pagar', $egreso))->assertSessionHas('error');

        $this->assertSame(Egreso::ESTADO_ANULADO, $egreso->refresh()->estado);
    }

    public function test_moneda_extranjera_guarda_tasa_y_monto_usd(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario(inventario: false);

        $moneda = Moneda::factory()->create(['codigo' => 'BS']);
        TasaCambio::factory()->create([
            'moneda_id' => $moneda->id,
            'tasa' => 500,
            'fecha' => now()->toDateString(),
        ]);

        $datos['payload']['moneda_id'] = $moneda->id;

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload']);

        $egreso = Egreso::firstOrFail();

        $this->assertEquals(500, (float) $egreso->tasa_cambio_usada);
        $this->assertNotNull($egreso->tasa_cambio_id);
        $this->assertEquals(0.2, $egreso->monto_usd);
    }

    public function test_moneda_extranjera_sin_tasa_devuelve_error(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario(inventario: false);

        $moneda = Moneda::factory()->create(['codigo' => 'COP']);
        $datos['payload']['moneda_id'] = $moneda->id;

        $this->actingAs($admin)
            ->post(route('egresos.store'), $datos['payload'])
            ->assertSessionHas('error');

        $this->assertSame(0, Egreso::count());
    }

    public function test_muestra_el_detalle_y_el_listado(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario();

        $this->actingAs($admin)->post(route('egresos.store'), $datos['payload']);
        $egreso = Egreso::firstOrFail();

        $this->actingAs($admin)
            ->get(route('egresos.show', $egreso))
            ->assertOk()
            ->assertSee($egreso->numero)
            ->assertSee('Compra de 20 kg de café');

        $this->actingAs($admin)
            ->get(route('egresos.index'))
            ->assertOk()
            ->assertSee($egreso->numero);
    }

    public function test_vendedor_no_accede_a_egresos(): void
    {
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)
            ->get(route('egresos.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_rechaza_monto_mayor_al_soportado_por_la_columna(): void
    {
        $admin = User::factory()->admin()->create();
        $datos = $this->escenario(inventario: false);
        $datos['payload']['monto'] = 100000000;

        $this->actingAs($admin)
            ->postJson(route('egresos.store'), $datos['payload'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('monto');

        $this->assertSame(0, Egreso::count());
    }

    public function test_filtro_con_fecha_invalida_redirige_sin_500(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('egresos.index', ['desde' => 'no-es-fecha']))
            ->assertStatus(302);
    }
}
