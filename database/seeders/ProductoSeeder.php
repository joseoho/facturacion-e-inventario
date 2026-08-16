<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener categorías
        $lacteos = Categoria::where('nombre', 'Lácteos')->first();
        $carnes = Categoria::where('nombre', 'Carnes')->first();
        $verduras = Categoria::where('nombre', 'Verduras')->first();
        $frutas = Categoria::where('nombre', 'Frutas')->first();
        $granos = Categoria::where('nombre', 'Granos y Cereales')->first();
        $embutidos = Categoria::where('nombre', 'Embutidos')->first();
        $panaderia = Categoria::where('nombre', 'Panadería')->first();
        $bebidas = Categoria::where('nombre', 'Bebidas')->first();
        $aseo = Categoria::where('nombre', 'Aseo y Limpieza')->first();
        $congelados = Categoria::where('nombre', 'Congelados')->first();

        $productos = [
            // Lácteos
            [
                'sku' => 'LAC-001',
                'nombre' => 'Queso Blanco Fresco',
                'descripcion' => 'Queso blanco pasteurizado, ideal para desayunos',
                'precio_kg_usd' => 5.50,
                'stock_kg' => 25.500,
                'categoria_id' => $lacteos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 5.000
            ],
            [
                'sku' => 'LAC-002',
                'nombre' => 'Mantequilla Premium',
                'descripcion' => 'Mantequilla de leche pasteurizada, salada',
                'precio_kg_usd' => 8.75,
                'stock_kg' => 12.750,
                'categoria_id' => $lacteos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 3.000
            ],
            [
                'sku' => 'LAC-003',
                'nombre' => 'Yogurt Natural',
                'descripcion' => 'Yogurt natural sin azúcar, probióticos',
                'precio_kg_usd' => 4.25,
                'stock_kg' => 18.500,
                'categoria_id' => $lacteos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 4.000
            ],

            // Carnes
            [
                'sku' => 'CAR-001',
                'nombre' => 'Carne de Res Molida',
                'descripcion' => 'Carne de res molida fresca, 20% grasa',
                'precio_kg_usd' => 6.25,
                'stock_kg' => 30.000,
                'categoria_id' => $carnes->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 8.000
            ],
            [
                'sku' => 'CAR-002',
                'nombre' => 'Pechuga de Pollo',
                'descripcion' => 'Pechuga de pollo sin hueso, fileteada',
                'precio_kg_usd' => 5.75,
                'stock_kg' => 28.500,
                'categoria_id' => $carnes->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 6.000
            ],
            [
                'sku' => 'CAR-003',
                'nombre' => 'Lomo de Cerdo',
                'descripcion' => 'Lomo de cerdo premium, magro',
                'precio_kg_usd' => 7.50,
                'stock_kg' => 15.250,
                'categoria_id' => $carnes->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 4.000
            ],

            // Verduras
            [
                'sku' => 'VER-001',
                'nombre' => 'Tomate Criollo',
                'descripcion' => 'Tomate criollo fresco, ideal para ensaladas',
                'precio_kg_usd' => 2.25,
                'stock_kg' => 20.000,
                'categoria_id' => $verduras->id,
                'iva_porcentaje' => 0,
                'activo' => true,
                'stock_minimo' => 5.000
            ],
            [
                'sku' => 'VER-002',
                'nombre' => 'Cebolla Blanca',
                'descripcion' => 'Cebolla blanca grande, fresca',
                'precio_kg_usd' => 1.85,
                'stock_kg' => 35.000,
                'categoria_id' => $verduras->id,
                'iva_porcentaje' => 0,
                'activo' => true,
                'stock_minimo' => 8.000
            ],
            [
                'sku' => 'VER-003',
                'nombre' => 'Papa Criolla',
                'descripcion' => 'Papa criolla de primera calidad',
                'precio_kg_usd' => 1.95,
                'stock_kg' => 40.000,
                'categoria_id' => $verduras->id,
                'iva_porcentaje' => 0,
                'activo' => true,
                'stock_minimo' => 10.000
            ],

            // Frutas
            [
                'sku' => 'FRU-001',
                'nombre' => 'Banano',
                'descripcion' => 'Banano de exportación, maduro',
                'precio_kg_usd' => 1.50,
                'stock_kg' => 22.000,
                'categoria_id' => $frutas->id,
                'iva_porcentaje' => 0,
                'activo' => true,
                'stock_minimo' => 6.000
            ],
            [
                'sku' => 'FRU-002',
                'nombre' => 'Manzana Verde',
                'descripcion' => 'Manzana verde importada, crujiente',
                'precio_kg_usd' => 3.25,
                'stock_kg' => 12.500,
                'categoria_id' => $frutas->id,
                'iva_porcentaje' => 0,
                'activo' => true,
                'stock_minimo' => 3.000
            ],
            [
                'sku' => 'FRU-003',
                'nombre' => 'Naranja Dulce',
                'descripcion' => 'Naranja jugosa, ideal para jugos',
                'precio_kg_usd' => 1.75,
                'stock_kg' => 18.500,
                'categoria_id' => $frutas->id,
                'iva_porcentaje' => 0,
                'activo' => true,
                'stock_minimo' => 5.000
            ],

            // Granos y Cereales
            [
                'sku' => 'GRA-001',
                'nombre' => 'Arroz Premium',
                'descripcion' => 'Arroz blanco de grano largo, primera calidad',
                'precio_kg_usd' => 2.35,
                'stock_kg' => 50.000,
                'categoria_id' => $granos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 12.000
            ],
            [
                'sku' => 'GRA-002',
                'nombre' => 'Frijol Negro',
                'descripcion' => 'Frijol negro seleccionado, rico en proteínas',
                'precio_kg_usd' => 3.85,
                'stock_kg' => 28.000,
                'categoria_id' => $granos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 7.000
            ],

            // Embutidos
            [
                'sku' => 'EMB-001',
                'nombre' => 'Jamón de Pavo',
                'descripcion' => 'Jamón de pavo ahumado, bajo en grasa',
                'precio_kg_usd' => 6.50,
                'stock_kg' => 12.000,
                'categoria_id' => $embutidos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 3.000
            ],
            [
                'sku' => 'EMB-002',
                'nombre' => 'Salchicha Premium',
                'descripcion' => 'Salchicha de carne premium, ahumada',
                'precio_kg_usd' => 4.95,
                'stock_kg' => 15.500,
                'categoria_id' => $embutidos->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 4.000
            ],

            // Panadería
            [
                'sku' => 'PAN-001',
                'nombre' => 'Pan de Molde Integral',
                'descripcion' => 'Pan de molde integral con semillas',
                'precio_kg_usd' => 3.25,
                'stock_kg' => 10.000,
                'categoria_id' => $panaderia->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 2.500
            ],
            [
                'sku' => 'PAN-002',
                'nombre' => 'Pastel de Chocolate',
                'descripcion' => 'Pastel de chocolate con relleno de crema',
                'precio_kg_usd' => 8.50,
                'stock_kg' => 5.000,
                'categoria_id' => $panaderia->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 1.000
            ],

            // Bebidas
            [
                'sku' => 'BEB-001',
                'nombre' => 'Jugo de Naranja Natural',
                'descripcion' => 'Jugo de naranja 100% natural, sin conservantes',
                'precio_kg_usd' => 2.85,
                'stock_kg' => 8.000,
                'categoria_id' => $bebidas->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 2.000
            ],
            [
                'sku' => 'BEB-002',
                'nombre' => 'Agua Mineral',
                'descripcion' => 'Agua mineral natural, envasada',
                'precio_kg_usd' => 1.25,
                'stock_kg' => 15.000,
                'categoria_id' => $bebidas->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 4.000
            ],

            // Aseo
            [
                'sku' => 'ASE-001',
                'nombre' => 'Detergente Líquido',
                'descripcion' => 'Detergente líquido para ropa, aroma floral',
                'precio_kg_usd' => 3.75,
                'stock_kg' => 20.000,
                'categoria_id' => $aseo->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 5.000
            ],
            [
                'sku' => 'ASE-002',
                'nombre' => 'Desinfectante Multiusos',
                'descripcion' => 'Desinfectante de superficie, aroma a limón',
                'precio_kg_usd' => 2.45,
                'stock_kg' => 12.500,
                'categoria_id' => $aseo->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 3.000
            ],

            // Congelados
            [
                'sku' => 'CON-001',
                'nombre' => 'Vegetales Congelados',
                'descripcion' => 'Mezcla de vegetales congelados: brócoli, zanahoria, coliflor',
                'precio_kg_usd' => 3.25,
                'stock_kg' => 18.000,
                'categoria_id' => $congelados->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 4.000
            ],
            [
                'sku' => 'CON-002',
                'nombre' => 'Helado de Vainilla',
                'descripcion' => 'Helado cremoso de vainilla, premium',
                'precio_kg_usd' => 5.95,
                'stock_kg' => 8.500,
                'categoria_id' => $congelados->id,
                'iva_porcentaje' => 16,
                'activo' => true,
                'stock_minimo' => 2.000
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}