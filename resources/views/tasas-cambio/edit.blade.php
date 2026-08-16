@extends('layouts.app')

@section('title', 'Editar tasaCambio de Cambio')
@section('page-title', 'Editar tasaCambio de Cambio')

@section('content')
<div class="stat-card">
    <form action="{{ route('tasas.update', $tasaCambio) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Moneda -->
            <div class="col-md-6">
                <label for="moneda_id" class="form-label fw-semibold">
                    <i class="bi bi-coin me-1"></i> Moneda <span class="text-danger">*</span>
                </label>
                <select name="moneda_id" id="moneda_id" class="form-select @error('moneda_id') is-invalid @enderror" required>
                    <option value="">Selecciona una moneda</option>
                    @foreach($monedas as $moneda)
                        <option value="{{ $moneda->id }}" 
                            {{ old('moneda_id', $tasaCambio->moneda_id) == $moneda->id ? 'selected' : '' }}>
                            {{ $moneda->nombre }} ({{ $moneda->codigo }})
                        </option>
                    @endforeach
                </select>
                @error('moneda_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- tasaCambio -->
            <div class="col-md-6">
                <label for="tasaCambio" class="form-label fw-semibold">
                    <i class="bi bi-arrow-left-right me-1"></i> tasaCambio de Cambio <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">1 USD =</span>
                    <input type="number" 
                           step="0.000001" 
                           name="tasaCambio" 
                           id="tasaCambio" 
                           class="form-control @error('tasaCambio') is-invalid @enderror" 
                           placeholder="0.000000"
                           value="{{ old('tasaCambio', $tasaCambio->tasaCambio) }}" 
                           required>
                    <span class="input-group-text" id="monedaCodigoDisplay">
                        {{ $tasaCambio->moneda->codigo }}
                    </span>
                </div>
                @error('tasaCambio')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Ingresa la tasaCambio de cambio de la moneda seleccionada respecto al USD</small>
            </div>

            <!-- Fecha -->
            <div class="col-md-6">
                <label for="fecha" class="form-label fw-semibold">
                    <i class="bi bi-calendar3 me-1"></i> Fecha <span class="text-danger">*</span>
                </label>
                <input type="datetime-local" 
                       name="fecha" 
                       id="fecha" 
                       class="form-control @error('fecha') is-invalid @enderror" 
                       value="{{ old('fecha', $tasaCambio->fecha->format('Y-m-d\TH:i')) }}" 
                       required>
                @error('fecha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Información -->
            <div class="col-md-6">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Información:</strong>
                    <br>
                    <small>
                        Registrado por: {{ $tasaCambio->user ? $tasaCambio->user->name : 'N/A' }}
                        <br>
                        Fecha registro: {{ $tasaCambio->created_at->format('d/m/Y H:i') }}
                    </small>
                </div>
            </div>

            <!-- Botones -->
            <div class="col-12">
                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-pencil me-1"></i> Actualizar tasaCambio
                    </button>
                    <a href="{{ route('tasas.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monedaSelect = document.getElementById('moneda_id');
        const codigoDisplay = document.getElementById('monedaCodigoDisplay');
        
        if (monedaSelect) {
            monedaSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const codigo = selectedOption.text.match(/\(([^)]+)\)/);
                if (codigo) {
                    codigoDisplay.textContent = codigo[1];
                } else {
                    codigoDisplay.textContent = 'MON';
                }
            });
        }
    });
</script>
@endpush
@endsection