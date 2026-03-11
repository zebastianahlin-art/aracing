CREATE TABLE IF NOT EXISTS vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(120) NOT NULL,
    model VARCHAR(120) NOT NULL,
    generation VARCHAR(120) NULL,
    engine VARCHAR(120) NULL,
    fuel_type VARCHAR(60) NULL,
    year_from INT NULL,
    year_to INT NULL,
    body_type VARCHAR(60) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vehicles_make (make),
    INDEX idx_vehicles_model (model),
    INDEX idx_vehicles_is_active (is_active),
    INDEX idx_vehicles_make_model (make, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_fitments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    fitment_type VARCHAR(40) NOT NULL DEFAULT 'confirmed',
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_fitments_product_vehicle (product_id, vehicle_id),
    INDEX idx_product_fitments_product_id (product_id),
    INDEX idx_product_fitments_vehicle_id (vehicle_id),
    CONSTRAINT fk_product_fitments_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_fitments_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
