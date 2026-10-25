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
            // La moneda puede ser null en facturas históricas importadas.
            $table->foreignId('moneda_id')->nullable()->constrained('monedas')->onDelete('cascade');
            // La moneda base (USD) no requiere tasa: se guarda null.
            $table->foreignId('tasa_cambio_id')->nullable()->constrained('tasas_cambio')->onDelete('cascade');
            $table->string('moneda_pago', 10)->nullable();
            $table->decimal('tasa_cambio_usada', 15, 4)->nullable();
            $table->decimal('subtotal_neto', 15, 4);
            $table->decimal('total_impuesto', 15, 4);
            $table->decimal('total', 15, 4);
            $table->decimal('total_usd', 15, 4)->nullable();
            $table->enum('estado', ['pendiente', 'pagada', 'anulada'])->default('pendiente');
            $table->date('fecha_emision');
            $table->date('fecha_pago')->nullable();
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
