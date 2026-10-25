<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnulacionPagoTest extends TestCase
{
    use RefreshDatabase;

    private function facturaConLinea(Producto $producto, float $cantidad = 2): Factura
    {
        $factura = Factura::factory()->create([
            'moneda_id' => Moneda::query()->where('es_base', true)->value('id'),
            'total' => 20,
            'subtotal_neto' => 20,
            'total_impuesto' => 0,
        ]);

        FacturaLinea::create([
            'factura_id' => $factura->id,
            'producto_id' => $producto->id,
            'cantidad_kg' => $cantidad,
            'precio_kg' => 10,
            'neto' => 20,
            'impuesto_porcentaje' => 0,
            'impuesto_monto' => 0,
            'total' => 20,
        ]);

        return $factura;
    }

    public function test_anular_restaura_el_stock(): void
    {
        $admin = User::factory()->admin()->create();
        Moneda::factory()->base()->create();
        $producto = Producto::factory()->create(['stock_kg' => 8]);
        $factura = $this->facturaConLinea($producto, 2);

        $this->actingAs($admin)
            ->postJson(route('facturas.anular', $factura))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(Factura::ESTADO_ANULADA, $factura->refresh()->estado);
        $this->assertEquals(10, (float) $producto->refresh()->stock_kg);
    }

    public function test_anular_dos_veces_devuelve_422_sin_tocar_stock(): void
    {
        $admin = User::factory()->admin()->create();
        Moneda::factory()->base()->create();
        $producto = Producto::factory()->create(['stock_kg' => 8]);
        $factura = $this->facturaConLinea($producto, 2);

        $this->actingAs($admin)->postJson(route('facturas.anular', $factura))->assertOk();
        $this->actingAs($admin)->postJson(route('facturas.anular', $factura))->assertStatus(422);

        $this->assertEquals(10, (float) $producto->refresh()->stock_kg);
    }

    public function test_vendedor_no_puede_anular(): void
    {
        $vendedor = User::factory()->create();
        Moneda::factory()->base()->create();
        $producto = Producto::factory()->create(['stock_kg' => 8]);
        $factura = $this->facturaConLinea($producto, 2);

        $this->actingAs($vendedor)
            ->postJson(route('facturas.anular', $factura))
            ->assertForbidden();

        $this->assertSame(Factura::ESTADO_PENDIENTE, $factura->refresh()->estado);
        $this->assertEquals(8, (float) $producto->refresh()->stock_kg);
    }

    public function test_pagar_guarda_estado_y_fecha_de_pago(): void
    {
        $admin = User::factory()->admin()->create();
        Moneda::factory()->base()->create();
        $producto = Producto::factory()->create(['stock_kg' => 8]);
        $factura = $this->facturaConLinea($producto, 2);

        $this->actingAs($admin)
            ->postJson(route('facturas.pagar', $factura))
            ->assertOk()
            ->assertJson(['success' => true]);

        $factura->refresh();

        $this->assertSame(Factura::ESTADO_PAGADA, $factura->estado);
        $this->assertNotNull($factura->fecha_pago);
        $this->assertSame(now()->toDateString(), $factura->fecha_pago->toDateString());
    }

    public function test_no_se_puede_pagar_una_factura_anulada(): void
    {
        $admin = User::factory()->admin()->create();
        Moneda::factory()->base()->create();
        $producto = Producto::factory()->create(['stock_kg' => 8]);
        $factura = $this->facturaConLinea($producto, 2);

        $this->actingAs($admin)->postJson(route('facturas.anular', $factura))->assertOk();
        $this->actingAs($admin)->postJson(route('facturas.pagar', $factura))->assertStatus(422);
    }
}
