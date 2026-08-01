<?php

declare(strict_types=1);

use App\Core\SocialPublisher;
use App\Core\TelegramRichMessage;

/**
 * Расширенный формат поста (Bot API 10.1+, `sendRichMessage`). Подпись к фото
 * ограничена 1024 символами, и двуязычная новость резалась пополам. У
 * rich-сообщения предел 32768 символов, поэтому текст уходит целиком, а вторая
 * языковая версия прячется под «развернуть».
 *
 * Формат новый: если Telegram его не примет, публикация обязана продолжиться
 * прежним путём — это проверяется отдельно.
 */

function rich_post(): array
{
    return [
        'message' => 'Sarlavha',
        'link' => 'https://site.uz/news/x',
        'image_url' => 'https://site.uz/cover.jpg',
        'gallery' => ['https://site.uz/1.jpg', 'https://site.uz/2.jpg'],
        'category' => 'Мероприятия',
        'date' => '1 августа 2026',
        'hashtags' => '#реформы',
        'langs' => [
            ['code' => 'uz', 'label' => 'Oʻzbekcha', 'title' => 'Sarlavha', 'excerpt' => "Birinchi.\n\nIkkinchi.",
             'link' => 'https://site.uz/uz/news/x', 'read_more' => 'Saytda oʻqish →'],
            ['code' => 'ru', 'label' => 'Русский', 'title' => 'Заголовок', 'excerpt' => 'Русский анонс.',
             'link' => 'https://site.uz/news/x', 'read_more' => 'Читать на сайте →'],
        ],
    ];
}

test('Rich: пост уходит одним sendRichMessage со всей вёрсткой', function () {
    $calls = [];
    $http = function ($m, $u, $b, $h) use (&$calls) {
        $calls[] = ['url' => $u, 'body' => json_decode($b, true)];
        return ['status' => 200, 'body' => '{"ok":true,"result":{"message_id":7}}'];
    };
    $res = (new SocialPublisher($http))->publish('telegram', ['token' => 'T', 'chat_id' => '@c', 'signature' => '<b>Агентство</b>'], rich_post());

    assert_true($res['ok']);
    assert_same('7', (string) $res['remote_id']);
    assert_same(1, count($calls), 'весь пост — одно сообщение');
    assert_contains('/sendRichMessage', (string) $calls[0]['url']);

    $rich = $calls[0]['body']['rich_message'];
    $html = (string) $rich['html'];
    // Снимки в разметке — ссылками tg://photo?id=…, файлы отдельным полем.
    assert_contains('<img src="tg://photo?id=p1">', $html);
    assert_not_contains('<img src="https://', $html);
    assert_same(3, count($rich['media']), 'обложка и два снимка галереи');
    assert_same('p1', (string) $rich['media'][0]['id']);
    assert_same('photo', (string) $rich['media'][0]['media']['type']);
    assert_same('https://site.uz/cover.jpg', (string) $rich['media'][0]['media']['media']);
    // sendRichMessage принимает reply_markup — кнопки есть и в новом формате.
    $buttons = $calls[0]['body']['reply_markup']['inline_keyboard'][0] ?? [];
    assert_same(2, count($buttons), 'по кнопке на язык');
    assert_contains('<h1>Sarlavha</h1>', $html);
    assert_contains('Мероприятия · 1 августа 2026', $html);
    // Второй язык — под «развернуть», а не вторым экраном текста.
    assert_contains('<details><summary>Русский</summary>', $html);
    assert_contains('Русский анонс.', $html);
    // Галерея — слайд-шоу: альбом из снимков занимал бы весь экран ленты.
    assert_contains('<tg-slideshow>', $html);
    assert_contains('#реформы', $html);
    assert_contains('<b>Агентство</b>', $html, 'подпись сохраняет свою разметку');
});

test('Rich: отказ Telegram не срывает публикацию — работает прежний формат', function () {
    $calls = [];
    $http = function ($m, $u, $b, $h) use (&$calls) {
        $calls[] = $u;
        // Старый Bot API не знает метод: публикация обязана продолжиться.
        return str_contains($u, 'sendRichMessage')
            ? ['status' => 404, 'body' => '{"ok":false,"error_code":404,"description":"Not Found: method not found"}']
            : ['status' => 200, 'body' => '{"ok":true,"result":[{"message_id":9}]}'];
    };
    $res = (new SocialPublisher($http))->publish('telegram', ['token' => 'T', 'chat_id' => '@c'], rich_post());

    assert_true($res['ok'], 'после отката пост всё равно опубликован');
    assert_true(str_contains($calls[0], 'sendRichMessage'), 'сначала пробуем расширенный формат');
    assert_true(str_contains($calls[1], 'sendMediaGroup'), 'затем прежний путь с галереей');
});

test('Rich: формат «обычный» отключает новый метод полностью', function () {
    $calls = [];
    $http = function ($m, $u, $b, $h) use (&$calls) {
        $calls[] = $u;
        return ['status' => 200, 'body' => '{"ok":true,"result":[{"message_id":3}]}'];
    };
    (new SocialPublisher($http))->publish('telegram', ['token' => 'T', 'chat_id' => '@c', 'format' => 'classic'], rich_post());

    foreach ($calls as $url) {
        assert_not_contains('sendRichMessage', $url);
    }
});

test('Rich: тихая публикация не будит подписчиков', function () {
    $seen = [];
    $http = function ($m, $u, $b, $h) use (&$seen) { $seen = json_decode($b, true); return ['status' => 200, 'body' => '{"ok":true,"result":{"message_id":1}}']; };
    (new SocialPublisher($http))->publish('telegram', ['token' => 'T', 'chat_id' => '@c', 'silent' => '1'], rich_post());

    assert_true(!empty($seen['disable_notification']));
});

test('Rich: разметка экранируется, чужой HTML внутрь не попадает', function () {
    $doc = TelegramRichMessage::build(
        [['code' => 'ru', 'label' => 'Русский', 'title' => '<script>alert(1)</script>', 'excerpt' => 'Текст & «кавычки»',
          'link' => 'https://site.uz/news/x', 'read_more' => 'Читать']],
        ['https://site.uz/a.jpg'],
        '',
        '',
        '',
        ''
    );

    assert_not_contains('<script>', $doc['html']);
    assert_contains('&lt;script&gt;', $doc['html']);
    assert_contains('&amp;', $doc['html']);
});

test('Rich: без языковых блоков формат не применяется', function () {
    assert_same('', TelegramRichMessage::build([], ['https://site.uz/a.jpg'])['html']);

    // Предел текста rich-сообщения — 32768 символов против 1024 у подписи.
    $long = TelegramRichMessage::build(
        [['code' => 'ru', 'label' => 'Русский', 'title' => 'T', 'excerpt' => str_repeat('я', 40000), 'link' => '', 'read_more' => '']],
        []
    );
    assert_false(TelegramRichMessage::fits($long['html']), 'слишком длинный текст откатится на прежний формат');
});
