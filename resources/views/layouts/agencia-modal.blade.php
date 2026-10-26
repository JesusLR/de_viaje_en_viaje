<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="{{ asset('logo.ico') }}" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    @include('partials.select2-styles')
    <link href="{{ asset('css/apartados-modals.css') }}" rel="stylesheet">
    <link href="{{ asset('css/apartados-acciones.css') }}?v={{ filemtime(public_path('css/apartados-acciones.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="apartado-modal-page">
    <main class="apartado-modal-main">@yield('contenido')</main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.select2-scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/agencia-alerts.js') }}?v={{ filemtime(public_path('js/agencia-alerts.js')) }}"></script>
    <script>window.userPermissions = @json(auth()->user()?->permissionKeys() ?? []);</script>
<script src="{{ asset('js/permissions-ui.js') }}?v={{ filemtime(public_path('js/permissions-ui.js')) }}"></script>
<script src="{{ asset('js/apartados-acciones.js') }}?v={{ filemtime(public_path('js/apartados-acciones.js')) }}"></script>
@stack('scripts')
</body>
</html>
