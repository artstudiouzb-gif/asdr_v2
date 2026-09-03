<?php

declare(strict_types=1);

namespace App\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Проверка медиатеки на диске: отдаст ли веб-сервер то, на что ссылается сайт.
 *
 * Отвечает на вопрос, который иначе выясняется только по SSH. Картинка
 * пропадает со страницы по двум механическим причинам, и обе видно с диска:
 *
 * 1. **Файл не открыт на чтение.** Файл, созданный самим PHP, получает права
 *    по umask — на части хостингов это 0600. PHP файл видит, веб-сервер отдать
 *    не может, и на месте фотографии остаётся alt-текст.
 * 2. **Файл пустой.** Обрыв записи оставляет ноль байт: `is_file()` такой файл
 *    находит, браузер декодировать не может.
 *
 * Для webp-вариантов любая из причин смертельна: `<picture>` выбирает источник
 * по типу, а НЕ по тому, загрузился ли он, и на `<img>` уже не откатывается.
 *
 * Обход ограничен и по числу файлов, и по времени: медиатека растёт, а раздел
 * админки не имеет права зависнуть. Упёрлись в предел — отчёт честно говорит,
 * что показан не весь каталог.
 */
final class MediaHealth
{
    /** Потолок обхода: дальше отчёт помечается как неполный. */
    public const MAX_FILES = 40000;

    /** Секунды на обход. Панель обязана ответить, даже если каталог огромен. */
    public const TIME_BUDGET = 8.0;

    /** Сколько путей показывать по каждой находке: список для человека, а не выгрузка. */
    public const SAMPLE_SIZE = 15;

    /**
     * @return array{
     *     dir: string,
     *     exists: bool,
     *     checked: int,
     *     images: int,
     *     unreadable: int,
     *     empty: int,
     *     truncated: bool,
     *     samples: array{unreadable: list<string>, empty: list<string>}
     * }
     */
    public static function scan(?string $dir = null): array
    {
        $dir = rtrim($dir ?? (string) Config::get('paths.public_uploads', ''), '/');
        $out = [
            'dir' => $dir,
            'exists' => $dir !== '' && is_dir($dir),
            'checked' => 0,
            'images' => 0,
            'unreadable' => 0,
            'empty' => 0,
            'truncated' => false,
            'samples' => ['unreadable' => [], 'empty' => []],
        ];
        if (!$out['exists']) {
            return $out;
        }

        $started = microtime(true);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            /** @var SplFileInfo $item */
            if (!$item->isFile()) {
                continue;
            }
            if ($out['checked'] >= self::MAX_FILES || (microtime(true) - $started) >= self::TIME_BUDGET) {
                $out['truncated'] = true;
                break;
            }

            $out['checked']++;
            $path = $item->getPathname();
            $relative = ltrim(substr($path, strlen($dir)), '/');
            $isImage = (bool) preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $relative);
            if ($isImage) {
                $out['images']++;
            }

            // Пустой файл — до прав дело не доходит: отдавать нечего.
            if ($item->getSize() === 0 && !str_starts_with($item->getFilename(), '.')) {
                $out['empty']++;
                if (count($out['samples']['empty']) < self::SAMPLE_SIZE) {
                    $out['samples']['empty'][] = $relative;
                }
                continue;
            }

            $mode = fileperms($path);
            if ($mode !== false && ($mode & 0004) === 0) {
                $out['unreadable']++;
                if (count($out['samples']['unreadable']) < self::SAMPLE_SIZE) {
                    $out['samples']['unreadable'][] = $relative;
                }
            }
        }

        return $out;
    }

    /**
     * Есть ли что чинить — короткий ответ для заголовка отчёта.
     *
     * @param array<string, mixed> $report отчёт scan()
     */
    public static function healthy(array $report): bool
    {
        return (int) ($report['unreadable'] ?? 0) === 0 && (int) ($report['empty'] ?? 0) === 0;
    }
}
