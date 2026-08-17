(function () {
    'use strict';

    var current = document.currentScript;
    var currentUrl = current && current.src ? current.src : '/assets/js/admin-media-loader.js';
    var version = '';

    try {
        version = new URL(currentUrl, document.baseURI).search || '';
    } catch (error) {}

    function siblingUrl(fileName) {
        try {
            return new URL(fileName + version, currentUrl).toString();
        } catch (error) {
            return '/assets/js/' + fileName + version;
        }
    }

    function styleUrl(fileName) {
        try {
            var url = new URL('../css/' + fileName, currentUrl);
            url.search = version.replace(/^\?/, '');
            return url.toString();
        } catch (error) {
            return '/assets/css/' + fileName + version;
        }
    }

    // Сохраняем исторический marker: другие проверки и расширения админки
    // могут использовать его как контракт загрузки workflow-стилей.
    function loadWorkflowStyle() {
        if (document.querySelector('link[data-admin-workflow-fixes]')) { return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = styleUrl('admin-workflow-fixes.css');
        link.setAttribute('data-admin-workflow-fixes', '');
        document.head.appendChild(link);
    }

    function loadSliderSettingsStyle() {
        if (document.querySelector('link[data-admin-slider-settings-layout]')) { return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = styleUrl('admin-slider-settings-layout.css');
        link.setAttribute('data-admin-slider-settings-layout', '');
        document.head.appendChild(link);
    }

    function load(fileName, done) {
        var script = document.createElement('script');
        script.src = siblingUrl(fileName);
        script.async = false;
        script.onload = function () { if (done) { done(); } };
        script.onerror = function () {
            if (window.console && console.error) {
                console.error('Не удалось загрузить административный скрипт: ' + fileName);
            }
        };
        document.head.appendChild(script);
    }

    loadWorkflowStyle();
    loadSliderSettingsStyle();

    // admin.js остаётся основным скриптом. Мост загружается строго после него,
    // затем подключаются небольшие workflow-слои: они не меняют данные форм,
    // а улучшают поведение медиаполей и компоновку настроек.
    load('admin.js', function () {
        load('admin-media-bridge.js', function () {
            load('admin-workflow-fixes.js', function () {
                load('admin-slider-settings-layout.js');
            });
        });
    });
})();
