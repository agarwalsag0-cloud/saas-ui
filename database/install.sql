-- Multi-Business Subscription Platform schema (structure + feature registry ONLY)
-- For fresh installs prefer database/mbsp_database.sql (this file + demo content,
-- one import). install.sql stays as the schema source of truth for testing/setup_db.php.
-- Import this file into the database you created in phpMyAdmin.
-- The schema intentionally does not CREATE or USE a database, so you can choose your own DB name.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS customer_accounts;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS enquiry_status_history;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS enquiries;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS business_subscriptions;
DROP TABLE IF EXISTS subscription_plan_features;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS platform_features;
DROP TABLE IF EXISTS business_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS businesses;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    category VARCHAR(120) NOT NULL,
    tagline VARCHAR(255) NULL,
    description TEXT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(120) NULL,
    country VARCHAR(120) NULL DEFAULT 'India',
    website VARCHAR(255) NULL,
    social_links JSON NULL,
    timings TEXT NULL,
    logo_path VARCHAR(255) NULL,
    cover_path VARCHAR(255) NULL,
    status ENUM('pending','under_review','changes_requested','approved','active','inactive','suspended','rejected','archived') NOT NULL DEFAULT 'pending',
    submitted_for_review_at DATETIME NULL,
    approved_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_businesses_slug (slug),
    KEY idx_businesses_status (status),
    KEY idx_businesses_category (category),
    KEY idx_businesses_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NULL,
    google_sub VARCHAR(190) NULL,
    avatar_path VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_customer_accounts_email (email),
    UNIQUE KEY uq_customer_accounts_google_sub (google_sub),
    KEY idx_customer_accounts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','business_owner','business_staff') NOT NULL,
    status ENUM('active','inactive','invited') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_business_role (business_id, role),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE business_settings (
    business_id BIGINT UNSIGNED PRIMARY KEY,
    website_enabled TINYINT(1) NOT NULL DEFAULT 1,
    website_published TINYINT(1) NOT NULL DEFAULT 0,
    website_published_at DATETIME NULL,
    allow_indexing TINYINT(1) NOT NULL DEFAULT 1,
    show_in_directory TINYINT(1) NOT NULL DEFAULT 1,
    allow_public_enquiries TINYINT(1) NOT NULL DEFAULT 1,
    allow_public_orders TINYINT(1) NOT NULL DEFAULT 1,
    theme_preset ENUM('modern','elegant','minimal','professional','bold') NOT NULL DEFAULT 'modern',
    layout_style ENUM('classic','showcase','compact') NOT NULL DEFAULT 'classic',
    background_style ENUM('light','soft','gradient','dark') NOT NULL DEFAULT 'light',
    button_style ENUM('rounded','pill','square') NOT NULL DEFAULT 'rounded',
    text_style ENUM('system','serif','modern') NOT NULL DEFAULT 'system',
    primary_color VARCHAR(20) NOT NULL DEFAULT '#2563eb',
    secondary_color VARCHAR(20) NOT NULL DEFAULT '#0f172a',
    theme_color VARCHAR(20) NOT NULL DEFAULT '#2563eb',
    accent_color VARCHAR(20) NOT NULL DEFAULT '#f97316',
    section_visibility JSON NULL,
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(300) NULL,
    portal_settings JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_business_settings_website (website_enabled, show_in_directory, website_published),
    CONSTRAINT fk_business_settings_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE platform_features (
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

CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    billing_cycle ENUM('monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    monthly_price DECIMAL(12,2) NULL,
    yearly_price DECIMAL(12,2) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'INR',
    features JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_subscription_plans_name (name),
    KEY idx_subscription_plans_active (is_active),
    KEY idx_subscription_plans_cycle (billing_cycle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE subscription_plan_features (
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

CREATE TABLE business_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','trialing','active','expired','suspended','cancelled') NOT NULL DEFAULT 'pending',
    starts_at DATE NOT NULL,
    expires_at DATE NOT NULL,
    grace_ends_at DATE NULL,
    renewal_status ENUM('manual','pending','renewed','cancelled') NOT NULL DEFAULT 'manual',
    auto_renew TINYINT(1) NOT NULL DEFAULT 0,
    price_at_signup DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_subscriptions_business (business_id),
    KEY idx_subscriptions_plan (plan_id),
    KEY idx_subscriptions_status_expiry (status, expires_at),
    CONSTRAINT fk_subscriptions_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    plan_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'INR',
    payment_date DATE NOT NULL,
    method VARCHAR(80) NOT NULL,
    reference VARCHAR(190) NULL,
    gateway_provider VARCHAR(80) NULL,
    gateway_payment_id VARCHAR(190) NULL,
    period_start DATE NULL,
    period_end DATE NULL,
    status ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'paid',
    notes TEXT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_payments_business (business_id),
    KEY idx_payments_subscription (subscription_id),
    KEY idx_payments_status_date (status, payment_date),
    KEY idx_payments_gateway (gateway_provider, gateway_payment_id),
    CONSTRAINT fk_payments_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_subscription FOREIGN KEY (subscription_id) REFERENCES business_subscriptions(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    visible_on_website TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_categories_business_slug (business_id, slug),
    KEY idx_categories_business_active (business_id, is_active),
    KEY idx_categories_public (business_id, is_active, visible_on_website),
    KEY idx_categories_parent (parent_id),
    CONSTRAINT fk_categories_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE listings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    type ENUM('product','service','package','booking','custom') NOT NULL DEFAULT 'product',
    title VARCHAR(190) NOT NULL,
    slug VARCHAR(210) NOT NULL,
    short_description VARCHAR(500) NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NULL,
    price_label VARCHAR(120) NULL,
    compare_at_price DECIMAL(12,2) NULL,
    stock_quantity INT NULL,
    manage_stock TINYINT(1) NOT NULL DEFAULT 0,
    specifications JSON NULL,
    image_path VARCHAR(255) NULL,
    gallery JSON NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    visible_on_website TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_listings_business_slug (business_id, slug),
    KEY idx_listings_business_status (business_id, status),
    KEY idx_listings_public (business_id, status, visible_on_website, is_featured),
    KEY idx_listings_business_type (business_id, type),
    KEY idx_listings_category (category_id),
    CONSTRAINT fk_listings_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_listings_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    discount_type ENUM('percentage','fixed','custom') NOT NULL DEFAULT 'custom',
    discount_value DECIMAL(12,2) NULL,
    start_at DATE NULL,
    end_at DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    visible_on_website TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_offers_business_active (business_id, is_active),
    KEY idx_offers_public (business_id, is_active, visible_on_website),
    KEY idx_offers_listing (listing_id),
    CONSTRAINT fk_offers_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_offers_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_customers_business (business_id),
    KEY idx_customers_phone (business_id, phone),
    KEY idx_customers_email (business_id, email),
    CONSTRAINT fk_customers_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    customer_account_id BIGINT UNSIGNED NULL,
    listing_id BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NULL,
    message TEXT NOT NULL,
    requested_item VARCHAR(190) NULL,
    status ENUM('new','contacted','in_progress','converted','closed') NOT NULL DEFAULT 'new',
    internal_notes TEXT NULL,
    source ENUM('public_portal','admin','phone','whatsapp','other') NOT NULL DEFAULT 'public_portal',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_enquiries_business_status (business_id, status),
    KEY idx_enquiries_customer (customer_id),
    KEY idx_enquiries_account (customer_account_id),
    KEY idx_enquiries_listing (listing_id),
    CONSTRAINT fk_enquiries_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_enquiries_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_enquiries_account FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_enquiries_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    customer_account_id BIGINT UNSIGNED NULL,
    listing_id BIGINT UNSIGNED NULL,
    order_number VARCHAR(60) NOT NULL,
    request_type ENUM('product_order','service_request','booking_request','package_enquiry','custom_request') NOT NULL DEFAULT 'custom_request',
    customer_name VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NULL,
    details TEXT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(12,2) NULL,
    status ENUM('new','confirmed','in_progress','completed','cancelled','closed') NOT NULL DEFAULT 'new',
    internal_notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_orders_business_number (business_id, order_number),
    KEY idx_orders_business_status (business_id, status),
    KEY idx_orders_customer (customer_id),
    KEY idx_orders_account (customer_account_id),
    KEY idx_orders_listing (listing_id),
    CONSTRAINT fk_orders_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_orders_account FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_orders_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiry_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enquiry_id BIGINT UNSIGNED NOT NULL,
    business_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    note TEXT NULL,
    changed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    KEY idx_enquiry_history_enquiry (enquiry_id),
    KEY idx_enquiry_history_business (business_id),
    CONSTRAINT fk_enquiry_history_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_enquiry_history_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_enquiry_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    business_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    note TEXT NULL,
    changed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    KEY idx_order_history_order (order_id),
    KEY idx_order_history_business (business_id),
    CONSTRAINT fk_order_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_history_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    customer_account_id BIGINT UNSIGNED NULL,
    audience ENUM('super_admin','business','customer') NOT NULL,
    type VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    related_type VARCHAR(80) NULL,
    related_id BIGINT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    read_at DATETIME NULL,
    KEY idx_notifications_audience (audience, is_read),
    KEY idx_notifications_business (business_id, audience, is_read),
    KEY idx_notifications_user (user_id),
    KEY idx_notifications_customer (customer_account_id, audience, is_read),
    CONSTRAINT fk_notifications_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_customer FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_role VARCHAR(80) NULL,
    business_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id BIGINT UNSIGNED NULL,
    properties JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_activity_business (business_id, created_at),
    KEY idx_activity_actor (actor_user_id),
    KEY idx_activity_action (action),
    CONSTRAINT fk_activity_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_activity_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default platform feature/module registry. These are not plan definitions; plans choose from this registry dynamically.
INSERT INTO platform_features (identifier, name, description, category, icon, is_active, available_for_plans, sort_order, created_at, updated_at) VALUES
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

