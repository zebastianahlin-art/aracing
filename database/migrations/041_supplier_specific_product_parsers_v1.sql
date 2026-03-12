ALTER TABLE ai_product_import_drafts
    ADD COLUMN parser_key VARCHAR(120) NULL AFTER status,
    ADD COLUMN parser_version VARCHAR(40) NULL AFTER parser_key,
    ADD COLUMN extraction_strategy VARCHAR(60) NULL AFTER parser_version;

ALTER TABLE ai_product_import_drafts
    ADD KEY idx_ai_import_parser_key (parser_key),
    ADD KEY idx_ai_import_strategy (extraction_strategy);
