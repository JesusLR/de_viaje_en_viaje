@extends('layouts.app')
@section('titulo', 'Cotización web '.$cotizacion->folio)
@section('contenido')
<div class="apartados-panel">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="fw-bold">{{ $cotizacion->folio }} · {{ $cotizacion->nombre }}</h3><p class="text-muted mb-0">Riviera Maya · {{ \App\Models\Cotizacion::ESTADOS[$cotizacion->estado] }} · Atiende: {{ $cotizacion->responsable?->nombre_completo ?? 'Sin asignar' }}</p></div><a class="btn btn-outline-secondary" href="{{ route('cotizaciones.index') }}">Volver</a></div>
    @include('agencia.partials.avisos')
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-4"><div class="col-lg-7"><div class="card card-body rounded-4 mb-4"><h4 class="h5 mb-4">Datos de la solicitud</h4><dl class="row mb-0">
        @foreach(['WhatsApp' => $cotizacion->whatsapp, 'Fechas de viaje' => $cotizacion->fechas, 'Estancia' => $cotizacion->dias.' días / '.$cotizacion->noches.' noches', 'Viajeros' => $cotizacion->personas.' personas: '.$cotizacion->adultos.' adultos y '.$cotizacion->menores.' menores', 'Edades de menores' => implode(', ', $cotizacion->edades_menores) ?: 'Sin menores', 'Hotel en específico' => $cotizacion->hotel ?: 'Sin preferencia', 'Tipo de hotel' => $cotizacion->tipo_hotel, 'Ambiente' => $cotizacion->ambiente, 'Presupuesto por persona' => number_format($cotizacion->presupuesto, 2).' '.$cotizacion->moneda] as $campo => $valor)
        <dt class="col-sm-5">{{ $campo }}</dt><dd class="col-sm-7 text-break">{{ $valor }}</dd>
        @endforeach
    </dl></div>
    @if($cotizacion->observacion_final)<div class="card card-body rounded-4 mb-4"><h4 class="h5">Observación final</h4><p class="text-break mb-1" style="white-space: pre-wrap">{{ $cotizacion->observacion_final }}</p><small class="text-muted">Atendida el {{ $cotizacion->atendida_at->format('d/m/Y H:i') }}</small></div>@endif
    <div class="card card-body rounded-4"><h4 class="h5 mb-3">Historial de seguimiento</h4>
        @forelse($cotizacion->observaciones as $nota)
        <div class="border-bottom pb-3 mb-3"><strong>{{ $nota->usuario?->nombre_completo ?? 'Usuario no disponible' }}</strong><small class="d-block text-muted">{{ $nota->created_at->format('d/m/Y H:i') }} · {{ \App\Models\Cotizacion::ESTADOS[$nota->estado] }}</small><p class="mb-0 mt-2 text-break" style="white-space: pre-wrap">{{ $nota->observacion ?: 'Cambio de estado' }}</p></div>
        @empty
        <p class="text-muted mb-0">Aún no hay observaciones.</p>
        @endforelse
    </div></div><div class="col-lg-5">
    @can('cotizaciones.atender')
        @if(!in_array($cotizacion->estado, ['ATENDIDO', 'CANCELADO']))
        <form method="POST" action="{{ route('cotizaciones.seguimiento', $cotizacion) }}" class="card card-body rounded-4">
            @csrf @method('PUT')<h4 class="h5">Actualizar seguimiento</h4>
            <label for="estadoSeguimiento" class="form-label mt-3">Estado</label><select id="estadoSeguimiento" name="estado" class="form-select mb-3" required><option value="EN_ATENCION">{{ $cotizacion->estado === 'NUEVA' ? 'Iniciar atención' : 'En atención' }}</option><option value="CANCELADO" @selected(old('estado') === 'CANCELADO')>Cancelado</option>@if($cotizacion->estado === 'EN_ATENCION')<option value="ATENDIDO" @selected(old('estado') === 'ATENDIDO')>Atendido</option>@endif</select>
            <label for="observacion" class="form-label">Observaciones</label><textarea id="observacion" name="observacion" rows="6" maxlength="5000" class="form-control" aria-describedby="notaAyuda">{{ old('observacion') }}</textarea><p id="notaAyuda" class="form-text">La observación final es obligatoria al marcar la cotización como atendida.</p>
            <button class="btn btn-primary mt-3">Guardar seguimiento</button>
        </form>
        @else
        <div class="alert alert-secondary">Esta cotización está cerrada. Puedes consultar sus datos e historial.</div>
        @endif
    @endcan
    </div></div>
</div>
@endsection
@push('styles')<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">@endpush
