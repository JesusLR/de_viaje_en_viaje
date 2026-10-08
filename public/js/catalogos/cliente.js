$(document).ready(function () {
    // 1. Configuración global CSRF Token para AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaClientes = null;
    let modalCliente = new bootstrap.Modal(document.getElementById('modalCliente'));

    // 2. Inicialización de DataTables AJAX
    function initTabla() {
        tablaClientes = $('#tablaClientes').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            ajax: {
                url: '/catalogos/clientes/gridData',
                type: 'GET',
                dataSrc: function (json) {
                    if (json.lSuccess) {
                        return json.cData;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar',
                            text: json.cMensaje || 'No se pudieron cargar los clientes.'
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
                        return data !== 'N/A' && data ? `<span class="text-primary"><i class="fas fa-envelope me-1"></i>${data}</span>` : '<span class="text-muted">N/A</span>';
                    }
                },
                { 
                    data: 'cRFC',
                    render: function (data) {
                        return data !== 'N/A' && data ? `<span class="badge bg-light text-dark border">${data}</span>` : '<span class="text-muted">N/A</span>';
                    }
                },
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

    // 3. Resetear formulario para Nuevo Cliente (iID = 0)
    $('#btnNuevo').on('click', function () {
        $('#formCliente')[0].reset();
        $('#iID').val(0);
        $('#modalClienteTitle').text('Nuevo Cliente');
        modalCliente.show();
    });

    // 4. Cargar datos para Editar (getData)
    $('#tablaClientes').on('click', '.btn-editar', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/catalogos/clientes/getData/${iID}`,
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
                    $('#cRFC').val(oData.cRFC);
                    $('#cDireccion').val(oData.cDireccion);
                    $('#modalClienteTitle').text('Editar Cliente');
                    modalCliente.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.cMensaje
                    });
                }
            },
            error: function (xhr) {
                let cMensaje = xhr.responseJSON ? xhr.responseJSON.cMensaje : 'Error al obtener los datos del cliente.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: cMensaje
                });
            }
        });
    });

    // 5. Enviar Guardar / Actualizar (saveData)
    $('#formCliente').on('submit', function (e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({
            url: '/catalogos/clientes/saveData',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
            },
            success: function (response) {
                if (response.lSuccess) {
                    modalCliente.hide();
                    tablaClientes.ajax.reload(null, false);
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
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Cliente');
            }
        });
    });

    // 6. Eliminar / Desactivar Lógicamente (deleteData)
    $('#tablaClientes').on('click', '.btn-eliminar', function () {
        let iID = $(this).data('id');

        Swal.fire({
            title: '¿Está seguro?',
            text: 'El cliente será deshabilitado del catálogo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, deshabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/catalogos/clientes/deleteData',
                    type: 'POST',
                    data: { iID: iID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.lSuccess) {
                            tablaClientes.ajax.reload(null, false);
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
                        let cMensaje = xhr.responseJSON ? xhr.responseJSON.cMensaje : 'Error al eliminar el cliente.';
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
