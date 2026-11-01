(function () {
    const slider = document.querySelector('[data-hero-slider]');
    if (!slider) {
        return;
    }

    const slides = Array.from(slider.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(slider.querySelectorAll('[data-hero-dot]'));
    if (slides.length < 2) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const interval = 6000;
    let current = 0;
    let timer = null;

    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach(function (slide, i) {
            const active = i === current;
            slide.classList.toggle('is-active', active);
            slide.toggleAttribute('aria-hidden', !active);
            const link = slide.querySelector('a');
            if (link) {
                link.tabIndex = active ? 0 : -1;
            }
        });
        dots.forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === current);
            if (i === current) {
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.removeAttribute('aria-current');
            }
        });
    }

    function start() {
        if (reduceMotion || timer) {
            return;
        }
        timer = window.setInterval(function () { show(current + 1); }, interval);
    }

    function stop() {
        window.clearInterval(timer);
        timer = null;
    }

    function go(index) {
        stop();
        show(index);
        start();
    }

    slider.querySelector('[data-hero-prev]').addEventListener('click', function () { go(current - 1); });
    slider.querySelector('[data-hero-next]').addEventListener('click', function () { go(current + 1); });
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () { go(Number(dot.dataset.heroDot)); });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);

    slider.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') {
            go(current - 1);
        } else if (e.key === 'ArrowRight') {
            go(current + 1);
        }
    });

    let touchX = null;
    slider.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
        if (touchX === null) {
            return;
        }
        const dx = e.changedTouches[0].clientX - touchX;
        touchX = null;
        if (Math.abs(dx) > 40) {
            go(dx < 0 ? current + 1 : current - 1);
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    start();
})();
