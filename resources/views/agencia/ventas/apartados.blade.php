@extends('layouts.app')
@section('titulo', 'Apartados')
@section('contenido')
<div class="apartados-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-plane-departure" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">Apartados</h3><p class="text-muted mb-0 small">Gestiona tus reservas y sigue el avance de sus pagos.</p></div></div>
        <a href="{{ route('agencia.vender.index') }}" class="apartado-icono nuevo-apartado" title="Nuevo apartado" aria-label="Nuevo apartado"><i class="fas fa-plus" aria-hidden="true"></i><span>Nuevo apartado</span></a>
    </div>
    @include('agencia.partials.avisos')
    <form method="GET" class="apartados-filtros mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5"><label for="buscar" class="form-label">Buscar apartado</label><div class="apartados-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input id="buscar" name="buscar" maxlength="150" value="{{ request('buscar') }}" class="form-control" placeholder="Nombre, paquete o folio…"></div></div>
            <div class="col-6 col-lg-2"><label for="desde" class="form-label">Desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="form-control"></div>
            <div class="col-6 col-lg-2"><label for="hasta" class="form-label">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="form-control"></div>
            <div class="col-lg-3"><div class="d-flex gap-2">
                <button class="apartado-icono accion-principal" title="Consultar" aria-label="Consultar apartados"><i class="fas fa-search" aria-hidden="true"></i></button>
                <button type="button" class="apartado-icono" data-bs-toggle="collapse" data-bs-target="#filtrosApartados" aria-controls="filtrosApartados" aria-expanded="{{ request()->filled('cliente') || request()->filled('usuario') || request()->filled('estado') ? 'true' : 'false' }}" title="Más filtros" aria-label="Más filtros"><i class="fas fa-sliders" aria-hidden="true"></i></button>
                <a href="{{ route('agencia.apartados.index') }}" class="apartado-icono" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fas fa-rotate-left" aria-hidden="true"></i></a>
            </div></div>
        </div>
        <div class="collapse {{ request()->filled('cliente') || request()->filled('usuario') || request()->filled('estado') ? 'show' : '' }}" id="filtrosApartados"><div class="row g-3 pt-3 mt-3 border-top">
            <div class="col-md-4"><label for="cliente" class="form-label">Cliente</label><select id="cliente" name="cliente" class="form-select"><option value="">Todos los clientes</option>@foreach($clientes as $cliente)<option value="{{ $cliente->iID }}" @selected(request('cliente') == $cliente->iID)>{{ $cliente->nombre_completo }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="usuario" class="form-label">Vendedor</label><select id="usuario" name="usuario" class="form-select"><option value="">Todos los vendedores</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(request('usuario') == $usuario->id)>{{ $usuario->nombre_completo }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos los estados</option>@foreach(['Abierto', 'Liquidado', 'Cancelado'] as $estado)<option @selected(request('estado') === $estado)>{{ $estado }}</option>@endforeach</select></div>
        </div></div>
    </form>
    <div class="apartados-grid">
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom"><span class="fw-semibold">Tus apartados <span class="apartados-contador ms-2">{{ $ventas->total() }}</span></span><small class="text-muted">{{ $ventas->firstItem() ?? 0 }}–{{ $ventas->lastItem() ?? 0 }} de {{ $ventas->total() }}</small></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Apartado</th><th>Cliente</th><th class="text-end">Total</th><th class="text-end">Abonado</th><th class="text-end">Por abonar</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
        @forelse($ventas as $venta)
            @php
                $totalCentavos = (int) round((float) $venta->dTotal * 100);
                $estadoClase = $venta->cancelada_at ? 'cancelado' : ($venta->saldo_centavos === 0 ? 'liquidado' : 'abierto');
            @endphp
            <tr>
                <td><span class="apartado-folio">{{ $venta->folio }}</span><div class="apartado-nombre" title="{{ $venta->cNombre ?: $venta->cPaquete }}">{{ $venta->cNombre ?: $venta->cPaquete }}</div><small class="text-muted">{{ $venta->created_at->format('d/m/Y') }} <span class="mx-1">·</span> {{ $venta->detalles->count() }} {{ $venta->detalles->count() === 1 ? 'paquete' : 'paquetes' }}</small></td>
                <td><span class="apartado-cliente">{{ $venta->cliente->nombre_completo }}</span></td>
                <td class="apartado-importe text-end"><strong>{{ number_format($totalCentavos / 100, 2) }}</strong><small>{{ $venta->cMoneda }}</small></td>
                <td class="apartado-importe text-end"><strong class="text-success">{{ number_format(($totalCentavos - $venta->saldo_centavos) / 100, 2) }}</strong><small>{{ $venta->cMoneda }}</small></td>
                <td class="apartado-importe text-end"><strong class="{{ $venta->saldo_centavos > 0 ? 'text-warning-emphasis' : 'text-success' }}">{{ number_format($venta->saldo_centavos / 100, 2) }}</strong><small>{{ $venta->cMoneda }}</small></td>
                <td><span class="apartado-estado {{ $estadoClase }}"><span class="estado-punto" aria-hidden="true"></span>{{ $venta->estado_apartado }}</span></td>
                <td><div class="apartado-acciones">
                    @foreach(['detalle' => ['fa-receipt', 'Ver compra'], 'historial' => ['fa-clock-rotate-left', 'Historial de abonos'], 'abono' => ['fa-money-bill-wave', 'Registrar abono'], 'paquetes' => ['fa-box-open', 'Agregar paquetes'], 'nombre' => ['fa-pen', 'Cambiar nombre']] as $accion => [$icono, $titulo])
                        @if(!in_array($accion, ['abono', 'paquetes']) || (!$venta->cancelada_at && !$venta->finalizada_at && ($accion !== 'abono' || $venta->saldo_centavos > 0)))
                            <button type="button" class="apartado-icono {{ $accion === 'abono' ? 'accion-abono' : '' }}" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => $accion]) }}" data-titulo="{{ $titulo }} · {{ $venta->folio }}" title="{{ $titulo }}" aria-label="{{ $titulo }} de {{ $venta->folio }}"><i class="fas {{ $icono }}" aria-hidden="true"></i></button>
                        @endif
                    @endforeach
                    @if(!$venta->cancelada_at && !$venta->finalizada_at)
                        @if($venta->saldo_centavos === 0)
                            <form method="POST" action="{{ route('agencia.ventas.finalizar', $venta) }}" data-confirm="¿Finalizar este apartado liquidado?">@csrf<button class="apartado-icono accion-abono" title="Finalizar apartado" aria-label="Finalizar {{ $venta->folio }}"><i class="fas fa-check" aria-hidden="true"></i></button></form>
                        @endif
                        <form method="POST" action="{{ route('agencia.ventas.cancelar', $venta) }}" data-confirm="¿Cancelar este apartado?" data-confirm-text="Los abonos recibidos se conservarán en finanzas.">@csrf<button class="apartado-icono accion-cancelar" title="Cancelar apartado" aria-label="Cancelar {{ $venta->folio }}"><i class="fas fa-ban" aria-hidden="true"></i></button></form>
                    @endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="apartados-vacio"><i class="fas fa-suitcase-rolling mb-3" aria-hidden="true"></i><h5>No hay apartados para mostrar</h5><p class="text-muted mb-0">Prueba otro periodo o cambia los filtros de búsqueda.</p></div></td></tr>
        @endforelse
        </tbody></table></div>
        @if($ventas->hasPages())<div class="px-4 pt-3 border-top">{{ $ventas->links('pagination::bootstrap-5') }}</div>@endif
    </div>
