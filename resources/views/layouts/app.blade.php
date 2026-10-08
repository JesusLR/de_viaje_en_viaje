<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Dashboard') - {{ config('app.name', 'Laravel 12') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
        }

        .user-pill {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
        }

        .main-content {
            flex: 1;
            padding: 2rem 1rem;
        }

        .footer-custom {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 0;
            font-size: 0.875rem;
            color: #64748b;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Topbar Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand text-white" href="{{ route('dashboard') }}">
                <i class="fas fa-cubes text-primary me-2"></i>{{ config('app.name', 'Laravel App') }}
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('dashboard') }}"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    @auth
                        <div class="user-pill text-white d-flex align-items-center gap-2">
                            <i class="fas fa-user-circle fs-5 text-info"></i>
                            <span>{{ Auth::user()->name }}</span>
                        </div>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm rounded-pill px-3" id="btnLogout">
                                <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <main class="main-content container">
        @yield('contenido')
    </main>

    <!-- Footer -->
    <footer class="footer-custom text-center">
        <div class="container">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Todos los derechos reservados.</span>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            // Configurar CSRF Token global para todas las peticiones jQuery AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Manejador AJAX para cerrar sesión
            $('#logout-form').on('submit', function (e) {
                e.preventDefault();
                let $form = $(this);

                Swal.fire({
                    title: '¿Deseas cerrar sesión?',
                    text: 'Se finalizará tu sesión actual.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, cerrar sesión',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: $form.attr('action'),
                            type: 'POST',
                            dataType: 'json',
                            success: function (response) {
                                if (response.lSuccess) {
                                    window.location.href = response.redirect || '/login';
                                } else {
                                    window.location.href = '/login';
                                }
                            },
                            error: function () {
                                $form[0].submit();
                            }
                        });
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
