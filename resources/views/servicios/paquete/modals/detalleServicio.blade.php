<div class="modal fade" id="modalDetalleServicio" tabindex="-1" aria-labelledby="modalDetalleServicioTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="modalDetalleServicioTitle">
                    <i class="fas fa-file-invoice text-info me-2"></i> Detalle General del Servicio Turístico
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Encabezado con Código y Estatus -->
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm mb-3 flex-wrap gap-2">
                    <div>
                        <span class="badge bg-dark font-monospace fs-6" id="detCodigo">SERV-0000</span>
                        <h4 class="fw-bold mb-0 text-slate-800 mt-1" id="detNombre">Nombre del Servicio</h4>
                    </div>
                    <div id="detEstatusBadge">
                        <!-- Badge de disponibilidad -->
                    </div>
                </div>

                <!-- Ficha Informativa: Categoría, Destino y Proveedor -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body py-2 px-3">
                        <div class="row text-center text-md-start align-items-center py-1">
                            <div class="col-md-4 border-end border-md-0 py-1">
                                <span class="text-muted small fw-semibold text-uppercase d-block">Categoría</span>
                                <span class="fw-bold text-dark fs-6" id="detCategoria">N/A</span>
                            </div>
                            <div class="col-md-4 border-end border-md-0 py-1">
                                <span class="text-muted small fw-semibold text-uppercase d-block">Destino Turístico</span>
                                <span class="fw-bold text-dark fs-6" id="detDestino">N/A</span>
                            </div>
                            <div class="col-md-4 py-1">
                                <span class="text-muted small fw-semibold text-uppercase d-block">Proveedor Comercial</span>
                                <span class="fw-bold text-dark fs-6" id="detProveedor">N/A</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado Financiero y Precios (Formato Tabla Formal) -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white py-2 border-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-balance-scale me-1"></i> Precios
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 text-secondary fw-semibold">Concepto Financiero</th>
                                        <th class="text-end pe-3 text-secondary fw-semibold">Importe / Ratios</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-3 text-secondary">
                                            <i class="fas fa-shopping-cart text-muted me-2"></i> Precio de Compra (Costo Agencia)
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-secondary" id="detPrecioCompra">$0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3 text-secondary">
                                            <i class="fas fa-tag text-primary me-2"></i> Precio de Venta Público Normal
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-dark" id="detPrecioVenta">$0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3 text-secondary">
                                            <i class="fas fa-percent text-warning-emphasis me-2"></i> Precio Final Promocional
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-dark" id="detPrecioFinal">$0.00</td>
                                    </tr>
                                    <tr class="table-success bg-opacity-10">
                                        <td class="ps-3 fw-bold text-success-emphasis">
                                            <i class="fas fa-chart-line text-success me-2"></i> Ganancia Efectiva Estimada
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="fw-bold text-success fs-6" id="detGananciaEfectiva">$0.00</span>
                                            <span class="d-block small text-muted" id="detUtilidad">(0.00% utilidad sobre costo)</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sección de Promoción Configurada si aplica -->
                <div class="card border-0 shadow-sm rounded-3 mb-3 d-none" id="detContainerPromo">
                    <div class="card-header bg-white py-2 border-0">
                        <h6 class="m-0 font-weight-bold text-warning-emphasis">
                            <i class="fas fa-tag me-1"></i> Promoción Aplicada
                        </h6>
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <span class="text-muted small d-block">Promoción:</span>
                                <strong id="detNombrePromo" class="text-dark">-</strong>
                                <small id="detDescPromo" class="d-block text-muted">-</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <span class="text-muted small d-block">Descuento & Vigencia:</span>
                                <span id="detValorDescuento" class="badge bg-warning text-dark fw-bold">-</span>
                                <small id="detFechasPromo" class="d-block text-muted mt-1">-</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vigencia Operativa -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white py-2 border-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calendar-check me-1"></i> Vigencia Operativa del Servicio
                        </h6>
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="row text-center text-md-start">
                            <div class="col-md-6 py-1">
                                <span class="text-muted small d-block">Fecha Inicio:</span>
                                <strong id="detFechaInicioVigencia" class="text-dark">-</strong>
                            </div>
                            <div class="col-md-6 py-1">
                                <span class="text-muted small d-block">Fecha Término:</span>
                                <strong id="detFechaFinVigencia" class="text-dark">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descripciones -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white py-2 border-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-align-left me-1"></i> Descripción & Especificaciones
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2" id="detDescCorta"><em>Sin descripción corta.</em></p>
                        <div class="p-3 bg-white rounded border text-slate-700 mt-2" id="detDescCompleta"><em>Sin descripción completa.</em></div>
                    </div>
                </div>

                <!-- Galería de Imágenes -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2 border-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-images me-1"></i> Galería Fotográfica
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="detGaleriaContainer" class="d-flex flex-wrap gap-2">
                            <span class="text-muted">Sin imágenes disponibles.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
