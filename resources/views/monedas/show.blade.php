@extends('layouts.app')

@section('title', 'Detalles de Moneda')
@section('page-title', 'Detalles de Moneda')

@section('content')
<div class="stat-card">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $moneda->nombre }}</h5>
                    <div>
                        <a href="{{ route('monedas.edit', $moneda) }}" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="{{ route('monedas.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>ID</th>
                                    <td>{{ $moneda->id }}</td>
                                </tr>
                                <tr>
                                    <th>Código</th>
                                    <td><strong>{{ $moneda->codigo }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Nombre</th>
                                    <td>{{ $moneda->nombre }}</td>
                                </tr>
                                <tr>
                                    <th>Símbolo</th>
                                    <td><span class="badge bg-info">{{ $moneda->simbolo }}</span></td>
                                </tr>
                                <tr>
                                    <th>Tipo</th>
                                    <td>
                                        @if($moneda->es_base)
                                            <span class="badge bg-warning text-dark">Moneda Base</span>
                                        @else
                                            <span class="badge bg-secondary">Moneda Secundaria</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        @if($moneda->activa)
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-danger">Inactiva</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha de Creación</th>
                                    <td>{{ $moneda->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Última Actualización</th>
                                    <td>{{ $moneda->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Estadísticas</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Facturas
                                            <span class="badge bg-primary rounded-pill">{{ $totalFacturas }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Productos con precios
                                            <span class="badge bg-success rounded-pill">{{ $totalProductos }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Tasas de cambio registradas
                                            <span class="badge bg-info rounded-pill">{{ $moneda->tasasCambio->count() }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($moneda->tasasCambio->count() > 0)
                        <div class="mt-4">
                            <h6>Últimas Tasas de Cambio</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tasa</th>
                                            <th>Registrado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($moneda->tasasCambio as $tasa)
                                            <tr>
                                                <td>{{ $tasa->fecha->format('d/m/Y') }}</td>
                                                <td>{{ number_format($tasa->tasa, 6) }}</td>
                                                <td>{{ $tasa->user ? $tasa->user->name : 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection