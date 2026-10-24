<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $content['brand'] }} | Viajes y experiencias</title>
    <meta name="description" content="{{ $content['hero_description'] }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/landing-module.css') }}?v={{ filemtime(public_path('css/landing-module.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/viajes-inmersivo.css') }}?v={{ filemtime(public_path('css/viajes-inmersivo.css')) }}" rel="stylesheet">
</head>
<body class="viajes-landing">
    <nav class="navbar navbar-expand-lg landing-nav">
        <div class="container">
            <a class="navbar-brand landing-brand" href="{{ route('landing') }}"><img src="{{ $settings->logo_path ? route('landing.image', ['image' => 'logo', 'v' => $settings->updated_at?->timestamp]) : asset('img/logo.jpg') }}" alt="Logo de {{ $content['brand'] }}"><span>{{ $content['brand'] }}</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLanding" aria-controls="navLanding" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navLanding"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                <li><a class="nav-link" href="{{ route('cotizacion.formulario') }}">Cotiza tu viaje</a></li><li><a class="nav-link" href="#paquetes">Paquetes</a></li><li><a class="nav-link" href="{{ route('catalogo.publico.index') }}">Catálogo</a></li><li><a class="nav-link" href="#nosotros">Nuestra agencia</a></li><li><a class="nav-link" href="#contacto">Contacto</a></li>
                <li><a class="landing-button compact" href="{{ $whatsappUrl ?? '#contacto' }}" @if($whatsappUrl) target="_blank" rel="noopener" @endif><i class="fas fa-comment-dots" aria-hidden="true"></i>Planea tu viaje</a></li>
            </ul></div>
        </div>
    </nav>
    <main>
        <section class="landing-hero" id="inicio">
            <div class="container"><div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="landing-eyebrow"><i class="fas fa-compass me-2" aria-hidden="true"></i>{{ $content['hero_label'] }}</span>
                    <h1>{{ $content['hero_title'] }}</h1><p>{{ $content['hero_description'] }}</p>
                    <div class="d-flex flex-wrap gap-3"><a class="landing-button" href="{{ route('catalogo.publico.index') }}">{{ $content['hero_button'] }}<i class="fas fa-arrow-right" aria-hidden="true"></i></a><a class="landing-button secondary" href="{{ $whatsappUrl ?? '#contacto' }}" @if($whatsappUrl) target="_blank" rel="noopener" @endif>Hablar con un asesor<i class="fas fa-comment-dots" aria-hidden="true"></i></a></div>
                    <a class="landing-hero-note" href="#paquetes"><span>Descubre nuestros viajes</span><i class="fas fa-arrow-down" aria-hidden="true"></i></a>
                </div>
                <div class="col-lg-6"><div class="landing-hero-visual">
                    @php($slides = $settings->slides())
                    @if(count($slides))
                    <div id="portadaCarrusel" class="carousel slide" data-bs-interval="5000" aria-label="Imágenes de nuestros viajes">
                        <div class="carousel-inner">
                            @foreach($slides as $index => $slide)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}"><img src="{{ route('landing.image', ['image' => 'slide-'.$index, 'v' => $settings->updated_at?->timestamp]) }}" alt="Inspiración para tu próximo viaje {{ $index + 1 }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif></div>
                            @endforeach
                        </div>
                        @if(count($slides) > 1)
                        <div class="carousel-indicators">@foreach($slides as $index => $slide)<button type="button" data-bs-target="#portadaCarrusel" data-bs-slide-to="{{ $index }}" class="{{ $loop->first ? 'active' : '' }}" @if($loop->first) aria-current="true" @endif aria-label="Ver imagen {{ $index + 1 }}"></button>@endforeach</div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#portadaCarrusel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Imagen anterior</span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#portadaCarrusel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Imagen siguiente</span></button>
                        <button type="button" id="portadaPausa" class="landing-carousel-pause" aria-label="Pausar carrusel" aria-pressed="false"><i class="fas fa-pause" aria-hidden="true"></i></button>
                        @endif
                    </div>
                    @else
                    <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=2000&q=85" alt="Inspiración para tu próximo viaje" fetchpriority="high">
                    @endif
                    <div class="landing-visual-caption"><span><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span><div><strong>Un nuevo lugar. Una nueva historia.</strong><small>Empieza a planear tu próxima experiencia.</small></div></div>
                </div></div>
            </div></div>
        </section>
        <section class="landing-section py-5" id="cotiza"><div class="container"><div class="card border-0 shadow-sm rounded-4 p-4 p-md-5"><div class="row g-4 align-items-center"><div class="col-lg-8"><span class="landing-eyebrow">Riviera Maya te espera</span><h2 class="mt-2">¡COTIZA TU PRÓXIMO VIAJE DE FORMA RÁPIDA Y FÁCIL!</h2><p class="text-muted mb-0">Cuéntanos cuándo quieres viajar, con quién y qué tienes en mente. Te ayudamos a encontrar opciones para ti.</p></div><div class="col-lg-4"><a class="landing-button" href="{{ route('cotizacion.formulario') }}">Cotizar mi viaje <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div></div></div></div></section>
        <section class="landing-section" id="paquetes"><div class="container">
            <div class="landing-section-heading"><div><span class="landing-eyebrow">EXPLORA NUESTROS PAQUETES</span><h2>{{ $content['packages_title'] }}</h2><p>{{ $content['packages_description'] }}</p></div><a class="landing-text-link" href="{{ route('catalogo.publico.index') }}">Ver catálogo completo<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a></div>
            <div class="row g-4">
                @forelse($paquetes as $paquete)
                    <div class="col-md-6 col-xl-4">@include('publico.partials.paquete-card')</div>
                @empty
                    <div class="col-12"><div class="landing-empty"><i class="fas fa-compass mb-3" aria-hidden="true"></i><h3>Tu próximo viaje está por comenzar</h3><p>Consulta con nuestro equipo las próximas opciones disponibles.</p><a class="landing-text-link" href="#contacto">Contactar a la agencia<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a></div></div>
                @endforelse
            </div>
        </div></section>
        <section class="landing-about landing-section" id="nosotros"><div class="container">
            <div class="row g-5 align-items-center"><div class="col-lg-5"><span class="landing-eyebrow">VIAJA CON {{ $content['brand'] }}</span><h2>{{ $content['about_title'] }}</h2><p>{{ $content['about_description'] }}</p></div>
                <div class="col-lg-7"><div class="landing-benefits">@foreach($content['benefits'] as $benefit)<article><span><i class="fas {{ ['fa-headset', 'fa-compass', 'fa-shield-alt'][$loop->index] }}" aria-hidden="true"></i></span><div><h3>{{ $benefit['title'] }}</h3><p>{{ $benefit['description'] }}</p></div></article>@endforeach</div></div>
            </div>
        </div></section>
        <section class="landing-section" id="contacto"><div class="container"><div class="landing-contact">
            <div><span class="landing-eyebrow">ESTAMOS PARA AYUDARTE</span><h2>{{ $content['contact_title'] }}</h2><p>{{ $content['contact_description'] }}</p></div>
            <div class="landing-contact-details">
                @if($whatsappUrl)<a class="landing-button" href="{{ $whatsappUrl }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp" aria-hidden="true"></i>Escríbenos por WhatsApp</a>@endif
                @if($content['phone'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $content['phone']) }}"><i class="fas fa-phone" aria-hidden="true"></i>{{ $content['phone'] }}</a>@endif
                @if($content['email'])<a href="mailto:{{ $content['email'] }}"><i class="fas fa-envelope" aria-hidden="true"></i>{{ $content['email'] }}</a>@endif
                @if($content['facebook'])<a href="{{ $content['facebook'] }}" target="_blank" rel="noopener"><i class="fab fa-facebook" aria-hidden="true"></i>Facebook</a>@endif
                @if($content['instagram'])<a href="{{ $content['instagram'] }}" target="_blank" rel="noopener"><i class="fab fa-instagram" aria-hidden="true"></i>Instagram</a>@endif
                @if($content['address'])<span><i class="fas fa-map-marker-alt" aria-hidden="true"></i>{{ $content['address'] }}</span>@endif
            </div>
        </div>
        @if($content['maps_embed_url'] || $content['maps_url'])
            <div class="landing-map mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><h3><i class="fas fa-map-marker-alt me-2" aria-hidden="true"></i>Visítanos</h3>@if($content['maps_url'])<a class="landing-text-link" href="{{ $content['maps_url'] }}" target="_blank" rel="noopener">Abrir en Google Maps<i class="fas fa-external-link-alt ms-2" aria-hidden="true"></i></a>@endif</div>
                @if($content['maps_embed_url'])<iframe src="{{ $content['maps_embed_url'] }}" title="Ubicación de {{ $content['brand'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>@endif
            </div>
        @endif
        </div></section>
    </main>
    <footer class="landing-footer"><div class="container"><span>© {{ date('Y') }} {{ $content['brand'] }}. Todos los derechos reservados.</span></div></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/viajes-publico.js') }}?v={{ filemtime(public_path('js/viajes-publico.js')) }}" defer></script>
<script>
const portada = document.getElementById('portadaCarrusel');
const pausa = document.getElementById('portadaPausa');
if (portada && pausa) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const carrusel = new bootstrap.Carousel(portada, {interval: 5000});
    let paused = reducedMotion;
    function actualizarPausa() {
        pausa.setAttribute('aria-pressed', String(paused));
        pausa.setAttribute('aria-label', paused ? 'Reanudar carrusel' : 'Pausar carrusel');
        pausa.innerHTML = '<i class="fas ' + (paused ? 'fa-play' : 'fa-pause') + '" aria-hidden="true"></i>';
        if (paused) carrusel.pause(); else carrusel.cycle();
    }
    actualizarPausa();
    pausa.addEventListener('click', () => { paused = !paused; actualizarPausa(); });
}
</script>
</body></html>
