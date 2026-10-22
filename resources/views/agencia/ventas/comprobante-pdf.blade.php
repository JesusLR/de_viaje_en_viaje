<!DOCTYPE html>
@php $compraSaldada = $compraSaldada ?? false; @endphp
<html lang="es"><head><meta charset="UTF-8"><title>Comprobante {{ $venta->folio }}</title>
<style>
@page{margin:36px 36px 48px}body{font-family:'DejaVu Sans',sans-serif;font-size:10px;color:#25324b;line-height:1.5}table{border-collapse:collapse;width:100%}thead{display:table-header-group}tr{page-break-inside:avoid}th{text-align:left;background:#f1f4fb;color:#64748b;font-size:8px;padding:9px 8px;border-bottom:1px solid #e1e7f0}td{padding:9px 8px;border-bottom:1px solid #edf0f5;vertical-align:top;word-wrap:break-word}h2{font-size:15px;margin:22px 0 10px;font-weight:bold}h1{font-size:21px;margin:0 0 4px}.muted{color:#8793a5;font-size:8px}.right{text-align:right}.header td{border:0;padding:0 0 18px}.logo{width:76px;height:76px;object-fit:contain}.header{border-bottom:2px solid #536fe8}.folio{color:#536fe8;font-size:14px;font-weight:bold}.info{margin-top:16px}.info td{border:0;background:#f8fafc;width:50%;padding:12px}.label{font-size:8px;color:#8793a5}.totales{margin:18px 0}.totales td{width:33%;background:#f1f4fb;border:4px solid white;padding:12px}.totales strong{font-size:14px}.verde{color:#24885b}.nota{margin-top:20px;font-size:8px;color:#8793a5;white-space:pre-wrap}.promocion{color:#24885b;font-size:8px}
</style></head><body>
<table class="header"><tr><td style="width:95px">@if($logo)<img src="{{ $logo }}" class="logo" alt="Logo de la agencia">@endif</td><td><h1>{{ config('app.name') }}</h1><div>Comprobante de {{ $compraSaldada ? 'compra' : ($venta->cTipoOperacion === 'APARTADO' ? 'apartado' : 'venta') }}</div><div class="muted">Emitido el {{ now()->format('d/m/Y H:i') }}</div></td><td class="right"><div class="folio">{{ $venta->folio }}</div><div class="muted">Registro: {{ $venta->created_at->format('d/m/Y') }}</div></td></tr></table>
@if($venta->cNombre)<h2>{{ $venta->cNombre }}</h2>@endif
<table class="info"><tr><td><div class="label">CLIENTE</div><strong>{{ $venta->cliente->nombre_completo }}</strong><br>{{ $venta->cliente->cTelefono }}<br>{{ $venta->cliente->cEmail }}</td><td><div class="label">VENDEDOR</div>{{ $venta->usuario?->nombre_completo ?? 'No registrado' }}<br><span class="label">MONEDA</span> {{ $venta->cMoneda }}@if($venta->finalizada_at)<br><span class="label">FINALIZADO</span> {{ $venta->finalizada_at->format('d/m/Y') }}@endif</td></tr></table>
@php
    $serviciosConFechas = $venta->detalles->filter(fn ($detalle) => $detalle->dFechaInicio && $detalle->dFechaFin);
@endphp
@if($serviciosConFechas->isNotEmpty())
<h2>Fechas del viaje</h2>
<table><thead><tr><th style="width:50%">Servicio</th><th>Salida</th><th>Regreso</th></tr></thead><tbody>
@foreach($serviciosConFechas as $detalle)
    <tr><td>{{ $detalle->cPaquete }}</td><td><strong>{{ $detalle->dFechaInicio->format('d/m/Y') }}</strong></td><td><strong>{{ $detalle->dFechaFin->format('d/m/Y') }}</strong></td></tr>
@endforeach
</tbody></table>
@endif
<h2>Servicios y paquetes</h2>
<table><thead><tr><th style="width:32%">Paquete</th><th class="right">Cantidad</th><th class="right">Precio regular</th><th class="right">Descuento unitario</th><th class="right">Precio final</th><th class="right">Subtotal</th></tr></thead><tbody>
@foreach($venta->detalles as $detalle)
@php
    $subtotalMenores = (int) round((float) $detalle->dPrecioMenor * 100) * $detalle->iCantidadMenores;
    $subtotalAdultos = (int) round((float) $detalle->dSubtotal * 100) - $subtotalMenores;
@endphp
<tr><td><strong>{{ $detalle->cPaquete }}</strong><br><span class="promocion">{{ $detalle->dPrecioRegular === null ? 'Desglose histórico no disponible' : ($detalle->cPromocion ?: 'Sin promoción') }}</span></td><td class="right">{{ $detalle->iCantidad }} {{ $detalle->iCantidad == 1 ? 'adulto' : 'adultos' }}</td><td class="right">{{ $detalle->dPrecioRegular === null ? '—' : number_format($detalle->dPrecioRegular, 2) }}</td><td class="right">{{ $detalle->dDescuentoUnitario === null ? '—' : number_format($detalle->dDescuentoUnitario, 2) }}</td><td class="right">{{ number_format($detalle->dPrecioUnitario, 2) }}</td><td class="right">{{ number_format($subtotalAdultos / 100, 2) }}</td></tr>
@if($detalle->iCantidadMenores > 0)
<tr><td><strong>{{ $detalle->cPaquete }}</strong><br><span class="muted">Tarifa para menores</span></td><td class="right">{{ $detalle->iCantidadMenores }} {{ $detalle->iCantidadMenores == 1 ? 'menor' : 'menores' }}</td><td class="right">{{ number_format($detalle->dPrecioMenor, 2) }}</td><td class="right">0.00</td><td class="right">{{ $detalle->dPrecioMenor > 0 ? number_format($detalle->dPrecioMenor, 2) : 'Gratis' }}</td><td class="right">{{ number_format($subtotalMenores / 100, 2) }}</td></tr>
@endif
@endforeach
</tbody></table>
@if($venta->detalles->contains(fn ($detalle) => $detalle->dPrecioRegular === null || $detalle->dDescuentoUnitario === null))
<p class="nota">En operaciones antiguas sin desglose guardado, se conserva el precio final original. Los datos históricos faltantes se indican con —.</p>
@endif
<table class="totales"><tr>
    <td style="width:{{ $compraSaldada ? '50' : '33' }}%"><span class="label">TOTAL ({{ $venta->cMoneda }})</span><br><strong>{{ number_format($venta->dTotal, 2) }}</strong></td>
    <td style="width:{{ $compraSaldada ? '50' : '33' }}%"><span class="label">{{ $compraSaldada ? 'TOTAL PAGADO' : 'ABONADO' }} ({{ $venta->cMoneda }})</span><br><strong class="verde">{{ number_format((float) $venta->dTotal - $venta->saldo_centavos / 100, 2) }}</strong></td>
    @if(!$compraSaldada)
    <td><span class="label">POR ABONAR ({{ $venta->cMoneda }})</span><br><strong>{{ number_format($venta->saldo_centavos / 100, 2) }}</strong></td>
    @endif
</tr></table>
@if($compraSaldada)
<div style="margin-top:20px;padding:16px;background:#e9f8f0;border:1px solid #b9e2cc;border-radius:8px;color:#24885b">
    <strong style="font-size:15px">COMPRA SALDADA</strong>
    <p style="margin:6px 0 0">El importe total de {{ number_format($venta->dTotal, 2) }} {{ $venta->cMoneda }} ha sido pagado en su totalidad.</p>
    <span style="font-size:9px">Fecha de liquidación: {{ $venta->finalizada_at?->format('d/m/Y') }}</span>
</div>
@else
<h2>Historial de abonos</h2>
<table><thead><tr><th>Fecha</th><th>Método / Referencia</th><th>Registró</th><th class="right">Importe ({{ $venta->cMoneda }})</th></tr></thead><tbody>
@forelse($venta->pagos->whereNull('anulado_at') as $pago)
<tr><td>{{ ($pago->dFechaPago ?? $pago->created_at)->format('d/m/Y') }}</td><td>{{ $pago->cMetodo }}<br><span class="muted">{{ $pago->cReferencia ?: 'Sin referencia' }}</span></td><td>{{ $pago->usuario?->nombre_completo ?? 'No registrado' }}</td><td class="right">{{ number_format($pago->dImporte, 2) }}</td></tr>
@empty<tr><td colspan="4">Sin abonos registrados.</td></tr>@endforelse
</tbody></table>
@endif
@if($venta->cObservaciones)<div class="nota"><strong>Observaciones:</strong><br>{{ $venta->cObservaciones }}</div>@endif
</body></html>
