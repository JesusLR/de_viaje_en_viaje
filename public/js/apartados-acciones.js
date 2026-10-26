(() => {
    const registered = new Map();
    const permission = key => new Set(window.userPermissions || []).has(key);
    const actions = menu => [...menu.querySelectorAll('a.dropdown-item, button.dropdown-item')];

    function prepareAction(element) {
        const controls = element.matches('form') ? element.querySelectorAll('button, a') : [element];
        controls.forEach(control => {
            const label = control.getAttribute('title') || control.getAttribute('aria-label') || 'Ver opciones';
            const danger = control.matches('.accion-cancelar, .text-danger, .btn-outline-danger, .btn-danger');
            const success = control.matches('.accion-abono, .text-success, .btn-outline-success, .btn-success');
            ['apartado-icono', 'accion-cancelar', 'accion-abono', 'btn', 'btn-sm', 'btn-primary', 'btn-secondary', 'btn-light', 'btn-danger', 'btn-success', 'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-danger', 'btn-outline-success'].forEach(name => control.classList.remove(name));
            control.classList.add('dropdown-item');
            if (danger) control.classList.add('text-danger');
            if (success) control.classList.add('text-success');
            if (!control.textContent.trim()) {
                const span = document.createElement('span'); span.textContent = label;
                control.append(span);
            }
        });
    }

    function convertCell(cell, table) {
        if (cell.querySelector('.apartado-mas')) return;
        cell.querySelectorAll('[data-permission]').forEach(element => {
            if (!permission(element.dataset.permission)) element.remove();
        });
        const items = [...cell.querySelectorAll('a, button, form')].filter(element => !element.parentElement.closest('form'));
        if (!items.length) return;
        const wrapper = document.createElement('div'); wrapper.className = 'dropdown apartado-mas';
        const toggle = document.createElement('button'); toggle.type = 'button'; toggle.className = 'apartado-icono';
        toggle.dataset.bsToggle = 'dropdown'; toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Más opciones'); toggle.title = 'Más opciones';
        const icon = document.createElement('i'); icon.className = 'fas fa-ellipsis-vertical'; icon.setAttribute('aria-hidden', 'true'); toggle.append(icon);
        const menu = document.createElement('ul'); menu.className = 'dropdown-menu dropdown-menu-end apartado-menu';
        if (table?.id) menu.dataset.gridTable = table.id;
        items.forEach(item => { prepareAction(item); const li = document.createElement('li'); li.append(item); menu.append(li); });
        wrapper.append(toggle, menu);
        const align = document.createElement('div'); align.className = 'apartado-acciones'; align.append(wrapper);
        cell.replaceChildren(align);
    }

    function closeMenus() {
        registered.forEach(({toggle}) => bootstrap.Dropdown.getInstance(toggle)?.hide());
    }

    function refresh() {
        registered.forEach(({toggle, menu}, wrapper) => {
            if (!wrapper.isConnected) {
                bootstrap.Dropdown.getInstance(toggle)?.dispose(); menu.remove(); registered.delete(wrapper);
            }
        });
        // Action-column headers identify both server-rendered grids and DataTables.
        document.querySelectorAll('table').forEach(table => {
            const headers = [...(table.tHead?.rows[0]?.cells || [])];
            headers.forEach((header, index) => {
                if (header.textContent.trim().toLowerCase() !== 'acciones') return;
                [...table.tBodies].forEach(body => [...body.rows].forEach(row => {
                    if (row.cells.length === headers.length && row.cells[index]?.colSpan === 1) convertCell(row.cells[index], table);
                }));
            });
        });
        document.querySelectorAll('[data-grid-actions]').forEach(cell => convertCell(cell, cell.closest('table')));
        document.querySelectorAll('.apartado-mas').forEach(wrapper => {
            if (registered.has(wrapper)) return;
            const toggle = wrapper.querySelector('[data-bs-toggle="dropdown"]');
            const menu = wrapper.querySelector('.dropdown-menu');
            if (!toggle || !menu) return;
            if (wrapper.closest('table')?.id) menu.dataset.gridTable = wrapper.closest('table').id;
            registered.set(wrapper, {toggle, menu});
            toggle.addEventListener('shown.bs.dropdown', () => {
                document.body.append(menu);
                bootstrap.Dropdown.getInstance(toggle)?.update();
            });
            toggle.addEventListener('hidden.bs.dropdown', () => wrapper.append(menu));
            menu.addEventListener('click', event => {
                const action = event.target.closest('a.dropdown-item, button.dropdown-item');
                if (action && !action.closest('form')) bootstrap.Dropdown.getInstance(toggle)?.hide();
            });
            menu.addEventListener('keydown', event => {
                if (!['Escape', 'ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key) || event.target.matches('input, textarea, select')) return;
                event.preventDefault(); event.stopPropagation();
                if (event.key === 'Escape') { bootstrap.Dropdown.getInstance(toggle)?.hide(); toggle.focus(); return; }
                const items = actions(menu).filter(item => !item.disabled && item.getClientRects().length);
                if (!items.length) return;
                const current = items.indexOf(document.activeElement);
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : event.key === 'ArrowDown' ? (current + 1) % items.length : (current - 1 + items.length) % items.length;
                items[next].focus();
            });
        });
        registered.forEach(({menu}, wrapper) => {
            const empty = !actions(menu).length;
            if (wrapper.hidden !== empty) wrapper.hidden = empty;
        });
    }
    window.inicializarAccionesApartados = refresh;
    let scheduled = false;
    const schedule = () => {
        if (scheduled) return;
        scheduled = true;
        queueMicrotask(() => { scheduled = false; refresh(); });
    };
    function start() {
        refresh();
        new MutationObserver(schedule).observe(document.body, {childList: true, subtree: true});
        document.addEventListener('grid:beforeupdate', closeMenus);
        document.addEventListener('grid:updated', refresh);
        if (window.jQuery) window.jQuery(document).on('preDraw.dt', 'table', closeMenus).on('draw.dt', 'table', refresh);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
