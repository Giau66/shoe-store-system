-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2026 at 11:13 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_shoe`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `button_text` varchar(100) DEFAULT 'Mua Ngay',
  `position` enum('hero','promo_left','promo_right','sidebar') DEFAULT 'hero',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `link_url`, `button_text`, `position`, `sort_order`, `status`, `created_at`) VALUES
(1, 'BỨT PHÁ PHONG CÁCH 2026', 'Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi giảm đến 33% hôm nay!', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200', 'all-products.php', 'MUA SẮM NGAY', 'hero', 1, 1, '2026-07-29 07:40:31'),
(2, 'Bộ Sưu Tập Mùa Hè', 'Dép & Sandal nhẹ nhàng thoải mái cho mọi chuyến đi', 'https://images.unsplash.com/photo-1603487742131-4160ec999306?q=80&w=800', 'all-products.php?type=dep', 'Xem Ngay', 'promo_left', 1, 1, '2026-07-29 07:40:31'),
(3, 'Jordan Collection', 'Bộ sưu tập Air Jordan chính hãng dành cho tín đồ Sneaker', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', 'all-products.php?brand_id=3', 'Khám Phá', 'promo_right', 1, 1, '2026-07-29 07:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES
(1, 'Nike', 'nike', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/800px-Logo_NIKE.svg.png', 'Thương hiệu thể thao hàng đầu thế giới từ Mỹ, nổi tiếng với công nghệ Air và thiết kế iconic.', 1, '2026-07-29 07:40:30'),
(2, 'Adidas', 'adidas', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/800px-Adidas_Logo.svg.png', 'Thương hiệu thể thao Đức với 3 sọc huyền thoại, tiên phong trong công nghệ Boost.', 1, '2026-07-29 07:40:30'),
(3, 'Jordan', 'jordan', 'https://upload.wikimedia.org/wikipedia/en/thumb/3/37/Jumpman_logo.svg/800px-Jumpman_logo.svg.png', 'Thương hiệu giày bóng rổ huyền thoại mang tên Michael Jordan.', 1, '2026-07-29 07:40:30'),
(4, 'Puma', 'puma', 'https://upload.wikimedia.org/wikipedia/en/thumb/d/da/Puma_complete_logo.svg/800px-Puma_complete_logo.svg.png', 'Thương hiệu thể thao Đức với phong cách trẻ trung, năng động.', 1, '2026-07-29 07:40:30'),
(5, 'New Balance', 'new-balance', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ea/New_Balance_logo.svg/800px-New_Balance_logo.svg.png', 'Thương hiệu Mỹ nổi tiếng với sự thoải mái và chất lượng đỉnh cao.', 1, '2026-07-29 07:40:30'),
(6, 'Converse', 'converse', 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/Converse_logo.svg/800px-Converse_logo.svg.png', 'Biểu tượng văn hóa đường phố với dòng Chuck Taylor huyền thoại.', 1, '2026-07-29 07:40:30'),
(7, 'Vans', 'vans', 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Vans-logo.svg/800px-Vans-logo.svg.png', 'Thương hiệu skate đình đám với slogan \"Off The Wall\".', 1, '2026-07-29 07:40:30'),
(8, 'Birkenstock', 'birkenstock', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/Birkenstock_logo.svg/800px-Birkenstock_logo.svg.png', 'Thương hiệu dép Đức cao cấp với đế cork thoải mái.', 1, '2026-07-29 07:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` enum('giay','dep') DEFAULT 'giay',
  `gender` enum('nam','nu','unisex') DEFAULT 'unisex',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `type`, `gender`, `sort_order`, `status`, `created_at`) VALUES
(1, NULL, 'Giày Nam', 'giay-nam', NULL, 'giay', 'nam', 1, 1, '2026-07-29 07:40:30'),
(2, NULL, 'Giày Nữ', 'giay-nu', NULL, 'giay', 'nu', 2, 1, '2026-07-29 07:40:30'),
(3, NULL, 'Dép Nam', 'dep-nam', NULL, 'dep', 'nam', 3, 1, '2026-07-29 07:40:30'),
(4, NULL, 'Dép Nữ', 'dep-nu', NULL, 'dep', 'nu', 4, 1, '2026-07-29 07:40:30'),
(5, 1, 'Sneaker Nam', 'sneaker-nam', NULL, 'giay', 'nam', 1, 1, '2026-07-29 07:40:30'),
(6, 1, 'Giày Chạy Bộ Nam', 'giay-chay-bo-nam', NULL, 'giay', 'nam', 2, 1, '2026-07-29 07:40:30'),
(7, 1, 'Giày Bóng Rổ', 'giay-bong-ro', NULL, 'giay', 'nam', 3, 1, '2026-07-29 07:40:30'),
(8, 1, 'Giày Thời Trang Nam', 'giay-thoi-trang-nam', NULL, 'giay', 'nam', 4, 1, '2026-07-29 07:40:30'),
(9, 2, 'Sneaker Nữ', 'sneaker-nu', NULL, 'giay', 'nu', 1, 1, '2026-07-29 07:40:30'),
(10, 2, 'Giày Chạy Bộ Nữ', 'giay-chay-bo-nu', NULL, 'giay', 'nu', 2, 1, '2026-07-29 07:40:30'),
(11, 2, 'Giày Thời Trang Nữ', 'giay-thoi-trang-nu', NULL, 'giay', 'nu', 3, 1, '2026-07-29 07:40:30'),
(12, 3, 'Dép Quai Ngang Nam', 'dep-quai-ngang-nam', NULL, 'dep', 'nam', 1, 1, '2026-07-29 07:40:30'),
(13, 3, 'Sandal Nam', 'sandal-nam', NULL, 'dep', 'nam', 2, 1, '2026-07-29 07:40:30'),
(14, 4, 'Dép Quai Ngang Nữ', 'dep-quai-ngang-nu', NULL, 'dep', 'nu', 1, 1, '2026-07-29 07:40:30'),
(15, 4, 'Sandal Nữ', 'sandal-nu', NULL, 'dep', 'nu', 2, 1, '2026-07-29 07:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `rating` tinyint(1) DEFAULT 5,
  `content` text NOT NULL,
  `staff_reply` text DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `content`, `staff_reply`, `staff_id`, `status`, `created_at`) VALUES
