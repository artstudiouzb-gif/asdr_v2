-- Проекты становятся страницами с подтипом.
--
-- Зачем: у проекта появилась нужда в конструкторе блоков, а вся его обвязка
-- (ревизии, сниппеты, пресеты, копирование перевода, сброс кэша) адресуется
-- page_id. Делать блоки полиморфными — та же работа, что и слияние, но без
-- выгоды: после слияния у проекта тот же редактор, что и у страницы.
--
-- Что остаётся прежним: адреса (/projects/{slug}), раздел «Проекты» в админке,
-- галерея и произвольные поля проекта, блоки projects_list и cards_grid.
--
-- Совместимость: `projects` и `project_translations` остаются как VIEW над
-- новыми таблицами — читающий код (поиск, карта сайта, счётчики, помощники
-- переводов) продолжает работать без правок. Пишущий код переведён на pages;
-- вставка через VIEW запрещена бы молча создала обычную страницу, поэтому
-- INSERT'ы в проекты переписаны на pages с entity_type='project'.

-- 1. Поля, которые есть у проекта, но не было у страницы -------------------
ALTER TABLE pages
    ADD COLUMN entity_type ENUM('page', 'project') NOT NULL DEFAULT 'page'
        COMMENT 'подтип записи: обычная страница или проект' AFTER slug,
    ADD COLUMN cover_image VARCHAR(255) NULL COMMENT 'обложка (проекты)' AFTER `lead`,
    ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'показывать на главном (проекты)',
    ADD COLUMN sort_order INT NOT NULL DEFAULT 0 COMMENT 'ручной порядок (проекты)',
    ADD COLUMN legacy_project_id INT UNSIGNED NULL COMMENT 'временная карта переноса, удаляется в конце миграции';

-- Slug уникален внутри своего типа: страница «about» и проект «about» жили в
-- разных таблицах и разных адресных пространствах, так и остаётся.
ALTER TABLE pages DROP INDEX uq_pages_slug_lang;
ALTER TABLE pages ADD UNIQUE KEY uq_pages_type_slug_lang (entity_type, slug, lang);
ALTER TABLE pages ADD KEY idx_pages_projects (entity_type, status, deleted_at, is_featured, sort_order);

-- 2. Перенос самих проектов -------------------------------------------------
-- Анонс для карточки берём из описания: теги вырезаем, пробелы схлопываем,
-- режем до 300 знаков. Полное описание ниже становится блоком, поэтому здесь
-- нужен именно короткий текст, а не копия тела.
INSERT INTO pages (
    title, slug, entity_type, `lead`, cover_image, is_featured, sort_order,
    status, is_home, layout_type, lang, translation_group_id,
    created_at, updated_at, lock_version, deleted_at, legacy_project_id
)
SELECT
    src.title,
    src.slug,
    'project',
    NULLIF(TRIM(LEFT(REGEXP_REPLACE(REGEXP_REPLACE(COALESCE(src.description, ''), '<[^>]*>', ' '), '[[:space:]]+', ' '), 300)), ''),
    src.cover_image,
    src.is_featured,
    src.sort_order,
    src.status,
    0,
    'no_sidebar',
    src.lang,
    NULL,
    src.created_at,
    src.updated_at,
    src.lock_version,
    src.deleted_at,
    src.id
FROM projects src;

-- Группы переводов пересчитываем на новые id.
UPDATE pages tgt
    JOIN projects src ON src.id = tgt.legacy_project_id
    JOIN pages grp ON grp.legacy_project_id = COALESCE(NULLIF(src.translation_group_id, 0), src.id)
SET tgt.translation_group_id = grp.id
WHERE tgt.legacy_project_id IS NOT NULL;

-- 3. Описание проекта становится текстовым блоком страницы ------------------
INSERT INTO blocks (page_id, lang, type, title, data, custom_css, sort_order, is_active, created_at)
SELECT
    tgt.id,
    tgt.lang,
    'text',
    NULL,
    JSON_OBJECT('variant', 'default', 'content', src.description),
    '',
    0,
    1,
    NOW()
FROM pages tgt
    JOIN projects src ON src.id = tgt.legacy_project_id
WHERE TRIM(COALESCE(src.description, '')) <> '';

