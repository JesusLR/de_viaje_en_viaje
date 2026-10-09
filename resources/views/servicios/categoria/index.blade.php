@extends('layouts.app')

@section('titulo', 'Categorías Turísticas')

@section('contenido')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-1"><i class="fas fa-layer-group text-primary me-2"></i> Categorías Turísticas</h3>
        <p class="text-muted mb-0">Catálogo general para clasificación de paquetes y servicios vacacionales</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnNuevo">
            <i class="fas fa-plus me-2"></i> Nueva Categoría
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Listado de Categorías</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaCategorias">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 30%;">Nombre</th>
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

@include('servicios.categoria.modals.formCategoria')

@endsection

@push('scripts')
<script src="{{ asset('js/servicios/categoria.js') }}"></script>
@endpush
