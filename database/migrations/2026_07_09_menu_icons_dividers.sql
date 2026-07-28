-- Пункты меню: инлайновая SVG-иконка и признак «разделитель».
-- icon_svg  — очищенный санитайзером SVG, рисуется перед подписью пункта;
-- is_divider — пункт-разделитель (визуальная черта/зазор без ссылки).
ALTER TABLE menu_items
    ADD COLUMN icon_svg   TEXT NULL COMMENT 'инлайновая SVG-иконка пункта' AFTER title,
    ADD COLUMN is_divider TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'пункт-разделитель меню' AFTER icon_svg;
