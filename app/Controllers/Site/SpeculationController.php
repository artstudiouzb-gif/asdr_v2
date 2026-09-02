<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Speculation;
use App\Models\Language;

/**
 * Файл правил предзагрузки (см. App\Core\Speculation).
 *
 * Отдаётся PHP, а не лежит статикой: тип `application/speculationrules+json`
 * shared-хостинг сам не проставит, а с `application/json` браузер правила
 * молча не примет. Заодно в исключения попадают адреса активных языков.
 */
final class SpeculationController
{
    public function rules(): void
    {
        header('Content-Type: application/speculationrules+json; charset=UTF-8');
        // Кэшируем как обычную статику: правила меняются вместе с релизом.
        header('Cache-Control: public, max-age=3600');
        header('X-Robots-Tag: noindex');

        $langs = [];
        try {
            $default = Language::defaultCode();
            foreach (Language::activeCodes() as $code) {
                if ($code !== $default) {
                    $langs[] = $code;
                }
            }
        } catch (\Throwable) {
            // Без базы правила остаются без языковых префиксов — это лучше,
            // чем пятисотка на файле, который запрашивает браузер сам.
            $langs = [];
        }

        echo Speculation::json($langs);
    }
}
