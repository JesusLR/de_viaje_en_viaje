<div class="apartado-acciones">
    <div class="dropdown apartado-mas">
        <button type="button" class="apartado-icono" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones" aria-label="Más opciones de {{ $venta->folio }}"><i class="fas fa-ellipsis-vertical" aria-hidden="true"></i></button>
        <ul class="dropdown-menu dropdown-menu-end apartado-menu">
    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'detalle']) }}" data-titulo="Detalle · {{ $venta->folio }}" title="Ver detalle" aria-label="Ver detalle de {{ $venta->folio }}"><i class="fas fa-receipt" aria-hidden="true"></i><span>Ver detalle</span></button></li>
    @if(!$venta->cancelada_at && !$venta->finalizada_at)
        @if($venta->saldo_centavos > 0)
            @can('apartados.abonos')
            <li><button type="button" class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'abono']) }}" data-titulo="Registrar abono · {{ $venta->folio }}" title="Registrar abono" aria-label="Registrar abono de {{ $venta->folio }}"><i class="fas fa-money-bill-wave" aria-hidden="true"></i><span>Abonar</span></button></li>
            @endcan
        @else
            @can('apartados.finalizar')
            <li><form method="POST" action="{{ route('agencia.ventas.finalizar', $venta) }}" data-confirm="¿Finalizar este apartado liquidado?">@csrf<button class="dropdown-item text-success" title="Finalizar apartado" aria-label="Finalizar {{ $venta->folio }}"><i class="fas fa-check" aria-hidden="true"></i><span>Finalizar</span></button></form></li>
            @endcan
        @endif
    @endif
    @if(auth()->user()->hasPermission('itinerarios.ver') && $venta->detalles->contains(fn ($detalle) => !empty($detalle->itinerario) || $detalle->servicio?->iIDItinerario))
    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'itinerario']) }}" data-titulo="Itinerario · {{ $venta->folio }}" title="Ver itinerario" aria-label="Ver itinerario de {{ $venta->folio }}"><i class="fas fa-route" aria-hidden="true"></i><span>Ver itinerario</span></button></li>
    @endif

            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'historial']) }}" data-titulo="Historial de abonos · {{ $venta->folio }}"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i>Historial de abonos</button></li>
            @can('apartados.pdf')
            <li><a class="dropdown-item" href="{{ route('agencia.ventas.comprobante', $venta) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i>Descargar comprobante PDF</a></li>
            @endcan
            @if(!$venta->cancelada_at && !$venta->finalizada_at)
                @can('apartados.paquetes')
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'paquetes']) }}" data-titulo="Agregar paquetes · {{ $venta->folio }}"><i class="fas fa-box-open" aria-hidden="true"></i>Agregar paquetes</button></li>
                @endcan
                @can('apartados.nombre')
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-operation-module="apartados" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'nombre']) }}" data-titulo="Cambiar nombre · {{ $venta->folio }}"><i class="fas fa-pen" aria-hidden="true"></i>Cambiar nombre</button></li>
                @endcan
                @can('apartados.cancelar')
                <li><hr class="dropdown-divider"></li>
                <li><form method="POST" action="{{ route('agencia.ventas.cancelar', $venta) }}" data-confirm="¿Cancelar este apartado?" data-confirm-text="Los abonos recibidos se conservarán en finanzas.">@csrf<button class="dropdown-item text-danger"><i class="fas fa-ban" aria-hidden="true"></i>Cancelar apartado</button></form></li>
                @endcan
            @endif
        </ul>
    </div>
</div>
