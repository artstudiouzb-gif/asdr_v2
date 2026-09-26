<?php

declare(strict_types=1);

/*
 * Демо-контент: страницы с блоками на русском и узбекском.
 * Подключает App\Core\DemoSeeder::seedPages() через DemoSeeder::content('pages').
 */

return [
    'o-nas' => [
        'ru' => [
            'title' => 'Об Агентстве',
            'blocks' => [
                ['text', 'Об Агентстве', [
                    'title' => 'Об Агентстве',
                    'content' => '<h2>Правовой статус и полномочия</h2><p>Агентство стратегического развития и реформ при Президенте Республики Узбекистан — уполномоченный государственный орган в сфере стратегического планирования и развития страны.</p><p>Указом Президента Республики Узбекистан от 30 октября 2025 года № УП-201 <a href="https://lex.uz/uz/docs/7806484" target="_blank" rel="noopener">«Об организационных мерах по внедрению системы стратегического планирования и развития»</a> в стране создана единая и комплексная система стратегического планирования и развития.</p><p>В соответствии с данным Указом Агентство:</p><ul><li>является уполномоченным государственным органом по регулированию организации системы стратегического планирования и развития, разработке конкретных механизмов, направленных на её эффективное внедрение, а также подготовке проектов документов стратегического планирования;</li><li>координирует деятельность структурных подразделений министерств и ведомств по стратегическому планированию, а также информационно-аналитических групп Совета Министров Республики Каракалпакстан, хокимиятов областей и города Ташкента.</li></ul><p>Организация работы Агентства строится вокруг трёх направлений, закреплённых в его структуре: стратегическое планирование и развитие отраслей и сфер, стратегическое планирование и развитие регионов, а также изучение передового зарубежного опыта и международное сотрудничество. По каждому направлению действуют профильные секторы и проектные офисы.</p><p>Ознакомиться с распределением задач можно в разделе <a href="/struktura">«Структура»</a>, с составом подразделений — в разделе <a href="/rukovodstvo">«Руководство»</a>.</p><h3>Основные документы, касающиеся деятельности Агентства</h3><ul><li><a href="https://lex.uz/uz/docs/5520880" target="_blank" rel="noopener">«О мерах по созданию Агентства стратегического развития Республики Узбекистан»</a> — Указ Президента Республики Узбекистан от 19 июля 2021 года № УП-6264;</li><li><a href="https://lex.uz/uz/docs/6188707" target="_blank" rel="noopener">«О дополнительных мерах по ускорению стратегических реформ»</a> — Указ Президента Республики Узбекистан от 8 сентября 2022 года № УП-216;</li><li><a href="https://lex.uz/uz/docs/6656978" target="_blank" rel="noopener">«О мерах по дальнейшему совершенствованию деятельности Агентства стратегических реформ при Президенте Республики Узбекистан»</a> — Указ Президента Республики Узбекистан от 8 ноября 2023 года № УП-190;</li><li><a href="https://lex.uz/uz/docs/7806484" target="_blank" rel="noopener">«Об организационных мерах по внедрению системы стратегического планирования и развития»</a> — Указ Президента Республики Узбекистан от 30 октября 2025 года № УП-201.</li></ul><p>Полные тексты документов публикуются в Национальной базе данных законодательства Республики Узбекистан lex.uz.</p>'
                ]]
            ]
        ],
        'uz' => [
            'title' => 'Agentlik haqida',
            'blocks' => [
                ['text', 'Agentlik haqida', [
                    'title' => 'Agentlik haqida',
                    'content' => '<h2>Huquqiy maqom va vakolatlar</h2><p>Oʻzbekiston Respublikasi Prezidenti huzuridagi Strategik rivojlanish va islohotlar agentligi — mamlakatda strategik rejalashtirish va rivojlanish sohasidagi vakolatli davlat organi.</p><p>Oʻzbekiston Respublikasi Prezidentining 2025-yil 30-oktabrdagi PF-201-son <a href="https://lex.uz/uz/docs/7806484" target="_blank" rel="noopener">«Strategik rejalashtirish va rivojlanish tizimini joriy etish boʻyicha tashkiliy chora-tadbirlar toʻgʻrisida»</a>gi Farmoni bilan mamlakatda yagona va kompleks strategik rejalashtirish va rivojlanish tizimi yaratildi.</p><p>Ushbu Farmonga muvofiq Agentlik:</p><ul><li>strategik rejalashtirish va rivojlanish tizimini tashkil etishni tartibga solish, uni samarali joriy etishga qaratilgan aniq mexanizmlarni ishlab chiqish, shuningdek, strategik rejalashtirish hujjatlari loyihalarini tayyorlash boʻyicha vakolatli davlat organi hisoblanadi;</li><li>vazirlik va idoralarning strategik rejalashtirish boʻyicha tarkibiy boʻlinmalari, shuningdek, Qoraqalpogʻiston Respublikasi Vazirlar Kengashi, viloyatlar va Toshkent shahri hokimliklarining axborot-tahlil guruhlari faoliyatini muvofiqlashtiradi.</li></ul><p>Agentlik faoliyati uning tuzilmasida mustahkamlangan uch yoʻnalish atrofida tashkil etilgan: soha va tarmoqlarni strategik rejalashtirish va rivojlantirish, hududlarni strategik rejalashtirish va rivojlantirish, hamda ilgʻor xorijiy tajribani oʻrganish va xalqaro hamkorlik. Har bir yoʻnalish boʻyicha tegishli shoʻbalar va loyiha ofislari faoliyat yuritadi.</p><p>Vazifalar taqsimoti bilan <a href="/struktura">«Tuzilma»</a> boʻlimida, boʻlinmalar tarkibi bilan <a href="/rukovodstvo">«Rahbariyat»</a> boʻlimida tanishishingiz mumkin.</p><h3>Agentlik faoliyatiga oid asosiy hujjatlar</h3><ul><li><a href="https://lex.uz/uz/docs/5520880" target="_blank" rel="noopener">«Oʻzbekiston Respublikasi Strategik taraqqiyot agentligini tashkil etish chora-tadbirlari toʻgʻrisida»</a> — Oʻzbekiston Respublikasi Prezidentining 2021-yil 19-iyuldagi PF-6264-son Farmoni;</li><li><a href="https://lex.uz/uz/docs/6188707" target="_blank" rel="noopener">«Strategik islohotlarni jadallashtirish boʻyicha qoʻshimcha chora-tadbirlar toʻgʻrisida»</a> — Oʻzbekiston Respublikasi Prezidentining 2022-yil 8-sentyabrdagi PF-216-son Farmoni;</li><li><a href="https://lex.uz/uz/docs/6656978" target="_blank" rel="noopener">«Oʻzbekiston Respublikasi Prezidenti huzuridagi Strategik islohotlar agentligi faoliyatini yanada takomillashtirish chora-tadbirlari toʻgʻrisida»</a> — Oʻzbekiston Respublikasi Prezidentining 2023-yil 8-noyabrdagi PF-190-son Farmoni;</li><li><a href="https://lex.uz/uz/docs/7806484" target="_blank" rel="noopener">«Strategik rejalashtirish va rivojlanish tizimini joriy etish boʻyicha tashkiliy chora-tadbirlar toʻgʻrisida»</a> — Oʻzbekiston Respublikasi Prezidentining 2025-yil 30-oktabrdagi PF-201-son Farmoni.</li></ul><p>Hujjatlarning toʻliq matnlari Oʻzbekiston Respublikasi qonunchilik maʼlumotlari milliy bazasi lex.uz saytida eʼlon qilinadi.</p>'
                ]]
            ]
        ]
    ],
    'rukovodstvo' => [
        'ru' => [
            'title' => 'Руководство',
            'blocks' => [
                ['text', 'Введение', ['title' => 'Руководство', 'content' => '<p>Руководящий состав организации.</p>']],
                ['team_list', 'Команда', ['title' => 'Руководящий состав', 'limit' => 0, 'group_by_department' => true]],
                ['cta', 'Директор', ['variant' => 'band', 'title' => 'Директор Агентства', 'text' => 'Биография, приоритеты работы и публикации руководителя.', 'button_text' => 'Страница директора', 'button_url' => '/direktor', 'bg_color' => '#072b61', 'text_color' => '#ffffff']]
            ]
        ],
        'uz' => [
            'title' => 'Rahbariyat',
            'blocks' => [
                ['text', 'Kirish', ['title' => 'Rahbariyat', 'content' => '<p>Tashkilotning rahbariyat tarkibi.</p>']],
                ['team_list', 'Jamoa', ['title' => 'Rahbariyat tarkibi', 'limit' => 0, 'group_by_department' => true]]
            ]
        ]
    ],
    'struktura' => [
        'ru' => [
            'title' => 'Структура',
            'lead' => 'Руководство, секторы и проектные офисы Агентства, а также их подчинённость.',
            'blocks' => [
                ['org_structure', 'Оргсхема', [
                    'title' => 'Структура Агентства стратегического развития и реформ при Президенте Республики Узбекистан',
                    'layout' => 'tree',
                    'columns' => 4,
                    'council' => 'Координационный совет',
                    'head_title' => 'Директор',
                    'head_name' => '',
                    'head_url' => '/direktor',
                    'side_items' => 'Советник',
                    'branches' => [
                        ['title' => 'Первый заместитель директора', 'name' => '', 'units' => "Сектор стратегического планирования и развития отраслей и сфер\nСектор анализа и исследований | /rukovodstvo#team-sektor-analiza-i-issledovaniy\nСектор организации деятельности Координационного совета\n* Проектные офисы по развитию отраслей"],
                        ['title' => 'Заместитель директора', 'name' => '', 'units' => "Сектор стратегического планирования и развития регионов\nСектор контроля за исполнением задач по стратегическому развитию\nСектор экспертизы проектов нормативно-правовых актов\n* Проектные офисы по развитию регионов"],
                        ['title' => 'Заместитель директора', 'name' => '', 'units' => "Сектор изучения передового зарубежного опыта, результатов научно-исследовательской деятельности и практики\nСектор координации процессов привлечения иностранных экспертов, консультантов и советников\n* Проектные офисы по развитию международного сотрудничества"],
                        ['title' => '', 'name' => '', 'units' => "Сектор координации системы стратегического планирования\nСектор мониторинга и оценки эффективности реформ\nИнформационно-аналитический и организационный сектор | /rukovodstvo#team-informacionno-analiticheskiy-i-organizacionnyy-sektor\n- группа по работе с кадрами\n- первый отдел\nФинансово-хозяйственный сектор (Главный бухгалтер)\n- группа материального обеспечения и хозяйственных дел\nСектор по связям с общественностью | /rukovodstvo#team-sektor-po-svyazyam-s-obschestvennostyu"],
                    ],
                    'notes' => 'Секторы четвёртой колонки подчиняются директору напрямую.',
                    'footnote' => 'Структура утверждена в установленном порядке и может уточняться при изменении задач Агентства.',
                ]]
            ]
        ],
        'uz' => [
            'title' => 'Tuzilma',
            'lead' => 'Agentlik rahbariyati, shoʻbalari va loyiha ofislari hamda ularning boʻysunuvi.',
            'blocks' => [
                ['org_structure', 'Tuzilma sxemasi', [
                    'title' => 'Oʻzbekiston Respublikasi Prezidenti huzuridagi Strategik rivojlanish va islohotlar agentligining tuzilmasi',
                    'layout' => 'tree',
                    'columns' => 4,
                    'council' => 'Muvofiqlashtiruvchi kengash',
                    'head_title' => 'Direktor',
                    'head_name' => '',
                    'head_url' => '/direktor',
                    'side_items' => 'Maslahatchi',
                    'branches' => [
                        ['title' => 'Direktorning birinchi oʻrinbosari', 'name' => '', 'units' => "Soha va tarmoqlarni strategik rejalashtirish va rivojlantirish shoʻbasi\nTahlil va tadqiqotlar shoʻbasi\nMuvofiqlashtiruvchi kengash faoliyatini tashkil qilish shoʻbasi\n* Tarmoqlarni rivojlantirish boʻyicha loyiha ofislari"],
                        ['title' => 'Direktor oʻrinbosari', 'name' => '', 'units' => "Hududlarni strategik rejalashtirish va rivojlantirish shoʻbasi\nStrategik rivojlanish boʻyicha vazifalar ijrosini nazorat qilish shoʻbasi\nMeʼyoriy-huquqiy hujjatlar loyihalarini ekspertiza qilish shoʻbasi\n* Hududlarni rivojlantirish boʻyicha loyiha ofislari"],
                        ['title' => 'Direktor oʻrinbosari', 'name' => '', 'units' => "Ilgʻor xorijiy tajriba, ilmiy-tadqiqot va amaliyot natijalarini oʻrganish shoʻbasi\nXorijiy ekspertlar, konsultant va maslahatchilarni jalb etish jarayonlarini muvofiqlashtirish shoʻbasi\n* Xalqaro hamkorlikni rivojlantirish boʻyicha loyiha ofislari"],
                        ['title' => '', 'name' => '', 'units' => "Strategik rejalashtirish tizimini muvofiqlashtirish shoʻbasi\nIslohotlar samaradorligini monitoring qilish va baholash shoʻbasi\nAxborot-tahlil va tashkiliy masalalar shoʻbasi\n- kadrlar bilan ishlash guruhi\n- birinchi boʻlim\nMoliya-xoʻjalik shoʻbasi (Bosh buxgalter)\n- moddiy taʼminot va xoʻjalik ishlari guruhi\nJamoatchilik bilan aloqalar shoʻbasi"],
                    ],
                    'notes' => 'Toʻrtinchi ustundagi shoʻbalar bevosita direktorga boʻysunadi.',
                    'footnote' => 'Tuzilma belgilangan tartibda tasdiqlangan boʻlib, Agentlik vazifalari oʻzgarganda aniqlashtirilishi mumkin.',
                ]]
            ]
        ]
    ],
    'antikorrupciya' => [
        'ru' => [
            'title' => 'Противодействие коррупции',
            'lead' => 'Антикоррупционная политика Агентства, нормативные документы и порядок обращений.',
            'blocks' => [
                ['text', 'Антикоррупция', ['title' => 'Противодействие коррупции', 'content' => '<p>Организация проводит последовательную антикоррупционную политику. Ознакомиться с нормативными документами можно в разделе «Документы».</p><p>Сообщить о фактах коррупции можно через форму обратной связи.</p>']]
            ]
        ],
        'uz' => [
            'title' => 'Korrupsiyaga qarshi kurash',
            'lead' => 'Agentlikning korrupsiyaga qarshi siyosati, normativ hujjatlar va murojaat tartibi.',
            'blocks' => [
                ['text', 'Korrupsiyaga qarshi kurashish', ['title' => 'Korrupsiyaga qarshi kurashish', 'content' => '<p>Tashkilotda korrupsiyaga qarshi kurashish bo‘yicha tizimli siyosat yuritiladi. Normativ hujjatlar bilan «Hujjatlar» bo‘limida tanishishingiz mumkin.</p><p>Korrupsiya holatlari haqida xabar berish uchun qayta aloqa shaklidan foydalanishingiz mumkin.</p>']]
            ]
        ]
    ],
    // Видео и фото живут в блоке «Медиагалерея»; отдельного маршрута
    // /videos у публички нет, поэтому раздел — обычная страница.
    'media' => [
        'ru' => [
            'title' => 'Медиатека',
            'lead' => 'Фотографии и видеозаписи с мероприятий, встреч и рабочих поездок Агентства.',
            'blocks' => [
                ['media_gallery', 'Медиатека', ['title' => 'Фото и видео', 'source' => 'media', 'limit' => 12]]
            ]
        ],
        'uz' => [
            'title' => 'Mediateka',
            'lead' => 'Agentlik tadbirlari, uchrashuvlari va ish safarlaridan foto va videolavhalar.',
            'blocks' => [
                ['media_gallery', 'Mediateka', ['title' => 'Foto va video', 'source' => 'media', 'limit' => 12]]
            ]
        ]
    ]
];
