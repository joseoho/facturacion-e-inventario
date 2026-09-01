@extends('layouts.app')

@section('title', 'Facturas de ' . $cliente->nombre)
@section('page-title', 'Facturas de ' . $cliente->nombre)

@section('content')
<div class="stat-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al Cliente
            </a>
        </div>
        <div>
            <strong>Total facturas:</strong> {{ $facturas->total() }}
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Tipo Moneda</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $factura)
                    <tr>
                        <td>{{ $factura->id }}</td>
                        <td>{{ $factura->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($factura->total, 2) }}</td>
                        <td>{{ $factura->moneda->codigo }}</td>
                        <td>
                            <span class="badge bg-{{ $factura->estado === 'pagada' ? 'success' : 'warning' }}">
                                {{ $factura->estado }}
                            </span>
                        </td>
                        <td>
                            <a href="#" class="btn btn-sm btn-info" title="Ver factura">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-receipt fs-2 d-block"></i>
                            Este cliente no tiene facturas registradas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Mostrando {{ $facturas->firstItem() ?? 0 }} - {{ $facturas->lastItem() ?? 0 }} 
            de {{ $facturas->total() }} facturas
        </div>
        <div>
            {{ $facturas->links() }}
        </div>
    </div>
</div>
@endsection