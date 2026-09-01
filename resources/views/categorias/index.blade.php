@extends('layouts.app')

@section('title', 'Categorías')
@section('page-title', 'Categorías')

@section('content')
<div class="stat-card">
    <!-- Filtros y búsqueda -->
    <div class="row mb-4 no-print">
        <div class="col-md-8">
            <form action="{{ route('categorias.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por nombre o descripción..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="activo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('categorias.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                        <i class="bi bi-eraser"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('categorias.create') }}" class="btn btn-success no-print">
                <i class="bi bi-plus-circle me-1"></i> Nueva Categoría
            </a>
            <button type="button" class="btn btn-primary me-2 no-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Tabla de categorías -->
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th class="no-print">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categorias as $categoria)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">{{ $categoria->id }}</span>
                        </td>
                        <td>
                            <strong>{{ $categoria->nombre }}</strong>
                        </td>
                        <td>
                            {{ $categoria->descripcion ? Str::limit($categoria->descripcion, 60) : 'Sin descripción' }}
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $categoria->productos()->count() }}</span>
                        </td>
                        <td>
                            @if($categoria->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('categorias.show', $categoria) }}" class="btn btn-info" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-danger" title="Eliminar"
                                        onclick="confirmDelete('{{ $categoria->id }}', '{{ $categoria->nombre }}')"
                                        {{ $categoria->productos()->count() > 0 ? 'disabled' : '' }}>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            
                            <form id="delete-form-{{ $categoria->id }}" 
                                  action="{{ route('categorias.destroy', $categoria) }}" 
                                  method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block"></i>
                            No hay categorías registradas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <!-- Total de registros para impresión -->
            <div class="print-total">
                <p>Total de categorías: {{ $categorias->total() }}</p>
                <p>Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}</p>
            </div>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-between align-items-center mt-3 no-print">
        <div>
            Mostrando {{ $categorias->firstItem() ?? 0 }} - {{ $categorias->lastItem() ?? 0 }} 
            de {{ $categorias->total() }} categorías
        </div>
        <div>
            {{ $categorias->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id, nombre) {
        if (confirm(`¿Está seguro de eliminar la categoría "${nombre}"?`)) {
            document.getElementById(`delete-form-${id}`).submit();
        }
    }
</script>
<style>
    /* Ocultar en pantalla lo que solo es para impresión */
    .print-header {
        display: none;
    }
    .print-total {
        display: none;
    }

    /* Estilos para impresión */
    @media print {
        /* Ocultar todo lo que tenga clase no-print */
        .no-print {
            display: none !important;
        }
        
        /* Ocultar botones de navegación y encabezado */
        .navbar,
        .navbar-nav,
        .nav,
        .nav-link,
        .navbar-brand,
        .navbar-toggler,
        .btn-group,
        .btn,
        .pagination,
        .card .card-body form,
        .card .card-body .d-flex.gap-2,
        .page-link,
        .page-item,
        .breadcrumb,
        .header,
        .main-header,
        .app-header,
        .top-nav,
        .navigation,
        .menu,
        .sidebar,
        .nav-menu,
        .header-menu,
        .top-menu,
        .main-nav,
        .site-header,
        .page-header {
            display: none !important;
        }
        
        /* Mostrar encabezado de impresión */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-total {
            display: block !important;
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
        }
        
        /* Mostrar solo la tabla */
        .table-responsive {
            overflow: visible !important;
        }
        
        .table {
            width: 100% !important;
            font-size: 12px !important;
        }
        
        .table-bordered {
            border: 1px solid #000 !important;
        }
        
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
        
        /* Fondo blanco */
        body {
            background: white !important;
        }
        
        .stat-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        
        .card-body {
            padding: 0 !important;
        }
        
        /* Ocultar columna de acciones */
        .table thead tr th:last-child,
        .table tbody tr td:last-child {
            display: none !important;
        }
    }
</style>
@endpush
@endsection