<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'facturas'; // Nombre mapeado de la tabla exacta

    protected $fillable = [
        'numero',
        'cliente_id',
        'user_id',
        'moneda_id',
        'tasa_cambio_id',
        'subtotal_neto',
        'total_impuesto',
        'total',
        'estado',
        'fecha_emision',
    ];

    protected $casts = [
        'subtotal_neto'  => 'decimal:4',
        'total_impuesto' => 'decimal:4',
        'total'          => 'decimal:4',
        'fecha_emision'  => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'tasa_cambio_id');
    }

    /**
     * Una factura se compone de varias líneas de detalle.
     */
    public function lineas(): HasMany
    {
        return $this->hasMany(FacturaLinea::class, 'factura_id');
    }
}