(1, 1, 3, 'Trần Văn Khách', 5, 'Giày cực đẹp, chất da mềm mại, rất vừa chân. Đi rất êm!', NULL, NULL, 1, '2026-07-20 03:30:00'),
(2, 2, 4, 'Nguyễn Thị Lan', 5, 'Dunk Low Panda quá đẹp luôn, đúng hàng chính hãng. Giao hàng nhanh!', NULL, NULL, 1, '2026-07-22 07:15:00'),
(3, 3, 3, 'Trần Văn Khách', 4, 'Samba OG rất đẹp, hơi cứng ban đầu nhưng đi vài ngày mềm ra.', NULL, NULL, 1, '2026-07-25 02:45:00'),
(4, 12, 4, 'Nguyễn Thị Lan', 5, 'Jordan 1 Chicago huyền thoại! Đóng gói cẩn thận, box đẹp.', NULL, NULL, 1, '2026-07-26 09:20:00'),
(5, 28, 3, 'Trần Văn Khách', 5, 'Birkenstock đi cực kỳ thoải mái, nhẹ và bền!', NULL, NULL, 1, '2026-07-27 04:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `citizen_id` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `fullname`, `phone`, `email`, `citizen_id`, `address`, `position`, `work_shift`, `base_salary`, `commission_rate`, `work_days`, `off_days`, `off_dates_detail`, `bonus`, `bonus_reason`, `fine`, `fine_reason`, `notes`, `status`, `created_at`) VALUES
(1, 2, 'Nhân Viên Bán Hàng', '0907654321', 'staff@shoes.com', '079200012345', '456 Lê Đại Hành, Q11, TP.HCM', 'Nhân viên bán hàng', 'Ca 1 (08:00 - 16:00)', 6000000, 2.50, 26, 0, NULL, 0, NULL, 0, NULL, NULL, 1, '2026-07-29 07:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT '#',
  `icon` varchar(100) DEFAULT NULL,
  `menu_type` enum('main','footer','mobile') DEFAULT 'main',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `parent_id`, `title`, `url`, `icon`, `menu_type`, `sort_order`, `status`, `created_at`) VALUES
(1, NULL, 'Trang Chủ', 'index.php', 'fa-solid fa-house', 'main', 1, 1, '2026-07-29 07:40:31'),
(2, NULL, 'Sản Phẩm', 'all-products.php', 'fa-solid fa-shoe-prints', 'main', 2, 1, '2026-07-29 07:40:31'),
(3, NULL, 'Giảm Giá', 'all-products.php?discount=1', 'fa-solid fa-fire', 'main', 3, 1, '2026-07-29 07:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(500) DEFAULT NULL,
  `size` varchar(10) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `cost_price` decimal(12,0) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `target` varchar(100) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `type` enum('phone','email') NOT NULL,
  `action` enum('login','register','reset_password') NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL,
  `target` varchar(100) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `type` enum('login','register','forgot','verify') NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`, `created_at`) VALUES
(1, 'NK-AF1-WHT', 'Nike Air Force 1 \'07 White', 'nike-air-force-1-07-white', 5, 1, 'Nam', 2929000, 3500000, 1800000, 16, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png', 'Đôi giày huyền thoại Nike Air Force 1 07 với chất liệu da cao cấp, đệm Air êm ái, phù hợp mọi phong cách.', 0, 0, 1250, 345, 1, '2026-07-29 07:40:30'),
(2, 'NK-DUNK-PANDA', 'Nike Dunk Low Retro Panda', 'nike-dunk-low-retro-panda', 5, 1, 'Nam', 3100000, 3600000, 1900000, 14, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png', 'Nike Dunk Low phối màu Panda đen trắng kinh điển, dễ phối đồ nhất mọi thời đại.', 1, 0, 2100, 520, 1, '2026-07-29 07:40:30'),
(3, 'AD-SAMBA-OG', 'Adidas Samba OG White Black', 'adidas-samba-og-white-black', 5, 2, 'Nam', 2700000, 3100000, 1600000, 13, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7fce7de0e8984e84a447a8bf01187e1c_9366/Giay_Samba_OG_trang_B75806_01_standard.jpg', 'Adidas Samba OG retro với chất liệu da mềm, đế cao su grip tốt, icon thời trang đường phố.', 1, 1, 3500, 880, 1, '2026-07-29 07:40:30'),
(4, 'AD-SUPERSTAR', 'Adidas Superstar Cloud White', 'adidas-superstar-cloud-white', 5, 2, 'Nam', 2500000, 2900000, 1500000, 14, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg', 'Adidas Superstar với shell toe huyền thoại, phong cách streetwear không bao giờ lỗi mốt.', 0, 0, 980, 290, 1, '2026-07-29 07:40:30'),
(5, 'NB-574-GRY', 'New Balance 574 Classic Grey', 'new-balance-574-classic-grey', 5, 5, 'Nam', 2650000, 2900000, 1600000, 9, 'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'New Balance 574 phiên bản Classic Grey, êm ái thoải mái cho mọi hoạt động hàng ngày.', 0, 1, 650, 180, 1, '2026-07-29 07:40:30'),
(6, 'CV-CHUCK70-BLK', 'Converse Chuck 70 High Black', 'converse-chuck-70-high-black', 8, 6, 'Nam', 2000000, 2300000, 1200000, 13, 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg', 'Chuck 70 cổ cao canvas dày dặn, đế ngà vintage, biểu tượng văn hóa sneaker toàn cầu.', 0, 0, 780, 240, 1, '2026-07-29 07:40:30'),
(7, 'VN-OLD-SKOOL', 'Vans Old Skool Black White', 'vans-old-skool-black-white', 8, 7, 'Nam', 1800000, 2100000, 1000000, 14, 'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$', 'Vans Old Skool với sọc Jazz huyền thoại, giày skate kinh điển mọi thời đại.', 0, 1, 920, 310, 1, '2026-07-29 07:40:30'),
(8, 'PM-SUEDE-BLK', 'Puma Suede Classic XXI Black', 'puma-suede-classic-xxi-black', 8, 4, 'Nam', 2100000, 2400000, 1300000, 13, 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers', 'Puma Suede Classic với chất da lộn mềm mại, thiết kế thanh lịch phù hợp cả đi chơi lẫn đi làm.', 0, 0, 450, 120, 1, '2026-07-29 07:40:30'),
(9, 'NK-PEGASUS41', 'Nike Air Zoom Pegasus 41', 'nike-air-zoom-pegasus-41', 6, 1, 'Nam', 3600000, 4200000, 2200000, 14, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png', 'Nike Pegasus 41 với đệm Air Zoom React siêu nhẹ, lý tưởng cho chạy bộ hàng ngày.', 0, 1, 890, 195, 1, '2026-07-29 07:40:30'),
(10, 'AD-ULTRABOOST', 'Adidas Ultraboost Light', 'adidas-ultraboost-light', 6, 2, 'Nam', 3800000, 4500000, 2300000, 16, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg', 'Ultraboost Light với đệm Boost siêu nhẹ mang lại trải nghiệm êm ái tuyệt đối cho runner.', 0, 1, 750, 165, 1, '2026-07-29 07:40:30'),
(11, 'NB-FUELCELL', 'New Balance FuelCell Propel v4', 'new-balance-fuelcell-propel-v4', 6, 5, 'Nam', 2900000, 3400000, 1800000, 15, 'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'FuelCell Propel v4 với đệm FuelCell năng động, thiết kế nhẹ tối đa cho tốc độ.', 0, 1, 320, 85, 1, '2026-07-29 07:40:30'),
(12, 'JD-1-CHICAGO', 'Air Jordan 1 Retro High OG Chicago', 'air-jordan-1-retro-high-og-chicago', 7, 3, 'Nam', 5200000, 6000000, 3200000, 13, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cd6b3ec7-e7d0-47c5-86c4-2e53cfe67ed7/AIR+JORDAN+1+RETRO+HIGH+OG.png', 'Jordan 1 Chicago phối màu đỏ-trắng-đen kinh điển, biểu tượng sneaker culture toàn cầu.', 1, 0, 4200, 420, 1, '2026-07-29 07:40:30'),
(13, 'JD-4-BRED', 'Air Jordan 4 Retro Bred Reimagined', 'air-jordan-4-retro-bred-reimagined', 7, 3, 'Nam', 5800000, 6500000, 3500000, 11, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e21943c5-47d6-4a50-8d7e-d7a45c4b42a2/JORDAN+4+RETRO.png', 'Jordan 4 Bred Reimagined với phối màu đen đỏ huyền thoại, chất liệu da premium.', 1, 1, 3100, 280, 1, '2026-07-29 07:40:30'),
(14, 'NK-LEBRON21', 'Nike LeBron 21', 'nike-lebron-21', 7, 1, 'Nam', 5500000, 6200000, 3300000, 11, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png', 'LeBron 21 với công nghệ Zoom Air đỉnh cao, phục vụ sân bóng rổ chuyên nghiệp.', 0, 1, 580, 95, 1, '2026-07-29 07:40:30'),
(15, 'NK-AF1-WMNS', 'Nike Air Force 1 \'07 Women White', 'nike-air-force-1-07-women-white', 9, 1, 'Nữ', 2929000, 3500000, 1800000, 16, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png', 'Air Force 1 phiên bản nữ với thiết kế thanh lịch, chất da mềm mại.', 0, 0, 1800, 420, 1, '2026-07-29 07:40:30'),
(16, 'AD-SAMBA-ROSE', 'Adidas Samba OG Women Rose', 'adidas-samba-og-women-rose', 9, 2, 'Nữ', 2800000, 3200000, 1700000, 13, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3aed9c49f77f4c27b267af4c00e60e83_9366/Giay_Samba_OG_Hong_IG4199_01_standard.jpg', 'Adidas Samba OG phối màu Rose dịu dàng, phong cách retro nữ tính.', 1, 1, 2200, 510, 1, '2026-07-29 07:40:30'),
(17, 'NB-530-SLV', 'New Balance 530 Metallic Silver', 'new-balance-530-metallic-silver', 9, 5, 'Nữ', 2650000, 2900000, 1600000, 9, 'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880', 'NB 530 phong cách Dad Shoe năng động với đệm ABZORB cực êm.', 0, 1, 680, 175, 1, '2026-07-29 07:40:30'),
(18, 'CV-RUNSTAR', 'Converse Run Star Hike High Black', 'converse-run-star-hike-high-black', 11, 6, 'Nữ', 2600000, 3000000, 1500000, 13, 'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg', 'Run Star Hike đế chunky trendy, phong cách và cá tính dành cho các bạn nữ.', 0, 1, 520, 140, 1, '2026-07-29 07:40:30'),
(19, 'PM-PALERMO-PNK', 'Puma Palermo Pink White', 'puma-palermo-pink-white', 11, 4, 'Nữ', 2250000, 2600000, 1350000, 13, 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers', 'Puma Palermo phối hồng trắng cá tính, trẻ trung dành cho phái nữ.', 0, 1, 441, 125, 1, '2026-07-29 07:40:30'),
(20, 'NK-AIRMAX90-PNK', 'Nike Air Max 90 Futura Pink', 'nike-air-max-90-futura-pink', 10, 1, 'Nữ', 3200000, 3800000, 1900000, 16, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png', 'Air Max 90 Futura phối hồng nữ tính với đệm Air Max êm ái huyền thoại.', 0, 1, 950, 210, 1, '2026-07-29 07:40:30'),
(21, 'AD-ULTRABOOST-W', 'Adidas Ultraboost Light Women', 'adidas-ultraboost-light-women', 10, 2, 'Nữ', 3600000, 4300000, 2200000, 16, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg', 'Ultraboost Light phiên bản nữ với đệm Boost tối ưu, nhẹ và thoáng khí.', 0, 1, 420, 98, 1, '2026-07-29 07:40:30'),
(22, 'NK-BENASSI', 'Nike Benassi JDI Slide Black', 'nike-benassi-jdi-slide-black', 12, 1, 'Nam', 790000, 950000, 450000, 17, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png', 'Nike Benassi JDI slide đen trắng thoải mái, dễ mang hàng ngày.', 0, 0, 380, 200, 1, '2026-07-29 07:40:30'),
(23, 'AD-ADILETTE', 'Adidas Adilette Comfort Slide', 'adidas-adilette-comfort-slide', 12, 2, 'Nam', 850000, 1000000, 480000, 15, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg', 'Adidas Adilette Comfort với đệm Cloudfoam siêu nhẹ, thương hiệu dép quốc dân.', 0, 1, 290, 150, 1, '2026-07-29 07:40:30'),
(24, 'PM-LEADCAT', 'Puma Leadcat 2.0 Slide', 'puma-leadcat-2-slide', 12, 4, 'Nam', 650000, 800000, 380000, 19, 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides', 'Puma Leadcat 2.0 nhẹ nhàng thoải mái với logo Puma nổi bật.', 0, 0, 183, 90, 1, '2026-07-29 07:40:30'),
(25, 'NK-CANYON', 'Nike Canyon Sandal Black', 'nike-canyon-sandal-black', 13, 1, 'Nam', 1950000, 2300000, 1100000, 15, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png', 'Nike Canyon Sandal outdoor phong cách, đế chunky bám tốt trên mọi địa hình.', 0, 1, 210, 65, 1, '2026-07-29 07:40:30'),
(26, 'NK-VICTORI-W', 'Nike Victori One Slide Women', 'nike-victori-one-slide-women', 14, 1, 'Nữ', 750000, 900000, 420000, 17, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png', 'Nike Victori One Slide nữ tính thoải mái cho mùa hè.', 0, 1, 320, 170, 1, '2026-07-29 07:40:30'),
(27, 'AD-ADILETTE-W', 'Adidas Adilette Aqua Slide Women', 'adidas-adilette-aqua-slide-women', 14, 2, 'Nữ', 650000, 800000, 380000, 19, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg', 'Adilette Aqua với chất liệu nhẹ chống nước, phù hợp đi biển và hồ bơi.', 0, 0, 250, 130, 1, '2026-07-29 07:40:30'),
(28, 'BK-ARIZONA-EVA', 'Birkenstock Arizona EVA White', 'birkenstock-arizona-eva-white', 15, 8, 'Nữ', 1200000, 1500000, 700000, 20, 'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg', 'Birkenstock Arizona EVA nhẹ nhàng, đế cork thoải mái cho đôi chân suốt cả ngày.', 0, 1, 680, 195, 1, '2026-07-29 07:40:30'),
(29, 'NK-OWAFFLE-W', 'Nike OffCourt Adjust Slide Women', 'nike-offcourt-adjust-slide-women', 15, 1, 'Nữ', 1100000, 1350000, 650000, 19, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png', 'Nike OffCourt Adjust Slide với quai điều chỉnh, năng động mùa hè.', 0, 1, 191, 75, 1, '2026-07-29 07:40:30'),
(30, 'NK-AIRMAX97', 'Nike Air Max 97 Silver Bullet', 'nike-air-max-97-silver-bullet', 5, 1, 'Nam', 3200000, 4800000, 1900000, 33, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/d69e2486-1e28-4a57-86a6-1a82e4c50018/AIR+MAX+97+OG.png', 'Air Max 97 Silver Bullet với thiết kế gợn sóng huyền thoại, đệm Air full-length.', 0, 0, 1650, 380, 1, '2026-07-29 07:40:30'),
(31, 'VN-SK8HI', 'Vans Sk8-Hi Black White', 'vans-sk8-hi-black-white', 8, 7, 'Nam', 1500000, 2200000, 900000, 32, 'https://images.vans.com/is/image/VansBrand/VN000D5IB8C-HERO?$PDP-FULL-IMAGE$', 'Vans Sk8-Hi cổ cao, icon của văn hóa skate và punk rock.', 0, 0, 720, 210, 1, '2026-07-29 07:40:30'),
(32, 'AD-GAZELLE', 'Adidas Gazelle Bold Green', 'adidas-gazelle-bold-green', 11, 2, 'Nữ', 2200000, 3200000, 1300000, 31, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg', 'Gazelle Bold đế platform trendy với phối xanh lá retro, hot trend 2026.', 0, 1, 1900, 450, 1, '2026-07-29 07:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`) VALUES
(1, 1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png', 1),
(2, 1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/005e8105-ffad-4e50-94d3-e7f09f061266/AIR+FORCE+1+%2707.png', 2),
(3, 1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9a83eb9e-a0e2-41a2-9447-4a008c2a95c9/AIR+FORCE+1+%2707.png', 3),
(4, 1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/96d03d09-4081-4200-84cf-23579bcf3c95/AIR+FORCE+1+%2707.png', 4),
(5, 1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/8c69bfed-be76-47e1-b1e0-717088b9c7b5/AIR+FORCE+1+%2707.png', 5),
(6, 1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/28bb3f83-e18e-4a6c-9477-99df2f6d2fef/AIR+FORCE+1+%2707.png', 6),
(7, 2, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png', 1),
(8, 2, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/a14704fb-2231-4a1d-a99f-bbd75605d8f6/NIKE+DUNK+LOW+RETRO.png', 2),
(9, 2, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/34dfa8b1-3829-450f-bb08-8f5b40cf326e/NIKE+DUNK+LOW+RETRO.png', 3),
(10, 2, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/75b81a7b-0d04-4530-9b4a-a3a8309b85c1/NIKE+DUNK+LOW+RETRO.png', 4),
(11, 2, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e9faef0b-d264-42b7-827d-0cb4eb61bf9a/NIKE+DUNK+LOW+RETRO.png', 5),
(12, 2, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/03295252-9599-4d69-a1b4-7d52f6f4142f/NIKE+DUNK+LOW+RETRO.png', 6),
(13, 3, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7fce7de0e8984e84a447a8bf01187e1c_9366/Giay_Samba_OG_trang_B75806_01_standard.jpg', 1),
(14, 3, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/6b763ec253454b52b217a8bf011894d8_9366/Giay_Samba_OG_trang_B75806_02_standard_hover.jpg', 2),
(15, 3, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/815915d3fa78486ca9c2a8bf0118a803_9366/Giay_Samba_OG_trang_B75806_04_standard.jpg', 3),
(16, 3, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/4e622b7d413346e6a100a8bf0118bc61_9366/Giay_Samba_OG_trang_B75806_05_standard.jpg', 4),
(17, 3, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3b5db492160946b5a752a8bf01189e47_9366/Giay_Samba_OG_trang_B75806_03_standard.jpg', 5),
(18, 3, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/01f80ef307614d3ca976a8bf0118ca21_9366/Giay_Samba_OG_trang_B75806_41_detail.jpg', 6),
(19, 12, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cd6b3ec7-e7d0-47c5-86c4-2e53cfe67ed7/AIR+JORDAN+1+RETRO+HIGH+OG.png', 1),
(20, 12, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/032fcfc5-d72b-426c-85fa-7fcf1dd12781/AIR+JORDAN+1+RETRO+HIGH+OG.png', 2),
(21, 12, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/7d363d66-ebbe-4835-9fa8-1f19fbb1c7a5/AIR+JORDAN+1+RETRO+HIGH+OG.png', 3),
(22, 12, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/f23ff1ad-8b27-4632-9f68-1a520be32fef/AIR+JORDAN+1+RETRO+HIGH+OG.png', 4),
(23, 12, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e9efcfc5-231a-4d92-bf1c-554446b14d23/AIR+JORDAN+1+RETRO+HIGH+OG.png', 5),
(24, 12, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fa114bd-1002-4fdf-9755-6b5860d5bdfb/AIR+JORDAN+1+RETRO+HIGH+OG.png', 6);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`) VALUES
(1, 1, '39', 'Trắng', 15),
(2, 1, '40', 'Trắng', 20),
(3, 1, '41', 'Trắng', 18),
(4, 1, '42', 'Trắng', 12),
(5, 1, '43', 'Trắng', 8),
(6, 2, '39', 'Đen Trắng', 10),
(7, 2, '40', 'Đen Trắng', 25),
(8, 2, '41', 'Đen Trắng', 20),
(9, 2, '42', 'Đen Trắng', 15),
(10, 2, '43', 'Đen Trắng', 8),
(11, 3, '38', 'Trắng Đen', 8),
(12, 3, '39', 'Trắng Đen', 15),
(13, 3, '40', 'Trắng Đen', 22),
(14, 3, '41', 'Trắng Đen', 18),
(15, 3, '42', 'Trắng Đen', 10),
(16, 4, '39', 'Trắng', 12),
(17, 4, '40', 'Trắng', 18),
(18, 4, '41', 'Trắng', 15),
(19, 4, '42', 'Trắng', 10),
(20, 5, '40', 'Xám', 10),
(21, 5, '41', 'Xám', 14),
(22, 5, '42', 'Xám', 12),
(23, 5, '43', 'Xám', 6),
(24, 6, '39', 'Đen', 12),
(25, 6, '40', 'Đen', 18),
(26, 6, '41', 'Đen', 15),
(27, 6, '42', 'Đen', 10),
(28, 7, '39', 'Đen Trắng', 10),
(29, 7, '40', 'Đen Trắng', 15),
(30, 7, '41', 'Đen Trắng', 12),
(31, 7, '42', 'Đen Trắng', 8),
(32, 8, '40', 'Đen', 8),
(33, 8, '41', 'Đen', 12),
(34, 8, '42', 'Đen', 10),
(35, 8, '43', 'Đen', 5),
(36, 9, '40', 'Đen Trắng', 10),
(37, 9, '41', 'Đen Trắng', 15),
(38, 9, '42', 'Đen Trắng', 12),
(39, 9, '43', 'Đen Trắng', 8),
(40, 10, '40', 'Đen', 7),
(41, 10, '41', 'Đen', 12),
(42, 10, '42', 'Đen', 10),
(43, 10, '43', 'Đen', 5),
(44, 11, '40', 'Trắng Đen', 6),
(45, 11, '41', 'Trắng Đen', 10),
(46, 11, '42', 'Trắng Đen', 8),
(47, 12, '40', 'Đỏ Trắng Đen', 5),
(48, 12, '41', 'Đỏ Trắng Đen', 8),
(49, 12, '42', 'Đỏ Trắng Đen', 6),
(50, 12, '43', 'Đỏ Trắng Đen', 3),
(51, 13, '40', 'Đen Đỏ', 4),
(52, 13, '41', 'Đen Đỏ', 7),
(53, 13, '42', 'Đen Đỏ', 5),
(54, 13, '43', 'Đen Đỏ', 3),
(55, 14, '41', 'Đen Vàng', 5),
(56, 14, '42', 'Đen Vàng', 8),
(57, 14, '43', 'Đen Vàng', 6),
(58, 14, '44', 'Đen Vàng', 3),
(59, 15, '36', 'Trắng', 10),
(60, 15, '37', 'Trắng', 18),
(61, 15, '38', 'Trắng', 20),
(62, 15, '39', 'Trắng', 15),
(63, 15, '40', 'Trắng', 8),
(64, 16, '36', 'Hồng', 8),
(65, 16, '37', 'Hồng', 15),
(66, 16, '38', 'Hồng', 20),
(67, 16, '39', 'Hồng', 12),
(68, 17, '36', 'Bạc', 8),
(69, 17, '37', 'Bạc', 12),
(70, 17, '38', 'Bạc', 15),
(71, 17, '39', 'Bạc', 10),
(72, 18, '36', 'Đen', 6),
(73, 18, '37', 'Đen', 10),
(74, 18, '38', 'Đen', 12),
(75, 18, '39', 'Đen', 8),
(76, 19, '36', 'Hồng Trắng', 5),
(77, 19, '37', 'Hồng Trắng', 10),
(78, 19, '38', 'Hồng Trắng', 12),
(79, 19, '39', 'Hồng Trắng', 8),
(80, 20, '36', 'Hồng', 6),
(81, 20, '37', 'Hồng', 12),
(82, 20, '38', 'Hồng', 15),
(83, 20, '39', 'Hồng', 10),
(84, 21, '36', 'Trắng', 5),
(85, 21, '37', 'Trắng', 10),
(86, 21, '38', 'Trắng', 12),
(87, 21, '39', 'Trắng', 8),
(88, 22, '39', 'Đen', 20),
(89, 22, '40', 'Đen', 30),
(90, 22, '41', 'Đen', 25),
(91, 22, '42', 'Đen', 20),
(92, 22, '43', 'Đen', 15),
(93, 23, '39', 'Đen', 18),
(94, 23, '40', 'Đen', 25),
(95, 23, '41', 'Đen', 22),
(96, 23, '42', 'Đen', 15),
(97, 24, '39', 'Đen', 15),
(98, 24, '40', 'Đen', 20),
(99, 24, '41', 'Đen', 18),
(100, 24, '42', 'Đen', 12),
(101, 25, '40', 'Đen', 8),
(102, 25, '41', 'Đen', 12),
(103, 25, '42', 'Đen', 10),
(104, 25, '43', 'Đen', 6),
(105, 26, '36', 'Trắng', 12),
(106, 26, '37', 'Trắng', 18),
(107, 26, '38', 'Trắng', 15),
(108, 26, '39', 'Trắng', 10),
(109, 27, '36', 'Đen', 15),
(110, 27, '37', 'Đen', 20),
(111, 27, '38', 'Đen', 18),
(112, 27, '39', 'Đen', 12),
(113, 28, '36', 'Trắng', 8),
(114, 28, '37', 'Trắng', 12),
(115, 28, '38', 'Trắng', 15),
(116, 28, '39', 'Trắng', 10),
(117, 28, '40', 'Trắng', 6),
(118, 29, '36', 'Đen', 6),
(119, 29, '37', 'Đen', 10),
(120, 29, '38', 'Đen', 12),
(121, 29, '39', 'Đen', 8),
(122, 30, '40', 'Bạc', 6),
(123, 30, '41', 'Bạc', 10),
(124, 30, '42', 'Bạc', 8),
(125, 30, '43', 'Bạc', 4),
(126, 31, '39', 'Đen Trắng', 8),
(127, 31, '40', 'Đen Trắng', 12),
(128, 31, '41', 'Đen Trắng', 10),
(129, 31, '42', 'Đen Trắng', 6),
(130, 32, '36', 'Xanh', 6),
(131, 32, '37', 'Xanh', 12),
(132, 32, '38', 'Xanh', 15),
(133, 32, '39', 'Xanh', 10);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_provinces`
--

CREATE TABLE `shipping_provinces` (
  `id` int(11) NOT NULL,
  `province_name` varchar(100) NOT NULL,
  `shipping_fee` decimal(12,0) NOT NULL DEFAULT 30000,
  `estimated_days` varchar(50) DEFAULT '2-4 ngày',
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_provinces`
--

INSERT INTO `shipping_provinces` (`id`, `province_name`, `shipping_fee`, `estimated_days`, `status`) VALUES
(1, 'Hà Nội', 20000, '1-2 ngày', 1),
(2, 'TP. Hồ Chí Minh', 25000, '2-3 ngày', 1),
(3, 'Đà Nẵng', 25000, '2-3 ngày', 1),
(4, 'Vĩnh Long', 15000, '1-2 ngày', 1),
(5, 'Cần Thơ', 20000, '2-3 ngày', 1),
(6, 'Bình Dương', 22000, '2-3 ngày', 1),
(7, 'Đồng Nai', 22000, '2-3 ngày', 1),
(8, 'Hải Phòng', 25000, '2-3 ngày', 1),
(9, 'Tỉnh/Thành Khác', 35000, '3-5 ngày', 1);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_group`) VALUES
(1, 'site_name', 'SHOES STORE', 'general'),
(2, 'site_logo', '', 'general'),
(3, 'site_description', 'Thương hiệu Sneaker hàng đầu mang đến trải nghiệm thời trang dịu nhẹ, thanh lịch và chất lượng cam kết chính hãng.', 'general'),
(4, 'site_keywords', 'giày sneaker, giày chính hãng, nike, adidas, jordan, dép nam nữ', 'general'),
(5, 'contact_address', 'Long Châu, TP. Vĩnh Long', 'contact'),
(6, 'contact_hotline', '0912.345.678', 'contact'),
(7, 'contact_email', 'support@shoes.vn', 'contact'),
(8, 'bank_id', 'ACB', 'payment'),
(9, 'bank_account', '0123456789', 'payment'),
(10, 'bank_name', 'SHOP OWNER', 'payment'),
(11, 'footer_copyright', '© 2026 SHOES STORE. Thiết kế bởi Trang Sỉ Giàu.', 'footer'),
(12, 'hero_title', 'BỨT PHÁ PHONG CÁCH', 'cms'),
(13, 'hero_subtitle', 'Siêu Phẩm Sneaker 2026', 'cms'),
(14, 'hero_image', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'cms'),
(15, 'hero_button_text', 'MUA SẮM NGAY', 'cms'),
(16, 'hero_button_link', 'all-products.php', 'cms'),
(17, 'section_hot_title', '🔥 SẢN PHẨM NỔI BẬT', 'cms'),
(18, 'section_new_title', '✨ HÀNG MỚI VỀ', 'cms'),
(19, 'section_sale_title', '💰 ĐANG GIẢM GIÁ SỐC', 'cms'),
(20, 'section_brand_title', '🏆 THƯƠNG HIỆU NỔI BẬT', 'cms'),
(21, 'section_voucher_title', '🎟️ MÃ GIẢM GIÁ KHUYẾN MÃI', 'cms'),
(22, 'service_1_icon', 'fa-solid fa-truck-fast', 'services'),
(23, 'service_1_title', 'Miễn Phí Vận Chuyển', 'services'),
(24, 'service_1_desc', 'Cho đơn hàng từ 500.000đ', 'services'),
(25, 'service_2_icon', 'fa-solid fa-shield-halved', 'services'),
(26, 'service_2_title', '100% Chính Hãng', 'services'),
(27, 'service_2_desc', 'Cam kết hàng Authentic', 'services'),
(28, 'service_3_icon', 'fa-solid fa-rotate-left', 'services'),
(29, 'service_3_title', 'Đổi Trả 30 Ngày', 'services'),
(30, 'service_3_desc', 'Miễn phí nếu lỗi sản phẩm', 'services'),
(31, 'service_4_icon', 'fa-solid fa-headset', 'services'),
(32, 'service_4_title', 'Hỗ Trợ 24/7', 'services'),
(33, 'service_4_desc', 'Tư vấn mọi lúc mọi nơi', 'services'),
(34, 'marquee_text', '🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN TỪ 500.000Đ | 🎁 MÃ WELCOME50K GIẢM 50K | 🏆 100% CHÍNH HÃNG AUTHENTIC', 'general');

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform`, `url`, `icon`, `sort_order`, `status`) VALUES
(1, 'Facebook', 'https://facebook.com/shoesstore', 'fa-brands fa-facebook-f', 1, 1),
(2, 'Instagram', 'https://instagram.com/shoesstore', 'fa-brands fa-instagram', 2, 1),
(3, 'TikTok', 'https://tiktok.com/@shoesstore', 'fa-brands fa-tiktok', 3, 1),
(4, 'Zalo', 'https://zalo.me/0901234567', 'fa-solid fa-comment-dots', 4, 1),
(5, 'YouTube', 'https://youtube.com/@shoesstore', 'fa-brands fa-youtube', 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `google_id`, `avatar`, `birthday`, `auth_provider`, `is_email_verified`, `is_phone_verified`, `role`, `commission_rate`, `total_commission`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Quản Trị Viên', 'admin@shoes.com', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '1995-01-15', 'local', 1, 0, 'admin', 0.00, 0, 1, '2026-07-29 07:40:30', '2026-07-29 07:40:30'),
(2, 'Nhân Viên Bán Hàng', 'staff@shoes.com', '0907654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '1998-06-20', 'local', 1, 0, 'staff', 0.00, 0, 1, '2026-07-29 07:40:30', '2026-07-29 07:40:30'),
(3, 'Trần Văn Khách', 'khachhang@gmail.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '2000-03-10', 'local', 1, 0, 'customer', 0.00, 0, 1, '2026-07-29 07:40:30', '2026-07-29 07:40:30'),
(4, 'Nguyễn Thị Lan', 'lanng@gmail.com', '0933456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '1999-11-05', 'local', 1, 0, 'customer', 0.00, 0, 1, '2026-07-29 07:40:30', '2026-07-29 07:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `address_detail` varchar(500) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_id`, `address_detail`, `is_default`, `created_at`) VALUES
(1, 3, 'Trần Văn Khách', '0912345678', 4, '123 Đường Phạm Hùng, Phường 1, TP. Vĩnh Long', 1, '2026-07-29 07:40:31'),
(2, 3, 'Trần Văn Khách', '0912345678', 2, '456 Đường Nguyễn Huệ, Quận 1, TP. HCM', 0, '2026-07-29 07:40:31'),
(3, 4, 'Nguyễn Thị Lan', '0933456789', 1, '789 Đường Láng, Đống Đa, Hà Nội', 1, '2026-07-29 07:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_vouchers`
--

CREATE TABLE `user_vouchers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
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
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `title`, `brand_id`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `per_user_limit`, `event_type`, `start_date`, `end_date`, `status`) VALUES
(1, 'WELCOME50', 'Chào mừng khách hàng mới - Giảm 50K', NULL, 'fixed', 50000, 500000, 50000, 100, 12, 1, 'new_user', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
(2, 'SHOES10', 'Giảm 10% cho đơn từ 1 triệu', NULL, 'percent', 10, 1000000, 200000, 50, 5, 2, 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
(3, 'FREESHIP', 'Miễn phí vận chuyển', NULL, 'freeship', 35000, 300000, 35000, 200, 34, 3, 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
(4, 'SUMMER2026', 'Mùa hè sôi động - Giảm 15%', NULL, 'percent', 15, 2000000, 500000, 80, 10, 1, 'holiday', '2026-06-01 00:00:00', '2026-08-31 23:59:59', 1),
(5, 'SALE100K', 'Flash Sale - Giảm 100K', NULL, 'fixed', 100000, 800000, 100000, 50, 8, 1, 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
(6, 'VIP20', 'Khách VIP - Giảm 20%', NULL, 'percent', 20, 3000000, 800000, 30, 2, 1, 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_prod_var` (`user_id`,`product_id`,`variant_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shipping_provinces`
--
ALTER TABLE `shipping_provinces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `province_name` (`province_name`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `user_vouchers`
--
ALTER TABLE `user_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `shipping_provinces`
--
ALTER TABLE `shipping_provinces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_vouchers`
--
ALTER TABLE `user_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `shipping_provinces` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_addresses_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `shipping_provinces` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_vouchers`
--
ALTER TABLE `user_vouchers`
  ADD CONSTRAINT `user_vouchers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_vouchers_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
