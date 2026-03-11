ALTER TABLE purchase_order_drafts
    ADD COLUMN IF NOT EXISTS receiving_status VARCHAR(40) NULL AFTER exported_at,
    ADD COLUMN IF NOT EXISTS received_at DATETIME NULL AFTER receiving_status;

UPDATE purchase_order_drafts
SET receiving_status = CASE
    WHEN status = 'cancelled' THEN 'cancelled'
    ELSE 'not_received'
END
WHERE receiving_status IS NULL;

ALTER TABLE purchase_order_drafts
    MODIFY COLUMN receiving_status VARCHAR(40) NOT NULL,
    ADD INDEX IF NOT EXISTS idx_purchase_order_drafts_receiving_status (receiving_status);

ALTER TABLE purchase_order_draft_items
    ADD COLUMN IF NOT EXISTS received_quantity INT NOT NULL DEFAULT 0 AFTER quantity,
    ADD COLUMN IF NOT EXISTS last_received_at DATETIME NULL AFTER received_quantity;

CREATE TABLE IF NOT EXISTS purchase_order_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_draft_id BIGINT UNSIGNED NOT NULL,
    received_by_user_id BIGINT UNSIGNED NULL,
    note TEXT NULL,
    submission_token VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_purchase_order_receipts_submission (purchase_order_draft_id, submission_token),
    KEY idx_purchase_order_receipts_draft (purchase_order_draft_id),
    KEY idx_purchase_order_receipts_created_at (created_at),
    CONSTRAINT fk_purchase_order_receipts_draft FOREIGN KEY (purchase_order_draft_id) REFERENCES purchase_order_drafts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_order_receipt_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_receipt_id BIGINT UNSIGNED NOT NULL,
    purchase_order_draft_item_id BIGINT UNSIGNED NOT NULL,
    quantity_received INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_purchase_order_receipt_items_receipt (purchase_order_receipt_id),
    KEY idx_purchase_order_receipt_items_draft_item (purchase_order_draft_item_id),
    CONSTRAINT fk_purchase_order_receipt_items_receipt FOREIGN KEY (purchase_order_receipt_id) REFERENCES purchase_order_receipts(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_order_receipt_items_draft_item FOREIGN KEY (purchase_order_draft_item_id) REFERENCES purchase_order_draft_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
