@extends('layouts.app')

@section('title', 'Egreso ' . $egreso->numero)
@section('page-title', 'Egreso ' . $egreso->numero)

@section('content')
<div class="stat-card">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2 no-print">
        <div>
            <h5 class="mb-1">{{ $egreso->numero }}</h5>
            <span class="badge bg-{{ $egreso->estado === 'pagado' ? 'success' : ($egreso->estado === 'anulado' ? 'danger' : 'warning') }}">
                {{ $egreso->estado_texto }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('egresos.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir
            </button>
            @if($egreso->estado === 'pendiente')
                <form action="{{ route('egresos.pagar', $egreso) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('¿Marcar como pagado?')">
                    @csrf
                    <button class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Marcar como pagado
                    </button>
                </form>
            @endif
            @if($egreso->estado !== 'anulado')
                <form action="{{ route('egresos.anular', $egreso) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('¿Anular este egreso?')">
                    @csrf
                    <button class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Anular
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="text-muted small">Fecha</div>
            <div class="fw-semibold">{{ $egreso->fecha_formateada }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Categoría</div>
            <div class="fw-semibold">{{ $egreso->categoria->nombre ?? '—' }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Proveedor</div>
            <div class="fw-semibold">{{ $egreso->proveedor_nombre ?: '—' }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Registrado por</div>
            <div class="fw-semibold">{{ $egreso->user->name ?? '—' }}</div>
        </div>

        <div class="col-12">
            <div class="text-muted small">Concepto</div>
            <div>{{ $egreso->concepto }}</div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Moneda</div>
            <div class="fw-semibold">{{ $egreso->moneda_pago }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Monto</div>
            <div class="fw-bold fs-5">{{ number_format((float) $egreso->monto, 2, ',', '.') }} {{ $egreso->moneda_pago }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Monto en USD</div>
            <div class="fw-semibold">{{ number_format($egreso->monto_usd, 2, ',', '.') }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Tasa usada</div>
            <div class="fw-semibold">
                {{ $egreso->tasa_cambio_usada ? number_format((float) $egreso->tasa_cambio_usada, 4, ',', '.') : '—' }}
            </div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Método de pago</div>
            <div class="fw-semibold">{{ $egreso->metodo_pago_texto }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Referencia</div>
            <div class="fw-semibold">{{ $egreso->referencia_pago ?: '—' }}</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted small">Fecha de pago</div>
            <div class="fw-semibold">{{ $egreso->fecha_pago?->format('d/m/Y') ?? '—' }}</div>
        </div>

        @if($egreso->producto_id)
            <div class="col-md-3">
                <div class="text-muted small">Producto afectado</div>
                <div class="fw-semibold">{{ $egreso->producto->nombre ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Cantidad ingresada</div>
                <div class="fw-semibold">{{ number_format((float) $egreso->cantidad_kg, 3, ',', '.') }} Kg</div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
@media print {
    .no-print, .sidebar, .top-header, .nav, .btn, .pagination { display: none !important; }
    .main-content { margin: 0 !important; }
}
</style>
@endpush
