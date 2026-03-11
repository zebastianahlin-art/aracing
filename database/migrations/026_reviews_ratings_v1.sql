CREATE TABLE IF NOT EXISTS product_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,
    rating INT NOT NULL,
    title VARCHAR(190) NULL,
    review_text TEXT NULL,
    status VARCHAR(40) NOT NULL,
    is_verified_purchase TINYINT(1) NOT NULL DEFAULT 0,
    reviewer_name VARCHAR(190) NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_product_reviews_rating_range CHECK (rating >= 1 AND rating <= 5),
    CONSTRAINT fk_product_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_product_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product_reviews_product_id (product_id),
    INDEX idx_product_reviews_status (status),
    INDEX idx_product_reviews_user_id (user_id),
    INDEX idx_product_reviews_order_id (order_id),
    INDEX idx_product_reviews_product_status (product_id, status),
    UNIQUE KEY uq_product_reviews_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE products
    ADD COLUMN review_count INT NOT NULL DEFAULT 0 AFTER sort_priority,
    ADD COLUMN average_rating DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER review_count,
    ADD INDEX idx_products_review_count (review_count),
    ADD INDEX idx_products_average_rating (average_rating);
