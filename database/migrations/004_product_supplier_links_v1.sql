ALTER TABLE products
    ADD COLUMN IF NOT EXISTS sale_price DECIMAL(12,2) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS currency_code VARCHAR(10) NOT NULL DEFAULT 'SEK' AFTER sale_price,
    ADD COLUMN IF NOT EXISTS stock_status VARCHAR(40) NULL AFTER currency_code,
    ADD COLUMN IF NOT EXISTS stock_quantity INT NULL AFTER stock_status;

ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_stock_status (stock_status),
    ADD INDEX IF NOT EXISTS idx_products_sale_price (sale_price);

CREATE TABLE IF NOT EXISTS product_supplier_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    supplier_item_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    supplier_sku_snapshot VARCHAR(120) NULL,
    supplier_title_snapshot VARCHAR(255) NULL,
    supplier_price_snapshot DECIMAL(12,2) NULL,
    supplier_stock_snapshot INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_product_supplier_item (product_id, supplier_item_id),
    INDEX idx_psl_product (product_id),
    INDEX idx_psl_supplier (supplier_id),
    INDEX idx_psl_supplier_item (supplier_item_id),
    CONSTRAINT fk_psl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_psl_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    CONSTRAINT fk_psl_supplier_item FOREIGN KEY (supplier_item_id) REFERENCES supplier_items(id) ON DELETE CASCADE,
    CONSTRAINT chk_psl_primary CHECK (is_primary IN (0,1))
);
