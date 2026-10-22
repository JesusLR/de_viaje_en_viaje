document.querySelectorAll('.apartado-mas').forEach(wrapper => {
    const toggle = wrapper.querySelector('[data-bs-toggle="dropdown"]');
    const menu = wrapper.querySelector('.dropdown-menu');
    // Keep the menu outside the scrollable grid so the last rows are not clipped.
    toggle.addEventListener('shown.bs.dropdown', () => {
        document.body.appendChild(menu);
        bootstrap.Dropdown.getInstance(toggle)?.update();
    });
    toggle.addEventListener('hidden.bs.dropdown', () => wrapper.appendChild(menu));
    menu.addEventListener('click', event => {
        if (event.target.closest('[data-bs-toggle="modal"]')) bootstrap.Dropdown.getInstance(toggle)?.hide();
    });
    menu.addEventListener('keydown', event => {
        if (!['Escape', 'ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        event.stopPropagation();
        if (event.key === 'Escape') {
            bootstrap.Dropdown.getInstance(toggle)?.hide();
            toggle.focus();
            return;
        }
        const items = [...menu.querySelectorAll('.dropdown-item')].filter(item => !item.disabled && item.getClientRects().length);
        if (!items.length) return;
        const current = items.indexOf(document.activeElement);
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
            : event.key === 'ArrowDown' ? (current + 1) % items.length : (current - 1 + items.length) % items.length;
        items[next].focus();
    });
});
