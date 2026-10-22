<div class="apartado-acciones"><div class="dropdown apartado-mas">
    <button type="button" class="apartado-icono" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones" aria-label="Más opciones de {{ $venta->folio }}"><i class="fas fa-ellipsis-vertical" aria-hidden="true"></i></button>
    <ul class="dropdown-menu dropdown-menu-end apartado-menu">
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'detalle']) }}" data-titulo="Detalle · {{ $venta->folio }}"><i class="fas fa-receipt" aria-hidden="true"></i>Ver detalle</button></li>
        @if(auth()->user()->hasPermission('itinerarios.ver') && $venta->detalles->contains(fn ($detalle) => !empty($detalle->itinerario) || $detalle->servicio?->iIDItinerario))
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'itinerario']) }}" data-titulo="Itinerario · {{ $venta->folio }}"><i class="fas fa-route" aria-hidden="true"></i>Ver itinerario</button></li>
        @if(auth()->user()->hasPermission('itinerarios.pdf') && auth()->user()->hasPermission('ventas.pdf'))
        <li><a class="dropdown-item" href="{{ route('agencia.ventas.itinerarioPdf', $venta) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i>Descargar itinerario PDF</a></li>
        @endif
        @endif
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'historial']) }}" data-titulo="Historial de abonos · {{ $venta->folio }}"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i>Historial de abonos</button></li>
        @can('ventas.pdf')
            @if($venta->saldo_centavos === 0)
            <li><a class="dropdown-item" href="{{ route('agencia.ventas.comprobanteCompra', $venta) }}"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>Descargar compra saldada PDF</a></li>
            @endif
            @if($venta->pagos->whereNull('anulado_at')->isNotEmpty())
            <li><a class="dropdown-item" href="{{ route('agencia.ventas.comprobante', $venta) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i>Descargar historial de abonos PDF</a></li>
            @endif
        @endcan
        @can('ventas.nombre')
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-operation-module="ventas" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'nombre']) }}" data-titulo="Cambiar nombre · {{ $venta->folio }}"><i class="fas fa-pen" aria-hidden="true"></i>Cambiar nombre</button></li>
        @endcan
    </ul>
</div></div>
