<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', '') }} | Agencia de Viajes y Experiencias Inolvidables</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            overflow-x: hidden;
        }

        /* Navbar Landing */
        .navbar-landing {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: #ffffff !important;
            letter-spacing: -0.5px;
        }

        .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: #60a5fa !important;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0.85) 100%), 
                        url('https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            min-height: 85vh;
            display: flex;
            align-items: center;
            color: #ffffff;
            position: relative;
            padding: 7rem 0 5rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 1.25rem;
        }

        .hero-title span {
            background: linear-gradient(135deg, #60a5fa 0%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            max-width: 600px;
            margin-bottom: 2.5rem;
        }

        /* Search Box */
        .search-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            padding: 1.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            color: #1e293b;
        }

        .form-select, .form-control {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            font-size: 0.95rem;
        }

        .form-select:focus, .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        /* Destination Cards */
        .card-destination {
            border: none;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            height: 100%;
        }

        .card-destination:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .card-img-wrapper {
            position: relative;
            height: 240px;
            overflow: hidden;
        }

        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .card-destination:hover .card-img-wrapper img {
            transform: scale(1.08);
        }

        .badge-price {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            color: #ffffff;
            font-weight: 700;
            padding: 0.4rem 0.9rem;
            border-radius: 2rem;
            font-size: 0.9rem;
        }

        .badge-tag {
            position: absolute;
            bottom: 1rem;
            left: 1rem;
            background: #2563eb;
            color: #ffffff;
            font-weight: 600;
            padding: 0.3rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        /* Features Section */
        .feature-box {
            padding: 2rem;
            border-radius: 1.25rem;
            background: #f8fafc;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-box:hover {
            background: #ffffff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            border-radius: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
        }

        /* Footer Landing */
        .footer-landing {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 4rem 0 2rem;
        }

        .footer-title {
            color: #ffffff;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-landing fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('landing') }}">
                <i class="fas fa-paper-plane text-primary"></i>
                <span>{{ config('app.name', '') }}</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLanding">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navLanding">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="#inicio">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#destinos">Destinos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#nosotros">Por qué elegirnos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="inicio">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-6">
                    <span class="badge bg-primary bg-opacity-20 text-info px-3 py-2 rounded-pill fw-semibold mb-3">
                        <i class="fas fa-compass me-1"></i> La mejor agencia de viajes de México
                    </span>
                    <h1 class="hero-title">Explora el Mundo a <span>Tu Manera</span></h1>
                    <p class="hero-subtitle">
                        Descubre destinos fascinantes, paquetes todo incluido y experiencias inolvidables diseñadas para hacer tus sueños realidad.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#destinos" class="btn btn-primary rounded-pill px-4 py-3 fw-bold">
                            Ver Destinos <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        <a href="#contacto" class="btn btn-outline-light rounded-pill px-4 py-3 fw-bold">
                            Cotizar Viaje
                        </a>
                    </div>
                </div>

                <!-- Buscador rápido -->
                <div class="col-lg-6">
                    <div class="search-card">
                        <h4 class="fw-bold mb-3"><i class="fas fa-search-location text-primary me-2"></i>Encuentra tu próximo viaje</h4>
                        <form onsubmit="event.preventDefault(); alert('Buscador disponible próximamente');">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold">Destino deseado</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <select class="form-select">
                                        <option selected>Selecciona un destino...</option>
                                        <option>Cancún & Riviera Maya</option>
                                        <option>París, Francia</option>
                                        <option>Tokio, Japón</option>
                                        <option>Santorini, Grecia</option>
                                        <option>Machu Picchu, Perú</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold">Fecha de Salida</label>
                                    <input type="date" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold">Pasajeros</label>
                                    <select class="form-select">
                                        <option>1 Pasajero</option>
                                        <option selected>2 Pasajeros</option>
                                        <option>Family (3-5)</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm">
                                <i class="fas fa-plane-departure me-2"></i>Buscar Paquetes Disponibles
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Destinos Destacados -->
    <section class="py-5" id="destinos">
        <div class="container py-4">
            <div class="text-center max-width-600 mx-auto mb-5">
                <span class="text-primary fw-bold text-uppercase tracking-wider small">Destinos Populares</span>
                <h2 class="fw-bold fs-1 mt-1">Paquetes Más Solicitados</h2>
                <p class="text-muted">Elige entre nuestros destinos preferidos por miles de viajeros.</p>
            </div>

            <div class="row g-4">
                <!-- Destino 1 -->
                <div class="col-md-4">
                    <div class="card card-destination">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80" alt="Cancún">
                            <span class="badge-price">Desde $699 USD</span>
                            <span class="badge-tag">Playa & Sol</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-warning small"><i class="fas fa-star"></i> 4.9 (120 opiniones)</span>
                                <span class="text-muted small"><i class="far fa-clock me-1"></i>5 Días / 4 Noches</span>
                            </div>
                            <h5 class="fw-bold">Cancún & Riviera Maya</h5>
                            <p class="text-muted small">Playas turquesa, resorts todo incluido y cenotes mágicos.</p>
                        </div>
                    </div>
                </div>

                <!-- Destino 2 -->
                <div class="col-md-4">
                    <div class="card card-destination">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=800&q=80" alt="París">
                            <span class="badge-price">Desde $1,299 USD</span>
                            <span class="badge-tag">Romance & Cultura</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-warning small"><i class="fas fa-star"></i> 5.0 (210 opiniones)</span>
                                <span class="text-muted small"><i class="far fa-clock me-1"></i>7 Días / 6 Noches</span>
                            </div>
                            <h5 class="fw-bold">París, Francia</h5>
                            <p class="text-muted small">Recorre la ciudad del amor, el Louvre y la emblemática Torre Eiffel.</p>
                        </div>
                    </div>
                </div>

                <!-- Destino 3 -->
                <div class="col-md-4">
                    <div class="card card-destination">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1503899036084-c55cdd92da26?auto=format&fit=crop&w=800&q=80" alt="Tokio">
                            <span class="badge-price">Desde $1,850 USD</span>
                            <span class="badge-tag">Aventura & Futuro</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-warning small"><i class="fas fa-star"></i> 4.9 (95 opiniones)</span>
                                <span class="text-muted small"><i class="far fa-clock me-1"></i>10 Días / 9 Noches</span>
                            </div>
                            <h5 class="fw-bold">Tokio & Kioto, Japón</h5>
                            <p class="text-muted small">Templos milenarios, cerezos en flor y tecnología futurista.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Por qué elegirnos -->
    <section class="py-5 bg-light" id="nosotros">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="text-primary fw-bold text-uppercase tracking-wider small">Nuestra Promesa</span>
                <h2 class="fw-bold fs-1 mt-1">¿Por qué viajar con {{ config('app.name', '') }}?</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                        <h5 class="fw-bold mb-2">Viajes Seguros</h5>
                        <p class="text-muted small mb-0">Seguro de viajero incluido y asistencia médica 24/7 en cualquier destino.</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-tags"></i></div>
                        <h5 class="fw-bold mb-2">Mejores Precios</h5>
                        <p class="text-muted small mb-0">Garantizamos tarifas exclusivas en vuelos y hoteles 5 estrellas.</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-headset"></i></div>
                        <h5 class="fw-bold mb-2">Soporte 24/7</h5>
                        <p class="text-muted small mb-0">Agentes de viajes dedicados antes, durante y después de tu itinerario.</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                        <h5 class="fw-bold mb-2">Pagos Flexibles</h5>
                        <p class="text-muted small mb-0">Reserva con enganche mínimo y paga a meses sin intereses.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Landing -->
    <footer class="footer-landing" id="contacto">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h5 class="footer-title d-flex align-items-center gap-2">
                        <i class="fas fa-paper-plane text-primary"></i> {{ config('app.name', '') }}
                    </h5>
                    <p class="small">
                        Tu agencia de viajes de confianza. Creamos itinerarios únicos para que vivas experiencias mágicas e inolvidables alrededor del mundo.
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="footer-title">Contacto</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i> Av. Reforma 123, Ciudad de México</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-primary"></i> +52 (55) 1234 5678</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2 text-primary"></i> contacto@deviaje.com</li>
                    </ul>
                </div>
            </div>

            <hr class="border-secondary my-4">
            <div class="d-flex justify-content-between flex-wrap small">
                <span>&copy; {{ date('Y') }} {{ config('app.name', '') }}. Todos los derechos reservados.</span>
                <span>Desarrollado con Laravel 12 & Blade</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
