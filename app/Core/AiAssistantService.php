<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * ИИ-Ассистент редактора.
 * Автоматически генерирует SEO-метаданные, анонсы и переводит текст.
 */
final class AiAssistantService
{
    public static function generateMetaTitle(string $title, string $content = ''): string
    {
        $cleanTitle = trim(strip_tags($title));
        if ($cleanTitle === '') {
            return '';
        }
        return TextProcessor::truncate($cleanTitle, 60);
    }

    public static function generateMetaDescription(string $content, int $maxLength = 160): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        if ($clean === '') {
            return '';
        }
        return TextProcessor::truncate($clean, $maxLength);
    }

    public static function generateExcerpt(string $content, int $length = 250): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        if ($clean === '') {
            return '';
        }

        // Берем первое или первое и второе предложение
        $sentences = preg_split('/(?<=[.!?])\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        if ($sentences !== false && !empty($sentences[0])) {
            $lead = $sentences[0];
            if (isset($sentences[1]) && mb_strlen($lead) < 100) {
                $lead .= ' ' . $sentences[1];
            }
            return TextProcessor::truncate($lead, $length);
        }

        return TextProcessor::truncate($clean, $length);
    }

    /**
     * Попытка перевода текста через внешний API (если задан ключ) или возвращает разметку-каркас.
     */
    public static function translate(string $text, string $sourceLang, string $targetLang): string
    {
        $apiKey = Setting::get('ai_api_key', '');
        if ($apiKey === '') {
            return $text; // Фолбэк на оригинальный текст при отсутствии ключа
        }

        // Если задан ключ API — выполняем HTTP-запрос к ИИ-сервису
        try {
            $response = Http::postJson('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => "You are a professional editorial translator from {$sourceLang} to {$targetLang}."],
                    ['role' => 'user', 'content' => $text],
                ],
            ], [
                'Authorization: Bearer ' . $apiKey,
            ]);

            return $response['choices'][0]['message']['content'] ?? $text;
        } catch (\Throwable $e) {
            Logger::error('AI Translation error: ' . $e->getMessage());
            return $text;
        }
    }
}
