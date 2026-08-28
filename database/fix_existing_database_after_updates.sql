-- Fix for 500 errors after replacing the PHP files over an older database.
-- Import this in phpMyAdmin ONLY if you kept an older database and now see a 500 error after updating the project.
-- Fresh installs should normally import database/install.sql instead.
-- This script is designed to be safe to run more than once on MySQL/MariaDB used by XAMPP.

DELIMITER $$

DROP PROCEDURE IF EXISTS mbsp_add_column_if_missing $$
CREATE PROCEDURE mbsp_add_column_if_missing(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @mbsp_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
        PREPARE mbsp_stmt FROM @mbsp_sql;
        EXECUTE mbsp_stmt;
        DEALLOCATE PREPARE mbsp_stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS mbsp_add_index_if_missing $$
CREATE PROCEDURE mbsp_add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @mbsp_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX ', p_index, ' ', p_definition);
        PREPARE mbsp_stmt FROM @mbsp_sql;
        EXECUTE mbsp_stmt;
        DEALLOCATE PREPARE mbsp_stmt;
    END IF;
END $$

DELIMITER ;

-- Public website columns from the previous update.
CALL mbsp_add_column_if_missing('businesses', 'tagline', '`tagline` VARCHAR(255) NULL AFTER `category`');

CALL mbsp_add_column_if_missing('business_settings', 'website_enabled', '`website_enabled` TINYINT(1) NOT NULL DEFAULT 1');
CALL mbsp_add_column_if_missing('business_settings', 'show_in_directory', '`show_in_directory` TINYINT(1) NOT NULL DEFAULT 1');
CALL mbsp_add_column_if_missing('business_settings', 'allow_public_enquiries', '`allow_public_enquiries` TINYINT(1) NOT NULL DEFAULT 1');
CALL mbsp_add_column_if_missing('business_settings', 'allow_public_orders', '`allow_public_orders` TINYINT(1) NOT NULL DEFAULT 1');
CALL mbsp_add_column_if_missing('business_settings', 'theme_preset', '`theme_preset` ENUM(''modern'',''elegant'',''minimal'',''professional'',''bold'') NOT NULL DEFAULT ''modern''');
CALL mbsp_add_column_if_missing('business_settings', 'layout_style', '`layout_style` ENUM(''classic'',''showcase'',''compact'') NOT NULL DEFAULT ''classic''');
CALL mbsp_add_column_if_missing('business_settings', 'background_style', '`background_style` ENUM(''light'',''soft'',''gradient'',''dark'') NOT NULL DEFAULT ''light''');
CALL mbsp_add_column_if_missing('business_settings', 'button_style', '`button_style` ENUM(''rounded'',''pill'',''square'') NOT NULL DEFAULT ''rounded''');
CALL mbsp_add_column_if_missing('business_settings', 'text_style', '`text_style` ENUM(''system'',''serif'',''modern'') NOT NULL DEFAULT ''system''');
CALL mbsp_add_column_if_missing('business_settings', 'primary_color', '`primary_color` VARCHAR(20) NOT NULL DEFAULT ''#2563eb''');
CALL mbsp_add_column_if_missing('business_settings', 'secondary_color', '`secondary_color` VARCHAR(20) NOT NULL DEFAULT ''#0f172a''');
CALL mbsp_add_column_if_missing('business_settings', 'theme_color', '`theme_color` VARCHAR(20) NOT NULL DEFAULT ''#2563eb''');
CALL mbsp_add_column_if_missing('business_settings', 'accent_color', '`accent_color` VARCHAR(20) NOT NULL DEFAULT ''#f97316''');
CALL mbsp_add_column_if_missing('business_settings', 'section_visibility', '`section_visibility` JSON NULL');
CALL mbsp_add_column_if_missing('business_settings', 'seo_title', '`seo_title` VARCHAR(190) NULL');
CALL mbsp_add_column_if_missing('business_settings', 'seo_description', '`seo_description` VARCHAR(300) NULL');
CALL mbsp_add_column_if_missing('business_settings', 'portal_settings', '`portal_settings` JSON NULL');
CALL mbsp_add_index_if_missing('business_settings', 'idx_business_settings_website', '(`website_enabled`, `show_in_directory`)');

CALL mbsp_add_column_if_missing('categories', 'visible_on_website', '`visible_on_website` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`');
CALL mbsp_add_index_if_missing('categories', 'idx_categories_public', '(`business_id`, `is_active`, `visible_on_website`)');

CALL mbsp_add_column_if_missing('listings', 'visible_on_website', '`visible_on_website` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_featured`');
CALL mbsp_add_index_if_missing('listings', 'idx_listings_public', '(`business_id`, `status`, `visible_on_website`, `is_featured`)');

CALL mbsp_add_column_if_missing('offers', 'visible_on_website', '`visible_on_website` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`');
CALL mbsp_add_index_if_missing('offers', 'idx_offers_public', '(`business_id`, `is_active`, `visible_on_website`)');

INSERT IGNORE INTO business_settings (business_id, theme_color, accent_color, created_at, updated_at)
SELECT id, '#2563eb', '#f97316', NOW(), NOW()
FROM businesses;

UPDATE business_settings
SET primary_color = COALESCE(NULLIF(theme_color, ''), '#2563eb'),
    secondary_color = COALESCE(NULLIF(secondary_color, ''), '#0f172a'),
    website_enabled = COALESCE(website_enabled, 1),
    show_in_directory = COALESCE(show_in_directory, 1),
    allow_public_enquiries = COALESCE(allow_public_enquiries, 1),
    allow_public_orders = COALESCE(allow_public_orders, 1)
WHERE business_id IS NOT NULL;

-- Feature registry and configurable plan columns.
CREATE TABLE IF NOT EXISTS platform_features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(120) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    category VARCHAR(120) NOT NULL DEFAULT 'General',
    icon VARCHAR(40) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    available_for_plans TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_platform_features_identifier (identifier),
    KEY idx_platform_features_category (category, sort_order),
    KEY idx_platform_features_active (is_active, available_for_plans)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL mbsp_add_column_if_missing('subscription_plans', 'monthly_price', '`monthly_price` DECIMAL(12,2) NULL AFTER `price`');
CALL mbsp_add_column_if_missing('subscription_plans', 'yearly_price', '`yearly_price` DECIMAL(12,2) NULL AFTER `monthly_price`');

CREATE TABLE IF NOT EXISTS subscription_plan_features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    limits_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_subscription_plan_features (plan_id, feature_id),
    KEY idx_plan_features_feature (feature_id),
    CONSTRAINT fk_plan_features_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_plan_features_feature FOREIGN KEY (feature_id) REFERENCES platform_features(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO platform_features (identifier, name, description, category, icon, is_active, available_for_plans, sort_order, created_at, updated_at) VALUES
('business_profile', 'Business Profile', 'Manage public business information, contact details and basic branding.', 'Core Business', '🏪', 1, 1, 10, NOW(), NOW()),
('public_website', 'Public Business Website', 'Dynamic public mini-website for the business.', 'Website & Online Presence', '🌐', 1, 1, 20, NOW(), NOW()),
('website_customization', 'Website Theme Customization', 'Theme presets, colors, layout, buttons, section visibility and appearance controls.', 'Website & Online Presence', '🎨', 1, 1, 30, NOW(), NOW()),
('custom_branding', 'Custom Business Branding', 'Logo, cover image, tagline and business-specific public branding.', 'Website & Online Presence', '🖼', 1, 1, 40, NOW(), NOW()),
('basic_seo', 'Basic SEO Settings', 'SEO page title and meta description for public website pages.', 'Website & Online Presence', '🔎', 1, 1, 50, NOW(), NOW()),
('enquiries', 'Enquiry Form', 'Public enquiry form and business enquiry management.', 'Customer Interaction', '💬', 1, 1, 100, NOW(), NOW()),
('customer_management', 'Customer Management', 'Customer records generated from enquiries and orders.', 'Customer Interaction', '👥', 1, 1, 110, NOW(), NOW()),
('lead_management', 'Contact/Lead Management', 'Lead tracking, statuses and internal follow-up notes.', 'Customer Interaction', '📌', 1, 1, 120, NOW(), NOW()),
('whatsapp_button', 'WhatsApp Contact Button', 'Future-ready WhatsApp contact and automation entry point.', 'Customer Interaction', '🟢', 1, 1, 130, NOW(), NOW()),
('product_management', 'Product Management', 'Create and manage physical product listings.', 'Products & Services', '🛒', 1, 1, 200, NOW(), NOW()),
('service_management', 'Service Management', 'Create and manage service, package, booking and custom listings.', 'Products & Services', '🧾', 1, 1, 210, NOW(), NOW()),
('categories', 'Categories', 'Create and manage public product/service categories.', 'Products & Services', '🗂', 1, 1, 220, NOW(), NOW()),
('offers', 'Offers', 'Create and display active promotions/offers.', 'Products & Services', '🏷', 1, 1, 230, NOW(), NOW()),
('featured_listings', 'Featured Products/Services', 'Mark listings as featured on the public website.', 'Products & Services', '⭐', 1, 1, 240, NOW(), NOW()),
('inventory', 'Inventory Management', 'Track stock quantities for product listings.', 'Products & Services', '📦', 1, 1, 250, NOW(), NOW()),
('orders', 'Orders', 'Order/request management for products and services.', 'Orders & Bookings', '📦', 1, 1, 300, NOW(), NOW()),
('booking_requests', 'Booking Requests', 'Accept and manage booking-style requests.', 'Orders & Bookings', '📅', 1, 1, 310, NOW(), NOW()),
('service_requests', 'Service Requests', 'Accept and manage service request workflows.', 'Orders & Bookings', '🛠', 1, 1, 320, NOW(), NOW()),
('dashboard_analytics', 'Dashboard Analytics', 'Business dashboard statistics and activity overview.', 'Business Tools', '📈', 1, 1, 400, NOW(), NOW()),
('advanced_analytics', 'Advanced Analytics', 'Future advanced analytics, reports and insights.', 'Business Tools', '📊', 1, 1, 410, NOW(), NOW()),
('notifications', 'Notifications', 'Internal notifications for enquiries, orders and subscription events.', 'Business Tools', '🔔', 1, 1, 420, NOW(), NOW()),
('customer_history', 'Customer History', 'Future detailed customer history and timeline.', 'Business Tools', '🕘', 1, 1, 430, NOW(), NOW()),
('activity_tracking', 'Activity Tracking', 'Tenant activity logs and operational history.', 'Business Tools', '🧭', 1, 1, 440, NOW(), NOW()),
('online_payments', 'Online Payments', 'Future Razorpay/Stripe or other gateway payment acceptance.', 'Future Premium Features', '💳', 1, 1, 500, NOW(), NOW()),
('coupons', 'Coupons', 'Future coupons and discount code engine.', 'Future Premium Features', '🎟', 1, 1, 510, NOW(), NOW()),
('custom_domain', 'Custom Domain', 'Future custom domains/subdomains for business websites.', 'Future Premium Features', '🔗', 1, 1, 520, NOW(), NOW()),
('email_automation', 'Email Automation', 'Future automated email notifications and campaigns.', 'Future Premium Features', '✉️', 1, 1, 530, NOW(), NOW()),
('sms_automation', 'SMS Automation', 'Future SMS automation and alerts.', 'Future Premium Features', '📲', 1, 1, 540, NOW(), NOW()),
('ai_tools', 'AI Tools', 'Future AI assistance and content tools.', 'Future Premium Features', '🤖', 1, 1, 550, NOW(), NOW());

-- Preserve access for existing old plans. Super Admin can later remove/add features plan-by-plan.
INSERT IGNORE INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT sp.id, pf.id, 1, NULL, NOW(), NOW()
FROM subscription_plans sp
CROSS JOIN platform_features pf
WHERE pf.is_active = 1
  AND pf.available_for_plans = 1
  AND pf.identifier IN (
    'business_profile','public_website','website_customization','custom_branding','basic_seo',
    'enquiries','customer_management','lead_management','product_management','service_management',
    'categories','offers','featured_listings','inventory','orders','booking_requests','service_requests',
    'dashboard_analytics','notifications','activity_tracking'
  );

UPDATE subscription_plans
SET monthly_price = COALESCE(monthly_price, CASE WHEN billing_cycle = 'yearly' THEN NULL ELSE price END),
    yearly_price = COALESCE(yearly_price, CASE WHEN billing_cycle = 'yearly' THEN price ELSE NULL END);

DROP PROCEDURE IF EXISTS mbsp_add_column_if_missing;
DROP PROCEDURE IF EXISTS mbsp_add_index_if_missing;
