<?php

use App\Core\Csrf;
use App\Models\Language;

$isEdit = !empty($news['id']);
$pageTitle = $isEdit ? 'Редактирование новости' : 'Новая новость';
$activeNav = 'news';
require __DIR__ . '/../layout/header.php';

/** @var array|null $news */
/** @var string|null $error */
/** @var array $gallery */
$gallery = $gallery ?? [];
$layout = $news['layout_type'] ?? 'standard';
$layoutLabels = [
    'standard' => 'Умный макет (авто-галерея при нескольких фото)',
    'video' => 'Видео (YouTube)',
    'side_image' => 'Изображение сбоку',
    'premium' => 'Премиум (тёмный hero)',
];

$action = $isEdit ? '/admin/news/' . (int) $news['id'] . '/edit' : '/admin/news/create';
$publishedAtValue = '';
if (!empty($news['published_at'])) {
    $publishedAtValue = str_replace(' ', 'T', substr((string) $news['published_at'], 0, 16));
}
$defaultCode = Language::defaultCode();

$keyPoints = (string) ($news['key_points'] ?? '');
$eventMeta = (string) ($news['event_meta'] ?? '');

$docsRaw = $news['docs'] ?? [];
if (is_string($docsRaw)) {
    $docsRaw = json_decode($docsRaw, true) ?: [];
}
$docs = is_array($docsRaw) ? $docsRaw : [];

// Миграция старого поля press_release_url в документы при необходимости
$legacyPressUrl = trim((string) ($news['press_release_url'] ?? ''));
if ($legacyPressUrl !== '') {
    $alreadyInDocs = false;
    foreach ($docs as $d) {
        if (is_array($d) && trim((string) ($d['url'] ?? '')) === $legacyPressUrl) {
            $alreadyInDocs = true;
            break;
        }
    }
    if (!$alreadyInDocs) {
        $docs[] = ['title' => 'Пресс-релиз', 'meta' => 'PDF', 'url' => $legacyPressUrl];
    }
}

if (empty($docs)) {
    $docs = [['title' => '', 'meta' => '', 'url' => '']];
}

$timelineText = '';
if (!empty($news['timeline_json'])) {
    $tEvents = json_decode((string) $news['timeline_json'], true);
    if (is_array($tEvents)) {
        $lines = [];
        foreach ($tEvents as $e) {
            $lines[] = ($e['date'] ?? '') . ' | ' . ($e['title'] ?? '') . ' | ' . ($e['text'] ?? '');
        }
        $timelineText = implode("\n", $lines);
    }
}
$existingPoll = !empty($news['id']) ? \App\Models\NewsPoll::findByNews((int) $news['id']) : null;
?>
<?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
<?php if ($isEdit): ?>
    <div style="margin-bottom:16px;"><a class="btn btn--small" href="/admin/revisions/news/<?= (int) $news['id'] ?>">История версий</a></div>
<?php endif; ?>

