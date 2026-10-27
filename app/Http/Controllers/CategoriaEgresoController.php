<?php

namespace App\Http\Controllers;

use App\Models\CategoriaEgreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaEgresoController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', CategoriaEgreso::class);

        $categorias = CategoriaEgreso::query()
            ->withCount('egresos')
            ->orderBy('nombre')
            ->paginate(15);

        return view('categorias-egreso.index', compact('categorias'));
    }

    public function create(): View
    {
        $this->authorize('create', CategoriaEgreso::class);

        return view('categorias-egreso.create', ['categoria' => new CategoriaEgreso]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CategoriaEgreso::class);

        CategoriaEgreso::create($this->validar($request));

        return redirect()
            ->route('categorias-egreso.index')
            ->with('success', 'Categoría de egreso creada exitosamente.');
    }

    public function edit(CategoriaEgreso $categoriaEgreso): View
    {
        $this->authorize('update', $categoriaEgreso);

        return view('categorias-egreso.edit', ['categoria' => $categoriaEgreso]);
    }

    public function update(Request $request, CategoriaEgreso $categoriaEgreso): RedirectResponse
    {
        $this->authorize('update', $categoriaEgreso);

        $categoriaEgreso->update($this->validar($request, $categoriaEgreso->id));

        return redirect()
            ->route('categorias-egreso.index')
            ->with('success', 'Categoría de egreso actualizada exitosamente.');
    }

    public function destroy(CategoriaEgreso $categoriaEgreso): RedirectResponse
    {
        $this->authorize('delete', $categoriaEgreso);

        if ($categoriaEgreso->egresos()->exists()) {
            return back()->with('error', 'No se puede eliminar una categoría con egresos asociados.');
        }

        $categoriaEgreso->delete();

        return redirect()
            ->route('categorias-egreso.index')
            ->with('success', 'Categoría de egreso eliminada exitosamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?int $ignorarId = null): array
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias_egreso', 'nombre')->ignore($ignorarId),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'afecta_inventario' => ['boolean'],
            'activo' => ['boolean'],
        ]);

        $validated['afecta_inventario'] = $request->boolean('afecta_inventario');
        $validated['activo'] = $request->boolean('activo', true);

        return $validated;
    }
}
