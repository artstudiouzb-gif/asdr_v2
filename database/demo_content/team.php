<?php

declare(strict_types=1);

/*
 * Демо-контент: сотрудники для раздела «Руководство».
 * Подключает App\Core\DemoSeeder::seedTeam() через DemoSeeder::content('team').
 */

return [
    // Руководство — реальное (см. database/content/agency_content.php),
    // остальные сотрудники демонстрационные.
    ['Умурзаков Сардор Уктамович', 'Директор', 'Umurzoqov Sardor O‘ktamovich', 'Direktor', '', '', '', ''],
    ['Абдукодиров Абдулла Мамасаатович', 'Первый заместитель директора', 'Abduqodirov Abdulla Mamasaatovich', 'Direktor birinchi o‘rinbosari', '', '', '', ''],
    [
        'Каримов Бехзод Шухратович', 'Руководитель сектора',
        'Karimov Behzod Shuhratovich', 'Shoʻba rahbari',
        'Сектор анализа и исследований', '',
        'Tahlil va tadqiqotlar shoʻbasi', '',
    ],
    [
        'Исмоилова Дилноза Фарходовна', 'Руководитель сектора',
        'Ismoilova Dilnoza Farhodovna', 'Shoʻba rahbari',
        'Сектор по связям с общественностью', '',
        'Jamoatchilik bilan aloqalar shoʻbasi', '',
    ],
    [
        'Ражабов Отабек Улугбекович', 'Главный специалист',
        'Rajabov Otabek Ulugʻbekovich', 'Bosh mutaxassis',
        'Информационно-аналитический и организационный сектор', 'группа по работе с кадрами',
        'Axborot-tahlil va tashkiliy masalalar shoʻbasi', 'kadrlar bilan ishlash guruhi',
    ],
    [
        'Хамидова Севара Рустамовна', 'Ведущий специалист',
        'Hamidova Sevara Rustamovna', 'Yetakchi mutaxassis',
        'Информационно-аналитический и организационный сектор', 'первый отдел',
        'Axborot-tahlil va tashkiliy masalalar shoʻbasi', 'birinchi boʻlim',
    ],
];
