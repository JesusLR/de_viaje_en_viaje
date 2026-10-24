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
    @include('partials.select2-styles')

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
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            height: 100dvh;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-width: thin;
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

        #content-wrapper {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease-in-out;
        }

        #sidebar.toggled + #content-wrapper {
            margin-left: 0;
        }

        #sidebar > .sidebar-brand,
        #sidebar > .sidebar-section,
        #sidebar > .sidebar-nav {
            flex-shrink: 0;
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
            }
            #sidebar.toggled {
                margin-left: 0;
            }
            #content-wrapper {
                margin-left: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/perfil-module.css') }}?v={{ filemtime(public_path('css/perfil-module.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}?v={{ filemtime(public_path('css/notificaciones.css')) }}">
    @stack('styles')
</head>
<body>

    <div id="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside id="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand d-flex align-items-center gap-2">
                <img src="{{ $logoAgenciaUrl }}" alt="Logo" class="rounded-circle border border-2 border-white shadow-sm" style="height: 38px; width: 38px; object-fit: contain; background: white;">
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

                {{-- @if(Route::has('categoria.index'))
                <li class="nav-item">
                    <a href="{{ route('categoria.index') }}" class="nav-link {{ request()->routeIs('categoria.*') ? 'active' : '' }}">
                        <i class="fas fa-tags"></i>
                        <span>Categorías</span>
                    </a>
                </li>
                @endif --}}
            </ul>

            @canany(['usuarios.ver', 'permisos.ver', 'servicios.ver', 'itinerarios.ver', 'cotizaciones.ver', 'cotizaciones_servicios.ver', 'vender.ver', 'apartados.ver', 'ventas.ver', 'finanzas.ver', 'configuracion.ver', 'clientes.ver', 'categorias.ver', 'destinos.ver', 'proveedores.ver'])
            <!-- Módulo Administración y Catálogos -->
            <div class="sidebar-section">Módulos</div>
            <ul class="sidebar-nav">
                <!-- Administración -->
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
                            @can('permisos.ver')
                            <li class="nav-item"><a href="{{ route('admin.permisos.index') }}" class="nav-link {{ request()->routeIs('admin.permisos.*') ? 'active' : '' }}"><i class="fas fa-user-lock me-1"></i><span>Roles y permisos</span></a></li>
                            @endcan
                            @can('usuarios.ver')
<li class="nav-item">
                                <a href="{{ route('admin.usuarios.index') }}" class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                                    <i class="fas fa-users me-1"></i>
                                    <span>Usuarios</span>
                                </a>
                            </li>
@endcan
                        </ul>
                    </div>
                </li>

                <!-- Módulo Agencia -->
                <li class="nav-item mt-1">
                    <a href="#menuAgencia" data-bs-toggle="collapse" class="nav-link justify-content-between {{ request()->routeIs('servicios.*', 'agencia.*', 'itinerarios.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('servicios.*', 'agencia.*', 'itinerarios.*') ? 'true' : 'false' }}" aria-controls="menuAgencia">
                        <div>
                            <i class="fas fa-plane me-1"></i>
                            <span>Agencia</span>
                        </div>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('servicios.*', 'agencia.*', 'itinerarios.*') ? 'show' : '' }}" id="menuAgencia">
                        <ul class="sidebar-subnav mt-1">
                            @can('servicios.ver')
<li class="nav-item">
                                <a href="{{ route('servicios.paquetes.index') }}" class="nav-link {{ request()->routeIs('servicios.*') ? 'active' : '' }}">
                                    <i class="fas fa-boxes me-1"></i>
                                    <span>Servicios y Paquetes</span>
                                </a>
                            </li>
@endcan
                            @can('itinerarios.ver')
                            <li class="nav-item"><a href="{{ route('itinerarios.index') }}" class="nav-link {{ request()->routeIs('itinerarios.*') ? 'active' : '' }}"><i class="fas fa-route me-1" aria-hidden="true"></i><span>Itinerarios</span></a></li>
                            @endcan
                            @can('vender.ver')
<li class="nav-item">
                                <a href="{{ route('agencia.vender.index') }}" class="nav-link {{ request()->routeIs('agencia.vender.*') ? 'active' : '' }}">
                                    <i class="fas fa-shopping-cart me-1"></i>
                                    <span>Vender</span>
                                </a>
                            </li>
@endcan
                            @can('apartados.ver')
<li class="nav-item">
                                <a href="{{ route('agencia.apartados.index') }}" class="nav-link {{ request()->routeIs('agencia.apartados.*') ? 'active' : '' }}">
                                    <i class="fas fa-clipboard-list me-1"></i><span>Apartados</span>
                                </a>
                            </li>
@endcan
                            @can('ventas.ver')
<li class="nav-item">
                                <a href="{{ route('agencia.ventas.index') }}" class="nav-link {{ request()->routeIs('agencia.ventas.*') ? 'active' : '' }}">
                                    <i class="fas fa-list me-1"></i><span>Ventas</span>
                                </a>
                            </li>
@endcan
                            @can('finanzas.ver')
<li class="nav-item">
                                <a href="{{ route('agencia.finanzas.index') }}" class="nav-link {{ request()->routeIs('agencia.finanzas.*') ? 'active' : '' }}">
                                    <i class="fas fa-wallet me-1"></i><span>Finanzas</span>
                                </a>
                            </li>
