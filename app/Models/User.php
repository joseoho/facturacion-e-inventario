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
        // ============================================================
    // ROLES DEL SISTEMA — fuente única de verdad
    // ============================================================
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_VENDEDOR = 'vendedor';

    // Lista maestra de roles válidos (útil para validaciones)
    public const ROLES_VALIDOS = [
        self::ROLE_ADMIN,
        self::ROLE_VENDEDOR,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'activo'            => 'boolean',
        'deleted_at'        => 'datetime',
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

        // ============================================================
    // HELPERS DE ROL
    // ============================================================

    /**
     * ¿Es administrador? Los admins pasan TODAS las autorizaciones
     * (vía Gate::before en AppServiceProvider).
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * ¿Es vendedor?
     */
    public function isVendedor(): bool
    {
        return $this->role === self::ROLE_VENDEDOR;
    }

    /**
     * ¿Tiene un rol específico?
     */
    public function tieneRol(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * ¿Tiene alguno de los roles indicados?
     */
    public function tieneAlgunRol(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Scope: usuarios con un rol específico.
     * Uso: User::delRol('admin')->get();
     */
    public function scopeDelRol($query, string $role)
    {
        return $query->where('role', $role);
    }
}