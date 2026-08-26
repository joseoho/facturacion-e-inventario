@extends('layouts.app')

@section('title', 'Nueva Factura')
@section('page-title', 'Nueva Factura')

@section('content')
<div x-data="facturaCreator()" x-init="init()" x-cloak>
    
    <!-- Mensajes de alerta -->
    <template x-if="mensaje">
        <div class="alert alert-dismissible fade show" 
             :class="'alert-' + mensajeTipo"
             role="alert">
            <i class="bi bi-info-circle me-2" x-show="mensajeTipo === 'info'"></i>
            <i class="bi bi-check-circle me-2" x-show="mensajeTipo === 'success'"></i>
            <i class="bi bi-exclamation-triangle me-2" x-show="mensajeTipo === 'warning'"></i>
            <i class="bi bi-x-circle me-2" x-show="mensajeTipo === 'danger'"></i>
            <span x-text="mensaje"></span>
            <button type="button" class="btn-close" @click="mensaje = ''"></button>
        </div>
    </template>

    <div class="row g-4">
        <!-- Panel izquierdo - Datos -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-receipt me-2"></i>
                    <span>Datos de la Factura</span>
                </div>
                <div class="card-body">
                    <!-- Número de Factura -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Número de Factura</label>
                        <div class="form-control bg-light">
                            <span x-text="numeroFactura || '{{ $siguienteNumero ?? 'FACT-00000001' }}'"></span>
                        </div>
                    </div>
        
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="cliente_id" required>
                            <option value="">Seleccionar cliente...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">
                                    {{ $cliente->nombre }} 
                                    @if(isset($cliente->documento))
                                        ({{ $cliente->documento }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                        
                    <!-- Moneda -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Moneda <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="moneda_id" required>
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
                        <label class="form-label fw-bold">Fecha de Emisión</label>
                        <input type="date" class="form-control" x-model="fecha_emision" required>
                    </div>

                    <hr>

                    <!-- Resumen de la factura -->
                    <div class="bg-light p-3 rounded">
                        <h6 class="fw-bold mb-3">Resumen</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Productos:</span>
                            <span class="fw-semibold" x-text="items.length"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-semibold" x-text="formatPrecio(subtotal_neto)"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">IVA:</span>
                            <span class="fw-semibold" x-text="formatPrecio(total_impuesto)"></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold text-primary fs-5" x-text="formatPrecio(total_general)"></span>
                        </div>
                    </div>
                                        <!-- MONEDA DE PAGO - 3 BOTONES -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Moneda de Pago <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-2">
                                                <button type="button" 
                                                        class="btn flex-grow-1 d-flex flex-column align-items-center py-2"
                                                        :class="monedaPago === 'USD' ? 'btn-success active' : 'btn-outline-secondary'"
                                                        @click="seleccionarMonedaPago('USD')">
                                                    <i class="bi bi-currency-dollar fs-5"></i>
                                                    <span class="small">USD</span>
                                                    <span class="badge bg-light text-dark mt-1" x-show="monedaPago === 'USD'">✓</span>
                                                    <small class="text-muted" x-show="monedaPago === 'USD'">1 USD</small>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="btn flex-grow-1 d-flex flex-column align-items-center py-2"
                                                        :class="monedaPago === 'VES' ? 'btn-success active' : 'btn-outline-secondary'"
                                                        @click="seleccionarMonedaPago('VES')">
                                                    <i class="bi bi-currency-exchange fs-5"></i>
                                                    <span class="small">Bs</span>
                                                    <span class="badge bg-light text-dark mt-1" x-show="monedaPago === 'VES'">✓</span>
                                                    <small class="text-muted" x-show="monedaPago === 'BS'" x-text="'1 USD = ' + formatPrecio(tasaVES) + ' Bs'"></small>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="btn flex-grow-1 d-flex flex-column align-items-center py-2"
                                                        :class="monedaPago === 'COP' ? 'btn-success active' : 'btn-outline-secondary'"
                                                        @click="seleccionarMonedaPago('COP')">
                                                    <i class="bi bi-currency-exchange fs-5"></i>
                                                    <span class="small">COP</span>
                                                    <span class="badge bg-light text-dark mt-1" x-show="monedaPago === 'COP'">✓</span>
                                                    <small class="text-muted" x-show="monedaPago === 'COP'" x-text="'1 USD = ' + formatPrecio(tasaCOP) + ' COP'"></small>
                                                </button>
                                            </div>
                            
                            <!-- Mostrar tasa seleccionada -->
                            <div class="mt-2 text-center small" x-show="monedaPago">
                                <span class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Tasa: 1 USD = <span class="fw-bold" x-text="formatPrecio(tasaSeleccionada)"></span> 
                                    <span x-text="monedaPago"></span>
                                </span>
                            </div>
                        </div>

                    <!-- Botón guardar -->
                    <button type="button" class="btn btn-success w-100 mt-3" 
                            @click="submitFactura()"
                            :disabled="loading || items.length === 0">
                        <span x-show="!loading">
                            <i class="bi bi-check-circle me-2"></i>
                            Crear Factura
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel derecho - Productos -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-cart me-2"></i>
                    <span>Agregar Productos</span>
                    <span class="badge bg-light text-dark ms-2" x-text="items.length"></span>
                </div>
                <div class="card-body">
                    <!-- Buscador de productos -->
                    <div class="mb-3 position-relative">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   x-model="busqueda" 
                                   @input.debounce.300ms="buscarProductos()"
                                   placeholder="Buscar producto por nombre o código..."
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" @click="limpiarBusqueda()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>

                        <!-- Resultados de búsqueda -->
                        <div x-show="productosBusqueda.length > 0" 
                             class="position-absolute top-100 start-0 end-0 mt-1 bg-white border rounded-3 shadow-lg"
                             style="max-height: 250px; overflow-y: auto; z-index: 1000;">
                            <template x-for="producto in productosBusqueda" :key="producto.id">
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom hover-bg-light"
                                     style="cursor: pointer;"
                                     @click="agregarProducto(producto)">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold" x-text="producto.nombre"></div>
                                        <div class="small text-muted">
                                            <span x-text="producto.sku"></span>
                                            <span class="mx-2">|</span>
                                            <span>Stock: <span x-text="Number(producto.stock_kg).toFixed(3)"></span> Kg</span>
                                            <span class="mx-2">|</span>
                                            <span class="text-success" x-text="'$' + formatPrecio(producto.precio_kg)"></span>
                                            <span x-show="producto.iva_porcentaje > 0" class="text-muted">
                                                <span class="mx-2">|</span>
                                                <span class="badge bg-secondary" x-text="'IVA ' + producto.iva_porcentaje + '%'"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-primary ms-2" @click.stop="agregarProducto(producto)">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Mensaje de sin resultados -->
                        <div x-show="busqueda.length >= 2 && productosBusqueda.length === 0" 
                             class="text-muted mt-2 small">
                            <i class="bi bi-info-circle me-1"></i>
                            No se encontraron productos con ese nombre o código
                        </div>
                    </div>

                    <!-- Tabla de productos agregados -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width: 120px;">Cantidad (Kg)</th>
                                    <th style="width: 120px;" class="text-end">Precio</th>
                                    <th style="width: 80px;" class="text-center">IVA</th>
                                    <th style="width: 120px;" class="text-end">Total</th>
                                    <th style="width: 50px;" class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <div class="fw-semibold" x-text="item.nombre"></div>
                                            <div class="small text-muted" x-text="item.sku"></div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       class="form-control text-end" 
                                                       x-model="item.cantidad_kg"
                                                       @input="calcularItem(index)"
                                                       step="0.001"
                                                       min="0.001"
                                                       required>
                                                <span class="input-group-text">Kg</span>
                                            </div>
                                            <small class="text-danger" x-show="parseFloat(item.cantidad_kg) > parseFloat(item.stock_kg)">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                Stock: <span x-text="Number(item.stock_kg).toFixed(3)"></span> Kg
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <span x-text="formatPrecio(item.precio_kg)"></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary" x-text="item.iva_porcentaje + '%'"></span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <span x-text="formatPrecio(item.total_linea)"></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    @click="eliminarItem(index)"
                                                    title="Eliminar producto">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        <span>No hay productos agregados</span>
                                        <br>
                                        <small>Busca productos en el campo de arriba</small>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot x-show="items.length > 0" class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end fw-bold" x-text="formatPrecio(subtotal_neto)"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">IVA:</td>
                                    <td class="text-end fw-bold" x-text="formatPrecio(total_impuesto)"></td>
                                    <td></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold fs-6" x-text="formatPrecio(total_general)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="d-flex gap-2 mt-3" x-show="items.length > 0">
                        <button type="button" class="btn btn-outline-danger btn-sm" @click="limpiarItems()">
                            <i class="bi bi-trash3 me-1"></i> Limpiar todo
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" @click="agregarCantidad(0.5)">
                            <i class="bi bi-plus-lg me-1"></i> +0.5 Kg a todos
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="agregarCantidad(-0.5)">
                            <i class="bi bi-dash-lg me-1"></i> -0.5 Kg a todos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                <!-- Tasa de Cambio -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tasa de Cambio del Día</label>
                        <div class="form-control bg-light">
                            <span class="text-muted small">
                                @if(isset($tasaCambio))
                                    1 {{ $tasaCambio->moneda->codigo ?? 'USD' }} = 
                                    <span class="fw-semibold">{{ number_format($tasaCambio->tasa, 2) }}</span> Bs
                                    <br>
                                    <span class="text-muted" style="font-size: 10px;">
                                        {{ \Carbon\Carbon::parse($tasaCambio->fecha)->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="text-warning">No hay tasa configurada</span>
                                @endif
                            </span>
                        </div>
                    </div>
<style>
    [x-cloak] { display: none !important; }
    .hover-bg-light:hover { background-color: #f8f9fa; }
    .card-header { font-weight: 600; }
    .z-1000 { z-index: 1000; }
</style>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('facturaCreator', () => ({
        // =============================================
        // VARIABLES PRINCIPALES
        // =============================================
        cliente_id: '',
        moneda_id: '',
        fecha_emision: '',
        items: [],
        subtotal_neto: 0,
        total_impuesto: 0,
        total_general: 0,
        
        // =============================================
        // TASAS DE CAMBIO DESDE BD
        // =============================================
        monedaPago: 'USD',
        tasaCOP: {{ $tasaCOP->tasa ?? 3200 }},
        tasaVES: {{ $tasaVES->tasa ?? 785.5855 }},
        tasaUSD: {{ $tasaUSD->tasa ?? 1 }},
        tasaSeleccionada: 1, // Por defecto USD
        
        // =============================================
        // BÚSQUEDA
        // =============================================
        busqueda: '',
        productosBusqueda: [],
        loading: false,
        mensaje: '',
        mensajeTipo: 'info',
        
        // =============================================
        // INICIALIZACIÓN
        // =============================================
        init() {
            this.fecha_emision = new Date().toISOString().split('T')[0];
            this.tasaSeleccionada = 1; // USD por defecto
            
            // Mostrar mensaje de bienvenida
            this.mostrarAlerta('Selecciona productos y elige la moneda de pago', 'info');
        },
        
        // =============================================
        // SELECCIONAR MONEDA DE PAGO
        // =============================================
        seleccionarMonedaPago(moneda) {
            this.monedaPago = moneda;
            
            // Asignar tasa según moneda seleccionada
            switch(moneda) {
                case 'USD':
                    this.tasaSeleccionada = 1;
                    break;
                case 'COP':
                    this.tasaSeleccionada = this.tasaCOP;
                    break;
                case 'VES':
                    this.tasaSeleccionada = this.tasaVES;
                    break;
                default:
                    this.tasaSeleccionada = 1;
            }
            
            // Recalcular TODOS los precios de los items
            this.recalcularPrecios();
            
            this.mostrarAlerta(`Moneda cambiada a ${moneda} (1 USD = ${this.formatPrecio(this.tasaSeleccionada)} ${moneda})`, 'info');
        },
        
        // =============================================
        // RECALCULAR PRECIOS DE TODOS LOS ITEMS
        // =============================================
        recalcularPrecios() {
            if (this.items.length === 0) return;
            
            this.items.forEach((item, index) => {
                const precioUsd = parseFloat(item.precio_kg_usd) || 0;
                // Multiplicar por la tasa seleccionada
                item.precio_kg = precioUsd * this.tasaSeleccionada;
                this.calcularItem(index);
            });
        },
        
        // =============================================
        // BUSCAR PRODUCTOS
        // =============================================
        buscarProductos() {
            const termino = this.busqueda.trim();
            
            if (termino.length < 2) {
                this.productosBusqueda = [];
                return;
            }
            
            fetch(`/facturas/buscar-productos?q=${encodeURIComponent(termino)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta');
                    }
                    return response.json();
                })
                .then(data => {
                    this.productosBusqueda = Array.isArray(data) ? data : [];
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.productosBusqueda = [];
                });
        },
        
        limpiarBusqueda() {
            this.busqueda = '';
            this.productosBusqueda = [];
        },
        
        // =============================================
        // AGREGAR PRODUCTO - USA LA TASA SELECCIONADA
        // =============================================
        agregarProducto(producto) {
            if (!producto || !producto.id) {
                this.mostrarAlerta('Producto inválido', 'warning');
                return;
            }
            
            if (parseFloat(producto.stock_kg) <= 0) {
                this.mostrarAlerta('Producto sin stock', 'warning');
                return;
            }
            
            // Verificar si ya existe
            const existente = this.items.find(item => item.id === producto.id);
            if (existente) {
                existente.cantidad_kg = parseFloat(existente.cantidad_kg) + 0.5;
                const index = this.items.indexOf(existente);
                this.calcularItem(index);
                this.limpiarBusqueda();
                this.mostrarAlerta(`+0.5 Kg de ${producto.nombre}`, 'info');
                return;
            }
            
            // Calcular precio según la moneda seleccionada
            const precioUsd = parseFloat(producto.precio_kg_usd) || 0;
            const precioMoneda = precioUsd * this.tasaSeleccionada;
            
            // Agregar nuevo producto
            this.items.push({
                id: producto.id,
                nombre: producto.nombre || 'Sin nombre',
                sku: producto.sku || 'N/A',
                precio_kg_usd: precioUsd,
                precio_kg: precioMoneda,
                cantidad_kg: 0.5,
                iva_porcentaje: parseFloat(producto.iva_porcentaje) || 0,
                total_linea: 0,
                stock_kg: parseFloat(producto.stock_kg) || 0
            });
            
            const index = this.items.length - 1;
            this.calcularItem(index);
            this.limpiarBusqueda();
            this.mostrarAlerta(`Producto "${producto.nombre}" agregado`, 'success');
        },
        
        // =============================================
        // CALCULAR ITEM
        // =============================================
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
                this.mostrarAlerta(`Stock limitado a ${stock.toFixed(3)} Kg`, 'warning');
            }
            
            // Calcular totales
            const neto = precio * cantidad;
            const impuesto = neto * (iva / 100);
            item.total_linea = neto + impuesto;
            
            this.recalcularTodo();
        },
        
        // =============================================
        // ELIMINAR ITEM
        // =============================================
        eliminarItem(index) {
            const item = this.items[index];
            if (!item) return;
            
            if (confirm(`¿Eliminar "${item.nombre}" de la lista?`)) {
                this.items.splice(index, 1);
                this.recalcularTodo();
                this.mostrarAlerta('Producto eliminado', 'info');
            }
        },
        
        // =============================================
        // LIMPIAR TODO
        // =============================================
        limpiarItems() {
            if (this.items.length === 0) return;
            
            if (confirm('¿Eliminar todos los productos de la lista?')) {
                this.items = [];
                this.recalcularTodo();
                this.mostrarAlerta('Lista limpiada', 'info');
            }
        },
        
        // =============================================
        // AGREGAR CANTIDAD A TODOS
        // =============================================
        agregarCantidad(cantidad) {
            if (this.items.length === 0) {
                this.mostrarAlerta('No hay productos', 'warning');
                return;
            }
            
            this.items.forEach((item, index) => {
                const nueva = parseFloat(item.cantidad_kg) + cantidad;
                if (nueva > 0) {
                    item.cantidad_kg = nueva;
                    this.calcularItem(index);
                }
            });
            
            const msg = cantidad > 0 ? `+${cantidad} Kg` : `${cantidad} Kg`;
            this.mostrarAlerta(`${msg} a todos los productos`, 'info');
        },
        
        // =============================================
        // RECALCULAR TOTALES - MUESTRA EN MONEDA SELECCIONADA
        // =============================================
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
                
                item.total_linea = neto + impuesto;
                
                this.subtotal_neto += neto;
                this.total_impuesto += impuesto;
                this.total_general += neto + impuesto;
            });
        },
        
        // =============================================
        // ENVIAR FACTURA
        // =============================================
        async submitFactura() {
            // Validaciones
            if (!this.cliente_id) {
                this.mostrarAlerta('Selecciona un cliente', 'warning');
                return;
            }
            
            if (!this.moneda_id) {
                this.mostrarAlerta('Selecciona una moneda', 'warning');
                return;
            }
            
            if (this.items.length === 0) {
                this.mostrarAlerta('Agrega al menos un producto', 'warning');
                return;
            }
            
            // Validar cantidades
            const itemsInvalidos = this.items.filter(item => parseFloat(item.cantidad_kg) <= 0);
            if (itemsInvalidos.length > 0) {
                this.mostrarAlerta('Todos los productos deben tener cantidad > 0', 'warning');
                return;
            }
            
            this.loading = true;
            
            try {
                const formData = {
                    cliente_id: parseInt(this.cliente_id),
                    moneda_id: parseInt(this.moneda_id),
                    moneda_pago: this.monedaPago,
                    productos: this.items.map(item => ({
                        producto_id: parseInt(item.id),
                        cantidad_kg: parseFloat(item.cantidad_kg),
                        precio_kg: parseFloat(item.precio_kg)
                    }))
                };
                
                const token = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = token ? token.content : '';
                
                const response = await fetch('/facturas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.mostrarAlerta('¡Factura creada exitosamente!', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    let mensajeError = result.message || 'Error al crear la factura';
                    if (result.errors) {
                        const errores = Object.values(result.errors).flat();
                        mensajeError = errores.join('\n');
                    }
                    this.mostrarAlerta(mensajeError, 'danger');
                    this.loading = false;
                }
                
            } catch (error) {
                console.error('Error:', error);
                this.mostrarAlerta('Error de conexión al servidor', 'danger');
                this.loading = false;
            }
        },
        
        // =============================================
        // MOSTRAR ALERTA
        // =============================================
        mostrarAlerta(texto, tipo = 'info') {
            this.mensaje = texto;
            this.mensajeTipo = tipo;
            
            // Auto cerrar después de 4 segundos
            setTimeout(() => {
                this.mensaje = '';
            }, 4000);
        },
        
        // =============================================
        // FORMATO DE PRECIOS
        // =============================================
        formatPrecio(valor) {
            if (valor === undefined || valor === null || isNaN(valor)) {
                return '0.00';
            }
            return Number(valor).toFixed(2);
        }
    }));
});
</script>
@endpush
@endsection