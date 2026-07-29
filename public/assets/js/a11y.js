/* Версия для слабовидящих: панель управления + сохранение в cookie.
   Формат cookie "a11y": "scheme:size:images", например "cw:l:on".
   Отсутствие/пустое значение — обычная версия. Начальное состояние также
   применяется на сервере (_header.php), поэтому мигания нет. */
(function () {
    'use strict';

    var COOKIE = 'a11y';
    var SCHEMES = ['cw', 'wc', 'bb'];
    var SIZES = ['m', 'l', 'xl'];

    function readCookie(name) {
        var m = document.cookie.match('(?:^|; )' + name + '=([^;]*)');
        return m ? decodeURIComponent(m[1]) : '';
    }

    function writeCookie(name, value) {
        var maxAge = value ? 60 * 60 * 24 * 365 : 0;
        document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + maxAge + '; samesite=Lax';
    }

    function parse(value) {
        var p = (value || '').split(':');
        return {
            on: SCHEMES.indexOf(p[0]) !== -1,
            scheme: SCHEMES.indexOf(p[0]) !== -1 ? p[0] : 'cw',
            size: SIZES.indexOf(p[1]) !== -1 ? p[1] : 'l',
            images: p[2] === 'off' ? 'off' : 'on'
        };
    }

    var state = parse(readCookie(COOKIE));

    function apply() {
        var h = document.documentElement;
        if (state.on) {
            h.setAttribute('data-a11y', '1');
            h.setAttribute('data-a11y-scheme', state.scheme);
            h.setAttribute('data-a11y-size', state.size);
            h.setAttribute('data-a11y-images', state.images);
            writeCookie(COOKIE, state.scheme + ':' + state.size + ':' + state.images);
        } else {
            h.removeAttribute('data-a11y');
            h.removeAttribute('data-a11y-scheme');
            h.removeAttribute('data-a11y-size');
            h.removeAttribute('data-a11y-images');
            writeCookie(COOKIE, '');
        }
        syncButtons();
    }

    function syncButtons() {
        document.querySelectorAll('[data-a11y-set]').forEach(function (btn) {
            var spec = btn.getAttribute('data-a11y-set').split(':');
            var key = spec[0], val = spec[1];
            btn.setAttribute('aria-pressed', String(state[key] === val));
        });
    }

    function createDynamicPanel() {
        var panel = document.createElement('section');
        panel.className = 'a11y-panel';
        panel.id = 'a11y-panel';
        panel.setAttribute('aria-label', 'Настройки версии для слабовидящих');
        panel.innerHTML =
            '<div class="a11y-panel__group">' +
                '<b>Цвет:</b>' +
                '<button type="button" data-a11y-set="scheme:cw" title="Чёрным по белому">A</button>' +
                '<button type="button" data-a11y-set="scheme:wc" title="Белым по чёрному">A</button>' +
                '<button type="button" data-a11y-set="scheme:bb" title="Тёмно-синим по голубому">A</button>' +
            '</div>' +
            '<div class="a11y-panel__group">' +
                '<b>Размер:</b>' +
                '<button type="button" class="a11y-panel__size-a1" data-a11y-set="size:m" title="Обычный">A</button>' +
                '<button type="button" class="a11y-panel__size-a2" data-a11y-set="size:l" title="Крупный">A</button>' +
                '<button type="button" class="a11y-panel__size-a3" data-a11y-set="size:xl" title="Очень крупный">A</button>' +
            '</div>' +
            '<div class="a11y-panel__group">' +
                '<b>Изображения:</b>' +
                '<button type="button" data-a11y-set="images:on" title="Показывать">Вкл</button>' +
                '<button type="button" data-a11y-set="images:off" title="Скрыть">Выкл</button>' +
            '</div>' +
            '<a href="#" class="a11y-panel__off">Обычная версия</a>';

        var target = document.querySelector('.site-header') || document.querySelector('.repo-topbar') || document.body;
        if (target.parentNode) {
            target.parentNode.insertBefore(panel, target);
        } else {
            document.body.insertBefore(panel, document.body.firstChild);
        }
        bindPanelEvents(panel);
        return panel;
    }

    function bindPanelEvents(panelEl) {
        if (!panelEl) return;
        panelEl.querySelectorAll('[data-a11y-set]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var spec = btn.getAttribute('data-a11y-set').split(':');
                state[spec[0]] = spec[1];
                state.on = true;
                apply();
            });
        });

        var off = panelEl.querySelector('.a11y-panel__off');
        if (off) {
            off.addEventListener('click', function (e) {
                e.preventDefault();
                state.on = false;
                apply();
                panelEl.classList.remove('is-open');
            });
        }
    }

    function onReady() {
        var toggles = document.querySelectorAll('.a11y-toggle');
        var panel = document.querySelector('.a11y-panel');
        var lastToggle = null;
        var setPanelOpen = function (open, restoreFocus) {
            if (!panel) { return; }
            panel.classList.toggle('is-open', open);
            toggles.forEach(function (toggle) {
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            window.dispatchEvent(new CustomEvent('a11y:panelchange', { detail: { open: open } }));
            if (restoreFocus && lastToggle) { lastToggle.focus(); }
        };

        if (panel) {
            bindPanelEvents(panel);
        }

        if (toggles.length) {
            toggles.forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    lastToggle = toggle;
                    if (!panel) {
                        panel = createDynamicPanel();
                    }
                    var willOpen = !panel.classList.contains('is-open');
                    if (willOpen && !state.on) {
                        state.on = true;
                        apply();
                    }
                    setPanelOpen(willOpen, false);
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) {
                    setPanelOpen(false, true);
                }
            });
        }

        syncButtons();
        if (panel) { setPanelOpen(panel.classList.contains('is-open'), false); }
    }

    // Атрибуты уже проставлены сервером; JS лишь синхронизирует и вешает обработчики.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
