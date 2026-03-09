CREATE TABLE IF NOT EXISTS cms_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    page_type ENUM('page', 'legal', 'info') NOT NULL DEFAULT 'page',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    content_html MEDIUMTEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY ux_cms_pages_slug (slug),
    KEY idx_cms_pages_active_type (is_active, page_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_home_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(80) NOT NULL,
    title VARCHAR(190) NULL,
    subtitle VARCHAR(255) NULL,
    body_html MEDIUMTEXT NULL,
    button_text VARCHAR(80) NULL,
    button_url VARCHAR(255) NULL,
    content_refs_json TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY ux_cms_home_sections_key (section_key),
    KEY idx_cms_home_sections_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
