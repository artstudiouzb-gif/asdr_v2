(function () {
    'use strict';

    try {
        var theme = localStorage.getItem('theme');
        if (theme) {
            document.documentElement.setAttribute('data-theme', theme);
        }
    } catch (error) {
        // Storage can be unavailable in privacy modes; the server default remains valid.
    }
})();
