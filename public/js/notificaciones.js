(() => {
    const root = document.getElementById('notificaciones');
    if (!root) return;
    const bell = document.getElementById('campanaNotificaciones');
    const badge = document.getElementById('notificacionesContador');
    const list = document.getElementById('notificacionesLista');
    const all = document.getElementById('notificacionesLeerTodas');
    const status = document.getElementById('notificacionesEstado');
    let loading = false;

    async function mark(url) {
        const response = await fetch(url, {method: 'POST', credentials: 'same-origin', headers: {
            Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }});
        if (!response.ok) throw new Error('No se pudo marcar la notificación como leída.');
    }

    async function refresh() {
        if (loading || document.hidden) return;
        loading = true;
        try {
            const response = await fetch(root.dataset.url, {credentials: 'same-origin', headers: {Accept: 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error('No se pudieron actualizar las notificaciones.');
            const data = await response.json();
            badge.textContent = data.sin_leer > 99 ? '99+' : data.sin_leer;
            badge.classList.toggle('d-none', data.sin_leer === 0);
            bell.setAttribute('aria-label', `Notificaciones: ${data.sin_leer} sin leer`);
            all.disabled = data.sin_leer === 0;
            list.replaceChildren();
            data.notificaciones.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'notificacion-item' + (item.leida ? '' : ' sin-leer');
                const title = document.createElement('strong'); title.textContent = item.titulo;
                const message = document.createElement('span'); message.textContent = item.mensaje;
                const date = document.createElement('small'); date.textContent = item.fecha + (item.leida ? ' · Leída' : ' · Sin leer');
                button.append(title, message, date);
                button.addEventListener('click', async () => {
                    button.disabled = true;
                    try {
                        await mark(root.dataset.leerUrl.replace('__ID__', encodeURIComponent(item.id)));
                        if (item.url) window.location.assign(item.url);
                        else await refresh();
                    } catch (error) { status.textContent = error.message; button.disabled = false; }
                });
                list.append(button);
            });
            if (!data.notificaciones.length) {
                const empty = document.createElement('p'); empty.className = 'text-muted small px-3 py-4 mb-0';
                empty.textContent = 'No tienes notificaciones.'; list.append(empty);
            }
            status.textContent = 'Se muestran las 20 más recientes.';
        } catch (error) { status.textContent = error.message; }
        finally { loading = false; }
    }
    all.addEventListener('click', async () => {
        all.disabled = true;
        try { await mark(root.dataset.todasUrl); await refresh(); }
        catch (error) { status.textContent = error.message; all.disabled = false; }
    });
    bell.addEventListener('show.bs.dropdown', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    refresh();
    window.setInterval(refresh, 30000);
})();
