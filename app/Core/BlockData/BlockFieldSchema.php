<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\AdminUi;
use App\Core\HtmlSanitizer;
use App\Core\Icon;
use App\Core\TextProcessor;

/**
 * Схемы настроек блоков: одно описание поля вместо четырёх его копий.
 *
 * Тип, попавший сюда, получает умолчания, поле формы и нормализацию из одного
 * места (см. `Field`). Шаблон такого блока обязан читать `$data` как есть:
 * значение уже проверено, повторный `in_array()` в шаблоне — это вторая копия
 * списка, которая рано или поздно разойдётся с первой.
 *
 * Переезд типов постепенный: пока схемы нет, тип живёт по-старому
 * (`BlockTypeRegistry::BASE_DEFAULTS` + рукописное поле формы + ветка
 * `BlockController::collectData()`).
 *
 * Схема описывает **скалярные** настройки. Мимо неё идут:
 * репитеры (`items` — у строки свой набор полей) и значения, зависящие от
 * другого поля (ширина колонок зависит от их числа). Их умолчания перечислены
 * в EXTRA, а сборка остаётся явной.
 */
final class BlockFieldSchema
{
    /** @var array<string, array<string, mixed>> ключи типа, собираемые мимо схемы */
    private const EXTRA = [
        'columns' => ['ratio' => ''],
        'tabs' => ['items' => []],
        'partners' => ['items' => []],
        'advantages' => ['items' => []],
        'testimonials' => ['items' => []],
        'faq' => ['items' => []],
        'docs_list' => ['items' => []],
        'stages' => ['items' => []],
        'contact_cards' => ['items' => []],
        // Положение иконки у «Иконка и текст» зависит от выравнивания: у
        // блоков, сохранённых до появления поля, пустое значение означает
        // «над текстом при выравнивании по центру, иначе слева». Схема такую
        // связь не выражает, поэтому поле собирается отдельно.
        'icon_text' => ['items' => [], 'icon_position' => ''],
        'person_cards' => ['items' => []],
        'slider' => ['slides' => []],
    ];

    /** @var array<string, array<string, Field>>|null */
    private static ?array $cache = null;

