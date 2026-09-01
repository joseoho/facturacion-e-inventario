@extends('layouts.app')

@section('title', 'Detalles del Cliente')
@section('page-title', 'Detalles del Cliente')

@section('content')
<div class="stat-card">
    <!-- Botones de acción -->
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="{{ route('clientes.facturas', $cliente) }}" class="btn btn-info ms-auto">
            <i class="bi bi-receipt me-1"></i> Ver Facturas
        </a>
    </div>

    <div class="row g-4">
        <!-- Información del cliente -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Información del Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Nombre:</label>
                            <p class="fw-bold">{{ $cliente->nombre }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Documento:</label>
                            <p>{{ $cliente->tipo_documento }}: {{ $cliente->documento }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Email:</label>
                            <p>{{ $cliente->email ?? 'No registrado' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Teléfono:</label>
                            <p>{{ $cliente->telefono ?? 'No registrado' }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Dirección:</label>
                            <p>{{ $cliente->direccion ?? 'No registrada' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Contacto:</label>
                            <p>{{ $cliente->contacto ?? 'No registrado' }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Tipo de Cliente:</label>
                            <p><span class="badge bg-info">{{ $cliente->tipo_cliente }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Estado:</label>
                            <p>
                                @if($cliente->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Límite de Crédito:</label>
                            <p class="fw-bold text-success">${{ number_format($cliente->limite_credito ?? 0, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fw-semibold">Días de Crédito:</label>
                            <p>{{ $cliente->dias_credito ?? 0 }} días</p>
                        </div>
                    </div>

                    @if($cliente->notas)
                        <div class="row">
                            <div class="col-12">
                                <label class="text-muted fw-semibold">Notas:</label>
                                <p class="bg-light p-2 rounded">{{ $cliente->notas }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-3">
                        <div class="col-12">
                            <small class="text-muted">
                                Creado: {{ $cliente->created_at->format('d/m/Y H:i') }} | 
                                Última actualización: {{ $cliente->updated_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted fw-semibold">Total Facturas:</label>
                        <h4 class="fw-bold">{{ $stats['total_facturas'] }}</h4>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted fw-semibold">Total Facturado:</label>
                        <h4 class="fw-bold text-primary">{{ number_format($stats['total_facturado'], 2) }}</h4>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted fw-semibold">Total Pagado:</label>
                        <h4 class="fw-bold text-success">{{ number_format($stats['total_pagado'], 2) }}</h4>
                    </div>
                    <div>
                        <label class="text-muted fw-semibold">Saldo Pendiente:</label>
                        <h4 class="fw-bold text-danger">{{ number_format($stats['saldo_pendiente'], 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facturas recientes -->
        @if($facturasRecientes->count() > 0)
            <div class="col-12 mt-3">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Facturas Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th># Factura</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Tipo de Pago</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facturasRecientes as $factura)
                                        <tr>
                                            <td>{{ $factura->id }}</td>
                                            <td>{{ $factura->created_at->format('d/m/Y') }}</td>
                                            <td>{{ number_format($factura->total, 2) }}</td>
                                            <td>{{ ucfirst($factura->moneda->codigo) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $factura->estado === 'pagada' ? 'success' : 'warning' }}">
                                                    {{ $factura->estado }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('clientes.facturas', $cliente) }}" class="btn btn-sm btn-primary">
                                Ver todas las facturas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection