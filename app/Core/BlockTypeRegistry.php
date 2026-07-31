<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Единый реестр типов блоков.
 *
 * Здесь собраны ключи типов, их дефолтные данные и названия для редактора.
 * Шаблоны обычных блоков следуют соглашению templates/blocks/{type}.php;
 * columns рендерится программно, потому отдельного шаблона у него нет.
 */
final class BlockTypeRegistry
{
    /** @var array<string, array<string, mixed>> */
    public const DEFAULTS = [
        'text' => ['title' => '', 'content' => ''],
        'html' => ['html' => ''],
        'cta' => ['variant' => 'card', 'title' => '', 'text' => '', 'icon_svg' => '', 'image' => '', 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'button_text' => '', 'button_url' => '', 'bg_color' => '', 'text_color' => '', 'button_color' => ''],
        'advantages' => ['variant' => 'grid', 'title' => '', 'items' => []],
        'slider' => ['slides' => []],
        'form' => ['form_id' => null],
        'columns' => ['columns' => 2, 'gap' => 'medium'],
        'testimonials' => ['title' => '', 'items' => []],
        'counters' => ['title' => '', 'card_bg' => '', 'text_color' => '', 'items' => []],
        'team_list' => ['title' => '', 'limit' => 0, 'department' => '', 'group_by_department' => false],
        'projects_list' => ['title' => '', 'limit' => 3],
        'news_latest' => ['title' => 'Последние новости', 'limit' => 3],
        'partners' => ['title' => 'Партнёры', 'items' => []],
        'subscribe' => ['title' => 'Подписка на новости', 'text' => 'Получайте дайджест новостей на почту раз в неделю.', 'button_text' => 'Подписаться'],
        'faq' => ['title' => '', 'search_enabled' => true, 'single_open' => false, 'items' => []],
        'contact_cards' => ['title' => '', 'items' => []],
        'hero' => ['title' => '', 'eyebrow' => '', 'subtitle' => '', 'bg_type' => 'none', 'image' => '', 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'video_url' => '', 'youtube_url' => '', 'bg_color' => '', 'width' => 'full', 'height' => 'regular', 'custom_height' => '720px', 'overlay_enabled' => false, 'overlay_mode' => 'gradient', 'overlay_direction' => 'auto', 'overlay_color' => '#0b1a30', 'overlay_opacity' => 35, 'text_position' => 'left', 'text_width' => '', 'text_color' => '', 'button_color' => '', 'panel_enabled' => false, 'panel_color' => '#0b1a30', 'panel_opacity' => 0, 'button_text' => '', 'button_url' => '', 'button_icon' => '', 'button_icon_image' => '', 'button2_text' => '', 'button2_url' => '', 'button2_icon' => '', 'button2_icon_image' => '', 'video_button_text' => '', 'video_button_url' => '', 'slides' => [], 'autoplay' => 0],
        'cards_grid' => ['variant' => 'icon', 'title' => '', 'all_text' => '', 'all_url' => '', 'columns' => 5, 'card_bg' => '', 'text_color' => '', 'source' => 'manual', 'limit' => 6, 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'items' => []],
        'media_gallery' => ['title' => '', 'all_text' => '', 'all_url' => '', 'source' => 'manual', 'limit' => 8, 'items' => []],
        'news_feature' => ['title' => 'Новости и аналитика', 'all_text' => 'Все новости', 'all_url' => '', 'limit' => 6],
        'person_cards' => ['title' => '', 'all_text' => '', 'all_url' => '', 'items' => []],
        'timeline' => ['title' => '', 'items' => [], 'button_text' => '', 'button_url' => '', 'cta_title' => '', 'cta_text' => '', 'cta_button_text' => '', 'cta_button_url' => '', 'cta_image' => ''],
        'news_docs' => ['news_title' => 'Актуальные новости', 'news_all_text' => 'Все новости', 'news_all_url' => '', 'limit' => 3, 'docs_title' => 'Документы', 'docs_all_text' => 'Все документы', 'docs_all_url' => '', 'docs' => []],
        'person_profile' => ['photo' => '', 'name' => '', 'position' => '', 'text' => '', 'phone' => '', 'phone_label' => 'Приёмная:', 'email' => '', 'email_label' => 'E-mail:', 'button_text' => '', 'button_url' => ''],
        'bio_education' => ['bio_title' => 'Биография', 'bio_text' => '', 'career' => [], 'edu_title' => 'Образование', 'edu_items' => [], 'extra_title' => '', 'extra_text' => '', 'quote_text' => '', 'quote_author' => ''],
        'anchor_nav' => ['items' => []],
        'stages' => ['title' => '', 'all_text' => '', 'all_url' => '', 'items' => []],
        'text_image' => ['title' => '', 'text' => '', 'image' => '', 'image_position' => 'center-center', 'image_position_mobile' => 'center-center', 'image_side' => 'right', 'items' => []],
        'docs_list' => ['variant' => 'grid', 'title' => '', 'all_text' => '', 'all_url' => '', 'columns' => 4, 'search_enabled' => true, 'items' => []],
        'map_point' => ['title' => '', 'image' => '', 'embed_url' => '', 'load_mode' => 'click', 'card_title' => '', 'address' => '', 'copy_enabled' => true, 'button_text' => '', 'button_url' => ''],
        'org_structure' => ['title' => '', 'layout' => 'tree', 'columns' => 4, 'council' => '', 'head_title' => 'Директор', 'head_name' => '', 'head_url' => '', 'side_items' => '', 'branches' => [], 'collapsible' => false, 'notes' => '', 'footnote' => ''],
    ];

