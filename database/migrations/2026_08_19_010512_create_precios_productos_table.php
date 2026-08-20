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
        Schema::create('precios_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('moneda_id')->constrained('monedas')->onDelete('cascade');
            // 👇 CORREGIDO: referencia explícita a 'tasas_cambio'
            $table->foreignId('tasa_cambio_id')->constrained('tasas_cambio')->onDelete('cascade');
            $table->decimal('precio_kg', 15, 4);
            $table->timestamps();
            
            // Índices y restricciones
            $table->unique(['producto_id', 'moneda_id', 'tasa_cambio_id'], 'precios_productos_unique');
            $table->index(['producto_id', 'moneda_id']);
            $table->index(['tasa_cambio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precios_productos');
    }
};