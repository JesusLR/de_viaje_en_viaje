@extends('layouts.app')
@section('titulo', 'Cotización '.$cotizacion->folio)
@section('contenido')
<div class="apartados-panel"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h3 class="fw-bold">{{ $cotizacion->folio }}</h3><p class="text-muted mb-0">{{ $cotizacion->nombre }}</p></div><div class="d-flex gap-2"><a class="apartado-icono" href="{{ route('atencion.cotizaciones.index') }}" title="Volver a cotizaciones" aria-label="Volver a cotizaciones"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>@can('cotizaciones_servicios.pdf')<a class="apartado-icono accion-abono" href="{{ route('atencion.cotizaciones.pdf', $cotizacion) }}" title="Descargar PDF" aria-label="Descargar PDF"><i class="fas fa-file-pdf" aria-hidden="true"></i></a>@endcan</div></div>
@include('agencia.partials.avisos')
<div class="card card-body rounded-4"><div class="row mb-4"><div class="col-md-6"><strong>Preparada para</strong><p>{{ $cotizacion->cliente_nombre }}</p></div><div class="col-md-6"><strong>Fecha de cotización</strong><p>{{ $cotizacion->created_at->format('d/m/Y H:i') }}<br>Asesor: {{ $cotizacion->usuario?->nombre_completo }}</p></div></div>
@include('atencion.cotizaciones.partidas')
<div class="text-end border-top pt-3"><strong class="fs-4">Total: {{ number_format($cotizacion->total, 2) }} {{ $cotizacion->moneda }}</strong></div>
@if($cotizacion->observaciones)<h5 class="mt-4">Observaciones</h5><p class="text-break" style="white-space: pre-wrap">{{ $cotizacion->observaciones }}</p>@endif
<p class="small text-muted mt-4 mb-0">Cotización sujeta a disponibilidad y cambios de tarifa al confirmar la reserva. No constituye una venta ni un apartado.</p></div></div>
@endsection
@push('styles')<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">@endpush