-- 4. Переводы проекта — отдельные записи своего языка ------------------------
-- Редактор работает с языками именно так: у языковой версии своя карточка в
-- разделе, связанная через translation_group_id (кнопка «Создать перевод»).
-- Slug при этом остаётся общим: он уникален в пределах пары «тип + язык».
INSERT INTO pages (
    title, slug, entity_type, `lead`, cover_image, is_featured, sort_order,
    status, is_home, layout_type, lang, translation_group_id, created_at, updated_at
)
SELECT
    pt.title,
    base.slug,
    'project',
    NULLIF(TRIM(LEFT(REGEXP_REPLACE(REGEXP_REPLACE(COALESCE(pt.description, ''), '<[^>]*>', ' '), '[[:space:]]+', ' '), 300)), ''),
    base.cover_image,
    base.is_featured,
    base.sort_order,
    base.status,
    0,
    'no_sidebar',
    pt.lang,
    COALESCE(NULLIF(base.translation_group_id, 0), base.id),
    base.created_at,
    base.updated_at
FROM project_translations pt
    JOIN pages base ON base.legacy_project_id = pt.project_id
WHERE TRIM(COALESCE(pt.title, '')) <> ''
  AND pt.lang <> base.lang
  -- Своя запись этого языка уже могла существовать (механизм «отдельная
  -- запись»): второй такой же создавать нельзя.
  AND NOT EXISTS (
      SELECT 1 FROM projects other
      WHERE COALESCE(NULLIF(other.translation_group_id, 0), other.id)
            = COALESCE(NULLIF(base.translation_group_id, 0), base.id)
        AND other.lang = pt.lang
  );

-- Тело перевода — текстовый блок его собственной записи.
INSERT INTO blocks (page_id, lang, type, title, data, custom_css, sort_order, is_active, created_at)
SELECT
    tgt.id,
    tgt.lang,
    'text',
    NULL,
    JSON_OBJECT('variant', 'default', 'content', pt.description),
    '',
    0,
    1,
    NOW()
FROM project_translations pt
    JOIN pages base ON base.legacy_project_id = pt.project_id
    JOIN pages tgt ON tgt.entity_type = 'project'
                  AND tgt.lang = pt.lang
                  AND tgt.slug = base.slug
WHERE TRIM(COALESCE(pt.description, '')) <> '';

-- 5. Галерея, поля и ревизии переезжают на новые id --------------------------
ALTER TABLE project_images DROP FOREIGN KEY fk_project_images_project;
ALTER TABLE project_fields DROP FOREIGN KEY fk_project_fields_project;

UPDATE project_images pi JOIN pages tgt ON tgt.legacy_project_id = pi.project_id
SET pi.project_id = tgt.id;
UPDATE project_fields pf JOIN pages tgt ON tgt.legacy_project_id = pf.project_id
SET pf.project_id = tgt.id;
UPDATE content_revisions cr JOIN pages tgt ON tgt.legacy_project_id = cr.entity_id
SET cr.entity_id = tgt.id
WHERE cr.entity_type = 'project';

ALTER TABLE project_images
    ADD CONSTRAINT fk_project_images_project FOREIGN KEY (project_id) REFERENCES pages(id) ON DELETE CASCADE;
ALTER TABLE project_fields
    ADD CONSTRAINT fk_project_fields_project FOREIGN KEY (project_id) REFERENCES pages(id) ON DELETE CASCADE;

-- 6. Старые таблицы уступают место совместимым представлениям ----------------
DROP TABLE project_translations;
DROP TABLE projects;

CREATE OR REPLACE VIEW projects AS
SELECT
    p.id,
    p.title,
    p.slug,
    p.`lead` AS description,
    p.cover_image,
    p.status,
    p.is_featured,
    p.sort_order,
    p.created_at,
    p.updated_at,
    p.lock_version,
    p.deleted_at,
    p.lang,
    p.translation_group_id
FROM pages p
WHERE p.entity_type = 'project'
-- WITH CHECK OPTION: вставка через представление не проставила бы entity_type и
-- молча создала обычную страницу. Пусть лучше падает с ошибкой.
WITH CHECK OPTION;

CREATE OR REPLACE VIEW project_translations AS
SELECT
    pt.id,
    pt.page_id AS project_id,
    pt.lang,
    pt.title,
    pt.`lead` AS description
FROM page_translations pt
    JOIN pages p ON p.id = pt.page_id AND p.entity_type = 'project';

ALTER TABLE pages DROP COLUMN legacy_project_id;
