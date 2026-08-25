-- phpMyAdmin SQL Dump
-- Database: `web_shoe`
-- Full Export with 63 Provinces & System Data
-- Exported on: 2026-08-08 14:49:15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `web_shoe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `web_shoe`;

-- --------------------------------------------------------

-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `button_text` varchar(100) DEFAULT 'Mua Ngay',
  `position` enum('hero','promo_left','promo_right','sidebar') DEFAULT 'hero',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `link_url`, `button_text`, `position`, `sort_order`, `status`, `created_at`) VALUES ('1', 'BỨT PHÁ PHONG CÁCH 2026', 'Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi giảm đến 33% hôm nay!', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200', 'all-products.php', 'MUA SẮM NGAY', 'hero', '1', '1', '2026-08-04 18:50:22');
INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `link_url`, `button_text`, `position`, `sort_order`, `status`, `created_at`) VALUES ('2', 'Bộ Sưu Tập Mùa Hè', 'Dép & Sandal nhẹ nhàng thoải mái cho mọi chuyến đi', 'https://images.unsplash.com/photo-1603487742131-4160ec999306?q=80&w=800', 'all-products.php?type=dep', 'Xem Ngay', 'promo_left', '1', '1', '2026-08-04 18:50:22');
INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `link_url`, `button_text`, `position`, `sort_order`, `status`, `created_at`) VALUES ('3', 'Jordan Collection', 'Bộ sưu tập Air Jordan chính hãng dành cho tín đồ Sneaker', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', 'all-products.php?brand_id=3', 'Khám Phá', 'promo_right', '1', '1', '2026-08-04 18:50:22');

-- --------------------------------------------------------

-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('1', 'Nike', 'nike', 'uploads/1785904385_brand_images (4).jpg', 'Thương hiệu thể thao hàng đầu thế giới từ Mỹ, nổi tiếng với công nghệ Air và thiết kế iconic.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('2', 'Adidas', 'adidas', 'uploads/1785904422_brand_images.png', 'Thương hiệu thể thao Đức với 3 sọc huyền thoại, tiên phong trong công nghệ Boost & Primeknit.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('3', 'Jordan', 'jordan', 'https://upload.wikimedia.org/wikipedia/en/thumb/3/37/Jumpman_logo.svg/800px-Jumpman_logo.svg.png', 'Thương hiệu giày bóng rổ & thời trang đường phố huyền thoại mang tên Michael Jordan.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('4', 'Puma', 'puma', 'https://upload.wikimedia.org/wikipedia/en/thumb/d/da/Puma_complete_logo.svg/800px-Puma_complete_logo.svg.png', 'Thương hiệu thể thao Đức với phong cách trẻ trung, năng động và dòng da lộn kinh điển.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('5', 'New Balance', 'new-balance', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ea/New_Balance_logo.svg/800px-New_Balance_logo.svg.png', 'Thương hiệu Mỹ nổi tiếng với sự thoải mái, đệm ABZORB & N-ergy, cùng phong cách Dad Shoes.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('6', 'Converse', 'converse', 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/Converse_logo.svg/800px-Converse_logo.svg.png', 'Biểu tượng văn hóa đường phố toàn cầu với dòng Chuck Taylor All Star huyền thoại.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('7', 'Vans', 'vans', 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Vans-logo.svg/800px-Vans-logo.svg.png', 'Thương hiệu trượt ván đường phố đình đám với slogan \"Off The Wall\" và sọc Jazz cá tính.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('8', 'Birkenstock', 'birkenstock', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/Birkenstock_logo.svg/800px-Birkenstock_logo.svg.png', 'Thương hiệu dép Đức cao cấp hơn 240 năm lịch sử với đế cork định hình bàn chân siêu êm.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('9', 'Crocs', 'crocs', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Crocs_logo.svg/800px-Crocs_logo.svg.png', 'Thương hiệu dép Clog siêu nhẹ toàn cầu từ Mỹ với chất liệu Croslite chống nước và kháng khuẩn.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('10', 'MLB Korea', 'mlb-korea', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Major_League_Baseball_logo.svg/800px-Major_League_Baseball_logo.svg.png', 'Thương hiệu thời trang đường phố Hàn Quốc phong cách Chunky năng động lấy cảm hứng từ giải bóng chày Mỹ.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('11', 'Asics', 'asics', 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b1/Asics_Logo.svg/800px-Asics_Logo.svg.png', 'Thương hiệu thể thao Nhật Bản tiên phong công nghệ GEL đệm giảm chấn vượt trội.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('12', 'Skechers', 'skechers', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Skechers_logo.svg/800px-Skechers_logo.svg.png', 'Thương hiệu giày Mỹ dẫn đầu về sự thoải mái với đệm Memory Foam & Arch Fit.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('13', 'Yeezy', 'yeezy', 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/Yeezy_logo.svg/800px-Yeezy_logo.svg.png', 'Dòng sản phẩm độc đáo mang tầm vóc biểu tượng tương lai hợp tác giữa Kanye West & Adidas.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('14', 'Salomon', 'salomon', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Salomon_logo.svg/800px-Salomon_logo.svg.png', 'Thương hiệu Pháp hàng đầu về giày outdoor, trail running và thiết kế gorpcore đỉnh cao.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('15', 'On Running', 'on-running', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a0/On_Running_logo.svg/800px-On_Running_logo.svg.png', 'Thương hiệu giày chạy bộ Thụy Sĩ đỉnh cao với công nghệ đế CloudTec êm như bước trên mây.', '1', '2026-08-04 18:51:36');
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES ('16', 'Nike 4', 'nike-4', '', '', '1', '2026-08-05 12:14:15');

-- --------------------------------------------------------

-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_prod_var` (`user_id`,`product_id`,`variant_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` enum('giay','dep') DEFAULT 'giay',
  `gender` enum('nam','nu','unisex') DEFAULT 'unisex',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('1', NULL, 'Giày Nam', 'giay-nam', NULL, 'giay', 'nam', '1', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('2', NULL, 'Giày Nữ', 'giay-nu', NULL, 'giay', 'nu', '2', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('3', NULL, 'Dép Nam', 'dep-nam', NULL, 'dep', 'nam', '3', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('4', NULL, 'Dép Nữ', 'dep-nu', NULL, 'dep', 'nu', '4', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('5', '1', 'Sneaker Nam', 'sneaker-nam', NULL, 'giay', 'nam', '1', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('6', '1', 'Giày Chạy Bộ Nam', 'giay-chay-bo-nam', NULL, 'giay', 'nam', '2', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('7', '1', 'Giày Bóng Rổ', 'giay-bong-ro', NULL, 'giay', 'nam', '3', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('8', '1', 'Giày Thời Trang Nam', 'giay-thoi-trang-nam', NULL, 'giay', 'nam', '4', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('9', '2', 'Sneaker Nữ', 'sneaker-nu', NULL, 'giay', 'nu', '1', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('10', '2', 'Giày Chạy Bộ Nữ', 'giay-chay-bo-nu', NULL, 'giay', 'nu', '2', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('11', '2', 'Giày Thời Trang Nữ', 'giay-thoi-trang-nu', NULL, 'giay', 'nu', '3', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('12', '3', 'Dép Quai Ngang Nam', 'dep-quai-ngang-nam', NULL, 'dep', 'nam', '1', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('13', '3', 'Sandal Nam', 'sandal-nam', NULL, 'dep', 'nam', '2', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('14', '4', 'Dép Quai Ngang Nữ', 'dep-quai-ngang-nu', NULL, 'dep', 'nu', '1', '1', '2026-08-04 18:51:36');
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES ('15', '4', 'Sandal Nữ', 'sandal-nu', NULL, 'dep', 'nu', '2', '1', '2026-08-04 18:51:36');

-- --------------------------------------------------------

-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `rating` tinyint(1) DEFAULT 5,
  `content` text NOT NULL,
  `staff_reply` text DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('1', '1', '3', 'Trần Văn Khách', '5', 'Giày cực đẹp, chất da mềm mại, rất vừa chân. Đi rất êm!', NULL, NULL, '1', '2026-07-20 10:30:00');
INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('2', '2', '4', 'Nguyễn Thị Lan', '5', 'Dunk Low Panda quá đẹp luôn, đúng hàng chính hãng. Giao hàng nhanh!', NULL, NULL, '1', '2026-07-22 14:15:00');
INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('3', '3', '3', 'Trần Văn Khách', '4', 'Samba OG rất đẹp, hơi cứng ban đầu nhưng đi vài ngày mềm ra.', NULL, NULL, '1', '2026-07-25 09:45:00');
INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('4', '12', '4', 'Nguyễn Thị Lan', '5', 'Jordan 1 Chicago huyền thoại! Đóng gói cẩn thận, box đẹp.', NULL, NULL, '1', '2026-07-26 16:20:00');
INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('5', '28', '3', 'Trần Văn Khách', '5', 'Birkenstock đi cực kỳ thoải mái, nhẹ và bền!', NULL, NULL, '1', '2026-07-27 11:00:00');
INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('6', '26', '1', 'Quản Trị Viên', '4', 'ád', NULL, NULL, '1', '2026-08-04 18:55:47');
INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES ('7', '60', '7', 'Admin', '5', 'ưeee', NULL, NULL, '1', '2026-08-05 13:26:02');

-- --------------------------------------------------------

-- Table structure for table `employee_schedules`
--

DROP TABLE IF EXISTS `employee_schedules`;
CREATE TABLE `employee_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `shift_name` varchar(100) DEFAULT 'Ca Sáng (07:30 - 12:00)',
  `start_time` time DEFAULT '07:30:00',
  `end_time` time DEFAULT '12:00:00',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employee_schedules`
--

INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('1', '1', 'Thu 2', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('2', '1', 'Thu 3', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('3', '1', 'Thu 4', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('4', '1', 'Thu 5', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('5', '1', 'Thu 6', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('6', '1', 'Thu 7', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('7', '1', 'Chu Nhat', 'Ca Sáng (07:30 - 12:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:02:13');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('8', '2', 'Thu 2', 'Ca Chiều (12:00 - 17:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:09:46');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('9', '2', 'Thu 3', 'Ca Chiều (12:00 - 17:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:09:46');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('10', '2', 'Thu 4', 'Ca Chiều (12:00 - 17:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:09:46');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('11', '2', 'Thu 5', 'Ca Tối (17:00 - 22:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:09:46');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('12', '2', 'Thu 6', 'Ca Chiều (12:00 - 17:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:09:46');
INSERT INTO `employee_schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `note`, `created_at`) VALUES ('13', '2', 'Thu 7', 'Ca Tối (17:00 - 22:00)', '07:30:00', '12:00:00', '', '2026-08-05 12:09:46');

-- --------------------------------------------------------

-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `citizen_id` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `position` varchar(100) DEFAULT 'Nhân viên bán hàng',
  `work_shift` varchar(100) DEFAULT 'Ca 1 (08:00 - 16:00)',
  `base_salary` decimal(12,0) DEFAULT 5000000,
  `commission_rate` decimal(5,2) DEFAULT 2.50,
  `work_days` int(11) DEFAULT 26,
  `off_days` int(11) DEFAULT 0,
  `off_dates_detail` text DEFAULT NULL,
  `bonus` decimal(12,0) DEFAULT 0,
  `bonus_reason` varchar(255) DEFAULT NULL,
  `fine` decimal(12,0) DEFAULT 0,
  `fine_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `fullname`, `phone`, `email`, `citizen_id`, `address`, `avatar`, `position`, `work_shift`, `base_salary`, `commission_rate`, `work_days`, `off_days`, `off_dates_detail`, `bonus`, `bonus_reason`, `fine`, `fine_reason`, `notes`, `status`, `created_at`) VALUES ('1', '2', 'Nhân Viên Bán Hàng', '0907654321', 'staff@shoes.com', '079200012345', '456 Lê Đại Hành, Q11, TP.HCM', NULL, 'Nhân viên bán hàng', 'Ca 1 (08:00 - 16:00)', '6000000', '2.50', '26', '0', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-04 18:50:22');
INSERT INTO `employees` (`id`, `user_id`, `fullname`, `phone`, `email`, `citizen_id`, `address`, `avatar`, `position`, `work_shift`, `base_salary`, `commission_rate`, `work_days`, `off_days`, `off_dates_detail`, `bonus`, `bonus_reason`, `fine`, `fine_reason`, `notes`, `status`, `created_at`) VALUES ('2', '3', 'Trần Văn Khách', '0912345678', 'khachhang@gmail.com', '', '', NULL, 'Nhân viên bán hàng', 'Ca 1 (08:00 - 16:00)', '5000000', '2.50', '26', '0', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-05 12:09:22');
INSERT INTO `employees` (`id`, `user_id`, `fullname`, `phone`, `email`, `citizen_id`, `address`, `avatar`, `position`, `work_shift`, `base_salary`, `commission_rate`, `work_days`, `off_days`, `off_dates_detail`, `bonus`, `bonus_reason`, `fine`, `fine_reason`, `notes`, `status`, `created_at`) VALUES ('3', '8', 'Nguyễn Văn C', '0901234568', 'nhanvien@gmail.com', '079200012345', 'Vĩnh Long', NULL, 'Nhân viên bán hàng', 'Ca 1 (08:00 - 16:00)', '5000000', '2.50', '26', '0', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-05 12:13:47');
INSERT INTO `employees` (`id`, `user_id`, `fullname`, `phone`, `email`, `citizen_id`, `address`, `avatar`, `position`, `work_shift`, `base_salary`, `commission_rate`, `work_days`, `off_days`, `off_dates_detail`, `bonus`, `bonus_reason`, `fine`, `fine_reason`, `notes`, `status`, `created_at`) VALUES ('6', '16', 'Nguyễn Văn Be', '0901234571', 'nhanvien1@gmail.com', '079200012345', 'Vĩnh Long', 'assets/images/default-avatar.png', 'Nhân viên bán hàng', 'Ca Tối (17:00 - 22:00)', '5000000', '2.50', '0', '0', '', '0', '', '0', '', '', '1', '2026-08-05 12:21:07');

-- --------------------------------------------------------

-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT '#',
  `icon` varchar(100) DEFAULT NULL,
  `menu_type` enum('main','footer','mobile') DEFAULT 'main',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `parent_id`, `title`, `url`, `icon`, `menu_type`, `sort_order`, `status`, `created_at`) VALUES ('1', NULL, 'Trang Chủ', 'index.php', 'fa-solid fa-house', 'main', '1', '1', '2026-08-04 18:50:22');
INSERT INTO `menu_items` (`id`, `parent_id`, `title`, `url`, `icon`, `menu_type`, `sort_order`, `status`, `created_at`) VALUES ('2', NULL, 'Sản Phẩm', 'all-products.php', 'fa-solid fa-shoe-prints', 'main', '2', '1', '2026-08-04 18:50:22');
INSERT INTO `menu_items` (`id`, `parent_id`, `title`, `url`, `icon`, `menu_type`, `sort_order`, `status`, `created_at`) VALUES ('3', NULL, 'Giảm Giá', 'all-products.php?discount=1', 'fa-solid fa-fire', 'main', '3', '1', '2026-08-04 18:50:22');

-- --------------------------------------------------------

-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(500) DEFAULT NULL,
  `size` varchar(10) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `cost_price` decimal(12,0) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('1', '1', '60', '371', 'Yeezy Foam Runner Onyx', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg', '36', 'Phối Màu', '1', '2905000', '2200000');
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('2', '1', '64', '394', 'Crocs Mega Crush Clog Bone', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '36', 'Phối Màu', '1', '2058000', '1500000');
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('3', '2', '57', '353', 'Nike Victori One Slide Women', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png', '36', 'Phối Màu', '1', '622500', '420000');
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('4', '3', '57', '353', 'Nike Victori One Slide Women', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png', '36', 'Phối Màu', '1', '622500', '420000');
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('5', '4', '66', '404', 'MLB Korea Chunky Sandal Monogram', 'uploads/1785903879_angle1_images.jpg', '36', 'Phối Màu', '1', '1974000', '1300000');
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('6', '5', '20', '472', 'Air Jordan 4 Retro Bred Reimagined', 'uploads/1785904277_angle1_images (2).jpg', '36', '', '2', '5162000', '3500000');
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `price`, `cost_price`) VALUES ('7', '6', '20', '472', 'Air Jordan 4 Retro Bred Reimagined', 'uploads/1785904277_angle1_images (2).jpg', '36', '', '2', '5162000', '3500000');

-- --------------------------------------------------------

-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_detail` varchar(500) NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `shipping_fee` decimal(12,0) DEFAULT 30000,
  `subtotal` decimal(12,0) NOT NULL,
  `discount_amount` decimal(12,0) DEFAULT 0,
  `voucher_code` varchar(50) DEFAULT NULL,
  `total_money` decimal(12,0) NOT NULL,
  `payment_method` enum('COD','BANKING_QR') DEFAULT 'COD',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `status` enum('pending','confirmed','packing','shipping','completed','returning','cancelled') DEFAULT 'pending',
  `staff_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `return_reason` varchar(255) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `shipping_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `user_id` (`user_id`),
  KEY `province_id` (`province_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `shipping_provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `phone`, `address_detail`, `province_id`, `shipping_fee`, `subtotal`, `discount_amount`, `voucher_code`, `total_money`, `payment_method`, `payment_status`, `status`, `staff_id`, `note`, `cancel_reason`, `return_reason`, `confirmed_at`, `shipping_at`, `completed_at`, `cancelled_at`, `created_at`) VALUES ('1', 'SH2026080413595129', '1', 'Quản Trị Viên', '0901234567', 'jhuoh', '6', '22000', '4963000', '200000', 'JORDAN200K', '4785000', 'COD', 'unpaid', 'pending', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 18:59:51');
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `phone`, `address_detail`, `province_id`, `shipping_fee`, `subtotal`, `discount_amount`, `voucher_code`, `total_money`, `payment_method`, `payment_status`, `status`, `staff_id`, `note`, `cancel_reason`, `return_reason`, `confirmed_at`, `shipping_at`, `completed_at`, `cancelled_at`, `created_at`) VALUES ('2', 'SH2026080414001435', '1', 'Quản Trị Viên', '0901234567', 'jhuoh', '6', '22000', '622500', '0', NULL, '644500', 'BANKING_QR', 'unpaid', 'pending', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 19:00:14');
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `phone`, `address_detail`, `province_id`, `shipping_fee`, `subtotal`, `discount_amount`, `voucher_code`, `total_money`, `payment_method`, `payment_status`, `status`, `staff_id`, `note`, `cancel_reason`, `return_reason`, `confirmed_at`, `shipping_at`, `completed_at`, `cancelled_at`, `created_at`) VALUES ('3', 'SH2026080506351457', '7', 'Admin', '0901234567', 'sdsdsadsd', '5', '20000', '622500', '50000', 'WELCOME50', '592500', 'BANKING_QR', 'paid', 'pending', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 11:35:14');
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `phone`, `address_detail`, `province_id`, `shipping_fee`, `subtotal`, `discount_amount`, `voucher_code`, `total_money`, `payment_method`, `payment_status`, `status`, `staff_id`, `note`, `cancel_reason`, `return_reason`, `confirmed_at`, `shipping_at`, `completed_at`, `cancelled_at`, `created_at`) VALUES ('4', 'SH2026080508231711', '5', 'Giàu AI', '0901234569', 'ewtgdsgsg', '5', '20000', '1974000', '20000', 'FREESHIP', '1974000', 'COD', 'unpaid', 'pending', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 13:23:17');
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `phone`, `address_detail`, `province_id`, `shipping_fee`, `subtotal`, `discount_amount`, `voucher_code`, `total_money`, `payment_method`, `payment_status`, `status`, `staff_id`, `note`, `cancel_reason`, `return_reason`, `confirmed_at`, `shipping_at`, `completed_at`, `cancelled_at`, `created_at`) VALUES ('5', 'SH2026080813350988', '7', 'Giàu', '0901234569', 'Sa Đéc, Phường Sa Đéc, Tỉnh Đồng Tháp, 81000, Việt Nam', '25', '18000', '10324000', '0', NULL, '10342000', 'BANKING_QR', 'unpaid', 'pending', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-08 18:35:09');
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `phone`, `address_detail`, `province_id`, `shipping_fee`, `subtotal`, `discount_amount`, `voucher_code`, `total_money`, `payment_method`, `payment_status`, `status`, `staff_id`, `note`, `cancel_reason`, `return_reason`, `confirmed_at`, `shipping_at`, `completed_at`, `cancelled_at`, `created_at`) VALUES ('6', 'SH2026080814125034', '7', 'Quản Trị Viên', '0901234569', 'Trung tâm Công tác xã hội Vĩnh Long, Đường Trần Phú, Phường Thanh Đức, Tỉnh Vĩnh Long, 85108, Việt Nam', '4', '18000', '10324000', '300000', 'NIKE15', '10042000', 'COD', 'unpaid', 'pending', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-08 19:12:50');

-- --------------------------------------------------------

-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target` varchar(100) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `type` enum('phone','email') NOT NULL,
  `action` enum('login','register','reset_password') NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `otp_verifications`
--

DROP TABLE IF EXISTS `otp_verifications`;
CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target` varchar(100) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `type` enum('login','register','forgot','verify') NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `target`, `otp_code`, `type`, `is_used`, `expires_at`, `created_at`) VALUES ('1', 'admin_demo@shoesstore.vn', '367418', 'register', '1', '2026-08-05 06:22:59', '2026-08-05 11:17:59');

-- --------------------------------------------------------

-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('1', '1', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('2', '1', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/005e8105-ffad-4e50-94d3-e7f09f061266/AIR+FORCE+1+%2707.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('3', '1', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9a83eb9e-a0e2-41a2-9447-4a008c2a95c9/AIR+FORCE+1+%2707.png', '3');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('4', '1', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/96d03d09-4081-4200-84cf-23579bcf3c95/AIR+FORCE+1+%2707.png', '4');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('5', '2', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('6', '2', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/a14704fb-2231-4a1d-a99f-bbd75605d8f6/NIKE+DUNK+LOW+RETRO.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('7', '2', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/34dfa8b1-3829-450f-bb08-8f5b40cf326e/NIKE+DUNK+LOW+RETRO.png', '3');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('8', '2', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/75b81a7b-0d04-4530-9b4a-a3a8309b85c1/NIKE+DUNK+LOW+RETRO.png', '4');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('13', '4', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('14', '4', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('15', '5', 'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('16', '5', 'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('17', '6', 'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('18', '6', 'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('19', '7', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('20', '7', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('21', '8', 'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('22', '8', 'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('23', '9', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('24', '9', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('25', '10', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('26', '10', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('27', '11', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('28', '11', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('29', '12', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('30', '12', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('31', '13', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('32', '13', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('33', '14', 'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('34', '14', 'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('35', '15', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('36', '15', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('37', '16', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('38', '16', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('39', '17', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('40', '17', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('41', '18', 'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('42', '18', 'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('48', '21', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('49', '21', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('50', '22', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('51', '22', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('52', '23', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('53', '23', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('54', '24', 'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('55', '24', 'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('56', '25', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('57', '25', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('58', '26', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('59', '26', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('60', '27', 'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('61', '27', 'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('62', '28', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('63', '28', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('66', '30', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('67', '30', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('68', '31', 'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('69', '31', 'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('70', '32', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('71', '32', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('72', '33', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('73', '33', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('74', '34', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('75', '34', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('76', '35', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('77', '35', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('78', '36', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('79', '36', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('80', '37', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('81', '37', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('82', '38', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('83', '38', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('84', '39', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('85', '39', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('86', '40', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('87', '40', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('88', '41', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('89', '41', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('90', '42', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('91', '42', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('92', '43', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('93', '43', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('94', '44', 'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('95', '44', 'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('96', '45', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('97', '45', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('98', '46', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('99', '46', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('100', '47', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('101', '47', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('102', '48', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('103', '48', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('104', '49', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('105', '49', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('108', '51', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('109', '51', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('110', '52', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('111', '52', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('112', '53', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('113', '53', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('114', '54', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('115', '54', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('116', '55', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('117', '55', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('118', '56', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('119', '56', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('120', '57', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('121', '57', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('122', '58', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('123', '58', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('124', '59', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('125', '59', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('126', '60', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('127', '60', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('128', '61', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('129', '61', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('130', '62', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('131', '62', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('132', '63', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('133', '63', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('134', '64', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('135', '64', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('136', '65', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png', '1');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('137', '65', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('140', '66', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('141', '3', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/6b763ec253454b52b217a8bf011894d8_9366/Giay_Samba_OG_trang_B75806_02_standard_hover.jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('142', '3', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/815915d3fa78486ca9c2a8bf0118a803_9366/Giay_Samba_OG_trang_B75806_04_standard.jpg', '3');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('143', '3', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/01f80ef307614d3ca976a8bf0118ca21_9366/Giay_Samba_OG_trang_B75806_41_detail.jpg', '4');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('144', '19', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/032fcfc5-d72b-426c-85fa-7fcf1dd12781/AIR+JORDAN+1+RETRO+HIGH+OG.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('145', '19', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/7d363d66-ebbe-4835-9fa8-1f19fbb1c7a5/AIR+JORDAN+1+RETRO+HIGH+OG.png', '3');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('148', '50', 'uploads/1785904174_angle2_images (1).jpg', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('150', '20', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e21943c5-47d6-4a50-8d7e-d7a45c4b42a2/JORDAN+4+RETRO.png', '2');
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES ('152', '29', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3aed9c49f77f4c27b267af4c00e60e83_9366/Giay_Samba_OG_Hong_IG4199_01_standard.jpg', '2');

-- --------------------------------------------------------

-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=508 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('1', '1', '36', 'Trắng', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('2', '1', '37', 'Trắng', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('3', '1', '38', 'Trắng', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('4', '1', '39', 'Trắng', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('5', '1', '40', 'Trắng', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('6', '1', '41', 'Trắng', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('7', '1', '42', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('8', '1', '43', 'Trắng', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('9', '2', '36', 'Phối Màu', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('10', '2', '37', 'Phối Màu', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('11', '2', '38', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('12', '2', '39', 'Phối Màu', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('13', '2', '40', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('14', '2', '41', 'Phối Màu', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('15', '2', '42', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('16', '2', '43', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('17', '3', '36', 'Trắng', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('18', '3', '37', 'Trắng', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('19', '3', '38', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('20', '3', '39', 'Trắng', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('21', '3', '40', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('22', '3', '41', 'Trắng', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('23', '3', '42', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('24', '3', '43', 'Trắng', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('25', '4', '36', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('26', '4', '37', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('27', '4', '38', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('28', '4', '39', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('29', '4', '40', 'Trắng', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('30', '4', '41', 'Trắng', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('31', '4', '42', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('32', '4', '43', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('33', '5', '36', 'Xám', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('34', '5', '37', 'Xám', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('35', '5', '38', 'Xám', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('36', '5', '39', 'Xám', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('37', '5', '40', 'Xám', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('38', '5', '41', 'Xám', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('39', '5', '42', 'Xám', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('40', '5', '43', 'Xám', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('41', '6', '36', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('42', '6', '37', 'Trắng', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('43', '6', '38', 'Trắng', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('44', '6', '39', 'Trắng', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('45', '6', '40', 'Trắng', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('46', '6', '41', 'Trắng', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('47', '6', '42', 'Trắng', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('48', '6', '43', 'Trắng', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('49', '7', '36', 'Phối Màu', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('50', '7', '37', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('51', '7', '38', 'Phối Màu', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('52', '7', '39', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('53', '7', '40', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('54', '7', '41', 'Phối Màu', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('55', '7', '42', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('56', '7', '43', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('57', '8', '36', 'Trắng', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('58', '8', '37', 'Trắng', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('59', '8', '38', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('60', '8', '39', 'Trắng', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('61', '8', '40', 'Trắng', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('62', '8', '41', 'Trắng', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('63', '8', '42', 'Trắng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('64', '8', '43', 'Trắng', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('65', '9', '36', 'Phối Màu', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('66', '9', '37', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('67', '9', '38', 'Phối Màu', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('68', '9', '39', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('69', '9', '40', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('70', '9', '41', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('71', '9', '42', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('72', '9', '43', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('73', '10', '36', 'Xám', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('74', '10', '37', 'Xám', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('75', '10', '38', 'Xám', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('76', '10', '39', 'Xám', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('77', '10', '40', 'Xám', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('78', '10', '41', 'Xám', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('79', '10', '42', 'Xám', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('80', '10', '43', 'Xám', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('81', '11', '39', 'Phối Màu', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('82', '11', '40', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('83', '11', '41', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('84', '11', '42', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('85', '11', '43', 'Phối Màu', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('86', '11', '44', 'Phối Màu', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('87', '12', '39', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('88', '12', '40', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('89', '12', '41', 'Trắng', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('90', '12', '42', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('91', '12', '43', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('92', '12', '44', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('93', '13', '39', 'Đen', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('94', '13', '40', 'Đen', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('95', '13', '41', 'Đen', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('96', '13', '42', 'Đen', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('97', '13', '43', 'Đen', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('98', '13', '44', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('99', '14', '39', 'Phối Màu', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('100', '14', '40', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('101', '14', '41', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('102', '14', '42', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('103', '14', '43', 'Phối Màu', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('104', '14', '44', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('105', '15', '39', 'Phối Màu', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('106', '15', '40', 'Phối Màu', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('107', '15', '41', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('108', '15', '42', 'Phối Màu', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('109', '15', '43', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('110', '15', '44', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('111', '16', '39', 'Đen', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('112', '16', '40', 'Đen', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('113', '16', '41', 'Đen', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('114', '16', '42', 'Đen', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('115', '16', '43', 'Đen', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('116', '16', '44', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('117', '17', '39', 'Đen', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('118', '17', '40', 'Đen', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('119', '17', '41', 'Đen', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('120', '17', '42', 'Đen', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('121', '17', '43', 'Đen', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('122', '17', '44', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('123', '18', '39', 'Đen', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('124', '18', '40', 'Đen', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('125', '18', '41', 'Đen', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('126', '18', '42', 'Đen', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('127', '18', '43', 'Đen', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('128', '18', '44', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('129', '19', '36', 'Phối Màu', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('130', '19', '37', 'Phối Màu', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('131', '19', '38', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('132', '19', '39', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('133', '19', '40', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('134', '19', '41', 'Phối Màu', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('135', '19', '42', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('136', '19', '43', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('137', '20', '39', 'Đỏ', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('138', '20', '40', 'Đỏ', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('139', '20', '41', 'Đỏ', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('140', '20', '42', 'Đỏ', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('141', '20', '43', 'Đỏ', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('142', '20', '44', 'Đỏ', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('143', '21', '39', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('144', '21', '40', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('145', '21', '41', 'Phối Màu', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('146', '21', '42', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('147', '21', '43', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('148', '21', '44', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('149', '22', '39', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('150', '22', '40', 'Trắng', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('151', '22', '41', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('152', '22', '42', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('153', '22', '43', 'Trắng', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('154', '22', '44', 'Trắng', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('155', '23', '36', 'Đen', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('156', '23', '37', 'Đen', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('157', '23', '38', 'Đen', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('158', '23', '39', 'Đen', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('159', '23', '40', 'Đen', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('160', '23', '41', 'Đen', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('161', '23', '42', 'Đen', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('162', '23', '43', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('163', '24', '36', 'Trắng', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('164', '24', '37', 'Trắng', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('165', '24', '38', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('166', '24', '39', 'Trắng', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('167', '24', '40', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('168', '24', '41', 'Trắng', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('169', '24', '42', 'Trắng', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('170', '24', '43', 'Trắng', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('171', '25', '36', 'Đen', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('172', '25', '37', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('173', '25', '38', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('174', '25', '39', 'Đen', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('175', '25', '40', 'Đen', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('176', '25', '41', 'Đen', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('177', '25', '42', 'Đen', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('178', '25', '43', 'Đen', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('179', '26', '39', 'Phối Màu', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('180', '26', '40', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('181', '26', '41', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('182', '26', '42', 'Phối Màu', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('183', '26', '43', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('184', '26', '44', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('185', '27', '39', 'Bạc', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('186', '27', '40', 'Bạc', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('187', '27', '41', 'Bạc', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('188', '27', '42', 'Bạc', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('189', '27', '43', 'Bạc', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('190', '27', '44', 'Bạc', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('191', '28', '36', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('192', '28', '37', 'Trắng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('193', '28', '38', 'Trắng', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('194', '28', '39', 'Trắng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('195', '28', '40', 'Trắng', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('196', '29', '36', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('197', '29', '37', 'Phối Màu', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('198', '29', '38', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('199', '29', '39', 'Phối Màu', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('200', '29', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('201', '30', '36', 'Đen', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('202', '30', '37', 'Đen', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('203', '30', '38', 'Đen', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('204', '30', '39', 'Đen', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('205', '30', '40', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('206', '31', '36', 'Bạc', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('207', '31', '37', 'Bạc', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('208', '31', '38', 'Bạc', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('209', '31', '39', 'Bạc', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('210', '31', '40', 'Bạc', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('211', '32', '36', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('212', '32', '37', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('213', '32', '38', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('214', '32', '39', 'Trắng', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('215', '32', '40', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('216', '33', '36', 'Trắng', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('217', '33', '37', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('218', '33', '38', 'Trắng', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('219', '33', '39', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('220', '33', '40', 'Trắng', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('221', '34', '36', 'Bạc', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('222', '34', '37', 'Bạc', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('223', '34', '38', 'Bạc', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('224', '34', '39', 'Bạc', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('225', '34', '40', 'Bạc', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('226', '35', '36', 'Hồng', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('227', '35', '37', 'Hồng', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('228', '35', '38', 'Hồng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('229', '35', '39', 'Hồng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('230', '35', '40', 'Hồng', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('231', '36', '36', 'Trắng', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('232', '36', '37', 'Trắng', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('233', '36', '38', 'Trắng', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('234', '36', '39', 'Trắng', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('235', '36', '40', 'Trắng', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('236', '37', '36', 'Trắng', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('237', '37', '37', 'Trắng', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('238', '37', '38', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('239', '37', '39', 'Trắng', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('240', '37', '40', 'Trắng', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('241', '38', '36', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('242', '38', '37', 'Phối Màu', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('243', '38', '38', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('244', '38', '39', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('245', '38', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('246', '39', '36', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('247', '39', '37', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('248', '39', '38', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('249', '39', '39', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('250', '39', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('251', '40', '36', 'Đen', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('252', '40', '37', 'Đen', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('253', '40', '38', 'Đen', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('254', '40', '39', 'Đen', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('255', '40', '40', 'Đen', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('256', '41', '36', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('257', '41', '37', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('258', '41', '38', 'Phối Màu', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('259', '41', '39', 'Phối Màu', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('260', '41', '40', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('261', '42', '36', 'Xanh Lá', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('262', '42', '37', 'Xanh Lá', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('263', '42', '38', 'Xanh Lá', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('264', '42', '39', 'Xanh Lá', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('265', '42', '40', 'Xanh Lá', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('266', '43', '36', 'Xanh', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('267', '43', '37', 'Xanh', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('268', '43', '38', 'Xanh', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('269', '43', '39', 'Xanh', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('270', '43', '40', 'Xanh', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('271', '44', '36', 'Đỏ', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('272', '44', '37', 'Đỏ', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('273', '44', '38', 'Đỏ', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('274', '44', '39', 'Đỏ', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('275', '44', '40', 'Đỏ', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('276', '45', '36', 'Trắng', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('277', '45', '37', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('278', '45', '38', 'Trắng', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('279', '45', '39', 'Trắng', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('280', '45', '40', 'Trắng', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('281', '46', '39', 'Đen', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('282', '46', '40', 'Đen', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('283', '46', '41', 'Đen', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('284', '46', '42', 'Đen', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('285', '46', '43', 'Đen', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('286', '46', '44', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('287', '47', '39', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('288', '47', '40', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('289', '47', '41', 'Phối Màu', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('290', '47', '42', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('291', '47', '43', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('292', '47', '44', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('293', '48', '39', 'Đen', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('294', '48', '40', 'Đen', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('295', '48', '41', 'Đen', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('296', '48', '42', 'Đen', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('297', '48', '43', 'Đen', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('298', '48', '44', 'Đen', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('299', '49', '39', 'Đen', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('300', '49', '40', 'Đen', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('301', '49', '41', 'Đen', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('302', '49', '42', 'Đen', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('303', '49', '43', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('304', '49', '44', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('305', '50', '36', 'Trắng', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('306', '50', '37', 'Trắng', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('307', '50', '38', 'Trắng', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('308', '50', '39', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('309', '50', '40', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('310', '50', '41', 'Trắng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('311', '50', '42', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('312', '50', '43', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('313', '51', '39', 'Phối Màu', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('314', '51', '40', 'Phối Màu', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('315', '51', '41', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('316', '51', '42', 'Phối Màu', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('317', '51', '43', 'Phối Màu', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('318', '51', '44', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('319', '52', '39', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('320', '52', '40', 'Đen', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('321', '52', '41', 'Đen', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('322', '52', '42', 'Đen', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('323', '52', '43', 'Đen', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('324', '52', '44', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('325', '53', '36', 'Đen', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('326', '53', '37', 'Đen', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('327', '53', '38', 'Đen', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('328', '53', '39', 'Đen', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('329', '53', '40', 'Đen', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('330', '53', '41', 'Đen', '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('331', '53', '42', 'Đen', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('332', '53', '43', 'Đen', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('333', '54', '36', 'Nâu Tây', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('334', '54', '37', 'Nâu Tây', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('335', '54', '38', 'Nâu Tây', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('336', '54', '39', 'Nâu Tây', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('337', '54', '40', 'Nâu Tây', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('338', '54', '41', 'Nâu Tây', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('339', '54', '42', 'Nâu Tây', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('340', '54', '43', 'Nâu Tây', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('341', '55', '39', 'Đen', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('342', '55', '40', 'Đen', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('343', '55', '41', 'Đen', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('344', '55', '42', 'Đen', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('345', '55', '43', 'Đen', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('346', '55', '44', 'Đen', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('347', '56', '39', 'Phối Màu', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('348', '56', '40', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('349', '56', '41', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('350', '56', '42', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('351', '56', '43', 'Phối Màu', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('352', '56', '44', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('353', '57', '36', 'Phối Màu', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('354', '57', '37', 'Phối Màu', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('355', '57', '38', 'Phối Màu', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('356', '57', '39', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('357', '57', '40', 'Phối Màu', '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('358', '58', '36', 'Phối Màu', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('359', '58', '37', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('360', '58', '38', 'Phối Màu', '21');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('361', '58', '39', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('362', '58', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('363', '59', '36', 'Trắng', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('364', '59', '37', 'Trắng', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('365', '59', '38', 'Trắng', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('366', '59', '39', 'Trắng', '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('367', '59', '40', 'Trắng', '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('368', '59', '41', 'Trắng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('369', '59', '42', 'Trắng', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('370', '59', '43', 'Trắng', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('371', '60', '36', 'Phối Màu', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('372', '60', '37', 'Phối Màu', '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('373', '60', '38', 'Phối Màu', '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('374', '60', '39', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('375', '60', '40', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('376', '60', '41', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('377', '60', '42', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('378', '60', '43', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('379', '61', '36', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('380', '61', '37', 'Phối Màu', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('381', '61', '38', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('382', '61', '39', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('383', '61', '40', 'Phối Màu', '9');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('384', '62', '36', 'Trắng', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('385', '62', '37', 'Trắng', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('386', '62', '38', 'Trắng', '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('387', '62', '39', 'Trắng', '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('388', '62', '40', 'Trắng', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('389', '63', '36', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('390', '63', '37', 'Phối Màu', '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('391', '63', '38', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('392', '63', '39', 'Phối Màu', '17');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('393', '63', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('394', '64', '36', 'Phối Màu', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('395', '64', '37', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('396', '64', '38', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('397', '64', '39', 'Phối Màu', '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('398', '64', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('399', '65', '36', 'Phối Màu', '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('400', '65', '37', 'Phối Màu', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('401', '65', '38', 'Phối Màu', '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('402', '65', '39', 'Phối Màu', '24');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('403', '65', '40', 'Phối Màu', '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('404', '66', '36', 'Phối Màu', '14');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('405', '66', '37', 'Phối Màu', '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('406', '66', '38', 'Phối Màu', '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('407', '66', '39', 'Phối Màu', '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('408', '66', '40', 'Phối Màu', '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('409', '66', '36', NULL, '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('410', '66', '37', NULL, '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('411', '66', '38', NULL, '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('412', '66', '39', NULL, '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('413', '66', '40', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('414', '66', '41', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('415', '66', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('416', '66', '43', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('417', '66', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('418', '3', '36', NULL, '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('419', '3', '37', NULL, '18');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('420', '3', '38', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('421', '3', '39', NULL, '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('422', '3', '40', NULL, '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('423', '3', '41', NULL, '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('424', '3', '42', NULL, '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('425', '3', '43', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('426', '3', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('427', '19', '36', NULL, '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('428', '19', '37', NULL, '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('429', '19', '38', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('430', '19', '39', NULL, '5');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('431', '19', '40', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('432', '19', '41', NULL, '25');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('433', '19', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('434', '19', '43', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('435', '19', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('436', '50', '36', NULL, '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('437', '50', '37', NULL, '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('438', '50', '38', NULL, '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('439', '50', '39', NULL, '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('440', '50', '40', NULL, '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('441', '50', '41', NULL, '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('442', '50', '42', NULL, '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('443', '50', '43', NULL, '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('444', '50', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('445', '50', '36', NULL, '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('446', '50', '37', NULL, '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('447', '50', '38', NULL, '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('448', '50', '39', NULL, '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('449', '50', '40', NULL, '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('450', '50', '41', NULL, '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('451', '50', '42', NULL, '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('452', '50', '43', NULL, '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('453', '50', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('454', '50', '36', NULL, '12');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('455', '50', '37', NULL, '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('456', '50', '38', NULL, '11');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('457', '50', '39', NULL, '15');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('458', '50', '40', NULL, '23');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('459', '50', '41', NULL, '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('460', '50', '42', NULL, '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('461', '50', '43', NULL, '7');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('462', '50', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('463', '20', '36', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('464', '20', '37', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('465', '20', '38', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('466', '20', '39', NULL, '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('467', '20', '40', NULL, '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('468', '20', '41', NULL, '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('469', '20', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('470', '20', '43', NULL, '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('471', '20', '44', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('472', '20', '36', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('473', '20', '37', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('474', '20', '38', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('475', '20', '39', NULL, '8');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('476', '20', '40', NULL, '19');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('477', '20', '41', NULL, '16');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('478', '20', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('479', '20', '43', NULL, '13');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('480', '20', '44', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('481', '29', '36', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('482', '29', '37', NULL, '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('483', '29', '38', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('484', '29', '39', NULL, '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('485', '29', '40', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('486', '29', '41', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('487', '29', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('488', '29', '43', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('489', '29', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('490', '29', '36', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('491', '29', '37', NULL, '20');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('492', '29', '38', NULL, '6');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('493', '29', '39', NULL, '22');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('494', '29', '40', NULL, '0');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('495', '29', '41', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('496', '29', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('497', '29', '43', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('498', '29', '44', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('499', '70', '36', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('500', '70', '37', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('501', '70', '38', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('502', '70', '39', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('503', '70', '40', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('504', '70', '41', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('505', '70', '42', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('506', '70', '43', NULL, '10');
INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES ('507', '70', '44', NULL, '10');

-- --------------------------------------------------------

-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `gender` enum('Nam','Nữ','Unisex') DEFAULT 'Unisex',
  `price` decimal(12,0) NOT NULL,
  `old_price` decimal(12,0) DEFAULT NULL,
  `cost_price` decimal(12,0) DEFAULT 0,
  `discount_percent` int(11) DEFAULT 0,
  `main_image` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `is_hot` tinyint(1) DEFAULT 0,
  `is_new` tinyint(1) DEFAULT 1,
  `view_count` int(11) DEFAULT 0,
  `sold_count` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('1', 'NK-AF1-WHT', 'Nike Air Force 1 \'07 White', 'nike-air-force-1-07-white', '5', '1', 'Unisex', '2929000', '3500000', '1800000', '16', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png', 'Đôi giày huyền thoại Nike Air Force 1 \'07 với chất liệu da thật cao cấp, đệm Air êm ái, thích hợp phối mọi trang phục streetwear.', '0', '0', '1450', '420', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('2', 'NK-DUNK-PANDA', 'Nike Dunk Low Retro Panda', 'nike-dunk-low-retro-panda', '5', '1', 'Unisex', '3100000', '3600000', '1900000', '14', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png', 'Phối màu Panda đen trắng kinh điển của dòng Nike Dunk Low, lựa chọn cực kỳ thời thượng và dễ phối đồ nhất hiện nay.', '1', '0', '2300', '580', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('3', 'AD-SAMBA-OG', 'Adidas Samba OG White Black', 'adidas-samba-og-white-black', '5', '2', 'Unisex', '2700000', '3100000', '1600000', '13', 'uploads/1785903983_angle1_giay_adidas_samba_og_white_black_gum_b758068_985da8a8aa2a4662ac9857f9efd30238_master.webp', 'Adidas Samba OG phong cách Retro chưa bao giờ giảm sức hút, chất da thật mềm mại kết hợp đế cao su bám đường tuyệt vời.', '1', '1', '3801', '920', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('4', 'AD-SUPERSTAR', 'Adidas Superstar Cloud White', 'adidas-superstar-cloud-white', '5', '2', 'Unisex', '2500000', '2900000', '1500000', '14', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg', 'Biểu tượng mũi sò Shell-Toe vượt thời gian của Adidas Superstar, chất liệu da cổ điển cùng 3 sọc đen nổi bật.', '0', '0', '1050', '310', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('5', 'NB-574-GRY', 'New Balance 574 Classic Grey', 'new-balance-574-classic-grey', '5', '5', 'Unisex', '2650000', '2900000', '1600000', '9', 'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'Dòng New Balance 574 huyền thoại phối màu Xám Classic, tích hợp bộ đệm ENCAP hỗ trợ tối đa cho việc đi lại cả ngày.', '0', '1', '720', '195', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('6', 'NB-550-WHT', 'New Balance 550 White Grey', 'new-balance-550-white-grey', '5', '5', 'Unisex', '3250000', '3800000', '2000000', '14', 'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'New Balance 550 với nguồn gốc từ giày bóng rổ thập niên 80, thiết kế Retro bứt phá cực hot trên toàn thế giới.', '0', '1', '1600', '390', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('7', 'MLB-CHUNKY-BOS', 'MLB Korea Big Ball Chunky A Boston', 'mlb-korea-big-ball-chunky-a-boston', '5', '10', 'Unisex', '2850000', '3300000', '1700000', '14', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', 'Sneaker MLB Chunky đế cao 6cm tôn dáng đỉnh cao, in logo đội bóng chày Boston Red Sox thời thượng.', '0', '0', '1850', '460', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('8', 'VN-KNU-SKOOL', 'Vans Knu Skool Black White', 'vans-knu-skool-black-white', '5', '7', 'Unisex', '2200000', '2600000', '1300000', '15', 'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$', 'Vans Knu Skool với lưỡi gà mập phồng độc đáo phong cách 90s Y2K, sọc Sidestripe 3D nổi bật.', '0', '1', '1200', '280', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('9', 'YZY-350-ONYX', 'Yeezy Boost 350 V2 Onyx', 'yeezy-boost-350-v2-onyx', '5', '13', 'Unisex', '6200000', '7000000', '4200000', '11', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg', 'Yeezy Boost 350 V2 Onyx phủ sắc đen quyến rũ, chất liệu vải dệt Primeknit mượt mà cùng đế đệm Boost êm vượt trội.', '1', '0', '2900', '510', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('10', 'AS-GEL-NYC', 'Asics Gel-NYC Cream Oyster Grey', 'asics-gel-nyc-cream-oyster-grey', '5', '11', 'Unisex', '3600000', '4200000', '2200000', '14', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', 'Giày Asics Gel-NYC phong cách Techwear & Dad Shoe kết hợp cấu trúc GEL và đệm Solyte êm ái hàng đầu Nhật Bản.', '0', '1', '1400', '320', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('11', 'NK-PEGASUS41', 'Nike Air Zoom Pegasus 41', 'nike-air-zoom-pegasus-41', '6', '1', 'Nam', '3600000', '4200000', '2200000', '14', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png', 'Dòng giày chạy bộ quốc dân Nike Pegasus 41 trang bị đệm bọt ReactX kết hợp Air Zoom phản hồi lực siêu nhạy.', '0', '1', '980', '220', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('12', 'NK-INVINCIBLE3', 'Nike ZoomX Invincible 3 White', 'nike-zoomx-invincible-3-white', '6', '1', 'Nam', '4800000', '5500000', '3000000', '13', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png', 'Siêu phẩm chạy bộ Nike Invincible 3 với đệm ZoomX dày tối đa giúp bảo vệ khớp gối và hồi phục năng lượng tuyệt vời.', '0', '0', '1350', '290', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('13', 'AD-ULTRABOOST', 'Adidas Ultraboost Light Black', 'adidas-ultraboost-light-black', '6', '2', 'Nam', '3800000', '4500000', '2300000', '16', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg', 'Adidas Ultraboost Light với đệm Light Boost nhẹ hơn 30% so với thế hệ trước, trải nghiệm chạy êm mượt vô hạn.', '0', '1', '890', '195', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('14', 'NB-FUELCELL', 'New Balance FuelCell Propel v4', 'new-balance-fuelcell-propel-v4', '6', '5', 'Nam', '2900000', '3400000', '1800000', '15', 'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'New Balance FuelCell Propel v4 tích hợp tấm TPU giữa đế giúp bật nảy đà tốt cho cự ly chạy từ 5km đến Marathon.', '0', '1', '410', '95', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('15', 'AS-KAYANO14', 'Asics Gel-Kayano 14 Metallic Plum', 'asics-gel-kayano-14-metallic-plum', '6', '11', 'Nam', '4200000', '4800000', '2600000', '13', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', 'Mẫu chạy bộ đỉnh cao Asics Gel-Kayano 14 mang tính biểu tượng những năm 2000, êm ái và ổn định bàn chân tuyệt đối.', '0', '1', '1650', '340', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('16', 'ON-MONSTER2', 'On Running Cloudmonster 2 Black', 'on-running-cloudmonster-2-black', '6', '15', 'Nam', '4950000', '5600000', '3100000', '12', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'On Running Cloudmonster 2 sở hữu các đệm mây CloudTec khổng lồ mang đến khả năng đệm tối đa và năng lượng bùng nổ.', '0', '1', '1102', '240', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('17', 'SLM-XT6-GTX', 'Salomon XT-6 Gore-Tex Black', 'salomon-xt-6-gore-tex-black', '6', '14', 'Nam', '5400000', '6200000', '3500000', '13', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', 'Salomon XT-6 Gore-Tex chống nước tuyệt đối, công nghệ dây buộc Quicklace tiện lợi cùng đệm ACS nâng đỡ vượt địa hình.', '0', '1', '1890', '410', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('18', 'SK-GOWALK6', 'Skechers Go Walk 6 Black Navy', 'skechers-go-walk-6-black-navy', '6', '12', 'Nam', '1950000', '2300000', '1100000', '15', 'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800', 'Skechers Go Walk 6 tích hợp đệm ULTRA GO siêu nhẹ và công nghệ đệm Air Cooled Goga Mat cực kỳ êm ái cho đôi chân.', '0', '1', '530', '140', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('19', 'JD-1-CHICAGO', 'Air Jordan 1 Retro High OG Chicago', 'air-jordan-1-retro-high-og-chicago', '7', '3', 'Unisex', '5200000', '6000000', '3200000', '13', 'uploads/1785904051_angle1_images (1).jpg', 'Phối màu Chicago đỏ trắng đen huyền thoại của Air Jordan 1, biểu tượng số 1 trong thế giới Sneaker & Bóng rổ.', '1', '0', '4601', '890', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('20', 'JD-4-BRED', 'Air Jordan 4 Retro Bred Reimagined', 'air-jordan-4-retro-bred-reimagined', '7', '3', 'Nam', '5800000', '6500000', '3500000', '11', 'uploads/1785904277_angle1_images (2).jpg', 'Air Jordan 4 Bred Reimagined bằng chất da thật cao cấp láng mịn, form dáng thể thao chuẩn mực cực kỳ đẳng cấp.', '1', '1', '3407', '624', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('21', 'NK-LEBRON21', 'Nike LeBron 21 Akoya', 'nike-lebron-21-akoya', '7', '1', 'Nam', '5500000', '6200000', '3300000', '11', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png', 'Giày bóng rổ Nike LeBron 21 trang bị hệ thống đệm Air Zoom kẹp giữa bọt Cushlon 2.0 tối ưu cú bật nhảy và tiếp đất.', '0', '1', '650', '130', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('22', 'NK-GTCUT3', 'Nike GT Cut 3 Summit White', 'nike-gt-cut-3-summit-white', '7', '1', 'Nam', '4600000', '5200000', '2800000', '12', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png', 'Dòng giày bóng rổ tốc độ Nike GT Cut 3 tích hợp bọt ZoomX đầu tiên trên sân bóng rổ, giúp xoay đổi hướng tức thì.', '0', '1', '820', '180', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('23', 'CV-CHUCK70-BLK', 'Converse Chuck 70 High Black', 'converse-chuck-70-high-black', '8', '6', 'Unisex', '2000000', '2300000', '1200000', '13', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg', 'Converse Chuck 70 cổ cao chất vải Canvas 12oz dày dặn, đệm lót OrthoLite êm ái cùng đường chỉ khâu Vintage.', '0', '0', '890', '280', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('24', 'VN-OLD-SKOOL', 'Vans Old Skool Black White', 'vans-old-skool-black-white', '8', '7', 'Unisex', '1800000', '2100000', '1000000', '14', 'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$', 'Vans Old Skool với đường sọc trắng Jazz kinh điển, da lộn phối vải canvas bền bỉ cho giới trẻ năng động.', '0', '1', '1100', '360', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('25', 'PM-SUEDE-BLK', 'Puma Suede Classic XXI Black', 'puma-suede-classic-xxi-black', '8', '4', 'Unisex', '2100000', '2400000', '1300000', '13', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers', 'Puma Suede Classic da lộn mịn đẹp, kiểu dáng retro tối giản chuẩn phong cách hip-hop từ thập niên 80.', '0', '0', '520', '150', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('26', 'NB-2002R-RAIN', 'New Balance 2002R Protection Pack Rain Cloud', 'new-balance-2002r-protection-pack-rain-cloud', '8', '5', 'Nam', '4500000', '5200000', '2800000', '13', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', 'Siêu phẩm New Balance 2002R thiết kế dải da rách đan lớp Protection Pack cá tính, đế N-ergy giảm xóc vượt trội.', '1', '1', '2403', '580', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('27', 'NB-1906R-SLV', 'New Balance 1906R Metallic Silver', 'new-balance-1906r-metallic-silver', '8', '5', 'Nam', '3850000', '4400000', '2400000', '13', 'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'New Balance 1906R mang đậm tinh thần retro-futuristic những năm 2000, bộ đệm N-ergy kết hợp đệm gót ABZORB.', '0', '1', '1750', '410', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('28', 'NK-AF1-WMNS', 'Nike Air Force 1 \'07 Women White', 'nike-air-force-1-07-women-white', '9', '1', 'Nữ', '2929000', '3500000', '1800000', '16', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png', 'Nike Air Force 1 phiên bản dành riêng cho nữ với thiết kế thanh thoát, màu trắng tinh khôi cực kỳ dễ phối đồ.', '0', '0', '1950', '490', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('29', 'AD-SAMBA-ROSE', 'Adidas Samba OG Women Rose', 'adidas-samba-og-women-rose', '9', '2', 'Nữ', '2800000', '3200000', '1700000', '13', 'uploads/1785904336_angle1_images (3).jpg', 'Adidas Samba OG phối màu hồng pastel nữ tính kết hợp cùng sọc da trắng ngọt ngào, hot trend thời trang phái đẹp.', '1', '1', '2601', '610', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('30', 'AD-CAMPUS-00S', 'Adidas Campus 00s Core Black Women', 'adidas-campus-00s-core-black-women', '9', '2', 'Nữ', '2600000', '3000000', '1600000', '13', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg', 'Adidas Campus 00s phom dáng béo mập skate độc đáo, dây giày bản to thời thượng tạo dấu ấn riêng cho các bạn nữ.', '0', '1', '1450', '330', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('31', 'NB-530-SLV', 'New Balance 530 Metallic Silver', 'new-balance-530-metallic-silver', '9', '5', 'Nữ', '2650000', '2900000', '1600000', '9', 'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'New Balance 530 Metallic Silver mang đến vẻ đẹp hoài cổ năng động, chất liệu lưới thoáng khí đệm ABZORB siêu nhẹ.', '0', '1', '780', '210', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('32', 'MLB-LINER-WHT', 'MLB Korea Chunky Liner Mid White Navy', 'mlb-korea-chunky-liner-mid-white-navy', '9', '10', 'Nữ', '3200000', '3700000', '2000000', '14', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', 'MLB Chunky Liner thiết kế đường viền hiện đại, tôn dáng cao ráo cho các quý cô cá tính.', '0', '1', '1250', '290', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('33', 'PM-PALERMO-PNK', 'Puma Palermo Leather Pink White', 'puma-palermo-leather-pink-white', '9', '4', 'Nữ', '2250000', '2600000', '1350000', '13', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers', 'Puma Palermo phối màu hồng pastel ngọt ngào, chất da cao cấp sang trọng cùng đế gum cá tính.', '0', '1', '560', '160', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('34', 'AS-GT2160-SLV', 'Asics GT-2160 Cream Pure Silver', 'asics-gt-2160-cream-pure-silver', '9', '11', 'Nữ', '3300000', '3800000', '2000000', '13', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', 'Asics GT-2160 tone màu kem bạc thời thượng, cấu trúc GEL lót đệm siêu êm ái khi di chuyển liên tục.', '0', '1', '980', '240', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('35', 'NK-AIRMAX90-PNK', 'Nike Air Max 90 Futura Pink', 'nike-air-max-90-futura-pink', '10', '1', 'Nữ', '3200000', '3800000', '1900000', '16', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png', 'Air Max 90 Futura biến tấu hiện đại của dòng Air Max 90 kinh điển với sắc hồng ngọt ngào và đệm Air êm ái.', '0', '1', '1150', '270', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('36', 'AD-ULTRABOOST-W', 'Adidas Ultraboost Light Women White', 'adidas-ultraboost-light-women-white', '10', '2', 'Nữ', '3600000', '4300000', '2200000', '16', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg', 'Giày chạy bộ phái đẹp Ultraboost Light cực kỳ mượt mà, ôm chân chuẩn xác và hỗ trợ vận động tối ưu.', '0', '1', '520', '130', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('37', 'AS-KAYANO30-W', 'Asics Gel-Kayano 30 Women White', 'asics-gel-kayano-30-women-white', '10', '11', 'Nữ', '4100000', '4700000', '2500000', '13', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'Asics Gel-Kayano 30 tích hợp hệ thống 4D GUIDANCE SYSTEM bảo vệ bàn chân chống lật cổ chân khi tập luyện.', '0', '1', '890', '210', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('38', 'ON-TILT-W', 'On Running Cloudtilt Women Quartz', 'on-running-cloudtilt-women-quartz', '10', '15', 'Nữ', '4500000', '5100000', '2800000', '12', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', 'On Running Cloudtilt với thiết kế siêu nhẹ, xỏ chân nhanh chóng không cần buộc dây, cảm giác êm ái tuyệt vời.', '0', '1', '740', '180', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('39', 'SK-DLITES-WHT', 'Skechers D\'Lites Fresh Start', 'skechers-dlites-fresh-start', '10', '12', 'Nữ', '1850000', '2200000', '1000000', '16', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', 'Skechers D\'Lites Chunky năng động với lót giày Air-Cooled Memory Foam siêu êm, giảm áp lực tối đa cho bàn chân.', '0', '1', '620', '160', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('40', 'CV-RUNSTAR-W', 'Converse Run Star Hike High Black', 'converse-run-star-hike-high-black', '11', '6', 'Nữ', '2600000', '3000000', '1500000', '13', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg', 'Converse Run Star Hike đế ziczac cực ngầu, giúp hack chiều cao 5cm hiệu quả cho các cô nàng cá tính.', '0', '1', '680', '190', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('41', 'CV-ALLSTAR-MOVE', 'Converse Chuck Taylor All Star Move Platform', 'converse-chuck-taylor-all-star-move-platform', '11', '6', 'Nữ', '2100000', '2400000', '1200000', '13', 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg', 'Dòng All Star Move siêu nhẹ với đế bánh mì nâng chiều cao uyển chuyển, năng động năng suất cả ngày dài.', '0', '1', '840', '220', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('42', 'AD-GAZELLE-GRN', 'Adidas Gazelle Bold Green Women', 'adidas-gazelle-bold-green-women', '11', '2', 'Nữ', '2200000', '3200000', '1300000', '31', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg', 'Gazelle Bold thiết kế 3 tầng đế cao cá tính, tông xanh lá lục bảo retro cực kỳ thời trang và nổi bật.', '1', '1', '2100', '520', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('43', 'AD-SPEZIAL-BLU', 'Adidas Handball Spezial Blue Women', 'adidas-handball-spezial-blue-women', '11', '2', 'Nữ', '2750000', '3200000', '1600000', '14', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg', 'Adidas Handball Spezial chất da lộn xanh denim cổ điển, biểu tượng thời trang Terracewear mốt nhất hiện nay.', '0', '1', '1850', '450', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('44', 'VN-AUTHENTIC-RED', 'Vans Authentic Core Classics Red', 'vans-authentic-core-classics-red', '11', '7', 'Nữ', '1450000', '1700000', '800000', '15', 'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$', 'Vans Authentic sắc đỏ rực rỡ, phom dáng cổ thấp tối giản tinh tế dễ dàng mix&match trang phục dạo phố.', '0', '0', '480', '130', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('45', 'PM-RSX-EFEKT', 'Puma RS-X Efekt Archive White', 'puma-rs-x-efekt-archive-white', '11', '4', 'Nữ', '2700000', '3200000', '1600000', '16', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers', 'Puma RS-X Efekt dòng Chunky thiết kế tương lai với các mảng phối da ấn tượng cùng đế đệm Running System.', '0', '1', '621', '170', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('46', 'NK-BENASSI', 'Nike Benassi JDI Slide Black', 'nike-benassi-jdi-slide-black', '12', '1', 'Nam', '790000', '950000', '450000', '17', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png', 'Dép quai ngang Nike Benassi JDI quai lót bông siêu mềm, đế xốp Phylon êm ái chống trơn trượt.', '0', '0', '520', '240', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('47', 'AD-ADILETTE', 'Adidas Adilette Comfort Slide', 'adidas-adilette-comfort-slide', '12', '2', 'Nam', '850000', '1000000', '480000', '15', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg', 'Dép Adidas Adilette Comfort trang bị lót lòng đệm Cloudfoam cực êm như mát xa lòng bàn chân.', '0', '1', '430', '190', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('48', 'PM-LEADCAT', 'Puma Leadcat 2.0 Slide Black', 'puma-leadcat-2-slide-black', '12', '4', 'Nam', '650000', '800000', '380000', '19', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides', 'Dép quai ngang Puma Leadcat 2.0 phom dáng thể thao tối giản, chất liệu EVA cao cấp siêu nhẹ.', '0', '0', '280', '110', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('49', 'CRC-MELLOW-SLD', 'Crocs Mellow Recovery Slide Black', 'crocs-mellow-recovery-slide-black', '12', '9', 'Nam', '1350000', '1600000', '800000', '16', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', 'Dép Crocs Mellow dòng đệm nhung LiteRide nhún êm sâu giúp đôi chân thư giãn tức thì sau giờ tập thể thao.', '0', '1', '891', '230', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('50', 'YZY-SLIDE-BONE', 'Yeezy Slide Bone White', 'yeezy-slide-bone-white', '12', '13', 'Unisex', '3200000', '3800000', '2000000', '16', 'uploads/1785904121_angle1_yeezy-slide-bone-800x650.jpg', 'Yeezy Slide Bone đúc nguyên khối bọt EVA mềm mại, thiết kế răng cưa tối giản hiện đại nhất giới thời trang.', '1', '0', '3106', '720', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('51', 'MLB-SLIDER-MONO', 'MLB Korea Chunky Slider Monogram', 'mlb-korea-chunky-slider-monogram', '12', '10', 'Nam', '1650000', '1950000', '950000', '15', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', 'Dép quai ngang MLB Korea hoa văn Monogram cao cấp, đế cao 4cm tôn dáng và thời trang đỉnh cao.', '0', '1', '1152', '280', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('52', 'NK-CANYON', 'Nike Canyon Sandal Black', 'nike-canyon-sandal-black', '13', '1', 'Nam', '1950000', '2300000', '1100000', '15', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png', 'Sandal dã ngoại Nike Canyon quai dán linh hoạt, đế gai hãm ma sát cao thích hợp mọi hoạt động outdoor năng động.', '0', '1', '310', '85', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('53', 'BK-ARIZONA-BLK', 'Birkenstock Arizona EVA Black', 'birkenstock-arizona-eva-black', '13', '8', 'Unisex', '1250000', '1500000', '750000', '17', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', 'Birkenstock Arizona EVA 2 quai màu đen đúc nguyên khối chống nước 100%, đệm chân Ergonomic uốn lượn thoải mái.', '0', '1', '950', '260', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('54', 'BK-BOSTON-TAUPE', 'Birkenstock Boston Clog Suede Taupe', 'birkenstock-boston-clog-suede-taupe', '13', '8', 'Unisex', '3800000', '4400000', '2400000', '14', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg', 'Birkenstock Boston Clog bọc da lộn Taupe sang trọng, đế bần Cork tự nhiên chuẩn mực phong cách Quiet Luxury.', '1', '1', '2100', '490', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('55', 'CRC-ECHO-BLK', 'Crocs Echo Clog All Black', 'crocs-echo-clog-all-black', '13', '9', 'Nam', '1950000', '2300000', '1150000', '15', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', 'Crocs Echo Clog phong cách Streetwear gai góc cá tính, quai đeo gót êm ái cùng đệm Licker-in LiteRide.', '0', '1', '1420', '330', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('56', 'AD-HYDROTERRA', 'Adidas Terrex Hydroterra Sandal', 'adidas-terrex-hydroterra-sandal', '13', '2', 'Nam', '1800000', '2200000', '1000000', '18', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', 'Sandal dã ngoại Adidas Terrex đế cao su Traxion siêu bám đường ướt, chất liệu dây đai tái chế bảo vệ môi trường.', '0', '1', '411', '110', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('57', 'NK-VICTORI-W', 'Nike Victori One Slide Women', 'nike-victori-one-slide-women', '14', '1', 'Nữ', '750000', '900000', '420000', '17', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png', 'Dép quai ngang nữ Nike Victori One lót bọt đệm êm mềm mới, quai quấn ôm sát mu bàn chân tạo sự thoải mái dịu nhẹ.', '0', '1', '454', '192', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('58', 'AD-ADILETTE-W', 'Adidas Adilette Aqua Slide Women', 'adidas-adilette-aqua-slide-women', '14', '2', 'Nữ', '650000', '800000', '380000', '19', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', 'Dép đúc Adidas Adilette Aqua nhanh khô chống nước, lý tưởng đi phòng tập, đi biển hay mang ở nhà cực kỳ tiện lợi.', '0', '0', '381', '150', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('59', 'CRC-CLASSIC-WHT', 'Crocs Classic Clog White', 'crocs-classic-clog-white', '14', '9', 'Unisex', '1150000', '1400000', '650000', '18', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', 'Crocs Classic Clog màu trắng huyền thoại, dễ dàng gắn sticker Jibbitz thể hiện cá tính riêng độc đáo.', '0', '1', '1890', '520', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('60', 'YZY-FOAM-ONYX', 'Yeezy Foam Runner Onyx', 'yeezy-foam-runner-onyx', '14', '13', 'Unisex', '3500000', '4200000', '2200000', '17', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg', 'Yeezy Foam Runner thiết kế điêu khắc tương lai bằng bọt tảo biển EVA siêu thoáng khí và độc lạ nhất hành tinh.', '1', '1', '2610', '641', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('61', 'SK-ARCHFIT-SND', 'Skechers Arch Fit Horizon Sandal', 'skechers-arch-fit-horizon-sandal', '14', '12', 'Nữ', '1450000', '1750000', '800000', '17', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', 'Dép nữ Skechers Arch Fit được thiết kế theo phom bác sĩ bàn chân chứng nhận, hỗ trợ lòm chân giảm mỏi tối ưu.', '0', '1', '380', '110', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('62', 'BK-ARIZONA-WHT', 'Birkenstock Arizona EVA White', 'birkenstock-arizona-eva-white', '15', '8', 'Nữ', '1200000', '1500000', '700000', '20', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', 'Birkenstock Arizona EVA tone trắng thanh lịch, chất siêu nhẹ giặt rửa thoải mái và năng động trong mọi chuyến đi.', '0', '1', '880', '240', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('63', 'BK-MAYARI-SLV', 'Birkenstock Mayari Birko-Flor Graceful', 'birkenstock-mayari-birko-flor-graceful', '15', '8', 'Nữ', '2400000', '2800000', '1400000', '14', 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg', 'Birkenstock Mayari xỏ ngón quai thanh mảnh duyên dáng, lót bần Cork nâng đỡ lòng bàn chân dịu dàng.', '0', '1', '620', '170', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('64', 'CRC-MEGACRUSH', 'Crocs Mega Crush Clog Bone', 'crocs-mega-crush-clog-bone', '15', '9', 'Nữ', '2450000', '2900000', '1500000', '16', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', 'Crocs Mega Crush đế nâng cao 7cm cực kỳ ấn tượng, chi tiết TPU quanh đế cá tính và quyến rũ cho phái đẹp.', '0', '1', '1753', '431', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('65', 'NK-OFFCOURT-ADJ', 'Nike OffCourt Adjust Slide Women', 'nike-offcourt-adjust-slide-women', '15', '1', 'Nữ', '1100000', '1350000', '650000', '19', 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png', 'Dép Nike OffCourt Adjust có quai dán điều chỉnh độ rộng linh hoạt, lót Revive Foam 2 lớp vô cùng thoải mái.', '0', '1', '292', '90', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('66', 'MLB-SANDAL-MONO', 'MLB Korea Chunky Sandal Monogram', 'mlb-korea-chunky-sandal-monogram', '15', '10', 'Nữ', '2350000', '2800000', '1300000', '16', 'uploads/1785903879_angle1_images.jpg', 'Sandal nữ MLB Korea đệm quai êm mềm, đế răng cưa cao 5cm tôn nét sang chảnh và hiện đại cho phái nữ.', '0', '1', '922', '251', '1', '2026-08-04 18:51:36');
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES ('70', 'JD-4-BRED5', 'Yeezy Slide Bone White 5', 'yeezy-slide-bone-white-5', '3', '2', 'Unisex', '180000', '200000', '200000', '10', 'uploads/1785907222_angle1_images.png', '', '0', '1', '1', '0', '1', '2026-08-05 12:20:22');

-- --------------------------------------------------------

-- Table structure for table `shipping_provinces`
--

DROP TABLE IF EXISTS `shipping_provinces`;
CREATE TABLE `shipping_provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `province_name` varchar(100) NOT NULL,
  `distance_km` int(11) NOT NULL DEFAULT 100,
  `shipping_fee` decimal(12,0) NOT NULL DEFAULT 30000,
  `estimated_days` varchar(50) DEFAULT '2-4 ngày',
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `province_name` (`province_name`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `shipping_provinces`
--

INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('1', 'Hà Nội', '1760', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('2', 'TP. Hồ Chí Minh', '135', '17000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('3', 'Đà Nẵng', '950', '29000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('4', 'Vĩnh Long', '10', '15000', 'Nội thành (1 ngày)', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('5', 'Cần Thơ', '35', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('6', 'Bình Dương', '160', '17000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('7', 'Đồng Nai', '170', '18000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('8', 'Hải Phòng', '1800', '42000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('9', 'Tỉnh/Thành Khác', '1000', '30000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('10', 'An Giang', '110', '17000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('11', 'Bà Rịa - Vũng Tàu', '210', '18000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('12', 'Bắc Giang', '1820', '42000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('13', 'Bắc Kạn', '1930', '44000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('14', 'Bạc Liêu', '135', '17000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('15', 'Bắc Ninh', '1790', '42000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('16', 'Bến Tre', '60', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('17', 'Bình Định', '720', '26000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('18', 'Bình Phước', '240', '19000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('19', 'Bình Thuận', '320', '20000', '2-3 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('20', 'Cà Mau', '200', '18000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('21', 'Cao Bằng', '2030', '45000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('22', 'Đắk Lắk', '500', '23000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('23', 'Đắk Nông', '420', '21000', '2-3 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('24', 'Điện Biên', '2140', '47000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('25', 'Đồng Tháp', '55', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('26', 'Gia Lai', '620', '24000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('27', 'Hà Giang', '2060', '46000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('28', 'Hà Nam', '1720', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('29', 'Hà Tĩnh', '1370', '36000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('30', 'Hải Dương', '1750', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('31', 'Hậu Giang', '70', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('32', 'Hòa Bình', '1730', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('33', 'Hưng Yên', '1740', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('34', 'Khánh Hòa', '520', '23000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('35', 'Kiên Giang', '160', '17000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('36', 'Kon Tum', '670', '25000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('37', 'Lai Châu', '2180', '48000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('38', 'Lâm Đồng', '380', '21000', '2-3 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('39', 'Lạng Sơn', '1910', '44000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('40', 'Lào Cai', '2070', '46000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('41', 'Long An', '85', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('42', 'Nam Định', '1700', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('43', 'Nghệ An', '1450', '37000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('44', 'Ninh Bình', '1670', '40000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('45', 'Ninh Thuận', '410', '21000', '2-3 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('46', 'Phú Thọ', '1840', '43000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('47', 'Phú Yên', '630', '24000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('48', 'Quảng Bình', '1220', '33000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('49', 'Quảng Nam', '910', '29000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('50', 'Quảng Ngãi', '820', '27000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('51', 'Quảng Ninh', '1880', '43000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('52', 'Quảng Trị', '1120', '32000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('53', 'Sóc Trăng', '90', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('54', 'Sơn La', '1920', '44000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('55', 'Tây Ninh', '180', '18000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('56', 'Thái Bình', '1730', '41000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('57', 'Thái Nguyên', '1840', '43000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('58', 'Thanh Hóa', '1590', '39000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('59', 'Thừa Thiên Huế', '1050', '31000', '2-4 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('60', 'Tiền Giang', '45', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('61', 'Trà Vinh', '50', '16000', '1-2 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('62', 'Tuyên Quang', '1890', '43000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('63', 'Vĩnh Phúc', '1810', '42000', '3-5 ngày', '1');
INSERT INTO `shipping_provinces` (`id`, `province_name`, `distance_km`, `shipping_fee`, `estimated_days`, `status`) VALUES ('64', 'Yên Bái', '1910', '44000', '3-5 ngày', '1');

-- --------------------------------------------------------

-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('1', 'site_name', 'SHOES STORE', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('2', 'site_logo', '', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('3', 'site_description', 'Thương hiệu Sneaker hàng đầu mang đến trải nghiệm thời trang dịu nhẹ, thanh lịch và chất lượng cam kết chính hãng.', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('4', 'site_keywords', 'giày sneaker, giày chính hãng, nike, adidas, jordan, dép nam nữ', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('5', 'contact_address', 'TP. Vĩnh Long, Việt Nam', 'contact');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('6', 'contact_hotline', '0901.234.567', 'contact');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('7', 'contact_email', 'support@shoesstore.vn', 'contact');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('8', 'bank_id', 'ACB', 'payment');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('9', 'bank_account', '0123456789', 'payment');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('10', 'bank_name', 'SHOP OWNER', 'payment');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('11', 'footer_copyright', '© 2026 SHOES STORE. Thiết kế bởi Trang Sỉ Giàu.', 'footer');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('12', 'hero_title', 'BỨT PHÁ PHONG CÁCH', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('13', 'hero_subtitle', 'Siêu Phẩm Sneaker 2026', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('14', 'hero_image', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('15', 'hero_button_text', 'MUA SẮM NGAY', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('16', 'hero_button_link', 'all-products.php', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('17', 'section_hot_title', '🔥 SẢN PHẨM NỔI BẬT', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('18', 'section_new_title', '✨ HÀNG MỚI VỀ', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('19', 'section_sale_title', '💰 ĐANG GIẢM GIÁ SỐC', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('20', 'section_brand_title', '🏆 THƯƠNG HIỆU NỔI BẬT', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('21', 'section_voucher_title', '🎟️ MÃ GIẢM GIÁ KHUYẾN MÃI', 'cms');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('22', 'service_1_icon', 'fa-solid fa-truck-fast', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('23', 'service_1_title', 'Miễn Phí Vận Chuyển', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('24', 'service_1_desc', 'Cho đơn hàng từ 500.000đ', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('25', 'service_2_icon', 'fa-solid fa-shield-halved', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('26', 'service_2_title', '100% Chính Hãng', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('27', 'service_2_desc', 'Cam kết hàng Authentic', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('28', 'service_3_icon', 'fa-solid fa-rotate-left', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('29', 'service_3_title', 'Đổi Trả 30 Ngày', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('30', 'service_3_desc', 'Miễn phí nếu lỗi sản phẩm', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('31', 'service_4_icon', 'fa-solid fa-headset', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('32', 'service_4_title', 'Hỗ Trợ 24/7', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('33', 'service_4_desc', 'Tư vấn mọi lúc mọi nơi', 'services');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('34', 'ship_fee_mekong', '18000', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('35', 'ship_fee_southeast', '22000', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('36', 'ship_fee_central', '30000', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('37', 'ship_fee_north', '35000', 'general');
INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES ('38', 'free_ship_threshold', '500000', 'general');

-- --------------------------------------------------------

-- Table structure for table `social_links`
--

DROP TABLE IF EXISTS `social_links`;
CREATE TABLE `social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform`, `url`, `icon`, `sort_order`, `status`) VALUES ('1', 'Facebook', 'https://facebook.com/shoesstore', 'fa-brands fa-facebook-f', '1', '1');
INSERT INTO `social_links` (`id`, `platform`, `url`, `icon`, `sort_order`, `status`) VALUES ('2', 'Instagram', 'https://instagram.com/shoesstore', 'fa-brands fa-instagram', '2', '1');
INSERT INTO `social_links` (`id`, `platform`, `url`, `icon`, `sort_order`, `status`) VALUES ('3', 'TikTok', 'https://tiktok.com/@shoesstore', 'fa-brands fa-tiktok', '3', '1');
INSERT INTO `social_links` (`id`, `platform`, `url`, `icon`, `sort_order`, `status`) VALUES ('4', 'Zalo', 'https://zalo.me/0901234567', 'fa-solid fa-comment-dots', '4', '1');
INSERT INTO `social_links` (`id`, `platform`, `url`, `icon`, `sort_order`, `status`) VALUES ('5', 'YouTube', 'https://youtube.com/@shoesstore', 'fa-brands fa-youtube', '5', '1');

-- --------------------------------------------------------

-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `address_detail` varchar(500) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `province_id` (`province_id`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_addresses_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `shipping_provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('1', '3', 'Trần Văn Khách', '0912345678', '4', '123 Đường Phạm Hùng, Phường 1, TP. Vĩnh Long', '1', '2026-08-04 18:50:22');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('2', '3', 'Trần Văn Khách', '0912345678', '2', '456 Đường Nguyễn Huệ, Quận 1, TP. HCM', '0', '2026-08-04 18:50:22');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('3', '4', 'Nguyễn Thị Lan', '0933456789', '1', '789 Đường Láng, Đống Đa, Hà Nội', '1', '2026-08-04 18:50:22');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('4', '1', 'Quản Trị Viên', '0901234567', '6', 'jhuoh', '0', '2026-08-04 18:59:51');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('5', '7', 'Admin', '0901234567', '5', 'sdsdsadsd', '0', '2026-08-05 11:35:14');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('6', '5', 'Giàu AI', '0901234569', '5', 'ewtgdsgsg', '0', '2026-08-05 13:23:17');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('7', '7', 'Giàu', '0901234569', '25', 'Sa Đéc, Phường Sa Đéc, Tỉnh Đồng Tháp, 81000, Việt Nam', '0', '2026-08-08 18:35:09');
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES ('8', '7', 'Quản Trị Viên', '0901234569', '4', 'Trung tâm Công tác xã hội Vĩnh Long, Đường Trần Phú, Phường Thanh Đức, Tỉnh Vĩnh Long, 85108, Việt Nam', '0', '2026-08-08 19:12:50');

-- --------------------------------------------------------

-- Table structure for table `user_vouchers`
--

DROP TABLE IF EXISTS `user_vouchers`;
CREATE TABLE `user_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `voucher_id` (`voucher_id`),
  CONSTRAINT `user_vouchers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_vouchers_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_vouchers`
--

INSERT INTO `user_vouchers` (`id`, `user_id`, `voucher_id`, `used_at`) VALUES ('1', '1', '6', '2026-08-04 18:59:51');
INSERT INTO `user_vouchers` (`id`, `user_id`, `voucher_id`, `used_at`) VALUES ('2', '7', '1', '2026-08-05 11:35:14');
INSERT INTO `user_vouchers` (`id`, `user_id`, `voucher_id`, `used_at`) VALUES ('3', '5', '4', '2026-08-05 13:23:17');
INSERT INTO `user_vouchers` (`id`, `user_id`, `voucher_id`, `used_at`) VALUES ('4', '7', '2', '2026-08-08 19:12:50');

-- --------------------------------------------------------

-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `citizen_id` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `auth_provider` varchar(50) DEFAULT 'local',
  `is_email_verified` tinyint(1) DEFAULT 0,
  `is_phone_verified` tinyint(1) DEFAULT 0,
  `role` enum('admin','staff','customer') DEFAULT 'customer',
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `total_commission` decimal(12,0) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Quản Trị Viên', 'admin@shoes.com', '0901234567', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '1995-01-15', 'local', '1', '0', 'admin', '0.00', '0', '1', '2026-08-04 18:50:22', '2026-08-04 18:50:22');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Nhân Viên Bán Hàng', 'staff@shoes.com', '0907654321', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '1998-06-20', 'local', '1', '0', 'staff', '0.00', '0', '1', '2026-08-04 18:50:22', '2026-08-04 18:50:22');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Trần Văn Khách', 'khachhang@gmail.com', '0912345678', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '2000-03-10', 'local', '1', '0', 'staff', '0.00', '0', '1', '2026-08-04 18:50:22', '2026-08-05 12:09:22');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Nguyễn Thị Lan', 'lanng@gmail.com', '0933456789', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '1999-11-05', 'local', '1', '0', 'customer', '0.00', '0', '1', '2026-08-04 18:50:22', '2026-08-04 18:50:22');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Giàu AI', 'customer_demo@shoesstore.vn', '0901234569', NULL, NULL, NULL, '107566788099454876210', 'https://lh3.googleusercontent.com/a/ACg8ocLXlVAMY8jWJWrQuNarOnFTn_K2h_I_d6uNIKN1Xv5rbVqAfQ=s96-c', '2026-08-19', 'google', '1', '0', 'customer', '0.00', '0', '1', '2026-08-04 19:51:43', '2026-08-04 19:51:43');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('6', 'Trang Sỉ Giàu', 'user_demo@shoesstore.vn', '0901234570', NULL, NULL, NULL, '112102372721493483637', 'https://lh3.googleusercontent.com/a/ACg8ocJ4wyagiCGTza35inFXqspcHBfVLm0_F46glCmLlYgYdLUpMg=s96-c', '2026-07-31', 'google', '1', '0', 'customer', '0.00', '0', '1', '2026-08-04 19:53:20', '2026-08-04 19:53:20');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('7', 'Admin', 'admin_demo@shoesstore.vn', '0901234567', NULL, NULL, '$2y$10$HGyYCU4nUtyLaP8xhQFmd.jcJNgYcEyMNhnx5hpYKfw/nvg2F2iMS', NULL, 'uploads/avatars/avatar_7_1785903718.jpg', '0000-00-00', 'email_otp', '1', '0', 'admin', '0.00', '0', '1', '2026-08-05 11:18:17', '2026-08-05 11:21:58');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('8', 'Nguyễn Văn C', 'nhanvien@gmail.com', '0901234568', NULL, NULL, '$2y$10$V5SI/ii05C/71/UwkEBwIuTrw6ENoqOR5ggtSC11r220C7EIdA1Eu', NULL, 'assets/images/default-avatar.png', NULL, 'local', '0', '0', 'staff', '0.00', '0', '1', '2026-08-05 12:13:47', '2026-08-05 12:13:47');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('10', 'Khách Hàng Test', 'testemp1785907057@shoes.vn', '0983464845', '079200003873', '123 Đường Test, TP.HCM', '$2y$10$9/hmoRrT2NwjZ5aJ0VSlveoXTJ1Y7TqZfBwLaBsrKpyFjXtVRYej.', NULL, 'assets/images/default-avatar.png', NULL, 'local', '0', '0', '', '0.00', '0', '1', '2026-08-05 12:17:37', '2026-08-05 12:17:37');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('15', 'Nguyễn Văn H', 'khachhang1@gmail.com', '', '', 'Vĩnh Long', '$2y$10$VNtWVPvziuhkpwLb.bwTkOW8PxKrhCnnq2PJwnHM7.QYpCPTz6PN.', NULL, 'assets/images/default-avatar.png', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-05 12:20:40', '2026-08-05 12:20:40');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('16', 'Nguyễn Văn Be', 'nhanvien1@gmail.com', '0901234571', '079200012345', 'Vĩnh Long', '$2y$10$HRzWKBE6V3km5yZWxY1/L.fmMaGdBQvW2ZjMhvOm2CkPtwRqKkq8C', NULL, 'assets/images/default-avatar.png', NULL, 'local', '0', '0', 'admin', '0.00', '0', '1', '2026-08-05 12:21:07', '2026-08-05 15:41:55');
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `citizen_id`, `address`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES ('17', 'Nguyễn Văn Aa', 'nhanvienn@gmail.com', '0901234572', '', 'Vĩnh Long', '$2y$10$KcSe.jQF9KhNx/6EFBqVeugHhoCNuLfy.CMEqPQ80PHhACo9wh3k.', NULL, 'assets/images/default-avatar.png', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-05 13:24:21', '2026-08-08 19:28:36');

-- --------------------------------------------------------

-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `discount_type` enum('fixed','percent','freeship') NOT NULL,
  `discount_value` decimal(12,0) NOT NULL,
  `min_order_value` decimal(12,0) DEFAULT 0,
  `max_discount` decimal(12,0) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT 100,
  `used_count` int(11) DEFAULT 0,
  `per_user_limit` int(11) DEFAULT 1,
  `event_type` enum('general','holiday','new_user') DEFAULT 'general',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('1', NULL, 'WELCOME50', 'Chào mừng khách hàng mới - Giảm 50K', 'fixed', '50000', '500000', '50000', '100', '13', '1', 'new_user', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('2', '1', 'NIKE15', 'Ưu đãi Nike - Giảm 15% cho sản phẩm Nike', 'percent', '15', '1000000', '300000', '50', '5', '1', 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('3', '2', 'ADIDAS100K', 'Ưu đãi Adidas - Giảm 100K cho sản phẩm Adidas', 'fixed', '100000', '1200000', '100000', '60', '8', '1', 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('4', NULL, 'FREESHIP', 'Miễn phí vận chuyển toàn quốc', 'freeship', '35000', '300000', '35000', '200', '35', '3', 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('5', NULL, 'SUMMER2026', 'Mùa hè sôi động - Giảm 15%', 'percent', '15', '2000000', '500000', '80', '10', '1', 'holiday', '2026-06-01 00:00:00', '2026-08-31 23:59:59', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('6', '3', 'JORDAN200K', 'Bóng rổ Jordan - Giảm 200K cho giày Jordan', 'fixed', '200000', '2500000', '200000', '30', '3', '1', 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('7', NULL, 'TEST455', 'Mã Giảm Giá Test', 'fixed', '50000', '100000', '0', '100', '0', '1', 'general', '2026-08-05 12:00:00', '2026-08-31 23:59:00', '1');
INSERT INTO `vouchers` (`id`, `brand_id`, `code`, `title`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES ('10', NULL, 'FSDFSDF', 'ưewewe', 'fixed', '50000', '200000', '0', '100', '0', '1', 'general', '2026-08-05 07:19:48', '2026-09-04 07:19:48', '1');

-- --------------------------------------------------------

-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
