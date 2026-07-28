<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#173a63">
<title>503 — Сервис временно недоступен</title>
<style>
    :root {
        color-scheme: light;
        --navy: #173a63;
        --teal: #137f82;
        --ink: #1f2b3d;
        --muted: #5c697b;
        --surface: #ffffff;
        --background: #f3f6f9;
        --border: #dfe6ee;
    }
    * { box-sizing: border-box; }
    body {
        min-height: 100vh;
        margin: 0;
        padding: 24px;
        display: grid;
        place-items: center;
        font-family: "Segoe UI", Roboto, Arial, sans-serif;
        color: var(--ink);
        background:
            radial-gradient(circle at 50% 0, rgba(19, 127, 130, .12), transparent 38rem),
            var(--background);
        line-height: 1.55;
    }
    main {
        width: min(100%, 620px);
        padding: clamp(32px, 7vw, 56px);
        text-align: center;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        box-shadow: 0 24px 70px rgba(23, 58, 99, .12);
    }
    .status {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 22px;
        padding: 7px 12px;
        color: var(--teal);
        background: rgba(19, 127, 130, .08);
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
    }
    .status::before {
        width: 9px;
        height: 9px;
        content: "";
        background: currentColor;
        border-radius: 50%;
        box-shadow: 0 0 0 5px rgba(19, 127, 130, .12);
    }
    .code {
        margin: 0;
        color: var(--navy);
        font: 800 clamp(64px, 16vw, 96px)/.9 Georgia, serif;
        letter-spacing: -.04em;
    }
    h1 {
        margin: 18px 0 10px;
        color: var(--navy);
        font-size: clamp(24px, 5vw, 34px);
        line-height: 1.2;
    }
    p { margin: 0 auto; color: var(--muted); max-width: 470px; }
    .actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
    }
    a {
        min-height: 44px;
        padding: 11px 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: var(--navy);
        border: 2px solid var(--navy);
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
    }
    a.secondary { color: var(--navy); background: transparent; }
    a:hover { filter: brightness(1.08); }
    a:focus-visible { outline: 3px solid var(--teal); outline-offset: 3px; }
    .hint { margin-top: 24px; font-size: 13px; }
    @media (max-width: 420px) {
        body { padding: 12px; }
        main { padding: 30px 20px; border-radius: 16px; }
        .actions { display: grid; }
    }
</style>
</head>
<body>
<main>
    <div class="status" role="status">Временные технические работы</div>
    <p class="code" aria-hidden="true">503</p>
    <h1>Сервис временно недоступен</h1>
    <p>Не удалось подключиться к одному из компонентов сайта. Пожалуйста, повторите попытку через минуту.</p>
    <div class="actions">
        <a href="">Повторить попытку</a>
        <a class="secondary" href="/">На главную</a>
    </div>
    <p class="hint">Код состояния: 503.</p>
</main>
</body>
</html>
