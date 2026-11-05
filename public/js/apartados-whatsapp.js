(() => {
    const modal = document.getElementById('whatsappApartado');
    if (!modal) return;
    const form = document.getElementById('formWhatsappApartado');
    const telefono = document.getElementById('whatsappApartadoTelefono');
    const enviar = document.getElementById('whatsappApartadoEnviar');
    const error = document.getElementById('whatsappApartadoError');
    const estado = document.getElementById('whatsappApartadoEstado');
    let actual = null, ocupado = false, enviando = false;
    const aviso = mensaje => { error.textContent = mensaje || ''; error.classList.toggle('d-none', !mensaje); };
    function uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        const bytes = crypto.getRandomValues(new Uint8Array(16)); bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
        const h = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
        return [h.slice(0, 8), h.slice(8, 12), h.slice(12, 16), h.slice(16, 20), h.slice(20)].join('-');
    }
    function bloquear(valor) {
        telefono.disabled = valor; enviar.disabled = valor;
    }
    async function api(url, datos) {
        const response = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(datos) });
        const body = await response.json().catch(() => ({}));
        if (!response.ok || !body.lSuccess) throw Object.assign(new Error(body.mensaje || body.cMensaje || Object.values(body.errors || {})[0]?.[0] || body.message || 'No fue posible completar la solicitud.'), { incierto: body.incierto ?? response.status >= 500 });
        return body.datos;
    }
    modal.addEventListener('show.bs.modal', async event => {
        const origen = event.relatedTarget;
        if (!origen) return;
        const intento = actual = { tipo: origen.dataset.tipo, preparar: origen.dataset.preparar, enviar: origen.dataset.enviar, solicitud: uuid() };
        ocupado = true; enviando = false; form.reset(); aviso(''); bloquear(true);
        document.getElementById('whatsappApartadoDocumento').textContent = intento.tipo === 'comprobante' ? 'Comprobante de pago PDF' : intento.tipo === 'cotizacion' ? 'Cotización PDF' : (intento.tipo === 'itinerario' ? 'Itinerario PDF' : 'Historial de abonos PDF');
        document.getElementById('whatsappApartadoCliente').textContent = '';
        estado.textContent = 'Consultando las sesiones conectadas…';
        try {
            const datos = await api(intento.preparar, { tipo: intento.tipo });
            if (actual !== intento) return;
            telefono.value = datos.telefono || '';
            document.getElementById('whatsappApartadoCliente').textContent = datos.folio + ' · ' + datos.cliente;
            bloquear(false); estado.textContent = 'Revisa el número antes de confirmar. Se utilizará automáticamente la sesión conectada.';
        } catch (e) { if (actual === intento) { aviso(e.message); estado.textContent = ''; } }
        finally { if (actual === intento) ocupado = false; }
    });
    modal.addEventListener('hide.bs.modal', event => { if (enviando) event.preventDefault(); });
    modal.addEventListener('hidden.bs.modal', () => { actual = null; ocupado = false; });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (!actual || ocupado || actual.bloqueado) return;
        const intento = actual;
        const datos = { tipo: intento.tipo, telefono: telefono.value.trim(), solicitud: intento.solicitud };
        ocupado = true; bloquear(true); aviso('');
        try {
            enviando = true; estado.textContent = 'Enviando documento…';
            const resultado = await api(intento.enviar, datos);
            enviando = false;
            bootstrap.Modal.getOrCreateInstance(modal).hide();
            await Swal.fire({ icon: 'success', title: 'Documento enviado', text: resultado.archivo + '\nEnviado a ' + resultado.telefono, timer: 2500, showConfirmButton: false });
        } catch (e) {
            if (actual === intento) {
                if (enviando && e.incierto !== false) intento.bloqueado = true;
                aviso(e.message);
                estado.textContent = intento.bloqueado ? 'Cierra esta ventana y revisa el chat antes de iniciar otro envío.' : '';
            }
        }
        finally { if (actual === intento) { ocupado = false; enviando = false; bloquear(Boolean(intento.bloqueado)); } }
    });
})();
