ALTER TABLE orders
    ADD COLUMN internal_reference VARCHAR(120) NULL AFTER fulfillment_status,
    ADD COLUMN packed_at TIMESTAMP NULL AFTER internal_reference,
    ADD COLUMN shipped_at TIMESTAMP NULL AFTER packed_at,
    ADD INDEX idx_orders_payment_status (payment_status),
    ADD INDEX idx_orders_fulfillment_status (fulfillment_status),
    ADD INDEX idx_orders_internal_reference (internal_reference);

CREATE TABLE IF NOT EXISTS order_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    note_type VARCHAR(40) NOT NULL,
    note_text TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_order_notes_order_id (order_id),
    INDEX idx_order_notes_type (note_type),
    CONSTRAINT fk_order_notes_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    event_message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_order_events_order_id (order_id),
    INDEX idx_order_events_type (event_type),
    CONSTRAINT fk_order_events_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
