<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CategoriaEgreso;
use App\Models\Cliente;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\User;
use Database\Seeders\InstalacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstalacionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_instalacion_seeder_crea_los_datos_base_y_es_idempotente(): void
    {
        $this->seed(InstalacionSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@facturador.com', 'role' => 'admin']);

        $conteos = [
            'users' => User::count(),
            'monedas' => Moneda::count(),
            'categorias' => Categoria::count(),
            'productos' => Producto::count(),
            'clientes' => Cliente::count(),
            'tasas' => TasaCambio::count(),
            'categorias_egreso' => CategoriaEgreso::count(),
        ];

        $this->assertGreaterThan(0, $conteos['productos']);
        $this->assertGreaterThan(0, $conteos['clientes']);
        $this->assertGreaterThan(0, $conteos['monedas']);
        $this->assertGreaterThan(0, $conteos['categorias_egreso']);

        // Ejecutar otra vez no debe duplicar ni fallar.
        $this->seed(InstalacionSeeder::class);

        $this->assertSame($conteos, [
            'users' => User::count(),
            'monedas' => Moneda::count(),
            'categorias' => Categoria::count(),
            'productos' => Producto::count(),
            'clientes' => Cliente::count(),
            'tasas' => TasaCambio::count(),
            'categorias_egreso' => CategoriaEgreso::count(),
        ]);
    }
}
