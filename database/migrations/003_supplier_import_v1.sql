CREATE TABLE IF NOT EXISTS suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    contact_name VARCHAR(180) NULL,
    contact_email VARCHAR(190) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_suppliers_active (is_active)
);

CREATE TABLE IF NOT EXISTS import_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    file_type VARCHAR(30) NOT NULL DEFAULT 'csv',
    delimiter VARCHAR(5) NOT NULL DEFAULT ',',
    enclosure VARCHAR(5) NULL,
    escape_char VARCHAR(5) NULL,
    column_mapping_json JSON NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_import_profiles_supplier (supplier_id),
    INDEX idx_import_profiles_active (is_active),
    CONSTRAINT fk_import_profiles_supplier_id__suppliers_id FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

ALTER TABLE import_runs
    ADD COLUMN IF NOT EXISTS supplier_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS import_profile_id BIGINT UNSIGNED NULL AFTER supplier_id,
    ADD COLUMN IF NOT EXISTS filename VARCHAR(255) NULL AFTER source,
    ADD COLUMN IF NOT EXISTS total_rows INT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS processed_rows INT UNSIGNED NOT NULL DEFAULT 0 AFTER total_rows,
    ADD COLUMN IF NOT EXISTS success_rows INT UNSIGNED NOT NULL DEFAULT 0 AFTER processed_rows,
    ADD COLUMN IF NOT EXISTS failed_rows INT UNSIGNED NOT NULL DEFAULT 0 AFTER success_rows;

ALTER TABLE import_runs
    ADD INDEX IF NOT EXISTS idx_import_runs_supplier (supplier_id),
    ADD INDEX IF NOT EXISTS idx_import_runs_profile (import_profile_id),
    ADD INDEX IF NOT EXISTS idx_import_runs_status (status);

ALTER TABLE import_runs
    ADD CONSTRAINT fk_import_runs_supplier_id__suppliers_id FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_import_runs_import_profile_id__import_profiles_id FOREIGN KEY (import_profile_id) REFERENCES import_profiles(id) ON DELETE SET NULL;

ALTER TABLE supplier_items
    ADD COLUMN IF NOT EXISTS supplier_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS supplier_title VARCHAR(255) NULL AFTER supplier_sku,
    ADD COLUMN IF NOT EXISTS import_run_id BIGINT UNSIGNED NULL AFTER raw_payload,
    ADD COLUMN IF NOT EXISTS price DECIMAL(12,2) NULL AFTER import_run_id,
    ADD COLUMN IF NOT EXISTS stock_qty INT NULL AFTER price;

ALTER TABLE supplier_items
    MODIFY COLUMN supplier_name VARCHAR(160) NULL;

ALTER TABLE supplier_items
    DROP INDEX IF EXISTS uq_supplier_item,
    ADD UNIQUE KEY uq_supplier_item_supplier (supplier_id, supplier_sku),
    ADD INDEX idx_supplier_items_supplier (supplier_id),
    ADD INDEX idx_supplier_items_import_run (import_run_id);

ALTER TABLE supplier_items
    ADD CONSTRAINT fk_supplier_items_supplier_id__suppliers_id FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_supplier_items_import_run_id__import_runs_id FOREIGN KEY (import_run_id) REFERENCES import_runs(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS import_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_run_id BIGINT UNSIGNED NOT NULL,
    source_row_number INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    raw_row_json JSON NOT NULL,
    mapped_row_json JSON NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_import_rows_run (import_run_id),
    INDEX idx_import_rows_status (status),
    CONSTRAINT fk_import_rows_import_run_id__import_runs_id FOREIGN KEY (import_run_id) REFERENCES import_runs(id) ON DELETE CASCADE
);
