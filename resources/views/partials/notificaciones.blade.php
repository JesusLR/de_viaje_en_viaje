<div class="dropdown" id="notificaciones" data-url="{{ route('notificaciones.index') }}" data-leer-url="{{ route('notificaciones.leer', ['notificacion' => '__ID__']) }}" data-todas-url="{{ route('notificaciones.leerTodas') }}">
    <button class="user-badge position-relative" type="button" id="campanaNotificaciones" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notificaciones" title="Notificaciones">
        <i class="fas fa-bell" aria-hidden="true"></i><span id="notificacionesContador" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" aria-hidden="true"></span>
    </button>
    <div class="dropdown-menu dropdown-menu-end notificaciones-menu" aria-labelledby="campanaNotificaciones">
        <div class="d-flex justify-content-between align-items-center gap-3 border-bottom px-3 py-2"><strong>Notificaciones</strong><button type="button" id="notificacionesLeerTodas" class="btn btn-link btn-sm p-0" disabled>Marcar todas leídas</button></div>
        <div id="notificacionesLista" class="notificaciones-lista"><p class="text-muted small px-3 py-4 mb-0">Cargando notificaciones…</p></div>
        <p id="notificacionesEstado" class="small text-muted border-top px-3 py-2 mb-0" role="status" aria-live="polite">Se muestran las 20 más recientes.</p>
    </div>
</div>
