@props([
    'target' => 'reporteContent',
    'label' => 'Imprimir Reporte',
    'class' => 'btn-primary'
])

<button type="button" class="btn {{ $class }}" onclick="imprimirSeccion('{{ $target }}')">
    <i class="bi bi-printer me-1"></i> {{ $label }}
</button>

@push('scripts')
<script>
    function imprimirSeccion(elementId) {
        const contenido = document.getElementById(elementId);
        if (!contenido) {
            toast('Error', 'No se encontró el contenido para imprimir', 'error');
            return;
        }
        
        // Ocultar elementos no imprimibles temporalmente
        const noPrintElements = document.querySelectorAll('.no-print');
        noPrintElements.forEach(el => el.style.display = 'none');
        
        // Mostrar elementos solo para impresión
        const printOnlyElements = document.querySelectorAll('.print-only');
        printOnlyElements.forEach(el => el.style.display = 'block');
        
        // Imprimir
        window.print();
        
        // Restaurar visibilidad
        setTimeout(() => {
            noPrintElements.forEach(el => el.style.display = '');
            printOnlyElements.forEach(el => el.style.display = 'none');
        }, 100);
    }
</script>
@endpush