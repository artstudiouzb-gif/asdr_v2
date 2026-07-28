<?php

declare(strict_types=1);

use App\Core\TranslationGroupHelper;
use App\Models\Page;

test('Админка: группы переводов не дублируют строки в списке и выводят кликабельные языковые баджи', function (): void {
    ensure_test_db();

    TranslationGroupHelper::ensureSchema();

    // Создаём тестовую первичную страницу RU
    $origId = Page::create([
        'title' => 'Тестовая страница RU',
        'slug' => 'test-page-ru',
        'meta_title' => null,
        'meta_description' => null,
        'lead' => null,
        'layout_type' => 'no_sidebar',
        'status' => 'published',
        'is_home' => 0,
        'hide_chrome' => 0,
        'transparent_header' => 0,
        'lang' => 'ru',
    ]);

    // Переводим на UZ
    $uzId = TranslationGroupHelper::createTranslation('pages', $origId, 'uz');

    // Проверяем список без фильтра по языку (общий стандарт: выводит все записи)
    $adminList = Page::adminList(['lang' => '', 'status' => '', 'q' => 'test-page-ru', 'sort' => 'newest', 'per_page' => 20, 'offset' => 0]);
    
    $idsInList = array_map(static fn(array $item): int => (int) $item['id'], $adminList);
    assert_true(in_array($origId, $idsInList, true), 'Первичная запись присутствует в списке');
    assert_true(in_array($uzId, $idsInList, true), 'Перевод также присутствует в общем списке Все языки');

    // Фильтр конкретного языка возвращает только запись этого языка
    $uzOnly = Page::adminList(['lang' => 'uz', 'status' => '', 'q' => 'test-page-ru', 'sort' => 'newest', 'per_page' => 20, 'offset' => 0]);
    $uzOnlyIds = array_map(static fn(array $item): int => (int) $item['id'], $uzOnly);
    assert_true(in_array($uzId, $uzOnlyIds, true), 'UZ-фильтр показывает самостоятельную UZ-запись');
    assert_false(in_array($origId, $uzOnlyIds, true), 'UZ-фильтр не подмешивает RU-запись той же группы');

    // Проверяем карту языков
    $langList = Page::availableLangsForIds([$origId]);
    assert_true(in_array('ru', $langList[$origId], true), 'В списке языков есть RU');
    assert_true(in_array('uz', $langList[$origId], true), 'В списке языков есть UZ');

    $langMap = Page::availableLangsForIds([$origId], true);
    assert_true(isset($langMap[$origId]['ru']), 'Есть бадж RU');
    assert_true(isset($langMap[$origId]['uz']), 'Есть бадж UZ');
    assert_same($uzId, $langMap[$origId]['uz'], 'Бадж UZ ведет на ID перевода #uzId');

    // Очистка
    Page::forceDelete($uzId);
    Page::forceDelete($origId);
});

test('Связывание переводов: findHome("uz") возвращает переведённую страницу и autoLink связывает неручные записи', function (): void {
    ensure_test_db();

    TranslationGroupHelper::ensureSchema();

    // Создаём главную страницу RU
    $homeRuId = Page::create([
        'title' => 'Тестовая главная',
        'slug' => 'translation-home',
        'meta_title' => null,
        'meta_description' => null,
        'status' => 'published',
        'is_home' => 1,
        'lang' => 'ru',
    ]);

    // Создаём перевод UZ
    $homeUzId = TranslationGroupHelper::createTranslation('pages', $homeRuId, 'uz');
    Page::update($homeUzId, [
        'title' => 'Тестовая главная (UZ)',
        'slug' => 'translation-home-uz',
        'meta_title' => null,
        'meta_description' => null,
        'status' => 'published',
        'lang' => 'uz',
    ]);

    // 1. Проверяем findHome('uz')
    $foundUz = Page::findHome('uz');
    assert_true($foundUz !== null, 'Найдена переведённая главная страница для uz');
    assert_same($homeUzId, (int) $foundUz['id'], 'findHome("uz") вернул именно страницу перевода #homeUzId');
    assert_same(1, (int) $foundUz['is_home'], 'Перевод главной сохраняет контекст главной и не выводит хлебные крошки');
    assert_true(in_array('uz', Page::availableLangs($homeRuId), true), 'RU-версия видит опубликованный UZ-перевод');
    assert_true(in_array('uz', Page::availableLangs($homeUzId), true), 'UZ-версия считается доступным переводом');

    // 2. Симулируем некорректную отвязанную запись (translation_group_id = id)
    \App\Core\Database::pdo()
        ->prepare('UPDATE pages SET translation_group_id = id WHERE id = :id')
        ->execute([':id' => $homeUzId]);
    
    // Вызываем автосвязывание
    TranslationGroupHelper::autoLinkStandaloneTranslations();

    $reloadedUz = Page::findById($homeUzId);
    assert_same($homeRuId, (int) $reloadedUz['translation_group_id'], 'Автосвязывание привязало отвязанную запись к первичной главной странице');

    Page::forceDelete($homeUzId);
    Page::forceDelete($homeRuId);
});

test('Единый slug (contacts, about, home) поддерживает одинаковое имя на разных языках', function (): void {
    ensure_test_db();

    TranslationGroupHelper::ensureSchema();

    // Создаём страницу "Контакты" RU со слагом "contacts"
    $ruPageId = Page::create([
        'title' => 'Контакты (RU)',
        'slug' => 'contacts',
        'meta_title' => null,
        'meta_description' => null,
        'status' => 'published',
        'lang' => 'ru',
    ]);

    // Создаём перевод "Контакты" UZ — он должен получить тот же самый slug "contacts"
    $uzPageId = TranslationGroupHelper::createTranslation('pages', $ruPageId, 'uz');
    Page::update($uzPageId, [
        'title' => 'Aloqa (UZ)',
        'slug' => 'contacts',
        'meta_title' => null,
        'meta_description' => null,
        'status' => 'published',
        'lang' => 'uz',
    ]);

    // Проверяем, что обе страницы имеют slug "contacts", но разный язык
    $ruPage = Page::findById($ruPageId);
    $uzPage = Page::findById($uzPageId);

    assert_same('contacts', $ruPage['slug'], 'Русская страница имеет slug = contacts');
    assert_same('contacts', $uzPage['slug'], 'Узбекская страница имеет ТОТ ЖЕ slug = contacts');

    // Проверяем резолв по findBySlug
    $resolvedRu = Page::findBySlug('contacts', 'ru');
    $resolvedUz = Page::findBySlug('contacts', 'uz');

    assert_same($ruPageId, (int) $resolvedRu['id'], 'findBySlug("contacts", "ru") вернул русскую страницу');
    assert_same($uzPageId, (int) $resolvedUz['id'], 'findBySlug("contacts", "uz") вернул узбекскую страницу');

    Page::forceDelete($uzPageId);
    Page::forceDelete($ruPageId);
});
