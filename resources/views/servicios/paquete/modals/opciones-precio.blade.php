<div class="col-12 mb-3">
    <label for="lOpcionesPrecio" class="form-label fw-bold">Modalidad de precios</label>
    <select id="lOpcionesPrecio" name="lOpcionesPrecio" class="form-select">
        <option value="0">Precio único</option>
        <option value="1">Varias opciones de precio / habitación</option>
    </select>
    <div id="editorOpcionesPrecio" class="d-none mt-3">
        <p class="text-muted small">Primero elige cómo se cobra cada tarifa. Después captura su precio y cómo se manejan los menores. Todos los importes corresponden a la estancia completa.</p>
        <div id="opcionesPrecioFilas"></div>
        <button type="button" id="agregarOpcionPrecio" class="btn btn-outline-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i>Agregar opción</button>
        <div class="form-text">El precio base y el costo muestran la opción activa de menor precio. Las promociones del servicio se aplican a todas las opciones.</div>
    </div>
</div>
<template id="opcionPrecioTemplate">
    <div class="card card-body bg-light mb-3 opcion-precio-editor">
        <input type="hidden" data-campo="id">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label d-block">Nombre de la opción<input data-campo="nombre" maxlength="100" class="form-control" placeholder="Ej. Habitación doble"></label></div>
            <div class="col-md-6"><label class="form-label d-block">¿Cómo se cobra?<select data-campo="unidad" class="form-select"><option value="HABITACION">Precio por habitación</option><option value="PERSONA">Precio por persona</option></select></label></div>
            <div class="col-md-6 capacidad-habitacion"><label class="form-label d-block">Adultos incluidos por habitación<input data-campo="adultos" type="number" min="1" max="20" value="2" class="form-control"></label></div>
            <div class="col-md-6 capacidad-habitacion"><label class="form-label d-block"><span class="etiqueta-capacidad-menores">Menores incluidos por habitación</span><input data-campo="menores" type="number" min="0" max="20" value="0" class="form-control"></label></div>
            @can('servicios.costos.ver')
            <div class="col-md-4"><label class="form-label d-block">Costo de la estancia<input data-campo="dPrecioCompra" type="number" min="0" max="9999999999.99" step="0.01" value="0" class="form-control" @readonly(!auth()->user()->hasPermission('servicios.costos.editar'))></label></div>
            @endcan
            <div class="col-md-4"><label class="form-label d-block"><span class="etiqueta-precio-opcion">Precio total de la habitación por estancia</span><input data-campo="dPrecioVenta" type="number" min="0" max="9999999999.99" step="0.01" value="0" class="form-control"></label></div>
            <div class="col-md-4"><label class="form-label d-block">Estado<select data-campo="activo" class="form-select"><option value="1">Activa</option><option value="0">Inactiva</option></select></label></div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" data-campo="lPrecioMenorActivo" data-menor-hidden value="0">
                    <input class="form-check-input activar-menor-opcion" type="checkbox" data-campo="lPrecioMenorActivo" value="1">
                    <label class="form-check-label">Activar precio para menor</label>
                    <small class="ayuda-menores-opcion text-muted d-block mt-1"></small>
                </div>
                <div class="precio-menor-opcion d-none mt-2">
                    <label class="form-label d-block">Precio para menor<input data-campo="dPrecioMenor" type="number" min="0" max="9999999999.99" step="0.01" value="0" placeholder="0.00" class="form-control"></label>
                    <small class="text-muted">En blanco o en 0: gratis. Este importe se cobra por cada menor que viaje.</small>
                </div>
            </div>
            <div class="col-12"><div class="resumen-cobro-opcion alert alert-primary mb-0" role="status" aria-live="polite"></div></div>
            <div class="col-12"><details><summary class="text-primary fw-semibold">Datos opcionales de la habitación</summary><div class="mt-3">
                <label class="form-label d-block">Tipo de cama<input data-campo="cama" maxlength="100" class="form-control" placeholder="Ej. Dos camas matrimoniales"></label>
                <label class="form-label d-block">Descripción<textarea data-campo="descripcion" maxlength="500" rows="2" class="form-control"></textarea></label>
            </div></details></div>
        </div>
        <button type="button" class="btn btn-outline-danger align-self-end quitar-opcion mt-2" aria-label="Quitar opción"><i class="fas fa-trash" aria-hidden="true"></i></button>
    </div>
</template>
