<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reconcilia el esquema real (mantenido a mano en producción) con las
     * migraciones del repositorio.
     *
     * Contexto: la base de datos de producción tenía columnas que ninguna
     * migración creaba (`users.role`, `users.activo`, `users.deleted_at`,
     * `facturas.moneda_pago`, `facturas.tasa_cambio_usada`,
     * `facturas.fecha_pago`, ...). Eso hacía imposible reconstruir el
     * esquema con `migrate:fresh`.
     *
     * Esta migración es idempotente: solo añade lo que falte, así que se
     * puede ejecutar sobre la BD existente sin perder datos y también sobre
     * una instalación limpia (donde no hará nada).
     */
    public function up(): void
    {
        // ============================================================
        // USERS — rol y estado lógico
        // ============================================================
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('vendedor')->after('password');
                $table->index('role');
            }

            if (! Schema::hasColumn('users', 'activo')) {
                $table->boolean('activo')->default(true)->after('role');
                $table->index('activo');
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // ============================================================
        // FACTURAS — trazabilidad de moneda/tasa y fecha de pago
        // ============================================================
        Schema::table('facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas', 'moneda_pago')) {
                $table->string('moneda_pago', 10)->nullable()->after('moneda_id');
            }

            if (! Schema::hasColumn('facturas', 'tasa_cambio_usada')) {
                $table->decimal('tasa_cambio_usada', 15, 4)->nullable()->after('moneda_pago');
            }

            if (! Schema::hasColumn('facturas', 'total_usd')) {
                $table->decimal('total_usd', 15, 4)->nullable()->after('total');
            }

            if (! Schema::hasColumn('facturas', 'fecha_pago')) {
                $table->date('fecha_pago')->nullable()->after('fecha_emision');
            }
        });

        // ============================================================
        // FACTURA_LINEAS — precio base USD para auditoría
        // ============================================================
        Schema::table('factura_lineas', function (Blueprint $table) {
            if (! Schema::hasColumn('factura_lineas', 'precio_kg_usd')) {
                $table->decimal('precio_kg_usd', 15, 4)->nullable()->after('precio_kg');
            }
        });
    }

    public function down(): void
    {
        // No eliminamos columnas: en producción pueden contener datos
        // que el esquema "oficial" no sabía que existían.
    }
};
