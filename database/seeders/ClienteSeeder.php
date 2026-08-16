<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientes = [
            // Mayoristas
            [
                'nombre' => 'Supermercado El Sol C.A.',
                'documento' => 'J-12345678-9',
                'tipo_documento' => 'RIF',
                'direccion' => 'Av. Principal, Urb. Las Mercedes, Caracas, D.F.',
                'telefono' => '0212-5550001',
                'email' => 'ventas@supersol.com',
                'contacto' => 'María González',
                'tipo_cliente' => 'mayorista',
                'limite_credito' => 5000.00,
                'dias_credito' => 30,
                'activo' => true,
                'notas' => 'Cliente mayorista con buen historial de pago. Compras quincenales.'
            ],
            [
                'nombre' => 'Distribuidora La Exprés S.R.L.',
                'documento' => 'J-98765432-1',
                'tipo_documento' => 'RIF',
                'direccion' => 'Calle 5, Zona Industrial, Valencia, Carabobo',
                'telefono' => '0241-5550002',
                'email' => 'distribuidora@laexpres.com',
                'contacto' => 'Carlos Rodríguez',
                'tipo_cliente' => 'mayorista',
                'limite_credito' => 8000.00,
                'dias_credito' => 45,
                'activo' => true,
                'notas' => 'Cliente corporativo con excelente crédito. Compra mensual.'
            ],
            [
                'nombre' => 'Almacén El Éxito',
                'documento' => 'J-45678901-2',
                'tipo_documento' => 'RIF',
                'direccion' => 'Av. Libertador, Centro, Maracaibo, Zulia',
                'telefono' => '0261-5550003',
                'email' => 'compras@elexito.com',
                'contacto' => 'Ana López',
                'tipo_cliente' => 'mayorista',
                'limite_credito' => 3000.00,
                'dias_credito' => 20,
                'activo' => true,
                'notas' => 'Compra semanal de productos variados.'
            ],

            // Corporativos
            [
                'nombre' => 'Restaurante La Casona',
                'documento' => 'J-78901234-5',
                'tipo_documento' => 'RIF',
                'direccion' => 'Calle Comercio, San Cristóbal, Táchira',
                'telefono' => '0276-5550004',
                'email' => 'reservas@lacasona.com',
                'contacto' => 'José Pérez',
                'tipo_cliente' => 'corporativo',
                'limite_credito' => 3000.00,
                'dias_credito' => 20,
                'activo' => true,
                'notas' => 'Compra semanal de productos cárnicos y vegetales.'
            ],
            [
                'nombre' => 'Hotel Plaza Mayor',
                'documento' => 'J-23456789-0',
                'tipo_documento' => 'RIF',
                'direccion' => 'Av. Principal, Los Teques, Miranda',
                'telefono' => '0212-5550005',
                'email' => 'compras@hotelplazamayor.com',
                'contacto' => 'Pedro Sánchez',
                'tipo_cliente' => 'corporativo',
                'limite_credito' => 6000.00,
                'dias_credito' => 30,
                'activo' => true,
                'notas' => 'Proveedor exclusivo de productos lácteos y panadería.'
            ],
            [
                'nombre' => 'Comedor Industrial El Sabor',
                'documento' => 'J-34567890-1',
                'tipo_documento' => 'RIF',
                'direccion' => 'Zona Industrial, Guacara, Carabobo',
                'telefono' => '0241-5550006',
                'email' => 'gerencia@elsabor.com',
                'contacto' => 'Luis Martínez',
                'tipo_cliente' => 'corporativo',
                'limite_credito' => 4000.00,
                'dias_credito' => 15,
                'activo' => true,
                'notas' => 'Compra diaria de productos para comedor industrial.'
            ],

            // Minoristas
            [
                'nombre' => 'Panadería La Central',
                'documento' => 'V-10123456',
                'tipo_documento' => 'CI',
                'direccion' => 'Av. Bolívar, Centro, Barquisimeto, Lara',
                'telefono' => '0251-5550007',
                'email' => 'panaderia@lacentral.com',
                'contacto' => 'Ana López',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 1000.00,
                'dias_credito' => 15,
                'activo' => true,
                'notas' => 'Cliente frecuente, compras diarias de panadería.'
            ],
            [
                'nombre' => 'Comercial El Amigo',
                'documento' => 'V-11223344',
                'tipo_documento' => 'CI',
                'direccion' => 'Av. Principal, Los Teques, Miranda',
                'telefono' => '0212-5550008',
                'email' => 'ventas@elamigo.com',
                'contacto' => 'Pedro Sánchez',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 500.00,
                'dias_credito' => 10,
                'activo' => true,
                'notas' => 'Tienda de barrio, compras pequeñas semanales.'
            ],
            [
                'nombre' => 'Frutas y Verduras El Fresco',
                'documento' => 'V-12345678',
                'tipo_documento' => 'CI',
                'direccion' => 'Mercado Municipal, Puesto 15, Maracay, Aragua',
                'telefono' => '0243-5550009',
                'email' => 'elfresco@email.com',
                'contacto' => 'Carmen Díaz',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 300.00,
                'dias_credito' => 7,
                'activo' => true,
                'notas' => 'Compra diaria de frutas y verduras.'
            ],
            [
                'nombre' => 'Carnicería El Buen Corte',
                'documento' => 'V-23456789',
                'tipo_documento' => 'CI',
                'direccion' => 'Av. Principal, San Antonio de Los Altos, Miranda',
                'telefono' => '0212-5550010',
                'email' => 'buencorte@email.com',
                'contacto' => 'Roberto Jiménez',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 800.00,
                'dias_credito' => 10,
                'activo' => true,
                'notas' => 'Compra de carnes para carnicería familiar.'
            ],
            [
                'nombre' => 'Licorería El Buen Sabor',
                'documento' => 'V-34567890',
                'tipo_documento' => 'CI',
                'direccion' => 'Calle 10, Urb. El Paraíso, Caracas',
                'telefono' => '0212-5550011',
                'email' => 'elbuensabor@email.com',
                'contacto' => 'Sofía Ramírez',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 400.00,
                'dias_credito' => 5,
                'activo' => true,
                'notas' => 'Compra de bebidas para licorería.'
            ],
            [
                'nombre' => 'Farmacia La Salud',
                'documento' => 'V-45678901',
                'tipo_documento' => 'CI',
                'direccion' => 'Av. Principal, Los Dos Caminos, Caracas',
                'telefono' => '0212-5550012',
                'email' => 'lasalud@email.com',
                'contacto' => 'Dr. Carlos Méndez',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 600.00,
                'dias_credito' => 15,
                'activo' => true,
                'notas' => 'Compra de productos de higiene personal.'
            ],
            [
                'nombre' => 'Bodegón El Ahorro',
                'documento' => 'V-56789012',
                'tipo_documento' => 'CI',
                'direccion' => 'Calle 3, Urb. Montecristo, Barinas',
                'telefono' => '0273-5550013',
                'email' => 'elahorro@email.com',
                'contacto' => 'Jorge Castillo',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 200.00,
                'dias_credito' => 3,
                'activo' => true,
                'notas' => 'Compra de abarrotes y productos básicos.'
            ],
            [
                'nombre' => 'Heladería El Sabor Tropical',
                'documento' => 'V-67890123',
                'tipo_documento' => 'CI',
                'direccion' => 'Av. Principal, Lechería, Anzoátegui',
                'telefono' => '0281-5550014',
                'email' => 'sabortropical@email.com',
                'contacto' => 'María Fernanda Torres',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 250.00,
                'dias_credito' => 7,
                'activo' => true,
                'notas' => 'Compra de productos congelados para heladería.'
            ],
            [
                'nombre' => 'Pizzería La Italiana',
                'documento' => 'V-78901234',
                'tipo_documento' => 'CI',
                'direccion' => 'Calle Principal, El Cafetal, Caracas',
                'telefono' => '0212-5550015',
                'email' => 'laitaliana@email.com',
                'contacto' => 'Giuseppe Romano',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 450.00,
                'dias_credito' => 10,
                'activo' => true,
                'notas' => 'Compra de productos lácteos y panadería.'
            ],
            [
                'nombre' => 'Cliente Ocasional',
                'documento' => 'V-89012345',
                'tipo_documento' => 'CI',
                'direccion' => 'Calle 8, Urb. Los Rosales, Caracas',
                'telefono' => '0212-5550016',
                'email' => 'ocasional@email.com',
                'contacto' => 'Lucía Fernández',
                'tipo_cliente' => 'minorista',
                'limite_credito' => 0,
                'dias_credito' => 0,
                'activo' => true,
                'notas' => 'Cliente eventual, compras esporádicas.'
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::create($cliente);
        }
    }
}