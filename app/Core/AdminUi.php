<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Language;

/**
 * Хелперы разметки админки. Пока — единое поле выбора изображения с превью и
 * выбором из медиабиблиотеки (используется во всех типах контента).
 */
final class AdminUi
{
    /** Линейные иконки (24px, stroke=currentColor) для кнопок, карточек и меток. */
    private const ICONS = [
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'home' => '<path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5L22 3z"/>',
        'reset' => '<path d="M21 12a9 9 0 1 1-2.64-6.36M21 3v6h-6"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/>',
        'maximize' => '<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M21 16v5h-5"/>',
        'logo' => '<path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="m3 7 9 5 9-5M12 12v10"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'block' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M15 21V9"/>',
        'send' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        'telegram' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-13h4v2a6 6 0 0 1 2-2z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'media' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'tender' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'seo' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'arrow-up' => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
        'arrow-down' => '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>',
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'warning' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'code' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'stats' => '<rect x="3" y="12" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="12" rx="1"/><rect x="17" y="4" width="4" height="16" rx="1"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'columns' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/>',
        'list' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5"/><circle cx="3.5" cy="12" r="1.5"/><circle cx="3.5" cy="18" r="1.5"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'palette' => '<circle cx="13.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="12" r="2.5"/><circle cx="17" cy="14" r="2.5"/><path d="M12 22a10 10 0 1 1 10-10c0 2-1.5 3-3 3h-2a2 2 0 0 0-1 3.7A2 2 0 0 1 12 22Z"/>',
        'type' => '<path d="M4 7V4h16v3M9 20h6M12 4v16"/>',
        'sliders' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
        'dashboard' => '<path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/>',
        'news' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/>',
        'pages' => '<path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/>',
        'projects' => '<path d="M3 7h7l2 2h9v11H3z"/>',
        'forms' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'files' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M5 18l5-5 4 4 3-3 2 2"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'widgets' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'header' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/>',
        'footer' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15h18"/>',
        'performance' => '<path d="M13 2 3 14h7l-1 8 10-12h-7z"/>',
        'languages' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'content_types' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'social' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.5 13.5l7 4M15.5 6.5l-7 4"/>',
        'webhooks' => '<path d="M13 3 4 14h7l-1 7 9-11h-7z"/>',
        'profile' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'repository' => '<path d="M12 2 4 6v6c0 5 3.4 7.7 8 10 4.6-2.3 8-5 8-10V6l-8-4Z"/><path d="m9 12 2 2 4-4"/>',
        'design' => '<circle cx="13.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="12" r="2.5"/><circle cx="17" cy="14" r="2.5"/><path d="M12 22a10 10 0 1 1 10-10c0 2-1.5 3-3 3h-2a2 2 0 0 0-1 3.7A2 2 0 0 1 12 22Z"/>',
        'albums' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m5 17 4-4 3 3 3-3 4 4"/>',
        'videos' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m10 9 5 3-5 3z"/>',
        'redirects' => '<path d="M4 7h10a5 5 0 0 1 5 5v5"/><path d="m15 13 4 4 4-4"/>',
        'subscribers' => '<path d="M4 5h16v14H4z"/><path d="m4 7 8 6 8-6"/>',
        'audit' => '<path d="M9 3h6l1 3h3v15H5V6h3z"/><path d="M9 11h6M9 15h4"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'panel-left' => '<path d="m14 18-6-6 6-6"/><path d="M20 5v14"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'button' => '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M8 12h8"/>',
        'pause' => '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>',
        'theme' => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
        'a11y' => '<circle cx="12" cy="5" r="2"/><path d="M4 9h16M12 9v6m0 0-3.5 6M12 15l3.5 6"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'email' => '<path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/>',
        'snippet' => '<path d="m8 8-4 4 4 4m8-8 4 4-4 4M14 5l-4 14"/>',
        'divider' => '<path d="M12 4v16"/>',
        'spacer' => '<path d="M4 12h16M4 12l3-3M4 12l3 3M20 12l-3-3M20 12l-3 3"/>',
        'space' => '<path d="M6 5v14M18 5v14"/>',
        'grip' => '<circle cx="8" cy="5" r="1.6" fill="currentColor" stroke="none"/><circle cx="16" cy="5" r="1.6" fill="currentColor" stroke="none"/><circle cx="8" cy="12" r="1.6" fill="currentColor" stroke="none"/><circle cx="16" cy="12" r="1.6" fill="currentColor" stroke="none"/><circle cx="8" cy="19" r="1.6" fill="currentColor" stroke="none"/><circle cx="16" cy="19" r="1.6" fill="currentColor" stroke="none"/>',
        'sparkles' => '<path d="m12 3-1.2 3.2L8 8l2.8 1.8L12 13l1.2-3.2L16 8l-2.8-1.8L12 3Z"/><path d="m19 13-.8 2.2L16 16l2.2.8L19 19l.8-2.2L22 16l-2.2-.8L19 13Z"/><path d="m5 14-1 2.5L2 18l2 1.5L5 22l1-2.5L8 18l-2-1.5L5 14Z"/>',
    ];

