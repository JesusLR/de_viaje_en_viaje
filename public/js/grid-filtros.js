(() => {
    const forms = [...document.querySelectorAll('form[data-grid-filter]')];
    if (!forms.length) return;
    const pending = new Map();
    const regions = (root, scope) => [...root.querySelectorAll('[data-grid-results]')].filter(el => el.dataset.gridResults === scope);
    const findForm = scope => forms.find(form => form.dataset.gridFilter === scope);
    const status = document.createElement('div');
    status.className = 'small text-muted mb-3';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    forms[0].after(status);

    async function refresh(form, url, push = true) {
        const scope = form.dataset.gridFilter;
        const target = new URL(url, location.href);
        if (target.origin !== location.origin || target.pathname !== new URL(form.action).pathname) return;
        pending.get(scope)?.abort();
        const controller = new AbortController();
        pending.set(scope, controller);
        const old = regions(document, scope);
        old.forEach(el => el.setAttribute('aria-busy', 'true'));
        status.textContent = 'Actualizando resultados…';
        status.className = 'small text-muted mb-3';
        try {
            const response = await fetch(target, {signal: controller.signal, credentials: 'same-origin', headers: {Accept: 'application/json'}});
            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(Object.values(error.errors || {}).flat().join(' ') || error.message || 'No se pudieron actualizar los resultados. Intenta de nuevo.');
            }
            if (!response.headers.get('content-type')?.includes('text/html')) throw new Error('La sesión o la respuesta del servidor no es válida.');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = regions(html, scope);
            const freshForm = [...html.querySelectorAll('form[data-grid-filter]')].find(el => el.dataset.gridFilter === scope);
            if (!freshForm || !old.length || fresh.length !== old.length) throw new Error('No se pudo actualizar el grid. Comprueba que tu sesión siga activa.');
            if (controller.signal.aborted) return;
            document.dispatchEvent(new CustomEvent('grid:beforeupdate', {detail: {scope}}));
            fresh.forEach((node, index) => {
                const replacement = document.importNode(node, true);
                replacement.querySelectorAll('script').forEach(script => script.remove());
                if (window.jQuery) window.jQuery(old[index]).find('select.select2-hidden-accessible').each(function () { window.jQuery(this).select2('destroy'); });
                old[index].replaceWith(replacement);
                window.inicializarControles?.(replacement);
            });
            // Keep the filter controls (and keyboard focus), but synchronize server defaults.
            [...form.elements].forEach(input => {
                if (!input.name || !['INPUT', 'SELECT', 'TEXTAREA'].includes(input.tagName)) return;
                const source = [...freshForm.elements].find(el => el.name === input.name && el.type === input.type);
                if (!source) return;
                if (['checkbox', 'radio'].includes(input.type)) input.checked = source.checked;
                else if (input.tagName === 'SELECT' && input.multiple) [...input.options].forEach(option => { option.selected = [...source.selectedOptions].some(selected => selected.value === option.value); });
                else input.value = source.value;
                if (input.tagName === 'SELECT' && window.jQuery) window.jQuery(input).trigger('change.select2');
            });
            if (push && target.href !== location.href) history.pushState({grid: scope}, '', target);
            status.textContent = 'Resultados actualizados.';
            document.dispatchEvent(new CustomEvent('grid:updated', {detail: {scope}}));
        } catch (error) {
            if (error.name === 'AbortError') return;
            status.className = 'small text-danger mb-3';
            status.textContent = error.message || 'No se pudo conectar con el servidor. Intenta de nuevo.';
        } finally {
            if (pending.get(scope) === controller) {
                pending.delete(scope);
                regions(document, scope).forEach(el => el.removeAttribute('aria-busy'));
            }
        }
    }

    document.addEventListener('submit', event => {
        const form = event.target.closest('form[data-grid-filter]');
        if (!form || event.defaultPrevented) return;
        event.preventDefault();
        const target = new URL(form.action);
        target.search = new URLSearchParams(new FormData(form)).toString();
        refresh(form, target);
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
        const clear = link.hasAttribute('data-grid-clear');
        const region = link.closest('[data-grid-results]');
        if (!clear && !(region && link.classList.contains('page-link'))) return;
        const form = clear ? link.closest('form[data-grid-filter]') || forms[0] : findForm(region.dataset.gridResults);
        if (!form) return;
        event.preventDefault();
        refresh(form, link.href);
    });
    window.addEventListener('popstate', () => forms.forEach(form => refresh(form, location.href, false)));
})();
