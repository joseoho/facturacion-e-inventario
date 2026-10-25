@extends('layouts.app')

@section('title', 'Nuevo Egreso')
@section('page-title', 'Nuevo Egreso')

@section('content')
<div class="stat-card">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('egresos.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fecha <span class="text-danger">*</span></label>
                <input type="date" name="fecha_egreso" class="form-control" required
                       value="{{ old('fecha_egreso', now()->toDateString()) }}">
            </div>

            <div class="col-md-5">
                <label class="form-label">Categoría <span class="text-danger">*</span></label>
                <select name="categoria_egreso_id" id="categoria_egreso_id" class="form-select" required>
                    <option value="">Seleccionar...</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}"
                                data-afecta="{{ $categoria->afecta_inventario ? '1' : '0' }}"
                                @selected(old('categoria_egreso_id') == $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Proveedor</label>
                <input type="text" name="proveedor_nombre" class="form-control" maxlength="200"
                       placeholder="Nombre del proveedor (opcional)"
                       value="{{ old('proveedor_nombre') }}">
            </div>

            <div class="col-12">
                <label class="form-label">Concepto <span class="text-danger">*</span></label>
                <input type="text" name="concepto" class="form-control" required maxlength="1000"
                       placeholder="Ej: Compra de 20 kg de café, pago de luz, sueldos..."
                       value="{{ old('concepto') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Moneda de pago <span class="text-danger">*</span></label>
                <select name="moneda_id" class="form-select" required>
                    <option value="">Seleccionar...</option>
                    @foreach($monedas as $moneda)
                        <option value="{{ $moneda->id }}" @selected(old('moneda_id') == $moneda->id)>
                            {{ $moneda->codigo }} — {{ $moneda->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Monto <span class="text-danger">*</span></label>
                <input type="number" name="monto" class="form-control" step="0.0001" min="0.0001" required
                       value="{{ old('monto') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Método de pago</label>
                <select name="metodo_pago" class="form-select">
                    <option value="">—</option>
                    @foreach($metodosPago as $valor => $texto)
                        <option value="{{ $valor }}" @selected(old('metodo_pago') === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Referencia</label>
                <input type="text" name="referencia_pago" class="form-control" maxlength="100"
                       placeholder="Nro. de transferencia, cheque..."
                       value="{{ old('referencia_pago') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Estado inicial <span class="text-danger">*</span></label>
                <select name="estado" class="form-select" required>
                    <option value="pagado" @selected(old('estado', 'pagado') === 'pagado')>Pagado</option>
                    <option value="pendiente" @selected(old('estado') === 'pendiente')>Pendiente</option>
                </select>
                <div class="form-text">Si lo marcas como pagado se guarda la fecha de pago de hoy.</div>
            </div>

            <div class="col-md-8" id="inventario" style="display:none;">
                <div class="row g-3 border rounded p-3">
                    <div class="col-12">
                        <strong class="text-muted small">Esta categoría afecta inventario: indica qué producto entra.</strong>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Producto afectado <span class="text-danger">*</span></label>
                        <select name="producto_id" class="form-select">
                            <option value="">Seleccionar producto...</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>
                                    {{ $producto->nombre }} ({{ $producto->sku }}) — {{ $producto->stock_kg }} Kg
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Cantidad a sumar (Kg) <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad_kg" class="form-control" step="0.001" min="0.001"
                               value="{{ old('cantidad_kg') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i> Registrar egreso
            </button>
            <a href="{{ route('egresos.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const categoria = document.getElementById('categoria_egreso_id');
        const bloque = document.getElementById('inventario');

        function actualizar() {
            const opcion = categoria.selectedOptions[0];
            const afecta = opcion && opcion.dataset.afecta === '1';
            bloque.style.display = afecta ? 'block' : 'none';
        }

        categoria.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
@endpush
