$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaCategorias = null;
    let modalCategoria = new bootstrap.Modal(document.getElementById('modalCategoria'));

    function initTabla() {
        tablaCategorias = $('#tablaCategorias').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            ajax: {
                url: '/catalogos/categorias/gridData',
                type: 'GET',
                dataSrc: function (json) {
                    if (json.lSuccess) {
                        return json.cData;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de carga',
                            text: json.cMensaje || 'No se pudieron cargar las categorías.'
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
                        return `<span class="badge bg-info text-dark">${data} servicios</span>`;
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

    $('#btnNuevo').on('click', function () {
        $('#formCategoria')[0].reset();
        $('#iID').val(0);
        $('#modalCategoriaTitle').text('Nueva Categoría Turística');
        modalCategoria.show();
    });

    $('#tablaCategorias').on('click', '.btn-editar', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/catalogos/categorias/getData/${iID}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.lSuccess) {
                    let oData = response.cData;
                    $('#iID').val(oData.iID);
                    $('#cNombre').val(oData.cNombre);
                    $('#cDescripcion').val(oData.cDescripcion);
                    $('#modalCategoriaTitle').text('Editar Categoría Turística');
                    modalCategoria.show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            }
        });
    });

    $('#formCategoria').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '/catalogos/categorias/saveData',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
            },
            success: function (response) {
                if (response.lSuccess) {
                    modalCategoria.hide();
                    tablaCategorias.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: response.cMensaje });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            },
            error: function (xhr) {
                let cMensaje = xhr.responseJSON && xhr.responseJSON.cMensaje ? xhr.responseJSON.cMensaje : 'Error al guardar la categoría.';
                Swal.fire({ icon: 'error', title: 'Error', text: cMensaje });
            },
            complete: function () {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Categoría');
            }
        });
    });

    $('#tablaCategorias').on('click', '.btn-eliminar', function () {
        let iID = $(this).data('id');

        Swal.fire({
            title: '¿Desactivar categoría?',
            text: 'La categoría quedará inactiva para nuevos registros.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/catalogos/categorias/deleteData',
                    type: 'POST',
                    data: { iID: iID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.lSuccess) {
                            tablaCategorias.ajax.reload(null, false);
                            Swal.fire({ icon: 'success', title: 'Desactivada', text: response.cMensaje });
                        }
                    }
                });
            }
        });
    });
});
