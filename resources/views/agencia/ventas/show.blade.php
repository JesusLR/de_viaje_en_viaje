@extends($seccion ? 'layouts.agencia-modal' : 'layouts.app')
@section('titulo', $venta->finalizada_at ? 'Detalle de venta' : 'Detalle de apartado')
@section('contenido')
<div class="{{ $seccion ? '' : 'apartados-panel venta-detalle-page' }}">
@if(!$seccion)
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-receipt" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">{{ $venta->finalizada_at ? 'Detalle de venta' : 'Detalle de apartado' }}</h3><p class="text-muted small mb-0">{{ $venta->folio }} · {{ $venta->cliente->nombre_completo }}</p></div></div>
    <div class="apartado-acciones">
        <a href="{{ route($venta->finalizada_at ? 'agencia.ventas.index' : 'agencia.apartados.index') }}" class="apartado-icono" title="{{ $venta->finalizada_at ? 'Volver a ventas' : 'Volver a apartados' }}" aria-label="{{ $venta->finalizada_at ? 'Volver a ventas' : 'Volver a apartados' }}"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
        @if($venta->finalizada_at && !$venta->cancelada_at && $venta->saldo_centavos === 0)<a data-permission="{{ $venta->finalizada_at ? 'ventas.pdf' : 'apartados.pdf' }}" href="{{ route('agencia.ventas.comprobanteCompra', $venta) }}" class="apartado-icono accion-abono" title="Descargar compra saldada PDF" aria-label="Descargar compra saldada PDF"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></a>@endif
        @if(!$venta->finalizada_at || $venta->pagos->whereNull('anulado_at')->isNotEmpty())<a data-permission="{{ $venta->finalizada_at ? 'ventas.pdf' : 'apartados.pdf' }}" href="{{ route('agencia.ventas.comprobante', $venta) }}" class="apartado-icono" title="Descargar historial de abonos PDF" aria-label="Descargar historial de abonos PDF"><i class="fas fa-file-pdf" aria-hidden="true"></i></a>@endif
    </div>
</div>
@endif
@include('agencia.partials.avisos')
@if($seccion === 'itinerario')
<div class="p-4">@include('agencia.ventas.itinerarios')</div>
@endif
@if($seccion && $seccion !== 'detalle')
<div class="apartado-modal-context"><small>{{ $venta->folio }} · {{ $venta->estado_apartado }}</small><p>{{ $venta->cNombre ?: $venta->cliente->nombre_completo }}</p></div>
<div class="apartado-modal-totals">
    <div><span>Total</span><strong>{{ number_format($venta->dTotal, 2) }} <small>{{ $venta->cMoneda }}</small></strong></div>
    <div><span>Abonado</span><strong class="text-success">{{ number_format((float) $venta->dTotal - $venta->saldo_centavos / 100, 2) }} <small>{{ $venta->cMoneda }}</small></strong></div>
    <div><span>Por abonar</span><strong>{{ number_format($venta->saldo_centavos / 100, 2) }} <small>{{ $venta->cMoneda }}</small></strong></div>
