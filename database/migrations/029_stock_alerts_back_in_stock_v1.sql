CREATE TABLE IF NOT EXISTS stock_alert_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL,
    status VARCHAR(40) NOT NULL,
    subscribed_at DATETIME NOT NULL,
    notified_at DATETIME NULL,
    unsubscribed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_alert_subscriptions_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_alert_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_stock_alert_active_product_email (product_id, email, status),
    KEY idx_stock_alert_product_id (product_id),
    KEY idx_stock_alert_user_id (user_id),
    KEY idx_stock_alert_email (email),
    KEY idx_stock_alert_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
