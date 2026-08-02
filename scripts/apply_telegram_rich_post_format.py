from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    file = Path(path)
    text = file.read_text(encoding="utf-8")
    if old not in text:
        raise SystemExit(f"Expected block not found in {path}")
    file.write_text(text.replace(old, new, 1), encoding="utf-8")


rich_path = "app/Core/TelegramRichMessage.php"

replace_once(
    rich_path,
    """        foreach ($rest as $lang) {
            $html .= '<hr/>';
            $section = $metaLine($lang)
                . '<h2>' . $esc((string) $lang['title']) . '</h2>'
                . self::body($lang, $esc);
            if ($secondLang === 'details') {
""",
    """        foreach ($rest as $lang) {
            $html .= '<hr/>';
            // Содержимое языковой версии собирается один раз. Режим меняет
            // только оболочку, поэтому inline и details получают одинаковые
            // заголовок, метаданные, лид и ссылку.
            $section = self::languageSection($lang, $esc, $metaLine);
            if ($secondLang === 'details') {
""",
)

replace_once(
    rich_path,
    """        // Хештеги — отдельный блок после разделителя: они визуально не
        // сливаются с последней ссылкой, Telegram делает их кликабельными.
        if (trim($hashtags) !== '') {
            $html .= '<hr/><p>' . $esc(self::normalizeHashtags($hashtags)) . '</p>';
        }
""",
    """        // Хештеги — отдельные выделенные строки по языкам. <mark>
        // поддерживается Rich HTML, а автоматическое распознавание hashtag
        // entities остаётся включённым, поэтому теги сохраняют кликабельность.
        $hashtagLines = self::hashtagLines($langs, $hashtags);
        if ($hashtagLines !== []) {
            $html .= '<hr/>';
            foreach ($hashtagLines as $line) {
                $html .= '<p><mark>' . $esc($line) . '</mark></p>';
            }
        }
""",
)

body_end = """        return $html;
    }

    /**
     * Делает узбекские хештеги цельными для Telegram. Кавычки U+2018/U+2019
"""
helpers = """        return $html;
    }

    /**
     * Полный языковой раздел. Открытый и сворачиваемый режимы используют
     * один результат и отличаются только внешней оболочкой <details>.
     *
     * @param array<string,mixed> $lang
     * @param callable(array):string $metaLine
     */
    private static function languageSection(array $lang, callable $esc, callable $metaLine): string
    {
        return $metaLine($lang)
            . '<h2>' . $esc((string) ($lang['title'] ?? '')) . '</h2>'
            . self::body($lang, $esc);
    }

    /**
     * Группирует хештеги по языковым версиям. Общая строка используется как
     * резерв для старых вызовов и добавляет только теги, которых ещё не было.
     *
     * @param list<array<string,mixed>> $langs
     * @return list<string>
     */
    public static function hashtagLines(array $langs, string $hashtags): array
    {
        $seen = [];
        $lines = [];
        $take = static function (string $raw) use (&$seen): string {
            $normalized = self::normalizeHashtags($raw);
            $line = [];
            foreach (preg_split('/\\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $tag) {
                $key = mb_strtolower($tag);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $line[] = $tag;
                }
            }

            return implode(' ', $line);
        };

        foreach ($langs as $lang) {
            $line = $take((string) ($lang['hashtags'] ?? ''));
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        $fallback = $take($hashtags);
        if ($fallback !== '') {
            $lines[] = $fallback;
        }

        return $lines;
    }

    /**
     * Делает узбекские хештеги цельными для Telegram. Кавычки U+2018/U+2019
"""
replace_once(rich_path, body_end, helpers)

publisher_path = "app/Core/SocialPublisher.php"
replace_once(
    publisher_path,
    """        $hashtags = TelegramRichMessage::normalizeHashtags(trim((string) ($post['hashtags'] ?? '')));
        // Полноценная пустая строка между последней ссылкой и тегами.
        $tail = ($hashtags !== '' ? "\\n\\n\\n" . $esc($hashtags) : '')
            . ($signature !== '' ? "\\n\\n" . $signature : '');
""",
    """        $hashtagLines = TelegramRichMessage::hashtagLines(
            $langs,
            trim((string) ($post['hashtags'] ?? ''))
        );
        $hashtagHtml = implode("\\n", array_map(
            static fn (string $line): string => '<b>' . $esc($line) . '</b>',
            $hashtagLines
        ));
        // Classic HTML не поддерживает <mark>, поэтому используем жирное
        // выделение. Языковые наборы остаются отдельными строками.
        $tail = ($hashtagHtml !== '' ? "\\n\\n\\n" . $hashtagHtml : '')
            . ($signature !== '' ? "\\n\\n" . $signature : '');
""",
)

