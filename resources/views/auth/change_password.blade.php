@extends('layouts.auth')

@section('titulo', 'Cambio Obligatorio de Contraseña')

@section('contenido')
<div class="auth-card">
    <div class="auth-header bg-warning bg-gradient text-dark">
        <div class="brand-icon bg-white bg-opacity-50 text-warning">
            <i class="fas fa-shield-alt fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1 text-dark">Actualiza tu Contraseña</h4>
        <p class="text-dark-50 small mb-0">Por tu seguridad, debes cambiar tu contraseña inicial</p>
    </div>

    <div class="auth-body">
        <div class="alert alert-info rounded-3 small mb-4">
            <i class="fas fa-info-circle me-1"></i>
            Has ingresado con una contraseña inicial o autogenerada. Por favor define una nueva clave segura.
        </div>

        <form id="formChangePassword" action="{{ route('password.update') }}" method="POST">
            @csrf

            <!-- Contraseña Actual -->
            <div class="mb-3">
                <label for="cPasswordActual" class="form-label fw-semibold text-secondary small">Contraseña Actual <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="cPasswordActual" 
                           name="cPasswordActual" 
                           placeholder="Ingresa tu contraseña actual" 
                           required 
                           autofocus>
                </div>
            </div>

            <!-- Nueva Contraseña -->
            <div class="mb-3">
                <label for="cPasswordNueva" class="form-label fw-semibold text-secondary small">Nueva Contraseña <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="cPasswordNueva" 
                           name="cPasswordNueva" 
                           placeholder="Mínimo 6 caracteres" 
                           required>
                </div>
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div class="mb-4">
                <label for="cPasswordConfirm" class="form-label fw-semibold text-secondary small">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="cPasswordConfirm" 
                           name="cPasswordConfirm" 
                           placeholder="Repite la nueva contraseña" 
                           required>
                </div>
            </div>

            <!-- Botón de Guardar -->
            <button type="submit" class="btn btn-primary-custom" id="btnCambiar">
                <span id="btnText"><i class="fas fa-save me-2"></i>Actualizar Contraseña</span>
                <span id="btnSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Guardando...
                </span>
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    $('#formChangePassword').on('submit', function (e) {
        e.preventDefault();

        let $form = $(this);
        let $btn = $('#btnCambiar');
        let $btnText = $('#btnText');
        let $btnSpinner = $('#btnSpinner');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            beforeSend: function () {
                $btn.prop('disabled', true);
                $btnText.addClass('d-none');
                $btnSpinner.removeClass('d-none');
            },
            success: function (response) {
                if (response.lSuccess) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Contraseña Actualizada!',
                        text: response.cMensaje,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.redirect || '/dashboard';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.cMensaje
                    });
                    resetBoton();
                }
            },
            error: function (xhr) {
                resetBoton();
                let cMensaje = 'Ocurrió un error al procesar la solicitud.';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        let aErrores = [];
                        $.each(xhr.responseJSON.errors, function (key, messages) {
                            aErrores.push(messages[0]);
                        });
                        cMensaje = aErrores.join('<br>');
                    } else if (xhr.responseJSON.cMensaje) {
                        cMensaje = xhr.responseJSON.cMensaje;
                    }
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validacion',
                    html: cMensaje
                });
            }
        });

        function resetBoton() {
            $btn.prop('disabled', false);
            $btnText.removeClass('d-none');
            $btnSpinner.addClass('d-none');
        }
    });
});
</script>
@endpush
