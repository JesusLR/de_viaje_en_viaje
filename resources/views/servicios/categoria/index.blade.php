@extends('layouts.app')

@section('titulo', 'Categorías Turísticas')

@section('contenido')
<div class="apartados-panel catalogos-panel">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="apartados-emblema"><i class="fas fa-tags" aria-hidden="true"></i></span>
            <div><h3 class="fw-bold mb-1">Categorías</h3><p class="text-muted small mb-0">Administra tu catálogo de categorías.</p></div>
        </div>
        <button type="button" class="apartado-icono nuevo-apartado" id="btnNuevo"><i class="fas fa-plus" aria-hidden="true"></i> Nueva Categoría</button>
    </div>
    <div class="apartados-filtros mb-4">
        <label for="buscarCatalogo" class="form-label">Buscar categorías</label>
        <div class="apartados-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="buscarCatalogo" class="form-control" placeholder="Escribe para buscar…" autocomplete="off"></div>
    </div>
    <div class="apartados-grid mb-4">
        <table class="table align-middle w-100 mb-0" id="tablaCategorias">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 30%;">Nombre</th>
                        <th style="width: 35%;">Descripción</th>
                        <th style="width: 10%;">Servicios</th>
                        <th style="width: 15%;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
    </div>
    @include('servicios.categoria.modals.formCategoria')
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/servicios/categoria.js') }}?v={{ filemtime(public_path('js/servicios/categoria.js')) }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}">
<link rel="stylesheet" href="{{ asset('css/apartados-modals.css') }}?v={{ filemtime(public_path('css/apartados-modals.css')) }}">
<link rel="stylesheet" href="{{ asset('css/catalogos-module.css') }}?v={{ filemtime(public_path('css/catalogos-module.css')) }}">
@endpush
