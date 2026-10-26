@extends('layouts.app')
@section('titulo', $apartados ? 'Apartados' : 'Ventas')
@section('contenido')
<div class="apartados-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas {{ $apartados ? 'fa-plane-departure' : 'fa-receipt' }}" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">{{ $apartados ? 'Apartados' : 'Ventas' }}</h3><p class="text-muted mb-0 small">{{ $apartados ? 'Gestiona tus reservas y sigue el avance de sus pagos.' : 'Consulta tus ventas directas y apartados finalizados.' }}</p></div></div>
        <a data-permission="vender.ver" href="{{ route('agencia.vender.index') }}" class="apartado-icono nuevo-apartado" title="{{ $apartados ? 'Nuevo apartado' : 'Nueva venta' }}" aria-label="{{ $apartados ? 'Nuevo apartado' : 'Nueva venta' }}"><i class="fas fa-plus" aria-hidden="true"></i><span>{{ $apartados ? 'Nuevo apartado' : 'Nueva venta' }}</span></a>
    </div>
    @include('agencia.partials.avisos')
    <form method="GET" class="apartados-filtros mb-4" data-grid-filter="operaciones">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5"><label for="buscar" class="form-label">{{ $apartados ? 'Buscar apartado' : 'Buscar venta' }}</label><div class="apartados-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input id="buscar" name="buscar" maxlength="150" value="{{ request('buscar') }}" class="form-control" placeholder="Nombre, paquete o folio…"></div></div>
            <div class="col-6 col-lg-2"><label for="desde" class="form-label">Desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="form-control"></div>
            <div class="col-6 col-lg-2"><label for="hasta" class="form-label">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="form-control"></div>
            <div class="col-lg-3"><div class="d-flex gap-2">
                <button class="apartado-icono accion-principal" title="Consultar" aria-label="{{ $apartados ? 'Consultar apartados' : 'Consultar ventas' }}"><i class="fas fa-search" aria-hidden="true"></i></button>
                <button type="button" class="apartado-icono" data-bs-toggle="collapse" data-bs-target="#filtrosApartados" aria-controls="filtrosApartados" aria-expanded="{{ request()->filled('cliente') || request()->filled('usuario') || request()->filled('estado') ? 'true' : 'false' }}" title="Más filtros" aria-label="Más filtros"><i class="fas fa-sliders" aria-hidden="true"></i></button>
                <a href="{{ route($apartados ? 'agencia.apartados.index' : 'agencia.ventas.index') }}" class="apartado-icono" data-grid-clear title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fas fa-rotate-left" aria-hidden="true"></i></a>
            </div></div>
        </div>
        <div class="collapse {{ request()->filled('cliente') || request()->filled('usuario') || request()->filled('estado') ? 'show' : '' }}" id="filtrosApartados"><div class="row g-3 pt-3 mt-3 border-top">
            <div class="col-md-4"><label for="cliente" class="form-label">Cliente</label><select id="cliente" name="cliente" class="form-select"><option value="">Todos los clientes</option>@foreach($clientes as $cliente)<option value="{{ $cliente->iID }}" @selected(request('cliente') == $cliente->iID)>{{ $cliente->nombre_completo }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="usuario" class="form-label">Vendedor</label><select id="usuario" name="usuario" class="form-select" @disabled(!$puedeFiltrarVendedor)>@if($puedeFiltrarVendedor)<option value="">Todos los vendedores</option>@endif
            @foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(request('usuario') == $usuario->id)>{{ $usuario->nombre_completo }}</option>@endforeach</select></div>
@if($apartados)
            <div class="col-md-4"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos los estados</option>@foreach(['Abierto', 'Liquidado', 'Cancelado'] as $estado)<option @selected(request('estado') === $estado)>{{ $estado }}</option>@endforeach</select></div>
@else
            <div class="col-md-4"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select">@foreach(['Todas' => 'Todas las ventas', 'Liquidadas' => 'Liquidadas', 'Eliminadas' => 'Eliminadas'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected((request('estado') ?: 'Liquidadas') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
@endif
        </div></div>
    </form>
    <div class="apartados-grid" data-grid-results="operaciones">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom"><span class="fw-semibold">{{ $apartados ? 'Tus apartados' : 'Tus ventas' }} <span class="apartados-contador ms-2">{{ $ventas->total() }}</span></span></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>{{ $apartados ? 'Apartado' : 'Venta' }}</th><th>Cliente</th><th class="text-end">Total</th><th class="text-end">Abonado</th><th class="text-end">{{ $apartados ? 'Por abonar' : 'Origen' }}</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
        @forelse($ventas as $venta)
            @php
                $totalCentavos = (int) round((float) $venta->dTotal * 100);
                $estadoClase = ($venta->cancelada_at || $venta->eliminada_at) ? 'cancelado' : ($venta->saldo_centavos === 0 ? 'liquidado' : 'abierto');
            @endphp
            <tr>
                <td><span class="apartado-folio">{{ $venta->folio }}</span><div class="apartado-nombre" title="{{ $venta->cNombre ?: $venta->cPaquete }}">{{ $venta->cNombre ?: $venta->cPaquete }}</div><small class="text-muted">{{ ($apartados ? $venta->created_at : $venta->finalizada_at)->format('d/m/Y') }} <span class="mx-1">·</span> {{ $venta->detalles->count() }} {{ $venta->detalles->count() === 1 ? 'paquete' : 'paquetes' }}</small>
                    @php
                        $fechasViajes = $venta->detalles->filter(fn ($detalle) => $detalle->dFechaInicio && $detalle->dFechaFin)
                            ->map(fn ($detalle) => $detalle->dFechaInicio->format('d/m/Y').' — '.$detalle->dFechaFin->format('d/m/Y'))->unique()->values();
                    @endphp
                    @if($fechasViajes->isNotEmpty())
                    <small class="d-block text-muted mt-1" title="{{ $fechasViajes->implode(' · ') }}"><i class="fas fa-calendar-alt me-1" aria-hidden="true"></i>{{ $fechasViajes->first() }}{{ $fechasViajes->count() > 1 ? ' · +'.($fechasViajes->count() - 1).' viajes' : '' }}</small>
                    @endif
                </td>
                <td><span class="apartado-cliente">{{ $venta->cliente->nombre_completo }}</span></td>
                <td class="apartado-importe text-end"><strong>{{ number_format($totalCentavos / 100, 2) }}</strong><small>{{ $venta->cMoneda }}</small></td>
                <td class="apartado-importe text-end"><strong class="text-success">{{ number_format(($totalCentavos - $venta->saldo_centavos) / 100, 2) }}</strong><small>{{ $venta->cMoneda }}</small></td>
@if($apartados)
                <td class="apartado-importe text-end"><strong class="{{ $venta->saldo_centavos > 0 ? 'text-warning-emphasis' : 'text-success' }}">{{ number_format($venta->saldo_centavos / 100, 2) }}</strong><small>{{ $venta->cMoneda }}</small></td>
@else
                <td><span class="apartado-cliente">{{ $venta->cTipoOperacion === 'APARTADO' ? 'Apartado' : 'Venta directa' }}</span></td>
@endif
                <td><span class="apartado-estado {{ $estadoClase }}"><span class="estado-punto" aria-hidden="true"></span>{{ $apartados ? $venta->estado_apartado : ($venta->eliminada_at ? 'Eliminada' : 'Liquidada') }}</span></td>
                <td>@if($apartados)
                    @include('agencia.ventas.acciones-apartado')
                @else
                    @include('agencia.ventas.acciones-venta')
                @endif</td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="apartados-vacio"><i class="fas fa-suitcase-rolling mb-3" aria-hidden="true"></i><h5>{{ $apartados ? 'No hay apartados para mostrar' : 'No hay ventas para mostrar' }}</h5><p class="text-muted mb-0">Prueba otro periodo o cambia los filtros de búsqueda.</p></div></td></tr>
        @endforelse
        </tbody></table></div>
        @include('agencia.partials.paginacion', ['paginador' => $ventas, 'etiqueta' => $apartados ? 'apartados' : 'ventas'])
    </div>
</div>
@include('agencia.ventas.modal-accion')
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">
@endpush
@push('scripts')
@endpush
