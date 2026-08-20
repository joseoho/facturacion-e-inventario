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
        Schema::create('factura_lineas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('factura_id')
                  ->constrained('facturas')
                  ->onDelete('cascade'); // Si se elimina la factura, se eliminan las líneas
            
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict'); // No permitir eliminar producto con líneas
            
            // Datos de la línea
            $table->decimal('cantidad_kg', 15, 3); // Cantidad en Kg (3 decimales)
            $table->decimal('precio_kg', 15, 4); // Precio por Kg en la moneda de la factura
            
            // Cálculos
            $table->decimal('neto', 15, 4); // Subtotal sin impuesto
            $table->decimal('impuesto_porcentaje', 5, 2)->default(0); // % IVA aplicado
            $table->decimal('impuesto_monto', 15, 4); // Monto del IVA
            $table->decimal('total', 15, 4); // Total de la línea (neto + impuesto)
            
            // Datos adicionales
            $table->text('nota')->nullable(); // Nota adicional por línea
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('factura_id');
            $table->index('producto_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_lineas');
    }
};