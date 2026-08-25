-- Bảng quy đổi Size Giày (Size Chart)
CREATE TABLE IF NOT EXISTS `size_guides` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `brand_id` INT NULL DEFAULT NULL,
    `gender` ENUM('all', 'nam', 'nu', 'tre_em') DEFAULT 'all',
    `category_type` ENUM('all', 'giay', 'dep') DEFAULT 'all',
    `foot_length_cm` DECIMAL(4,1) NOT NULL,
    `size_eu` VARCHAR(20) NOT NULL,
    `size_us` VARCHAR(20) DEFAULT NULL,
    `size_uk` VARCHAR(20) DEFAULT NULL,
    `size_cm` VARCHAR(20) DEFAULT NULL,
    `note` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sg_brand` (`brand_id`),
    INDEX `idx_sg_gender` (`gender`),
    INDEX `idx_sg_foot` (`foot_length_cm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng các bước hướng dẫn đo size & mẹo chọn size
CREATE TABLE IF NOT EXISTS `size_guide_tips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `step_number` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `icon` VARCHAR(100) DEFAULT 'fa-solid fa-ruler',
    `image_url` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thêm dữ liệu mẫu các bước đo chân
INSERT INTO `size_guide_tips` (`step_number`, `title`, `description`, `icon`, `sort_order`) VALUES
(1, 'Chuẩn Bị Dụng Cụ', 'Chuẩn bị 1 tờ giấy trắng lớn hơn bàn chân, 1 cây bút chì hoặc bút bi, và 1 cây thước kẻ (thước dây hoặc thước thẳng). Nên đo vào buổi chiều hoặc tối khi chân nở tối đa.', 'fa-solid fa-pencil', 1),
(2, 'Vẽ Khung Bàn Chân', 'Đặt tờ giấy sát tường, đặt bàn chân phẳng lên giấy (gót chân chạm nhẹ vào tường). Dùng bút vẽ bo viền quanh mép bàn chân thật chuẩn xác, giữ bút thẳng đứng 90 độ.', 'fa-solid fa-shoe-prints', 2),
(3, 'Đo Chiều Dài & Chiều Rộng', 'Dùng thước đo khoảng cách từ điểm xa nhất của gót chân đến đầu ngón chân dài nhất (ngón cái hoặc ngón trỏ) để lấy chiều dài L (cm). Đo phần rộng nhất của mu bàn chân để lấy chiều rộng W (cm).', 'fa-solid fa-ruler-combined', 3),
(4, 'Đối Chiếu Bảng Size', 'Lấy Chiều dài chân L + 0.5cm (độ dư thoải mái) rồi đối chiếu vào bảng size bên dưới. Nếu chân bè ngang hoặc có mu bàn chân dày, nên chọn tăng thêm +1 Size (VD: 40 lên 41).', 'fa-solid fa-circle-check', 4)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Thêm dữ liệu quy đổi size mẫu chuẩn
INSERT INTO `size_guides` (`brand_id`, `gender`, `foot_length_cm`, `size_eu`, `size_us`, `size_uk`, `size_cm`, `note`, `sort_order`) VALUES
(NULL, 'all', 22.0, '35', '4.5', '3.5', '22.5', 'Chuẩn size nữ / chân nhỏ', 1),
(NULL, 'all', 22.5, '36', '5.0', '4.0', '23.0', 'Chuẩn size', 2),
(NULL, 'all', 23.0, '37', '5.5', '4.5', '23.5', 'Chuẩn size', 3),
(NULL, 'all', 23.5, '38', '6.0', '5.0', '24.0', 'Chuẩn size', 4),
(NULL, 'all', 24.5, '39', '6.5', '5.5', '24.5', 'Chuẩn size', 5),
(NULL, 'all', 25.0, '40', '7.0', '6.0', '25.0', 'Size phổ biến nam/nữ', 6),
(NULL, 'all', 25.5, '41', '8.0', '7.0', '26.0', 'Size chuẩn nam', 7),
(NULL, 'all', 26.0, '42', '8.5', '7.5', '26.5', 'Size chuẩn nam', 8),
(NULL, 'all', 26.5, '43', '9.5', '8.5', '27.5', 'Size chuẩn nam', 9),
(NULL, 'all', 27.5, '44', '10.0', '9.0', '28.0', 'Chân lớn', 10),
(NULL, 'all', 28.5, '45', '11.0', '10.0', '29.0', 'Chân ngoại cỡ', 11)
ON DUPLICATE KEY UPDATE `size_eu` = VALUES(`size_eu`);
