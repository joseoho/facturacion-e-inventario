@extends('layouts.app')

@section('title', 'Ventas Diarias')
@section('page-title', 'Ventas Diarias')

@section('content')
<div x-data="reporteVentas()">
    <!-- Filtros -->
    <div class="stat-card mb-3 no-print">
        <form method="GET" action="{{ route('reportes.ventas.diarias') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
            </div>
            <div class="col-12 col-md-7 text-end">
                <button type="button" class="btn btn-success" @click="imprimirReporte()">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </form>
    </div>

    <!-- Resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Total Ventas</span>
                        <h3 class="mb-0 mt-1 fw-bold text-success">
                            {{ number_format($totales['total_ventas'], 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon green">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Facturas</span>
                        <h3 class="mb-0 mt-1 fw-bold">
                            {{ number_format($totales['total_facturas']) }}
                        </h3>
                    </div>
                    <div class="stat-icon blue">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Ticket Promedio</span>
                        <h3 class="mb-0 mt-1 fw-bold">
                            {{ number_format($totales['promedio'], 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon purple">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Monedas</span>
                        <h3 class="mb-0 mt-1 fw-bold">
                            {{ count($totales['por_moneda']) }}
                        </h3>
                    </div>
                    <div class="stat-icon yellow">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido del Reporte -->
    <div class="stat-card" id="reporteContent">
        <div class="report-header print-only">
            <div class="text-center mb-4">
                <h1 class="fw-bold" style="color: #1e293b;">Reporte de Ventas Diarias</h1>
                <p class="text-muted">Fecha: {{ $fecha->format('d/m/Y') }}</p>
                <p class="text-muted">Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
                <hr>
            </div>
        </div>

        <h6 class="fw-bold mb-3">
            <i class="bi bi-list me-2"></i>
            Ventas del día {{ $fecha->format('d/m/Y') }}
            <span class="badge bg-primary-subtle text-primary-emphasis ms-2">
                {{ $totales['total_facturas'] }} facturas
            </span>
        </h6>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaVentas">
                <thead>
                    <tr>
                        <th style="width: 15%;">Factura</th>
                        <th style="width: 25%;">Cliente</th>
                        <th style="width: 15%;" class="text-end">Total</th>
                        <th style="width: 15%;" class="text-center">Moneda</th>
                        <th style="width: 15%;" class="text-center">Estado</th>
                        <th style="width: 15%;" class="text-center no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $venta)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $venta->numero }}</span>
                            </td>
                            <td>{{ $venta->cliente->nombre }}</td>
                            <td class="text-end fw-semibold">
                                {{ number_format($venta->total, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    {{ $venta->moneda->codigo }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-status {{ $venta->estado }}">
                                    {{ $venta->estado_texto }}
                                </span>
                            </td>
                            <td class="text-center no-print">
                                <a href="{{ route('facturas.show', $venta) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                No hay ventas registradas para esta fecha
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">TOTAL:</td>
                        <td class="text-end">{{ number_format($totales['total_ventas'], 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                    <tr class="print-only">
                        <td colspan="6" class="text-center pt-3">
                            <hr>
                            <small class="text-muted">
                                Reporte generado automáticamente - {{ now()->format('d/m/Y H:i:s') }}
                            </small>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Resumen por Moneda -->
        @if(count($totales['por_moneda']) > 1)
            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-bold mb-2">Resumen por Moneda</h6>
                <div class="row g-2">
                    @foreach($totales['por_moneda'] as $moneda)
                        <div class="col-md-3">
                            <div class="p-2 bg-light rounded-3">
                                <span class="fw-semibold">{{ $moneda['moneda'] }}</span>
                                <br>
                                <span class="fw-bold">{{ number_format($moneda['total'], 2) }}</span>
                                <br>
                                <small class="text-muted">{{ $moneda['cantidad'] }} facturas</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        body {
            background: white !important;
            font-size: 10px !important;
        }
        .stat-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            padding: 12px !important;
            margin-bottom: 10px !important;
        }
        .table {
            font-size: 9px !important;
        }
        .table th {
            background: #f8f9fa !important;
        }
        .report-header h1 {
            font-size: 16px !important;
        }
        @page {
            margin: 10mm 8mm 10mm 8mm !important;
        }
    }
    .print-only {
        display: none !important;
    }
</style>

@push('scripts')
<script>
    function reporteVentas() {
        return {
            imprimirReporte() {
                window.print();
            }
        }
    }
</script>
@endpush
@endsection