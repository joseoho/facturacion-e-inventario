<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garantiza el UNIQUE en `facturas.numero`.
     *
     * La migración original lo creaba siempre. En una instalación limpia,
     * `create_facturas_table` ya lo crea con el nombre `facturas_numero_unique`,
     * así que el CREATE INDEX fallaba con "Duplicate key name 1061" y
     * `migrate:fresh` se rompía. Ahora es idempotente.
     */
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('facturas'))->pluck('name');

        if (! $indexes->contains('facturas_numero_unique')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->unique('numero', 'facturas_numero_unique');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('facturas'))->pluck('name');

        if ($indexes->contains('facturas_numero_unique')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->dropUnique('facturas_numero_unique');
            });
        }
    }
};
