@extends('layouts.app')
@section('titulo','Conversaciones')
@section('contenido')
<div class="apartados-panel" id="conversaciones" data-base="{{ route('conversaciones.index') }}">
<div id="chatSinConexion" role="status" aria-live="polite">No hay una sesión de WhatsApp conectada.</div>
<div class="chat-page-title"><h3>Chats</h3><p>WhatsApp de la agencia · Conversaciones y atención al cliente</p></div>
<div id="chatError" class="alert alert-warning d-none" role="alert"></div>
<div class="chat-layout"><aside class="chat-sidebar"><div class="chat-sidebar-title"><strong>Conversaciones</strong><span>WhatsApp</span></div><input id="chatBuscar" type="search" maxlength="150" class="form-control mb-3" placeholder="Buscar por nombre o teléfono" aria-label="Buscar conversación"><label for="chatFiltro" class="form-label">Estado</label><select id="chatFiltro" class="form-select"><option value="">Todos los estados</option><option value="sin_asignar">Sin asignar</option><option value="en_atencion">En atención</option><option value="atendida">Atendida</option></select><div id="chatLista" class="list-group mt-3"></div><div class="d-flex gap-2 mt-2"><button id="chatAnterior" class="btn btn-outline-secondary">Anterior</button><button id="chatSiguiente" class="btn btn-outline-secondary">Siguiente</button></div></aside>
<section class="chat-thread"><div class="chat-empty">Selecciona una conversación para comenzar</div><div id="chatPanel" class="d-none"><header class="chat-heading"><button type="button" id="chatVolver" class="btn btn-light">←</button><h5 id="chatNombre"></h5><div id="chatCliente" class="d-flex flex-wrap gap-2 mb-2"></div><p id="chatResponsable" class="text-muted"></p>
</header><details class="chat-tools"><summary>Responsable y seguimiento</summary>
@can('conversaciones.asignar')<div class="mb-3"><label for="chatUsuario" class="form-label">Responsable</label><select id="chatUsuario" class="form-select" aria-label="Responsable"></select></div>@endcan
@can('conversaciones.atender')<div class="d-flex gap-2 mb-3"><button class="btn btn-outline-primary" id="chatCerrar">Marcar atendida</button><button class="btn btn-outline-secondary" id="chatReabrir">Reabrir atención</button></div>@endcan
</details><div class="chat-paging d-flex gap-2"><button type="button" id="chatAnteriores" class="btn btn-outline-secondary">Mensajes anteriores</button><button type="button" id="chatRecientes" class="btn btn-outline-secondary">Más recientes</button></div><div id="chatMensajes" class="border rounded p-3 mb-3" style="overflow:auto;white-space:pre-wrap" aria-live="polite"></div>
<p id="chatEnvioAviso" class="alert alert-info m-2 d-none" role="status"></p>
@can('conversaciones.responder')
<div id="chatAdjuntoVista" class="chat-staged-file d-none"><span id="chatAdjuntoNombre"></span><button type="button" id="chatQuitarAdjunto" class="btn btn-light" title="Quitar adjunto" aria-label="Quitar adjunto"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
<form id="chatRespuesta">
<input id="chatPdf" type="file" class="d-none" aria-label="Seleccionar archivo">
<button type="button" id="chatClip" class="btn chat-clip" title="Adjuntar archivo (máximo 15 MB)" aria-label="Adjuntar documento"><i class="fas fa-paperclip" aria-hidden="true"></i></button>
<label for="chatTexto" class="form-label">Mensaje para el cliente</label><textarea id="chatTexto" class="form-control" maxlength="4000" required rows="2" placeholder="Escribe un mensaje…" title="Enter para enviar · Shift + Enter para salto de línea" aria-keyshortcuts="Enter"></textarea>
<button type="submit" class="btn btn-primary chat-send" title="Enviar" aria-label="Enviar"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
</form>@endcan
<details class="chat-tools"><summary>Adjuntos, notas e historial interno</summary>
@can('conversaciones.nota')<form id="chatNota" class="mt-3"><label for="chatNotaTexto" class="form-label">Nota interna (no se envía al cliente)</label><textarea id="chatNotaTexto" class="form-control" maxlength="4000" required rows="2"></textarea><button class="btn btn-outline-primary mt-2">Guardar nota</button></form>@endcan

<h6 class="mt-4">Notas y movimientos</h6><div id="chatEventos"></div>
</details></div></section></div></div>
<div class="modal fade apartado-modal-shell" id="chatImagenModal" tabindex="-1" aria-labelledby="chatImagenTitulo" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="chatImagenTitulo">Imagen de la conversación</h5><button type="button" class="apartado-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button></div><div class="modal-body text-center"><img id="chatImagenAmpliada" alt="Imagen de la conversación" style="max-width:100%;max-height:75dvh;object-fit:contain"></div></div></div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/apartados-modals.css') }}"><link rel="stylesheet" href="{{ asset('css/conversaciones.css') }}?v={{ filemtime(public_path('css/conversaciones.css')) }}"><style>#conversaciones .card{border:1px solid #e8edf4;border-radius:16px}#conversaciones .btn{border-radius:10px;font-size:13px}#conversaciones .form-control,#conversaciones .form-select{border-radius:10px}#conversaciones #chatMensajes>div{overflow-wrap:anywhere}@media(max-width:768px){#app-wrapper:has(#conversaciones) .topbar{flex-wrap:wrap;height:auto;min-height:64px;padding:12px 16px;gap:8px}#app-wrapper:has(#conversaciones) .topbar h5{font-size:16px}#app-wrapper:has(#conversaciones) .main-container{padding:16px}#conversaciones .d-flex{flex-wrap:wrap}#conversaciones .card-body{padding:14px}}</style><link rel="stylesheet" href="{{ asset('css/agencia-grid.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/conversaciones.js') }}?v={{ filemtime(public_path('js/conversaciones.js')) }}" defer></script>@endpush
