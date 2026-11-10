<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteVentasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function factura(array $datos = []): Factura
    {
        return Factura::factory()->create(array_merge([
            'estado' => Factura::ESTADO_PAGADA,
            'fecha_emision' => '2026-01-10',
            'moneda_pago' => 'USD',
            'tasa_cambio_usada' => null,
            'total' => 100,
        ], $datos));
    }

    public function test_filtra_por_rango_y_totaliza_por_moneda_sin_mezclar(): void
    {
        $admin = $this->admin();
        $usd = Moneda::factory()->base()->create();
        $bs = Moneda::factory()->create(['codigo' => 'BS']);

        $this->factura([
            'fecha_emision' => '2026-01-10',
            'moneda_id' => $usd->id,
            'total' => 100,
        ]);

        $this->factura([
            'fecha_emision' => '2026-01-11',
            'moneda_id' => $bs->id,
            'moneda_pago' => 'BS',
            'tasa_cambio_usada' => 400,
            'total' => 40000,
        ]);

        // Fuera del rango.
        $this->factura([
            'fecha_emision' => '2026-01-12',
            'moneda_id' => $usd->id,
            'total' => 999,
        ]);

        // Pendiente: no debe sumar.
        $this->factura([
            'fecha_emision' => '2026-01-10',
            'moneda_id' => $usd->id,
            'estado' => Factura::ESTADO_PENDIENTE,
            'total' => 500,
        ]);

        $response = $this->actingAs($admin)->get(route('reportes.ventas.diarias', [
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => '2026-01-11',
        ]));

        $response->assertOk();

        $response->assertViewHas('totales', function (array $totales) {
            return (float) $totales['total_usd'] === 200.0
                && $totales['total_facturas'] === 2
                && (float) $totales['promedio_usd'] === 100.0;
        });

        $response->assertViewHas('totales', function (array $totales) {
            $porMoneda = collect($totales['por_moneda'])->keyBy('moneda');

            return (float) $porMoneda['USD']['total'] === 100.0
                && (float) $porMoneda['USD']['total_usd'] === 100.0
                && (float) $porMoneda['BS']['total'] === 40000.0
                && (float) $porMoneda['BS']['total_usd'] === 100.0;
        });

        $response->assertViewHas('porPeriodo', fn ($periodos) => $periodos->count() === 2);
    }

    public function test_filtra_por_cliente(): void
    {
        $admin = $this->admin();
        $usd = Moneda::factory()->base()->create();

        $clienteA = Cliente::factory()->create(['nombre' => 'Cliente Alfa']);
        $clienteB = Cliente::factory()->create(['nombre' => 'Cliente Beta']);

        $this->factura([
            'fecha_emision' => '2026-01-10',
            'cliente_id' => $clienteA->id,
            'moneda_id' => $usd->id,
            'total' => 100,
        ]);

        $this->factura([
            'fecha_emision' => '2026-01-10',
            'cliente_id' => $clienteB->id,
            'moneda_id' => $usd->id,
            'total' => 300,
        ]);

        $response = $this->actingAs($admin)->get(route('reportes.ventas.diarias', [
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-31',
            'cliente_id' => $clienteA->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('totales', function (array $totales) {
            return $totales['total_facturas'] === 1
                && (float) $totales['total_usd'] === 100.0
                && $totales['clientes_count'] === 1;
        });
        $response->assertViewHas('porCliente', function ($clientes) {
            return $clientes->count() === 1
                && $clientes->first()['cliente'] === 'Cliente Alfa';
        });
    }

    public function test_agrupa_por_semana_y_mes(): void
    {
        $admin = $this->admin();
        $usd = Moneda::factory()->base()->create();
        $cliente = Cliente::factory()->create();

        foreach (['2026-01-05', '2026-01-07', '2026-02-03'] as $fecha) {
            $this->factura([
                'fecha_emision' => $fecha,
                'cliente_id' => $cliente->id,
                'moneda_id' => $usd->id,
                'total' => 100,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('reportes.ventas.diarias', [
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-02-28',
            'agrupar' => 'semana',
        ]));

        $response->assertOk();
        $response->assertViewHas('agrupar', 'semana');
        $response->assertViewHas('porPeriodo', fn ($periodos) => $periodos->count() === 2);

        $response = $this->actingAs($admin)->get(route('reportes.ventas.diarias', [
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-02-28',
            'agrupar' => 'mes',
        ]));

        $response->assertOk();
        $response->assertViewHas('agrupar', 'mes');
        $response->assertViewHas('porPeriodo', fn ($periodos) => $periodos->count() === 2);
    }

    public function test_rechaza_rango_con_fecha_final_anterior(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.ventas.diarias', [
                'fecha_inicio' => '2026-01-12',
                'fecha_fin' => '2026-01-10',
            ]))
            ->assertSessionHasErrors('fecha_fin');
    }

    public function test_mantiene_compatibilidad_con_el_parametro_fecha(): void
    {
        $this->factura([
            'fecha_emision' => '2026-01-10',
            'total' => 100,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('reportes.ventas.diarias', ['fecha' => '2026-01-10']));

        $response->assertOk();
        $response->assertViewHas('inicio', fn ($inicio) => $inicio->toDateString() === '2026-01-10');
        $response->assertViewHas('fin', fn ($fin) => $fin->toDateString() === '2026-01-10');
    }

    public function test_muestra_mensaje_cuando_no_hay_ventas(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.ventas.diarias', [
                'fecha_inicio' => '2025-01-01',
                'fecha_fin' => '2025-01-31',
            ]))
            ->assertOk()
            ->assertSee('No hay facturas pagadas en este rango de fechas.');
    }

    public function test_usa_la_tasa_historica_si_la_factura_no_la_guardo(): void
    {
        $bs = Moneda::factory()->create(['codigo' => 'BS']);

        TasaCambio::factory()->create([
            'moneda_id' => $bs->id,
            'tasa' => 800,
            'fecha' => '2026-01-10',
        ]);

        $factura = $this->factura([
            'fecha_emision' => '2026-01-10',
            'moneda_id' => $bs->id,
            'moneda_pago' => 'BS',
            'tasa_cambio_id' => null,
            'tasa_cambio_usada' => null,
            'total' => 800,
        ]);

        $this->assertEquals(1.0, $factura->fresh()->total_usd_calculado);
    }

    public function test_factura_usd_no_se_divide_aunque_tenga_una_tasa_vieja(): void
    {
        $usd = Moneda::factory()->base()->create();

        $factura = $this->factura([
            'fecha_emision' => '2026-01-10',
            'moneda_id' => $usd->id,
            'moneda_pago' => 'USD',
            'tasa_cambio_usada' => 800,
            'total' => 100,
        ]);

        $this->assertEquals(100.0, $factura->fresh()->total_usd_calculado);
    }
}
