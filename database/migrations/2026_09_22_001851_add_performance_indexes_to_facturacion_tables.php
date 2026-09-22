<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
{
     // 1. Obtenemos todos los índices actuales de la tabla de forma nativa
    $indexes = Schema::getIndexes('facturas');
    $indexNames = collect($indexes)->pluck('name');

    Schema::table('facturas', function (Blueprint $table) use ($indexNames) {
        // 2. Solo agrega el índice si NO existe previamente
        if (!$indexNames->contains('facturas_estado_index')) {
            $table->index('estado');
        }

        // Hacemos lo mismo para el de número por si acaso
        if (!$indexNames->contains('facturas_numero_unique')) {
            $table->unique('numero');
        }
    });
}
};
