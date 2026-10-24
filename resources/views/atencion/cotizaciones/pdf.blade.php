<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Cotización {{ $cotizacion->folio }}</title><style>

@page{margin:36px 36px 48px}body{font-family:'DejaVu Sans',sans-serif;font-size:10px;color:#25324b;line-height:1.5}table{border-collapse:collapse;width:100%}thead{display:table-header-group}tr{page-break-inside:avoid}th{text-align:left;background:#f1f4fb;color:#64748b;font-size:8px;padding:9px 8px;border-bottom:1px solid #e1e7f0}td{padding:9px 8px;border-bottom:1px solid #edf0f5;vertical-align:top;word-wrap:break-word}h2{font-size:15px;margin:22px 0 10px;font-weight:bold}h1{font-size:21px;margin:0 0 4px}.muted{color:#8793a5;font-size:8px}.right{text-align:right}.header td{border:0;padding:0 0 18px}.logo{width:76px;height:76px;object-fit:contain}.header{border-bottom:2px solid #536fe8}.folio{color:#536fe8;font-size:14px;font-weight:bold}.info{margin-top:16px}.info td{border:0;background:#f8fafc;width:50%;padding:12px}.label{font-size:8px;color:#8793a5}.totales{margin:18px 0}.totales td{width:33%;background:#f1f4fb;border:4px solid white;padding:12px}.totales strong{font-size:14px}.verde{color:#24885b}.nota{margin-top:20px;font-size:8px;color:#8793a5;white-space:pre-wrap}.promocion{color:#24885b;font-size:8px}

.items{table-layout:fixed}.items td{font-size:9px}.items th{font-size:8px}.amount{text-align:right}.note{white-space:pre-wrap;word-wrap:break-word}.total-cotizacion td{width:100%}.total-cotizacion{page-break-inside:avoid}
</style></head><body>
<table class="header"><tr><td style="width:95px">@if($logo)<img class="logo" src="{{ $logo }}" alt="Logo de la agencia">@endif</td><td><h1>{{ $marca }}</h1><div>Cotización</div></td><td class="right"><div class="folio">{{ $cotizacion->folio }}</div></td></tr></table>
@if($cotizacion->nombre)<h2>{{ $cotizacion->nombre }}</h2>@endif
<table class="info"><tr><td><span class="muted">PREPARADA PARA</span><br><strong>{{ $cotizacion->cliente_nombre }}</strong></td><td><span class="muted">FECHA DE COTIZACIÓN</span><br>{{ $cotizacion->created_at->format('d/m/Y') }}<br><span class="muted">Asesor: {{ $cotizacion->usuario?->nombre_completo }}</span></td></tr></table>
<table class="items"><thead><tr><th style="width:45%">Servicio / Fechas</th><th style="width:15%">Pasajeros</th><th style="width:20%">Precio unitario</th><th style="width:20%">Importe</th></tr></thead><tbody>
@foreach($cotizacion->partidas as $partida)
<tr><td><strong>{{ $partida['nombre'] }}</strong><br><span class="muted">{{ $partida['inicio'] ?? 'Por confirmar' }} — {{ $partida['fin'] ?? 'Por confirmar' }}</span>@if($partida['promocion'])<br><span class="muted">{{ $partida['promocion'] }}<br>Descuento por adulto: {{ number_format($partida['descuento'], 2) }} {{ $cotizacion->moneda }}</span>@endif</td><td>{{ $partida['adultos'] }} adultos</td><td>{{ number_format($partida['precio'], 2) }} {{ $cotizacion->moneda }}</td><td class="amount">{{ number_format($partida['precio'] * $partida['adultos'], 2) }} {{ $cotizacion->moneda }}</td></tr>
@if($partida['menores'] > 0)
<tr><td>{{ $partida['nombre'] }}<br><span class="muted">Tarifa de menores</span></td><td>{{ $partida['menores'] }} menores</td><td>{{ $partida['precio_menor'] == 0 ? 'Gratis' : number_format($partida['precio_menor'], 2).' '.$cotizacion->moneda }}</td><td class="amount">{{ number_format($partida['precio_menor'] * $partida['menores'], 2) }} {{ $cotizacion->moneda }}</td></tr>
@endif
@endforeach
</tbody></table>
<table class="totales total-cotizacion"><tr><td class="right"><span class="label">TOTAL ({{ $cotizacion->moneda }})</span><br><strong>{{ number_format($cotizacion->total, 2) }}</strong></td></tr></table>
@if($cotizacion->observaciones)<h2>Observaciones</h2><div class="note">{{ $cotizacion->observaciones }}</div>@endif
<div class="nota">Cotización sujeta a disponibilidad y cambios de tarifa al confirmar la reserva. No constituye una venta ni un apartado.<br>Gracias por confiar en {{ $marca }}.</div>
</body></html>
