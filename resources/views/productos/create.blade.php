@extends('layouts.app')

@section('title', 'Nuevo Producto')
@section('page-title', 'Nuevo Producto')

@section('content')
<div class="stat-card">
    <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-6">
                <!-- SKU -->
                <div class="mb-3">
                    <label for="sku" class="form-label">SKU *</label>
                    <input type="text" class="form-control @error('sku') is-invalid @enderror" 
                           id="sku" name="sku" value="{{ old('sku') }}" required>
                    @error('sku')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Nombre -->
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                           id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                              id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <!-- Imagen -->
                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen</label>
                    <input type="file" class="form-control @error('imagen') is-invalid @enderror" 
                           id="imagen" name="imagen" accept="image/*">
                    @error('imagen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB</small>
                </div>

                <!-- Categoría -->
                <div class="mb-3">
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select class="form-select @error('categoria_id') is-invalid @enderror" 
                            id="categoria_id" name="categoria_id">
                        <option value="">Seleccionar categoría</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoria_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Precio USD -->
                <div class="mb-3">
                    <label for="precio_kg_usd" class="form-label">Precio por KG (USD) *</label>
                    <input type="number" step="0.0001" class="form-control @error('precio_kg_usd') is-invalid @enderror" 
                           id="precio_kg_usd" name="precio_kg_usd" value="{{ old('precio_kg_usd') }}" required>
                    @error('precio_kg_usd')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Stock -->
                <div class="mb-3">
                    <label for="stock_kg" class="form-label">Stock (KG) *</label>
                    <input type="number" step="0.001" class="form-control @error('stock_kg') is-invalid @enderror" 
                           id="stock_kg" name="stock_kg" value="{{ old('stock_kg') }}" required>
                    @error('stock_kg')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <!-- IVA -->
                <div class="mb-3">
                    <label for="iva_porcentaje" class="form-label">IVA (%) *</label>
                    <input type="number" step="0.01" class="form-control @error('iva_porcentaje') is-invalid @enderror" 
                           id="iva_porcentaje" name="iva_porcentaje" value="{{ old('iva_porcentaje', 16) }}" required>
                    @error('iva_porcentaje')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <!-- Stock Mínimo -->
                <div class="mb-3">
                    <label for="stock_minimo" class="form-label">Stock Mínimo (KG)</label>
                    <input type="number" step="0.001" class="form-control @error('stock_minimo') is-invalid @enderror" 
                           id="stock_minimo" name="stock_minimo" value="{{ old('stock_minimo', 0) }}">
                    @error('stock_minimo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <!-- Activo -->
                <div class="mb-3 mt-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="activo">Producto Activo</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Precio en moneda local (opcional) -->
        <div class="row mt-3">
            <div class="col-12">
                <h5>Precio en Moneda Local (Opcional)</h5>
                <hr>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="moneda_id" class="form-label">Moneda</label>
                    <select class="form-select" id="moneda_id" name="moneda_id">
                        <option value="">Seleccionar moneda</option>
                        @foreach($monedas as $moneda)
                            <option value="{{ $moneda->id }}">{{ $moneda->codigo }} - {{ $moneda->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- <div class="col-md-4">
                <div class="mb-3">
                    <label for="precio_kg_local" class="form-label">Precio por KG (Local)</label>
                    <input type="number" step="0.0001" class="form-control" 
                           id="precio_kg_local" name="precio_kg_local" value="{{ old('precio_kg_local') }}">
                </div>
            </div> --}}
            {{-- <div class="col-md-4">
                <div class="mb-3">
                    <label for="tasa_cambio_id" class="form-label">Tasa de Cambio</label>
                    <select class="form-select" id="tasa_cambio_id" name="tasa_cambio_id">
                        <option value="">Seleccionar tasa</option>
                        @foreach($tasasCambio ?? [] as $tasa)
                            <option value="{{ $tasa->id }}">{{ $tasa->fecha }} - {{ $tasa->tasa }}</option>
                        @endforeach
                    </select>
                </div> --}}
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Guardar Producto
            </button>
            <a href="{{ route('productos.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>
@endsection