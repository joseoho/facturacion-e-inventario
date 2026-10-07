<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
{
    /**
     * Ver listado de categorías.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Ver una categoría concreta.
     * Admin: ✅ (Gate::before). Vendedor: ✅
     */
    public function view(User $user, Categoria $categoria): bool
    {
        return true;
    }

    /**
     * Crear categoría.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Editar categoría.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function update(User $user, Categoria $categoria): bool
    {
        return false;
    }

    /**
     * Eliminar categoría.
     * Admin: ✅ (Gate::before). Vendedor: ❌
     */
    public function delete(User $user, Categoria $categoria): bool
    {
        return false;
    }
}