    /** @return array<string, array<string, Field>> */
    public static function all(): array
    {
        return self::$cache ??= [
            'columns' => [
                'columns' => Field::intChoice('Количество колонок', [2, 3, 4], 2),
                'gap' => Field::enum('Промежуток между колонками', [
                    'small' => 'Малый',
                    'medium' => 'Средний',
                    'large' => 'Большой',
                ], 'medium', 'Наполнение колонок настраивается на странице: кнопка «+ блок» в каждой колонке.'),
                'valign' => Field::enum('Выравнивание содержимого по высоте', [
                    'stretch' => 'Растянуть — колонки одной высоты',
                    'top' => 'По верху',
                    'center' => 'По центру',
                    'bottom' => 'По низу',
                ], 'stretch', 'Заметно, когда в колонках разное количество содержимого: «по центру» ставит короткую колонку вровень с серединой длинной.'),
                'mobile_order' => Field::enum('Порядок колонок на телефоне', [
                    'normal' => 'Как в редакторе',
                    'reverse' => 'В обратном порядке',
                ], 'normal', 'Обратный порядок нужен паре «текст + фото»: на широком экране фото слева, а на телефоне первым читается текст.'),
            ],
            'tabs' => [
                'variant' => Field::enum('Вариант отображения', [
                    'segmented' => 'Переключатель — вкладки в общей дорожке',
                    'underline' => 'Подчёркивание — активная вкладка с чертой',
                    'vertical' => 'Список слева, содержимое справа',
                ], 'segmented', 'На телефоне вертикальный вариант тоже показывает вкладки сверху — сбоку для них нет места.'),
                'align' => Field::enum('Положение полосы вкладок', [
                    'left' => 'Слева',
                    'center' => 'По центру',
                    'stretch' => 'На всю ширину',
                ], 'left'),
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'description' => Field::richtext('Описание раздела'),
                'autoplay' => Field::int(
                    'Автоматическое переключение, секунд (0 — выключено)',
                    0,
                    30,
                    0,
                    'Активная вкладка показывает отсчёт до следующей. Переключение прекращается насовсем, как только посетитель выбрал вкладку сам, — и не начинается у тех, кто просил меньше движения.'
                ),
            ],
            'partners' => [
                'variant' => Field::enum('Вариант отображения', [
                    'row' => 'Ряд — логотипы стоят сеткой',
                    'marquee' => 'Бегущая строка — логотипы едут непрерывно',
                ], 'row', 'Бегущая строка идёт сама и останавливается под курсором, при фокусе внутри и у посетителей, которые просили меньше движения. Ей нужно хотя бы три логотипа.'),
                'title' => Field::text('Заголовок, показываемый на сайте', 'Партнёры')->named('title_field'),
                'description' => Field::textarea('Описание раздела'),
                'all_text' => Field::text('Ссылка «Все …» — текст', '', '', 'Все партнёры'),
                'all_url' => Field::url('Ссылка «Все …» — URL'),
                'columns' => Field::intChoice(
                    'Логотипов в ряду',
                    [3, 4, 5, 6, 7, 8],
                    6,
                    'Если логотипов больше, ряд превращается в прокручиваемую полосу. На узких экранах число подбирается автоматически.'
                )->onlyWhen('variant', ['row']),
                'logo_size' => Field::enum('Высота логотипа', [
                    'small' => 'Мелкие',
                    'medium' => 'Средние',
                    'large' => 'Крупные',
                ], 'medium', 'Общая высота выравнивает ряд: логотипы у партнёров разных пропорций.'),
                'grayscale' => Field::bool('Обесцвечивать логотипы, возвращать цвет при наведении', true),
                'autoplay' => Field::int('Автопрокрутка, секунд (0 — выключена)', 0, 30, 0)
                    ->onlyWhen('variant', ['row']),
            ],
            'advantages' => [
                'variant' => Field::enum('Вариант отображения', [
                    'grid' => 'Карточки',
                    'indexed' => 'Карточки с нумерацией',
                    'inline' => 'Иконка и заголовок в одну строку',
                    'band' => 'Компактная полоса',
                ], 'grid', 'В варианте «в одну строку» иконка стоит рядом с заголовком, а не над ним — карточка получается ниже, и в ряд их влезает больше.'),
                'title' => Field::text('Заголовок раздела')->named('title_field'),
                'description' => Field::richtext(
                    'Описание раздела',
                    'Заголовок и описание выводятся над содержимым блока — отдельный текстовый блок не нужен.'
                ),
                'all_text' => Field::text('Ссылка «Все …» — текст', '', '', 'Все направления'),
                'all_url' => Field::url('Ссылка «Все …» — URL'),
                'columns' => Field::intChoice(
                    'Колонок в сетке',
                    [0, 2, 3, 4, 5],
                    0,
                    'Автоматический режим не оставляет в последнем ряду одинокую карточку. Не действует в варианте «Компактная полоса».',
                    [0 => 'Автоматически по числу карточек']
                ),
            ],
            'testimonials' => [
                'variant' => Field::enum('Вариант отображения', [
                    'carousel' => 'Карусель — отзывы едут полосой',
                    'grid' => 'Сетка — отзывы стоят рядами',
                ], 'carousel'),
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'description' => Field::textarea('Описание раздела'),
                'columns' => Field::intChoice(
                    'Колонок в сетке',
                    [2, 3, 4],
                    3,
                    'Работает в варианте «Сетка». Если в последнем ряду остаётся две-три карточки, они растягиваются на всю ширину; одинокую карточку блок не растягивает.'
                )->onlyWhen('variant', ['grid']),
                'autoplay' => Field::int(
                    'Автопрокрутка, секунд (0 — выключена)',
                    0,
                    30,
                    0,
                    'Работает в варианте «Карусель». Останавливается под курсором, при фокусе внутри и у посетителей, которые просили меньше движения.'
                )->onlyWhen('variant', ['carousel']),
            ],
            'faq' => [
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'search_enabled' => Field::bool('Показывать поиск, если вопросов четыре или больше', true),
                'single_open' => Field::bool('Одновременно открывать только один ответ', false),
            ],
            'docs_list' => [
                'variant' => Field::enum('Вариант списка', [
                    'grid' => 'Карточки документов',
                    'links' => 'Компактный список ссылок',
                    'acts' => 'Правовые акты (номер и дата)',
                    'acts-editorial' => 'Правовые акты — редакционные карточки',
                ], 'grid', 'Компактный вариант подходит для короткого списка файлов и внешних материалов. «Правовые акты» показывают номер и дату из полей ниже.'),
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'all_text' => Field::text('Ссылка «Все …» — текст', '', '', 'Все документы'),
                'all_url' => Field::url('Ссылка «Все …» — URL'),
                'columns' => Field::intChoice(
                    'Колонок',
                    [1, 2, 3, 4, 5],
                    4,
                    'Компактный список выводится строками, колонки к нему не применяются.'
                )->onlyWhen('variant', ['grid', 'acts', 'acts-editorial']),
                'search_enabled' => Field::bool(
                    'Добавлять поиск и фильтр форматов',
                    true,
                    'Появляется, когда документов четыре или больше.'
                ),
                'emblem' => Field::bool(
                    'Фирменный знак на карточках',
                    true,
                    'Эмблема водяным знаком в углу карточки правового акта. Отключите, если карточки идут на пёстром фоне.'
                )->onlyWhen('variant', ['acts', 'acts-editorial']),
            ],
            'stages' => [
                'variant' => Field::enum('Вариант отображения', [
                    'default' => 'Этапы реализации',
                    'history' => 'История организации',
                ], 'default'),
                'title' => Field::text('Заголовок раздела')->named('title_field'),
                'description' => Field::richtext(
                    'Описание раздела',
                    'Заголовок и описание выводятся над содержимым блока — отдельный текстовый блок не нужен.'
                ),
                'all_text' => Field::text('Ссылка «Все …» — текст', '', '', 'Все этапы'),
                'all_url' => Field::url('Ссылка «Все …» — URL'),
                'columns' => Field::intChoice(
                    'Этапов в ряду',
                    [0, 2, 3, 4, 5],
                    0,
                    'Если этапов больше, ряд превращается в прокручиваемую полосу.',
                    [0 => 'Автоматически по числу этапов']
                ),
                'autoplay' => Field::int('Автопрокрутка, секунд (0 — выключена)', 0, 30, 0),
            ],
            'projects_list' => [
                'variant' => Field::enum('Вариант отображения', [
                    'grid' => 'Сетка карточек',
                    'list' => 'Список — фото слева, текст справа',
                    'carousel' => 'Полоса с прокруткой',
                ], 'grid', 'В списке помещается более длинный анонс, в полосе карточки листаются стрелками и свайпом.'),
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'description' => Field::text('Описание раздела', '', '', 'Одна строка под заголовком'),
                'limit' => Field::int(
                    'Сколько записей показывать (0 — все)',
                    0,
                    null,
                    3,
                    'Блок выводит опубликованные записи раздела «Проекты» по порядку сортировки.'
                ),
                'all_text' => Field::text('Ссылка «Все …» — текст', '', '', 'Все проекты'),
                'all_url' => Field::url(
                    'Ссылка «Все …» — URL',
                    'Пусто — блок сам подставит адрес раздела. Ссылка показывается и без заголовка блока.'
                ),
                'columns' => Field::intChoice(
                    'Колонок в сетке',
                    [2, 3, 4],
                    3,
                    'Если в последнем ряду остаётся две-три карточки, они растягиваются на всю ширину; одинокую карточку блок не растягивает. На узких экранах колонок всегда меньше.'
                )->onlyWhen('variant', ['grid']),
                'autoplay' => Field::int(
                    'Автопрокрутка полосы, секунд',
                    0,
                    30,
                    0,
                    '0 — без автопрокрутки. Работает только у варианта «полоса»; движение замирает под курсором и у посетителей, просивших меньше анимации.'
                )->onlyWhen('variant', ['carousel']),
            ],
            'subscribe' => [
                'variant' => Field::enum('Вариант оформления', [
                    'band' => 'Полоса во всю ширину',
                    'card' => 'Карточка по центру',
                    'image' => 'На фоне изображения',
                ], 'band', 'Без загруженного изображения вариант «На фоне» показывается как обычная полоса.'),
                'title' => Field::text('Заголовок, показываемый на сайте', 'Подписка на новости')->named('title_field'),
                'image' => Field::media('Фоновое изображение'),
                'text' => Field::text('Текст под заголовком', 'Получайте дайджест новостей на почту раз в неделю.'),
                'placeholder' => Field::text('Подсказка в поле e-mail', '', '', 'Ваш e-mail'),
                'button_text' => Field::text('Текст кнопки', 'Подписаться', '', 'Подписаться'),
                'note' => Field::text('Примечание под формой', '', '', 'Отписаться можно в один клик'),
            ],
            'slider' => [
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'ratio' => Field::enum(
                    'Пропорция кадра',
                    \App\Core\SliderRatio::ALL,
                    '16-9',
                    'Общая пропорция удерживает высоту слайдера: при варианте «как у изображения» страница подпрыгивает на каждом переключении, если снимки разного размера.'
                ),
                'autoplay' => Field::int(
                    'Автопрокрутка, секунд (0 — выключена)',
                    0,
                    30,
                    0,
                    'Прокрутка останавливается при наведении, фокусе внутри слайдера и у посетителей, которые просили меньше движения в системе.'
                ),
            ],
            'person_cards' => [
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'description' => Field::textarea('Описание раздела'),
                'all_text' => Field::text('Ссылка «Все …» — текст', '', '', 'Все руководство'),
                'all_url' => Field::url('Ссылка «Все …» — URL'),
                'columns' => Field::intChoice(
                    'Колонок в сетке',
                    [2, 3, 4, 5],
                    4,
                    'Если в последнем ряду остаётся две-три карточки, они растягиваются на всю ширину; одинокую карточку блок не растягивает.'
                ),
            ],
            'contact_cards' => [
                'variant' => Field::enum('Вариант отображения', [
                    'cards' => 'Иконка над заголовком',
                    'inline' => 'Иконка и заголовок в одну строку',
                ], 'cards', 'Во втором варианте карточка ниже — в ряд их влезает больше.'),
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'icon_size' => Field::int('Размер иконки, px', 16, 64, 22),
                'icon_bg' => Field::enum('Подложка под иконкой', [
                    'on' => 'Есть — иконка в плитке',
                    'off' => 'Нет — только иконка',
                ], 'on', 'Без подложки иконка стоит на фоне карточки. Размер плитки подстраивается под размер иконки.'),
                'line_icons' => Field::bool(
                    'Мини-иконки у строк контактов',
                    true,
                    'Подбираются по содержимому строки: трубка к номеру, конверт к почте, часы к режиму работы. Строка без узнаваемого содержимого остаётся без значка.'
                ),
            ],
            'icon_text' => [
                'variant' => Field::enum('Вариант отображения', [
                    'cards' => 'Карточки с рамкой',
                    'plain' => 'Без рамок — иконка и текст',
                    'inline' => 'В строку — короткие реквизиты',
                ], 'cards', '«Без рамок» стоит выбрать, если рядом уже есть карточные секции — иначе блоки спорят друг с другом.'),
                'title' => Field::text('Заголовок, показываемый на сайте')->named('title_field'),
                'description' => Field::textarea('Описание под заголовком'),
                'rows_layout' => Field::enum('Строки внутри карточки', [
                    'stacked' => 'Одна под другой',
                    'inline' => 'В строку, через разделитель',
                ], 'stacked'),
                'align' => Field::enum('Выравнивание текста', [
                    'left' => 'По левому краю',
                    'center' => 'По центру',
                ], 'left'),
                'columns' => Field::intChoice('Колонок', [1, 2, 3, 4], 3),
            ],
        ];
    }