    /** Возвращает SVG-иконку для конкретного типа блока. */
    public static function blockIcon(string $type, int $size = 18): string
    {
        $iconMap = [
            'hero' => 'layout',
            'text' => 'document',
            'html' => 'code',
            'cta' => 'send',
            'advantages' => 'check',
            'slider' => 'media',
            'gallery' => 'media',
            'form' => 'send',
            'columns' => 'columns',
            'testimonials' => 'info',
            'counters' => 'stats',
            'team_list' => 'users',
            'projects_list' => 'briefcase',
            'news_latest' => 'document',
            'partners' => 'shield',
            'banner' => 'layout',
            'subscribe' => 'send',
            'faq' => 'info',
            'contact_cards' => 'user',
            'categories_grid' => 'grid',
            'media_materials' => 'media',
            'cards_grid' => 'grid',
            'image_cards' => 'media',
            'media_gallery' => 'media',
            'news_feature' => 'document',
            'person_cards' => 'users',
            'timeline' => 'calendar',
            'news_docs' => 'document',
            'cta_band' => 'send',
            'person_profile' => 'user',
            'feature_band' => 'check',
            'bio_education' => 'user',
            'anchor_nav' => 'list',
            'stages' => 'calendar',
            'text_image' => 'document',
            'docs_list' => 'document',
            'map_point' => 'globe',
            'org_structure' => 'users',
        ];

        $iconName = $iconMap[$type] ?? 'block';
        return self::icon($iconName, $size);
    }

    /**
     * Инлайновая иконка для кнопки/метки. Неизвестное имя — пустая строка
     * (кнопка просто останется без иконки).
     */
    public static function icon(
        string $name,
        int $size = 16,
        string $class = 'btn__icon',
        float $strokeWidth = 2.0
    ): string
    {
        $inner = self::ICONS[$name] ?? '';
        if ($inner === '') {
            return '';
        }

        $stroke = rtrim(rtrim(number_format($strokeWidth, 2, '.', ''), '0'), '.');
        return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
            . 'fill="none" stroke="currentColor" stroke-width="' . $stroke . '" stroke-linecap="round" stroke-linejoin="round" '
            . 'aria-hidden="true" focusable="false">' . $inner . '</svg>';
    }

    /** Каталог популярных иконок AdminUI с русскими подписями для селекторов меню и блоков. */
    public static function iconCatalog(): array
    {
        return [
            'home' => 'Главная / Дом',
            'document' => 'Документ / Страница',
            'news' => 'Новости / Публикации',
            'projects' => 'Проекты / Портфолио',
            'phone' => 'Телефон / Контакты',
            'email' => 'Почта / Письма',
            'globe' => 'Глобус / Сайт / Язык',
            'calendar' => 'Календарь / События',
            'briefcase' => 'Дела / Услуги',
            'tender' => 'Тендеры / Закупки',
            'users' => 'Команда / Люди',
            'info' => 'Информация / Справка',
            'shield' => 'Безопасность / Защита',
            'search' => 'Поиск по сайту',
            'stats' => 'Статистика / Аналитика',
            'media' => 'Медиа / Галерея',
            'image' => 'Изображение / Фото',
            'videos' => 'Видео / Медиатека',
            'files' => 'Файлы / Документы',
            'settings' => 'Настройки',
            'palette' => 'Дизайн / Стили',
            'grid' => 'Сетка / Разделы',
            'list' => 'Список / Каталог',
            'external' => 'Внешняя ссылка',
            'check' => 'Галочка / Успех',
            'sparkles' => 'Акцент / Важное',
            'user' => 'Личный кабинет',
            'code' => 'Разработка / Код',
            'social' => 'Соцсети',
            'clock' => 'Часы / Время',
            'a11y' => 'Доступность',
        ];
    }

    public static function navigationIcon(string $name): string
    {
        $name = [
            'team' => 'users',
            'security' => 'shield',
        ][$name] ?? $name;

        return self::icon($name, 18, 'admin-nav-item__icon', 1.7);
    }

