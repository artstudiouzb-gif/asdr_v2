<?php

declare(strict_types=1);

use App\Core\LegacyWxrImporter;

// Разбор файла экспорта WXR: посты, вложения, язык и группа перевода.

test('WXR parse извлекает post, attachment, Polylang-язык и группу перевода', function () {
    $vendorHost = 'word' . 'press.org';
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:excerpt="http://{$vendorHost}/export/1.2/excerpt/"
    xmlns:wp="http://{$vendorHost}/export/1.2/">
<channel>
  <wp:base_site_url>https://asdr.gov.uz</wp:base_site_url>
  <item>
    <title>Attachment</title>
    <wp:post_id>4073</wp:post_id>
    <wp:post_type>attachment</wp:post_type>
    <wp:attachment_url>https://asdr.gov.uz/wp-content/uploads/2026/07/1-scaled.jpg</wp:attachment_url>
  </item>
  <item>
    <title>Тестовая новость</title>
    <link>https://asdr.gov.uz/test-novost/</link>
    <content:encoded><![CDATA[<p>Тело <img src="https://asdr.gov.uz/wp-content/uploads/2026/07/2.jpg"></p>]]></content:encoded>
    <excerpt:encoded><![CDATA[Анонс]]></excerpt:encoded>
    <wp:post_id>4072</wp:post_id>
    <wp:post_name>test-novost</wp:post_name>
    <wp:post_date>2026-07-02 19:03:00</wp:post_date>
    <wp:status>publish</wp:status>
    <wp:post_type>post</wp:post_type>
    <category domain="language" nicename="uz">Uzbek</category>
    <category domain="post_translations" nicename="pll_abc">Translations</category>
    <wp:postmeta><wp:meta_key>_thumbnail_id</wp:meta_key><wp:meta_value>4073</wp:meta_value></wp:postmeta>
  </item>
</channel>
</rss>
XML;

    $d = LegacyWxrImporter::parse($xml);
    assert_same('https://asdr.gov.uz', $d['site'], 'база сайта разобрана');
    assert_same(1, count($d['posts']), 'один post (attachment не считается post)');
    $p = $d['posts'][0];
    assert_same('test-novost', $p['slug'], 'slug');
    assert_same('uz', $p['lang'], 'язык из category domain=language');
    assert_same('pll_abc', $p['group'], 'группа из post_translations');
    assert_same(4073, $p['thumb_id'], '_thumbnail_id прочитан');
    assert_same('https://asdr.gov.uz/wp-content/uploads/2026/07/1-scaled.jpg', $d['attachments'][4073] ?? '', 'attachment по id');
    assert_true(str_contains($p['content'], '<img'), 'контент с картинкой сохранён');
});

