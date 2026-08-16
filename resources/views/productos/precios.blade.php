@extends('layouts.app')

@section('title', 'Precios del Producto')
@section('page-title', 'Precios del Producto')

@section('content')
<div class="stat-card">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4>{{ $producto->nombre }}</h4>
            <p class="text-muted">SKU: {{ $producto->sku }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('productos.show', $producto) }}" class="btn btn-info">
                <i class="bi bi-eye me-1"></i> Ver Producto
            </a>
            <a href="{{ route('productos.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Precios existentes -->
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Moneda</th>
                    <th>Precio por KG</th>
                    <th>Tasa de Cambio</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($producto->preciosProductos as $precio)
                    <tr>
                        <td>{{ $precio->moneda->codigo }} - {{ $precio->moneda->nombre }}</td>
                        <td>{{ number_format($precio->precio_kg, 4) }}</td>
                        <td>{{ $precio->tasaCambio->tasa ?? 'N/A' }}</td>
                        <td>{{ $precio->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="confirmDeletePrice('{{ $precio->id }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <form id="delete-price-form-{{ $precio->id }}" 
                                  action="{{ route('productos.precios.destroy', $precio) }}" 
                                  method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block"></i>
                            No hay precios adicionales registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Formulario para agregar nuevo precio -->
    <div class="mt-4 p-3 bg-light rounded">
        <h5>Agregar Precio en otra Moneda</h5>
        <form action="{{ route('productos.precios.store', $producto) }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="moneda_id" class="form-label">Moneda *</label>
                    <select class="form-select @error('moneda_id') is-invalid @enderror" 
                            id="moneda_id" name="moneda_id" required>
                        <option value="">Seleccionar moneda</option>
                        @foreach($monedas as $moneda)
                            <option value="{{ $moneda->id }}">{{ $moneda->codigo }} - {{ $moneda->nombre }}</option>
                        @endforeach
                    </select>
                    @error('moneda_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="precio_kg" class="form-label">Precio por KG *</label>
                    <input type="number" step="0.0001" class="form-control @error('precio_kg') is-invalid @enderror" 
                           id="precio_kg" name="precio_kg" required>
                    @error('precio_kg')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="tasa_cambio_id" class="form-label">Tasa de Cambio *</label>
                    <select class="form-select @error('tasa_cambio_id') is-invalid @enderror" 
                            id="tasa_cambio_id" name="tasa_cambio_id" required>
                        <option value="">Seleccionar tasa</option>
                        @foreach($tasasCambio as $tasa)
                            <option value="{{ $tasa->id }}">{{ $tasa->fecha }} - {{ $tasa->tasa }}</option>
                        @endforeach
                    </select>
                    @error('tasa_cambio_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i> Agregar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function confirmDeletePrice(id) {
        if (confirm('¿Está seguro de eliminar este precio?')) {
            document.getElementById(`delete-price-form-${id}`).submit();
        }
    }
</script>
@endpush
@endsection