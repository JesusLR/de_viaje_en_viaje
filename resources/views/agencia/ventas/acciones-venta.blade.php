<div class="apartado-acciones"><div class="dropdown apartado-mas">
    <button type="button" class="apartado-icono" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones" aria-label="Más opciones de {{ $venta->folio }}"><i class="fas fa-ellipsis-vertical" aria-hidden="true"></i></button>
    <ul class="dropdown-menu dropdown-menu-end apartado-menu">
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'detalle']) }}" data-titulo="Detalle · {{ $venta->folio }}"><i class="fas fa-receipt" aria-hidden="true"></i>Ver detalle</button></li>
        @if(auth()->user()->hasPermission('itinerarios.ver') && $venta->detalles->contains(fn ($detalle) => $detalle->tiene_itinerario))
        @include('agencia.ventas.acciones-itinerario')
        @if(auth()->user()->hasPermission('itinerarios.pdf') && auth()->user()->hasPermission('ventas.pdf'))
        <li><a class="dropdown-item" href="{{ route('agencia.ventas.itinerarioPdf', $venta) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i>Descargar itinerario PDF</a></li>
        @endif
        @endif
        @if($venta->tiene_historial_abonos)
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'historial']) }}" data-titulo="Historial de abonos · {{ $venta->folio }}"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i>Historial de abonos</button></li>
        @endif
        @can('ventas.pdf')
            @if(!$venta->eliminada_at && $venta->saldo_centavos === 0)
            <li><a class="dropdown-item" href="{{ route('agencia.ventas.comprobanteCompra', $venta) }}"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>Descargar comprobante de pago PDF</a></li>
            @endif
            @if($venta->tiene_historial_abonos)
            <li><a class="dropdown-item" href="{{ route('agencia.ventas.comprobante', $venta) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i>Descargar historial de abonos PDF</a></li>
            @endif
        @endcan
        @if(!$venta->eliminada_at)
        @can('ventas.nombre')
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-operation-module="ventas" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'nombre']) }}" data-titulo="Cambiar nombre · {{ $venta->folio }}"><i class="fas fa-pen" aria-hidden="true"></i>Cambiar nombre</button></li>
        @endcan
        @can('ventas.eliminar')
        <li><hr class="dropdown-divider"></li>
        <li><form method="POST" action="{{ route('agencia.ventas.destroy', $venta) }}" data-confirm="¿Eliminar esta venta?" data-confirm-text="Sus pagos dejarán de sumar en finanzas. Podrás consultar el historial con el filtro Eliminadas.">@csrf @method('DELETE')<button class="dropdown-item text-danger"><i class="fas fa-trash" aria-hidden="true"></i>Eliminar venta</button></form></li>
        @endcan
        @endif
    </ul>
</div>
    @if(!$venta->eliminada_at && !$venta->cancelada_at && auth()->user()->hasPermission('ventas.whatsapp') && auth()->user()->hasPermission('ventas.pdf'))
    <div class="dropdown apartado-mas">
        <button type="button" class="apartado-icono accion-abono" data-bs-toggle="dropdown" aria-expanded="false" title="Enviar por WhatsApp" aria-label="Enviar por WhatsApp {{ $venta->folio }}"><i class="fab fa-whatsapp" aria-hidden="true"></i></button>
        <ul class="dropdown-menu dropdown-menu-end apartado-menu">
            @if($venta->saldo_centavos === 0)
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#whatsappApartado" data-tipo="comprobante" data-preparar="{{ route('agencia.ventas.whatsappPreparar', $venta) }}" data-enviar="{{ route('agencia.ventas.whatsappEnviar', $venta) }}"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i><span>Enviar comprobante de pago PDF</span></button></li>
            @endif

            @if(auth()->user()->hasPermission('itinerarios.ver') && auth()->user()->hasPermission('itinerarios.pdf') && $venta->detalles->contains(fn ($detalle) => $detalle->tiene_itinerario))
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#whatsappApartado" data-tipo="itinerario" data-preparar="{{ route('agencia.ventas.whatsappPreparar', $venta) }}" data-enviar="{{ route('agencia.ventas.whatsappEnviar', $venta) }}"><i class="fas fa-route" aria-hidden="true"></i><span>Enviar itinerario PDF</span></button></li>
            @endif
            @if($venta->tiene_historial_abonos)
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#whatsappApartado" data-tipo="abonos" data-preparar="{{ route('agencia.ventas.whatsappPreparar', $venta) }}" data-enviar="{{ route('agencia.ventas.whatsappEnviar', $venta) }}"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i><span>Enviar historial de abonos PDF</span></button></li>
            @endif
        </ul>
    </div>
    @endif
</div>
