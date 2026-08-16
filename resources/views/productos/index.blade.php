@extends('layouts.app')

@section('title', 'Productos')
@section('page-title', 'Productos')

@section('content')
<div class="stat-card">
    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="{{ route('productos.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por nombre, SKU o descripción..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="categoria_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas las Categorías</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="activo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="stock_bajo" value="1" 
                               class="form-check-input" id="stockBajo"
                               {{ request('stock_bajo') ? 'checked' : '' }}
                               onchange="this.form.submit()">
                        <label class="form-check-label" for="stockBajo">
                            Stock Bajo
                        </label>
                    </div>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('productos.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                        <i class="bi bi-eraser"></i>
                    </a>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('productos.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Stock (Kg)</th>
                    <th>Precio USD</th>
                    <th>IVA</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $producto)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">{{ $producto->sku }}</span>
                        </td>
                        <td>
                            <strong>{{ $producto->nombre }}</strong>
                            @if($producto->descripcion)
                                <br><small class="text-muted">{{ Str::limit($producto->descripcion, 50) }}</small>
                            @endif
                        </td>
                        <td>{{ $producto->categoria->nombre ?? 'Sin categoría' }}</td>
                        <td>
                            <span class="{{ $producto->stock_kg < $producto->stock_minimo ? 'text-danger' : '' }}">
                                {{ number_format($producto->stock_kg, 3) }}
                            </span>
                            @if($producto->stock_kg < $producto->stock_minimo)
                                <i class="bi bi-exclamation-triangle text-danger" title="Stock bajo"></i>
                            @endif
                        </td>
                        <td>$ {{ number_format($producto->precio_kg_usd, 2) }}</td>
                        <td>{{ number_format($producto->iva_porcentaje, 2) }}%</td>
                        <td>
                            @if($producto->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('productos.show', $producto) }}" class="btn btn-info" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('productos.precios', $producto) }}" class="btn btn-primary" title="Precios">
                                    <i class="bi bi-currency-dollar"></i>
                                </a>
                                <button type="button" class="btn btn-danger" title="Eliminar"
                                        onclick="confirmDelete('{{ $producto->id }}', '{{ $producto->nombre }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            
                            <form id="delete-form-{{ $producto->id }}" 
                                  action="{{ route('productos.destroy', $producto) }}" 
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
                            No hay productos registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} 
            de {{ $productos->total() }} productos
        </div>
        <div>
            {{ $productos->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id, nombre) {
        if (confirm(`¿Está seguro de eliminar el producto "${nombre}"?`)) {
            document.getElementById(`delete-form-${id}`).submit();
        }
    }
</script>
@endpush
@endsection