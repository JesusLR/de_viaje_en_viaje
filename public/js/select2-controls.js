(function ($) {
    'use strict';

    window.fechaActualAgencia = function () {
        const partes = new Intl.DateTimeFormat('en-US', {
            timeZone: window.agenciaTimezone || 'America/Mexico_City',
            year: 'numeric', month: '2-digit', day: '2-digit'
        }).formatToParts(new Date());
        const valor = tipo => partes.find(parte => parte.type === tipo).value;
        return `${valor('year')}-${valor('month')}-${valor('day')}`;
    };

    window.inicializarControles = function (raiz = document) {
        const $raiz = $(raiz);
        $raiz.find('select').add($raiz.filter('select')).each(function () {
            const $select = $(this);
            if ($select.closest('.swal2-container').length || $select.hasClass('swal2-select')) return;
            if (!$select.hasClass('select2-hidden-accessible')) {
                const $modal = $select.closest('.modal');
                $select.select2({ width: '100%', language: 'es', dropdownParent: $modal.length ? $modal : $(document.body) });
            }
        });
        $raiz.find('input[type="date"]').add($raiz.filter('input[type="date"]')).each(function () {
            if ($(this).closest('.swal2-container').length) return;
            if (!this.value && !this.readOnly && !this.disabled) {
                this.value = window.fechaActualAgencia();
                this.defaultValue = this.value;
            }
        });
    };

    $(function () {
        window.inicializarControles();
        $(document).on('shown.bs.modal shown.bs.collapse', function (evento) {
            window.inicializarControles(evento.target);
            $(evento.target).find('select').trigger('change.select2');
        });
        $(document).on('reset', 'form', function () {
            const formulario = this;
            setTimeout(function () {
                window.inicializarControles(formulario);
                $(formulario).find('select').trigger('change.select2');
            }, 0);
        });
        new MutationObserver(function (cambios) {
            cambios.forEach(cambio => cambio.addedNodes.forEach(nodo => {
                if (nodo.nodeType === 1 && (nodo.matches('select, input[type="date"]') || nodo.querySelector('select, input[type="date"]'))) {
                    window.inicializarControles(nodo);
                }
            }));
        }).observe(document.body, { childList: true, subtree: true });
        document.addEventListener('invalid', function (evento) {
            if ($(evento.target).hasClass('select2-hidden-accessible')) {
                evento.preventDefault();
                $(evento.target).select2('open');
            }
        }, true);
    });
})(jQuery);
