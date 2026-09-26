<?php

declare(strict_types=1);

/*
 * Демо-контент: узбекские переводы записей каталогов.
 * Подключает App\Core\DemoSeeder::seedEntries() через DemoSeeder::content('entries_translations').
 */

return [
    'documenty' => [
        'metodologiya-monitoringa-2030' => ['«O‘zbekiston–2030» strategiyasini monitoring qilish metodologiyasi', ['category' => 'Metodologiya', 'summary' => 'Islohotlar maqsadlari, indikatorlari va natijalarini baholashga yagona yondashuvlar.']],
        'otchet-strukturnye-reformy' => ['Tarkibiy islohotlarning borishi bo‘yicha tahliliy hisobot', ['category' => 'Tahliliy hisobotlar', 'summary' => 'Tarkibiy o‘zgarishlar monitoringi natijalari va keyingi qadamlar bo‘yicha tavsiyalar.']],
        'reglament-koordinacii' => ['Idoralararo muvofiqlashtirish reglamenti', ['category' => 'Reglamentlar', 'summary' => 'Ma’lumot almashish, qarorlarni kelishish va ijroni nazorat qilish tartibi.']],
        'obzor-mezhdunarodnogo-opyta' => ['Strategik rejalashtirish bo‘yicha xalqaro tajriba sharhi', ['category' => 'Sharhlar', 'summary' => 'Strategik boshqaruv va islohotlarni baholashning zamonaviy modellarini taqqoslash.']],
    ],
    'vakansii' => [
        'vedushchiy-it' => ['IT bo‘limining yetakchi mutaxassisi', ['department' => 'Axborot texnologiyalari bo‘limi', 'requirements' => 'Oliy ma’lumot, kamida 3 yillik tajriba, PHP/MySQL bilimlari.', 'duties' => 'Axborot tizimlarini qo‘llab-quvvatlash va rivojlantirish.']],
        'yuriskonsult' => ['Yuriskonsult', ['department' => 'Yuridik bo‘lim', 'requirements' => 'Oliy yuridik ma’lumot va kamida 2 yillik tajriba.', 'duties' => 'Agentlik faoliyatini huquqiy qo‘llab-quvvatlash.']],
        'specialist-kadry' => ['Kadrlar bo‘yicha mutaxassis', ['department' => 'Inson resurslari bo‘limi', 'requirements' => 'Kadrlar ish yurituvi bo‘yicha tajriba.', 'duties' => 'Kadrlar hisobi va hujjatlarini yuritish.']],
        'press-sekretar' => ['Matbuot kotibi', ['department' => 'Matbuot xizmati', 'requirements' => 'OAV yoki PR sohasida tajriba, savodli nutq.', 'duties' => 'OAV bilan hamkorlik va sayt yangiliklarini yuritish.']],
    ],
    'tendery' => [
        'platforma-monitoringa-zakupka' => ['Monitoring tahliliy platformasini rivojlantirish', ['summary' => 'Ko‘rsatkichlarni vizuallashtirish va idoralararo ma’lumot almashish modullarini ishlab chiqish.']],
        'issledovanie-regionov' => ['Hududlarning ijtimoiy-iqtisodiy dinamikasini o‘rganish', ['summary' => 'Hududlarda o‘sish omillari va hayot sifatini kompleks o‘rganish.']],
        'ekspertnyy-forum' => ['Xalqaro ekspert forumini tashkil etish', ['summary' => 'Ekspert forumini tashkiliy va texnik jihatdan qo‘llab-quvvatlash.']],
    ],
    'meropriyatiya' => [
        'prezentaciya-monitoringa-strategii' => ['Strategiyani monitoring qilish tizimining ochiq taqdimoti', ['location' => 'Toshkent, Agentlik konferensiya zali', 'summary' => '«O‘zbekiston–2030» strategiyasi maqsad va ko‘rsatkichlarini monitoring qilish raqamli tizimi taqdimoti.']],
        'dialog-regionalnoe-razvitie' => ['Hududiy rivojlanish bo‘yicha ekspert muloqoti', ['location' => 'Gibrid shakl', 'summary' => 'Hududlarni rivojlantirish va davlat dasturlari sifatini baholashning yangi yondashuvlari muhokamasi.']],
        'forum-strategicheskih-iniciativ' => ['Strategik tashabbuslar forumi', ['location' => 'Toshkent', 'summary' => 'Davlat organlari, ekspertlar va xalqaro hamkorlar o‘rtasida tajriba almashish maydoni.']],
    ],
];
