@extends('layouts.app')

@section('titulo', 'Servicios y Paquetes Turísticos')

@section('contenido')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="fw-bold mb-1"><i class="fas fa-boxes text-primary me-2"></i> Servicios y Paquetes Turísticos</h3>
        <p class="text-muted mb-0">Catálogo general de paquetes vacacionales, tours, excursiones y promociones</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnNuevo">
            <i class="fas fa-plus me-2"></i> Nuevo Servicio
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Listado de Servicios Turísticos</h6>
        
        <!-- Botón para alternar los filtros avanzados -->
        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros" aria-expanded="false">
            <i class="fas fa-filter me-1"></i> Filtros Avanzados
        </button>
    </div>

    <!-- Sección de Filtros integrada -->
    <div class="collapse show" id="collapseFiltros">
        <div class="card-body bg-light border-top border-bottom py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label for="filtroCategoria" class="form-label small fw-semibold text-muted mb-1">Categoría</label>
                    <select class="form-select form-select-sm" id="filtroCategoria">
                        <option value="">-- Todas las Categorías --</option>
                        @foreach($aCategorias as $oCat)
                            <option value="{{ $oCat->iID }}">{{ $oCat->cNombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtroDestino" class="form-label small fw-semibold text-muted mb-1">Destino</label>
                    <select class="form-select form-select-sm" id="filtroDestino">
                        <option value="">-- Todos los Destinos --</option>
                        @foreach($aDestinos as $oDest)
                            <option value="{{ $oDest->iID }}">{{ $oDest->cNombre }} ({{ $oDest->cPais }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtroEstatus" class="form-label small fw-semibold text-muted mb-1">Estatus</label>
                    <select class="form-select form-select-sm" id="filtroEstatus">
                        <option value="">-- Todos los Estatus --</option>
                        <option value="ACTIVO">Activos (Disponibles)</option>
                        <option value="PROGRAMADO">Programados (Próximos)</option>
                        <option value="VENCIDO">Vencidos (Finalizados)</option>
                        <option value="INACTIVO">Desactivados (Manual)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtroPromocion" class="form-label small fw-semibold text-muted mb-1">Promociones</label>
                    <select class="form-select form-select-sm" id="filtroPromocion">
                        <option value="">-- Todos --</option>
                        <option value="1">Solo con Promoción Activa</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaServicios">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">Código</th>
                        <th style="width: 8%;">Imagen</th>
                        <th style="width: 24%;">Servicio Turístico</th>
                        <th style="width: 14%;">Categoría</th>
                        <th style="width: 14%;">Destino</th>
                        <th style="width: 14%;">Proveedor</th>
                        <th style="width: 8%;">Estatus</th>
                        <th style="width: 8%;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Carga dinámica vía AJAX DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Formulario Servicio / Paquete -->
@include('servicios.paquete.modals.formServicio')

<!-- Modal Detalle Completo de Servicio -->
@include('servicios.paquete.modals.detalleServicio')

@endsection

@push('scripts')
<script src="{{ asset('js/servicios/servicio.js') }}"></script>
@endpush
