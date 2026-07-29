</main>
<script src="/assets/js/a11y.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Переключение режима вида (Grid / Table) ---
    var gridView = document.getElementById('rd-grid-view');
    var tableView = document.getElementById('rd-table-view');
    var toggleBtns = document.querySelectorAll('[data-view-mode]');

    var savedMode = localStorage.getItem('repo_view_mode') || 'grid';
    setViewMode(savedMode);

    toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-view-mode');
            setViewMode(mode);
            localStorage.setItem('repo_view_mode', mode);
        });
    });

    function setViewMode(mode) {
        toggleBtns.forEach(function (b) {
            b.classList.toggle('is-active', b.getAttribute('data-view-mode') === mode);
        });
        if (gridView && tableView) {
            if (mode === 'table') {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
            } else {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
            }
        }
    }

    // --- Копирование ссылки на файл ---
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy-link]');
        if (!btn) return;

        e.preventDefault();
        var relUrl = btn.getAttribute('data-copy-link');
        var fullUrl = window.location.origin + relUrl;

        navigator.clipboard.writeText(fullUrl).then(function () {
            var oldTitle = btn.getAttribute('title');
            btn.setAttribute('title', 'Скопировано!');
            var oldText = btn.innerHTML;
            btn.innerHTML = <?= json_encode(\App\Core\Icon::render('check', 16), JSON_UNESCAPED_SLASHES) ?>;
            setTimeout(function () {
                btn.setAttribute('title', oldTitle);
                btn.innerHTML = oldText;
            }, 1800);
        }).catch(function () {
            prompt('Ссылка на файл:', fullUrl);
        });
    });
});
</script>
</body>
</html>
