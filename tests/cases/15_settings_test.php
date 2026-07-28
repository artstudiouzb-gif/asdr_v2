<?php

declare(strict_types=1);

use App\Core\Analytics;
use App\Core\Database;
use App\Core\SettingsValidator;
use App\Models\Setting;

test('Setting: getLocalized возвращает языковой вариант при наличии и базовый откат', function () {
    Setting::set('site_name', 'Базовый сайт');
    Setting::set('site_name_uz', 'Oʻzbekiston portali');

    assert_same('Oʻzbekiston portali', Setting::getLocalized('site_name', 'uz'));
    assert_same('Базовый сайт', Setting::getLocalized('site_name', 'ru'));
    assert_same('Базовый сайт', Setting::getLocalized('site_name', 'en'));
});

test('SettingsValidator: GA ID только формата G-XXXX', function () {
    assert_same('G-ABCD1234', SettingsValidator::gaId('g-abcd1234'));
    assert_same('', SettingsValidator::gaId('<script>alert(1)</script>'));
    assert_same('', SettingsValidator::gaId('UA-12345'));
});

test('SettingsValidator: Метрика — только цифры', function () {
    assert_same('12345678', SettingsValidator::ymId('12345678'));
    assert_same('', SettingsValidator::ymId('12ab'));
    assert_same('', SettingsValidator::ymId('<script>'));
});

test('SettingsValidator: HEX-цвет и дефолт', function () {
    assert_same('#1a2b3c', SettingsValidator::hexColor('#1A2B3C'));
    assert_same('#1a1a1a', SettingsValidator::hexColor('red', '#1a1a1a'));
    assert_same('#1a1a1a', SettingsValidator::hexColor('#xyz', '#1a1a1a'));
});

test('SettingsValidator: короткое имя PWA обрезается до 12 и чистит теги', function () {
    assert_same('Очень длинно', SettingsValidator::shortName('Очень длинное имя'));
    assert_same('safe', SettingsValidator::shortName("sa<>fe"));
    assert_same(12, mb_strlen(SettingsValidator::shortName(str_repeat('x', 30))));
});

test('SettingsValidator: неотрицательное целое', function () {
    assert_same(30, SettingsValidator::nonNegativeInt('30'));
    assert_same(0, SettingsValidator::nonNegativeInt('-5', 0));
    assert_same(0, SettingsValidator::nonNegativeInt('abc', 0));
});

test('Analytics: скрипт строится из ID без сырого JS', function () {
    $s = Analytics::buildScript('G-TEST1234', '99887766');
    assert_contains('G-TEST1234', $s);
    assert_contains('googletagmanager', $s);
    assert_contains('99887766', $s);
    assert_contains('mc.yandex.ru', $s);
    // Мусорный ввод не попадает в скрипт (валидируется).
    $safe = Analytics::buildScript('"><script>evil()</script>', 'x');
    assert_not_contains('evil', $safe);
    assert_same('', $safe);
});