</div>
@endif
@if(!$seccion || in_array($seccion, ['detalle', 'historial']))
<div class="card card-body mb-4" id="comprobante">
    @if($seccion !== 'historial')
    <h3>{{ config('app.name') }} — {{ $venta->folio }}</h3>
    @if($venta->cNombre)<h5>{{ $venta->cNombre }}</h5>@endif
    <p class="text-muted">Comprobante de {{ $venta->finalizada_at ? 'venta' : 'apartado' }} · {{ $venta->created_at->format('d/m/Y H:i') }} · {{ $venta->estado_apartado }}</p>
    <div class="row"><div class="col-md-6"><strong>Cliente</strong><p>{{ $venta->cliente->nombre_completo }}<br>{{ $venta->cliente->cTelefono }}<br>{{ $venta->cliente->cEmail }}</p></div><div class="col-md-6"><strong>Viaje</strong><p>Las fechas de inicio y fin se indican en cada paquete.<br>Vendedor: {{ $venta->usuario?->nombre_completo ?? '—' }}@if($venta->finalizada_at)<br>Finalizada: {{ $venta->finalizada_at->format('d/m/Y H:i') }}@endif</p></div></div>
    @include('agencia.ventas.tabla-detalles')
    <div class="row g-3 mb-3"><div class="col-md-4">Total: <strong>{{ number_format($venta->dTotal, 2) }} {{ $venta->cMoneda }}</strong></div><div class="col-md-4">Abonado: <strong>{{ number_format((float) $venta->dTotal - $venta->saldo_centavos / 100, 2) }} {{ $venta->cMoneda }}</strong></div><div class="col-md-4">Saldo: <strong>{{ number_format($venta->saldo_centavos / 100, 2) }} {{ $venta->cMoneda }}</strong></div></div>
    @if($venta->cancelada_at)<div class="alert alert-secondary">Apartado cancelado. Los abonos recibidos se conservan en el historial y en finanzas.</div>@endif
    @if($venta->cObservaciones)<p class="text-break">Observaciones: {{ $venta->cObservaciones }}</p>@endif
    @endif
    @if(!$seccion || $seccion === 'historial')
    <h5 class="mt-3">Historial de abonos</h5>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Fecha</th><th>Importe</th><th>Método / Referencia</th><th>Registró</th><th class="no-print">Acciones</th></tr></thead><tbody>
    @forelse($venta->pagos as $pago)
    <tr class="{{ $pago->anulado_at ? 'text-muted' : '' }}"><td>{{ ($pago->dFechaPago ?? $pago->created_at)->format('d/m/Y') }}</td><td>{{ number_format($pago->dImporte, 2) }} {{ $venta->cMoneda }}@if($pago->anulado_at)<br><span class="badge bg-secondary">Anulado</span>@endif</td><td>{{ $pago->cMetodo }}<br><small>{{ $pago->cReferencia ?: '—' }}</small></td><td>{{ $pago->usuario?->nombre_completo ?? '—' }}</td><td class="no-print">
    @if(!$pago->anulado_at && !$venta->finalizada_at)
        @if(!$venta->cancelada_at)<form data-permission="apartados.fecha" method="POST" action="{{ route('agencia.ventas.fechaPago', [$venta, $pago]) }}" class="d-flex gap-1 mb-2">@csrf<label class="visually-hidden" for="fecha-{{ $pago->iID }}">Fecha del abono</label><input id="fecha-{{ $pago->iID }}" type="date" name="dFechaPago" value="{{ ($pago->dFechaPago ?? $pago->created_at)->toDateString() }}" max="{{ today()->toDateString() }}" required class="form-control form-control-sm"><button class="btn btn-outline-primary btn-sm"><i class="fas fa-calendar-check me-2" aria-hidden="true"></i>Guardar fecha</button></form>@endif
        <form data-permission="apartados.anular" method="POST" action="{{ route('agencia.ventas.anularPago', [$venta, $pago]) }}" data-confirm="¿Anular este abono? Se descontará de finanzas y del saldo abonado.">@csrf<button class="btn btn-outline-danger btn-sm"><i class="fas fa-ban me-2" aria-hidden="true"></i>Anular abono</button></form>
    @endif
    </td></tr>
    @empty<tr><td colspan="5">Sin abonos registrados.</td></tr>@endforelse
    </tbody></table></div>
    @endif
</div>
@endif
@if(!$venta->cancelada_at && !$venta->finalizada_at)
    @if($venta->saldo_centavos > 0 && (!$seccion || $seccion === 'abono'))
    <form data-permission="apartados.abonos" class="card card-body mb-3" action="{{ route('agencia.ventas.pago', $venta) }}" method="POST">@csrf<h5>Registrar abono</h5><div class="row g-3">
        <div class="col-md-3"><label for="importe" class="form-label">Importe ({{ $venta->cMoneda }})</label><input id="importe" name="dImporte" type="number" min="0.01" step="0.01" max="{{ $venta->saldo_centavos / 100 }}" value="{{ old('dImporte') }}" class="form-control" required></div>
        <div class="col-md-3"><label for="fechaPago" class="form-label">Fecha del pago</label><input id="fechaPago" name="dFechaPago" type="date" max="{{ today()->toDateString() }}" value="{{ old('dFechaPago', today()->toDateString()) }}" required class="form-control"></div>
        <div class="col-md-3"><label for="metodo" class="form-label">Método</label><select id="metodo" name="cMetodo" class="form-select">@foreach(['Efectivo', 'Transferencia', 'Tarjeta'] as $metodo)<option @selected(old('cMetodo') === $metodo)>{{ $metodo }}</option>@endforeach</select></div>
        <div class="col-md-3"><label for="referencia" class="form-label">Referencia</label><input id="referencia" name="cReferencia" maxlength="150" value="{{ old('cReferencia') }}" class="form-control"></div>
    </div><div class="mt-3"><button class="btn btn-success"><i class="fas fa-money-bill-wave me-2" aria-hidden="true"></i>Guardar abono</button></div></form>
    @elseif($venta->saldo_centavos === 0 && !$seccion)
    <div class="alert alert-success no-print d-flex justify-content-between align-items-center"><span>El apartado está liquidado. Puedes finalizarlo para que aparezca en Ventas.</span><form data-permission="apartados.finalizar" method="POST" action="{{ route('agencia.ventas.finalizar', $venta) }}" data-confirm="¿Finalizar este apartado liquidado?">@csrf<button class="btn btn-success"><i class="fas fa-check me-2" aria-hidden="true"></i>Finalizar apartado</button></form></div>
    @endif
    @if(!$seccion || $seccion === 'paquetes')
    <form data-permission="apartados.paquetes" method="POST" action="{{ route('agencia.ventas.agregarPaquete', $venta) }}" class="card card-body mb-3">@csrf<h5>Agregar paquete al apartado</h5><div class="row g-3"><div class="col-md-7"><label for="paquete" class="form-label">Paquete vigente ({{ $venta->cMoneda }})</label><select id="paquete" name="iIDServicio" required class="form-select">@include('agencia.ventas.opciones-paquetes')</select></div><div class="col-md-2"><label for="cantidad" class="form-label">Adultos</label><input id="cantidad" name="iCantidad" type="number" min="1" max="1000" value="1" required class="form-control"></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary"><i class="fas fa-box-open me-2" aria-hidden="true"></i>Agregar paquete</button></div></div><div id="menoresApartadoContainer" class="d-none mt-3"><label for="cantidadMenores" class="form-label">Menores</label><input id="cantidadMenores" name="iCantidadMenores" type="number" min="0" max="1000" value="0" class="form-control" disabled><small id="tarifaMenorApartado" class="text-muted"></small></div><p id="resumenPaquete" class="text-muted mt-3 mb-0" aria-live="polite">Selecciona un paquete para ver sus fechas y promoción.</p></form>
    @endif
    @if(!$seccion)
    <form data-permission="apartados.cancelar" method="POST" action="{{ route('agencia.ventas.cancelar', $venta) }}" data-confirm="¿Cancelar este apartado? Los abonos recibidos se conservarán en finanzas." class="mb-3">@csrf<button class="btn btn-outline-danger"><i class="fas fa-ban me-2" aria-hidden="true"></i>Cancelar apartado</button></form>
    @endif
