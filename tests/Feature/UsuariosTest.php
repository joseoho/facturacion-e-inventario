<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ve_el_listado_de_usuarios(): void
    {
        $admin = User::factory()->admin()->create();
        $vendedor = User::factory()->create(['name' => 'Vendedor Demo']);

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee($admin->email)
            ->assertSee('Vendedor Demo')
            ->assertSee('Nuevo Usuario');
    }

    public function test_vendedor_no_accede_al_modulo(): void
    {
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)
            ->get(route('usuarios.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($vendedor)
            ->get(route('usuarios.create'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_ve_los_formularios_de_alta_y_edicion(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('usuarios.create'))
            ->assertOk()
            ->assertSee('Nuevo Usuario');

        $this->actingAs($admin)
            ->get(route('usuarios.edit', $otro))
            ->assertOk()
            ->assertSee($otro->email);
    }

    public function test_admin_crea_un_vendedor_con_contrasena_cifrada(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'María Vendedor',
                'email' => 'maria@example.com',
                'role' => User::ROLE_VENDEDOR,
                'password' => 'Vende1234',
                'password_confirmation' => 'Vende1234',
                'activo' => 1,
            ])
            ->assertRedirect(route('usuarios.index'));

        $nuevo = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_VENDEDOR, $nuevo->role);
        $this->assertTrue($nuevo->activo);
        $this->assertTrue(Hash::check('Vende1234', $nuevo->password));
    }

    public function test_no_permite_correo_duplicado(): void
    {
        $admin = User::factory()->admin()->create();
        $existente = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'Otro',
                'email' => $existente->email,
                'role' => User::ROLE_VENDEDOR,
                'password' => 'Vende1234',
                'password_confirmation' => 'Vende1234',
                'activo' => 1,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_cambia_rol_y_estado(): void
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $objetivo), [
                'name' => $objetivo->name,
                'email' => $objetivo->email,
                'role' => User::ROLE_ADMIN,
                'activo' => 1,
            ])
            ->assertRedirect(route('usuarios.index'));

        $objetivo->refresh();

        $this->assertSame(User::ROLE_ADMIN, $objetivo->role);
        $this->assertTrue($objetivo->activo);
    }

    public function test_admin_resetea_la_contrasena(): void
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $objetivo), [
                'name' => $objetivo->name,
                'email' => $objetivo->email,
                'role' => $objetivo->role,
                'activo' => 1,
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertTrue(Hash::check('Nueva1234', $objetivo->refresh()->password));
    }

    public function test_no_puede_desactivar_su_propia_cuenta(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => User::ROLE_ADMIN,
                'activo' => 0,
            ])
            ->assertSessionHas('error');

        $this->assertTrue($admin->refresh()->activo);
    }

    public function test_no_puede_quitarse_el_rol_de_administrador(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => User::ROLE_VENDEDOR,
                'activo' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $admin->refresh()->role);
    }

    public function test_toggle_activa_y_desactiva_a_otro_usuario(): void
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->create(['activo' => true]);

        $this->actingAs($admin)
            ->post(route('usuarios.toggle', $objetivo))
            ->assertSessionHas('success');

        $this->assertFalse($objetivo->refresh()->activo);

        $this->actingAs($admin)
            ->post(route('usuarios.toggle', $objetivo))
            ->assertSessionHas('success');

        $this->assertTrue($objetivo->refresh()->activo);
    }

    public function test_usuario_desactivado_pierde_la_sesion_activa(): void
    {
        $vendedor = User::factory()->create(['activo' => true]);

        $this->actingAs($vendedor)->get(route('dashboard'))->assertOk();

        $vendedor->update(['activo' => false]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
