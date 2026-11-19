<option value="">Selecciona un paquete</option>
@foreach($paquetes as $paquete)
<option value="{{ $paquete->iID }}"
    data-precio="{{ $paquete->d_precio_final }}" data-regular="{{ $paquete->dPrecioVenta }}"
    data-opciones="{{ json_encode(app(\App\Services\OpcionesPrecioService::class)->publicas($paquete)) }}"
    data-multiple="{{ $paquete->lOpcionesPrecio ? 1 : 0 }}"
    data-moneda="{{ $paquete->cMoneda }}"
    data-menores-activo="{{ $paquete->lPrecioMenorActivo ? 1 : 0 }}" data-precio-menor="{{ $paquete->lPrecioMenorActivo ? $paquete->dPrecioMenor : 0 }}"
    data-inicio="{{ $paquete->dFechaInicioViaje?->format('d/m/Y') ?? 'Sin fechas de viaje' }}"
    data-fin="{{ $paquete->dFechaFinViaje?->format('d/m/Y') ?? 'Sin fechas de viaje' }}"
    data-promocion="{{ $paquete->l_promocion_vigente ? ($paquete->cNombrePromocion ?: 'Promoción vigente') : '' }}"
    data-tipo-descuento="{{ $paquete->l_promocion_vigente ? $paquete->cTipoDescuento : '' }}"
    data-valor-descuento="{{ $paquete->l_promocion_vigente ? $paquete->dValorDescuento : 0 }}">
    {{ $paquete->cCodigo }} — {{ $paquete->cNombre }} · {{ $paquete->lOpcionesPrecio ? 'Desde ' : '' }}{{ number_format($paquete->d_precio_final, 2) }} {{ $paquete->cMoneda }}{{ $paquete->l_promocion_vigente ? ' · Con promoción' : '' }}
</option>
@endforeach
