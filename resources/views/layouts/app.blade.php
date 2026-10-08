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
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --topbar-height: 64px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            overflow-x: hidden;
            margin: 0;
        }

        /* Layout Container */
        #app-wrapper {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: #f8fafc;
            transition: margin-left 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        #sidebar.toggled {
            margin-left: calc(-1 * var(--sidebar-width));
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: #ffffff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-section {
            padding: 1.25rem 1.25rem 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0.5rem 0.75rem;
            margin: 0;
        }

        .sidebar-nav .nav-item {
            margin-bottom: 0.25rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .sidebar-nav .nav-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }

        .sidebar-nav .nav-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }

        .sidebar-nav .nav-link.active {
            color: #ffffff;
            background-color: var(--sidebar-active);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .sidebar-subnav {
            list-style: none;
            padding-left: 1.5rem;
            margin: 0;
        }

        .sidebar-subnav .nav-link {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        /* Page Content Wrapper */
        #content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Topbar Header */
        .topbar {
            height: var(--topbar-height);
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #475569;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: background 0.2s;
        }

        .toggle-btn:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0.85rem;
            background-color: #f1f5f9;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }

        /* Main Content Body */
        .main-container {
            flex: 1;
            padding: 2rem;
        }

        /* Footer */
        .footer-custom {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                position: fixed;
                height: 100vh;
            }
            #sidebar.toggled {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <div id="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside id="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand">
                <i class="fas fa-cubes text-primary me-2"></i>
                <span>{{ config('app.name', 'Laravel') }}</span>
            </a>

            <!-- Menú Principal -->
            <div class="sidebar-section">Menú Principal</div>
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(Route::has('categoria.index'))
                <li class="nav-item">
                    <a href="{{ route('categoria.index') }}" class="nav-link {{ request()->routeIs('categoria.*') ? 'active' : '' }}">
                        <i class="fas fa-tags"></i>
                        <span>Categorías</span>
                    </a>
                </li>
                @endif
            </ul>

            <!-- Módulo Administración -->
            <div class="sidebar-section">Módulos</div>
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="#menuAdmin" data-bs-toggle="collapse" class="nav-link justify-content-between {{ request()->routeIs('admin.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('admin.*') ? 'true' : 'false' }}">
                        <div>
                            <i class="fas fa-user-shield me-1"></i>
                            <span>Administración</span>
                        </div>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.*') ? 'show' : '' }}" id="menuAdmin">
                        <ul class="sidebar-subnav mt-1">
                            <li class="nav-item">
                                <a href="{{ route('admin.usuarios.index') }}" class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                                    <i class="fas fa-users me-1"></i>
                                    <span>Usuarios</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <div id="content-wrapper">
            <!-- Topbar Header -->
            <header class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button id="sidebarToggle" class="toggle-btn" title="Alternar Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="fw-bold mb-0 text-slate-800">@yield('titulo', 'Panel de Control')</h5>
                </div>

                <div class="user-menu">
                    @auth
                        <div class="user-badge">
                            <i class="fas fa-user-circle fs-5 text-primary"></i>
                            <span>{{ Auth::user()->name }}</span>
                        </div>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnLogout" title="Cerrar Sesión">
                                <i class="fas fa-sign-out-alt me-1"></i> Salir
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <!-- Main Body -->
            <main class="main-container">
                @yield('contenido')
            </main>

            <!-- Footer -->
            <footer class="footer-custom text-center text-md-start">
                <div class="container-fluid d-flex justify-content-between flex-wrap gap-2">
                    <span>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Todos los derechos reservados.</span>
                    <span class="text-muted">Sistema de gestión interna.</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            // Configurar CSRF Token global para solicitudes jQuery AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Toggle para ocultar/mostrar Sidebar
            $('#sidebarToggle').on('click', function () {
                $('#sidebar').toggleClass('toggled');
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
