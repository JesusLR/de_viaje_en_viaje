@extends('layouts.publico')
@section('titulo', 'Cotiza tu viaje a la Riviera Maya')
@section('contenido')
<section class="landing-section"><div class="container" style="max-width: 960px">
    <div class="landing-section-heading"><div><span class="landing-eyebrow">Tu próximo viaje empieza aquí</span><h1>🌴✨ FORMULARIO DE COTIZACIÓN – VIAJE A LA RIVIERA MAYA ✨🌴</h1><p>Gracias por confiar en De Viaje en Viaje. Por favor completa la siguiente información para enviarte la mejor cotización disponible:</p></div></div>
    @if(session('cotizacion_enviada'))
    <div class="alert alert-success" role="status"><h2 class="h5">¡Recibimos tu solicitud!</h2>Un asesor revisará tus datos para contactarte por WhatsApp.</div>
    @endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Revisa la información:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div id="cotizacionErrores" class="alert alert-danger d-none" role="alert" tabindex="-1"></div>
    <form id="formCotizacion" method="POST" action="{{ route('cotizacion.enviar', [], false) }}" class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
        @csrf
        <div class="row g-4">
            <div class="col-md-7"><label for="nombre" class="form-label">Nombre completo</label><input id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="150" autocomplete="name" class="form-control"></div>
            <div class="col-md-5"><label for="whatsapp" class="form-label">Número de WhatsApp</label><input id="whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp') }}" required maxlength="30" autocomplete="tel" class="form-control" placeholder="Incluye la clave de tu país"></div>
            <div class="col-12"><label for="fechas" class="form-label">¿En qué fechas desea viajar? (fecha exacta o aproximada)</label><input id="fechas" name="fechas" value="{{ old('fechas') }}" required maxlength="255" class="form-control" placeholder="Ej. del 10 al 15 de diciembre o durante enero"></div>
            <fieldset class="col-12"><legend class="h6">¿Cuántos días y cuántas noches desea hospedarse?</legend><div class="row g-3">
                <div class="col-6"><label for="dias" class="form-label">Días</label><input id="dias" name="dias" type="number" min="1" max="365" value="{{ old('dias') }}" required class="form-control"></div>
                <div class="col-6"><label for="noches" class="form-label">Noches</label><input id="noches" name="noches" type="number" min="0" max="365" value="{{ old('noches') }}" required class="form-control"></div>
            </div></fieldset>
            <div class="col-12"><label for="personas" class="form-label">¿Cuántas personas en total viajarán?</label><input id="personas" name="personas" type="number" min="1" max="500" value="{{ old('personas') }}" required class="form-control"></div>
            <div class="col-md-6"><label for="adultos" class="form-label">¿Cuántos adultos?</label><input id="adultos" name="adultos" type="number" min="1" max="500" value="{{ old('adultos') }}" required class="form-control"></div>
            <div class="col-md-6"><label for="menores" class="form-label">¿Cuántos menores?</label><input id="menores" name="menores" type="number" min="0" max="100" value="{{ old('menores', 0) }}" required class="form-control"></div>
            <div class="col-12"><label for="edades" class="form-label">En caso de menores, ¿qué edades tienen?</label><input id="edades" name="edades" value="{{ old('edades') }}" maxlength="500" class="form-control" placeholder="Ej. 4, 8, 12" aria-describedby="edadesAyuda"><div id="edadesAyuda" class="form-text">Escribe las edades de 0 a 17 años separadas por comas. Sin menores, deja este campo vacío.</div></div>
            <div class="col-12"><label for="hotel" class="form-label">¿Busca algún hotel en específico? (opcional)</label><input id="hotel" name="hotel" value="{{ old('hotel') }}" maxlength="255" class="form-control" placeholder="Nombre del hotel, si tienes alguno en mente"></div>
            <div class="col-md-7"><label for="tipo_hotel" class="form-label">¿Qué tipo de hotel desea?</label><input id="tipo_hotel" name="tipo_hotel" value="{{ old('tipo_hotel') }}" required maxlength="255" class="form-control" placeholder="Ej. todo incluido, frente al mar, 5 estrellas"></div>
            <div class="col-md-5"><label for="ambiente" class="form-label">¿Prefiere ambiente familiar, solo adultos o ambos?</label><select id="ambiente" name="ambiente" required class="form-select"><option value="">Selecciona una opción</option>@foreach(['Familiar', 'Solo adultos', 'Ambos'] as $ambiente)<option @selected(old('ambiente') === $ambiente)>{{ $ambiente }}</option>@endforeach</select></div>
            <div class="col-md-8"><label for="presupuesto" class="form-label">¿Qué presupuesto aproximado tiene por persona?</label><input id="presupuesto" name="presupuesto" type="number" min="0.01" step="0.01" max="9999999999.99" value="{{ old('presupuesto') }}" required class="form-control"></div>
            <div class="col-md-4"><label for="moneda" class="form-label">Moneda</label><select id="moneda" name="moneda" class="form-select">@foreach(['MXN', 'USD', 'EUR'] as $moneda)<option @selected(old('moneda', 'MXN') === $moneda)>{{ $moneda }}</option>@endforeach</select></div>
            <div class="col-12"><button class="landing-button" type="submit">Solicitar cotización <i class="fas fa-paper-plane" aria-hidden="true"></i></button><p class="small text-muted mt-3 mb-0">Usaremos tus datos para atender esta solicitud. El envío no confirma una reserva.</p></div>
        </div>
    </form>
</div></section>
@endsection

@push('scripts')
<script src="{{ asset('js/cotizacion-publica.js') }}?v={{ filemtime(public_path('js/cotizacion-publica.js')) }}" defer></script>
@endpush
