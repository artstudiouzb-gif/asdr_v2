<?php

declare(strict_types=1);

/*
 * Демо-контент: формы обратной связи.
 * Подключает App\Core\DemoSeeder::seedForms() через DemoSeeder::content('forms').
 */

return [
    [
        'Обращение в Агентство',
        'public-appeal',
        [
            ['name' => 'name', 'label' => 'Ваше имя', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
            ['name' => 'phone', 'label' => 'Телефон', 'type' => 'tel', 'required' => false],
            ['name' => 'topic', 'label' => 'Тема обращения', 'type' => 'select', 'options' => 'Общий вопрос,Предложение,Запрос информации,Запись на приём', 'required' => true],
            ['name' => 'message', 'label' => 'Сообщение', 'type' => 'textarea', 'required' => true],
            ['name' => 'consent', 'label' => 'Согласен на обработку предоставленных данных', 'type' => 'checkbox', 'required' => true],
        ],
        'Спасибо! Ваше обращение зарегистрировано.',
    ],
    [
        'Регистрация на мероприятие',
        'event-registration',
        [
            ['name' => 'name', 'label' => 'Ф.И.О.', 'type' => 'text', 'required' => true],
            ['name' => 'organization', 'label' => 'Организация', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
            ['name' => 'participation', 'label' => 'Формат участия', 'type' => 'radio', 'options' => 'Очно,Онлайн', 'required' => true],
            ['name' => 'topics', 'label' => 'Интересующие направления', 'type' => 'checkbox_group', 'options' => 'Стратегическое планирование,Региональное развитие,Зелёная экономика,Цифровизация', 'required' => false],
            ['name' => 'event_date', 'label' => 'Предпочтительная дата', 'type' => 'date', 'required' => false],
        ],
        'Регистрация принята. Подтверждение будет направлено на указанный e-mail.',
    ],
    [
        'Заявка в экспертный резерв',
        'expert-pool',
        [
            ['name' => 'name', 'label' => 'Ф.И.О.', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
            ['name' => 'specialization', 'label' => 'Специализация', 'type' => 'select', 'options' => 'Экономика,Аналитика данных,Управление проектами,Международное сотрудничество', 'required' => true],
            ['name' => 'experience', 'label' => 'Кратко опишите опыт', 'type' => 'textarea', 'required' => true],
            ['name' => 'resume', 'label' => 'Резюме', 'type' => 'file', 'required' => true],
            ['name' => 'consent', 'label' => 'Подтверждаю достоверность сведений', 'type' => 'checkbox', 'required' => true],
        ],
        'Заявка принята и направлена на рассмотрение.',
    ],
];
