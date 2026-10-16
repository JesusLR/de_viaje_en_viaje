(() => {
    document.querySelectorAll('[data-galeria]').forEach(gallery => {
        const thumbnails = Array.from(gallery.querySelectorAll('[data-galeria-miniatura]'));
        let current = 0;
        function show(index) {
            current = (index + thumbnails.length) % thumbnails.length;
            const selected = thumbnails[current];
            gallery.querySelectorAll('[data-galeria-imagen], [data-galeria-ampliada]').forEach(img => {
                img.src = selected.dataset.imagen;
                img.alt = selected.dataset.descripcion;
            });
            gallery.querySelectorAll('[data-galeria-contador]').forEach(counter => {
                counter.textContent = `${current + 1} / ${thumbnails.length}`;
            });
            thumbnails.forEach((button, position) => {
                if (!button.closest('.experiencia-grid')) {
                    button.classList.toggle('activa', position === current);
                    button.setAttribute('aria-pressed', String(position === current));
                }
            });
            const strip = gallery.querySelector('.experiencia-miniaturas');
            if (!strip.classList.contains('experiencia-grid')) strip.scrollLeft = selected.offsetLeft - strip.offsetLeft - (strip.clientWidth - selected.clientWidth) / 2;
        }
        thumbnails.forEach((button, index) => button.addEventListener('click', () => show(index)));
        gallery.querySelectorAll('[data-galeria-anterior]').forEach(button => button.addEventListener('click', () => show(current - 1)));
        gallery.querySelectorAll('[data-galeria-siguiente]').forEach(button => button.addEventListener('click', () => show(current + 1)));
        gallery.addEventListener('keydown', event => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                show(current + (event.key === 'ArrowLeft' ? -1 : 1));
            }
        });
    });
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced || !('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('viajes-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.landing-package, .landing-benefits article, .landing-section-heading, .landing-contact').forEach(element => {
        element.classList.add('viajes-reveal');
        observer.observe(element);
    });
})();
