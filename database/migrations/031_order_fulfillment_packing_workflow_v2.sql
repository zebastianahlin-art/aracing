ALTER TABLE orders
    ADD COLUMN picking_started_at DATETIME NULL AFTER fulfillment_status,
    ADD COLUMN packed_at DATETIME NULL AFTER picking_started_at,
    ADD COLUMN internal_pick_note TEXT NULL AFTER internal_reference,
    ADD COLUMN internal_pack_note TEXT NULL AFTER internal_pick_note,
    ADD INDEX idx_orders_fulfillment_operational (fulfillment_status, order_status, picking_started_at, packed_at);
