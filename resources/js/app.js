import './bootstrap';
import Alpine from 'alpinejs'
// import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Inicializar Alpine.js
window.Alpine = Alpine
Alpine.start()

// Función global para mostrar notificaciones toast
window.toast = function(title, message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const colors = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    const icons = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-x-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast-custom ${colors[type] || 'info'}`;
    toast.innerHTML = `
        <div class="d-flex align-items-start gap-3">
            <i class="bi ${icons[type] || icons.info} fs-5"></i>
            <div class="flex-grow-1">
                <strong class="d-block text-dark">${title}</strong>
                <span class="text-muted small">${message}</span>
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
};

// Manejador de formularios con Alpine.js
document.addEventListener('alpine:init', () => {
    // Validación de cantidad en Kg
    Alpine.directive('kg-input', (el) => {
        el.addEventListener('input', function() {
            let value = this.value.replace(',', '.');
            if (value && !isNaN(value)) {
                value = parseFloat(value);
                if (value < 0.001) {
                    this.value = 0.001;
                }
            }
        });
    });
});

// Cerrar sidebar al hacer click en enlace (móvil)
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    const sidebar = document.querySelector('.sidebar');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                const overlay = document.querySelector('.sidebar-overlay');
                if (sidebar) sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('show');
            }
        });
    });
});