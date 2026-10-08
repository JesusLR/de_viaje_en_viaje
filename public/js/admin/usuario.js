$(document).ready(function () {
    // 1. Configuración global CSRF Token para AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaUsuarios = null;
    let modalUsuario = new bootstrap.Modal(document.getElementById('modalUsuario'));
    let emailEditadoManualmente = false;

    // Dominio corporativo predeterminado
    const DOMINIO_CORP = 'deviaje.com';

    // 2. Función para generar automáticamente el correo en base al PRIMER NOMBRE y PRIMER APELLIDO
    function generarCorreoDesdeCampos() {
        let iID = parseInt($('#iID').val(), 10);
        if (iID !== 0 || emailEditadoManualmente) return;

        let cNombreVal = ($('#cNombre').val() || '').trim();
        let cPrimerApeVal = ($('#cPrimerApellido').val() || '').trim();

        if (!cNombreVal && !cPrimerApeVal) {
            $('#cEmail').val('');
            return;
        }

        // Si tiene múltiples nombres (ej. "Juan Carlos"), tomar únicamente el primer nombre ("Juan")
        let aNombres = cNombreVal.split(/\s+/);
        let cPrimerNombre = aNombres.length > 0 ? aNombres[0] : '';

        // Tomar el primer apellido (ej. "Pérez")
        let cPrimerApellido = cPrimerApeVal;

        let cTextoCombinado = `${cPrimerNombre} ${cPrimerApellido}`.trim();

        // Normalizar acentos y diacríticos (ej. Pérez -> perez, Ñ -> n)
        let cLimpio = cTextoCombinado.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        cLimpio = cLimpio.replace(/ñ/g, 'n').replace(/Ñ/g, 'n');

        // Formatear a minúsculas y sustituir espacios por puntos
        let cSlug = cLimpio.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '.');

        if (cSlug) {
            $('#cEmail').val(`${cSlug}@${DOMINIO_CORP}`);
        }
    }

    // Escuchar eventos en los campos de nombre y primer apellido
    $('#cNombre, #cPrimerApellido').on('input keyup change', function () {
        generarCorreoDesdeCampos();
    });

    // Detectar edición manual de correo
    $('#cEmail').on('input', function () {
        emailEditadoManualmente = true;
    });

    // 3. Inicialización de DataTables AJAX
    function initTabla() {
        tablaUsuarios = $('#tablaUsuarios').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            ajax: {
                url: '/admin/usuarios/gridData',
                type: 'GET',
                dataSrc: function (json) {
                    if (json.lSuccess) {
                        return json.cData;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar',
                            text: json.cMensaje || 'No se pudieron cargar los usuarios.'
                        });
                        return [];
                    }
                }
            },
            columns: [
                { data: 'iID' },
                { 
                    data: 'cNombreCompleto',
                    render: function (data) {
                        return `<strong>${data}</strong>`;
                    }
                },
                { 
                    data: 'cTelefono',
                    render: function (data) {
                        return data !== 'N/A' && data ? `<i class="fas fa-phone-alt me-1 text-muted"></i>${data}` : '<span class="text-muted">N/A</span>';
                    }
                },
                { 
                    data: 'cEmail',
                    render: function (data) {
                        return `<span class="text-primary"><i class="fas fa-envelope me-1"></i>${data}</span>`;
                    }
                },
                { data: 'cCreado' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="btn btn-sm btn-outline-warning btn-editar me-1" data-id="${row.iID}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${row.iID}" title="Desactivar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                    }
                }
            ]
        });
    }

    initTabla();

    // 4. Resetear formulario para Nuevo Usuario (iID = 0)
    $('#btnNuevo').on('click', function () {
        $('#formUsuario')[0].reset();
        $('#iID').val(0);
        emailEditadoManualmente = false;
        $('#modalUsuarioTitle').text('Nuevo Usuario');
        modalUsuario.show();
    });

    // 5. Cargar datos para Editar (getData)
    $('#tablaUsuarios').on('click', '.btn-editar', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/admin/usuarios/getData/${iID}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.lSuccess) {
                    let oData = response.cData;
                    $('#iID').val(oData.iID);
                    $('#cNombre').val(oData.cNombre);
                    $('#cPrimerApellido').val(oData.cPrimerApellido);
                    $('#cSegundoApellido').val(oData.cSegundoApellido);
                    $('#cTelefono').val(oData.cTelefono);
                    $('#cEmail').val(oData.cEmail);
                    $('#cPassword').val('');
                    emailEditadoManualmente = true;
                    $('#modalUsuarioTitle').text('Editar Usuario');
                    modalUsuario.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.cMensaje
                    });
                }
            },
            error: function (xhr) {
                let cMensaje = xhr.responseJSON ? xhr.responseJSON.cMensaje : 'Error al obtener los datos del usuario.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: cMensaje
                });
            }
        });
    });

    // 6. Enviar Guardar / Actualizar (saveData)
    $('#formUsuario').on('submit', function (e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({
            url: '/admin/usuarios/saveData',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
            },
            success: function (response) {
                if (response.lSuccess) {
                    modalUsuario.hide();
                    tablaUsuarios.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: response.cMensaje
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.cMensaje
                    });
                }
            },
            error: function (xhr) {
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
                    title: 'Error de Validación',
                    html: cMensaje
                });
            },
            complete: function () {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Usuario');
            }
        });
    });

    // 7. Eliminar / Desactivar Lógicamente (deleteData)
    $('#tablaUsuarios').on('click', '.btn-eliminar', function () {
        let iID = $(this).data('id');

        Swal.fire({
            title: '¿Está seguro?',
            text: 'El usuario perderá el acceso al sistema.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, deshabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/usuarios/deleteData',
                    type: 'POST',
                    data: { iID: iID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.lSuccess) {
                            tablaUsuarios.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Deshabilitado',
                                text: response.cMensaje
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.cMensaje
                            });
                        }
                    },
                    error: function (xhr) {
                        let cMensaje = xhr.responseJSON ? xhr.responseJSON.cMensaje : 'Error al eliminar el usuario.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: cMensaje
                        });
                    }
                });
            }
        });
    });
});
