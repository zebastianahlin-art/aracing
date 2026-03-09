ALTER TABLE orders
    ADD COLUMN tracking_number VARCHAR(120) NULL AFTER shipped_at,
    ADD COLUMN shipping_method VARCHAR(120) NULL AFTER tracking_number,
    ADD COLUMN shipped_by_name VARCHAR(120) NULL AFTER shipping_method,
    ADD COLUMN shipment_note TEXT NULL AFTER shipped_by_name,
    ADD INDEX idx_orders_tracking_number (tracking_number),
    ADD INDEX idx_orders_shipping_method (shipping_method),
    ADD INDEX idx_orders_shipped_at (shipped_at);
