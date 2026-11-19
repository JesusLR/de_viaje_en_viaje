/* Shared by sales, reservations and quotes. Prices are verified again on the server. */
window.opcionPrecioOperacion = function (contenedor, servicio) {
    const selector = contenedor.querySelector('.opcion-precio');
    const multiple = servicio.dataset.multiple === '1';
    const tarifas = JSON.parse(servicio.dataset.opciones || '[]');
    if (selector.dataset.servicio !== servicio.value) {
        selector.replaceChildren(new Option('Selecciona una opción', ''));
        tarifas.forEach(t => selector.add(new Option(`${t.nombre} · ${Number(t.precio).toLocaleString('es-MX', {minimumFractionDigits:2})} ${servicio.dataset.moneda} / ${t.unidad === 'HABITACION' ? 'habitación' : 'adulto'} · estancia completa`, t.id)));
        selector.dataset.servicio = servicio.value;
        selector.value = selector.dataset.anterior || '';
        delete selector.dataset.anterior;
        if (window.jQuery) $(selector).trigger('change.select2');
    }
    selector.disabled = !multiple;
    selector.required = multiple;
    contenedor.querySelector('.selector-opcion-precio').classList.toggle('d-none', !multiple);
    const elegida = tarifas.find(t => t.id === selector.value);
    const habitacion = multiple && elegida?.unidad === 'HABITACION';
    const etiqueta = contenedor.querySelector('.cantidad-etiqueta');
    if (etiqueta) etiqueta.textContent = habitacion ? 'Habitaciones' : (multiple && !elegida ? 'Unidades' : 'Adultos');
    contenedor.querySelector('.ocupacion-opcion').textContent = elegida ? [elegida.descripcion, elegida.cama, `Capacidad por habitación: ${elegida.adultos} adultos${elegida.menores > 0 ? ' y ' + elegida.menores + ' menores' : ''}.`, habitacion && !elegida.lPrecioMenorActivo ? 'Adultos y menores incluidos en el precio de la habitación.' : 'Precio de adultos y tarifa por menor por separado.'].filter(Boolean).join(' · ') : '';
    const datos = {...servicio.dataset};
    if (multiple) {
        datos.precio = elegida?.precio ?? 0;
        datos.regular = elegida?.dPrecioVenta ?? 0;
        datos.menoresActivo = elegida && (elegida.lPrecioMenorActivo || elegida.menoresIncluidos) ? '1' : '0';
        datos.menoresIncluidos = elegida?.menoresIncluidos ? '1' : '0';
        datos.capacidadMenores = habitacion ? elegida.menores : '';
        const menoresInput = contenedor.querySelector('.menores, #cantidadMenores');
        const cantidadInput = contenedor.querySelector('.cantidad, #cantidad');
        if (menoresInput) menoresInput.max = habitacion ? Math.min(1000, Number(cantidadInput?.value || 1) * Number(elegida.menores)) : 1000;
        datos.precioMenor = elegida?.dPrecioMenor ?? 0;
    }
    if (!multiple) {
        const menoresInput = contenedor.querySelector('.menores, #cantidadMenores');
        if (menoresInput) menoresInput.max = 1000;
    }
    return {dataset: datos, value: servicio.value};
};