    public static function has(string $type): bool
    {
        return isset(self::all()[$type]);
    }

    /** @return array<string, Field> */
    public static function fields(string $type): array
    {
        return self::all()[$type] ?? [];
    }

    /**
     * Умолчания типа: поля схемы плюс то, что собирается мимо неё.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        $defaults = [];
        foreach (self::fields($type) as $key => $field) {
            $defaults[$key] = $field->default;
        }

        return array_merge($defaults, self::EXTRA[$type] ?? []);
    }

    /**
     * Присланное формой значение каждого поля схемы. Ключи мимо схемы
     * (репитеры, зависимые значения) добавляет вызывающий код.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public static function normalize(string $type, array $post, string $locale): array
    {
        $data = [];
        foreach (self::fields($type) as $key => $field) {
            $name = $field->inputName($key);
            $data[$key] = match ($field->kind) {
                'enum' => BlockDataInput::enum($post, $name, array_keys($field->options), (string) $field->default),
                'int' => BlockDataInput::int($post, $name, (int) $field->min, $field->max ?? PHP_INT_MAX, (int) $field->default),
                'int_choice' => isset($field->options[(int) (is_scalar($post[$name] ?? null) ? (string) $post[$name] : '')])
                    ? (int) $post[$name]
                    : (int) $field->default,
                'bool' => !empty($post[$name]),
                'text', 'textarea' => BlockDataInput::plain($post, $name, $locale),
                'richtext' => TextProcessor::process(
                    HtmlSanitizer::sanitizeText(is_scalar($post[$name] ?? null) ? (string) $post[$name] : ''),
                    $locale
                ),
                'url' => BlockDataInput::safeLink($post[$name] ?? ''),
                'media' => BlockDataInput::safeMedia($post[$name] ?? ''),
                'icon' => Icon::cleanName($post[$name] ?? ''),
                'color' => BlockDataInput::optionalColor($post, $name),
                default => $field->default,
            };
        }

        return $data;
    }

    /**
     * Сохранённые данные, приведённые к схеме: значение вне списка или вне
     * границ заменяется умолчанием.
     *
     * Вызывается на выводе, а не только при сохранении: `data` блока приезжает
     * и из старых записей, и из загруженного файла шаблона страницы, где
     * проверены только ключи. Благодаря этому шаблон блока читает значение как
     * есть — вторая копия списка допустимых значений ему не нужна.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function apply(string $type, array $data): array
    {
        foreach (self::fields($type) as $key => $field) {
            $value = $data[$key] ?? $field->default;
            $data[$key] = match ($field->kind) {
                'enum' => isset($field->options[is_scalar($value) ? (string) $value : ''])
                    ? (string) $value
                    : (string) $field->default,
                'int' => max((int) $field->min, min($field->max ?? PHP_INT_MAX, is_numeric($value) ? (int) $value : (int) $field->default)),
                'int_choice' => isset($field->options[is_numeric($value) ? (int) $value : -1])
                    ? (int) $value
                    : (int) $field->default,
                'bool' => (bool) $value,
                'media' => BlockDataInput::safeMedia($value),
                'icon' => Icon::cleanName($value),
                'color' => preg_match('/^#[0-9a-f]{6}$/i', is_scalar($value) ? (string) $value : '') === 1
                    ? strtolower((string) $value)
                    : '',
                default => is_scalar($value) ? (string) $value : '',
            };
        }

        return $data;
    }

    /**
     * Поля формы блока. Разметка та же, что у рукописных полей: класс
     * `form-field`, подпись, подсказка и `data-field-when` для полей,
     * применимых не ко всем вариантам.
     *
     * @param array<string, mixed> $data сохранённые значения блока
     */
    public static function formHtml(string $type, array $data): string
    {
        $html = '';
        $colorRun = '';
        foreach (self::fields($type) as $key => $field) {
            // Соседние настройки цвета встают в один ряд — так они свёрстаны в
            // остальной админке, и пара «фон / текст» читается как пара.
            if ($field->kind === 'color') {
                $colorRun .= self::fieldHtml($key, $field, $data);
                continue;
            }
            if ($colorRun !== '') {
                $html .= '<div class="colorfield-row">' . $colorRun . '</div>';
                $colorRun = '';
            }
            $html .= self::fieldHtml($key, $field, $data);
        }

        return $colorRun !== '' ? $html . '<div class="colorfield-row">' . $colorRun . '</div>' : $html;
    }

