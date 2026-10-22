@foreach($dias as $dia)
<details class="border rounded-3 p-3 mb-3 bg-white" @if($loop->first) open @endif>
    <summary class="fw-bold">Día {{ $dia['numero'] }} · {{ $dia['titulo'] }}@if(!empty($fechaInicio)) <span class="text-muted small"> · {{ \Carbon\Carbon::parse($fechaInicio)->addDays($dia['numero'] - 1)->format('d/m/Y') }}</span>@endif</summary>
    @if(!empty($dia['descripcion']))<p class="mt-3 text-break">{{ $dia['descripcion'] }}</p>@endif
    @foreach($dia['actividades'] as $actividad)
    <div class="border-start ps-3 mt-3 text-break">
        <strong>@if(!empty($actividad['hora'])){{ $actividad['hora'] }} · @endif{{ $actividad['titulo'] }}</strong>
        @if(!empty($actividad['lugar']))<div class="small text-muted">{{ $actividad['lugar'] }}</div>@endif
        @if(!empty($actividad['descripcion']))<p class="mb-1">{{ $actividad['descripcion'] }}</p>@endif
        @if(!empty($actividad['incluidos']))<div class="small text-success">Incluye: {{ $actividad['incluidos'] }}</div>@endif
        @if(($interno ?? false) && !empty($actividad['notas_internas']))<div class="small text-muted">Nota interna: {{ $actividad['notas_internas'] }}</div>@endif
    </div>
    @endforeach
</details>
@endforeach
