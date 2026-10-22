@extends('layouts.app')
@section('titulo', 'Itinerarios')
@section('contenido')
<div class="apartados-panel servicios-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-route" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">Itinerarios</h3><p class="text-muted small mb-0">Crea recorridos con nombre y asígnalos a uno o varios servicios.</p></div></div>@if(auth()->user()->hasPermission('itinerarios.editar'))<a class="apartado-icono nuevo-apartado" href="{{ route('itinerarios.create') }}"><i class="fas fa-plus" aria-hidden="true"></i><span>Nuevo itinerario</span></a>@endif</div>
    <form method="GET" class="apartados-filtros mb-4">
        <label for="buscarItinerario" class="form-label">Buscar itinerario</label>
        <div class="d-flex gap-2"><div class="apartados-busqueda flex-grow-1"><i class="fas fa-search" aria-hidden="true"></i><input class="form-control" id="buscarItinerario" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre del itinerario" maxlength="150"></div><button class="btn btn-primary">Buscar</button>@if(request()->filled('buscar'))<a href="{{ route('itinerarios.index') }}" class="btn btn-outline-secondary">Limpiar</a>@endif</div>
    </form>
    <div class="apartados-grid">
        <div class="px-4 py-3 border-bottom fw-semibold">Tus itinerarios <span class="apartados-contador ms-2">{{ $itinerarios->total() }}</span></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Itinerario</th><th>Días</th><th>Servicios asignados</th><th class="text-end">Acciones</th></tr></thead><tbody>
        @forelse($itinerarios as $itinerario)
        <tr><td><div class="apartado-nombre">{{ $itinerario->nombre }}</div></td><td>{{ $itinerario->dias_count }}</td><td><span class="apartados-contador">{{ $itinerario->servicios_count }}</span></td><td class="text-end"><a class="apartado-icono" href="{{ route('itinerarios.edit', $itinerario) }}" title="Abrir {{ $itinerario->nombre }}" aria-label="Abrir {{ $itinerario->nombre }}"><i class="fas fa-route" aria-hidden="true"></i></a></td></tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-5">No se encontraron itinerarios. Crea uno para asignarlo a tus servicios.</td></tr>
        @endforelse
        </tbody></table></div>
        <div class="p-3">{{ $itinerarios->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet"><link href="{{ asset('css/servicios-module.css') }}" rel="stylesheet">
@endpush
