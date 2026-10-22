<article class="landing-package">
    <a class="landing-package-image" href="{{ route('catalogo.publico.show', $paquete->iID) }}" aria-label="Ver {{ $paquete->cNombre }}">
        @if($paquete->cImagenPrincipal)
            <img src="{{ asset(str_replace('public/', 'storage/', $paquete->cImagenPrincipal)) }}" alt="{{ $paquete->cNombre }}" loading="lazy">
        @else
            <div class="landing-package-placeholder"><span class="viajes-placeholder-label">EXPLORA · DESCUBRE · VIAJA</span><i class="fas fa-plane-departure" aria-hidden="true"></i><span>{{ $paquete->destino?->cNombre ?? 'Tu próxima aventura' }}</span></div>
        @endif
        @if($paquete->l_promocion_vigente)
            <span class="landing-promo"><i class="fas fa-tag me-1" aria-hidden="true"></i>{{ $paquete->cNombrePromocion ?: 'Promoción' }}</span>
        @endif
    </a>
    <div class="landing-package-body">
        @if($paquete->destino)
            <span class="landing-package-location"><i class="fas fa-map-marker-alt me-1" aria-hidden="true"></i>{{ $paquete->destino->cNombre }}</span>
        @endif
        <h3><a class="catalogo-nombre" href="{{ route('catalogo.publico.show', $paquete->iID) }}">{{ $paquete->cNombre }}</a></h3>
        <p>{{ \Illuminate\Support\Str::limit(strip_tags($paquete->cDescripcionCorta ?? ''), 170) }}</p>
        @if($paquete->dFechaInicioViaje && $paquete->dFechaFinViaje)
            <div class="landing-package-dates"><i class="far fa-calendar-alt me-2" aria-hidden="true"></i>{{ $paquete->dFechaInicioViaje->format('d/m/Y') }} — {{ $paquete->dFechaFinViaje->format('d/m/Y') }}</div>
        @endif
        @include('publico.partials.precio-menor')
        <div class="landing-package-bottom">
            <div><small>Precio por persona</small>@if($paquete->l_promocion_vigente)<del>{{ number_format($paquete->dPrecioVenta, 2) }}</del>@endif<strong>{{ number_format($paquete->d_precio_final, 2) }} <small>{{ $paquete->cMoneda }}</small></strong></div>
            <a class="landing-package-link" aria-label="Ver detalles de {{ $paquete->cNombre }}" href="{{ route('catalogo.publico.show', $paquete->iID) }}"><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @if($paquete->l_promocion_vigente)
            <small class="landing-discount">Descuento: {{ number_format($paquete->d_monto_descuento, 2) }} {{ $paquete->cMoneda }} por persona</small>
        @endif
    </div>
</article>