<form method="post" action="<?= $action ?>" id="news_edit_form" enctype="multipart/form-data" data-content-draft="news:<?= $isEdit ? (int) $news['id'] : 'new' ?>" data-record-updated="<?= htmlspecialchars((string) ($news['updated_at'] ?? ''), ENT_QUOTES) ?>">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="expected_updated_at" value="<?= htmlspecialchars((string) $news['updated_at'], ENT_QUOTES) ?>">
        <input type="hidden" name="expected_lock_version" value="<?= max(1, (int) ($news['lock_version'] ?? 1)) ?>">
    <?php endif; ?>

    <div class="entry-grid">
        <div class="entry-main">
            <!-- Блок 1: Основная информация (Текстовый редактор) -->
            <div class="form-card">
                <?= \App\Core\AdminUi::cardHeader('1. Основная информация', 'document') ?>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Заголовок новости <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($news['title'] ?? '', ENT_QUOTES) ?>" placeholder="Введите заголовок новости" required style="font-size:1.05rem;font-weight:600;">
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label for="badge" style="font-weight:600;margin-bottom:6px;display:block;">Бейдж категории</label>
                    <input type="text" id="badge" name="badge" value="<?= htmlspecialchars($news['badge'] ?? '', ENT_QUOTES) ?>" placeholder="Например: Реформа, Экономика, Срочно">
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="margin:0;font-weight:600;">Краткий лид (анонс)</label>
                        <button type="button" class="btn btn--sm btn--secondary" data-ai-generate-summary><?= \App\Core\AdminUi::icon('sparkles') ?>ИИ-Аннотация</button>
                    </div>
                    <textarea name="excerpt" rows="3" placeholder="Кратко опишите суть новости"><?= htmlspecialchars($news['excerpt'] ?? '', ENT_QUOTES) ?></textarea>
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Текст новости (Визуальный редактор)</label>
                    <textarea name="content" data-wysiwyg style="min-height:300px;"><?= htmlspecialchars($news['content'] ?? '', ENT_QUOTES) ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-grid-2col">
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">Хештеги (#hashtags)</label>
                        <input type="text" name="hashtags" value="<?= htmlspecialchars($news['hashtags'] ?? '', ENT_QUOTES) ?>" placeholder="#культура, #ташкент, #событие">
                    </div>
                    <div class="form-field">
                        <label for="source_note" style="font-weight:600;margin-bottom:6px;display:block;">Подпись источника</label>
                        <input type="text" id="source_note" name="source_note" value="<?= htmlspecialchars($news['source_note'] ?? '', ENT_QUOTES) ?>" placeholder="Подготовлено пресс-службой Агентства">
                    </div>
                </div>
            </div>

            <!-- Блок 2: Видео, Аудио и Фотогалерея статьи -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                    <span class="admin-section-icon admin-section-icon--info"><?= \App\Core\AdminUi::icon('media', 22) ?></span>
                    <h3 style="margin:0;font-size:1.15rem;font-weight:700;">2. Дополнительные медиа (Видео, Аудио, Галерея)</h3>
                </div>

                <div class="form-grid" style="gap:18px;">
                    <!-- Ссылка на видео -->
                    <div class="form-field">
                        <label for="video_url" style="font-weight:600;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                            <?= \App\Core\AdminUi::icon('videos', 18) ?>
                            Ссылка на видео (YouTube / Rutube)
                        </label>
                        <input type="text" id="video_url" name="video_url" value="<?= htmlspecialchars($news['video_url'] ?? '', ENT_QUOTES) ?>" placeholder="https://youtu.be/... или https://youtube.com/watch?v=...">
                        <span class="form-hint">Встраивает интерактивный видеоплеер в новость. При макете «Видео» плеер выводится на месте обложки.</span>
                    </div>

                    <!-- Аудиозапись / Подкаст в 2 колонки -->
                    <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:14px;" class="form-grid-2col">
                        <div class="form-field">
                            <label for="audio_url" style="font-weight:600;margin-bottom:6px;display:block;">Аудиозапись / Подкаст <span class="form-hint">(MP3, AAC, OGG)</span></label>
                            <div style="display:flex;gap:8px;">
                                <input type="text" id="audio_url" name="audio_url" value="<?= htmlspecialchars($news['audio_url'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/... или https://...">
                                <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="#audio_url" data-media-type="audio" style="white-space:nowrap;">Выбрать аудио</button>
                            </div>
                            <span class="form-hint">Выводит аудиоплеер под заголовком новости.</span>
                        </div>

                        <div class="form-field">
                            <label for="audio_title" style="font-weight:600;margin-bottom:6px;display:block;">Название аудиотрека</label>
                            <input type="text" id="audio_title" name="audio_title" value="<?= htmlspecialchars($news['audio_title'] ?? '', ENT_QUOTES) ?>" placeholder="Аудиоверсия новости">
                        </div>
                    </div>

                    <!-- Фотогалерея -->
                    <div class="form-field" style="grid-column: 1 / -1;margin-top:4px;">
                        <label style="font-weight:600;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                            <?= \App\Core\AdminUi::icon('image', 18) ?>
                            Фотогалерея статьи
                        </label>
                        <?php if (!empty($gallery)): ?>
                            <div class="news-gallery-admin" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:12px;">
                                <?php foreach ($gallery as $gi): ?>
                                    <div style="border:1px solid var(--admin-border,#e1e3e8);border-radius:8px;padding:10px;background:var(--admin-card-bg,#fff);box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                                        <img src="<?= htmlspecialchars((string) $gi['path'], ENT_QUOTES) ?>" alt="" style="width:100%;height:110px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                                        <input type="text" name="gallery[<?= (int) $gi['id'] ?>][alt]" value="<?= htmlspecialchars((string) ($gi['alt_text'] ?? ''), ENT_QUOTES) ?>" placeholder="alt-текст" style="width:100%;margin-bottom:6px;font-size:0.82rem;">
                                        <div style="display:flex;gap:4px;margin-bottom:6px;">
                                            <input type="number" name="gallery[<?= (int) $gi['id'] ?>][sort]" value="<?= (int) $gi['sort_order'] ?>" title="Порядок сортировки" placeholder="№" style="width:50px;font-size:0.82rem;">
                                            <input type="number" name="gallery[<?= (int) $gi['id'] ?>][focal_x]" min="0" max="100" value="<?= htmlspecialchars((string) ($gi['focal_x'] ?? ''), ENT_QUOTES) ?>" placeholder="fx %" title="Фокус X %" style="width:55px;font-size:0.82rem;">
                                            <input type="number" name="gallery[<?= (int) $gi['id'] ?>][focal_y]" min="0" max="100" value="<?= htmlspecialchars((string) ($gi['focal_y'] ?? ''), ENT_QUOTES) ?>" placeholder="fy %" title="Фокус Y %" style="width:55px;font-size:0.82rem;">
                                        </div>
                                        <label style="font-size:12px;display:flex;gap:6px;align-items:center;color:#ef4444;cursor:pointer;font-weight:600;">
                                            <input type="checkbox" name="gallery[<?= (int) $gi['id'] ?>][delete]" value="1"> удалить
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!$isEdit): ?>
                            <span class="form-hint" style="display:block;margin-bottom:8px;">Сохраните новость, чтобы управлять галереей.</span>
                        <?php endif; ?>
                        <div style="display:flex;align-items:center;gap:12px;background:var(--admin-surface-soft,#f8fafc);padding:10px 14px;border-radius:8px;border:1px dashed var(--admin-border,#cbd5e1);">
                            <input type="file" name="news_gallery[]" accept="image/*" multiple style="font-size:0.875rem;">
                            <span class="form-hint" style="margin:0;">Выберите одно или несколько фото. Сжимаются в формат WebP.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Блок 3: Тезисы и Мероприятие -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;" class="form-grid-2col">
                <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <span class="admin-section-icon admin-section-icon--success"><?= \App\Core\AdminUi::icon('info', 20) ?></span>
                        <h4 style="margin:0;font-size:1.05rem;font-weight:700;">Ключевые тезисы</h4>
                    </div>
                    <textarea name="key_points" rows="4" placeholder="Главный вывод новости 1&#10;Главный вывод новости 2&#10;Ключевая цифра или цитата"><?= htmlspecialchars($keyPoints, ENT_QUOTES) ?></textarea>
                    <span class="form-hint">Каждый тезис с новой строки.</span>
                </div>

                <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <span class="admin-section-icon admin-section-icon--violet"><?= \App\Core\AdminUi::icon('calendar', 20) ?></span>
                        <h4 style="margin:0;font-size:1.05rem;font-weight:700;">О мероприятии</h4>
                    </div>
                    <textarea name="event_meta" rows="4" placeholder="Дата: 25 Июля 2026, 14:00&#10;Место: Дворец Симпозиумов&#10;Организатор: Министерство Культуры"><?= htmlspecialchars($eventMeta, ENT_QUOTES) ?></textarea>
                    <span class="form-hint">Формат: Название: Значение.</span>
                </div>
            </div>

            <!-- Блок 4: Таймлайн и Интерактивный опрос -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                    <span class="admin-section-icon admin-section-icon--violet"><?= \App\Core\AdminUi::icon('clock', 22) ?></span>
                    <h3 style="margin:0;font-size:1.15rem;font-weight:700;">4. Опрос и Хронология событий</h3>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">Вопрос для читателей (Опрос)</label>
                        <input type="text" name="poll_question" value="<?= htmlspecialchars($existingPoll['question'] ?? '', ENT_QUOTES) ?>" placeholder="Поддерживаете ли вы эту инициативу?">
                    </div>
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">Варианты ответов (по одному на строку)</label>
                        <textarea name="poll_options" rows="3" placeholder="Да&#10;Нет&#10;Затрудняюсь ответить"><?= htmlspecialchars(implode("\n", $existingPoll['options'] ?? []), ENT_QUOTES) ?></textarea>
                        <span class="form-hint">Оставьте пустым, если опрос не требуется.</span>
                    </div>
                    <?php if (!empty($existingPoll)): ?>
                        <?php $pollStats = \App\Models\NewsPoll::getResults((int) $existingPoll['id'], $existingPoll['options']); ?>
                        <div class="form-field admin-poll-results">
                            <strong class="admin-poll-results__title"><?= \App\Core\AdminUi::icon('stats') ?>Результаты голосования читателей (Всего проголосовало: <?= (int) ($pollStats['total'] ?? 0) ?> чел.)</strong>
                            <?php if (($pollStats['total'] ?? 0) === 0): ?>
                                <span class="form-hint">Голосов пока нет. Посетители смогут голосовать прямо в статье на сайте.</span>
                            <?php else: ?>
                                <?php foreach (($pollStats['items'] ?? []) as $item): ?>
                                    <div style="margin-bottom:8px;font-size:0.875rem;">
                                        <div style="display:flex;justify-content:space-between;margin-bottom:3px;font-weight:600;">
                                            <span><?= htmlspecialchars((string) ($item['label'] ?? $item['option'] ?? ''), ENT_QUOTES) ?></span>
                                            <span><?= (int) $item['votes'] ?> гол. (<?= (int) $item['percent'] ?>%)</span>
                                        </div>
                                        <div class="admin-progress">
                                            <div class="admin-progress__bar" style="width:<?= (int) $item['percent'] ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">Хронология событий (Таймлайн)</label>
                        <textarea name="timeline_raw" rows="4" placeholder="10 Июля 2026 | Анонс проекта | Выпущено официальное заявление&#10;15 Июля 2026 | Начало работ | Подписан договор с подрядчиком"><?= htmlspecialchars($timelineText, ENT_QUOTES) ?></textarea>
                        <span class="form-hint">Формат каждой строки: <code>Дата | Заголовок | Краткий текст</code>. Выводится красивой цепочкой событий.</span>
                    </div>
                </div>
            </div>

            <!-- Блок 5: Прикреплённые документы -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--info"><?= \App\Core\AdminUi::icon('document', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">5. Прикреплённые документы</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-add-doc-row="<?= htmlspecialchars($defaultCode, ENT_QUOTES) ?>">+ Добавить документ</button>
                </div>
                <div class="docs-container" data-docs-container="<?= htmlspecialchars($defaultCode, ENT_QUOTES) ?>" style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($docs as $idx => $doc): ?>
                        <div class="doc-item-row" style="display:grid;grid-template-columns:1fr 140px 2fr 36px;gap:10px;align-items:center;background:color-mix(in srgb, var(--admin-border,#e2e8f0) 25%, transparent);padding:10px 14px;border-radius:8px;border:1px solid var(--admin-border,#e2e8f0);">
                            <input type="text" name="docs[<?= $idx ?>][title]" value="<?= htmlspecialchars($doc['title'] ?? '', ENT_QUOTES) ?>" placeholder="Название документа (например: Указ №124)">
                            <input type="text" name="docs[<?= $idx ?>][meta]" value="<?= htmlspecialchars($doc['meta'] ?? '', ENT_QUOTES) ?>" placeholder="PDF, 2.4 МБ">
                            <div style="display:flex;gap:6px;">
                                <input type="text" name="docs[<?= $idx ?>][url]" value="<?= htmlspecialchars($doc['url'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/... или https://">
                                <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="[name='docs[<?= $idx ?>][url]']" data-media-type="all_files">Выбрать</button>
                            </div>
                            <button type="button" class="btn btn--small btn--danger" onclick="this.closest('.doc-item-row').remove();" title="Удалить" style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Блок 6: SEO Оптимизация -->
            <div class="form-card">
                <?= \App\Core\AdminUi::cardHeader('6. SEO Оптимизация', 'seo', 'var(--admin-success)') ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-grid-2col">
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">SEO: Meta Title</label>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($news['meta_title'] ?? '', ENT_QUOTES) ?>" placeholder="SEO Заголовок для поисковиков">
                    </div>
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">SEO: Meta Description</label>
                        <input type="text" name="meta_description" value="<?= htmlspecialchars($news['meta_description'] ?? '', ENT_QUOTES) ?>" placeholder="SEO Краткое описание для поисковиков">
                    </div>
                </div>

                <?= \App\Core\AdminUi::seoPreviewBox($news ?? []) ?>
            </div>
        </div>

        <!-- Правая колонка настройки публикации -->
        <aside class="entry-side">
            <?= \App\Core\TranslationGroupHelper::renderSidebarMetaBox('news', $news ?? []) ?>

            <!-- Главная обложка статьи -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:18px;margin-bottom:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                <h3 style="margin-top:0;font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <?= \App\Core\AdminUi::icon('image', 18) ?>
                    Главная обложка
                </h3>

                <div class="sidebar-cover-widget form-field image-field" data-image-field style="display:flex;flex-direction:column;gap:12px;">
                    <!-- Превью обложки -->
                    <div class="sidebar-cover-preview" data-image-preview>
                        <?php $hasImg = !empty($news['image']); ?>
                        <img src="<?= htmlspecialchars((string) ($news['image'] ?? ''), ENT_QUOTES) ?>" 
                             id="sidebar_cover_img" alt="Обложка" 
                             style="width:100%;height:100%;object-fit:cover;display:<?= $hasImg ? 'block' : 'none' ?>;">
                        <div id="sidebar_cover_placeholder" class="image-field__placeholder" style="padding:20px;text-align:center;color:var(--admin-text-muted,#64748b);display:<?= $hasImg ? 'none' : 'block' ?>;">
                            <span style="margin:0 auto 6px;display:block;opacity:0.55;"><?= \App\Core\AdminUi::icon('image', 36) ?></span>
                            <span style="font-size:0.82rem;font-weight:500;">Обложка не выбрана</span>
                        </div>
                    </div>

                    <!-- Индикатор локально выбранного файла -->
                    <div id="cover_file_badge" style="display:none;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:6px 10px;border-radius:6px;font-size:0.78rem;font-weight:600;align-items:center;gap:6px;">
                        <?= \App\Core\AdminUi::icon('check', 14, 'btn__icon', 2.5) ?>
                        <span>Новый файл подготовлен для сохранения</span>
                    </div>

                    <!-- Кнопки выбора, загрузки и удаления СНИЗУ обложки -->
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="#image_url" data-media-type="image" style="flex:1;justify-content:center;font-weight:600;font-size:0.8rem;padding:7px 8px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                            <?= \App\Core\AdminUi::icon('image', 15) ?>
                            Медиабиблиотека
                        </button>
                        <label class="btn btn--small btn--secondary" title="Загрузить файл с компьютера" style="margin:0;cursor:pointer;font-weight:600;font-size:0.8rem;padding:7px 10px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                            <?= \App\Core\AdminUi::icon('upload', 15) ?>
                            Загрузить
                            <input type="file" id="image_file_input" name="image_file" accept="image/*" data-image-file style="display:none;">
                        </label>
                        <button type="button" id="sidebar_cover_clear" class="btn btn--small btn--danger" data-image-clear title="Удалить обложку" style="font-size:0.8rem;padding:7px 10px;display:<?= $hasImg ? 'inline-flex' : 'none' ?>;align-items:center;gap:6px;">
                            <?= \App\Core\AdminUi::icon('trash', 15) ?>
                            Удалить
                        </button>
                    </div>

                    <!-- Поле ввода URL обложки -->
                    <div class="form-field" style="margin:0;">
                        <input type="text" id="image_url" name="image_url" data-image-input value="<?= htmlspecialchars((string) ($news['image'] ?? ''), ENT_QUOTES) ?>" 
                               placeholder="или вставьте URL изображения..." style="font-size:0.82rem;padding:6px 10px;">
                    </div>
                </div>
            </div>

            <script nonce="<?= \App\Core\SecurityHeaders::nonce() ?>">
            document.addEventListener('DOMContentLoaded', function () {
                var imgInput = document.getElementById('image_url');
                var fileInput = document.getElementById('image_file_input');
                var coverImg = document.getElementById('sidebar_cover_img');
                var placeholder = document.getElementById('sidebar_cover_placeholder');
                var clearBtn = document.getElementById('sidebar_cover_clear');
                var fileBadge = document.getElementById('cover_file_badge');

                function updateCoverPreview(url, isFromFile) {
                    var val = url ? url.trim() : '';
                    if (val !== '') {
                        if (coverImg) { coverImg.src = val; coverImg.style.display = 'block'; }
                        if (placeholder) placeholder.style.display = 'none';
                        if (clearBtn) clearBtn.style.display = 'inline-flex';
                        if (fileBadge) fileBadge.style.display = isFromFile ? 'flex' : 'none';
                    } else {
                        if (coverImg) { coverImg.src = ''; coverImg.style.display = 'none'; }
                        if (placeholder) placeholder.style.display = 'block';
                        if (clearBtn) clearBtn.style.display = 'none';
                        if (fileBadge) fileBadge.style.display = 'none';
                    }
                }

                if (imgInput) {
                    ['input', 'change', 'keyup', 'paste'].forEach(function (evt) {
                        imgInput.addEventListener(evt, function () { updateCoverPreview(imgInput.value, false); });
                    });

                    // Мгновенная реакция при вызове из MediaPicker (JS setter)
                    try {
                        var descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
                        if (descriptor && descriptor.set) {
                            var originalSet = descriptor.set;
                            Object.defineProperty(imgInput, 'value', {
                                set: function (val) {
                                    originalSet.call(this, val);
                                    updateCoverPreview(val, false);
                                },
                                get: function () {
                                    return descriptor.get.call(this);
                                }
                            });
                        }
                    } catch (e) {}
                }

                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        if (fileInput.files && fileInput.files[0]) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                updateCoverPreview(e.target.result, true);
                            };
                            reader.readAsDataURL(fileInput.files[0]);
                        }
                    });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (imgInput) { imgInput.value = ''; }
                        if (fileInput) { fileInput.value = ''; }
                        updateCoverPreview('', false);
                    });
                }
            });
            </script>

            <!-- Параметры публикации и макета -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:18px;margin-bottom:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                <h3 style="margin-top:0;font-size:1.05rem;font-weight:700;margin-bottom:14px;">Параметры публикации</h3>
                <div class="form-grid" style="gap:14px;">
                    <div class="form-field">
                        <label for="status" style="font-weight:600;">Статус</label>
                        <select id="status" name="status">
                            <option value="draft" <?= ($news['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                            <option value="published" <?= ($news['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="published_at" style="font-weight:600;">Дата публикации</label>
                        <input type="datetime-local" id="published_at" name="published_at" value="<?= htmlspecialchars($publishedAtValue, ENT_QUOTES) ?>">
                    </div>
                    <div class="form-field">
                        <label for="layout_type" style="font-weight:600;">Тип макета статьи</label>
                        <select id="layout_type" name="layout_type">
                            <?php foreach ($layoutLabels as $lt => $ltLabel): ?>
                                <option value="<?= $lt ?>" <?= $layout === $lt ? 'selected' : '' ?>><?= htmlspecialchars($ltLabel, ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="sidebar_layout" style="font-weight:600;">Макет страницы с виджетами</label>
                        <select id="sidebar_layout" name="sidebar_layout">
                            <option value="no_sidebar" <?= ($news['sidebar_layout'] ?? 'right_sidebar') === 'no_sidebar' ? 'selected' : '' ?>>Без сайдбара</option>
                            <option value="left_sidebar" <?= ($news['sidebar_layout'] ?? '') === 'left_sidebar' ? 'selected' : '' ?>>Левый сайдбар</option>
                            <option value="right_sidebar" <?= ($news['sidebar_layout'] ?? 'right_sidebar') === 'right_sidebar' ? 'selected' : '' ?>>Правый сайдбар</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="slug" style="font-weight:600;">ЧПУ (slug)</label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($news['slug'] ?? '', ENT_QUOTES) ?>" placeholder="оставьте пустым для автогенерации">
                    </div>
                </div>
            </div>

            <?php require __DIR__ . '/_detail_sidebar.php'; ?>

            <?php if ($isEdit): ?>
                <?php
                $socialPosts = \App\Models\SocialPost::forNews((int) $news['id']);
                $readyNetworks = \App\Core\SocialSettings::readyNetworks();
                $netLabels = ['telegram' => 'Telegram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram'];
                $stBadge = ['sent' => 'published', 'failed' => 'danger', 'pending' => 'draft'];
                ?>
                <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin-top:0;font-size:1.1rem;font-weight:700;">Публикация в соцсети</h3>
                    <?php if (empty($readyNetworks)): ?>
                        <p class="form-hint">Ни одна сеть не настроена. Включите их в разделе <a href="/admin/social">«Соцсети»</a>.</p>
                    <?php else: ?>
                        <?php if (!empty($socialPosts)): ?>
                            <table class="data-table" style="margin-bottom:12px;">
                                <thead><tr><th>Сеть</th><th>Статус</th><th>Попыток</th><th>Инфо</th></tr></thead>
                                <tbody>
                                    <?php foreach ($socialPosts as $sp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($netLabels[$sp['network']] ?? $sp['network'], ENT_QUOTES) ?></td>
                                            <td><span class="badge badge--<?= $stBadge[$sp['status']] ?? 'draft' ?>"><?= htmlspecialchars((string) $sp['status'], ENT_QUOTES) ?></span></td>
                                            <td><?= (int) $sp['attempts'] ?></td>
                                            <td><?= htmlspecialchars((string) ($sp['remote_id'] ?: ($sp['last_error'] ?? '')), ENT_QUOTES) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        <div style="font-size:0.8rem;color:var(--admin-text-muted,#475569);margin-bottom:12px;background:#f8fafc;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0;line-height:1.45;">
                            <div style="font-weight:700;margin-bottom:4px;color:var(--admin-text,#1e293b);display:flex;align-items:center;gap:6px;">
                                <?= \App\Core\AdminUi::icon('lock', 15) ?>
                                Подтверждение публикации
                            </div>
                            Отправка в Telegram/соцсети выполняется <strong>только при явном согласии администратора</strong> (отметьте флажок при сохранении в нижней панели или нажмите кнопку отправки ниже).
                        </div>
                        <div class="news-social__btns" style="margin-bottom:8px;">
                            <?php foreach ($readyNetworks as $net): ?>
                                <button type="submit" form="news-social-form" name="network" value="<?= htmlspecialchars($net, ENT_QUOTES) ?>"
                                        class="btn btn--small btn--social btn--social-<?= htmlspecialchars($net, ENT_QUOTES) ?>">
                                    <?= \App\Core\AdminUi::icon($net) ?><?= htmlspecialchars($netLabels[$net] ?? ucfirst($net), ENT_QUOTES) ?>
                                </button>
                            <?php endforeach; ?>
                            <?php if (count($readyNetworks) > 1): ?>
                                <button type="submit" form="news-social-form" class="btn btn--small"><?= \App\Core\AdminUi::icon('send') ?>Во все сети</button>
                            <?php endif; ?>
                        </div>
                        <p class="form-hint" style="margin-bottom:0;">Отправляет мгновенную публикацию в привязанные каналы соцсетей.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</form>

<?php if ($isEdit): ?>
    <form id="news-social-form" method="post" action="/admin/news/<?= (int) $news['id'] ?>/social" hidden>
        <?= Csrf::field() ?>
    </form>
<?php endif; ?>

<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-ai-generate-summary]');
    if (!btn) return;

    e.preventDefault();
    var form = btn.closest('form');
    if (!form) return;

    var titleInput = form.querySelector('[name="title"]');
    var title = titleInput ? titleInput.value : '';

    var content = '';
    if (window.tinymce) {
        if (tinymce.activeEditor) {
            content = tinymce.activeEditor.getContent();
        } else if (tinymce.get(0)) {
            content = tinymce.get(0).getContent();
        }
    }
    if (!content) {
        var contentField = form.querySelector('[name="content"]');
        if (contentField) { content = contentField.value; }
    }

    if (!title.trim() && !content.trim()) {
        alert('Пожалуйста, введите заголовок или текст новости перед генерацией ИИ-аннотации.');
        return;
    }

    var oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⌛ ИИ думает...';

    var body = new URLSearchParams();
    body.append('title', title);
    body.append('content', content);

    var csrfInput = form.querySelector('[name="csrf_token"]');
    if (csrfInput) {
        body.append('csrf_token', csrfInput.value);
    }

    fetch('/admin/news/ai-summary', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    }).then(function (res) {
        return res.json();
    }).then(function (data) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        if (data && data.ok) {
            if (data.excerpt) {
                var excerptField = form.querySelector('[name="excerpt"]');
                if (excerptField) { excerptField.value = data.excerpt; }
            }
            if (data.hashtags) {
                var hashtagsField = form.querySelector('[name="hashtags"]');
                if (hashtagsField) { hashtagsField.value = data.hashtags; }
            }
        } else if (data && data.error) {
            alert(data.error);
        }
    }).catch(function (err) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        alert('Ошибка при генерации ИИ: ' + (err.message || err));
    });
});

