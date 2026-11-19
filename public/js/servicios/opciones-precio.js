(() => {
    const modo = document.getElementById('lOpcionesPrecio');
    const filas = document.getElementById('opcionesPrecioFilas');
    let indice = 0;
    const editarPrecios = !Array.isArray(window.userPermissions) || window.userPermissions.includes('servicios.precios');
    modo.disabled = !editarPrecios;
    document.getElementById('agregarOpcionPrecio').disabled = !editarPrecios;
    $(filas).on('select2:opening', 'select', event => { if (!editarPrecios) event.preventDefault(); });
    function actualizar() {
        const multiple = modo.value === '1';
        document.getElementById('editorOpcionesPrecio').classList.toggle('d-none', !multiple);
        document.getElementById('precioMenorGeneral')?.classList.toggle('d-none', multiple);
        filas.querySelectorAll('[data-campo]').forEach(el => {
            el.disabled = !multiple;
            el.required = multiple && ['nombre', 'adultos', 'menores', 'dPrecioVenta'].includes(el.dataset.campo);
            if (!editarPrecios && el.dataset.campo !== 'dPrecioCompra') {
                el.readOnly = true;
                if (el.tagName === 'SELECT') { el.tabIndex = -1; el.style.pointerEvents = 'none'; }
            }
        });
        filas.querySelectorAll('.opcion-precio-editor').forEach(fila => {
            const activo = fila.querySelector('.activar-menor-opcion').checked;
            const habitacion = fila.querySelector('[data-campo="unidad"]').value === 'HABITACION';
            fila.querySelectorAll('.capacidad-habitacion').forEach(el => {
                el.classList.toggle('d-none', !habitacion);
                el.querySelectorAll('input').forEach(input => { input.disabled = !multiple || !habitacion; input.required = multiple && habitacion; });
            });
            fila.querySelector('.etiqueta-precio-opcion').textContent = habitacion ? 'Precio total de la habitación por estancia' : 'Precio por adulto';
            fila.querySelector('.etiqueta-capacidad-menores').textContent = activo ? 'Menores permitidos por habitación' : 'Menores incluidos por habitación';
            fila.querySelector('.form-check-label').textContent = habitacion ? 'Cobrar un importe adicional por cada menor' : 'Activar precio para menor';
            fila.querySelector('.ayuda-menores-opcion').textContent = habitacion ? 'Apagado: los menores están incluidos hasta la capacidad indicada. Encendido: se cobra la habitación más el importe por cada menor.' : 'Actívalo para registrar menores con una tarifa independiente. En blanco o en 0: gratis.';
            const moneda = document.getElementById('cMoneda')?.value || 'MXN';
            const dinero = valor => Number(valor || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + moneda;
            const precio = dinero(fila.querySelector('[data-campo="dPrecioVenta"]').value);
            const precioMenor = dinero(fila.querySelector('[data-campo="dPrecioMenor"]').value);
            const adultos = fila.querySelector('[data-campo="adultos"]').value || 0;
            const menores = fila.querySelector('[data-campo="menores"]').value || 0;
            fila.querySelector('.resumen-cobro-opcion').textContent = habitacion
                ? `Esta tarifa cobra ${precio} por habitación por estancia e incluye hasta ${adultos} adultos` + (activo ? `. Admite hasta ${menores} menores con un importe adicional de ${precioMenor} por cada uno.` : (Number(menores) > 0 ? ` y ${menores} menores.` : '.'))
                : `Esta tarifa cobra ${precio} por adulto por estancia.` + (activo ? ` Cada menor paga ${precioMenor}.` : ' No tiene tarifa para menores.');

            fila.querySelector('.precio-menor-opcion').classList.toggle('d-none', !activo);
            fila.querySelector('[data-campo="dPrecioMenor"]').disabled = !multiple || !activo;
            fila.querySelector('.activar-menor-opcion').disabled = !multiple || !editarPrecios;
        });
        modo.setCustomValidity(multiple && !filas.querySelector('[data-campo="activo"]:not(:disabled)') ? 'Agrega una opción activa.' : '');
        const activas = [...filas.children].filter(f => f.querySelector('[data-campo="activo"]').value === '1');
        if (multiple && !activas.length) modo.setCustomValidity('Agrega una opción activa.');
        document.getElementById('dPrecioVenta').readOnly = multiple;
        if (multiple && activas.length) {
            const menor = activas.sort((a,b) => Number(a.querySelector('[data-campo="dPrecioVenta"]').value) - Number(b.querySelector('[data-campo="dPrecioVenta"]').value))[0];
            document.getElementById('dPrecioVenta').value = menor.querySelector('[data-campo="dPrecioVenta"]').value || 0;
            const costo = menor.querySelector('[data-campo="dPrecioCompra"]');
            if (costo && document.getElementById('dPrecioCompra')) document.getElementById('dPrecioCompra').value = costo.value || 0;
            else if (document.getElementById('dPrecioCompra') && !document.getElementById('dPrecioCompra').value) document.getElementById('dPrecioCompra').value = 0;
        }
        if (multiple && document.getElementById('dPrecioCompra')) document.getElementById('dPrecioCompra').readOnly = true;
        else if (document.getElementById('dPrecioCompra')?.dataset.opcionesReadonly === '0') document.getElementById('dPrecioCompra').readOnly = false;
        document.getElementById('dPrecioVenta').dispatchEvent(new Event('change', {bubbles:true}));
    }
    function agregar(datos = {}) {
        if (filas.children.length >= 30) return;
        const fila = document.getElementById('opcionPrecioTemplate').content.firstElementChild.cloneNode(true);
        fila.querySelectorAll('[data-campo]').forEach(el => {
            const campo = el.dataset.campo;
            el.name = `opciones_precio[${indice}][${campo}]`;
            if (el.type === 'checkbox') {
                el.checked = Boolean(datos[campo]);
                el.id = `opcionMenor${indice}`;
                fila.querySelector('.form-check-label').htmlFor = el.id;
            } else if (!el.hasAttribute('data-menor-hidden') && Object.hasOwn(datos, campo)) {
                el.value = typeof datos[campo] === 'boolean' ? Number(datos[campo]) : datos[campo];
            }
            if (el.hasAttribute('data-menor-hidden') && !editarPrecios) el.value = datos[campo] ? '1' : '0';
        });
        indice++;
        fila.querySelector('.quitar-opcion').disabled = !editarPrecios;
        filas.appendChild(fila);
        actualizar();
    }
    window.cargarOpcionesPrecio = datos => {
        filas.replaceChildren(); indice = 0;
        modo.value = datos.lOpcionesPrecio ? '1' : '0';
        $(modo).trigger('change.select2');
        (datos.opciones_precio || []).forEach(opcion => agregar({
            ...opcion,
            lPrecioMenorActivo: opcion.lPrecioMenorActivo ?? (opcion.unidad === 'PERSONA' && datos.lPrecioMenorActivo),
            dPrecioMenor: opcion.dPrecioMenor ?? datos.dPrecioMenor ?? 0
        }));
        actualizar();
    };
    const costoBase = document.getElementById('dPrecioCompra');
    if (costoBase) costoBase.dataset.opcionesReadonly = costoBase.readOnly ? '1' : '0';
    $('#cMoneda').on('change', actualizar);
    $(modo).on('change', () => { if (modo.value === '1' && !filas.children.length) agregar(); actualizar(); });
    filas.addEventListener('input', actualizar);
    $(filas).on('change', 'select, .activar-menor-opcion', actualizar);
    filas.addEventListener('click', e => { const boton = e.target.closest('.quitar-opcion'); if (boton) { boton.closest('.opcion-precio-editor').remove(); actualizar(); } });
    document.getElementById('agregarOpcionPrecio').addEventListener('click', () => agregar());
})();
