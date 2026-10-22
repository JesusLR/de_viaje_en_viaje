@extends('layouts.app')
@section('titulo', 'Finanzas')
@section('contenido')
<div class="apartados-panel finanzas-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-wallet" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">Finanzas</h3><p class="text-muted mb-0 small">Consulta tus ingresos, abonos y gastos por periodo.</p></div></div>
        <button type="button" class="apartado-icono nuevo-apartado" data-bs-toggle="modal" data-bs-target="#nuevoMovimiento"><i class="fas fa-plus" aria-hidden="true"></i><span>Nuevo movimiento</span></button>
    </div>
    @include('agencia.partials.avisos')
    <form method="GET" class="apartados-filtros mb-4" data-grid-filter="finanzas"><div class="row g-3 align-items-end">
        <div class="col-6 col-lg-3"><label for="desde" class="form-label">Desde</label><input id="desde" name="desde" type="date" value="{{ $desde }}" required class="form-control"></div>
        <div class="col-6 col-lg-3"><label for="hasta" class="form-label">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ $hasta }}" required class="form-control"></div>
        <div class="col-lg-3"><label for="moneda" class="form-label">Moneda</label><select id="moneda" name="moneda" class="form-select">@foreach($monedas as $opcion)<option @selected($opcion === $moneda)>{{ $opcion }}</option>@endforeach</select></div>
        <div class="col-lg-3"><div class="d-flex gap-2"><button class="apartado-icono accion-principal" title="Consultar" aria-label="Consultar finanzas"><i class="fas fa-search" aria-hidden="true"></i></button><a data-permission="finanzas.ver" class="apartado-icono" href="{{ route('agencia.finanzas.index') }}" data-grid-clear title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fas fa-rotate-left" aria-hidden="true"></i></a>@can('finanzas.exportar')<a data-grid-results="finanzas" data-permission="finanzas.exportar" class="apartado-icono accion-abono" href="{{ route('agencia.finanzas.exportar', compact('desde', 'hasta', 'moneda')) }}" title="Exportar CSV para Excel" aria-label="Exportar CSV para Excel"><i class="fas fa-file-excel" aria-hidden="true"></i></a>@endcan</div></div>
    </div></form>
    <div data-grid-results="finanzas"><div class="row g-3 mb-4">
        @foreach([['Ingresos recibidos', $totales->ingresos, 'text-success', 'fa-arrow-trend-up'], ['Egresos registrados', $totales->egresos, 'text-danger', 'fa-arrow-trend-down'], ['Balance', $totales->ingresos - $totales->egresos, $totales->ingresos - $totales->egresos < 0 ? 'text-danger' : 'finanzas-balance', 'fa-scale-balanced']] as [$titulo, $importe, $color, $icono])
        <div class="col-md-4" data-permission="{{ $titulo === 'Ingresos recibidos' ? 'finanzas.ingresos' : ($titulo === 'Egresos registrados' ? 'finanzas.egresos' : 'finanzas.balance') }}"><div class="apartados-filtros h-100"><div class="d-flex justify-content-between align-items-center"><small class="text-muted">{{ $titulo }}</small><i class="fas {{ $icono }} {{ $color }}" aria-hidden="true"></i></div><strong class="finanzas-importe {{ $color }}">{{ number_format($importe, 2) }} <small>{{ $moneda }}</small></strong></div></div>
        @endforeach
    </div>
    <div class="finanzas-origenes d-flex flex-wrap gap-3 mb-4" data-permission="finanzas.ingresos">
        @foreach(['Venta' => 'Ventas', 'Abono' => 'Abonos de apartados', 'Manual' => 'Ingresos manuales'] as $origen => $titulo)
        <span>{{ $titulo }} <strong>{{ number_format($origenes->get($origen, 0), 2) }} {{ $moneda }}</strong></span>
        @endforeach
    </div>
@can('finanzas.ingresos')
    @include('agencia.finanzas.tabla-movimientos', ['registros' => $ingresos, 'tipoTabla' => 'ingresos', 'tituloTabla' => 'Ingresos', 'iconoTabla' => 'fa-arrow-trend-up'])
@endcan
@can('finanzas.egresos')
    @include('agencia.finanzas.tabla-movimientos', ['registros' => $egresos, 'tipoTabla' => 'egresos', 'tituloTabla' => 'Egresos', 'iconoTabla' => 'fa-arrow-trend-down'])
@endcan
</div>
</div>
<div class="modal fade finanzas-modal" id="nuevoMovimiento" tabindex="-1" aria-labelledby="nuevoMovimientoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="nuevoMovimientoTitulo"><i class="fas fa-wallet me-2" aria-hidden="true"></i>Nuevo movimiento</h5><button type="button" class="apartado-icono" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
        <form data-permission="finanzas.crear" method="POST" action="{{ route('agencia.finanzas.store') }}">@csrf<div class="modal-body"><div class="row g-3">
            <div class="col-md-4"><label for="tipo" class="form-label">Tipo</label><select id="tipo" name="cTipo" class="form-select"><option value="EGRESO" @selected(old('cTipo') === 'EGRESO')>Egreso (gasto)</option><option value="INGRESO" @selected(old('cTipo') === 'INGRESO')>Ingreso manual</option></select></div>
            <div class="col-md-8"><label for="concepto" class="form-label">Concepto</label><input id="concepto" name="cConcepto" maxlength="150" required value="{{ old('cConcepto') }}" class="form-control"></div>
            <div class="col-md-4"><label for="importe" class="form-label">Importe</label><input id="importe" name="dImporte" type="number" min="0.01" max="9999999999.99" step="0.01" value="{{ old('dImporte') }}" required class="form-control"></div>
            <div class="col-md-4"><label for="monedaMovimiento" class="form-label">Moneda</label><select id="monedaMovimiento" name="cMoneda" class="form-select">@foreach($monedas as $opcion)<option @selected(old('cMoneda', $moneda) === $opcion)>{{ $opcion }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="fechaMovimiento" class="form-label">Fecha</label><input id="fechaMovimiento" name="dFecha" type="date" max="{{ today()->toDateString() }}" value="{{ old('dFecha', today()->toDateString()) }}" required class="form-control"></div>
            <div class="col-md-4"><label for="metodo" class="form-label">Método</label><select id="metodo" name="cMetodo" class="form-select">@foreach(['Efectivo', 'Transferencia', 'Tarjeta'] as $metodo)<option @selected(old('cMetodo') === $metodo)>{{ $metodo }}</option>@endforeach</select></div>
            <div class="col-md-8"><label for="observaciones" class="form-label">Observaciones</label><textarea id="observaciones" name="cObservaciones" maxlength="2000" class="form-control" rows="2">{{ old('cObservaciones') }}</textarea></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button><button class="btn finanzas-guardar"><i class="fas fa-floppy-disk me-2" aria-hidden="true"></i>Guardar movimiento</button></div></form>
    </div></div>
</div>
@include('agencia.ventas.modal-accion')
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/finanzas-module.css') }}" rel="stylesheet">
@endpush
@push('scripts')
@if($errors->hasAny(['cTipo', 'cConcepto', 'dImporte', 'cMoneda', 'cMetodo', 'dFecha', 'cObservaciones']))
<script>$(function () { new bootstrap.Modal(document.getElementById('nuevoMovimiento')).show(); });</script>
@endif
@endpush
