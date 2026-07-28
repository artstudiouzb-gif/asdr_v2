(function () {
    'use strict';

    var labels = {};
    var labelsNode = document.getElementById('frontend-labels');
    if (labelsNode) {
        try {
            labels = JSON.parse(labelsNode.textContent || '{}');
        } catch (error) {
            labels = {};
        }
    }
    var label = function (key, fallback) {
        return typeof labels[key] === 'string' && labels[key] !== '' ? labels[key] : fallback;
    };

    // Same-origin page hints belong to the shared frontend bundle, not to
    // executable snippets injected into every response.
    (function () {
        var prefetched = new Set();
        document.addEventListener('mouseover', function (event) {
            var anchor = event.target.closest('a');
            if (!anchor || !anchor.href || anchor.origin !== location.origin
                || anchor.href.includes('#') || anchor.href.includes('/admin')
                || prefetched.has(anchor.href)) {
                return;
            }
            prefetched.add(anchor.href);
            var link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = anchor.href;
            document.head.appendChild(link);
        }, { passive: true });
    })();

    // MP4 в Hero — декоративный фон, а не видеоплеер. Атрибутов разметки
    // достаточно в большинстве браузеров, но после возврата на вкладку или
    // системной паузы autoplay может не возобновиться сам. Восстанавливаем
    // фон без контролов, звука и видимой кнопки воспроизведения.
    (function () {
        var videos = document.querySelectorAll('[data-hero-background-video]');
        if (!videos.length) { return; }

        videos.forEach(function (video) {
            var resume = function () {
                video.controls = false;
                video.muted = true;
                video.defaultMuted = true;
                video.loop = true;
                video.playsInline = true;
                video.removeAttribute('controls');

                var promise = video.play();
                if (promise && typeof promise.catch === 'function') {
                    // Браузер сам повторит попытку после canplay/visibilitychange.
                    promise.catch(function () {});
                }
            };

            video.addEventListener('canplay', resume);
            video.addEventListener('ended', function () {
                // Резерв для браузеров, игнорирующих loop после выгрузки вкладки.
                video.currentTime = 0;
                resume();
            });
            // Опережающий перезапуск за 0.2 секунды до реального окончания видео,
            // чтобы избежать черного экрана/вспышки и появления кнопок управления.
            video.addEventListener('timeupdate', function () {
                if (video.duration && video.currentTime >= video.duration - 0.2) {
                    video.currentTime = 0;
                    video.play().catch(function () {});
                }
            });
            video.addEventListener('pause', function () {
                if (!document.hidden && !video.ended) { resume(); }
            });
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) { resume(); }
            });

            resume();
        });
    })();

    // YouTube-фон использует те же правила. enablejsapi=1 в iframe позволяет
    // вернуть воспроизведение после фоновой приостановки вкладки, не открывая
    // пользователю элементы плеера.
    (function () {
        var frames = document.querySelectorAll('[data-hero-youtube-background]');
        if (!frames.length) { return; }

        frames.forEach(function (frame) {
            var command = function (name, args) {
                if (!frame.contentWindow) { return; }
                frame.contentWindow.postMessage(JSON.stringify({
                    event: 'command',
                    func: name,
                    args: args || []
                }), '*');
            };
            var resume = function () {
                command('mute');
                command('setLoop', [true]);
                command('playVideo');
                // Отправляем handshake-сообщение "listening", чтобы YouTube начал присылать события о состоянии
                if (frame.contentWindow) {
                    frame.contentWindow.postMessage(JSON.stringify({ event: 'listening' }), '*');
                }
            };

            frame.addEventListener('load', resume);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) { resume(); }
            });

            // Слушаем сообщения об изменении состояния плеера YouTube,
            // чтобы перехватить окончание или время воспроизведения и запустить ролик заново.
            window.addEventListener('message', function (e) {
                if (!/https?:\/\/(www\.)?youtube(-nocookie)?\.com/.test(e.origin)) { return; }
                try {
                    var data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
                    if (!data) { return; }

                    var ended = false;
                    if (data.event === 'infoDelivery' && data.info) {
                        // Опережающий перезапуск за 0.3 секунды до конца видео, чтобы избежать черного экрана
                        if (typeof data.info.currentTime === 'number' && typeof data.info.duration === 'number') {
                            if (data.info.duration > 0 && data.info.currentTime >= data.info.duration - 0.3) {
                                ended = true;
                            }
                        }
                        if (data.info.playerState === 0) {
                            ended = true;
                        }
                    } else if (data.event === 'onStateChange' && (data.info === 0 || data.data === 0)) {
                        ended = true;
                    }

                    if (ended) {
                        resume();
                        command('seekTo', [0, true]);
                    }
                } catch (err) {
                    // Игнорируем невалидные сообщения от сторонних скриптов
                }
            });
        });
    })();

    // Переключатели главного меню: бургер (мобильные / макет «боковая панель»),
    // а также фон и кнопка закрытия off-canvas панели.
    var updateMobileMenuState = function (open) {
        if (open) {
            document.body.classList.add('mobile-menu-open');
        } else {
            document.body.classList.remove('mobile-menu-open');
        }
        var state = open ? 'true' : 'false';
        document.querySelectorAll('[data-mobile-menu-toggle], .site-burger').forEach(function (el) {
            el.setAttribute('aria-expanded', state);
        });
    };

    var handleToggleClick = function (e) {
        var toggle = e.target.closest('[data-mobile-menu-toggle], .site-burger');
        if (!toggle) { return; }
        e.preventDefault();
        var open = !document.body.classList.contains('mobile-menu-open');
        updateMobileMenuState(open);
    };

    document.addEventListener('click', handleToggleClick);

    var handleEscapeMenu = function (e) {
        var isEscape = e.key === 'Escape' || e.key === 'Esc' || e.code === 'Escape' || e.keyCode === 27;
        if (isEscape && document.body.classList.contains('mobile-menu-open')) {
            updateMobileMenuState(false);
        }
    };
    document.addEventListener('keydown', handleEscapeMenu);

    document.querySelectorAll('.site-drawer__panel .site-menu__link').forEach(function (link) {
        link.addEventListener('click', function () {
            updateMobileMenuState(false);
        });
    });

    // Плавное раскрытие/сворачивание поля поиска при клике на безрамочную иконку
    var searchToggles = document.querySelectorAll('[data-search-toggle]');
    var searchOverlay = document.querySelector('[data-search-overlay]');

    searchToggles.forEach(function (toggle) {
        if (!toggle.closest('.site-search-wrap')) { return; }
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var wrap = toggle.closest('.site-search-wrap') || toggle.parentElement;
            if (!wrap) { return; }
            var form = wrap.querySelector('.site-search');
            var isExpanded = wrap.classList.toggle('is-expanded');
            toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            if (form) {
                form.classList.toggle('is-open', isExpanded);
                if (isExpanded) {
                    var input = form.querySelector('input[type="search"]');
                    if (input) {
                        setTimeout(function () { input.focus(); input.select(); }, 50);
                    }
                }
            }
        });
    });

    document.querySelectorAll('[data-search-close]').forEach(function (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var wrap = closeBtn.closest('.site-search-wrap');
            if (wrap) {
                wrap.classList.remove('is-expanded');
                var toggle = wrap.querySelector('[data-search-toggle]');
                if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
                var form = wrap.querySelector('.site-search');
                if (form) { form.classList.remove('is-open'); }
            }
        });
    });

    // Клик вне поля поиска — закрыть выезжающий поиск
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.site-search-wrap')) {
            document.querySelectorAll('.site-search-wrap.is-expanded').forEach(function (wrap) {
                wrap.classList.remove('is-expanded');
                var toggle = wrap.querySelector('[data-search-toggle]');
                if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
                var form = wrap.querySelector('.site-search');
                if (form) { form.classList.remove('is-open'); }
            });
        }
    });

    if (searchToggles.length && searchOverlay) {
        var searchInput = searchOverlay.querySelector('[data-search-input]');
        var searchForm = searchOverlay.querySelector('.site-search-overlay__form');
        var activeSearchToggle = null;
        var searchCloseTimer = null;
        var positionSearch = function (toggle) {
            if (!toggle || !searchForm) { return; }
            var toggleRect = toggle.getBoundingClientRect();
            var header = toggle.closest('.site-header');
            var anchorRect = header ? header.getBoundingClientRect() : toggleRect;
            var desiredTop = anchorRect.bottom + 10;
            var maxTop = Math.max(12, window.innerHeight - searchForm.offsetHeight - 12);
            var top = Math.max(12, Math.min(desiredTop, maxTop));
            var desiredRight = Math.max(12, window.innerWidth - toggleRect.right);
            var maxRight = Math.max(12, window.innerWidth - searchForm.offsetWidth - 12);
            var right = Math.min(desiredRight, maxRight);
            searchOverlay.style.setProperty('--search-popover-top', top + 'px');
            searchOverlay.style.setProperty('--search-popover-right', right + 'px');
        };
        var openSearch = function (toggle) {
            if (searchOverlay.classList.contains('is-open') && activeSearchToggle === toggle) {
                closeSearch(true);
                return;
            }
            if (searchCloseTimer) { clearTimeout(searchCloseTimer); searchCloseTimer = null; }
            activeSearchToggle = toggle;
            searchOverlay.hidden = false;
            document.body.classList.add('site-search-open');
            positionSearch(toggle);
            searchToggles.forEach(function (t) { t.setAttribute('aria-expanded', 'true'); });
            requestAnimationFrame(function () {
                searchOverlay.classList.add('is-open');
                if (searchInput) { searchInput.focus(); }
            });
        };
        var closeSearch = function (restoreFocus) {
            var focusTarget = activeSearchToggle;
            searchOverlay.classList.remove('is-open');
            document.body.classList.remove('site-search-open');
            searchToggles.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
            searchCloseTimer = setTimeout(function () {
                searchOverlay.hidden = true;
                searchCloseTimer = null;
                if (restoreFocus && focusTarget) { focusTarget.focus(); }
                activeSearchToggle = null;
            }, 180);
        };
        searchToggles.forEach(function (t) {
            if (t.closest('.site-search-wrap')) { return; }
            t.addEventListener('click', function () { openSearch(t); });
        });
        searchOverlay.addEventListener('click', function (e) {
            if (e.target === searchOverlay || e.target.closest('[data-search-close]')) {
                closeSearch(true);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (searchOverlay.hidden) { return; }
            if (e.key === 'Escape') {
                closeSearch(true);
                return;
            }
            if (e.key === 'Tab' && searchForm) {
                var focusable = Array.prototype.slice.call(searchForm.querySelectorAll('input, button, [href], [tabindex]:not([tabindex="-1"])'))
                    .filter(function (element) { return !element.disabled && element.offsetParent !== null; });
                if (!focusable.length) { return; }
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
        window.addEventListener('resize', function () {
            if (!searchOverlay.hidden && activeSearchToggle) { positionSearch(activeSearchToggle); }
        });
        window.addEventListener('a11y:panelchange', function () {
            if (!searchOverlay.hidden && activeSearchToggle) { positionSearch(activeSearchToggle); }
        });
    }

    // Выпадающее подменю: клик по стрелке раскрывает (мобильные/клавиатура).
    // На desktop работает и hover/focus-within (CSS), клик — дополнительно.
    document.querySelectorAll('.site-menu__item--has-children .site-menu__toggle').forEach(function (toggle) {
        var item = toggle.closest('.site-menu__item');
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var open = item.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    // Клик вне меню — закрыть все раскрытые подменю.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.site-menu__item.is-open').forEach(function (item) {
            if (!item.contains(e.target)) {
                item.classList.remove('is-open');
                var t = item.querySelector('.site-menu__toggle');
                if (t) { t.setAttribute('aria-expanded', 'false'); }
            }
        });
        document.querySelectorAll('details.site-lang-dropdown[open]').forEach(function (dropdown) {
            if (!dropdown.contains(e.target)) {
                dropdown.removeAttribute('open');
            }
        });
    });

    // Переключатель светлой/тёмной темы с сохранением выбора в localStorage.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.site-theme-toggle');
        if (!btn) { return; }
        e.preventDefault();
        var root = document.documentElement;
        var current = root.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        if (document.body) {
            document.body.setAttribute('data-theme', next);
        }
        try { localStorage.setItem('theme', next); } catch (err) {}
    });

    // Счётчики (группа 4): анимация инкремента числа при попадании в зону
    // видимости. Переиспользуем IntersectionObserver. Уважает reduced-motion.
    (function () {
        var counters = document.querySelectorAll('.counter__value[data-counter-target]');
        if (!counters.length) { return; }
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || !('IntersectionObserver' in window)) {
            counters.forEach(function (el) { el.textContent = el.getAttribute('data-counter-target'); });
            return;
        }
        function animate(el) {
            var target = parseInt(el.getAttribute('data-counter-target'), 10) || 0;
            var start = null, dur = 1400;
            function step(ts) {
                if (start === null) { start = ts; }
                var p = Math.min((ts - start) / dur, 1);
                el.textContent = Math.round(p * target).toString();
                if (p < 1) { requestAnimationFrame(step); }
            }
            requestAnimationFrame(step);
        }
        var cio = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { animate(e.target); obs.unobserve(e.target); }
            });
        }, { threshold: 0.4 });
        counters.forEach(function (el) {
            el.textContent = '0'; // start from 0 to avoid jumping
            cio.observe(el);
        });
    })();

    // Автоматический скролл-ревил для всех секций, кроме Hero
    document.querySelectorAll('.cms-block:not(.cms-block--hero)').forEach(function (block) {
        if (!block.hasAttribute('data-reveal')) {
            block.setAttribute('data-reveal', '');
            block.setAttribute('data-reveal-type', 'slide-up');
        }
    });

    // Микро-движок анимаций появления при скролле на Intersection Observer.
    (function () {
        var reveals = document.querySelectorAll('[data-reveal]');
        if (reveals.length) {
            if (!('IntersectionObserver' in window)) {
                reveals.forEach(function (el) { el.classList.add('is-visible'); });
            } else {
                var io = new IntersectionObserver(function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
                reveals.forEach(function (el) { io.observe(el); });
            }
        }
    })();
})();

    // Медиа-галерея: переключатели «Видео / Фото».
    document.querySelectorAll('[data-media-gallery]').forEach(function (gallery) {
        var tabs = gallery.querySelectorAll('[data-media-tab]');
        if (!tabs.length) { return; }
        var cards = gallery.querySelectorAll('[data-media-kind]');
        var apply = function (kind) {
            cards.forEach(function (c) { c.hidden = c.getAttribute('data-media-kind') !== kind; });
            tabs.forEach(function (t) {
                var on = t.getAttribute('data-media-tab') === kind;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        };
        tabs.forEach(function (t) { t.addEventListener('click', function () { apply(t.getAttribute('data-media-tab')); }); });
        apply('video');
    });

    // Карусель проектов: прокрутка трека кнопками ‹ ›.
    document.querySelectorAll('[data-carousel]').forEach(function (root) {
        var track = root.querySelector('[data-carousel-track]');
        var prev = root.querySelector('[data-carousel-prev]');
        var next = root.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) { return; }
        var step = function () {
            var card = track.querySelector('.imgcard');
            var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '20') || 20;
            return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
        };
        var sync = function () {
            var max = track.scrollWidth - track.clientWidth - 1;
            prev.disabled = track.scrollLeft <= 0;
            next.disabled = track.scrollLeft >= max;
        };
        prev.addEventListener('click', function () { track.scrollBy({ left: -step() * 2, behavior: 'smooth' }); });
        next.addEventListener('click', function () { track.scrollBy({ left: step() * 2, behavior: 'smooth' }); });
        track.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    });

    // Детальная новость: слайдер медиа-модуля (главное фото + миниатюры + счётчик).
    document.querySelectorAll('[data-ndgallery]').forEach(function (root) {
        var slides = root.querySelectorAll('.newsdetail-gallery__slide');
        if (slides.length < 2) { return; }
        var thumbs = root.querySelectorAll('[data-ndg-thumb]');
        var counter = root.querySelector('[data-ndg-current]');
        var idx = 0;
        var show = function (i) {
            idx = (i + slides.length) % slides.length;
            slides.forEach(function (s, n) { s.classList.toggle('is-active', n === idx); });
            thumbs.forEach(function (t, n) { t.classList.toggle('is-active', n === idx); });
            if (counter) { counter.textContent = String(idx + 1); }
        };
        var prev = root.querySelector('[data-ndg-prev]');
        var next = root.querySelector('[data-ndg-next]');
        if (prev) { prev.addEventListener('click', function () { show(idx - 1); }); }
        if (next) { next.addEventListener('click', function () { show(idx + 1); }); }
        thumbs.forEach(function (t, n) { t.addEventListener('click', function () { show(n); }); });
        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { show(idx - 1); }
            if (e.key === 'ArrowRight') { show(idx + 1); }
        });
    });

    // «Скопировать ссылку» в блоке «Поделиться».
    document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-copy-link');
            var done = function () {
                btn.classList.add('is-copied');
                var prevLabel = btn.getAttribute('aria-label');
                btn.setAttribute('aria-label', label('linkCopied', 'Ссылка скопирована'));
                setTimeout(function () { btn.classList.remove('is-copied'); btn.setAttribute('aria-label', prevLabel); }, 1600);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = url; document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(ta);
            }
        });
    });

    // Кнопка «Печать».
    document.querySelectorAll('[data-print-page]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });

    // Липкая/прозрачная шапка: класс is-scrolled после небольшой прокрутки.
    (function () {
        var hdr = document.querySelector('[data-header-scroll]');
        if (!hdr) { return; }
        // Прозрачная шапка стартует сразу под верхней полосой (если есть).
        var topbar = document.querySelector('.site-topbar');
        var a11yPanel = document.querySelector('.a11y-panel');
        var offset = function () {
            var panelHeight = a11yPanel && a11yPanel.classList.contains('is-open') ? a11yPanel.offsetHeight : 0;
            hdr.style.setProperty('--hdr-panel-height', panelHeight + 'px');
            if (hdr.classList.contains('site-header--transparent')) {
                var topbarHeight = topbar ? topbar.offsetHeight : 0;
                hdr.style.setProperty('--hdr-top', (topbarHeight + panelHeight) + 'px');
                // Верхняя полоса тоже наложена (absolute) — держим её под a11y-панелью.
                if (topbar) { topbar.style.setProperty('--hdr-panel-height', panelHeight + 'px'); }
            }
        };
        var apply = function () {
            hdr.classList.toggle('is-scrolled', window.scrollY > 12);
        };
        window.addEventListener('scroll', apply, { passive: true });
        window.addEventListener('resize', offset);
        window.addEventListener('a11y:panelchange', offset);
        offset();
        apply();
    })();

    // Плавающая кнопка «Наверх»: активна только при включённом тумблере
    // (body.design-scrolltop), появляется после прокрутки, скроллит вверх.
    (function () {
        if (!document.body.classList.contains('design-scrolltop')) { return; }
        var btn = document.querySelector('[data-scroll-top]');
        if (!btn) { return; }
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var shown = false;
        var toggle = function () {
            var need = window.scrollY > 600;
            if (need === shown) { return; }
            shown = need;
            btn.classList.toggle('is-visible', need);
        };
        window.addEventListener('scroll', toggle, { passive: true });
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        });
        toggle();
    })();

    // Делегированные обработчики вместо инлайн-атрибутов (CSP без 'unsafe-inline'):
    // [data-auto-submit] — селект отправляет свою форму; [data-captcha-refresh] —
    // кнопка обновляет картинку капчи рядом с собой.
    document.addEventListener('change', function (e) {
        var el = e.target;
        // Форму списка обслуживает AJAX-модуль ниже — иначе селект сортировки
        // сработал бы дважды и перезагрузил страницу поверх подгрузки.
        if (el && el.matches && el.matches('select[data-auto-submit]') && el.form
            && !el.form.hasAttribute('data-listing-form')) {
            el.form.submit();
        }
    });
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-captcha-refresh]') : null;
        if (!btn) { return; }
        var img = btn.parentNode.querySelector('img');
        if (img) { img.src = '/captcha.png?ts=' + Date.now(); }
    });

    // Сворачивание/разворачивание формы поиска при клике на иконку
    (function () {
        var searchForms = document.querySelectorAll('.site-search');
        searchForms.forEach(function (form) {
            var input = form.querySelector('input[type="search"]');
            var button = form.querySelector('button[type="submit"]');
            if (!input || !button) { return; }
            
            button.addEventListener('click', function (e) {
                if (!form.classList.contains('is-active')) {
                    e.preventDefault();
                    form.classList.add('is-active');
                    input.focus();
                } else {
                    if (input.value.trim() === '') {
                        e.preventDefault();
                        form.classList.remove('is-active');
                    }
                }
            });
            
            document.addEventListener('click', function (e) {
                if (!form.contains(e.target)) {
                    if (input.value.trim() === '') {
                        form.classList.remove('is-active');
                    }
                }
            });
        });
    })();

    // Лайтбокс: фото (альбомы, блок-галерея, фотолента новости, медиа-карточки)
    // и видео YouTube (карточки на главной/страницах, «Смотреть видео» в новостях).
    (function () {
        var IMG_RE = /\.(jpe?g|png|gif|webp|avif)(\?.*)?$/i;
        var PHOTO_SCOPES = '.album-photos, .block-gallery__grid, .newsdetail-photos__grid, .mediagallery-grid';

        function ytId(url) {
            var patterns = [
                /youtu\.be\/([\w-]{11})/,
                /youtube\.com\/watch\?[^\s]*v=([\w-]{11})/,
                /youtube\.com\/embed\/([\w-]{11})/,
                /youtube\.com\/shorts\/([\w-]{11})/
            ];
            for (var i = 0; i < patterns.length; i++) {
                var m = String(url || '').match(patterns[i]);
                if (m) { return m[1]; }
            }
            return null;
        }

        var box = null, stage = null, captionEl = null, prevBtn = null, nextBtn = null;
        var items = [], index = 0, lastFocus = null;

        function ensure() {
            if (box) { return; }
            box = document.createElement('div');
            box.className = 'cms-lightbox';
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');
            box.setAttribute('aria-label', label('mediaViewer', 'Просмотр медиа'));
            box.innerHTML =
                '<button type="button" class="cms-lightbox__close" aria-label="' + label('close', 'Закрыть') + '">&times;</button>' +
                '<button type="button" class="cms-lightbox__nav cms-lightbox__nav--prev" aria-label="' + label('previous', 'Предыдущее') + '">&#10094;</button>' +
                '<div class="cms-lightbox__stage"></div>' +
                '<button type="button" class="cms-lightbox__nav cms-lightbox__nav--next" aria-label="' + label('next', 'Следующее') + '">&#10095;</button>' +
                '<div class="cms-lightbox__caption"></div>';
            document.body.appendChild(box);
            stage = box.querySelector('.cms-lightbox__stage');
            captionEl = box.querySelector('.cms-lightbox__caption');
            prevBtn = box.querySelector('.cms-lightbox__nav--prev');
            nextBtn = box.querySelector('.cms-lightbox__nav--next');

            box.querySelector('.cms-lightbox__close').addEventListener('click', close);
            box.addEventListener('click', function (e) {
                if (e.target === box || e.target === stage) { close(); }
            });
            prevBtn.addEventListener('click', function () { go(-1); });
            nextBtn.addEventListener('click', function () { go(1); });
            document.addEventListener('keydown', function (e) {
                if (!box.classList.contains('is-open')) { return; }
                if (e.key === 'Escape') { close(); return; }
                if (e.key === 'ArrowLeft') { go(-1); return; }
                if (e.key === 'ArrowRight') { go(1); return; }
                // Focus-trap: Tab не выпускает фокус за пределы модалки (WCAG 2.4.3).
                if (e.key === 'Tab') {
                    var focusable = Array.prototype.filter.call(
                        box.querySelectorAll('button:not([hidden]), a[href], iframe'),
                        function (el) { return el.offsetParent !== null; }
                    );
                    if (!focusable.length) { return; }
                    var first = focusable[0];
                    var last = focusable[focusable.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            });
        }

        function render() {
            var item = items[index];
            if (!item) { return; }
            if (item.type === 'video') {
                stage.innerHTML = '<iframe class="cms-lightbox__video" src="https://www.youtube-nocookie.com/embed/'
                    + item.id + '?rel=0&modestbranding=1&autoplay=1" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="'
                    + label('video', 'Видео') + '"></iframe>';
            } else {
                var img = document.createElement('img');
                img.src = item.src;
                img.alt = item.caption || '';
                stage.innerHTML = '';
                stage.appendChild(img);
            }
            captionEl.textContent = item.caption || '';
            captionEl.hidden = !item.caption;
            var many = items.length > 1;
            prevBtn.hidden = !many;
            nextBtn.hidden = !many;
        }

        function open(list, i, trigger) {
            ensure();
            items = list;
            index = i;
            lastFocus = trigger || document.activeElement;
            render();
            box.classList.add('is-open');
            document.body.classList.add('lightbox-active');
            box.querySelector('.cms-lightbox__close').focus();
        }

        function close() {
            if (!box) { return; }
            box.classList.remove('is-open');
            stage.innerHTML = ''; // останавливает видео
            document.body.classList.remove('lightbox-active');
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        }

        function go(step) {
            if (items.length < 2) { return; }
            index = (index + step + items.length) % items.length;
            render();
        }

        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented) { return; }
            var href = a.getAttribute('href') || '';

            // Видео YouTube — в лайтбокс на любой публичной странице.
            var id = ytId(href);
            if (id) {
                e.preventDefault();
                open([{ type: 'video', id: id }], 0, a);
                return;
            }

            // Фото: только в известных контейнерах, группой с листанием.
            var scope = a.closest(PHOTO_SCOPES);
            if (!scope || !IMG_RE.test(href)) { return; }
            var links = Array.prototype.filter.call(scope.querySelectorAll('a[href]'), function (el) {
                return IMG_RE.test(el.getAttribute('href') || '');
            });
            var list = links.map(function (el) {
                var fig = el.closest('figure');
                var cap = fig ? fig.querySelector('figcaption') : null;
                return {
                    type: 'image',
                    src: el.getAttribute('href'),
                    caption: (cap && cap.textContent) || el.getAttribute('aria-label') || (el.querySelector('img') && el.querySelector('img').alt) || ''
                };
            });
            e.preventDefault();
            open(list, Math.max(0, links.indexOf(a)), a);
        });
    })();

    // Мягкий каскад появления карточек в сетках при прокрутке.
    // Начальное скрытие навешивает сам JS (.anim-card), поэтому при отсутствии
    // JS, старом браузере или reduced-motion карточки остаются видимыми.
    (function () {
        'use strict';
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
        if (!('IntersectionObserver' in window)) { return; }
        var GRIDS = '.imgcards-grid, .newsfeat-grid, .mediagallery-grid, .albums-grid, .persons-grid, .cards-grid, .cat-grid';
        var grids = document.querySelectorAll(GRIDS);
        if (!grids.length) { return; }
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }
                (entry.target.__animCards || []).forEach(function (card, i) {
                    card.style.setProperty('--card-reveal-delay', Math.min(i * 60, 360) + 'ms');
                    card.classList.add('is-inview');
                });
                obs.unobserve(entry.target);
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });
        grids.forEach(function (grid) {
            // Не дублируем анимацию, если блок уже проявляется через data-reveal.
            if (grid.closest('[data-reveal]')) { return; }
            var cards = Array.prototype.filter.call(grid.children, function (c) { return c.nodeType === 1; });
            if (cards.length < 2) { return; }
            cards.forEach(function (c) { c.classList.add('anim-card'); });
            grid.__animCards = cards;
            io.observe(grid);
        });

        // Страховка: если IntersectionObserver почему-то не сработал, любая
        // карточка, попавшая в область просмотра (скролл/ресайз/через 2.5с),
        // всё равно проявляется — контент никогда не остаётся скрытым.
        var failsafe = function () {
            var hidden = document.querySelectorAll('.anim-card:not(.is-inview)');
            if (!hidden.length) {
                window.removeEventListener('scroll', onScroll);
                window.removeEventListener('resize', onScroll);
                return;
            }
            hidden.forEach(function (c) {
                var r = c.getBoundingClientRect();
                if (r.top < window.innerHeight - 20 && r.bottom > 0) { c.classList.add('is-inview'); }
            });
        };
        var onScroll = function () { window.requestAnimationFrame(failsafe); };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        setTimeout(failsafe, 2500);
    })();

    // AJAX-фильтрация списков (новости, каталоги). Прогрессивное улучшение:
    // ссылки фильтров/пагинации и форма поиска остаются рабочими без JS, а
    // здесь мы лишь перехватываем их и подменяем область результатов.
    // Сервер отдаёт тот же список фрагментом по параметру _fragment=1.
    (function () {
        var listings = document.querySelectorAll('[data-listing]');
        if (!listings.length || !window.fetch || !window.history || !history.pushState) { return; }

        var FRAGMENT_PARAM = '_fragment';

        var fragmentUrl = function (url) {
            var u = new URL(url, location.href);
            u.searchParams.set(FRAGMENT_PARAM, '1');
            return u.toString();
        };

        // Адрес для истории браузера — без служебного параметра фрагмента.
        var publicUrl = function (url) {
            var u = new URL(url, location.href);
            u.searchParams.delete(FRAGMENT_PARAM);
            return u.pathname + (u.search === '?' ? '' : u.search);
        };

        listings.forEach(function (root) {
            var results = root.querySelector('[data-listing-results]');
            if (!results) { return; }

            var controller = null;
            var timer = null;

            var setBusy = function (busy) {
                results.setAttribute('aria-busy', busy ? 'true' : 'false');
                root.classList.toggle('listing--loading', busy);
            };

            // Активная «таблетка» рубрики подсвечивается сразу: ответ сервера
            // касается только результатов, состояние фильтров — на нас.
            // syncForm=true только когда адрес пришёл не из самой формы (клик по
            // «Сбросить», кнопка «назад»): иначе ответ затёр бы текст, который
            // посетитель успел дописать, пока летел запрос.
            var syncFilters = function (url, syncForm) {
                var target = new URL(url, location.href);
                root.querySelectorAll('.listing-filter__item').forEach(function (link) {
                    var href = new URL(link.getAttribute('href'), location.href);
                    var same = href.pathname === target.pathname
                        && (href.searchParams.get('badge') || '') === (target.searchParams.get('badge') || '');
                    link.classList.toggle('is-active', same);
                });
                var reset = root.querySelector('[data-listing-reset]');
                if (reset) { reset.hidden = !(target.searchParams.get('q') || ''); }

                if (!syncForm) { return; }
                var search = root.querySelector('[data-listing-form] input[type="search"]');
                if (search) { search.value = target.searchParams.get('q') || ''; }
                var sort = root.querySelector('[data-listing-form] select[name="sort"]');
                if (sort) { sort.value = target.searchParams.get('sort') || 'new'; }
            };

            var load = function (url, push, syncForm) {
                if (controller) { controller.abort(); }
                controller = ('AbortController' in window) ? new AbortController() : null;
                setBusy(true);

                fetch(fragmentUrl(url), {
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                }).then(function (r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.text();
                }).then(function (html) {
                    results.innerHTML = html;
                    syncFilters(url, syncForm);
                    if (push) { history.pushState({ listing: true }, '', publicUrl(url)); }
                    setBusy(false);
                }).catch(function (err) {
                    if (err && err.name === 'AbortError') { return; }
                    // Любая ошибка — обычный переход: посетитель не должен
                    // остаться со старым списком и без объяснений.
                    location.href = publicUrl(url);
                });
            };

            // Клики по рубрикам и страницам.
            root.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) { return; }
                var link = e.target.closest ? e.target.closest('.listing-filter__item, .listing-pager__item, [data-listing-reset]') : null;
                if (!link || link.tagName !== 'A' || !link.getAttribute('href')) { return; }
                e.preventDefault();
                load(link.getAttribute('href'), true, true);
                // Пагинация уводит взгляд наверх списка, фильтры — нет.
                if (link.classList.contains('listing-pager__item')) {
                    root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            // Форма поиска/сортировки каталога.
            var form = root.querySelector('[data-listing-form]');
            if (form) {
                var formUrl = function () {
                    var params = new URLSearchParams(new FormData(form));
                    // Пустые значения и сортировку по умолчанию в адрес не тащим.
                    if (!params.get('q')) { params.delete('q'); }
                    if (params.get('sort') === 'new') { params.delete('sort'); }
                    var qs = params.toString();
                    return form.getAttribute('action') + (qs ? '?' + qs : '');
                };
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    load(formUrl(), true, false);
                });
                form.addEventListener('change', function (e) {
                    if (e.target && e.target.name === 'sort') { load(formUrl(), true, false); }
                });
                form.addEventListener('input', function (e) {
                    if (!e.target || e.target.type !== 'search') { return; }
                    if (timer) { clearTimeout(timer); }
                    timer = setTimeout(function () { load(formUrl(), true, false); }, 400);
                });
            }

            window.addEventListener('popstate', function () {
                load(location.href, false, true);
            });
        });
    })();

    // Живой поиск: под полем в шапке показываем несколько найденных страниц,
    // не уходя на страницу результатов. Прогрессивное улучшение — форма
    // остаётся обычной формой и без JS работает как раньше.
    (function () {
        var inputs = document.querySelectorAll('.site-search input[type="search"], .site-search-overlay__form input[type="search"]');
        if (!inputs.length || !window.fetch) { return; }

        inputs.forEach(function (input, inputIndex) {
            var form = input.form;
            if (!form) { return; }

            var panel = document.createElement('div');
            panel.className = 'search-suggest';
            panel.id = 'search-suggest-' + inputIndex;
            panel.setAttribute('aria-live', 'polite');
            panel.hidden = true;
            form.appendChild(panel);
            form.classList.add('has-suggest');
            input.setAttribute('aria-expanded', 'false');
            input.setAttribute('aria-controls', panel.id);

            var timer = null;
            var controller = null;
            var suggestionLinks = function () {
                return Array.prototype.slice.call(panel.querySelectorAll('a[href]'));
            };

            var close = function () {
                panel.hidden = true;
                panel.innerHTML = '';
                input.setAttribute('aria-expanded', 'false');
            };

            var load = function (query) {
                if (controller) { controller.abort(); }
                controller = ('AbortController' in window) ? new AbortController() : null;

                // Адрес подсказок наследует язык от action формы (/uz/search).
                var url = form.getAttribute('action') + '/suggest?q=' + encodeURIComponent(query);
                fetch(url, {
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                }).then(function (r) {
                    return r.ok ? r.text() : '';
                }).then(function (html) {
                    if (!html) { close(); return; }
                    panel.innerHTML = html;
                    panel.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                }).catch(function (err) {
                    // Отмена — норма; всё остальное просто оставляет форму
                    // обычной формой, поиск по Enter продолжает работать.
                    if (!err || err.name !== 'AbortError') { close(); }
                });
            };

            input.addEventListener('input', function () {
                var query = input.value.trim();
                if (timer) { clearTimeout(timer); }
                if (query.length < 2) { close(); return; }
                timer = setTimeout(function () { load(query); }, 250);
            });

            input.addEventListener('focus', function () {
                if (input.value.trim().length >= 2 && panel.innerHTML !== '') {
                    panel.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown' && !panel.hidden) {
                    var firstLink = suggestionLinks()[0];
                    if (firstLink) {
                        e.preventDefault();
                        firstLink.focus();
                    }
                } else if (e.key === 'Escape' && !panel.hidden) {
                    e.preventDefault();
                    close();
                }
            });

            panel.addEventListener('keydown', function (e) {
                var links = suggestionLinks();
                var current = links.indexOf(document.activeElement);
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close();
                    input.focus();
                } else if (e.key === 'ArrowDown' && links.length) {
                    e.preventDefault();
                    links[(current + 1 + links.length) % links.length].focus();
                } else if (e.key === 'ArrowUp' && links.length) {
                    e.preventDefault();
                    if (current <= 0) {
                        input.focus();
                    } else {
                        links[current - 1].focus();
                    }
                }
            });

            document.addEventListener('click', function (e) {
                if (!form.contains(e.target)) { close(); }
            });
        });
    })();

    // Интерактивный «прожектор» (Spotlight) для карточек, кнопок и полей ввода
    document.addEventListener('mousemove', function (e) {
        var el = e.target.closest(
            '.cat-tile, .contact-card, .project-card, .team-card, .feature-card, .news-card, .person-card, .album-card, .doc-card, .catcard, .testimonial, .block-advantages__item, .mediacard, .imgcard, .faq-item, .stage, .timeline-item, ' +
            '.btn, .block-cta__button, .btn-cta, .block-hero__button, .timeline-card__button, .timeline-cta__button, ' +
            '.a11y-toggle, .site-theme-toggle, .site-search-toggle, ' +
            'input[type="text"], input[type="email"], input[type="password"], input[type="search"]:not(.site-search input), textarea, select'
        );
        if (!el) { return; }
        var rect = el.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        el.style.setProperty('--mouse-x', x + 'px');
        el.style.setProperty('--mouse-y', y + 'px');
    });

    // Индикатор прогресса прокрутки страницы
    (function () {
        var bar = document.getElementById('site-scroll-progress-bar');
        if (!bar) { return; }
        var update = function () {
            var winScroll = document.documentElement.scrollTop || document.body.scrollTop;
            var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            var scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            bar.style.setProperty('--scroll-progress', Math.min(Math.max(scrolled, 0), 100) + '%');
        };
        window.addEventListener('scroll', update, { passive: true });
        update();
    })();

    // Движок Toast-уведомлений
    window.showToast = function (message, type, duration) {
        var container = document.getElementById('site-toast-container');
        if (!container) { return; }
        type = type || 'info';
        duration = duration || 3500;
        if (['info', 'success', 'warning', 'error'].indexOf(type) === -1) {
            type = 'info';
        }

        var toast = document.createElement('div');
        toast.className = 'site-toast site-toast--' + type;
        
        var icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
        if (type === 'error') {
            icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>';
        }

        toast.innerHTML = icon;
        var messageEl = document.createElement('span');
        messageEl.className = 'site-toast__msg';
        messageEl.textContent = String(message == null ? '' : message);
        toast.appendChild(messageEl);
        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('is-leaving');
            setTimeout(function () {
                if (toast.parentNode) { toast.parentNode.removeChild(toast); }
            }, 300);
        }, duration);
    };

    // Быстрый поиск по сочетанию клавиш Ctrl + K / Cmd + K
    (function () {
        var modal = document.getElementById('site-quick-search-modal');
        var input = document.getElementById('site-quick-search-input');
        if (!modal || !input) { return; }
        var lastFocus = null;
        var closeTimer = null;

        var open = function () {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
            lastFocus = document.activeElement;
            modal.hidden = false;
            modal.classList.add('is-open');
            document.body.classList.add('quick-search-active');
            setTimeout(function () { input.focus(); }, 50);
        };
        var close = function () {
            if (modal.hidden) { return; }
            modal.classList.remove('is-open');
            document.body.classList.remove('quick-search-active');
            closeTimer = setTimeout(function () {
                modal.hidden = true;
                closeTimer = null;
                if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
            }, 200);
        };

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (modal.hidden) { open(); } else { close(); }
            } else if (e.key === 'Escape' && !modal.hidden) {
                e.preventDefault();
                close();
            } else if (e.key === 'Tab' && !modal.hidden) {
                var focusable = Array.prototype.filter.call(
                    modal.querySelectorAll('button:not([disabled]), input:not([disabled]), a[href]'),
                    function (el) { return el.offsetParent !== null; }
                );
                if (!focusable.length) { return; }
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        modal.querySelectorAll('[data-quick-search-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });
    })();

    // === Режим чтения для новостей (Reader Mode) ===
    (function () {
        var overlay = document.getElementById('reader-mode-overlay');
        if (!overlay) { return; }

        var body = document.body;
        var progress = document.getElementById('reader-progress');
        var articleContent = overlay.querySelector('.reader-mode-container');
        var fontSizeLevel = 1.0;
        var readerLastFocus = null;

        var setReaderIsolation = function (enabled) {
            if (!enabled) {
                document.querySelectorAll('[data-reader-inert]').forEach(function (el) {
                    el.removeAttribute('inert');
                    el.removeAttribute('data-reader-inert');
                });
                return;
            }

            var node = overlay;
            while (node && node !== document.body) {
                var parent = node.parentElement;
                if (!parent) { break; }
                Array.prototype.forEach.call(parent.children, function (sibling) {
                    if (sibling !== node && !sibling.hasAttribute('inert')) {
                        sibling.setAttribute('inert', '');
                        sibling.setAttribute('data-reader-inert', '');
                    }
                });
                node = parent;
            }
        };

        var updateProgress = function () {
            if (overlay.hidden) { return; }
            var scrollTop = overlay.scrollTop;
            var scrollHeight = overlay.scrollHeight - overlay.clientHeight;
            var pct = scrollHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / scrollHeight) * 100)) : 0;
            if (progress) {
                progress.style.setProperty('--reader-progress', pct + '%');
                progress.setAttribute('aria-valuenow', String(Math.round(pct)));
            }
        };

        overlay.addEventListener('scroll', updateProgress, { passive: true });

        var openReader = function (trigger) {
            readerLastFocus = trigger || document.activeElement;
            overlay.hidden = false;
            body.classList.add('reader-mode-active');
            setReaderIsolation(true);
            overlay.scrollTop = 0;
            updateProgress();
            var closeButton = overlay.querySelector('[data-reader-close]');
            if (closeButton) { closeButton.focus(); }
        };

        var closeReader = function () {
            if (overlay.hidden) { return; }
            overlay.hidden = true;
            body.classList.remove('reader-mode-active');
            setReaderIsolation(false);
            if (readerLastFocus && readerLastFocus.focus) { readerLastFocus.focus(); }
        };

        document.querySelectorAll('[data-reader-mode-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openReader(btn);
            });
        });

        document.querySelectorAll('[data-reader-close]').forEach(function (btn) {
            btn.addEventListener('click', closeReader);
        });

        overlay.querySelectorAll('button[data-reader-theme]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var theme = btn.getAttribute('data-reader-theme');
                overlay.setAttribute('data-reader-theme', theme);
                overlay.querySelectorAll('button[data-reader-theme]').forEach(function (b) {
                    b.classList.toggle('is-active', b === btn);
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
            });
        });

        overlay.querySelectorAll('button[data-reader-font]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-reader-font');
                if (action === 'inc' && fontSizeLevel < 1.6) {
                    fontSizeLevel += 0.15;
                } else if (action === 'dec' && fontSizeLevel > 0.7) {
                    fontSizeLevel -= 0.15;
                }
                if (articleContent) {
                    articleContent.style.setProperty('--reader-scale', fontSizeLevel.toFixed(2));
                }
            });
        });

        document.addEventListener('keydown', function (e) {
            if (overlay.hidden) { return; }
            if (e.key === 'Escape' || e.code === 'Escape' || e.keyCode === 27) {
                e.preventDefault();
                closeReader();
            } else if (e.key === 'Tab') {
                var focusable = Array.prototype.filter.call(
                    overlay.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'),
                    function (el) { return el.offsetParent !== null; }
                );
                if (!focusable.length) { return; }
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
    })();

    // === Универсальный Lightbox для фото в новостях и статьях ===
    (function () {
        var images = Array.prototype.slice.call(document.querySelectorAll('.rich-content img, .newsdetail-article img, .newsdetail-gallery img'));
        if (!images.length) { return; }

        var modal = document.createElement('div');
        modal.className = 'rich-lightbox-modal';
        modal.hidden = true;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', label('imageViewer', 'Просмотр изображения'));

        modal.innerHTML =
            '<div class="rich-lightbox-backdrop" data-lightbox-close></div>' +
            '<div class="rich-lightbox-bar">' +
                '<span class="rich-lightbox-counter" data-lightbox-counter>1 / 1</span>' +
                '<div class="rich-lightbox-actions">' +
                    '<a class="rich-lightbox-btn" data-lightbox-download download target="_blank" rel="noopener" title="' + label('downloadPhoto', 'Скачать фото') + '" aria-label="' + label('download', 'Скачать') + '">' +
                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' +
                    '</a>' +
                    '<button type="button" class="rich-lightbox-btn" data-lightbox-close aria-label="' + label('close', 'Закрыть') + '">&times;</button>' +
                '</div>' +
            '</div>' +
            '<div class="rich-lightbox-stage">' +
                '<button type="button" class="rich-lightbox-nav rich-lightbox-nav--prev" data-lightbox-prev aria-label="' + label('previousPhoto', 'Предыдущее фото') + '">&#10094;</button>' +
                '<div class="rich-lightbox-content">' +
                    '<img class="rich-lightbox-img" data-lightbox-img src="" alt="">' +
                    '<div class="rich-lightbox-caption" data-lightbox-caption></div>' +
                '</div>' +
                '<button type="button" class="rich-lightbox-nav rich-lightbox-nav--next" data-lightbox-next aria-label="' + label('nextPhoto', 'Следующее фото') + '">&#10095;</button>' +
            '</div>';

        document.body.appendChild(modal);

        var modalImg = modal.querySelector('[data-lightbox-img]');
        var modalCaption = modal.querySelector('[data-lightbox-caption]');
        var modalCounter = modal.querySelector('[data-lightbox-counter]');
        var modalDownload = modal.querySelector('[data-lightbox-download]');
        var prevBtn = modal.querySelector('[data-lightbox-prev]');
        var nextBtn = modal.querySelector('[data-lightbox-next]');

        var currentIndex = 0;
        var validImages = [];
        var lightboxLastFocus = null;

        var openModal = function (trigger) {
            lightboxLastFocus = trigger || document.activeElement;
            modal.hidden = false;
            document.body.classList.add('lightbox-active');
            var closeButton = modal.querySelector('button[data-lightbox-close]');
            if (closeButton) { closeButton.focus(); }
        };

        images.forEach(function (img) {
            if (img.width > 0 && img.width < 80 && img.height > 0 && img.height < 80) { return; }

            var trigger = img.closest('a[href]') || img;
            img.classList.add('is-lightboxable');
            img.setAttribute('title', label('zoomImage', 'Нажмите для увеличения'));
            if (trigger === img) {
                img.setAttribute('role', 'button');
                img.setAttribute('tabindex', '0');
                img.setAttribute(
                    'aria-label',
                    (img.getAttribute('alt') ? img.getAttribute('alt') + '. ' : '')
                        + label('zoomImage', 'Нажмите для увеличения')
                );
            }
            validImages.push(img);

            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                var idx = validImages.indexOf(img);
                if (idx !== -1) {
                    showIndex(idx);
                    openModal(trigger);
                }
            });
            trigger.addEventListener('keydown', function (e) {
                // Ссылка сама создаст click по Enter; для изображения обрабатываем
                // Enter и Space, для ссылки — только Space.
                if (e.key !== ' ' && !(trigger === img && e.key === 'Enter')) { return; }
                e.preventDefault();
                var idx = validImages.indexOf(img);
                if (idx !== -1) {
                    showIndex(idx);
                    openModal(trigger);
                }
            });
        });

        if (!validImages.length) { return; }

        var showIndex = function (idx) {
            if (idx < 0) { idx = validImages.length - 1; }
            if (idx >= validImages.length) { idx = 0; }
            currentIndex = idx;

            var target = validImages[currentIndex];
            var src = target.getAttribute('src') || target.currentSrc;
            var alt = target.getAttribute('alt') || '';

            var fig = target.closest('figure');
            var figCap = fig ? fig.querySelector('figcaption') : null;
            var captionText = figCap ? figCap.innerText : alt;

            modalImg.src = src;
            modalImg.alt = alt;
            modalDownload.href = src;

            if (captionText && captionText.trim() !== '') {
                modalCaption.innerText = captionText.trim();
                modalCaption.hidden = false;
            } else {
                modalCaption.hidden = true;
                modalCaption.innerText = '';
            }

            modalCounter.innerText = (currentIndex + 1) + ' / ' + validImages.length;
            prevBtn.hidden = validImages.length <= 1;
            nextBtn.hidden = validImages.length <= 1;
        };

        var closeModal = function () {
            if (modal.hidden) { return; }
            modal.hidden = true;
            document.body.classList.remove('lightbox-active');
            if (lightboxLastFocus && lightboxLastFocus.focus) { lightboxLastFocus.focus(); }
        };

        modal.querySelectorAll('[data-lightbox-close]').forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });

        prevBtn.addEventListener('click', function () { showIndex(currentIndex - 1); });
        nextBtn.addEventListener('click', function () { showIndex(currentIndex + 1); });

        document.addEventListener('keydown', function (e) {
            if (modal.hidden) { return; }
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeModal();
            } else if (e.key === 'ArrowLeft' || e.keyCode === 37) {
                showIndex(currentIndex - 1);
            } else if (e.key === 'ArrowRight' || e.keyCode === 39) {
                showIndex(currentIndex + 1);
            } else if (e.key === 'Tab') {
                var focusable = Array.prototype.filter.call(
                    modal.querySelectorAll('button:not([disabled]), a[href]'),
                    function (el) { return el.offsetParent !== null; }
                );
                if (!focusable.length) { return; }
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
    })();

    /* ==========================================================================
       1. Быстрый шеринг выделенной цитаты (Quote-Share Toolbar)
       ========================================================================== */
    (function () {
        var popover = document.createElement('div');
        popover.className = 'quote-share-popover';
        popover.hidden = true;
        popover.innerHTML =
            '<button type="button" class="quote-share-btn" data-action="tg">' +
                '<svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M21.9 4.6 19 19.3c-.2 1-.8 1.2-1.6.8l-4.5-3.3-2.2 2.1c-.2.2-.4.4-.9.4l.3-4.6 8.4-7.6c.4-.3-.1-.5-.6-.2L7.6 13.4l-4.5-1.4c-1-.3-1-1 .2-1.4l17.3-6.7c.8-.3 1.5.2 1.3 1.3z"/></svg> Telegram' +
            '</button>' +
            '<button type="button" class="quote-share-btn" data-action="copy">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> ' + label('copy', 'Копировать') +
            '</button>';
        document.body.appendChild(popover);

        var currentSelectedText = '';

        var handleSelection = function () {
            var sel = window.getSelection();
            if (!sel || sel.isCollapsed) {
                popover.hidden = true;
                return;
            }
            var text = sel.toString().trim();
            if (text.length < 8) {
                popover.hidden = true;
                return;
            }
            var anchor = sel.anchorNode;
            if (!anchor) { return; }
            var parent = anchor.nodeType === 3 ? anchor.parentNode : anchor;
            if (!parent.closest('.rich-content, .newsdetail-article, .newsdetail')) {
                popover.hidden = true;
                return;
            }

            currentSelectedText = text;
            var range = sel.getRangeAt(0);
            var rect = range.getBoundingClientRect();

            popover.style.setProperty('--quote-popover-top', (window.scrollY + rect.top - 48) + 'px');
            popover.style.setProperty('--quote-popover-left', (window.scrollX + rect.left + (rect.width / 2)) + 'px');
            popover.hidden = false;
        };

        document.addEventListener('mouseup', function () { setTimeout(handleSelection, 10); });
        document.addEventListener('keyup', function () { setTimeout(handleSelection, 10); });

        popover.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) { return; }
            var action = btn.getAttribute('data-action');
            if (action === 'tg') {
                var url = 'https://t.me/share/url?url=' + encodeURIComponent(window.location.href) + '&text=' + encodeURIComponent('«' + currentSelectedText + '»');
                window.open(url, '_blank', 'noopener');
            } else if (action === 'copy') {
                var copyText = '«' + currentSelectedText + '» — ' + window.location.href;
                navigator.clipboard.writeText(copyText).then(function () {
                    btn.textContent = '✓ ' + label('copied', 'Скопировано');
                    setTimeout(function () {
                        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> '
                            + label('copy', 'Копировать');
                    }, 2000);
                });
            }
        });
    })();

    /* ==========================================================================
       2. Интерактивные опросы (Poll AJAX)
       ========================================================================== */
    document.querySelectorAll('[data-poll-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var card = form.closest('.news-poll-card');
            if (!card) { return; }
            var pollId = card.getAttribute('data-poll-id');
            var selected = form.querySelector('input[name="poll_option"]:checked');
            if (!selected) { return; }

            var btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; }

            var body = new URLSearchParams();
            body.append('option', selected.value);
            var csrf = form.querySelector('input[name="csrf_token"]');
            if (csrf) { body.append('csrf_token', csrf.value); }

            fetch('/api/polls/' + pollId + '/vote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok || !data.ok) {
                        throw new Error(data.error || 'HTTP ' + res.status);
                    }
                    return data;
                });
            }).then(function (data) {
                if (!data.results) { throw new Error('Invalid poll response'); }
                var resDiv = card.querySelector('.news-poll-card__results');
                if (!resDiv) { return; }

                resDiv.textContent = '';
                (Array.isArray(data.results.items) ? data.results.items : []).forEach(function (item) {
                    var percent = Number(item.percent);
                    percent = Number.isFinite(percent) ? Math.min(100, Math.max(0, percent)) : 0;
                    var votes = Number.parseInt(item.votes, 10);
                    votes = Number.isFinite(votes) ? Math.max(0, votes) : 0;

                    var row = document.createElement('div');
                    row.className = 'news-poll-res-row';
                    var info = document.createElement('div');
                    info.className = 'news-poll-res-info';
                    var resultLabel = document.createElement('span');
                    resultLabel.className = 'news-poll-res-label';
                    resultLabel.textContent = String(item.label == null ? '' : item.label);
                    var value = document.createElement('span');
                    value.className = 'news-poll-res-val';
                    value.textContent = percent + '% (' + votes + ')';
                    info.appendChild(resultLabel);
                    info.appendChild(value);

                    var track = document.createElement('div');
                    track.className = 'news-poll-bar-track';
                    track.setAttribute('role', 'progressbar');
                    track.setAttribute('aria-valuemin', '0');
                    track.setAttribute('aria-valuemax', '100');
                    track.setAttribute('aria-valuenow', String(percent));
                    track.setAttribute('aria-label', resultLabel.textContent);
                    var fill = document.createElement('div');
                    fill.className = 'news-poll-bar-fill';
                    fill.style.setProperty('--poll-percent', percent + '%');
                    track.appendChild(fill);

                    row.appendChild(info);
                    row.appendChild(track);
                    resDiv.appendChild(row);
                });

                var meta = document.createElement('div');
                meta.className = 'news-poll-card__meta';
                meta.appendChild(document.createTextNode(label('totalVotes', 'Всего голосов:') + ' '));
                var total = document.createElement('strong');
                var totalValue = Number.parseInt(data.results.total, 10);
                total.textContent = String(Number.isFinite(totalValue) ? Math.max(0, totalValue) : 0);
                meta.appendChild(total);
                resDiv.appendChild(meta);
                resDiv.hidden = false;
                form.hidden = true;
            }).catch(function (err) {
                if (btn) { btn.disabled = false; }
                if (window.showToast) {
                    window.showToast(err && err.message ? err.message : 'Request failed', 'error');
                }
            });
        });
    });

    /* ==========================================================================
       3. Кнопка Печать / Экспорт в PDF
       ========================================================================== */
    document.querySelectorAll('[data-print-page]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });
