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
            $table->string('proveedor_nombre', 200)->nullable();
            // Solo se usa cuando la categoría afecta inventario.
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->decimal('cantidad_kg', 10, 3)->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->string('moneda_pago', 3);
            $table->foreignId('tasa_cambio_id')->nullable()->constrained('tasas_cambio')->nullOnDelete();
            $table->decimal('tasa_cambio_usada', 15, 4)->nullable();
            $table->decimal('monto', 12, 4);
            $table->date('fecha_egreso');
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'pago_movil', 'zelle', 'otro'])->nullable();
            $table->string('referencia_pago', 100)->nullable();
            $table->text('concepto');
            $table->timestamps();

            $table->index('fecha_egreso');
            $table->index('estado');
            $table->index('moneda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};
