-- ========================================================
-- CƠ SỞ DỮ LIỆU DỰ ÁN WEB BÁN GIÀY (web_shoe)
-- ========================================================
CREATE DATABASE IF NOT EXISTS `web_shoe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `web_shoe`;

-- 1. BẢNG NGƯỜI DÙNG (users)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL UNIQUE,
  `password` VARCHAR(255) DEFAULT NULL,
  `google_id` VARCHAR(255) DEFAULT NULL UNIQUE,
  `is_email_verified` TINYINT(1) DEFAULT 0,
  `is_phone_verified` TINYINT(1) DEFAULT 0,
  `role` ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
  `avatar` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. BẢNG MÃ OTP (otp_codes)
CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `target` VARCHAR(100) NOT NULL,
  `otp_code` VARCHAR(10) NOT NULL,
  `type` ENUM('phone', 'email') NOT NULL,
  `action` ENUM('login', 'register', 'reset_password') NOT NULL,
  `is_used` TINYINT(1) DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. BẢNG DANH MỤC (categories)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. BẢNG THƯƠNG HIỆU (brands)
CREATE TABLE IF NOT EXISTS `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `logo` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. BẢNG SẢN PHẨM (products)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `category_id` INT NOT NULL,
  `brand_id` INT NOT NULL,
  `gender` ENUM('Nam', 'Nữ', 'Unisex') DEFAULT 'Unisex',
  `price` DECIMAL(12,0) NOT NULL,
  `old_price` DECIMAL(12,0) DEFAULT NULL,
  `main_image` VARCHAR(500) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_hot` TINYINT(1) DEFAULT 0,
  `is_new` TINYINT(1) DEFAULT 1,
  `view_count` INT DEFAULT 0,
  `sold_count` INT DEFAULT 0,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. BẢNG BIẾN THỂ SIZE & TỒN KHO (product_variants)
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size` VARCHAR(10) NOT NULL,
  `color` VARCHAR(50) DEFAULT NULL,
  `stock_quantity` INT DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. BẢNG PHÍ SHIP TỈNH THÀNH (shipping_provinces)
CREATE TABLE IF NOT EXISTS `shipping_provinces` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `province_name` VARCHAR(100) NOT NULL UNIQUE,
  `shipping_fee` DECIMAL(12,0) NOT NULL DEFAULT 30000,
  `estimated_days` VARCHAR(50) DEFAULT '2-4 ngày',
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. BẢNG ĐƠN HÀNG (orders)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(20) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address_detail` VARCHAR(255) NOT NULL,
  `province_id` INT NOT NULL,
  `shipping_fee` DECIMAL(12,0) DEFAULT 30000,
  `subtotal` DECIMAL(12,0) NOT NULL,
  `discount_amount` DECIMAL(12,0) DEFAULT 0,
  `total_money` DECIMAL(12,0) NOT NULL,
  `payment_method` ENUM('COD', 'BANKING_QR') DEFAULT 'COD',
  `payment_status` ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
  `status` ENUM('pending', 'confirmed', 'packing', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
  `staff_id` INT DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `cancel_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`province_id`) REFERENCES `shipping_provinces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. BẢNG CHI TIẾT ĐƠN HÀNG (order_details)
CREATE TABLE IF NOT EXISTS `order_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `size` VARCHAR(10) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(12,0) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. BẢNG MÃ GIẢM GIÁ (vouchers)
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('fixed', 'percent', 'freeship') NOT NULL,
  `discount_value` DECIMAL(12,0) NOT NULL,
  `min_order_value` DECIMAL(12,0) DEFAULT 0,
  `max_discount` DECIMAL(12,0) DEFAULT NULL,
  `usage_limit` INT DEFAULT 100,
  `used_count` INT DEFAULT 0,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. BẢNG BÌNH LUẬN & ĐÁNH GIÁ (comments)
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `rating` TINYINT(1) DEFAULT 5,
  `content` TEXT NOT NULL,
  `staff_reply` TEXT DEFAULT NULL,
  `staff_id` INT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
-- 12. BẢNG HỒ SƠ NHÂN VIÊN (employees)
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `citizen_id` VARCHAR(20) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL,
  `position` VARCHAR(100) DEFAULT 'Nhân viên bán hàng',
  `work_shift` VARCHAR(100) DEFAULT 'Ca 1 (07:30 - 12:00)',
  `base_salary` DECIMAL(12,0) DEFAULT 5000000,
  `commission_rate` FLOAT DEFAULT 2.5,
  `work_days` INT DEFAULT 26,
  `off_days` INT DEFAULT 0,
  `off_dates_detail` TEXT DEFAULT NULL,
  `bonus` DECIMAL(12,0) DEFAULT 0,
  `bonus_reason` VARCHAR(255) DEFAULT NULL,
  `fine` DECIMAL(12,0) DEFAULT 0,
  `fine_reason` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. BẢNG THỜI KHÓA BIỂU LỊCH LÀM (employee_schedules)
CREATE TABLE IF NOT EXISTS `employee_schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `day_of_week` VARCHAR(20) NOT NULL,
  `shift_name` VARCHAR(100) DEFAULT 'Ca Sáng (07:30 - 12:00)',
  `start_time` TIME DEFAULT '07:30:00',
  `end_time` TIME DEFAULT '12:00:00',
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NẠP DỮ LIỆU MẪU BAN ĐẦU
INSERT INTO `shipping_provinces` (`province_name`, `shipping_fee`, `estimated_days`) VALUES
('Hà Nội', 20000, '1-2 ngày'),
('TP. Hồ Chí Minh', 25000, '2-3 ngày'),
('Đà Nẵng', 25000, '2-3 ngày'),
('Vĩnh Long', 30000, '3-4 ngày'),
('Tỉnh/Thành Khác', 35000, '3-5 ngày')
ON DUPLICATE KEY UPDATE `shipping_fee` = VALUES(`shipping_fee`);