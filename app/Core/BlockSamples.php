<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Пример наполнения для нового блока.
 *
 * Пустой блок ничего не объясняет: редактор добавляет «Этапы» или «Хронологию»
 * и видит пустое место, не понимая, из чего блок состоит. Поэтому при создании
 * блок приходит с образцом — текстами-заготовками и парой строк повторителя,
 * которые остаётся заменить своими.
 *
 * Правила образцов:
 *  - только обычный текст: поля, которые шаблон экранирует, не должны получать
 *    разметку (на этом уже обожглись в готовых сборках);
 *  - кнопки — либо с рабочей ссылкой, либо без подписи: кнопка с подписью, но
 *    без адреса на сайте не выводится;
 *  - изображения не подставляем: чужие пути ведут в никуда, а поле в форме и
 *    так видно.
 */
final class BlockSamples
{
    /** Текст «замените меня» единым тоном для всех блоков. */
    private const LEAD = 'Краткое пояснение в одну-две строки — замените своим текстом.';

    /**
     * @param string|null $lang язык стека блока: ссылки образца ведут в раздел
     *                          того же языка, иначе UZ-блок ссылался бы на
     *                          русскую версию
     * @return array<string,mixed> данные образца ([] — для типа образца нет)
     */
    public static function for(string $type, ?string $lang = null): array
    {
        return self::all($lang)[$type] ?? [];
    }

