@php
    $esEdicion = $user->exists;
    $action = $esEdicion ? route('usuarios.update', $user) : route('usuarios.store');
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST">
    @csrf
    @if($esEdicion)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $user->name) }}" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Correo <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email) }}" required maxlength="255">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Rol <span class="text-danger">*</span></label>
            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                <option value="vendedor" @selected(old('role', $user->role) === 'vendedor')>Vendedor</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrador</option>
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                       @checked(old('activo', $user->activo ?? true))>
                <label class="form-check-label" for="activo">Usuario activo</label>
            </div>
        </div>

        <div class="col-12"><hr class="my-1"></div>

        <div class="col-md-4">
            <label class="form-label">
                Contraseña {{ $esEdicion ? '(opcional)' : '' }}
                @unless($esEdicion)<span class="text-danger">*</span>@endunless
            </label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   autocomplete="new-password" @required(! $esEdicion)>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">
                Mínimo 8 caracteres, con letras y números.
                @if($esEdicion) Déjala en blanco para conservar la actual. @endif
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label">Repetir contraseña</label>
            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i> {{ $esEdicion ? 'Guardar cambios' : 'Crear usuario' }}
        </button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
