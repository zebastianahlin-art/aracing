ALTER TABLE supplier_fitment_candidates
    ADD COLUMN normalized_make VARCHAR(120) NULL AFTER raw_text,
    ADD COLUMN normalized_model VARCHAR(120) NULL AFTER normalized_make,
    ADD COLUMN normalized_generation VARCHAR(120) NULL AFTER normalized_model,
    ADD COLUMN normalized_engine VARCHAR(120) NULL AFTER normalized_generation,
    ADD COLUMN mapping_source VARCHAR(40) NULL AFTER confidence_label,
    ADD COLUMN mapping_note VARCHAR(255) NULL AFTER mapping_source,
    ADD INDEX idx_sfc_mapping_source (mapping_source);
