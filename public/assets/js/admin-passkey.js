/*
 * Ключи доступа (passkeys): регистрация в профиле и подтверждение входа.
 * Сервер отдаёт параметры с двоичными полями в base64url и принимает ответ
 * ключа полями формы — с тем же CSRF-токеном, что у обычных форм.
 */
(function () {
    'use strict';

    function fromB64url(text) {
        var b64 = text.replace(/-/g, '+').replace(/_/g, '/');
        while (b64.length % 4) {
            b64 += '=';
        }
        var raw = atob(b64);
        var bytes = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) {
            bytes[i] = raw.charCodeAt(i);
        }
        return bytes.buffer;
    }

    function toB64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var raw = '';
        for (var i = 0; i < bytes.length; i++) {
            raw += String.fromCharCode(bytes[i]);
        }
        return btoa(raw).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function post(url, fields) {
        var body = new URLSearchParams();
        Object.keys(fields).forEach(function (key) {
            body.append(key, fields[key]);
        });
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            body: body
        }).then(function (response) {
            return response.json().catch(function () {
                return { error: 'Сервер ответил ошибкой ' + response.status + '.' };
            });
        });
    }

    function credentialList(list) {
        return (list || []).map(function (item) {
            return { type: item.type, id: fromB64url(item.id) };
        });
    }

    function showError(root, message) {
        var box = root.querySelector('[data-passkey-error]');
        if (box) {
            box.textContent = message;
            box.hidden = false;
        }
    }

    function failure(error) {
        if (error && error.name === 'NotAllowedError') {
            return 'Операция отменена или время ожидания истекло.';
        }
        if (error && error.name === 'InvalidStateError') {
            return 'Этот ключ уже добавлен.';
        }
        return (error && error.message) || 'Не удалось связаться с ключом.';
    }

    function csrf(root) {
        var input = root.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function register(form) {
        var token = csrf(form);
        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        post('/admin/profile/passkey/options', { csrf_token: token }).then(function (options) {
            if (options.error) {
                throw new Error(options.error);
            }
            options.challenge = fromB64url(options.challenge);
            options.user.id = fromB64url(options.user.id);
            options.excludeCredentials = credentialList(options.excludeCredentials);
            return navigator.credentials.create({ publicKey: options });
        }).then(function (credential) {
            return post('/admin/profile/passkey/register', {
                csrf_token: token,
                name: form.querySelector('[name="name"]').value,
                password: form.querySelector('[name="password"]').value,
                client_data: toB64url(credential.response.clientDataJSON),
                attestation: toB64url(credential.response.attestationObject)
            });
        }).then(function (result) {
            if (!result.ok) {
                throw new Error(result.error || 'Ключ не добавлен.');
            }
            window.location.reload();
        }).catch(function (error) {
            showError(form, failure(error));
            button.disabled = false;
        });
    }

    function login(root, button) {
        var token = csrf(root);
        button.disabled = true;
        post('/admin/login/2fa/passkey/options', { csrf_token: token }).then(function (options) {
            if (options.error) {
                throw new Error(options.error);
            }
            options.challenge = fromB64url(options.challenge);
            options.allowCredentials = credentialList(options.allowCredentials);
            return navigator.credentials.get({ publicKey: options });
        }).then(function (credential) {
            return post('/admin/login/2fa/passkey', {
                csrf_token: token,
                id: toB64url(credential.rawId),
                client_data: toB64url(credential.response.clientDataJSON),
                authenticator_data: toB64url(credential.response.authenticatorData),
                signature: toB64url(credential.response.signature)
            });
        }).then(function (result) {
            if (!result.ok) {
                throw new Error(result.error || 'Ключ не подошёл.');
            }
            window.location.assign(result.redirect || '/admin');
        }).catch(function (error) {
            showError(root, failure(error));
            button.disabled = false;
        });
    }

    var supported = !!(window.PublicKeyCredential && navigator.credentials);

    document.querySelectorAll('[data-passkey-register]').forEach(function (form) {
        if (!supported) {
            showError(form, 'Этот браузер не поддерживает ключи доступа.');
            form.querySelector('button[type="submit"]').disabled = true;
            return;
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            register(form);
        });
    });

    document.querySelectorAll('[data-passkey-login]').forEach(function (root) {
        var button = root.querySelector('button');
        if (!supported) {
            root.hidden = true;
            return;
        }
        button.addEventListener('click', function () {
            login(root, button);
        });
    });
})();
