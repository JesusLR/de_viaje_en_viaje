@extends('layouts.app')
@section('titulo', $apartados ? 'Apartados' : 'Ventas liquidadas')
@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="fw-bold">{{ $apartados ? 'Apartados' : 'Ventas liquidadas' }}</h3><p class="text-muted mb-0">{{ $apartados ? 'Administra saldos, abonos y apartados pendientes de finalizar.' : 'Consulta ventas directas y apartados finalizados.' }}</p></div><a class="btn btn-primary" href="{{ route('agencia.vender.index') }}">{{ $apartados ? 'Nuevo apartado / venta' : 'Vender' }}</a></div>
@include('agencia.partials.avisos')
<form method="GET" class="card card-body mb-4"><div class="row g-3">
    <div class="col-md-3"><label for="cliente" class="form-label">Cliente</label><select id="cliente" name="cliente" class="form-select"><option value="">Todos</option>@foreach($clientes as $cliente)<option value="{{ $cliente->iID }}" @selected(request('cliente') == $cliente->iID)>{{ $cliente->nombre_completo }}</option>@endforeach</select></div>
    <div class="col-md-3"><label for="usuario" class="form-label">Vendedor</label><select id="usuario" name="usuario" class="form-select"><option value="">Todos</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(request('usuario') == $usuario->id)>{{ $usuario->nombre_completo }}</option>@endforeach</select></div>
    @if($apartados)<div class="col-md-3"><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option>@foreach(['Abierto', 'Liquidado', 'Cancelado'] as $estado)<option @selected(request('estado') === $estado)>{{ $estado }}</option>@endforeach</select></div>@endif
    <div class="col-md-3"><label for="buscar" class="form-label">Nombre, paquete o folio</label><input id="buscar" name="buscar" maxlength="150" value="{{ request('buscar') }}" class="form-control"></div>
    <div class="col-md-3"><label for="desde" class="form-label">Desde</label><input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="form-control"></div><div class="col-md-3"><label for="hasta" class="form-label">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="form-control"></div>
    <div class="col-md-6 d-flex align-items-end gap-2"><button class="btn btn-primary">Consultar</button><a href="{{ route($apartados ? 'agencia.apartados.index' : 'agencia.ventas.index') }}" class="btn btn-outline-secondary">Limpiar</a></div>
