Warning: truncated output (original token count: 42701)
Total output lines: 3482

(function () {
    'use strict';

    // Преобразует разрешённую разметку лида в текст без повторного разбора
    // строки как HTML. Это безопасный fallback до загрузки TinyMCE; после
    // загрузки текст берётся непосредственно из уже созданного DOM редактора.
    function decodeLeadEntities(value) {
        var named = {amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' '};
        return String(value || '').replace(/&(?:#([0-9]+)|#x([0-9a-f]+)|(amp|lt|gt|quot|apos|nbsp));/gi, function (match, decimal, hex, name) {
            if (name) { return named[name.toLowerCase()] || match; }
            var codePoint = parseInt(decimal || hex, decimal ? 10 : 16);
            if (!Number.isFinite(codePoint) || codePoint < 0 || codePoint > 0x10FFFF
                || (codePoint >= 0xD800 && codePoint <= 0xDFFF)) {
                return '\uFFFD';
            }
            return String.fromCodePoint(codePoint);
        });
    }

    // Один проход не позволяет фрагментам вложенных тегов сложиться в новый
    // тег после удаления (например, «<scr<x>ipt>»). Между тегами оставляем
    // пробел, чтобы соседние абзацы и пункты списка не склеивались.
    function stripLeadTags(value) {
        var text = String(value || '');
        var out = '';
        var tag = null;
        for (var i = 0; i < text.length; i++) {
            var character = text.charAt(i);
            if (tag === null) {
                if (character === '<') { tag = character; } else { out += character; }
                continue;
            }
            tag += character;
            if (character === '>') {
                out += ' ';
                tag = null;
            }
        }
        return tag === null ? out : out + tag;
    }

    function plainTextFromLeadMarkup(value) {
        var text = stripLeadTags(value);
        return decodeLeadEntities(text).replace(/\s+/g, ' ').trim();
    }

    function leadEditorFor(field) {
        return window.tinymce && field && field.id ? window.tinymce.get(field.id) : null;
    }

    function plainLeadText(field) {
        var editor = leadEditorFor(field);
        var body = editor && editor.getBody ? editor.getBody() : null;
        return body
            ? (body.textContent || '').replace(/\s+/g, ' ').trim()
            : plainTextFromLeadMarkup(field ? field.value : '');
    }

    // --- Единая система выбора цвета во всей админке. ---
    // В HTML остаётся нативный input[type=color] как рабочий fallback без JS.
    // До DOMContentLoaded он превращается в текстовое HEX-поле и подключается
    // к локально размещённому Coloris.
    (function () {
        var colorInputs = document.querySelectorAll('input[type="color"]');
        if (!colorInputs.length) { return; }

        colorInputs.forEach(function (input) {
            input.type = 'text';
            input.setAttribute('data-coloris', '');
            input.setAttribute('inputmode', 'text');
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('spellcheck', 'false');
            input.setAttribute('maxlength', '7');
            input.setAttribute('pattern', '#[0-9a-fA-F]{6}');
            input.setAttribute('placeholder', '#17375E');
        });

        if (window.Coloris) {
            window.Coloris({
                el: '[data-coloris]',
                theme: 'large',
                themeMode: document.documentElement.getAttribute('data-admin-theme') === 'dark_emerald' ? 'dark' : 'light',
                format: 'hex',
                formatToggle: false,
                alpha: false,
                focusInput: true,
                selectInput: true,
                closeButton: true,
                closeLabel: 'Готово',
                clearButton: false,
                swatches: [
                    '#17375e', '#214d84', '#5e7fa6', '#a8b7c9',
                    '#6cb9b1', '#a8dad4', '#ffffff', '#0b1a30', '#000000'
                ],
                a11y: {
                    open: 'Открыть выбор цвета',
                    close: 'Закрыть выбор цвета',
                    clear: 'Очистить цвет',
                    marker: 'Насыщенность: {s}. Яркость: {v}.',
                    hueSlider: 'Оттенок',
                    alphaSlider: 'Прозрачность',
                    input: 'Значение цвета',
                    format: 'Формат цвета',
                    swatch: 'Образец цвета',
                    instruction: 'Выбор насыщенности и яркости. Используйте клавиши со стрелками.'
                }
            });
        }

        document.querySelectorAll('.colorfield').forEach(function (group) {
            var off = group.querySelector('.colorfield__off input[type="checkbox"]');
            var color = group.querySelector('[data-coloris]');
            if (!off || !color) { return; }

            function syncDefaultState() {
                color.disabled = off.checked;
                group.classList.toggle('is-default', off.checked);
            }

            off.addEventListener('change', syncDefaultState);
            syncDefaultState();
        });
    })();

    // Универсальная функция копирования в буфер обмена (работает по HTTPS и HTTP с фаллбэком)
    function copyToClipboard(text, btnEl) {
        if (!text) { return Promise.reject(new Error('Empty text')); }

        function showSuccess() {
            if (btnEl) {
                // Прежнее содержимое сохраняем узлами: чтение innerHTML с
                // обратной записью заново разбирает разметку кнопки.
                const oldNodes = Array.prototype.slice.call(btnEl.childNodes);
                btnEl.textContent = 'Скопировано!';
                btnEl.classList.add('is-copy-success');
                setTimeout(function () {
                    btnEl.textContent = '';
                    oldNodes.forEach(function (node) { btnEl.appendChild(node); });
                    btnEl.classList.remove('is-copy-success');
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
            textarea.className = 'clipboard-fallback';
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

    document.querySelectorAll('[data-swatch-color]').forEach(function (element) {
        element.style.setProperty('--swatch-color', element.getAttribute('data-swatch-color'));
    });
    document.querySelectorAll('[data-font-family]').forEach(function (element) {
        element.style.setProperty('font-family', element.getAttribute('data-font-family'));
    });
    document.querySelectorAll('[data-font-size]').forEach(function (element) {
        element.style.setProperty('--preview-font-size', element.getAttribute('data-font-size'));
    });
    document.querySelectorAll('[data-progress-width]').forEach(function (element) {
        var value = Math.max(0, Math.min(100, Number(element.getAttribute('data-progress-width')) || 0));
        element.style.setProperty('--progress-width', value + '%');
    });

    // --- Раздел «Дизайн сайта»: доступные вкладки и честный live preview. ---
    (function initDesignBuilder() {
        var tabsRoot = document.querySelector('[data-design-tabs]');
        if (!tabsRoot) { return; }

        var tabs = Array.prototype.slice.call(tabsRoot.querySelectorAll('[role="tab"][data-tab-target]'));
        var panels = tabs.map(function (tab) {
            return document.getElementById(tab.getAttribute('data-tab-target'));
        }).filter(Boolean);
        var storageKey = 'artstudio:design-active-tab';

        function activateTab(tab, shouldFocus, shouldPersist) {
            if (!tab) { return; }
            var targetId = tab.getAttribute('data-tab-target');
            tabs.forEach(function (item) {
                var active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                item.setAttribute('tabindex', active ? '0' : '-1');
            });
            panels.forEach(function (panel) {
                var active = panel.id === targetId;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
            var saveActions = document.querySelector('[data-design-save-actions]');
            if (saveActions) {
                saveActions.hidden = targetId === 'tab-presets';
            }
            if (shouldPersist !== false) {
                try { localStorage.setItem(storageKey, targetId); } catch (err) {}
            }
            if (shouldFocus) { tab.focus(); }
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activateTab(tab, false, true); });
            tab.addEventListener('keydown', function (event) {
                var nextIndex = null;
                if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                    nextIndex = (index + 1) % tabs.length;
                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = tabs.length - 1;
                }
                if (nextIndex !== null) {
                    event.preventDefault();
                    activateTab(tabs[nextIndex], true, true);
                }
            });
        });

        var initialTab = tabs.find(function (tab) { return tab.classList.contains('is-active'); }) || tabs[0];
        try {
            var savedTarget = localStorage.getItem(storageKey);
            initialTab = tabs.find(function (tab) {
                return tab.getAttribute('data-tab-target') === savedTarget;
            }) || initialTab;
        } catch (err) {}
        activateTab(initialTab, false, false);

        var form = document.querySelector('[data-design-form]');
        var canvas = document.getElementById('liveDeckCanvas');
        if (!form || !canvas) { return; }

        function fieldValue(name, fallback) {
            var control = form.elements.namedItem(name);
            if (!control) { return fallback; }
            var value = typeof control.value === 'string' ? control.value : '';
            return value !== '' ? value : fallback;
        }

        function numericValue(name) {
            var raw = fieldValue(name, '');
            var value = parseFloat(String(raw).replace(',', '.'));
            return Number.isFinite(value) ? value : null;
        }

        function selectedFont(select, fallback) {
            if (!select || !select.options || select.selectedIndex < 0) { return fallback; }
            return select.options[select.selectedIndex].getAttribute('data-font-family') || fallback;
        }

        function rounded(value) {
            return Math.round(value * 10) / 10;
        }

        function setLive(name, value) {
            canvas.style.setProperty(name, value);
        }

        function updateCodePreview(values) {
            var output = document.querySelector('[data-design-code-preview]');
            if (!output) { return; }
            output.textContent = ':root {\n'
                + '    --color-primary: ' + values.primary + ';\n'
                + '    --color-accent: ' + values.accent + ';\n'
                + '    --bg-primary: ' + values.bgPrimary + ';\n'
                + '    --bg-surface: ' + values.bgSurface + ';\n'
                + '    --text-main: ' + values.textMain + ';\n'
                + '    --text-muted: ' + values.textMuted + ';\n'
                + '    --border-color: ' + values.borderColor + ';\n'
                + '    --space-small: ' + fieldValue('space_small', 'clamp(14px, 2.5vw, 24px)') + ';\n'
                + '    --space-premium: ' + fieldValue('space_premium', 'clamp(28px, 4vw, 56px)') + ';\n'
                + '    --space-max: ' + fieldValue('space_max', 'clamp(40px, 5vw, 76px)') + ';\n'
                + '    --font-family: ' + values.bodyFont + ';\n'
                + '    --font-heading: ' + values.headingFont + ';\n'
                + '}';
        }

        function updateLiveDeck() {
            var radiusMap = { none: 0, small: 8, medium: 14, large: 22 };
            var gapMap = { xs: 8, sm: 16, md: 24, lg: 32 };
            var densityMap = { compact: 16, standard: 24, spacious: 32 };
            var sizeMap = { sm: 15, md: 16, lg: 17, xl: 18 };
            var lineMap = { tight: 1.45, normal: 1.6, relaxed: 1.8 };
            var headingLineMap = { tight: 1.15, normal: 1.25, relaxed: 1.35 };
            var letterMap = { tight: '-0.03em', normal: '-0.02em', wide: '0em' };
            var shadowMap = {
                flat: 'none',
                soft: '0 1px 3px rgba(16,24,40,.06), 0 6px 18px rgba(16,24,40,.05)',
                elevated: '0 10px 30px rgba(16,24,40,.12)'
            };
            var ratioMap = { theme: 0, compact: 1.2, classic: 1.25, expressive: 1.333 };

            var primary = fieldValue('color_primary', '#173a63');
            var accent = fieldValue('color_accent', '#17999b');
            var bgPrimary = fieldValue('bg_primary', '#f6f8fa');
            var bgSurface = fieldValue('bg_surface', '#ffffff');
            var textMain = fieldValue('text_main', '#1a1a1a');
            var textMuted = fieldValue('text_muted', '#666666');
            var borderColor = fieldValue('border_color', '#e1e3e8');

            var customRadius = numericValue('radius_custom');
            var radius = customRadius !== null ? customRadius : (radiusMap[fieldValue('radius', 'medium')] || 0);
            var baseSize = numericValue('font_size_custom');
            if (baseSize === null) { baseSize = sizeMap[fieldValue('font_size', 'md')] || 16; }
            var lineHeight = numericValue('line_height_custom');
            if (lineHeight === null) { lineHeight = lineMap[fieldValue('line_height', 'normal')] || 1.6; }
            var headingLine = numericValue('heading_line_height_custom');
            if (headingLine === null) { headingLine = headingLineMap[fieldValue('heading_line_height', 'normal')] || 1.25; }

            var bodySelect = form.querySelector('[data-font-body-choice]');
            var bodyFont = selectedFont(bodySelect, 'system-ui, sans-serif');
            if (bodySelect && bodySelect.value === 'style:custom') {
                bodyFont = fieldValue('font_family', bodyFont);
            }
            var headingSelect = form.elements.namedItem('font_google_heading');
            var headingFont = headingSelect && headingSelect.value !== ''
                ? selectedFont(headingSelect, bodyFont)
                : bodyFont;

            var defaults = { fs_h1: 32, fs_h2: 24, fs_h3: 18, fs_btn: 15 };
            var scale = fieldValue('typo_scale', 'theme');
            var ratio = ratioMap[scale] || 0;
            var steps = { fs_h1: 5, fs_h2: 4, fs_h3: 3 };
            Object.keys(steps).forEach(function (key) {
                var manual = numericValue(key);
                defaults[key] = manual !== null
                    ? manual
                    : (ratio > 1 ? rounded(baseSize * Math.pow(ratio, steps[key])) : defaults[key]);
            });
            var manualButton = numericValue('fs_btn');
            if (manualButton !== null) { defaults.fs_btn = manualButton; }

            var buttonStyle = fieldValue('button', 'rounded');
            var buttonRadius = buttonStyle === 'pill' ? 999 : (buttonStyle === 'square' ? 0 : radius);

            setLive('--live-color-primary', primary);
            setLive('--live-color-accent', accent);
            setLive('--live-bg-primary', bgPrimary);
            setLive('--live-bg-surface', bgSurface);
            setLive('--live-text-main', textMain);
            setLive('--live-text-muted', textMuted);
            setLive('--live-border-color', borderColor);
            setLive('--live-radius', radius + 'px');
            setLive('--live-btn-radius', buttonRadius + 'px');
            setLive('--live-card-shadow', shadowMap[fieldValue('card_style', 'soft')] || shadowMap.soft);
            setLive('--live-card-gap', (gapMap[fieldValue('card_gap', 'md')] || 24) + 'px');
            setLive('--live-section-pad', (densityMap[fieldValue('density', 'standard')] || 24) + 'px');
            setLive('--live-font-size', baseSize + 'px');
            setLive('--live-line-height', String(lineHeight));
            setLive('--live-heading-line-height', String(headingLine));
            setLive('--live-heading-font-weight', fieldValue('heading_font_weight', '700'));
            setLive('--live-heading-letter-spacing', letterMap[fieldValue('heading_letter_spacing', 'normal')] || '-0.02em');
            setLive('--live-font-body', bodyFont);
            setLive('--live-font-heading', headingFont);
            setLive('--live-fs-h1', defaults.fs_h1 + 'px');
            setLive('--live-fs-h2', defaults.fs_h2 + 'px');
            setLive('--live-fs-h3', defaults.fs_h3 + 'px');
            setLive('--live-fs-btn', defaults.fs_btn + 'px');

            updateCodePreview({
                primary: primary,
                accent: accent,
                bgPrimary: bgPrimary,
                bgSurface: bgSurface,
                textMain: textMain,
                textMuted: textMuted,
                borderColor: borderColor,
                bodyFont: bodyFont,
                headingFont: headingFont
            });
        }

        var fontChoice = form.querySelector('[data-font-body-choice]');
        var customFontFields = form.querySelector('[data-custom-font-fields]');
        function syncCustomFontFields() {
            if (fontChoice && customFontFields) {
                customFontFields.hidden = fontChoice.value !== 'style:custom';
            }
        }

        form.querySelectorAll('[data-design-preview-field]').forEach(function (element) {
            element.addEventListener('input', updateLiveDeck);
            element.addEventListener('change', function () {
                syncCustomFontFields();
                updateLiveDeck();
            });
        });
        syncCustomFontFields();
        updateLiveDeck();

        var frame = document.querySelector('[data-design-page-preview]');
        var widthButtons = document.querySelectorAll('[data-design-preview-width]');
        widthButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!frame) { return; }
                frame.style.width = button.getAttribute('data-design-preview-width') || '100%';
                widthButtons.forEach(function (item) {
                    var active = item === button;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
        });

        var refreshButton = document.querySelector('[data-design-preview-refresh]');
        if (refreshButton && frame) {
            refreshButton.addEventListener('click', function () {
                var params = new URLSearchParams(new FormData(form));
                params.delete('csrf_token');
                var base = frame.getAttribute('data-preview-base') || '/admin/design/preview';
                var url = base + '?' + params.toString();
                frame.src = url;
                var external = document.querySelector('.design-preview__bar a[target="_blank"]');
                if (external) { external.href = url; }
            });
        }
    })();

    // --- Telegram: toolbar, безопасное удаление токена и быстрые действия. ---
    (function initTelegramAdmin() {
        var signature = document.querySelector('[data-tg-signature]');
        var signatureCount = document.querySelector('[data-tg-signature-count]');

        // Считаем длину так, как её увидит Telegram: без тегов и с раскрытыми
        // сущностями. Разбирать ввод как разметку (innerHTML) нельзя, а
        // вырезать теги одним replace недостаточно: после удаления пары
        // «<…>» соседние куски складываются в новый тег («<scr<x>ipt>»).
        // Поэтому идём по строке ровно один раз.
        function stripTags(text) {
            var out = '';
            var tag = null;
            for (var i = 0; i < text.length; i++) {
                var ch = text.charAt(i);
                if (tag === null) {
                    if (ch === '<') { tag = ch; } else { out += ch; }
                    continue;
                }
                tag += ch;
                if (ch === '>') { tag = null; }
            }
            // Незакрытый «<» тегом не является — это обычный текст.
            return tag === null ? out : out + tag;
        }

        var NAMED_ENTITIES = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: '\u00a0' };

        // Один проход: раскрытая сущность повторно не разбирается, поэтому
        // «&amp;lt;» остаётся текстом «&lt;», как и в самом Telegram.
        function decodeEntities(text) {
            return text.replace(/&(#\d{1,7}|#x[0-9a-f]{1,6}|[a-z]+);/gi, function (match, body) {
                if (body.charAt(0) === '#') {
                    var hex = body.charAt(1) === 'x' || body.charAt(1) === 'X';
                    var code = hex ? parseInt(body.slice(2), 16) : parseInt(body.slice(1), 10);
                    return code > 0 && code <= 0x10FFFF ? String.fromCodePoint(code) : match;
                }
                var name = body.toLowerCase();
                return Object.prototype.hasOwnProperty.call(NAMED_ENTITIES, name) ? NAMED_ENTITIES[name] : match;
            });
        }

        function updateSignatureCount() {
            if (!signature || !signatureCount) { return; }
            var length = decodeEntities(stripTags(signature.value)).length;
            signatureCount.textContent = length + ' / 500';
            signatureCount.classList.toggle('is-over-limit', length > 500);
        }

        if (signature) {
            signature.addEventListener('input', updateSignatureCount);
            updateSignatureCount();
        }

        var clearToken = document.querySelector('[data-tg-clear-token]');
        var clearConfirm = document.querySelector('[data-tg-clear-confirm]');
        function syncClearToken() {
            if (!clearToken || !clearConfirm) { return; }
            clearConfirm.hidden = !clearToken.checked;
            var input = clearConfirm.querySelector('input');
            if (input) {
                input.required = clearToken.checked;
                if (!clearToken.checked) { input.value = ''; }
            }
        }
        if (clearToken) {
            clearToken.addEventListener('change', syncClearToken);
            syncClearToken();
        }

        document.addEventListener('click', function (event) {
            var tagButton = event.target.closest('[data-tg-tag-start]');
            if (tagButton && signature) {
                event.preventDefault();
                var startTag = tagButton.getAttribute('data-tg-tag-start') || '';
                var endTag = tagButton.getAttribute('data-tg-tag-end') || '';
                var start = signature.selectionStart;
                var end = signature.selectionEnd;
                var selected = signature.value.substring(start, end);
                signature.setRangeText(startTag + selected + endTag, start, end, 'end');
                signature.focus();
                signature.setSelectionRange(start + startTag.length, start + startTag.length + selected.length);
                updateSignatureCount();
                return;
            }

            var copyButton = event.target.closest('[data-tg-copy-code]');
            if (copyButton) {
                event.preventDefault();
                var code = document.getElementById('tg_link_code_val');
                if (code) { copyToClipboard(code.textContent.trim(), copyButton); }
                return;
            }

            var addButton = event.target.closest('[data-tg-add-chat-id]');
            if (addButton) {
                event.preventDefault();
                var input = document.getElementById('telegram_notify_chat_ids');
                var chatId = addButton.getAttribute('data-tg-add-chat-id') || '';
                if (!input || chatId === '') { return; }
                var values = input.value.split(/[\s,;]+/).filter(Boolean);
                if (values.indexOf(chatId) === -1) { values.push(chatId); }
                input.value = values.join(', ');
                input.dispatchEvent(new Event('input', { bubbles: true }));
                return;
            }

            var channelButton = event.target.closest('[data-tg-use-channel-id]');
            if (channelButton) {
                event.preventDefault();
                var channelInput = document.getElementById('tg_chat_id');
                var channelId = channelButton.getAttribute('data-tg-use-channel-id') || '';
                if (!channelInput || channelId === '') { return; }
                channelInput.value = channelId;
                channelInput.dispatchEvent(new Event('input', { bubbles: true }));
                channelInput.dispatchEvent(new Event('change', { bubbles: true }));
                channelInput.focus();
                document.querySelectorAll('[data-tg-use-channel-id]').forEach(function (button) {
                    button.classList.toggle('is-active', button === channelButton);
                    button.setAttribute('aria-pressed', button === channelButton ? 'true' : 'false');
                });
            }
        });
    })();

    /**
     * Номер для новой строки репитера: на единицу больше самого большого
     * индекса в именах полей. Считать по числу строк нельзя — после удаления
     * строки из середины номер повторится, и в POST останется только
     * последняя из двух одноимённых строк (введённое молча пропадает).
     */
    function nextRepeaterIndex(container, template) {
        // Образец имени берём из шаблона строки: у разных повторителей
        // индекс стоит по-разному («columns[0][heading]», «custom_fields[cf_0][key]»).
        var probe = template.content.querySelector('[name*="__INDEX__"]');
        if (!probe) {
            return container.children.length;
        }

        var escaped = probe.getAttribute('name').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var pattern = new RegExp('^' + escaped.replace('__INDEX__', '(\\d+)') + '$');
        var max = -1;
        Array.prototype.forEach.call(container.querySelectorAll('[name]'), function (field) {
            var match = pattern.exec(field.getAttribute('name') || '');
            if (match) {
                max = Math.max(max, Number(match[1]));
            }
        });

        return max + 1;
    }

    /**
     * Разворачивает <template> репитера: клон содержимого с заменой
     * плейсхолдера __INDEX__ в атрибутах и текстовых узлах.
     */
    function instantiateRepeaterTemplate(template, index) {
        const fragment = template.content.cloneNode(true);
        const walker = document.createTreeWalker(fragment, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
        const marker = /__INDEX__/g;
        let node = walker.nextNode();

        while (node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                Array.prototype.forEach.call(node.attributes, function (attr) {
                    if (attr.value.indexOf('__INDEX__') !== -1) {
                        attr.value = attr.value.replace(marker, String(index));
                    }
                });
            } else if (node.nodeValue && node.nodeValue.indexOf('__INDEX__') !== -1) {
                node.nodeValue = node.nodeValue.replace(marker, String(index));
            }
            node = walker.nextNode();
        }

        return fragment;
    }

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
            const maxRows = Number(container.getAttribute('data-repeater-max'));
            if (Number.isFinite(maxRows) && maxRows > 0 && container.children.length >= maxRows) {
                adminAlert('Максимум строк: ' + maxRows + '.');
                return;
            }
            const hasStoredIndex = container.hasAttribute('data-repeater-next-index');
            const storedIndex = Number(container.getAttribute('data-repeater-next-index'));
            const index = hasStoredIndex && Number.isFinite(storedIndex) && storedIndex >= 0
                ? storedIndex
                : nextRepeaterIndex(container, template);
            if (hasStoredIndex) {
                container.setAttribute('data-repeater-next-index', String(index + 1));
            }
            const wrapper = document.createElement('div');
            wrapper.className = 'repeater-row';
            // Клонируем содержимое <template> и подставляем номер строки в
            // узлах: чтение innerHTML с обратной записью — это повторный
            // разбор разметки, на котором данные становятся HTML.
            wrapper.appendChild(instantiateRepeaterTemplate(template, index));
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
            document.body.classList.add('has-modal-open');
            requestAnimationFrame(function () { overlay.classList.add('is-open'); });

            var okBtn = overlay.querySelector('.admin-modal__ok');
            var cancelBtn = overlay.querySelector('.admin-modal__cancel');
            okBtn.focus();

            function close(result) {
                overlay.classList.remove('is-open');
                document.removeEventListener('keydown', onKey);
                document.body.classList.remove('has-modal-open');
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

    function adminAlert(message) {
        return new Promise(function (resolve) {
            var overlay = document.createElement('div');
            overlay.className = 'admin-modal-overlay';
            overlay.innerHTML =
                '<div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="admin-modal-msg">'
                + '<div class="admin-modal__body">'
                + '<div class="admin-modal__icon" aria-hidden="true">ℹ</div>'
                + '<p class="admin-modal__msg" id="admin-modal-msg"></p>'
                + '</div>'
                + '<div class="admin-modal__actions">'
                + '<button type="button" class="btn btn--primary admin-modal__ok">ОК</button>'
                + '</div>'
                + '</div>';
            overlay.querySelector('.admin-modal__msg').textContent = message;
            document.body.appendChild(overlay);
            document.body.classList.add('has-modal-open');
            requestAnimationFrame(function () { overlay.classList.add('is-open'); });

            var okBtn = overlay.querySelector('.admin-modal__ok');
            okBtn.focus();

            function close() {
                overlay.classList.remove('is-open');
                document.removeEventListener('keydown', onKey);
                document.body.classList.remove('has-modal-open');
                setTimeout(function () { overlay.remove(); }, 150);
                resolve();
            }
            function onKey(e) {
                if (e.key === 'Escape' || e.key === 'Enter') { close(); }
            }
            okBtn.addEventListener('click', close);
            overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });
            document.addEventListener('keydown', onKey);
        });
    }
    window.adminAlert = adminAlert;

    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        if (element.tagName === 'FORM') {
            element.addEventListener('submit', function (event) {
                if (element.dataset.confirmed === '1') {
                    element.dataset.confirmed = '0';
                    return;
                }
                event.preventDefault();
                adminConfirm(element.getAttribute('data-confirm')).then(function (ok) {
                    if (!ok) { return; }
                    element.dataset.confirmed = '1';
                    if (typeof element.requestSubmit === 'function') { element.requestSubmit(); }
                    else { element.submit(); }
                });
            });
        } else {
            element.addEventListener('click', function (event) {
                if (element.dataset.confirmed === '1') {
                    element.dataset.confirmed = '0';
                    return;
                }
                event.preventDefault();
                adminConfirm(element.getAttribute('data-confirm')).then(function (ok) {
                    if (!ok) { return; }
                    element.dataset.confirmed = '1';
                    if (element.tagName === 'A') {
                        window.location.href = element.href;
                    } else if (element.type === 'submit' && element.form) {
                        if (typeof element.form.requestSubmit === 'function') {
                            element.form.requestSubmit(element);
                        } else {
                            element.form.submit();
                        }
                    } else {
                        element.click();
                    }
                });
            });
        }
    });

    // Применение шаблона страницы: режим «заменить» требует подтверждения.
    document.querySelectorAll('[data-snippet-insert]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var mode = form.querySelector('select[name=mode]');
            if (mode && mode.value === 'replace') {
                if (form.dataset.confirmed === '1') {
                    form.dataset.confirmed = '0';
                    return;
                }
                event.preventDefault();
                adminConfirm('Заменить все текущие бл…22701 tokens truncated… visibleSelectedZone();
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
                    var active = t === tab;
                    t.classList.toggle('is-active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                group.querySelectorAll('[data-hdr-panel]').forEach(function (p) {
                    var active = p.getAttribute('data-hdr-panel') === name;
                    p.classList.toggle('is-active', active);
                    p.hidden = !active;
                });
            });
        });
    });
})();

/* Перестановка строк повторителей стрелками (футер и слоты виджетов). */
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-fb-move], [data-repeater-move]');
        if (!btn) { return; }
        e.preventDefault();
        var row = btn.closest('.repeater-row');
        if (!row) { return; }
        var direction = btn.getAttribute('data-repeater-move') || btn.getAttribute('data-fb-move');
        if (direction === 'up') {
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
                block.classList.toggle('is-hidden', block.getAttribute('data-wtype') !== el.value);
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
            var statusIcon = window.asdrTablerIcon
                ? window.asdrTablerIcon(type === 'error' ? 'circle-x' : 'alert-triangle', 16)
                : '';
            var closeIcon = window.asdrTablerIcon ? window.asdrTablerIcon('x', 16) : '';
            toast.innerHTML = '<div class="u-inline-7e30d285d2">'
                + '<span class="u-inline-4f1925a8a6">' + statusIcon + '</span>'
                + '<span class="u-inline-94c3db5540">' + msg + '</span>'
                + '</div>'
                + '<button class="u-inline-d8c73d8aa0" type="button" onclick="this.parentNode.remove()">' + closeIcon + '</button>';
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
                panel.hidden = panel.getAttribute('data-seo-panel') !== tabName;
            });
        });

        function updateSeoLivePreview() {
            var liveBoxes = document.querySelectorAll('.seo-live-preview');
            if (!liveBoxes.length) return;

            var titleInput = document.querySelector('input[name="title"], input[name="meta_title"]');
            var descInput = document.querySelector('input[name="meta_description"], textarea[name="meta_description"], textarea[name="lead_html"], textarea[name="key_points"]');
            var imageInput = document.querySelector('input[name="image_url"], input[name="image"]');

            var titleVal = titleInput ? (titleInput.value || '').trim() : '';
            var descVal = descInput ? (descInput.value || '').trim() : '';
            if (descInput && descInput.getAttribute('name') === 'lead_html') {
                descVal = plainLeadText(descInput);
            }
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
                    tCount.classList.toggle('is-invalid', titleVal.length > 65);
                    tCount.classList.toggle('is-valid', titleVal.length >= 30 && titleVal.length <= 65);
                }
                if (dCount) {
                    dCount.textContent = descVal.length;
                    dCount.classList.toggle('is-invalid', descVal.length > 160);
                    dCount.classList.toggle('is-valid', descVal.length >= 70 && descVal.length <= 160);
                }

                if (sImg && sNoImg) {
                    if (imgVal !== '') {
                        sImg.src = imgVal;
                        sImg.classList.remove('is-hidden');
                        sNoImg.classList.add('is-hidden');
                    } else {
                        sImg.classList.add('is-hidden');
                        sNoImg.classList.remove('is-hidden');
                    }
                }
            });
        }

        document.addEventListener('input', updateSeoLivePreview);
        document.addEventListener('change', updateSeoLivePreview);
        setTimeout(updateSeoLivePreview, 300);
    })();

    // --- Универсальная командная палитра (Ctrl + K / Cmd + K) ---
    // --- Универсальная командная палитра (Ctrl + K / Cmd + K) ---
    (function () {
        var paletteHtml = '<div class="admin-cmd-palette-overlay u-inline-58d7c6be2b" data-cmd-overlay>'
            + '<div class="admin-cmd-palette-modal u-inline-27aa68da7d" role="dialog" aria-modal="true" aria-label="Командная палитра">'
            + '<div class="u-inline-907d56949b">'
            + (window.asdrTablerIcon ? window.asdrTablerIcon('search', 18) : '')
            + '<input class="u-inline-7180243890" type="text" data-cmd-input placeholder="Введите название раздела, страницы или действие..." aria-label="Поиск по разделу или действию">'
            + '<kbd class="u-inline-8d83117354">ESC</kbd>'
            + '</div>'
            + '<div class="admin-cmd-results u-inline-afdf6b2045" data-cmd-results>'
            + '</div>'
            + '<div class="u-inline-a650a7522f">'
            + '<span>↑↓ Выбор</span><span>↵ Переход</span><span>ESC Закрыть</span>'
            + '</div>'
            + '</div>'
            + '</div>';

        document.body.insertAdjacentHTML('beforeend', paletteHtml);

        var overlay = document.querySelector('[data-cmd-overlay]');
        var input = document.querySelector('[data-cmd-input]');
        var resultsContainer = document.querySelector('[data-cmd-results]');
        var selectedIdx = 0;
        var matchedItems = [];
        var lastActiveElement = null;
        var searchTimer = null;

        var allCommands = [
            { icon: 'news', title: 'Новости', desc: 'Управление публикациями и новостями', url: '/admin/news' },
            { icon: 'plus', title: 'Добавить новую новость', desc: 'Создать статью или анонс', url: '/admin/news/create' },
            { icon: 'files', title: 'Страницы', desc: 'Структура и разделы сайта', url: '/admin/pages' },
            { icon: 'plus', title: 'Добавить страницу', desc: 'Создать новую страницу', url: '/admin/pages/create' },
            { icon: 'briefcase', title: 'Проекты', desc: 'Реестр проектов и направлений', url: '/admin/projects' },
            { icon: 'palette', title: 'Дизайн и Оформление', desc: 'Шрифты, цвета и пресеты', url: '/admin/design' },
            { icon: 'menu-2', title: 'Конструктор меню', desc: 'Навигация в шапке и подвале', url: '/admin/menu' },
            { icon: 'photo', title: 'Медиабиблиотека', desc: 'Загрузка изображений и документов', url: '/admin/files' },
            { icon: 'brand-telegram', title: 'Telegram Автопостинг', desc: 'Настройка социальных сетей', url: '/admin/telegram' },
            { icon: 'clipboard-list', title: 'Журнал действий', desc: 'Аудит системы и историй', url: '/admin/audit' },
            { icon: 'shield-lock', title: 'Безопасность & 2FA', desc: 'Управление доступом и сессиями', url: '/admin/security' },
            { icon: 'world', title: 'Перейти на сайт', desc: 'Открыть публичный сайт', url: '/' }
        ];

        function isUrlAccessible(url) {
            if (url === '/' || url === '/admin' || url === '/admin/profile') { return true; }
            var sidebarLinks = document.querySelectorAll('.admin-nav-item[href], .admin-sidebar a[href]');
            if (!sidebarLinks.length) { return true; }
            for (var i = 0; i < sidebarLinks.length; i++) {
                var href = sidebarLinks[i].getAttribute('href');
                if (href && (href === url || url.indexOf(href) === 0)) {
                    return true;
                }
            }
            return false;
        }

        function getAccessibleCommands() {
            return allCommands.filter(function (c) {
                return isUrlAccessible(c.url);
            });
        }

        function updateSelectionVisual() {
            var links = resultsContainer.querySelectorAll('.admin-cmd-item');
            for (var i = 0; i < links.length; i++) {
                if (i === selectedIdx) {
                    links[i].classList.add('is-selected');
                    links[i].setAttribute('aria-selected', 'true');
                    links[i].scrollIntoView({ block: 'nearest' });
                } else {
                    links[i].classList.remove('is-selected');
                    links[i].removeAttribute('aria-selected');
                }
            }
        }

        function renderResults(filter, serverResults) {
            filter = (filter || '').toLowerCase().trim();
            var availableCmds = getAccessibleCommands();
            var matchedCmds = availableCmds.filter(function (c) {
                return !filter || c.title.toLowerCase().indexOf(filter) !== -1 || c.desc.toLowerCase().indexOf(filter) !== -1;
            });

            matchedItems = matchedCmds.map(function (c) {
                return { icon: c.icon, title: c.title, desc: c.desc, url: c.url, type: 'Раздел' };
            });

            if (serverResults && serverResults.length) {
                serverResults.forEach(function (r) {
                    matchedItems.push({
                        icon: 'search',
                        title: r.title,
                        desc: r.type || 'Объект',
                        url: r.url,
                        type: r.type || 'Объект'
                    });
                });
            }

            if (!matchedItems.length) {
                resultsContainer.innerHTML = '<div class="u-inline-f16ce3d7a8">Ничего не найдено</div>';
                return;
            }

            if (selectedIdx >= matchedItems.length) {
                selectedIdx = 0;
            }

            var html = '';
            matchedItems.forEach(function (c, idx) {
                var isSel = idx === selectedIdx;
                html += '<a href="' + c.url + '" class="admin-cmd-item u-inline-9e9c073f14' + (isSel ? ' is-selected' : '') + '" data-cmd-index="' + idx + '">'
                    + '<span class="u-inline-da71aab0cc">' + (window.asdrTablerIcon ? window.asdrTablerIcon(c.icon, 20) : '') + '</span>'
                    + '<div>'
                    + '<div class="u-inline-3f7fce4b31"></div>'
                    + '<div class="u-inline-afa3d0ea3b"></div>'
                    + '</div>'
                    + '</a>';
            });
            resultsContainer.innerHTML = html;

            var itemElems = resultsContainer.querySelectorAll('.admin-cmd-item');
            matchedItems.forEach(function (c, idx) {
                if (itemElems[idx]) {
                    var titleEl = itemElems[idx].querySelector('.u-inline-3f7fce4b31');
                    var descEl = itemElems[idx].querySelector('.u-inline-afa3d0ea3b');
                    if (titleEl) { titleEl.textContent = c.title; }
                    if (descEl) { descEl.textContent = c.desc; }
                }
            });

            updateSelectionVisual();
        }

        function openPalette() {
            lastActiveElement = document.activeElement;
            overlay.classList.add('is-open');
            input.value = '';
            selectedIdx = 0;
            renderResults('');
            setTimeout(function () { input.focus(); }, 50);
        }

        function closePalette() {
            overlay.classList.remove('is-open');
            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                try { lastActiveElement.focus(); } catch (err) {}
            }
        }

        document.addEventListener('keydown', function (e) {
            var isK = (e.key === 'k' || e.key === 'K' || e.keyCode === 75);
            if ((e.ctrlKey || e.metaKey) && isK) {
                e.preventDefault();
                if (overlay.classList.contains('is-open')) {
                    closePalette();
                } else {
                    openPalette();
                }
                return;
            }

            if (!overlay.classList.contains('is-open')) { return; }

            if (e.key === 'Escape') {
                e.preventDefault();
                closePalette();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (matchedItems.length > 0) {
                    selectedIdx = (selectedIdx + 1) % matchedItems.length;
                    updateSelectionVisual();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (matchedItems.length > 0) {
                    selectedIdx = (selectedIdx - 1 + matchedItems.length) % matchedItems.length;
                    updateSelectionVisual();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (matchedItems[selectedIdx]) {
                    window.location.href = matchedItems[selectedIdx].url;
                }
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

        resultsContainer.addEventListener('mouseover', function (e) {
            var item = e.target.closest('.admin-cmd-item');
            if (!item) { return; }
            var idx = parseInt(item.getAttribute('data-cmd-index'), 10);
            if (!isNaN(idx) && idx !== selectedIdx) {
                selectedIdx = idx;
                updateSelectionVisual();
            }
        });

        if (input) {
            input.addEventListener('input', function () {
                selectedIdx = 0;
                var q = input.value.trim();
                renderResults(q);

                clearTimeout(searchTimer);
                if (q.length >= 2) {
                    searchTimer = setTimeout(function () {
                        fetch('/admin/search?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (overlay.classList.contains('is-open') && input.value.trim() === q) {
                                    renderResults(q, data.results || []);
                                }
                            })
                            .catch(function () {});
                    }, 200);
                }
            });
        }
    })();


    // --- ИИ-редактор новости: аннотация и SEO ---
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ai-generate]');
        if (!btn) return;

        e.preventDefault();
        var form = btn.closest('form');
        if (!form) return;

        var titleInput = form.querySelector('[name="title"]');
        var title = titleInput ? titleInput.value : '';
        var target = btn.getAttribute('data-ai-generate') || 'summary';

        var content = '';
        var contentField = form.querySelector('[name="content"]');
        if (window.tinymce && contentField && contentField.id && tinymce.get(contentField.id)) {
            content = tinymce.get(contentField.id).getContent();
        }
        if (!content && contentField) { content = contentField.value; }

        if (!title.trim() && !content.trim()) {
            adminAlert('Пожалуйста, введите заголовок или текст новости перед генерацией.');
            return;
        }

        // Содержимое кнопки возвращаем узлами: перезапись innerHTML заново
        // разбирала бы разметку кнопки как HTML.
        var oldNodes = Array.prototype.slice.call(btn.childNodes);
        var restoreButton = function () {
            btn.textContent = '';
            oldNodes.forEach(function (node) { btn.appendChild(node); });
        };
        btn.disabled = true;
        btn.textContent = '⌛ ИИ думает...';

        var body = new URLSearchParams();
        body.append('title', title);
        body.append('content', content);
        body.append('target', target);

        var csrfInput = form.querySelector('[name="csrf_token"]');
        if (csrfInput) {
            body.append('csrf_token', csrfInput.value);
        }

        fetch('/admin/news/ai-summary', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (res) {
            return res.json().catch(function () {
                throw new Error('Сервер вернул некорректный ответ.');
            }).then(function (data) {
                if (!res.ok) {
                    throw new Error(data.error || ('HTTP ' + res.status));
                }
                return data;
            });
        }).then(function (data) {
            btn.disabled = false;
            restoreButton();
            if (data && data.ok) {
                if (target === 'summary' && data.excerpt) {
                    var excerptField = form.querySelector('[name="lead_html"]');
                    if (excerptField) {
                        var escaped = data.excerpt.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var leadHtml = '<p>' + escaped + '</p>';
                        excerptField.value = leadHtml;
                        if (window.tinymce && excerptField.id && window.tinymce.get(excerptField.id)) {
                            window.tinymce.get(excerptField.id).setContent(leadHtml);
                            window.tinymce.get(excerptField.id).save();
                        }
                        excerptField.dispatchEvent(new Event('input'));
                    }
                }
                if (target === 'summary' && data.hashtags) {
                    var hashtagsField = form.querySelector('[name="hashtags"]');
                    if (hashtagsField) { hashtagsField.value = data.hashtags; }
                }
                if (target === 'meta_title' && data.meta_title) {
                    var metaTitleField = form.querySelector('[name="meta_title"]');
                    if (metaTitleField) { metaTitleField.value = data.meta_title; }
                }
                if (target === 'meta_description' && data.meta_description) {
                    var metaDescriptionField = form.querySelector('[name="meta_description"]');
                    if (metaDescriptionField) { metaDescriptionField.value = data.meta_description; }
                }
                if (data.notice) {
                    adminAlert(data.notice);
                }
            } else if (data && data.error) {
                adminAlert(data.error);
            }
        }).catch(function (err) {
            btn.disabled = false;
            restoreButton();
            adminAlert('Ошибка при вызове ИИ: ' + (err.message || err));
        });
    });
})();

/**
 * Обработчики, заменившие inline-атрибуты (onclick/onchange) в шаблонах:
 * CSP админки не разрешает inline-скрипты, поэтому такие атрибуты браузер
 * молча не выполнял — не открывался выбор файла, не работали фильтры
 * медиабиблиотеки и предпросмотр темы.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var picker = event.target.closest('[data-file-pick]');
        if (picker) {
            var input = document.getElementById(picker.getAttribute('data-file-pick'));
            if (input) { input.click(); }
            return;
        }

        var closer = event.target.closest('[data-close-target]');
        if (closer) {
            var box = document.getElementById(closer.getAttribute('data-close-target'));
            if (box) { box.classList.remove('is-open'); }
            return;
        }

        var remover = event.target.closest('[data-remove-closest]');
        if (remover) {
            var row = remover.closest(remover.getAttribute('data-remove-closest'));
            if (row) { row.remove(); }
            return;
        }

        var themePreview = event.target.closest('[data-admin-theme-preview]');
        if (themePreview) {
            document.documentElement.setAttribute(
                'data-admin-theme',
                themePreview.getAttribute('data-admin-theme-preview')
            );
        }
    });

    // Фильтры медиабиблиотеки отправляют форму сразу после выбора.
    document.addEventListener('change', function (event) {
        var select = event.target.closest('select[data-autosubmit]');
        if (select && select.form) { select.form.submit(); }
    });
})();

// Напоминания редактору: список свёрнут, чтобы не занимать экран у тех, кто
// и так всё заполняет.
document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-checklist-toggle]');
    if (!toggle) { return; }
    var box = toggle.closest('[data-content-checklist]');
    var list = box && box.querySelector('.content-checklist__list');
    if (!list) { return; }
    var open = list.hidden;
    list.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.textContent = open ? 'скрыть' : 'показать';
});

// --- Автоматическое скрытие Toast-уведомлений и управление выпадающими меню ---
document.addEventListener('DOMContentLoaded', function () {
    // 1. Авто-скрытие Toast через 4 секунды
    var toasts = document.querySelectorAll('.admin-toast, [data-toast]');
    toasts.forEach(function (toast) {
        toast.classList.add('is-show');
        var timer = setTimeout(function () {
            toast.classList.remove('is-show');
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
        }, 4000);
        
        var closeBtn = toast.querySelector('.admin-toast__close, [data-toast-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                clearTimeout(timer);
                toast.classList.remove('is-show');
                setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
            });
        }
    });

    // 2. Делегирование кликов для Dropdown Kebab Menu (.admin-dropdown)
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-dropdown-toggle], .admin-dropdown__toggle');
        if (toggle) {
            event.preventDefault();
            var dropdown = toggle.closest('.admin-dropdown');
            if (dropdown) {
                var isOpen = dropdown.classList.contains('is-open');
                document.querySelectorAll('.admin-dropdown.is-open').forEach(function (d) {
                    if (d !== dropdown) d.classList.remove('is-open');
                });
                dropdown.classList.toggle('is-open', !isOpen);
            }
            return;
        }

        // Закрывать выпадающие меню при клике вне
        if (!event.target.closest('.admin-dropdown')) {
            document.querySelectorAll('.admin-dropdown.is-open').forEach(function (d) {
                d.classList.remove('is-open');
            });
        }
    });
});
