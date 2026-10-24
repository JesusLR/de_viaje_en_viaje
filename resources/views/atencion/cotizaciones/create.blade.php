@extends('layouts.app')
@section('titulo', 'Nueva cotización')
@section('contenido')
<div class="apartados-panel vender-panel">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-cart-plus" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">Cotizar servicios</h3><p class="text-muted mb-0 small">Selecciona servicios activos y prepara una propuesta para tu cliente.</p></div></div>
    <a data-permission="cotizaciones_servicios.ver" class="apartado-icono" href="{{ route('atencion.cotizaciones.index') }}" title="Ver cotizaciones" aria-label="Ver cotizaciones"><i class="fas fa-clipboard-list" aria-hidden="true"></i></a>
</div>
@include('agencia.partials.avisos')
<form method="POST" action="{{ route('atencion.cotizaciones.store') }}" id="formVenta">
    @csrf
    <div class="apartados-filtros mb-4"><h5 class="vender-seccion"><span>01</span>Datos de la cotización</h5><div class="row g-3">
        <div class="col-md-4"><label for="tipoCliente" class="form-label">Tipo de cliente</label><select id="tipoCliente" name="tipo_cliente" class="form-select"><option value="registrado" @selected(old('tipo_cliente', $clientes->isEmpty() ? 'generico' : 'registrado') === 'registrado')>Cliente registrado</option><option value="generico" @selected(old('tipo_cliente', $clientes->isEmpty() ? 'generico' : 'registrado') === 'generico')>Nombre genérico</option></select></div>
        <div class="col-md-8" id="clienteRegistrado"><label for="cliente" class="form-label">Cliente registrado</label><select id="cliente" name="iIDCliente" class="form-select"><option value="">Selecciona un cliente</option>@foreach($clientes as $cliente)<option value="{{ $cliente->iID }}" @selected(old('iIDCliente') == $cliente->iID)>{{ $cliente->nombre_completo }}</option>@endforeach</select></div>
        <div class="col-md-8 d-none" id="clienteGenerico"><label for="nombreGenerico" class="form-label">Nombre del cliente o grupo</label><input id="nombreGenerico" name="cliente_nombre" maxlength="255" value="{{ old('cliente_nombre') }}" class="form-control" placeholder="Ej. Familia López o Público general"></div>
        <div class="col-md-6"><label for="nombre" class="form-label">Nombre de la cotización (opcional)</label><input id="nombre" name="cNombre" maxlength="150" value="{{ old('cNombre') }}" class="form-control" placeholder="Ej. Viaje familiar a Cancún"></div>
        <div class="col-md-4"><label for="fechaRegistro" class="form-label">Fecha de registro</label><input id="fechaRegistro" type="date" value="{{ today()->toDateString() }}" class="form-control" readonly></div>
        <div class="col-md-8"><label for="observaciones" class="form-label">Observaciones</label><input id="observaciones" name="cObservaciones" maxlength="2000" value="{{ old('cObservaciones') }}" class="form-control"></div>
    </div></div>
    <div class="apartados-grid mb-4">
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center px-4 py-3 border-bottom"><h5 class="vender-seccion mb-0"><span>02</span>Servicios de la cotización</h5><button type="button" id="agregarFila" class="apartado-icono nuevo-apartado"><i class="fas fa-plus" aria-hidden="true"></i><span>Agregar paquete</span></button></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th style="min-width:260px">Paquete / Promoción</th><th style="min-width:180px">Inicio / Fin</th><th style="min-width:100px">Cantidad</th><th>Precio regular</th><th>Descuento</th><th class="text-end">Subtotal</th><th></th></tr></thead><tbody id="lineas"></tbody></table></div>
        <div class="vender-resumen border-top"><div><small>Descuento total</small><strong id="descuentoTotal" class="text-success">0.00</strong></div><div class="vender-total"><small>Total estimado</small><strong id="total">0.00</strong></div></div>
        <div class="px-4 pb-3"><small class="text-muted">Los precios incluyen las promociones vigentes y se verifican al guardar. Todos los paquetes deben tener la misma moneda.</small></div>
    </div>
    <div class="apartados-filtros vender-confirmacion"><p class="text-muted mb-0 small">Guarda la propuesta para consultarla y descargar su PDF.</p><button id="guardarVenta" class="vender-guardar" @disabled($paquetes->isEmpty())><i class="fas fa-check" aria-hidden="true"></i><span>Guardar cotización</span></button></div>
    @if($paquetes->isEmpty())<div class="alert alert-warning mt-3">No hay servicios activos disponibles para cotizar.</div>@endif
