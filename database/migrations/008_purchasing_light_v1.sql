CREATE TABLE IF NOT EXISTS purchase_lists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_purchase_lists_status (status),
    INDEX idx_purchase_lists_updated (updated_at),
    CONSTRAINT chk_purchase_lists_status CHECK (status IN ('draft', 'reviewed', 'exported'))
);

CREATE TABLE IF NOT EXISTS purchase_list_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_list_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    supplier_item_id BIGINT UNSIGNED NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    sku_snapshot VARCHAR(120) NULL,
    supplier_sku_snapshot VARCHAR(120) NULL,
    supplier_title_snapshot VARCHAR(255) NULL,
    supplier_price_snapshot DECIMAL(12,2) NULL,
    supplier_stock_snapshot INT NULL,
    current_stock_quantity INT NULL,
    suggested_quantity INT NULL,
    selected_quantity INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_pli_purchase_list (purchase_list_id),
    INDEX idx_pli_product (product_id),
    INDEX idx_pli_supplier (supplier_id),
    INDEX idx_pli_supplier_item (supplier_item_id),
    CONSTRAINT fk_pli_purchase_list FOREIGN KEY (purchase_list_id) REFERENCES purchase_lists(id) ON DELETE CASCADE,
    CONSTRAINT fk_pli_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pli_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    CONSTRAINT fk_pli_supplier_item FOREIGN KEY (supplier_item_id) REFERENCES supplier_items(id) ON DELETE SET NULL
);
