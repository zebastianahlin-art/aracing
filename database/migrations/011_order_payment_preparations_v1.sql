ALTER TABLE orders
    ADD COLUMN payment_method VARCHAR(40) NULL AFTER payment_status,
    ADD COLUMN payment_reference VARCHAR(120) NULL AFTER payment_method,
    ADD COLUMN payment_note TEXT NULL AFTER payment_reference,
    ADD INDEX idx_orders_payment_method (payment_method),
    ADD INDEX idx_orders_payment_status_method (payment_status, payment_method);
