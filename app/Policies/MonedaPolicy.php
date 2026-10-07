<?php

namespace App\Policies;

use App\Models\Moneda;
use App\Models\User;

class MonedaPolicy
{
    /**
     * Ver listado de monedas.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Ver una moneda concreta.
     */
    public function view(User $user, Moneda $moneda): bool
    {
        return false;
    }

    /**
     * Crear moneda.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Editar moneda.
     */
    public function update(User $user, Moneda $moneda): bool
    {
        return false;
    }

    /**
     * Eliminar moneda.
     */
    public function delete(User $user, Moneda $moneda): bool
    {
        return false;
    }
}