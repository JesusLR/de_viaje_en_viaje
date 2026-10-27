(() => {
    const periodo = document.getElementById('periodo');
    if (!periodo) return;
    function sync() {
        const historico = periodo.value === 'historico';
        ['desde', 'hasta'].forEach(id => { document.getElementById(id).disabled = historico; });
    }
    periodo.addEventListener('change', sync);
    if (window.jQuery) window.jQuery(periodo).on('change.estadisticas', sync);
    document.addEventListener('grid:updated', sync);
    sync();
})();
