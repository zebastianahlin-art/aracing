ALTER TABLE orders
    ADD COLUMN payment_provider VARCHAR(60) NULL AFTER payment_method,
    ADD COLUMN payment_provider_reference VARCHAR(190) NULL AFTER payment_reference,
    ADD COLUMN payment_provider_session_id VARCHAR(190) NULL AFTER payment_provider_reference,
    ADD COLUMN payment_provider_status VARCHAR(120) NULL AFTER payment_provider_session_id,
    ADD COLUMN payment_authorized_at DATETIME NULL AFTER payment_provider_status,
    ADD COLUMN payment_paid_at DATETIME NULL AFTER payment_authorized_at,
    ADD COLUMN payment_failed_at DATETIME NULL AFTER payment_paid_at,
    ADD INDEX idx_orders_payment_provider (payment_provider),
    ADD INDEX idx_orders_payment_provider_reference (payment_provider_reference),
    ADD INDEX idx_orders_payment_provider_session_id (payment_provider_session_id);

CREATE TABLE IF NOT EXISTS payment_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(60) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    provider_event_id VARCHAR(190) NULL,
    payment_reference VARCHAR(190) NULL,
    payload_json LONGTEXT NULL,
    status_before VARCHAR(40) NULL,
    status_after VARCHAR(40) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_events_order_id (order_id),
    INDEX idx_payment_events_provider (provider),
    INDEX idx_payment_events_provider_event_id (provider_event_id),
    INDEX idx_payment_events_payment_reference (payment_reference),
    INDEX idx_payment_events_event_type (event_type),
    UNIQUE KEY uq_payment_events_provider_event (provider, provider_event_id),
    CONSTRAINT fk_payment_events_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
