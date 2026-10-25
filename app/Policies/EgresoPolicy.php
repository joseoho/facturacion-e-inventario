<?php

namespace App\Policies;

use App\Models\Egreso;
use App\Models\User;

/**
 * Los egresos son solo para administradores.
 *
 * Gate::before en AppServiceProvider deja pasar a los admins a todas las
 * habilidades, así que aquí solo devolvemos false para el resto de roles.
 */
class EgresoPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Egreso $egreso): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Egreso $egreso): bool
    {
        return false;
    }

    public function delete(User $user, Egreso $egreso): bool
    {
        return false;
    }

    public function anular(User $user, Egreso $egreso): bool
    {
        return false;
    }

    public function pagar(User $user, Egreso $egreso): bool
    {
        return false;
    }
}
