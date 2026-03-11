CREATE TABLE IF NOT EXISTS purchase_order_drafts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NULL,
    status VARCHAR(40) NOT NULL,
    order_number VARCHAR(60) NOT NULL,
    supplier_name_snapshot VARCHAR(190) NULL,
    supplier_reference VARCHAR(120) NULL,
    internal_note TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    exported_at DATETIME NULL,
    UNIQUE KEY uk_purchase_order_drafts_order_number (order_number),
    KEY idx_purchase_order_drafts_supplier_id (supplier_id),
    KEY idx_purchase_order_drafts_status (status),
    CONSTRAINT fk_purchase_order_drafts_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    CONSTRAINT chk_purchase_order_drafts_status CHECK (status IN ('draft', 'exported', 'cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_order_draft_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_draft_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    supplier_item_id BIGINT UNSIGNED NULL,
    sku VARCHAR(120) NULL,
    supplier_sku VARCHAR(120) NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_cost_snapshot DECIMAL(12,2) NULL,
    line_note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    KEY idx_pod_items_purchase_order_draft_id (purchase_order_draft_id),
    KEY idx_pod_items_supplier_item_id (supplier_item_id),
    CONSTRAINT fk_pod_items_draft FOREIGN KEY (purchase_order_draft_id) REFERENCES purchase_order_drafts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pod_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_pod_items_supplier_item FOREIGN KEY (supplier_item_id) REFERENCES supplier_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
