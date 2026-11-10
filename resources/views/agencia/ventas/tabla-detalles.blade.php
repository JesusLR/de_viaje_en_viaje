<div class="table-responsive"><table class="table"><thead><tr><th>Paquete / Promoción</th><th>Inicio / Fin</th><th>Cantidad</th><th>Precio regular</th><th>Descuento por unidad</th><th>Precio final</th><th>Subtotal</th></tr></thead><tbody>
@foreach($venta->detalles as $detalle)
<tr><td>{{ $detalle->cPaquete }}<br><small class="{{ $detalle->cPromocion ? 'text-success' : 'text-muted' }}">{{ $detalle->dPrecioRegular === null ? 'Promoción histórica no registrada' : ($detalle->cPromocion ?: 'Sin promoción') }}@if($detalle->cTipoDescuento === 'PORCENTAJE') · {{ number_format($detalle->dValorDescuento, 2) }}%@elseif($detalle->cTipoDescuento === 'IMPORTE') · {{ number_format($detalle->dValorDescuento, 2) }} {{ $venta->cMoneda }}@endif</small></td>
<td class="text-nowrap">@include('agencia.ventas.fechas-detalle')</td><td>{{ $detalle->iCantidad }} adultos @if($detalle->iCantidadMenores > 0)<br>{{ $detalle->iCantidadMenores }} menores @endif</td><td>{{ $detalle->dPrecioRegular === null ? 'No registrado' : number_format($detalle->dPrecioRegular, 2).' '.$venta->cMoneda }}</td><td class="text-success">{{ $detalle->dDescuentoUnitario === null ? 'No registrado' : number_format($detalle->dDescuentoUnitario, 2).' '.$venta->cMoneda }}</td><td>{{ number_format($detalle->dPrecioUnitario, 2) }} {{ $venta->cMoneda }} / adulto @if($detalle->iCantidadMenores > 0)<br><small>Menor: {{ $detalle->dPrecioMenor > 0 ? number_format($detalle->dPrecioMenor, 2).' '.$venta->cMoneda : 'Gratis' }}</small>@endif</td><td>{{ number_format($detalle->dSubtotal, 2) }} {{ $venta->cMoneda }}</td></tr>
@endforeach
</tbody>
@if($venta->dPromocionVenta > 0)
<tfoot>
<tr class="text-success"><td colspan="6"><strong>Promocion aplicada</strong> · {{ $venta->cTipoPromocionVenta === 'PORCENTAJE' ? number_format($venta->dValorPromocionVenta, 2).'%' : number_format($venta->dValorPromocionVenta, 2).' '.$venta->cMoneda }}</td><td class="text-nowrap">−{{ number_format($venta->dPromocionVenta, 2) }} {{ $venta->cMoneda }}</td></tr>
<tr><td colspan="6"><strong>Total con promoción</strong></td><td class="text-nowrap"><strong>{{ number_format($venta->dTotal, 2) }} {{ $venta->cMoneda }}</strong></td></tr>
</tfoot>
@endif
</table></div>
@include('agencia.ventas.itinerarios')
@if($venta->detalles->every(fn ($detalle) => $detalle->dDescuentoUnitario !== null))
<p class="text-success text-end">Descuento total aplicado: <strong>{{ number_format($venta->detalles->sum(fn ($detalle) => (float) $detalle->dDescuentoUnitario * $detalle->iCantidad) + (float)$venta->dPromocionVenta, 2) }} {{ $venta->cMoneda }}</strong></p>
@endif
