(function () {
    'use strict';

    var loading = false;
    var callbacks = [];

    function loadTinyMCE(callback) {
        if (window.tinymce) {
            callback();
            return;
        }
        callbacks.push(callback);
        if (loading) { return; }
        loading = true;

        var script = document.createElement('script');
        script.src = '/assets/js/vendor/tinymce/tinymce.min.js';
        script.onload = function () {
            while (callbacks.length > 0) {
                var cb = callbacks.shift();
                try { cb(); } catch (e) { console.error(e); }
            }
        };
        script.onerror = function () {
            console.error('Failed to load TinyMCE');
        };
        document.head.appendChild(script);
    }

    function attach(textarea) {
        if (textarea.dataset.wysiwygReady === '1') { return; }
        textarea.dataset.wysiwygReady = '1';

        loadTinyMCE(function () {
            if (!textarea.id) {
                textarea.id = 'wysiwyg-' + Math.random().toString(36).substr(2, 9);
            }

            window.tinymce.init({
                selector: '#' + textarea.id,
                base_url: '/assets/js/vendor/tinymce',
                suffix: '.min',
                license_key: 'gpl',
                promotion: false,
                branding: false,
                language: 'ru',
                language_url: '/assets/js/vendor/tinymce/langs/ru.js',
                height: 520,
                menubar: 'file edit view insert format table tools help',
                paste_webkit_styles: 'none',
                paste_retain_style_properties: 'none',
                paste_remove_styles_if_webkit: true,
                paste_merge_formats: true,
                paste_as_text: false,
                paste_data_images: false,
                paste_postprocess: function (plugin, args) {
                    var all = args.node.querySelectorAll('*');
                    for (var i = 0; i < all.length; i++) {
                        var el = all[i];
                        el.removeAttribute('style');
                        if (el.className && (el.className.indexOf('Mso') === 0 || el.className.indexOf('ms-') === 0)) {
                            el.removeAttribute('class');
                        }
                        if (el.hasAttribute('lang')) {
                            el.removeAttribute('lang');
                        }
                    }
                    if (args.node.removeAttribute) {
                        args.node.removeAttribute('style');
                    }
                },
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'wordcount', 'codesample',
                    'emoticons', 'help', 'nonbreaking', 'quickbars'
                ],
                toolbar1: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough subscript superscript | blockquote forecolor backcolor | alignleft aligncenter alignright alignjustify',
                toolbar2: 'bullist numlist outdent indent | link image media table blockquote hr codesample emoticons | removeformat searchreplace visualblocks code fullscreen preview',
                quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote',
                content_style: 'body { max-width: none; margin: 1.2rem; font-family: system-ui, -apple-system, sans-serif; font-size: 15px; line-height: 1.65; color: #0f172a; }',
                setup: function (editor) {
                    editor.on('change input blur', function () {
                        editor.save();
                        textarea.dispatchEvent(new Event('input'));
                    });
                },
                file_picker_callback: function (callback, value, meta) {
                    if (meta.filetype === 'image' && window.MediaPicker) {
                        window.MediaPicker.pick(function (url) {
                            callback(url, { alt: '' });
                        });
                    }
                }
            });
        });
    }

    window.ArtEditor = { attach: attach };
})();
