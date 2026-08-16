<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaLinea extends Model
{
    use HasFactory;

    protected $table = 'factura_lineas';

    protected $fillable = [
        'factura_id',
        'producto_id',
        'cantidad_kg',
        'precio_kg',
        'neto',
        'impuesto_porcentaje',
        'impuesto_monto',
        'total',
    ];

    protected $casts = [
        'cantidad_kg'         => 'decimal:3',
        'precio_kg'           => 'decimal:4',
        'neto'                => 'decimal:4',
        'impuesto_porcentaje' => 'decimal:2',
        'impuesto_monto'      => 'decimal:4',
        'total'               => 'decimal:4',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}