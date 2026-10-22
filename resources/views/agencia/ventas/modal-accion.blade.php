<div class="modal fade apartado-modal-shell" id="accionApartado" tabindex="-1" aria-labelledby="accionApartadoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div class="apartado-modal-heading"><span class="apartado-modal-symbol"><i id="accionApartadoIcono" class="fas fa-receipt" aria-hidden="true"></i></span><h5 class="modal-title mb-0" id="accionApartadoTitulo">Apartado</h5></div><button type="button" class="apartado-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
        <div class="modal-body p-0"><div id="accionApartadoCarga" class="apartado-modal-loading" role="status"><i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>Cargando…</div><iframe id="accionApartadoContenido" class="apartado-modal-frame" title="Acción del apartado"></iframe></div>
    </div></div>
</div>
@push('scripts')
<script>
const modalApartado = document.getElementById('accionApartado');
const contenidoApartado = document.getElementById('accionApartadoContenido');
let apartadoModificado = false;
let observadorApartado;
const cargaApartado = document.getElementById('accionApartadoCarga');
modalApartado.addEventListener('show.bs.modal', function (evento) {
    const boton = evento.relatedTarget;
    document.getElementById('accionApartadoTitulo').textContent = boton.dataset.titulo;
    contenidoApartado.title = boton.dataset.titulo;
    document.getElementById('accionApartadoIcono').className = boton.querySelector('i')?.className || 'fas fa-receipt';
    const seccion = new URL(boton.dataset.url, window.location.origin).searchParams.get('seccion');
    modalApartado.querySelector('.modal-dialog').classList.toggle('modal-xl', ['detalle', 'historial', 'itinerario'].includes(seccion));
    modalApartado.querySelector('.modal-dialog').classList.toggle('modal-lg', ['abono', 'paquetes'].includes(seccion));
    observadorApartado?.disconnect();
    cargaApartado.classList.remove('d-none');
    contenidoApartado.style.opacity = '0';
    contenidoApartado.style.height = '280px';
    apartadoModificado = false;
    contenidoApartado.src = boton.dataset.url;
});
contenidoApartado.addEventListener('load', function () {
    if (!modalApartado.classList.contains('show')) return;
    cargaApartado.classList.add('d-none');
    contenidoApartado.style.opacity = '1';
    const ajustarAltura = function () {
        const principal = contenidoApartado.contentDocument?.querySelector('main');
        if (principal) contenidoApartado.style.height = Math.min(Math.max(principal.scrollHeight + 2, 180), window.innerHeight * .72) + 'px';
    };
    ajustarAltura();
    observadorApartado?.disconnect();
    if (contenidoApartado.contentDocument?.body) {
        observadorApartado = new ResizeObserver(ajustarAltura);
        observadorApartado.observe(contenidoApartado.contentDocument.body);
    }
    if (contenidoApartado.contentDocument) contenidoApartado.contentDocument.addEventListener('submit', function (evento) {
        if (!evento.defaultPrevented) apartadoModificado = true;
    });
});
modalApartado.addEventListener('hidden.bs.modal', function () {
    observadorApartado?.disconnect();
    contenidoApartado.src = 'about:blank';
    if (apartadoModificado) window.location.reload();
});
</script>
@endpush
@push('styles')
<link href="{{ asset('css/apartados-modals.css') }}" rel="stylesheet">
@endpush
