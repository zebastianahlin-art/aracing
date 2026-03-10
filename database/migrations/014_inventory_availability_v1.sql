ALTER TABLE products
    ADD COLUMN IF NOT EXISTS backorder_allowed TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_status,
    ADD COLUMN IF NOT EXISTS stock_updated_at DATETIME NULL AFTER backorder_allowed;

UPDATE products
SET stock_quantity = COALESCE(stock_quantity, 0),
    stock_status = CASE
        WHEN stock_status IN ('in_stock', 'out_of_stock', 'backorder') THEN stock_status
        WHEN stock_status IN ('i lager', 'låg lagerstatus') THEN 'in_stock'
        WHEN stock_status = 'slut i lager' THEN 'out_of_stock'
        ELSE 'out_of_stock'
    END,
    backorder_allowed = COALESCE(backorder_allowed, 0),
    stock_updated_at = COALESCE(stock_updated_at, updated_at, NOW());

ALTER TABLE products
    MODIFY COLUMN stock_quantity INT NOT NULL DEFAULT 0,
    MODIFY COLUMN stock_status VARCHAR(40) NOT NULL,
    MODIFY COLUMN backorder_allowed TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_backorder_allowed (backorder_allowed),
    ADD INDEX IF NOT EXISTS idx_products_stock_quantity (stock_quantity);

CREATE TABLE IF NOT EXISTS inventory_stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type VARCHAR(40) NOT NULL,
    quantity_delta INT NOT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    reference_type VARCHAR(60) NULL,
    reference_id BIGINT UNSIGNED NULL,
    comment TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_movements_product_id (product_id),
    INDEX idx_inventory_movements_type (movement_type),
    INDEX idx_inventory_movements_created_at (created_at),
    CONSTRAINT fk_inventory_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