    /** @return array<string, array<string,mixed>> */
    public static function all(?string $lang = null): array
    {
        $person = static fn (string $role): array => ['name' => 'Фамилия Имя Отчество', 'role' => $role, 'position' => $role, 'photo' => '', 'url' => ''];
        // Раздел новостей есть на любом сайте — это безопасный адрес для
        // кнопок образца (кнопка без ссылки на сайте не отображается).
        $news = Locale::url('news', $lang);

        return [
            'text' => ['title' => 'Заголовок раздела', 'content' => '<p>' . self::LEAD . '</p>'],
            'html' => ['html' => '<p>Здесь можно вставить произвольный HTML: таблицу, виджет или код встраивания.</p>'],
            'cta' => [
                'title' => 'Заголовок призыва к действию',
                'text' => self::LEAD,
                'button_text' => 'Перейти к новостям',
                'button_url' => $news,
            ],
            'advantages' => ['title' => 'Преимущества', 'items' => [
                ['title' => 'Первое преимущество', 'text' => self::LEAD, 'icon_svg' => ''],
                ['title' => 'Второе преимущество', 'text' => self::LEAD, 'icon_svg' => ''],
                ['title' => 'Третье преимущество', 'text' => self::LEAD, 'icon_svg' => ''],
            ]],
            // Слайд без изображения не сохраняется (так устроен блок), поэтому
            // строку-образец не подставляем: она исчезла бы при первом
            // сохранении и только запутала бы.
            'slider' => ['slides' => []],
            'gallery' => ['title' => 'Фотогалерея', 'images' => []],
            'form' => ['form_id' => null],
            'columns' => ['columns' => 2, 'gap' => 'medium'],
            'testimonials' => ['title' => 'Отзывы', 'items' => [
                ['quote' => 'Короткая цитата о работе организации.', 'name' => 'Фамилия Имя', 'company' => 'Организация', 'photo' => ''],
                ['quote' => 'Вторая цитата — замените своей.', 'name' => 'Фамилия Имя', 'company' => 'Организация', 'photo' => ''],
            ]],
            'counters' => ['title' => 'В цифрах', 'items' => [
                ['value' => '120', 'suffix' => '+', 'label' => 'реализованных проектов', 'icon_svg' => ''],
                ['value' => '14', 'suffix' => '', 'label' => 'регионов охвата', 'icon_svg' => ''],
                ['value' => '35', 'suffix' => ' млрд', 'label' => 'привлечённых инвестиций', 'icon_svg' => ''],
            ]],
            // Блоки-обёртки наполняются из базы: образцу достаточно заголовка.
            'team_list' => ['title' => 'Руководящий состав', 'limit' => 0],
            'projects_list' => ['title' => 'Проекты', 'limit' => 3],
            'news_latest' => ['title' => 'Последние новости', 'limit' => 3],
            'news_feature' => ['title' => 'Новости и аналитика', 'all_text' => 'Все новости', 'limit' => 6],
            'news_docs' => [
                'news_title' => 'Актуальные новости', 'news_all_text' => 'Все новости', 'limit' => 3,
                'docs_title' => 'Документы', 'docs_all_text' => '',
                'docs' => [
                    ['title' => 'Название документа', 'meta' => 'PDF', 'url' => ''],
                    ['title' => 'Второй документ', 'meta' => 'PDF', 'url' => ''],
                ],
            ],
            'partners' => ['title' => 'Партнёры', 'items' => [
                ['name' => 'Название организации', 'logo' => '', 'url' => ''],
                ['name' => 'Вторая организация', 'logo' => '', 'url' => ''],
            ]],
            'banner' => [
                'title' => 'Заголовок баннера',
                'text' => self::LEAD,
                'image' => '',
                'button_text' => 'Подробнее',
                'button_url' => $news,
            ],
            'subscribe' => [
                'title' => 'Подписка на новости',
                'text' => 'Получайте дайджест новостей на почту раз в неделю.',
                'button_text' => 'Подписаться',
            ],
            'faq' => ['title' => 'Вопросы и ответы', 'items' => [
                ['question' => 'Как подать обращение?', 'answer' => 'Опишите порядок подачи и срок рассмотрения.'],
                ['question' => 'В какие сроки поступит ответ?', 'answer' => 'Укажите срок и ссылку на нормативный акт.'],
            ]],
            'contact_cards' => ['title' => 'Контакты', 'items' => [
                ['title' => 'Приёмная', 'lines' => "+998 (71) 000-00-00\nПн–Пт, 9:00–18:00", 'link_text' => '', 'link_url' => '', 'icon_svg' => ''],
                ['title' => 'Электронная почта', 'lines' => 'info@example.uz', 'link_text' => 'Написать', 'link_url' => 'mailto:info@example.uz', 'icon_svg' => ''],
            ]],
            'hero' => [
                'eyebrow' => 'Раздел сайта',
                'title' => 'Заголовок страницы',
                'subtitle' => 'Одно предложение о содержании страницы.',
                'height' => 'regular',
                'width' => 'full',
                'text_position' => 'left',
                'overlay_opacity' => 55,
            ],
            'categories_grid' => ['title' => 'Направления', 'items' => [
                ['title' => 'Название направления', 'label' => 'Название направления', 'url' => $news, 'icon_svg' => ''],
                ['title' => 'Второе направление', 'label' => 'Второе направление', 'url' => $news, 'icon_svg' => ''],
                ['title' => 'Третье направление', 'label' => 'Третье направление', 'url' => $news, 'icon_svg' => ''],
            ]],
            'media_materials' => ['title' => 'Медиаматериалы', 'items' => [
                ['label' => 'Пресс-релиз', 'action' => 'Скачать', 'url' => $news, 'icon_svg' => ''],
                ['label' => 'Фотоматериалы', 'action' => 'Открыть', 'url' => $news, 'icon_svg' => ''],
            ]],
            'cards_grid' => ['title' => 'Разделы', 'columns' => 3, 'items' => [
                ['title' => 'Название карточки', 'text' => self::LEAD, 'url' => $news, 'icon_svg' => ''],
                ['title' => 'Вторая карточка', 'text' => self::LEAD, 'url' => $news, 'icon_svg' => ''],
                ['title' => 'Третья карточка', 'text' => self::LEAD, 'url' => $news, 'icon_svg' => ''],
            ]],
            'image_cards' => ['title' => 'Карточки с фото', 'source' => 'manual', 'limit' => 6, 'items' => [
                ['title' => 'Название карточки', 'image' => '', 'url' => $news],
                ['title' => 'Вторая карточка', 'image' => '', 'url' => $news],
            ]],
            'media_gallery' => ['title' => 'Медиа', 'source' => 'manual', 'limit' => 8, 'items' => [
                ['kind' => 'photo', 'title' => 'Название материала', 'image' => '', 'url' => $news, 'meta' => '', 'text' => ''],
                ['kind' => 'photo', 'title' => 'Второй материал', 'image' => '', 'url' => $news, 'meta' => '', 'text' => ''],
            ]],
            'person_cards' => ['title' => 'Руководство', 'items' => [
                $person('Директор'), $person('Заместитель директора'),
            ]],
            'timeline' => ['title' => 'Хронология', 'items' => [
                ['year' => '2024', 'text' => 'Событие или этап — замените своим описанием.'],
                ['year' => '2025', 'text' => 'Следующий этап.'],
                ['year' => '2026', 'text' => 'Текущий этап.'],
            ]],
            'cta_band' => [
                'title' => 'Заголовок призыва',
                'text' => self::LEAD,
                'button_text' => 'Перейти к новостям',
                'button_url' => $news,
                'icon_svg' => '',
            ],
            'person_profile' => [
                'photo' => '', 'name' => 'Фамилия Имя Отчество', 'position' => 'Должность',
                'text' => self::LEAD,
                'phone' => '+998 (71) 000-00-00', 'phone_label' => 'Приёмная:',
                'email' => 'info@example.uz', 'email_label' => 'E-mail:',
                'button_text' => '', 'button_url' => '',
            ],
            'feature_band' => ['title' => 'Основные направления', 'items' => [
                ['title' => 'Первое направление', 'text' => self::LEAD, 'icon_svg' => ''],
                ['title' => 'Второе направление', 'text' => self::LEAD, 'icon_svg' => ''],
                ['title' => 'Третье направление', 'text' => self::LEAD, 'icon_svg' => ''],
            ]],
            'bio_education' => [
                'bio_title' => 'Биография',
                'bio_text' => self::LEAD,
                'career' => [
                    ['years' => '2020–2023', 'text' => 'Должность и организация.'],
                    ['years' => 'с 2023', 'text' => 'Текущая должность.'],
                ],
                'edu_title' => 'Образование',
                'edu_items' => [
                    ['years' => '2010–2015', 'org' => 'Название университета', 'title' => 'Специальность', 'text' => 'Специальность'],
                ],
                'extra_title' => '', 'extra_text' => '', 'quote_text' => '', 'quote_author' => '',
            ],
            'anchor_nav' => ['items' => [
                ['label' => 'Первый раздел', 'url' => '#sec-1'],
                ['label' => 'Второй раздел', 'url' => '#sec-2'],
            ]],
            'stages' => ['title' => 'Этапы', 'items' => [
                ['stage' => 'Шаг 1', 'title' => 'Подготовка', 'text' => 'Что происходит на этом этапе.', 'year' => '', 'status' => 'done', 'status_text' => 'Завершён'],
                ['stage' => 'Шаг 2', 'title' => 'Реализация', 'text' => 'Что происходит на этом этапе.', 'year' => '', 'status' => 'active', 'status_text' => 'В работе'],
                ['stage' => 'Шаг 3', 'title' => 'Результат', 'text' => 'Что происходит на этом этапе.', 'year' => '', 'status' => '', 'status_text' => 'Запланирован'],
            ]],
            'text_image' => [
                'title' => 'Заголовок раздела',
                'text' => self::LEAD . "\n\nВторой абзац отделяется пустой строкой.",
                'image' => '',
                'items' => [
                    ['label' => 'Короткий факт', 'icon_svg' => ''],
                    ['label' => 'Второй факт', 'icon_svg' => ''],
                ],
            ],
            'docs_list' => ['title' => 'Документы', 'columns' => 3, 'items' => [
                ['title' => 'Название документа', 'meta' => 'PDF', 'url' => ''],
                ['title' => 'Второй документ', 'meta' => 'PDF', 'url' => ''],
                ['title' => 'Третий документ', 'meta' => 'DOCX', 'url' => ''],
            ]],
            'map_point' => [
                'title' => 'Как добраться',
                'card_title' => 'Главный офис',
                'address' => 'г. Ташкент, ул. Примерная, 1',
                'image' => '', 'embed_url' => '',
                'button_text' => '', 'button_url' => '',
            ],
            'org_structure' => [
                'title' => 'Организационная структура',
                'head_title' => 'Директор',
                'head_name' => 'Фамилия Имя Отчество',
                'head_url' => '',
                'side_items' => "Коллегия\nСоветник директора",
                'branches' => [
                    ['title' => 'Первый заместитель директора', 'name' => '', 'units' => "Отдел планирования\nОтдел анализа"],
                    ['title' => 'Заместитель директора', 'name' => '', 'units' => "Юридический отдел\nОтдел кадров"],
                ],
                'footnote' => '',
            ],
        ];
    }
}
