<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Deshabilitar verificaciones de clave foránea
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar tablas (si existen datos previos)
        $this->truncateTables();

        // Ejecutar seeders en orden correcto
        $this->call([
            UserSeeder::class,
            MonedaSeeder::class,
            TasaCambioSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            ClienteSeeder::class,
        ]);

        // Habilitar verificaciones de clave foránea
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Todos los seeders han sido ejecutados correctamente.');
    }

    /**
     * Truncar todas las tablas relevantes
     */
    private function truncateTables(): void
    {
        $tables = [
            'users',
            'monedas',
            'tasas_cambio',
            'categorias',
            'productos',
            'precios_productos',
            'clientes',
            'facturas',
            'factura_lineas',
            'actualizaciones_precios'
        ];

        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                DB::table($table)->truncate();
                $this->command->info("Tabla {$table} truncada.");
            }
        }
    }
}