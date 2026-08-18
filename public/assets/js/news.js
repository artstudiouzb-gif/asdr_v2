(function () {
    'use strict';

    // --- Скелетоны: снимаем состояние загрузки, когда картинка готова ---
    function clearSkeleton(img) {
        var box = img.closest('.skeleton');
        if (box) { box.classList.remove('skeleton'); }
    }
    document.querySelectorAll('.skeleton img').forEach(function (img) {
        if (img.complete && img.naturalWidth > 0) {
            clearSkeleton(img);
        } else {
            img.addEventListener('load', function () { clearSkeleton(img); });
            img.addEventListener('error', function () {
                // Fallback обложки (YouTube hqdefault) при ошибке загрузки.
                var fb = img.getAttribute('data-fallback');
                if (fb && img.src !== fb) { img.src = fb; }
                else { clearSkeleton(img); }
            });
        }
    });

    // --- Нативный share всей фотогалереи новости ---
    // URL-кнопки Telegram/Facebook/X/LinkedIn умеют передавать только ссылку.
    // Web Share API, напротив, принимает File[], поэтому на поддерживаемых
    // устройствах добавляем отдельную кнопку, которая отдаёт все og:image.
    function shareAllPhotosLabel() {
        var lang = (document.documentElement.lang || 'ru').toLowerCase();
        if (lang.indexOf('uz') === 0) { return 'Barcha rasmlarni ulashish'; }
        if (lang.indexOf('en') === 0) { return 'Share all photos'; }
        if (lang.indexOf('kk') === 0) { return 'Барлық фотолармен бөлісу'; }
        if (lang.indexOf('tr') === 0) { return 'Tüm fotoğrafları paylaş'; }
        if (lang.indexOf('de') === 0) { return 'Alle Fotos teilen'; }
        return 'Поделиться всеми фото';
    }

    function collectOpenGraphImages() {
        var seen = Object.create(null);
        var urls = [];
        document.querySelectorAll('meta[property="og:image"]').forEach(function (meta) {
            var raw = (meta.getAttribute('content') || '').trim();
            if (!raw) { return; }
            try {
                var absolute = new URL(raw, window.location.href).href;
                if (!seen[absolute]) {
                    seen[absolute] = true;
                    urls.push(absolute);
                }
            } catch (e) {
                // Некорректный URL просто не участвует в файловом share.
            }
        });
        return urls;
    }

    function extensionForMime(type) {
        return {
            'image/jpeg': 'jpg',
            'image/png': 'png',
            'image/gif': 'gif',
            'image/webp': 'webp',
            'image/avif': 'avif'
        }[type] || 'jpg';
    }

    function shareFileName(url, index, type) {
        var base = '';
        try {
            base = decodeURIComponent(new URL(url).pathname.split('/').pop() || '');
        } catch (e) {
            base = '';
        }
        base = base.replace(/[^a-zA-Z0-9._-]+/g, '-').replace(/^-+|-+$/g, '');
        if (!base || base.indexOf('.') === -1) {
            base = 'photo-' + String(index + 1).padStart(2, '0') + '.' + extensionForMime(type);
        }
        return base;
    }

    function loadShareFiles(urls) {
        return Promise.all(urls.map(function (url, index) {
            var requestUrl;
            try {
                requestUrl = new URL(url, window.location.href);
            } catch (e) {
                return null;
            }

            return fetch(requestUrl.href, {
                credentials: requestUrl.origin === window.location.origin ? 'same-origin' : 'omit',
                mode: 'cors'
            }).then(function (response) {
                if (!response.ok) { return null; }
                return response.blob();
            }).then(function (blob) {
                if (!blob || !/^image\//i.test(blob.type || '')) { return null; }
                return new File([blob], shareFileName(requestUrl.href, index, blob.type), {
                    type: blob.type,
                    lastModified: Date.now()
                });
            }).catch(function () {
                // Внешние картинки без CORS могут не отдаться как File.
                return null;
            });
        })).then(function (files) {
            return files.filter(Boolean);
        });
    }

    function nativeShareButton(label) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'newsdetail-share__btn newsdetail-share__btn--native';
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('data-share-gallery-native', '');
        button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"/><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/></svg>';
        return button;
    }

    function initNativeGalleryShare() {
        if (typeof navigator.share !== 'function' || typeof window.File !== 'function') { return; }

        var imageUrls = collectOpenGraphImages();
        if (imageUrls.length === 0) { return; }

        var label = shareAllPhotosLabel();
        var preparedFiles = null;
        var preparePromise = null;

        function prepareFiles() {
            if (preparedFiles !== null) { return Promise.resolve(preparedFiles); }
            if (!preparePromise) {
                preparePromise = loadShareFiles(imageUrls).then(function (files) {
                    preparedFiles = files;
                    return files;
                });
            }
            return preparePromise;
        }

        function shareNow(files) {
            var titleMeta = document.querySelector('meta[property="og:title"]');
            var title = titleMeta ? (titleMeta.getAttribute('content') || document.title) : document.title;
            var pageUrl = window.location.href;

            if (files.length > 0 && typeof navigator.canShare === 'function') {
                var fileData = {
                    title: title,
                    text: title + '\n' + pageUrl,
                    files: files
                };
                if (navigator.canShare({ files: files }) && navigator.canShare(fileData)) {
                    return navigator.share(fileData);
                }
            }

            return navigator.share({ title: title, url: pageUrl });
        }

        document.querySelectorAll('.newsdetail-share__row').forEach(function (row) {
            if (row.querySelector('[data-share-gallery-native]')) { return; }

            var button = nativeShareButton(label);
            row.insertBefore(button, row.firstChild);

            // На desktop начинаем подготовку до клика. На touch — с первого
            // касания. Это уменьшает шанс потерять transient user activation
            // во время загрузки нескольких полноразмерных файлов.
            button.addEventListener('pointerenter', prepareFiles, { once: true });
            button.addEventListener('focus', prepareFiles, { once: true });
            button.addEventListener('touchstart', prepareFiles, { once: true, passive: true });

            button.addEventListener('click', function () {
                button.setAttribute('aria-busy', 'true');

                if (preparedFiles !== null) {
                    // Файлы уже готовы: navigator.share вызывается прямо внутри
                    // пользовательского click и гарантированно видит activation.
                    shareNow(preparedFiles).catch(function (error) {
                        if (!error || error.name !== 'AbortError') {
                            console.warn('Gallery share failed:', error);
                        }
                    }).finally(function () {
                        button.removeAttribute('aria-busy');
                    });
                    return;
                }

                prepareFiles().then(function (files) {
                    // Для быстрых/закэшированных файлов пробуем поделиться сразу.
                    // Если браузер уже погасил activation, файлы останутся
                    // подготовленными и повторный клик сработает мгновенно.
                    return shareNow(files);
                }).catch(function (error) {
                    if (!error || (error.name !== 'AbortError' && error.name !== 'NotAllowedError')) {
                        console.warn('Gallery share failed:', error);
                    }
                }).finally(function () {
                    button.removeAttribute('aria-busy');
                });
            });
        });
    }

    initNativeGalleryShare();

    // --- Слайдер галереи новости ---
    document.querySelectorAll('[data-news-slider]').forEach(function (slider) {
        var slides = Array.prototype.slice.call(slider.querySelectorAll('.news-slider__slide'));
        if (slides.length < 2) { return; }
        var index = 0;

        function show(next) {
            slides[index].classList.remove('is-active');
            index = (next + slides.length) % slides.length;
            slides[index].classList.add('is-active');
            // Лениво «разбудим» соседние картинки.
            var img = slides[index].querySelector('img[loading="lazy"]');
            if (img && img.dataset.pending !== '0') { img.dataset.pending = '0'; }
        }

        var prev = slider.querySelector('.news-slider__nav--prev');
        var next = slider.querySelector('.news-slider__nav--next');
        if (prev) { prev.addEventListener('click', function () { show(index - 1); }); }
        if (next) { next.addEventListener('click', function () { show(index + 1); }); }
    });

    // --- Ленивый YouTube: превью -> iframe только по клику ---
    // Штатный end screen YouTube нельзя отключить параметром rel=0. После
    // завершения IFrame API сразу закрывает его исходной обложкой новости.
    var youtubeApiPromise = null;
    function loadYoutubeApi() {
        if (window.YT && typeof window.YT.Player === 'function') {
            return Promise.resolve(window.YT);
        }
        if (youtubeApiPromise) { return youtubeApiPromise; }

        youtubeApiPromise = new Promise(function (resolve, reject) {
            var previousReady = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function () {
                if (typeof previousReady === 'function') { previousReady(); }
                resolve(window.YT);
            };

            var script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            script.async = true;
            script.onerror = function () { reject(new Error('YouTube API unavailable')); };
            document.head.appendChild(script);
        });

        return youtubeApiPromise;
    }

    document.querySelectorAll('[data-youtube]').forEach(function (box) {
        var btn = box.querySelector('.news-video__play');
        var target = btn || box;
        target.addEventListener('click', function () {
            if (box.classList.contains('is-playing')) { return; }
            var embed = box.getAttribute('data-embed');
            if (!embed) { return; }
            var originalThumb = box.querySelector('.news-video__thumb');
            var endThumb = originalThumb ? originalThumb.cloneNode(true) : null;
            var replayLabel = box.getAttribute('data-replay-label') || 'Посмотреть ещё раз';

            var iframe = document.createElement('iframe');
            var separator = embed.indexOf('?') === -1 ? '?' : '&';
            iframe.setAttribute('src', embed + separator + 'origin=' + encodeURIComponent(window.location.origin));
            iframe.setAttribute('title', 'YouTube video');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'autoplay; encrypted-media');
            iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            iframe.className = 'news-video__iframe';

            var endCard = document.createElement('div');
            endCard.className = 'news-video__end';
            endCard.hidden = true;
            if (endThumb) {
                endThumb.className = 'news-video__thumb news-video__end-thumb';
                endCard.appendChild(endThumb);
            }
            var replay = document.createElement('button');
            replay.type = 'button';
            replay.className = 'news-video__replay';
            replay.textContent = replayLabel;
            endCard.appendChild(replay);

            box.innerHTML = '';
            box.classList.remove('skeleton');
            box.classList.add('is-playing');
            box.appendChild(iframe);
            box.appendChild(endCard);

            loadYoutubeApi().then(function (YT) {
                var player = new YT.Player(iframe, {
                    events: {
                        onStateChange: function (event) {
                            if (event.data === YT.PlayerState.ENDED) {
                                endCard.hidden = false;
                                box.classList.add('is-ended');
                            } else if (event.data === YT.PlayerState.PLAYING) {
                                endCard.hidden = true;
                                box.classList.remove('is-ended');
                            }
                        }
                    }
                });

                replay.addEventListener('click', function () {
                    endCard.hidden = true;
                    box.classList.remove('is-ended');
                    player.seekTo(0, true);
                    player.playVideo();
                });
            }).catch(function () {
                // Сам iframe остаётся рабочим; недоступен только свой end screen.
                endCard.remove();
            });
        });
    });
})();
