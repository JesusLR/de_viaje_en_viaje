@if($detalle->dFechaInicio && $detalle->dFechaFin)
    {{ $detalle->dFechaInicio->format('d/m/Y') }} — {{ $detalle->dFechaFin->format('d/m/Y') }}
@else
    <span class="text-muted">Sin fechas de viaje</span>
@endif
