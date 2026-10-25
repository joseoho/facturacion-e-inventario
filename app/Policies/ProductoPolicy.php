<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    /**
     * Ver listado de productos.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Ver un producto concreto.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function view(User $user, Producto $producto): bool
    {
        return true;
    }

    /**
     * Crear producto.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Editar producto.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function update(User $user, Producto $producto): bool
    {
        return false;
    }

    /**
     * Eliminar producto.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function delete(User $user, Producto $producto): bool
    {
        return false;
    }

    /**
     * Gestionar precios de un producto (precios por moneda).
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function gestionarPrecios(User $user, Producto $producto): bool
    {
        return false;
    }
}
