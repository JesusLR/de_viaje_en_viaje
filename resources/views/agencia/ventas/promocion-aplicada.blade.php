@if($venta->dPromocionVenta > 0)
<div class="promocion-aplicada" style="margin:14px 0;padding:12px;background:#edf8f2;color:#24885b"><strong>Promocion aplicada</strong>: {{ $venta->cTipoPromocionVenta === 'PORCENTAJE' ? number_format($venta->dValorPromocionVenta, 2).'%' : number_format($venta->dValorPromocionVenta, 2).' '.$venta->cMoneda }} · Descuento: {{ number_format($venta->dPromocionVenta, 2) }} {{ $venta->cMoneda }}<br>Subtotal antes de esta promoción: {{ number_format((float)$venta->dTotal + (float)$venta->dPromocionVenta, 2) }} {{ $venta->cMoneda }}</div>
@endif
