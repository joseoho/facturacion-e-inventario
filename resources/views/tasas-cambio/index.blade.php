@extends('layouts.app')

@section('title', 'Tasas de Cambio')
@section('page-title', 'Tasas de Cambio')

@section('content')
<div x-data="tasasCambioIndex()" x-init="init()">
    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small">Total Registros</span>
                        <h5 class="fw-bold mb-0">{{ number_format($stats['total'] ?? 0) }}</h5>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-currency-exchange fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small">Tasa Promedio</span>
                        <h5 class="fw-bold mb-0">{{ number_format($stats['tasa_promedio'] ?? 0, 6) }}</h5>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small">Tasa Máxima</span>
                        <h5 class="fw-bold mb-0">{{ number_format($stats['tasa_max'] ?? 0, 6) }}</h5>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-arrow-up-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small">Última Actualización</span>
                        <h6 class="fw-bold mb-0">
                            {{ $stats['ultima_fecha'] ? $stats['ultima_fecha']->format('d/m/Y') : 'Sin registros' }}
                        </h6>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🚀 BOTÓN DE ACTUALIZACIÓN MASIVA -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="stat-card bg-primary bg-opacity-10 border border-primary border-opacity-25">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary text-white rounded-3 p-3">
                            <i class="bi bi-arrow-repeat fs-3"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-primary">Actualización Masiva de Precios</h6>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Actualiza todos los precios de los productos según la tasa del día.
                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Directo</span>
                            </p>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-box me-1"></i> 
                                    <span id="totalProductos">{{ $stats['total_productos'] ?? 0 }}</span> productos
                                </span>
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-currency-exchange me-1"></i> 
                                    {{ $stats['monedas_activas'] ?? 0 }} monedas
                                </span>
                                <span class="badge bg-light text-dark" id="ultimaActualizacion">
                                    <i class="bi bi-clock me-1"></i> 
                                    Última: {{ $stats['ultima_actualizacion'] ?? 'Nunca' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" 
                                class="btn btn-primary btn-lg px-4"
                                @click="actualizarPreciosMasivos()"
                                :disabled="actualizando">
                            <span x-show="!actualizando">
                                <i class="bi bi-arrow-repeat me-2"></i> Actualizar Precios
                            </span>
                            <span x-show="actualizando">
                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Actualizando...
                            </span>
                        </button>
                        <a href="{{ route('tasas.historial') }}" 
                           class="btn btn-outline-secondary align-self-center">
                            <i class="bi bi-clock-history me-1"></i> Historial
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Acciones -->
    <div class="stat-card mb-3">
        <form method="GET" action="{{ route('tasas.index') }}" class="row g-2 align-items-end" id="filterForm">
            <div class="col-12 col-md-2">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Moneda, tasa, usuario..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Moneda</label>
                <select name="moneda_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($monedas as $moneda)
                        <option value="{{ $moneda->id }}" {{ request('moneda_id') == $moneda->id ? 'selected' : '' }}>
                            {{ $moneda->nombre }} ({{ $moneda->codigo }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control" 
                       value="{{ request('fecha_desde') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" 
                       value="{{ request('fecha_hasta') }}">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label">Tasa Mín.</label>
                <input type="number" step="0.000001" name="tasa_min" class="form-control" 
                       placeholder="0.00" value="{{ request('tasa_min') }}">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label">Tasa Máx.</label>
                <input type="number" step="0.000001" name="tasa_max" class="form-control" 
                       placeholder="0.00" value="{{ request('tasa_max') }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <button type="button" class="btn btn-outline-secondary" 
                        onclick="window.location.href='{{ route('tasas.index') }}'">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Tasas de Cambio -->
    <div class="stat-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-currency-exchange me-2"></i>
                Listado de Tasas de Cambio
                <span class="badge bg-primary-subtle text-primary-emphasis ms-2">
                    {{ $tasasCambio->total() }} registros
                </span>
            </h6>
            <div class="d-flex gap-2 flex-wrap">
                <!-- Selector de registros por página -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Mostrar:</span>
                    <select name="per_page" class="form-select form-select-sm" 
                            style="width: auto;" 
                            onchange="window.location.href='{{ route('tasas.index') }}?per_page='+this.value+'&{{ http_build_query(request()->except('per_page')) }}'">
                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                
                <a href="{{ route('tasas.create') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Tasa
                </a>
                <button class="btn btn-sm btn-outline-secondary" @click="exportarCSV()">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">
                            <a href="{{ route('tasas.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                               class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                #
                                @if(request('sort') == 'id')
                                    <i class="bi bi-chevron-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th style="width: 25%;">
                            <a href="{{ route('tasas.index', array_merge(request()->all(), ['sort' => 'moneda_id', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                               class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Moneda
                                @if(request('sort') == 'moneda_id')
                                    <i class="bi bi-chevron-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th style="width: 20%;" class="text-end">
                            <a href="{{ route('tasas.index', array_merge(request()->all(), ['sort' => 'tasa', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                               class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1">
                                Tasa
                                @if(request('sort') == 'tasa')
                                    <i class="bi bi-chevron-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th style="width: 20%;">
                            <a href="{{ route('tasas.index', array_merge(request()->all(), ['sort' => 'fecha', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                               class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Fecha
                                @if(request('sort') == 'fecha')
                                    <i class="bi bi-chevron-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th style="width: 15%;">Registrado por</th>
                        <th style="width: 15%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasasCambio as $tasa)
                        <tr>
                            <td>{{ $loop->iteration + ($tasasCambio->currentPage() - 1) * $tasasCambio->perPage() }}</td>
                            <td>
                                <div>
                                    <span class="fw-semibold">{{ $tasa->moneda->nombre }}</span>
                                    <br>
                                    <small class="text-muted">{{ $tasa->moneda->codigo }}</small>
                                    @if($tasa->moneda->es_base)
                                        <span class="badge bg-primary ms-1">Base</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-bold">
                                <span class="text-primary">{{ number_format($tasa->tasa, 6) }}</span>
                            </td>
                            <td>
                                <span>{{ $tasa->fecha->format('d/m/Y') }}</span>
                                <br>
                                <small class="text-muted">{{ $tasa->fecha->format('H:i') }}</small>
                            </td>
                            <td>
                                <span>{{ $tasa->user ? $tasa->user->name : 'N/A' }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('tasas.show', $tasa->id) }}" 
                                       class="btn btn-outline-primary" 
                                       title="Ver Detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <a href="{{ route('tasas.edit', $tasa->id) }}" 
                                       class="btn btn-outline-warning" 
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a href="{{ route('tasas.duplicate', $tasa->id) }}" 
                                       class="btn btn-outline-info" 
                                       title="Duplicar">
                                        <i class="bi bi-copy"></i>
                                    </a>
                                    
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            title="Eliminar"
                                            @click="eliminarTasa({{ $tasa->id }})">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-currency-exchange fs-1 d-block mb-3 text-muted"></i>
                                <h6 class="text-muted">No hay tasas de cambio registradas</h6>
                                <p class="text-muted small">Registra la primera tasa de cambio</p>
                                <a href="{{ route('tasas.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-lg me-1"></i> Nueva Tasa
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación Mejorada -->
        @if($tasasCambio->hasPages())
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Mostrando 
                        <strong>{{ $tasasCambio->firstItem() ?? 0 }}</strong> 
                        - 
                        <strong>{{ $tasasCambio->lastItem() ?? 0 }}</strong> 
                        de 
                        <strong>{{ $tasasCambio->total() }}</strong> 
                        registros
                        <span class="text-muted">(Página {{ $tasasCambio->currentPage() }} de {{ $tasasCambio->lastPage() }})</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <!-- Navegación rápida -->
                        <div class="d-flex gap-1">
                            <a href="{{ $tasasCambio->url(1) }}" 
                               class="btn btn-sm btn-outline-secondary {{ $tasasCambio->currentPage() == 1 ? 'disabled' : '' }}"
                               title="Primera página">
                                <i class="bi bi-chevron-double-left"></i>
                            </a>
                            
                            <a href="{{ $tasasCambio->previousPageUrl() }}" 
                               class="btn btn-sm btn-outline-secondary {{ $tasasCambio->currentPage() == 1 ? 'disabled' : '' }}"
                               title="Página anterior">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            
                            <!-- Ir a página específica -->
                            <form method="GET" action="{{ route('tasas.index') }}" class="d-flex align-items-center gap-1">
                                @foreach(request()->except(['page', 'go_to_page']) as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <span class="text-muted small">Ir a</span>
                                <input type="number" 
                                       name="page" 
                                       class="form-control form-control-sm" 
                                       style="width: 60px;"
                                       min="1" 
                                       max="{{ $tasasCambio->lastPage() }}"
                                       value="{{ $tasasCambio->currentPage() }}"
                                       onchange="this.form.submit()">
                                <span class="text-muted small">de {{ $tasasCambio->lastPage() }}</span>
                            </form>
                            
                            <a href="{{ $tasasCambio->nextPageUrl() }}" 
                               class="btn btn-sm btn-outline-secondary {{ $tasasCambio->currentPage() == $tasasCambio->lastPage() ? 'disabled' : '' }}"
                               title="Página siguiente">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            
                            <a href="{{ $tasasCambio->url($tasasCambio->lastPage()) }}" 
                               class="btn btn-sm btn-outline-secondary {{ $tasasCambio->currentPage() == $tasasCambio->lastPage() ? 'disabled' : '' }}"
                               title="Última página">
                                <i class="bi bi-chevron-double-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Paginación Bootstrap -->
                <div class="d-flex justify-content-center mt-2">
                    {{ $tasasCambio->links('pagination::bootstrap-5') }}
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
    function tasasCambioIndex() {
        return {
            modalTitle: '',
            modalMessage: '',
            modalButtonText: '',
            modalButtonClass: '',
            accionId: null,
            modal: null,
            actualizando: false,
            
            init() {
                this.modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            },
            
            async actualizarPreciosMasivos() {
                // Confirmar antes de ejecutar
                if (!confirm('⚠️ ¿Estás seguro de actualizar todos los precios?\n\nEsta acción recalculará los precios de todos los productos en todas las monedas según la tasa del día.\n\nEste proceso puede tomar varios minutos dependiendo de la cantidad de productos.')) {
                    return;
                }
                
                this.actualizando = true;
                
                try {
                    // 1. Obtener la tasa más reciente
                    const response = await fetch('/tasas/ultimas');
                    
                    if (!response.ok) {
                        throw new Error('Error al obtener las tasas');
                    }
                    
                    const tasas = await response.json();
                    
                    if (!tasas || tasas.length === 0) {
                        alert('❌ No hay tasas de cambio registradas. Por favor, registra una tasa primero.');
                        this.actualizando = false;
                        return;
                    }
                    
                    // Buscar la primera tasa que no sea base
                    const tasaSeleccionada = tasas.find(t => !t.es_base);
                    
                    if (!tasaSeleccionada) {
                        alert('❌ No hay tasas para monedas no base. Registra una tasa para una moneda diferente a la base.');
                        this.actualizando = false;
                        return;
                    }
                    
                    // 2. Confirmar la tasa a usar
                    if (!confirm(`📊 ¿Usar la tasa de ${tasaSeleccionada.codigo}?\n\nTasa: ${tasaSeleccionada.tasa}\nFecha: ${tasaSeleccionada.fecha}\n\nSe actualizarán TODOS los productos.`)) {
                        this.actualizando = false;
                        return;
                    }
                    
                    // 3. Ejecutar la actualización
                    const updateResponse = await fetch('/tasas/actualizar-precios', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            tasa_cambio_id: tasaSeleccionada.id
                        })
                    });
                    
                    const result = await updateResponse.json();
                    
                    if (result.success) {
                        // Mostrar mensaje de éxito
                        alert(`✅ ${result.message}`);
                        // Recargar la página para ver los cambios
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert(`❌ ${result.message}`);
                        this.actualizando = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('❌ Error de conexión al servidor. Intenta nuevamente.');
                    this.actualizando = false;
                }
            },
            
            eliminarTasa(id) {
                this.accionId = id;
                this.modalTitle = '⚠️ Eliminar Tasa de Cambio';
                this.modalMessage = '¿Estás seguro de que deseas eliminar esta tasa de cambio? Esta acción no se puede deshacer.';
                this.modalButtonText = 'Sí, Eliminar';
                this.modalButtonClass = 'btn-danger';
                this.modal.show();
            },
            
            async confirmarAccion() {
                const id = this.accionId;
                
                if (!id) return;
                
                try {
                    const response = await fetch(`/tasas/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('✅ ' + (result.message || 'Tasa eliminada exitosamente'));
                        this.modal.hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('❌ ' + (result.message || 'Error al eliminar la tasa'));
                        this.modal.hide();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('❌ Error de conexión al servidor');
                    this.modal.hide();
                }
            },
            
            exportarCSV() {
                const params = new URLSearchParams(window.location.search);
                window.location.href = `/tasas/export?${params.toString()}`;
            }
        }
    }
</script>
@endpush
@endsection