@endcan
                        </ul>
                    </div>
                </li>

                @canany(['cotizaciones.ver', 'cotizaciones_servicios.ver'])
                <li class="nav-item mt-1">
                    <a href="#menuAtencionCliente" data-bs-toggle="collapse" class="nav-link justify-content-between {{ request()->routeIs('cotizaciones.*', 'atencion.cotizaciones.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('cotizaciones.*', 'atencion.cotizaciones.*') ? 'true' : 'false' }}" aria-controls="menuAtencionCliente"><div><i class="fas fa-headset me-1" aria-hidden="true"></i><span>Atención al cliente</span></div><i class="fas fa-chevron-down small" aria-hidden="true"></i></a>
                    <div class="collapse {{ request()->routeIs('cotizaciones.*', 'atencion.cotizaciones.*') ? 'show' : '' }}" id="menuAtencionCliente"><ul class="sidebar-subnav mt-1">
                        @can('cotizaciones.ver')
                        <li class="nav-item"><a href="{{ route('cotizaciones.index') }}" class="nav-link {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}"><i class="fas fa-globe me-1" aria-hidden="true"></i><span>Cotizaciones web</span></a></li>
                        @endcan
                        @can('cotizaciones_servicios.ver')
                        <li class="nav-item"><a href="{{ route('atencion.cotizaciones.index') }}" class="nav-link {{ request()->routeIs('atencion.cotizaciones.*') ? 'active' : '' }}"><i class="fas fa-file-invoice-dollar me-1" aria-hidden="true"></i><span>Cotizaciones</span></a></li>
                        @endcan
                    </ul></div>
                </li>
                @endcanany

                <!-- Módulo Catálogos -->
                <li class="nav-item mt-1">
                    <a href="#menuConfiguracion" data-bs-toggle="collapse" class="nav-link justify-content-between {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('configuracion.*') ? 'true' : 'false' }}">
                        <div><i class="fas fa-cog me-1"></i><span>Configuración</span></div><i class="fas fa-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('configuracion.*') ? 'show' : '' }}" id="menuConfiguracion">
                        <ul class="sidebar-subnav mt-1">@can('configuracion.ver')
<li class="nav-item"><a href="{{ route('configuracion.landing.edit') }}" class="nav-link {{ request()->routeIs('configuracion.landing.*') ? 'active' : '' }}"><i class="fas fa-globe me-1"></i><span>Página de inicio</span></a></li>
@endcan</ul>
                    </div>
                </li>
                <li class="nav-item mt-1">
                    <a href="#menuCatalogos" data-bs-toggle="collapse" class="nav-link justify-content-between {{ request()->routeIs('catalogos.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('catalogos.*') ? 'true' : 'false' }}">
                        <div>
                            <i class="fas fa-folder-open me-1"></i>
                            <span>Catálogos</span>
                        </div>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('catalogos.*') ? 'show' : '' }}" id="menuCatalogos">
                        <ul class="sidebar-subnav mt-1">
                            @can('clientes.ver')
<li class="nav-item">
                                <a href="{{ route('catalogos.clientes.index') }}" class="nav-link {{ request()->routeIs('catalogos.clientes.*') ? 'active' : '' }}">
                                    <i class="fas fa-address-book me-1"></i>
                                    <span>Clientes</span>
                                </a>
                            </li>
@endcan
                            @can('categorias.ver')
<li class="nav-item">
                                <a href="{{ route('catalogos.categorias.index') }}" class="nav-link {{ request()->routeIs('catalogos.categorias.*') ? 'active' : '' }}">
                                    <i class="fas fa-tags me-1"></i>
                                    <span>Categorías</span>
                                </a>
                            </li>
@endcan
                            @can('destinos.ver')
<li class="nav-item">
                                <a href="{{ route('catalogos.destinos.index') }}" class="nav-link {{ request()->routeIs('catalogos.destinos.*') ? 'active' : '' }}">
                                    <i class="fas fa-map-marked-alt me-1"></i>
                                    <span>Destinos</span>
                                </a>
                            </li>
@endcan
                            @can('proveedores.ver')
<li class="nav-item">
                                <a href="{{ route('catalogos.proveedores.index') }}" class="nav-link {{ request()->routeIs('catalogos.proveedores.*') ? 'active' : '' }}">
                                    <i class="fas fa-truck-loading me-1"></i>
                                    <span>Proveedores</span>
                                </a>
                            </li>
@endcan
                        </ul>
                    </div>
                </li>
            </ul>
            @endcanany
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
                        @include('partials.notificaciones')
                        <div class="dropdown">
                            <button class="user-badge perfil-menu-boton" type="button" id="menuPerfil" data-bs-toggle="dropdown" aria-expanded="false">
                                @if(Auth::user()->profile_photo_path)
                                    <img class="perfil-avatar-navbar" src="{{ route('perfil.photo', ['v' => Auth::user()->updated_at?->timestamp]) }}" alt="Foto de perfil">
                                @else
                                    <i class="fas fa-user-circle fs-5 text-primary" aria-hidden="true"></i>
                                @endif
                                <span>{{ Auth::user()->name }}</span><i class="fas fa-chevron-down small" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end perfil-menu" aria-labelledby="menuPerfil">
                                <li><a class="dropdown-item" href="{{ route('perfil.edit') }}"><i class="fas fa-user-edit me-2" aria-hidden="true"></i>Editar perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><form id="logout-form" action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger" id="btnLogout"><i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Cerrar sesión</button>
                                </form></li>
                            </ul>
                        </div>
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
    @include('partials.select2-scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/agencia-alerts.js') }}?v={{ filemtime(public_path('js/agencia-alerts.js')) }}"></script>

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
    <script>window.userPermissions = @json(auth()->user()?->permissionKeys() ?? []);</script>
    <script src="{{ asset('js/permissions-ui.js') }}?v={{ filemtime(public_path('js/permissions-ui.js')) }}"></script>
    <script src="{{ asset('js/grid-filtros.js') }}?v={{ filemtime(public_path('js/grid-filtros.js')) }}"></script>
    <script src="{{ asset('js/notificaciones.js') }}?v={{ filemtime(public_path('js/notificaciones.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
