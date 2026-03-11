ALTER TABLE users
    ADD COLUMN company_name VARCHAR(190) NULL AFTER phone,
    ADD COLUMN company_registration_number VARCHAR(120) NULL AFTER company_name,
    ADD COLUMN vat_number VARCHAR(120) NULL AFTER company_registration_number;

ALTER TABLE orders
    ADD COLUMN company_name VARCHAR(190) NULL AFTER customer_phone,
    ADD COLUMN company_registration_number VARCHAR(120) NULL AFTER company_name,
    ADD COLUMN vat_number VARCHAR(120) NULL AFTER company_registration_number;