test_path = "tests/cases/205_telegram_rich_test.php"
replace_once(
    test_path,
    """        'hashtags' => '#реформы',
        'langs' => [
            ['code' => 'uz', 'label' => 'Oʻzbekcha', 'title' => 'Sarlavha', 'excerpt' => "Birinchi.\\n\\nIkkinchi.",
             'link' => 'https://site.uz/uz/news/x', 'read_more' => 'Saytda oʻqish →'],
            ['code' => 'ru', 'label' => 'Русский', 'title' => 'Заголовок', 'excerpt' => 'Русский анонс.',
             'link' => 'https://site.uz/news/x', 'read_more' => 'Читать на сайте →'],
""",
    """        'hashtags' => '#o‘zbekiston2030 #strategiya #узбекистан2030 #стратегия',
        'langs' => [
            ['code' => 'uz', 'label' => 'Oʻzbekcha', 'title' => 'Sarlavha', 'excerpt' => "Birinchi.\\n\\nIkkinchi.",
             'link' => 'https://site.uz/uz/news/x', 'read_more' => 'Saytda oʻqish →',
             'hashtags' => '#o‘zbekiston2030 #strategiya'],
            ['code' => 'ru', 'label' => 'Русский', 'title' => 'Заголовок', 'excerpt' => 'Русский анонс.',
             'link' => 'https://site.uz/news/x', 'read_more' => 'Читать на сайте →',
             'hashtags' => '#узбекистан2030 #стратегия'],
""",
)
replace_once(
    test_path,
    """    assert_contains('<hr/><p>#Реформы</p>', $html, 'хештеги отделены от последней ссылки и нормализованы');
""",
    """    assert_contains('<hr/><p><mark>#Oʻzbekiston2030 #Strategiya</mark></p>', $html, 'узбекские теги выделены отдельной строкой');
    assert_contains('<p><mark>#Узбекистан2030 #Стратегия</mark></p>', $html, 'русские теги выделены отдельной строкой');
""",
)
replace_once(
    test_path,
    """    assert_contains('<hr/><p>#Oʻzbekiston2030 #Gʻoya #Maʻnaviyat</p>', $doc['html']);
""",
    """    assert_contains('<hr/><p><mark>#Oʻzbekiston2030 #Gʻoya #Maʻnaviyat</mark></p>', $doc['html']);
""",
)
replace_once(
    test_path,
    """    assert_contains("</a>\\n\\n\\n#Oʻzbekiston2030", $caption, 'хештеги не приклеиваются к последней ссылке');
""",
    """    assert_contains("</a>\\n\\n\\n<b>#Oʻzbekiston2030</b>", $caption, 'classic выделяет теги и не приклеивает их к ссылке');
""",
)

anchor = """test('Rich: узбекский буквенный апостроф не разрывает хештег Telegram', function () {
"""
mode_test = """test('Rich: inline и details используют одинаковое содержимое языковой версии', function () {
    $post = rich_post();
    $args = [
        $post['langs'],
        [],
        '',
        (string) $post['category'],
        (string) $post['date'],
        (string) $post['hashtags'],
        [],
    ];
    $inline = TelegramRichMessage::build(...[...$args, 'inline'])['html'];
    $details = TelegramRichMessage::build(...[...$args, 'details'])['html'];

    foreach ([
        '<h2>Заголовок</h2>',
        'Русский анонс.',
        '<a href="https://site.uz/news/x">Читать на сайте →</a>',
        '<p><mark>#Oʻzbekiston2030 #Strategiya</mark></p>',
        '<p><mark>#Узбекистан2030 #Стратегия</mark></p>',
    ] as $fragment) {
        assert_contains($fragment, $inline, 'inline содержит полный языковой блок');
        assert_contains($fragment, $details, 'details содержит тот же языковой блок');
    }
    assert_not_contains('<details>', $inline);
    assert_contains('<details><summary>Русский</summary>', $details);
});

""" + anchor
replace_once(test_path, anchor, mode_test)

print("Telegram rich post formatting patch applied")