</form>
<template id="filaPaquete"><tr><td><select class="form-select paquete" required>@include('agencia.ventas.opciones-paquetes')</select><small class="promocion d-block mt-2"></small></td><td class="fechas text-nowrap">Selecciona un paquete</td><td><label class="small d-block">Adultos<input class="form-control cantidad" type="number" min="1" max="1000" value="1" required></label><div class="menores-container d-none mt-2"><label class="small d-block">Menores<input class="form-control menores" type="number" min="0" max="1000" value="0" disabled></label><small class="tarifa-menor text-muted"></small></div></td><td class="regular text-nowrap">0.00</td><td class="descuento text-success text-nowrap">0.00</td><td class="subtotal text-nowrap text-end">0.00</td><td><button type="button" class="apartado-icono accion-cancelar quitar" title="Quitar paquete" aria-label="Quitar paquete"><i class="fas fa-xmark" aria-hidden="true"></i></button></td></tr></template>
</div>
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/vender-module.css') }}" rel="stylesheet">
@endpush
@push('scripts')
<script>
const lineas = document.getElementById('lineas');
let siguienteFila = 0;
const formato = n => n.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
function calcular() {
    const totales = {};
    const descuentos = {};
    lineas.querySelectorAll('tr').forEach(fila => {
        const opcion = fila.querySelector('.paquete').selectedOptions[0];
        const cantidad = Number(fila.querySelector('.cantidad').value);
        const moneda = opcion.dataset.moneda;
        const admiteMenores = opcion.dataset.menoresActivo === '1';
        const menoresInput = fila.querySelector('.menores');
        fila.querySelector('.menores-container').classList.toggle('d-none', !admiteMenores);
        menoresInput.disabled = !admiteMenores;
        if (!admiteMenores) menoresInput.value = 0;
        const menores = admiteMenores ? Number(menoresInput.value) : 0;
        const precioMenor = Math.round(Number(opcion.dataset.precioMenor || 0) * 100);
        fila.querySelector('.tarifa-menor').textContent = admiteMenores ? (precioMenor === 0 ? 'Gratis para menores' : formato(precioMenor / 100) + ' ' + moneda + ' por menor') : '';
        const centavos = Math.round(Number(opcion.dataset.precio || 0) * 100) * cantidad + precioMenor * menores;
        const regular = Math.round(Number(opcion.dataset.regular || 0) * 100);
        const descuento = Math.max(0, regular - Math.round(Number(opcion.dataset.precio || 0) * 100));
        const sufijo = moneda ? ' ' + moneda : '';
        fila.querySelector('.regular').textContent = formato(regular / 100) + sufijo + ' / unidad';
        fila.querySelector('.descuento').textContent = formato(descuento * cantidad / 100) + sufijo;
        fila.querySelector('.fechas').textContent = opcion.dataset.inicio ? opcion.dataset.inicio + ' — ' + opcion.dataset.fin : 'Selecciona un paquete';
        const regla = opcion.dataset.tipoDescuento === 'PORCENTAJE' ? opcion.dataset.valorDescuento + '%' : formato(Number(opcion.dataset.valorDescuento || 0)) + sufijo;
        fila.querySelector('.promocion').textContent = opcion.value ? (opcion.dataset.promocion ? opcion.dataset.promocion + ' · ' + regla + ' · Descuento por unidad: ' + formato(descuento / 100) + sufijo : 'Sin promoción vigente') : '';
        fila.querySelector('.subtotal').textContent = formato(centavos / 100) + (moneda ? ' ' + moneda : '');
        if (moneda) totales[moneda] = (totales[moneda] || 0) + centavos;
        if (moneda) descuentos[moneda] = (descuentos[moneda] || 0) + descuento * cantidad;
    });
    document.getElementById('total').textContent = Object.entries(totales).map(([moneda, centavos]) => formato(centavos / 100) + ' ' + moneda).join(' + ') || '0.00';
    document.getElementById('descuentoTotal').textContent = Object.entries(descuentos).map(([moneda, centavos]) => formato(centavos / 100) + ' ' + moneda).join(' + ') || '0.00';
}
function agregar(item = {}) {
    const fila = document.getElementById('filaPaquete').content.cloneNode(true);
    fila.querySelector('.paquete').name = `items[${siguienteFila}][iIDServicio]`;
    fila.querySelector('.cantidad').name = `items[${siguienteFila}][iCantidad]`;
    fila.querySelector('.menores').name = `items[${siguienteFila}][iCantidadMenores]`;
    fila.querySelector('.paquete').value = item.iIDServicio || '';
    fila.querySelector('.cantidad').value = item.iCantidad || 1;
    fila.querySelector('.menores').value = item.iCantidadMenores || 0;
    siguienteFila++;
    lineas.appendChild(fila);
    window.inicializarControles(lineas);
    calcular();
}
document.getElementById('agregarFila').addEventListener('click', () => { if (lineas.children.length < 30) agregar(); });
$(lineas).on('change', '.paquete', calcular);
lineas.addEventListener('input', calcular);
lineas.addEventListener('click', e => { const boton = e.target.closest('.quitar'); if (boton) { $(boton.closest('tr')).find('select').select2('destroy'); boton.closest('tr').remove(); if (!lineas.children.length) agregar(); calcular(); } });
function tipoCliente() {
    const generico = document.getElementById('tipoCliente').value === 'generico';
    document.getElementById('clienteGenerico').classList.toggle('d-none', !generico);
    document.getElementById('clienteRegistrado').classList.toggle('d-none', generico);
    document.getElementById('nombreGenerico').disabled = !generico;
    document.getElementById('nombreGenerico').required = generico;
    document.getElementById('cliente').disabled = generico;
    document.getElementById('cliente').required = !generico;
}
$('#tipoCliente').on('change', tipoCliente);
const anteriores = @json(old('items', []));
if (Object.keys(anteriores).length) Object.values(anteriores).forEach(agregar); else agregar();
tipoCliente();
document.getElementById('formVenta').addEventListener('submit', function(e) {
    if (e.defaultPrevented) return;
    document.getElementById('guardarVenta').disabled = true;
});
</script>
@endpush
