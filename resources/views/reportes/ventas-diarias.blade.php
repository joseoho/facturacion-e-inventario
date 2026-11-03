@extends('layouts.app')

@section('title', 'Facturación por rango')
@section('page-title', 'Facturación por rango')

@section('content')
@php
    $hoy = today();
    $inicioSemana = $hoy->copy()->startOfWeek();
    $finSemana = $hoy->copy()->endOfWeek();
    $inicioMes = $hoy->copy()->startOfMonth();
    $finMes = $hoy->copy()->endOfMonth();
@endphp

<div>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 no-print">
        <div>
            <h4 class="fw-bold mb-1">Facturación por rango de fechas</h4>
            <p class="text-muted mb-0">
                Ventas pagadas entre dos fechas, con totales separados por moneda y su equivalente en USD.
            </p>
        </div>
        <button type="button" class="btn btn-success" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
    </div>

    @if ($errors->has('fecha_fin'))
        <div class="alert alert-danger no-print">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ $errors->first('fecha_fin') }}
        </div>
    @endif

    <div class="stat-card mb-3 no-print">
        <form method="GET" action="{{ route('reportes.ventas.diarias') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio"
                           class="form-control @error('fecha_inicio') is-invalid @enderror"
                           value="{{ old('fecha_inicio', $inicio->format('Y-m-d')) }}">
                    @error('fecha_inicio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin"
                           class="form-control @error('fecha_fin') is-invalid @enderror"
                           value="{{ old('fecha_fin', $fin->format('Y-m-d')) }}">
                    @error('fecha_fin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                </div>

                <div class="col-12 col-md-4 text-md-end">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Rangos rápidos">
                        <a class="btn btn-outline-secondary"
                           href="{{ route('reportes.ventas.diarias', ['fecha_inicio' => $hoy->toDateString(), 'fecha_fin' => $hoy->toDateString()]) }}">
                            Hoy
                        </a>
                        <a class="btn btn-outline-secondary"
                           href="{{ route('reportes.ventas.diarias', ['fecha_inicio' => $inicioSemana->toDateString(), 'fecha_fin' => $finSemana->toDateString()]) }}">
                            Semana
                        </a>
                        <a class="btn btn-outline-secondary"
                           href="{{ route('reportes.ventas.diarias', ['fecha_inicio' => $inicioMes->toDateString(), 'fecha_fin' => $finMes->toDateString()]) }}">
                            Mes
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card h-100">
                <span class="text-muted small text-uppercase fw-semibold">Total USD</span>
                <h3 class="mb-0 mt-1 fw-bold text-success">
                    $ {{ number_format($totales['total_usd'], 2, ',', '.') }}
                </h3>
                <small class="text-muted">Convertido con la tasa de cada factura</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card h-100">
                <span class="text-muted small text-uppercase fw-semibold">Facturas</span>
                <h3 class="mb-0 mt-1 fw-bold">{{ number_format($totales['total_facturas']) }}</h3>
                <small class="text-muted">Pagadas en el rango</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card h-100">
                <span class="text-muted small text-uppercase fw-semibold">Ticket promedio</span>
                <h3 class="mb-0 mt-1 fw-bold">
                    $ {{ number_format($totales['promedio_usd'], 2, ',', '.') }}
                </h3>
                <small class="text-muted">Promedio en USD</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card h-100">
                <span class="text-muted small text-uppercase fw-semibold">Monedas</span>
                <h3 class="mb-0 mt-1 fw-bold">{{ $totales['monedas_count'] }}</h3>
                <small class="text-muted">Con ventas en el rango</small>
            </div>
        </div>
    </div>

    <div class="stat-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-currency-exchange me-2"></i> Totales por moneda
            </h6>
            <span class="badge bg-primary-subtle text-primary-emphasis">
                {{ $inicio->isSameDay($fin)
                    ? $inicio->format('d/m/Y')
                    : $inicio->format('d/m/Y').' al '.$fin->format('d/m/Y') }}
            </span>
        </div>

        @if (count($totales['por_moneda']) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Moneda</th>
                            <th class="text-end">Facturas</th>
                            <th class="text-end">Total en moneda</th>
                            <th class="text-end">Total USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($totales['por_moneda'] as $fila)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $fila['moneda'] }}</span>
                                </td>
                                <td class="text-end">{{ number_format($fila['cantidad']) }}</td>
                                <td class="text-end fw-semibold">
                                    {{ number_format($fila['total'], 2, ',', '.') }} {{ $fila['moneda'] }}
                                </td>
                                <td class="text-end fw-semibold">
                                    $ {{ number_format($fila['total_usd'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end">{{ number_format($totales['total_facturas']) }}</td>
                            <td class="text-end text-muted">—</td>
                            <td class="text-end">$ {{ number_format($totales['total_usd'], 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <small class="text-muted d-block mt-2">
                Los totales en moneda no se suman entre sí; el total consolidado se expresa en USD.
            </small>
        @else
            <div class="text-center text-muted py-4">
                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                No hay facturas pagadas en este rango de fechas.
            </div>
        @endif
    </div>

    @if (count($porDia) > 0)
        <div class="stat-card mb-3">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-calendar3 me-2"></i> Resumen por día
            </h6>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Facturas</th>
                            @foreach ($totales['por_moneda'] as $fila)
                                <th class="text-end">{{ $fila['moneda'] }}</th>
                            @endforeach
                            <th class="text-end">Total USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($porDia as $dia)
                            <tr>
                                <td class="fw-semibold">{{ $dia['fecha']->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($dia['cantidad']) }}</td>
                                @foreach ($totales['por_moneda'] as $fila)
                                    <td class="text-end">
                                        {{ number_format($dia['monedas'][$fila['moneda']] ?? 0, 2, ',', '.') }}
                                    </td>
                                @endforeach
                                <td class="text-end fw-semibold">
                                    $ {{ number_format($dia['total_usd'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end">{{ number_format($totales['total_facturas']) }}</td>
                            @foreach ($totales['por_moneda'] as $fila)
                                <td class="text-end">{{ number_format($fila['total'], 2, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end">$ {{ number_format($totales['total_usd'], 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <div class="stat-card" id="reporteContent">
        <div class="print-only mb-4">
            <div class="text-center">
                <h1 class="fw-bold" style="color: #1e293b;">Reporte de facturación</h1>
                <p class="text-muted mb-1">
                    {{ $inicio->isSameDay($fin)
                        ? 'Fecha: '.$inicio->format('d/m/Y')
                        : 'Del '.$inicio->format('d/m/Y').' al '.$fin->format('d/m/Y') }}
                </p>
                <p class="text-muted">Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
                <hr>
            </div>
        </div>

        <h6 class="fw-bold mb-3">
            <i class="bi bi-list me-2"></i>
            Detalle de facturas
            <span class="badge bg-primary-subtle text-primary-emphasis ms-2">
                {{ $totales['total_facturas'] }} facturas
            </span>
        </h6>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Moneda</th>
                        <th class="text-end">Total USD</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center no-print">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                        <tr>
                            <td>{{ $venta->fecha_emision->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $venta->numero }}</td>
                            <td>{{ $venta->cliente->nombre }}</td>
                            <td class="text-end fw-semibold">
                                {{ number_format((float) $venta->total, 2, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">
                                    {{ $venta->moneda_pago ?? $venta->moneda?->codigo ?? '—' }}
                                </span>
                            </td>
                            <td class="text-end">
                                $ {{ number_format($venta->total_usd_calculado, 2, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge-status {{ $venta->estado }}">
                                    {{ $venta->estado_texto }}
                                </span>
                            </td>
                            <td class="text-center no-print">
                                <a href="{{ route('facturas.show', $venta) }}"
                                   class="btn btn-sm btn-outline-primary" title="Ver factura">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                No hay facturas pagadas en este rango de fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totales['total_facturas'] > 0)
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">TOTAL USD:</td>
                            <td colspan="2"></td>
                            <td class="text-end">$ {{ number_format($totales['total_usd'], 2, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="print-only text-center pt-3">
            <hr>
            <small class="text-muted">
                Reporte generado automáticamente - {{ now()->format('d/m/Y H:i:s') }}
            </small>
        </div>
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
            padding: 8px !important;
            margin-bottom: 8px !important;
        }
        .table {
            font-size: 8.5px !important;
        }
        .table th {
            background: #f8f9fa !important;
        }
        .table tr {
            page-break-inside: avoid;
        }
        @page {
            margin: 10mm 8mm 10mm 8mm !important;
        }
    }
    .print-only {
        display: none !important;
    }
</style>
@endsection
