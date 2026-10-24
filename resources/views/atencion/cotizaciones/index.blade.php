@extends('layouts.app')
@section('titulo', 'Cotizaciones')
@section('contenido')
<div class="apartados-panel">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4"><div><h3 class="fw-bold mb-1">Cotizaciones</h3><p class="text-muted small mb-0">Propuestas de servicios para clientes registrados o nombres genéricos.</p></div>@can('cotizaciones_servicios.crear')<a href="{{ route('atencion.cotizaciones.create') }}" class="apartado-icono nuevo-apartado"><i class="fas fa-plus" aria-hidden="true"></i>Nueva cotización</a>@endcan</div>
    @include('agencia.partials.avisos')
    <form method="GET" class="apartados-filtros mb-4" data-grid-filter="cotizacionesServicios"><label for="buscar" class="form-label">Cliente o nombre de cotización</label><div class="d-flex gap-2"><input id="buscar" name="buscar" maxlength="150" value="{{ request('buscar') }}" class="form-control"><button class="btn btn-primary">Buscar</button><a class="btn btn-outline-secondary" data-grid-clear href="{{ route('atencion.cotizaciones.index') }}">Limpiar</a></div></form>
    <div class="apartados-grid" data-grid-results="cotizacionesServicios"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Cotización</th><th>Cliente</th><th>Creada por</th><th>Total</th><th>Acciones</th></tr></thead><tbody>
    @forelse($cotizaciones as $cotizacion)
    <tr><td><span class="apartado-folio">{{ $cotizacion->folio }}</span><div>{{ $cotizacion->nombre }}</div><small class="text-muted">{{ $cotizacion->created_at->format('d/m/Y H:i') }}</small></td><td>{{ $cotizacion->cliente_nombre }}<small class="d-block text-muted">{{ $cotizacion->cliente_id ? 'Cliente registrado' : 'Nombre genérico' }}</small></td><td>{{ $cotizacion->usuario?->nombre_completo }}</td><td>{{ number_format($cotizacion->total, 2) }} {{ $cotizacion->moneda }}</td><td><a class="apartado-icono" href="{{ route('atencion.cotizaciones.show', $cotizacion) }}" title="Ver cotización" aria-label="Ver cotización"><i class="fas fa-eye" aria-hidden="true"></i></a>@can('cotizaciones_servicios.pdf')<a class="apartado-icono" href="{{ route('atencion.cotizaciones.pdf', $cotizacion) }}" title="Descargar PDF" aria-label="Descargar PDF"><i class="fas fa-file-pdf" aria-hidden="true"></i></a>@endcan</td></tr>
    @empty
    <tr><td colspan="5" class="text-center text-muted py-5">No hay cotizaciones para mostrar.</td></tr>
    @endforelse
    </tbody></table></div>@include('agencia.partials.paginacion', ['paginador' => $cotizaciones, 'etiqueta' => 'cotizaciones'])</div>
</div>
@endsection
@push('styles')<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">@endpush
