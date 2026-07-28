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
        <div class="media-modal__upload u-inline-c8be1ccba6" data-media-upload data-csrf="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES) ?>">
            <div class="media-modal__dropzone">
                <div class="u-inline-30220647a6">Перетащите файлы сюда</div>
                <div class="u-inline-a43690dc6d">или нажмите кнопку для выбора на диске (до 200 МБ)</div>
                <label class="btn btn--primary u-inline-42278569ee" data-media-upload-button>
                    <?= \App\Core\AdminUi::icon('plus', 16, 'btn__icon', 2.5) ?>
                    Выберите файл
                    <input class="u-inline-c8be1ccba6" type="file" data-media-upload-input>
                </label>
                <div class="media-modal__upload-status u-inline-d8a81eac84" data-media-upload-status aria-live="polite"></div>
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

/* Обратная связь при сохранении контента: сразу показываем процесс у нажатой
   кнопки, а после серверного redirect дублируем success-flash заметным toast. */
document.querySelectorAll('form[data-content-draft]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var submitter = event.submitter;
        window.setTimeout(function () {
            if (event.defaultPrevented || !submitter) { return; }
            submitter.dataset.originalHtml = submitter.innerHTML;
            submitter.classList.add('is-loading');
            submitter.setAttribute('aria-busy', 'true');
            submitter.textContent = 'Сохранение…';

            window.setTimeout(function () {
                if (!document.body.contains(submitter)) { return; }
                submitter.classList.remove('is-loading');
                submitter.removeAttribute('aria-busy');
                if (submitter.dataset.originalHtml) {
                    submitter.innerHTML = submitter.dataset.originalHtml;
                    delete submitter.dataset.originalHtml;
                }
            }, 15000);
        }, 0);
    });
});

window.addEventListener('DOMContentLoaded', function () {
    var success = document.querySelector('.admin-main > .alert--success');
    if (!success) { return; }

    var message = (success.textContent || '').trim();
    if (!message) { return; }

    var toast = document.createElement('div');
    toast.className = 'admin-toast-notification admin-toast--success';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = '<div class="u-inline-7e30d285d2">'
        + '<span class="u-inline-4f1925a8a6" aria-hidden="true">✓</span>'
        + '<span class="u-inline-94c3db5540"></span>'
        + '</div>'
        + '<button class="u-inline-d8c73d8aa0" type="button" aria-label="Закрыть уведомление">✕</button>';
    toast.querySelector('.u-inline-94c3db5540').textContent = message;
    toast.querySelector('button').addEventListener('click', function () { toast.remove(); });
    document.body.appendChild(toast);
    success.remove();

    window.requestAnimationFrame(function () { toast.classList.add('is-visible'); });
    window.setTimeout(function () {
        toast.classList.remove('is-visible');
        window.setTimeout(function () { toast.remove(); }, 300);
    }, 5000);
});
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
