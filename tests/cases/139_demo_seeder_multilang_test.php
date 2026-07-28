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
});
