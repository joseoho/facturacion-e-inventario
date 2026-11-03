<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use App\Policies\FacturaPolicy;

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
        'fecha_pago',
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

    /**
     * Total de la factura convertido a USD con la tasa guardada.
     * Si la tasa es cero o no existe, se asume que el total ya está en USD.
     */
    public function getTotalUsdCalculadoAttribute(): float
    {
        $tasa = (float) ($this->tasa_cambio_usada ?? 0);

        if ($tasa <= 0 && $this->tasa_cambio_id) {
            $tasa = (float) ($this->tasaCambio?->tasa ?? 0);
        }

        $codigo = $this->moneda_pago ?: $this->moneda?->codigo;

        // Facturas antiguas sin tasa guardada: buscar la tasa vigente
        // para esa moneda en la fecha de emisión.
        if ($tasa <= 0 && $codigo && strtoupper((string) $codigo) !== 'USD') {
            $tasa = (float) TasaCambio::query()
                ->where('moneda_id', $this->moneda_id)
                ->whereDate('fecha', '<=', $this->fecha_emision)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->value('tasa');
        }

        $total = (float) $this->total;

        return round($tasa > 0 ? $total / $tasa : $total, 4);
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
