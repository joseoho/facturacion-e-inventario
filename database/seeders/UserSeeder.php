<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Administrador
        // User::create([
        //     'name' => 'Administrador Principal',
        //     'email' => 'admin@facturador.com',
        //     'password' => Hash::make('Admin123!'),
        //     'role' => 'admin',
        //     'activo' => true,
        //     'telefono' => '0412-5550001',
        // ]);

        // Usuario Vendedor
        User::create([
            'name' => 'María Vendedor',
            'email' => 'vendedor@facturador.com',
            'password' => Hash::make('Vendedor123!'),
            'role' => 'vendedor',
            'activo' => true,
            // 'telefono' => '0412-5550002',
        ]);

        // Usuario Vendedor 2
        User::create([
            'name' => 'Carlos Pérez',
            'email' => 'cperez@facturador.com',
            'password' => Hash::make('Vendedor123!'),
            'role' => 'vendedor',
            'activo' => true,
            // 'telefono' => '0412-5550003',
        ]);

        // Usuario Administrador 2
        User::create([
            'name' => 'Ana Rodríguez',
            'email' => 'arodriguez@facturador.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
            'activo' => true,
            // 'telefono' => '0412-5550004',
        ]);

        // Usuario inactivo (para pruebas)
        User::create([
            'name' => 'Usuario Inactivo',
            'email' => 'inactivo@facturador.com',
            'password' => Hash::make('Inactivo123!'),
            'role' => 'vendedor',
            'activo' => false,
            // 'telefono' => '0412-5550005',
        ]);
    }
}