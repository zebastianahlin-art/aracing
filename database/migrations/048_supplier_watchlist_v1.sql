CREATE TABLE IF NOT EXISTS monitored_supplier_entities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    priority_level VARCHAR(20) NOT NULL,
    note TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_mse_entity_type (entity_type),
    INDEX idx_mse_entity_id (entity_id),
    INDEX idx_mse_priority_level (priority_level),
    UNIQUE KEY uq_mse_entity (entity_type, entity_id)
);
