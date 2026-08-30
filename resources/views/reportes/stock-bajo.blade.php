@extends('layouts.app')

@section('title', 'Productos con Stock Bajo')
@section('page-title', 'Productos con Stock Bajo')

@section('content')
<div x-data="reporteStockBajo()">
    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Stock Bajo</span>
                        <h3 class="mb-0 mt-1 fw-bold text-warning">
                            {{ number_format($estadisticas['total_productos_bajo']) }}
                        </h3>
                    </div>
                    <div class="stat-icon yellow">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Sin Stock</span>
                        <h3 class="mb-0 mt-1 fw-bold text-danger">
                            {{ number_format($estadisticas['total_sin_stock']) }}
                        </h3>
                    </div>
                    <div class="stat-icon red">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Total Productos</span>
                        <h3 class="mb-0 mt-1 fw-bold">
                            {{ number_format($estadisticas['total_productos']) }}
                        </h3>
                    </div>
                    <div class="stat-icon blue">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Categorías Afectadas</span>
                        <h3 class="mb-0 mt-1 fw-bold">
                            {{ number_format($estadisticas['categorias_afectadas']) }}
                        </h3>
                    </div>
                    <div class="stat-icon purple">
                        <i class="bi bi-tags"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="no-print mb-3">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" @click="imprimirReporte()">
                <i class="bi bi-printer me-1"></i> Imprimir Reporte
            </button>
            {{-- <button class="btn btn-success" @click="exportarExcel()">
                <i class="bi bi-file-excel me-1"></i> Exportar Excel
            </button>
            <a href="{{ route('reportes.inventario.pdf') }}" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf me-1"></i> Exportar PDF
            </a> --}}
            <button class="btn btn-outline-secondary" onclick="window.location.href='{{ route('reportes.inventario') }}'">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Contenido del Reporte -->
    <div class="stat-card" id="reporteContent">
        <!-- Encabezado del Reporte (visible solo en impresión) -->
        <div class="report-header print-only">
            <div class="text-center mb-4">
                <h1 class="fw-bold" style="color: #1e293b;">Reporte de Stock Bajo</h1>
                <p class="text-muted">Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
                <hr>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-list me-2"></i>
                Lista de Productos con Stock Bajo
                <span class="badge bg-primary-subtle text-primary-emphasis ms-2">
                    {{ $productos->total() }} productos
                </span>
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaReporte">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Producto</th>
                        <th style="width: 20%;">Categoría</th>
                        <th style="width: 15%;" class="text-end">Stock Actual</th>
                        <th style="width: 15%;" class="text-end">Stock Mínimo</th>
                        <th style="width: 10%;" class="text-center">Estado</th>
                        <th style="width: 10%;" class="text-center no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div>
                                    <span class="fw-semibold">{{ $producto->nombre }}</span>
                                    <br>
                                    <small class="text-muted">{{ $producto->sku }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    {{ $producto->categoria->nombre }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-warning">
                                    {{ number_format($producto->stock_kg, 3) }} Kg
                                </span>
                            </td>
                            <td class="text-end">
                                {{ number_format($producto->stock_minimo, 3) }} Kg
                            </td>
                            <td class="text-center">
                                @if($producto->stock_kg <= 0)
                                    <span class="badge-status sin-stock">Sin Stock</span>
                                @elseif($producto->stock_kg <= 1)
                                    <span class="badge-status stock-bajo">Crítico</span>
                                @else
                                    <span class="badge-status stock-bajo">Stock Bajo</span>
                                @endif
                            </td>
                            <td class="text-center no-print">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('productos.show', $producto) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('update', $producto)
                                        <a href="{{ route('productos.edit', $producto) }}" 
                                           class="btn btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                                <h6>¡Excelente! No hay productos con stock bajo</h6>
                                <p class="small">Todos los productos tienen stock suficiente</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="print-only">
                    <tr>
                        <td colspan="7" class="text-center pt-3">
                            <hr>
                            <small class="text-muted">
                                Reporte generado automáticamente - {{ now()->format('d/m/Y H:i:s') }}
                            </small>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Paginación -->
        @if($productos->hasPages())
            <div class="mt-3 d-flex justify-content-end no-print">
                {{ $productos->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    /* Estilos para impresión */
    @media print {
        /* Ocultar elementos no imprimibles */
        .no-print {
            display: none !important;
        }
        
        /* Mostrar elementos solo en impresión */
        .print-only {
            display: block !important;
        }
        
        /* Resetear estilos para impresión */
        body {
            background: white !important;
            font-size: 11px !important;
        }
        
        .stat-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            padding: 15px !important;
            margin-bottom: 15px !important;
            border-radius: 0 !important;
        }
        
        .table {
            font-size: 10px !important;
        }
        
        .table th {
            background: #f8f9fa !important;
            color: #000 !important;
            font-weight: bold !important;
        }
        
        .table td {
            padding: 5px 8px !important;
        }
        
        .badge-status {
            padding: 2px 8px !important;
            border-radius: 4px !important;
            font-size: 9px !important;
        }
        
        .badge-status.sin-stock {
            background: #fee2e2 !important;
            color: #991b1b !important;
        }
        
        .badge-status.stock-bajo {
            background: #fef3c7 !important;
            color: #92400e !important;
        }
        
        .report-header {
            margin-bottom: 20px !important;
        }
        
        .report-header h1 {
            font-size: 18px !important;
            margin-bottom: 5px !important;
        }
        
        .report-header p {
            font-size: 11px !important;
            color: #666 !important;
        }
        
        /* Evitar saltos de página dentro de la tabla */
        table {
            page-break-inside: auto !important;
        }
        
        tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }
        
        thead {
            display: table-header-group !important;
        }
        
        tfoot {
            display: table-footer-group !important;
        }
        
        /* Configuración de página */
        @page {
            margin: 15mm 10mm 15mm 10mm !important;
            size: A4 portrait !important;
        }
    }
    
    /* Estilos para pantalla */
    .print-only {
        display: none !important;
    }
</style>

@push('scripts')
<script>
    function reporteStockBajo() {
        return {
            imprimirReporte() {
                window.print();
            },
            exportarExcel() {
                // Función para exportar a Excel usando la tabla
                const tabla = document.getElementById('tablaReporte');
                const filas = tabla.querySelectorAll('tr');
                let contenido = '';
                
                // Obtener encabezados
                const headers = tabla.querySelectorAll('thead th');
                let headerRow = [];
                headers.forEach(th => {
                    // Omitir la columna de acciones
                    if (!th.classList.contains('no-print')) {
                        headerRow.push(th.textContent.trim());
                    }
                });
                contenido += headerRow.join('\t') + '\n';
                
                // Obtener datos
                const tbody = tabla.querySelector('tbody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        let rowData = [];
                        cells.forEach((td, index) => {
                            // Omitir la columna de acciones (última columna)
                            if (index < cells.length - 1) {
                                let text = td.textContent.trim();
                                // Limpiar texto de badges
                                text = text.replace(/Sin Stock|Crítico|Stock Bajo/g, '').trim();
                                rowData.push(text);
                            }
                        });
                        if (rowData.length > 0) {
                            contenido += rowData.join('\t') + '\n';
                        }
                    });
                }
                
                // Crear y descargar archivo
                const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `stock_bajo_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
                
                toast('Éxito', 'Reporte exportado a Excel', 'success');
            }
        }
    }
</script>
@endpush
@endsection