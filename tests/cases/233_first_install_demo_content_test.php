<?php

declare(strict_types=1);

/**
 * Первая установка должна выглядеть как готовый сайт Агентства, но не должна
 * выдавать условные demo-цифры за реальные результаты. Главная поэтому берёт
 * проверяемые количества и направления из той же фикстуры, что и страницы
 * «Об Агентстве» / «Руководство».
 */
test('Первая установка: цифры главной подтверждаются фикстурой Агентства', function (): void {
    $agency = require APP_ROOT . '/database/content/agency_content.php';

    foreach (['ru' => 'home_blocks.json', 'uz' => 'home_blocks_uz.json'] as $lang => $file) {
        $home = json_decode(
            (string) file_get_contents(APP_ROOT . '/database/demo_assets/' . $file),
            true
        );
        assert_true(is_array($home), "главная {$lang} валидна");

        $aboutBlocks = $agency['pages']['o-nas'][$lang]['blocks'] ?? [];
        $findAgencyBlock = static function (string $type) use ($aboutBlocks): array {
            foreach ($aboutBlocks as $block) {
                if (($block[0] ?? '') === $type) {
                    return is_array($block[2] ?? null) ? $block[2] : [];
                }
            }
            return [];
        };

        $expected = [
            count($findAgencyBlock('advantages')['items'] ?? []),
            count($findAgencyBlock('stages')['items'] ?? []),
            count($findAgencyBlock('docs_list')['items'] ?? []),
            count($agency['team'] ?? []),
        ];

        $counterValues = [];
        foreach ($home as $block) {
            if (($block['type'] ?? '') !== 'counters') {
                continue;
            }
            foreach ($block['data']['items'] ?? [] as $item) {
                $counterValues[] = (int) ($item['value'] ?? 0);
                assert_same('', (string) ($item['suffix'] ?? ''), "{$lang}: демо-число не маскируется знаком +");
            }
        }

        assert_same($expected, $counterValues, "{$lang}: счётчики соответствуют фактической фикстуре");
    }
});

test('Первая установка: направления главной совпадают с разделом Об Агентстве', function (): void {
    $agency = require APP_ROOT . '/database/content/agency_content.php';

    foreach (['ru' => 'home_blocks.json', 'uz' => 'home_blocks_uz.json'] as $lang => $file) {
        $home = json_decode(
            (string) file_get_contents(APP_ROOT . '/database/demo_assets/' . $file),
            true
        );
        assert_true(is_array($home));

        $agencyTitles = [];
        foreach ($agency['pages']['o-nas'][$lang]['blocks'] ?? [] as $block) {
            if (($block[0] ?? '') !== 'advantages') {
                continue;
            }
            foreach ($block[2]['items'] ?? [] as $item) {
                $agencyTitles[] = (string) ($item['title'] ?? '');
            }
        }

        $homeTitles = [];
        foreach ($home as $block) {
            if (($block['type'] ?? '') !== 'cards_grid' || ($block['data']['variant'] ?? '') === 'image') {
                continue;
            }
            foreach ($block['data']['items'] ?? [] as $item) {
                $homeTitles[] = (string) ($item['title'] ?? '');
            }
            break;
        }

        assert_same($agencyTitles, $homeTitles, "{$lang}: направления не расходятся с официальной страницей");
    }
});
