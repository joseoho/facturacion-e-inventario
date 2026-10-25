<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_la_pantalla_de_cambio_de_contrasena(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee($user->email);
    }

    public function test_usuario_cambia_su_contrasena(): void
    {
        $user = User::factory()->create(['password' => 'Actual123']);

        $this->actingAs($user)
            ->put(route('perfil.password'), [
                'current_password' => 'Actual123',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('Nueva1234', $user->refresh()->password));
    }

    public function test_rechaza_contrasena_actual_incorrecta(): void
    {
        $user = User::factory()->create(['password' => 'Actual123']);

        $this->actingAs($user)
            ->put(route('perfil.password'), [
                'current_password' => 'Incorrecta',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Actual123', $user->refresh()->password));
    }

    public function test_rechaza_si_la_confirmacion_no_coincide(): void
    {
        $user = User::factory()->create(['password' => 'Actual123']);

        $this->actingAs($user)
            ->put(route('perfil.password'), [
                'current_password' => 'Actual123',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Otra12345',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_invitado_no_accede_al_perfil(): void
    {
        $this->get(route('perfil.edit'))->assertRedirect(route('login'));
    }
}
