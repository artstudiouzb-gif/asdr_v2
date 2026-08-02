from pathlib import Path


def replace(path: str, old: str, new: str) -> None:
    file = Path(path)
    text = file.read_text(encoding="utf-8")
    if old not in text:
        raise SystemExit(f"Expected fragment not found in {path}")
    file.write_text(text.replace(old, new, 1), encoding="utf-8")


replace(
    "app/Core/TelegramRichMessage.php",
    """            $html .= '<p><a href=\"' . $esc((string) $lang['link']) . '\">' . $esc((string) $lang['read_more']) . '</a></p>';""",
    """            $html .= '<p><a href=\"' . $esc((string) $lang['link']) . '\"><b>' . $esc((string) $lang['read_more']) . '</b></a></p>';""",
)

replace(
    "app/Core/SocialPublisher.php",
    """                ? "\\n\\n" . '<a href=\"' . $esc((string) $l['link']) . '\">' . $esc((string) $l['read_more']) . '</a>'""",
    """                ? "\\n\\n" . '<a href=\"' . $esc((string) $l['link']) . '\"><b>' . $esc((string) $l['read_more']) . '</b></a>'""",
)

replace(
    "tests/cases/205_telegram_rich_test.php",
    """        '<a href=\"https://site.uz/news/x\">Читать на сайте →</a>',""",
    """        '<a href=\"https://site.uz/news/x\"><b>Читать на сайте →</b></a>',""",
)

replace(
    "tests/cases/205_telegram_rich_test.php",
    """    $caption = (string) ($calls[0]['body']['media'][0]['caption'] ?? '');
    assert_contains('#Oʻzbekiston2030', $caption, 'обычный формат использует единый регистр и безопасный апостроф');""",
    """    $caption = (string) ($calls[0]['body']['media'][0]['caption'] ?? '');
    assert_contains('<a href=\"https://site.uz/uz/news/x\"><b>Saytda oʻqish →</b></a>', $caption, 'classic делает ссылку на сайт жирной');
    assert_contains('#Oʻzbekiston2030', $caption, 'обычный формат использует единый регистр и безопасный апостроф');""",
)
