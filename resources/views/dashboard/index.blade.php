@extends('layouts.app')
@section('content')
<x-layout title="Dashboard" page-title="Dashboard">
    <div x-data="dashboard()" x-init="init()">
        <!-- Alertas de Stock - CON VERIFICACIÓN -->
        @if(isset($alertasStock) && $alertasStock->count() > 0)
        <div class="alert alert-warning d-flex align-items-center gap-3 border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
            <div>
                <strong>¡Atención! Hay {{ $alertasStock->count() }} producto(s) con stock bajo:</strong>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    @foreach($alertasStock->take(5) as $alerta)
                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">
                            {{ $alerta['nombre'] }}: {{ number_format($alerta['stock_kg'], 3) }} Kg
                        </span>
                    @endforeach
                    @if($alertasStock->count() > 5)
                        <span class="badge bg-secondary-subtle text-secondary-emphasis px-3 py-2">
                            +{{ $alertasStock->count() - 5 }} más
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif
        
        <!-- Tarjetas de Métricas -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Ventas Hoy</span>
                            <h3 class="mb-0 mt-1 fw-bold">
                                {{ isset($metricas['ventas_hoy']) ? number_format($metricas['ventas_hoy'], 2) : '0.00' }}
                            </h3>
                            <small class="text-muted">
                                {{ isset($metricas['facturas_hoy']) ? number_format($metricas['facturas_hoy']) : '0' }} facturas
                            </small>
                        </div>
                        <div class="stat-icon blue">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    @if(isset($metricas['crecimiento']) && $metricas['crecimiento'] != 0)
                        <div class="mt-2">
                            <span class="badge {{ $metricas['crecimiento'] > 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }}">
                                <i class="bi bi-arrow-{{ $metricas['crecimiento'] > 0 ? 'up' : 'down' }}"></i>
                                {{ abs($metricas['crecimiento']) }}% vs mes anterior
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Resto de las tarjetas con verificaciones similares -->
                    <!-- Desglose del Mes Anterior -->
        @if(isset($desgloseMesAnterior))
        <div class="stat-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    Facturación {{ now()->subMonthNoOverflow()->translatedFormat('F Y') }}
                </h6>
                <span class="badge bg-secondary-subtle text-secondary-emphasis px-3 py-2">
                    <i class="bi bi-calendar-check me-1"></i> Mes cerrado
                </span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded border border-success-subtle bg-success-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success-emphasis fw-semibold">
                                <i class="bi bi-check-circle me-1"></i> Pagadas
                            </span>
                            <span class="badge bg-success">
                                {{ $desgloseMesAnterior['pagadas']['cantidad'] }}
                            </span>
                        </div>
                        <h4 class="mb-0 mt-2 fw-bold text-success-emphasis">
                            {{ number_format($desgloseMesAnterior['pagadas']['total'], 2) }}
                        </h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded border border-warning-subtle bg-warning-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-warning-emphasis fw-semibold">
                                <i class="bi bi-clock-history me-1"></i> Pendientes
                            </span>
                            <span class="badge bg-warning text-dark">
                                {{ $desgloseMesAnterior['pendientes']['cantidad'] }}
                            </span>
                        </div>
                        <h4 class="mb-0 mt-2 fw-bold text-warning-emphasis">
                            {{ number_format($desgloseMesAnterior['pendientes']['total'], 2) }}
                        </h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded border border-danger-subtle bg-danger-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-danger-emphasis fw-semibold">
                                <i class="bi bi-x-circle me-1"></i> Anuladas
                            </span>
                            <span class="badge bg-danger">
                                {{ $desgloseMesAnterior['anuladas']['cantidad'] }}
                            </span>
                        </div>
                        <h4 class="mb-0 mt-2 fw-bold text-danger-emphasis">
                            {{ number_format($desgloseMesAnterior['anuladas']['total'], 2) }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif
        </div>
        
        <!-- Ventas de la Semana -->
      <div class="col-lg-8">
    <div class="stat-card h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Ventas de la Semana</h6>
            <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">
                <i class="bi bi-calendar3 me-1"></i> Últimos 7 días
            </span>
        </div>

        @php
            $totalSemana = array_sum($ventasSemana['data'] ?? []);
            $maxDia = !empty($ventasSemana['data']) ? max($ventasSemana['data']) : 0;
        @endphp

        <div class="table-responsive">
            <table class="table table-sm table-borderless align-middle mb-0">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>Día</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end" style="width: 60px;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventasSemana['labels'] as $i => $label)
                        @php
                            $valor = $ventasSemana['data'][$i] ?? 0;
                            $esHoy = $i === count($ventasSemana['labels']) - 1;
                            $pct = $totalSemana > 0 ? round(($valor / $totalSemana) * 100, 1) : 0;
                        @endphp
                        <tr class="{{ $esHoy ? 'table-primary fw-semibold' : '' }}">
                            <td>
                                {{ $label }}
                                @if($esHoy)
                                    <span class="badge bg-primary ms-1">Hoy</span>
                                @endif
                            </td>
                            <td class="text-end {{ $valor > 0 ? 'fw-semibold' : 'text-muted' }}">
                                {{ number_format($valor, 2) }}
                            </td>
                            <td class="text-end small {{ $valor > 0 ? 'text-primary' : 'text-muted' }}">
                                {{ $pct }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-top">
                    <tr class="fw-bold">
                        <td>Total semana</td>
                        <td class="text-end">{{ number_format($totalSemana, 2) }}</td>
                        <td class="text-end small">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
        
        <!-- Facturas Recientes -->
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Facturas Recientes</h6>
                <a href="{{ route('facturas.index') }}" class="btn btn-sm btn-outline-primary">
                    Ver todas <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Moneda</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($facturasRecientes) && $facturasRecientes->count() > 0)
                            @foreach($facturasRecientes as $factura)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $factura->numero }}</span>
                                </td>
                                <td>{{ $factura->cliente->nombre ?? 'Sin cliente' }}</td>
                                <td>{{ $factura->fecha_formateada ?? $factura->fecha_emision }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                        {{ $factura->moneda->codigo ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ number_format($factura->total, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge-status {{ $factura->estado ?? 'pendiente' }}">
                                        {{ $factura->estado_texto ?? $factura->estado ?? 'Pendiente' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('facturas.show', $factura) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                    No hay facturas recientes
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>

@endsection