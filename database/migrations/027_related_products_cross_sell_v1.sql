CREATE TABLE IF NOT EXISTS product_relations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    related_product_id BIGINT UNSIGNED NOT NULL,
    relation_type VARCHAR(40) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_relations_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_relations_related_product FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT chk_product_relations_not_self CHECK (product_id <> related_product_id),
    UNIQUE KEY uq_product_relations_pair_type (product_id, related_product_id, relation_type),
    KEY idx_product_relations_product_id (product_id),
    KEY idx_product_relations_related_product_id (related_product_id),
    KEY idx_product_relations_relation_type (relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
