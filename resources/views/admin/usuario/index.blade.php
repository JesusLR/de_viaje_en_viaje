@extends('layouts.app')

@section('titulo', 'Gestión de Usuarios')

@section('contenido')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-1"><i class="fas fa-users-cog text-primary me-2"></i> Submódulo de Usuarios</h3>
        <p class="text-muted mb-0">Administración y alta de usuarios del sistema</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnNuevo">
            <i class="fas fa-user-plus me-2"></i> Nuevo Usuario
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Listado de Usuarios Activos</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaUsuarios">
                <thead class="table-light">
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 27%;">Nombre Completo</th>
                        <th style="width: 15%;">Teléfono</th>
                        <th style="width: 25%;">Correo Electrónico</th>
                        <th style="width: 15%;">Fecha Alta</th>
                        <th style="width: 10%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Formulario Usuario -->
@include('admin.usuario.modals.formUsuario')

@endsection

@push('scripts')
<script src="{{ asset('js/admin/usuario.js') }}"></script>
@endpush
