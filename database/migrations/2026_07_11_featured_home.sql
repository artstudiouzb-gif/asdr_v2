-- «Показать на главном»: флаг для проектов и фотоальбомов, чтобы блоки главной
-- (image_cards → Проекты, media_gallery → Фотоальбомы) собирали отмеченные
-- записи автоматически, без ручного дублирования.
ALTER TABLE projects     ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE photo_albums ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_published;
