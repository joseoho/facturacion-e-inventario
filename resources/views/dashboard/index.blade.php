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
            
        </div>
        
        <!-- Gráficos -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Ventas de la Semana</h6>
                        <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">
                            <i class="bi bi-calendar3 me-1"></i> Últimos 7 días
                        </span>
                    </div>
                    <canvas id="ventasChart" height="250"></canvas>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="stat-card h-100">
                    <h6 class="fw-bold mb-3">Productos Más Vendidos</h6>
                    <div class="list-group list-group-flush">
                        @if(isset($productosTop) && $productosTop->count() > 0)
                            @foreach($productosTop as $producto)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0 border-bottom">
                                <div>
                                    <span class="fw-semibold">{{ $producto->nombre }}</span>
                                    <br>
                                    <small class="text-muted">{{ number_format($producto->total_kg, 2) }} Kg</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">
                                    {{ number_format($producto->veces_vendido) }} ventas
                                </span>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-bar-chart-line fs-3 d-block mb-2"></i>
                                Sin datos de ventas este mes
                            </div>
                        @endif
                    </div>
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