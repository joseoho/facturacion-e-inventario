<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActualizacionPrecio extends Model
{
    protected $table = 'actualizaciones_precios';

    protected $fillable = [
        'user_id',
        'fecha_ejecucion',
        'monedas_actualizadas',
        'cantidad_productos',
    ];

    protected $casts = [
        'fecha_ejecucion' => 'datetime',
        'monedas_actualizadas' => 'array',
    ];

    // Relación con el usuario
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accesor para obtener las monedas actualizadas como objeto
    public function getMonedasActualizadasAttribute($value)
    {
        return json_decode($value, true);
    }

    // Mutador para guardar las monedas actualizadas como JSON
    public function setMonedasActualizadasAttribute($value)
    {
        $this->attributes['monedas_actualizadas'] = is_array($value) 
            ? json_encode($value) 
            : $value;
    }
}