    /** @param array<string, mixed> $data */
    private static function fieldHtml(string $key, Field $field, array $data): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
        $name = $field->inputName($key);
        $id = 'bf_' . (string) preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        $when = $field->when === null
            ? ''
            : ' data-field-when="' . $esc($field->when['field']) . '" data-field-value="' . $esc(implode(',', $field->when['values'])) . '"';
        $hint = $field->hint !== '' ? '<span class="form-hint">' . $esc($field->hint) . '</span>' : '';
        $label = '<label for="' . $id . '">' . $esc($field->label) . '</label>';

        // Готовые виджеты админки приносят собственный `.form-field`, поэтому
        // условие показа вешается обёрткой снаружи, а не атрибутом внутри.
        if (in_array($field->kind, ['media', 'icon', 'color'], true)) {
            $widget = match ($field->kind) {
                'media' => AdminUi::imageField($name, is_scalar($value = $data[$key] ?? '') ? (string) $value : '', ['label' => $field->label, 'hint' => $field->hint]),
                'icon' => AdminUi::iconField($name, $data[$key] ?? '', array_filter([
                    'label' => $field->label,
                    'hint' => $field->hint !== '' ? $field->hint : null,
                ], static fn ($v) => $v !== null)),
                default => AdminUi::colorField($name, (string) ($data[$key] ?? ''), $field->label, $field->swatch),
            };

            return $when === '' ? $widget : '<div' . $when . '>' . $widget . '</div>';
        }

