@extends('layouts.app')

@section('titulo', 'Catálogo de Clientes')

@section('contenido')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-1"><i class="fas fa-address-book text-primary me-2"></i> Submódulo de Clientes</h3>
        <p class="text-muted mb-0">Catálogo general de clientes registrados para reservaciones y viajes</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnNuevo">
            <i class="fas fa-user-plus me-2"></i> Nuevo Cliente
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Listado de Clientes Activos</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaClientes">
                <thead class="table-light">
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 25%;">Nombre Completo</th>
                        <th style="width: 15%;">Teléfono</th>
                        <th style="width: 22%;">Correo Electrónico</th>
                        <th style="width: 15%;">RFC / ID Fiscal</th>
                        <th style="width: 15%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Formulario Cliente -->
@include('catalogos.cliente.modals.formCliente')

@endsection

@push('scripts')
<script src="{{ asset('js/catalogos/cliente.js') }}"></script>
@endpush
