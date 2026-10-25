<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturacionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function monedaBase(): Moneda
    {
        return Moneda::factory()->base()->create();
    }

    public function test_crea_factura_en_usd_descuenta_stock_y_calcula_impuesto(): void
    {
        $admin = $this->admin();
        $moneda = $this->monedaBase();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create([
            'precio_kg_usd' => 10,
            'stock_kg' => 10,
            'iva_porcentaje' => 16,
        ]);

        $response = $this->actingAs($admin)->postJson(route('facturas.store'), [
            'cliente_id' => $cliente->id,
            'moneda_id' => $moneda->id,
            'moneda_pago' => 'USD',
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad_kg' => 2, 'precio_kg' => 10],
            ],
        ]);

        $response->assertCreated()->assertJson(['success' => true]);

        $factura = Factura::firstOrFail();
        $linea = FacturaLinea::firstOrFail();

        $this->assertSame('USD', $factura->moneda_pago);
        $this->assertEquals(20, (float) $linea->neto);
        $this->assertEquals(3.2, (float) $linea->impuesto_monto);
        $this->assertEquals(23.2, (float) $factura->total);
        $this->assertEquals(8, (float) $producto->refresh()->stock_kg);
        $this->assertSame(Factura::ESTADO_PENDIENTE, $factura->estado);
    }

    public function test_factura_en_bolivares_aplica_la_tasa_vigente(): void
    {
        $admin = $this->admin();
        $moneda = Moneda::factory()->create(['codigo' => 'BS']);
        $tasa = TasaCambio::factory()->create([
            'moneda_id' => $moneda->id,
            'tasa' => 500,
            'fecha' => now()->toDateString(),
        ]);
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create([
            'precio_kg_usd' => 10,
            'stock_kg' => 10,
            'iva_porcentaje' => 0,
        ]);

        $response = $this->actingAs($admin)->postJson(route('facturas.store'), [
            'cliente_id' => $cliente->id,
            'moneda_id' => $moneda->id,
            'moneda_pago' => 'BS',
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad_kg' => 1, 'precio_kg' => 5000],
            ],
        ]);

        $response->assertCreated();

        $factura = Factura::firstOrFail();

        $this->assertEquals(5000, (float) $factura->lineas()->first()->precio_kg);
        $this->assertEquals($tasa->id, $factura->tasa_cambio_id);
        $this->assertEquals(500, (float) $factura->tasa_cambio_usada);
        $this->assertEquals(5000, (float) $factura->total);
    }

    public function test_rechaza_factura_si_no_hay_stock_y_no_deja_residuos(): void
    {
        $admin = $this->admin();
        $moneda = $this->monedaBase();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock_kg' => 1]);

        $response = $this->actingAs($admin)->postJson(route('facturas.store'), [
            'cliente_id' => $cliente->id,
            'moneda_id' => $moneda->id,
            'moneda_pago' => 'USD',
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad_kg' => 5, 'precio_kg' => 10],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame(0, Factura::count());
        $this->assertSame(0, FacturaLinea::count());
        $this->assertEquals(1, (float) $producto->refresh()->stock_kg);
    }

    public function test_impone_el_precio_del_backend_si_el_frontend_lo_manipula(): void
    {
        $admin = $this->admin();
        $moneda = $this->monedaBase();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create([
            'precio_kg_usd' => 10,
            'stock_kg' => 10,
            'iva_porcentaje' => 0,
        ]);

        $this->actingAs($admin)->postJson(route('facturas.store'), [
            'cliente_id' => $cliente->id,
            'moneda_id' => $moneda->id,
            'moneda_pago' => 'USD',
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad_kg' => 1, 'precio_kg' => 0.01],
            ],
        ])->assertCreated();

        $this->assertEquals(10, (float) FacturaLinea::firstOrFail()->precio_kg);
    }

    public function test_rechaza_factura_si_la_moneda_no_tiene_tasa(): void
    {
        $admin = $this->admin();
        $moneda = Moneda::factory()->create(['codigo' => 'COP']);
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create();

        $this->actingAs($admin)->postJson(route('facturas.store'), [
            'cliente_id' => $cliente->id,
            'moneda_id' => $moneda->id,
            'moneda_pago' => 'COP',
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad_kg' => 1, 'precio_kg' => 1],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Factura::count());
    }

    public function test_genera_numeros_consecutivos(): void
    {
        $admin = $this->admin();
        $moneda = $this->monedaBase();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock_kg' => 100]);

        foreach ([1, 2] as $cantidad) {
            $this->actingAs($admin)->postJson(route('facturas.store'), [
                'cliente_id' => $cliente->id,
                'moneda_id' => $moneda->id,
                'moneda_pago' => 'USD',
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad_kg' => $cantidad, 'precio_kg' => 10],
                ],
            ])->assertCreated();
        }

        $this->assertSame(['FACT-00000001', 'FACT-00000002'], Factura::orderBy('id')->pluck('numero')->all());
    }

    public function test_muestra_e_imprime_la_factura(): void
    {
        $admin = $this->admin();
        $factura = Factura::factory()->create();

        $this->actingAs($admin)
            ->get(route('facturas.show', $factura))
            ->assertOk()
            ->assertSee($factura->numero);

        $this->actingAs($admin)
            ->get(route('facturas.imprimir', $factura))
            ->assertOk()
            ->assertSee($factura->numero);
    }
}
