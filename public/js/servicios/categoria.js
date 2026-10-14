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
                        return `<span class="apartados-contador">${data} servicios</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-end catalogo-acciones',
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="apartado-icono btn-editar" data-permission="categorias.editar" data-id="${row.iID}" title="Editar" aria-label="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="apartado-icono accion-cancelar btn-eliminar" data-permission="categorias.activar" data-id="${row.iID}" title="Desactivar" aria-label="Desactivar">
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
        tablaCategorias.search(this.value).draw();
    });

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
