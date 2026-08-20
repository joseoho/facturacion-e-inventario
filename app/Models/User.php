<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
        use HasFactory, Notifiable, SoftDeletes;

    /**
     * ✅ FORZAR: Solo strings en fillable
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * ✅ FORZAR: Sobrescribir el método que causa el error
     */
    public function fillableFromArray(array $attributes)
    {
        // Asegurar que fillable solo contenga strings
        $fillable = array_map('strval', $this->getFillable());
        return array_intersect_key($attributes, array_flip($fillable));
    }
}