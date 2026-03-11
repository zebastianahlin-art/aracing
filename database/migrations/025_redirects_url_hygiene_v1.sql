CREATE TABLE IF NOT EXISTS redirects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_path VARCHAR(255) NOT NULL,
    target_path VARCHAR(255) NOT NULL,
    redirect_type INT NOT NULL DEFAULT 301,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    hit_count INT NOT NULL DEFAULT 0,
    last_hit_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_redirects_source_path (source_path),
    KEY idx_redirects_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
