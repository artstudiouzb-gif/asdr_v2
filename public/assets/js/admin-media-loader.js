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

    function loadStyle(fileName) {
        var marker = fileName === 'admin-workflow-fixes.css'
            ? 'data-admin-workflow-fixes'
            : 'data-admin-slider-settings-layout';
        if (document.querySelector('link[' + marker + ']')) { return; }

        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = styleUrl(fileName);
        link.setAttribute(marker, '');
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

    // Исторический workflow-marker и вызов оставлены без изменения, чтобы
    // существующие расширения/проверки админки продолжали видеть тот же контракт.
    loadStyle('admin-workflow-fixes.css');
    loadStyle('admin-slider-settings-layout.css');

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
