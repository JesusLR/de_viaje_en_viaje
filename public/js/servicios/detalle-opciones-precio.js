window.mostrarDetalleOpcionesPrecio = function (servicio) {
    const multiple = Boolean(servicio.lOpcionesPrecio);
    document.getElementById('detPreciosUnico').classList.toggle('d-none', multiple);
    document.getElementById('detOpcionesPrecio').classList.toggle('d-none', !multiple);
    const cuerpo = document.getElementById('detOpcionesPrecioFilas');
    cuerpo.replaceChildren();
    if (!multiple) return;
    const importe = valor => Number(valor || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + (servicio.cMoneda || 'MXN');
    (servicio.tarifas_detalle || []).forEach(opcion => {
        const fila = document.createElement('tr');
        function celda(texto, clase = '') {
            const td = document.createElement('td');
            td.className = clase;
            td.textContent = texto;
            fila.appendChild(td);
            return td;
        }
        const info = celda('');
        const nombre = document.createElement('strong');
        nombre.textContent = opcion.nombre;
        info.appendChild(nombre);
        [opcion.descripcion, opcion.cama, `Capacidad: ${opcion.adultos} adultos${opcion.menores > 0 ? ' y ' + opcion.menores + ' menores' : ''} por habitación.`].filter(Boolean).forEach(texto => {
            const detalle = document.createElement('small');
            detalle.className = 'd-block text-muted';
            detalle.textContent = texto;
            info.appendChild(detalle);
        });
        celda(opcion.unidad === 'HABITACION' ? 'Precio por habitación' : 'Precio Por Persona');
        if (document.getElementById('detOpcionesCosto')) {
            const original = (servicio.opciones_precio || []).find(item => item.id === opcion.id);
            celda(original?.dPrecioCompra === undefined ? '—' : importe(original.dPrecioCompra), 'text-end text-nowrap');
        }
        celda(importe(opcion.dPrecioVenta), 'text-end text-nowrap');
        celda(importe(opcion.precio), 'text-end text-nowrap fw-bold');
        celda(opcion.lPrecioMenorActivo ? importe(opcion.dPrecioMenor) : (opcion.unidad === 'HABITACION' && opcion.menores > 0 ? 'Incluidos' : 'No aplica'), 'text-end text-nowrap');
        const estado = celda('');
        const badge = document.createElement('span');
        badge.className = 'badge ' + (opcion.activo ? 'bg-success' : 'bg-secondary');
        badge.textContent = opcion.activo ? 'Activa' : 'Inactiva';
        estado.appendChild(badge);
        cuerpo.appendChild(fila);
    });
};
