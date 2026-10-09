@extends('layouts.app')

@section('titulo', 'Catálogo de Proveedores')

@section('contenido')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-1"><i class="fas fa-truck-loading text-primary me-2"></i> Submódulo de Proveedores</h3>
        <p class="text-muted mb-0">Catálogo general de proveedores turísticos (aerolíneas, cadenas hoteleras, operadores local)</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnNuevo">
            <i class="fas fa-plus me-2"></i> Nuevo Proveedor
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Listado de Proveedores</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaProveedores">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 30%;">Nombre del Proveedor</th>
                        <th style="width: 35%;">Descripción</th>
                        <th style="width: 10%;">Servicios</th>
                        <th style="width: 15%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('catalogos.proveedor.modals.formProveedor')

@endsection

@push('scripts')
<script src="{{ asset('js/catalogos/proveedor.js') }}"></script>
@endpush
