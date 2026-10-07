<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    /**
     * Ver listado de clientes.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Ver un cliente concreto.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function view(User $user, Cliente $cliente): bool
    {
        return true;
    }

    /**
     * Ver las facturas de un cliente.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function verFacturas(User $user, Cliente $cliente): bool
    {
        return true;
    }

    /**
     * Crear cliente.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Editar cliente.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function update(User $user, Cliente $cliente): bool
    {
        return false;
    }

    /**
     * Eliminar cliente.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function delete(User $user, Cliente $cliente): bool
    {
        return false;
    }
}