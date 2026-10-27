<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_crea_cliente_con_limite_y_dias_de_credito_vacios(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), [
                'nombre' => 'Cliente de Prueba',
                'documento' => 'J-99999999-9',
                'tipo_documento' => 'J',
                'tipo_cliente' => 'minorista',
                'limite_credito' => '',
                'dias_credito' => '',
            ])
            ->assertRedirect(route('clientes.index'));

        $cliente = Cliente::firstOrFail();

        $this->assertEquals(0, (float) $cliente->limite_credito);
        $this->assertSame(0, $cliente->dias_credito);
    }

    public function test_actualiza_cliente_con_credito_vacio(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create([
            'limite_credito' => 500,
            'dias_credito' => 30,
        ]);

        $this->actingAs($admin)
            ->put(route('clientes.update', $cliente), [
                'nombre' => $cliente->nombre,
                'documento' => $cliente->documento,
                'tipo_documento' => 'J',
                'tipo_cliente' => 'minorista',
                'limite_credito' => '',
                'dias_credito' => '',
            ])
            ->assertRedirect(route('clientes.index'));

        $cliente->refresh();

        $this->assertEquals(0, (float) $cliente->limite_credito);
        $this->assertSame(0, $cliente->dias_credito);
    }

    public function test_rechaza_nombre_mas_largo_que_la_columna(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), [
                'nombre' => str_repeat('A', 201),
                'documento' => 'J-88888888-8',
                'tipo_documento' => 'J',
                'tipo_cliente' => 'minorista',
            ])
            ->assertSessionHasErrors('nombre');
    }

    public function test_vendedor_no_puede_crear_clientes(): void
    {
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)
            ->post(route('clientes.store'), [
                'nombre' => 'Cliente',
                'documento' => 'J-77777777-7',
                'tipo_documento' => 'J',
                'tipo_cliente' => 'minorista',
            ])
            ->assertForbidden();
    }
}
