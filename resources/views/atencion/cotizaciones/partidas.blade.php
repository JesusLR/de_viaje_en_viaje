<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Servicio / Fechas</th><th>Pasajeros</th><th>Precio unitario</th><th>Importe</th></tr></thead><tbody>
@foreach($cotizacion->partidas as $partida)
<tr><td>{{ $partida['nombre'] }}<small class="d-block text-muted">{{ $partida['inicio'] ?? 'Por confirmar' }} — {{ $partida['fin'] ?? 'Por confirmar' }}</small>@if($partida['promocion'])<small class="d-block text-success">{{ $partida['promocion'] }} · Descuento por adulto: {{ number_format($partida['descuento'], 2) }} {{ $cotizacion->moneda }}</small>@endif</td><td>{{ $partida['adultos'] }} adultos</td><td>{{ number_format($partida['precio'], 2) }} {{ $cotizacion->moneda }}</td><td>{{ number_format($partida['precio'] * $partida['adultos'], 2) }} {{ $cotizacion->moneda }}</td></tr>
@if($partida['menores'] > 0)
<tr><td>{{ $partida['nombre'] }} · Tarifa de menores</td><td>{{ $partida['menores'] }} menores</td><td>{{ $partida['precio_menor'] == 0 ? 'Gratis' : number_format($partida['precio_menor'], 2).' '.$cotizacion->moneda }}</td><td>{{ number_format($partida['precio_menor'] * $partida['menores'], 2) }} {{ $cotizacion->moneda }}</td></tr>
@endif
@endforeach
</tbody></table></div>
