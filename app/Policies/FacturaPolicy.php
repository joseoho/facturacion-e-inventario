<?php

namespace App\Policies;

use App\Models\Factura;
use App\Models\User;

class FacturaPolicy
{
    /**
     * Gate::before en AppServiceProvider ya deja pasar a los admins
     * a TODAS las habilidades. Por eso aquí solo definimos qué puede
     * hacer un VENDEDOR (y los admins pasan sin tocar estos métodos).
     */

    /**
     * Ver listado de facturas.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ✅
     */
    public function viewAny(User $user): bool
    {
        return true; // cualquier usuario autenticado
    }

    /**
     * Ver una factura concreta.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ✅
     */
    public function view(User $user, Factura $factura): bool
    {
        return true; // cualquier usuario autenticado
    }

    /**
     * Crear factura.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ✅
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Editar factura.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ✅ SOLO si está pendiente
     *
     * La lógica de "solo pendientes" se valida además en el controlador,
     * pero la ponemos aquí también como doble seguridad.
     */
    public function update(User $user, Factura $factura): bool
    {
        return $factura->estado === Factura::ESTADO_PENDIENTE;
    }

    /**
     * Anular factura.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ❌
     */
    public function anular(User $user, Factura $factura): bool
    {
        // Solo admin. Vendedor no puede anular.
        // (El admin ya pasa por Gate::before, así que aquí solo
        //  denegamos explícitamente para vendedores.)
        return false;
    }

    /**
     * Marcar como pagada.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ✅ SOLO si está pendiente
     */
    public function pagar(User $user, Factura $factura): bool
    {
        return $factura->estado === Factura::ESTADO_PENDIENTE;
    }

    /**
     * Imprimir / PDF.
     * Admin: ✅ (por Gate::before)
     * Vendedor: ✅
     */
    public function imprimir(User $user, Factura $factura): bool
    {
        return true;
    }

    /**
     * Eliminar factura (hard delete).
     * Admin: ✅ (por Gate::before)
     * Vendedor: ❌
     */
    public function delete(User $user, Factura $factura): bool
    {
        // Vendedor no puede borrar facturas. Solo admin (que pasa por
        // Gate::before). Además, la práctica recomendada es "anular"
        // en lugar de "eliminar". Este método existe por completitud.
        return false;
    }
}