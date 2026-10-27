<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_activo_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'email' => 'activo@example.com',
            'password' => bcrypt('secret123'),
            'activo' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->inactivo()->create([
            'email' => 'inactivo@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertGuest();
    }

    public function test_bloquea_intentos_de_login_repetidos(): void
    {
        $user = User::factory()->create([
            'email' => 'bruteforce@example.com',
            'password' => 'secret123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'incorrecta',
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }
}
