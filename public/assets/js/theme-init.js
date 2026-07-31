(function () {
    'use strict';

    var root = document.documentElement;
    // Серверная настройка «Тема по умолчанию для посетителей» (light|dark|auto).
    var base = root.getAttribute('data-theme') || 'light';
    root.setAttribute('data-theme-base', base);

    try {
        var theme = localStorage.getItem('theme');
        var savedBase = localStorage.getItem('theme-base');

        // Выбор посетителя действует только для той серверной настройки, при
        // которой он был сделан. Иначе смена темы в админке не доходила бы до
        // тех, кто когда-то нажимал переключатель: у них навсегда оставалась
        // прежняя тема.
        if (theme && savedBase === base) {
            root.setAttribute('data-theme', theme);
        } else if (theme) {
            localStorage.removeItem('theme');
            localStorage.removeItem('theme-base');
        }
    } catch (error) {
        // Storage can be unavailable in privacy modes; the server default remains valid.
    }
})();
