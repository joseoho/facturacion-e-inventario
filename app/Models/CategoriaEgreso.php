<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaEgreso extends Model
{
    use HasFactory;

    protected $table = 'categorias_egreso';

    protected $fillable = [
        'nombre',
        'descripcion',
        'afecta_inventario',
        'activo',
    ];

    protected $casts = [
        'afecta_inventario' => 'boolean',
        'activo' => 'boolean',
    ];

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'categoria_egreso_id');
    }
}
