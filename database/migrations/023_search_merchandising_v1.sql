ALTER TABLE products
    ADD COLUMN IF NOT EXISTS is_search_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_search_hidden,
    ADD COLUMN IF NOT EXISTS search_boost INT NOT NULL DEFAULT 0 AFTER is_featured,
    ADD COLUMN IF NOT EXISTS sort_priority INT NOT NULL DEFAULT 0 AFTER search_boost;

ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_public_visibility (is_active, is_search_hidden),
    ADD INDEX IF NOT EXISTS idx_products_featured_priority (is_featured, sort_priority),
    ADD INDEX IF NOT EXISTS idx_products_sort_priority (sort_priority);
