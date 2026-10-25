@extends('layouts.app')

@section('title', 'Cambiar contraseña')
@section('page-title', 'Cambiar contraseña')

@section('content')
<div class="stat-card" style="max-width: 640px;">
    <h5 class="fw-bold mb-1">Cambiar contraseña</h5>
    <p class="text-muted small mb-4">
        Usuario: <strong>{{ $user->email }}</strong>
    </p>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('perfil.password') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="current_password" class="form-label">Contraseña actual <span class="text-danger">*</span></label>
            <input type="password" name="current_password" id="current_password"
                   class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Nueva contraseña <span class="text-danger">*</span></label>
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Repetir nueva contraseña <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="form-control" required autocomplete="new-password">
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i> Guardar contraseña
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
