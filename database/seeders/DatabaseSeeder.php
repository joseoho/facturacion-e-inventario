<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $esMysql = DB::connection()->getDriverName() === 'mysql';

        // Deshabilitar verificaciones de clave foránea (solo MySQL;
        // SQLite no soporta esta sentencia y rompía los tests).
        if ($esMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Limpiar tablas (si existen datos previos)
        $this->truncateTables();

        // Ejecutar seeders en orden correcto
        $this->call(InstalacionSeeder::class);

        // Habilitar verificaciones de clave foránea
        if ($esMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

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
            'actualizaciones_precios',
            'egresos',
            'proveedores',
            'categorias_egreso',
            'movimientos_inventario',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->truncate();
            $this->command->info("Tabla {$table} truncada.");
        }
    }
}
