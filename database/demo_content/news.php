<?php

declare(strict_types=1);

/*
 * Демо-контент: новости ленты (заголовок, анонс, дата, обложка).
 * Подключает App\Core\DemoSeeder::seedNews() через DemoSeeder::content('news').
 */

return [
    [
        'title' => 'Представлена цифровая платформа мониторинга реформ',
        'slug' => 'platforma-monitoringa-reform',
        'excerpt' => 'Новая платформа объединяет ключевые показатели Стратегии «Узбекистан–2030» и позволяет отслеживать достижение результатов.',
        'category' => 'Цифровизация',
        'image' => '/uploads/public/demo-agency-hero.jpg',
        'hashtags' => '#Узбекистан2030 #цифровизация #реформы',
        'layout' => 'standard',
        'uz_title' => 'Islohotlarni monitoring qilish raqamli platformasi taqdim etildi',
        'uz_excerpt' => 'Yangi platforma «O‘zbekiston–2030» strategiyasining asosiy ko‘rsatkichlarini birlashtiradi va natijalar ijrosini kuzatish imkonini beradi.',
        'uz_category' => 'Raqamlashtirish',
        'uz_hashtags' => '#O‘zbekiston2030 #raqamlashtirish',
    ],
    [
        'title' => 'Обсуждены приоритеты устойчивого регионального развития',
        'slug' => 'regionalnoe-razvitie-prioritety',
        'excerpt' => 'Эксперты и представители регионов рассмотрели проекты инфраструктуры, занятости и развития человеческого капитала.',
        'category' => 'Региональное развитие',
        'image' => '/uploads/public/demo-urban-development.jpg',
        'hashtags' => '#регионы #инфраструктура #развитие',
        'layout' => 'side_image',
        'uz_title' => 'Hududlarni barqaror rivojlantirish ustuvor yo‘nalishlari muhokama qilindi',
        'uz_excerpt' => 'Ekspertlar va hududlar vakillari infratuzilma, bandlik va inson kapitalini rivojlantirish loyihalarini ko‘rib chiqdilar.',
        'uz_category' => 'Hududiy rivojlanish',
        'uz_hashtags' => '#hududlar #infratuzilma',
    ],
    [
        'title' => 'Опубликован аналитический обзор социально-экономической динамики',
        'slug' => 'analiticheskiy-obzor-dinamiki',
        'excerpt' => 'Обзор содержит ключевые тенденции, сценарные оценки и рекомендации для дальнейшего повышения устойчивости экономики.',
        'category' => 'Аналитика',
        'image' => '/uploads/public/demo-strategy-meeting.jpg',
        'hashtags' => '#аналитика #экономика #прогноз',
        'layout' => 'premium',
        'uz_title' => 'Ijtimoiy-iqtisodiy dinamika bo‘yicha tahliliy sharh e’lon qilindi',
        'uz_excerpt' => 'Sharh asosiy tendensiyalar, ssenariy baholari va iqtisodiyot barqarorligini oshirish bo‘yicha tavsiyalarni qamrab oladi.',
        'uz_category' => 'Tahlil',
        'uz_hashtags' => '#tahlil #iqtisodiyot',
    ],
    [
        'title' => 'Расширяется портфель проектов зелёной экономики',
        'slug' => 'portfel-zelenoy-ekonomiki',
        'excerpt' => 'В портфель включены инициативы в сфере возобновляемой энергетики, энергоэффективности и устойчивой инфраструктуры.',
        'category' => 'Зелёная экономика',
        'image' => '/uploads/public/demo-green-energy.jpg',
        'hashtags' => '#зелёнаяэкономика #энергетика #ESG',
        'layout' => 'gallery',
        // Слайдер новости — единственное место, где подпись и автор
        // снимка видны текстом. Без снимков демо этого не показывало.
        'gallery' => [
            ['/uploads/public/demo-green-energy.jpg', 'Ввод в эксплуатацию объекта возобновляемой энергетики', 'пресс-служба Агентства'],
            ['/uploads/public/demo-urban-development.jpg', 'Устойчивая городская инфраструктура', 'пресс-служба Агентства'],
            ['/uploads/public/demo-strategy-meeting.jpg', 'Обсуждение портфеля проектов', ''],
        ],
        'uz_title' => 'Yashil iqtisodiyot loyihalari portfeli kengaymoqda',
        'uz_excerpt' => 'Portfelga qayta tiklanuvchi energiya, energiya samaradorligi va barqaror infratuzilma tashabbuslari kiritildi.',
        'uz_category' => 'Yashil iqtisodiyot',
        'uz_hashtags' => '#yashiliqtisodiyot #energetika',
    ],
    [
        'title' => 'Открыт приём заявок в экспертный кадровый резерв',
        'slug' => 'ekspertnyy-kadrovyy-rezerv',
        'excerpt' => 'К участию приглашаются специалисты в области стратегического планирования, анализа данных и управления проектами.',
        'category' => 'Карьера',
        'image' => '/uploads/public/hero-demo-g2.jpg',
        'hashtags' => '#карьера #эксперты #вакансии',
        'layout' => 'standard',
        'uz_title' => 'Ekspert kadrlar zaxirasiga arizalar qabul qilinmoqda',
        'uz_excerpt' => 'Strategik rejalashtirish, ma’lumotlar tahlili va loyihalarni boshqarish sohasidagi mutaxassislar taklif etiladi.',
        'uz_category' => 'Karyera',
        'uz_hashtags' => '#karyera #ekspertlar',
    ],
    // Седьмая новость нужна раскладке «мозаика»: она строится как
    // 1 + 2 + 4, и при шести записях нижний ряд оставался неполным —
    // на свежей установке блок выглядел бы недоделанным.
    [
        'title' => 'Подписаны новые соглашения о партнёрстве с зарубежными организациями',
        'slug' => 'soglasheniya-o-partnerstve',
        'excerpt' => 'Договорённости касаются обмена опытом в стратегическом планировании и совместных исследований.',
        'category' => 'Международное сотрудничество',
        'image' => '/uploads/public/hero-demo-g3.jpg',
        'hashtags' => '#партнёрство #сотрудничество',
        'layout' => 'standard',
        'uz_title' => 'Xorijiy tashkilotlar bilan yangi hamkorlik bitimlari imzolandi',
        'uz_excerpt' => 'Kelishuvlar strategik rejalashtirish sohasidagi tajriba almashish va qo‘shma tadqiqotlarga taalluqli.',
        'uz_category' => 'Xalqaro hamkorlik',
        'uz_hashtags' => '#hamkorlik #xalqaro',
    ],
];
