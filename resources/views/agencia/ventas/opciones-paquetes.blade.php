<option value="">Selecciona un paquete</option>
@foreach($paquetes as $paquete)
<option value="{{ $paquete->iID }}"
    data-precio="{{ $paquete->d_precio_final }}" data-regular="{{ $paquete->dPrecioVenta }}"
    data-moneda="{{ $paquete->cMoneda }}"
    data-inicio="{{ $paquete->lUsaVigencia ? $paquete->dFechaInicioVigencia?->format('d/m/Y') : 'Sin vigencia' }}"
    data-fin="{{ $paquete->lUsaVigencia ? $paquete->dFechaFinVigencia?->format('d/m/Y') : 'Sin vigencia' }}"
    data-promocion="{{ $paquete->l_promocion_vigente ? ($paquete->cNombrePromocion ?: 'Promoción vigente') : '' }}"
    data-tipo-descuento="{{ $paquete->l_promocion_vigente ? $paquete->cTipoDescuento : '' }}"
    data-valor-descuento="{{ $paquete->l_promocion_vigente ? $paquete->dValorDescuento : 0 }}">
    {{ $paquete->cCodigo }} — {{ $paquete->cNombre }} · {{ number_format($paquete->d_precio_final, 2) }} {{ $paquete->cMoneda }}{{ $paquete->l_promocion_vigente ? ' · Con promoción' : '' }}
</option>
@endforeach
