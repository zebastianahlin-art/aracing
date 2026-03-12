ALTER TABLE ai_product_import_drafts
    ADD COLUMN IF NOT EXISTS handed_off_at DATETIME NULL AFTER reviewed_at,
    ADD COLUMN IF NOT EXISTS handed_off_by_user_id BIGINT UNSIGNED NULL AFTER reviewed_by_user_id,
    ADD COLUMN IF NOT EXISTS handoff_target_type VARCHAR(60) NULL AFTER handed_off_at,
    ADD COLUMN IF NOT EXISTS handoff_target_id BIGINT UNSIGNED NULL AFTER handoff_target_type,
    ADD INDEX IF NOT EXISTS idx_ai_import_handed_off_by (handed_off_by_user_id),
    ADD INDEX IF NOT EXISTS idx_ai_import_handoff_target (handoff_target_type, handoff_target_id);

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS source_type VARCHAR(60) NULL AFTER sort_priority,
    ADD COLUMN IF NOT EXISTS source_reference_id BIGINT UNSIGNED NULL AFTER source_type,
    ADD COLUMN IF NOT EXISTS source_url TEXT NULL AFTER source_reference_id,
    ADD INDEX IF NOT EXISTS idx_products_source_reference (source_type, source_reference_id);
