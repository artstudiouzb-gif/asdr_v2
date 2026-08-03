-- Редакционные метаданные медиабиблиотеки.
-- Изображение загружается и описывается до привязки к новости/странице.

ALTER TABLE files
    ADD COLUMN IF NOT EXISTS alt_text VARCHAR(255) NULL AFTER uploaded_by,
    ADD COLUMN IF NOT EXISTS caption VARCHAR(255) NULL AFTER alt_text,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER caption,
    ADD COLUMN IF NOT EXISTS credit VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS focal_x TINYINT UNSIGNED NULL AFTER credit,
    ADD COLUMN IF NOT EXISTS focal_y TINYINT UNSIGNED NULL AFTER focal_x,
    ADD COLUMN IF NOT EXISTS metadata_updated_at DATETIME NULL AFTER focal_y;
