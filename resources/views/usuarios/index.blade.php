@extends('layouts.app')

@section('title', 'Usuarios')
@section('page-title', 'Usuarios')

@section('content')
<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="mb-0">Usuarios</h5>
            <small class="text-muted">Alta, rol, estado y reseteo de contraseña.</small>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn btn-success">
            <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
        </a>
    </div>

    <form action="{{ route('usuarios.index') }}" method="GET" class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o correo..."
                   value="{{ request('q') }}">
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select">
                <option value="">Todos los roles</option>
                <option value="admin" @selected(request('role') === 'admin')>Administrador</option>
                <option value="vendedor" @selected(request('role') === 'vendedor')>Vendedor</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="activo" class="form-select">
                <option value="">Todos</option>
                <option value="1" @selected(request('activo') === '1')>Activos</option>
                <option value="0" @selected(request('activo') === '0')>Inactivos</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Filtrar</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary"><i class="bi bi-eraser me-1"></i> Limpiar</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Alta</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <strong>{{ $u->name }}</strong>
                            @if($u->is(auth()->user()))
                                <span class="badge bg-info ms-1">Tú</span>
                            @endif
                        </td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @if($u->isAdmin())
                                <span class="badge bg-primary">Administrador</span>
                            @else
                                <span class="badge bg-secondary">Vendedor</span>
                            @endif
                        </td>
                        <td>
                            @if($u->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>{{ $u->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-warning" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>

                            @if($u->is(auth()->user()))
                                <button class="btn btn-sm btn-outline-secondary" disabled title="No puedes desactivar tu propia cuenta">
                                    <i class="bi bi-person-x"></i>
                                </button>
                            @else
                                <form action="{{ route('usuarios.toggle', $u) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ $u->activo ? '¿Desactivar' : '¿Activar' }} este usuario?')">
                                    @csrf
                                    <button class="btn btn-sm {{ $u->activo ? 'btn-danger' : 'btn-success' }}"
                                            title="{{ $u->activo ? 'Desactivar' : 'Activar' }}">
                                        <i class="bi {{ $u->activo ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios con los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">{{ $users->total() }} usuario(s)</div>
        <div>{{ $users->links() }}</div>
    </div>
</div>
@endsection
