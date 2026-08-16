@extends('layouts.app')

@section('title', 'Nueva Categoría')
@section('page-title', 'Nueva Categoría')

@section('content')
<div class="stat-card">
    <div class="card-body">
        <form action="{{ route('categorias.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('nombre') is-invalid @enderror" 
                           id="nombre" 
                           name="nombre" 
                           value="{{ old('nombre') }}" 
                           required 
                           maxlength="100">
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-check mt-4 pt-2">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="activo" 
                               name="activo" 
                               value="1"
                               {{ old('activo', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="activo">
                            Categoría Activa
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                              id="descripcion" 
                              name="descripcion" 
                              rows="4"
                              maxlength="500">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Guardar Categoría
                    </button>
                    <a href="{{ route('categorias.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection