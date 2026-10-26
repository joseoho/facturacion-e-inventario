<?php

namespace Database\Seeders;

use App\Models\CategoriaEgreso;
use Illuminate\Database\Seeder;

class CategoriaEgresoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Compra de mercancía', 'descripcion' => 'Compra de productos para inventario', 'afecta_inventario' => true],
            ['nombre' => 'Servicios', 'descripcion' => 'Luz, agua, internet, teléfono', 'afecta_inventario' => false],
            ['nombre' => 'Sueldos', 'descripcion' => 'Pago de nómina y salarios', 'afecta_inventario' => false],
            ['nombre' => 'Alquiler', 'descripcion' => 'Alquiler de local u oficina', 'afecta_inventario' => false],
            ['nombre' => 'Impuestos', 'descripcion' => 'Pagos de impuestos y tasas', 'afecta_inventario' => false],
            ['nombre' => 'Mantenimiento', 'descripcion' => 'Reparaciones y mantenimiento', 'afecta_inventario' => false],
            ['nombre' => 'Fletes', 'descripcion' => 'Transporte y envíos', 'afecta_inventario' => false],
            ['nombre' => 'Otros', 'descripcion' => 'Otros egresos no clasificados', 'afecta_inventario' => false],
        ];

        foreach ($categorias as $categoria) {
            CategoriaEgreso::firstOrCreate(
                ['nombre' => $categoria['nombre']],
                [
                    'descripcion' => $categoria['descripcion'],
                    'afecta_inventario' => $categoria['afecta_inventario'],
                    'activo' => true,
                ]
            );
        }
    }
}
