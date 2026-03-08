INSERT INTO brands (name, slug, created_at, updated_at) VALUES
('Brembo', 'brembo', NOW(), NOW()),
('Sparco', 'sparco', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO categories (name, slug, parent_id, created_at, updated_at) VALUES
('Bromsar', 'bromsar', NULL, NOW(), NOW()),
('Skyddsutrustning', 'skyddsutrustning', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO products (brand_id, category_id, name, slug, sku, description, is_active, created_at, updated_at)
SELECT b.id, c.id, 'Racing bromsbelägg X1', 'racing-bromsbelagg-x1', 'RBX1-001', 'MVP-demoprodukt för katalogflödet.', 1, NOW(), NOW()
FROM brands b, categories c
WHERE b.slug = 'brembo' AND c.slug = 'bromsar'
ON DUPLICATE KEY UPDATE updated_at = NOW(), is_active = VALUES(is_active);
