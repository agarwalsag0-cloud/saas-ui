-- ============================================================================
-- MULTI-BUSINESS SUBSCRIPTION PLATFORM — SINGLE COMPLETE DATABASE IMPORT
-- ============================================================================
-- Import THIS one file in phpMyAdmin (into an empty database) and you get the
-- full schema, the platform feature registry and demo content for every
-- workflow: three login doors, business approval & review, publish/unpublish,
-- subscription gating and the customer portal.
--
--   phpMyAdmin → New database "multi_business_platform" (utf8mb4_unicode_ci)
--             → Import → this file → Go
--
-- Demo passwords are:  password
-- No Super Admin is pre-created: open the app URL and /setup asks you to
-- create it (secure hash, self-chosen credentials, setup locks afterwards).
--
-- database/install.sql (schema+registry only) and the older update_*.sql
-- patch files remain in the repo for reference/upgrades of existing installs.
-- ============================================================================

-- Multi-Business Subscription Platform schema
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


-- ============================================================================
-- DEMO CONTENT (part of the single import — safe to delete for production)
-- ============================================================================
-- This seed gives you a fully populated platform to test end-to-end:
--   * NO Super Admin row is inserted on purpose. On first browser visit the
--     app redirects to /setup, where YOU create the admin (secure, self-chosen
--     password — never hard-coded).
--   * All demo business-owner / staff / customer passwords are:  password
--
--   Tenant states included for the workflow tests:
--     1 Lucky Tour & Travel    — active + subscription + website PUBLISHED (live public site)
--     2 ElectroHub             — active + approved, website configured but UNPUBLISHED (test Publish)
--     3 CalmCare Salon         — PENDING review (owner can prepare content, submit for review; not public)
--     4 SpiceLeaf Restaurant   — approved but subscription EXPIRED (portal writes restricted)
--     5 NorthStar Consulting   — SUSPENDED (public site offline, portal locked)
--     6 Dune Books             — REJECTED (owner login blocked; never listed publicly)
--
--   Customer accounts (platform-level, self-service, no approval needed):
--     amit@example.test / password    — has a linked enquiry with Lucky Tour
--     neha@example.test / password    — has a linked order request with ElectroHub
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE activity_logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE order_status_history;
TRUNCATE TABLE enquiry_status_history;
TRUNCATE TABLE orders;
TRUNCATE TABLE enquiries;
TRUNCATE TABLE customers;
TRUNCATE TABLE customer_accounts;
TRUNCATE TABLE offers;
TRUNCATE TABLE listings;
TRUNCATE TABLE categories;
TRUNCATE TABLE payments;
TRUNCATE TABLE business_subscriptions;
TRUNCATE TABLE subscription_plan_features;
TRUNCATE TABLE subscription_plans;
TRUNCATE TABLE business_settings;
TRUNCATE TABLE users;
TRUNCATE TABLE businesses;

-- Subscription plans (features are assigned through the registry below)
INSERT INTO subscription_plans (id, name, description, billing_cycle, price, monthly_price, yearly_price, currency, features, is_active, sort_order, created_at, updated_at) VALUES
(1, 'Basic Monthly', 'Catalog + enquiries only. No public website.', 'monthly', 1499.00, 1499.00, 14990.00, 'INR', '[]', 1, 1, NOW(), NOW()),
(2, 'Growth Monthly', 'Public website, customization, offers, orders, notifications.', 'monthly', 2999.00, 2999.00, 29990.00, 'INR', '[]', 1, 2, NOW(), NOW()),
(3, 'Annual Pro', 'Everything included, yearly pricing.', 'yearly', 24999.00, 2499.00, 24999.00, 'INR', '[]', 1, 3, NOW(), NOW());

INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT 1, id, 1,
       CASE identifier
           WHEN 'product_management' THEN '{"max_items":25}'
           WHEN 'service_management' THEN '{"max_items":25}'
           ELSE NULL
       END,
       NOW(), NOW()
FROM platform_features
WHERE identifier IN ('business_profile','categories','product_management','service_management','enquiries','customer_management','dashboard_analytics','notifications');

INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT 2, id, 1,
       CASE identifier
           WHEN 'product_management' THEN '{"max_items":150}'
           WHEN 'service_management' THEN '{"max_items":150}'
           ELSE NULL
       END,
       NOW(), NOW()