</div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Folio / Nombre</th><th>Cliente / Paquetes</th><th>{{ $apartados ? 'Registro' : 'Liquidación' }}</th><th>Viaje</th><th>Total</th><th>{{ $apartados ? 'Saldo' : 'Origen' }}</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($ventas as $venta)
<tr>
    <td>{{ $venta->folio }}<br><small>{{ $venta->cNombre }}</small></td>
    <td>{{ $venta->cliente->nombre_completo }}<br>
        @foreach($venta->detalles as $detalle)
            <small class="text-muted d-block">{{ $detalle->cPaquete }} × {{ $detalle->iCantidad }}</small>
            @if($detalle->cPromocion)
                <small class="text-success d-block">{{ $detalle->cPromocion }} · Descuento: {{ number_format($detalle->dDescuentoUnitario * $detalle->iCantidad, 2) }} {{ $venta->cMoneda }}</small>
            @endif
        @endforeach
        <small>Vendedor: {{ $venta->usuario?->nombre_completo ?? '—' }}</small>
    </td>
    <td>{{ ($apartados ? $venta->created_at : $venta->finalizada_at)->format('d/m/Y') }}</td>
    <td>@foreach($venta->detalles as $detalle)<small class="d-block text-nowrap">@include('agencia.ventas.fechas-detalle')</small>@endforeach</td>
    <td class="text-nowrap">{{ number_format($venta->dTotal, 2) }} {{ $venta->cMoneda }}</td>
    <td>{{ $apartados ? number_format($venta->saldo_centavos / 100, 2).' '.$venta->cMoneda : ($venta->cTipoOperacion === 'APARTADO' ? 'Apartado' : 'Venta directa') }}</td>
    <td><span class="badge bg-{{ $venta->cancelada_at ? 'secondary' : ($venta->saldo_centavos === 0 ? 'success' : 'warning text-dark') }}">{{ $apartados ? $venta->estado_apartado : 'Liquidada' }}</span></td>
    <td>
        @if($apartados)
        <div class="d-flex flex-wrap gap-1" style="min-width:200px">
            @foreach(['detalle' => ['fa-receipt', 'Ver compra'], 'historial' => ['fa-history', 'Historial de abonos'], 'abono' => ['fa-money-bill-wave', 'Registrar abono'], 'paquetes' => ['fa-boxes', 'Agregar paquetes'], 'nombre' => ['fa-pen', 'Cambiar nombre']] as $accion => [$icono, $titulo])
                @if(!in_array($accion, ['abono', 'paquetes']) || (!$venta->cancelada_at && !$venta->finalizada_at && ($accion !== 'abono' || $venta->saldo_centavos > 0)))
                <button type="button" class="btn btn-outline-{{ $accion === 'abono' ? 'success' : 'primary' }} btn-sm" data-bs-toggle="modal" data-bs-target="#accionApartado" data-url="{{ route('agencia.ventas.show', ['venta' => $venta, 'seccion' => $accion]) }}" data-titulo="{{ $titulo }} · {{ $venta->folio }}" title="{{ $titulo }}" aria-label="{{ $titulo }} de {{ $venta->folio }}"><i class="fas {{ $icono }} me-1"></i>{{ $titulo }}</button>
                @endif
            @endforeach
            @if(!$venta->cancelada_at && !$venta->finalizada_at)
                @if($venta->saldo_centavos === 0)
                <form method="POST" action="{{ route('agencia.ventas.finalizar', $venta) }}" data-confirm="¿Finalizar este apartado liquidado?">@csrf<button class="btn btn-success btn-sm">Finalizar</button></form>
                @endif
                <form method="POST" action="{{ route('agencia.ventas.cancelar', $venta) }}" data-confirm="¿Cancelar este apartado? Los abonos recibidos se conservarán en finanzas.">@csrf<button class="btn btn-outline-danger btn-sm">Cancelar</button></form>
            @endif
        </div>
        @else
        <a href="{{ route('agencia.ventas.show', $venta) }}" class="btn btn-outline-primary btn-sm">Detalle</a>
        @endif
    </td>
</tr>
@empty<tr><td colspan="8" class="text-center py-4 text-muted">No hay registros para los filtros seleccionados.</td></tr>@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $ventas->links('pagination::bootstrap-5') }}</div>
@if($apartados)
<div class="modal fade" id="accionApartado" tabindex="-1" aria-labelledby="accionApartadoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="accionApartadoTitulo">Apartado</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body p-0"><iframe id="accionApartadoContenido" title="Acción del apartado" style="width:100%;height:75vh;border:0"></iframe></div>
    </div></div>
</div>
@endif
@endsection
@if($apartados)
@push('scripts')
<script>
const modalApartado = document.getElementById('accionApartado');
const contenidoApartado = document.getElementById('accionApartadoContenido');
let apartadoModificado = false;
modalApartado.addEventListener('show.bs.modal', function (evento) {
    const boton = evento.relatedTarget;
    document.getElementById('accionApartadoTitulo').textContent = boton.dataset.titulo;
    contenidoApartado.title = boton.dataset.titulo;
    apartadoModificado = false;
    contenidoApartado.src = boton.dataset.url;
});
contenidoApartado.addEventListener('load', function () {
    if (contenidoApartado.contentDocument) contenidoApartado.contentDocument.addEventListener('submit', function (evento) {
        if (!evento.defaultPrevented) apartadoModificado = true;
    });
});
modalApartado.addEventListener('hidden.bs.modal', function () {
    contenidoApartado.src = 'about:blank';
    if (apartadoModificado) window.location.reload();
});
</script>
@endpush
@endif
