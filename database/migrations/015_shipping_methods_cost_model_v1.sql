CREATE TABLE IF NOT EXISTS shipping_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    price_ex_vat DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    price_inc_vat DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shipping_methods_code (code),
    INDEX idx_shipping_methods_active_sort (is_active, sort_order)
);

INSERT INTO shipping_methods (code, name, description, price_ex_vat, price_inc_vat, is_active, sort_order, created_at, updated_at)
SELECT 'standard', 'Standardfrakt', 'Leverans inom 2–5 vardagar.', 39.20, 49.00, 1, 10, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM shipping_methods WHERE code = 'standard');

INSERT INTO shipping_methods (code, name, description, price_ex_vat, price_inc_vat, is_active, sort_order, created_at, updated_at)
SELECT 'express', 'Expressfrakt', 'Prioriterad leverans inom 1–2 vardagar.', 79.20, 99.00, 1, 20, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM shipping_methods WHERE code = 'express');

INSERT INTO shipping_methods (code, name, description, price_ex_vat, price_inc_vat, is_active, sort_order, created_at, updated_at)
SELECT 'pickup', 'Hämtas i butik', 'Hämta själv hos A-Racing efter avisering.', 0.00, 0.00, 1, 30, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM shipping_methods WHERE code = 'pickup');

ALTER TABLE orders
    ADD COLUMN shipping_method_code VARCHAR(60) NULL AFTER shipping_country,
    ADD COLUMN shipping_method_name VARCHAR(120) NULL AFTER shipping_method_code,
    ADD COLUMN shipping_method_description TEXT NULL AFTER shipping_method_name,
    ADD COLUMN shipping_cost_ex_vat DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER subtotal_amount,
    ADD COLUMN shipping_cost_inc_vat DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER shipping_cost_ex_vat,
    ADD INDEX idx_orders_shipping_method_code (shipping_method_code);

UPDATE orders
SET shipping_cost_inc_vat = COALESCE(shipping_amount, 0),
    shipping_cost_ex_vat = COALESCE(shipping_amount, 0),
    total_amount = COALESCE(subtotal_amount, 0) + COALESCE(shipping_amount, 0)
WHERE shipping_cost_inc_vat = 0 AND shipping_amount IS NOT NULL;
