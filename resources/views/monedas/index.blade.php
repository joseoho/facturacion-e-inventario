@extends('layouts.app')

@section('title', 'Monedas')
@section('page-title', 'Monedas')

@section('content')
<div class="stat-card">
    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="{{ route('monedas.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por código, nombre o símbolo..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="activa" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="1" {{ request('activa') === '1' ? 'selected' : '' }}>Activas</option>
                        <option value="0" {{ request('activa') === '0' ? 'selected' : '' }}>Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('monedas.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                        <i class="bi bi-eraser"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('monedas.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Nueva Moneda
            </a>
        </div>
    </div>

    <!-- Tabla de monedas -->
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Símbolo</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monedas as $moneda)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">{{ $moneda->id }}</span>
                        </td>
                        <td>
                            <strong>{{ $moneda->codigo }}</strong>
                        </td>
                        <td>
                            {{ $moneda->nombre }}
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $moneda->simbolo }}</span>
                        </td>
                        <td>
                            @if($moneda->es_base)
                                <span class="badge bg-warning text-dark">Base</span>
                            @else
                                <span class="badge bg-secondary">Secundaria</span>
                            @endif
                        </td>
                        <td>
                            @if($moneda->activa)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-danger">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('monedas.show', $moneda) }}" class="btn btn-info" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('monedas.edit', $moneda) }}" class="btn btn-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-danger" title="Eliminar"
                                        onclick="confirmDelete('{{ $moneda->id }}', '{{ $moneda->nombre }}')"
                                        {{ $moneda->es_base || $moneda->facturas()->exists() || $moneda->preciosProductos()->exists() ? 'disabled' : '' }}>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            
                            <form id="delete-form-{{ $moneda->id }}" 
                                  action="{{ route('monedas.destroy', $moneda) }}" 
                                  method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block"></i>
                            No hay monedas registradas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Mostrando {{ $monedas->firstItem() ?? 0 }} - {{ $monedas->lastItem() ?? 0 }} 
            de {{ $monedas->total() }} monedas
        </div>
        <div>
            {{ $monedas->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id, nombre) {
        if (confirm(`¿Está seguro de eliminar la moneda "${nombre}"?`)) {
            document.getElementById(`delete-form-${id}`).submit();
        }
    }
</script>
@endpush
@endsection