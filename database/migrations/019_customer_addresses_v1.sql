ALTER TABLE users
    ADD COLUMN address_line_1 VARCHAR(190) NULL AFTER phone,
    ADD COLUMN address_line_2 VARCHAR(190) NULL AFTER address_line_1,
    ADD COLUMN postal_code VARCHAR(40) NULL AFTER address_line_2,
    ADD COLUMN city VARCHAR(120) NULL AFTER postal_code,
    ADD COLUMN country_code VARCHAR(10) NULL AFTER city;
