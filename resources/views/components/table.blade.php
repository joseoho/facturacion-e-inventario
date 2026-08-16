@props([
    'headers' => [],
    'actions' => false,
    'search' => false,
    'paginator' => null,
    'emptyMessage' => 'No hay datos disponibles',
    'responsive' => true,
    'striped' => true,
    'hover' => true,
    'compact' => false,
])

@php
    $tableClasses = 'table align-middle mb-0';
    if ($striped) $tableClasses .= ' table-striped';
    if ($hover) $tableClasses .= ' table-hover';
    if ($compact) $tableClasses .= ' table-sm';
@endphp

<div class="table-container">
    @if($search || $actions)
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            @if($search)
                <div class="flex-grow-1 flex-shrink-0" style="max-width: 300px;">
                    {{ $search }}
                </div>
            @endif
            
            @if($actions)
                <div class="d-flex gap-2 flex-wrap">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    
    @if($responsive)
        <div class="table-responsive">
    @endif
    
    <table class="{{ $tableClasses }}">
        @if(!empty($headers))
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        
        <tbody>
            {{ $slot }}
            
            @if($slot->isEmpty())
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    
    @if($responsive)
        </div>
    @endif
    
    @if($paginator)
        <div class="mt-3 d-flex justify-content-end">
            {{ $paginator->links() }}
        </div>
    @endif
</div>