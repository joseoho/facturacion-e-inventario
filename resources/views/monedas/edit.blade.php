@extends('layouts.app')

@section('title', 'Editar Moneda')
@section('page-title', 'Editar Moneda')

@section('content')
<div class="stat-card">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Editar Moneda: {{ $moneda->nombre }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('monedas.update', $moneda) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="codigo" class="form-label">Código *</label>
                                <input type="text" 
                                       class="form-control @error('codigo') is-invalid @enderror" 
                                       id="codigo" 
                                       name="codigo" 
                                       value="{{ old('codigo', $moneda->codigo) }}"
                                       maxlength="3"
                                       placeholder="Ej: USD, EUR, COP"
                                       required>
                                @error('codigo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" 
                                       class="form-control @error('nombre') is-invalid @enderror" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="{{ old('nombre', $moneda->nombre) }}"
                                       placeholder="Ej: Dólar Estadounidense"
                                       required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="simbolo" class="form-label">Símbolo *</label>
                                <input type="text" 
                                       class="form-control @error('simbolo') is-invalid @enderror" 
                                       id="simbolo" 
                                       name="simbolo" 
                                       value="{{ old('simbolo', $moneda->simbolo) }}"
                                       placeholder="Ej: $, €, ₡"
                                       required>
                                @error('simbolo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="es_base" 
                                           name="es_base"
                                           value="1"
                                           {{ old('es_base', $moneda->es_base) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="es_base">
                                        Moneda Base
                                    </label>
                                    <small class="text-muted d-block">
                                        La moneda base se usará como referencia para las tasas de cambio
                                    </small>
                                    @error('es_base')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="activa" 
                                           name="activa"
                                           value="1"
                                           {{ old('activa', $moneda->activa) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activa">
                                        Activa
                                    </label>
                                    <small class="text-muted d-block">
                                        Solo las monedas activas pueden ser utilizadas
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('monedas.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Actualizar Moneda
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection