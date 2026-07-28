<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

final class OpenGraphHelper
{
    /**
     * Генерирует расширенные мета-теги Open Graph и Twitter Cards для идеального превью в Telegram, WhatsApp, Facebook и VK.
     */
    public static function render(
        string $appUrl,
        string $title,
        string $description,
        string $canonicalUrl,
        string $currentLang = "ru",
        string $image = "",
        string $type = "website",
        ?string $publishedTime = null
    ): string {
        $siteName = Setting::getLocalized("site_name", Locale::current(), "Государственный портал Республики Узбекистан");

        // 1. Дефолтное брендовое фото превью (1200x630), если у новости нет обложки
        if ($image === "") {
            $defaultOg = Setting::get("default_og_image", "") ?: Setting::get("og_default_image", "") ?: Setting::get("logo_url", "");
            if ($defaultOg === "") {
                $defaultOg = "/assets/img/hero-bg.jpg";
            }
            $image = str_starts_with($defaultOg, "/") ? rtrim($appUrl, "/") . $defaultOg : $defaultOg;
        } elseif (str_starts_with($image, "/")) {
            $image = rtrim($appUrl, "/") . $image;
        }

        // 2. Локаль для соцсетей
        $locales = [
            "ru" => "ru_RU",
            "uz" => "uz_UZ",
            "en" => "en_US",
        ];
        $locale = $locales[$currentLang] ?? "ru_RU";

        $html = "\n<!-- Rich Open Graph & Social Cards -->\n";
        $html .= "<meta property=\"og:site_name\" content=\"" . htmlspecialchars($siteName, ENT_QUOTES) . "\" />\n";
        $html .= "<meta property=\"og:locale\" content=\"" . htmlspecialchars($locale, ENT_QUOTES) . "\" />\n";
        $html .= "<meta property=\"og:type\" content=\"" . htmlspecialchars($type, ENT_QUOTES) . "\" />\n";
        $html .= "<meta property=\"og:title\" content=\"" . htmlspecialchars($title, ENT_QUOTES) . "\" />\n";

        if ($description !== "") {
            $html .= "<meta property=\"og:description\" content=\"" . htmlspecialchars($description, ENT_QUOTES) . "\" />\n";
        }

        $html .= "<meta property=\"og:url\" content=\"" . htmlspecialchars($canonicalUrl, ENT_QUOTES) . "\" />\n";

        if ($image !== "") {
            $html .= "<meta property=\"og:image\" content=\"" . htmlspecialchars($image, ENT_QUOTES) . "\" />\n";
            $html .= "<meta property=\"og:image:secure_url\" content=\"" . htmlspecialchars($image, ENT_QUOTES) . "\" />\n";
            $html .= "<meta property=\"og:image:width\" content=\"1200\" />\n";
            $html .= "<meta property=\"og:image:height\" content=\"630\" />\n";
            $html .= "<meta property=\"og:image:alt\" content=\"" . htmlspecialchars($title, ENT_QUOTES) . "\" />\n";
        }

        if ($publishedTime !== null && $publishedTime !== "") {
            $time = strtotime($publishedTime);
            if ($time !== false) {
                $html .= "<meta property=\"article:published_time\" content=\"" . htmlspecialchars(date("c", $time), ENT_QUOTES) . "\" />\n";
            }
        }

        // Twitter / Telegram Card Optimization
        $html .= "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
        $html .= "<meta name=\"twitter:title\" content=\"" . htmlspecialchars($title, ENT_QUOTES) . "\" />\n";
        if ($description !== "") {
            $html .= "<meta name=\"twitter:description\" content=\"" . htmlspecialchars($description, ENT_QUOTES) . "\" />\n";
        }
        if ($image !== "") {
            $html .= "<meta name=\"twitter:image\" content=\"" . htmlspecialchars($image, ENT_QUOTES) . "\" />\n";
        }

        return $html;
    }
}

