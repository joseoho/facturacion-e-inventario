<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monedas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 3)->unique(); // USD, BS, COP, EUR, MXN
            $table->string('nombre', 50);
            $table->string('simbolo', 5);
            $table->boolean('es_base')->default(false); // Solo una moneda es base (USD)
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('codigo');
            $table->index('es_base');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monedas');
    }
};