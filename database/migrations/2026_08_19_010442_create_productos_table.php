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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique(); // Código único del producto
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            
            // Precios y stock
            $table->decimal('precio_kg_usd', 15, 4); // Precio base en USD por Kg
            $table->decimal('stock_kg', 15, 3)->default(0); // Stock en Kg (3 decimales)
            $table->decimal('stock_minimo', 15, 3)->default(0); // Alerta de stock mínimo
            
            // Relaciones
            $table->foreignId('categoria_id')
                  ->constrained('categorias')
                  ->onDelete('restrict'); // No permitir eliminar categoría con productos
                  
            // Impuestos y estado
            $table->decimal('iva_porcentaje', 5, 2)->default(0); // IVA %
            $table->string('imagen')->nullable(); // Ruta de la imagen
            
            // Estado
            $table->boolean('activo')->default(true);
            
            // Soft deletes
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('sku');
            $table->index('nombre');
            $table->index('activo');
            $table->index('categoria_id');
            $table->index('stock_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};