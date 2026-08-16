<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioProducto extends Model
{
    use HasFactory;

    protected $table = 'precios_productos';

    protected $fillable = [
        'producto_id',
        'moneda_id',
        'tasa_cambio_id',
        'precio_kg',
    ];

    protected $casts = [
        'precio_kg' => 'decimal:4',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'tasa_cambio_id');
    }
}