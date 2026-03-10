CREATE TABLE IF NOT EXISTS email_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    related_type VARCHAR(60) NOT NULL,
    related_id BIGINT UNSIGNED NOT NULL,
    email_type VARCHAR(60) NOT NULL,
    recipient_email VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL,
    provider VARCHAR(60) NULL,
    provider_message_id VARCHAR(190) NULL,
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_email_messages_related (related_type, related_id),
    INDEX idx_email_messages_email_type (email_type),
    INDEX idx_email_messages_status (status),
    INDEX idx_email_messages_recipient (recipient_email)
);
