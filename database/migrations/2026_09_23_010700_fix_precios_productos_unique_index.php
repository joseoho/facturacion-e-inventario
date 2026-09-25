<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // PASO 1: Eliminar el UNIQUE compuesto incorrecto (3 columnas)
        // ============================================================
        // Laravel no puede eliminar un unique que agrupa varias columnas
        // si no conoce su nombre exacto. Aquí el nombre es
        // `precios_productos_unique` y agrupa producto_id, moneda_id
        // y tasa_cambio_id. Lo eliminamos por SQL directo para asegurar.
        // ============================================================
        try {
            DB::statement('ALTER TABLE `precios_productos` DROP INDEX `precios_productos_unique`');
        } catch (\Throwable $e) {
            // Si no existe, no pasa nada
        }

        // ============================================================
        // PASO 2: Eliminar índices redundantes / mal nombrados
        // ============================================================
        // Estos índices sobran una vez que tengamos el UNIQUE compuesto
        // de dos columnas (producto_id, moneda_id).
        // ============================================================
        $indicesAEliminar = [
            'precios_productos_producto_id_moneda_id_index',
            'precios_productos_moneda_id_foreign',
            'precios_productos_tasa_cambio_id_index',
        ];

        foreach ($indicesAEliminar as $idx) {
            try {
                DB::statement("ALTER TABLE `precios_productos` DROP INDEX `{$idx}`");
            } catch (\Throwable $e) {
                // Si no existe, seguimos
            }
        }

        // ============================================================
        // PASO 3: Eliminar duplicados antes de aplicar el UNIQUE
        // ============================================================
        // Dejamos solo la fila más reciente (mayor id) por producto+moneda.
        // Si esto borra algo que no debería, es porque había datos
        // duplicados ilegítimos. Revisa antes con:
        //   SELECT producto_id, moneda_id, COUNT(*) FROM precios_productos
        //   GROUP BY producto_id, moneda_id HAVING COUNT(*) > 1;
        // ============================================================
        DB::statement('
            DELETE pp1 FROM precios_productos pp1
            INNER JOIN precios_productos pp2
                ON pp1.producto_id = pp2.producto_id
               AND pp1.moneda_id   = pp2.moneda_id
               AND pp1.id < pp2.id
        ');

        // ============================================================
        // PASO 4: Crear el UNIQUE compuesto correcto
        // ============================================================
        Schema::table('precios_productos', function (Blueprint $table) {
            $table->unique(
                ['producto_id', 'moneda_id'],
                'precios_productos_producto_moneda_unique'
            );
        });

        // ============================================================
        // PASO 5: Índice para consultas por tasa (FK, NO unique)
        // ============================================================
        Schema::table('precios_productos', function (Blueprint $table) {
            $table->index('tasa_cambio_id', 'precios_productos_tasa_index');
        });
    }

    public function down(): void
    {
        Schema::table('precios_productos', function (Blueprint $table) {
            $table->dropUnique('precios_productos_producto_moneda_unique');
            $table->dropIndex('precios_productos_tasa_index');
        });

        // No restauramos el índice roto ni los duplicados eliminados.
        // Este down es "best effort", no reversible al 100%.
    }
};