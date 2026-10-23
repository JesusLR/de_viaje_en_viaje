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
        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => 'historial']) }}" data-titulo="Historial de abonos · {{ $venta->folio }}"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i>Historial de abonos</button></li>
        @can('ventas.pdf')
            @if(!$venta->eliminada_at && $venta->saldo_centavos === 0)
            <li><a class="dropdown-item" href="{{ route('agencia.ventas.comprobanteCompra', $venta) }}"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>Descargar compra saldada PDF</a></li>
            @endif
            @if($venta->pagos->whereNull('anulado_at')->isNotEmpty())
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
</div></div>
