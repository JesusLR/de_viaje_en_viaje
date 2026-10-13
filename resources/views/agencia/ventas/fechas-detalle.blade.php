@if(!$detalle->lUsaVigencia)
    <span class="text-muted">Sin vigencia</span>
@elseif($detalle->dFechaInicio && $detalle->dFechaFin)
    {{ $detalle->dFechaInicio->format('d/m/Y') }} — {{ $detalle->dFechaFin->format('d/m/Y') }}
@else
    <span class="text-muted">Fechas de inicio y fin no registradas</span>
@endif