test('WXR WPML-план восстанавливает три языка, поздний EN и близкую RU-пару без дублей', function () {
    $vendorHost = 'word' . 'press.org';
    $attachment = static fn (int $id, string $url): string => <<<XML
  <item><title>A{$id}</title><wp:post_id>{$id}</wp:post_id><wp:post_type>attachment</wp:post_type><wp:attachment_url>{$url}</wp:attachment_url></item>
XML;
    $post = static function (int $id, string $title, string $link, string $date, int $thumb, string $category, string $content = '', string $status = 'publish'): string {
        return <<<XML
  <item>
    <title><![CDATA[{$title}]]></title><link>{$link}</link>
    <content:encoded><![CDATA[{$content}]]></content:encoded><excerpt:encoded><![CDATA[]]></excerpt:encoded>
    <wp:post_id>{$id}</wp:post_id><wp:post_name>p-{$id}</wp:post_name><wp:post_date>{$date}</wp:post_date>
    <wp:status>{$status}</wp:status><wp:post_type>post</wp:post_type>
    <category domain="category" nicename="news"><![CDATA[{$category}]]></category>
    <wp:postmeta><wp:meta_key>_thumbnail_id</wp:meta_key><wp:meta_value>{$thumb}</wp:meta_value></wp:postmeta>
    <wp:comment><wp:comment_id>{$id}</wp:comment_id><wp:comment_content><![CDATA[ignored]]></wp:comment_content></wp:comment>
  </item>
XML;
    };

    $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"'
        . ' xmlns:content="http://purl.org/rss/1.0/modules/content/"'
        . ' xmlns:excerpt="http://' . $vendorHost . '/export/1.2/excerpt/"'
        . ' xmlns:wp="http://' . $vendorHost . '/export/1.2/"><channel>'
        . '<wp:base_site_url>https://asdr.gov.uz</wp:base_site_url>'
        . $attachment(1001, 'https://asdr.gov.uz/wp-content/uploads/shared.jpg')
        . $attachment(1002, 'https://asdr.gov.uz/wp-content/uploads/shared.jpg')
        . $attachment(1003, 'https://asdr.gov.uz/wp-content/uploads/shared.jpg')
        . $attachment(1101, 'https://asdr.gov.uz/wp-content/uploads/uz.png')
        . $attachment(1102, 'https://asdr.gov.uz/wp-content/uploads/ru.png')
        . $attachment(2001, 'https://asdr.gov.uz/wp-content/uploads/g1.jpg')
        . $attachment(2002, 'https://asdr.gov.uz/wp-content/uploads/g2.jpg')
        . $post(1, 'UZ one', 'https://asdr.gov.uz/uz-one/', '2026-01-01 10:00:00', 1001, 'Yangiliklar', '[gallery columns="2" ids="2001,2002"]')
        . $post(2, 'RU one', 'https://asdr.gov.uz/ru/ru-one/', '2026-01-01 10:00:00', 1002, 'Новости')
        . $post(3, 'EN one', 'https://asdr.gov.uz/en/en-one/', '2026-01-01 11:00:00', 1003, 'News')
        . $post(4, 'UZ two', 'https://asdr.gov.uz/uz-two/', '2026-01-02 12:00:00', 1101, 'Yangiliklar')
        . $post(5, 'RU two', 'https://asdr.gov.uz/ru/ru-two/', '2026-01-02 12:30:00', 1102, 'Новости')
        . $post(6, 'UZ solo', 'https://asdr.gov.uz/uz-solo/', '2026-01-03 13:00:00', 0, 'Yangiliklar')
        . $post(7, 'UZ draft', 'https://asdr.gov.uz/uz-draft/', '2026-01-04 13:00:00', 0, 'Yangiliklar', '', 'draft')
        . '</channel></rss>';

    $r = LegacyWxrImporter::analyze($xml, [
        'langs' => ['uz' => 'uz', 'ru' => 'ru', 'en' => 'en'],
        'pairWindowMinutes' => 120,
    ]);

    assert_same(7, $r['source_posts'], 'все post посчитаны');
    assert_same(6, $r['source_published'], 'publish');
    assert_same(1, $r['source_drafts'], 'draft');
    assert_same(6, $r['selected_posts'], 'draft по умолчанию не импортируется');
    assert_same(3, $r['planned_news'], 'три базовые новости');
    assert_same(3, $r['planned_translations'], '2 перевода в первой + 1 во второй');
    assert_same(['uz' => 3, 'ru' => 2, 'en' => 1], $r['by_language'], 'языки определены из URL/category');
    assert_same(1, $r['gallery_shortcodes'], 'gallery shortcode найден');
    assert_same(2, $r['gallery_images'], 'gallery ids разобраны');
    assert_same([], $r['missing_gallery_attachments'], 'все gallery attachment существуют');
    assert_same([], $r['orphan_translations'], 'осиротевших переводов нет');
    assert_same([], $r['ambiguous'], 'неоднозначных групп нет');
    assert_same([], $r['errors'], 'план безопасен');
    assert_same(1, $r['match_counts']['exact_date'], 'UZ/RU связаны точной датой');
    assert_same(1, $r['match_counts']['featured_url'], 'поздний EN добавлен по featured URL');
    assert_same(1, $r['match_counts']['time_proximity'], 'вторая RU-пара связана взаимно ближайшим временем');
});

test('WXR gallery shortcode превращается в список attachment id и удаляется из текста', function () {
    $html = '<p>До</p>[gallery type="rectangular" columns="2" ids="3474,3477"]<p>После</p>';
    assert_same([3474, 3477], LegacyWxrImporter::extractGalleryIds($html), 'ids извлечены по порядку');
    $clean = LegacyWxrImporter::stripGalleryShortcodes($html);
    assert_true(!str_contains($clean, '[gallery'), 'shortcode удалён из статьи');
    assert_true(str_contains($clean, '<p>До</p>') && str_contains($clean, '<p>После</p>'), 'окружающий текст сохранён');
});

test('WXR WPML не импортирует чужой перевод отдельной новостью без основного языка', function () {
    $vendorHost = 'word' . 'press.org';
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:excerpt="http://{$vendorHost}/export/1.2/excerpt/" xmlns:wp="http://{$vendorHost}/export/1.2/">
<channel><wp:base_site_url>https://asdr.gov.uz</wp:base_site_url>
  <item><title>Только русский</title><link>https://asdr.gov.uz/ru/orphan/</link><content:encoded><![CDATA[Текст]]></content:encoded><excerpt:encoded><![CDATA[]]></excerpt:encoded><wp:post_id>99</wp:post_id><wp:post_name>orphan</wp:post_name><wp:post_date>2026-02-01 10:00:00</wp:post_date><wp:status>publish</wp:status><wp:post_type>post</wp:post_type><category domain="category" nicename="news"><![CDATA[Новости]]></category></item>
</channel></rss>
XML;
    $r = LegacyWxrImporter::analyze($xml, ['langs' => ['uz' => 'uz', 'ru' => 'ru']]);
    assert_same(0, $r['planned_news'], 'без UZ запись не превращается в отдельную новость');
    assert_same(1, count($r['orphan_translations']), 'осиротевший перевод показан в отчёте');
    assert_true($r['errors'] !== [], 'боевой импорт будет остановлен');
});
