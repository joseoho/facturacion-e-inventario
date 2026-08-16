@extends('layouts.app')

@section('title', 'Detalles del Producto')
@section('page-title', 'Detalles del Producto')

@section('content')
<div class="stat-card">
    <div class="row">
        <div class="col-md-4 text-center">
            @if($producto->imagen)
                <img src="{{ asset('storage/' . $producto->imagen) }}" 
                     alt="{{ $producto->nombre }}" 
                     class="img-fluid rounded" 
                     style="max-height: 300px;">
            @else
                <div class="bg-light rounded p-5">
                    <i class="bi bi-box fs-1 text-muted"></i>
                    <p class="text-muted">Sin imagen</p>
                </div>
            @endif
        </div>
        <div class="col-md-8">
            <h3>{{ $producto->nombre }}</h3>
            <p class="text-muted">{{ $producto->descripcion }}</p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">SKU:</dt>
                        <dd class="col-sm-8"><span class="badge bg-secondary">{{ $producto->sku }}</span></dd>

                        <dt class="col-sm-4">Categoría:</dt>
                        <dd class="col-sm-8">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</dd>

                        <dt class="col-sm-4">Precio USD:</dt>
                        <dd class="col-sm-8">$ {{ number_format($producto->precio_kg_usd, 2) }}</dd>

                        <dt class="col-sm-4">IVA:</dt>
                        <dd class="col-sm-8">{{ number_format($producto->iva_porcentaje, 2) }}%</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Stock:</dt>
                        <dd class="col-sm-8">
                            <span class="{{ $producto->stock_kg < $producto->stock_minimo ? 'text-danger' : '' }}">
                                {{ number_format($producto->stock_kg, 3) }} KG
                            </span>
                            @if($producto->stock_kg < $producto->stock_minimo)
                                <i class="bi bi-exclamation-triangle text-danger" title="Stock bajo"></i>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Stock Mínimo:</dt>
                        <dd class="col-sm-8">{{ number_format($producto->stock_minimo, 3) }} KG</dd>

                        <dt class="col-sm-4">Estado:</dt>
                        <dd class="col-sm-8">
                            @if($producto->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Creado:</dt>
                        <dd class="col-sm-8">{{ $producto->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Precios en otras monedas -->
    @if($producto->preciosProductos->isNotEmpty())
        <div class="mt-4">
            <h5>Precios en otras monedas</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Moneda</th>
                            <th>Precio por KG</th>
                            <th>Tasa de Cambio</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($producto->preciosProductos as $precio)
                            <tr>
                                <td>{{ $precio->moneda->codigo }} - {{ $precio->moneda->nombre }}</td>
                                <td>{{ number_format($precio->precio_kg, 4) }}</td>
                                <td>{{ $precio->tasaCambio->tasa ?? 'N/A' }}</td>
                                <td>{{ $precio->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        <a href="{{ route('productos.precios', $producto) }}" class="btn btn-primary">
            <i class="bi bi-currency-dollar me-1"></i> Gestionar Precios
        </a>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>
@endsection