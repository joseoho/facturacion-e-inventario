@extends('layouts.app')

@section('title', 'Nueva Tasa de Cambio')
@section('page-title', 'Nueva Tasa de Cambio')

@section('content')
<div class="stat-card">
    <form action="{{ route('tasas.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf

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
                            {{ old('moneda_id', $nuevaTasa->moneda_id ?? '') == $moneda->id ? 'selected' : '' }}>
                            {{ $moneda->nombre }} ({{ $moneda->codigo }})
                        </option>
                    @endforeach
                </select>
                @error('moneda_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tasa -->
            <div class="col-md-6">
                <label for="tasa" class="form-label fw-semibold">
                    <i class="bi bi-arrow-left-right me-1"></i> Tasa de Cambio <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">1 USD =</span>
                    <input type="number" 
                           step="0.000001" 
                           name="tasa" 
                           id="tasa" 
                           class="form-control @error('tasa') is-invalid @enderror" 
                           placeholder="0.000000"
                           value="{{ old('tasa', $nuevaTasa->tasa ?? $tasaActual ?? '') }}" 
                           required>
                    <span class="input-group-text" id="monedaCodigoDisplay">COP</span>
                </div>
                @error('tasa')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Ingresa la tasa de cambio de la moneda seleccionada respecto al USD</small>
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
                       value="{{ old('fecha', $nuevaTasa->fecha ?? now()->format('Y-m-d\TH:i')) }}" 
                       required>
                @error('fecha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Información Adicional -->
            <div class="col-md-6">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Nota:</strong> La tasa de cambio quedará registrada bajo tu usuario.
                    <br>
                    <small class="text-muted">Usuario: {{ Auth::user()->name }}</small>
                </div>
            </div>

            <!-- Botones -->
            <div class="col-12">
                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Guardar Tasa
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
        // Actualizar el código de la moneda al seleccionar
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

            // Disparar evento inicial
            if (monedaSelect.value) {
                monedaSelect.dispatchEvent(new Event('change'));
            }
        }
    });
</script>
@endpush
@endsection