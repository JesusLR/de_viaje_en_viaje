@extends('layouts.app')
@section('titulo', 'Cotizaciones web')
@section('contenido')
<div class="apartados-panel">
    <div class="d-flex align-items-center gap-3 mb-4"><span class="apartados-emblema"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></span><div><h3 class="fw-bold mb-1">Cotizaciones web</h3><p class="text-muted small mb-0">Solicitudes de viajes a la Riviera Maya recibidas desde la página web.</p></div></div>
    @include('agencia.partials.avisos')
    <form method="GET" class="apartados-filtros mb-4" data-grid-filter="cotizaciones"><div class="row g-3 align-items-end">
        <div class="col-md-4"><label for="buscar" class="form-label">Nombre o WhatsApp</label><input id="buscar" name="buscar" value="{{ request('buscar') }}" maxlength="150" class="form-control"></div>
        <div class="col-md-2"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos los estados</option>@foreach(\App\Models\Cotizacion::ESTADOS as $valor => $etiqueta)<option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
        <div class="col-md-2"><label for="desde" class="form-label">Enviada desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="form-control"></div>
        <div class="col-md-2"><label for="hasta" class="form-label">Enviada hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="form-control"></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" aria-label="Buscar"><i class="fas fa-search" aria-hidden="true"></i></button><a class="btn btn-outline-secondary" data-grid-clear href="{{ route('cotizaciones.index') }}" aria-label="Limpiar filtros"><i class="fas fa-rotate-left" aria-hidden="true"></i></a></div>
    </div></form>
    <div class="apartados-grid" data-grid-results="cotizaciones">
        <div class="px-4 py-3 border-bottom fw-semibold">Solicitudes <span class="apartados-contador ms-2">{{ $cotizaciones->total() }}</span></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Solicitud</th><th>Fecha de envío</th><th>Contacto</th><th>Viaje</th><th>Presupuesto / persona</th><th>Estado</th><th>Atiende</th><th>Acciones</th></tr></thead><tbody>
        @forelse($cotizaciones as $cotizacion)
        <tr><td><span class="apartado-folio">{{ $cotizacion->folio }}</span></td><td class="text-nowrap">{{ $cotizacion->created_at->format('d/m/Y') }}</td><td><strong>{{ $cotizacion->nombre }}</strong><small class="d-block text-muted">{{ $cotizacion->whatsapp }}</small></td><td>{{ $cotizacion->fechas }}<small class="d-block text-muted">{{ $cotizacion->personas }} personas · {{ $cotizacion->dias }} días / {{ $cotizacion->noches }} noches</small></td><td>{{ number_format($cotizacion->presupuesto, 2) }} {{ $cotizacion->moneda }}</td><td><span class="badge {{ ['NUEVA' => 'bg-secondary', 'EN_ATENCION' => 'bg-primary', 'ATENDIDO' => 'bg-success', 'CANCELADO' => 'bg-danger'][$cotizacion->estado] }}">{{ \App\Models\Cotizacion::ESTADOS[$cotizacion->estado] }}</span></td><td>{{ $cotizacion->responsable?->nombre_completo ?? 'Sin asignar' }}</td><td><a class="apartado-icono" href="{{ route('cotizaciones.show', $cotizacion) }}" title="Ver solicitud" aria-label="Ver {{ $cotizacion->folio }}"><i class="fas fa-eye" aria-hidden="true"></i></a></td></tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-5">No hay cotizaciones para estos filtros.</td></tr>
        @endforelse
        </tbody></table></div>
        @include('agencia.partials.paginacion', ['paginador' => $cotizaciones, 'etiqueta' => 'cotizaciones'])
    </div>
</div>
@endsection
@push('styles')<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">@endpush
