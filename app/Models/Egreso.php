<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Egreso extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADO = 'pagado';

    public const ESTADO_ANULADO = 'anulado';

    public const METODOS_PAGO = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'pago_movil' => 'Pago móvil',
        'zelle' => 'Zelle',
        'otro' => 'Otro',
    ];

    public const MONEDAS_PAGO = ['USD', 'BS', 'COP'];

    protected $table = 'egresos';

    protected $fillable = [
        'numero',
        'categoria_egreso_id',
        'proveedor_nombre',
        'producto_id',
        'cantidad_kg',
        'user_id',
        'moneda_id',
        'moneda_pago',
        'tasa_cambio_id',
        'tasa_cambio_usada',
        'monto',
        'fecha_egreso',
        'fecha_pago',
        'estado',
        'metodo_pago',
        'referencia_pago',
        'concepto',
    ];

    protected $casts = [
        'cantidad_kg' => 'decimal:3',
        'tasa_cambio_usada' => 'decimal:4',
        'monto' => 'decimal:4',
        'fecha_egreso' => 'date',
        'fecha_pago' => 'date',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEgreso::class, 'categoria_egreso_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
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

    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha_egreso ? $this->fecha_egreso->format('d/m/Y') : 'N/A';
    }

    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_ANULADO => 'Anulado',
            default => 'Desconocido',
        };
    }

    public function getMetodoPagoTextoAttribute(): string
    {
        return self::METODOS_PAGO[$this->metodo_pago] ?? '—';
    }

    /**
     * Monto convertido a USD con la tasa congelada al momento del egreso.
     */
    public function getMontoUsdAttribute(): float
    {
        $monto = (float) $this->monto;

        if (! $this->tasa_cambio_usada || (float) $this->tasa_cambio_usada <= 0) {
            return $monto;
        }

        return round($monto / (float) $this->tasa_cambio_usada, 4);
    }

    public function scopeNoAnulados($query)
    {
        return $query->where('estado', '!=', self::ESTADO_ANULADO);
    }

    public function scopePagados($query)
    {
        return $query->where('estado', self::ESTADO_PAGADO);
    }
}
