(function () {
    'use strict';

    // Универсальная функция копирования в буфер обмена (работает по HTTPS и HTTP с фаллбэком)
    function copyToClipboard(text, btnEl) {
        if (!text) { return Promise.reject(new Error('Empty text')); }

        function showSuccess() {
            if (btnEl) {
                const oldHtml = btnEl.innerHTML;
                const oldColor = btnEl.style.color;
                const oldBorder = btnEl.style.borderColor;
                btnEl.innerHTML = '✓ Скопировано!';
                btnEl.style.color = '#10b981';
                btnEl.style.borderColor = '#10b981';
                setTimeout(function () {
                    btnEl.innerHTML = oldHtml;
                    btnEl.style.color = oldColor;
                    btnEl.style.borderColor = oldBorder;
                }, 2000);
            }
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(function () {
                showSuccess();
                return true;
            }).catch(function () {
                return fallbackCopy(text, showSuccess);
            });
        }
        return Promise.resolve(fallbackCopy(text, showSuccess));
    }

    function fallbackCopy(text, onSuccess) {
        try {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '-9999px';
            textarea.setAttribute('readonly', '');
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            var successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (successful) {
                if (onSuccess) { onSuccess(); }
                return true;
            }
        } catch (e) {
            console.error('Fallback copy failed:', e);
        }
        return false;
    }

    window.copyToClipboard = copyToClipboard;

    document.addEventListener('click', function (event) {
        const copyBtn = event.target.closest('[data-copy-link], [data-copy-text]');
        if (copyBtn) {
            event.preventDefault();
            const text = copyBtn.getAttribute('data-copy-link') || copyBtn.getAttribute('data-copy-text');
            if (text) {
                copyToClipboard(text, copyBtn);
                return;
            }
        }

        const addBtn = event.target.closest('[data-repeater-add]');
        if (addBtn) {
            event.preventDefault();
            const name = addBtn.getAttribute('data-repeater-add');
            const container = document.querySelector('[data-repeater="' + name + '"]');
            const template = document.querySelector('template[data-repeater-template="' + name + '"]');
            if (!container || !template) {
                return;
            }
            const index = container.children.length;
            const html = template.innerHTML.replace(/__INDEX__/g, String(index));
            const wrapper = document.createElement('div');
            wrapper.className = 'repeater-row';
            wrapper.innerHTML = html;
            container.appendChild(wrapper);
            if (window.__enhanceIconFields) { window.__enhanceIconFields(wrapper); }
            return;
        }

        const removeBtn = event.target.closest('[data-repeater-remove]');
        if (removeBtn) {
            event.preventDefault();
            const row = removeBtn.closest('.repeater-row');
            if (row) {
                row.remove();
            }
        }
    });

    // Стилизованное модальное подтверждение — замена нативного window.confirm.
    // Возвращает Promise<boolean>. Доступно и другим скриптам как window.adminConfirm.
    function adminConfirm(message) {
        return new Promise(function (resolve) {
            var overlay = document.createElement('div');
            overlay.className = 'admin-modal-overlay';
            overlay.innerHTML =
                '<div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="admin-modal-msg">'
                + '<div class="admin-modal__body">'
                + '<div class="admin-modal__icon" aria-hidden="true">?</div>'
                + '<p class="admin-modal__msg" id="admin-modal-msg"></p>'
                + '</div>'
                + '<div class="admin-modal__actions">'
                + '<button type="button" class="btn admin-modal__cancel">Отмена</button>'
                + '<button type="button" class="btn btn--primary admin-modal__ok">Подтвердить</button>'
                + '</div>'
                + '</div>';
            overlay.querySelector('.admin-modal__msg').textContent = message;
            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(function () { overlay.classList.add('is-open'); });

            var okBtn = overlay.querySelector('.admin-modal__ok');
            var cancelBtn = overlay.querySelector('.admin-modal__cancel');
            okBtn.focus();

            function close(result) {
                overlay.classList.remove('is-open');
                document.removeEventListener('keydown', onKey);
                document.body.style.overflow = '';
                setTimeout(function () { overlay.remove(); }, 150);
                resolve(result);
            }
            function onKey(e) {
                if (e.key === 'Escape') { close(false); }
                else if (e.key === 'Enter') { close(true); }
            }
            okBtn.addEventListener('click', function () { close(true); });
            cancelBtn.addEventListener('click', function () { close(false); });
            overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(false); } });
            document.addEventListener('keydown', onKey);
        });
    }
    window.adminConfirm = adminConfirm;

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1') { return; } // уже подтверждено — пропускаем
            event.preventDefault();
            adminConfirm(form.getAttribute('data-confirm')).then(function (ok) {
                if (!ok) { return; }
                form.dataset.confirmed = '1';
                if (typeof form.requestSubmit === 'function') { form.requestSubmit(); }
                else { form.submit(); }
            });
        });
    });

    // Применение шаблона страницы: режим «заменить» требует подтверждения.
    document.querySelectorAll('[data-snippet-insert]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var mode = form.querySelector('select[name=mode]');
            if (mode && mode.value === 'replace'
                && !window.confirm('Заменить все текущие блоки этого языка блоками из шаблона? Действие необратимо.')) {
                event.preventDefault();
            }
        });
    });

    // Чанковая загрузка больших файлов через File API.
    var chunkBtn = document.getElementById('chunk_upload_btn');
    if (chunkBtn) {
        chunkBtn.addEventListener('click', function () {
            var input = document.getElementById('chunk_file');
            var progress = document.getElementById('chunk_progress');
            var access = document.getElementById('chunk_access');
            if (!input.files || !input.files.length) {
                progress.textContent = 'Выберите файл.';
                return;
            }
            var file = input.files[0];
            var chunkSize = 1024 * 1024; // 1 МБ
            var total = Math.ceil(file.size / chunkSize);
            var uploadId = '';
            for (var i = 0; i < 32; i++) { uploadId += Math.floor(Math.random() * 16).toString(16); }
            var csrf = chunkBtn.getAttribute('data-csrf');
            chunkBtn.disabled = true;

            function sendChunk(index) {
                var start = index * chunkSize;
                var blob = file.slice(start, Math.min(start + chunkSize, file.size));
                var fd = new FormData();
                fd.append('csrf_token', csrf);
                fd.append('upload_id', uploadId);
                fd.append('index', String(index));
                fd.append('total', String(total));
                fd.append('name', file.name);
                fd.append('access_type', access.value);
                fd.append('chunk', blob);

                fetch('/admin/files/chunk', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'HTTP ' + r.status }; }); })
                    .then(function (res) {
                        if (!res.ok) {
                            progress.textContent = 'Ошибка: ' + (res.error || 'неизвестная');
                            chunkBtn.disabled = false;
                            return;
                        }
                        if (res.done) {
                            progress.textContent = 'Готово! Файл загружен. Обновите страницу.';
                            chunkBtn.disabled = false;
                            setTimeout(function () { window.location.reload(); }, 800);
                            return;
                        }
                        progress.textContent = 'Загрузка… ' + Math.round(((index + 1) / total) * 100) + '%';
                        sendChunk(index + 1);
                    })
                    .catch(function () {
                        progress.textContent = 'Сетевая ошибка при загрузке.';
                        chunkBtn.disabled = false;
                    });
            }

            progress.textContent = 'Загрузка… 0%';
            sendChunk(0);
        });
    }

    // --- Массовый выбор в списках (задача 91) ---
    document.querySelectorAll('[data-select-all]').forEach(function (master) {
        var table = master.closest('table');
        if (!table) { return; }
        var items = table.querySelectorAll('[data-bulk-item]');
        var formId = items.length ? items[0].getAttribute('form') : '';
        var bulkForm = formId ? document.getElementById(formId) : null;
        var counter = bulkForm ? bulkForm.querySelector('[data-bulk-count]') : null;
        function refresh() {
            var checked = table.querySelectorAll('[data-bulk-item]:checked').length;
            if (counter) { counter.textContent = checked + ' выбрано'; }
            master.checked = checked > 0 && checked === items.length;
            master.indeterminate = checked > 0 && checked < items.length;
        }
        master.addEventListener('change', function () {
            items.forEach(function (i) { i.checked = master.checked; });
            refresh();
        });
        items.forEach(function (i) { i.addEventListener('change', refresh); });
    });

    // Не отправлять bulk-форму без выбранного действия/записей.
    document.querySelectorAll('[data-bulk-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var formId = form.id;
            var associated = Array.prototype.filter.call(document.querySelectorAll('[data-bulk-item]:checked'), function (item) {
                return item.getAttribute('form') === formId;
            });
            var anyChecked = associated.length > 0;
            var action = form.querySelector('[name="bulk_action"]');
            if (!anyChecked) { e.preventDefault(); alert('Выберите хотя бы одну запись.'); return; }
            if (action && !action.value) { e.preventDefault(); alert('Выберите действие.'); return; }
            if (action && action.value === 'trash'
                && !window.confirm('Переместить выбранные записи в корзину?')) {
                e.preventDefault();
            }
        });
    });

    // --- Быстрый глобальный поиск (задача 92, Ctrl+K) ---
    (function () {
        var box = document.querySelector('[data-search]');
        if (!box) { return; }
        var input = box.querySelector('[data-search-input]');
        var results = box.querySelector('[data-search-results]');
        var timer = null, lastQuery = '';

        function render(items) {
            if (!items.length) { results.innerHTML = '<div class="admin-search__empty">Ничего не найдено</div>'; }
            else {
                results.innerHTML = items.map(function (r) {
                    return '<a class="admin-search__item" href="' + r.url + '">' +
                        '<span class="admin-search__type">' + r.type + '</span>' +
                        '<span class="admin-search__title"></span></a>';
                }).join('');
                // Заголовки вставляем через textContent (без риска XSS).
                var links = results.querySelectorAll('.admin-search__item');
                items.forEach(function (r, i) {
                    links[i].querySelector('.admin-search__title').textContent = r.title;
                });
            }
            results.hidden = false;
        }

        function search() {
            var q = input.value.trim();
            if (q === lastQuery) { return; }
            lastQuery = q;
            if (q.length < 2) { results.hidden = true; results.innerHTML = ''; return; }
            fetch('/admin/search?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) { render(data.results || []); })
                .catch(function () { results.hidden = true; });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(search, 200);
        });
        input.addEventListener('focus', function () { if (results.innerHTML) { results.hidden = false; } });
        document.addEventListener('click', function (e) {
            if (!box.contains(e.target)) { results.hidden = true; }
        });
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault(); input.focus(); input.select();
            }
            if (e.key === 'Escape') { results.hidden = true; input.blur(); }
        });
    })();

    // --- Медиабиблиотека: выбор или загрузка файла прямо из формы ---
    (function () {
        var modal = document.querySelector('[data-media-modal]');
        if (!modal) { return; }
        var grid = modal.querySelector('[data-media-grid]');
        var uploadBox = modal.querySelector('[data-media-upload]');
        var uploadInput = modal.querySelector('[data-media-upload-input]');
        var uploadStatus = modal.querySelector('[data-media-upload-status]');
        var searchInput = modal.querySelector('[data-media-search]');
        var selectedInfo = modal.querySelector('[data-media-selected-info]');
        var selectBtn = modal.querySelector('[data-media-select-btn]');
        var toolbar = modal.querySelector('[data-media-toolbar]');
        var tabs = modal.querySelectorAll('[data-media-tab]');

        var currentTarget = null;
        var currentCallback = null;
        var loaded = false;
        var loadedType = null;
        var currentType = 'image';
        var selectedUrl = null;
        var selectedName = null;
        var libraryItems = [];

        var typeOptions = {
            image: { accept: '.jpg,.jpeg,.png,.gif,.webp,.svg', label: 'изображение' },
            svg: { accept: '.svg,image/svg+xml', label: 'SVG-файл' },
            video: { accept: '.mp4,video/mp4', label: 'видео MP4' },
            document: { accept: '.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip', label: 'документ' },
            all_files: { accept: '', label: 'файл' },
            all: { accept: '', label: 'файл' }
        };

        function setUploadStatus(message, state) {
            if (!uploadStatus) { return; }
            uploadStatus.textContent = message || '';
            uploadStatus.classList.toggle('is-error', state === 'error');
            uploadStatus.classList.toggle('is-success', state === 'success');
        }

        function selectUrl(url) {
            if (!url) { return; }
            if (currentCallback) {
                currentCallback(url);
            } else if (currentTarget) {
                currentTarget.value = url;
                currentTarget.dispatchEvent(new Event('change', { bubbles: true }));
                currentTarget.dispatchEvent(new Event('input', { bubbles: true }));
            }
            close();
        }

        function updateSelectedUI() {
            if (!selectedUrl) {
                if (selectedInfo) selectedInfo.textContent = 'Файл не выбран';
                if (selectBtn) selectBtn.disabled = true;
            } else {
                if (selectedInfo) selectedInfo.textContent = 'Выбран: ' + (selectedName || selectedUrl);
                if (selectBtn) selectBtn.disabled = false;
            }

            grid.querySelectorAll('.media-modal__item').forEach(function (fig) {
                var isThis = fig.getAttribute('data-url') === selectedUrl;
                fig.classList.toggle('is-selected', isThis);
            });
        }

        function renderLibraryItems(items) {
            libraryItems = items || [];
            if (!libraryItems.length) {
                grid.innerHTML = '<div class="media-modal__empty">В библиотеке нет подходящих файлов.</div>';
                return;
            }
            grid.innerHTML = '';
            var term = searchInput ? searchInput.value.toLowerCase().trim() : '';

            libraryItems.forEach(function (it) {
                if (term && !it.name.toLowerCase().includes(term)) {
                    return;
                }
                var fig = document.createElement('button');
                fig.type = 'button';
                fig.className = 'media-modal__item';
                fig.title = it.name;
                fig.setAttribute('data-url', it.url);

                if (selectedUrl === it.url) {
                    fig.classList.add('is-selected');
                }

                var isVideo = /\.(mp4|webm|ogg|mov|m4v)$/i.test(it.url);
                var isImage = /\.(jpe?g|png|gif|svg|webp)$/i.test(it.url);
                var ext = (it.name.split('.').pop() || 'file').toUpperCase();

                if (isVideo) {
                    fig.classList.add('media-modal__item--file');
                    fig.innerHTML = '<span class="media-modal__fileicon">▶</span><span class="media-modal__filename"></span>';
                    fig.querySelector('.media-modal__filename').textContent = it.name;
                } else if (!isImage) {
                    fig.classList.add('media-modal__item--file');
                    fig.innerHTML = '<span class="media-modal__fileicon">' + ext + '</span><span class="media-modal__filename"></span>';
                    fig.querySelector('.media-modal__filename').textContent = it.name;
                } else {
                    var img = document.createElement('img');
                    img.src = it.url;
                    img.alt = it.name;
                    img.loading = 'lazy';
                    fig.appendChild(img);
                }

                fig.addEventListener('click', function () {
                    selectedUrl = it.url;
                    selectedName = it.name;
                    updateSelectedUI();
                });

                fig.addEventListener('dblclick', function () {
                    selectUrl(it.url);
                });

                grid.appendChild(fig);
            });
        }

        function loadLibrary(type, force) {
            if (!force && loaded && loadedType === type) { return Promise.resolve(); }
            loaded = false; loadedType = type;
            grid.setAttribute('aria-busy', 'true');
            grid.innerHTML = '<div class="media-modal__empty">Загрузка…</div>';
            return fetch('/admin/media/list?type=' + encodeURIComponent(type), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    loaded = true;
                    grid.setAttribute('aria-busy', 'false');
                    renderLibraryItems(data.items || []);
                })
                .catch(function () {
                    grid.setAttribute('aria-busy', 'false');
                    grid.innerHTML = '<div class="media-modal__empty">Ошибка загрузки.</div>';
                });
        }

        function switchTab(tabName) {
            tabs.forEach(function (t) {
                t.classList.toggle('is-active', t.getAttribute('data-media-tab') === tabName);
            });
            if (tabName === 'upload') {
                if (uploadBox) uploadBox.style.display = 'block';
                if (toolbar) toolbar.style.display = 'none';
                if (grid) grid.style.display = 'none';
            } else {
                if (uploadBox) uploadBox.style.display = 'none';
                if (toolbar) toolbar.style.display = 'block';
                if (grid) grid.style.display = 'grid';
            }
        }

        tabs.forEach(function (t) {
            t.addEventListener('click', function () {
                switchTab(t.getAttribute('data-media-tab'));
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                renderLibraryItems(libraryItems);
            });
        }

        if (selectBtn) {
            selectBtn.addEventListener('click', function () {
                if (selectedUrl) selectUrl(selectedUrl);
            });
        }

        function open(targetSelector, callback, type) {
            currentTarget = targetSelector ? document.querySelector(targetSelector) : null;
            currentCallback = callback || null;
            currentType = type || 'image';
            selectedUrl = null;
            selectedName = null;
            updateSelectedUI();

            var options = typeOptions[currentType] || typeOptions.all;
            if (uploadInput) {
                uploadInput.value = '';
                uploadInput.accept = options.accept;
            }
            setUploadStatus('');
            switchTab('library');
            modal.hidden = false;
            loadLibrary(currentType, false);
        }

        function close() {
            modal.hidden = true;
            currentCallback = null;
        }

        if (uploadInput && uploadBox) {
            uploadInput.addEventListener('change', function () {
                if (!uploadInput.files || !uploadInput.files.length) return;
                var file = uploadInput.files[0];
                var options = typeOptions[currentType] || typeOptions.all;
                if (!file.size || file.size > 200 * 1024 * 1024) {
                    setUploadStatus('Неверный размер файла (макс 200 МБ).', 'error');
                    return;
                }

                var chunkSize = 1024 * 1024;
                var total = Math.ceil(file.size / chunkSize);
                var uploadId = '';
                for (var i = 0; i < 32; i++) { uploadId += Math.floor(Math.random() * 16).toString(16); }

                uploadInput.disabled = true;
                setUploadStatus('Загрузка… 0%');

                function sendChunk(index) {
                    var fd = new FormData();
                    fd.append('csrf_token', uploadBox.getAttribute('data-csrf'));
                    fd.append('upload_id', uploadId);
                    fd.append('index', String(index));
                    fd.append('total', String(total));
                    fd.append('name', file.name);
                    fd.append('access_type', 'public');
                    fd.append('chunk', file.slice(index * chunkSize, Math.min((index + 1) * chunkSize, file.size)));

                    fetch('/admin/files/chunk', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (!res.ok) { throw new Error(res.error || 'Ошибка загрузки'); }
                            if (res.done) {
                                uploadInput.disabled = false;
                                setUploadStatus('Файл загружен!', 'success');
                                loaded = false;
                                switchTab('library');
                                loadLibrary(currentType, true).then(function () {
                                    if (res.url) {
                                        selectUrl(res.url);
                                    }
                                });
                                return;
                            }
                            setUploadStatus('Загрузка… ' + Math.round(((index + 1) / total) * 100) + '%');
                            sendChunk(index + 1);
                        })
                        .catch(function (err) {
                            uploadInput.disabled = false;
                            setUploadStatus('Ошибка: ' + err.message, 'error');
                        });
                }

                sendChunk(0);
            });
        }

        // Публичный API для редактора: выбор изображения/SVG с колбэком.
        window.MediaPicker = {
            pick: function (cb) { open(null, cb, 'image'); },
            pickSvg: function (cb) { open(null, cb, 'svg'); }
        };

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-media-pick]');
            if (btn) { e.preventDefault(); open(btn.getAttribute('data-media-target'), null, btn.getAttribute('data-media-type')); return; }
            if (e.target.closest('[data-media-close]') || e.target === modal) { close(); }
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
    })();

    // --- Поля SVG-иконок: код вручную ИЛИ выбор файла из медиабиблиотеки ---
    // К каждому textarea иконки добавляется панель с кнопкой «Выбрать из медиа»:
    // выбранный SVG-файл подгружается и вставляется как код (инлайн). Так поле
    // остаётся единым (icon_svg), а на сохранении код санитайзится сервером.
    (function () {
        function enhance(ta) {
            if (ta.getAttribute('data-icon-enhanced')) { return; }
            ta.setAttribute('data-icon-enhanced', '1');
            var bar = document.createElement('div');
            bar.className = 'icon-field__tools';
            var pick = document.createElement('button');
            pick.type = 'button'; pick.className = 'btn btn--small'; pick.textContent = 'Выбрать SVG из медиа';
            var clear = document.createElement('button');
            clear.type = 'button'; clear.className = 'btn btn--small'; clear.textContent = 'Очистить';
            bar.appendChild(pick); bar.appendChild(clear);
            ta.insertAdjacentElement('afterend', bar);

            pick.addEventListener('click', function () {
                if (!window.MediaPicker) { return; }
                window.MediaPicker.pickSvg(function (url) {
                    fetch(url, { credentials: 'same-origin' })
                        .then(function (r) { return r.text(); })
                        .then(function (txt) {
                            ta.value = txt.trim();
                            ta.dispatchEvent(new Event('input', { bubbles: true }));
                        })
                        .catch(function () { window.alert('Не удалось загрузить SVG-файл.'); });
                });
            });
            clear.addEventListener('click', function () {
                ta.value = '';
                ta.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
        function enhanceIn(root) {
            (root || document).querySelectorAll('textarea[name$="[icon_svg]"], textarea[name="icon_svg"]').forEach(enhance);
        }
        window.__enhanceIconFields = enhanceIn;
        enhanceIn(document);
    })();

    // --- Живое значение ползунков прозрачности (overlay/подложка hero и др.) ---
    document.addEventListener('input', function (e) {
        var input = e.target.closest('input[type="range"][data-range-input]');
        if (!input) { return; }
        var out = document.querySelector('[data-range-output="' + input.getAttribute('data-range-input') + '"]');
        if (out) { out.textContent = input.value; }
    });

    // --- Hero: произвольная высота с единицей измерения. ---
    (function () {
        var mode = document.querySelector('[data-hero-height]');
        var custom = document.querySelector('[data-hero-custom-height]');
        var value = document.getElementById('hero_height_value');
        var unit = document.getElementById('hero_height_unit');
        if (!mode || !custom || !value || !unit) { return; }

        function sync() {
            custom.hidden = mode.value !== 'custom';
            var limits = unit.value === 'px' ? [160, 2000]
                : (unit.value === 'rem' ? [10, 120] : [20, 150]);
            value.min = String(limits[0]);
            value.max = String(limits[1]);
        }
        mode.addEventListener('change', sync);
        unit.addEventListener('change', sync);
        sync();
    })();

    // --- Поле изображения с превью (медиабиблиотека / URL / загрузка файла) ---
    (function () {
        function setPreview(field, src) {
            var box = field.querySelector('[data-image-preview]');
            if (!box) { return; }
            if (src) {
                box.innerHTML = '';
                var img = document.createElement('img');
                img.src = src; img.alt = ''; img.loading = 'lazy';
                box.appendChild(img);
            } else {
                box.innerHTML = '<span class="image-field__placeholder" aria-hidden="true">'
                    + '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
                    + '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M5 18l5-5 4 4 3-3 2 2"/></svg></span>';
            }
        }
        // URL-инпут (в т.ч. установленный медиабиблиотекой — она шлёт change).
        document.addEventListener('input', function (e) {
            var input = e.target.closest('[data-image-input]');
            if (!input) { return; }
            var field = input.closest('[data-image-field]');
            if (field) { setPreview(field, input.value.trim()); }
        });
        document.addEventListener('change', function (e) {
            var input = e.target.closest('[data-image-input]');
            if (input) {
                var f = input.closest('[data-image-field]');
                if (f) { setPreview(f, input.value.trim()); }
                return;
            }
            // Локальное превью выбранного файла (до загрузки на сервер).
            var file = e.target.closest('[data-image-file]');
            if (file && file.files && file.files[0]) {
                var field = file.closest('[data-image-field]');
                if (field && window.FileReader) {
                    var reader = new FileReader();
                    reader.onload = function (ev) { setPreview(field, ev.target.result); };
                    reader.readAsDataURL(file.files[0]);
                }
            }
        });
        // Очистка.
        document.addEventListener('click', function (e) {
            var clear = e.target.closest('[data-image-clear]');
            if (!clear) { return; }
            e.preventDefault();
            var field = clear.closest('[data-image-field]');
            if (!field) { return; }
            var input = field.querySelector('[data-image-input]');
            var file = field.querySelector('[data-image-file]');
            if (input) { input.value = ''; }
            if (file) { file.value = ''; }
            setPreview(field, '');
        });

        // Обложка (hero): выбор фото при типе фона «Без фона» раньше молча
        // терялся — снимок сохранялся, но не показывался. Переключаем список
        // сами, чтобы редактор видел, что фон стал фотографией.
        var syncHeroBg = function (target) {
            var bgSelect = document.querySelector('[data-hero-bg]');
            if (!bgSelect) { return; }
            if (target.matches('[name="youtube_url"]')
                && /(?:youtu\.be\/|youtube\.com\/(?:watch\?[^\s]*v=|embed\/|shorts\/))[A-Za-z0-9_-]{11}/i.test(target.value.trim())) {
                bgSelect.value = 'youtube';
                return;
            }
            if (target.matches('[name="video_url"]') && target.value.trim() !== '') {
                bgSelect.value = 'video';
                return;
            }
            if (bgSelect.value !== 'none') { return; }
            var field = target.closest('[data-image-field]');
            var input = field ? field.querySelector('[data-image-input]') : null;
            // Только поле фонового изображения обложки, не прочие картинки блока.
            if (!input || input.getAttribute('name') !== 'image') { return; }
            var hasImage = input.value.trim() !== ''
                || (field.querySelector('[data-image-file]') || {}).value;
            if (hasImage) { bgSelect.value = 'image'; }
        };
        document.addEventListener('input', function (e) {
            if (e.target.closest('[data-image-input]') || e.target.matches('[name="youtube_url"], [name="video_url"]')) {
                syncHeroBg(e.target);
            }
        });
        document.addEventListener('change', function (e) {
            if (e.target.closest('[data-image-input]') || e.target.closest('[data-image-file]')
                || e.target.matches('[name="youtube_url"], [name="video_url"]')) {
                syncHeroBg(e.target);
            }
        });
    })();

    // --- Умный интерактивный виджет фокальной точки (UI/UX Pro Max) ---
    (function initFocalPickers() {
        function updateFocal(picker) {
            var targetName = picker.getAttribute('data-image-input-name') || 'image_url';
            var imgInput = document.querySelector('[name="' + targetName + '"]') || document.querySelector('[data-image-input]');
            var imgEl = picker.querySelector('[data-focal-img]');
            var placeholder = picker.querySelector('[data-focal-placeholder]');
            var pin = picker.querySelector('[data-focal-pin]');
            var inputX = picker.querySelector('[data-focal-input-x]');
            var inputY = picker.querySelector('[data-focal-input-y]');

            var xVal = parseInt(inputX ? inputX.value : '', 10);
            var yVal = parseInt(inputY ? inputY.value : '', 10);
            var x = isNaN(xVal) ? 50 : Math.max(0, Math.min(100, xVal));
            var y = isNaN(yVal) ? 50 : Math.max(0, Math.min(100, yVal));

            if (pin) {
                pin.style.left = x + '%';
                pin.style.top = y + '%';
            }

            if (imgInput && imgEl) {
                var src = imgInput.value.trim();
                if (src) {
                    imgEl.src = src;
                    imgEl.style.display = 'block';
                    if (placeholder) { placeholder.style.display = 'none'; }
                } else {
                    imgEl.style.display = 'none';
                    if (placeholder) { placeholder.style.display = 'flex'; }
                }
            }

            picker.querySelectorAll('[data-focal-set-x]').forEach(function (btn) {
                var px = parseInt(btn.getAttribute('data-focal-set-x'), 10);
                var py = parseInt(btn.getAttribute('data-focal-set-y'), 10);
                btn.classList.toggle('is-active', px === x && py === y);
            });
        }

        document.querySelectorAll('[data-focal-picker]').forEach(updateFocal);

        document.addEventListener('click', function (e) {
            var canvas = e.target.closest('[data-focal-canvas]');
            if (canvas) {
                var picker = canvas.closest('[data-focal-picker]');
                if (!picker) return;
                var rect = canvas.getBoundingClientRect();
                var clickX = e.clientX - rect.left;
                var clickY = e.clientY - rect.top;
                var pctX = Math.round((clickX / rect.width) * 100);
                var pctY = Math.round((clickY / rect.height) * 100);
                pctX = Math.max(0, Math.min(100, pctX));
                pctY = Math.max(0, Math.min(100, pctY));

                var inputX = picker.querySelector('[data-focal-input-x]');
                var inputY = picker.querySelector('[data-focal-input-y]');
                if (inputX) inputX.value = pctX;
                if (inputY) inputY.value = pctY;
                updateFocal(picker);
                return;
            }

            var presetBtn = e.target.closest('[data-focal-set-x]');
            if (presetBtn) {
                var picker = presetBtn.closest('[data-focal-picker]');
                if (!picker) return;
                var px = presetBtn.getAttribute('data-focal-set-x');
                var py = presetBtn.getAttribute('data-focal-set-y');
                var inputX = picker.querySelector('[data-focal-input-x]');
                var inputY = picker.querySelector('[data-focal-input-y]');
                if (inputX) inputX.value = px;
                if (inputY) inputY.value = py;
                updateFocal(picker);
                return;
            }

            var resetBtn = e.target.closest('[data-focal-reset]');
            if (resetBtn) {
                var picker = resetBtn.closest('[data-focal-picker]');
                if (!picker) return;
                var inputX = picker.querySelector('[data-focal-input-x]');
                var inputY = picker.querySelector('[data-focal-input-y]');
                if (inputX) inputX.value = '';
                if (inputY) inputY.value = '';
                updateFocal(picker);
                return;
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target.matches('[data-focal-input-x], [data-focal-input-y]') || e.target.matches('[data-image-input]')) {
                document.querySelectorAll('[data-focal-picker]').forEach(updateFocal);
            }
        });
    })();

    // --- Автономный WYSIWYG (задача 75): инициализация на textarea[data-wysiwyg] ---
    if (window.ArtEditor) {
        document.querySelectorAll('textarea[data-wysiwyg]').forEach(function (ta) {
            window.ArtEditor.attach(ta);
        });
    }

    // --- Панель явного сохранения порядка ---
    // Раньше перетаскивание сохранялось мгновенно (AJAX на каждый drop). Теперь
    // изменения порядка копятся, а применяются только по кнопке «Сохранить» —
    // при уходе со страницы с несохранёнными правками браузер предупреждает.
    var ReorderBar = (function () {
        var bar = null, saveBtn = null, statusEl = null;
        var pendingSave = null, dirty = false, saving = false, hideTimer = null;

        function build() {
            bar = document.createElement('div');
            bar.className = 'reorder-bar';
            bar.setAttribute('hidden', '');
            bar.setAttribute('role', 'status');
            bar.setAttribute('aria-live', 'polite');
            bar.innerHTML = '<span class="reorder-bar__text"></span>'
                + '<button type="button" class="btn btn--small" data-reorder-cancel>Отменить</button>'
                + '<button type="button" class="btn btn--small btn--primary" data-reorder-save>Сохранить</button>';
            document.body.appendChild(bar);
            statusEl = bar.querySelector('.reorder-bar__text');
            saveBtn = bar.querySelector('[data-reorder-save]');
            saveBtn.addEventListener('click', function () {
                if (!pendingSave || saving) { return; }
                saving = true; saveBtn.disabled = true;
                statusEl.textContent = 'Сохранение…';
                pendingSave(function (ok, msg) {
                    saving = false; saveBtn.disabled = false;
                    if (ok) {
                        dirty = false;
                        statusEl.textContent = 'Порядок сохранён ✓';
                        hideTimer = window.setTimeout(function () { bar.setAttribute('hidden', ''); }, 1400);
                    } else {
                        statusEl.textContent = msg || 'Не удалось сохранить. Попробуйте ещё раз.';
                    }
                });
            });
            bar.querySelector('[data-reorder-cancel]').addEventListener('click', function () {
                // Отмена = вернуться к последнему сохранённому порядку (перезагрузка).
                dirty = false;
                window.location.reload();
            });
        }

        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; return ''; }
        });

        return {
            markDirty: function (saveFn) {
                if (!bar) { build(); }
                if (hideTimer) { window.clearTimeout(hideTimer); hideTimer = null; }
                pendingSave = saveFn;
                dirty = true;
                statusEl.textContent = 'Есть несохранённые изменения порядка';
                bar.removeAttribute('hidden');
            }
        };
    })();

    // --- Drag-and-drop сортировка блоков (задача 134, нативный HTML5 DnD) ---
    document.querySelectorAll('[data-block-sortable]').forEach(function (list) {
        var dragged = null;

        list.querySelectorAll('.block-list-item').forEach(function (item) {
            item.addEventListener('dragstart', function (e) {
                dragged = item;
                item.classList.add('is-dragging');
                try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', ''); } catch (err) {}
            });
            item.addEventListener('dragend', function () {
                item.classList.remove('is-dragging');
                ReorderBar.markDirty(saveOrder);
            });
        });

        list.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragged) { return; }
            var after = null;
            var items = Array.prototype.slice.call(list.querySelectorAll('.block-list-item:not(.is-dragging)'));
            for (var i = 0; i < items.length; i++) {
                var box = items[i].getBoundingClientRect();
                if (e.clientY < box.top + box.height / 2) { after = items[i]; break; }
            }
            if (after == null) { list.appendChild(dragged); }
            else { list.insertBefore(dragged, after); }
        });

        function saveOrder(done) {
            var order = Array.prototype.map.call(
                list.querySelectorAll('.block-list-item'),
                function (el) { return el.getAttribute('data-block-id'); }
            );
            var body = new URLSearchParams();
            body.append('csrf_token', list.getAttribute('data-csrf'));
            body.append('page_id', list.getAttribute('data-page-id'));
            body.append('block_lang', list.getAttribute('data-block-lang'));
            order.forEach(function (id) { body.append('order[]', id); });

            fetch('/admin/blocks/reorder', {
                method: 'POST', body: body, credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); })
              .then(function (res) { done(!!res.ok, res.ok ? '' : 'Не удалось сохранить порядок.'); })
              .catch(function () { done(false, 'Сетевая ошибка при сохранении порядка.'); });
        }
    });

    // --- Меню: drag-and-drop сортировка + вложенность (задача 3, группа 3) ---
    document.querySelectorAll('[data-menu-sortable]').forEach(function (root) {
        var dragged = null, startParent = null, startNext = null, moved = false;

        function isChildList(list) { return list.hasAttribute('data-menu-children'); }
        function draggedHasChildren() {
            var kids = dragged.querySelector('[data-menu-children]');
            return kids && kids.querySelector('.menu-node');
        }

        root.addEventListener('dragstart', function (e) {
            var handle = e.target.closest('.menu-node__handle');
            var node = handle ? handle.closest('.menu-node') : null;
            if (!node || node.getAttribute('data-menu-lang') !== root.getAttribute('data-menu-lang')) {
                e.preventDefault();
                return;
            }
            dragged = node;
            startParent = node.parentNode;
            startNext = node.nextElementSibling;
            moved = false;
            node.classList.add('is-dragging');
            try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', ''); } catch (err) {}
        });

        root.addEventListener('dragend', function () {
            if (dragged) { dragged.classList.remove('is-dragging'); }
            if (dragged && (moved || dragged.parentNode !== startParent || dragged.nextElementSibling !== startNext)) {
                ReorderBar.markDirty(saveOrder);
            }
            dragged = null;
            startParent = null;
            startNext = null;
            moved = false;
        });

        // Разрешаем вставку в root и в любой children-список.
        var lists = [root].concat(Array.prototype.slice.call(root.querySelectorAll('[data-menu-children]')));
        lists.forEach(function (list) {
            list.addEventListener('dragover', function (e) {
                if (!dragged) { return; }
                // Ограничение глубины 1: пункт со своими детьми нельзя вкладывать.
                if (isChildList(list) && draggedHasChildren()) { return; }
                if (isChildList(list) && dragged.classList.contains('menu-node--divider')) { return; }
                // Нельзя поместить пункт внутрь его собственной области детей.
                if (dragged.contains(list)) { return; }
                e.preventDefault();
                e.stopPropagation();
                var siblings = Array.prototype.slice.call(list.querySelectorAll(':scope > .menu-node:not(.is-dragging)'));
                var after = null;
                for (var i = 0; i < siblings.length; i++) {
                    var box = siblings[i].getBoundingClientRect();
                    if (e.clientY < box.top + box.height / 2) { after = siblings[i]; break; }
                }
                if (after == null) { list.appendChild(dragged); }
                else { list.insertBefore(dragged, after); }
                dragged.classList.toggle('menu-node--child', isChildList(list));
                moved = true;
            });
        });

        function saveOrder(done) {
            var ids = [];
            var parents = [];
            Array.prototype.forEach.call(root.querySelectorAll(':scope > .menu-node'), function (top) {
                ids.push(top.getAttribute('data-menu-id'));
                parents.push('');
                var childList = top.querySelector(':scope > [data-menu-children]');
                if (childList) {
                    Array.prototype.forEach.call(childList.querySelectorAll(':scope > .menu-node'), function (child) {
                        ids.push(child.getAttribute('data-menu-id'));
                        parents.push(top.getAttribute('data-menu-id'));
                    });
                }
            });

            var body = new URLSearchParams();
            body.append('csrf_token', root.getAttribute('data-csrf'));
            ids.forEach(function (id) { body.append('id[]', id); });
            parents.forEach(function (p) { body.append('parent_id[]', p); });

            fetch('/admin/menu/reorder', {
                method: 'POST', body: body, credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); })
              .then(function (res) { done(!!res.ok, res.ok ? '' : (res.error || 'Не удалось сохранить меню. Обновите страницу.')); })
              .catch(function () { done(false, 'Сетевая ошибка при сохранении меню.'); });
        }
    });

    // --- Меню: языковые вкладки, редактор и зависимые поля ---
    (function () {
        function syncMenuForm(form) {
            var type = form.querySelector('[data-menu-url-type]');
            var divider = form.querySelector('[data-menu-divider]');
            var lang = form.querySelector('[data-menu-lang-select]');
            var isDivider = !!(divider && divider.checked);
            form.querySelectorAll('[data-menu-link-only]').forEach(function (field) {
                field.hidden = isDivider;
            });
            form.querySelectorAll('[data-menu-url-field]').forEach(function (field) {
                field.hidden = isDivider || !type || field.getAttribute('data-menu-url-field') !== type.value;
            });
            form.querySelectorAll('[data-menu-parent-field]').forEach(function (field) {
                field.hidden = isDivider;
            });

            var parent = form.querySelector('[data-menu-parent-select]');
            if (parent && lang) {
                Array.prototype.forEach.call(parent.options, function (option, index) {
                    if (index === 0) { option.hidden = false; option.disabled = false; return; }
                    var matches = option.getAttribute('data-lang') === lang.value;
                    option.hidden = !matches;
                    option.disabled = !matches;
                    if (!matches && option.selected) { parent.value = ''; }
                });
            }

            var pageSelect = form.querySelector('[data-menu-page-select]');
            if (pageSelect && lang) {
                Array.prototype.forEach.call(pageSelect.options, function (option, index) {
                    if (index === 0) { option.hidden = false; option.disabled = false; return; }
                    var matches = option.getAttribute('data-lang') === lang.value;
                    option.hidden = !matches;
                    option.disabled = !matches;
                    if (!matches && option.selected) { pageSelect.value = ''; }
                });
            }
        }

        document.querySelectorAll('[data-menu-link-form]').forEach(syncMenuForm);

        document.addEventListener('change', function (e) {
            if (!e.target.matches('[data-menu-url-type], [data-menu-divider], [data-menu-lang-select]')) { return; }
            var form = e.target.closest('[data-menu-link-form]');
            if (form) syncMenuForm(form);
        });

        // Название пункта меню подставляется из заголовка выбранной страницы,
        // пока его не отредактировали вручную (флаг data-autofilled).
        document.addEventListener('change', function (e) {
            if (!e.target.matches('[data-menu-page-select]')) { return; }
            var form = e.target.closest('[data-menu-link-form]');
            if (!form) { return; }
            var titleInput = form.querySelector('input[name="title"]');
            if (!titleInput) { return; }
            var opt = e.target.options[e.target.selectedIndex];
            var pageTitle = opt ? (opt.getAttribute('data-title') || '') : '';
            if (pageTitle === '') { return; }
            if (titleInput.value.trim() === '' || titleInput.dataset.autofilled === '1') {
                titleInput.value = pageTitle;
                titleInput.dataset.autofilled = '1';
            }
        });
        document.addEventListener('input', function (e) {
            if (e.target.matches('[data-menu-link-form] input[name="title"]')) {
                delete e.target.dataset.autofilled;
            }
        });

        document.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-menu-edit-toggle]');
            if (toggle) {
                var panel = document.getElementById(toggle.getAttribute('aria-controls'));
                if (!panel) { return; }
                var opening = panel.hasAttribute('hidden');
                panel.toggleAttribute('hidden', !opening);
                toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
                if (opening) {
                    var first = panel.querySelector('input:not([type="hidden"]), select, textarea');
                    if (first) first.focus();
                }
                return;
            }
            var close = e.target.closest('[data-menu-edit-close]');
            if (close) {
                var editPanel = close.closest('[data-menu-edit-panel]');
                if (!editPanel) { return; }
                editPanel.setAttribute('hidden', '');
                var editToggle = document.querySelector('[aria-controls="' + editPanel.id + '"]');
                if (editToggle) { editToggle.setAttribute('aria-expanded', 'false'); editToggle.focus(); }
                return;
            }
            var tab = e.target.closest('[data-menu-lang-tab]');
            if (tab) {
                var code = tab.getAttribute('data-menu-lang-tab');
                try { localStorage.setItem('artstudio:admin-menu-lang', code); } catch (err) {}
                document.querySelectorAll('[data-menu-lang-tab]').forEach(function (button) {
                    var active = button === tab;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                document.querySelectorAll('[data-menu-lang-panel]').forEach(function (panel) {
                    panel.toggleAttribute('hidden', panel.getAttribute('data-menu-lang-panel') !== code);
                });
                var createLang = document.querySelector('#menu-add [data-menu-lang-select]');
                if (createLang) { createLang.value = code; syncMenuForm(createLang.closest('[data-menu-link-form]')); }
            }
        });

        try {
            var savedMenuLang = localStorage.getItem('artstudio:admin-menu-lang');
            if (savedMenuLang !== null) {
                var savedTab = Array.prototype.find.call(document.querySelectorAll('[data-menu-lang-tab]'), function (button) {
                    return button.getAttribute('data-menu-lang-tab') === savedMenuLang;
                });
                if (savedTab) savedTab.click();
            }
        } catch (e) {}
    })();

    // Языковые вкладки: переключение панелей внутри одной группы [data-lang-tabs]
    document.querySelectorAll('[data-lang-tabs]').forEach(function (group) {
        const buttons = group.querySelectorAll('.lang-tab-btn');
        const panels = group.querySelectorAll('.lang-tab-panel');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                const target = btn.getAttribute('data-lang-target');
                buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                panels.forEach(function (p) {
                    p.classList.toggle('is-active', p.getAttribute('data-lang-panel') === target);
                });

                if (group.hasAttribute('data-sync-block-language')) {
                    var search = window.location.search;
                    var hasBlockLang = search.indexOf('block_lang=') !== -1;
                    var param = 'block_lang=' + encodeURIComponent(target);
                    var newSearch = '';
                    if (hasBlockLang) {
                        newSearch = search.replace(/block_lang=[^&]*/g, param);
                    } else {
                        newSearch = search ? (search + '&' + param) : ('?' + param);
                    }
                    newSearch = newSearch.replace(/[&?]draft_saved=[^&]*/g, '');
                    newSearch = newSearch.replace(/&&+/g, '&').replace(/\?&/, '?').replace(/&$/, '');
                    if (search !== newSearch) {
                        window.location.assign(window.location.pathname + newSearch + window.location.hash);
                    }
                }
            });
        });
    });
})();

/* ==========================================================================
   Конструктор шапки: drag-and-drop микро-виджетов по зонам (палитра ↔ зоны).
   Палитра — источник доступных элементов. Неповторяемые (поиск, языки, тема,
   слабовидящие, соцсети, кнопка) размещаются по одному; «Разделитель» —
   повторяемый (клонируется из палитры). Порядок в зоне задаётся перетаскиванием.
   ========================================================================== */
(function () {
    'use strict';
    var REPEATABLE = ['divider', 'spacer', 'space'];
    var builders = document.querySelectorAll('[data-hdr-builder]');
    if (!builders.length) { return; }
    // Pro Max: палитра — общий источник (чипы клонируются), секции — приёмники.
    // Перетаскивание работает МЕЖДУ билдерами (глобальное состояние).
    var palette = document.querySelector('[data-hdr-zone="palette"]');
    var dragged = null;       // перетаскиваемый чип (клон или размещённый)
    var fromPalette = false;  // тянем из палитры (клонировать)

    function serializeAll() {
        builders.forEach(function (builder) {
            builder.querySelectorAll('[data-hdr-input]').forEach(function (input) {
                var dz = builder.querySelector('[data-hdr-zone="' + input.getAttribute('data-hdr-input') + '"]');
                if (!dz) { return; }
                var types = Array.prototype.map.call(dz.querySelectorAll('.hdr-chip'), function (c) {
                    return c.getAttribute('data-el');
                });
                input.value = types.join(',');
            });
        });
    }

    // Уникальность в пределах одной секции (билдера): повторяем только divider.
    function sectionHasType(builder, type) {
        return !!builder.querySelector('[data-hdr-zone]:not([data-hdr-zone="palette"]) .hdr-chip[data-el="' + type + '"]:not(.is-dragging)');
    }

    function makeChip(type) {
        var src = palette ? palette.querySelector('.hdr-chip[data-el="' + type + '"]') : null;
        if (!src) { return null; }
        var chip = src.cloneNode(true);
        chip.classList.add('hdr-chip--placed');
        bindChip(chip);
        return chip;
    }

    function bindChip(chip) {
        chip.addEventListener('dragstart', function (e) {
            fromPalette = !!chip.closest('[data-hdr-zone="palette"]');
            dragged = fromPalette ? makeChip(chip.getAttribute('data-el')) : chip;
            if (!fromPalette) {
                setTimeout(function () { chip.classList.add('is-dragging'); }, 0);
            }
            e.dataTransfer.effectAllowed = fromPalette ? 'copy' : 'move';
            try { e.dataTransfer.setData('text/plain', chip.getAttribute('data-el')); } catch (err) {}
        });
        chip.addEventListener('dragend', function () {
            chip.classList.remove('is-dragging');
            // Отменённое перетаскивание из палитры: убираем невставленный клон.
            if (fromPalette && dragged && !dragged.parentNode) { /* не вставлен */ }
            dragged = null;
            fromPalette = false;
            serializeAll();
        });
        var rm = chip.querySelector('.hdr-chip__remove, .hb-el__remove');
        if (rm) {
            rm.addEventListener('click', function () {
                if (chip.closest('[data-hdr-zone="palette"]')) { return; }
                chip.remove();
                serializeAll();
            });
        }
    }

    function afterElement(zone, x, y) {
        var chips = Array.prototype.slice.call(zone.querySelectorAll('.hdr-chip:not(.is-dragging)'));
        var closest = { offset: -Infinity, el: null };
        chips.forEach(function (c) {
            var box = c.getBoundingClientRect();
            // Горизонтальные зоны: сравниваем по X в пределах строки, иначе по Y.
            var offset = (Math.abs(y - (box.top + box.height / 2)) < box.height)
                ? x - box.left - box.width / 2
                : y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) { closest = { offset: offset, el: c }; }
        });
        return closest.el;
    }

    document.querySelectorAll('[data-hdr-zone]').forEach(function (zone) {
        var isPalette = zone.getAttribute('data-hdr-zone') === 'palette';
        zone.addEventListener('dragover', function (e) {
            if (!dragged) { return; }
            e.preventDefault();
            zone.classList.add('is-over');
            var type = dragged.getAttribute('data-el');

            if (isPalette) {
                // Бросок размещённого чипа в палитру = удаление из секции.
                if (!fromPalette && dragged.parentNode) { dragged.remove(); }
                return;
            }

            var builder = zone.closest('[data-hdr-builder]');
            // Не даём дублировать неповторяемый элемент в той же секции
            // (перенос внутри секции — можно; из палитры/другой секции — нет).
            var draggedBuilder = dragged.parentNode ? dragged.closest('[data-hdr-builder]') : null;
            if (REPEATABLE.indexOf(type) === -1 && draggedBuilder !== builder && sectionHasType(builder, type)) {
                return;
            }
            var after = afterElement(zone, e.clientX, e.clientY);
            if (after == null) { zone.appendChild(dragged); }
            else { zone.insertBefore(dragged, after); }
        });
        zone.addEventListener('dragleave', function () { zone.classList.remove('is-over'); });
        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('is-over');
            serializeAll();
        });
    });

    document.querySelectorAll('.hdr-chip').forEach(bindChip);
    serializeAll();
})();

