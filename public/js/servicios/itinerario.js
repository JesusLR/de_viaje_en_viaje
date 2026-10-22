(() => {
    const form = document.getElementById('formItinerario');
    if (!form) return;
    const editable = form.dataset.editable === '1';
    const container = document.getElementById('itinerarioDias');
    const json = document.getElementById('diasJson');
    const fields = node => [...node.querySelectorAll('[data-field]')].filter(el => el.closest('.actividad') === (node.matches('.actividad') ? node : null));
    const read = node => Object.fromEntries(fields(node).map(el => [el.dataset.field, el.value]));
    function fill(node, values) {
        fields(node).forEach(el => { el.value = values[el.dataset.field] || ''; el.disabled = !editable; });
        if (!editable) node.querySelectorAll('.controles').forEach(el => el.remove());
    }
    function renumber() {
        [...container.children].forEach((day, i) => {
            day.querySelector('.dia-numero').textContent = `Día ${i + 1}`;
            [...day.querySelector('.actividades').children].forEach((activity, n) => {
                activity.querySelector('.actividad-numero').textContent = `Actividad ${n + 1}`;
            });
        });
        document.getElementById('itinerarioVacio').hidden = !!container.children.length;
    }
    function activity(day, values = {}) {
        const node = document.getElementById('actividadTemplate').content.firstElementChild.cloneNode(true);
        fill(node, values);
        day.querySelector('.actividades').append(node);
        renumber();
    }
    function day(values = {}) {
        const node = document.getElementById('diaTemplate').content.firstElementChild.cloneNode(true);
        fill(node, values);
        container.append(node);
        (values.actividades || []).forEach(item => activity(node, item));
        renumber();
    }
    let initial;
    try { initial = JSON.parse(json.value); } catch { initial = []; }
    (Array.isArray(initial) ? initial : []).forEach(day);
    renumber();
    document.getElementById('agregarDia')?.addEventListener('click', () => day());
    container.addEventListener('click', event => {
        if (!editable) return;
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const node = button.closest('.actividad') || button.closest('.itinerario-dia');
        if (button.dataset.action === 'add') activity(node);
        if (button.dataset.action === 'remove') {
            if (!window.confirm('¿Eliminar este elemento del itinerario?')) return;
            node.remove();
        }
        if (button.dataset.action === 'up' && node.previousElementSibling) node.parentNode.insertBefore(node, node.previousElementSibling);
        if (button.dataset.action === 'down' && node.nextElementSibling) node.parentNode.insertBefore(node.nextElementSibling, node);
        renumber();
    });
    form.addEventListener('submit', () => {
        json.value = JSON.stringify([...container.children].map(node => ({
            ...read(node), actividades: [...node.querySelector('.actividades').children].map(read),
        })));
    });
})();
