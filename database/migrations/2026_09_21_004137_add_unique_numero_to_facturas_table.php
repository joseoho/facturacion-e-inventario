<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo agregar si no existe ya
        Schema::table('facturas', function (Blueprint $table) {
            $table->unique('numero', 'facturas_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique('facturas_numero_unique');
        });
    }
};