@extends('layouts.auth')

@section('titulo', 'Cambio Obligatorio de Contraseña')
@section('auth-page-class', 'auth-login auth-password')

@section('contenido')
<div class="auth-card">
    <div class="auth-header">
        <div class="brand-icon password-emblema">
            <i class="fas fa-shield-alt fs-2" aria-hidden="true"></i>
        </div>
        <h4 class="fw-bold mb-1">Actualiza tu Contraseña</h4>
        <p class="text-muted small mb-0">Por tu seguridad, debes cambiar tu contraseña inicial</p>
    </div>

    <div class="auth-body">
        <div class="password-aviso mb-4">
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
                           autocomplete="current-password"
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
                           autocomplete="new-password"
                           minlength="6"
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
                           autocomplete="new-password"
                           minlength="6"
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
        <form id="formSalirPassword" action="{{ route('logout') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="btn password-salir w-100"><i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Cerrar sesión</button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/login-module.css') }}?v={{ filemtime(public_path('css/login-module.css')) }}">
<style>
.auth-password .auth-header .password-emblema{width:56px;height:56px;background:#edf1ff;border:0;border-radius:16px;color:#536fe8}
.auth-password .password-aviso{padding:14px 16px;background:#f8fafc;border:1px solid #e8edf4;border-radius:12px;color:#64748b;font-size:12px;line-height:1.7}
.auth-password .password-aviso i{color:#536fe8}
.auth-password .password-salir{background:#f3f5fa;color:#64748b;border-radius:10px;min-height:44px;font-size:13px;font-weight:600}
.auth-password .password-salir:hover{background:#eaf0ff;color:#536fe8}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    $('#formSalirPassword').on('submit', function (event) {
        event.preventDefault();
        const form = this;
        Swal.fire({
            icon: 'question', title: '¿Deseas cerrar sesión?',
            text: 'Volverás a la pantalla de inicio de sesión.',
            showCancelButton: true, confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar', confirmButtonColor: '#536fe8'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
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
