<?php

declare(strict_types=1);

/*
 * Демо-контент: слайды обложки главной.
 * Подключает App\Core\DemoSeeder::seedHeroes() через DemoSeeder::content('hero_slides').
 */

return [
    [
        'ru' => [
            'eyebrow' => 'Цель. Действие. Результат.',
            'title' => 'От стратегической цели — к измеримому результату',
            'subtitle' => 'Агентство формирует единую систему стратегического планирования: от анализа и подготовки инициатив до координации, мониторинга и оценки достигнутых результатов.',
            'cta_text' => 'Об Агентстве',
            'cta2_text' => 'Узбекистан — 2030',
        ],
        'uz' => [
            'eyebrow' => 'Maqsad. Harakat. Natija.',
            'title' => 'Strategik maqsaddan — o‘lchanadigan natijaga',
            'subtitle' => 'Agentlik strategik rejalashtirishning yagona tizimini shakllantiradi: tahlil va tashabbuslarni tayyorlashdan tortib muvofiqlashtirish, monitoring va erishilgan natijalarni baholashgacha.',
            'cta_text' => 'Agentlik haqida',
            'cta2_text' => 'O‘zbekiston — 2030',
        ],
        'data' => [
            'media_type' => 'image',
            'image' => '/uploads/public/demo-agency-hero.jpg',
            'cta_enabled' => '1', 'cta_url' => '/o-nas', 'cta_icon' => 'arrow-right',
            'cta2_enabled' => '1', 'cta2_url' => '/strategiya-2030', 'cta2_style' => 'ghost',
        ],
    ],
    [
        'ru' => [
            'eyebrow' => 'Приоритеты',
            'title' => 'Цифровая трансформация государственных услуг',
            'subtitle' => 'Единая система показателей, межведомственный обмен данными и сокращение сроков предоставления услуг.',
            'cta_text' => 'Проекты',
            'cta2_text' => '',
        ],
        'uz' => [
            'eyebrow' => 'Ustuvor yo‘nalishlar',
            'title' => 'Davlat xizmatlarining raqamli transformatsiyasi',
            'subtitle' => 'Yagona ko‘rsatkichlar tizimi, idoralararo ma’lumot almashinuvi va xizmatlar muddatlarining qisqarishi.',
            'cta_text' => 'Loyihalar',
            'cta2_text' => '',
        ],
        'data' => [
            'media_type' => 'image',
            'image' => '/uploads/public/demo-urban-development.jpg',
            'overlay' => 'gradient', 'overlay_opacity' => '55',
            'cta_enabled' => '1', 'cta_url' => '/projects', 'cta_icon' => 'arrow-right',
        ],
    ],
    [
        'ru' => [
            'eyebrow' => 'Устойчивое развитие',
            'title' => 'Зелёная энергетика и рациональное природопользование',
            'subtitle' => 'Повышение энергоэффективности, развитие солнечной и ветровой генерации.',
            'cta_text' => 'Пресс-центр',
            'cta2_text' => '',
        ],
        'uz' => [
            'eyebrow' => 'Barqaror rivojlanish',
            'title' => 'Yashil energetika va tabiatdan oqilona foydalanish',
            'subtitle' => 'Energiya samaradorligini oshirish, quyosh va shamol generatsiyasini rivojlantirish.',
            'cta_text' => 'Matbuot markazi',
            'cta2_text' => '',
        ],
        'data' => [
            'media_type' => 'image',
            'image' => '/uploads/public/demo-green-energy.jpg',
            'cta_enabled' => '1', 'cta_url' => '/press-centr', 'cta_icon' => 'arrow-right',
        ],
    ],
    [
        // Светлый кадр. Демо показывало только тёмные обложки, и по
        // нему нельзя было увидеть вторую половину поведения: белая
        // вуаль осветляет фотографию, по ней же выбирается тёмный
        // текст, а прозрачная шапка переходит в тёмный набор —
        // логотип, меню и иконки перекрашиваются вместе с кадром.
        // Заодно в заголовке показано выделение слова звёздочками.
        'ru' => [
            'eyebrow' => 'Открытость',
            'title' => 'Аналитика и данные — в *открытом* доступе',
            'subtitle' => 'Показатели реформ, отчёты и наборы данных публикуются открыто: их можно скачать, перепроверить и использовать в собственных расчётах.',
            'cta_text' => 'Аналитика',
            'cta2_text' => '',
        ],
        'uz' => [
            'eyebrow' => 'Ochiqlik',
            'title' => 'Tahlil va ma’lumotlar — *ochiq* foydalanishda',
            'subtitle' => 'Islohotlar ko‘rsatkichlari, hisobotlar va ma’lumotlar to‘plamlari ochiq e’lon qilinadi: ularni yuklab olish, qayta tekshirish va o‘z hisob-kitoblaringizda ishlatish mumkin.',
            'cta_text' => 'Tahlil',
            'cta2_text' => '',
        ],
        'data' => [
            'media_type' => 'image',
            'image' => '/uploads/public/demo-strategy-meeting.jpg',
            'overlay' => 'solid', 'overlay_color' => '#ffffff', 'overlay_opacity' => '62',
            'cta_enabled' => '1', 'cta_url' => '/analitika', 'cta_icon' => 'arrow-right',
        ],
    ],
];
