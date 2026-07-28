<?php

declare(strict_types=1);

use App\Core\DemoSeeder;
use App\Models\News;
use App\Models\NewsTranslation;

test('DemoSeeder создает демо-данные с многоязычными деталями (Тезисы, Мероприятие, Документы, Опрос)', function (): void {
    ensure_test_db();

    $pdo = \App\Core\Database::pdo();
    DemoSeeder::run($pdo);

    $news = News::findPublishedBySlug('zasedanie-strategiya-2030');
    assert_true($news !== null, 'Флагманская демо-новость создана');

    $uzTrans = NewsTranslation::find((int) $news['id'], 'uz');
    assert_true($uzTrans !== null, 'Узбекский перевод для демо-новости создан');
    assert_contains('O‘zbekiston–2030', (string) $uzTrans['title'], 'Узбекский заголовок новости создан');
    assert_contains('Tadbirlar', (string) $uzTrans['badge'], 'Узбекский бейдж новости создан');
    assert_contains('ustuvor yo‘nalishlari', (string) $uzTrans['key_points'], 'Узбекские тезисы новости созданы');
    assert_true(in_array('uz', News::availableLangs((int) $news['id']), true), 'Опубликованная UZ-версия доступна на сайте');

    assert_contains('#Узбекистан2030', (string) $news['hashtags'], 'У флагманской новости заполнены хештеги');
    $timeline = json_decode((string) $news['timeline_json'], true);
    assert_true(is_array($timeline) && count($timeline) >= 3, 'У флагманской новости заполнена хроника');

    $pollCountStmt = $pdo->prepare('SELECT COUNT(*) FROM news_polls WHERE news_id = :news_id');
    $pollCountStmt->execute([':news_id' => (int) $news['id']]);
    assert_same(1, (int) $pollCountStmt->fetchColumn(), 'У флагманской новости создан опрос');

    $uzHomeBlocks = (int) $pdo->query(
        "SELECT COUNT(*)
         FROM blocks b
         INNER JOIN pages p ON p.id = b.page_id AND p.is_home = 1
         WHERE b.lang = 'uz'"
    )->fetchColumn();
    assert_true($uzHomeBlocks >= 6, 'Главная страница имеет полный UZ-стек блоков');

    foreach (['content_entry_translations', 'project_translations', 'photo_album_translations', 'video_translations', 'team_member_translations'] as $table) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE lang = 'uz'")->fetchColumn();
        assert_true($count > 0, "{$table}: созданы узбекские переводы");
    }
});
