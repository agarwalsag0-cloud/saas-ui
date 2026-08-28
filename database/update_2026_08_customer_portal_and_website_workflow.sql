-- ============================================================
-- Customer Portal, Website Publishing and Review Workflow update
-- Import this ONCE in phpMyAdmin if you already imported an older
-- install.sql and are updating the PHP files to the integrated version.
-- Fresh installs only need database/install.sql.
-- Safe to run more than once (all changes are checked first).
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS mbsp_add_column_if_missing $$
CREATE PROCEDURE mbsp_add_column_if_missing(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
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
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
    ) THEN
        SET @mbsp_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX ', p_index, ' ', p_definition);
        PREPARE mbsp_stmt FROM @mbsp_sql;
        EXECUTE mbsp_stmt;
        DEALLOCATE PREPARE mbsp_stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS mbsp_add_fk_if_missing $$
CREATE PROCEDURE mbsp_add_fk_if_missing(IN p_table VARCHAR(64), IN p_constraint VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND CONSTRAINT_NAME = p_constraint
    ) THEN
        SET @mbsp_sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT ', p_constraint, ' ', p_definition);
        PREPARE mbsp_stmt FROM @mbsp_sql;
        EXECUTE mbsp_stmt;
        DEALLOCATE PREPARE mbsp_stmt;
    END IF;
END $$

DELIMITER ;

-- 1) Platform customer accounts (independent from tenant CRM `customers`).
CREATE TABLE IF NOT EXISTS customer_accounts (
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

-- 2) Link enquiries/orders/notifications to platform customer accounts.
CALL mbsp_add_column_if_missing('enquiries', 'customer_account_id', '`customer_account_id` BIGINT UNSIGNED NULL AFTER `customer_id`');
CALL mbsp_add_index_if_missing('enquiries', 'idx_enquiries_account', '`idx_enquiries_account` (`customer_account_id`)');
CALL mbsp_add_fk_if_missing('enquiries', 'fk_enquiries_account', 'FOREIGN KEY (`customer_account_id`) REFERENCES `customer_accounts`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');

CALL mbsp_add_column_if_missing('orders', 'customer_account_id', '`customer_account_id` BIGINT UNSIGNED NULL AFTER `customer_id`');
CALL mbsp_add_index_if_missing('orders', 'idx_orders_account', '`idx_orders_account` (`customer_account_id`)');
CALL mbsp_add_fk_if_missing('orders', 'fk_orders_account', 'FOREIGN KEY (`customer_account_id`) REFERENCES `customer_accounts`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');

CALL mbsp_add_column_if_missing('notifications', 'customer_account_id', '`customer_account_id` BIGINT UNSIGNED NULL AFTER `user_id`');
CALL mbsp_add_index_if_missing('notifications', 'idx_notifications_customer', '`idx_notifications_customer` (`customer_account_id`, `audience`, `is_read`)');
CALL mbsp_add_fk_if_missing('notifications', 'fk_notifications_customer', 'FOREIGN KEY (`customer_account_id`) REFERENCES `customer_accounts`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
ALTER TABLE notifications MODIFY COLUMN audience ENUM('super_admin','business','customer') NOT NULL;

-- 3) Website state separation: configured vs published vs directory-visible.
CALL mbsp_add_column_if_missing('business_settings', 'website_published', '`website_published` TINYINT(1) NOT NULL DEFAULT 0 AFTER `website_enabled`');
CALL mbsp_add_column_if_missing('business_settings', 'website_published_at', '`website_published_at` DATETIME NULL AFTER `website_published`');
CALL mbsp_add_column_if_missing('business_settings', 'allow_indexing', '`allow_indexing` TINYINT(1) NOT NULL DEFAULT 1 AFTER `website_published_at`');
CALL mbsp_add_index_if_missing('business_settings', 'idx_business_settings_website', '`idx_business_settings_website` (`website_enabled`, `show_in_directory`, `website_published`)');

-- Existing tenants that already had a live public website keep it live after
-- the update (no surprise unpublish); brand-new setups must explicitly Publish.
UPDATE business_settings
SET website_published = website_enabled,
    website_published_at = CASE WHEN website_enabled = 1 THEN COALESCE(website_published_at, NOW()) ELSE NULL END
WHERE website_published = 0;

-- 4) Review workflow states for business approval.
ALTER TABLE businesses
    MODIFY COLUMN status ENUM('pending','under_review','changes_requested','approved','active','inactive','suspended','rejected','archived') NOT NULL DEFAULT 'pending';
CALL mbsp_add_column_if_missing('businesses', 'submitted_for_review_at', '`submitted_for_review_at` DATETIME NULL AFTER `status`');
CALL mbsp_add_column_if_missing('businesses', 'review_note', '`review_note` TEXT NULL AFTER `approved_at`');

DROP PROCEDURE IF EXISTS mbsp_add_column_if_missing;
DROP PROCEDURE IF EXISTS mbsp_add_index_if_missing;
DROP PROCEDURE IF EXISTS mbsp_add_fk_if_missing;

-- Done. Update the PHP files afterwards; do not re-import install.sql over a live database.