FROM platform_features
WHERE identifier IN ('business_profile','public_website','website_customization','custom_branding','basic_seo','categories','product_management','service_management','featured_listings','offers','enquiries','customer_management','lead_management','orders','booking_requests','service_requests','dashboard_analytics','notifications','activity_tracking');

INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT 3, id, 1, NULL, NOW(), NOW()
FROM platform_features
WHERE identifier IN ('business_profile','public_website','website_customization','custom_branding','basic_seo','categories','product_management','service_management','featured_listings','inventory','offers','enquiries','customer_management','lead_management','whatsapp_button','orders','booking_requests','service_requests','dashboard_analytics','advanced_analytics','notifications','customer_history','activity_tracking','custom_domain','email_automation','sms_automation','ai_tools');

INSERT INTO businesses (id, name, slug, category, description, phone, email, address, city, state, country, website, social_links, timings, logo_path, cover_path, status, approved_at, submitted_for_review_at, review_note, created_at, updated_at) VALUES
(1, 'Lucky Tour & Travel', 'lucky-tour-travel', 'Travel Agency', 'Tours, pilgrimage packages, cab services, hotel assistance, visa support and custom travel planning.', '+91 98765 43210', 'hello@luckytour.test', 'Fatehabad Road, Near Taj East Gate', 'Agra', 'Uttar Pradesh', 'India', 'https://example.com/lucky', '{"whatsapp":"https://wa.me/919876543210","instagram":"https://instagram.com/example"}', 'Mon-Sat: 10:00 AM - 8:00 PM\nSunday: 11:00 AM - 4:00 PM', NULL, NULL, 'active', NOW(), NULL, NULL, DATE_SUB(NOW(), INTERVAL 40 DAY), NOW()),
(2, 'ElectroHub Electronics', 'electrohub-electronics', 'Electronics Retail', 'Smartphones, laptops, accessories, installation services and seasonal offers.', '+91 99887 76655', 'sales@electrohub.test', 'MG Road Market', 'Agra', 'Uttar Pradesh', 'India', 'https://example.com/electrohub', '{"whatsapp":"https://wa.me/919988776655"}', 'Daily: 10:30 AM - 9:00 PM', NULL, NULL, 'approved', NOW(), NULL, NULL, DATE_SUB(NOW(), INTERVAL 25 DAY), NOW()),
(3, 'CalmCare Salon', 'calmcare-salon', 'Salon & Wellness', 'Demo PENDING tenant: sign in as its owner to prepare content, then press Submit for review.', '+91 90000 11111', 'info@calmcare.test', 'Civil Lines', 'Agra', 'Uttar Pradesh', 'India', NULL, NULL, 'Mon-Sun: 10:00 AM - 7:00 PM', NULL, NULL, 'pending', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(4, 'SpiceLeaf Restaurant', 'spiceleaf-restaurant', 'Restaurant', 'Demo approved tenant with an expired subscription — portal writes are restricted until renewal.', '+91 91111 22222', 'orders@spiceleaf.test', 'Sanjay Place', 'Agra', 'Uttar Pradesh', 'India', NULL, NULL, 'Daily: 12:00 PM - 11:00 PM', NULL, NULL, 'active', NOW(), NULL, NULL, DATE_SUB(NOW(), INTERVAL 300 DAY), NOW()),
(5, 'NorthStar Consulting', 'northstar-consulting', 'Consulting', 'Demo SUSPENDED tenant — public site offline and portal locked.', '+91 92222 33333', 'hello@northstar.test', 'Remote', 'Delhi', 'Delhi', 'India', NULL, NULL, 'By appointment', NULL, NULL, 'suspended', NOW(), NULL, NULL, DATE_SUB(NOW(), INTERVAL 180 DAY), NOW()),
(6, 'Dune Books & Cafe', 'dune-books', 'Bookstore', 'Demo REJECTED tenant — its owner cannot sign in to the business portal and it never appears in the directory.', '+91 93333 44444', 'hello@dunebooks.test', 'Hauz Khas', 'Delhi', 'Delhi', 'India', NULL, NULL, 'Daily: 9:00 AM - 9:00 PM', NULL, NULL, 'rejected', NULL, DATE_SUB(NOW(), INTERVAL 12 DAY), 'Business documents were incomplete. Resubmit with updated proof of address.', DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));

