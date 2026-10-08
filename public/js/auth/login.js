$(document).ready(function () {
    // 1. Configuración global del Token CSRF en AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    // 2. Toggle Ocultar/Mostrar Contraseña
    $('#togglePassword').on('click', function () {
        const passwordInput = $('#password');
        const eyeIcon = $('#eyeIcon');
        const isPassword = passwordInput.attr('type') === 'password';

        passwordInput.attr('type', isPassword ? 'text' : 'password');
        eyeIcon.toggleClass('fa-eye fa-eye-slash');
    });

    // 3. Manejador de Formulario de Login vía AJAX
    $('#formLogin').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $('#btnLogin');
        const $btnText = $('#btnText');
        const $btnSpinner = $('#btnSpinner');
        const $alertContainer = $('#alertContainer');

        // Limpiar errores visuales previos
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $alertContainer.empty();

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
                        title: '¡Acceso Correcto!',
                        text: response.cMensaje || 'Iniciando sesión...',
                        timer: 1500,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = response.redirect || '/dashboard';
                    });
                } else {
                    mostrarError(response.cMensaje || 'Credenciales no válidas.');
                    resetBoton();
                }
            },
            error: function (xhr) {
                resetBoton();

                if (xhr.status === 422 && xhr.responseJSON) {
                    const response = xhr.responseJSON;

                    // Si hay mensaje directo
                    if (response.cMensaje) {
                        mostrarError(response.cMensaje);
                    }

                    // Si hay errores de validación por campo
                    if (response.errors) {
                        $.each(response.errors, function (field, messages) {
                            const $input = $('#' + field);
                            $input.addClass('is-invalid');
                            $('.id-error-' + field).text(messages[0]);
                        });
                    }
                } else {
                    const errorMsg = xhr.responseJSON && xhr.responseJSON.cMensaje 
                        ? xhr.responseJSON.cMensaje 
                        : 'Ocurrió un error inesperado al intentar iniciar sesión.';
                    mostrarError(errorMsg);
                }
            }
        });

        function resetBoton() {
            $btn.prop('disabled', false);
            $btnText.removeClass('d-none');
            $btnSpinner.addClass('d-none');
        }

        function mostrarError(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Autenticación',
                text: mensaje,
                confirmButtonColor: '#2563eb'
            });

            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show rounded-3 small mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $alertContainer.html(alertHtml);
        }
    });
});
