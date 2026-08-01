<?php

declare(strict_types=1);

use App\Core\WeeklyRoundup;

/**
 * Итоги недели одним постом в канал. Подписчик, пропустивший будни, получает
 * сводку со ссылками и не листает ленту назад.
 */

test('Сводка собирает новости недели по языкам (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $suffix = uniqid();
    $now = new \DateTimeImmutable();

    $fresh = \App\Models\News::create([
        'title' => 'Свежая новость', 'slug' => 'rd-fresh-' . $suffix, 'excerpt' => 'А',
        'content' => 'т', 'status' => 'published',
        'published_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
    ]);
    $old = \App\Models\News::create([
        'title' => 'Старая новость', 'slug' => 'rd-old-' . $suffix, 'excerpt' => 'А',
        'content' => 'т', 'status' => 'published',
        'published_at' => $now->modify('-20 days')->format('Y-m-d H:i:s'),
    ]);
    $draft = \App\Models\News::create([
        'title' => 'Черновик', 'slug' => 'rd-draft-' . $suffix, 'excerpt' => 'А',
        'content' => 'т', 'status' => 'draft',
        'published_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
    ]);

    try {
        $slugs = array_map(
            static fn (array $i): string => (string) $i['slug'],
            WeeklyRoundup::collect(7, $now)['ru'] ?? []
        );

        assert_true(in_array('rd-fresh-' . $suffix, $slugs, true), 'новость недели попадает в сводку');
        assert_false(in_array('rd-old-' . $suffix, $slugs, true), 'прошлый месяц в сводку не тянем');
        assert_false(in_array('rd-draft-' . $suffix, $slugs, true), 'черновик не публикуем');
    } finally {
        $pdo->prepare('DELETE FROM news WHERE id IN (:a, :b, :c)')
            ->execute([':a' => $fresh, ':b' => $old, ':c' => $draft]);
    }
});

test('Пост — список ссылок с рубриками, по разделу на язык', function () {
    $now = new \DateTimeImmutable('2026-08-03 09:00:00');
    $html = WeeklyRoundup::buildHtml([
        'ru' => [['title' => 'Русский заголовок', 'slug' => 'ru-slug', 'badge' => 'Мероприятия']],
        'uz' => [['title' => 'Uzbek sarlavha', 'slug' => 'uz-slug', 'badge' => 'Tadbirlar']],
    ], $now);

    assert_contains('Итоги недели', $html);
    assert_contains('Hafta yakunlari', $html);
    assert_contains('/news/ru-slug', $html);
    // У неосновного языка адрес со своим префиксом.
    assert_contains('/uz/news/uz-slug', $html);
    assert_contains('<i>Мероприятия</i>', $html);
    assert_contains('———', $html, 'языки разделены, а не свалены в один список');

    // Разметка экранируется: заголовок пишет редактор.
    $evil = WeeklyRoundup::buildHtml(['ru' => [['title' => '<script>x</script>', 'slug' => 's', 'badge' => '']]], $now);
    assert_not_contains('<script>', $evil);

    // Пустая неделя — пустой пост, отправлять нечего.
    assert_same('', WeeklyRoundup::buildHtml([], $now));
});

test('Пустую неделю и повторный запуск пропускаем молча (БД)', function () {
    ensure_test_db();
    \App\Models\Setting::set(WeeklyRoundup::ENABLED_KEY, '0');
    \App\Models\Setting::set(WeeklyRoundup::LAST_SENT_KEY, '');

    try {
        // Выключено — ничего не делаем.
        $off = WeeklyRoundup::send();
        assert_false($off['sent']);
        assert_contains('выключена', $off['reason']);

        // Включено, но канал не настроен — тоже не отправляем.
        \App\Models\Setting::set(WeeklyRoundup::ENABLED_KEY, '1');
        \App\Models\Setting::set('social_telegram_enabled', '0');
        $noChannel = WeeklyRoundup::send();
        assert_false($noChannel['sent']);
        assert_contains('не настроен', $noChannel['reason']);

        // Уже отправляли на этой неделе — защита от задвоенного cron.
        \App\Models\Setting::set(WeeklyRoundup::LAST_SENT_KEY, date('Y-m-d H:i:s'));
        \App\Models\Setting::set('social_telegram_enabled', '1');
        \App\Models\Setting::set('social_telegram_chat_id', '-1001234567890');
        \App\Models\Setting::set('telegram_bot_token', '123456:AAtest');
        $again = WeeklyRoundup::send();
        assert_false($again['sent']);
        assert_contains('уже отправлена', $again['reason']);
    } finally {
        foreach ([WeeklyRoundup::ENABLED_KEY, WeeklyRoundup::LAST_SENT_KEY,
                  'social_telegram_enabled', 'social_telegram_chat_id', 'telegram_bot_token'] as $key) {
            \App\Models\Setting::set($key, '');
        }
    }
});

test('Сводка уходит одним обычным сообщением, без медиа', function () {
    $seen = [];
    $http = function ($m, $u, $b, $h) use (&$seen) {
        $seen = ['url' => $u, 'body' => json_decode($b, true)];
        return ['status' => 200, 'body' => '{"ok":true,"result":{"message_id":11}}'];
    };
    $res = (new \App\Core\SocialPublisher($http))->sendChannelMessage(
        ['token' => 'T', 'chat_id' => '-1001234567890'],
        '<b>Итоги недели</b>'
    );

    assert_true($res['ok']);
    assert_contains('/sendMessage', (string) $seen['url']);
    assert_same('HTML', (string) $seen['body']['parse_mode']);
    assert_true(!isset($seen['body']['media']), 'список ссылок медиа не требует');

    // Пустое сообщение не отправляем.
    assert_false((new \App\Core\SocialPublisher($http))->sendChannelMessage(['token' => 'T', 'chat_id' => 'c'], '  ')['ok']);
});

test('Настройка и задание Cron описаны в разделе Telegram', function () {
    $view = (string) file_get_contents(APP_ROOT . '/app/Views/admin/telegram/index.php');
    assert_contains('name="telegram_roundup"', $view);
    assert_contains('app/Console/weekly_roundup.php', $view);
    assert_contains('0 9 * * 1', $view);

    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/TelegramController.php');
    assert_contains('WeeklyRoundup::ENABLED_KEY', $controller);
    assert_true(is_file(APP_ROOT . '/app/Console/weekly_roundup.php'));
});
