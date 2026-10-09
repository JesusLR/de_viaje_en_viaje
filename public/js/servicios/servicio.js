$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaServicios = null;
    let modalServicio = new bootstrap.Modal(document.getElementById('modalServicio'));
    let modalDetalleServicio = new bootstrap.Modal(document.getElementById('modalDetalleServicio'));

    // Inicializar DataTables con Filtros Dinámicos
    function initTabla() {
        tablaServicios = $('#tablaServicios').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            serverSide: false,
            ajax: {
                url: '/servicios/paquetes/gridData',
                type: 'GET',
                data: function (d) {
                    d.iIDCategoria   = $('#filtroCategoria').val();
                    d.iIDDestino     = $('#filtroDestino').val();
                    d.cEstatusFiltro = $('#filtroEstatus').val();
                    d.lConPromocion  = $('#filtroPromocion').val();
                },
                dataSrc: function (json) {
                    if (json.lSuccess) {
                        return json.cData;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de carga',
                            text: json.cMensaje || 'No se pudieron cargar los servicios turísticos.'
                        });
                        return [];
                    }
                }
            },
            columns: [
                { 
                    data: 'cCodigo',
                    render: function (data) {
                        return `<span class="badge bg-dark font-monospace fs-6">${data}</span>`;
                    }
                },
                { 
                    data: 'cImagenPrincipal',
                    orderable: false,
                    render: function (data) {
                        if (data) {
                            return `<img src="${data}" class="rounded shadow-sm" style="width: 50px; height: 40px; object-fit: cover;">`;
                        }
                        return `<div class="bg-light rounded text-center text-muted py-2" style="width: 50px; font-size: 0.75rem;"><i class="fas fa-image"></i></div>`;
                    }
                },
                { 
                    data: 'cNombre',
                    render: function (data, type, row) {
                        let promoBadge = row.lTienePromocion && row.lPromocionVigente
                            ? '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-percentage me-1"></i>PROMO</span>' 
                            : '';
                        return `<strong>${data}</strong> ${promoBadge}`;
                    }
                },
                { data: 'cCategoria' },
                { data: 'cDestino' },
                { data: 'cProveedor' },
                { 
                    data: 'cEstatusDisponibilidad',
                    render: function (data) {
                        if (data === 'ACTIVO') return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>';
                        if (data === 'PROGRAMADO') return '<span class="badge bg-info text-dark"><i class="fas fa-clock me-1"></i>Programado</span>';
                        if (data === 'VENCIDO') return '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Vencido</span>';
                        return '<span class="badge bg-secondary"><i class="fas fa-minus-circle me-1"></i>Inactivo</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="btn btn-sm btn-outline-info btn-detalle me-1" data-id="${row.iID}" title="Ver Detalle Completo">
                                <i class="fas fa-eye"></i>
                            </button>
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

    // Eventos para refrescar tabla cuando cambia un filtro
    $('#filtroCategoria, #filtroDestino, #filtroEstatus, #filtroPromocion').on('change', function () {
        tablaServicios.ajax.reload();
    });

    // Ver Detalle Completo del Servicio
    $('#tablaServicios').on('click', '.btn-detalle', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/servicios/paquetes/getData/${iID}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.lSuccess) {
                    let oData = response.cData;

                    $('#detCodigo').text(oData.cCodigo);
                    $('#detNombre').text(oData.cNombre);
                    
                    // Categoría, Destino, Proveedor
                    $('#detCategoria').text($('#tablaServicios').DataTable().row(function (idx, data) { return data.iID === oData.iID; }).data()?.cCategoria || 'N/A');
                    $('#detDestino').text($('#tablaServicios').DataTable().row(function (idx, data) { return data.iID === oData.iID; }).data()?.cDestino || 'N/A');
                    $('#detProveedor').text($('#tablaServicios').DataTable().row(function (idx, data) { return data.iID === oData.iID; }).data()?.cProveedor || 'N/A');

                    // Precios y Comerciales
                    let rowData = $('#tablaServicios').DataTable().row(function (idx, data) { return data.iID === oData.iID; }).data();
                    if (rowData) {
                        $('#detPrecioCompra').text(`$${rowData.dPrecioCompra} ${oData.cMoneda || 'MXN'}`);
                        $('#detPrecioVenta').text(`$${rowData.dPrecioVenta} ${oData.cMoneda || 'MXN'}`);
                        $('#detPrecioFinal').text(`$${rowData.dPrecioFinal} ${oData.cMoneda || 'MXN'}`);
                        $('#detGananciaEfectiva').text(`+$${rowData.dGananciaEfectiva}`);
                        $('#detUtilidad').text(`${rowData.dPorcentajeUtilidad}% de utilidad sobre costo`);

                        // Estatus Badge
                        let st = rowData.cEstatusDisponibilidad;
                        let stBadge = '<span class="badge bg-secondary fs-6">Inactivo</span>';
                        if (st === 'ACTIVO') stBadge = '<span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Activo</span>';
                        if (st === 'PROGRAMADO') stBadge = '<span class="badge bg-info text-dark fs-6"><i class="fas fa-clock me-1"></i>Programado</span>';
                        if (st === 'VENCIDO') stBadge = '<span class="badge bg-warning text-dark fs-6"><i class="fas fa-exclamation-triangle me-1"></i>Vencido</span>';
                        $('#detEstatusBadge').html(stBadge);
                    }

                    // Helper para formatear fecha YYYY-MM-DD a dd/mm/YYYY
                    function formatFechaDMY(fechaStr) {
                        if (!fechaStr || fechaStr === 'N/A' || fechaStr === '') return 'N/A';
                        let cleanDate = fechaStr.split('T')[0].trim();
                        let parts = cleanDate.split('-');
                        if (parts.length === 3) {
                            return `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                        return fechaStr;
                    }

                    // Promoción
                    if (oData.lTienePromocion) {
                        $('#detContainerPromo').removeClass('d-none');
                        $('#detNombrePromo').text(oData.cNombrePromocion || 'Promoción Especial');
                        $('#detDescPromo').text(oData.cDescripcionPromocion || 'Sin detalle');
                        let tipoLabel = oData.cTipoDescuento === 'PORCENTAJE' ? `${oData.dValorDescuento}%` : `$${oData.dValorDescuento}`;
                        $('#detValorDescuento').text(tipoLabel);
                        
                        let fIni = formatFechaDMY(oData.dFechaInicioPromocion);
                        let fFin = formatFechaDMY(oData.dFechaFinPromocion);
                        $('#detFechasPromo').text(`${fIni} al ${fFin}`);
                    } else {
                        $('#detContainerPromo').addClass('d-none');
                    }

                    // Vigencia
                    $('#detFechaInicioVigencia').text(formatFechaDMY(oData.dFechaInicioVigencia));
                    $('#detFechaFinVigencia').text(formatFechaDMY(oData.dFechaFinVigencia));

                    // Descripciones
                    $('#detDescCorta').text(oData.cDescripcionCorta || 'Sin descripción corta.');
                    $('#detDescCompleta').html(oData.cDescripcionCompleta ? oData.cDescripcionCompleta.replace(/\n/g, '<br>') : '<em>Sin descripción detallada.</em>');

                    // Galería
                    let $galeria = $('#detGaleriaContainer').empty();
                    if (oData.cImagenPrincipal) {
                        $galeria.append(`
                            <div class="position-relative">
                                <img src="${oData.cImagenPrincipal}" class="img-thumbnail rounded shadow-sm" style="height: 100px; object-fit: cover;">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-1">Principal</span>
                            </div>
                        `);
                    }
                    if (oData.aGaleria && oData.aGaleria.length > 0) {
                        oData.aGaleria.forEach(img => {
                            $galeria.append(`
                                <img src="${img.cRuta}" class="img-thumbnail rounded shadow-sm" style="height: 100px; object-fit: cover;">
                            `);
                        });
                    }
                    if (!oData.cImagenPrincipal && (!oData.aGaleria || oData.aGaleria.length === 0)) {
                        $galeria.html('<span class="text-muted">No se han cargado imágenes para este servicio.</span>');
                    }

                    modalDetalleServicio.show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            }
        });
    });

    // Mostrar / Ocultar contenedor de promociones en modal formulario
    $('#lTienePromocion').on('change', function () {
        if ($(this).is(':checked')) {
            $('#containerPromocion').removeClass('d-none');
        } else {
            $('#containerPromocion').addClass('d-none');
        }
        ejecutarSimulacionPrecios();
    });

    // Cálculo dinámico de precios y utilidades en tiempo real
    $('.calc-trigger').on('input change', function () {
        ejecutarSimulacionPrecios();
    });

    function ejecutarSimulacionPrecios() {
        let dCompra = parseFloat($('#dPrecioCompra').val()) || 0;
        let dVenta = parseFloat($('#dPrecioVenta').val()) || 0;
        let lPromo = $('#lTienePromocion').is(':checked') ? 1 : 0;
        let cTipo = $('#cTipoDescuento').val();
        let dValor = parseFloat($('#dValorDescuento').val()) || 0;
        let dIni = $('#dFechaInicioPromocion').val();
        let dFin = $('#dFechaFinPromocion').val();

        $.ajax({
            url: '/servicios/paquetes/simularPrecios',
            type: 'POST',
            data: {
                dPrecioCompra: dCompra,
                dPrecioVenta: dVenta,
                lTienePromocion: lPromo,
                cTipoDescuento: cTipo,
                dValorDescuento: dValor,
                dFechaInicioPromocion: dIni,
                dFechaFinPromocion: dFin
            },
            dataType: 'json',
            success: function (res) {
                if (res.lSuccess) {
                    let d = res.cData;
                    let cMoneda = $('#cMoneda').val() || 'MXN';
                    let dGananciaNormal = (d.dGananciaNormal ?? d.dGananciaRegular ?? 0).toFixed(2);
                    let dUtilidad = (d.dPorcentajeUtilidad ?? d.dUtilidadRegular ?? 0).toFixed(2);
                    let dPrecioFinal = (d.dPrecioFinalPromocional ?? d.dPrecioFinal ?? 0).toFixed(2);
                    let dGananciaEfectiva = (d.dGananciaEfectiva ?? 0).toFixed(2);

                    $('#previewGanancia').text(`$${dGananciaNormal} ${cMoneda}`);
                    $('#previewUtilidad').text(`${dUtilidad}%`);
                    $('#previewPrecioFinal').text(`$${dPrecioFinal} ${cMoneda}`);
                    $('#previewGananciaFinal').text(`$${dGananciaEfectiva} ${cMoneda}`);
                }
            }
        });
    }

    // Previsualización de Imagen Principal
    $('#cImagenFile').on('change', function (e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (evt) {
                $('#imgPreviewPrincipal').attr('src', evt.target.result);
                $('#previewPrincipalContainer').removeClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    // Previsualización de Galería de Imágenes
    $('#cGaleriaFiles').on('change', function (e) {
        let files = e.target.files;
        let $container = $('#galeriaPreviewContainer').empty();

        if (files && files.length > 0) {
            Array.from(files).forEach(file => {
                let reader = new FileReader();
                reader.onload = function (evt) {
                    $container.append(`
                        <img src="${evt.target.result}" class="img-thumbnail rounded shadow-sm" style="width: 70px; height: 60px; object-fit: cover;">
                    `);
                };
                reader.readAsDataURL(file);
            });
        }
    });

    // Abrir Modal para Nuevo Servicio
    $('#btnNuevo').on('click', function () {
        $('#formServicio')[0].reset();
        $('#iID').val(0);
        $('#iIDProveedor').val('');

        let hoy = new Date().toISOString().split('T')[0];
        $('#dFechaInicioVigencia').val(hoy);
        $('#dFechaFinVigencia').val(hoy);
        $('#dFechaInicioPromocion').val(hoy);
        $('#dFechaFinPromocion').val(hoy);

        $('#colCodigoContainer').hide();
        $('#lTienePromocion').prop('checked', false).trigger('change');
        $('#lActivo').prop('checked', true);
        $('#lPromocionActiva').prop('checked', true);
        $('#previewPrincipalContainer').addClass('d-none');
        $('#galeriaPreviewContainer').empty();
        $('#modalServicioTitle').html('<i class="fas fa-umbrella-beach me-2"></i> Nuevo Servicio / Paquete Turístico');
        
        // Activar primera pestaña
        $('#general-tab').tab('show');
        
        ejecutarSimulacionPrecios();
        modalServicio.show();
    });

    // Editar Servicio Existente
    $('#tablaServicios').on('click', '.btn-editar', function () {
        let iID = $(this).data('id');

        $.ajax({
            url: `/servicios/paquetes/getData/${iID}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.lSuccess) {
                    let oData = response.cData;
                    $('#iID').val(oData.iID);
                    $('#cCodigo').val(oData.cCodigo);
                    $('#colCodigoContainer').show();
                    $('#cNombre').val(oData.cNombre);
                    $('#cDescripcionCorta').val(oData.cDescripcionCorta);
                    $('#cDescripcionCompleta').val(oData.cDescripcionCompleta);
                    $('#iIDCategoria').val(oData.iIDCategoria);
                    $('#iIDDestino').val(oData.iIDDestino);
                    $('#iIDProveedor').val(oData.iIDProveedor || '');
                    $('#dPrecioCompra').val(oData.dPrecioCompra);
                    $('#dPrecioVenta').val(oData.dPrecioVenta);
                    $('#cMoneda').val(oData.cMoneda || 'MXN');

                    // Promociones
                    $('#lTienePromocion').prop('checked', oData.lTienePromocion == 1).trigger('change');
                    $('#cNombrePromocion').val(oData.cNombrePromocion);
                    $('#cDescripcionPromocion').val(oData.cDescripcionPromocion);
                    $('#cTipoDescuento').val(oData.cTipoDescuento || 'PORCENTAJE');
                    $('#dValorDescuento').val(oData.dValorDescuento);
                    $('#dFechaInicioPromocion').val(oData.dFechaInicioPromocion);
                    $('#dFechaFinPromocion').val(oData.dFechaFinPromocion);
                    $('#lPromocionActiva').prop('checked', oData.lPromocionActiva == 1);

                    // Vigencia y Estatus
                    $('#dFechaInicioVigencia').val(oData.dFechaInicioVigencia);
                    $('#dFechaFinVigencia').val(oData.dFechaFinVigencia);
                    $('#lActivo').prop('checked', oData.lActivo == 1);

                    // Imágenes
                    if (oData.cImagenPrincipal) {
                        $('#imgPreviewPrincipal').attr('src', oData.cImagenPrincipal);
                        $('#previewPrincipalContainer').removeClass('d-none');
                    } else {
                        $('#previewPrincipalContainer').addClass('d-none');
                    }

                    // Galería existente
                    let $container = $('#galeriaPreviewContainer').empty();
                    if (oData.aGaleria && oData.aGaleria.length > 0) {
                        oData.aGaleria.forEach(img => {
                            $container.append(`
                                <img src="${img.cRuta}" class="img-thumbnail rounded shadow-sm" style="width: 70px; height: 60px; object-fit: cover;">
                            `);
                        });
                    }

                    $('#modalServicioTitle').html(`<i class="fas fa-edit me-2"></i> Editar Servicio ${oData.cCodigo}`);
                    $('#general-tab').tab('show');

                    ejecutarSimulacionPrecios();
                    modalServicio.show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                }
            }
        });
    });

    // Guardar Servicio (Submit Form AJAX con FormData)
    $('#formServicio').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url: '/servicios/paquetes/saveData',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
            },
            success: function (response) {
                if (response.lSuccess) {
                    modalServicio.hide();
                    tablaServicios.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: '¡Operación Exitosa!', text: response.cMensaje });
                } else {
                    Swal.fire({ icon: 'error', title: 'Atención', text: response.cMensaje });
                }
            },
            error: function (xhr) {
                let cMensaje = 'Error al guardar el servicio.';
                if (xhr.responseJSON && xhr.responseJSON.cMensaje) {
                    cMensaje = xhr.responseJSON.cMensaje;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let firstErr = Object.values(xhr.responseJSON.errors)[0][0];
                    cMensaje = firstErr;
                }
                Swal.fire({ icon: 'error', title: 'Error de Validación', text: cMensaje });
            },
            complete: function () {
                $('#btnGuardar').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Servicio');
            }
        });
    });

    // Desactivar Servicio Turístico
    $('#tablaServicios').on('click', '.btn-eliminar', function () {
        let iID = $(this).data('id');

        Swal.fire({
            title: '¿Desactivar servicio?',
            text: 'El servicio pasará a estatus Inactivo y no estará disponible para cotizar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/servicios/paquetes/deleteData',
                    type: 'POST',
                    data: { iID: iID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.lSuccess) {
                            tablaServicios.ajax.reload(null, false);
                            Swal.fire({ icon: 'success', title: 'Servicio Desactivado', text: response.cMensaje });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.cMensaje });
                        }
                    }
                });
            }
        });
    });
});
