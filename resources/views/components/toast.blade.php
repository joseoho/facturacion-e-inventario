@props([
    'type' => 'success', // success, error, warning, info
    'title' => '',
    'message' => '',
    'dismissible' => true,
    'autohide' => true,
    'delay' => 5000,
])

@php
    $icons = [
        'success' => 'bi-check-circle-fill text-success',
        'error' => 'bi-x-circle-fill text-danger',
        'warning' => 'bi-exclamation-triangle-fill text-warning',
        'info' => 'bi-info-circle-fill text-info',
    ];
    
    $icon = $icons[$type] ?? $icons['info'];
    $toastClass = 'toast-custom ' . $type;
@endphp

<div class="{{ $toastClass }}" role="alert" @if($autohide) data-bs-autohide="true" data-bs-delay="{{ $delay }}" @endif>
    <div class="d-flex align-items-start gap-3">
        <i class="bi {{ $icon }} fs-5"></i>
        <div class="flex-grow-1">
            @if($title)
                <strong class="d-block text-dark">{{ $title }}</strong>
            @endif
            <span class="text-muted small">{{ $message }}</span>
        </div>
        @if($dismissible)
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        @endif
    </div>
</div>