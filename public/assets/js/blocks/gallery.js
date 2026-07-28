(function () {
    'use strict';

    // Простой нативный лайтбокс для блоков галереи (без сторонних библиотек).
    var overlay = null;

    function openLightbox(src, alt) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'cms-lightbox';
            overlay.innerHTML = '<img alt=""><button type="button" class="cms-lightbox__close" aria-label="Закрыть">&times;</button>';
            overlay.addEventListener('click', function () { overlay.classList.remove('is-open'); });
            document.body.appendChild(overlay);
        }
        overlay.querySelector('img').src = src;
        overlay.querySelector('img').alt = alt || '';
        overlay.classList.add('is-open');
    }

    document.querySelectorAll('.block-gallery__item').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var img = link.querySelector('img');
            if (!img) {
                return;
            }
            event.preventDefault();
            openLightbox(link.getAttribute('href') || img.src, img.alt);
        });
    });
})();
