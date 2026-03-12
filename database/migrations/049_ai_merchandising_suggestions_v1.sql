CREATE TABLE IF NOT EXISTS ai_merchandising_suggestions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    suggestion_type VARCHAR(60) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    suggested_product_ids JSON NOT NULL,
    context_snapshot JSON NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    INDEX idx_ai_merchandising_suggestions_status (status),
    INDEX idx_ai_merchandising_suggestions_type (suggestion_type),
    CONSTRAINT chk_ai_merchandising_suggestions_status CHECK (status IN ('pending', 'approved', 'rejected'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
