<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz está protegida: un invitado debe ir al login.
     */
    public function test_la_raiz_redirige_al_login_para_invitados(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
