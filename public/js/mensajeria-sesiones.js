(() => {
    const root = document.getElementById('mensajeria-sesiones');
    if (!root) return;
    const base = root.dataset.base.replace(/\/$/, '');
    const puedeConectar = root.dataset.conectar === '1';
    const puedeEliminar = root.dataset.eliminar === '1';
    const filas = document.getElementById('sesionesFilas');
    const error = document.getElementById('sesionesError');
    const estado = document.getElementById('sesionesActualizacion');
    const qrModal = document.getElementById('qrSesion');
    const qrImagen = document.getElementById('qrSesionImagen');
    const qrEstado = document.getElementById('qrSesionEstado');
    const qrError = document.getElementById('qrSesionError');
    let sesiones = [], pagina = 1, cargando = false, operando = false, qrId = null, qrTimer = null, qrPeticion = 0;
    const estados = { created: ['Creada', 'bg-secondary'], initializing: ['Iniciando', 'bg-warning text-dark'], qr_ready: ['Pendiente de escanear', 'bg-warning text-dark'], authenticating: ['Autenticando', 'bg-info text-dark'], ready: ['Conectada', 'bg-success'], disconnected: ['Desconectada', 'bg-secondary'], action_required: ['Requiere atención', 'bg-warning text-dark'], failed: ['Error de conexión', 'bg-danger'] };
    const nodo = (tag, texto, clase) => { const el = document.createElement(tag); if (texto != null) el.textContent = texto; if (clase) el.className = clase; return el; };
    const aviso = (el, mensaje) => { el.textContent = mensaje || ''; el.classList.toggle('d-none', !mensaje); };
    async function api(ruta, metodo = 'GET', datos) {
        const response = await fetch(base + ruta, { method: metodo, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, ...(datos ? { 'Content-Type': 'application/json' } : {}) }, ...(datos ? { body: JSON.stringify(datos) } : {}) });
        if (response.status === 401 || response.status === 419) throw new Error('Tu sesión expiró. Actualiza la página para iniciar sesión nuevamente.');
        const body = await response.json().catch(() => ({}));
        if (!response.ok || !body.lSuccess) throw new Error(body.mensaje || Object.values(body.errors || {})[0]?.[0] || (response.status === 429 ? 'Espera un momento antes de intentar de nuevo.' : 'No fue posible completar la solicitud.'));
        return body.datos;
    }
    function accion(menu, sesion, tipo, titulo, icono, clase = '') {
        const boton = nodo('button', null, 'dropdown-item ' + clase);
        boton.type = 'button'; boton.dataset.sesionAccion = tipo; boton.dataset.sesionId = sesion.id; boton.title = titulo;
        const i = nodo('i', null, icono); i.setAttribute('aria-hidden', 'true');
        boton.append(i, nodo('span', titulo)); const li = nodo('li'); li.append(boton); menu.append(li);
    }
    function pintar() {
        const crear = document.getElementById('crearSesion');
        if (crear) crear.disabled = sesiones.length > 0 || root.dataset.configurado !== '1';
        const buscar = document.getElementById('buscarSesion').value.trim().toLocaleLowerCase();
        const visibles = sesiones.filter(s => [s.nombre, s.telefono, s.cuenta].some(valor => String(valor || '').toLocaleLowerCase().includes(buscar)));
        const paginas = Math.max(1, Math.ceil(visibles.length / 10)); pagina = Math.min(pagina, paginas);
        filas.replaceChildren();
        visibles.slice((pagina - 1) * 10, pagina * 10).forEach(s => {
            const tr = nodo('tr'); const nombre = nodo('td'); nombre.append(nodo('strong', s.nombre));
            if (s.error) nombre.append(nodo('small', s.error, 'd-block text-danger mt-1'));
            tr.append(nombre, nodo('td', s.cuenta || 'Sin vincular'), nodo('td', s.telefono || '—'));
            const celdaEstado = nodo('td'); const etiqueta = estados[s.estado] || ['Estado desconocido', 'bg-secondary']; celdaEstado.append(nodo('span', etiqueta[0], 'badge ' + etiqueta[1])); tr.append(celdaEstado);
            const fecha = s.creada ? new Date(s.creada) : null;
            tr.append(nodo('td', fecha && !isNaN(fecha) ? new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: window.agenciaTimezone || 'America/Mexico_City' }).format(fecha) : '—'));
            const celda = nodo('td', null, 'text-end');
            if (puedeConectar || puedeEliminar) {
                const contenedor = nodo('div', null, 'apartado-acciones'); const dropdown = nodo('div', null, 'dropdown apartado-mas');
                const boton = nodo('button', null, 'apartado-icono'); boton.type = 'button'; boton.dataset.bsToggle = 'dropdown'; boton.setAttribute('aria-expanded', 'false'); boton.setAttribute('aria-label', 'Más opciones de ' + s.nombre); boton.title = 'Más opciones'; boton.append(nodo('i', null, 'fas fa-ellipsis-vertical'));
                const menu = nodo('ul', null, 'dropdown-menu dropdown-menu-end apartado-menu');
                if (puedeConectar && s.estado !== 'ready') accion(menu, s, 'conectar', 'Conectar por QR', 'fas fa-qrcode');
                if (puedeConectar && s.motorActivo) accion(menu, s, 'cerrar', 'Cerrar sesión', 'fas fa-right-from-bracket');
                if (puedeEliminar) accion(menu, s, 'eliminar', 'Eliminar sesión', 'fas fa-trash', 'text-danger');
                if (menu.children.length) { dropdown.append(boton, menu); contenedor.append(dropdown); celda.append(contenedor); }
            }
            tr.append(celda); filas.append(tr);
        });
        if (!visibles.length) { const tr = nodo('tr'); const td = nodo('td', buscar ? 'No hay sesiones que coincidan con la búsqueda.' : 'No hay sesiones creadas para este sistema.', 'text-center text-muted py-4'); td.colSpan = 6; tr.append(td); filas.append(tr); }
        document.getElementById('sesionesConteo').textContent = visibles.length + ' sesiones · Página ' + pagina + ' de ' + paginas;
        document.getElementById('sesionesAnterior').disabled = pagina <= 1;
        document.getElementById('sesionesSiguiente').disabled = pagina >= paginas;
        document.dispatchEvent(new CustomEvent('grid:updated', { detail: { scope: root } }));
    }
    async function cargar() {
        if (cargando || root.dataset.configurado !== '1') return;
        cargando = true;
        try { sesiones = await api('/datos'); pintar(); aviso(error, ''); estado.textContent = 'Actualizado: ' + new Date().toLocaleTimeString('es-MX'); }
        catch (e) { aviso(error, e.message); estado.textContent = 'No se pudo actualizar'; }
        finally { cargando = false; }
    }
    async function consultarQr() {
        const id = qrId, peticion = ++qrPeticion;
        if (!id || document.hidden) return;
        try {
            const datos = await api('/' + id + '/qr');
            if (id !== qrId || peticion !== qrPeticion) return;
            aviso(qrError, '');
            if (datos.estado === 'ready') {
                qrImagen.classList.add('d-none'); qrImagen.removeAttribute('src'); qrEstado.textContent = 'WhatsApp vinculado correctamente.'; await cargar(); return;
            }
            if (datos.qr) { qrImagen.src = datos.qr; qrImagen.classList.remove('d-none'); qrEstado.textContent = 'Escanea el código. Se actualiza automáticamente.'; }
            else { qrImagen.classList.add('d-none'); qrImagen.removeAttribute('src'); qrEstado.textContent = (estados[datos.estado]?.[0] || 'Preparando QR') + '. Esperando el código QR…'; }
        } catch (e) { if (id === qrId && peticion === qrPeticion) { aviso(qrError, e.message); qrImagen.classList.add('d-none'); qrImagen.removeAttribute('src'); } }
        if (id === qrId && peticion === qrPeticion) qrTimer = setTimeout(consultarQr, 5000);
    }
    async function conectar(sesion) {
        if (operando || !puedeConectar) return;
        operando = true; qrId = sesion.id; qrPeticion++; clearTimeout(qrTimer);
        document.getElementById('qrSesionNombre').textContent = sesion.nombre;
        qrEstado.textContent = 'Iniciando sesión y preparando el QR…'; qrImagen.classList.add('d-none'); qrImagen.removeAttribute('src'); aviso(qrError, '');
        bootstrap.Modal.getOrCreateInstance(qrModal).show();
        try { await api('/' + sesion.id + '/iniciar', 'POST'); if (qrId === sesion.id) await consultarQr(); await cargar(); }
        catch (e) { if (qrId === sesion.id) aviso(qrError, e.message); await cargar(); }
        finally { operando = false; }
    }
    qrModal?.addEventListener('hidden.bs.modal', () => { qrId = null; qrPeticion++; clearTimeout(qrTimer); qrImagen.classList.add('d-none'); qrImagen.removeAttribute('src'); });
    document.addEventListener('click', async event => {
        const boton = event.target.closest('[data-sesion-accion]'); if (!boton || operando) return;
        const sesion = sesiones.find(s => s.id === boton.dataset.sesionId); if (!sesion) return;
        if (boton.dataset.sesionAccion === 'conectar') return conectar(sesion);
        const eliminar = boton.dataset.sesionAccion === 'eliminar';
        const confirmacion = await Swal.fire({ icon: 'warning', title: eliminar ? '¿Eliminar esta sesión?' : '¿Cerrar esta sesión?', text: sesion.nombre + ': ' + (eliminar ? 'Se eliminará la sesión y su vinculación con WhatsApp.' : 'Se desvinculará WhatsApp. Podrás volver a conectarlo escaneando un nuevo QR.'), showCancelButton: true, confirmButtonText: eliminar ? 'Eliminar' : 'Cerrar sesión', cancelButtonText: 'Cancelar' });
        if (!confirmacion.isConfirmed || operando) return;
        operando = true;
        try { await api('/' + sesion.id + (eliminar ? '' : '/cerrar'), eliminar ? 'DELETE' : 'POST'); await cargar(); }
        catch (e) { await Swal.fire({ icon: 'error', title: 'No se pudo completar la acción', text: e.message }); }
        finally { operando = false; }
    });
    document.getElementById('formNuevaSesion')?.addEventListener('submit', async event => {
        event.preventDefault(); if (operando) return;
        const boton = document.getElementById('guardarSesion'), avisoNueva = document.getElementById('nuevaSesionError');
        boton.disabled = true; operando = true; aviso(avisoNueva, '');
        try {
            const sesion = await api('', 'POST', { nombre: document.getElementById('nombreSesion').value.trim() });
            const nuevaModal = document.getElementById('nuevaSesion');
            if (nuevaModal.classList.contains('show')) await new Promise(resolve => { nuevaModal.addEventListener('hidden.bs.modal', resolve, { once: true }); bootstrap.Modal.getOrCreateInstance(nuevaModal).hide(); });
            event.target.reset(); await cargar();
            operando = false;
            if (puedeConectar) await conectar(sesion);
            else await Swal.fire({ icon: 'success', title: 'Sesión creada', text: 'Un usuario con permiso para conectar podrá vincular WhatsApp.' });
        } catch (e) { aviso(avisoNueva, e.message); }
        finally { boton.disabled = false; operando = false; }
    });
    document.getElementById('actualizarSesiones').addEventListener('click', cargar);
    document.getElementById('buscarSesion').addEventListener('input', () => { pagina = 1; pintar(); });
    document.getElementById('sesionesAnterior').addEventListener('click', () => { pagina--; pintar(); });
    document.getElementById('sesionesSiguiente').addEventListener('click', () => { pagina++; pintar(); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { cargar(); if (qrId && !operando) { clearTimeout(qrTimer); consultarQr(); } } });
    setInterval(() => { if (!document.hidden && !operando && !document.querySelector('.apartado-menu.show')) cargar(); }, 15000);
    cargar();
})();
