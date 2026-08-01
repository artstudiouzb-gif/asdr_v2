<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Сборка поста для Telegram Rich Messages (Bot API 10.1+, метод
 * `sendRichMessage`). Формат — HTML документа: заголовки, абзацы, разделители,
 * сворачиваемые блоки, изображения с подписями, слайд-шоу, сноска.
 *
 * Зачем это вместо обычной подписи: у подписи к фото жёсткий лимит в 1024
 * символа, и двуязычный пост резался пополам — по ~400 знаков на язык. У
 * rich-сообщения предел 32768 символов и до 50 медиа, поэтому текст уходит
 * целиком, а вторая языковая версия прячется под «развернуть».
 *
 * Класс ничего не отправляет: отдаёт готовый HTML, а транспорт и откат на
 * старый формат — забота SocialPublisher.
 */
final class TelegramRichMessage
{
    /** Пределы rich-сообщения: текст без тегов, число медиа, число блоков. */
    public const TEXT_LIMIT = 32768;
    public const MEDIA_LIMIT = 50;

    /**
     * Собирает пост. Возвращает html документа и список медиа: в разметке
     * снимки указываются ссылками `tg://photo?id=…`, а сами файлы уходят
     * отдельным полем `media` — так требует InputRichMessage.
     *
     * @param list<array{code:string,label:string,title:string,excerpt:string,link:string,read_more:string}> $langs
     * @param list<string> $photos абсолютные https-адреса
     * @param array<string, array{caption:string, credit:string}> $photoMeta подписи по адресу снимка
     * @return array{html:string, media:list<array{id:string,media:array{type:string,media:string}}>}
     */
    public static function build(
        array $langs,
        array $photos,
        string $signature = '',
        string $category = '',
        string $date = '',
        string $hashtags = '',
        array $photoMeta = [],
        string $secondLang = 'inline'
    ): array {
        if ($langs === []) {
            return ['html' => '', 'media' => []];
        }

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $photos = array_slice(array_values(array_filter($photos, static fn (string $u): bool => str_starts_with($u, 'https://'))), 0, self::MEDIA_LIMIT);

        $first = $langs[0];
        $rest = array_slice($langs, 1);
        $html = '';

        // Рубрика и дата — служебной строкой над заголовком: в ленте канала по
        // ней видно, о чём пост, ещё до заголовка. Значения берём из самого
        // языкового блока, если они там есть: дата и рубрика переводятся, и
        // под русским заголовком не должно стоять «1-avgust, 2026-yil».
        $metaLine = static function (array $lang) use ($esc, $category, $date): string {
            $parts = array_values(array_filter([
                trim((string) ($lang['category'] ?? $category)),
                trim((string) ($lang['date'] ?? $date)),
            ], static fn (string $s): bool => $s !== ''));

            return $parts === [] ? '' : '<p><b>' . $esc(implode(' · ', $parts)) . '</b></p>';
        };
        $html .= $metaLine($first);

        $media = [];
        foreach ($photos as $i => $url) {
            // Идентификатор — только A-Z, a-z, 0-9, _ и -, как требует API.
            $media[] = [
                'id' => 'p' . ($i + 1),
                'media' => ['type' => 'photo', 'media' => $url],
                'caption' => trim((string) ($photoMeta[$url]['caption'] ?? '')),
                'credit' => trim((string) ($photoMeta[$url]['credit'] ?? '')),
            ];
        }

        $html .= '<h1>' . $esc($first['title']) . '</h1>';
        $html .= self::media($media, $esc);
        $html .= self::body($first, $esc);

        // Остальные языки — тем же текстом следом за разделителем. Под
        // «развернуть» (`details`) пост компактнее, но редактору, который
        // дорабатывает пост в канале руками, спойлер только мешает.
        foreach ($rest as $lang) {
            $html .= '<hr/>';
            $section = $metaLine($lang)
                . '<h2>' . $esc((string) $lang['title']) . '</h2>'
                . self::body($lang, $esc);
            if ($secondLang === 'details') {
                $label = trim((string) ($lang['label'] ?? '')) ?: mb_strtoupper((string) ($lang['code'] ?? ''));
                $section = '<details><summary>' . $esc($label) . '</summary>' . $section . '</details>';
            }
            $html .= $section;
        }

        // Хештеги — отдельным абзацем: Telegram сам делает их кликабельными.
        if (trim($hashtags) !== '') {
            $html .= '<p>' . $esc(trim($hashtags)) . '</p>';
        }
        // Подпись редактор задаёт с HTML-тегами Telegram — не экранируем её.
        // Своим блоком, а не внутри абзаца: в подписи бывает и цитата, а
        // <footer> принимает только строчное содержимое.
        if (trim($signature) !== '') {
            $html .= '<footer>' . trim($signature) . '</footer>';
        }

        return [
            'html' => $html,
            'media' => array_map(static fn (array $m): array => ['id' => $m['id'], 'media' => $m['media']], $media),
        ];
    }

    /**
     * Одно фото — картинкой, несколько — слайд-шоу: альбом из десяти снимков
     * занимает в ленте целый экран, а слайд-шоу листается на месте.
     *
     * @param list<array{id:string,media:array{type:string,media:string}}> $media
     */
    private static function media(array $media, callable $esc): string
    {
        if ($media === []) {
            return '';
        }
        // Подпись и автор снимка — документированный <figcaption> с <cite>.
        $img = static function (array $item) use ($esc): string {
            $tag = '<img src="tg://photo?id=' . $esc($item['id']) . '"/>';
            if ($item['caption'] === '' && $item['credit'] === '') {
                return $tag;
            }

            return '<figure>' . $tag . '<figcaption>' . $esc($item['caption'])
                . ($item['credit'] !== '' ? '<cite>' . $esc(Media::photoCredit($item['credit'])) . '</cite>' : '')
                . '</figcaption></figure>';
        };
        if (count($media) === 1) {
            return $img($media[0]);
        }

        $items = '';
        foreach ($media as $item) {
            $items .= $img($item);
        }

        return '<tg-slideshow>' . $items . '</tg-slideshow>';
    }

    /**
     * Тело языкового блока: анонс абзацами и ссылка на страницу новости.
     *
     * @param array{excerpt:string,link:string,read_more:string} $lang
     */
    private static function body(array $lang, callable $esc): string
    {
        $html = '';
        foreach (preg_split('/\R{2,}/u', trim((string) $lang['excerpt'])) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $html .= '<p>' . nl2br($esc($paragraph), false) . '</p>';
            }
        }
        if (trim((string) $lang['link']) !== '') {
            $html .= '<p><a href="' . $esc((string) $lang['link']) . '">' . $esc((string) $lang['read_more']) . '</a></p>';
        }

        return $html;
    }

    /** Видимая длина текста без тегов — по ней Telegram считает лимит. */
    public static function textLength(string $html): int
    {
        return mb_strlen(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    public static function fits(string $html): bool
    {
        return self::textLength($html) <= self::TEXT_LIMIT;
    }
}
