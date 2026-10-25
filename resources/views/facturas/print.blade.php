<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $factura->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 32px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #666; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 16px; margin-bottom: 18px; }
        .grid div span { display: block; font-size: 11px; color: #666; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f1f1f1; }
        .right { text-align: right; }
        .totals { margin-top: 12px; width: 45%; margin-left: auto; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .label { text-align: right; color: #444; }
        .totals .value { text-align: right; font-weight: bold; }
        .badge { display: inline-block; border: 1px solid #111; padding: 2px 8px; border-radius: 10px; font-size: 11px; text-transform: uppercase; }
        @media print {
            body { margin: 12mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div>
            <h1>Factura {{ $factura->numero }}</h1>
            <div class="muted">Fecha de emisión: {{ $factura->fecha_formateada }}</div>
            @if($factura->fecha_pago)
                <div class="muted">Fecha de pago: {{ $factura->fecha_pago->format('d/m/Y') }}</div>
            @endif
        </div>
        <div class="badge">{{ $factura->estado_texto }}</div>
    </div>

    <div class="grid">
        <div><span>Cliente</span>{{ $factura->cliente->nombre ?? '—' }}</div>
        <div><span>Documento</span>{{ $factura->cliente->documento ?? '—' }}</div>
        <div><span>Moneda</span>{{ $factura->moneda->codigo ?? $factura->moneda_pago ?? '—' }}</div>
        <div><span>Tasa usada</span>{{ $factura->tasa_cambio_usada ? number_format((float) $factura->tasa_cambio_usada, 4, ',', '.') : '—' }}</div>
        <div><span>Emitida por</span>{{ $factura->user->name ?? '—' }}</div>
        <div><span>Total USD</span>{{ $factura->total_usd ? number_format((float) $factura->total_usd, 2, ',', '.') : '—' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="right">Cantidad (Kg)</th>
                <th class="right">Precio / Kg</th>
                <th class="right">IVA %</th>
                <th class="right">Neto</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->lineas as $linea)
                <tr>
                    <td>{{ $linea->producto->nombre ?? 'Producto eliminado' }}</td>
                    <td class="right">{{ number_format((float) $linea->cantidad_kg, 3, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $linea->precio_kg, 4, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $linea->impuesto_porcentaje, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $linea->neto, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $linea->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Subtotal</td><td class="value">{{ number_format((float) $factura->subtotal_neto, 2, ',', '.') }}</td></tr>
        <tr><td class="label">Impuesto</td><td class="value">{{ number_format((float) $factura->total_impuesto, 2, ',', '.') }}</td></tr>
        <tr><td class="label">Total</td><td class="value">{{ number_format((float) $factura->total, 2, ',', '.') }} {{ $factura->moneda->codigo ?? $factura->moneda_pago }}</td></tr>
    </table>

    <p class="muted" style="margin-top:32px;font-size:11px;">
        Documento generado el {{ now()->format('d/m/Y H:i') }}.
    </p>
</body>
</html>
