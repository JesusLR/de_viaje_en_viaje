@extends('layouts.app')
@section('titulo', 'Mensajería')
@section('contenido')
<div class="container-fluid py-4" id="mensajeria-sesiones" data-base="{{ route('mensajeria.index') }}" data-configurado="{{ $configurado ? '1' : '0' }}" data-conectar="{{ auth()->user()->hasPermission('mensajeria.conectar') ? '1' : '0' }}" data-eliminar="{{ auth()->user()->hasPermission('mensajeria.eliminar') ? '1' : '0' }}">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1"><i class="fab fa-whatsapp text-success me-2" aria-hidden="true"></i>Inicio de sesión</h1><p class="text-muted mb-0">Vincula WhatsApp y utiliza una única sesión para enviar documentos.</p></div>
        <div class="d-flex gap-2">
            <button type="button" class="apartado-icono" id="actualizarSesiones" title="Actualizar sesiones" aria-label="Actualizar sesiones" @disabled(!$configurado)><i class="fas fa-rotate" aria-hidden="true"></i></button>
            @can('mensajeria.crear')<button type="button" class="apartado-icono accion-principal" data-bs-toggle="modal" data-bs-target="#nuevaSesion" id="crearSesion" title="Nueva sesión" aria-label="Nueva sesión" @disabled(!$configurado)><i class="fas fa-plus" aria-hidden="true"></i></button>@endcan
        </div>
    </div>
    @unless($configurado)<div class="alert alert-warning">La conexión con OpenWA todavía no está configurada. Solicita su configuración al administrador.</div>@endunless
    <div id="sesionesError" class="alert alert-danger d-none" role="alert"></div>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="row g-3 align-items-end mb-3"><div class="col-md-6"><label for="buscarSesion" class="form-label">Buscar sesión</label><input type="search" class="form-control" id="buscarSesion" placeholder="Nombre, teléfono o cuenta"></div><div class="col-md-6 text-md-end"><small class="text-muted" id="sesionesActualizacion" aria-live="polite">{{ $configurado ? 'Cargando sesiones…' : 'Conexión pendiente' }}</small></div></div>
        <div class="table-responsive"><table class="table align-middle agencia-grid" id="tablaSesiones"><thead><tr><th>Sesión</th><th>Cuenta</th><th>Teléfono</th><th>Estado</th><th>Creación</th><th class="text-end">Acciones</th></tr></thead><tbody id="sesionesFilas"><tr><td colspan="6" class="text-center text-muted py-4">{{ $configurado ? 'Cargando sesiones…' : 'Configura la conexión para consultar sesiones.' }}</td></tr></tbody></table></div>
        <div class="d-flex justify-content-between align-items-center gap-2 mt-3"><small class="text-muted" id="sesionesConteo"></small><div class="d-flex gap-2"><button type="button" class="apartado-icono" id="sesionesAnterior" title="Página anterior" aria-label="Página anterior" disabled><i class="fas fa-chevron-left" aria-hidden="true"></i></button><button type="button" class="apartado-icono" id="sesionesSiguiente" title="Página siguiente" aria-label="Página siguiente" disabled><i class="fas fa-chevron-right" aria-hidden="true"></i></button></div></div>
    </div></div>
</div>
@can('mensajeria.crear')
<div class="modal fade apartado-modal-shell" id="nuevaSesion" tabindex="-1" aria-labelledby="nuevaSesionTitulo" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content venta-detalle-page">
    <div class="modal-header"><h2 class="modal-title h5" id="nuevaSesionTitulo">Nueva sesión de WhatsApp</h2><button type="button" class="apartado-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form id="formNuevaSesion"><div class="modal-body"><label for="nombreSesion" class="form-label fw-semibold">Nombre de la sesión</label><input id="nombreSesion" name="nombre" class="form-control" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9-]+" placeholder="Ej. recepcion" autocomplete="off"><p class="form-text mb-0">De 3 a 50 caracteres: letras sin acentos, números y guiones.</p><div class="alert alert-danger d-none mt-3 mb-0" id="nuevaSesionError" role="alert"></div></div><div class="modal-footer"><button type="button" class="apartado-icono" data-bs-dismiss="modal" title="Cancelar" aria-label="Cancelar"><i class="fas fa-xmark" aria-hidden="true"></i></button><button type="submit" id="guardarSesion" class="apartado-icono accion-principal" title="Crear sesión" aria-label="Crear sesión"><i class="fas fa-check" aria-hidden="true"></i></button></div></form>
</div></div></div>
@endcan
@can('mensajeria.conectar')
<div class="modal fade apartado-modal-shell" id="qrSesion" tabindex="-1" aria-labelledby="qrSesionTitulo" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content venta-detalle-page">
    <div class="modal-header"><h2 class="modal-title h5" id="qrSesionTitulo">Vincular WhatsApp</h2><button type="button" class="apartado-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <div class="modal-body text-center"><p class="fw-semibold text-break" id="qrSesionNombre"></p><p class="text-muted">En WhatsApp abre <strong>Dispositivos vinculados → Vincular un dispositivo</strong> y escanea el código.</p><div id="qrSesionEstado" role="status" aria-live="polite" class="mb-3">Preparando el código QR…</div><img id="qrSesionImagen" class="mensajeria-qr d-none" alt="Código QR para vincular WhatsApp"><div id="qrSesionError" class="alert alert-danger d-none text-start mt-3" role="alert"></div></div>
    <div class="modal-footer"><button type="button" class="apartado-icono" data-bs-dismiss="modal" title="Cerrar" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
</div></div></div>
@endcan
@endsection
@push('styles')<link href="{{ asset('css/apartados-modals.css') }}" rel="stylesheet"><link href="{{ asset('css/agencia-grid.css') }}" rel="stylesheet"><link href="{{ asset('css/mensajeria.css') }}?v={{ filemtime(public_path('css/mensajeria.css')) }}" rel="stylesheet">@endpush
@push('scripts')<script src="{{ asset('js/mensajeria-sesiones.js') }}?v={{ filemtime(public_path('js/mensajeria-sesiones.js')) }}" defer></script>@endpush
