<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Iniciar Sesión') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .auth-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            padding: 2.25rem 2rem;
            text-align: center;
            position: relative;
        }

        .auth-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 4px;
            background: #60a5fa;
            border-radius: 2px;
        }

        .auth-header .brand-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(8px);
        }

        .auth-body {
            padding: 2.25rem 2rem 2rem;
        }

        .form-control {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .input-group-text {
            border-radius: 0.75rem;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            color: #ffffff;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }

        .btn-primary-custom:disabled {
            opacity: 0.7;
            transform: none;
        }
    </style>
    @stack('styles')
</head>
<body>

    @yield('contenido')

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')
</body>
</html>
