<div class="table-responsive"><table class="table"><thead><tr><th>Paquete / Promoción</th><th>Inicio / Fin</th><th>Cantidad</th><th>Precio regular</th><th>Descuento por unidad</th><th>Precio final</th><th>Subtotal</th></tr></thead><tbody>
@foreach($venta->detalles as $detalle)
<tr><td>{{ $detalle->cPaquete }}<br><small class="{{ $detalle->cPromocion ? 'text-success' : 'text-muted' }}">{{ $detalle->dPrecioRegular === null ? 'Promoción histórica no registrada' : ($detalle->cPromocion ?: 'Sin promoción') }}@if($detalle->cTipoDescuento === 'PORCENTAJE') · {{ number_format($detalle->dValorDescuento, 2) }}%@elseif($detalle->cTipoDescuento === 'IMPORTE') · {{ number_format($detalle->dValorDescuento, 2) }} {{ $venta->cMoneda }}@endif</small></td>
<td class="text-nowrap">@include('agencia.ventas.fechas-detalle')</td><td>{{ $detalle->iCantidad }}</td><td>{{ $detalle->dPrecioRegular === null ? 'No registrado' : number_format($detalle->dPrecioRegular, 2).' '.$venta->cMoneda }}</td><td class="text-success">{{ $detalle->dDescuentoUnitario === null ? 'No registrado' : number_format($detalle->dDescuentoUnitario, 2).' '.$venta->cMoneda }}</td><td>{{ number_format($detalle->dPrecioUnitario, 2) }} {{ $venta->cMoneda }}</td><td>{{ number_format($detalle->dSubtotal, 2) }} {{ $venta->cMoneda }}</td></tr>
@endforeach
</tbody></table></div>
@if($venta->detalles->every(fn ($detalle) => $detalle->dDescuentoUnitario !== null))
<p class="text-success text-end">Descuento total aplicado: <strong>{{ number_format($venta->detalles->sum(fn ($detalle) => (float) $detalle->dDescuentoUnitario * $detalle->iCantidad), 2) }} {{ $venta->cMoneda }}</strong></p>
@endif
