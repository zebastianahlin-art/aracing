CREATE TABLE IF NOT EXISTS user_vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    primary_user_id BIGINT UNSIGNED AS (CASE WHEN is_primary = 1 THEN user_id ELSE NULL END) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_vehicles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_vehicles_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_vehicles_user_vehicle (user_id, vehicle_id),
    UNIQUE KEY uq_user_vehicles_one_primary (primary_user_id),
    KEY idx_user_vehicles_user_id (user_id),
    KEY idx_user_vehicles_vehicle_id (vehicle_id),
    KEY idx_user_vehicles_user_primary (user_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
