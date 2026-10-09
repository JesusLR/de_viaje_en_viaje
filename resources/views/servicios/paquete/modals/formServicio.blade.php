<div class="modal fade" id="modalServicio" tabindex="-1" aria-labelledby="modalServicioTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalServicioTitle">
                    <i class="fas fa-umbrella-beach me-2"></i> Nuevo Servicio / Paquete Turístico
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formServicio" enctype="multipart/form-data" novalidate class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                @csrf
                <input type="hidden" id="iID" name="iID" value="0">

                <div class="modal-body p-4 bg-light">

                    <!-- Navegación por pestañas del formulario -->
                    <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded shadow-sm" id="servicioTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold" id="general-tab" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                                <i class="fas fa-info-circle me-1"></i> 1. Info General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="catdest-tab" data-bs-toggle="tab" data-bs-target="#tab-catdest" type="button" role="tab">
                                <i class="fas fa-map-marked-alt me-1"></i> 2. Categoría & Destino
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="precios-tab" data-bs-toggle="tab" data-bs-target="#tab-precios" type="button" role="tab">
                                <i class="fas fa-tags me-1"></i> 3. Precios & Márgenes
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="promo-tab" data-bs-toggle="tab" data-bs-target="#tab-promo" type="button" role="tab">
                                <i class="fas fa-percentage me-1"></i> 4. Promoción
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="vigencia-tab" data-bs-toggle="tab" data-bs-target="#tab-vigencia" type="button" role="tab">
                                <i class="fas fa-calendar-alt me-1"></i> 5. Vigencia & Galería
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="servicioTabsContent">
                        
                        <!-- PESTAÑA 1: INFORMACIÓN GENERAL -->
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                            <div class="card border-0 shadow-sm p-3">
                                <div class="row g-3">
                                    <div class="col-md-4" id="colCodigoContainer" style="display:none;">
                                        <label class="form-label fw-semibold text-muted">Código Interno Generado</label>
                                        <input type="text" id="cCodigo" class="form-bg-readonly form-control fw-bold text-primary" readonly disabled>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="cNombre" class="form-label fw-bold">Nombre del Servicio Turístico <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="cNombre" name="cNombre" placeholder="Ej. Paquete Vacacional Cancún & Riviera Maya 5 Días" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="cDescripcionCorta" class="form-label fw-semibold">Descripción Corta</label>
                                        <input type="text" class="form-control" id="cDescripcionCorta" name="cDescripcionCorta" placeholder="Resumen atractivo para ofertas y catálogos rápidos" maxlength="255">
                                    </div>
                                    <div class="col-md-12">
                                        <label for="cDescripcionCompleta" class="form-label fw-semibold">Descripción Detallada / Incluye</label>
                                        <textarea class="form-control" id="cDescripcionCompleta" name="cDescripcionCompleta" rows="4" placeholder="Detalles de lo que incluye el viaje o excursión (vuelos, hospedaje, desayunos, traslados, etc.)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 2: CATEGORÍA, DESTINO Y PROVEEDOR -->
                        <div class="tab-pane fade" id="tab-catdest" role="tabpanel">
                            <div class="card border-0 shadow-sm p-3">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="iIDCategoria" class="form-label fw-bold">Categoría Turística <span class="text-danger">*</span></label>
                                        <select class="form-select" id="iIDCategoria" name="iIDCategoria" required>
                                            <option value="">-- Seleccionar Categoría --</option>
                                            @foreach($aCategorias as $oCat)
                                                <option value="{{ $oCat->iID }}">{{ $oCat->cNombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="iIDDestino" class="form-label fw-bold">Destino Turístico <span class="text-danger">*</span></label>
                                        <select class="form-select" id="iIDDestino" name="iIDDestino" required>
                                            <option value="">-- Seleccionar Destino --</option>
                                            @foreach($aDestinos as $oDest)
                                                <option value="{{ $oDest->iID }}">{{ $oDest->cNombre }} ({{ $oDest->ubicacion_completa }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="iIDProveedor" class="form-label fw-bold">Proveedor Turístico</label>
                                        <select class="form-select" id="iIDProveedor" name="iIDProveedor">
                                            <option value="">-- Sin Proveedor / Directo --</option>
                                            @foreach($aProveedores as $oProv)
                                                <option value="{{ $oProv->iID }}">{{ $oProv->cNombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                                            <i class="fas fa-lightbulb fs-4 me-3"></i>
                                            <div>
                                                Asocia este servicio turístico a su categoría, destino y proveedor comercial correspondiente.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 3: PRECIOS Y MÁRGENES -->
                        <div class="tab-pane fade" id="tab-precios" role="tabpanel">
                            <div class="card border-0 shadow-sm p-3">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="dPrecioCompra" class="form-label fw-bold">Precio de Compra (Costo) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control calc-trigger" id="dPrecioCompra" name="dPrecioCompra" placeholder="0.00" required>
                                        </div>
                                        <div class="form-text">Costo directo contratado para la agencia.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="dPrecioVenta" class="form-label fw-bold">Precio de Venta Normal <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control calc-trigger" id="dPrecioVenta" name="dPrecioVenta" placeholder="0.00" required>
                                        </div>
                                        <div class="form-text">Precio estándar cobrado al cliente.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="cMoneda" class="form-label fw-semibold">Moneda</label>
                                        <select class="form-select" id="cMoneda" name="cMoneda">
                                            <option value="MXN" selected>MXN - Pesos Mexicanos</option>
                                            <option value="USD">USD - Dólares Estadounidenses</option>
                                            <option value="EUR">EUR - Euros</option>
                                        </select>
                                    </div>

                                    <!-- Simulación y Balance Financiero Formal -->
                                    <div class="col-12 mt-4">
                                        <div class="card border shadow-none rounded-3">
                                            <div class="card-header bg-light py-2 border-bottom">
                                                <h6 class="m-0 font-weight-bold text-secondary">
                                                    <i class="fas fa-calculator me-1"></i> Balance & Proyección Financiera
                                                </h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle mb-0">
                                                        <tbody>
                                                            <tr>
                                                                <td class="ps-3 text-muted" style="width: 50%;">
                                                                    <i class="fas fa-chart-line text-success me-2"></i> Ganancia Estimada Regular <small class="text-muted d-block">(Precio Venta - Precio Compra)</small>
                                                                </td>
                                                                <td class="text-end pe-3 fw-bold text-success fs-5" id="previewGanancia">
                                                                    $0.00 MXN
                                                                </td>
                                                            </tr>
                                                            <tr class="table-light">
                                                                <td class="ps-3 text-muted">
                                                                    <i class="fas fa-percentage text-info me-2"></i> Porcentaje de Utilidad sobre Costo <small class="text-muted d-block">(Ganancia / Compra) × 100</small>
                                                                </td>
                                                                <td class="text-end pe-3 fw-bold text-info fs-5" id="previewUtilidad">
                                                                    0.00%
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 4: PROMOCIÓN Y DESCUENTOS -->
                        <div class="tab-pane fade" id="tab-promo" role="tabpanel">
                            <div class="card border-0 shadow-sm p-3">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="lTienePromocion" name="lTienePromocion" value="1">
                                    <label class="form-check-label fw-bold text-primary fs-5" for="lTienePromocion">
                                        ¿Activar Promoción Especial para este Servicio?
                                    </label>
                                </div>

                                <div id="containerPromocion" class="row g-3 d-none">
                                    <div class="col-md-6">
                                        <label for="cNombrePromocion" class="form-label fw-semibold">Nombre de la Promoción</label>
                                        <input type="text" class="form-control" id="cNombrePromocion" name="cNombrePromocion" placeholder="Ej. Oferta Buen Fin / Descuento de Temporada">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="cDescripcionPromocion" class="form-label fw-semibold">Detalle de la Promoción</label>
                                        <input type="text" class="form-control" id="cDescripcionPromocion" name="cDescripcionPromocion" placeholder="Ej. Válido únicamente en reservaciones anticipadas">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="cTipoDescuento" class="form-label fw-bold">Tipo de Descuento</label>
                                        <select class="form-select calc-trigger" id="cTipoDescuento" name="cTipoDescuento">
                                            <option value="PORCENTAJE">Porcentaje (%)</option>
                                            <option value="IMPORTE">Importe Fijo ($)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="dValorDescuento" class="form-label fw-bold">Valor del Descuento</label>
                                        <input type="number" step="0.01" min="0" class="form-control calc-trigger" id="dValorDescuento" name="dValorDescuento" placeholder="Ej. 10 o 500.00">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="lPromocionActiva" class="form-label fw-semibold">Estado de la Promoción</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="lPromocionActiva" name="lPromocionActiva" value="1" checked>
                                            <label class="form-check-label" for="lPromocionActiva">Promoción Habilitada</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="dFechaInicioPromocion" class="form-label fw-semibold">Inicio de Promoción</label>
                                        <input type="date" class="form-control calc-trigger" id="dFechaInicioPromocion" name="dFechaInicioPromocion" value="{{ date('Y-m-d') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="dFechaFinPromocion" class="form-label fw-semibold">Fin de Promoción</label>
                                        <input type="date" class="form-control calc-trigger" id="dFechaFinPromocion" name="dFechaFinPromocion" value="{{ date('Y-m-d') }}">
                                    </div>

                                    <!-- Resumen Promocional Formal -->
                                    <div class="col-12 mt-4">
                                        <div class="card border shadow-none rounded-3">
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle mb-0">
                                                        <tbody>
                                                            <tr class="table-warning bg-opacity-25">
                                                                <td class="ps-3 text-dark fw-semibold" style="width: 50%;">
                                                                    <i class="fas fa-tag text-warning-emphasis me-2"></i> Precio Promocional Final
                                                                </td>
                                                                <td class="text-end pe-3 fw-bold text-dark fs-5" id="previewPrecioFinal">
                                                                    $0.00 MXN
                                                                </td>
                                                            </tr>
                                                            <tr class="table-success bg-opacity-25">
                                                                <td class="ps-3 text-dark fw-semibold">
                                                                    <i class="fas fa-chart-line text-success me-2"></i> Ganancia Efectiva con Promoción
                                                                </td>
                                                                <td class="text-end pe-3 fw-bold text-success fs-5" id="previewGananciaFinal">
                                                                    $0.00 MXN
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 5: VIGENCIA DEL SERVICIO E IMÁGENES -->
                        <div class="tab-pane fade" id="tab-vigencia" role="tabpanel">
                            <div class="card border-0 shadow-sm p-3">
                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-calendar-check me-1"></i> Rango de Vigencia del Servicio</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-5">
                                        <label for="dFechaInicioVigencia" class="form-label fw-bold">Fecha de Inicio de Vigencia <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="dFechaInicioVigencia" name="dFechaInicioVigencia" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="dFechaFinVigencia" class="form-label fw-bold">Fecha de Fin de Vigencia <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="dFechaFinVigencia" name="dFechaFinVigencia" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Estatus Manual</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="lActivo" name="lActivo" value="1" checked>
                                            <label class="form-check-label" for="lActivo">Habilitado</label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-images me-1"></i> Gestión de Imágenes</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="cImagenFile" class="form-label fw-semibold">Imagen Principal del Servicio</label>
                                        <input type="file" class="form-control" id="cImagenFile" name="cImagenFile" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text">Formatos válidos: JPG, PNG, WEBP. Máx. 5MB.</div>
                                        <div id="previewPrincipalContainer" class="mt-2 text-center d-none">
                                            <img id="imgPreviewPrincipal" src="" class="img-thumbnail shadow-sm" style="max-height: 140px; object-fit: cover;">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="cGaleriaFiles" class="form-label fw-semibold">Galería de Imágenes Adicionales</label>
                                        <input type="file" class="form-control" id="cGaleriaFiles" name="cGaleriaFiles[]" multiple accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text">Puedes seleccionar múltiples archivos para la galería.</div>
                                        <div id="galeriaPreviewContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnGuardar">
                        <i class="fas fa-save me-1"></i> Guardar Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