document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-add-doc-row]');
    if (btn) {
        var langCode = btn.getAttribute('data-add-doc-row');
        var container = document.querySelector('[data-docs-container="' + langCode + '"]');
        if (container) {
            var count = container.querySelectorAll('.doc-item-row').length;
            var namePrefix = 'docs[' + count + ']';

            var row = document.createElement('div');
            row.className = 'doc-item-row';
            row.style.cssText = 'display:grid;grid-template-columns:1fr 140px 2fr 36px;gap:10px;align-items:center;background:color-mix(in srgb, var(--admin-border,#e2e8f0) 25%, transparent);padding:10px 14px;border-radius:8px;border:1px solid var(--admin-border,#e2e8f0);';
            row.innerHTML = '<input type="text" name="' + namePrefix + '[title]" placeholder="Название документа">' +
                '<input type="text" name="' + namePrefix + '[meta]" placeholder="PDF, 2.4 МБ">' +
                '<div style="display:flex;gap:6px;"><input type="text" name="' + namePrefix + '[url]" placeholder="/uploads/... или https://"><button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="[name=\'' + namePrefix + '[url]\']" data-media-type="all_files">Выбрать</button></div>' +
                '<button type="button" class="btn btn--small btn--danger" onclick="this.closest(\'.doc-item-row\').remove();" title="Удалить" style="padding:6px 10px;">✕</button>';
            container.appendChild(row);
        }
    }
});
</script>

