CREATE TABLE IF NOT EXISTS support_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(60) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL,
    name VARCHAR(190) NULL,
    phone VARCHAR(60) NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(40) NOT NULL,
    priority VARCHAR(40) NULL,
    source VARCHAR(40) NOT NULL,
    admin_note TEXT NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_support_cases_case_number (case_number),
    INDEX idx_support_cases_user_id (user_id),
    INDEX idx_support_cases_order_id (order_id),
    INDEX idx_support_cases_status (status),
    INDEX idx_support_cases_source (source),
    CONSTRAINT fk_support_cases_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_support_cases_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS support_case_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    support_case_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    from_value VARCHAR(120) NULL,
    to_value VARCHAR(120) NULL,
    comment TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_support_case_history_case_id (support_case_id),
    CONSTRAINT fk_support_case_history_case FOREIGN KEY (support_case_id) REFERENCES support_cases(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_case_history_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
