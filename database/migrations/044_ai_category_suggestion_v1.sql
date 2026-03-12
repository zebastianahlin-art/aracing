ALTER TABLE ai_product_enrichment_suggestions
    ADD COLUMN suggested_category_id BIGINT UNSIGNED NULL AFTER input_snapshot,
    ADD KEY idx_ai_enrichment_suggested_category_id (suggested_category_id),
    ADD CONSTRAINT fk_ai_enrichment_suggested_category FOREIGN KEY (suggested_category_id) REFERENCES categories(id) ON DELETE SET NULL;