@endif
@if(($venta->cancelada_at || $venta->finalizada_at || $venta->saldo_centavos === 0) && $seccion === 'abono')
<div class="alert alert-info">Este apartado no admite nuevos abonos.</div>
@endif
@if(($venta->cancelada_at || $venta->finalizada_at) && $seccion === 'paquetes')
<div class="alert alert-info">Este apartado no admite agregar paquetes.</div>
@endif
@if(!$seccion || $seccion === 'nombre')
<form data-permission="{{ $venta->finalizada_at ? 'ventas.nombre' : 'apartados.nombre' }}" method="POST" action="{{ route('agencia.ventas.nombre', $venta) }}" class="card card-body">@csrf<label for="nombre" class="form-label">Nombre de la operación</label><div class="d-flex gap-2"><input id="nombre" name="cNombre" maxlength="150" value="{{ old('cNombre', $venta->cNombre) }}" class="form-control"><button class="btn btn-outline-primary"><i class="fas fa-floppy-disk me-2" aria-hidden="true"></i>Guardar nombre</button></div></form>
@endif
</div>
@endsection
@push('styles')
@if(!$seccion)
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/apartados-modals.css') }}" rel="stylesheet">
<link href="{{ asset('css/venta-detalle.css') }}" rel="stylesheet">
@endif
<style>@media print { #sidebar, .topbar, .footer-custom, button, form, .no-print, .alert, a.btn { display:none!important; } .main-container {padding:0;} #comprobante {border:0;} #content-wrapper {width:100%;} }</style>
@endpush
@push('scripts')
<script>
$('#paquete, #cantidad, #cantidadMenores').on('change input', function () {
    const opcion = document.getElementById('paquete').selectedOptions[0];
    const permiteMenores = opcion.dataset.menoresActivo === '1';
    $('#menoresApartadoContainer').toggleClass('d-none', !permiteMenores);
    $('#cantidadMenores').prop('disabled', !permiteMenores);
    if (!permiteMenores) $('#cantidadMenores').val(0);
    const precioMenor = Math.round(Number(opcion.dataset.precioMenor || 0) * 100);
    const menores = permiteMenores ? Number($('#cantidadMenores').val()) : 0;
    $('#tarifaMenorApartado').text(precioMenor === 0 ? 'Gratis para menores' : (precioMenor / 100).toFixed(2) + ' ' + opcion.dataset.moneda + ' por menor');
    if (!opcion.value) { $('#resumenPaquete').text('Selecciona un paquete para ver sus fechas y promoción.'); return; }
    const datos = opcion.dataset;
    const descuento = Math.max(0, Math.round(Number(datos.regular) * 100) - Math.round(Number(datos.precio) * 100));
    const cantidad = Number(document.getElementById('cantidad').value);
    $('#resumenPaquete').text('Inicio: ' + datos.inicio + ' · Fin: ' + datos.fin + ' · ' + (datos.promocion || 'Sin promoción vigente') + ' · Precio regular: ' + Number(datos.regular).toFixed(2) + ' ' + datos.moneda + ' · Descuento por unidad: ' + (descuento / 100).toFixed(2) + ' ' + datos.moneda + ' · Descuento total: ' + (descuento * cantidad / 100).toFixed(2) + ' ' + datos.moneda + ' · Subtotal: ' + ((Math.round(Number(datos.precio) * 100) * cantidad + precioMenor * menores) / 100).toFixed(2) + ' ' + datos.moneda);
});
</script>
@endpush