    /** Короткие русские названия для сообщений редактору. */
    public const TYPE_LABELS = [
        'text' => 'Текст', 'html' => 'Произвольный HTML', 'cta' => 'Призыв к действию',
        'advantages' => 'Преимущества', 'slider' => 'Слайдер',
        'form' => 'Форма', 'columns' => 'Колонки', 'testimonials' => 'Отзывы',
        'counters' => 'Счётчики', 'team_list' => 'Команда', 'projects_list' => 'Проекты',
        'news_latest' => 'Последние новости', 'partners' => 'Партнёры',
        'subscribe' => 'Подписка', 'faq' => 'Вопросы и ответы', 'contact_cards' => 'Контакты',
        'hero' => 'Обложка',
        'cards_grid' => 'Карточки', 'media_gallery' => 'Медиагалерея',
        'news_feature' => 'Новости и аналитика', 'person_cards' => 'Карточки персон', 'timeline' => 'Хронология',
        'news_docs' => 'Новости и документы', 'person_profile' => 'Профиль персоны',
        'bio_education' => 'Биография и образование',
        'anchor_nav' => 'Якорная навигация', 'stages' => 'Этапы', 'text_image' => 'Текст с фото',
        'docs_list' => 'Список документов', 'map_point' => 'Карта', 'org_structure' => 'Оргструктура',
    ];

    /**
     * Более подробные подписи только там, где список добавления блока требует
     * пояснения. Остальные берутся из TYPE_LABELS.
     */
    private const EDITOR_LABEL_OVERRIDES = [
        'cta' => 'Призыв к действию (CTA)',
        'team_list' => 'Список команды',
        'projects_list' => 'Список проектов',
        'partners' => 'Партнёры (логотипы)',
        'subscribe' => 'Подписка на дайджест',
        'faq' => 'FAQ / аккордеон',
        'contact_cards' => 'Контактные карточки',
        'hero' => 'Герой (титул + фото/видео)',
        'cards_grid' => 'Карточки (иконки / фото / категории)',
        'media_gallery' => 'Медиа-галерея (видео/фото)',
        'news_feature' => 'Новости и аналитика (крупная + список)',
        'person_cards' => 'Руководство (карточки персон)',
        'timeline' => 'История (таймлайн + CTA-карточка)',
        'news_docs' => 'Новости + документы (2 колонки)',
        'person_profile' => 'Профиль руководителя',
        'bio_education' => 'Биография + образование',
        'anchor_nav' => 'Якорная навигация (вкладки)',
        'stages' => 'Этапы реализации (таймлайн)',
        'text_image' => 'Текст + фото (о проекте)',
        'docs_list' => 'Документы (сетка)',
        'map_point' => 'Карта с меткой',
        'org_structure' => 'Структура организации (оргсхема)',
    ];

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::DEFAULTS);
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::DEFAULTS);
    }

    /** @return array<string, mixed> */
    public static function defaultsFor(string $type): array
    {
        return self::DEFAULTS[$type] ?? [];
    }

    /** @return array<string, string> */
    public static function editorLabels(): array
    {
        return array_replace(self::TYPE_LABELS, self::EDITOR_LABEL_OVERRIDES);
    }

    public static function templateFile(string $type): ?string
    {
        if (!self::has($type) || $type === 'columns') {
            return null;
        }

        return dirname(__DIR__, 2) . '/templates/blocks/' . $type . '.php';
    }
}
