<?php

declare(strict_types=1);

use App\Core\Media;
use App\Core\NewsBadge;
use App\Core\StyleVars;

/*
 * Переменные элемента без атрибута style (StyleVars). Атрибут style держит в
 * публичной CSP `style-src 'unsafe-inline'`; переменные фокуса картинки,
 * цвета метки, обложки новости и процентов опроса переехали в правило класса.
 */
test('StyleVars: класс и правило, тег один раз за запрос, фрейм копит правила', function (): void {
    StyleVars::reset();

    $first = StyleVars::apply(['--poll-percent' => '42%']);
    assert_true(str_starts_with($first['class'], 'sv-'), 'класс переменных');
    assert_same('<style>.' . $first['class'] . '{--poll-percent:42%}</style>', $first['style']);
    assert_same('', StyleVars::apply(['--poll-percent' => '42%'])['style'], 'повторное правило тегом не выводится');

    StyleVars::begin();
    $inFrame = StyleVars::apply(['--poll-percent' => '7%']);
    $css = StyleVars::end();
    assert_same('', $inFrame['style'], 'внутри фрейма тега нет');
    assert_same('.' . $inFrame['class'] . '{--poll-percent:7%}', $css, 'правило ушло во фрейм');

    StyleVars::reset();
});

test('StyleVars: значение не может закрыть объявление или тег', function (): void {
    StyleVars::reset();
    foreach (['red;background:url(x)', 'a}b{', '</style><script>', "a\nb", 'a/*b*/'] as $bad) {
        assert_same(['class' => '', 'style' => ''], StyleVars::apply(['--x' => $bad]), 'пропущено: ' . $bad);
    }
    assert_same(['class' => '', 'style' => ''], StyleVars::apply(['color' => 'red']), 'только пользовательские переменные');
    assert_same("url('/a\\'b.jpg')", StyleVars::url("/a'b.jpg"));
    StyleVars::reset();
});

test('Публичная разметка не пишет атрибут style', function (): void {
    StyleVars::reset();
    $picture = Media::picture('/uploads/public/demo.jpg', 'Демо', 30, 70, 'news-card__img');
    assert_not_contains(' style="', $picture, 'картинка с точкой фокуса');
    assert_contains('media-focal', $picture);

    $badge = NewsBadge::render('Важно', '#0b1a30');
    assert_not_contains(' style="', $badge, 'цветная метка');
    assert_contains('--news-badge-bg:#0b1a30', $badge, 'цвет метки в правиле класса');

    // Шаблоны блоков и публичные вьюхи не выводят style сами: переменные —
    // через $templateCss или StyleVars.
    $offenders = [];
    $files = array_merge(glob(APP_ROOT . '/templates/blocks/*.php') ?: [], glob(APP_ROOT . '/app/Views/site/*.php') ?: []);
    foreach ($files as $file) {
        if (preg_match('/\sstyle="/', (string) file_get_contents($file)) === 1) {
            $offenders[] = basename($file);
        }
    }
    assert_same([], $offenders, 'атрибут style в шаблоне: ' . implode(', ', $offenders));
    StyleVars::reset();
});
