@if(auth()->user()->hasPermission(($moduloWhatsapp ?? ($apartados ? 'apartados' : 'ventas')).'.whatsapp') && auth()->user()->hasPermission(($moduloWhatsapp ?? ($apartados ? 'apartados' : 'ventas')).'.pdf'))
<div class="modal fade apartado-modal-shell" id="whatsappApartado" tabindex="-1" aria-labelledby="whatsappApartadoTitulo" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content venta-detalle-page">
    <div class="modal-header"><h2 class="modal-title h5" id="whatsappApartadoTitulo"><i class="fab fa-whatsapp me-2" aria-hidden="true"></i>Enviar documento por WhatsApp</h2><button type="button" class="apartado-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form id="formWhatsappApartado"><div class="modal-body">
        <p id="whatsappApartadoDocumento" class="fw-semibold mb-1"></p><p id="whatsappApartadoCliente" class="text-muted"></p>
        <div id="whatsappApartadoEstado" class="text-muted mb-3" role="status" aria-live="polite">Consultando las sesiones conectadas…</div>
        <div id="whatsappApartadoError" class="alert alert-danger d-none" role="alert"></div>
        <label for="whatsappApartadoTelefono" class="form-label fw-semibold">Número de WhatsApp del destinatario</label>
        <input type="tel" inputmode="tel" id="whatsappApartadoTelefono" name="telefono" class="form-control" required maxlength="50" placeholder="Ej. +52 999 123 4567" autocomplete="off">
        <p class="form-text">Confirma o cambia el número antes de enviar. México: 10 dígitos, con o sin +52. Otros países: incluye + y el código de país. El cambio solo aplica a este envío.</p>

    </div><div class="modal-footer"><button type="button" class="apartado-icono" data-bs-dismiss="modal" title="Cancelar" aria-label="Cancelar"><i class="fas fa-xmark" aria-hidden="true"></i></button><button type="submit" id="whatsappApartadoEnviar" class="btn btn-primary" disabled><i class="fab fa-whatsapp me-1" aria-hidden="true"></i>Confirmar envío</button></div></form>
</div></div></div>
@endif
