<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simplifica el módulo de egresos: un egreso es un registro plano
     * (sin líneas), con proveedor en texto libre y un único `monto`.
     *
     * La tabla estaba vacía en producción, así que se puede reestructurar
     * sin migración de datos. Es idempotente para instalaciones limpias,
     * donde `create_egresos_table` ya crea el esquema nuevo.
     */
    public function up(): void
    {
        Schema::dropIfExists('egreso_lineas');

        Schema::table('egresos', function (Blueprint $table) {
            if (! Schema::hasColumn('egresos', 'monto')) {
                $table->decimal('monto', 12, 4)->default(0)->after('tasa_cambio_usada');
            }

            if (! Schema::hasColumn('egresos', 'proveedor_nombre')) {
                $table->string('proveedor_nombre', 200)->nullable()->after('categoria_egreso_id');
            }

            if (! Schema::hasColumn('egresos', 'producto_id')) {
                $table->foreignId('producto_id')->nullable()->after('proveedor_nombre')
                    ->constrained('productos')->nullOnDelete();
            }

            if (! Schema::hasColumn('egresos', 'cantidad_kg')) {
                $table->decimal('cantidad_kg', 10, 3)->nullable()->after('producto_id');
            }

            if (! Schema::hasColumn('egresos', 'concepto')) {
                $table->text('concepto')->nullable()->after('referencia_pago');
            }

            if (Schema::hasColumn('egresos', 'proveedor_id')) {
                $table->dropForeign(['proveedor_id']);
                $table->dropColumn('proveedor_id');
            }

            foreach (['subtotal', 'total_impuesto', 'total', 'total_usd', 'descripcion'] as $columna) {
                if (Schema::hasColumn('egresos', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }

    public function down(): void
    {
        // Reversible solo de forma parcial: no restauramos egreso_lineas
        // ni las columnas de totales desglosados.
    }
};
