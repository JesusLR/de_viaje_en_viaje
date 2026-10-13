@extends('layouts.app')

@section('titulo', 'Catálogo de Clientes')

@section('contenido')
<div class="apartados-panel catalogos-panel">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="apartados-emblema"><i class="fas fa-address-book" aria-hidden="true"></i></span>
            <div><h3 class="fw-bold mb-1">Clientes</h3><p class="text-muted small mb-0">Administra tu catálogo de clientes.</p></div>
        </div>
        <button type="button" class="apartado-icono nuevo-apartado" id="btnNuevo"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo Cliente</button>
    </div>
    <div class="apartados-filtros mb-4">
        <label for="buscarCatalogo" class="form-label">Buscar clientes</label>
        <div class="apartados-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="buscarCatalogo" class="form-control" placeholder="Escribe para buscar…" autocomplete="off"></div>
    </div>
    <div class="apartados-grid mb-4">
        <table class="table align-middle w-100 mb-0" id="tablaClientes">
                <thead class="table-light">
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 25%;">Nombre Completo</th>
                        <th style="width: 15%;">Teléfono</th>
                        <th style="width: 22%;">Correo Electrónico</th>
                        <th style="width: 15%;">RFC / ID Fiscal</th>
                        <th style="width: 15%;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
    </div>
    @include('catalogos.cliente.modals.formCliente')
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/catalogos/cliente.js') }}?v={{ filemtime(public_path('js/catalogos/cliente.js')) }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}">
<link rel="stylesheet" href="{{ asset('css/apartados-modals.css') }}?v={{ filemtime(public_path('css/apartados-modals.css')) }}">
<link rel="stylesheet" href="{{ asset('css/catalogos-module.css') }}?v={{ filemtime(public_path('css/catalogos-module.css')) }}">
@endpush
