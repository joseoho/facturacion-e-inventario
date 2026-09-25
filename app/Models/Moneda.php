<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Moneda extends Model
{
    use HasFactory;

    protected $table = 'monedas';

        // ============================================================
    // CÓDIGOS DE MONEDA — fuente única de verdad
    // ============================================================
    public const CODIGO_USD = 'USD';
    public const CODIGO_COP = 'COP';
    public const CODIGO_VES = 'BS';   // Bolívar (según tu BD)
    public const CODIGO_EUR = 'EUR';

    public const ALIAS_VES = ['BS', 'VES'];

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'es_base',
        'activo',
    ];

    protected $casts = [
        'es_base' => 'boolean',
        'activo'  => 'boolean',
    ];

    // protected $fillable = [
    //     'codigo',
    //     'nombre',
    //     'simbolo',
    //     'es_base',
    //     'activo',
    // ];

    // protected $casts = [
    //     'es_base' => 'boolean',
    // ];

    /**
     * Tasas de cambio registradas para esta moneda.
     */
    public function tasasCambio(): HasMany
    {
        return $this->hasMany(TasaCambio::class, 'moneda_id');
    }

    /**
     * Precios de productos convertidos a esta moneda.
     */
    public function preciosProductos(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'moneda_id');
    }

    /**
     * Facturas emitidas utilizando esta moneda.
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'moneda_id');
    }

        public function scopePorCodigo($query, string $codigo)
    {
        $codigo = strtoupper($codigo);

        $codigos = match ($codigo) {
            'VES', 'BS' => self::ALIAS_VES,
            default     => [$codigo],
        };

        return $query->whereIn('codigo', $codigos);
    }

    public static function base(): ?self
    {
        return static::where('es_base', true)->first();
    }

    public function esBase(): bool
    {
        return (bool) $this->es_base;
    }
}