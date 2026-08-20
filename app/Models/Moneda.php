<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Moneda extends Model
{
    use HasFactory;

    protected $table = 'monedas';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'es_base',
        'activo',
    ];

    protected $casts = [
        'es_base' => 'boolean',
    ];

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
}