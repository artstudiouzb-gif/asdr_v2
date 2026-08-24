<?php

declare(strict_types=1);

/*
 * Витрина компонентов для визуальных регресс-тестов.
 *
 *   php tests/browser/seed_visual.php
 *
 * Скриншоты живых страниц сравнивать нельзя: их вид зависит от контента, и
 * тест краснел бы от каждой правки текста. Поэтому тесты снимают отдельную
 * страницу с фиксированным набором блоков — меняется только оформление, а
 * значит любое расхождение картинки означает правку стилей.
 */

require __DIR__ . '/../../app/Core/bootstrap.php';

use App\Core\Database;

const VISUAL_SLUG = 'visual-regression';

/** @return array<string, mixed> */
function block(string $type, array $data, int $sort): array
{
    return ['type' => $type, 'data' => $data, 'sort' => $sort];
}

$blocks = [
    block('text', [
        'title' => 'Витрина компонентов',
        'content' => '<p>Страница существует только для визуальных тестов: набор блоков '
            . 'фиксирован, поэтому расхождение снимка означает правку оформления.</p>',
        'variant' => 'plain',
    ], 0),
    block('counters', [
        'title' => 'Показатели',
        'variant' => 'strip',
        'items' => [
            ['value' => '1 200', 'label' => 'обращений рассмотрено', 'prefix' => '', 'note' => ''],
            ['value' => '24/7', 'label' => 'работа приёмной', 'prefix' => '', 'note' => 'без выходных'],
            ['value' => '№1', 'label' => 'место в рейтинге', 'prefix' => '', 'note' => ''],
        ],
    ], 1),
    block('advantages', [
        'title' => 'Направления работы',
        'description' => 'Три карточки с иконками и описанием.',
        'columns' => 3,
        'items' => [
            ['icon_svg' => 'target', 'title' => 'Планирование', 'text' => 'Стратегические цели и показатели.'],
            ['icon_svg' => 'chart-line', 'title' => 'Анализ', 'text' => 'Оценка хода реформ.'],
            ['icon_svg' => 'users', 'title' => 'Координация', 'text' => 'Работа с ведомствами.'],
        ],
    ], 2),
    block('docs_list', [
        'title' => 'Правовые акты',
        'variant' => 'acts',
        'columns' => 3,
        'search_enabled' => false,
        'items' => [
            ['title' => 'О мерах по совершенствованию системы', 'number' => 'ПФ-6264', 'date' => '19 июля 2021 года', 'url' => 'https://lex.uz/'],
            ['title' => 'Об организации деятельности Агентства', 'number' => 'ПП-216', 'date' => '8 сентября 2022 года', 'url' => 'https://lex.uz/'],
            ['title' => 'О дополнительных мерах поддержки', 'number' => 'ПК-394', 'date' => '29 декабря 2025 года', 'url' => 'https://lex.uz/'],
        ],
    ], 3),
    block('stages', [
        'title' => 'Этапы',
        'description' => 'Хронология с годами и статусами.',
        'items' => [
            ['year' => '2021', 'title' => 'Создание', 'text' => 'Агентство учреждено указом.', 'status' => 'Завершено'],
            ['year' => '2023', 'title' => 'Расширение', 'text' => 'Добавлены функции анализа.', 'status' => 'Завершено'],
            ['year' => '2026', 'title' => 'Цифровизация', 'text' => 'Единая платформа мониторинга.', 'status' => 'В работе'],
        ],
    ], 4),
    block('faq', [
        'title' => 'Частые вопросы',
        'search_enabled' => false,
        'items' => [
            ['q' => 'Как направить обращение?', 'a' => 'Через форму на сайте или почтой.'],
            ['q' => 'Где смотреть отчёты?', 'a' => 'В разделе «Открытые данные».'],
        ],
    ], 5),
    block('contact_cards', [
        'title' => 'Контакты',
        'variant' => 'cards',
        'items' => [
            ['icon_svg' => 'phone', 'title' => 'Приёмная', 'text' => '+998 (71) 000-00-00'],
            ['icon_svg' => 'mail', 'title' => 'Почта', 'text' => 'info@example.uz'],
        ],
    ], 6),
    block('cta', [
        'title' => 'Нужна консультация?',
        'text' => 'Оставьте обращение — ответим в течение трёх рабочих дней.',
        'button_text' => 'Написать',
        'button_url' => '/kontakty',
        'variant' => 'card',
    ], 7),
];

$pdo = Database::pdo();
$pdo->beginTransaction();

$existing = $pdo->prepare('SELECT id FROM pages WHERE slug = ? AND lang = ?');
$existing->execute([VISUAL_SLUG, 'ru']);
$pageId = (int) ($existing->fetchColumn() ?: 0);

if ($pageId === 0) {
    $insert = $pdo->prepare(
        'INSERT INTO pages (title, slug, lang, status, layout_type, entity_type, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $insert->execute(['Витрина компонентов', VISUAL_SLUG, 'ru', 'published', 'no_sidebar', 'page']);
    $pageId = (int) $pdo->lastInsertId();
} else {
    $pdo->prepare('DELETE FROM blocks WHERE page_id = ?')->execute([$pageId]);
}

// Язык блока обязателен: Block::forPage фильтрует по нему, и блоки с пустым
// значением на странице просто не появятся.
$lang = \App\Models\Language::defaultCode();
$add = $pdo->prepare(
    'INSERT INTO blocks (page_id, lang, type, data, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)'
);
foreach ($blocks as $b) {
    $add->execute([$pageId, $lang, $b['type'], json_encode($b['data'], JSON_UNESCAPED_UNICODE), $b['sort']]);
}

$pdo->commit();

echo 'Витрина готова: /' . VISUAL_SLUG . ' (блоков: ' . count($blocks) . ')' . PHP_EOL;
