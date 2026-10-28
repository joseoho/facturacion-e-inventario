<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('facturas.index'))->assertRedirect(route('login'));
        $this->get(route('egresos.index'))->assertRedirect(route('login'));
    }

    public function test_vendedor_no_accede_a_egresos(): void
    {
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)
            ->get(route('egresos.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($vendedor)
            ->get(route('egresos.create'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_vendedor_no_accede_a_monedas_ni_tasas(): void
    {
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)->get(route('monedas.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($vendedor)->get(route('tasas.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($vendedor)->get(route('reportes.inventario'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_accede_a_los_modulos_de_administracion(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('egresos.index'))->assertOk();
        $this->actingAs($admin)->get(route('egresos.create'))->assertOk();
        $this->actingAs($admin)->get(route('categorias-egreso.index'))->assertOk();
        $this->actingAs($admin)->get(route('monedas.index'))->assertOk();
        $this->actingAs($admin)->get(route('tasas.index'))->assertOk();
        $this->actingAs($admin)->get(route('tasas.historial'))->assertOk();
    }

    public function test_el_dashboard_muestra_finanzas_solo_al_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $vendedor = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Utilidad del Mes')
            ->assertSee('Ventas vs Egresos');

        $this->actingAs($vendedor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Utilidad del Mes');
    }

    public function test_el_registro_publico_esta_deshabilitado(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_las_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
