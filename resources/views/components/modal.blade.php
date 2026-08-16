@props([
    'id' => 'modal',
    'title' => 'Modal',
    'size' => '',
    'centered' => false,
    'scrollable' => false,
    'static' => false
])

@php
    $modalClasses = 'modal fade';
    $dialogClasses = 'modal-dialog';
    
    if ($size) {
        $dialogClasses .= ' modal-' . $size;
    }
    
    if ($centered) {
        $dialogClasses .= ' modal-dialog-centered';
    }
    
    if ($scrollable) {
        $dialogClasses .= ' modal-dialog-scrollable';
    }
    
    $dataBsBackdrop = $static ? 'static' : 'true';
@endphp

<div class="{{ $modalClasses }}" id="{{ $id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="{{ $dataBsBackdrop }}">
    <div class="{{ $dialogClasses }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>