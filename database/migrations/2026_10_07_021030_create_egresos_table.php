<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('categoria_egreso_id')->constrained('categorias_egreso');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->string('moneda_pago', 10)->default('USD');
            $table->foreignId('tasa_cambio_id')->nullable()->constrained('tasas_cambio')->nullOnDelete();
            $table->decimal('tasa_cambio_usada', 15, 4)->nullable();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('total_impuesto', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('total_usd', 15, 4)->default(0);
            $table->date('fecha_egreso');
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'pago_movil', 'zelle', 'otro'])->nullable();
            $table->string('referencia_pago', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('fecha_egreso');
            $table->index('estado');
            $table->index('categoria_egreso_id');
            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};