ALTER TABLE products
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER sku;

CREATE INDEX idx_products_is_active ON products (is_active);
CREATE INDEX idx_products_category_active ON products (category_id, is_active);
CREATE INDEX idx_products_brand_id ON products (brand_id);

CREATE TABLE IF NOT EXISTS product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_product_images_product_id (product_id),
    INDEX idx_product_images_sort (product_id, sort_order),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
