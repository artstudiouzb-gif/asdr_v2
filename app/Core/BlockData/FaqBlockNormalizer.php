<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\TextProcessor;

final class FaqBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        $items = [];
        foreach ((array) ($input['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }

            $items[] = [
                'question' => TextProcessor::typographPlain($question, $locale),
                'answer' => TextProcessor::process($answer, $locale),
            ];
        }

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'items' => $items,
        ];
    }
}
