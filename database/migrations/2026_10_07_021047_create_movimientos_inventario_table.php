<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste']);
            $table->decimal('cantidad', 15, 3);
            $table->decimal('stock_anterior', 15, 3);
            $table->decimal('stock_nuevo', 15, 3);
            $table->enum('referencia_tipo', ['factura', 'egreso', 'ajuste', 'compra', 'otro']);
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->index('producto_id');
            $table->index('tipo');
            $table->index(['referencia_tipo', 'referencia_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};