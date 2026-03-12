CREATE TABLE IF NOT EXISTS search_query_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query_text VARCHAR(255) NOT NULL,
    normalized_query VARCHAR(255) NULL,
    result_count INT NOT NULL DEFAULT 0,
    searched_at DATETIME NOT NULL,
    session_id VARCHAR(120) NULL,
    selected_product_id BIGINT UNSIGNED NULL,
    INDEX idx_search_query_logs_normalized_query (normalized_query),
    INDEX idx_search_query_logs_searched_at (searched_at),
    INDEX idx_search_query_logs_result_count (result_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_query_suggestions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_query VARCHAR(255) NOT NULL,
    suggestion_type VARCHAR(60) NOT NULL,
    suggested_value VARCHAR(255) NOT NULL,
    explanation TEXT NULL,
    status VARCHAR(40) NOT NULL,
    created_at DATETIME NOT NULL,
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    INDEX idx_search_query_suggestions_source (source_query),
    INDEX idx_search_query_suggestions_status (status),
    INDEX idx_search_query_suggestions_type (suggestion_type),
    CONSTRAINT chk_search_query_suggestions_status CHECK (status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT chk_search_query_suggestions_type CHECK (suggestion_type IN ('synonym', 'redirect_query', 'query_alias'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_query_aliases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_query VARCHAR(255) NOT NULL,
    target_query VARCHAR(255) NOT NULL,
    alias_type VARCHAR(60) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    INDEX idx_search_query_aliases_source_active (source_query, is_active),
    INDEX idx_search_query_aliases_target (target_query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

