-- Manual database upgrade for the campaign builder and incomplete-order capture.
-- Target: MySQL / MariaDB (phpMyAdmin compatible)
-- Safe to run more than once: existing tables, columns, and indexes are preserved.
-- IMPORTANT: Select the application's database in phpMyAdmin before importing this file.
-- Take a database backup before any production schema change.

SET @schema_name = DATABASE();

-- -----------------------------------------------------------------------------
-- Campaign custom builder and publication columns
-- -----------------------------------------------------------------------------

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_html_draft'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_html_draft` LONGTEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_css_draft'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_css_draft` LONGTEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_js_draft'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_js_draft` LONGTEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_html'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_html` LONGTEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_css'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_css` LONGTEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_js'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_js` LONGTEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'custom_page_published_at'),
    'ALTER TABLE `campaigns` ADD COLUMN `custom_page_published_at` TIMESTAMP NULL DEFAULT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'is_published'),
    'ALTER TABLE `campaigns` ADD COLUMN `is_published` TINYINT(1) NOT NULL DEFAULT 1',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'campaigns')
    AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'published_at'),
    'ALTER TABLE `campaigns` ADD COLUMN `published_at` TIMESTAMP NULL DEFAULT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'is_published')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'is_published'),
    'ALTER TABLE `campaigns` ADD INDEX `idx_campaigns_is_published` (`is_published`)',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Keep all previously active campaigns publicly available after the upgrade.
SET @ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'published_at')
    AND EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'campaigns' AND column_name = 'is_published'),
    'UPDATE `campaigns` SET `published_at` = CURRENT_TIMESTAMP WHERE `is_published` = 1 AND `published_at` IS NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Incomplete-order table
-- Some legacy installations created this table outside Laravel migrations.
-- This creates it only when absent and never deletes existing leads.
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `incomplete_orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NULL,
    `phone` VARCHAR(55) NULL,
    `address` TEXT NULL,
    `items` LONGTEXT NULL,
    `product_image` TEXT NULL,
    `product_link` TEXT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `recovery_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `recovery_note` TEXT NULL,
    `contacted_at` TIMESTAMP NULL DEFAULT NULL,
    `recovered_order_id` BIGINT UNSIGNED NULL,
    `campaign_id` BIGINT UNSIGNED NULL,
    `source` VARCHAR(100) NULL,
    `device_type` VARCHAR(30) NULL,
    `device_name` VARCHAR(120) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `checkout_started_at` TIMESTAMP NULL DEFAULT NULL,
    `last_activity_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_incomplete_orders_phone` (`phone`),
    INDEX `idx_incomplete_orders_recovery_status` (`recovery_status`),
    INDEX `idx_incomplete_orders_campaign_id` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recovery fields are included here because an older recovery migration may have
-- been skipped when incomplete_orders did not yet exist.
SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'recovery_status'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `recovery_status` VARCHAR(20) NOT NULL DEFAULT ''pending''',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'recovery_note'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `recovery_note` TEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'contacted_at'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `contacted_at` TIMESTAMP NULL DEFAULT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'recovered_order_id'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `recovered_order_id` BIGINT UNSIGNED NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- New checkout-capture metadata.
SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'campaign_id'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `campaign_id` BIGINT UNSIGNED NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'source'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `source` VARCHAR(100) NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'device_type'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `device_type` VARCHAR(30) NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'device_name'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `device_name` VARCHAR(120) NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'ip_address'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `ip_address` VARCHAR(45) NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'user_agent'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `user_agent` TEXT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'checkout_started_at'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `checkout_started_at` TIMESTAMP NULL DEFAULT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'last_activity_at'),
    'ALTER TABLE `incomplete_orders` ADD COLUMN `last_activity_at` TIMESTAMP NULL DEFAULT NULL',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add indexes only if no existing index already starts with the target column.
SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'phone'),
    'ALTER TABLE `incomplete_orders` ADD INDEX `idx_incomplete_orders_phone` (`phone`)',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'recovery_status'),
    'ALTER TABLE `incomplete_orders` ADD INDEX `idx_incomplete_orders_recovery_status` (`recovery_status`)',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'incomplete_orders' AND column_name = 'campaign_id'),
    'ALTER TABLE `incomplete_orders` ADD INDEX `idx_incomplete_orders_campaign_id` (`campaign_id`)',
    'SET @migration_noop = 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Verification result: phpMyAdmin should display the expected columns below.
-- -----------------------------------------------------------------------------
SELECT
    table_name,
    column_name,
    column_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = @schema_name
  AND (
      (table_name = 'campaigns' AND column_name IN (
          'custom_html_draft', 'custom_css_draft', 'custom_js_draft',
          'custom_html', 'custom_css', 'custom_js',
          'custom_page_published_at', 'is_published', 'published_at'
      ))
      OR
      (table_name = 'incomplete_orders' AND column_name IN (
          'recovery_status', 'recovery_note', 'contacted_at', 'recovered_order_id',
          'campaign_id', 'source', 'device_type', 'device_name', 'ip_address',
          'user_agent', 'checkout_started_at', 'last_activity_at'
      ))
  )
ORDER BY table_name, ordinal_position;
