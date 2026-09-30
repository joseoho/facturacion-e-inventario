<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Uso en rutas:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,vendedor')
     *
     * Si el usuario no está autenticado o no tiene ninguno de los
     * roles indicados, se aborta con 403.
     */
        public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado.');
        }

        if (empty($roles)) {
            abort(500, 'Middleware role requiere al menos un rol.');
        }

        if (!$user->tieneAlgunRol($roles)) {
            // En vez de abort(403), redirigimos al dashboard con un
            // mensaje. Esto evita que un redirect automático del Handler
            // acabe en /home (que ya no renderiza el dashboard).
            return redirect()
                ->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esa sección.');
        }

        return $next($request);
    }
}