/* Вкладки конструктора (Десктоп / Мобильный). */
(function () {
    'use strict';
    document.querySelectorAll('[data-hdr-tabs]').forEach(function (tabs) {
        var group = tabs.parentElement;
        tabs.querySelectorAll('[data-hdr-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                var name = tab.getAttribute('data-hdr-tab');
                tabs.querySelectorAll('[data-hdr-tab]').forEach(function (t) {
                    t.classList.toggle('is-active', t === tab);
                });
                group.querySelectorAll('[data-hdr-panel]').forEach(function (p) {
                    p.classList.toggle('is-active', p.getAttribute('data-hdr-panel') === name);
                });
            });
        });
    });
})();

/* Конструктор футера: перестановка колонок стрелками. */
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-fb-move]');
        if (!btn) { return; }
        var row = btn.closest('.repeater-row');
        if (!row) { return; }
        if (btn.getAttribute('data-fb-move') === 'up') {
            var prev = row.previousElementSibling;
            if (prev) { row.parentNode.insertBefore(row, prev); }
        } else {
            var next = row.nextElementSibling;
            if (next) { row.parentNode.insertBefore(next, row); }
        }
    });
})();

/* Делегированные обработчики вместо инлайн-атрибутов (CSP без 'unsafe-inline'). */
(function () {
    'use strict';
    // Селект с автоотправкой формы (фильтры списков новостей/страниц/проектов).
    document.addEventListener('change', function (e) {
        var el = e.target;
        if (el.matches && el.matches('select[data-auto-submit]') && el.form) {
            el.form.submit();
            return;
        }
        // Селект типа виджета показывает поля выбранного типа.
        if (el.matches && el.matches('select[data-widget-type-select]')) {
            document.querySelectorAll('[data-wtype]').forEach(function (block) {
                block.style.display = block.getAttribute('data-wtype') === el.value ? 'flex' : 'none';
            });
        }
    });
})();

