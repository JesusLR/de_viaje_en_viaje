<div class="table-responsive"><table class="table"><thead><tr><th>Paquete / Promoción</th><th>Inicio / Fin</th><th>Cantidad</th><th>Precio regular</th><th>Descuento por unidad</th><th>Precio final</th><th>Subtotal</th></tr></thead><tbody>
@foreach($venta->detalles as $detalle)
<tr><td>{{ $detalle->cPaquete }}<br><small class="{{ $detalle->cPromocion ? 'text-success' : 'text-muted' }}">{{ $detalle->dPrecioRegular === null ? 'Promoción histórica no registrada' : ($detalle->cPromocion ?: 'Sin promoción') }}@if($detalle->cTipoDescuento === 'PORCENTAJE') · {{ number_format($detalle->dValorDescuento, 2) }}%@elseif($detalle->cTipoDescuento === 'IMPORTE') · {{ number_format($detalle->dValorDescuento, 2) }} {{ $venta->cMoneda }}@endif</small></td>
<td class="text-nowrap">@include('agencia.ventas.fechas-detalle')</td><td>{{ $detalle->iCantidad }} adultos @if($detalle->iCantidadMenores > 0)<br>{{ $detalle->iCantidadMenores }} menores @endif</td><td>{{ $detalle->dPrecioRegular === null ? 'No registrado' : number_format($detalle->dPrecioRegular, 2).' '.$venta->cMoneda }}</td><td class="text-success">{{ $detalle->dDescuentoUnitario === null ? 'No registrado' : number_format($detalle->dDescuentoUnitario, 2).' '.$venta->cMoneda }}</td><td>{{ number_format($detalle->dPrecioUnitario, 2) }} {{ $venta->cMoneda }} / adulto @if($detalle->iCantidadMenores > 0)<br><small>Menor: {{ $detalle->dPrecioMenor > 0 ? number_format($detalle->dPrecioMenor, 2).' '.$venta->cMoneda : 'Gratis' }}</small>@endif</td><td>{{ number_format($detalle->dSubtotal, 2) }} {{ $venta->cMoneda }}</td></tr>
@endforeach
</tbody></table></div>
@if(auth()->user()->hasPermission('itinerarios.ver'))
@foreach($venta->detalles as $detalle)
    @if($detalle->itinerario)
    <section class="mt-4"><h5 class="fw-bold">Itinerario contratado · {{ $detalle->cPaquete }}</h5>
        @include('servicios.itinerario.recorrido', ['dias' => $detalle->itinerario, 'fechaInicio' => $detalle->dFechaInicio, 'interno' => true])
    </section>
    @endif
@endforeach
@endif
@if($venta->detalles->every(fn ($detalle) => $detalle->dDescuentoUnitario !== null))
<p class="text-success text-end">Descuento total aplicado: <strong>{{ number_format($venta->detalles->sum(fn ($detalle) => (float) $detalle->dDescuentoUnitario * $detalle->iCantidad), 2) }} {{ $venta->cMoneda }}</strong></p>
@endif
