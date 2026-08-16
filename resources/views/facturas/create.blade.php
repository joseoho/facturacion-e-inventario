@extends('layouts.app')

@section('title', 'Nueva Factura')
@section('page-title', 'Nueva Factura')

@section('content')
<!-- Contenedor principal con x-data -->
<div x-data="facturaCreator()" x-init="init()" x-cloak>
    <form @submit.prevent="submitFactura" id="facturaForm">
        @csrf
        
        <div class="row g-3">
            <!-- Columna Izquierda - Datos -->
            <div class="col-lg-4">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-2"></i> Datos de la Factura
                    </h6>
                    
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label class="form-label">Cliente <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="cliente_id" required>
                            <option value="">Seleccionar cliente...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">
                                    {{ $cliente->nombre }} ({{ $cliente->documento }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Moneda -->
                    <div class="mb-3">
                        <label class="form-label">Moneda <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="moneda_id" @change="onMonedaChange($event)" required>
                            <option value="">Seleccionar moneda...</option>
                            @foreach($monedas as $moneda)
                                <option value="{{ $moneda->id }}">
                                    {{ $moneda->nombre }} ({{ $moneda->codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Fecha -->
                    <div class="mb-3">
                        <label class="form-label">Fecha de Emisión <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" x-model="fecha_emision" 
                               :max="new Date().toISOString().split('T')[0]" required>
                    </div>
                    
                    <!-- Resumen -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal Neto:</span>
                            <span class="fw-semibold" x-text="formatNumber(subtotal_neto)"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total IVA:</span>
                            <span class="fw-semibold" x-text="formatNumber(total_impuesto)"></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total General:</span>
                            <span class="fw-bold fs-5 text-primary" x-text="formatNumber(total_general)"></span>
                        </div>
                        
                        <div class="text-muted small mt-1" x-show="moneda_seleccionada && moneda_seleccionada.codigo">
                            Moneda: <span x-text="moneda_seleccionada.codigo"></span>
                        </div>
                        <div class="text-muted small mt-1" x-show="!moneda_seleccionada || !moneda_seleccionada.codigo">
                            <i class="bi bi-info-circle"></i> Selecciona una moneda
                        </div>
                        
                        <div class="text-muted small" x-show="items.length > 0">
                            Productos: <span x-text="items.length"></span>
                        </div>
                    </div>
                    
                    <!-- Botón Guardar -->
                    <button type="submit" class="btn btn-primary w-100 mt-3" :disabled="loading || items.length === 0">
                        <span x-show="!loading">
                            <i class="bi bi-check-lg me-2"></i> Crear Factura
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm me-2"></span> Procesando...
                        </span>
                    </button>
                </div>
            </div>
            
            <!-- Columna Derecha - Productos -->
            <div class="col-lg-8">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-cart-plus me-2"></i> Agregar Productos
                    </h6>
                    
                    <!-- Buscador -->
                    <div class="mb-3 position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   x-model="busqueda" 
                                   @input.debounce="buscarProductos()"
                                   @focus="mostrarResultados = true"
                                   @keydown.escape="cerrarResultados"
                                   placeholder="Buscar producto por nombre o SKU...">
                            <button class="btn btn-outline-secondary" type="button" @click="limpiarBusqueda">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        
                        <!-- Resultados de búsqueda -->
                        <div x-show="mostrarResultados && productosBusqueda.length > 0" 
                             class="position-absolute top-100 start-0 end-0 mt-1 bg-white border rounded-3 shadow-lg z-3"
                             style="max-height: 300px; overflow-y: auto;"
                             @click.away="cerrarResultados">
                            <div class="list-group list-group-flush">
                                <template x-for="(producto, index) in productosBusqueda" :key="index">
                                    <div class="list-group-item list-group-item-action px-3 py-2" 
                                         style="cursor: pointer; border-bottom: 1px solid #e9ecef;"
                                         @click="agregarProducto(producto)">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-semibold" x-text="producto.nombre"></span>
                                                <div class="small text-muted">
                                                    <span x-text="producto.sku"></span>
                                                    <span class="mx-1">|</span>
                                                    Stock: <span x-text="Number(producto.stock_kg || 0).toFixed(3)"></span> Kg
                                                    <span class="mx-1">|</span>
                                                    <span x-text="'Precio: ' + formatNumber(producto.precio_kg)"></span>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-primary" @click.stop="agregarProducto(producto)">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Sin resultados -->
                        <div x-show="mostrarResultados && productosBusqueda.length === 0 && busqueda.length >= 2" 
                             class="position-absolute top-100 start-0 end-0 mt-1 bg-white border rounded-3 shadow-lg p-3 text-center text-muted z-3">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No se encontraron productos
                        </div>
                    </div>
                    
                    <!-- Tabla de Items -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Producto</th>
                                    <th style="width: 20%;">Cantidad (Kg)</th>
                                    <th style="width: 18%;" class="text-end">Precio/Kg</th>
                                    <th style="width: 15%;" class="text-end">IVA %</th>
                                    <th style="width: 12%;" class="text-end">Total</th>
                                    <th style="width: 5%;" class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <div>
                                                <span class="fw-semibold" x-text="item.nombre"></span>
                                                <br>
                                                <small class="text-muted" x-text="item.sku"></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       class="form-control text-end" 
                                                       x-model="item.cantidad_kg"
                                                       @input="calcularItem(index)"
                                                       step="0.001"
                                                       min="0.001"
                                                       :max="item.stock_kg"
                                                       required>
                                                <span class="input-group-text">Kg</span>
                                            </div>
                                            <small class="text-muted" x-show="parseFloat(item.cantidad_kg) > parseFloat(item.stock_kg)">
                                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                                Stock: <span x-text="Number(item.stock_kg).toFixed(3)"></span> Kg
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <span x-text="formatNumber(item.precio_kg)"></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis" 
                                                  x-text="item.iva_porcentaje + '%'"></span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <span x-text="formatNumber(item.total_linea)"></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger" @click="eliminarItem(index)">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-cart-plus fs-3 d-block mb-2"></i>
                                        Agrega productos usando el buscador de arriba
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    [x-cloak] { display: none !important; }
    
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
    }
    
    .toast-custom {
        background: #fff;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        border-left: 4px solid #2563eb;
        margin-bottom: 0.75rem;
        animation: slideInRight 0.4s ease;
    }
    
    .toast-custom.success { border-left-color: #059669; }
    .toast-custom.error { border-left-color: #dc2626; }
    .toast-custom.warning { border-left-color: #d97706; }
    .toast-custom.info { border-left-color: #2563eb; }
    
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('facturaCreator', () => ({
            // ========== DATOS DEL FORMULARIO ==========
            cliente_id: '',
            moneda_id: '',
            fecha_emision: new Date().toISOString().split('T')[0],
            items: [],
            subtotal_neto: 0,
            total_impuesto: 0,
            total_general: 0,
            
            // ========== BÚSQUEDA ==========
            busqueda: '',
            productosBusqueda: [],
            mostrarResultados: false,
            loading: false,
            buscando: false,
            
            // ========== SELECTORES ==========
            moneda_seleccionada: null,
            
            // ========== INICIALIZACIÓN ==========
            init() {
                // Cargar moneda por defecto después de que el DOM esté listo
                this.$nextTick(() => {
                    this.cargarMonedaPorDefecto();
                });
            },
            
            // ========== MÉTODOS ==========
            
            // Cargar moneda por defecto
            cargarMonedaPorDefecto() {
                const select = document.querySelector('select[name="moneda_id"]');
                if (select) {
                    // Buscar la primera opción con valor
                    for (let option of select.options) {
                        if (option.value) {
                            this.moneda_id = option.value;
                            this.actualizarMonedaSeleccionada(select);
                            break;
                        }
                    }
                }
            },
            
            // Actualizar moneda seleccionada desde el select
            actualizarMonedaSeleccionada(select) {
                if (!select) return;
                
                const selected = select.options[select.selectedIndex];
                if (selected && selected.value) {
                    const match = selected.text.match(/\(([^)]+)\)/);
                    const codigo = match ? match[1] : 'USD';
                    this.moneda_seleccionada = {
                        id: this.moneda_id,
                        codigo: codigo
                    };
                } else {
                    this.moneda_seleccionada = null;
                }
            },
            
            // Cambio de moneda - CORREGIDO
            onMonedaChange(event) {
                const select = event ? event.target : document.querySelector('select[name="moneda_id"]');
                if (select) {
                    this.moneda_id = select.value;
                    this.actualizarMonedaSeleccionada(select);
                    this.recalcularTodo();
                }
            },
            
            // Buscar productos
            async buscarProductos() {
                if (this.busqueda.length < 2) {
                    this.productosBusqueda = [];
                    this.mostrarResultados = false;
                    return;
                }
                
                this.buscando = true;
                
                try {
                    const url = `{{ route('facturas.buscar-productos') }}?q=${encodeURIComponent(this.busqueda)}&moneda_id=${this.moneda_id}`;
                    const response = await fetch(url);
                    
                    // Verificar si la respuesta es JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta no es JSON');
                    }
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    this.productosBusqueda = data || [];
                    this.mostrarResultados = this.productosBusqueda.length > 0;
                } catch (error) {
                    console.error('Error al buscar productos:', error);
                    this.productosBusqueda = [];
                    this.mostrarResultados = false;
                    this.mostrarToast('Error', 'No se pudieron cargar los productos', 'error');
                } finally {
                    this.buscando = false;
                }
            },
            
            // Limpiar búsqueda
            limpiarBusqueda() {
                this.busqueda = '';
                this.productosBusqueda = [];
                this.mostrarResultados = false;
            },
            
            // Cerrar resultados
            cerrarResultados() {
                this.mostrarResultados = false;
            },
            
            // Agregar producto al carrito
            agregarProducto(producto) {
                if (!producto || !producto.id) {
                    this.mostrarToast('Error', 'Producto inválido', 'error');
                    return;
                }
                
                // Verificar si ya existe
                const existente = this.items.find(item => item.id === producto.id);
                if (existente) {
                    existente.cantidad_kg = parseFloat(existente.cantidad_kg || 0) + 0.5;
                    const index = this.items.indexOf(existente);
                    this.calcularItem(index);
                    this.mostrarToast('Info', `Se agregó 0.5 Kg más de ${producto.nombre}`, 'info');
                    this.cerrarResultados();
                    return;
                }
                
                // Verificar stock
                const stock = parseFloat(producto.stock_kg || 0);
                if (stock <= 0) {
                    this.mostrarToast('Error', 'El producto no tiene stock disponible', 'error');
                    return;
                }
                
                // Agregar nuevo producto
                this.items.push({
                    id: producto.id,
                    nombre: producto.nombre || 'Sin nombre',
                    sku: producto.sku || 'N/A',
                    precio_kg: parseFloat(producto.precio_kg) || 0,
                    cantidad_kg: 0.250,
                    iva_porcentaje: parseFloat(producto.iva_porcentaje) || 0,
                    total_linea: 0,
                    stock_kg: stock
                });
                
                const index = this.items.length - 1;
                this.calcularItem(index);
                this.cerrarResultados();
                this.limpiarBusqueda();
                
                this.mostrarToast('Éxito', `Producto "${producto.nombre}" agregado`, 'success');
            },
            
            // Calcular item individual
            calcularItem(index) {
                const item = this.items[index];
                if (!item) return;
                
                let cantidad = parseFloat(item.cantidad_kg) || 0;
                const precio = parseFloat(item.precio_kg) || 0;
                const iva = parseFloat(item.iva_porcentaje) || 0;
                const stock = parseFloat(item.stock_kg) || 0;
                
                // Validar cantidad mínima
                if (cantidad < 0.001) {
                    cantidad = 0.001;
                    item.cantidad_kg = cantidad;
                }
                
                // Validar stock
                if (cantidad > stock && stock > 0) {
                    cantidad = stock;
                    item.cantidad_kg = cantidad;
                    this.mostrarToast('Advertencia', `Stock limitado a ${stock.toFixed(3)} Kg`, 'warning');
                }
                
                const neto = precio * cantidad;
                const impuesto = neto * (iva / 100);
                item.total_linea = neto + impuesto;
                
                this.recalcularTodo();
            },
            
            // Eliminar item del carrito
            eliminarItem(index) {
                const item = this.items[index];
                if (item) {
                    this.items.splice(index, 1);
                    this.recalcularTodo();
                    this.mostrarToast('Info', `Producto "${item.nombre}" eliminado`, 'info');
                }
            },
            
            // Recalcular todos los totales
            recalcularTodo() {
                this.subtotal_neto = 0;
                this.total_impuesto = 0;
                this.total_general = 0;
                
                this.items.forEach(item => {
                    const cantidad = parseFloat(item.cantidad_kg) || 0;
                    const precio = parseFloat(item.precio_kg) || 0;
                    const iva = parseFloat(item.iva_porcentaje) || 0;
                    
                    const neto = precio * cantidad;
                    const impuesto = neto * (iva / 100);
                    
                    this.subtotal_neto += neto;
                    this.total_impuesto += impuesto;
                    this.total_general += neto + impuesto;
                });
            },
            
            // Enviar factura
            async submitFactura() {
                // Validaciones
                if (!this.cliente_id) {
                    this.mostrarToast('Error', 'Selecciona un cliente', 'error');
                    return;
                }
                
                if (!this.moneda_id) {
                    this.mostrarToast('Error', 'Selecciona una moneda', 'error');
                    return;
                }
                
                if (this.items.length === 0) {
                    this.mostrarToast('Error', 'Agrega al menos un producto', 'error');
                    return;
                }
                
                // Validar que todos los items tengan cantidad válida
                const itemsInvalidos = this.items.filter(item => parseFloat(item.cantidad_kg || 0) <= 0);
                if (itemsInvalidos.length > 0) {
                    this.mostrarToast('Error', 'Todos los productos deben tener cantidad mayor a 0', 'error');
                    return;
                }
                
                this.loading = true;
                
                try {
                    const formData = {
                        cliente_id: this.cliente_id,
                        moneda_id: this.moneda_id,
                        fecha_emision: this.fecha_emision,
                        productos: this.items.map(item => ({
                            producto_id: item.id,
                            cantidad_kg: parseFloat(item.cantidad_kg),
                            precio_kg: parseFloat(item.precio_kg)
                        }))
                    };
                    
                    const response = await fetch('{{ route("facturas.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(formData)
                    });
                    
                    // Verificar si la respuesta es JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Respuesta no JSON:', text);
                        throw new Error('La respuesta del servidor no es JSON');
                    }
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.mostrarToast('Éxito', result.message, 'success');
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 1000);
                    } else {
                        this.mostrarToast('Error', result.message || 'Error al crear la factura', 'error');
                        this.loading = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.mostrarToast('Error', 'Error de conexión al servidor: ' + error.message, 'error');
                    this.loading = false;
                }
            },
            
            // ========== UTILIDADES ==========
            
            // Formatear números
            formatNumber(valor) {
                if (valor === undefined || valor === null || isNaN(valor)) {
                    return '0.00';
                }
                return Number(valor).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },
            
            // Mostrar toast
            mostrarToast(titulo, mensaje, tipo = 'info') {
                const container = document.getElementById('toastContainer');
                if (!container) {
                    console.warn('Toast container no encontrado');
                    return;
                }
                
                const colores = {
                    success: 'success',
                    error: 'error',
                    warning: 'warning',
                    info: 'info'
                };
                
                const iconos = {
                    success: 'bi-check-circle-fill text-success',
                    error: 'bi-x-circle-fill text-danger',
                    warning: 'bi-exclamation-triangle-fill text-warning',
                    info: 'bi-info-circle-fill text-info'
                };
                
                const toast = document.createElement('div');
                toast.className = `toast-custom ${colores[tipo] || 'info'}`;
                toast.innerHTML = `
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi ${iconos[tipo] || iconos.info} fs-5"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block text-dark">${titulo}</strong>
                            <span class="text-muted small">${mensaje}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                
                container.appendChild(toast);
                
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'all 0.5s ease';
                    setTimeout(() => toast.remove(), 500);
                }, 5000);
            }
        }));
    });
</script>
@endpush
@endsection