/* Локальное автосохранение контентных форм. Не сохраняем CSRF, файлы и
   пароли; черновик остаётся только в браузере редактора. */
(function () {
    'use strict';

    var currentUrl = new URL(window.location.href);
    var savedDraft = currentUrl.searchParams.get('draft_saved');
    if (savedDraft) {
        try { localStorage.removeItem('artstudio:draft:' + savedDraft); } catch (e) {}
        currentUrl.searchParams.delete('draft_saved');
        window.history.replaceState({}, document.title, currentUrl.pathname + currentUrl.search + currentUrl.hash);
    }

    document.querySelectorAll('form[data-content-draft]').forEach(function (form) {
        var key = 'artstudio:draft:' + form.getAttribute('data-content-draft');
        var dirty = false;

        function fields() {
            var values = {};
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.name || el.disabled || el.type === 'file' || el.type === 'password'
                    || el.name === 'csrf_token' || el.name === 'expected_updated_at'
                    || el.name === 'expected_lock_version') { return; }
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) { return; }
                if (values[el.name] !== undefined) {
                    if (!Array.isArray(values[el.name])) { values[el.name] = [values[el.name]]; }
                    values[el.name].push(el.value);
                } else {
                    values[el.name] = el.value;
                }
            });
            return values;
        }

        function save() {
            if (!dirty) { return; }
            try {
                localStorage.setItem(key, JSON.stringify({ savedAt: Date.now(), values: fields() }));
            } catch (e) {}
        }

        function apply(values) {
            form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(function (el) {
                el.checked = false;
            });
            Object.keys(values || {}).forEach(function (name) {
                var controls = form.querySelectorAll('[name="' + CSS.escape(name) + '"]');
                var inputValues = Array.isArray(values[name]) ? values[name].map(String) : [String(values[name])];
                controls.forEach(function (el) {
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = inputValues.indexOf(el.value) !== -1;
                    } else {
                        el.value = inputValues[0] || '';
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                        el.dispatchEvent(new Event('arteditor:restore'));
                    }
                });
            });
            dirty = true;
        }

        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () {
            try { localStorage.removeItem(key); } catch (e) {}
            dirty = false;
        });
        window.setInterval(save, 20000);
        window.addEventListener('beforeunload', function (event) {
            if (dirty) {
                save();
                event.preventDefault();
                event.returnValue = '';
            }
        });

        // После успешного сохранения на сервере (параметр draft_saved в URL)
        // сбрасываем устаревший локальный черновик и не выводим предупреждение.
        if (window.location.search.indexOf('draft_saved=') !== -1) {
            try { localStorage.removeItem(key); } catch (e) {}
            return;
        }

        try {
            var draft = JSON.parse(localStorage.getItem(key) || 'null');
            if (!draft || !draft.savedAt || !draft.values) { return; }
            if (Date.now() - Number(draft.savedAt) > 7 * 24 * 60 * 60 * 1000) {
                localStorage.removeItem(key);
                return;
            }
            var banner = document.createElement('div');
            banner.className = 'alert alert--warning content-draft-banner';
            banner.innerHTML = '<span>Найден локальный черновик от ' + new Date(draft.savedAt).toLocaleString() + '.</span> '
                + '<button type="button" class="btn btn--small" data-draft-restore>Восстановить</button> '
                + '<button type="button" class="btn btn--small" data-draft-discard>Удалить</button>';
            form.parentNode.insertBefore(banner, form);
            banner.querySelector('[data-draft-restore]').addEventListener('click', function () {
                apply(draft.values);
                banner.remove();
            });
            banner.querySelector('[data-draft-discard]').addEventListener('click', function () {
                localStorage.removeItem(key);
                banner.remove();
            });
        } catch (e) {}
    });
})();

