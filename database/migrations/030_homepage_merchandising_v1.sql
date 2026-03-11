CREATE TABLE IF NOT EXISTS homepage_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    subtitle VARCHAR(255) NULL,
    section_type VARCHAR(40) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    max_items INT NOT NULL DEFAULT 8,
    cta_label VARCHAR(120) NULL,
    cta_url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY ux_homepage_sections_key (section_key),
    KEY idx_homepage_sections_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_section_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    homepage_section_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(40) NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    KEY idx_homepage_section_items_section_id (homepage_section_id),
    KEY idx_homepage_section_items_active (is_active),
    KEY idx_homepage_section_items_sort (sort_order),
    CONSTRAINT fk_homepage_section_items_section
        FOREIGN KEY (homepage_section_id) REFERENCES homepage_sections(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
