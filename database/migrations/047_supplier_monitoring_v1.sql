CREATE TABLE IF NOT EXISTS supplier_item_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_item_id BIGINT UNSIGNED NOT NULL,
    import_run_id BIGINT UNSIGNED NULL,
    import_batch_key VARCHAR(120) NULL,
    supplier_price DECIMAL(12,2) NULL,
    currency VARCHAR(12) NULL,
    stock_quantity INT NULL,
    stock_status VARCHAR(40) NULL,
    is_available TINYINT(1) NULL,
    captured_at DATETIME NOT NULL,
    INDEX idx_supplier_item_snapshots_supplier_item (supplier_item_id),
    INDEX idx_supplier_item_snapshots_captured_at (captured_at),
    INDEX idx_supplier_item_snapshots_import_run (import_run_id),
    INDEX idx_supplier_item_snapshots_batch (import_batch_key),
    CONSTRAINT fk_supplier_item_snapshots_item FOREIGN KEY (supplier_item_id) REFERENCES supplier_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_supplier_item_snapshots_run FOREIGN KEY (import_run_id) REFERENCES import_runs(id) ON DELETE SET NULL
);
