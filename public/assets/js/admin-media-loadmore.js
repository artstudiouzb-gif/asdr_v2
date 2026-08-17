(function () {
    'use strict';

    var STEP = 15;
    var states = new WeakMap();

    function configFor(grid) {
        if (grid.classList.contains('gallery-media-modal__grid')) {
            return {
                selector: '.gallery-media-card',
                searchSelector: '[data-gallery-search]'
            };
        }
        return {
            selector: '.media-modal__item',
            searchSelector: '[data-media-search]'
        };
    }

    function cardsFor(grid, selector) {
        return Array.prototype.slice.call(grid.querySelectorAll(selector));
    }

    function schedule(state, reset) {
        if (reset) { state.resetPending = true; }
        if (state.scheduled) { return; }
        state.scheduled = true;
        window.requestAnimationFrame(function () {
            state.scheduled = false;
            var shouldReset = state.resetPending;
            state.resetPending = false;
            render(state, shouldReset);
        });
    }

    function enhancePager(state, pager) {
        var actions = pager.querySelector('.media-client-pager__actions');
        if (actions) { actions.hidden = true; }

        var button = pager.querySelector('[data-media-load-more]');
        if (!button) {
            button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn--secondary';
            button.setAttribute('data-media-load-more', '');
            button.textContent = 'Показать ещё';
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                state.limit += STEP;
                render(state, false);
            });
            pager.appendChild(button);
        }
        return button;
    }

    function render(state, reset) {
        var grid = state.grid;
        if (!grid || !grid.isConnected) { return; }
        if (reset) { state.limit = STEP; }

        var cards = cardsFor(grid, state.selector);
        var total = cards.length;
        var shown = Math.min(state.limit, total);

        cards.forEach(function (card, index) {
            var visible = index < shown;
            card.hidden = !visible;
            card.setAttribute('aria-hidden', visible ? 'false' : 'true');

            var image = card.querySelector('img');
            if (image) {
                image.loading = 'lazy';
                image.decoding = 'async';
                if (!visible) {
                    try { image.fetchPriority = 'low'; } catch (error) {}
                }
            }
        });

        var pager = grid.querySelector('[data-media-client-pager]');
        if (!pager) {
            // Базовый workflow создаёт контейнер пагинации после рендера карточек.
            // Ждём его, чтобы не дублировать нижнюю панель.
            window.setTimeout(function () { schedule(state, false); }, 0);
            return;
        }

        var status = pager.querySelector('.media-client-pager__status');
        if (status) {
            status.textContent = total
                ? 'Показано ' + shown + ' из ' + total
                : 'Нет файлов';
        }

        var button = enhancePager(state, pager);
        button.hidden = shown >= total;
        pager.hidden = total <= STEP;
    }

    function setup(grid) {
        if (!grid || states.has(grid)) { return; }

        var config = configFor(grid);
        var root = grid.closest('.media-modal, .gallery-media-modal') || document;
        var state = {
            grid: grid,
            selector: config.selector,
            search: root.querySelector(config.searchSelector),
            limit: STEP,
            scheduled: false,
            resetPending: false
        };
        states.set(grid, state);

        if (state.search) {
            state.search.addEventListener('input', function () {
                // admin.js сначала перерисует результаты поиска, затем базовый
                // workflow применит первую порцию. После этого возвращаем UX
                // «Показать ещё» и начинаем снова с 15 результатов.
                window.setTimeout(function () { schedule(state, true); }, 0);
            });
        }

        new MutationObserver(function (mutations) {
            var changed = mutations.some(function (mutation) {
                return mutation.type === 'childList'
                    && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0);
            });
            if (changed) { schedule(state, true); }
        }).observe(grid, { childList: true });

        schedule(state, true);
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('.media-modal__grid, .gallery-media-modal__grid').forEach(setup);
    }

    scan(document);
    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!node || node.nodeType !== 1) { return; }
                    if (node.matches && node.matches('.media-modal__grid, .gallery-media-modal__grid')) {
                        setup(node);
                    }
                    scan(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }
})();
