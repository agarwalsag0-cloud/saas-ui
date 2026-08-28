-- Public Business Website feature update
-- Import this ONCE only if you already imported an older install.sql before this feature update.
-- Fresh installs only need database/install.sql.

ALTER TABLE businesses
    ADD COLUMN tagline VARCHAR(255) NULL AFTER category;

ALTER TABLE business_settings ADD COLUMN website_enabled TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE business_settings ADD COLUMN show_in_directory TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE business_settings ADD COLUMN allow_public_enquiries TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE business_settings ADD COLUMN allow_public_orders TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE business_settings ADD COLUMN theme_preset ENUM('modern','elegant','minimal','professional','bold') NOT NULL DEFAULT 'modern';
ALTER TABLE business_settings ADD COLUMN layout_style ENUM('classic','showcase','compact') NOT NULL DEFAULT 'classic';
ALTER TABLE business_settings ADD COLUMN background_style ENUM('light','soft','gradient','dark') NOT NULL DEFAULT 'light';
ALTER TABLE business_settings ADD COLUMN button_style ENUM('rounded','pill','square') NOT NULL DEFAULT 'rounded';
ALTER TABLE business_settings ADD COLUMN text_style ENUM('system','serif','modern') NOT NULL DEFAULT 'system';
ALTER TABLE business_settings ADD COLUMN primary_color VARCHAR(20) NOT NULL DEFAULT '#2563eb';
ALTER TABLE business_settings ADD COLUMN secondary_color VARCHAR(20) NOT NULL DEFAULT '#0f172a';
ALTER TABLE business_settings ADD COLUMN section_visibility JSON NULL;
ALTER TABLE business_settings ADD COLUMN seo_title VARCHAR(190) NULL;
ALTER TABLE business_settings ADD COLUMN seo_description VARCHAR(300) NULL;
ALTER TABLE business_settings ADD INDEX idx_business_settings_website (website_enabled, show_in_directory);

ALTER TABLE categories
    ADD COLUMN visible_on_website TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active,
    ADD INDEX idx_categories_public (business_id, is_active, visible_on_website);

ALTER TABLE listings
    ADD COLUMN visible_on_website TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured,
    ADD INDEX idx_listings_public (business_id, status, visible_on_website, is_featured);

ALTER TABLE offers
    ADD COLUMN visible_on_website TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active,
    ADD INDEX idx_offers_public (business_id, is_active, visible_on_website);

INSERT IGNORE INTO business_settings (business_id, theme_color, accent_color, created_at, updated_at)
SELECT id, '#2563eb', '#f97316', NOW(), NOW()
FROM businesses;

UPDATE business_settings
SET primary_color = COALESCE(NULLIF(theme_color, ''), '#2563eb'),
    secondary_color = '#0f172a',
    website_enabled = 1,
    show_in_directory = 1,
    allow_public_enquiries = 1,
    allow_public_orders = 1
WHERE business_id IS NOT NULL;
