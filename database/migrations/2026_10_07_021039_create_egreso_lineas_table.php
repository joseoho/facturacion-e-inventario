<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egreso_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egreso_id')->constrained('egresos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 15, 3)->default(0);
            $table->decimal('precio_unitario', 15, 4)->default(0);
            $table->decimal('impuesto_porcentaje', 8, 2)->default(0);
            $table->decimal('impuesto_monto', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->boolean('afecta_inventario')->default(false);
            $table->timestamps();

            $table->index('egreso_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egreso_lineas');
    }
};