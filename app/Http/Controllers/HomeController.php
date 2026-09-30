<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    /**
     * El dashboard real vive en `DashboardController@index` (ruta `/`).
     *
     * Este controlador existía por compatibilidad con el scaffolding de
     * laravel/ui, pero renderizaba la vista del dashboard sin pasarle
     * datos. Ahora simplemente redirige al dashboard real.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}