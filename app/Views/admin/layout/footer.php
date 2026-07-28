    </main>
</div>

<div class="media-modal" data-media-modal hidden role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
    <div class="media-modal__dialog">
        <div class="media-modal__head">
            <div class="media-modal__tabs">
                <button type="button" class="media-modal__tab is-active" data-media-tab="library">Библиотека файлов</button>
                <button type="button" class="media-modal__tab" data-media-tab="upload">Загрузить файлы</button>
            </div>
            <button type="button" class="media-modal__close" data-media-close aria-label="Закрыть">×</button>
        </div>
        <div class="media-modal__toolbar" data-media-toolbar>
            <input type="search" class="media-modal__search" data-media-search placeholder="Поиск в медиабиблиотеке…">
        </div>
        <div class="media-modal__upload" data-media-upload data-csrf="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES) ?>" style="display:none;">
            <div class="media-modal__dropzone">
                <div style="font-weight:600; font-size:15px; margin-bottom:4px; color:var(--admin-ink, #0f172a);">Перетащите файлы сюда</div>
                <div style="font-size:13px; color:var(--admin-muted, #64748b); margin-bottom:14px;">или нажмите кнопку для выбора на диске (до 200 МБ)</div>
                <label class="btn btn--primary" data-media-upload-button style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <?= \App\Core\AdminUi::icon('plus', 16, 'btn__icon', 2.5) ?>
                    Выберите файл
                    <input type="file" data-media-upload-input style="display:none;">
                </label>
                <div class="media-modal__upload-status" data-media-upload-status aria-live="polite" style="margin-top:10px;"></div>
            </div>
        </div>
        <div class="media-modal__grid" data-media-grid aria-busy="true">
            <div class="media-modal__empty">Загрузка…</div>
        </div>
        <div class="media-modal__footer">
            <div class="media-modal__selected-info" data-media-selected-info>Файл не выбран</div>
            <div class="media-modal__footer-actions">
                <button type="button" class="btn" data-media-close>Отмена</button>
                <button type="button" class="btn btn--primary" data-media-select-btn disabled>Выбрать</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= \App\Core\SecurityHeaders::nonce() ?>">
var stickyActions = Array.prototype.slice.call(document.querySelectorAll('.form-actions--sticky'));
if (stickyActions.length) {
    document.body.classList.add('has-sticky-actions');
    stickyActions.forEach(function (actions) {
        actions.setAttribute('role', 'toolbar');
        actions.setAttribute('aria-label', 'Действия формы');
        actions.classList.remove('is-context-hidden');
    });
}
/* Навигация админки: мобильная панель и запоминаемое сворачивание на десктопе. */
(function () {
    var t = document.querySelector('[data-sidebar-toggle]');
    var s = document.querySelector('[data-sidebar]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');
    var collapse = document.querySelector('[data-sidebar-collapse]');

    function setMobileOpen(open) {
        document.body.classList.toggle('sidebar-open', open);
        if (s) {
            var mobile = window.matchMedia('(max-width: 960px)').matches;
            s.inert = mobile && !open;
            if (mobile) s.setAttribute('aria-hidden', open ? 'false' : 'true');
            else s.removeAttribute('aria-hidden');
        }
        if (t) {
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
            t.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        }
    }

    function syncCollapsedState() {
        if (!collapse) return;
        var collapsed = document.documentElement.classList.contains('admin-nav-collapsed');
        collapse.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        collapse.setAttribute('title', collapsed ? 'Развернуть меню' : 'Свернуть меню');
        var label = collapse.querySelector('span');
        if (label) label.textContent = collapsed ? 'Развернуть меню' : 'Свернуть меню';
    }

    if (t && s) {
        setMobileOpen(false);
        t.addEventListener('click', function () {
            var opening = !document.body.classList.contains('sidebar-open');
            setMobileOpen(opening);
            if (opening) {
                var current = s.querySelector('[aria-current="page"]') || s.querySelector('.admin-nav-item');
                if (current) current.focus();
            }
        });
        s.addEventListener('click', function (e) {
            if (e.target.closest('.admin-nav-item') && window.matchMedia('(max-width: 960px)').matches) {
                setMobileOpen(false);
            }
        });
        if (backdrop) backdrop.addEventListener('click', function () { setMobileOpen(false); t.focus(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                setMobileOpen(false);
                t.focus();
            }
        });
        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 960px)').matches) setMobileOpen(false);
        });
    }

    if (collapse) {
        syncCollapsedState();
        collapse.addEventListener('click', function () {
            var collapsed = document.documentElement.classList.toggle('admin-nav-collapsed');
            try { localStorage.setItem('artstudio:admin-sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
            syncCollapsedState();
        });
    }
})();
</script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/vendor/editor.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/admin.js'), ENT_QUOTES) ?>"></script>
</body>
</html>