<div class="form-actions form-actions--sticky">
    <div class="form-actions-left">
        <?php $nStatus = $news['status'] ?? 'draft'; ?>
        <span class="badge badge--<?= $nStatus === 'published' ? 'success' : 'draft' ?>" style="font-size:0.84rem;padding:6px 12px;display:inline-flex;align-items:center;gap:6px;font-weight:600;">
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $nStatus === 'published' ? '#10b981' : '#f59e0b' ?>;"></span>
            <?= $nStatus === 'published' ? 'Опубликовано' : 'Черновик' ?>
        </span>
        <?php if (!empty($news['language_code'])): ?>
            <span style="font-size:0.82rem;font-weight:600;color:var(--admin-text-muted,#64748b);display:inline-flex;align-items:center;gap:6px;background:var(--admin-surface-soft,#f8fafc);padding:5px 10px;border-radius:6px;border:1px solid var(--admin-border,#e2e8f0);">
                <?= \App\Core\AdminUi::icon('globe', 14) ?>
                Язык: <strong><?= strtoupper(htmlspecialchars((string) $news['language_code'], ENT_QUOTES)) ?></strong>
            </span>
        <?php endif; ?>

        <?php if (!empty(\App\Core\SocialSettings::readyNetworks())): ?>
            <label style="font-size:0.82rem;font-weight:600;color:var(--admin-text,#1e293b);display:inline-flex;align-items:center;gap:7px;background:color-mix(in srgb, var(--admin-primary,#0284c7) 8%, #ffffff);padding:5px 12px;border-radius:6px;border:1px solid color-mix(in srgb, var(--admin-primary,#0284c7) 30%, #cbd5e1);cursor:pointer;" title="Отправить публикацию в привязанные каналы (Telegram и др.) только при явном подтверждении">
                <input type="checkbox" name="publish_to_social" value="1" form="news_edit_form" style="width:16px;height:16px;margin:0;cursor:pointer;accent-color:var(--admin-primary,#0284c7);">
                <?= \App\Core\AdminUi::icon('send', 14) ?>
                <span>Опубликовать в соцсетях</span>
            </label>
        <?php endif; ?>
    </div>

    <div class="form-actions-right">
        <?php if ($nStatus !== 'published'): ?>
            <button type="submit" name="publish_action" value="1" form="news_edit_form" class="btn btn--primary"><?= \App\Core\AdminUi::icon('check') ?>Опубликовать</button>
            <button type="submit" form="news_edit_form" class="btn"><?= \App\Core\AdminUi::icon('save') ?>Сохранить черновик</button>
        <?php else: ?>
            <button type="submit" form="news_edit_form" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить изменения</button>
        <?php endif; ?>
        <a href="/admin/news" class="btn">Отмена</a>
        <?php if ($isEdit && !empty($news['slug'])): ?>
            <a href="/news/<?= htmlspecialchars((string) $news['slug'], ENT_QUOTES) ?>" class="btn btn--outline" target="_blank" rel="noopener" style="font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                <?= \App\Core\AdminUi::icon('eye', 14) ?>
                Предпросмотр ↗
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
