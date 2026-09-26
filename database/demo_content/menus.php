<?php

declare(strict_types=1);

/*
 * Демо-контент: пункты меню шапки и подвала.
 * Подключает App\Core\DemoSeeder::seedMenu() через DemoSeeder::content('menus').
 */

return [
    'ru' => [
        ['title' => 'Агентство', 'type' => 'page', 'value' => 'o-nas', 'mega' => 0, 'children' => [
            ['Об агентстве', 'page', 'o-nas'],
            ['Руководство', 'page', 'rukovodstvo'],
            ['Структура', 'page', 'struktura'],
            ['Директор', 'page', 'direktor'],
            ['Первый заместитель директора', 'page', 'pervyy-zamestitel-direktora'],
            ['Противодействие коррупции', 'page', 'antikorrupciya'],
        ]],
        ['title' => 'Деятельность', 'type' => 'page', 'value' => 'napravleniya', 'mega' => 0, 'children' => [
            ['Стратегия «Узбекистан–2030»', 'page', 'strategiya-2030', '2030'],
            ['Приоритетные направления', 'page', 'napravleniya'],
            ['Устойчивый экономический рост', 'page', 'ustoychivyy-ekonomicheskiy-rost'],
            ['Проекты и инициативы', 'custom', '/projects'],
            ['Аналитика', 'page', 'analitika'],
        ]],
        ['title' => 'Пресс-центр', 'type' => 'page', 'value' => 'press-centr', 'mega' => 0, 'children' => [
            ['Новости', 'news_index', ''],
            ['Мероприятия', 'page', 'meropriyatiya'],
            ['Фотоальбомы', 'custom', '/albums'],
            ['Видеоматериалы', 'page', 'media'],
        ]],
        ['title' => 'Открытые данные', 'type' => 'custom', 'value' => '/catalog/documenty', 'mega' => 0, 'children' => [
            ['Документы', 'custom', '/catalog/documenty'],
            ['Тендеры', 'custom', '/catalog/tendery'],
            ['Вакансии', 'custom', '/catalog/vakansii'],
        ]],
        ['title' => 'Контакты', 'type' => 'page', 'value' => 'kontakty', 'mega' => 0, 'children' => []],
    ],
    'uz' => [
        ['title' => 'Agentlik', 'type' => 'page', 'value' => 'o-nas', 'mega' => 0, 'children' => [
            ['Agentlik haqida', 'page', 'o-nas'],
            ['Rahbariyat', 'page', 'rukovodstvo'],
            ['Tuzilma', 'page', 'struktura'],
            ['Direktor', 'page', 'direktor'],
            ['Direktorning birinchi o‘rinbosari', 'page', 'pervyy-zamestitel-direktora'],
            ['Korrupsiyaga qarshi kurash', 'page', 'antikorrupciya'],
        ]],
        ['title' => 'Faoliyat', 'type' => 'page', 'value' => 'napravleniya', 'mega' => 0, 'children' => [
            ['«O‘zbekiston–2030» strategiyasi', 'page', 'strategiya-2030', '2030'],
            ['Ustuvor yo‘nalishlar', 'page', 'napravleniya'],
            ['Barqaror iqtisodiy o‘sish', 'page', 'ustoychivyy-ekonomicheskiy-rost'],
            ['Loyihalar va tashabbuslar', 'custom', '/projects'],
            ['Tahlil', 'page', 'analitika'],
        ]],
        ['title' => 'Matbuot markazi', 'type' => 'page', 'value' => 'press-centr', 'mega' => 0, 'children' => [
            ['Yangiliklar', 'news_index', ''],
            ['Tadbirlar', 'page', 'meropriyatiya'],
            ['Fotoalbomlar', 'custom', '/albums'],
            ['Videomateriallar', 'page', 'media'],
        ]],
        ['title' => 'Ochiq ma’lumotlar', 'type' => 'custom', 'value' => '/catalog/documenty', 'mega' => 0, 'children' => [
            ['Hujjatlar', 'custom', '/catalog/documenty'],
            ['Tenderlar', 'custom', '/catalog/tendery'],
            ['Bo‘sh ish o‘rinlari', 'custom', '/catalog/vakansii'],
        ]],
        ['title' => 'Aloqa', 'type' => 'page', 'value' => 'kontakty', 'mega' => 0, 'children' => []],
    ],
    'en' => [
        ['title' => 'Agency', 'type' => 'page', 'value' => 'o-nas', 'mega' => 0, 'children' => [
            ['About Agency', 'page', 'o-nas'],
            ['Leadership', 'page', 'rukovodstvo'],
            ['Structure', 'page', 'struktura'],
            ['Director', 'page', 'direktor'],
            ['First Deputy Director', 'page', 'pervyy-zamestitel-direktora'],
            ['Anti-Corruption', 'page', 'antikorrupciya'],
        ]],
        ['title' => 'Activity', 'type' => 'page', 'value' => 'napravleniya', 'mega' => 0, 'children' => [
            ['Strategy «Uzbekistan–2030»', 'page', 'strategiya-2030', '2030'],
            ['Priority Areas', 'page', 'napravleniya'],
            ['Sustainable Economic Growth', 'page', 'ustoychivyy-ekonomicheskiy-rost'],
            ['Projects and Initiatives', 'custom', '/projects'],
            ['Analytics', 'page', 'analitika'],
        ]],
        ['title' => 'Press Center', 'type' => 'page', 'value' => 'press-centr', 'mega' => 0, 'children' => [
            ['News', 'news_index', ''],
            ['Events', 'page', 'meropriyatiya'],
            ['Photo Albums', 'custom', '/albums'],
            ['Media Library', 'page', 'media'],
        ]],
        ['title' => 'Open Data', 'type' => 'custom', 'value' => '/catalog/documenty', 'mega' => 0, 'children' => [
            ['Documents', 'custom', '/catalog/documenty'],
            ['Tenders', 'custom', '/catalog/tendery'],
            ['Vacancies', 'custom', '/catalog/vakansii'],
        ]],
        ['title' => 'Contacts', 'type' => 'page', 'value' => 'kontakty', 'mega' => 0, 'children' => []],
    ],
];
