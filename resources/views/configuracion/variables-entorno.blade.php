@extends('layouts.app')
@section('titulo', 'Variables de entorno')
@section('contenido')
<div class="apartados-panel">
    <div class="d-flex align-items-center gap-3 mb-4"><span class="apartados-emblema"><i class="fas fa-sliders" aria-hidden="true"></i></span><div><h3 class="fw-bold mb-1">Variables de entorno</h3><p class="text-muted small mb-0">Configura los parámetros operativos del sistema.</p></div></div>
    @include('agencia.partials.avisos')
    <form method="POST" action="{{ route('configuracion.variables.update') }}" class="apartados-filtros" style="max-width:760px">
        @csrf @method('PUT')
        <h5 class="fw-bold mb-3">Recordatorios de abonos</h5>
        <label for="diasSinAbonos" class="form-label">Días sin abonos para generar una notificación</label>
        <input id="diasSinAbonos" name="dias_sin_abonos" type="number" min="1" max="3650" step="1" required value="{{ old('dias_sin_abonos', $dias) }}" class="form-control" style="max-width:220px" @disabled(!auth()->user()->hasPermission('variables_entorno.editar')) aria-describedby="ayudaDias">
        @error('dias_sin_abonos')<p class="text-danger small mt-2">{{ $message }}</p>@enderror
        <p id="ayudaDias" class="small text-muted mt-3">Al cumplir este número de días desde el último abono válido, el apartado aparecerá en la campana de los usuarios autorizados. Solo aplica a apartados abiertos con saldo pendiente y al menos un abono. Los abonos anulados no cuentan.</p>
        <p class="small text-muted">Cada atraso genera un solo aviso por usuario. Un nuevo abono inicia un nuevo periodo de seguimiento. Los avisos anteriores se conservan como historial leído cuando dejan de aplicar.</p>
        @if($dias === null)<div class="alert alert-info small">Aún no se ha configurado esta variable. Los recordatorios se activarán al guardar un número de días.</div>@endif
        @can('variables_entorno.editar')<button class="apartado-icono nuevo-apartado mt-2"><i class="fas fa-save" aria-hidden="true"></i>Guardar cambios</button>@endcan
    </form>
</div>
@endsection
@push('styles')<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">@endpush
