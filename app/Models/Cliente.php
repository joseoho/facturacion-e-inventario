<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'documento',
        'tipo_documento',
        'direccion',
        'telefono',
        'email',
        'contacto',
        'tipo_cliente',
        'limite_credito',
        'dias_credito',
        'activo',
        'notas',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'dias_credito'   => 'integer',
        'activo'         => 'boolean',
    ];

    /**
     * Un cliente puede tener muchas facturas emitidas.
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'cliente_id');
    }
}