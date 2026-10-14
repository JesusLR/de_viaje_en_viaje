$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaProveedores = null;
    let modalProveedor = new bootstrap.Modal(document.getElementById('modalProveedor'));

    function initTabla() {
        tablaProveedores = $('#tablaProveedores').DataTable({
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
                url: '/catalogos/proveedores/gridData',
                type: 'GET',
                dataSrc: function (json) {
                    if (json.lSuccess) {
                        return json.cData;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de carga',
                            text: json.cMensaje || 'No se pudieron cargar los proveedores.'
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
                { data: 'cDescripcion' },
                { 
                    data: 'iTotalServicios',
                    render: function (data) {
                        return `<span class="apartados-contador">${data} servicios</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-end catalogo-acciones',
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="apartado-icono btn-editar" data-permission="proveedores.editar" data-id="${row.iID}" title="Editar" aria-label="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="apartado-icono accion-cancelar btn-eliminar" data-permission="proveedores.activar" data-id="${row.iID}" title="Desactivar" aria-label="Desactivar">
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
        tablaProveedores.search(this.value).draw();
    });

    $('#btnNuevo').on('click', function () {
        $('#formProveedor')[0].reset();
        $('#iID').val(0);
        $('#modalProveedorTitle').text('Nuevo Proveedor Turístico');
        modalProveedor.show();
    });

    $('#tablaProveedores').on('click', '.btn-editar', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/catalogos/proveedores/getData/${iID}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.lSuccess) {
                    let oData = response.cData;
                    $('#iID').val(oData.iID);
                    $('#cNombre').val(oData.cNombre);
                    $('#cDescripcion').val(oData.cDescripcion);
                    $('#modalProveedorTitle').text('Editar Proveedor Turístico');
                    modalProveedor.show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            }
        });
    });

    $('#formProveedor').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '/catalogos/proveedores/saveData',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
            },
            success: function (response) {
                if (response.lSuccess) {
                    modalProveedor.hide();
                    tablaProveedores.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: response.cMensaje });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            },
            error: function (xhr) {
                let cMensaje = xhr.responseJSON && xhr.responseJSON.cMensaje ? xhr.responseJSON.cMensaje : 'Error al guardar el proveedor.';
                Swal.fire({ icon: 'error', title: 'Error', text: cMensaje });
            },
            complete: function () {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Proveedor');
            }
        });
    });

    $('#tablaProveedores').on('click', '.btn-eliminar', function () {
        let iID = $(this).data('id');

        Swal.fire({
            title: '¿Desactivar proveedor?',
            text: 'El proveedor quedará inactivo para nuevos registros.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/catalogos/proveedores/deleteData',
                    type: 'POST',
                    data: { iID: iID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.lSuccess) {
                            tablaProveedores.ajax.reload(null, false);
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
