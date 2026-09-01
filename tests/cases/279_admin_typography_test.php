
test('Ход перехода: полоса объявлена в CSS и живёт до конца перехода', function (): void {
    // Админка отвечает полной перезагрузкой на каждое сохранение и переход.
    // AJAX-сохранения здесь нет и не было: формы уходят обычным POST, а flash
    // и тосты устроены под редирект. Но между нажатием и новой страницей
    // раньше не происходило ничего — ожидание читалось как «не сработало».
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/admin.css');
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/admin.js');

    assert_contains('.admin-route-progress', $css, 'полоса без правил ничего не покажет');
    assert_contains("bar.className = 'admin-route-progress'", $js);
    // Цвет — из темы панели: у светлой и тёмной он разный.
    assert_contains('background: var(--admin-accent)', $css);

    // Переход бывает и не доходит до новой страницы (скачивание файла,
    // отменённая отправка, ошибка сети). Бесконечная полоса врёт хуже, чем её
    // отсутствие, поэтому у неё есть предел ожидания и снятие из кэша «назад».
    assert_contains('window.setTimeout(finishProgress, 20000)', $js);
    assert_contains("window.addEventListener('pageshow', finishProgress)", $js);
});

test('Возврат к месту правки после сохранения', function (): void {
    // Сохранение уводит в начало страницы, и на длинной форме («Настройки
    // сайта» — шесть с лишним тысяч пикселей) редактор терял место правки.
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/admin.js');
    assert_contains('artstudio:admin:scroll:', $js);
    assert_contains('function rememberScroll', $js);
    assert_contains('function restoreScroll', $js);
    // Якорь в адресе — осознанная цель редиректа (/admin/telegram#telegram-channel),
    // спорить с ней нельзя: иначе сообщение снова окажется выше экрана.
    assert_contains('if (window.location.hash) { return; }', $js);
});
