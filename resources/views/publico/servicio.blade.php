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
            <section class="mt-5 experiencia-galeria" data-galeria>
                <h2 class="catalogo-subtitulo">Conoce la experiencia</h2>
                <div class="experiencia-miniaturas experiencia-grid" aria-label="Seleccionar imagen">
                    @foreach($paquete->imagenes as $imagen)
                    <button type="button" class="experiencia-miniatura" data-galeria-miniatura data-bs-toggle="modal" data-bs-target="#galeriaAmpliada" data-imagen="{{ asset(str_replace('public/', 'storage/', $imagen->cRutaImagen)) }}" data-descripcion="Imagen {{ $loop->iteration }} de {{ $paquete->cNombre }}" aria-label="Ampliar imagen {{ $loop->iteration }}"><img src="{{ asset(str_replace('public/', 'storage/', $imagen->cRutaImagen)) }}" alt="" loading="lazy"><span class="experiencia-grid-ampliar"><i class="fas fa-expand" aria-hidden="true"></i></span></button>
                    @endforeach
                </div>
                <div class="modal fade experiencia-modal" id="galeriaAmpliada" tabindex="-1" aria-labelledby="galeriaAmpliadaTitulo" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
                        <div class="modal-header"><h2 class="modal-title" id="galeriaAmpliadaTitulo">{{ $paquete->cNombre }}</h2><button type="button" class="experiencia-cerrar" data-bs-dismiss="modal" aria-label="Cerrar galería"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
                        <div class="modal-body"><img data-galeria-ampliada src="{{ asset(str_replace('public/', 'storage/', $paquete->imagenes->first()->cRutaImagen)) }}" alt="Imagen 1 de {{ $paquete->cNombre }}"></div>
                        @if($paquete->imagenes->count() > 1)<div class="modal-footer"><button type="button" class="catalogo-aplicar" data-galeria-anterior><i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Anterior</button><span data-galeria-contador aria-live="polite">1 / {{ $paquete->imagenes->count() }}</span><button type="button" class="catalogo-aplicar" data-galeria-siguiente>Siguiente<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></button></div>@endif
                    </div></div>
                </div>
            </section>
            @endif
        </div>
        <div class="col-lg-4"><aside class="catalogo-consulta">
            <small class="text-muted">Precio por persona</small>
            @if($paquete->l_promocion_vigente)<del class="d-block text-muted">{{ number_format($paquete->dPrecioVenta, 2) }} {{ $paquete->cMoneda }}</del>@endif
            <strong class="catalogo-precio">{{ number_format($paquete->d_precio_final, 2) }} <small>{{ $paquete->cMoneda }}</small></strong>
            @include('publico.partials.precio-menor')
            @if($paquete->l_promocion_vigente)<div class="catalogo-oferta"><i class="fas fa-tag me-1" aria-hidden="true"></i>{{ $paquete->cNombrePromocion ?: 'Promoción vigente' }}<small class="d-block mt-1">Descuento de {{ number_format($paquete->d_monto_descuento, 2) }} {{ $paquete->cMoneda }} por persona.</small>@if($paquete->cDescripcionPromocion)<small class="d-block mt-2">{{ strip_tags($paquete->cDescripcionPromocion) }}</small>@endif</div>@endif
            @if($paquete->dFechaInicioViaje && $paquete->dFechaFinViaje)<div class="catalogo-fechas"><div><small>Salida</small><strong>{{ $paquete->dFechaInicioViaje->format('d/m/Y') }}</strong></div><div><small>Regreso</small><strong>{{ $paquete->dFechaFinViaje->format('d/m/Y') }}</strong></div></div>@endif
            <p class="text-muted small mt-3">Consulta disponibilidad y condiciones con la agencia para planear tu viaje.</p>
            @if($whatsappUrl)<a class="landing-button w-100" href="{{ $whatsappUrl.'?text='.rawurlencode('Hola, quiero información sobre '.$paquete->cNombre.' ('.$paquete->cCodigo.').') }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp" aria-hidden="true"></i>Consultar por WhatsApp</a>@else<a class="landing-button w-100" href="{{ route('landing') }}#contacto"><i class="fas fa-comment-dots" aria-hidden="true"></i>Consultar con la agencia</a>@endif
            @if($content['phone'])<a class="catalogo-telefono mt-3" href="tel:{{ preg_replace('/[^0-9+]/', '', $content['phone']) }}"><i class="fas fa-phone me-2" aria-hidden="true"></i>{{ $content['phone'] }}</a>@endif
        </aside></div>
    </div>
</section>
@endsection
