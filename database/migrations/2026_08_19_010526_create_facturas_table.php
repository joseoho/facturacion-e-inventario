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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('moneda_id')->constrained('monedas')->onDelete('cascade');
            // 👇 CORREGIDO: referencia explícita a 'tasas_cambio'
            $table->foreignId('tasa_cambio_id')->constrained('tasas_cambio')->onDelete('cascade');
            $table->decimal('subtotal_neto', 15, 4);
            $table->decimal('total_impuesto', 15, 4);
            $table->decimal('total', 15, 4);
            $table->enum('estado', ['pendiente', 'pagada', 'anulada'])->default('pendiente');
            $table->date('fecha_emision');
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('numero');
            $table->index('estado');
            $table->index('fecha_emision');
            $table->index(['cliente_id', 'estado']);
            $table->index(['fecha_emision', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};