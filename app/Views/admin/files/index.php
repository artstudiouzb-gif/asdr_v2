<?php

use App\Core\Csrf;
use App\Core\Format;
use App\Core\AdminUi;
use App\Models\FileEntry;

$pageTitle = 'Медиабиблиотека';
$activeNav = 'files';
require __DIR__ . '/../layout/header.php';

/** @var array $items */
/** @var array $availableDates */
$selectedType = (string) ($_GET['type'] ?? '');
$selectedDate = (string) ($_GET['date'] ?? '');
$selectedSort = (string) ($_GET['sort'] ?? 'date_desc');
$searchQuery = (string) ($_GET['q'] ?? '');
?>

<style>
/* ============================================================================
   ПРЕМИАЛЬНАЯ МЕДИАБИБЛИОТЕКА (Modern Media Library)
   ============================================================================ */

.media-lib {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Верхняя панель действий и фильтров */
.media-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    background: #ffffff;
    border: 1px solid var(--admin-border, #e6e8ec);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.media-toolbar__left {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.media-toolbar__right {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto;
}

.media-toolbar select,
.media-toolbar input[type="search"] {
    padding: 8px 12px;
    font-size: 13px;
    border-radius: 8px;
    border: 1px solid var(--admin-border, #d0d7de);
    background: #f8fafc;
    color: var(--admin-ink, #0f172a);
    outline: none;
    transition: all 0.2s ease;
}

.media-toolbar select:focus,
.media-toolbar input[type="search"]:focus {
    background: #ffffff;
    border-color: var(--admin-accent, #0f2756);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--admin-accent, #0f2756) 15%, transparent);
}

/* Кнопка выбора режима просмотра (Список / Сетка) */
#view_mode_list.is-active, #view_mode_grid.is-active {
    background: var(--admin-accent, #173a63) !important;
    color: #ffffff !important;
    border-color: var(--admin-accent, #173a63) !important;
}

.files-view-list .media-grid { display: none !important; }
.files-view-grid .files-table { display: none !important; }

/* Кнопка множественного выбора и пакетного удаления */
.media-bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 18px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    font-size: 13px;
    color: #1e40af;
}

.media-bulk-bar.is-active {
    display: flex;
}

/* Драг-н-Дроп зона загрузки файлов */
.media-upload-drawer {
    display: none;
    padding: 30px 24px;
    background: #f8fafc;
    border: 2px dashed var(--admin-accent, #0f2756);
    border-radius: 14px;
    text-align: center;
    margin-bottom: 20px;
    transition: all 0.2s ease;
}

.media-upload-drawer.is-open,
.media-upload-drawer.is-dragover {
    display: block;
    background: #f0f7ff;
}

.media-dropzone__title {
    font-size: 16px;
    font-weight: 600;
    color: var(--admin-ink, #0f172a);
    margin-bottom: 6px;
}

.media-dropzone__sub {
    font-size: 13px;
    color: var(--admin-muted, #64748b);
    margin-bottom: 16px;
}

/* Сетка карточек медиабиблиотеки (Квадратные плитки) */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
    gap: 14px;
}

@media (min-width: 1200px) {
    .media-grid {
        grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
    }
}

.media-card {
    position: relative;
    aspect-ratio: 1 / 1;
    background: #f1f5f9;
    border: 2px solid transparent;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.media-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.media-card.is-selected {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.3) !important;
}

.media-card__thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-card__icon-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    padding: 12px;
    color: var(--admin-muted, #64748b);
    text-align: center;
    background: #e2e8f0;
}

.media-card__ext {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    margin-top: 4px;
    letter-spacing: 0.05em;
}

.media-card__caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 6px 8px;
    background: rgba(15, 23, 42, 0.85);
    color: #ffffff;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    backdrop-filter: blur(4px);
}

.media-card__check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0284c7;
    color: #ffffff;
    display: none;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    z-index: 5;
}

.media-card.is-selected .media-card__check {
    display: flex;
}

/* Инспекционная модальная панель (Media Details Modal Drawer) */
.media-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.media-modal.is-open {
    display: flex;
    animation: media-fade-in 0.25s ease;
}

@keyframes media-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

.media-modal__dialog {
    position: relative;
    display: flex;
    width: 100%;
    max-width: 1000px;
    max-height: 88vh;
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
}

@media (max-width: 800px) {
    .media-modal__dialog {
        flex-direction: column;
        max-height: 94vh;
        overflow-y: auto;
    }
}

.media-modal__preview-side {
    flex: 1.3;
    background: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    position: relative;
    min-height: 320px;
}

.media-modal__preview-side img,
.media-modal__preview-side video {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
}

.media-modal__details-side {
    flex: 1;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    overflow-y: auto;
    background: #ffffff;
}

.media-modal__close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: background 0.2s ease;
}

.media-modal__close:hover {
    background: rgba(255, 255, 255, 0.4);
}

.media-modal__title {
    font-size: 16px;
    font-weight: 700;
    color: var(--admin-ink, #0f172a);
    margin: 0;
    word-break: break-all;
}

.media-modal__meta-list {
    font-size: 13px;
    color: var(--admin-muted, #64748b);
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--admin-border, #e2e8f0);
}

.media-modal__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.media-modal__field label {
    font-size: 11px;
    font-weight: 700;
    color: var(--admin-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.media-modal__input-group {
    display: flex;
    gap: 8px;
}

.media-modal__input-group input {
    flex: 1;
    padding: 8px 12px;
    font-size: 13px;
    border-radius: 8px;
    border: 1px solid var(--admin-border, #cbd5e1);
    background: #f8fafc;
}
</style>

<div class="media-lib">
    <!-- Драг-н-Дроп зона для загрузки файлов -->
    <div class="media-upload-drawer" id="media_upload_drawer">
        <form method="post" action="/admin/files/upload" enctype="multipart/form-data" id="upload_form">
            <?= Csrf::field() ?>
            <div class="media-dropzone__title">Перетащите файлы сюда</div>
            <div class="media-dropzone__sub">или нажмите кнопку ниже для выбора на диске</div>
            <div style="display:flex; justify-content:center; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:14px;">
                <input type="file" id="media_file_input" name="file" style="display:none;" required>
                <button type="button" class="btn btn--primary" onclick="document.getElementById('media_file_input').click()">Выберите файлы</button>
                <select name="access_type" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:13px;">
                    <option value="public">Открытый доступ</option>
                    <option value="protected">Защищённый доступ</option>
                </select>
            </div>
            <div id="upload_filename_preview" style="font-weight:600; font-size:13px; color:var(--admin-accent);"></div>
            <div id="upload_action_row" style="display:none; margin-top:12px;">
                <button type="submit" class="btn btn--success">Загрузить файл на сервер</button>
            </div>
        </form>
    </div>

    <!-- Верхний тулбар управления медиабиблиотекой -->
    <form method="get" action="/admin/files" class="media-toolbar" id="media_filter_form">
        <div class="media-toolbar__left">
            <button type="button" class="btn btn--primary" id="btn_toggle_upload">
                <?= AdminUi::icon('plus', 16, 'btn__icon', 2.5) ?>
                Добавить медиафайл
            </button>

            <!-- Фильтр по типам медиафайлов -->
            <select name="type" onchange="this.form.submit()">
                <option value="">Все медиафайлы</option>
                <option value="image" <?= $selectedType === 'image' ? 'selected' : '' ?>>Изображения</option>
                <option value="video" <?= $selectedType === 'video' ? 'selected' : '' ?>>Видео</option>
                <option value="document" <?= $selectedType === 'document' ? 'selected' : '' ?>>Документы</option>
            </select>

            <!-- Фильтр по дате создания -->
            <select name="date" onchange="this.form.submit()">
                <option value="">Все даты</option>
                <?php foreach (($availableDates ?? []) as $dVal): ?>
                    <?php 
                    $time = strtotime($dVal . '-01');
                    $monthsRu = ['01'=>'Январь','02'=>'Февраль','03'=>'Март','04'=>'Апрель','05'=>'Май','06'=>'Июнь','07'=>'Июль','08'=>'Август','09'=>'Сентябрь','10'=>'Октябрь','11'=>'Ноябрь','12'=>'Декабрь'];
                    $mNum = date('m', $time);
                    $dLabel = ($monthsRu[$mNum] ?? date('F', $time)) . ' ' . date('Y', $time);
                    ?>
                    <option value="<?= htmlspecialchars($dVal, ENT_QUOTES) ?>" <?= $selectedDate === $dVal ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dLabel, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Режим множественного выбора -->
            <button type="button" class="btn" id="btn_bulk_toggle">
                Множественный выбор
            </button>
        </div>

        <div class="media-toolbar__right">
            <!-- Поиск файлов -->
            <input type="search" name="q" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES) ?>" placeholder="Поиск медиафайлов…" id="media_search_input">

            <!-- Сортировка -->
            <select name="sort" onchange="this.form.submit()">
                <option value="date_desc" <?= $selectedSort === 'date_desc' ? 'selected' : '' ?>>Сначала новые</option>
                <option value="date_asc" <?= $selectedSort === 'date_asc' ? 'selected' : '' ?>>Сначала старые</option>
                <option value="name_asc" <?= $selectedSort === 'name_asc' ? 'selected' : '' ?>>По имени (А-Я)</option>
                <option value="size_desc" <?= $selectedSort === 'size_desc' ? 'selected' : '' ?>>Крупные</option>
            </select>

            <!-- Переключатель режима: Список / Сетка -->
            <button type="button" class="btn btn--small is-active" id="view_mode_grid" title="Сетка" style="min-width:36px;padding:6px 10px;">
                <?= AdminUi::icon('grid', 18) ?>
            </button>
            <button type="button" class="btn btn--small" id="view_mode_list" title="Список" style="min-width:36px;padding:6px 10px;">
                <?= AdminUi::icon('list', 18) ?>
            </button>
        </div>
    </form>

    <!-- Панель массовых действий -->
    <div class="media-bulk-bar" id="media_bulk_bar">
        <span>Выбрано элементов: <strong id="bulk_count">0</strong></span>
        <form method="post" action="/admin/files/bulk-delete" id="bulk_delete_form">
            <?= Csrf::field() ?>
            <input type="hidden" name="ids" id="bulk_ids_input" value="[]">
            <button type="submit" class="btn btn--small btn--danger" onclick="return confirm('Удалить выбранные файлы?')">Удалить выбранные</button>
        </form>
    </div>

    <!-- Основной контейнер материалов -->
    <div id="files_container" class="files-view-grid">
        <!-- Квадратная Сетка Медиафайлов (Square Grid) -->
        <div class="media-grid">
            <?php if (empty($items)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--admin-muted); font-size: 15px;">Медиафайлы не найдены.</div>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php 
                $isImg = str_starts_with((string) $item['mime_type'], 'image/');
                $isVideo = str_starts_with((string) $item['mime_type'], 'video/');
                $url = FileEntry::publicUrl($item);
                if ($item['access_type'] !== 'public') {
                    $url = '/download.php?file_id=' . (int) $item['id'] . '&token=' . htmlspecialchars((string) $item['access_token'], ENT_QUOTES);
                }
                $ext = pathinfo((string) $item['original_name'], PATHINFO_EXTENSION);
                ?>
                <div class="media-card" 
                     data-id="<?= (int) $item['id'] ?>"
                     data-name="<?= htmlspecialchars((string) $item['original_name'], ENT_QUOTES) ?>"
                     data-url="<?= htmlspecialchars($url, ENT_QUOTES) ?>"
                     data-size="<?= htmlspecialchars(Format::fileSize((int) $item['size']), ENT_QUOTES) ?>"
                     data-mime="<?= htmlspecialchars((string) $item['mime_type'], ENT_QUOTES) ?>"
                     data-date="<?= htmlspecialchars(date('d.m.Y H:i', strtotime((string) $item['created_at'])), ENT_QUOTES) ?>"
                     data-access="<?= htmlspecialchars((string) $item['access_type'], ENT_QUOTES) ?>"
                     data-token="<?= htmlspecialchars((string) $item['access_token'], ENT_QUOTES) ?>"
                     data-is-img="<?= $isImg ? '1' : '0' ?>"
                     data-is-video="<?= $isVideo ? '1' : '0' ?>">

                    <div class="media-card__check">
                        <?= AdminUi::icon('check', 14, 'btn__icon', 3) ?>
                    </div>

                    <?php if ($isImg): ?>
                        <img src="<?= htmlspecialchars($url, ENT_QUOTES) ?>" class="media-card__thumb" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="media-card__icon-wrap">
                            <?= AdminUi::icon('document', 36, 'media-card__file-icon', 1.8) ?>
                            <span class="media-card__ext"><?= htmlspecialchars($ext, ENT_QUOTES) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="media-card__caption" title="<?= htmlspecialchars((string) $item['original_name'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars((string) $item['original_name'], ENT_QUOTES) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Табличное отображение (Список) -->
        <table class="data-table files-table">
            <thead>
                <tr>
                    <th>Имя файла</th>
                    <th>Тип</th>
                    <th>Размер</th>
                    <th>Доступ</th>
                    <th>Ссылка</th>
                    <th class="data-table__action-cell">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="data-table__empty">Файлов пока нет.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <?php 
                    $url = FileEntry::publicUrl($item);
                    if ($item['access_type'] !== 'public') {
                        $url = '/download.php?file_id=' . (int) $item['id'] . '&token=' . htmlspecialchars((string) $item['access_token'], ENT_QUOTES);
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="file-cell">
                                <span class="file-name"><?= htmlspecialchars((string) $item['original_name'], ENT_QUOTES) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars((string) $item['mime_type'], ENT_QUOTES) ?></td>
                        <td><?= Format::fileSize((int) $item['size']) ?></td>
                        <td>
                            <span class="badge badge--<?= $item['access_type'] === 'public' ? 'published' : 'draft' ?>">
                                <?= $item['access_type'] === 'public' ? 'Открытый' : 'Защищённый' ?>
                            </span>
                        </td>
                        <td style="max-width:260px; word-break:break-all;">
                            <code><?= htmlspecialchars($url, ENT_QUOTES) ?></code>
                        </td>
                        <td class="data-table__action-cell">
                            <div class="data-table__actions">
                                <button type="button" class="btn btn--small" data-copy-link="<?= htmlspecialchars($url, ENT_QUOTES) ?>">Ссылка</button>
                                <form method="post" action="/admin/files/<?= (int) $item['id'] ?>/delete" data-confirm="Удалить файл «<?= htmlspecialchars((string) $item['original_name'], ENT_QUOTES) ?>»?">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn--small btn--danger">Удалить</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Модальная инспекционная панель свойств медиафайла -->
<div class="media-modal" id="media_modal">
    <div class="media-modal__dialog">
        <button type="button" class="media-modal__close" id="media_modal_close" aria-label="Закрыть">✕</button>
        <div class="media-modal__preview-side" id="modal_preview_container">
            <!-- Превью картинка/видео вставляется JS -->
        </div>
        <div class="media-modal__details-side">
            <h3 class="media-modal__title" id="modal_file_name">--</h3>
            <div class="media-modal__meta-list">
                <div>Загружен: <strong id="modal_file_date">--</strong></div>
                <div>Размер: <strong id="modal_file_size">--</strong></div>
                <div>MIME-тип: <strong id="modal_file_mime">--</strong></div>
                <div>Доступ: <strong id="modal_file_access">--</strong></div>
            </div>

            <div class="media-modal__field">
                <label>Прямая ссылка на файл</label>
                <div class="media-modal__input-group">
                    <input type="text" id="modal_file_url" readonly>
                    <button type="button" class="btn btn--primary" id="modal_copy_btn">Копировать</button>
                </div>
            </div>

            <div style="margin-top:auto; display:flex; gap:10px; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px solid #e2e8f0;">
                <form method="post" action="" id="modal_delete_form" onsubmit="return confirm('Вы уверены, что хотите навсегда удалить этот медиафайл?')">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--danger">Удалить навсегда</button>
                </form>
                <button type="button" class="btn" onclick="document.getElementById('media_modal').classList.remove('is-open')">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= \App\Core\SecurityHeaders::nonce() ?>">
(function() {
    'use strict';

    // 1. Показ/Скрытие панели загрузки
    var btnToggleUpload = document.getElementById('btn_toggle_upload');
    var uploadDrawer = document.getElementById('media_upload_drawer');
    var fileInput = document.getElementById('media_file_input');
    var filenamePreview = document.getElementById('upload_filename_preview');
    var uploadActionRow = document.getElementById('upload_action_row');

    if (btnToggleUpload && uploadDrawer) {
        btnToggleUpload.addEventListener('click', function() {
            uploadDrawer.classList.toggle('is-open');
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files[0]) {
                var f = fileInput.files[0];
                filenamePreview.textContent = 'Выбран файл: ' + f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' МБ)';
                uploadActionRow.style.display = 'block';
            } else {
                filenamePreview.textContent = '';
                uploadActionRow.style.display = 'none';
            }
        });
    }

    // Drag and Drop прямо на дравер
    if (uploadDrawer) {
        ['dragenter', 'dragover'].forEach(function(ev) {
            document.body.addEventListener(ev, function(e) {
                e.preventDefault();
                uploadDrawer.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            uploadDrawer.addEventListener(ev, function(e) {
                e.preventDefault();
                uploadDrawer.classList.remove('is-dragover');
            });
        });
    }

    // 2. Режим множественного выбора (Bulk Select)
    var isBulkMode = false;
    var selectedIds = new Set();
    var btnBulkToggle = document.getElementById('btn_bulk_toggle');
    var bulkBar = document.getElementById('media_bulk_bar');
    var bulkCountEl = document.getElementById('bulk_count');
    var bulkIdsInput = document.getElementById('bulk_ids_input');

    if (btnBulkToggle) {
        btnBulkToggle.addEventListener('click', function() {
            isBulkMode = !isBulkMode;
            btnBulkToggle.classList.toggle('btn--primary', isBulkMode);
            bulkBar.classList.toggle('is-active', isBulkMode);
            if (!isBulkMode) {
                selectedIds.clear();
                updateBulkUI();
            }
        });
    }

    function updateBulkUI() {
        document.querySelectorAll('.media-card').forEach(function(card) {
            var id = parseInt(card.getAttribute('data-id'), 10);
            card.classList.toggle('is-selected', selectedIds.has(id));
        });
        if (bulkCountEl) bulkCountEl.textContent = selectedIds.size;
        if (bulkIdsInput) bulkIdsInput.value = JSON.stringify(Array.from(selectedIds));
    }

    // 3. Клик по карточке — инспектор или bulk select
    var modal = document.getElementById('media_modal');
    var modalClose = document.getElementById('media_modal_close');
    var previewContainer = document.getElementById('modal_preview_container');
    var modalFileName = document.getElementById('modal_file_name');
    var modalFileDate = document.getElementById('modal_file_date');
    var modalFileSize = document.getElementById('modal_file_size');
    var modalFileMime = document.getElementById('modal_file_mime');
    var modalFileAccess = document.getElementById('modal_file_access');
    var modalFileUrl = document.getElementById('modal_file_url');
    var modalCopyBtn = document.getElementById('modal_copy_btn');
    var modalDeleteForm = document.getElementById('modal_delete_form');

    document.querySelectorAll('.media-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            var id = parseInt(card.getAttribute('data-id'), 10);
            
            if (isBulkMode) {
                if (selectedIds.has(id)) {
                    selectedIds.delete(id);
                } else {
                    selectedIds.add(id);
                }
                updateBulkUI();
                return;
            }

            // Показ модального инспектора
            var name = card.getAttribute('data-name');
            var url = card.getAttribute('data-url');
            var size = card.getAttribute('data-size');
            var mime = card.getAttribute('data-mime');
            var date = card.getAttribute('data-date');
            var access = card.getAttribute('data-access');
            var isImg = card.getAttribute('data-is-img') === '1';
            var isVideo = card.getAttribute('data-is-video') === '1';

            var fullUrl = url.startsWith('/') ? window.location.origin + url : url;

            if (modalFileName) modalFileName.textContent = name;
            if (modalFileDate) modalFileDate.textContent = date;
            if (modalFileSize) modalFileSize.textContent = size;
            if (modalFileMime) modalFileMime.textContent = mime;
            if (modalFileAccess) modalFileAccess.textContent = access === 'public' ? 'Открытый' : 'Защищённый';
            if (modalFileUrl) modalFileUrl.value = fullUrl;
            if (modalDeleteForm) modalDeleteForm.action = '/admin/files/' + id + '/delete';

            if (previewContainer) {
                if (isImg) {
                    previewContainer.innerHTML = '<img src="' + url + '" alt="">';
                } else if (isVideo) {
                    previewContainer.innerHTML = '<video src="' + url + '" controls autoplay></video>';
                } else {
                    previewContainer.innerHTML = '<div style="color:#ffffff; text-align:center;">'
                        + <?= json_encode(AdminUi::icon('document', 64, 'media-preview__file-icon', 1.8), JSON_UNESCAPED_SLASHES) ?>
                        + '<div style="margin-top:12px; font-weight:600;">' + mime + '</div></div>';
                }
            }

            if (modal) modal.classList.add('is-open');
        });
    });

    if (modalClose) {
        modalClose.addEventListener('click', function() {
            if (modal) modal.classList.remove('is-open');
        });
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('is-open');
            }
        });
    }

    if (modalCopyBtn && modalFileUrl) {
        modalCopyBtn.addEventListener('click', function() {
            if (window.copyToClipboard) {
                window.copyToClipboard(modalFileUrl.value, modalCopyBtn);
            }
        });
    }

    // 4. Переключение режима Список / Сетка
    var container = document.getElementById('files_container');
    var btnList = document.getElementById('view_mode_list');
    var btnGrid = document.getElementById('view_mode_grid');

    if (container && btnList && btnGrid) {
        var savedMode = localStorage.getItem('artstudio:files-view-mode') || 'grid';
        setMode(savedMode);

        btnList.addEventListener('click', function() { setMode('list'); });
        btnGrid.addEventListener('click', function() { setMode('grid'); });

        function setMode(mode) {
            if (mode === 'list') {
                container.className = 'files-view-list';
                btnList.classList.add('is-active');
                btnGrid.classList.remove('is-active');
            } else {
                container.className = 'files-view-grid';
                btnGrid.classList.add('is-active');
                btnList.classList.remove('is-active');
            }
            try { localStorage.setItem('artstudio:files-view-mode', mode); } catch(e) {}
        }
    }

    // 5. Поиск по списку (live search)
    var searchInput = document.getElementById('media_search_input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var term = searchInput.value.toLowerCase().trim();
            document.querySelectorAll('.media-card').forEach(function(card) {
                var name = (card.getAttribute('data-name') || '').toLowerCase();
                card.style.display = name.includes(term) ? '' : 'none';
            });
        });
    }
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
