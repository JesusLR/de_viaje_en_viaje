@extends('layouts.app')

@section('titulo', 'Destinos Turísticos')

@section('contenido')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-1"><i class="fas fa-map-marked-alt text-primary me-2"></i> Destinos Turísticos</h3>
        <p class="text-muted mb-0">Catálogo general de ubicaciones y destinos de viaje</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnNuevo">
            <i class="fas fa-plus me-2"></i> Nuevo Destino
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Listado de Destinos</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaDestinos">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 20%;">Destino</th>
                        <th style="width: 15%;">País</th>
                        <th style="width: 15%;">Estado / Región</th>
                        <th style="width: 15%;">Ciudad</th>
                        <th style="width: 10%;">Servicios</th>
                        <th style="width: 10%;">Estatus</th>
                        <th style="width: 10%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('servicios.destino.modals.formDestino')

@endsection

@push('scripts')
<script src="{{ asset('js/servicios/destino.js') }}"></script>
@endpush
