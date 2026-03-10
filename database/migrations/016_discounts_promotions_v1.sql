CREATE TABLE IF NOT EXISTS discount_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    discount_type VARCHAR(40) NOT NULL,
    discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    minimum_order_amount DECIMAL(12,2) NULL,
    usage_limit INT NULL,
    usage_count INT NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_discount_codes_code (code),
    INDEX idx_discount_codes_active_window (is_active, starts_at, ends_at),
    INDEX idx_discount_codes_sort (sort_order)
);

ALTER TABLE carts
    ADD COLUMN discount_code VARCHAR(80) NULL AFTER currency_code,
    ADD INDEX idx_carts_discount_code (discount_code);

ALTER TABLE orders
    ADD COLUMN discount_code VARCHAR(80) NULL AFTER shipping_method_description,
    ADD COLUMN discount_name VARCHAR(120) NULL AFTER discount_code,
    ADD COLUMN discount_type VARCHAR(40) NULL AFTER discount_name,
    ADD COLUMN discount_value DECIMAL(12,2) NULL AFTER discount_type,
    ADD COLUMN discount_amount_ex_vat DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER shipping_cost_inc_vat,
    ADD COLUMN discount_amount_inc_vat DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER discount_amount_ex_vat,
    ADD INDEX idx_orders_discount_code (discount_code);

INSERT INTO discount_codes (
    code, name, description, discount_type, discount_value, minimum_order_amount,
    usage_limit, usage_count, starts_at, ends_at, is_active, sort_order, created_at, updated_at
)
SELECT
    'RACE10', 'Racingkampanj 10%', 'Ger 10% rabatt på produktsubtotal.',
    'percent', 10.00, NULL,
    NULL, 0, NULL, NULL, 1, 10, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM discount_codes WHERE code = 'RACE10');
