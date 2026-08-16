<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Lácteos',
                'descripcion' => 'Productos lácteos: leche, queso, yogurt, mantequilla y derivados',
                'activo' => true
            ],
            [
                'nombre' => 'Carnes',
                'descripcion' => 'Carnes frescas y procesadas: res, cerdo, pollo, pescado',
                'activo' => true
            ],
            [
                'nombre' => 'Verduras',
                'descripcion' => 'Verduras y hortalizas frescas de temporada',
                'activo' => true
            ],
            [
                'nombre' => 'Frutas',
                'descripcion' => 'Frutas frescas de temporada y exóticas',
                'activo' => true
            ],
            [
                'nombre' => 'Granos y Cereales',
                'descripcion' => 'Arroz, maíz, frijoles, lentejas, cereales',
                'activo' => true
            ],
            [
                'nombre' => 'Embutidos',
                'descripcion' => 'Jamón, salchichas, chorizos, mortadela',
                'activo' => true
            ],
            [
                'nombre' => 'Panadería',
                'descripcion' => 'Pan, pasteles, galletas, repostería',
                'activo' => true
            ],
            [
                'nombre' => 'Bebidas',
                'descripcion' => 'Bebidas gaseosas, jugos, agua, refrescos, cervezas',
                'activo' => true
            ],
            [
                'nombre' => 'Aseo y Limpieza',
                'descripcion' => 'Productos de limpieza, detergentes, desinfectantes',
                'activo' => true
            ],
            [
                'nombre' => 'Congelados',
                'descripcion' => 'Alimentos congelados: vegetales, carnes, preparados',
                'activo' => true
            ],
            [
                'nombre' => 'Abarrotes',
                'descripcion' => 'Productos enlatados, conservas, salsas, aceites',
                'activo' => true
            ],
            [
                'nombre' => 'Higiene Personal',
                'descripcion' => 'Jabones, champús, cremas, desodorantes',
                'activo' => true
            ],
            [
                'nombre' => 'Bebés',
                'descripcion' => 'Pañales, leche, alimentos para bebés',
                'activo' => true
            ],
            [
                'nombre' => 'Mascotas',
                'descripcion' => 'Alimento y accesorios para mascotas',
                'activo' => true
            ],
            [
                'nombre' => 'Electrónica',
                'descripcion' => 'Dispositivos electrónicos, accesorios, cables',
                'activo' => true
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}