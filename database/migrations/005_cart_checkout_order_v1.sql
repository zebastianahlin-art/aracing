CREATE TABLE IF NOT EXISTS carts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(191) NOT NULL,
    currency_code VARCHAR(10) NOT NULL DEFAULT 'SEK',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_carts_session_id (session_id)
);

CREATE TABLE IF NOT EXISTS cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    sku_snapshot VARCHAR(120) NULL,
    unit_price_snapshot DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id),
    INDEX idx_cart_items_cart_id (cart_id),
    INDEX idx_cart_items_product_id (product_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL,
    status VARCHAR(40) NOT NULL,
    currency_code VARCHAR(10) NOT NULL DEFAULT 'SEK',
    customer_email VARCHAR(190) NOT NULL,
    customer_first_name VARCHAR(120) NOT NULL,
    customer_last_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(80) NULL,
    billing_address_line_1 VARCHAR(255) NOT NULL,
    billing_address_line_2 VARCHAR(255) NULL,
    billing_postal_code VARCHAR(40) NOT NULL,
    billing_city VARCHAR(120) NOT NULL,
    billing_country VARCHAR(2) NOT NULL,
    shipping_first_name VARCHAR(120) NOT NULL,
    shipping_last_name VARCHAR(120) NOT NULL,
    shipping_phone VARCHAR(80) NULL,
    shipping_address_line_1 VARCHAR(255) NOT NULL,
    shipping_address_line_2 VARCHAR(255) NULL,
    shipping_postal_code VARCHAR(40) NOT NULL,
    shipping_city VARCHAR(120) NOT NULL,
    shipping_country VARCHAR(2) NOT NULL,
    order_notes TEXT NULL,
    subtotal_amount DECIMAL(12,2) NOT NULL,
    shipping_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    payment_status VARCHAR(40) NOT NULL,
    fulfillment_status VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_orders_order_number (order_number),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at),
    INDEX idx_orders_email (customer_email)
);

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    sku_snapshot VARCHAR(120) NULL,
    unit_price_snapshot DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
