@extends('layouts.app')
@section('titulo', $itinerario->exists ? 'Editar itinerario' : 'Nuevo itinerario')
@section('contenido')
<div class="apartados-panel servicios-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-route" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">{{ $itinerario->exists ? $itinerario->nombre : 'Nuevo itinerario' }}</h3><p class="text-muted small mb-0">Un recorrido reutilizable para tus servicios y paquetes.</p></div></div>
        <a class="apartado-icono" href="{{ route('itinerarios.index') }}" aria-label="Volver a itinerarios" title="Volver a itinerarios"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
    </div>
    @include('agencia.partials.avisos')
    @if(auth()->user()->hasPermission('itinerarios.editar'))
    <form method="GET" class="apartados-filtros mb-4">
        <label for="copiar" class="form-label">Copiar el recorrido de otro itinerario</label>
        <div class="d-flex flex-wrap gap-2"><select class="form-select w-auto flex-grow-1" id="copiar" name="copiar" required><option value="">Selecciona un itinerario</option>@foreach($opciones as $opcion)<option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>@endforeach</select><button class="btn btn-outline-primary">Cargar copia</button></div>
        <div class="form-text">Carga una copia para revisarla. Solo reemplaza este itinerario cuando pulses Guardar.</div>
    </form>
    @endif
    @if($servicios->isNotEmpty())
    <div class="apartados-filtros mb-4"><strong>Asignado a {{ $servicios->count() }} servicios</strong><p class="small text-muted mt-2">Los cambios del recorrido se reflejarán en estos servicios. Las ventas y apartados anteriores conservan su copia.</p><ul class="mb-0">@foreach($servicios as $servicio)<li>{{ $servicio->cCodigo }} · {{ $servicio->cNombre }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ $itinerario->exists ? route('itinerarios.update', $itinerario) : route('itinerarios.store') }}" id="formItinerario" data-editable="{{ auth()->user()->hasPermission('itinerarios.editar') ? '1' : '0' }}">
        @csrf @if($itinerario->exists) @method('PUT') @endif
        <input type="hidden" name="dias_json" id="diasJson" value="{{ old('dias_json', json_encode($dias, JSON_UNESCAPED_UNICODE)) }}">
        <div class="apartados-filtros mb-4">
            <label for="nombreItinerario" class="form-label fw-semibold">Nombre del itinerario</label><input class="form-control" id="nombreItinerario" name="nombre" value="{{ old('nombre', $itinerario->nombre) }}" maxlength="255" required @disabled(!auth()->user()->hasPermission('itinerarios.editar')) placeholder="Ej. Cancún familiar · 4 días">
            <p class="text-muted small mt-2 mb-0">Organiza el recorrido por días. Las horas son opcionales y las notas internas solo se muestran a la agencia.</p>
        </div>
        <div id="itinerarioDias"></div>
        <div id="itinerarioVacio" class="apartados-filtros text-center text-muted mb-3">Agrega los días de este itinerario.</div>
        @if(auth()->user()->hasPermission('itinerarios.editar'))
        <div class="d-flex flex-wrap justify-content-between gap-3 mt-4"><button type="button" id="agregarDia" class="btn btn-outline-primary"><i class="fas fa-plus me-2"></i>Agregar día</button><button class="btn btn-primary"><i class="fas fa-save me-2"></i>Guardar itinerario</button></div>
        @endif
    </form>
</div>
<template id="diaTemplate">
    <section class="apartados-grid mb-4 itinerario-dia">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-bottom"><h5 class="fw-bold mb-0 dia-numero"></h5><div class="d-flex gap-2 controles"><button type="button" class="btn btn-sm btn-outline-secondary" data-action="up" aria-label="Subir día">↑</button><button type="button" class="btn btn-sm btn-outline-secondary" data-action="down" aria-label="Bajar día">↓</button><button type="button" class="btn btn-sm btn-outline-danger" data-action="remove">Eliminar día</button></div></div>
        <div class="p-3 p-md-4"><label class="form-label d-block">Título del día<input class="form-control" data-field="titulo" maxlength="255" required></label><label class="form-label d-block">Descripción<textarea class="form-control" data-field="descripcion" maxlength="5000" rows="2"></textarea></label><div class="actividades"></div><button type="button" class="btn btn-sm btn-outline-primary controles" data-action="add">Agregar actividad</button></div>
    </section>
</template>
<template id="actividadTemplate">
    <div class="actividad border rounded-3 bg-light p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3"><strong class="actividad-numero"></strong><div class="d-flex gap-2 controles"><button type="button" class="btn btn-sm btn-outline-secondary" data-action="up" aria-label="Subir actividad">↑</button><button type="button" class="btn btn-sm btn-outline-secondary" data-action="down" aria-label="Bajar actividad">↓</button><button type="button" class="btn btn-sm btn-outline-danger" data-action="remove">Eliminar</button></div></div>
        <div class="row g-3"><div class="col-md-3"><label class="form-label d-block">Hora (opcional)<input type="time" class="form-control" data-field="hora"></label></div><div class="col-md-9"><label class="form-label d-block">Actividad<input class="form-control" data-field="titulo" maxlength="255" required></label></div><div class="col-md-6"><label class="form-label d-block">Lugar<input class="form-control" data-field="lugar" maxlength="255"></label></div><div class="col-md-6"><label class="form-label d-block">Servicios incluidos<textarea class="form-control" data-field="incluidos" rows="2" maxlength="5000"></textarea></label></div><div class="col-12"><label class="form-label d-block">Descripción<textarea class="form-control" data-field="descripcion" rows="2" maxlength="5000"></textarea></label></div><div class="col-12"><label class="form-label d-block text-muted">Notas internas de la agencia<textarea class="form-control" data-field="notas_internas" rows="2" maxlength="5000"></textarea></label></div></div>
    </div>
</template>
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet"><link href="{{ asset('css/servicios-module.css') }}" rel="stylesheet">
@endpush
@push('scripts')
<script src="{{ asset('js/servicios/itinerario.js') }}?v={{ filemtime(public_path('js/servicios/itinerario.js')) }}"></script>
@endpush
