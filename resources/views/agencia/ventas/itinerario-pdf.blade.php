<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Itinerario de viaje</title>
<style>
@page{margin:36px 36px 48px}body{font-family:'DejaVu Sans',sans-serif;font-size:10px;color:#25324b;line-height:1.55;word-wrap:break-word}table{border-collapse:collapse;width:100%}td{vertical-align:top}h1{font-size:21px;margin:0 0 4px}h2{font-size:15px;margin:0;color:#536fe8}h3{font-size:11px;margin:0 0 4px;page-break-after:avoid}p{margin:5px 0 10px;white-space:pre-wrap}.header{border-bottom:2px solid #536fe8}.header td{padding:0 0 18px}.logo{width:70px;height:70px}.muted{color:#64748b;font-size:9px}.info{margin:16px 0 22px}.info td{background:#f8fafc;padding:12px;width:50%}.dia{padding:11px 12px;background:#f1f4fb;border-left:3px solid #536fe8;margin-top:20px;page-break-inside:avoid;page-break-after:avoid}.actividad{margin:14px 0 18px;padding-left:12px;border-left:1px solid #e1e7f0}.incluidos{color:#24885b}.paquete-siguiente{page-break-before:always}
</style></head><body>
@foreach($recorridos as $recorrido)
<div @class(['paquete-siguiente' => !$loop->first])>
<table class="header"><tr><td style="width:85px">@if($logo)<img class="logo" src="{{ $logo }}" alt="Logo">@endif</td><td><span class="muted">{{ $marca }}</span><h1>Itinerario de viaje</h1><strong>{{ $recorrido['nombre'] }}</strong>@if($venta->cNombre)<div class="muted">{{ $venta->cNombre }}</div>@endif</td></tr></table>
<table class="info"><tr><td><strong>{{ count($recorrido['dias']) }} {{ count($recorrido['dias']) === 1 ? 'día' : 'días' }}</strong><br>{{ $recorrido['contratado'] ? 'Recorrido contratado' : 'Recorrido actual del servicio' }}</td><td><span class="muted">FECHAS DEL VIAJE</span><br>@if($recorrido['inicio']){{ $recorrido['inicio']->format('d/m/Y') }}@if($recorrido['fin']) al {{ $recorrido['fin']->format('d/m/Y') }}@endif @else Por confirmar @endif</td></tr></table>
@if(!$recorrido['contratado'])<p class="muted">Esta operación no guardó una copia del itinerario. Se muestra el recorrido actual del servicio como referencia.</p>@endif
@foreach($recorrido['dias'] as $dia)
<div class="dia"><h2>Día {{ $dia['numero'] }} · {{ $dia['titulo'] }}</h2>@if($recorrido['inicio'])<span class="muted">{{ $recorrido['inicio']->copy()->addDays($dia['numero'] - 1)->format('d/m/Y') }}</span>@endif</div>
@if(!empty($dia['descripcion']))<p>{{ $dia['descripcion'] }}</p>@endif
@foreach($dia['actividades'] as $actividad)
<div class="actividad"><h3>@if(!empty($actividad['hora'])){{ $actividad['hora'] }} · @endif{{ $actividad['titulo'] }}</h3>
@if(!empty($actividad['lugar']))<div class="muted">Lugar: {{ $actividad['lugar'] }}</div>@endif
@if(!empty($actividad['descripcion']))<p>{{ $actividad['descripcion'] }}</p>@endif
@if(!empty($actividad['incluidos']))<p class="incluidos"><strong>Incluye:</strong> {{ $actividad['incluidos'] }}</p>@endif
</div>
@endforeach
@endforeach
</div>
@endforeach
</body></html>
