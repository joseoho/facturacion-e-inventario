@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('content')
<div class="stat-card">
    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="{{ route('clientes.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por nombre, documento o email..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="tipo_cliente" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los Tipos</option>
                        <option value="J" {{ request('tipo_cliente') === 'J' ? 'selected' : '' }}>JURIDICO</option>
                        <option value="V" {{ request('tipo_cliente') === 'V' ? 'selected' : '' }}>NATURAL</option>
                        <option value="G" {{ request('tipo_cliente') === 'G' ? 'selected' : '' }}>GUBERNAMENTAL</option>
                        <option value="E" {{ request('tipo_cliente') === 'E' ? 'selected' : '' }}>EXTRANJERO</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="activo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                        <i class="bi bi-eraser"></i>
                    </a>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('clientes.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Cliente
            </a>
        </div>
    </div>

    <!-- Tabla de clientes -->
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                    <tr>
                        <td>{{ $cliente->id }}</td>
                        <td>
                            <strong>{{ $cliente->nombre }}</strong>
                            @if($cliente->contacto)
                                <br><small class="text-muted">Contacto: {{ $cliente->contacto }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $cliente->tipo_documento }}: {{ $cliente->documento }}
                        </td>
                        <td>{{ $cliente->email ?? '-' }}</td>
                        <td>{{ $cliente->telefono ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $cliente->tipo_cliente }}</span>
                        </td>
                        <td>
                            @if($cliente->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-info" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-danger" title="Eliminar"
                                        onclick="confirmDelete('{{ $cliente->id }}', '{{ $cliente->nombre }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            
                            <form id="delete-form-{{ $cliente->id }}" 
                                  action="{{ route('clientes.destroy', $cliente) }}" 
                                  method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block"></i>
                            No hay clientes registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Mostrando {{ $clientes->firstItem() ?? 0 }} - {{ $clientes->lastItem() ?? 0 }} 
            de {{ $clientes->total() }} clientes
        </div>
        <div>
            {{ $clientes->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id, nombre) {
        if (confirm(`¿Está seguro de eliminar el cliente "${nombre}"?`)) {
            document.getElementById(`delete-form-${id}`).submit();
        }
    }
</script>
@endpush
@endsection