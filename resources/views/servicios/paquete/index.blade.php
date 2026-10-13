@extends('layouts.app')
@section('titulo', 'Servicios y Paquetes Turísticos')
@section('contenido')
<div class="apartados-panel servicios-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><div class="apartados-emblema"><i class="fas fa-box-open" aria-hidden="true"></i></div><div><h3 class="fw-bold mb-1">Servicios y paquetes</h3><p class="text-muted mb-0 small">Administra tu catálogo de viajes, precios y promociones.</p></div></div>
        <button type="button" class="apartado-icono nuevo-apartado" id="btnNuevo"><i class="fas fa-plus" aria-hidden="true"></i><span>Nuevo servicio</span></button>
    </div>
    <div class="apartados-filtros mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-6"><label for="buscarServicio" class="form-label">Buscar servicio</label><div class="apartados-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input id="buscarServicio" class="form-control" placeholder="Código, nombre, destino o proveedor…" type="search"></div></div>
            <div class="col-lg-3"><label for="filtroEstatus" class="form-label">Estado</label><select class="form-select" id="filtroEstatus"><option value="">Todos los estados</option><option value="ACTIVO">Activo</option><option value="PROGRAMADO">Programado</option><option value="VENCIDO">Vencido</option><option value="INACTIVO">Inactivo</option></select></div>
            <div class="col-lg-3"><div class="d-flex gap-2"><button type="button" class="apartado-icono" data-bs-toggle="collapse" data-bs-target="#collapseFiltros" aria-expanded="false" aria-controls="collapseFiltros" title="Más filtros" aria-label="Más filtros"><i class="fas fa-sliders" aria-hidden="true"></i></button><button type="button" class="apartado-icono" id="limpiarFiltrosServicios" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="fas fa-rotate-left" aria-hidden="true"></i></button></div></div>
        </div>
        <div class="collapse" id="collapseFiltros"><div class="row g-3 pt-3 mt-3 border-top">
            <div class="col-md-4"><label for="filtroCategoria" class="form-label">Categoría</label><select class="form-select" id="filtroCategoria"><option value="">Todas las categorías</option>@foreach($aCategorias as $oCat)<option value="{{ $oCat->iID }}">{{ $oCat->cNombre }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="filtroDestino" class="form-label">Destino</label><select class="form-select" id="filtroDestino"><option value="">Todos los destinos</option>@foreach($aDestinos as $oDest)<option value="{{ $oDest->iID }}">{{ $oDest->cNombre }} ({{ $oDest->cPais }})</option>@endforeach</select></div>
            <div class="col-md-4"><label for="filtroPromocion" class="form-label">Promociones</label><select class="form-select" id="filtroPromocion"><option value="">Todos</option><option value="1">Con promoción vigente</option></select></div>
        </div></div>
    </div>
    <div class="apartados-grid">
        <div class="px-4 py-3 border-bottom fw-semibold">Tu catálogo <span id="totalServicios" class="apartados-contador ms-2" aria-live="polite">0</span></div>
        <div class="table-responsive"><table class="table align-middle mb-0 w-100" id="tablaServicios" data-grid-url="{{ route('servicios.paquetes.gridData') }}"><thead><tr><th>Servicio</th><th>Destino / Proveedor</th><th>Inicio / Fin</th><th class="text-end">Precio de venta</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody></tbody></table></div>
        <div id="serviciosPaginacion"></div>
    </div>
</div>
@include('servicios.paquete.modals.formServicio')
@include('servicios.paquete.modals.detalleServicio')
@endsection
@push('styles')
<link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/servicios-module.css') }}" rel="stylesheet">
@endpush
@push('scripts')
<script src="{{ asset('js/servicios/servicio.js') }}?v={{ filemtime(public_path('js/servicios/servicio.js')) }}"></script>
@endpush
