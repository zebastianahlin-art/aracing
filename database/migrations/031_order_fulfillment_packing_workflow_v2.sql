ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS picking_started_at DATETIME NULL AFTER fulfillment_status,
    ADD COLUMN IF NOT EXISTS internal_pick_note TEXT NULL AFTER internal_reference,
    ADD COLUMN IF NOT EXISTS internal_pack_note TEXT NULL AFTER internal_pick_note,
    ADD INDEX IF NOT EXISTS idx_orders_fulfillment_operational (fulfillment_status, order_status, picking_started_at, packed_at);
