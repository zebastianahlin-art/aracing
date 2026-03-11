ALTER TABLE products
    ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS seo_description TEXT NULL AFTER seo_title,
    ADD COLUMN IF NOT EXISTS canonical_url VARCHAR(255) NULL AFTER seo_description,
    ADD COLUMN IF NOT EXISTS meta_robots VARCHAR(120) NULL AFTER canonical_url,
    ADD COLUMN IF NOT EXISTS is_indexable TINYINT(1) NOT NULL DEFAULT 1 AFTER meta_robots;

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255) NULL AFTER slug,
    ADD COLUMN IF NOT EXISTS seo_description TEXT NULL AFTER seo_title,
    ADD COLUMN IF NOT EXISTS canonical_url VARCHAR(255) NULL AFTER seo_description,
    ADD COLUMN IF NOT EXISTS meta_robots VARCHAR(120) NULL AFTER canonical_url,
    ADD COLUMN IF NOT EXISTS is_indexable TINYINT(1) NOT NULL DEFAULT 1 AFTER meta_robots;

ALTER TABLE cms_pages
    ADD COLUMN IF NOT EXISTS canonical_url VARCHAR(255) NULL AFTER meta_description,
    ADD COLUMN IF NOT EXISTS meta_robots VARCHAR(120) NULL AFTER canonical_url,
    ADD COLUMN IF NOT EXISTS is_indexable TINYINT(1) NOT NULL DEFAULT 1 AFTER meta_robots;
