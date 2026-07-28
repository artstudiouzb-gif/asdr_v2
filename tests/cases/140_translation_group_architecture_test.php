<?php

declare(strict_types=1);

use App\Core\TranslationGroupHelper;
use App\Core\TranslationGroupMigration;
use App\Models\News;
use App\Models\Project;

test('Мультиязычная архитектура: создание отдельной записи перевода и связывание через translation_group_id', function (): void {
    ensure_test_db();

    TranslationGroupMigration::run();

    $origId = News::create([
        'title' => 'Оригинальная новость (RU)',
        'slug' => 'original-news-ru',
        'excerpt' => 'Лид новости',
        'content' => '<p>Полный контент новости</p>',
        'status' => 'published',
        'lang' => 'ru',
    ]);

    assert_true($origId > 0, 'Новость создана');

    $uzId = TranslationGroupHelper::createTranslation('news', $origId, 'uz');
    assert_true($uzId > 0 && $uzId !== $origId, 'Создан отдельный пост для узбекского языка');

    $uzNews = News::findById($uzId);
    assert_true($uzNews !== null, 'Узбекская новость найдена в таблице news по своему ID');
    assert_same('uz', $uzNews['lang'], 'Узбекская новость имеет lang=uz');
    assert_same($origId, (int) $uzNews['translation_group_id'], 'Узбекская новость привязана к группе перевода оригинала');
    assert_same('original-news-ru', (string) News::findById($origId)['slug'], 'Slug оригинала не меняется при создании перевода');
    assert_same('original-news-ru-uz', (string) $uzNews['slug'], 'Перевод получает свой slug');

    \App\Core\Database::pdo()
        ->prepare('UPDATE news SET translation_group_id = id WHERE id = :id')
        ->execute([':id' => $uzId]);
    TranslationGroupHelper::autoLinkStandaloneTranslations();
    $relinkedUzNews = News::findById($uzId);
    assert_same($origId, (int) $relinkedUzNews['translation_group_id'], 'Автосвязывание восстановило группу перевода новости');
    assert_same('original-news-ru-uz', (string) $relinkedUzNews['slug'], 'Автосвязывание не перезаписывает slug перевода');

    $translations = TranslationGroupHelper::getTranslations('news', $origId);
    assert_true(isset($translations['ru']), 'В группе есть русский вариант');
    assert_true(isset($translations['uz']), 'В группе есть узбекский вариант');
    assert_same($uzId, (int) $translations['uz']['id'], 'Узбекский вариант ссылается на свой отдельный пост');

    assert_false(in_array('uz', News::availableLangs($origId), true), 'Черновик перевода не объявляется на сайте');
    \App\Core\Database::pdo()
        ->prepare("UPDATE news SET status = 'published', published_at = NOW() WHERE id = :id")
        ->execute([':id' => $uzId]);
    assert_true(in_array('uz', News::availableLangs($origId), true), 'Опубликованный перевод доступен от оригинала');
    assert_true(in_array('uz', News::availableLangs($uzId), true), 'Опубликованный перевод доступен от языковой версии');

    News::forceDelete($uzId);
    News::forceDelete($origId);
});

test('Публичные языки проекта учитывают публикацию независимой версии', function (): void {
    ensure_test_db();

    $origId = Project::create([
        'title' => 'Проект RU',
        'slug' => 'translation-project',
        'description' => 'Описание проекта',
        'cover_image' => null,
        'status' => 'published',
        'is_featured' => 0,
        'sort_order' => 0,
    ]);
    $uzId = TranslationGroupHelper::createTranslation('projects', $origId, 'uz');

    assert_false(in_array('uz', Project::availableLangs($origId), true), 'Черновик проекта не объявляется на сайте');
    \App\Core\Database::pdo()
        ->prepare("UPDATE projects SET status = 'published' WHERE id = :id")
        ->execute([':id' => $uzId]);
    assert_true(in_array('uz', Project::availableLangs($origId), true), 'Опубликованный перевод доступен от оригинала');
    assert_true(in_array('uz', Project::availableLangs($uzId), true), 'Опубликованный перевод доступен от языковой версии');

    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/ProjectController.php');
    assert_contains('Project::availableLangs(', $controller, 'публичный контроллер фильтрует черновики');

    Project::forceDelete($uzId);
    Project::forceDelete($origId);
});
