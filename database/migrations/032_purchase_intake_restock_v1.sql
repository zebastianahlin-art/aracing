CREATE TABLE IF NOT EXISTS restock_flags (
    product_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (product_id),
    KEY idx_restock_flags_status (status),
    CONSTRAINT fk_restock_flags_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT chk_restock_flags_status CHECK (status IN ('reviewed', 'handling'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
