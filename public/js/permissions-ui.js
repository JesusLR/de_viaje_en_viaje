(function () {
    const allowed = new Set(window.userPermissions || []);
    const can = key => key === 'finanzas.balance' ? allowed.has('finanzas.ingresos') && allowed.has('finanzas.egresos')
        : key === 'dashboard.balance' ? allowed.has('dashboard.ingresos') && allowed.has('dashboard.egresos') : allowed.has(key);
    const parts = window.location.pathname.split('/').filter(Boolean);
    const module = parts[0] === 'admin' ? (parts[1] === 'usuarios' ? 'usuarios' : 'permisos')
        : parts[0] === 'catalogos' ? parts[1]
        : parts[0] === 'servicios' ? (parts[1] === 'paquetes' ? 'servicios' : parts[1]) : null;
    function apply(root) {
        root.querySelectorAll('[data-permission]').forEach(element => {
            if (!can(element.dataset.permission)) {
                if (element.classList.contains('dashboard-indicador')) element.parentElement.remove();
                else element.remove();
            }
        });
        if (module && !can(module + '.crear')) root.querySelectorAll('#btnNuevo').forEach(el => el.remove());
        root.querySelectorAll('[data-bs-target="#nuevoMovimiento"]').forEach(el => { if (!can('finanzas.crear')) el.remove(); });
        root.querySelectorAll('[data-url]').forEach(el => {
            const url = new URL(el.dataset.url, window.location.origin);
            const section = url.searchParams.get('seccion');
            const permission = {abono: 'apartados.abonos', paquetes: 'apartados.paquetes', nombre: el.dataset.operationModule + '.nombre'}[section];
            if (permission && !can(permission)) el.remove();
        });
        if (module === 'servicios') {
            if (!can('servicios.costos.ver')) {
                root.querySelectorAll('#dPrecioCompra').forEach(el => el.closest('.col-md-4')?.remove());
                root.querySelectorAll('#detPrecioCompra').forEach(el => el.closest('tr')?.remove());
            } else if (!can('servicios.costos.editar')) root.querySelectorAll('#dPrecioCompra').forEach(el => { el.disabled = true; el.required = false; });
            if (!can('servicios.utilidad')) root.querySelectorAll('[id]').forEach(el => {
                if (/^(preview|det).*(ganancia|utilidad)/i.test(el.id)) el.closest('tr')?.remove();
            });
            const editing = Number(document.getElementById('iID')?.value || 0) > 0;
            if (!can('servicios.precios')) root.querySelectorAll('#dPrecioVenta, #cMoneda').forEach(el => { el.disabled = editing; el.required = el.id === 'dPrecioVenta' && !editing; });
            if (!can('servicios.precios')) root.querySelectorAll('[name="lPrecioMenorActivo"], #dPrecioMenor').forEach(el => { el.disabled = true; });
            if (!can('servicios.promociones')) root.querySelectorAll('[name]').forEach(el => {
                if (/Promocion|Descuento/.test(el.name)) el.disabled = true;
            });
            if (!can('servicios.activar')) root.querySelectorAll('[name="lActivo"]').forEach(el => el.disabled = true);
        }
        document.querySelectorAll('.sidebar-nav>li').forEach(el => {
            const menu = el.querySelector('.sidebar-subnav');
            if (menu && !menu.querySelector('a')) el.remove();
        });
    }
    document.addEventListener('DOMContentLoaded', () => {
        apply(document);
        if (window.jQuery) window.jQuery(document).on('shown.bs.modal', () => apply(document));
        new MutationObserver(changes => {
            if (changes.some(change => change.addedNodes.length)) apply(document);
        }).observe(document.body, {childList: true, subtree: true});
        document.addEventListener('submit', event => {
            const form = event.target;
            if (form.dataset.permission && !can(form.dataset.permission)) event.preventDefault();
        }, true);
    });
})();
