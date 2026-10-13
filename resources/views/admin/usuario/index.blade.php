@extends('layouts.app')

@section('titulo', 'Gestión de Usuarios')

@section('contenido')
<div class="apartados-panel catalogos-panel">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="apartados-emblema"><i class="fas fa-users-cog" aria-hidden="true"></i></span>
            <div><h3 class="fw-bold mb-1">Usuarios</h3><p class="text-muted small mb-0">Administra los usuarios del sistema.</p></div>
        </div>
        <button type="button" class="apartado-icono nuevo-apartado" id="btnNuevo"><i class="fas fa-user-plus" aria-hidden="true"></i> Nuevo Usuario</button>
    </div>
    <div class="apartados-filtros mb-4">
        <label for="buscarUsuarios" class="form-label">Buscar usuarios</label>
        <div class="apartados-busqueda"><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="buscarUsuarios" class="form-control" placeholder="Escribe para buscar…" autocomplete="off"></div>
    </div>
    <div class="apartados-grid mb-4">
        <table class="table align-middle w-100 mb-0" id="tablaUsuarios">
                <thead class="table-light">
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 27%;">Nombre Completo</th>
                        <th style="width: 15%;">Teléfono</th>
                        <th style="width: 25%;">Correo Electrónico</th>
                        <th style="width: 15%;">Fecha Alta</th>
                        <th style="width: 10%;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
    </div>
    @include('admin.usuario.modals.formUsuario')
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/admin/usuario.js') }}?v={{ filemtime(public_path('js/admin/usuario.js')) }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}?v={{ filemtime(public_path('css/agencia-grid.css')) }}">
<link rel="stylesheet" href="{{ asset('css/apartados-modals.css') }}?v={{ filemtime(public_path('css/apartados-modals.css')) }}">
<link rel="stylesheet" href="{{ asset('css/catalogos-module.css') }}?v={{ filemtime(public_path('css/catalogos-module.css')) }}">
@endpush
