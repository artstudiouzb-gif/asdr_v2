<?php

declare(strict_types=1);

use App\Core\DemoSeeder;
use App\Core\TranslationGroupHelper;
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

    $homePages = $pdo->query(
        "SELECT lang, id, translation_group_id
         FROM pages
         WHERE slug = 'home' AND lang IN ('ru', 'uz') AND deleted_at IS NULL"
    )->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    assert_true(isset($homePages['ru'], $homePages['uz']), 'Главная создана отдельными RU- и UZ-страницами');
    assert_same(
        (int) $homePages['ru']['id'],
        (int) $homePages['ru']['translation_group_id'],
        'RU-главная является корнем группы переводов'
    );
    assert_same(
        (int) $homePages['ru']['id'],
        (int) $homePages['uz']['translation_group_id'],
        'UZ-главная связана с RU-главной'
    );

    $uzHomeBlocksStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM blocks
         WHERE page_id = :page_id AND lang = 'uz'"
    );
    $uzHomeBlocksStmt->execute([':page_id' => (int) $homePages['uz']['id']]);
    $uzHomeBlocks = (int) $uzHomeBlocksStmt->fetchColumn();
    assert_true($uzHomeBlocks >= 6, 'Главная страница имеет полный UZ-стек блоков');

    $demoPagesStmt = $pdo->prepare(
        "SELECT lang, id, translation_group_id
         FROM pages
         WHERE slug = 'o-nas' AND lang IN ('ru', 'uz') AND deleted_at IS NULL"
    );
    $demoPagesStmt->execute();
    $demoPages = $demoPagesStmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    assert_true(isset($demoPages['ru'], $demoPages['uz']), 'Демо-раздел создан отдельными RU- и UZ-страницами');
    assert_true(
        (int) $demoPages['ru']['id'] !== (int) $demoPages['uz']['id'],
        'Языковые версии имеют разные page_id'
    );
    assert_same(
        (int) $demoPages['ru']['id'],
        (int) $demoPages['uz']['translation_group_id'],
        'Языковые версии входят в одну группу переводов'
    );

    $translations = TranslationGroupHelper::getTranslations('pages', (int) $demoPages['uz']['id']);
    assert_true(isset($translations['ru'], $translations['uz']), 'Редактор видит оба существующих перевода');
    assert_same(
        (int) $demoPages['uz']['id'],
        (int) $translations['uz']['id'],
        'Кнопка UZ в редакторе ведёт на существующую страницу'
    );

    $mixedStacksStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM blocks
         WHERE (page_id = :ru_id AND lang <> \'ru\')
            OR (page_id = :uz_id AND lang <> \'uz\')'
    );
    $mixedStacksStmt->execute([
        ':ru_id' => (int) $demoPages['ru']['id'],
        ':uz_id' => (int) $demoPages['uz']['id'],
    ]);
    assert_same(0, (int) $mixedStacksStmt->fetchColumn(), 'Блоки языков не смешиваются между страницами');

    foreach (['content_entry_translations', 'project_translations', 'photo_album_translations', 'video_translations', 'team_member_translations'] as $table) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE lang = 'uz'")->fetchColumn();
        assert_true($count > 0, "{$table}: созданы узбекские переводы");
    }
});
