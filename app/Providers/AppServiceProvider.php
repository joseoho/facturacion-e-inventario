<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ============================================================
        // GATE::BEFORE — Los admins pasan TODAS las autorizaciones
        // ============================================================
        // Se ejecuta ANTES que cualquier Policy o Gate. Si devuelve
        // true, la autorización se concede sin consultar nada más.
        // Si devuelve null, se evalúan las Policies normalmente.
        //
        // ⚠️ NUNCA devolver false aquí. Solo true o null.
        // Devolver false bloquearía a TODOS los usuarios (incluidos
        // los que SÍ tienen permiso), porque Gate::before anula la
        // evaluación normal.
        // ============================================================
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }
            return null; // deja que las Policies decidan
        });
    }
}