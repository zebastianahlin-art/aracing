ALTER TABLE ai_product_import_drafts
    ADD COLUMN quality_label VARCHAR(40) NULL AFTER extraction_strategy,
    ADD COLUMN confidence_summary TEXT NULL AFTER quality_label,
    ADD COLUMN missing_fields LONGTEXT NULL AFTER confidence_summary,
    ADD COLUMN quality_flags LONGTEXT NULL AFTER missing_fields,
    ADD KEY idx_ai_import_quality_label (quality_label);
