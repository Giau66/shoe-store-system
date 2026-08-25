-- =============================================
-- MIGRATION: Sale Events System
-- Run in: web_shoe database
-- =============================================

USE `web_shoe`;

-- 1. Table: sale_events
CREATE TABLE IF NOT EXISTS `sale_events` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `banner_image` VARCHAR(500) DEFAULT NULL,
  `hero_banner_image` VARCHAR(500) DEFAULT NULL,
  `hero_banner_title` VARCHAR(255) DEFAULT NULL,
  `hero_banner_subtitle` VARCHAR(500) DEFAULT NULL,
  `show_on_menu` TINYINT(1) DEFAULT 1,
  `show_on_homepage_banner` TINYINT(1) DEFAULT 1,
  `color_theme` VARCHAR(20) DEFAULT '#ef4444',
  `icon` VARCHAR(100) DEFAULT 'fa-solid fa-fire',
  `icon_image` VARCHAR(500) DEFAULT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: event_products
CREATE TABLE IF NOT EXISTS `event_products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `sale_price` DECIMAL(12,0) DEFAULT NULL,
  `discount_percent` INT DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_product` (`event_id`, `product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `ep_event_fk` FOREIGN KEY (`event_id`) REFERENCES `sale_events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `ep_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Alter vouchers: add sale_event_id (safe)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='web_shoe' AND TABLE_NAME='vouchers' AND COLUMN_NAME='sale_event_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `vouchers` ADD COLUMN `sale_event_id` INT DEFAULT NULL AFTER `brand_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Sample event data
INSERT IGNORE INTO `sale_events`
  (`name`, `slug`, `description`, `hero_banner_title`, `hero_banner_subtitle`, `color_theme`, `icon`, `start_date`, `end_date`, `status`, `sort_order`)
VALUES
  ('11.11 Sale Siêu Khủng', '11-11-sale',
   'Lễ hội mua sắm 11/11 với hàng ngàn ưu đãi giảm giá cực sốc dành riêng cho tín đồ giày.',
   '🔥 SALE 11.11 — GIÁ SỐC!', 'Giảm đến 50% hàng ngàn sản phẩm giày chính hãng. Chỉ trong 24 giờ!',
   '#f97316', 'fa-solid fa-fire-flame-curved',
   '2026-11-11 00:00:00', '2026-11-11 23:59:59', 1, 1),
  ('12.12 Mega Sale', '12-12-sale',
   'Sự kiện cuối năm 12/12 — Giải phóng kho hàng với hàng nghìn đôi giày giảm giá.',
   '❄️ SALE 12.12 — CUỐI NĂM BỨT PHÁ', 'Giảm đến 40% — Cơ hội cuối năm săn giày hiệu chính hãng!',
   '#3b82f6', 'fa-solid fa-snowflake',
   '2026-12-12 00:00:00', '2026-12-12 23:59:59', 1, 2);