UPDATE businesses SET tagline = 'Travel plans, pilgrimages and cab bookings made simple' WHERE id = 1;
UPDATE businesses SET tagline = 'Latest gadgets, honest pricing and reliable support' WHERE id = 2;
UPDATE businesses SET tagline = 'Salon and wellness appointments for modern customers' WHERE id = 3;
UPDATE businesses SET tagline = 'Fresh meals and family combos' WHERE id = 4;
UPDATE businesses SET tagline = 'Practical business consulting for growing teams' WHERE id = 5;
UPDATE businesses SET tagline = 'Rare paperbacks, quiet corners and good coffee' WHERE id = 6;

-- Website configuration rows. Note the deliberate state mix:
--   business 1: published + directory-visible + indexable  → live public site
--   business 2: configured but website_published = 0       → test the Publish action
--   others: configured defaults, unpublished
INSERT INTO business_settings (business_id, website_enabled, website_published, website_published_at, allow_indexing, show_in_directory, theme_preset, layout_style, background_style, button_style, text_style, primary_color, secondary_color, theme_color, accent_color, section_visibility, seo_title, seo_description, portal_settings, created_at, updated_at) VALUES
(1, 1, 1, NOW(), 1, 1, 'modern', 'showcase', 'gradient', 'pill', 'modern', '#2563eb', '#0f172a', '#2563eb', '#f97316', NULL, 'Lucky Tour & Travel - Tours, Visa and Cab Services', 'Explore tour packages, visa documentation and cab booking services from Lucky Tour & Travel in Agra.', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
(2, 1, 0, NULL, 1, 1, 'bold', 'classic', 'soft', 'rounded', 'modern', '#7c3aed', '#111827', '#7c3aed', '#06b6d4', NULL, 'ElectroHub Electronics - Mobiles, Laptops and Accessories', 'Shop demo electronics listings, offers and service requests from ElectroHub Electronics.', NULL, DATE_SUB(NOW(), INTERVAL 20 DAY), NOW()),
(3, 1, 0, NULL, 1, 1, 'elegant', 'classic', 'light', 'pill', 'serif', '#db2777', '#3f1d38', '#db2777', '#f59e0b', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(4, 1, 0, NULL, 1, 1, 'minimal', 'compact', 'light', 'rounded', 'system', '#16a34a', '#052e16', '#16a34a', '#ef4444', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 100 DAY), NOW()),
(5, 1, 0, NULL, 1, 1, 'professional', 'classic', 'soft', 'rounded', 'system', '#334155', '#0f172a', '#334155', '#14b8a6', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
(6, 1, 0, NULL, 1, 1, 'modern', 'classic', 'light', 'rounded', 'system', '#0f766e', '#042f2e', '#0f766e', '#f59e0b', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 14 DAY), NOW());

-- Staff logins (bcrypt hash of the word: password). No Super Admin row exists —
-- the /setup screen creates it with YOUR email and password on first visit.
INSERT INTO users (id, business_id, name, email, phone, password_hash, role, status, last_login_at, created_at, updated_at) VALUES
(2, 1, 'Lucky Owner', 'owner@luckytour.test', '+91 98765 43210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, DATE_SUB(NOW(), INTERVAL 40 DAY), NOW()),
(3, 2, 'ElectroHub Owner', 'owner@electrohub.test', '+91 99887 76655', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, DATE_SUB(NOW(), INTERVAL 25 DAY), NOW()),
(4, 3, 'CalmCare Owner', 'owner@calmcare.test', '+91 90000 11111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(5, 4, 'SpiceLeaf Owner', 'owner@spiceleaf.test', '+91 91111 22222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, DATE_SUB(NOW(), INTERVAL 300 DAY), NOW()),
(6, 5, 'NorthStar Owner', 'owner@northstar.test', '+91 92222 33333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, DATE_SUB(NOW(), INTERVAL 180 DAY), NOW()),
(7, 1, 'Lucky Staff', 'staff@luckytour.test', '+91 98765 43211', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_staff', 'active', NULL, DATE_SUB(NOW(), INTERVAL 35 DAY), NOW()),
(8, 6, 'Dune Owner', 'owner@dunebooks.test', '+91 93333 44444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, DATE_SUB(NOW(), INTERVAL 14 DAY), NOW());

INSERT INTO business_subscriptions (id, business_id, plan_id, status, starts_at, expires_at, grace_ends_at, renewal_status, auto_renew, price_at_signup, created_at, updated_at) VALUES
(1, 1, 2, 'active', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), 'manual', 0, 2999.00, NOW(), NOW()),
(2, 2, 3, 'active', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), DATE_ADD(CURDATE(), INTERVAL 342 DAY), 'manual', 0, 24999.00, NOW(), NOW()),
(3, 3, 1, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL, 'pending', 0, 1499.00, NOW(), NOW()),
(4, 4, 1, 'active', DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'pending', 0, 1499.00, NOW(), NOW()),
(5, 5, 2, 'suspended', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), NULL, 'manual', 0, 2999.00, NOW(), NOW()),
(6, 6, 1, 'active', DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 16 DAY), NULL, 'manual', 0, 1499.00, NOW(), NOW());

INSERT INTO payments (id, business_id, subscription_id, plan_id, amount, currency, payment_date, method, reference, gateway_provider, gateway_payment_id, period_start, period_end, status, notes, recorded_by, metadata, created_at, updated_at) VALUES
(1, 1, 1, 2, 2999.00, 'INR', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'UPI', 'DEMO-UPI-1001', NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'paid', 'Demo monthly renewal', NULL, NULL, NOW(), NOW()),
(2, 2, 2, 3, 24999.00, 'INR', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Bank Transfer', 'DEMO-BANK-2001', NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), 'paid', 'Demo annual payment', NULL, NULL, NOW(), NOW()),
(3, 3, 3, 1, 1499.00, 'INR', CURDATE(), 'Cash', 'DEMO-PENDING-3001', NULL, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'pending', 'Awaiting first-approval demo payment', NULL, NULL, NOW(), NOW()),
(4, 4, 4, 1, 1499.00, 'INR', DATE_SUB(CURDATE(), INTERVAL 80 DAY), 'Cash', 'DEMO-OLD-4001', NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'paid', 'Expired demo', NULL, NULL, NOW(), NOW());

INSERT INTO categories (id, business_id, parent_id, name, slug, description, is_active, sort_order, created_at, updated_at) VALUES
(1, 1, NULL, 'Tour Packages', 'tour-packages', 'Domestic and international travel packages.', 1, 1, NOW(), NOW()),
(2, 1, NULL, 'Visa Services', 'visa-services', 'Visa assistance and documentation.', 1, 2, NOW(), NOW()),
(3, 1, NULL, 'Cab Services', 'cab-services', 'Local and outstation cab bookings.', 1, 3, NOW(), NOW()),
(4, 2, NULL, 'Mobile Phones', 'mobile-phones', 'Latest smartphones and offers.', 1, 1, NOW(), NOW()),
(5, 2, NULL, 'Laptops', 'laptops', 'Business, student and gaming laptops.', 1, 2, NOW(), NOW()),
(6, 2, NULL, 'Accessories', 'accessories', 'Headphones, chargers and more.', 1, 3, NOW(), NOW()),
(7, 4, NULL, 'Meal Combos', 'meal-combos', 'Restaurant combos.', 1, 1, NOW(), NOW());

INSERT INTO listings (id, business_id, category_id, type, title, slug, short_description, description, price, price_label, compare_at_price, stock_quantity, manage_stock, specifications, image_path, gallery, is_featured, status, created_at, updated_at) VALUES
(1, 1, 1, 'package', 'Goa Holiday Package', 'goa-holiday-package', '4 nights and 5 days beach holiday package.', 'Includes hotel assistance, sightseeing guidance and customizable itinerary.', 18999.00, NULL, 21999.00, NULL, 0, '{"Duration":"5 days","Best for":"Family and couples","Customization":"Available"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(2, 1, 1, 'package', 'Char Dham Yatra Assistance', 'char-dham-yatra-assistance', 'Pilgrimage planning support for Char Dham Yatra.', 'Transport, hotel coordination and itinerary planning for pilgrimage travelers.', NULL, 'Custom quote', NULL, NULL, 0, '{"Service":"Pilgrimage package","Customization":"Available"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(3, 1, 2, 'service', 'Tourist Visa Documentation', 'tourist-visa-documentation', 'Documentation checklist and appointment guidance.', 'Visa documentation support for selected countries. Final visa approval depends on embassy rules.', 2500.00, NULL, NULL, NULL, 0, '{"Mode":"Consultation","Turnaround":"Depends on country"}', NULL, NULL, 0, 'active', NOW(), NOW()),
(4, 1, 3, 'booking', 'Agra Airport Cab', 'agra-airport-cab', 'Local airport pickup and drop cab service.', 'Sedan and SUV options for airport transfers and local sightseeing.', 1200.00, 'Starts from ₹1200', NULL, NULL, 0, '{"Vehicle":"Sedan/SUV","Availability":"On booking"}', NULL, NULL, 0, 'active', NOW(), NOW()),
(5, 2, 4, 'product', 'DemoPhone X Pro', 'demophone-x-pro', 'Flagship smartphone with 256GB storage.', 'A demo phone listing with stock tracking and specifications.', 69999.00, NULL, 74999.00, 12, 1, '{"Storage":"256GB","Warranty":"1 year","Color":"Black"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(6, 2, 5, 'product', 'WorkMate Laptop 14', 'workmate-laptop-14', 'Lightweight laptop for business and students.', '14 inch laptop with SSD storage and long battery life.', 54999.00, NULL, 59999.00, 6, 1, '{"RAM":"16GB","SSD":"512GB","Warranty":"1 year"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(7, 2, 6, 'product', 'Noise-Free Headphones', 'noise-free-headphones', 'Wireless headphones with active noise cancellation.', 'Comfortable over-ear headphones with long battery life.', 4999.00, NULL, 6999.00, 25, 1, '{"Battery":"40 hours","Warranty":"6 months"}', NULL, NULL, 0, 'active', NOW(), NOW()),
(8, 4, 7, 'product', 'Family Thali Combo', 'family-thali-combo', 'A restaurant listing for expired portal testing.', 'This listing should not be visible publicly while subscription is expired.', 799.00, NULL, NULL, 10, 1, NULL, NULL, NULL, 1, 'active', NOW(), NOW()),
(9, 3, NULL, 'service', 'Bridal Makeup Package', 'bridal-makeup-package', 'Demo listing created while the tenant is still pending.', 'Shows that a PENDING business can fully prepare catalog content — it just is not public yet.', 15000.00, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, 'active', NOW(), NOW());

INSERT INTO offers (id, business_id, listing_id, title, description, discount_type, discount_value, start_at, end_at, is_active, created_at, updated_at) VALUES
(1, 1, 1, 'Early bird Goa discount', 'Book this month and save on Goa travel planning.', 'fixed', 2000.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 1, NOW(), NOW()),
(2, 2, 5, 'Smartphone launch offer', 'Limited stock launch discount on DemoPhone X Pro.', 'fixed', 5000.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 1, NOW(), NOW());

-- Platform-level customer accounts (customer portal). Same bcrypt hash: 'password'
INSERT INTO customer_accounts (id, name, email, phone, password_hash, google_sub, avatar_path, status, last_login_at, created_at, updated_at) VALUES
(1, 'Amit Sharma', 'amit@example.test', '+91 80000 11111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'active', NULL, DATE_SUB(NOW(), INTERVAL 20 DAY), NOW()),
(2, 'Neha Singh', 'neha@example.test', '+91 80000 44444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'active', NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), NOW());

-- Per-business CRM customers (separate from platform customer accounts by design)
INSERT INTO customers (id, business_id, name, phone, email, address, metadata, created_at, updated_at) VALUES
(1, 1, 'Amit Sharma', '+91 80000 11111', 'amit@example.test', NULL, NULL, NOW(), NOW()),
(2, 1, 'Priya Verma', '+91 80000 22222', 'priya@example.test', NULL, NULL, NOW(), NOW()),
(3, 2, 'Rahul Gupta', '+91 80000 33333', 'rahul@example.test', NULL, NULL, NOW(), NOW()),
(4, 2, 'Neha Singh', '+91 80000 44444', 'neha@example.test', NULL, NULL, NOW(), NOW());

-- enquiries.customer_account_id links a public enquiry to a platform customer,
-- so it appears inside that customer's portal (My enquiries).
INSERT INTO enquiries (id, business_id, customer_id, customer_account_id, listing_id, name, phone, email, message, requested_item, status, internal_notes, source, created_at, updated_at) VALUES
(1, 1, 1, 1, 1, 'Amit Sharma', '+91 80000 11111', 'amit@example.test', 'Please share available dates for Goa package.', 'Goa Holiday Package', 'new', NULL, 'public_portal', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(2, 1, 2, NULL, 2, 'Priya Verma', '+91 80000 22222', 'priya@example.test', 'Need yatra package for parents.', 'Char Dham Yatra Assistance', 'contacted', 'Called once, waiting for travel month.', 'public_portal', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(3, 2, 3, NULL, 5, 'Rahul Gupta', '+91 80000 33333', 'rahul@example.test', 'Is DemoPhone X Pro available in blue?', 'DemoPhone X Pro', 'in_progress', NULL, 'public_portal', DATE_SUB(NOW(), INTERVAL 5 HOUR), NOW());

INSERT INTO enquiry_status_history (enquiry_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES
(1, 1, NULL, 'new', 'Seed demo enquiry', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, NULL, 'new', 'Seed demo enquiry', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 1, 'new', 'contacted', 'Called customer', 2, NOW()),
(3, 2, NULL, 'new', 'Seed demo enquiry', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 2, 'new', 'in_progress', 'Checking stock', 3, NOW());

INSERT INTO orders (id, business_id, customer_id, customer_account_id, listing_id, order_number, request_type, customer_name, phone, email, details, quantity, total_amount, status, internal_notes, created_at, updated_at) VALUES
(1, 1, 1, 1, 4, 'REQ-DEMO-LT-001', 'booking_request', 'Amit Sharma', '+91 80000 11111', 'amit@example.test', 'Need airport pickup tomorrow morning.', 1, 1200.00, 'confirmed', 'Driver to be assigned.', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(2, 2, 4, 2, 7, 'REQ-DEMO-EH-001', 'product_order', 'Neha Singh', '+91 80000 44444', 'neha@example.test', 'Please reserve one black headphone.', 1, 4999.00, 'new', NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR), NOW());

INSERT INTO order_status_history (order_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES
(1, 1, NULL, 'new', 'Seed demo order', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 1, 'new', 'confirmed', 'Confirmed over phone', 2, NOW()),
(2, 2, NULL, 'new', 'Seed demo order', NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- One notification for a customer so the bell in the customer portal has content
INSERT INTO notifications (business_id, user_id, customer_account_id, audience, type, title, body, related_type, related_id, is_read, created_at, read_at) VALUES
(NULL, NULL, 1, 'customer', 'enquiry_update', 'Your enquiry is being reviewed', 'Lucky Tour & Travel is checking Goa package dates for you.', 'enquiry', 1, 0, DATE_SUB(NOW(), INTERVAL 6 HOUR), NULL),
(1, NULL, NULL, 'business', 'new_enquiry', 'New enquiry received', 'Amit asked about the Goa Holiday Package.', 'enquiry', 1, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(1, NULL, NULL, 'business', 'new_order', 'Cab booking request', 'Amit requested an airport cab booking.', 'order', 1, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(2, NULL, NULL, 'business', 'new_order', 'Product order request', 'Neha requested Noise-Free Headphones.', 'order', 2, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR), NULL),
(4, NULL, NULL, 'business', 'subscription_expired', 'Subscription expired', 'Your subscription is past expiry and grace period.', 'subscription', 4, 0, NOW(), NULL);

INSERT INTO activity_logs (actor_user_id, actor_role, business_id, action, subject_type, subject_id, properties, ip_address, user_agent, created_at) VALUES
(NULL, 'system', 1, 'business_created', 'business', 1, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 40 DAY)),
(NULL, 'system', 2, 'business_created', 'business', 2, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(NULL, 'system', 1, 'payment_recorded', 'payment', 1, '{"status":"paid"}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 'business_owner', 1, 'listing_created', 'listing', 1, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, 'business_owner', 2, 'listing_created', 'listing', 5, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(NULL, 'system', 4, 'subscription_expired', 'subscription', 4, '{"seed":true}', NULL, NULL, NOW());

SET FOREIGN_KEY_CHECKS = 1;
