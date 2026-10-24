(() => {
    const form = document.getElementById('formCotizacion');
    if (!form) return;
    const button = form.querySelector('button[type="submit"]');
    const errors = document.getElementById('cotizacionErrores');
    let sending = false;

    function showError(message, fields = {}) {
        errors.replaceChildren();
        errors.classList.remove('d-none');
        const heading = document.createElement('strong'); heading.textContent = message;
        errors.append(heading);
        const list = document.createElement('ul'); list.className = 'mb-0 mt-2';
        Object.entries(fields).forEach(([name, messages]) => {
            const input = form.elements.namedItem(name);
            if (input) { input.classList.add('is-invalid'); input.setAttribute('aria-invalid', 'true'); }
            messages.forEach(text => { const item = document.createElement('li'); item.textContent = text; list.append(item); });
        });
        if (list.children.length) errors.append(list);
        errors.focus();
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (sending || !form.reportValidity()) return;
        sending = true;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const original = button.innerHTML;
        button.textContent = 'Enviando solicitud…';
        errors.classList.add('d-none');
        form.querySelectorAll('.is-invalid').forEach(input => { input.classList.remove('is-invalid'); input.removeAttribute('aria-invalid'); });
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.success) {
                let message = 'No se pudo enviar la cotización. Tus datos se conservan para que puedas intentarlo nuevamente.';
                if (response.status === 422) message = 'Revisa los siguientes datos para enviar tu cotización:';
                if (response.status === 419) message = 'Tu sesión ha caducado. Abre el formulario en otra pestaña para renovarla antes de volver a enviarlo.';
                if (response.status === 429) message = 'Has realizado varios intentos. Espera un minuto y vuelve a enviar la solicitud.';
                showError(message, response.status === 422 ? data?.errors ?? {} : {});
                return;
            }
            form.reset();
            // Select2 keeps a separate visual selection; update it after clearing the native controls.
            if (window.jQuery) window.jQuery(form).find('select').trigger('change');
            if (window.Swal) await window.Swal.fire({icon: 'success', title: '¡Cotización enviada con éxito!', text: data.message, confirmButtonText: 'Entendido'});
            else window.alert(data.message);
        } catch {
            showError('No se pudo confirmar el envío. Revisa tu conexión; tus datos siguen en el formulario.');
        } finally {
            sending = false;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.innerHTML = original;
        }
    });
})();
