CREATE TABLE IF NOT EXISTS return_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    return_number VARCHAR(60) NOT NULL,
    status VARCHAR(40) NOT NULL,
    reason_code VARCHAR(60) NULL,
    customer_comment TEXT NULL,
    admin_note TEXT NULL,
    requested_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    received_at DATETIME NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_return_requests_return_number (return_number),
    INDEX idx_return_requests_order_id (order_id),
    INDEX idx_return_requests_user_id (user_id),
    INDEX idx_return_requests_status (status),
    CONSTRAINT fk_return_requests_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_return_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS return_request_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_request_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    reason_code VARCHAR(60) NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_return_request_items_return_request_id (return_request_id),
    INDEX idx_return_request_items_order_item_id (order_item_id),
    CONSTRAINT fk_return_request_items_request FOREIGN KEY (return_request_id) REFERENCES return_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_return_request_items_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_return_request_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS return_request_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_request_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    from_value VARCHAR(120) NULL,
    to_value VARCHAR(120) NULL,
    comment TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_return_request_history_request_id (return_request_id),
    CONSTRAINT fk_return_request_history_request FOREIGN KEY (return_request_id) REFERENCES return_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_return_request_history_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
