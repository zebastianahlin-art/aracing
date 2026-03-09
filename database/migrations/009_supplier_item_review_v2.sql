ALTER TABLE supplier_items
    ADD COLUMN IF NOT EXISTS review_status VARCHAR(40) NULL AFTER stock_qty,
    ADD COLUMN IF NOT EXISTS matched_at TIMESTAMP NULL AFTER review_status,
    ADD COLUMN IF NOT EXISTS last_reviewed_at TIMESTAMP NULL AFTER matched_at;

ALTER TABLE supplier_items
    ADD INDEX IF NOT EXISTS idx_supplier_items_review_status (review_status),
    ADD INDEX IF NOT EXISTS idx_supplier_items_matched_at (matched_at);
