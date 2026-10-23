$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let tablaServicios = null;
    function escapar(valor) {
        return $('<div>').text(valor ?? '').html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    let modalServicio = new bootstrap.Modal(document.getElementById('modalServicio'));
    let modalDetalleServicio = new bootstrap.Modal(document.getElementById('modalDetalleServicio'));

    // Inicializar DataTables con Filtros Dinámicos
    function initTabla() {
        tablaServicios = $('#tablaServicios').DataTable({
            language: {
                emptyTable: 'No hay servicios registrados.',
                zeroRecords: 'No hay servicios que coincidan con los filtros.',
                info: 'Mostrando _START_–_END_ de _TOTAL_ servicios',
                infoEmpty: 'Mostrando 0 servicios',
                infoFiltered: '(de _MAX_ servicios)',
                processing: 'Cargando servicios…',
                loadingRecords: 'Cargando servicios…',
                paginate: {first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior'}
            },
            serverSide: true,
            processing: true,
            lengthChange: false,
            ordering: false,
            searchDelay: 300,
            ajax: {
                url: $('#tablaServicios').data('grid-url'),
                type: 'GET',
                data: function (d) {
                    d.iIDCategoria   = $('#filtroCategoria').val();
                    d.iIDDestino     = $('#filtroDestino').val();
                    d.cEstatusFiltro = $('#filtroEstatus').val();
                    d.lConPromocion  = $('#filtroPromocion').val();
                },
                dataSrc: 'data',
                error: function (xhr) {
                    Swal.fire({icon: 'error', title: 'No se pudieron cargar los servicios', text: xhr.responseJSON?.cMensaje || xhr.responseJSON?.message || 'Revisa tu sesión y vuelve a cargar el catálogo.'});
                }
            },
            dom: 'rt<"row servicios-pie"<"col-md-5"i><"col-md-7"p>>',
            pageLength: 10,
            initComplete: function () {
                $('#tablaServicios_wrapper .servicios-pie').appendTo('#serviciosPaginacion');
            },
            drawCallback: function () {
                $('#totalServicios').text(this.api().page.info().recordsDisplay);
            },
            columns: [
                {
                    data: 'cNombre',
                    render: function (data, type, row) {
                        if (type !== 'display') return row.cCodigo + ' ' + data + ' ' + row.cCategoria;
                        const promocion = row.lPromocionVigente ? '<span class="servicio-promocion"><i class="fas fa-tag" aria-hidden="true"></i>Promoción vigente</span>' : '';
                        const itinerario = row.cTipoItinerario === 'PDF' && row.lTieneItinerario ? '<small class="servicio-meta">Itinerario: PDF</small>' : (row.iDiasItinerario > 0 ? '<small class="servicio-meta">Itinerario: ' + Number(row.iDiasItinerario) + ' días</small>' : '');
                        return '<span class="apartado-folio">' + escapar(row.cCodigo) + '</span><div class="apartado-nombre" title="' + escapar(data) + '">' + escapar(data) + '</div><small class="servicio-meta">' + escapar(row.cCategoria) + '</small>' + itinerario + promocion;
                    }
                },
                {
                    data: 'cDestino',
                    render: function (data, type, row) {
                        if (type !== 'display') return data + ' ' + row.cProveedor;
                        return '<span class="apartado-cliente">' + escapar(data) + '</span><small class="servicio-meta">' + escapar(row.cProveedor) + '</small>';
                    }
                },
                {
                    data: 'dFechaInicioViaje',
                    render: function (data, type, row) {
                        if (type !== 'display') return (data || '') + ' ' + (row.dFechaFinViaje || '');
                        const viaje = data && row.dFechaFinViaje ? '<div class="servicio-fechas"><small>Salida</small> ' + escapar(data) + '<br><small>Regreso</small> ' + escapar(row.dFechaFinViaje) + '</div>' : '<span class="servicio-meta">Sin fechas de viaje</span>';
                        const publicacion = row.lUsaVigencia ? escapar(row.dFechaInicioVigencia) + ' — ' + escapar(row.dFechaFinVigencia) : 'Sin vencimiento';
                        return viaje + '<small class="servicio-meta">Publicación: ' + publicacion + '</small>';
                    }
                },
                {
                    data: 'dPrecioFinal',
                    className: 'text-end apartado-importe',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') return Number(String(data).replace(/,/g, ''));
                        if (type !== 'display') return data + ' ' + row.cMoneda;
                        const regular = row.lPromocionVigente ? '<div class="servicio-precio-anterior">' + escapar(row.dPrecioVenta) + '</div>' : '';
                        const menor = row.lPrecioMenorActivo ? '<div class="small mt-1">Menor: ' + (Number(row.dPrecioMenor) > 0 ? escapar(Number(row.dPrecioMenor).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})) + ' ' + escapar(row.cMoneda || 'MXN') : 'Gratis') + '</div>' : '';
                        return regular + '<strong>' + escapar(data) + '</strong><small>' + escapar(row.cMoneda || 'MXN') + '</small>' + menor;
                    }
                },
                {
                    data: 'cEstatusDisponibilidad',
                    render: function (data, type) {
                        if (type !== 'display') return data;
                        const estados = {ACTIVO: ['liquidado', 'Activo'], PROGRAMADO: ['programado', 'Programado'], VENCIDO: ['abierto', 'Vencido'], INACTIVO: ['cancelado', 'Inactivo']};
                        const estado = estados[data] || estados.INACTIVO;
                        return '<span class="apartado-estado ' + estado[0] + '"><span class="estado-punto" aria-hidden="true"></span>' + estado[1] + '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        return '<div class="apartado-acciones">' +
                            (row.lTieneItinerario ? '<a class="apartado-icono" data-permission="itinerarios.ver" href="' + (row.cTipoItinerario === 'PDF' ? '/servicios/paquetes/' + Number(row.iID) + '/itinerario-archivo' : '/servicios/itinerarios/' + Number(row.iIDItinerario)) + '"' + (row.cTipoItinerario === 'PDF' ? ' target="_blank" rel="noopener"' : '') + ' title="Itinerario asignado" aria-label="Itinerario asignado"><i class="fas fa-route" aria-hidden="true"></i></a>' : '') +
                            (row.lTieneItinerario ? '<a class="apartado-icono" data-permission="itinerarios.pdf" href="/servicios/paquetes/' + Number(row.iID) + '/itinerario.pdf" title="Descargar itinerario PDF" aria-label="Descargar itinerario PDF"><i class="fas fa-print" aria-hidden="true"></i></a>' : '') +
                            '<button type="button" class="apartado-icono btn-detalle" data-id="' + Number(row.iID) + '" title="Ver detalle" aria-label="Ver detalle"><i class="fas fa-eye" aria-hidden="true"></i></button>' +
                            '<button type="button" class="apartado-icono btn-editar" data-permission="servicios.editar" data-id="' + Number(row.iID) + '" title="Editar servicio" aria-label="Editar servicio"><i class="fas fa-pen" aria-hidden="true"></i></button>' +
                            '<button type="button" class="apartado-icono accion-cancelar btn-eliminar" data-permission="servicios.activar" data-id="' + Number(row.iID) + '" title="Desactivar servicio" aria-label="Desactivar servicio"><i class="fas fa-ban" aria-hidden="true"></i></button></div>';
                    }
                }
            ]
        });
    }

    initTabla();
    let busquedaPendiente;
    $('#buscarServicio').on('input', function () {
        const valor = this.value;
        clearTimeout(busquedaPendiente);
        busquedaPendiente = setTimeout(function () { tablaServicios.search(valor).draw(); }, 300);
    });
    $('#limpiarFiltrosServicios').on('click', function () {
        clearTimeout(busquedaPendiente);
        $('#buscarServicio').val('');
        $('#filtroCategoria, #filtroDestino, #filtroEstatus, #filtroPromocion').val('').trigger('change.select2');
        tablaServicios.search('');
        tablaServicios.ajax.reload();
    });

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
                        $('#detPrecioMenorFila').toggleClass('d-none', !oData.lPrecioMenorActivo);
                        $('#detPrecioMenor').text(Number(oData.dPrecioMenor) > 0 ? `$${Number(oData.dPrecioMenor).toFixed(2)} ${oData.cMoneda || 'MXN'}` : 'Gratis');
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
                    $('#detFechaInicioViaje').text(oData.dFechaInicioViaje ? formatFechaDMY(oData.dFechaInicioViaje) : 'Sin fechas de viaje');
                    $('#detFechaFinViaje').text(oData.dFechaFinViaje ? formatFechaDMY(oData.dFechaFinViaje) : 'Sin fechas de viaje');
                    $('#detFechaInicioVigencia').text(oData.lUsaVigencia ? formatFechaDMY(oData.dFechaInicioVigencia) : 'Sin vigencia');
                    $('#detFechaFinVigencia').text(oData.lUsaVigencia ? formatFechaDMY(oData.dFechaFinVigencia) : 'Sin vigencia');

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
    $('#galeriaExistenteContainer').on('click', '.eliminar-imagen-galeria', function () {
        const card = $(this).closest('[data-imagen-id]');
        Swal.fire({
            icon: 'warning', title: '¿Eliminar esta imagen?',
            text: 'Se eliminará de la galería al guardar el servicio.',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;
            $('<input>', { type: 'hidden', name: 'imagenesEliminar[]', value: card.attr('data-imagen-id') }).appendTo('#galeriaEliminaciones');
            card.remove();
        });
    });
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

    function actualizarTipoItinerario() {
        const esPdf = $('#cTipoItinerario').val() === 'PDF';
        const puedeAsignar = (window.userPermissions || []).includes('itinerarios.asignar');
        const tienePdf = $('#itinerarioPdfFile').attr('data-tiene-pdf') === '1';
        const urlPdf = $('#itinerarioPdfFile').attr('data-url-pdf');
        const id = $('#iIDItinerario').val();
        $('#itinerarioCatalogoCampos').toggleClass('d-none', esPdf);
        $('#itinerarioPdfCampos').toggleClass('d-none', !esPdf);
        $('#iIDItinerario').prop('disabled', esPdf || !puedeAsignar);
        $('#itinerarioPdfFile').prop('disabled', !esPdf || !puedeAsignar).prop('required', esPdf && !tienePdf && puedeAsignar);
        $('#itinerarioPdfEstado').text(tienePdf ? 'El servicio ya tiene un PDF guardado. Puedes conservarlo o reemplazarlo.' : 'Selecciona el PDF para guardar este itinerario.');
        const url = esPdf ? urlPdf : (id ? '/servicios/itinerarios/' + Number(id) : '');
        $('#abrirItinerario').toggleClass('d-none', !url).attr('href', url || '#').attr('target', esPdf ? '_blank' : '_self').attr('rel', 'noopener');
    }
    $('#iIDItinerario, #cTipoItinerario').on('change', actualizarTipoItinerario);

    function actualizarPrecioMenor() {
        const activo = $('#lPrecioMenorActivo').is(':checked');
        $('#precioMenorContainer').toggleClass('d-none', !activo);
        $('#dPrecioMenor').prop('disabled', !activo);
    }
    $('#lPrecioMenorActivo').on('change', actualizarPrecioMenor);

    function actualizarVigencia() {
        const activa = $('#lUsaVigencia').is(':checked');
        $('#dFechaInicioVigencia, #dFechaFinVigencia').prop('disabled', !activa).prop('required', activa);
        if (activa) {
            $('#dFechaInicioVigencia, #dFechaFinVigencia').each(function () { if (!this.value) this.value = window.fechaActualAgencia(); });
        }
    }
    $('#lUsaVigencia').on('change', actualizarVigencia);
    $('#lTieneFechasViaje').on('change', function () {
        const activa = this.checked;
        $('#dFechaInicioViaje, #dFechaFinViaje').prop('disabled', !activa).prop('required', activa);
        if (activa) $('#dFechaInicioViaje, #dFechaFinViaje').each(function () { if (!this.value) this.value = window.fechaActualAgencia(); });
    });

    // Abrir Modal para Nuevo Servicio
    $('#btnNuevo').on('click', function () {
        $('#formServicio')[0].reset();
        actualizarPrecioMenor();
        $('#iID').val(0);
        $('#abrirItinerario').addClass('d-none').removeAttr('href');
        $('#iIDItinerario').val('').trigger('change');
        $('#itinerarioPdfFile').val('').attr('data-tiene-pdf', '0').removeAttr('data-url-pdf');
        $('#cTipoItinerario').val('CATALOGO').trigger('change');
        $('#lPublicarItinerario').prop('checked', false);
        $('#iIDProveedor').val('');
        $('#iIDCategoria, #iIDDestino').val('').trigger('change.select2');
        $('#lUsaVigencia').prop('checked', true).trigger('change');

        let hoy = window.fechaActualAgencia();
        $('#dFechaInicioVigencia').val(hoy);
        $('#dFechaFinVigencia').val(hoy);
        $('#dFechaInicioViaje, #dFechaFinViaje').val(hoy);
        $('#lTieneFechasViaje').prop('checked', true).trigger('change');
        $('#dFechaInicioPromocion').val(hoy);
        $('#dFechaFinPromocion').val(hoy);

        $('#colCodigoContainer').hide();
        $('#lTienePromocion').prop('checked', false).trigger('change');
        $('#lActivo').prop('checked', true);
        $('#lPromocionActiva').prop('checked', true);
        $('#previewPrincipalContainer').addClass('d-none');
        $('#galeriaPreviewContainer').empty();
        $('#galeriaExistenteContainer, #galeriaEliminaciones').empty();
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
                    $('#iIDCategoria').val(oData.iIDCategoria || '');
                    $('#iIDDestino').val(oData.iIDDestino || '');
                    $('#iIDProveedor').val(oData.iIDProveedor || '');
                    $('#dPrecioCompra').val(oData.dPrecioCompra);
                    $('#dPrecioVenta').val(oData.dPrecioVenta);
                    $('#iIDItinerario').val(oData.iIDItinerario || '').trigger('change');
                    $('#itinerarioPdfFile').val('').attr('data-tiene-pdf', oData.lTienePdfItinerario ? '1' : '0').attr('data-url-pdf', oData.urlItinerarioPdf || '');
                    $('#cTipoItinerario').val(oData.cTipoItinerario || 'CATALOGO').trigger('change');
                    $('#lPublicarItinerario').prop('checked', !!oData.lPublicarItinerario);
                    $('#dPrecioMenor').val(oData.dPrecioMenor);
                    $('#lPrecioMenorActivo').prop('checked', !!oData.lPrecioMenorActivo).trigger('change');
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
                    $('#dFechaInicioViaje').val(oData.dFechaInicioViaje);
                    $('#dFechaFinViaje').val(oData.dFechaFinViaje);
                    $('#lTieneFechasViaje').prop('checked', !!(oData.dFechaInicioViaje && oData.dFechaFinViaje)).trigger('change');
                    $('#lUsaVigencia').prop('checked', oData.lUsaVigencia !== false).trigger('change');
                    $('#lActivo').prop('checked', oData.lActivo == 1);

                    // Imágenes
                    $('#cImagenFile, #cGaleriaFiles').val('');
                    $('#galeriaPreviewContainer, #galeriaEliminaciones').empty();
                    if (oData.cImagenPrincipal) {
                        $('#imgPreviewPrincipal').attr('src', oData.cImagenPrincipal);
                        $('#previewPrincipalContainer').removeClass('d-none');
                    } else {
                        $('#previewPrincipalContainer').addClass('d-none');
                    }

                    // Galería existente
                    let $container = $('#galeriaExistenteContainer').empty();
                    if (oData.aGaleria && oData.aGaleria.length > 0) {
                        oData.aGaleria.forEach(img => {
                            const card = $('<div>', { class: 'position-relative', 'data-imagen-id': img.iID });
                            $('<img>', { src: img.cRuta, alt: 'Imagen guardada', class: 'img-thumbnail rounded', css: { width: '90px', height: '75px', objectFit: 'cover' } }).appendTo(card);
                            $('<button>', { type: 'button', class: 'apartado-icono accion-cancelar eliminar-imagen-galeria position-absolute top-0 end-0', title: 'Eliminar imagen', 'aria-label': 'Eliminar imagen' }).html('<i class="fas fa-trash" aria-hidden="true"></i>').appendTo(card);
                            $container.append(card);
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
                    $('#cImagenFile, #cGaleriaFiles').val('');
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
