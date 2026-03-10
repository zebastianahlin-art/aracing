ALTER TABLE orders
    ADD COLUMN order_status VARCHAR(40) NULL AFTER status,
    ADD COLUMN carrier_code VARCHAR(60) NULL AFTER fulfillment_status,
    ADD COLUMN carrier_name VARCHAR(120) NULL AFTER carrier_code,
    ADD COLUMN tracking_url VARCHAR(255) NULL AFTER tracking_number,
    ADD COLUMN delivered_at DATETIME NULL AFTER shipped_at,
    ADD COLUMN cancelled_at DATETIME NULL AFTER delivered_at,
    ADD INDEX idx_orders_order_status (order_status),
    ADD INDEX idx_orders_fulfillment_status_v2 (fulfillment_status),
    ADD INDEX idx_orders_order_payment_fulfillment (order_status, payment_status, fulfillment_status),
    ADD INDEX idx_orders_shipped_delivered (shipped_at, delivered_at);

UPDATE orders
SET order_status = CASE
    WHEN status = 'pending' THEN 'placed'
    WHEN status = 'confirmed' THEN 'confirmed'
    WHEN status = 'cancelled' THEN 'cancelled'
    ELSE status
END
WHERE order_status IS NULL;

UPDATE orders
SET fulfillment_status = 'picking'
WHERE fulfillment_status = 'processing';

ALTER TABLE orders
    MODIFY COLUMN order_status VARCHAR(40) NOT NULL;

CREATE TABLE IF NOT EXISTS order_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    from_value VARCHAR(120) NULL,
    to_value VARCHAR(120) NULL,
    comment TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_order_status_history_order_id (order_id),
    INDEX idx_order_status_history_type (type),
    INDEX idx_order_status_history_created_at (created_at),
    CONSTRAINT fk_order_status_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

INSERT INTO order_status_history (order_id, type, from_value, to_value, comment, created_by_user_id, created_at)
SELECT id, 'order_status', NULL, order_status, 'Initial status from migration', NULL, COALESCE(updated_at, created_at, NOW())
FROM orders;
