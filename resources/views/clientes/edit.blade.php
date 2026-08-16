@extends('layouts.app')

@section('title', 'Editar Cliente')
@section('page-title', 'Editar Cliente')

@section('content')
<div class="stat-card">
    <form action="{{ route('clientes.update', $cliente) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Nombre -->
            <div class="col-md-6">
                <label for="nombre" class="form-label fw-semibold">
                    <i class="bi bi-person me-1"></i> Nombre <span class="text-danger">*</span>
                </label>
                <input type="text" name="nombre" id="nombre" 
                       class="form-control @error('nombre') is-invalid @enderror" 
                       placeholder="Nombre completo" 
                       value="{{ old('nombre', $cliente->nombre) }}" required>
                @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Documento -->
            <div class="col-md-6">
                <label for="documento" class="form-label fw-semibold">
                    <i class="bi bi-card-text me-1"></i> Documento <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <select name="tipo_documento" id="tipo_documento" 
                            class="form-select @error('tipo_documento') is-invalid @enderror" 
                            style="max-width: 130px;" required>
                        @foreach($tiposDocumento as $tipo)
                            <option value="{{ $tipo }}" 
                                    {{ old('tipo_documento', $cliente->tipo_documento) == $tipo ? 'selected' : '' }}>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="documento" id="documento" 
                           class="form-control @error('documento') is-invalid @enderror" 
                           placeholder="Número de documento" 
                           value="{{ old('documento', $cliente->documento) }}" required>
                </div>
                @error('documento')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">
                    <i class="bi bi-envelope me-1"></i> Email
                </label>
                <input type="email" name="email" id="email" 
                       class="form-control @error('email') is-invalid @enderror" 
                       placeholder="correo@ejemplo.com" 
                       value="{{ old('email', $cliente->email) }}">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Teléfono -->
            <div class="col-md-6">
                <label for="telefono" class="form-label fw-semibold">
                    <i class="bi bi-telephone me-1"></i> Teléfono
                </label>
                <input type="text" name="telefono" id="telefono" 
                       class="form-control @error('telefono') is-invalid @enderror" 
                       placeholder="Número de teléfono" 
                       value="{{ old('telefono', $cliente->telefono) }}">
                @error('telefono')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Dirección -->
            <div class="col-md-12">
                <label for="direccion" class="form-label fw-semibold">
                    <i class="bi bi-geo-alt me-1"></i> Dirección
                </label>
                <input type="text" name="direccion" id="direccion" 
                       class="form-control @error('direccion') is-invalid @enderror" 
                       placeholder="Dirección completa" 
                       value="{{ old('direccion', $cliente->direccion) }}">
                @error('direccion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Contacto -->
            <div class="col-md-6">
                <label for="contacto" class="form-label fw-semibold">
                    <i class="bi bi-person-badge me-1"></i> Persona de Contacto
                </label>
                <input type="text" name="contacto" id="contacto" 
                       class="form-control @error('contacto') is-invalid @enderror" 
                       placeholder="Nombre del contacto" 
                       value="{{ old('contacto', $cliente->contacto) }}">
                @error('contacto')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tipo Cliente -->
            <div class="col-md-6">
                <label for="tipo_cliente" class="form-label fw-semibold">
                    <i class="bi bi-tag me-1"></i> Tipo de Cliente <span class="text-danger">*</span>
                </label>
                <select name="tipo_cliente" id="tipo_cliente" 
                    class="form-select @error('tipo_cliente') is-invalid @enderror" required>
                    <option value="">Selecciona un tipo</option>
                    @foreach($tiposCliente as $tipo)
                        <option value="{{ $tipo }}" 
                                {{ old('tipo_cliente', $cliente->tipo_cliente) == $tipo ? 'selected' : '' }}>
                            {{ $tipo }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_cliente')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Límite de Crédito -->
            <div class="col-md-4">
                <label for="limite_credito" class="form-label fw-semibold">
                    <i class="bi bi-credit-card me-1"></i> Límite de Crédito
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" name="limite_credito" id="limite_credito" 
                           class="form-control @error('limite_credito') is-invalid @enderror" 
                           placeholder="0.00" 
                           value="{{ old('limite_credito', $cliente->limite_credito) }}">
                </div>
                @error('limite_credito')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Días de Crédito -->
            <div class="col-md-4">
                <label for="dias_credito" class="form-label fw-semibold">
                    <i class="bi bi-calendar-range me-1"></i> Días de Crédito
                </label>
                <div class="input-group">
                    <input type="number" name="dias_credito" id="dias_credito" 
                           class="form-control @error('dias_credito') is-invalid @enderror" 
                           placeholder="30" 
                           value="{{ old('dias_credito', $cliente->dias_credito ?? 30) }}">
                    <span class="input-group-text">días</span>
                </div>
                @error('dias_credito')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Activo -->
            <div class="col-md-4">
                <label class="form-label fw-semibold d-block">
                    <i class="bi bi-toggle-on me-1"></i> Estado
                </label>
                <div class="form-check form-switch mt-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo" value="1" 
                           class="form-check-input @error('activo') is-invalid @enderror" 
                           {{ old('activo', $cliente->activo) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activo">
                        <span id="estadoLabel">{{ old('activo', $cliente->activo) ? 'Activo' : 'Inactivo' }}</span>
                    </label>
                </div>
                @error('activo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Notas -->
            <div class="col-12">
                <label for="notas" class="form-label fw-semibold">
                    <i class="bi bi-sticky me-1"></i> Notas
                </label>
                <textarea name="notas" id="notas" rows="3" 
                          class="form-control @error('notas') is-invalid @enderror" 
                          placeholder="Información adicional sobre el cliente...">{{ old('notas', $cliente->notas) }}</textarea>
                @error('notas')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Botones -->
            <div class="col-12">
                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Actualizar Cliente
                    </button>
                    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-info">
                        <i class="bi bi-eye me-1"></i> Ver Cliente
                    </a>
                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
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
        const switchCheckbox = document.getElementById('activo');
        const estadoLabel = document.getElementById('estadoLabel');
        
        if (switchCheckbox) {
            switchCheckbox.addEventListener('change', function() {
                estadoLabel.textContent = this.checked ? 'Activo' : 'Inactivo';
            });
        }

        const form = document.querySelector('.needs-validation');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                this.classList.add('was-validated');
            });
        }
    });
</script>
@endpush
@endsection