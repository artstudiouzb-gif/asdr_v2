<?php

declare(strict_types=1);

/*
 * Демо-контент: записи каталогов по типам: документы, вакансии, тендеры, мероприятия.
 * Подключает App\Core\DemoSeeder::seedEntries() через DemoSeeder::content('entries').
 */

return [
    'documenty' => [
        ['Методология мониторинга Стратегии «Узбекистан–2030»', 'metodologiya-monitoringa-2030', ['doc_number' => 'ММ-2030', 'doc_date' => '2026-07-15', 'category' => 'Методология', 'summary' => 'Единые подходы к оценке достижения целей, индикаторов и результатов реформ.', 'file' => '/catalog/documenty/metodologiya-monitoringa-2030']],
        ['Аналитический отчёт о ходе структурных реформ', 'otchet-strukturnye-reformy', ['doc_number' => 'АО-07/26', 'doc_date' => '2026-07-01', 'category' => 'Аналитические отчёты', 'summary' => 'Результаты мониторинга структурных преобразований и рекомендации по дальнейшим шагам.', 'file' => '/catalog/documenty/otchet-strukturnye-reformy']],
        ['Регламент межведомственной координации', 'reglament-koordinacii', ['doc_number' => 'РК-12', 'doc_date' => '2026-06-20', 'category' => 'Регламенты', 'summary' => 'Порядок обмена данными, согласования решений и контроля исполнения.', 'file' => '/catalog/documenty/reglament-koordinacii']],
        ['Обзор международного опыта стратегического планирования', 'obzor-mezhdunarodnogo-opyta', ['doc_number' => 'ОМО-04', 'doc_date' => '2026-06-05', 'category' => 'Обзоры', 'summary' => 'Сравнение современных моделей стратегического управления и оценки реформ.', 'file' => '/catalog/documenty/obzor-mezhdunarodnogo-opyta']],
    ],
    'vakansii' => [
        ['Ведущий специалист отдела ИТ', 'vedushchiy-it', ['department' => 'Отдел информационных технологий', 'salary' => 'по договорённости', 'deadline' => '2026-08-31', 'requirements' => 'Высшее образование, опыт от 3 лет, знание PHP/MySQL.', 'duties' => 'Сопровождение и развитие информационных систем.']],
        ['Юрисконсульт', 'yuriskonsult', ['department' => 'Юридический отдел', 'salary' => 'от 8 000 000 сум', 'deadline' => '2026-08-20', 'requirements' => 'Высшее юридическое образование, опыт от 2 лет.', 'duties' => 'Правовое сопровождение деятельности организации.']],
        ['Специалист по кадрам', 'specialist-kadry', ['department' => 'Отдел кадров', 'salary' => 'от 6 000 000 сум', 'deadline' => '2026-09-10', 'requirements' => 'Опыт кадрового делопроизводства.', 'duties' => 'Ведение кадрового учёта и документации.']],
        ['Пресс-секретарь', 'press-sekretar', ['department' => 'Пресс-служба', 'salary' => 'по итогам собеседования', 'deadline' => '2026-08-05', 'requirements' => 'Опыт в СМИ или PR, грамотная речь.', 'duties' => 'Взаимодействие со СМИ, ведение новостей сайта.']],
    ],
    'tendery' => [
        ['Развитие аналитической платформы мониторинга', 'platforma-monitoringa-zakupka', ['tender_number' => 'T-2026-031', 'budget' => 'по итогам конкурса', 'start_date' => '2026-07-10', 'deadline' => '2026-08-20', 'summary' => 'Разработка модулей визуализации показателей и межведомственного обмена данными.', 'file' => '/catalog/tendery/platforma-monitoringa-zakupka']],
        ['Исследование социально-экономической динамики регионов', 'issledovanie-regionov', ['tender_number' => 'T-2026-034', 'budget' => 'по итогам конкурса', 'start_date' => '2026-07-18', 'deadline' => '2026-09-01', 'summary' => 'Комплексное исследование факторов роста и качества жизни в регионах.', 'file' => '/catalog/tendery/issledovanie-regionov']],
        ['Организация международного экспертного форума', 'ekspertnyy-forum', ['tender_number' => 'T-2026-038', 'budget' => 'по итогам конкурса', 'start_date' => '2026-07-25', 'deadline' => '2026-09-15', 'summary' => 'Организационное и техническое сопровождение экспертного форума.', 'file' => '/catalog/tendery/ekspertnyy-forum']],
    ],
    'meropriyatiya' => [
        ['Открытая презентация системы мониторинга Стратегии', 'prezentaciya-monitoringa-strategii', ['event_date' => '2026-09-12', 'event_time' => '10:00', 'location' => 'Ташкент, конференц-зал Агентства', 'banner_image' => '/uploads/public/demo-strategy-meeting.jpg', 'summary' => 'Презентация цифровой системы мониторинга целей и показателей Стратегии «Узбекистан–2030».']],
        ['Экспертный диалог по региональному развитию', 'dialog-regionalnoe-razvitie', ['event_date' => '2026-10-03', 'event_time' => '15:00', 'location' => 'Гибридный формат', 'banner_image' => '/uploads/public/demo-urban-development.jpg', 'summary' => 'Обсуждение новых подходов к развитию регионов и оценке качества государственных программ.']],
        ['Форум стратегических инициатив', 'forum-strategicheskih-iniciativ', ['event_date' => '2026-11-18', 'event_time' => '09:30', 'location' => 'Ташкент', 'banner_image' => '/uploads/public/demo-agency-hero.jpg', 'summary' => 'Площадка для обмена опытом между государственными органами, экспертами и международными партнёрами.']],
    ],
];
