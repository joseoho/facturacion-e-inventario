@extends('layouts.app')

@section('title', 'Categorías de Egreso')
@section('page-title', 'Categorías de Egreso')

@section('content')
<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0">
            Define si una categoría ingresa mercancía al inventario.
        </p>
        <a href="{{ route('categorias-egreso.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Nueva Categoría
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="text-center">Afecta inventario</th>
                    <th class="text-center">Egresos</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categorias as $categoria)
                    <tr>
                        <td><strong>{{ $categoria->nombre }}</strong></td>
                        <td>{{ $categoria->descripcion ?: '—' }}</td>
                        <td class="text-center">
                            @if($categoria->afecta_inventario)
                                <span class="badge bg-info">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $categoria->egresos_count }}</span></td>
                        <td>
                            @if($categoria->activo)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('categorias-egreso.edit', $categoria) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('categorias-egreso.destroy', $categoria) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar esta categoría?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay categorías registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">{{ $categorias->total() }} categoría(s)</div>
        <div>{{ $categorias->links() }}</div>
    </div>
</div>
@endsection
