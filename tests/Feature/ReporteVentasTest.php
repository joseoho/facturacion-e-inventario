<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\Moneda;
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

        $response->assertViewHas('porDia', fn ($dias) => $dias->count() === 2);
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
}
