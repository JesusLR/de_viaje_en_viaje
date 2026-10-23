@if(auth()->user()->hasPermission('itinerarios.ver'))
@php $hayItinerarios = false; @endphp
@foreach($venta->detalles as $detalle)
    @php
        $contratado = !empty($detalle->itinerario);
        $dias = $detalle->ruta_itinerario_pdf ? [] : ($contratado ? $detalle->itinerario : ($detalle->servicio?->itinerarioSnapshot() ?? []));
    @endphp
    @if($detalle->ruta_itinerario_pdf)
    @php $hayItinerarios = true; @endphp
    <section class="mt-4"><h5 class="fw-bold">Itinerario PDF · {{ $detalle->cPaquete }}</h5>
        @if(!$detalle->cItinerarioPdf)<p class="small text-muted">Se muestra el PDF actual del servicio como referencia; esta operación no guardó una copia.</p>@endif
        <a class="btn btn-outline-primary" href="{{ route('agencia.ventas.itinerarioArchivo', ['venta' => $venta, 'detalle' => $detalle->iID]) }}" target="_blank" rel="noopener">Ver itinerario PDF</a>
    </section>
    @endif
    @if($dias)
    @php $hayItinerarios = true; @endphp
    <section class="mt-4"><h5 class="fw-bold">{{ $contratado ? 'Itinerario contratado' : 'Itinerario actual del servicio' }} · {{ $detalle->cPaquete }}</h5>
        @if(!$contratado)<p class="small text-muted">Esta operación no tiene una copia del itinerario guardada. Se muestra el recorrido actual del servicio como referencia.</p>@endif
        @include('servicios.itinerario.recorrido', ['dias' => $dias, 'fechaInicio' => $detalle->dFechaInicio, 'interno' => true])
    </section>
    @endif
@endforeach
@if(!$hayItinerarios && ($seccion ?? null) === 'itinerario')<div class="alert alert-info mb-0">Los paquetes de esta operación no tienen itinerario disponible.</div>@endif
@endif
