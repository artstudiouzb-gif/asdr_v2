<?php

declare(strict_types=1);

/*
 * Язык движения фронта: одна кривая, одна шкала длительностей и переход
 * между страницами, в котором обложка новости переезжает из карточки в статью.
 *
 * Кривых было восемь, две из них с перелётом: элемент проскакивал конечную
 * точку и возвращался, и наведение читалось как щелчок. Соседние компоненты
 * двигались по-разному, поэтому общего характера у сайта не складывалось.
 */

/** Публичный CSS: тема, база, части и блоки. Админка живёт своей жизнью. */
function motion_public_css_files(): array
{
    $files = [];
    foreach ([APP_ROOT . '/public/assets/css/*.css', APP_ROOT . '/public/assets/css/blocks/*.css'] as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            $name = basename($file);
            if (str_contains($name, '.min.') || str_starts_with($name, 'admin')) {
                continue;
            }
            $files[] = $file;
        }
    }

    return $files;
}

test('Кривая движения одна на весь публичный фронт', function () {
    $curves = [];
    foreach (motion_public_css_files() as $file) {
        $css = (string) file_get_contents($file);
        if (preg_match_all('/cubic-bezier\([^)]*\)/', $css, $m) === false) {
            continue;
        }
        foreach ($m[0] as $curve) {
            $curves[basename($file)][] = $curve;
        }
    }

    // Кривая записывается значением в двух местах: объявление токенов в теме
    // и запасной вариант в портале файлов — у его страницы входа темы нет.
    assert_same(['gov-theme.css', 'repo.css'], array_keys($curves), 'своих кривых у компонентов не осталось');
    assert_same(
        ['cubic-bezier(.22, 1, .36, 1)', 'cubic-bezier(.65, 0, .35, 1)'],
        $curves['gov-theme.css'],
        'две кривые: выход и «туда-обратно», всё остальное — var(--ease-*)'
    );
    $repo = (string) file_get_contents(APP_ROOT . '/public/assets/css/repo.css');
    assert_same(
        substr_count($repo, 'cubic-bezier('),
        substr_count($repo, 'var(--ease-soft, cubic-bezier(') + substr_count($repo, 'var(--ease-inout-soft, cubic-bezier('),
        'в портале кривая допустима только как запасное значение токена'
    );
});

test('Токены движения объявлены там, где их видят обе поверхности', function () {
    $theme = theme_css();
    $base = (string) file_get_contents(APP_ROOT . '/public/assets/css/frontend.css');

    foreach (['--ease-soft:', '--ease-inout-soft:', '--dur-quick:', '--dur-base:', '--dur-slow:', '--dur-reveal:'] as $token) {
        assert_contains($token, $theme, "токен {$token} объявлен в теме");
    }
    // Тему грузит и публичная страница, и портал файлов /repo, у которого
    // базового файла нет: объявление в базе оставило бы половину переходов
    // портала без кривой — а переменная без значения делает недействительным
    // всё объявление, то есть анимация исчезает молча.
    assert_not_contains('--ease-soft:', $base, 'база токены не объявляет — иначе значения разъедутся');
    assert_contains('var(--dur-reveal) var(--ease-soft)', $base, 'появление при прокрутке берёт токены');

    // У страницы входа в портал нет даже темы, поэтому там нужен запасной вариант.
    $repo = (string) file_get_contents(APP_ROOT . '/public/assets/css/repo.css');
    if (str_contains($repo, 'var(--ease-soft')) {
        assert_contains('var(--ease-soft, cubic-bezier(.22, 1, .36, 1))', $repo, 'на странице входа темы нет');
    }
});

test('Переход между страницами: содержимое проявляется мягко', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/frontend.css');

    // Умолчание браузера — короткий линейный перелив: переход читается как
    // подмена картинки. Новая страница входит дольше старой и с подъёмом.
    assert_contains('::view-transition-old(root) { animation: vt-content-out var(--dur-base) var(--ease-soft) both; }', $css);
    assert_contains('::view-transition-new(root) { animation: vt-content-in var(--dur-slow) var(--ease-soft) both; }', $css);
    assert_contains('@keyframes vt-content-in { from { opacity: 0; transform: translateY(10px); } }', $css);

    // «Меньше движения» гасит и это: псевдоэлементы перехода живут отдельным
    // деревом у корня, поэтому у них своё правило со звёздочкой.
    $a11y = (string) file_get_contents(APP_ROOT . '/public/assets/css/a11y.css');
    assert_contains('html[data-a11y-motion="off"]::view-transition-group(*)', $a11y);
});

test('Обложка новости переезжает из карточки в статью', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/frontend.css');
    $view = (string) file_get_contents(APP_ROOT . '/app/Views/site/news_show.php');
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');

    assert_contains('[data-news-cover] { view-transition-name: news-cover; }', $css);
    // У снимка своя анимация с умолчанием браузера: без этого правила обложка
    // приезжала бы раньше, чем появится текст вокруг неё.
    assert_contains('::view-transition-group(news-cover)', $css);

    // Имя обязано быть уникальным: два элемента с одним именем отменяют
    // переход целиком. На статье цель называет разметка — по одной на каждую
    // ветку макета, а ветки взаимоисключающие.
    assert_same(3, substr_count($view, 'data-news-cover'), 'обложка, видео и галерея — по одной цели');
    assert_contains('$hasMedia = !$isPremium', $view, 'ветки макета не пересекаются');

    // На ленте карточек десятки, поэтому имя получает только та, по которой
    // кликнули, — в обработчике, до ухода со страницы.
    assert_contains("cover.style.viewTransitionName = 'news-cover';", $js);
    assert_contains("window.addEventListener('pageshow', clear);", $js, 'возврат «назад» отдаёт страницу с уже проставленным именем');
});

test('Минификатор не выбрасывает переменную из сокращённого transition', function () {
    // Отдельные грабли проекта: `transition` с пользовательской переменной
    // минификатор умеет терять, и видно это только замером — правило просто
    // исчезает из бандла. Здесь переменная стоит функцией времени, и сверка
    // числа вхождений доказывает, что ни одно объявление не потерялось.
    $sources = 0;
    foreach ([
        'gov-fonts.css', 'frontend.css', 'gov-theme.css', 'rich-content.css',
        'a11y.css', 'public-layout-polish.css', 'public-editorial-pages.css',
    ] as $name) {
        $sources += substr_count((string) file_get_contents(APP_ROOT . '/public/assets/css/' . $name), 'var(--ease-soft)');
    }

    $bundle = (string) file_get_contents(APP_ROOT . '/public/assets/css/public.min.css');
    assert_true($sources > 0, 'кривая берётся токеном хотя бы где-то');
    assert_same($sources, substr_count($bundle, 'var(--ease-soft)'), 'в бандле столько же объявлений, сколько в исходниках');
});
