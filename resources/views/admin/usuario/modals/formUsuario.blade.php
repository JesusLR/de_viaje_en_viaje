<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUsuario">
                <div class="modal-body">
                    <!-- Control ID (0 = nuevo, > 0 = edición) -->
                    <input type="hidden" id="iID" name="iID" value="0">

                    <div class="row">
                        <!-- Nombre(s) -->
                        <div class="col-md-6 mb-3">
                            <label for="cNombre" class="form-label fw-bold">Nombre(s) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cNombre" 
                                   name="cNombre" 
                                   maxlength="100" 
                                   required 
                                   placeholder="Ej. Juan Carlos"
                                   autocomplete="off">
                        </div>

                        <!-- Primer Apellido -->
                        <div class="col-md-6 mb-3">
                            <label for="cPrimerApellido" class="form-label fw-bold">Primer Apellido <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cPrimerApellido" 
                                   name="cPrimerApellido" 
                                   maxlength="100" 
                                   required 
                                   placeholder="Ej. Pérez"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Segundo Apellido (Opcional) -->
                        <div class="col-md-6 mb-3">
                            <label for="cSegundoApellido" class="form-label fw-bold">Segundo Apellido <span class="text-muted fw-normal">(Opcional)</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cSegundoApellido" 
                                   name="cSegundoApellido" 
                                   maxlength="100" 
                                   placeholder="Ej. Lira"
                                   autocomplete="off">
                        </div>

                        <!-- Teléfono (Opcional) -->
                        <div class="col-md-6 mb-3">
                            <label for="cTelefono" class="form-label fw-bold">Número de Teléfono <span class="text-muted fw-normal">(Opcional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" 
                                       class="form-control" 
                                       id="cTelefono" 
                                       name="cTelefono" 
                                       maxlength="20" 
                                       placeholder="Ej. 5512345678"
                                       autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- Correo Electrónico (Generado Automáticamente) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="cEmail" class="form-label fw-bold mb-0">Correo Electrónico <span class="text-danger">*</span></label>
                            <span class="badge bg-info-subtle text-info border border-info-subtle" id="badgeAutoCorreo">
                                <i class="fas fa-magic me-1"></i> Auto-generado
                            </span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" 
                                   class="form-control" 
                                   id="cEmail" 
                                   name="cEmail" 
                                   maxlength="255" 
                                   required 
                                   placeholder="juan.perez@deviaje.com">
                        </div>
                        <small class="form-text text-muted">Se genera automáticamente en base al nombre y apellidos, pero puedes modificarlo si lo deseas.</small>
                    </div>

                    <!-- Contraseña -->
                    <div class="mb-3">
                        <label for="cPassword" class="form-label fw-bold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" 
                                   class="form-control" 
                                   id="cPassword" 
                                   name="cPassword" 
                                   placeholder="••••••••">
                        </div>
                        <small class="form-text text-muted" id="helpPassword">
                            En altas: Si se deja en blanco, la contraseña inicial será <strong>igual al correo electrónico del usuario</strong> y se le exigirá cambiarla al ingresar.<br>
                            En ediciones: Dejar en blanco para mantener la contraseña actual.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
