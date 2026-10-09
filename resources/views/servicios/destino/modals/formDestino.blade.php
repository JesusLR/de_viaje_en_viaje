<div class="modal fade" id="modalDestino" tabindex="-1" aria-labelledby="modalDestinoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDestinoTitle">Destino Turístico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDestino">
                <div class="modal-body">
                    <input type="hidden" id="iID" name="iID" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cNombre" class="form-label fw-bold">Nombre del Destino <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cNombre" name="cNombre" maxlength="150" required placeholder="Ej. Cancún, Colombia, Roma, París">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cPais" class="form-label fw-bold">País</label>
                            <input type="text" class="form-control" id="cPais" name="cPais" maxlength="100" placeholder="Ej. México, Francia, Japón">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cEstado" class="form-label fw-bold">Estado o Región</label>
                            <input type="text" class="form-control" id="cEstado" name="cEstado" maxlength="100" placeholder="Ej. Quintana Roo, Île-de-France">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cCiudad" class="form-label fw-bold">Ciudad</label>
                            <input type="text" class="form-control" id="cCiudad" name="cCiudad" maxlength="100" placeholder="Ej. Benito Juárez, París">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cDescripcion" class="form-label fw-bold">Descripción del Destino</label>
                        <textarea class="form-control" id="cDescripcion" name="cDescripcion" rows="3" placeholder="Información destacada del destino turístico..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i> Guardar Destino
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
