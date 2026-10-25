@extends('layouts.app')

@section('title', 'Egresos')
@section('page-title', 'Egresos')

@section('content')
<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div>
            <h5 class="mb-0">Egresos</h5>
            <small class="text-muted">Un egreso = un concepto. Si necesitas varios, registra varios egresos.</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir
            </button>
            <a href="{{ route('egresos.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Egreso
            </a>
        </div>
    </div>

    <form action="{{ route('egresos.index') }}" method="GET" class="row g-2 mb-4 no-print">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control" placeholder="Número, concepto, referencia o proveedor"
                   value="{{ request('q') }}">
        </div>
        <div class="col-md-2">
            <select name="categoria_egreso_id" class="form-select">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(request('categoria_egreso_id') == $categoria->id)>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendientes</option>
                <option value="pagado" @selected(request('estado') === 'pagado')>Pagados</option>
                <option value="anulado" @selected(request('estado') === 'anulado')>Anulados</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="moneda_id" class="form-select">
                <option value="">Todas las monedas</option>
                @foreach($monedas as $moneda)
                    <option value="{{ $moneda->id }}" @selected(request('moneda_id') == $moneda->id)>
                        {{ $moneda->codigo }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <input type="date" name="desde" class="form-control" value="{{ request('desde') }}" title="Desde">
            <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}" title="Hasta">
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Filtrar</button>
            <a href="{{ route('egresos.index') }}" class="btn btn-secondary"><i class="bi bi-eraser me-1"></i> Limpiar</a>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="border rounded p-3">
                <div class="text-muted small">Egresos (filtro)</div>
                <div class="fs-5 fw-bold">{{ number_format((int) $totales->cantidad, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3">
                <div class="text-muted small">Total (USD)</div>
                <div class="fs-5 fw-bold">{{ number_format((float) $totales->total_usd, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Proveedor</th>
                    <th>Concepto</th>
                    <th>Moneda</th>
                    <th class="text-end">Monto</th>
                    <th>Estado</th>
                    <th class="text-end no-print">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($egresos as $egreso)
                    <tr>
                        <td><strong>{{ $egreso->numero }}</strong></td>
                        <td>{{ $egreso->fecha_formateada }}</td>
                        <td>{{ $egreso->categoria->nombre ?? '—' }}</td>
                        <td>{{ $egreso->proveedor_nombre ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($egreso->concepto, 45) }}</td>
                        <td>{{ $egreso->moneda_pago }}</td>
                        <td class="text-end">{{ number_format((float) $egreso->monto, 2, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-{{ $egreso->estado === 'pagado' ? 'success' : ($egreso->estado === 'anulado' ? 'danger' : 'warning') }}">
                                {{ $egreso->estado_texto }}
                            </span>
                        </td>
                        <td class="text-end no-print">
                            <a href="{{ route('egresos.show', $egreso) }}" class="btn btn-sm btn-info" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($egreso->estado === 'pendiente')
                                <form action="{{ route('egresos.pagar', $egreso) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Marcar como pagado?')">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Marcar como pagado">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                            @endif
                            @if($egreso->estado !== 'anulado')
                                <form action="{{ route('egresos.anular', $egreso) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Anular este egreso?')">
                                    @csrf
                                    <button class="btn btn-sm btn-danger" title="Anular">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No hay egresos con los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="text-muted small">{{ $egresos->total() }} egreso(s)</div>
        <div>{{ $egresos->links() }}</div>
    </div>

    @if($porCategoria->isNotEmpty())
        <h6 class="fw-bold">Egresos por categoría (filtro actual, sin anulados)</h6>
        <div class="table-responsive">
            <table class="table table-sm w-auto">
                <thead>
                    <tr><th>Categoría</th><th class="text-end">Cantidad</th><th class="text-end">Total USD</th></tr>
                </thead>
                <tbody>
                    @foreach($porCategoria as $fila)
                        <tr>
                            <td>{{ $fila->categoria }}</td>
                            <td class="text-end">{{ $fila->cantidad }}</td>
                            <td class="text-end">{{ number_format((float) $fila->total_usd, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<style>
@media print {
    .no-print, .sidebar, .top-header, .nav, .pagination { display: none !important; }
    .main-content { margin: 0 !important; }
}
</style>
@endpush
