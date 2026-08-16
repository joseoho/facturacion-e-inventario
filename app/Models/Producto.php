<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'sku',
        'nombre',
        'descripcion',
        'precio_kg_usd',
        'stock_kg',
        'categoria_id',
        'iva_porcentaje',
        'imagen',
        'activo',
        'stock_minimo',
    ];

    protected $casts = [
        'precio_kg_usd'  => 'decimal:4',
        'stock_kg'       => 'decimal:3',
        'iva_porcentaje' => 'decimal:2',
        'stock_minimo'   => 'decimal:3',
        'activo'         => 'boolean',
    ];

    /**
     * El producto pertenece a una categoría.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Histórico o desglose de precios en moneda local calculados.
     */
    public function preciosProductos(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'producto_id');
    }

    /**
     * Líneas de factura donde se ha vendido este producto.
     */
    public function facturaLineas(): HasMany
    {
        return $this->hasMany(FacturaLinea::class, 'producto_id');
    }
}