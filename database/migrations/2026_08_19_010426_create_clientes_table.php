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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('documento', 50)->unique(); // RIF, CI, NIT
            $table->string('tipo_documento', 20)->default('CI'); // CI, RIF, NIT, PASAPORTE
            $table->string('direccion', 500)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('contacto', 100)->nullable(); // Persona de contacto
            $table->enum('tipo_cliente', ['minorista', 'mayorista', 'corporativo'])->default('minorista');
            $table->decimal('limite_credito', 15, 2)->default(0);
            $table->integer('dias_credito')->default(0);
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('nombre');
            $table->index('documento');
            $table->index('email');
            $table->index('activo');
            $table->index('tipo_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};