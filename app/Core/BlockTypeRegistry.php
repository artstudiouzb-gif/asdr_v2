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
        'cta' => ['title' => '', 'text' => '', 'button_text' => '', 'button_url' => '', 'bg_color' => '', 'text_color' => '', 'button_color' => ''],
        'advantages' => ['title' => '', 'items' => []],
        'slider' => ['slides' => []],
        'gallery' => ['title' => '', 'images' => []],
        'form' => ['form_id' => null],
        'columns' => ['columns' => 2, 'gap' => 'medium'],
        'testimonials' => ['title' => '', 'items' => []],
        'counters' => ['title' => '', 'card_bg' => '', 'text_color' => '', 'items' => []],
        'team_list' => ['title' => '', 'limit' => 0],
        'projects_list' => ['title' => '', 'limit' => 3],
        'news_latest' => ['title' => 'Последние новости', 'limit' => 3],
        'partners' => ['title' => 'Партнёры', 'items' => []],
        'banner' => ['title' => '', 'text' => '', 'image' => '', 'button_text' => '', 'button_url' => '', 'bg_color' => '', 'text_color' => '', 'button_color' => ''],
        'subscribe' => ['title' => 'Подписка на новости', 'text' => 'Получайте дайджест новостей на почту раз в неделю.', 'button_text' => 'Подписаться'],
        'faq' => ['title' => '', 'items' => []],
        'contact_cards' => ['title' => '', 'items' => []],
        'hero' => ['title' => '', 'eyebrow' => '', 'subtitle' => '', 'bg_type' => '', 'image' => '', 'video_url' => '', 'youtube_url' => '', 'bg_color' => '', 'width' => 'full', 'height' => 'regular', 'custom_height' => '720px', 'overlay_direction' => 'auto', 'overlay_color' => '#0b1a30', 'overlay_end_color' => '#0b1a30', 'overlay_opacity' => 55, 'text_position' => 'left', 'text_width' => '', 'text_color' => '', 'button_color' => '', 'panel_enabled' => false, 'panel_color' => '#0b1a30', 'panel_opacity' => 0, 'button_text' => '', 'button_url' => '', 'button2_text' => '', 'button2_url' => '', 'video_button_text' => '', 'video_button_url' => ''],
        'categories_grid' => ['title' => '', 'items' => []],
        'media_materials' => ['title' => '', 'items' => []],
        'cards_grid' => ['title' => '', 'all_text' => '', 'all_url' => '', 'columns' => 5, 'card_bg' => '', 'text_color' => '', 'items' => []],
        'image_cards' => ['title' => '', 'all_text' => '', 'all_url' => '', 'source' => 'manual', 'limit' => 6, 'items' => []],
        'media_gallery' => ['title' => '', 'all_text' => '', 'all_url' => '', 'source' => 'manual', 'limit' => 8, 'items' => []],
        'news_feature' => ['title' => 'Новости и аналитика', 'all_text' => 'Все новости', 'all_url' => '', 'limit' => 6],
        'person_cards' => ['title' => '', 'all_text' => '', 'all_url' => '', 'items' => []],
        'timeline' => ['title' => '', 'items' => [], 'button_text' => '', 'button_url' => '', 'cta_title' => '', 'cta_text' => '', 'cta_button_text' => '', 'cta_button_url' => '', 'cta_image' => ''],
        'news_docs' => ['news_title' => 'Актуальные новости', 'news_all_text' => 'Все новости', 'news_all_url' => '', 'limit' => 3, 'docs_title' => 'Документы', 'docs_all_text' => 'Все документы', 'docs_all_url' => '', 'docs' => []],
        'cta_band' => ['title' => '', 'text' => '', 'icon_svg' => '', 'button_text' => '', 'button_url' => '', 'bg_color' => '', 'text_color' => '', 'button_color' => ''],
        'person_profile' => ['photo' => '', 'name' => '', 'position' => '', 'text' => '', 'phone' => '', 'phone_label' => 'Приёмная:', 'email' => '', 'email_label' => 'E-mail:', 'button_text' => '', 'button_url' => ''],
        'feature_band' => ['title' => '', 'items' => []],
        'bio_education' => ['bio_title' => 'Биография', 'bio_text' => '', 'career' => [], 'edu_title' => 'Образование', 'edu_items' => [], 'extra_title' => '', 'extra_text' => '', 'quote_text' => '', 'quote_author' => ''],
        'anchor_nav' => ['items' => []],
        'stages' => ['title' => '', 'all_text' => '', 'all_url' => '', 'items' => []],
        'text_image' => ['title' => '', 'text' => '', 'image' => '', 'items' => []],
        'docs_list' => ['title' => '', 'all_text' => '', 'all_url' => '', 'columns' => 4, 'items' => []],
        'map_point' => ['title' => '', 'image' => '', 'embed_url' => '', 'card_title' => '', 'address' => '', 'button_text' => '', 'button_url' => ''],
        'org_structure' => ['title' => '', 'head_title' => 'Директор', 'head_name' => '', 'head_url' => '', 'side_items' => '', 'branches' => [], 'footnote' => ''],
    ];

    /** Короткие русские названия для сообщений редактору. */
    public const TYPE_LABELS = [
        'text' => 'Текст', 'html' => 'Произвольный HTML', 'cta' => 'Призыв к действию',
        'advantages' => 'Преимущества', 'slider' => 'Слайдер', 'gallery' => 'Галерея',
        'form' => 'Форма', 'columns' => 'Колонки', 'testimonials' => 'Отзывы',
        'counters' => 'Счётчики', 'team_list' => 'Команда', 'projects_list' => 'Проекты',
        'news_latest' => 'Последние новости', 'partners' => 'Партнёры', 'banner' => 'Баннер',
        'subscribe' => 'Подписка', 'faq' => 'Вопросы и ответы', 'contact_cards' => 'Контакты',
        'hero' => 'Обложка', 'categories_grid' => 'Сетка категорий', 'media_materials' => 'Медиаматериалы',
        'cards_grid' => 'Сетка карточек', 'image_cards' => 'Карточки с фото', 'media_gallery' => 'Медиагалерея',
        'news_feature' => 'Новости и аналитика', 'person_cards' => 'Карточки персон', 'timeline' => 'Хронология',
        'news_docs' => 'Новости и документы', 'cta_band' => 'Полоса призыва', 'person_profile' => 'Профиль персоны',
        'feature_band' => 'Полоса преимуществ', 'bio_education' => 'Биография и образование',
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
        'banner' => 'Баннер с фоном',
        'subscribe' => 'Подписка на дайджест',
        'faq' => 'FAQ / аккордеон',
        'contact_cards' => 'Контактные карточки',
        'hero' => 'Герой (титул + фото/видео)',
        'cards_grid' => 'Карточки-направления (иконка+текст)',
        'image_cards' => 'Карточки с фото (проекты)',
        'media_gallery' => 'Медиа-галерея (видео/фото)',
        'news_feature' => 'Новости и аналитика (крупная + список)',
        'person_cards' => 'Руководство (карточки персон)',
        'timeline' => 'История (таймлайн + CTA-карточка)',
        'news_docs' => 'Новости + документы (2 колонки)',
        'cta_band' => 'CTA-полоса (обратная связь)',
        'person_profile' => 'Профиль руководителя',
        'feature_band' => 'Полоса компетенций (тёмная)',
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
