(function () {
    const aprobados = new WeakSet();
    const pendientes = new WeakSet();
    document.addEventListener('submit', function (evento) {
        const formulario = evento.target;
        if (!formulario.matches('form[data-confirm]')) return;
        if (aprobados.has(formulario)) { aprobados.delete(formulario); return; }
        evento.preventDefault();
        if (pendientes.has(formulario)) return;
        pendientes.add(formulario);
        const boton = evento.submitter;
        Swal.fire({
            title: formulario.dataset.confirm,
            text: formulario.dataset.confirmText || '',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Sí, continuar', cancelButtonText: 'Volver',
            confirmButtonColor: '#536fe8', cancelButtonColor: '#64748b'
        }).then(function (resultado) {
            pendientes.delete(formulario);
            if (resultado.isConfirmed) {
                aprobados.add(formulario);
                formulario.requestSubmit(boton || undefined);
            }
        });
    }, true);
})();
