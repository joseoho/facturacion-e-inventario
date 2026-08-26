@extends('layouts.app')

@section('title', 'Facturas')
@section('page-title', 'Facturas')

@section('content')
<!-- Mensajes de alerta -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('facturas.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Número de Factura</label>
                <input type="text" name="numero" class="form-control" 
                       placeholder="Buscar por número..." 
                       value="{{ request('numero') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="pagada" {{ request('estado') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                    <option value="anulada" {{ request('estado') == 'anulada' ? 'selected' : '' }}>Anulada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Moneda</label>
                <select name="moneda_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($monedas as $moneda)
                        <option value="{{ $moneda->id }}" {{ request('moneda_id') == $moneda->id ? 'selected' : '' }}>
                            {{ $moneda->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('facturas.create') }}" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                    <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de facturas -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-title mb-0">
                <i class="bi bi-receipt me-2"></i>
                Listado de Facturas
                <span class="badge bg-primary ms-2">
                    {{ $facturas->total() ?? 0 }} registros
                </span>
            </h6>
            <div>
                <button class="btn btn-sm btn-outline-secondary" onclick="alert('Función en desarrollo')">
                    <i class="bi bi-file-excel me-1"></i> Excel
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="alert('Función en desarrollo')">
                    <i class="bi bi-file-pdf me-1"></i> PDF
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
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
                    @forelse($facturas as $factura)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $factura->numero ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span>{{ $factura->cliente->nombre ?? 'N/A' }}</span>
                                @if(isset($factura->cliente->documento))
                                    <br>
                                    <small class="text-muted">{{ $factura->cliente->documento }}</small>
                                @endif
                            </td>
                            <td>
                                @if($factura->fecha_emision)
                                    {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $factura->moneda->codigo ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($factura->total ?? 0, 2) }}
                            </td>
                            <td class="text-center">
                                @php
                                    $estados = [
                                        'pendiente' => ['class' => 'bg-warning', 'text' => 'Pendiente'],
                                        'pagada' => ['class' => 'bg-success', 'text' => 'Pagada'],
                                        'anulada' => ['class' => 'bg-danger', 'text' => 'Anulada']
                                    ];
                                    $estado = $estados[$factura->estado] ?? ['class' => 'bg-secondary', 'text' => 'Desconocido'];
                                @endphp
                                <span class="badge {{ $estado['class'] }}">
                                    {{ $estado['text'] }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('facturas.show', $factura) }}" 
                                       class="btn btn-outline-primary" 
                                       title="Ver Factura">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($factura->estado === 'pendiente')
                                        <a href="{{ route('facturas.edit', $factura) }}" 
                                           class="btn btn-outline-warning" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-outline-success" 
                                                title="Pagar"
                                                onclick="pagarFactura({{ $factura->id }})">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    @endif
                                    
                                    @if($factura->estado !== 'anulada')
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Anular"
                                                onclick="anularFactura({{ $factura->id }})">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                    
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            title="PDF"
                                            onclick="alert('Función en desarrollo')">
                                        <i class="bi bi-file-pdf"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                <h6 class="text-muted">No hay facturas registradas</h6>
                                <p class="text-muted small">Comienza creando tu primera factura</p>
                                <a href="{{ route('facturas.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-lg me-1"></i> Crear Factura
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($facturas->hasPages())
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando {{ $facturas->firstItem() ?? 0 }} - {{ $facturas->lastItem() ?? 0 }} 
                    de {{ $facturas->total() }} registros
                </div>
                <div>
                    {{ $facturas->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Función para anular factura
function anularFactura(id) {
    if (!confirm('¿Estás seguro de que deseas anular esta factura?')) {
        return;
    }
    
    fetch(`/facturas/${id}/anular`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al servidor');
    });
}

// Función para pagar factura
function pagarFactura(id) {
    if (!confirm('¿Marcar esta factura como pagada?')) {
        return;
    }
    
    fetch(`/facturas/${id}/pagar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al servidor');
    });
}
</script>
@endpush
@endsection