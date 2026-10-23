@php
    $detallesConItinerario = $venta->detalles->filter(fn ($detalle) => $detalle->tiene_itinerario);
    $pdfsItinerario = $detallesConItinerario->filter(fn ($detalle) => $detalle->ruta_itinerario_pdf);
    $hayItinerarioCatalogo = $detallesConItinerario->contains(fn ($detalle) => !$detalle->ruta_itinerario_pdf);
@endphp
@if($hayItinerarioCatalogo)
<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'itinerario']) }}" data-titulo="Itinerario · {{ $venta->folio }}"><i class="fas fa-route" aria-hidden="true"></i>Ver itinerario</button></li>
@endif
@foreach($pdfsItinerario as $detalleItinerario)
<li><a class="dropdown-item" href="{{ route('agencia.ventas.itinerarioArchivo', ['venta' => $venta, 'detalle' => $detalleItinerario->iID]) }}" target="_blank" rel="noopener"><i class="fas fa-route" aria-hidden="true"></i>Ver itinerario{{ $detallesConItinerario->count() > 1 ? ' · '.$detalleItinerario->cPaquete : '' }}</a></li>
@endforeach
