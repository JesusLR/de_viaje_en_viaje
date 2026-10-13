@extends('layouts.app')
@section('titulo', 'Vender')
@section('contenido')
<h3 class="fw-bold"><i class="fas fa-cart-plus text-primary me-2"></i>Vender paquetes</h3>
<p class="text-muted">Selecciona los paquetes y registra una venta liquidada o un apartado.</p>
@include('agencia.partials.avisos')
<form method="POST" action="{{ route('agencia.ventas.store') }}" id="formVenta">
    @csrf
    <div class="card card-body mb-4"><div class="row g-3">
        <div class="col-md-6"><label for="cliente" class="form-label">Cliente</label><select id="cliente" name="iIDCliente" class="form-select" required><option value="">Selecciona un cliente</option>@foreach($clientes as $cliente)<option value="{{ $cliente->iID }}" @selected(old('iIDCliente') == $cliente->iID)>{{ $cliente->nombre_completo }}</option>@endforeach</select><a class="small" href="{{ route('catalogos.clientes.index') }}">Administrar clientes</a></div>
        <div class="col-md-6"><label for="nombre" class="form-label">Nombre de la operación (opcional)</label><input id="nombre" name="cNombre" maxlength="150" value="{{ old('cNombre') }}" class="form-control" placeholder="Ej. Viaje familiar a Cancún"></div>
        <div class="col-md-4"><label for="fechaRegistro" class="form-label">Fecha de registro</label><input id="fechaRegistro" type="date" value="{{ today()->toDateString() }}" class="form-control" readonly></div>
        <div class="col-md-8"><label for="observaciones" class="form-label">Observaciones</label><input id="observaciones" name="cObservaciones" maxlength="2000" value="{{ old('cObservaciones') }}" class="form-control"></div>
    </div></div>
    <div class="card card-body mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Paquetes de la operación</h5><button type="button" id="agregarFila" class="btn btn-outline-primary btn-sm">Agregar paquete</button></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th style="min-width:260px">Paquete / Promoción</th><th style="min-width:180px">Fecha de inicio / fin</th><th style="width:140px">Cantidad</th><th>Precio regular</th><th>Descuento</th><th>Subtotal</th><th></th></tr></thead><tbody id="lineas"></tbody></table></div>
        <div class="text-end text-success mb-2">Descuento total: <strong id="descuentoTotal">0.00</strong></div>
        <div class="text-end fs-5">Total estimado: <strong id="total">0.00</strong></div>
        <small class="text-muted">Los precios incluyen las promociones vigentes y se verifican al guardar. Todos los paquetes deben tener la misma moneda.</small>
    </div>
    <div class="card card-body"><div class="row g-3">
        <div class="col-md-4"><label for="accion" class="form-label">Operación</label><select id="accion" name="accion" class="form-select"><option value="vender" @selected(old('accion', 'vender') === 'vender')>Venta liquidada</option><option value="apartar" @selected(old('accion') === 'apartar')>Apartado (pago posterior)</option></select></div>
        <div class="col-md-4 pago-directo"><label for="metodo" class="form-label">Método de pago recibido</label><select id="metodo" name="cMetodo" class="form-select">@foreach(['Efectivo', 'Transferencia', 'Tarjeta'] as $metodo)<option @selected(old('cMetodo') === $metodo)>{{ $metodo }}</option>@endforeach</select></div>
        <div class="col-md-4 pago-directo"><label for="referencia" class="form-label">Referencia</label><input id="referencia" name="cReferencia" maxlength="150" value="{{ old('cReferencia') }}" class="form-control"></div>
    </div><p class="text-muted mt-3 mb-2" id="explicacion"></p><div><button id="guardarVenta" class="btn btn-primary" @disabled($clientes->isEmpty() || $paquetes->isEmpty())>Registrar venta liquidada</button></div></div>
    @if($clientes->isEmpty() || $paquetes->isEmpty())<div class="alert alert-warning mt-3">Necesitas clientes activos y paquetes vigentes para registrar una operación.</div>@endif
</form>
<template id="filaPaquete"><tr><td><select class="form-select paquete" required>@include('agencia.ventas.opciones-paquetes')</select><small class="promocion d-block mt-2"></small></td><td class="fechas text-nowrap">Selecciona un paquete</td><td><input class="form-control cantidad" type="number" min="1" max="1000" value="1" required></td><td class="regular text-nowrap">0.00</td><td class="descuento text-success text-nowrap">0.00</td><td class="subtotal text-nowrap">0.00</td><td><button type="button" class="btn btn-outline-danger btn-sm quitar" aria-label="Quitar paquete"><i class="fas fa-times"></i></button></td></tr></template>
@endsection
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
        const centavos = Math.round(Number(opcion.dataset.precio || 0) * 100) * cantidad;
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
    fila.querySelector('.paquete').value = item.iIDServicio || '';
    fila.querySelector('.cantidad').value = item.iCantidad || 1;
    siguienteFila++;
    lineas.appendChild(fila);
    window.inicializarControles(lineas);
    calcular();
}
document.getElementById('agregarFila').addEventListener('click', () => { if (lineas.children.length < 30) agregar(); });
$(lineas).on('change', '.paquete', calcular);
lineas.addEventListener('input', calcular);
lineas.addEventListener('click', e => { const boton = e.target.closest('.quitar'); if (boton) { $(boton.closest('tr')).find('select').select2('destroy'); boton.closest('tr').remove(); if (!lineas.children.length) agregar(); calcular(); } });
function operacion() {
    const venta = document.getElementById('accion').value === 'vender';
    document.querySelectorAll('.pago-directo').forEach(el => el.classList.toggle('d-none', !venta));
    document.getElementById('metodo').required = venta;
    document.getElementById('guardarVenta').textContent = venta ? 'Registrar venta liquidada' : 'Crear apartado';
    document.getElementById('explicacion').textContent = venta ? 'Confirma que recibiste el importe total antes de registrar la venta.' : 'El apartado quedará abierto. Podrás registrar sus abonos desde Apartados.';
}
$('#accion').on('change', operacion);
const anteriores = @json(old('items', []));
if (Object.keys(anteriores).length) Object.values(anteriores).forEach(agregar); else agregar();
operacion();
document.getElementById('formVenta').addEventListener('submit', function(e) {
    if (document.getElementById('accion').value === 'vender' && !confirm('¿Confirmas que recibiste el pago completo de esta venta?')) { e.preventDefault(); return; }
    document.getElementById('guardarVenta').disabled = true;
});
</script>
@endpush
