@extends('layouts.publico')
@section('titulo', $paquete->cNombre)
@section('descripcion', strip_tags($paquete->cDescripcionCorta ?? $content['hero_description']))
@section('contenido')
<section class="container catalogo-detalle">
    <a class="catalogo-limpiar d-inline-block mb-4" href="{{ route('catalogo.publico.index') }}"><i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Volver al catálogo</a>
    <div class="row g-4 g-lg-5">
        <div class="col-lg-8">
            @if($paquete->cImagenPrincipal)
                <img class="catalogo-imagen-principal" src="{{ asset(str_replace('public/', 'storage/', $paquete->cImagenPrincipal)) }}" alt="{{ $paquete->cNombre }}">
            @else
                <div class="catalogo-imagen-principal landing-package-placeholder"><i class="fas fa-plane-departure" aria-hidden="true"></i><span>{{ $paquete->destino?->cNombre ?? 'Tu próxima aventura' }}</span></div>
            @endif
            <div class="catalogo-descripcion mt-4">
                @if($paquete->destino)<span class="landing-eyebrow"><i class="fas fa-map-marker-alt me-1" aria-hidden="true"></i>{{ $paquete->destino->ubicacion_completa }}</span>@endif
                <h1>{{ $paquete->cNombre }}</h1>
                @if($paquete->categoria)<span class="catalogo-categoria">{{ $paquete->categoria->cNombre }}</span>@endif
                <p class="mt-3">{{ strip_tags($paquete->cDescripcionCorta ?? '') }}</p>
                @if($paquete->cDescripcionCompleta)<div class="catalogo-texto">{{ strip_tags($paquete->cDescripcionCompleta) }}</div>@endif
            </div>
            @if($paquete->imagenes->isNotEmpty())
            <section class="mt-4"><h2 class="catalogo-subtitulo">Conoce la experiencia</h2><div class="row g-3">@foreach($paquete->imagenes as $imagen)<div class="col-6 col-md-4"><a href="{{ asset(str_replace('public/', 'storage/', $imagen->cRutaImagen)) }}" target="_blank" rel="noopener"><img class="catalogo-galeria" src="{{ asset(str_replace('public/', 'storage/', $imagen->cRutaImagen)) }}" alt="Imagen de {{ $paquete->cNombre }}" loading="lazy"></a></div>@endforeach</div></section>
            @endif
        </div>
        <div class="col-lg-4"><aside class="catalogo-consulta">
            <small class="text-muted">Precio por persona</small>
            @if($paquete->l_promocion_vigente)<del class="d-block text-muted">{{ number_format($paquete->dPrecioVenta, 2) }} {{ $paquete->cMoneda }}</del>@endif
            <strong class="catalogo-precio">{{ number_format($paquete->d_precio_final, 2) }} <small>{{ $paquete->cMoneda }}</small></strong>
            @if($paquete->l_promocion_vigente)<div class="catalogo-oferta"><i class="fas fa-tag me-1" aria-hidden="true"></i>{{ $paquete->cNombrePromocion ?: 'Promoción vigente' }}<small class="d-block mt-1">Descuento de {{ number_format($paquete->d_monto_descuento, 2) }} {{ $paquete->cMoneda }} por persona.</small>@if($paquete->cDescripcionPromocion)<small class="d-block mt-2">{{ strip_tags($paquete->cDescripcionPromocion) }}</small>@endif</div>@endif
            @if($paquete->dFechaInicioViaje && $paquete->dFechaFinViaje)<div class="catalogo-fechas"><div><small>Salida</small><strong>{{ $paquete->dFechaInicioViaje->format('d/m/Y') }}</strong></div><div><small>Regreso</small><strong>{{ $paquete->dFechaFinViaje->format('d/m/Y') }}</strong></div></div>@endif
            <p class="text-muted small mt-3">Consulta disponibilidad y condiciones con la agencia para planear tu viaje.</p>
            @if($whatsappUrl)<a class="landing-button w-100" href="{{ $whatsappUrl.'?text='.rawurlencode('Hola, quiero información sobre '.$paquete->cNombre.' ('.$paquete->cCodigo.').') }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp" aria-hidden="true"></i>Consultar por WhatsApp</a>@else<a class="landing-button w-100" href="{{ route('landing') }}#contacto"><i class="fas fa-comment-dots" aria-hidden="true"></i>Consultar con la agencia</a>@endif
            @if($content['phone'])<a class="catalogo-telefono mt-3" href="tel:{{ preg_replace('/[^0-9+]/', '', $content['phone']) }}"><i class="fas fa-phone me-2" aria-hidden="true"></i>{{ $content['phone'] }}</a>@endif
        </aside></div>
    </div>
</section>
@endsection
