<?php

namespace App\Policies;

use App\Models\TasaCambio;
use App\Models\User;

class TasaCambioPolicy
{
    /**
     * Ver listado de tasas.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Ver una tasa concreta.
     */
    public function view(User $user, TasaCambio $tasaCambio): bool
    {
        return false;
    }

    /**
     * Crear tasa.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Editar tasa.
     */
    public function update(User $user, TasaCambio $tasaCambio): bool
    {
        return false;
    }

    /**
     * Eliminar tasa.
     */
    public function delete(User $user, TasaCambio $tasaCambio): bool
    {
        return false;
    }

    /**
     * Actualizar precios masivamente.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function actualizarPrecios(User $user): bool
    {
        return false;
    }
}
