@php
    $esEdicion = isset($categoria) && $categoria->exists;
    $action = $esEdicion ? route('categorias-egreso.update', $categoria) : route('categorias-egreso.store');
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if($esEdicion)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                   value="{{ old('nombre', $categoria->nombre) }}" required maxlength="100">
            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label">Descripción</label>
            <input type="text" name="descripcion" class="form-control"
                   value="{{ old('descripcion', $categoria->descripcion) }}" maxlength="500">
        </div>

        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="afecta_inventario" id="afecta_inventario" value="1"
                       @checked(old('afecta_inventario', $categoria->afecta_inventario))>
                <label class="form-check-label" for="afecta_inventario">
                    Afecta inventario (ingresa mercancía)
                </label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                       @checked(old('activo', $categoria->activo ?? true))>
                <label class="form-check-label" for="activo">Activa</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i> {{ $esEdicion ? 'Actualizar' : 'Guardar' }}
        </button>
        <a href="{{ route('categorias-egreso.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
