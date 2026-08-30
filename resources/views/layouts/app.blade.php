<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Facturación')</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --primary-color: #1e293b;
            --primary-light: #334155;
            --secondary-bg: #f8f9fa;
            --sidebar-width: 260px;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background-color: var(--secondary-bg);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: #fff;
            z-index: 1050;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            padding: 0;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-brand i {
            font-size: 2rem;
            color: #60a5fa;
        }
        
        .sidebar-brand h5 {
            margin: 0;
            font-weight: 700;
            color: #fff;
        }
        
        .sidebar-brand small {
            display: block;
            font-size: 0.65rem;
            color: #94a3b8;
            font-weight: 300;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }
        
        .sidebar-nav .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }
        
        .sidebar-nav .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.08);
            border-left-color: #60a5fa;
        }
        
        .sidebar-nav .nav-link i {
            font-size: 1.2rem;
            width: 1.5rem;
            text-align: center;
        }
        
        .sidebar-nav .nav-section {
            padding: 0.5rem 1.5rem;
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Header */
        .top-header {
            background: #fff;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1040;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        
        .top-header .toggle-sidebar {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
        }
        
        .top-header .toggle-sidebar:hover {
            color: #60a5fa;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Overlay para móvil */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1045;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Scrollbar personalizado */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
        }
        
        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        @media (min-width: 992px) {
            .sidebar-overlay {
                display: none !important;
            }
        }
        
        /* Cards y utilidades */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e2e8f0;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .stat-icon.green {
            background: #d1fae5;
            color: #059669;
        }
        
        .stat-icon.yellow {
            background: #fef3c7;
            color: #d97706;
        }
        
        .stat-icon.red {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .stat-icon.purple {
            background: #ede9fe;
            color: #7c3aed;
        }
        
        /* Badges de estado */
        .badge-status {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .badge-status.pendiente {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-status.pagada {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-status.anulada {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-status.activo {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-status.inactivo {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-status.stock-bajo {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-status.sin-stock {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Animaciones */
        .fade-enter {
            opacity: 0;
            transform: translateY(10px);
        }
        
        .fade-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.3s ease;
        }
        
        /* Toast personalizado */
        .toast-container-custom {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            max-width: 450px;
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
        
        .toast-custom.success {
            border-left-color: #059669;
        }
        
        .toast-custom.error {
            border-left-color: #dc2626;
        }
        
        .toast-custom.warning {
            border-left-color: #d97706;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Tablas */
        .table-container {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        
        .table-container .table {
            margin-bottom: 0;
        }
        
        .table-container .table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
        }
        
        .table-container .table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        
        .table-container .table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Formularios */
        .form-label {
            font-weight: 500;
            color: #334155;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .form-control, .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.6rem 0.9rem;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
        }
        
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc2626;
        }
        
        .form-control.is-invalid:focus, .form-select.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
        }
        
        .invalid-feedback {
            font-size: 0.8rem;
            color: #dc2626;
            margin-top: 0.25rem;
        }
        
        /* Botones */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            border-color: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.2);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background: #059669;
            border-color: #059669;
        }
        
        .btn-success:hover {
            background: #047857;
            border-color: #047857;
        }
        
        .btn-danger {
            background: #dc2626;
            border-color: #dc2626;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }
        
        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
        }
        
        /* Modales */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }
        
        .modal-header .modal-title {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
        }
        
        /* Select2 personalizado */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            min-height: 44px;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0.6rem 0.9rem;
        }
        
        /* Badges en tabla de factura */
        .product-badge {
            display: inline-block;
            background: #f1f5f9;
            padding: 0.15rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            color: #475569;
        }
        
        /* Loading spinner */
        .spinner-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
        }
        
        /* Scrollbar global */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>
<body>
    <div x-data="appLayout()" x-init="init()" class="d-flex">
        <!-- Overlay para móvil -->
        <div class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="closeSidebar()"></div>
        
        <!-- Sidebar -->
        <nav class="sidebar" :class="{ 'open': sidebarOpen }">
            <!-- Brand -->
            <div class="sidebar-brand">
                <i class="bi bi-box-seam"></i>
                <div>
                    <h5>Facturador</h5>
                    <small>Sistema de Facturación</small>
                </div>
            </div>
            
            <!-- Navigation -->
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-section">Principal</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2"></i> Dashboardd
                    </a>
                </li>
                
                {{-- @can('vendedor') --}}
                <li class="nav-section">Facturación</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('facturas.index') ? 'active' : '' }}" href="{{ route('facturas.index') }}">
                        <i class="bi bi-receipt"></i> Facturas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('facturas.create') ? 'active' : '' }}" href="{{ route('facturas.create') }}">
                        <i class="bi bi-plus-circle"></i> Nueva Factura
                    </a>
                </li>
                 {{-- @endcan --}}
                
                <li class="nav-section">Inventario</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                        <i class="bi bi-box"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}" href="{{ route('categorias.index') }}">
                        <i class="bi bi-tags"></i> Categorías
                    </a>
                </li>
                
                <li class="nav-section">Clientes</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                </li>
                
               {{-- @can('admin') --}} 
                <li class="nav-section">Administración</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tasas.*') ? 'active' : '' }}" href="{{ route('tasas.index') }}">
                        <i class="bi bi-currency-exchange"></i> Tasas de Cambio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('monedas.*') ? 'active' : '' }}" href="{{ route('monedas.index') }}">
                        <i class="bi bi-coin"></i> Monedas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.stock-bajo') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                    </a>
                </li> 

                {{-- @can('admin') --}}
                    <li class="nav-section">Reportes</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reportes.inventario') ? 'active' : '' }}" 
                        href="{{ route('reportes.inventario') }}">
                            <i class="bi bi-box-seam"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <!-- ✅ Usar 'reportes.stock-bajo' (sin 'productos.') -->
                        <a class="nav-link {{ request()->routeIs('reportes.stock-bajo') ? 'active' : '' }}" 
                        href="{{ route('reportes.stock-bajo') }}">
                            <i class="bi bi-exclamation-triangle"></i> Stock Bajo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reportes.ventas.diarias') ? 'active' : '' }}" 
                        href="{{ route('reportes.ventas.diarias') }}">
                            <i class="bi bi-graph-up"></i> Ventas Diarias
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reportes.facturas') ? 'active' : '' }}" 
                        href="{{ route('reportes.facturas') }}">
                            <i class="bi bi-receipt"></i> Facturas
                        </a>
                    </li> --}}
                    {{-- @endcan --}}
                {{-- @endcan --}}
                
                <li class="nav-section">Sistema</li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-link" style="background:none;border:none;width:100%;text-align:left;">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Header -->
            <header class="top-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button class="toggle-sidebar d-lg-none" @click="toggleSidebar()">
                            <i class="bi bi-list"></i>
                        </button>
                        <h6 class="mb-0 d-none d-sm-block" style="font-weight:600;color:#1e293b;">
                            @yield('page-title', 'Dashboard')
                        </h6>
                    </div>
                    
                    {{-- <div class="user-menu">
                         <span class="d-none d-md-inline text-sm text-muted" style="font-size:0.85rem;">
                            {{ Auth::users()->name }}
                        </span> 
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-1" style="font-size:0.7rem;font-weight:500;">
                            {{ ucfirst(Auth::users()->role) }}
                        </span> 
                        <div class="user-avatar">
                            {{ strtoupper(substr(Auth::users()->name, 0, 2)) }}
                        </div> 
                    </div> --}}
                </div>
            </header>
            
            <!-- Content -->
            <main class="p-3 p-md-4">
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container-custom" id="toastContainer">
        @if(session('success'))
            <div class="toast-custom success">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block text-dark">¡Éxito!</strong>
                        <span class="text-muted small">{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="toast-custom error">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block text-dark">¡Error!</strong>
                        <span class="text-muted small">{{ session('error') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
        @endif
        
        @if(session('warning'))
            <div class="toast-custom warning">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block text-dark">¡Atención!</strong>
                        <span class="text-muted small">{{ session('warning') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function appLayout() {
            return {
                sidebarOpen: false,
                init() {
                    // Cerrar sidebar al hacer click fuera en desktop
                    document.addEventListener('click', (e) => {
                        if (window.innerWidth >= 992) return;
                        const sidebar = document.querySelector('.sidebar');
                        const toggleBtn = document.querySelector('.toggle-sidebar');
                        if (sidebar && !sidebar.contains(e.target) && !toggleBtn?.contains(e.target)) {
                            this.sidebarOpen = false;
                        }
                    });
                },
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },
                closeSidebar() {
                    this.sidebarOpen = false;
                }
            }
        }
        
        // Auto-remover toasts después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toast-custom').forEach((toast, index) => {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'all 0.5s ease';
                    setTimeout(() => toast.remove(), 500);
                }, 5000 + (index * 500));
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>