<?php

declare(strict_types=1);

/*
 * SEO-превью формы новости вызывало plainLeadText() вне области видимости,
 * где функция объявлена, — каждая загрузка /admin/news/create падала с
 * ReferenceError, и превью не обновлялось. Функция экспортируется явно.
 */
test('SEO-превью новости берёт текст лида через экспорт, а не чужую область видимости', function (): void {
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/admin.js');
    assert_contains('window.asdrPlainLeadText = plainLeadText;', $js);
    assert_contains('window.asdrPlainLeadText(descInput)', $js);
    assert_not_contains('descVal = plainLeadText(', $js, 'вызов вне IIFE с объявлением');
});
