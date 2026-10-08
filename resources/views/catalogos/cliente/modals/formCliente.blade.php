<div class="modal fade" id="modalCliente" tabindex="-1" aria-labelledby="modalClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteTitle">Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCliente">
                <div class="modal-body">
                    <!-- Control ID (0 = nuevo, > 0 = edición) -->
                    <input type="hidden" id="iID" name="iID" value="0">

                    <!-- Nombre y Apellidos -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="cNombre" class="form-label fw-bold">Nombre(s) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cNombre" 
                                   name="cNombre" 
                                   maxlength="100" 
                                   required 
                                   placeholder="Ej. Roberto"
                                   autocomplete="off">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cPrimerApellido" class="form-label fw-bold">Primer Apellido <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cPrimerApellido" 
                                   name="cPrimerApellido" 
                                   maxlength="100" 
                                   required 
                                   placeholder="Ej. Gómez"
                                   autocomplete="off">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cSegundoApellido" class="form-label fw-bold">Segundo Apellido <span class="text-muted fw-normal">(Opcional)</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cSegundoApellido" 
                                   name="cSegundoApellido" 
                                   maxlength="100" 
                                   placeholder="Ej. Bolaños"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- Datos de Contacto y RFC -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="cTelefono" class="form-label fw-bold">Teléfono de Contacto</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" 
                                       class="form-control" 
                                       id="cTelefono" 
                                       name="cTelefono" 
                                       maxlength="20" 
                                       placeholder="Ej. 5598765432"
                                       autocomplete="off">
                            </div>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label for="cEmail" class="form-label fw-bold">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" 
                                       class="form-control" 
                                       id="cEmail" 
                                       name="cEmail" 
                                       maxlength="150" 
                                       placeholder="cliente@ejemplo.com"
                                       autocomplete="off">
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="cRFC" class="form-label fw-bold">RFC / ID Fiscal</label>
                            <input type="text" 
                                   class="form-control text-uppercase" 
                                   id="cRFC" 
                                   name="cRFC" 
                                   maxlength="20" 
                                   placeholder="XAXX010101000"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="mb-3">
                        <label for="cDireccion" class="form-label fw-bold">Dirección / Domicilio</label>
                        <textarea class="form-control" 
                                  id="cDireccion" 
                                  name="cDireccion" 
                                  rows="2" 
                                  placeholder="Calle, Número, Colonia, Ciudad, Estado"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
