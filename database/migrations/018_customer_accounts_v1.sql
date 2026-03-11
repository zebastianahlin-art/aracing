CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'customer',
    first_name VARCHAR(120) NULL,
    last_name VARCHAR(120) NULL,
    phone VARCHAR(80) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role (role)
);

ALTER TABLE orders
    ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER currency_code,
    ADD INDEX idx_orders_user_id (user_id),
    ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
