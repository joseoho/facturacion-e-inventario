<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'facturas';

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
        // 'fecha_pago',
        'moneda_pago', // NUEVO CAMPO
        'tasa_cambio_usada', // NUEVO CAMPO
    ];

    protected $casts = [
        'subtotal_neto' => 'decimal:4',
        'total_impuesto' => 'decimal:4',
        'total' => 'decimal:4',
        'fecha_emision' => 'date',
        'fecha_pago' => 'date',
        'tasa_cambio_usada' => 'decimal:4',
    ];

    // Constantes
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADA = 'pagada';
    const ESTADO_ANULADA = 'anulada';

    // Relaciones
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

    public function lineas(): HasMany
    {
        return $this->hasMany(FacturaLinea::class, 'factura_id');
    }

    // Accesores
    public function getFechaFormateadaAttribute()
    {
        return $this->fecha_emision ? $this->fecha_emision->format('d/m/Y') : 'N/A';
    }

    public function getEstadoTextoAttribute()
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'pagada' => 'Pagada',
            'anulada' => 'Anulada',
        ];
        return $estados[$this->estado] ?? 'Desconocido';
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', self::ESTADO_PAGADA);
    }

    public function scopeAnuladas($query)
    {
        return $query->where('estado', self::ESTADO_ANULADA);
    }
}