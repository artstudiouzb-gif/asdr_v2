(function () {
    'use strict';

    document.querySelectorAll('.block-slider').forEach(function (slider) {
        var slides = slider.querySelectorAll('.block-slider__slide');
        if (slides.length < 2) {
            return;
        }
        var current = 0;
        var dots = slider.querySelectorAll('[data-slide-index]');
        var status = slider.querySelector('[data-slider-status]');

        function show(index, announce) {
            slides[current].classList.remove('is-active');
            slides[current].setAttribute('aria-hidden', 'true');
            current = (index + slides.length) % slides.length;
            slides[current].classList.add('is-active');
            slides[current].setAttribute('aria-hidden', 'false');
            dots.forEach(function (dot, dotIndex) {
                var active = dotIndex === current;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-current', active ? 'true' : 'false');
            });
            if (announce && status) {
                status.textContent = slides[current].getAttribute('aria-label') || String(current + 1);
            }
        }

        var prev = slider.querySelector('.block-slider__prev');
        var next = slider.querySelector('.block-slider__next');
        if (prev) {
            prev.addEventListener('click', function () { show(current - 1, true); });
        }
        if (next) {
            next.addEventListener('click', function () { show(current + 1, true); });
        }
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                show(Number(dot.getAttribute('data-slide-index')) || 0, true);
            });
        });

        slider.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                show(current - 1, true);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                show(current + 1, true);
            } else if (event.key === 'Home') {
                event.preventDefault();
                show(0, true);
            } else if (event.key === 'End') {
                event.preventDefault();
                show(slides.length - 1, true);
            }
        });

        var startX = null;
        var startY = null;
        slider.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse') { return; }
            startX = event.clientX;
            startY = event.clientY;
        }, { passive: true });
        slider.addEventListener('pointerup', function (event) {
            if (startX === null || startY === null) { return; }
            var dx = event.clientX - startX;
            var dy = event.clientY - startY;
            startX = null;
            startY = null;
            if (Math.abs(dx) < 40 || Math.abs(dx) <= Math.abs(dy)) { return; }
            show(current + (dx < 0 ? 1 : -1), true);
        }, { passive: true });
    });
})();
