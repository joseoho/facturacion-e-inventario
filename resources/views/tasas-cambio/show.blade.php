@extends('layouts.app')

@section('title', 'Detalle tasaCambio de Cambio')
@section('page-title', 'Detalle tasaCambio de Cambio')

@section('content')
<div class="stat-card">
    <div class="row g-4">
        <!-- Información Principal -->
        <div class="col-md-6">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-3">
                        <i class="bi bi-info-circle me-2"></i> Información General
                    </h6>
                    
                    <div class="mb-3">
                        <label class="text-muted small d-block">Moneda</label>
                        <div class="d-flex align-items-center">
                            <span class="fw-bold fs-5">{{ $tasaCambio->moneda->nombre }}</span>
                            <span class="badge bg-secondary ms-2">{{ $tasaCambio->moneda->codigo }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block">tasaCambio de Cambio</label>
                        <div class="d-flex align-items-center">
                            <span class="fw-bold fs-3 text-primary">1 USD =</span>
                            <span class="fw-bold fs-3 text-primary ms-2">{{ number_format($tasaCambio->tasaCambio, 6) }}</span>
                            <span class="ms-2 text-muted">{{ $tasaCambio->moneda->codigo }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small d-block">Fecha y Hora</label>
                        <span class="fw-semibold">{{ $tasaCambio->fecha->format('d/m/Y') }}</span>
                        <span class="text-muted ms-2">{{ $tasaCambio->fecha->format('H:i:s') }}</span>
                    </div>

                    <div>
                        <label class="text-muted small d-block">Registrado por</label>
                        <span>{{ $tasaCambio->user ? $tasaCambio->user->name : 'N/A' }}</span>
                        <span class="text-muted ms-2">• {{ $tasaCambio->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uso de la tasaCambio -->
        <div class="col-md-6">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-3">
                        <i class="bi bi-boxes me-2"></i> Uso de la tasaCambio
                    </h6>

                    <!-- Precios de Productos -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Precios de Productos</span>
                            <span class="badge bg-primary">{{ $tasaCambio->preciosProductos->count() }}</span>
                        </div>
                        @if($tasaCambio->preciosProductos->count() > 0)
                            <div class="mt-2">
                                <ul class="list-unstyled small">
                                    @foreach($tasaCambio->preciosProductos->take(5) as $precio)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ $precio->producto->nombre ?? 'Producto' }}</span>
                                            <span class="text-muted">{{ number_format($precio->precio_kg, 4) }}</span>
                                        </li>
                                    @endforeach
                                    @if($tasaCambio->preciosProductos->count() > 5)
                                        <li class="text-muted">... y {{ $tasaCambio->preciosProductos->count() - 5 }} más</li>
                                    @endif
                                </ul>
                            </div>
                        @else
                            <span class="text-muted small">No se usa en precios de productos</span>
                        @endif
                    </div>

                    <!-- Facturas -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Facturas</span>
                            <span class="badge bg-success">{{ $tasaCambio->facturas->count() }}</span>
                        </div>
                        @if($tasaCambio->facturas->count() > 0)
                            <div class="mt-2">
                                <ul class="list-unstyled small">
                                    @foreach($tasaCambio->facturas->take(5) as $factura)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ $factura->numero }}</span>
                                            <span class="text-muted">{{ number_format($factura->total, 2) }}</span>
                                        </li>
                                    @endforeach
                                    @if($tasaCambio->facturas->count() > 5)
                                        <li class="text-muted">... y {{ $tasaCambio->facturas->count() - 5 }} más</li>
                                    @endif
                                </ul>
                            </div>
                        @else
                            <span class="text-muted small">No se usa en facturas</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="col-12">
            <hr>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('tasas.edit', $tasaCambio) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
                <a href="{{ route('tasas.duplicate', $tasaCambio) }}" class="btn btn-info">
                    <i class="bi bi-copy me-1"></i> Duplicar
                </a>
                <a href="{{ route('tasas.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>
@endsection