</div>
@include('agencia.ventas.modal-accion')
@endsection
@push('styles')
<style>
.apartados-emblema{width:48px;height:48px;display:grid;place-items:center;border-radius:16px;background:#eaf0ff;color:#4464df;font-size:20px}
.apartados-filtros,.apartados-grid{background:#fff;border:1px solid #e8edf4;border-radius:18px;box-shadow:0 4px 24px rgba(30,41,59,.035)}
.apartados-filtros{padding:22px}.apartados-panel .form-label{font-size:12px;font-weight:600;color:#64748b}.apartados-panel .form-control{border-radius:10px;min-height:40px}.apartados-busqueda{position:relative}.apartados-busqueda>i{position:absolute;left:14px;top:14px;color:#94a3b8}.apartados-busqueda input{padding-left:38px}
.apartados-grid{overflow:hidden}.apartados-grid thead th{background:#f8fafc;color:#8a96a8;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border:0;padding:14px 22px;white-space:nowrap}.apartados-grid tbody td{padding:20px 22px;border-color:#f0f3f8}.apartados-grid tbody tr{transition:background .18s}.apartados-grid tbody tr:hover{background:#fafbff}
.apartado-folio{color:#64748b;font-size:11px;font-weight:600;letter-spacing:.03em}.apartado-nombre{font-size:14px;font-weight:650;color:#25324b;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:4px 0}.apartado-cliente{font-size:13px;font-weight:500;max-width:180px}.apartado-importe{white-space:nowrap;font-variant-numeric:tabular-nums}.apartado-importe strong{display:block;font-size:14px;color:#273449}.apartado-importe small{font-size:10px;color:#94a3b8}
.apartado-estado{display:inline-flex;align-items:center;gap:7px;border-radius:20px;padding:6px 10px;font-size:11px;font-weight:600;white-space:nowrap}.apartado-estado.abierto{background:#fff6e5;color:#b37a19}.apartado-estado.liquidado{background:#e9f8f0;color:#24885b}.apartado-estado.cancelado{background:#f0f2f5;color:#7c8798}.estado-punto{height:5px;width:5px;border-radius:50%;background:currentColor}
.apartado-acciones{display:flex;align-items:center;justify-content:flex-end;gap:5px}.apartado-acciones form{margin:0}.apartado-icono{width:33px;height:33px;border:0;border-radius:10px;display:inline-flex;justify-content:center;align-items:center;background:#f3f5fa;color:#79869b;text-decoration:none;font-size:12px;transition:background .18s,color .18s,transform .18s;flex-shrink:0}.apartado-icono:hover{background:#eaf0ff;color:#4464df;transform:translateY(-2px)}.apartado-icono:focus-visible{outline:3px solid #a8b9ff;outline-offset:2px}.apartado-icono.accion-abono{color:#25946c;background:#edf8f2}.apartado-icono.accion-abono:hover{background:#d7f2e3}.apartado-icono.accion-cancelar:hover{background:#ffeded;color:#d65757}.apartado-icono.accion-principal,.apartado-icono.nuevo-apartado{background:#536fe8;color:#fff;width:40px;height:40px}.apartado-icono.nuevo-apartado{width:44px;height:44px;font-size:16px}.apartado-icono.accion-principal:hover,.apartado-icono.nuevo-apartado:hover{background:#405cd2}.apartados-contador{font-size:11px;padding:4px 8px;background:#f0f3fa;color:#75829a;border-radius:8px}.apartados-vacio{text-align:center;padding:50px 20px}.apartados-vacio>i{font-size:32px;color:#b5c1dc}
#accionApartado .modal-content{border:0;border-radius:18px;overflow:hidden}#accionApartado .modal-header{background:#f8fafc;border-bottom-color:#eef2f7}#accionApartado .modal-title{font-size:15px;font-weight:600}
.apartado-icono.nuevo-apartado{width:auto;padding:0 18px;gap:9px;font-size:13px;font-weight:600;white-space:nowrap}
@media(max-width:768px){.apartados-filtros{padding:16px}.apartados-grid tbody td{padding:16px}.apartados-panel h3{font-size:22px}}
@media(prefers-reduced-motion:reduce){.apartado-icono,.apartados-grid tbody tr{transition:none}.apartado-icono:hover{transform:none}}
</style>
@endpush
