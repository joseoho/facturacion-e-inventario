@extends('layouts.app')

@section('title', 'Factura ' . $factura->numero)
@section('page-title', 'Factura ' . $factura->numero)

@section('content')
<div class="stat-card">
    <!-- Botones de Acción -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('facturas.index') }}" class="btn btn-secondary no-print">
            <i class="bi bi-arrow-left me-2"></i> Volver
        </a>
        {{-- <a href="{{ route('facturas.pdf', $factura) }}" class="btn btn-primary" target="_blank">
            <i class="bi bi-file-pdf me-1"></i> PDF
        </a> --}}
        <button class="btn btn-outline-secondary no-print" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Imprimir
        </button>
        
        @if($factura->estado === 'pendiente')
            <a href="{{ route('facturas.edit', $factura) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            <button type="button" class="btn btn-success" onclick="pagarFactura({{ $factura->id }})">
                <i class="bi bi-check-lg me-1"></i> Pagar
            </button>
        @endif
        
        @if($factura->estado !== 'anulada')
            <button type="button" class="btn btn-danger" onclick="anularFactura({{ $factura->id }})">
                <i class="bi bi-x-lg me-1"></i> Anular
            </button>
        @endif
    </div>

    <!-- Información de la Factura -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <h6 class="fw-bold text-muted mb-3">Datos de la Factura</h6>
            <div class="row g-2">
                <div class="col-6">
                    <small class="text-muted d-block">Número</small>
                    <strong>{{ $factura->numero }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Fecha</small>
                    <strong>{{ $factura->fecha_formateada }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Moneda</small>
                    <strong>{{ $factura->moneda->codigo }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Estado</small>
                    <span class="badge-status {{ $factura->estado }}">
                        {{ $factura->estado_texto }}
                    </span>
                </div>
                @if($factura->tasaCambio)
                    <div class="col-12">
                        <small class="text-muted d-block">Tasa de Cambio</small>
                        <strong>1 USD = {{ number_format($factura->tasaCambio->tasa, 2) }} {{ $factura->moneda->codigo }}</strong>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold text-muted mb-3">Datos del Cliente</h6>
            <div class="row g-2">
                <div class="col-12">
                    <small class="text-muted d-block">Nombre</small>
                    <strong>{{ $factura->cliente->nombre }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Documento</small>
                    <strong>{{ $factura->cliente->documento }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Teléfono</small>
                    <strong>{{ $factura->cliente->telefono ?? 'N/A' }}</strong>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Dirección</small>
                    <strong>{{ $factura->cliente->direccion ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de Productos -->
    <h6 class="fw-bold text-muted mb-3">Detalle de Productos</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Producto</th>
                    <th style="width: 15%;" class="text-end">Cantidad (Kg)</th>
                    <th style="width: 15%;" class="text-end">Precio/Kg</th>
                    <th style="width: 15%;" class="text-end">Neto</th>
                    <th style="width: 10%;" class="text-center">IVA</th>
                    <th style="width: 15%;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->lineas as $index => $linea)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div>
                                <span class="fw-semibold">{{ $linea->producto->nombre }}</span>
                                <br>
                                <small class="text-muted">{{ $linea->producto->sku }}</small>
                            </div>
                        </td>
                        <td class="text-end">{{ number_format($linea->cantidad_kg, 3, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($linea->precio_kg, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($linea->neto, 2, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                {{ number_format($linea->impuesto_porcentaje, 0) }}%
                            </span>
                        </td>
                        <td class="text-end fw-semibold">
                            {{ number_format($linea->total, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>Subtotal Neto:</strong></td>
                    <td class="text-end">{{ number_format($factura->subtotal_neto, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end"><strong>Total IVA:</strong></td>
                    <td class="text-end">{{ number_format($factura->total_impuesto, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end text-primary"><strong>TOTAL GENERAL:</strong></td>
                    <td class="text-end text-primary fs-5">
                        {{ number_format($factura->total, 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Información Adicional -->
    <div class="mt-3 pt-3 border-top">
        <div class="row g-2 text-muted small">
            <div class="col-md-4">
                <i class="bi bi-person me-1"></i> 
                Creada por: {{ $factura->user->name }}
            </div>
            <div class="col-md-4">
                <i class="bi bi-clock me-1"></i> 
                Creada: {{ $factura->created_at->format('d/m/Y H:i:s') }}
            </div>
            <div class="col-md-4">
                <i class="bi bi-arrow-repeat me-1"></i> 
                Última actualización: {{ $factura->updated_at->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>
</div>

<!-- Scripts para acciones -->
@push('scripts')
<script>
    function anularFactura(id) {
        if (!confirm('¿Estás seguro de que deseas anular esta factura? Esta acción reincorporará el stock y no se puede deshacer.')) return;
        
        fetch(`/facturas/${id}/anular`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                toast('Éxito', result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                toast('Error', result.message || 'Error al anular la factura', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toast('Error', 'Error de conexión al servidor', 'error');
        });
    }
    
    function pagarFactura(id) {
        if (!confirm('¿Estás seguro de que deseas marcar esta factura como pagada?')) return;
        
        fetch(`/facturas/${id}/pagar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                toast('Éxito', result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                toast('Error', result.message || 'Error al pagar la factura', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toast('Error', 'Error de conexión al servidor', 'error');
        });
    }
</script>
<style>
    /* Estilos para impresión */
    @media print {
        /* Ocultar todo lo que tenga clase no-print */
        .no-print {
            display: none !important;
        }
        
        /* Ocultar navegación, header, botones */
        .navbar,
        .navbar-nav,
        .nav,
        .nav-link,
        .navbar-brand,
        .navbar-toggler,
        .btn-group,
        .btn,
        .pagination,
        .breadcrumb,
        .header,
        .main-header,
        .app-header,
        .top-nav,
        .navigation,
        .menu,
        .sidebar,
        .nav-menu,
        .header-menu,
        .top-menu,
        .main-nav,
        .site-header,
        .page-header,
        .card .card-body .d-flex.gap-2,
        .card-header .d-flex.justify-content-between,
        .card-footer,
        .action-buttons {
            display: none !important;
        }
        
        /* Mostrar solo el contenido principal */
        .table-responsive {
            overflow: visible !important;
        }
        
        .table {
            width: 100% !important;
            font-size: 12px !important;
        }
        
        .table-bordered {
            border: 1px solid #000 !important;
        }
        
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
        
        /* Fondo blanco */
        body {
            background: white !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-body {
            padding: 0 !important;
        }
        
        /* Ocultar columna de acciones si existe */
        .table thead tr th:last-child,
        .table tbody tr td:last-child {
            display: none !important;
        }
        
        /* Mostrar encabezado de impresión */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-total {
            display: block !important;
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
        }
    }
    
    /* Ocultar en pantalla lo que solo es para impresión */
    .print-header {
        display: none;
    }
    .print-total {
        display: none;
    }
</style>
@endpush
@endsection