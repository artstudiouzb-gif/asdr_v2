<?php

use App\Core\Hero\HeroRenderer;
use App\Core\Hero\HeroV2;

/**
 * «Обложка страницы»: слайды лежат в самом блоке, настроек — двадцать две
 * против семидесяти девяти у обложки-типа контента.
 *
 * Шаблон ничего не рисует сам. Разметку, scoped CSS и предзагрузку первого
 * кадра собирает `HeroRenderer` — тот же, что и у прежней обложки. Своя
 * разметка была бы третьей реализацией одного и того же: тот же кадр, то же
 * наложение, та же карусель. Блоки-близнецы расходятся при первой правке,
 * поэтому здесь только приведение настроек к контракту рендерера (HeroV2).
 *
 * @var array $data
 * @var int $blockId
 */
$slides = HeroV2::slides($data);
if ($slides === []) {
    return;
}

$rendered = HeroRenderer::render(
    ['id' => $blockId, 'name' => ''],
    $slides,
    HeroV2::settings($data),
    $blockId,
    (string) ($data['_heading_tag'] ?? 'h1')
);

$templateCss = $rendered['css'];
echo $rendered['html'];
