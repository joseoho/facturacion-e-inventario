@extends('layouts.app')

@section('title', 'Detalles de Categoría')
@section('page-title', 'Detalles de Categoría')

@section('content')
<div class="stat-card">
    <div class="card-body">
        <!-- Información de la categoría -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Información de la Categoría</h5>
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 150px;">ID</th>
                        <td>{{ $categoria->id }}</td>
                    </tr>
                    <tr>
                        <th>Nombre</th>
                        <td><strong>{{ $categoria->nombre }}</strong></td>
                    </tr>
                    <tr>
                        <th>Descripción</th>
                        <td>{{ $categoria->descripcion ?? 'Sin descripción' }}</td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td>
                            @if($categoria->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Total Productos</th>
                        <td>
                            <span class="badge bg-info">{{ $categoria->productos()->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Fecha Creación</th>
                        <td>{{ $categoria->created_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Última Actualización</th>
                        <td>{{ $categoria->updated_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Productos de la categoría -->
        <div class="row">
            <div class="col-12">
                <h5>Productos en esta Categoría</h5>
                @if($productos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Nombre</th>
                                    <th>Stock (Kg)</th>
                                    <th>Precio USD</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productos as $producto)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $producto->sku }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $producto->nombre }}</strong>
                                        </td>
                                        <td>
                                            {{ number_format($producto->stock_kg, 3) }}
                                            @if($producto->stock_kg < $producto->stock_minimo)
                                                <i class="bi bi-exclamation-triangle text-danger" title="Stock bajo"></i>
                                            @endif
                                        </td>
                                        <td>$ {{ number_format($producto->precio_kg_usd, 2) }}</td>
                                        <td>
                                            @if($producto->activo)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('productos.show', $producto) }}" 
                                               class="btn btn-sm btn-info" title="Ver">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $productos->links() }}
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block"></i>
                        No hay productos en esta categoría
                    </div>
                @endif
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Editar Categoría
                </a>
                <a href="{{ route('categorias.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Listado
                </a>
            </div>
        </div>
    </div>
</div>
@endsection