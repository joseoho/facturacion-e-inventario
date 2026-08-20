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
        Schema::create('tasas_cambio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moneda_id')->constrained('monedas')->onDelete('cascade');
            $table->decimal('tasa', 15, 6);
            $table->date('fecha');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // 👇 Asegurar índices únicos
            $table->unique(['moneda_id', 'fecha'], 'tasas_cambio_unique');
            $table->index(['fecha', 'moneda_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasas_cambio');
    }
};