// --- Выбор картинки из медиабиблиотеки в строках повторителей ---
// Поля логотипов, фото и изображений внутри повторяющихся строк были обычными
// текстовыми input: путь приходилось вписывать руками. Кнопку добавляем
// автоматически всем таким полям — включая строки, добавленные уже после
// загрузки страницы (шаблон __INDEX__ клонируется скриптом повторителя).
(function () {
    var NAME_RE = /\[(image|logo|photo|cover|media)\]$/i;
    var seq = 0;

    function enhance(input) {
        if (!input || input.dataset.mediaEnhanced === '1') { return; }
        var name = input.getAttribute('name') || '';
        if (input.type !== 'text' || !NAME_RE.test(name)) { return; }
        // Поля, уже обёрнутые общим компонентом, трогать не нужно.
        if (input.closest('[data-image-field]') || input.hasAttribute('data-image-input')) { return; }
        input.dataset.mediaEnhanced = '1';

        if (!input.id) { input.id = 'mediafld_' + (++seq); }
        var pick = document.createElement('button');
        pick.type = 'button';
        pick.className = 'btn btn--small';
        pick.textContent = 'Медиабиблиотека';
        pick.setAttribute('data-media-pick', '');
        pick.setAttribute('data-media-target', '#' + input.id);

        var row = document.createElement('div');
        row.className = 'repeater-media';
        input.parentNode.insertBefore(row, input);
        row.appendChild(input);
        row.appendChild(pick);
    }

    function scan(root) {
        (root || document).querySelectorAll('input[type="text"]').forEach(enhance);
    }

    scan(document);
    // Новые строки повторителя появляются после клика «Добавить».
    if (window.MutationObserver) {
        new MutationObserver(function (records) {
            records.forEach(function (r) {
                Array.prototype.forEach.call(r.addedNodes, function (node) {
                    if (node.nodeType !== 1) { return; }
                    if (node.matches && node.matches('input[type="text"]')) { enhance(node); }
                    scan(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    // Интерактивный «прожектор» (Spotlight) для карточек, кнопок и полей ввода в админке
    document.addEventListener('mousemove', function (e) {
        var el = e.target.closest(
            '.stat-card, .design-card, .hb-behavior-card, .fb-card, .preset-card, .form-card, ' +
            '.design-preset, .revision-item, .audit-item, .block-list-item, .menu-node__inner, ' +
            '.btn, input[type="text"], input[type="email"], input[type="password"], input[type="search"], textarea, select'
        );
        if (!el) { return; }
        var rect = el.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        el.style.setProperty('--mouse-x', x + 'px');
        el.style.setProperty('--mouse-y', y + 'px');
    });

    // --- Автоматические уведомления и подсветка при пропуске обязательных полей ---
    (function () {
        function showNotificationToast(msg, type) {
            var existing = document.querySelector('.admin-toast-notification');
            if (existing) { existing.remove(); }

            var toast = document.createElement('div');
            toast.className = 'admin-toast-notification admin-toast--' + (type || 'warning');
            toast.innerHTML = '<div style="display:flex;align-items:center;gap:10px;">'
                + '<span style="font-size:18px;">' + (type === 'error' ? '🚫' : '⚠️') + '</span>'
                + '<span style="font-weight:600;font-size:14px;">' + msg + '</span>'
                + '</div>'
                + '<button type="button" style="background:none;border:none;color:currentColor;cursor:pointer;font-size:18px;margin-left:12px;" onclick="this.parentNode.remove()">✕</button>';
            document.body.appendChild(toast);
            requestAnimationFrame(function () { toast.classList.add('is-visible'); });
            setTimeout(function () {
                toast.classList.remove('is-visible');
                setTimeout(function () { toast.remove(); }, 300);
            }, 5000);
        }
        window.showAdminNotification = showNotificationToast;

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || form.tagName !== 'FORM') { return; }
            if (form.getAttribute('novalidate') !== null || form.dataset.skipValidation === '1') { return; }

            var emptyFields = [];
            var firstEmpty = null;

            var inputs = form.querySelectorAll('input[required], textarea[required], select[required], [data-required]');
            inputs.forEach(function (input) {
                var val = (input.value || '').trim();
                if (window.tinymce && input.id && window.tinymce.get(input.id)) {
                    val = window.tinymce.get(input.id).getContent({ format: 'text' }).trim();
                }

                if (val === '') {
                    emptyFields.push(input);
                    input.classList.add('is-invalid');
                    if (!firstEmpty) { firstEmpty = input; }

                    input.addEventListener('input', function onInp() {
                        if ((input.value || '').trim() !== '') {
                            input.classList.remove('is-invalid');
                            input.removeEventListener('input', onInp);
                        }
                    });
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (emptyFields.length > 0) {
                event.preventDefault();
                event.stopPropagation();

                var labelText = '';
                if (firstEmpty) {
                    var label = form.querySelector('label[for="' + firstEmpty.id + '"]') || (firstEmpty.closest('.form-field') ? firstEmpty.closest('.form-field').querySelector('label') : null);
                    if (label) { labelText = label.textContent.replace(/\*/g, '').trim(); }
                }

                var msg = 'Пожалуйста, заполните обязательное поле' + (labelText ? ': «' + labelText + '»' : '');
                showNotificationToast(msg, 'warning');

                if (firstEmpty) {
                    firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    try { firstEmpty.focus({ preventScroll: true }); } catch (e) {}
                }
            }
        });
    })();

    // --- Интерактивный живой предпросмотр SEO & Соцсетей ---
    (function () {
        document.addEventListener('click', function (e) {
            var tabBtn = e.target.closest('[data-seo-tab]');
            if (!tabBtn) return;
            var tabName = tabBtn.getAttribute('data-seo-tab');
            var container = tabBtn.closest('.seo-live-preview');
            if (!container) return;

            container.querySelectorAll('[data-seo-tab]').forEach(function (btn) {
                btn.classList.toggle('is-active', btn === tabBtn);
            });
            container.querySelectorAll('[data-seo-panel]').forEach(function (panel) {
                panel.style.display = panel.getAttribute('data-seo-panel') === tabName ? 'block' : 'none';
            });
        });

        function updateSeoLivePreview() {
            var liveBoxes = document.querySelectorAll('.seo-live-preview');
            if (!liveBoxes.length) return;

            var titleInput = document.querySelector('input[name="title"], input[name="meta_title"]');
            var descInput = document.querySelector('textarea[name="excerpt"], input[name="meta_description"], textarea[name="key_points"]');
            var imageInput = document.querySelector('input[name="image_url"], input[name="image"]');

            var titleVal = titleInput ? (titleInput.value || '').trim() : '';
            var descVal = descInput ? (descInput.value || '').trim() : '';
            var imgVal = imageInput ? (imageInput.value || '').trim() : '';

            var titleText = titleVal || 'Заголовок вашей новости или страницы';
            var descText = descVal || 'Краткое описание или лид новости отображается здесь в поисковой выдаче и мессенджерах...';

            liveBoxes.forEach(function (box) {
                var gTitle = box.querySelector('[data-seo-google-title]');
                var gDesc = box.querySelector('[data-seo-google-desc]');
                var sTitle = box.querySelector('[data-seo-social-title]');
                var sDesc = box.querySelector('[data-seo-social-desc]');
                var tCount = box.querySelector('[data-seo-title-count]');
                var dCount = box.querySelector('[data-seo-desc-count]');
                var sImg = box.querySelector('[data-seo-social-img]');
                var sNoImg = box.querySelector('[data-seo-social-noimg]');

                if (gTitle) gTitle.textContent = titleText;
                if (gDesc) gDesc.textContent = descText;
                if (sTitle) sTitle.textContent = titleText;
                if (sDesc) sDesc.textContent = descText;

                if (tCount) {
                    tCount.textContent = titleVal.length;
                    tCount.style.color = titleVal.length > 65 ? '#ef4444' : (titleVal.length >= 30 ? '#10b981' : '#64748b');
                }
                if (dCount) {
                    dCount.textContent = descVal.length;
                    dCount.style.color = descVal.length > 160 ? '#ef4444' : (descVal.length >= 70 ? '#10b981' : '#64748b');
                }

                if (sImg && sNoImg) {
                    if (imgVal !== '') {
                        sImg.src = imgVal;
                        sImg.style.display = 'block';
                        sNoImg.style.display = 'none';
                    } else {
                        sImg.style.display = 'none';
                        sNoImg.style.display = 'inline-block';
                    }
                }
            });
        }

        document.addEventListener('input', updateSeoLivePreview);
        document.addEventListener('change', updateSeoLivePreview);
        setTimeout(updateSeoLivePreview, 300);
    })();

    // --- Универсальная командная палитра (Ctrl + K / Cmd + K) ---
    (function () {
        var paletteHtml = '<div class="admin-cmd-palette-overlay" data-cmd-overlay style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:99999;align-items:flex-start;justify-content:center;padding-top:10vh;">'
            + '<div class="admin-cmd-palette-modal" style="width:100%;max-width:580px;background:#ffffff;border-radius:14px;box-shadow:0 20px 40px rgba(0,0,0,0.25);overflow:hidden;border:1px solid #cbd5e1;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">'
            + '<div style="display:flex;align-items:center;padding:14px 18px;border-bottom:1px solid #e2e8f0;gap:12px;background:#f8fafc;">'
            + '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>'
            + '<input type="text" data-cmd-input placeholder="Введите название раздела, страницы или действие..." style="width:100%;border:none;background:transparent;outline:none;font-size:1.05rem;font-weight:500;color:#0f172a;">'
            + '<kbd style="background:#e2e8f0;border-radius:4px;padding:2px 6px;font-size:0.75rem;color:#475569;font-weight:600;">ESC</kbd>'
            + '</div>'
            + '<div class="admin-cmd-results" data-cmd-results style="max-height:380px;overflow-y:auto;padding:8px 0;">'
            + '</div>'
            + '<div style="padding:10px 18px;background:#f1f5f9;border-top:1px solid #e2e8f0;font-size:0.78rem;color:#64748b;display:flex;gap:16px;">'
            + '<span>↑↓ Выбор</span><span>↵ Переход</span><span>ESC Закрыть</span>'
            + '</div>'
            + '</div>'
            + '</div>';

        document.body.insertAdjacentHTML('beforeend', paletteHtml);

        var overlay = document.querySelector('[data-cmd-overlay]');
        var input = document.querySelector('[data-cmd-input]');
        var resultsContainer = document.querySelector('[data-cmd-results]');
        var selectedIdx = 0;

        var commands = [
            { icon: '📝', title: 'Новости', desc: 'Управление публикациями и новостями', url: '/admin/news' },
            { icon: '➕', title: 'Добавить новую новость', desc: 'Создать статью или анонс', url: '/admin/news/create' },
            { icon: '📄', title: 'Страницы', desc: 'Структура и разделы сайта', url: '/admin/pages' },
            { icon: '➕', title: 'Добавить страницу', desc: 'Создать новую страницу', url: '/admin/pages/create' },
            { icon: '📁', title: 'Проекты', desc: 'Реестр проектов и направлений', url: '/admin/projects' },
            { icon: '🎨', title: 'Дизайн и Оформление', desc: 'Шрифты, цвета и пресеты', url: '/admin/design' },
            { icon: '📌', title: 'Конструктор меню', desc: 'Навигация в шапке и подвале', url: '/admin/menu' },
            { icon: '📂', title: 'Медиабиблиотека', desc: 'Загрузка изображений и документов', url: '/admin/files' },
            { icon: '📱', title: 'Telegram Автопостинг', desc: 'Настройка социальных сетей', url: '/admin/telegram' },
            { icon: '📋', title: 'Журнал действий', desc: 'Аудит системы и историй', url: '/admin/audit' },
            { icon: '🔐', title: 'Безопасность & 2FA', desc: 'Управление доступом и сессиями', url: '/admin/security' },
            { icon: '🌐', title: 'Перейти на сайт', desc: 'Открыть публичный сайт', url: '/' }
        ];

        function renderResults(filter) {
            filter = (filter || '').toLowerCase().trim();
            var matched = commands.filter(function (c) {
                return c.title.toLowerCase().indexOf(filter) !== -1 || c.desc.toLowerCase().indexOf(filter) !== -1;
            });

            if (!matched.length) {
                resultsContainer.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:0.9rem;">Ничего не найдено</div>';
                return;
            }

            var html = '';
            matched.forEach(function (c, idx) {
                var isSel = idx === selectedIdx;
                html += '<a href="' + c.url + '" class="admin-cmd-item" style="display:flex;align-items:center;gap:12px;padding:10px 18px;text-decoration:none;background:' + (isSel ? '#eff6ff' : 'transparent') + ';border-left:3px solid ' + (isSel ? '#2563eb' : 'transparent') + ';">'
                    + '<span style="font-size:1.2rem;">' + c.icon + '</span>'
                    + '<div>'
                    + '<div style="font-weight:600;font-size:0.95rem;color:#0f172a;">' + c.title + '</div>'
                    + '<div style="font-size:0.8rem;color:#64748b;">' + c.desc + '</div>'
                    + '</div>'
                    + '</a>';
            });
            resultsContainer.innerHTML = html;
        }

        function openPalette() {
            overlay.style.display = 'flex';
            input.value = '';
            selectedIdx = 0;
            renderResults('');
            setTimeout(function () { input.focus(); }, 50);
        }

        function closePalette() {
            overlay.style.display = 'none';
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (overlay.style.display === 'flex') { closePalette(); } else { openPalette(); }
            }
            if (e.key === 'Escape' && overlay.style.display === 'flex') {
                closePalette();
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-open-command-palette], [data-search], [data-search-input]')) {
                e.preventDefault();
                openPalette();
            }
            if (e.target === overlay) {
                closePalette();
            }
        });

        if (input) {
            input.addEventListener('input', function () {
                selectedIdx = 0;
                renderResults(input.value);
            });
        }
    })();

    // --- Глобальная обработка кнопки ИИ-Аннотации ---
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ai-generate-summary]');
        if (!btn) return;

        e.preventDefault();
        var form = btn.closest('form');
        if (!form) return;

        var titleInput = form.querySelector('[name="title"]');
        var title = titleInput ? titleInput.value : '';

        var content = '';
        if (window.tinymce) {
            if (tinymce.activeEditor) {
                content = tinymce.activeEditor.getContent();
            } else if (tinymce.get(0)) {
                content = tinymce.get(0).getContent();
            }
        }
        if (!content) {
            var contentField = form.querySelector('[name="content"]');
            if (contentField) { content = contentField.value; }
        }

        if (!title.trim() && !content.trim()) {
            alert('Пожалуйста, введите заголовок или текст новости перед генерацией ИИ-аннотации.');
            return;
        }

        var oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⌛ ИИ думает...';

        var body = new URLSearchParams();
        body.append('title', title);
        body.append('content', content);

        var csrfInput = form.querySelector('[name="csrf_token"]');
        if (csrfInput) {
            body.append('csrf_token', csrfInput.value);
        }

        fetch('/admin/news/ai-summary', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            if (data && data.ok) {
                if (data.excerpt) {
                    var excerptField = form.querySelector('[name="excerpt"]');
                    if (excerptField) { excerptField.value = data.excerpt; }
                }
                if (data.hashtags) {
                    var hashtagsField = form.querySelector('[name="hashtags"]');
                    if (hashtagsField) { hashtagsField.value = data.hashtags; }
                }
            } else if (data && data.error) {
                alert(data.error);
            }
        }).catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            alert('Ошибка при вызове ИИ: ' + (err.message || err));
        });
    });
})();

