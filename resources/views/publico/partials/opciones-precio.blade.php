@if($paquete->lOpcionesPrecio)
@php
    $tarifasCatalogo = app(\App\Services\OpcionesPrecioService::class)->publicas($paquete);
    $menorPrecioCatalogo = collect($tarifasCatalogo)->min('precio');
@endphp
<section class="catalogo-tarifas mt-4" aria-labelledby="tituloTarifas">
    <h2 class="catalogo-subtitulo" id="tituloTarifas">Opciones de precio</h2>
    <p class="catalogo-tarifas-ayuda">Compara las tarifas y elige la opción para tu viaje. Precios por estancia completa.</p>
    <div class="catalogo-tarifas-grid">
    @foreach($tarifasCatalogo as $opcion)
        <article class="catalogo-tarifa{{ $opcion['precio'] == $menorPrecioCatalogo ? ' catalogo-tarifa-destacada' : '' }}">
            <div class="catalogo-tarifa-cabecera"><span class="catalogo-tarifa-tipo"><i class="fas {{ $opcion['unidad'] === 'HABITACION' ? 'fa-bed' : 'fa-user' }}" aria-hidden="true"></i> {{ $opcion['unidad'] === 'HABITACION' ? 'Por habitación' : 'Por persona' }}</span>@if($opcion['precio'] == $menorPrecioCatalogo)<span class="catalogo-tarifa-etiqueta">Menor precio</span>@endif</div>
            <h3>{{ $opcion['nombre'] }}</h3>
            <div class="catalogo-tarifa-importes">
                <div><span class="catalogo-tarifa-label">{{ $opcion['unidad'] === 'HABITACION' ? 'Habitación' : 'Adulto' }}</span>
                    @if($opcion['precio'] < $opcion['dPrecioVenta'])<del>{{ number_format($opcion['dPrecioVenta'], 2) }} {{ $paquete->cMoneda }}</del>@endif
                    <strong>{{ number_format($opcion['precio'], 2) }} <small>{{ $paquete->cMoneda }}</small></strong>
                </div>
                @if($opcion['lPrecioMenorActivo'])
                <div class="catalogo-tarifa-menor"><span class="catalogo-tarifa-label">Por menor</span><strong>Menor: {{ number_format($opcion['dPrecioMenor'], 2) }} <small>{{ $paquete->cMoneda }}</small></strong>@if($opcion['dPrecioMenor'] == 0)<small>Sin cargo adicional</small>@endif</div>
                @endif
            </div>
            @if($opcion['unidad'] === 'HABITACION')
            <p class="catalogo-tarifa-capacidad"><i class="fas fa-users" aria-hidden="true"></i> Hasta {{ $opcion['adultos'] }} adultos{{ $opcion['menores'] > 0 ? ' y '.$opcion['menores'].' menores' : '' }} por habitación.</p>
            @if(!$opcion['lPrecioMenorActivo'])<small class="catalogo-tarifa-nota">{{ $opcion['menores'] > 0 ? 'Adultos y menores incluidos' : 'Adultos incluidos' }} en el precio de la habitación.</small>@endif
            @endif
            @if($opcion['cama'])<small class="catalogo-tarifa-nota"><i class="fas fa-bed" aria-hidden="true"></i> {{ $opcion['cama'] }}</small>@endif
            @if($opcion['descripcion'])<details class="catalogo-tarifa-detalles"><summary>Ver condiciones de la tarifa</summary><p>{{ $opcion['descripcion'] }}</p></details>@endif
            <a class="catalogo-tarifa-consultar" href="{{ !empty($whatsappUrl) ? $whatsappUrl.'?text='.rawurlencode('Hola, quiero información sobre '.$paquete->cNombre.', opción '.$opcion['nombre'].'.') : route('landing').'#contacto' }}" @if(!empty($whatsappUrl)) target="_blank" rel="noopener" @endif>Consultar esta opción <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </article>
    @endforeach
    </div>
</section>
@endif
