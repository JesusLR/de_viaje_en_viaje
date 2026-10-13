$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaDestinos = null;
    let modalDestino = new bootstrap.Modal(document.getElementById('modalDestino'));

    function initTabla() {
        tablaDestinos = $('#tablaDestinos').DataTable({
            pageLength: 10,
            lengthChange: false,
            dom: 'rt<"catalogos-pie"ip>',
            language: {
                emptyTable: 'No hay registros disponibles',
                zeroRecords: 'No se encontraron resultados',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 registros',
                infoFiltered: '(de _MAX_ registros)',
                loadingRecords: 'Cargando…',
                paginate: { previous: 'Anterior', next: 'Siguiente' }
            },
            ajax: {
                url: '/catalogos/destinos/gridData',
                type: 'GET',
                dataSrc: function (json) {
                    if (json.lSuccess) {
                        return json.cData;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de carga',
                            text: json.cMensaje || 'No se pudieron cargar los destinos turísticos.'
                        });
                        return [];
                    }
                }
            },
            columns: [
                { data: 'iID' },
                {
                    data: 'cNombre',
                    render: function (data) {
                        return `<strong>${data}</strong>`;
                    }
                },
                { data: 'cPais' },
                { data: 'cEstado' },
                { data: 'cCiudad' },
                {
                    data: 'iTotalServicios',
                    render: function (data) {
                        return `<span class="apartados-contador">${data} servicios</span>`;
                    }
                },
                {
                    data: 'lActivo',
                    render: function (data) {
                        return data == 1
                            ? '<span class="apartado-estado liquidado">Activo</span>'
                            : '<span class="apartado-estado cancelado">Inactivo</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-end catalogo-acciones',
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="apartado-icono btn-editar" data-id="${row.iID}" title="Editar" aria-label="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="apartado-icono accion-cancelar btn-eliminar" data-id="${row.iID}" title="Desactivar" aria-label="Desactivar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                    }
                }
            ]
        });
    }

    initTabla();
    $('#buscarCatalogo').on('input', function () {
        tablaDestinos.search(this.value).draw();
    });

    $('#btnNuevo').on('click', function () {
        $('#formDestino')[0].reset();
        $('#iID').val(0);
        $('#modalDestinoTitle').text('Nuevo Destino Turístico');
        modalDestino.show();
    });

    $('#tablaDestinos').on('click', '.btn-editar', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/catalogos/destinos/getData/${iID}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.lSuccess) {
                    let oData = response.cData;
                    $('#iID').val(oData.iID);
                    $('#cNombre').val(oData.cNombre);
                    $('#cPais').val(oData.cPais);
                    $('#cEstado').val(oData.cEstado);
                    $('#cCiudad').val(oData.cCiudad);
                    $('#cDescripcion').val(oData.cDescripcion);
                    $('#modalDestinoTitle').text('Editar Destino Turístico');
                    modalDestino.show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            }
        });
    });

    $('#formDestino').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '/catalogos/destinos/saveData',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
            },
            success: function (response) {
                if (response.lSuccess) {
                    modalDestino.hide();
                    tablaDestinos.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: response.cMensaje });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            },
            error: function (xhr) {
                let cMensaje = xhr.responseJSON && xhr.responseJSON.cMensaje ? xhr.responseJSON.cMensaje : 'Error al guardar el destino.';
                Swal.fire({ icon: 'error', title: 'Error', text: cMensaje });
            },
            complete: function () {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Destino');
            }
        });
    });

    $('#tablaDestinos').on('click', '.btn-eliminar', function () {
        let iID = $(this).data('id');

        Swal.fire({
            title: '¿Desactivar destino?',
            text: 'El destino quedará inactivo pero conservará la información histórica de sus servicios.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/catalogos/destinos/deleteData',
                    type: 'POST',
                    data: { iID: iID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.lSuccess) {
                            tablaDestinos.ajax.reload(null, false);
                            Swal.fire({ icon: 'success', title: 'Desactivado', text: response.cMensaje });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                        }
                    }
                });
            }
        });
    });
});