    /**
     * Заголовок карточки с единой системой иконок UI/UX Pro Max.
     */
    public static function cardHeader(string $title, string $iconName = 'info', string $color = 'var(--admin-accent)', string $actionHtml = ''): string
    {
        $html = '<div class="admin-card-header">';
        $html .= '<div class="admin-card-header__title">';
        $html .= '<span class="admin-card-header__icon" style="--admin-card-header-color:' . htmlspecialchars($color, ENT_QUOTES) . ';">' . self::icon($iconName, 22) . '</span>';
        $html .= '<h3>' . htmlspecialchars($title, ENT_QUOTES) . '</h3>';
        $html .= '</div>';
        if ($actionHtml !== '') {
            $html .= '<div>' . $actionHtml . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Поле изображения: превью + URL-инпут + кнопка «Медиабиблиотека» + очистка,
     * опционально с companion-инпутом загрузки файла (FileReader-превью).
     *
     * @param string $urlName  имя поля со ссылкой (то, что читает контроллер)
     * @param string $urlValue текущее значение (URL)
     * @param array{label?:string,hint?:string,file?:?string,accept?:string,id?:string} $opts
     */
    public static function imageField(string $urlName, string $urlValue, array $opts = []): string
    {
        $label = $opts['label'] ?? 'Изображение';
        $hint = $opts['hint'] ?? '';
        $fileName = $opts['file'] ?? null;
        $accept = $opts['accept'] ?? 'image/*';
        $id = $opts['id'] ?? 'imgfld_' . preg_replace('/[^a-z0-9_]/i', '_', $urlName);

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
        $hasImg = trim($urlValue) !== '';

        $html = '<div class="form-field image-field" data-image-field>';
        $html .= '<label for="' . $esc($id) . '">' . $esc($label) . '</label>';
        $html .= '<div class="image-field__row">';

        // Превью.
        $html .= '<div class="image-field__preview" data-image-preview>';
        if ($hasImg) {
            $html .= '<img src="' . $esc($urlValue) . '" alt="">';
        } else {
            $html .= '<span class="image-field__placeholder" aria-hidden="true">'
                . '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
                . '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M5 18l5-5 4 4 3-3 2 2"/></svg></span>';
        }
        $html .= '</div>';

        // Управление.
        $html .= '<div class="image-field__main">';
        $html .= '<div class="image-field__controls">';
        $html .= '<input type="text" id="' . $esc($id) . '" name="' . $esc($urlName) . '" value="' . $esc($urlValue) . '"'
            . ' data-image-input placeholder="URL или выбор из медиабиблиотеки">';
        $html .= '<button type="button" class="btn btn--small" data-media-pick data-media-target="#' . $esc($id) . '">Медиабиблиотека</button>';
        $html .= '<button type="button" class="btn btn--small" data-image-clear title="Очистить" aria-label="Очистить">&times;</button>';
        $html .= '</div>';

        if ($fileName !== null) {
            $html .= '<div class="image-field__upload">';
            $html .= '<input type="file" name="' . $esc($fileName) . '" accept="' . $esc($accept) . '" data-image-file>';
            $html .= '<span class="form-hint">…или загрузите файл с компьютера.</span>';
            $html .= '</div>';
        }
        if ($hint !== '') {
            $html .= '<span class="form-hint">' . $esc($hint) . '</span>';
        }
        $html .= '</div></div></div>';

        return $html;
    }

    /**
     * Поле выбора цвета с галочкой «по умолчанию». Значение читается
     * контроллером через BlockController::color() — при включённой галочке
     * $name_off цвет сбрасывается (color-input всегда шлёт значение).
     */
    public static function colorField(string $name, ?string $value, string $label, string $defaultHex = '#173a63', string $offLabel = 'По умолчанию'): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
        $val = ($value !== null && $value !== '') ? $value : $defaultHex;
        $off = ($value === null || $value === '');

        $html = '<div class="form-field colorfield">';
        $html .= '<label for="' . $esc($name) . '">' . $esc($label) . '</label>';
        $html .= '<input type="color" id="' . $esc($name) . '" name="' . $esc($name) . '" value="' . $esc($val) . '">';
        $html .= '<label class="colorfield__off"><input type="checkbox" name="' . $esc($name) . '_off" value="1"'
            . ($off ? ' checked' : '') . '> ' . $esc($offLabel) . '</label>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Интерактивный умный виджет выбора фокальной точки (UI/UX Pro Max).
     * Позволяет кликать прямо по изображению, выбирать готовые пресеты из сетки 3x3
     * и мгновенно видеть точное кадрирование.
     */
    public static function focalPointField(string $xName = 'focal_x', string $yName = 'focal_y', ?string $xVal = '', ?string $yVal = '', string $imageTargetInput = 'image_url'): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
        $x = is_numeric($xVal) ? max(0, min(100, (int) $xVal)) : 50;
        $y = is_numeric($yVal) ? max(0, min(100, (int) $yVal)) : 50;

        $html = '<div class="form-field focal-field" data-focal-picker data-image-input-name="' . $esc($imageTargetInput) . '">';
        $html .= '<label class="focal-field__label u-inline-e925a44577">Фокальная точка обложки <span class="form-hint">(для мобильного кадрирования)</span></label>';

        $html .= '<div class="focal-field__container">';

        // Визуальная интерактивная область клика с маркером.
        $html .= '<div class="focal-field__preview" data-focal-canvas title="Кликните по обложке для установки точки фокусировки">';
        $html .= '<img class="u-inline-c8be1ccba6" src="" alt="" data-focal-img>';
        $html .= '<div class="focal-field__placeholder" data-focal-placeholder><span>Кликните по фото для установки точки фокусировки</span></div>';
        $html .= '<div class="focal-field__pin" data-focal-pin style="--focal-x:' . $x . '%;--focal-y:' . $y . '%;">🎯</div>';
        $html .= '</div>';

        // Панель быстрой сетки 3x3 и числовых полей.
        $html .= '<div class="focal-field__controls">';
        $html .= '<div class="focal-field__grid">';
        $presets = [
            ['0', '0', '↖', 'Сверху слева (0/0)'],
            ['50', '0', '⬆', 'Сверху по центру (50/0)'],
            ['100', '0', '↗', 'Сверху справа (100/0)'],
            ['0', '50', '⬅', 'Слева (0/50)'],
            ['50', '50', '🎯', 'По центру (50/50)'],
            ['100', '50', '➡', 'Справа (100/50)'],
            ['0', '100', '↙', 'Снизу слева (0/100)'],
            ['50', '100', '⬇', 'Снизу по центру (50/100)'],
            ['100', '100', '↘', 'Снизу справа (100/100)'],
        ];
        foreach ($presets as [$px, $py, $icon, $title]) {
            $active = ($x === (int) $px && $y === (int) $py) ? ' is-active' : '';
            $html .= '<button type="button" class="btn btn--small btn--icon focal-field__preset' . $active . '" data-focal-set-x="' . $px . '" data-focal-set-y="' . $py . '" title="' . $title . '" aria-label="' . $title . '">' . $icon . '</button>';
        }
        $html .= '</div>';

        $html .= '<div class="focal-field__inputs">';
        $html .= '<div class="focal-field__input-wrap"><label>X (%)</label><input type="number" name="' . $esc($xName) . '" data-focal-input-x min="0" max="100" value="' . ($xVal !== '' ? $esc((string) $xVal) : '') . '" placeholder="50"></div>';
        $html .= '<div class="focal-field__input-wrap"><label>Y (%)</label><input type="number" name="' . $esc($yName) . '" data-focal-input-y min="0" max="100" value="' . ($yVal !== '' ? $esc((string) $yVal) : '') . '" placeholder="50"></div>';
        $html .= '<button type="button" class="btn btn--small" data-focal-reset title="Сбросить на 50/50">Сброс</button>';
        $html .= '</div>';

        $html .= '</div>'; // controls
        $html .= '</div>'; // container
        $html .= '<span class="form-hint">Кликните мышью по фотографии выше или выберите быстрое положение на сетке.</span>';
        $html .= '</div>'; // form-field

        return $html;
    }

    /**
     * Рендерит горизонтальную панель переключения языков (subsubsub):
     * Все языки (349) | Русский (151) | O‘zbekcha (160) | English (38)
     */
    public static function renderLangSubsubsub(string $currentLang, array $counts, string $baseUrl, array $params = []): string
    {
        $languages = Language::active();
        $items = [];

        // Вкладка "Все языки"
        $allParams = $params;
        $allParams['lang'] = 'all';
        unset($allParams['page']);
        $allUrl = $baseUrl . ($allParams !== [] ? '?' . http_build_query($allParams) : '');
        $allCount = (int) ($counts['all'] ?? 0);
        $isAllActive = ($currentLang === '' || $currentLang === 'all');

        $items[] = sprintf(
            '<a href="%s" class="lang-filter-link %s">Все языки <span class="lang-filter-count">%d</span></a>',
            htmlspecialchars($allUrl, ENT_QUOTES),
            $isAllActive ? 'is-active' : '',
            $allCount
        );

        foreach ($languages as $lang) {
            $code = (string) $lang['code'];
            $name = (string) $lang['name'];
            $count = (int) ($counts[$code] ?? 0);

            $langParams = $params;
            $langParams['lang'] = $code;
            unset($langParams['page']);
            $url = $baseUrl . '?' . http_build_query($langParams);
            $isActive = ($currentLang === $code);

            $items[] = sprintf(
                '<a href="%s" class="lang-filter-link %s">%s <span class="lang-filter-count">%d</span></a>',
                htmlspecialchars($url, ENT_QUOTES),
                $isActive ? 'is-active' : '',
                htmlspecialchars($name, ENT_QUOTES),
                $count
            );
        }

        return '<div class="lang-subsubsub-bar u-inline-c0f5c28d5f">'
            . implode('<span class="u-inline-16a33d931a">|</span>', $items)
            . '</div>';
    }

    /**
     * Рендерит интерактивный живой предпросмотр SEO и карточек Telegram / соцсетей.
     */
    public static function seoPreviewBox(array $data = []): string
    {
        $siteName = htmlspecialchars(\App\Models\Setting::get('site_name', 'ASDR CMS'), ENT_QUOTES);
        $baseUrl = \App\Core\AppUrl::base();
        $domain = parse_url($baseUrl, PHP_URL_HOST) ?: 'agency.gov.uz';

        $title = htmlspecialchars((string) ($data['title'] ?? 'Заголовок вашей новости'), ENT_QUOTES);
        $excerpt = htmlspecialchars((string) ($data['excerpt'] ?? $data['meta_description'] ?? 'Краткое описание или лид новости отображается здесь...'), ENT_QUOTES);
        $image = htmlspecialchars((string) ($data['image_url'] ?? ''), ENT_QUOTES);
        $imageClass = $image === '' ? ' is-hidden' : '';
        $placeholderClass = $image !== '' ? ' is-hidden' : '';

        return <<<HTML
<div class="seo-live-preview u-inline-136e5b68ed">
    <div class="u-inline-7d8f4cd990">
        <div class="u-inline-a359bfa933">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Интерактивный SEO & Соцсети Предпросмотр
        </div>
        <div class="seo-preview-tabs u-inline-1d8943fa86">
            <button type="button" class="btn btn--small btn--outline is-active u-inline-94db95e1b0" data-seo-tab="google">🔍 Поисковики (Google/Yandex)</button>
            <button type="button" class="btn btn--small btn--outline u-inline-94db95e1b0" data-seo-tab="social">💬 Telegram & Соцсети</button>
        </div>
    </div>

    <!-- 1. Google / Yandex Card -->
    <div class="seo-preview-panel u-inline-21d91b7027" data-seo-panel="google">
        <div class="u-inline-b75b1843b5">
            <div class="u-inline-d60c3b2481">
                <span class="u-inline-0484dc067f">A</span>
                <span class="u-inline-43ae67e204">{$siteName}</span>
                <span class="u-inline-ab7dab3c79">https://{$domain} › news › ...</span>
            </div>
            <div class="u-inline-152ccfa629" data-seo-google-title>
                {$title}
            </div>
            <div class="u-inline-f8b0a0e950" data-seo-google-desc>
                {$excerpt}
            </div>
        </div>
        <div class="u-inline-6804b2c0e5">
            <span>Заголовок: <strong data-seo-title-count>0</strong> / 60 симв.</span>
            <span>Описание: <strong data-seo-desc-count>0</strong> / 160 симв.</span>
        </div>
    </div>

    <!-- 2. Telegram / Social Card -->
    <div class="seo-preview-panel u-inline-c8be1ccba6" data-seo-panel="social">
        <div class="u-inline-99bcb5a34b">
            <div class="seo-social-img-wrap u-inline-0d855cb4f4">
                <img class="seo-social-image{$imageClass}" src="{$image}" alt="" data-seo-social-img>
                <span class="seo-social-placeholder{$placeholderClass}" data-seo-social-noimg>🖼️ Фотография новости</span>
            </div>
            <div class="u-inline-4680c65b6a">
                <div class="u-inline-7235472cc2" data-seo-social-title>{$title}</div>
                <div class="u-inline-4774201452" data-seo-social-desc>{$excerpt}</div>
                <div class="u-inline-2ce9644849">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    {$domain}
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
    }
}
