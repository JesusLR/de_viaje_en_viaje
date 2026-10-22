<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Catálogo de viajes') | {{ $content['brand'] }}</title>
    <meta name="description" content="@yield('descripcion', 'Explora los servicios y paquetes de nuestra agencia. Consulta precios, promociones y fechas de viaje.')">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    @include('partials.select2-styles')
    <link href="{{ asset('css/landing-module.css') }}?v={{ filemtime(public_path('css/landing-module.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/catalogo-publico.css') }}?v={{ filemtime(public_path('css/catalogo-publico.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/viajes-inmersivo.css') }}?v={{ filemtime(public_path('css/viajes-inmersivo.css')) }}" rel="stylesheet">
</head>
<body class="catalogo-publico">
    <nav class="navbar navbar-expand-lg landing-nav"><div class="container">
        <a class="navbar-brand landing-brand" href="{{ route('landing') }}"><img src="{{ $settings->logoUrl() }}" alt="Logo de {{ $content['brand'] }}"><span>{{ $content['brand'] }}</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navCatalogo" aria-controls="navCatalogo" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navCatalogo"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
            <li><a class="nav-link" href="{{ route('landing') }}">Inicio</a></li>
            <li><a class="nav-link active" href="{{ route('catalogo.publico.index') }}">Catálogo</a></li>
            <li><a class="nav-link" href="{{ route('landing') }}#contacto">Contacto</a></li>
            <li><a class="landing-button compact" href="{{ $whatsappUrl ?? route('landing').'#contacto' }}" @if($whatsappUrl) target="_blank" rel="noopener" @endif><i class="fas fa-comment-dots" aria-hidden="true"></i>Hablar con un asesor</a></li>
        </ul></div>
    </div></nav>
    <main>@yield('contenido')</main>
    <footer class="landing-footer"><div class="container d-flex flex-wrap justify-content-between gap-3"><span>© {{ date('Y') }} {{ $content['brand'] }}. Todos los derechos reservados.</span><a href="{{ route('landing') }}#contacto">Contacto de la agencia</a></div></footer>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @include('partials.select2-scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('agencia.partials.avisos')
    <script src="{{ asset('js/grid-filtros.js') }}?v={{ filemtime(public_path('js/grid-filtros.js')) }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/viajes-publico.js') }}?v={{ filemtime(public_path('js/viajes-publico.js')) }}" defer></script>
</body>
</html>
