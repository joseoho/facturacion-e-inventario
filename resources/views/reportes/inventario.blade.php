@extends('layouts.app')

@section('title', 'Reporte de Inventario')
@section('page-title', 'Reporte de Inventario')

@section('content')
<div x-data="reporteInventario()">
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
        </div>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Total Productos</span>
                        <h3 class="mb-0 mt-1 fw-bold">{{ number_format($totales['total_productos']) }}</h3>
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
                        <span class="text-muted small text-uppercase fw-semibold">Valor Inventario</span>
                        <h3 class="mb-0 mt-1 fw-bold">${{ number_format($totales['valor_total'], 2) }}</h3>
                    </div>
                    <div class="stat-icon green">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Cantidad Total</span>
                        <h3 class="mb-0 mt-1 fw-bold">{{ number_format($totales['cantidad_total'], 2) }} Kg</h3>
                    </div>
                    <div class="stat-icon purple">
                        <i class="bi bi-weight-scale"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Categorías</span>
                        <h3 class="mb-0 mt-1 fw-bold">{{ number_format($totales['total_categorias']) }}</h3>
                    </div>
                    <div class="stat-icon yellow">
                        <i class="bi bi-tags"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido del Reporte -->
    <div class="stat-card" id="reporteContent">
        <!-- Encabezado del Reporte -->
        <div class="report-header print-only">
            <div class="text-center mb-4">
                <h1 class="fw-bold" style="color: #1e293b;">Reporte de Inventario</h1>
                <p class="text-muted">Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
                <hr>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-list me-2"></i>
                Lista de Productos en Inventario
                <span class="badge bg-primary-subtle text-primary-emphasis ms-2">
                    {{ $productos->total() }} productos
                </span>
            </h6>
            <div class="no-print">
                <input type="text" class="form-control form-control-sm" placeholder="Buscar..." 
                       style="width: 200px;" x-model="busqueda" @input="filtrarTabla()">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaInventario">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Producto</th>
                        <th style="width: 20%;">Categoría</th>
                        <th style="width: 15%;" class="text-end">Stock (Kg)</th>
                        <th style="width: 15%;" class="text-end">Precio USD</th>
                        <th style="width: 20%;" class="text-end">Valor Total</th>
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
                                @if($producto->stock_kg <= 0)
                                    <span class="text-danger fw-bold">{{ number_format($producto->stock_kg, 3) }}</span>
                                @elseif($producto->stock_kg <= 5)
                                    <span class="text-warning fw-bold">{{ number_format($producto->stock_kg, 3) }}</span>
                                @else
                                    <span>{{ number_format($producto->stock_kg, 3) }}</span>
                                @endif
                            </td>
                            <td class="text-end">${{ number_format($producto->precio_kg_usd, 2) }}</td>
                            <td class="text-end fw-semibold">
                                ${{ number_format($producto->precio_kg_usd * $producto->stock_kg, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No hay productos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">TOTALES:</td>
                        <td class="text-end">{{ number_format($totales['cantidad_total'], 2) }} Kg</td>
                        <td colspan="2" class="text-end">${{ number_format($totales['valor_total'], 2) }}</td>
                    </tr>
                    <tr class="print-only">
                        <td colspan="6" class="text-center pt-3">
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
    @media print {
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        body {
            background: white !important;
            font-size: 10px !important;
        }
        .stat-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            padding: 12px !important;
        }
        .table {
            font-size: 9px !important;
        }
        .table th {
            background: #f8f9fa !important;
            color: #000 !important;
        }
        .table td {
            padding: 4px 6px !important;
        }
        .report-header h1 {
            font-size: 16px !important;
        }
        @page {
            margin: 10mm 8mm 10mm 8mm !important;
        }
    }
    .print-only {
        display: none !important;
    }
</style>

@push('scripts')
<script>
    function reporteInventario() {
        return {
            busqueda: '',
            imprimirReporte() {
                window.print();
            },
            filtrarTabla() {
                const busqueda = this.busqueda.toLowerCase();
                const filas = document.querySelectorAll('#tablaInventario tbody tr');
                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = texto.includes(busqueda) ? '' : 'none';
                });
            },
            exportarExcel() {
                const tabla = document.getElementById('tablaInventario');
                let contenido = '';
                
                // Encabezados
                const headers = tabla.querySelectorAll('thead th');
                let headerRow = [];
                headers.forEach(th => {
                    headerRow.push(th.textContent.trim());
                });
                contenido += headerRow.join('\t') + '\n';
                
                // Datos
                const rows = tabla.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    let rowData = [];
                    cells.forEach(td => {
                        rowData.push(td.textContent.trim());
                    });
                    if (rowData.length > 0) {
                        contenido += rowData.join('\t') + '\n';
                    }
                });
                
                // Totales
                const tfoot = tabla.querySelector('tfoot');
                if (tfoot) {
                    const footRows = tfoot.querySelectorAll('tr');
                    footRows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        let rowData = [];
                        cells.forEach(td => {
                            rowData.push(td.textContent.trim());
                        });
                        if (rowData.length > 0) {
                            contenido += rowData.join('\t') + '\n';
                        }
                    });
                }
                
                const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `inventario_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
                
                toast('Éxito', 'Inventario exportado a Excel', 'success');
            }
        }
    }
</script>
@endpush
@endsection