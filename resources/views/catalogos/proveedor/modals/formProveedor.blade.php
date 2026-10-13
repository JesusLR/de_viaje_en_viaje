<div class="modal fade apartado-modal-shell" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProveedorTitle">Proveedor Turístico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formProveedor">
                <div class="modal-body venta-detalle-page">
                    <input type="hidden" id="iID" name="iID" value="0">

                    <div class="mb-3">
                        <label for="cNombre" class="form-label fw-bold">Nombre del Proveedor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cNombre" name="cNombre" maxlength="150" required placeholder="Ej. Hotel Meliá, Aeroméxico, Riu Hotels, Xcaret">
                    </div>

                    <div class="mb-3">
                        <label for="cDescripcion" class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" id="cDescripcion" name="cDescripcion" rows="3" placeholder="Información adicional del proveedor (servicios prestados, hoteles, traslados, etc.)..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1" aria-hidden="true"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i> Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
