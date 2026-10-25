<?php

namespace App\Policies;

use App\Models\CategoriaEgreso;
use App\Models\User;

/**
 * Categorías de egreso: solo administradores (Gate::before deja pasar al admin).
 */
class CategoriaEgresoPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, CategoriaEgreso $categoria): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CategoriaEgreso $categoria): bool
    {
        return false;
    }

    public function delete(User $user, CategoriaEgreso $categoria): bool
    {
        return false;
    }
}
