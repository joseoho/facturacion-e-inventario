<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = (string) $request->string('q');

                $query->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', (string) $request->string('role')))
            ->when($request->filled('activo'), fn ($query) => $query->where('activo', $request->boolean('activo')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('usuarios.create', [
            'user' => new User(['role' => User::ROLE_VENDEDOR, 'activo' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(User::ROLES_VALIDOS)],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'activo' => ['boolean'],
        ]);

        $validated['activo'] = $request->boolean('activo', true);

        $user = User::create($validated);

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario {$user->name} creado exitosamente.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('usuarios.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(User::ROLES_VALIDOS)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'activo' => ['boolean'],
        ]);

        $activo = $request->boolean('activo');

        if ($user->is($request->user()) && (! $activo || $validated['role'] !== User::ROLE_ADMIN)) {
            return back()->withInput()->with(
                'error',
                'No puedes desactivar tu propia cuenta ni quitarte el rol de administrador.'
            );
        }

        $this->garantizarAdminActivo($user, $validated['role'], $activo);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $validated['activo'] = $activo;
        $user->update($validated);

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario {$user->name} actualizado exitosamente.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        if ($user->activo) {
            $this->garantizarAdminActivo($user, $user->role, false);
        }

        $user->update(['activo' => ! $user->activo]);

        return back()->with(
            'success',
            $user->activo ? 'Usuario activado.' : 'Usuario desactivado.'
        );
    }

    /**
     * Evita quedarse sin ningún administrador activo.
     */
    private function garantizarAdminActivo(User $user, string $nuevoRol, bool $nuevoActivo): void
    {
        $eraAdminActivo = $user->role === User::ROLE_ADMIN && $user->activo;
        $sigueAdminActivo = $nuevoRol === User::ROLE_ADMIN && $nuevoActivo;

        if (! $eraAdminActivo || $sigueAdminActivo) {
            return;
        }

        $hayOtroAdmin = User::query()
            ->where('id', '!=', $user->id)
            ->where('role', User::ROLE_ADMIN)
            ->where('activo', true)
            ->exists();

        if (! $hayOtroAdmin) {
            throw ValidationException::withMessages([
                'role' => 'Debe existir al menos un administrador activo.',
            ]);
        }
    }
}
