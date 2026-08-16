<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TasaCambio extends Model
{
    use HasFactory;

    protected $table = 'tasas_cambio';

    protected $fillable = [
        'moneda_id',
        'tasa',
        'fecha',
        'user_id',
    ];

    protected $casts = [
        'tasa'  => 'decimal:6',
        'fecha' => 'date',
    ];

    /**
     * La tasa de cambio pertenece a una moneda concreta.
     */
    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    /**
     * El usuario que registró la tasa de cambio.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Precios calculados a partir de esta tasa de cambio.
     */
    public function preciosProductos(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'tasa_cambio_id');
    }

    /**
     * Facturas que tomaron como referencia esta tasa de cambio.
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'tasa_cambio_id');
    }
}