        if ($field->kind === 'bool') {
            // У флажка подпись идёт после самого флажка — так свёрстаны
            // остальные флажки формы блока.
            $checked = array_key_exists($key, $data) ? !empty($data[$key]) : (bool) $field->default;

            return '<div class="form-field form-field--checkbox"' . $when . '>'
                . '<input type="checkbox" id="' . $id . '" name="' . $esc($name) . '" value="1"' . ($checked ? ' checked' : '') . '>'
                . '<label for="' . $id . '">' . $esc($field->label) . '</label>' . $hint . '</div>';
        }

        $value = $data[$key] ?? $field->default;
        $control = match ($field->kind) {
            'enum' => self::selectHtml($id, $name, $field->options, is_scalar($value) ? (string) $value : ''),
            'int' => '<input type="number" id="' . $id . '" name="' . $esc($name) . '" min="' . (int) $field->min
                . ($field->max !== null ? '" max="' . $field->max : '') . '" value="' . (int) $value . '">',
            'int_choice' => self::selectHtml($id, $name, $field->options, (string) (int) $value),
            'textarea' => '<textarea id="' . $id . '" name="' . $esc($name) . '" rows="2">'
                . $esc(is_scalar($value) ? (string) $value : '') . '</textarea>',
            'richtext' => '<textarea id="' . $id . '" name="' . $esc($name) . '" rows="3" data-wysiwyg>'
                . $esc(is_scalar($value) ? (string) $value : '') . '</textarea>',
            default => '<input type="text" id="' . $id . '" name="' . $esc($name) . '" value="'
                . $esc(is_scalar($value) ? (string) $value : '') . '"'
                . ($field->placeholder !== '' ? ' placeholder="' . $esc($field->placeholder) . '"' : '') . '>',
        };

        return '<div class="form-field"' . $when . '>' . $label . $control . $hint . '</div>';
    }

    /**
     * Ключи списка — строки (значения enum) или числа (набор чисел): PHP
     * приводит числовую строку в ключе массива к int, и array-key здесь честнее.
     *
     * @param array<array-key, string> $options
     */
    private static function selectHtml(string $id, string $name, array $options, string $current): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
        $html = '<select id="' . $id . '" name="' . $esc($name) . '">';
        foreach ($options as $value => $label) {
            $value = (string) $value;
            $html .= '<option value="' . $esc($value) . '"' . ($current === $value ? ' selected' : '') . '>'
                . $esc($label) . '</option>';
        }

        return $html . '</select>';
    }
}
