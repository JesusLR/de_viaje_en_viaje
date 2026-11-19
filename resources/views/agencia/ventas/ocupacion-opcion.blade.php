@if(!empty($opcion) && ($compacto ?? false))
<br><small>{{ $opcion['nombre'] }} · {{ $opcion['unidad'] === 'HABITACION' ? 'Por habitación' : 'Por persona' }} · Estancia completa{{ ($opcion['menoresIncluidos'] ?? false) ? ' · Menores incluidos' : '' }}</small>
@elseif(!empty($opcion))
<br><small><strong>Opción: {{ $opcion['nombre'] }}</strong> · Por {{ $opcion['unidad'] === 'HABITACION' ? 'habitación' : 'adulto' }}<br>{{ $opcion['descripcion'] ?? '' }}{{ !empty($opcion['cama']) ? ' · '.$opcion['cama'] : '' }}<br>Capacidad por habitación: {{ $opcion['adultos'] }} adultos y {{ $opcion['menores'] }} menores. Estancia completa.@if($opcion['unidad'] === 'HABITACION' && !($opcion['lPrecioMenorActivo'] ?? false))<br>Adultos y menores incluidos en el precio de la habitación.@endif</small>
@endif
