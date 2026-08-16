@extends('layouts.app')

@section('title', 'Facturas')
@section('page-title', 'Facturas')

@section('content')
<div x-data="facturasIndex()" x-init="init()">
    <!-- Filtros y Acciones -->
    <div class="stat-card mb-3">
        <form method="GET" action="{{ route('facturas.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="numero" class="form-control" placeholder="Número de factura..." 
                           value="{{ request('numero') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
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
            <div class="col-6 col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                    <option value="pagada" {{ request('estado') == 'pagada' ? 'selected' : '' }}>Pagadas</option>
                    <option value="anulada" {{ request('estado') == 'anulada' ? 'selected' : '' }}>Anuladas</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
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
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <a href="{{ route('facturas.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> Nueva
                </a>
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='{{ route('facturas.index') }}'">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Facturas -->
    <div class="stat-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-receipt me-2"></i>
                Listado de Facturas
                <span class="badge bg-primary-subtle text-primary-emphasis ms-2">
                    {{ $facturas->total() }} registros
                </span>
            </h6>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" @click="exportarExcel()">
                    <i class="bi bi-file-excel"></i> Excel
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="exportarPDF()">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 12%;">Número</th>
                        <th style="width: 20%;">Cliente</th>
                        <th style="width: 12%;">Fecha</th>
                        <th style="width: 10%;">Moneda</th>
                        <th style="width: 12%;" class="text-end">Total</th>
                        <th style="width: 15%;" class="text-center">Estado</th>
                        <th style="width: 19%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturas as $factura)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $factura->numero }}</span>
                            </td>
                            <td>
                                <div>
                                    <span>{{ $factura->cliente->nombre }}</span>
                                    <br>
                                    <small class="text-muted">{{ $factura->cliente->documento }}</small>
                                </div>
                            </td>
                            <td>{{ $factura->fecha_formateada }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis px-3 py-2">
                                    {{ $factura->moneda->codigo }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($factura->total, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge-status {{ $factura->estado }}">
                                    {{ $factura->estado_texto }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('facturas.show', $factura) }}" 
                                       class="btn btn-outline-primary" 
                                       title="Ver Factura">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($factura->estado === 'pendiente')
                                        <a href="{{ route('facturas.edit', $factura) }}" 
                                           class="btn btn-outline-warning" 
                                           title="Editar Factura">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-outline-success" 
                                                title="Pagar Factura"
                                                @click="pagarFactura({{ $factura->id }})">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    @endif
                                    
                                    @if($factura->estado !== 'anulada')
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Anular Factura"
                                                @click="anularFactura({{ $factura->id }})">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                    
                                    <a href="{{ route('facturas.pdf', $factura) }}" 
                                       class="btn btn-outline-secondary" 
                                       title="Descargar PDF"
                                       target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    
                                    <a href="{{ route('facturas.imprimir', $factura) }}" 
                                       class="btn btn-outline-secondary" 
                                       title="Imprimir"
                                       target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-receipt-cutoff fs-1 d-block mb-3 text-muted"></i>
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
            <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
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

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="modalTitle">Confirmar Acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p x-text="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn" :class="modalButtonClass" @click="confirmarAccion()">
                        <span x-text="modalButtonText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function facturasIndex() {
        return {
            modalTitle: '',
            modalMessage: '',
            modalButtonText: '',
            modalButtonClass: '',
            accionId: null,
            accionTipo: null,
            modal: null,
            
            init() {
                // Inicializar modal
                this.modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            },
            
            anularFactura(id) {
                this.accionId = id;
                this.accionTipo = 'anular';
                this.modalTitle = '⚠️ Anular Factura';
                this.modalMessage = '¿Estás seguro de que deseas anular esta factura? Esta acción reincorporará el stock y no se puede deshacer.';
                this.modalButtonText = 'Sí, Anular';
                this.modalButtonClass = 'btn-danger';
                this.modal.show();
            },
            
            pagarFactura(id) {
                this.accionId = id;
                this.accionTipo = 'pagar';
                this.modalTitle = '✅ Pagar Factura';
                this.modalMessage = '¿Estás seguro de que deseas marcar esta factura como pagada?';
                this.modalButtonText = 'Sí, Pagar';
                this.modalButtonClass = 'btn-success';
                this.modal.show();
            },
            
            async confirmarAccion() {
                const id = this.accionId;
                const tipo = this.accionTipo;
                
                if (!id || !tipo) return;
                
                try {
                    const url = tipo === 'anular' 
                        ? `/facturas/${id}/anular`
                        : `/facturas/${id}/pagar`;
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        toast('Éxito', result.message, 'success');
                        this.modal.hide();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toast('Error', result.message || 'Error al procesar la acción', 'error');
                        this.modal.hide();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    toast('Error', 'Error de conexión al servidor', 'error');
                    this.modal.hide();
                }
            },
            
            exportarExcel() {
                toast('Info', 'Función de exportación a Excel en desarrollo', 'info');
            },
            
            exportarPDF() {
                toast('Info', 'Función de exportación a PDF en desarrollo', 'info');
            }
        }
    }
</script>
@endpush
@endsection