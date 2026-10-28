@extends('layouts.app')

@section('title', 'Historial de Tasas')
@section('page-title', 'Historial de Tasas')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('tasas.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Tasas
        </a>

        <a href="{{ route('tasas.export', request()->query()) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tasas.historial') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="moneda_id" class="form-label">Moneda</label>
                    <select name="moneda_id" id="moneda_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($monedas as $moneda)
                            <option value="{{ $moneda->id }}" {{ (string) request('moneda_id') === (string) $moneda->id ? 'selected' : '' }}>
                                {{ $moneda->codigo }} — {{ $moneda->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Desde</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
                           value="{{ request('fecha_inicio') }}">
                </div>

                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Hasta</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                           value="{{ request('fecha_fin') }}">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Tasas registradas</span>
            <span class="badge bg-primary-subtle text-primary-emphasis">{{ $historial->total() }} registros</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Moneda</th>
                        <th class="text-end">Tasa</th>
                        <th>Registrada por</th>
                        <th>Creada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historial as $tasa)
                        <tr>
                            <td>{{ $tasa->fecha?->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $tasa->moneda->codigo ?? '—' }}</span>
                                {{ $tasa->moneda->nombre ?? '' }}
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $tasa->tasa, 6) }}</td>
                            <td>{{ $tasa->user->name ?? 'Sistema' }}</td>
                            <td class="text-muted small">{{ $tasa->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No hay tasas registradas con esos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($historial->hasPages())
            <div class="card-footer">
                {{ $historial->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
