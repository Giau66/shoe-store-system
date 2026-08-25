-- ============================================================
-- WEB SHOE DATABASE EXPORT (FULL PRODUCTION READY)
-- Generated: 2026-08-18 16:33:18
-- System Suite 2026 - Optimized for Host Deployment
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Table structure for `banners`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `badge_text` varchar(100) DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `button_text` varchar(100) DEFAULT 'Mua Ngay',
  `position` enum('hero','promo_left','promo_right','sidebar') DEFAULT 'hero',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `banners` (3 rows)
INSERT INTO `banners` VALUES
('1', 'SIÊU SALE SNEAKER 2026', 'Bùng nổ hàng ngàn ưu đãi giảm đến 40% cho các siêu phẩm Nike, Adidas, Jordan, New Balance. Tặng Voucher 100K + Miễn phí vận chuyển toàn quốc!', '🔥 KHUYẾN MÃI LỚN NHẤT NĂM', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=1600', 'all-products.php', 'XEM TẤT CẢ SẢN PHẨM', 'hero', '1', '1', '2026-08-18 13:31:27'),
('2', 'Bộ Sưu Tập Samba & Dad Shoes Retro', 'Phong cách hoài cổ đang dẫn đầu xu hướng thời trang giới trẻ thế giới', 'HOT TRENDING', 'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?q=80&w=800', 'all-products.php?brand=2', 'Xem Ngay', 'promo_left', '1', '1', '2026-08-18 13:31:27'),
('3', 'Jordan 1 High & Travis Scott Series', 'Đẳng cấp văn hóa Sneakerhead và bóng rổ đường phố đỉnh cao', 'GIẢM ĐẾN 300K', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', 'all-products.php?brand=3', 'Săn Ngay', 'promo_right', '1', '1', '2026-08-18 13:31:27');

-- ------------------------------------------------------------
-- Table structure for `brands`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `brands` (5 rows)
INSERT INTO `brands` VALUES
('1', 'Nike', 'nike', 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg', NULL, 'Thương hiệu thể thao số 1 thế giới với khẩu hiệu Just Do It, nổi tiếng với công nghệ đệm Nike Air, ZoomX, React và các dòng sneaker huyền thoại.', '1', '2026-08-18 13:31:27'),
('2', 'Adidas', 'adidas', 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg', NULL, 'Biểu tượng 3 sọc từ nước Đức, tiên phong trong công nghệ Boost siêu êm ái, đệm EVA bền bỉ và phong cách thời trang đường phố Retro Blokecore.', '1', '2026-08-18 13:31:27'),
('3', 'Jordan', 'jordan', 'https://upload.wikimedia.org/wikipedia/en/3/37/Jumpman_logo.svg', NULL, 'Thương hiệu di sản gắn liền với huyền thoại bóng rổ Michael Jordan, biểu tượng số một của văn hóa Sneakerhead và Streetwear.', '1', '2026-08-18 13:31:27'),
('4', 'New Balance', 'new-balance', 'https://upload.wikimedia.org/wikipedia/commons/e/ea/New_Balance_logo.svg', NULL, 'Thương hiệu giày chạy bộ cao cấp từ Mỹ, nổi tiếng với công nghệ đệm ABZORB, ENCAP và các thiết kế Dad Shoes thời thượng.', '1', '2026-08-18 13:31:27'),
('5', 'Converse', 'converse', 'https://upload.wikimedia.org/wikipedia/commons/3/30/Converse_logo.svg', NULL, 'Biểu tượng văn hóa đại chúng toàn cầu với dòng giày vải Chuck Taylor All Star và Chuck 70s trường tồn qua nhiều thập kỷ.', '1', '2026-08-18 13:31:27');

-- ------------------------------------------------------------
-- Table structure for `cart_items`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `cart_items` (1 rows)
INSERT INTO `cart_items` VALUES
('1', '1', '4', '19', '1', '2026-08-18 15:10:12', '2026-08-18 15:10:12');

-- ------------------------------------------------------------
-- Table structure for `categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('giay','dep') DEFAULT 'giay',
  `gender` enum('nam','nu','unisex') DEFAULT 'unisex',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories` (15 rows)
INSERT INTO `categories` VALUES
('1', NULL, 'Giày Nam', 'giay-nam', NULL, NULL, 'giay', 'nam', '1', '1', '2026-08-04 18:51:36'),
('2', NULL, 'Giày Nữ', 'giay-nu', NULL, NULL, 'giay', 'nu', '2', '1', '2026-08-04 18:51:36'),
('3', NULL, 'Dép Nam', 'dep-nam', NULL, NULL, 'dep', 'nam', '3', '1', '2026-08-04 18:51:36'),
('4', NULL, 'Dép Nữ', 'dep-nu', NULL, NULL, 'dep', 'nu', '4', '1', '2026-08-04 18:51:36'),
('5', '1', 'Sneaker Nam', 'sneaker-nam', NULL, NULL, 'giay', 'nam', '1', '1', '2026-08-04 18:51:36'),
('6', '1', 'Giày Chạy Bộ Nam', 'giay-chay-bo-nam', NULL, NULL, 'giay', 'nam', '2', '1', '2026-08-04 18:51:36'),
('7', '1', 'Giày Bóng Rổ', 'giay-bong-ro', NULL, NULL, 'giay', 'nam', '3', '1', '2026-08-04 18:51:36'),
('8', '1', 'Giày Thời Trang Nam', 'giay-thoi-trang-nam', NULL, NULL, 'giay', 'nam', '4', '1', '2026-08-04 18:51:36'),
('9', '2', 'Sneaker Nữ', 'sneaker-nu', NULL, NULL, 'giay', 'nu', '1', '1', '2026-08-04 18:51:36'),
('10', '2', 'Giày Chạy Bộ Nữ', 'giay-chay-bo-nu', NULL, NULL, 'giay', 'nu', '2', '1', '2026-08-04 18:51:36'),
('11', '2', 'Giày Thời Trang Nữ', 'giay-thoi-trang-nu', NULL, NULL, 'giay', 'nu', '3', '1', '2026-08-04 18:51:36'),
('12', '3', 'Dép Quai Ngang Nam', 'dep-quai-ngang-nam', NULL, NULL, 'dep', 'nam', '1', '1', '2026-08-04 18:51:36'),
('13', '3', 'Sandal Nam', 'sandal-nam', NULL, NULL, 'dep', 'nam', '2', '1', '2026-08-04 18:51:36'),
('14', '4', 'Dép Quai Ngang Nữ', 'dep-quai-ngang-nu', NULL, NULL, 'dep', 'nu', '1', '1', '2026-08-04 18:51:36'),
('15', '4', 'Sandal Nữ', 'sandal-nu', NULL, NULL, 'dep', 'nu', '2', '1', '2026-08-04 18:51:36');

-- ------------------------------------------------------------
-- Table structure for `comments`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `comments` (15 rows)
INSERT INTO `comments` VALUES
('1', '1', '7', 'Nguyễn Quốc Thái', '5', 'Giày Nike AF1 chuẩn chính hãng, form cứng cáp trắng tinh, đi êm chân và bám sàn rất tốt. Đóng gói hộp 2 lớp cẩn thận không bị móp méo.', 'Cảm ơn bạn Thái đã tin tưởng và ủng hộ Shoes Store! Chúc bạn có những trải nghiệm thật tuyệt vời cùng đôi AF1 nhé ❤️', '2', '1', '2026-08-10 14:30:00'),
('2', '1', '8', 'Trần Thanh Trúc', '5', 'Shop giao hàng nhanh chóng, đặt hôm trước hôm sau đã nhận được tại Hà Nội. Đế Air đi rất êm, đúng size chuẩn EU 39.', 'Dạ Shoes Store cảm ơn bạn Trúc nhiều ạ! Rất mong được tiếp tục phục vụ bạn trong các đơn hàng tới nhé!', '2', '1', '2026-08-12 09:15:00'),
('3', '2', '9', 'Lê Hoàng Nam', '5', 'Nike Dunk Low Panda dáng cực kỳ dễ phối đồ, chất da mịn màng, mang đi học đi chơi đều nổi bật.', 'Cảm ơn bạn Nam đã tin yêu Shoes Store ạ!', '6', '1', '2026-08-14 16:45:00'),
('4', '3', '10', 'Phạm Thị Thúy', '5', 'Pegasus 40 đệm êm tuyệt hảo, mình chạy bộ mỗi sáng 5km không hề bị mỏi cổ chân hay đau gót.', 'Dạ chúc bạn Thúy luôn duy trì phong độ thể thao tràn đầy năng lượng cùng Pegasus 40 nha!', '2', '1', '2026-07-20 08:20:00'),
('5', '6', '11', 'Đặng Minh Khôi', '5', 'Adidas Samba OG mũi da lộn xịn đét, đế kẹo gum bám chắc, phối cùng quần ống rộng vintage siêu đẹp.', 'Shoes Store cảm ơn anh Khôi ạ! Mẫu Samba này đang là xu hướng hot nhất năm nay luôn đó ạ.', '6', '1', '2026-08-11 11:10:00'),
('6', '7', '12', 'Võ Thị Kiều Trang', '5', 'Ultraboost Light nhẹ hơn hẳn dòng cũ, đệm bọt BOOST nhún nhảy cực đã chân. Đáng đồng tiền bát gạo.', 'Dạ cảm ơn chị Trang đã dành tặng Shop đánh giá 5 sao ạ!', '2', '1', '2026-06-15 17:05:00'),
('7', '11', '13', 'Bùi Tuấn Anh', '5', 'Jordan 1 Chicago màu đỏ đen huyền thoại, da Tumbled mềm mại và logo Wings chạm nổi cực kỳ tinh xảo.', 'Cảm ơn anh Tuấn Anh! Mẫu Chicago này xứng đáng là bảo vật trong bộ sưu tập Sneaker của anh ạ!', '2', '1', '2026-05-18 19:30:00'),
('8', '12', '14', 'Nguyễn Thanh Huyền', '5', 'Jordan Travis Scott Reverse Mocha dấu Swoosh ngược chất ngất. Hàng chuẩn chỉnh từ đường kim mũi chỉ.', 'Dạ cảm ơn bạn Huyền nhiều nha ❤️', '6', '1', '2026-04-22 13:40:00'),
('9', '16', '15', 'Đoàn Ngọc Phúc', '5', 'New Balance 530 nhẹ bẫng, đệm ABZORB đi bộ cả ngày khám phá du lịch không hề ê buốt gót chân.', 'Cảm ơn bạn Phúc đã ủng hộ Shoes Store!', '2', '1', '2026-07-05 10:25:00'),
('10', '21', '16', 'Hoàng Thị Mai', '5', 'Converse Chuck 70s vải Canvas dày dặn, đế ngà bóng vintage rất đẹp, lót đệm êm hơn hẳn dòng classic.', 'Shoes Store cảm ơn bạn Mai rất nhiều ạ!', '6', '1', '2026-06-28 15:50:00'),
('11', '22', '7', 'Nguyễn Quốc Thái', '5', 'Run Star Hike đế răng cưa hack dáng cực đỉnh, mang vào chân trông dài và cá tính hẳn ra.', 'Dạ cảm ơn anh Thái tiếp tục ủng hộ sản phẩm của Shop ạ!', '2', '1', '2026-07-12 18:15:00'),
('12', '4', '8', 'Trần Thanh Trúc', '5', 'Air Max 90 phối màu Infrared hoài cổ đẹp mê ly, cửa sổ đệm khí đi rất êm và bền bỉ.', 'Cảm ơn bạn Trúc đã gửi feedback tuyệt vời cho Shop!', '2', '1', '2026-05-09 14:00:00'),
('13', '8', '9', 'Lê Hoàng Nam', '4', 'Stan Smith gót xanh lá tối giản, da trắng mịn. Giao hàng GHTK đúng hẹn.', 'Dạ Shoes Store cảm ơn bạn Nam ạ, chúc bạn luôn vui vẻ!', '6', '1', '2026-03-15 16:30:00'),
('14', '13', '10', 'Phạm Thị Thúy', '5', 'Jordan 4 Military Black phom cứng cáp hầm hố, đệm Air bảo vệ cổ chân cực an toàn.', 'Dạ cảm ơn bạn Thúy nhiều ạ!', '2', '1', '2026-04-10 11:45:00'),
('15', '17', '11', 'Đặng Minh Khôi', '5', 'NB 550 phong cách Retro bóng rổ thập niên 80 đỉnh chóp, mang đi làm hay đi cà phê đều hợp.', 'Cảm ơn anh Khôi đã tin tưởng Shop!', '6', '1', '2026-05-25 09:20:00');

-- ------------------------------------------------------------
-- Table structure for `employee_salaries`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `employee_salaries`;
CREATE TABLE `employee_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `month_year` varchar(20) NOT NULL,
  `base_salary` decimal(12,0) NOT NULL DEFAULT 5000000,
  `work_days` int(11) NOT NULL DEFAULT 26,
  `off_days` int(11) NOT NULL DEFAULT 0,
  `allowance` decimal(12,0) NOT NULL DEFAULT 500000,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 3.00,
  `commission_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `confirmed_orders_count` int(11) NOT NULL DEFAULT 0,
  `confirmed_sales_total` decimal(12,0) NOT NULL DEFAULT 0,
  `bonus` decimal(12,0) NOT NULL DEFAULT 0,
  `bonus_reason` varchar(255) DEFAULT NULL,
  `fine` decimal(12,0) NOT NULL DEFAULT 0,
  `fine_reason` varchar(255) DEFAULT NULL,
  `total_salary` decimal(12,0) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'unpaid',
  `payment_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_month_unique` (`employee_id`,`month_year`),
  KEY `fk_emp_salary` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employee_salaries` (20 rows)
INSERT INTO `employee_salaries` VALUES
('1', '1', '05/2026', '8500000', '26', '2', '600000', '2.00', '1274540', '22', '63727022', '700000', '0', '0', NULL, '11074540', 'paid', '2026-06-05', 'Lương kỳ 05/2026: Lương cứng 8,500,000đ + Hoa hồng 2% (1,274,540đ) + Thưởng 700,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('2', '1', '06/2026', '8500000', '26', '2', '600000', '2.00', '1021619', '13', '51080933', '800000', '0', '0', NULL, '10921619', 'paid', '2026-07-05', 'Lương kỳ 06/2026: Lương cứng 8,500,000đ + Hoa hồng 2% (1,021,619đ) + Thưởng 800,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('3', '1', '07/2026', '8500000', '26', '2', '600000', '2.00', '2084375', '22', '104218761', '300000', '0', '0', NULL, '11484375', 'paid', '2026-08-05', 'Lương kỳ 07/2026: Lương cứng 8,500,000đ + Hoa hồng 2% (2,084,375đ) + Thưởng 300,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('4', '1', '08/2026', '8500000', '16', '2', '600000', '2.00', '0', '0', '0', '400000', '0', '0', NULL, '6230769', 'pending', NULL, 'Lương kỳ 08/2026: Lương cứng 8,500,000đ + Hoa hồng 2% (923,446đ) + Thưởng 400,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('5', '2', '05/2026', '9000000', '26', '2', '600000', '1.50', '895671', '18', '59711375', '600000', '0', '0', NULL, '11095671', 'paid', '2026-06-05', 'Lương kỳ 05/2026: Lương cứng 9,000,000đ + Hoa hồng 1.5% (895,671đ) + Thưởng 600,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('6', '2', '06/2026', '9000000', '26', '2', '600000', '1.50', '988161', '21', '65877394', '600000', '0', '0', NULL, '11188161', 'paid', '2026-07-05', 'Lương kỳ 06/2026: Lương cứng 9,000,000đ + Hoa hồng 1.5% (988,161đ) + Thưởng 600,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('7', '2', '07/2026', '9000000', '26', '2', '600000', '1.50', '1342111', '22', '89474092', '500000', '0', '0', NULL, '11442111', 'paid', '2026-08-05', 'Lương kỳ 07/2026: Lương cứng 9,000,000đ + Hoa hồng 1.5% (1,342,111đ) + Thưởng 500,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('8', '2', '08/2026', '9000000', '16', '2', '600000', '1.50', '21750', '1', '1450000', '800000', '0', '0', NULL, '6960212', 'pending', NULL, 'Lương kỳ 08/2026: Lương cứng 9,000,000đ + Hoa hồng 1.5% (1,009,006đ) + Thưởng 800,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('9', '3', '05/2026', '9500000', '26', '2', '600000', '1.00', '645379', '22', '64537915', '300000', '0', '0', NULL, '11045379', 'paid', '2026-06-05', 'Lương kỳ 05/2026: Lương cứng 9,500,000đ + Hoa hồng 1% (645,379đ) + Thưởng 300,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('10', '3', '06/2026', '9500000', '26', '2', '600000', '1.00', '501033', '12', '50103255', '500000', '0', '0', NULL, '11101033', 'paid', '2026-07-05', 'Lương kỳ 06/2026: Lương cứng 9,500,000đ + Hoa hồng 1% (501,033đ) + Thưởng 500,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('11', '3', '07/2026', '9500000', '26', '2', '600000', '1.00', '699924', '19', '69992394', '500000', '0', '0', NULL, '11299924', 'paid', '2026-08-05', 'Lương kỳ 07/2026: Lương cứng 9,500,000đ + Hoa hồng 1% (699,924đ) + Thưởng 500,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('12', '3', '08/2026', '9500000', '16', '2', '600000', '1.00', '14500', '1', '1450000', '500000', '0', '0', NULL, '6960654', 'pending', NULL, 'Lương kỳ 08/2026: Lương cứng 9,500,000đ + Hoa hồng 1% (319,107đ) + Thưởng 500,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('13', '4', '05/2026', '7500000', '26', '2', '600000', '1.50', '949673', '18', '63311510', '400000', '0', '0', NULL, '9449673', 'paid', '2026-06-05', 'Lương kỳ 05/2026: Lương cứng 7,500,000đ + Hoa hồng 1.5% (949,673đ) + Thưởng 400,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('14', '4', '06/2026', '7500000', '26', '2', '600000', '1.50', '659342', '12', '43956165', '800000', '0', '0', NULL, '9559342', 'paid', '2026-07-05', 'Lương kỳ 06/2026: Lương cứng 7,500,000đ + Hoa hồng 1.5% (659,342đ) + Thưởng 800,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('15', '4', '07/2026', '7500000', '26', '2', '600000', '1.50', '1177641', '20', '78509424', '300000', '0', '0', NULL, '9577641', 'paid', '2026-08-05', 'Lương kỳ 07/2026: Lương cứng 7,500,000đ + Hoa hồng 1.5% (1,177,641đ) + Thưởng 300,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('16', '4', '08/2026', '7500000', '16', '2', '600000', '1.50', '96000', '1', '6400000', '600000', '0', '0', NULL, '5911385', 'pending', NULL, 'Lương kỳ 08/2026: Lương cứng 7,500,000đ + Hoa hồng 1.5% (781,516đ) + Thưởng 600,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('17', '5', '05/2026', '8000000', '26', '2', '600000', '2.50', '1304863', '15', '52194516', '400000', '0', '0', NULL, '10304863', 'paid', '2026-06-05', 'Lương kỳ 05/2026: Lương cứng 8,000,000đ + Hoa hồng 2.5% (1,304,863đ) + Thưởng 400,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('18', '5', '06/2026', '8000000', '26', '2', '600000', '2.50', '1180563', '11', '47222505', '800000', '0', '0', NULL, '10580563', 'paid', '2026-07-05', 'Lương kỳ 06/2026: Lương cứng 8,000,000đ + Hoa hồng 2.5% (1,180,563đ) + Thưởng 800,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('19', '5', '07/2026', '8000000', '26', '2', '600000', '2.50', '1188682', '14', '47547268', '300000', '0', '0', NULL, '10088682', 'paid', '2026-08-05', 'Lương kỳ 07/2026: Lương cứng 8,000,000đ + Hoa hồng 2.5% (1,188,682đ) + Thưởng 300,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47'),
('20', '5', '08/2026', '8000000', '16', '2', '600000', '2.50', '342500', '2', '13700000', '600000', '0', '0', NULL, '6465577', 'pending', NULL, 'Lương kỳ 08/2026: Lương cứng 8,000,000đ + Hoa hồng 2.5% (1,500,087đ) + Thưởng 600,000đ + Phụ cấp 600,000đ', '2026-08-18 14:00:47');

-- ------------------------------------------------------------
-- Table structure for `employee_schedules`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `employee_schedules`;
CREATE TABLE `employee_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `work_date` date DEFAULT NULL,
  `shift_name` varchar(100) DEFAULT 'Ca Sáng (07:30 - 12:00)',
  `start_time` time DEFAULT '07:30:00',
  `end_time` time DEFAULT '12:00:00',
  `status` varchar(50) DEFAULT 'active',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employee_schedules` (70 rows)
INSERT INTO `employee_schedules` VALUES
('1', '1', 'Thứ Hai', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('2', '1', 'Thứ Ba', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('3', '1', 'Thứ Tư', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('4', '1', 'Thứ Năm', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('5', '1', 'Thứ Sáu', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('6', '1', 'Thứ Bảy', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('7', '1', 'Chủ Nhật', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'off', 'Nghỉ hàng tuần theo quy định', '2026-08-18 14:00:47'),
('8', '1', 'Thứ Hai', '2026-08-17', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('9', '1', 'Thứ Ba', '2026-08-18', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('10', '1', 'Thứ Tư', '2026-08-19', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('11', '1', 'Thứ Năm', '2026-08-20', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('12', '1', 'Thứ Sáu', '2026-08-21', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('13', '1', 'Thứ Bảy', '2026-08-22', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Trưởng ca phụ trách quầy tư vấn', '2026-08-18 14:00:47'),
('14', '1', 'Chủ Nhật', '2026-08-23', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'off', 'Nghỉ tuần', '2026-08-18 14:00:47'),
('15', '2', 'Thứ Hai', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'off', 'Nghỉ hàng tuần theo quy định', '2026-08-18 14:00:47'),
('16', '2', 'Thứ Ba', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('17', '2', 'Thứ Tư', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('18', '2', 'Thứ Năm', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('19', '2', 'Thứ Sáu', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('20', '2', 'Thứ Bảy', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('21', '2', 'Chủ Nhật', NULL, 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('22', '2', 'Thứ Hai', '2026-08-17', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'off', 'Nghỉ tuần', '2026-08-18 14:00:47'),
('23', '2', 'Thứ Ba', '2026-08-18', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('24', '2', 'Thứ Tư', '2026-08-19', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('25', '2', 'Thứ Năm', '2026-08-20', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('26', '2', 'Thứ Sáu', '2026-08-21', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('27', '2', 'Thứ Bảy', '2026-08-22', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('28', '2', 'Chủ Nhật', '2026-08-23', 'Ca Chiều (14:00 - 22:00)', '14:00:00', '22:00:00', 'active', 'Kiểm kê nhập xuất kho giày', '2026-08-18 14:00:47'),
('29', '3', 'Thứ Hai', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('30', '3', 'Thứ Ba', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('31', '3', 'Thứ Tư', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('32', '3', 'Thứ Năm', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('33', '3', 'Thứ Sáu', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('34', '3', 'Thứ Bảy', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('35', '3', 'Chủ Nhật', NULL, 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'off', 'Nghỉ hàng tuần theo quy định', '2026-08-18 14:00:47'),
('36', '3', 'Thứ Hai', '2026-08-17', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('37', '3', 'Thứ Ba', '2026-08-18', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('38', '3', 'Thứ Tư', '2026-08-19', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('39', '3', 'Thứ Năm', '2026-08-20', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('40', '3', 'Thứ Sáu', '2026-08-21', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('41', '3', 'Thứ Bảy', '2026-08-22', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'active', 'Xử lý chứng từ & đối soát đơn hàng', '2026-08-18 14:00:47'),
('42', '3', 'Chủ Nhật', '2026-08-23', 'Ca Hành Chính (08:00 - 17:00)', '08:00:00', '17:00:00', 'off', 'Nghỉ tuần', '2026-08-18 14:00:47'),
('43', '4', 'Thứ Hai', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('44', '4', 'Thứ Ba', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('45', '4', 'Thứ Tư', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'off', 'Nghỉ hàng tuần theo quy định', '2026-08-18 14:00:47'),
('46', '4', 'Thứ Năm', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('47', '4', 'Thứ Sáu', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('48', '4', 'Thứ Bảy', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('49', '4', 'Chủ Nhật', NULL, 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('50', '4', 'Thứ Hai', '2026-08-17', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('51', '4', 'Thứ Ba', '2026-08-18', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('52', '4', 'Thứ Tư', '2026-08-19', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'off', 'Nghỉ tuần', '2026-08-18 14:00:47'),
('53', '4', 'Thứ Năm', '2026-08-20', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('54', '4', 'Thứ Sáu', '2026-08-21', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('55', '4', 'Thứ Bảy', '2026-08-22', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('56', '4', 'Chủ Nhật', '2026-08-23', 'Ca Tối (16:00 - 22:00)', '16:00:00', '22:00:00', 'active', 'Đóng gói bưu phẩm bưu tá lấy hàng', '2026-08-18 14:00:47'),
('57', '5', 'Thứ Hai', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('58', '5', 'Thứ Ba', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('59', '5', 'Thứ Tư', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('60', '5', 'Thứ Năm', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'off', 'Nghỉ hàng tuần theo quy định', '2026-08-18 14:00:47'),
('61', '5', 'Thứ Sáu', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('62', '5', 'Thứ Bảy', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('63', '5', 'Chủ Nhật', NULL, 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('64', '5', 'Thứ Hai', '2026-08-17', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('65', '5', 'Thứ Ba', '2026-08-18', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('66', '5', 'Thứ Tư', '2026-08-19', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('67', '5', 'Thứ Năm', '2026-08-20', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'off', 'Nghỉ tuần', '2026-08-18 14:00:47'),
('68', '5', 'Thứ Sáu', '2026-08-21', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('69', '5', 'Thứ Bảy', '2026-08-22', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47'),
('70', '5', 'Chủ Nhật', '2026-08-23', 'Ca Sáng (08:00 - 16:00)', '08:00:00', '16:00:00', 'active', 'Livestream & tư vấn phối đồ Sneaker', '2026-08-18 14:00:47');

-- ------------------------------------------------------------
-- Table structure for `employees`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `citizen_id` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `position` varchar(100) DEFAULT 'Nhân viên bán hàng',
  `department` varchar(100) DEFAULT 'Kinh Doanh & Bán Hàng',
  `work_shift` varchar(100) DEFAULT 'Ca 1 (08:00 - 16:00)',
  `base_salary` decimal(12,0) DEFAULT 5000000,
  `commission_rate` decimal(5,2) DEFAULT 3.00,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employees` (5 rows)
INSERT INTO `employees` VALUES
('1', '2', 'Nguyễn Ngọc Lan', '0908123456', 'nv_ngoclan@shoesstore.vn', '087198001111', 'Phường 4, TP. Vĩnh Long', NULL, 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=300', 'Trưởng ca tư vấn', 'Bán hàng', 'Ca Sáng (08:00 - 16:00)', '8500000', '2.00', '26', '2', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-18 13:31:27'),
('2', '3', 'Trần Quang Huy', '0908234567', 'nv_quanghuy@shoesstore.vn', '087195002222', 'Phường 2, TP. Vĩnh Long', NULL, 'https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=300', 'Quản lý kho vận', 'Kho vận', 'Ca Chiều (14:00 - 22:00)', '9000000', '1.50', '26', '2', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-18 13:31:27'),
('3', '4', 'Lê Thị Thu Hà', '0908345678', 'nv_thuha@shoesstore.vn', '087197003333', 'Phường 1, TP. Vĩnh Long', NULL, 'https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=300', 'Kế toán đơn hàng', 'Kế toán', 'Ca Hành chính (08:00 - 17:00)', '9500000', '1.00', '26', '2', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-18 13:31:27'),
('4', '5', 'Phạm Minh Đức', '0908456789', 'nv_minhduc@shoesstore.vn', '087199004444', 'Phường 8, TP. Vĩnh Long', NULL, 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=300', 'Đóng gói & CSKH', 'Chăm sóc khách hàng', 'Ca Tối (16:00 - 22:00)', '7500000', '1.50', '26', '2', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-18 13:31:27'),
('5', '6', 'Hoàng Quốc Nam', '0908567890', 'nv_hoangnam@shoesstore.vn', '087196005555', 'Phường 3, TP. Vĩnh Long', NULL, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=300', 'Chuyên viên Sneaker', 'Bán hàng', 'Ca Sáng (08:00 - 16:00)', '8000000', '2.50', '26', '2', NULL, '0', NULL, '0', NULL, NULL, '1', '2026-08-18 13:31:27');

-- ------------------------------------------------------------
-- Table structure for `event_products`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `event_products`;
CREATE TABLE `event_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 0,
  `sale_price` decimal(12,0) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `event_price` decimal(12,0) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evt_prod` (`event_id`,`product_id`),
  KEY `fk_evt_prod_prod` (`product_id`),
  CONSTRAINT `ep_sale_event_fk` FOREIGN KEY (`event_id`) REFERENCES `sale_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evt_prod_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `event_products` (100 rows)
INSERT INTO `event_products` VALUES
('19', '4', '7', '19', '3199500', '1', '3199500'),
('20', '4', '8', '20', '1720000', '2', '1720000'),
('21', '4', '9', '31', '1587000', '3', '1587000'),
('22', '4', '10', '19', '1174500', '4', '1174500'),
('23', '4', '11', '34', '3069000', '5', '3069000'),
('24', '4', '12', '27', '4234000', '6', '4234000'),
('25', '5', '9', '33', '1541000', '1', '1541000'),
('26', '5', '10', '34', '957000', '2', '957000'),
('27', '5', '11', '32', '3162000', '3', '3162000'),
('28', '5', '12', '29', '4118000', '4', '4118000'),
('29', '5', '13', '20', '4160000', '5', '4160000'),
('30', '5', '14', '30', '2555000', '6', '2555000'),
('31', '6', '11', '23', '3580500', '1', '3580500'),
('32', '6', '12', '26', '4292000', '2', '4292000'),
('33', '6', '13', '35', '3380000', '3', '3380000'),
('34', '6', '14', '31', '2518500', '4', '2518500'),
('35', '6', '15', '27', '985500', '5', '985500'),
('36', '6', '16', '25', '1837500', '6', '1837500'),
('37', '7', '13', '32', '3536000', '1', '3536000'),
('38', '7', '14', '28', '2628000', '2', '2628000'),
('39', '7', '15', '15', '1147500', '3', '1147500'),
('40', '7', '16', '28', '1764000', '4', '1764000'),
('41', '7', '17', '33', '1936300', '5', '1936300'),
('42', '7', '18', '20', '3400000', '6', '3400000'),
('43', '8', '15', '35', '877500', '1', '877500'),
('44', '8', '16', '29', '1739500', '2', '1739500'),
('45', '8', '17', '15', '2456500', '3', '2456500'),
('46', '8', '18', '34', '2805000', '4', '2805000'),
('47', '8', '19', '25', '4200000', '5', '4200000'),
('48', '8', '20', '27', '2445500', '6', '2445500'),
('49', '9', '17', '22', '2254200', '1', '2254200'),
('50', '9', '18', '25', '3187500', '2', '3187500'),
('51', '9', '19', '25', '4200000', '3', '4200000'),
('52', '9', '20', '18', '2747000', '4', '2747000'),
('53', '9', '21', '21', '1461500', '5', '1461500'),
('54', '9', '22', '24', '2014000', '6', '2014000'),
('55', '10', '19', '18', '4592000', '1', '4592000'),
('56', '10', '20', '24', '2546000', '2', '2546000'),
('57', '10', '21', '35', '1202500', '3', '1202500'),
('58', '10', '22', '32', '1802000', '4', '1802000'),
('59', '10', '23', '33', '1172500', '5', '1172500'),
('60', '10', '24', '31', '1345500', '6', '1345500'),
('61', '11', '21', '26', '1369000', '1', '1369000'),
('62', '11', '22', '20', '2120000', '2', '2120000'),
('63', '11', '23', '28', '1260000', '3', '1260000'),
('64', '11', '24', '33', '1306500', '4', '1306500'),
('65', '11', '25', '33', '971500', '5', '971500'),
('66', '11', '1', '30', '2050300', '6', '2050300'),
('67', '12', '23', '33', '1172500', '1', '1172500'),
('68', '12', '24', '33', '1306500', '2', '1306500'),
('69', '12', '25', '25', '1087500', '3', '1087500'),
('70', '12', '1', '19', '2372490', '4', '2372490'),
('71', '12', '2', '19', '2592000', '5', '2592000'),
('72', '12', '3', '23', '2387000', '6', '2387000'),
('73', '13', '25', '15', '1232500', '1', '1232500'),
('74', '13', '1', '19', '2372490', '2', '2372490'),
('75', '13', '2', '20', '2560000', '3', '2560000'),
('76', '13', '3', '15', '2635000', '4', '2635000'),
('77', '13', '4', '31', '2380500', '5', '2380500'),
('78', '13', '5', '24', '950000', '6', '950000'),
('79', '1', '1', '35', '1903850', '0', '1903850'),
('80', '1', '2', '30', '2240000', '1', '2240000'),
('81', '1', '3', '28', '2232000', '2', '2232000'),
('82', '1', '7', '25', '2962500', '3', '2962500'),
('83', '1', '11', '20', '3720000', '4', '3720000'),
('84', '1', '16', '32', '1666000', '5', '1666000'),
('85', '1', '17', '28', '2080800', '6', '2080800'),
('86', '1', '21', '35', '1202500', '7', '1202500'),
('88', '2', '4', '20', '2760000', '0', '2760000'),
('89', '2', '6', '22', '2145000', '1', '2145000'),
('90', '2', '8', '25', '1612500', '2', '1612500'),
('91', '2', '9', '18', '1886000', '3', '1886000'),
('92', '2', '14', '20', '2920000', '4', '2920000'),
('93', '2', '15', '28', '972000', '5', '972000'),
('94', '2', '20', '25', '2512500', '6', '2512500'),
('95', '2', '23', '30', '1225000', '7', '1225000'),
('96', '3', '5', '40', '750000', '0', '750000'),
('97', '3', '10', '35', '942500', '1', '942500'),
('98', '3', '13', '18', '4264000', '2', '4264000'),
('99', '3', '18', '22', '3315000', '3', '3315000'),
('100', '3', '19', '15', '4760000', '4', '4760000'),
('101', '3', '22', '28', '1908000', '5', '1908000'),
('102', '3', '24', '25', '1462500', '6', '1462500'),
('103', '3', '25', '30', '1015000', '7', '1015000'),
('104', '14', '4', '20', '2760000', '0', '2760000'),
('105', '14', '6', '22', '2145000', '0', '2145000'),
('106', '14', '8', '25', '1612500', '0', '1612500'),
('107', '14', '9', '18', '1886000', '0', '1886000'),
('108', '14', '14', '20', '2920000', '0', '2920000'),
('109', '14', '15', '28', '972000', '0', '972000'),
('110', '14', '20', '25', '2512500', '0', '2512500'),
('111', '14', '23', '30', '1225000', '0', '1225000'),
('112', '15', '5', '40', '750000', '0', '750000'),
('113', '15', '10', '35', '942500', '0', '942500'),
('114', '15', '13', '18', '4264000', '0', '4264000'),
('115', '15', '18', '22', '3315000', '0', '3315000'),
('116', '15', '19', '15', '4760000', '0', '4760000'),
('117', '15', '22', '28', '1908000', '0', '1908000'),
('118', '15', '24', '25', '1462500', '0', '1462500'),
('119', '15', '25', '30', '1015000', '0', '1015000');

-- ------------------------------------------------------------
-- Table structure for `event_vouchers`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `event_vouchers`;
CREATE TABLE `event_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evt_vouch` (`event_id`,`voucher_id`),
  KEY `fk_evt_vouch_vouch` (`voucher_id`),
  CONSTRAINT `fk_evt_vouch_evt` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evt_vouch_vouch` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `event_vouchers` (13 rows)
INSERT INTO `event_vouchers` VALUES
('1', '1', '4'),
('2', '2', '5'),
('3', '3', '6'),
('4', '4', '7'),
('5', '5', '8'),
('6', '6', '9'),
('7', '7', '10'),
('8', '8', '11'),
('9', '9', '12'),
('10', '10', '13'),
('11', '11', '14'),
('12', '12', '15'),
('13', '13', '16');

-- ------------------------------------------------------------
-- Table structure for `events`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `banner_img` varchar(550) DEFAULT NULL,
  `badge_text` varchar(100) DEFAULT 'SỰ KIỆN KHUYẾN MÃI',
  `event_type` varchar(50) DEFAULT 'double_day',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `events` (3 rows)
INSERT INTO `events` VALUES
('1', '🇻🇳 SALE QUỐC KHÁNH 19/8 – GIẢM GIÁ SỐC 1 NGÀY DUY NHẤT', 'Chào mừng Quốc Khánh 19/8! Ưu đãi đặc biệt chỉ diễn ra trong 24 giờ ngày 19/8 – Săn deal ngay kẻo lỡ!', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=1200', 'SALE 19/8 – 1 NGÀY', 'special_day', '2026-08-19 00:00:00', '2026-08-19 23:59:59', '1', '2026-08-10 22:27:25'),
('2', '⚡ SALE 18/8 – NGÀY MAI LÀ 19/8 – SĂN DEAL SỚM', 'Đừng đợi đến 19/8! Khởi động chuỗi ưu đãi từ ngày 18/8 – Deal cực sốc chỉ trong 24 giờ hôm nay!', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200', 'SALE 18/8 – 1 NGÀY', 'special_day', '2026-08-18 00:00:00', '2026-08-18 23:59:59', '1', '2026-08-18 15:04:37'),
('3', '🕐 GIỜ VÀNG SHOESSTORE – DEAL SỐC 2 TIẾNG MỖI TỐI', 'Mỗi tối từ 20:00 – 22:00, hàng loạt sản phẩm giảm giá THÊM 5-15% – Đặt báo thức và săn deal ngay!', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=1200', '⏰ GIỜ VÀNG 20:00-22:00', 'flash_sale', '2026-08-18 20:00:00', '2026-12-31 22:00:00', '1', '2026-08-18 15:04:37');

-- ------------------------------------------------------------
-- Table structure for `menu_items`
-- ------------------------------------------------------------
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

-- Dumping data for table `menu_items` (3 rows)
INSERT INTO `menu_items` VALUES
('1', NULL, 'Trang Chủ', 'index.php', 'fa-solid fa-house', 'main', '1', '1', '2026-08-04 18:50:22'),
('2', NULL, 'Sản Phẩm', 'all-products.php', 'fa-solid fa-shoe-prints', 'main', '2', '1', '2026-08-04 18:50:22'),
('3', NULL, 'Giảm Giá', 'all-products.php?discount=1', 'fa-solid fa-fire', 'main', '3', '1', '2026-08-04 18:50:22');

-- ------------------------------------------------------------
-- Table structure for `order_details`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `order_details` (50 rows)
INSERT INTO `order_details` VALUES
('1', '1', '1', NULL, 'Nike Air Force 1 \'07 All White', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '39', 'Trắng Tinh (Triple White)', '2', '2929000', '1800000'),
('2', '2', '20', NULL, 'New Balance 1906R Silver Metallic', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '40', 'Bạc Ánh Kim / Đen', '2', '3350000', '2050000'),
('3', '2', '17', NULL, 'New Balance 550 White Grey Vintage', 'https://images.unsplash.com/photo-1551107696-a4b0c5a0d9a2?q=80&w=800', '40', 'Trắng / Xám Nhạt (Sea Salt Grey)', '1', '2890000', '1750000'),
('4', '3', '11', NULL, 'Nike Air Jordan 1 Retro High OG \'Chicago\'', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', '42', 'Đỏ Varsity / Trắng / Đen', '2', '4650000', '3100000'),
('5', '3', '9', NULL, 'Adidas Superstar 80s Core Black', 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?q=80&w=800', '40', 'Đen / 3 Sọc Trắng', '1', '2300000', '1400000'),
('6', '4', '5', NULL, 'Nike Calm Slide Sandal Đen', 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?q=80&w=800', '39', 'Đen Mờ (Matte Black)', '1', '1250000', '750000'),
('7', '4', '4', NULL, 'Nike Air Max 90 Infrared Heritage', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=800', '42', 'Trắng / Xám / Đỏ Cam', '2', '3450000', '2100000'),
('8', '5', '2', NULL, 'Nike Dunk Low Retro \'White Black\' Panda', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '42', 'Trắng / Đen Panda', '1', '3200000', '2000000'),
('9', '5', '15', NULL, 'Jordan Hydro 8 Slide Quai Dán Đen', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '41', 'Đen / Logo Jumpman Trắng', '2', '1350000', '800000'),
('10', '6', '15', NULL, 'Jordan Hydro 8 Slide Quai Dán Đen', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '42', 'Đen / Logo Jumpman Trắng', '1', '1350000', '800000'),
('11', '7', '21', NULL, 'Converse Chuck Taylor All Star 1970s High Top Black', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '41', 'Đen Classic / Đế Ngà Vintage', '2', '1850000', '1100000'),
('12', '8', '25', NULL, 'Converse Chuck Taylor All Star Classic Navy', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '41', 'Xanh Navy Classic', '1', '1450000', '850000'),
('13', '8', '13', NULL, 'Air Jordan 4 Retro \'Military Black\'', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '44', 'Trắng / Đen / Xám Nhạt', '2', '5200000', '3400000'),
('14', '9', '19', NULL, 'New Balance 990v5 Made in USA Grey', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '45', 'Xám Di Sản (Heritage Grey)', '1', '5600000', '3600000'),
('15', '9', '2', NULL, 'Nike Dunk Low Retro \'White Black\' Panda', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '42', 'Trắng / Đen Panda', '1', '3200000', '2000000'),
('16', '10', '4', NULL, 'Nike Air Max 90 Infrared Heritage', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=800', '43', 'Trắng / Xám / Đỏ Cam', '2', '3450000', '2100000'),
('17', '11', '20', NULL, 'New Balance 1906R Silver Metallic', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '40', 'Bạc Ánh Kim / Đen', '1', '3350000', '2050000'),
('18', '12', '12', NULL, 'Air Jordan 1 Low \'Reverse Mocha\' Travis Scott', 'https://images.unsplash.com/photo-1575537302964-96cd47c06b1b?q=80&w=800', '41', 'Nâu Mocha / Trắng Kem / Đỏ', '1', '5800000', '3800000'),
('19', '13', '15', NULL, 'Jordan Hydro 8 Slide Quai Dán Đen', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '43', 'Đen / Logo Jumpman Trắng', '2', '1350000', '800000'),
('20', '13', '12', NULL, 'Air Jordan 1 Low \'Reverse Mocha\' Travis Scott', 'https://images.unsplash.com/photo-1575537302964-96cd47c06b1b?q=80&w=800', '44', 'Nâu Mocha / Trắng Kem / Đỏ', '2', '5800000', '3800000'),
('21', '14', '2', NULL, 'Nike Dunk Low Retro \'White Black\' Panda', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '40', 'Trắng / Đen Panda', '1', '3200000', '2000000'),
('22', '15', '9', NULL, 'Adidas Superstar 80s Core Black', 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?q=80&w=800', '43', 'Đen / 3 Sọc Trắng', '1', '2300000', '1400000'),
('23', '16', '19', NULL, 'New Balance 990v5 Made in USA Grey', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '44', 'Xám Di Sản (Heritage Grey)', '1', '5600000', '3600000'),
('24', '17', '3', NULL, 'Nike Air Pegasus 40 Running Shoes', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '40', 'Đỏ Năng Động (Infrared)', '2', '3100000', '1900000'),
('25', '18', '2', NULL, 'Nike Dunk Low Retro \'White Black\' Panda', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '37', 'Trắng / Đen Panda', '1', '3200000', '2000000'),
('26', '19', '25', NULL, 'Converse Chuck Taylor All Star Classic Navy', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '39', 'Xanh Navy Classic', '1', '1450000', '850000'),
('27', '20', '7', NULL, 'Adidas Ultraboost Light Running', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '43', 'Trắng Tinh Khôi (Core White)', '1', '3950000', '2500000'),
('28', '20', '22', NULL, 'Converse Run Star Hike High Top White', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '38', 'Trắng / Đế Răng Cưa Gum', '1', '2650000', '1600000'),
('29', '21', '18', NULL, 'New Balance 2002R Protection Pack Rain Cloud', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '41', 'Xám Khói (Rain Cloud)', '2', '4250000', '2700000'),
('30', '22', '16', NULL, 'New Balance 530 Steel Grey Retro', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '39', 'Xám Bạc Ánh Kim (Steel Grey)', '2', '2450000', '1500000'),
('31', '23', '20', NULL, 'New Balance 1906R Silver Metallic', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '42', 'Bạc Ánh Kim / Đen', '1', '3350000', '2050000'),
('32', '23', '8', NULL, 'Adidas Stan Smith Classic Green', 'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?q=80&w=800', '39', 'Trắng / Gót Xanh Lá (Fairway Green)', '1', '2150000', '1300000'),
('33', '24', '15', NULL, 'Jordan Hydro 8 Slide Quai Dán Đen', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '43', 'Đen / Logo Jumpman Trắng', '2', '1350000', '800000'),
('34', '24', '4', NULL, 'Nike Air Max 90 Infrared Heritage', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=800', '42', 'Trắng / Xám / Đỏ Cam', '2', '3450000', '2100000'),
('35', '25', '8', NULL, 'Adidas Stan Smith Classic Green', 'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?q=80&w=800', '36', 'Trắng / Gót Xanh Lá (Fairway Green)', '2', '2150000', '1300000'),
('36', '25', '10', NULL, 'Adidas Adilette 22 Slides Futuristic', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=800', '39', 'Xanh Rêu Địa Hình (Magic Lime)', '1', '1450000', '850000'),
('37', '26', '13', NULL, 'Air Jordan 4 Retro \'Military Black\'', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '41', 'Trắng / Đen / Xám Nhạt', '1', '5200000', '3400000'),
('38', '27', '11', NULL, 'Nike Air Jordan 1 Retro High OG \'Chicago\'', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', '39', 'Đỏ Varsity / Trắng / Đen', '1', '4650000', '3100000'),
('39', '27', '9', NULL, 'Adidas Superstar 80s Core Black', 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?q=80&w=800', '38', 'Đen / 3 Sọc Trắng', '2', '2300000', '1400000'),
('40', '28', '16', NULL, 'New Balance 530 Steel Grey Retro', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '38', 'Xám Bạc Ánh Kim (Steel Grey)', '1', '2450000', '1500000'),
('41', '29', '1', NULL, 'Nike Air Force 1 \'07 All White', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '38', 'Trắng Tinh (Triple White)', '2', '2929000', '1800000'),
('42', '29', '6', NULL, 'Adidas Samba OG White Black Gum', 'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?q=80&w=800', '42', 'Trắng / 3 Sọc Đen / Đế Gum', '1', '2750000', '1700000'),
('43', '30', '16', NULL, 'New Balance 530 Steel Grey Retro', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '42', 'Xám Bạc Ánh Kim (Steel Grey)', '1', '2450000', '1500000'),
('44', '31', '20', NULL, 'New Balance 1906R Silver Metallic', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '40', 'Bạc Ánh Kim / Đen', '2', '3350000', '2050000'),
('45', '31', '22', NULL, 'Converse Run Star Hike High Top White', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '41', 'Trắng / Đế Răng Cưa Gum', '2', '2650000', '1600000'),
('46', '32', '10', NULL, 'Adidas Adilette 22 Slides Futuristic', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=800', '39', 'Xanh Rêu Địa Hình (Magic Lime)', '1', '1450000', '850000'),
('47', '33', '25', NULL, 'Converse Chuck Taylor All Star Classic Navy', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '38', 'Xanh Navy Classic', '1', '1450000', '850000'),
('48', '33', '20', NULL, 'New Balance 1906R Silver Metallic', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '39', 'Bạc Ánh Kim / Đen', '1', '3350000', '2050000'),
('49', '34', '2', NULL, 'Nike Dunk Low Retro \'White Black\' Panda', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '39', 'Trắng / Đen Panda', '2', '3200000', '2000000'),
('50', '35', '14', NULL, 'Air Jordan 1 Mid Triple White Clean', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '43', 'Trắng Tinh (Triple White)', '2', '3650000', '2300000');

-- ------------------------------------------------------------
-- Table structure for `orders`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_detail` varchar(500) NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `shipping_carrier` varchar(50) DEFAULT 'LOCAL',
  `tracking_code` varchar(100) DEFAULT NULL,
  `carrier_order_id` varchar(100) DEFAULT NULL,
  `shipping_label_url` varchar(500) DEFAULT NULL,
  `carrier_status_text` varchar(100) DEFAULT NULL,
  `shipping_fee` decimal(12,0) DEFAULT 30000,
  `subtotal` decimal(12,0) NOT NULL,
  `discount_amount` decimal(12,0) DEFAULT 0,
  `voucher_code` varchar(50) DEFAULT NULL,
  `total_money` decimal(12,0) NOT NULL,
  `payment_method` enum('COD','BANKING_QR') DEFAULT 'COD',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `status` enum('pending','confirmed','shipping','completed','returning','cancelled') DEFAULT 'pending',
  `staff_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `return_reason` varchar(255) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `shipping_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `carrier_step` int(11) DEFAULT 1,
  `carrier_timeline_json` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `user_id` (`user_id`),
  KEY `province_id` (`province_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `shipping_provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `orders` (35 rows)
INSERT INTO `orders` VALUES
('1', 'ORD202601001', '7', 'Nguyễn Quốc Thái', '0913111222', 'Số 45 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', NULL, 'GHTK', 'GHTK689218213VN', NULL, NULL, NULL, '30000', '5858000', '0', NULL, '5888000', 'BANKING_QR', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-01-05 10:51:15', '2026-01-06 08:51:15', '2026-01-08 08:51:15', NULL, '2026-01-05 08:51:15', '5', NULL),
('2', 'ORD202601002', '11', 'Đặng Minh Khôi', '0913555666', 'Số 99 Trưng Nữ Vương, Phường 1, TP. Vĩnh Long', NULL, 'GHN', 'GHN346962734VN', NULL, NULL, NULL, '30000', '9590000', '0', NULL, '9620000', 'COD', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-01-16 21:55:25', '2026-01-17 19:55:25', '2026-01-19 19:55:25', NULL, '2026-01-16 19:55:25', '5', NULL),
('3', 'ORD202601003', '7', 'Nguyễn Quốc Thái', '0913111222', 'Số 45 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', NULL, 'GHTK', 'GHTK834758579VN', NULL, NULL, NULL, '30000', '11600000', '0', NULL, '11630000', 'BANKING_QR', 'paid', 'completed', '4', NULL, NULL, NULL, '2026-01-28 22:35:11', '2026-01-29 20:35:11', '2026-01-31 20:35:11', NULL, '2026-01-28 20:35:11', '5', NULL),
('4', 'ORD202601004', '14', 'Nguyễn Thanh Huyền', '0913888999', 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', NULL, 'ViettelPost', 'ViettelPost390741088VN', NULL, NULL, NULL, '30000', '8150000', '0', NULL, '8180000', 'COD', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-01-13 17:51:56', '2026-01-14 15:51:56', '2026-01-16 15:51:56', NULL, '2026-01-13 15:51:56', '5', NULL),
('5', 'ORD202602005', '14', 'Nguyễn Thanh Huyền', '0913888999', 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', NULL, 'GHN', 'GHN737834566VN', NULL, NULL, NULL, '30000', '5900000', '0', NULL, '5930000', 'COD', 'paid', 'completed', '6', NULL, NULL, NULL, '2026-02-02 10:15:44', '2026-02-03 08:15:44', '2026-02-05 08:15:44', NULL, '2026-02-02 08:15:44', '5', NULL),
('6', 'ORD202602006', '13', 'Bùi Tuấn Anh', '0913777888', 'Số 23 Đại Lộ Bình Dương, TP. Thủ Dầu Một, Bình Dương', NULL, 'GHN', 'GHN714411197VN', NULL, NULL, NULL, '0', '1350000', '50000', 'FREESHIP', '1300000', 'BANKING_QR', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-02-14 11:17:45', '2026-02-15 09:17:45', '2026-02-17 09:17:45', NULL, '2026-02-14 09:17:45', '5', NULL),
('7', 'ORD202602007', '16', 'Hoàng Thị Mai', '0913123789', 'Số 18 Lê Lợi, TP. Huế, Thừa Thiên Huế', NULL, 'ViettelPost', 'ViettelPost878624673VN', NULL, NULL, NULL, '30000', '3700000', '0', NULL, '3730000', 'COD', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-02-05 17:51:10', '2026-02-06 15:51:10', '2026-02-08 15:51:10', NULL, '2026-02-05 15:51:10', '5', NULL),
('8', 'ORD202602008', '7', 'Nguyễn Quốc Thái', '0913111222', 'Số 45 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', NULL, 'GHTK', 'GHTK681335281VN', NULL, NULL, NULL, '30000', '11850000', '0', NULL, '11880000', 'COD', 'paid', 'completed', '4', NULL, NULL, NULL, '2026-02-23 13:50:16', '2026-02-24 11:50:16', '2026-02-26 11:50:16', NULL, '2026-02-23 11:50:16', '5', NULL),
('9', 'ORD202603009', '10', 'Phạm Thị Thúy', '0913444555', 'Số 15 Đại Lộ Hòa Bình, Quận Ninh Kiều, Cần Thơ', NULL, 'GHTK', 'GHTK583665255VN', NULL, NULL, NULL, '30000', '8800000', '0', NULL, '8830000', 'BANKING_QR', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-03-10 18:38:49', '2026-03-11 16:38:49', '2026-03-13 16:38:49', NULL, '2026-03-10 16:38:49', '5', NULL),
('10', 'ORD202603010', '11', 'Đặng Minh Khôi', '0913555666', 'Số 99 Trưng Nữ Vương, Phường 1, TP. Vĩnh Long', NULL, 'GHN', 'GHN192267982VN', NULL, NULL, NULL, '0', '6900000', '50000', 'FREESHIP', '6850000', 'COD', 'paid', 'completed', '6', NULL, NULL, NULL, '2026-03-02 21:34:50', '2026-03-03 19:34:50', '2026-03-05 19:34:50', NULL, '2026-03-02 19:34:50', '5', NULL),
('11', 'ORD202603011', '16', 'Hoàng Thị Mai', '0913123789', 'Số 18 Lê Lợi, TP. Huế, Thừa Thiên Huế', NULL, 'J&T Express', 'J&T Express282820801VN', NULL, NULL, NULL, '30000', '3350000', '0', NULL, '3380000', 'BANKING_QR', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-03-14 13:44:36', '2026-03-15 11:44:36', '2026-03-17 11:44:36', NULL, '2026-03-14 11:44:36', '5', NULL),
('12', 'ORD202603012', '9', 'Lê Hoàng Nam', '0913333444', 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', NULL, 'GHN', 'GHN448020118VN', NULL, NULL, NULL, '0', '5800000', '50000', 'FREESHIP', '5750000', 'BANKING_QR', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-03-15 12:25:26', '2026-03-16 10:25:26', '2026-03-18 10:25:26', NULL, '2026-03-15 10:25:26', '5', NULL),
('13', 'ORD202604013', '8', 'Trần Thanh Trúc', '0913222333', 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', NULL, 'GHN', 'GHN800873732VN', NULL, NULL, NULL, '30000', '14300000', '0', NULL, '14330000', 'BANKING_QR', 'paid', 'completed', '4', NULL, NULL, NULL, '2026-04-26 11:51:58', '2026-04-27 09:51:58', '2026-04-29 09:51:58', NULL, '2026-04-26 09:51:58', '5', NULL),
('14', 'ORD202604014', '14', 'Nguyễn Thanh Huyền', '0913888999', 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', NULL, 'ViettelPost', 'ViettelPost838064516VN', NULL, NULL, NULL, '30000', '3200000', '0', NULL, '3230000', 'BANKING_QR', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-04-06 14:55:52', '2026-04-07 12:55:52', '2026-04-09 12:55:52', NULL, '2026-04-06 12:55:52', '5', NULL),
('15', 'ORD202604015', '10', 'Phạm Thị Thúy', '0913444555', 'Số 15 Đại Lộ Hòa Bình, Quận Ninh Kiều, Cần Thơ', NULL, 'ViettelPost', 'ViettelPost275903976VN', NULL, NULL, NULL, '0', '2300000', '50000', 'FREESHIP', '2250000', 'BANKING_QR', 'paid', 'completed', '6', NULL, NULL, NULL, '2026-04-16 11:24:51', '2026-04-17 09:24:51', '2026-04-19 09:24:51', NULL, '2026-04-16 09:24:51', '5', NULL),
('16', 'ORD202604016', '12', 'Võ Thị Kiều Trang', '0913666777', 'Số 74 Lạch Tray, Quận Ngô Quyền, Hải Phòng', NULL, 'ViettelPost', 'ViettelPost794340868VN', NULL, NULL, NULL, '30000', '5600000', '0', NULL, '5630000', 'COD', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-04-27 17:37:38', '2026-04-28 15:37:38', '2026-04-30 15:37:38', NULL, '2026-04-27 15:37:38', '5', NULL),
('17', 'ORD202605017', '15', 'Đoàn Ngọc Phúc', '0913999000', 'Số 31 Đồng Khởi, TP. Biên Hòa, Đồng Nai', NULL, 'GHN', 'GHN431664605VN', NULL, NULL, NULL, '0', '6200000', '50000', 'FREESHIP', '6150000', 'BANKING_QR', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-05-20 13:15:45', '2026-05-21 11:15:45', '2026-05-23 11:15:45', NULL, '2026-05-20 11:15:45', '5', NULL),
('18', 'ORD202605018', '8', 'Trần Thanh Trúc', '0913222333', 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', NULL, 'GHTK', 'GHTK515563003VN', NULL, NULL, NULL, '0', '3200000', '50000', 'FREESHIP', '3150000', 'BANKING_QR', 'paid', 'completed', '4', NULL, NULL, NULL, '2026-05-24 10:26:15', '2026-05-25 08:26:15', '2026-05-27 08:26:15', NULL, '2026-05-24 08:26:15', '5', NULL),
('19', 'ORD202605019', '14', 'Nguyễn Thanh Huyền', '0913888999', 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', NULL, 'J&T Express', 'J&T Express532069792VN', NULL, NULL, NULL, '0', '1450000', '50000', 'FREESHIP', '1400000', 'COD', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-05-27 13:22:32', '2026-05-28 11:22:32', '2026-05-30 11:22:32', NULL, '2026-05-27 11:22:32', '5', NULL),
('20', 'ORD202605020', '9', 'Lê Hoàng Nam', '0913333444', 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', NULL, 'GHN', 'GHN732339362VN', NULL, NULL, NULL, '0', '6600000', '50000', 'FREESHIP', '6550000', 'BANKING_QR', 'paid', 'completed', '6', NULL, NULL, NULL, '2026-05-15 20:15:26', '2026-05-16 18:15:26', '2026-05-18 18:15:26', NULL, '2026-05-15 18:15:26', '5', NULL),
('21', 'ORD202605021', '11', 'Đặng Minh Khôi', '0913555666', 'Số 99 Trưng Nữ Vương, Phường 1, TP. Vĩnh Long', NULL, 'GHTK', 'GHTK676662404VN', NULL, NULL, NULL, '30000', '8500000', '0', NULL, '8530000', 'COD', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-05-19 16:39:21', '2026-05-20 14:39:21', '2026-05-22 14:39:21', NULL, '2026-05-19 14:39:21', '5', NULL),
('22', 'ORD202606022', '12', 'Võ Thị Kiều Trang', '0913666777', 'Số 74 Lạch Tray, Quận Ngô Quyền, Hải Phòng', NULL, 'GHTK', 'GHTK477204390VN', NULL, NULL, NULL, '30000', '4900000', '0', NULL, '4930000', 'COD', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-06-11 17:38:36', '2026-06-12 15:38:36', '2026-06-14 15:38:36', NULL, '2026-06-11 15:38:36', '5', NULL),
('23', 'ORD202606023', '9', 'Lê Hoàng Nam', '0913333444', 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', NULL, 'GHN', 'GHN507331033VN', NULL, NULL, NULL, '30000', '5500000', '0', NULL, '5530000', 'COD', 'paid', 'completed', '4', NULL, NULL, NULL, '2026-06-14 16:23:33', '2026-06-15 14:23:33', '2026-06-17 14:23:33', NULL, '2026-06-14 14:23:33', '5', NULL),
('24', 'ORD202606024', '11', 'Đặng Minh Khôi', '0913555666', 'Số 99 Trưng Nữ Vương, Phường 1, TP. Vĩnh Long', NULL, 'GHN', 'GHN171757361VN', NULL, NULL, NULL, '0', '9600000', '50000', 'FREESHIP', '9550000', 'COD', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-06-16 19:48:17', '2026-06-17 17:48:17', '2026-06-19 17:48:17', NULL, '2026-06-16 17:48:17', '5', NULL),
('25', 'ORD202606025', '15', 'Đoàn Ngọc Phúc', '0913999000', 'Số 31 Đồng Khởi, TP. Biên Hòa, Đồng Nai', NULL, 'GHN', 'GHN907083965VN', NULL, NULL, NULL, '30000', '5750000', '0', NULL, '5780000', 'COD', 'paid', 'completed', '6', NULL, NULL, NULL, '2026-06-09 21:55:50', '2026-06-10 19:55:50', '2026-06-12 19:55:50', NULL, '2026-06-09 19:55:50', '5', NULL),
('26', 'ORD202606026', '8', 'Trần Thanh Trúc', '0913222333', 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', NULL, 'GHN', 'GHN956257378VN', NULL, NULL, NULL, '30000', '5200000', '0', NULL, '5230000', 'BANKING_QR', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-06-12 13:45:30', '2026-06-13 11:45:30', '2026-06-15 11:45:30', NULL, '2026-06-12 11:45:30', '5', NULL),
('27', 'ORD202607027', '12', 'Võ Thị Kiều Trang', '0913666777', 'Số 74 Lạch Tray, Quận Ngô Quyền, Hải Phòng', NULL, 'GHN', 'GHN215882371VN', NULL, NULL, NULL, '30000', '9250000', '0', NULL, '9280000', 'COD', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-07-06 22:20:29', '2026-07-07 20:20:29', '2026-07-09 20:20:29', NULL, '2026-07-06 20:20:29', '5', NULL),
('28', 'ORD202607028', '9', 'Lê Hoàng Nam', '0913333444', 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', NULL, 'GHTK', 'GHTK912256861VN', NULL, NULL, NULL, '30000', '2450000', '0', NULL, '2480000', 'COD', 'paid', 'completed', '4', NULL, NULL, NULL, '2026-07-17 21:42:52', '2026-07-18 19:42:52', '2026-07-20 19:42:52', NULL, '2026-07-17 19:42:52', '5', NULL),
('29', 'ORD202607029', '8', 'Trần Thanh Trúc', '0913222333', 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', NULL, 'GHN', 'GHN359099398VN', NULL, NULL, NULL, '0', '8608000', '50000', 'FREESHIP', '8558000', 'BANKING_QR', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-07-22 22:43:48', '2026-07-23 20:43:48', '2026-07-25 20:43:48', NULL, '2026-07-22 20:43:48', '5', NULL),
('30', 'ORD202607030', '9', 'Lê Hoàng Nam', '0913333444', 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', NULL, 'J&T Express', 'J&T Express812833736VN', NULL, NULL, NULL, '30000', '2450000', '0', NULL, '2480000', 'BANKING_QR', 'paid', 'completed', '6', NULL, NULL, NULL, '2026-07-03 18:49:57', '2026-07-04 16:49:57', '2026-07-06 16:49:57', NULL, '2026-07-03 16:49:57', '5', NULL),
('31', 'ORD202607031', '8', 'Trần Thanh Trúc', '0913222333', 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', NULL, 'GHTK', 'GHTK686100560VN', NULL, NULL, NULL, '30000', '12000000', '0', NULL, '12030000', 'COD', 'paid', 'completed', '2', NULL, NULL, NULL, '2026-07-10 12:13:41', '2026-07-11 10:13:41', '2026-07-13 10:13:41', NULL, '2026-07-10 10:13:41', '5', NULL),
('32', 'ORD202608032', '10', 'Phạm Thị Thúy', '0913444555', 'Số 15 Đại Lộ Hòa Bình, Quận Ninh Kiều, Cần Thơ', NULL, 'GHN', 'GHN679817487VN', NULL, NULL, NULL, '30000', '1450000', '0', NULL, '1480000', 'BANKING_QR', 'paid', 'completed', '3', NULL, NULL, NULL, '2026-08-15 14:59:27', '2026-08-16 12:59:27', '2026-08-18 12:59:27', NULL, '2026-08-15 12:59:27', '5', NULL),
('33', 'ORD202608033', '14', 'Nguyễn Thanh Huyền', '0913888999', 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', NULL, 'ViettelPost', NULL, NULL, NULL, NULL, '30000', '4800000', '0', NULL, '4830000', 'BANKING_QR', 'unpaid', 'pending', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-12 19:22:28', '1', NULL),
('34', 'ORD202608034', '12', 'Võ Thị Kiều Trang', '0913666777', 'Số 74 Lạch Tray, Quận Ngô Quyền, Hải Phòng', NULL, 'ViettelPost', 'ViettelPost122420593VN', NULL, NULL, NULL, '0', '6400000', '50000', 'FREESHIP', '6350000', 'BANKING_QR', 'paid', 'completed', '5', NULL, NULL, NULL, '2026-08-07 19:31:30', '2026-08-08 17:31:30', '2026-08-10 17:31:30', NULL, '2026-08-07 17:31:30', '5', NULL),
('35', 'ORD202608035', '16', 'Hoàng Thị Mai', '0913123789', 'Số 18 Lê Lợi, TP. Huế, Thừa Thiên Huế', NULL, 'J&T Express', NULL, NULL, NULL, NULL, '30000', '7300000', '0', NULL, '7330000', 'COD', 'unpaid', 'confirmed', '6', NULL, NULL, NULL, '2026-08-09 18:26:55', NULL, NULL, NULL, '2026-08-09 16:26:55', '2', NULL);

-- ------------------------------------------------------------
-- Table structure for `otp_codes`
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Table structure for `otp_verifications`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table structure for `product_images`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `product_images` (150 rows)
INSERT INTO `product_images` VALUES
('1', '1', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '1'),
('2', '1', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('3', '1', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('4', '1', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('5', '1', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('6', '1', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('7', '2', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '1'),
('8', '2', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('9', '2', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('10', '2', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('11', '2', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('12', '2', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('13', '3', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '1'),
('14', '3', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('15', '3', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('16', '3', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('17', '3', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('18', '3', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('19', '4', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=800', '1'),
('20', '4', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('21', '4', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('22', '4', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('23', '4', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('24', '4', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('25', '5', 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?q=80&w=800', '1'),
('26', '5', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('27', '5', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('28', '5', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('29', '5', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('30', '5', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('31', '6', 'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?q=80&w=800', '1'),
('32', '6', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('33', '6', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('34', '6', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('35', '6', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('36', '6', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('37', '7', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '1'),
('38', '7', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('39', '7', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('40', '7', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('41', '7', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('42', '7', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('43', '8', 'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?q=80&w=800', '1'),
('44', '8', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('45', '8', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('46', '8', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('47', '8', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('48', '8', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('49', '9', 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?q=80&w=800', '1'),
('50', '9', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('51', '9', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('52', '9', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('53', '9', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('54', '9', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('55', '10', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=800', '1'),
('56', '10', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('57', '10', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('58', '10', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('59', '10', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('60', '10', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('61', '11', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', '1'),
('62', '11', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('63', '11', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('64', '11', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('65', '11', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('66', '11', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('67', '12', 'https://images.unsplash.com/photo-1575537302964-96cd47c06b1b?q=80&w=800', '1'),
('68', '12', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('69', '12', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('70', '12', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('71', '12', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('72', '12', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('73', '13', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '1'),
('74', '13', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('75', '13', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('76', '13', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('77', '13', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('78', '13', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('79', '14', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '1'),
('80', '14', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('81', '14', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('82', '14', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('83', '14', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('84', '14', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('85', '15', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '1'),
('86', '15', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('87', '15', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('88', '15', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('89', '15', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('90', '15', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('91', '16', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '1'),
('92', '16', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('93', '16', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('94', '16', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('95', '16', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('96', '16', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('97', '17', 'https://images.unsplash.com/photo-1551107696-a4b0c5a0d9a2?q=80&w=800', '1'),
('98', '17', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('99', '17', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('100', '17', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('101', '17', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('102', '17', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('103', '18', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '1'),
('104', '18', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('105', '18', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('106', '18', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('107', '18', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('108', '18', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('109', '19', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '1'),
('110', '19', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('111', '19', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('112', '19', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('113', '19', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('114', '19', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('115', '20', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '1'),
('116', '20', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('117', '20', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('118', '20', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('119', '20', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('120', '20', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('121', '21', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '1'),
('122', '21', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('123', '21', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('124', '21', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('125', '21', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('126', '21', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('127', '22', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '1'),
('128', '22', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('129', '22', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('130', '22', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('131', '22', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('132', '22', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('133', '23', 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?q=80&w=800', '1'),
('134', '23', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('135', '23', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('136', '23', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('137', '23', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('138', '23', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('139', '24', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '1'),
('140', '24', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('141', '24', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('142', '24', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('143', '24', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('144', '24', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6'),
('145', '25', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '1'),
('146', '25', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '2'),
('147', '25', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '3'),
('148', '25', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '4'),
('149', '25', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '5'),
('150', '25', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '6');

-- ------------------------------------------------------------
-- Table structure for `product_variants`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prod_size` (`product_id`,`size`),
  UNIQUE KEY `product_size_unique` (`product_id`,`size`),
  KEY `idx_pv_prod_stock` (`product_id`,`stock_quantity`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `product_variants` (150 rows)
INSERT INTO `product_variants` VALUES
('1', '1', '38', 'Trắng Tinh (Triple White)', '29'),
('2', '1', '39', 'Trắng Tinh (Triple White)', '37'),
('3', '1', '40', 'Trắng Tinh (Triple White)', '30'),
('4', '1', '41', 'Trắng Tinh (Triple White)', '42'),
('5', '1', '42', 'Trắng Tinh (Triple White)', '51'),
('6', '1', '43', 'Trắng Tinh (Triple White)', '34'),
('7', '2', '37', 'Trắng / Đen Panda', '39'),
('8', '2', '38', 'Trắng / Đen Panda', '34'),
('9', '2', '39', 'Trắng / Đen Panda', '60'),
('10', '2', '40', 'Trắng / Đen Panda', '37'),
('11', '2', '41', 'Trắng / Đen Panda', '27'),
('12', '2', '42', 'Trắng / Đen Panda', '53'),
('13', '3', '39', 'Đỏ Năng Động (Infrared)', '35'),
('14', '3', '40', 'Đỏ Năng Động (Infrared)', '57'),
('15', '3', '41', 'Đỏ Năng Động (Infrared)', '42'),
('16', '3', '42', 'Đỏ Năng Động (Infrared)', '29'),
('17', '3', '43', 'Đỏ Năng Động (Infrared)', '43'),
('18', '3', '44', 'Đỏ Năng Động (Infrared)', '26'),
('19', '4', '38', 'Trắng / Xám / Đỏ Cam', '39'),
('20', '4', '39', 'Trắng / Xám / Đỏ Cam', '28'),
('21', '4', '40', 'Trắng / Xám / Đỏ Cam', '35'),
('22', '4', '41', 'Trắng / Xám / Đỏ Cam', '44'),
('23', '4', '42', 'Trắng / Xám / Đỏ Cam', '42'),
('24', '4', '43', 'Trắng / Xám / Đỏ Cam', '59'),
('25', '5', '38', 'Đen Mờ (Matte Black)', '55'),
('26', '5', '39', 'Đen Mờ (Matte Black)', '50'),
('27', '5', '40', 'Đen Mờ (Matte Black)', '34'),
('28', '5', '41', 'Đen Mờ (Matte Black)', '53'),
('29', '5', '42', 'Đen Mờ (Matte Black)', '34'),
('30', '5', '43', 'Đen Mờ (Matte Black)', '51'),
('31', '6', '38', 'Trắng / 3 Sọc Đen / Đế Gum', '29'),
('32', '6', '39', 'Trắng / 3 Sọc Đen / Đế Gum', '37'),
('33', '6', '40', 'Trắng / 3 Sọc Đen / Đế Gum', '47'),
('34', '6', '41', 'Trắng / 3 Sọc Đen / Đế Gum', '50'),
('35', '6', '42', 'Trắng / 3 Sọc Đen / Đế Gum', '59'),
('36', '6', '43', 'Trắng / 3 Sọc Đen / Đế Gum', '32'),
('37', '7', '39', 'Trắng Tinh Khôi (Core White)', '27'),
('38', '7', '40', 'Trắng Tinh Khôi (Core White)', '53'),
('39', '7', '41', 'Trắng Tinh Khôi (Core White)', '26'),
('40', '7', '42', 'Trắng Tinh Khôi (Core White)', '57'),
('41', '7', '43', 'Trắng Tinh Khôi (Core White)', '35'),
('42', '7', '44', 'Trắng Tinh Khôi (Core White)', '40'),
('43', '8', '36', 'Trắng / Gót Xanh Lá (Fairway Green)', '58'),
('44', '8', '37', 'Trắng / Gót Xanh Lá (Fairway Green)', '43'),
('45', '8', '38', 'Trắng / Gót Xanh Lá (Fairway Green)', '58'),
('46', '8', '39', 'Trắng / Gót Xanh Lá (Fairway Green)', '27'),
('47', '8', '40', 'Trắng / Gót Xanh Lá (Fairway Green)', '57'),
('48', '8', '41', 'Trắng / Gót Xanh Lá (Fairway Green)', '50'),
('49', '9', '38', 'Đen / 3 Sọc Trắng', '46'),
('50', '9', '39', 'Đen / 3 Sọc Trắng', '43'),
('51', '9', '40', 'Đen / 3 Sọc Trắng', '31'),
('52', '9', '41', 'Đen / 3 Sọc Trắng', '60'),
('53', '9', '42', 'Đen / 3 Sọc Trắng', '28'),
('54', '9', '43', 'Đen / 3 Sọc Trắng', '54'),
('55', '10', '38', 'Xanh Rêu Địa Hình (Magic Lime)', '33'),
('56', '10', '39', 'Xanh Rêu Địa Hình (Magic Lime)', '39'),
('57', '10', '40', 'Xanh Rêu Địa Hình (Magic Lime)', '47'),
('58', '10', '41', 'Xanh Rêu Địa Hình (Magic Lime)', '40'),
('59', '10', '42', 'Xanh Rêu Địa Hình (Magic Lime)', '33'),
('60', '10', '43', 'Xanh Rêu Địa Hình (Magic Lime)', '44'),
('61', '11', '39', 'Đỏ Varsity / Trắng / Đen', '38'),
('62', '11', '40', 'Đỏ Varsity / Trắng / Đen', '52'),
('63', '11', '41', 'Đỏ Varsity / Trắng / Đen', '46'),
('64', '11', '42', 'Đỏ Varsity / Trắng / Đen', '49'),
('65', '11', '43', 'Đỏ Varsity / Trắng / Đen', '47'),
('66', '11', '44', 'Đỏ Varsity / Trắng / Đen', '41'),
('67', '12', '39', 'Nâu Mocha / Trắng Kem / Đỏ', '27'),
('68', '12', '40', 'Nâu Mocha / Trắng Kem / Đỏ', '46'),
('69', '12', '41', 'Nâu Mocha / Trắng Kem / Đỏ', '26'),
('70', '12', '42', 'Nâu Mocha / Trắng Kem / Đỏ', '57'),
('71', '12', '43', 'Nâu Mocha / Trắng Kem / Đỏ', '60'),
('72', '12', '44', 'Nâu Mocha / Trắng Kem / Đỏ', '28'),
('73', '13', '40', 'Trắng / Đen / Xám Nhạt', '48'),
('74', '13', '41', 'Trắng / Đen / Xám Nhạt', '34'),
('75', '13', '42', 'Trắng / Đen / Xám Nhạt', '31'),
('76', '13', '43', 'Trắng / Đen / Xám Nhạt', '27'),
('77', '13', '44', 'Trắng / Đen / Xám Nhạt', '26'),
('78', '13', '45', 'Trắng / Đen / Xám Nhạt', '30'),
('79', '14', '38', 'Trắng Tinh (Triple White)', '51'),
('80', '14', '39', 'Trắng Tinh (Triple White)', '52'),
('81', '14', '40', 'Trắng Tinh (Triple White)', '46'),
('82', '14', '41', 'Trắng Tinh (Triple White)', '48'),
('83', '14', '42', 'Trắng Tinh (Triple White)', '39'),
('84', '14', '43', 'Trắng Tinh (Triple White)', '40'),
('85', '15', '39', 'Đen / Logo Jumpman Trắng', '60'),
('86', '15', '40', 'Đen / Logo Jumpman Trắng', '35'),
('87', '15', '41', 'Đen / Logo Jumpman Trắng', '41'),
('88', '15', '42', 'Đen / Logo Jumpman Trắng', '43'),
('89', '15', '43', 'Đen / Logo Jumpman Trắng', '28'),
('90', '15', '44', 'Đen / Logo Jumpman Trắng', '48'),
('91', '16', '37', 'Xám Bạc Ánh Kim (Steel Grey)', '27'),
('92', '16', '38', 'Xám Bạc Ánh Kim (Steel Grey)', '33'),
('93', '16', '39', 'Xám Bạc Ánh Kim (Steel Grey)', '37'),
('94', '16', '40', 'Xám Bạc Ánh Kim (Steel Grey)', '54'),
('95', '16', '41', 'Xám Bạc Ánh Kim (Steel Grey)', '44'),
('96', '16', '42', 'Xám Bạc Ánh Kim (Steel Grey)', '58'),
('97', '17', '38', 'Trắng / Xám Nhạt (Sea Salt Grey)', '27'),
('98', '17', '39', 'Trắng / Xám Nhạt (Sea Salt Grey)', '28'),
('99', '17', '40', 'Trắng / Xám Nhạt (Sea Salt Grey)', '47'),
('100', '17', '41', 'Trắng / Xám Nhạt (Sea Salt Grey)', '35'),
('101', '17', '42', 'Trắng / Xám Nhạt (Sea Salt Grey)', '56'),
('102', '17', '43', 'Trắng / Xám Nhạt (Sea Salt Grey)', '42'),
('103', '18', '39', 'Xám Khói (Rain Cloud)', '47'),
('104', '18', '40', 'Xám Khói (Rain Cloud)', '54'),
('105', '18', '41', 'Xám Khói (Rain Cloud)', '30'),
('106', '18', '42', 'Xám Khói (Rain Cloud)', '52'),
('107', '18', '43', 'Xám Khói (Rain Cloud)', '45'),
('108', '18', '44', 'Xám Khói (Rain Cloud)', '43'),
('109', '19', '40', 'Xám Di Sản (Heritage Grey)', '33'),
('110', '19', '41', 'Xám Di Sản (Heritage Grey)', '60'),
('111', '19', '42', 'Xám Di Sản (Heritage Grey)', '25'),
('112', '19', '43', 'Xám Di Sản (Heritage Grey)', '50'),
('113', '19', '44', 'Xám Di Sản (Heritage Grey)', '42'),
('114', '19', '45', 'Xám Di Sản (Heritage Grey)', '57'),
('115', '20', '38', 'Bạc Ánh Kim / Đen', '27'),
('116', '20', '39', 'Bạc Ánh Kim / Đen', '39'),
('117', '20', '40', 'Bạc Ánh Kim / Đen', '56'),
('118', '20', '41', 'Bạc Ánh Kim / Đen', '34'),
('119', '20', '42', 'Bạc Ánh Kim / Đen', '39'),
('120', '20', '43', 'Bạc Ánh Kim / Đen', '59'),
('121', '21', '38', 'Đen Classic / Đế Ngà Vintage', '26'),
('122', '21', '39', 'Đen Classic / Đế Ngà Vintage', '37'),
('123', '21', '40', 'Đen Classic / Đế Ngà Vintage', '55'),
('124', '21', '41', 'Đen Classic / Đế Ngà Vintage', '39'),
('125', '21', '42', 'Đen Classic / Đế Ngà Vintage', '46'),
('126', '21', '43', 'Đen Classic / Đế Ngà Vintage', '50'),
('127', '22', '36', 'Trắng / Đế Răng Cưa Gum', '41'),
('128', '22', '37', 'Trắng / Đế Răng Cưa Gum', '53'),
('129', '22', '38', 'Trắng / Đế Răng Cưa Gum', '37'),
('130', '22', '39', 'Trắng / Đế Răng Cưa Gum', '41'),
('131', '22', '40', 'Trắng / Đế Răng Cưa Gum', '34'),
('132', '22', '41', 'Trắng / Đế Răng Cưa Gum', '36'),
('133', '23', '36', 'Trắng Ngà (Parchment)', '60'),
('134', '23', '37', 'Trắng Ngà (Parchment)', '47'),
('135', '23', '38', 'Trắng Ngà (Parchment)', '38'),
('136', '23', '39', 'Trắng Ngà (Parchment)', '45'),
('137', '23', '40', 'Trắng Ngà (Parchment)', '28'),
('138', '23', '41', 'Trắng Ngà (Parchment)', '50'),
('139', '24', '38', 'Đen Da Lộn / Ngôi Sao Trắng', '44'),
('140', '24', '39', 'Đen Da Lộn / Ngôi Sao Trắng', '49'),
('141', '24', '40', 'Đen Da Lộn / Ngôi Sao Trắng', '58'),
('142', '24', '41', 'Đen Da Lộn / Ngôi Sao Trắng', '58'),
('143', '24', '42', 'Đen Da Lộn / Ngôi Sao Trắng', '39'),
('144', '24', '43', 'Đen Da Lộn / Ngôi Sao Trắng', '33'),
('145', '25', '37', 'Xanh Navy Classic', '52'),
('146', '25', '38', 'Xanh Navy Classic', '28'),
('147', '25', '39', 'Xanh Navy Classic', '29'),
('148', '25', '40', 'Xanh Navy Classic', '38'),
('149', '25', '41', 'Xanh Navy Classic', '26'),
('150', '25', '42', 'Xanh Navy Classic', '48');

-- ------------------------------------------------------------
-- Table structure for `products`
-- ------------------------------------------------------------
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
  KEY `idx_prod_status` (`status`),
  KEY `idx_prod_price` (`price`),
  KEY `idx_prod_sold` (`sold_count`),
  KEY `idx_prod_hot` (`is_hot`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `products` (25 rows)
INSERT INTO `products` VALUES
('1', 'SHOE-0001', 'Nike Air Force 1 \'07 All White', 'nike-air-force-1-07-all-white', '5', '1', 'Unisex', '2929000', '3350000', '1800000', '12', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '<div class=\"product-description\">\n<h3>Nike Air Force 1 \'07 All White – Biểu Tượng Bất Hủ Của Làng Sneaker</h3>\n<p>Ra đời năm 1982 và tái xuất dưới phiên bản dân dụng năm 1986, <strong>Nike Air Force 1</strong> là đôi giày bóng rổ đầu tiên trên thế giới được trang bị đệm khí Nike Air – một cuộc cách mạng trong công nghệ giày thể thao. Hơn 40 năm trôi qua, AF1 All White vẫn là lựa chọn không thể thay thế của hàng triệu tín đồ sneaker toàn cầu, từ sân bóng rổ đến đường phố New York, Tokyo cho đến Hà Nội.</p>\n<h4>Câu Chuyện Đằng Sau Đôi Giày</h4>\n<p>Phiên bản <em>Triple White</em> (hay còn gọi là All White) lần đầu xuất hiện vào thập niên 80 và trở thành huyền thoại nhờ sự thanh lịch đơn giản mà không cần bất kỳ điểm nhấn màu sắc nào. Màu trắng tinh khôi trên toàn bộ thân giày tạo nên vẻ đẹp vượt thời gian, kết hợp hoàn hảo với bất kỳ outfit nào – từ jeans, jogger đến sơ mi hay váy đầm.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da thật cao cấp (Full-Grain Leather):</strong> Bề mặt mịn màng, bền bỉ và dễ vệ sinh. Da thật cho phép giày thở tốt, giữ dáng lâu theo thời gian.</li>\n<li><strong>Đệm Air-Sole ở gót:</strong> Hệ thống đệm khí nén đặt tại vùng gót giúp hấp thụ lực tác động tốt, giảm mỏi chân khi đứng hoặc đi bộ cả ngày.</li>\n<li><strong>Đế ngoài cao su đặc (Herringbone Pattern):</strong> Họa tiết xương cá cổ điển cung cấp độ bám tốt trên nhiều bề mặt, chịu mài mòn cao.</li>\n<li><strong>Lót trong (Insole) êm ái:</strong> Lớp lót dày, ôm sát bàn chân tạo cảm giác thoải mái ngay từ lần đầu mang.</li>\n<li><strong>Lưỡi gà dày và bo viền mềm mại:</strong> Thiết kế lưỡi gà phồng đặc trưng của AF1, có thể gập xuống hoặc để thẳng tùy phong cách.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Nike Air Force 1 \'07</td></tr>\n<tr><td>SKU</td><td>CW2288-111</td></tr>\n<tr><td>Màu sắc</td><td>White/White</td></tr>\n<tr><td>Upper</td><td>Full-Grain Leather</td></tr>\n<tr><td>Midsole</td><td>Nike Air-Sole</td></tr>\n<tr><td>Outsole</td><td>Rubber (Herringbone Pattern)</td></tr>\n<tr><td>Độ cao cổ</td><td>Low-Top</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n<tr><td>Trọng lượng</td><td>~380g (Size 41)</td></tr>\n</table>\n<h4>Hướng Dẫn Phối Đồ</h4>\n<p><strong>Nam:</strong> Kết hợp với quần jogger đen, áo hoodie hoặc áo thun oversize để có look streetwear chuẩn chỉnh. Hoặc mặc với quần jeans slim cắt gấu cùng áo sơ mi white-on-white cho vẻ thanh lịch nhẹ nhàng.</p>\n<p><strong>Nữ:</strong> Phối với chân váy midi, wide-leg jeans, hay đơn giản là đồ thể thao year-round. AF1 All White là đôi giày linh hoạt nhất mà mọi tủ đồ đều cần có.</p>\n<h4>Hướng Dẫn Chọn Size</h4>\n<p>Nike Air Force 1 có thiết kế fit rộng hơn một chút so với chuẩn thông thường. Đặc biệt phần hộp mũi vuông tạo cảm giác thoải mái cho ngón chân. Người mang size bàn chân hẹp nên chọn đúng size thường hoặc thậm chí xuống 0.5 size. Người mang size bàn chân bình thường/rộng chọn đúng size là phù hợp nhất.</p>\n<h4>Hướng Dẫn Bảo Quản</h4>\n<ul>\n<li>Dùng bàn chải mềm và dung dịch tẩy giày chuyên dụng để vệ sinh.</li>\n<li>Tránh ngâm nước hoặc giặt máy để da không bị bong tróc.</li>\n<li>Nhét giấy báo hoặc shoe tree vào trong khi không mang để giày giữ dáng.</li>\n<li>Bảo quản ở nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp.</li>\n</ul>\n</div>', '0', '1', '593', '25', '1', '2026-08-18 13:31:27'),
('2', 'SHOE-0002', 'Nike Dunk Low Retro \'White Black\' Panda', 'nike-dunk-low-retro-white-black-panda', '5', '1', 'Unisex', '3200000', '3600000', '2000000', '11', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '<div class=\"product-description\">\n<h3>Nike Dunk Low Retro \'White Black\' Panda – Cơn Bão Không Bao Giờ Lặng Của Làng Sneaker</h3>\n<p>Không có đôi giày nào trong thập kỷ qua gây ra cơn sốt mạnh mẽ và bền bỉ như <strong>Nike Dunk Low Panda</strong>. Ra mắt lại năm 2021 với colorway Black/White đơn giản nhưng cực kỳ ăn nhìn, Panda đã trở thành biểu tượng văn hoá streetwear của cả thế hệ Gen-Z và Millennial. Giá resale tăng gấp đôi ngay sau khi ra mắt, phản ánh mức độ \"must-have\" không thể phủ nhận của đôi giày này.</p>\n<h4>Lịch Sử Huyền Thoại</h4>\n<p>Nike Dunk được thiết kế lần đầu năm 1985 bởi designer huyền thoại Peter Moore – người cũng là cha đẻ của Nike Air Jordan 1. Khởi đầu là giày bóng rổ đại học, Dunk dần len lỏi vào văn hoá skate và streetwear những năm 2000, trước khi chính thức bùng nổ trở lại vào năm 2020-2021 với loạt collab đình đám cùng Off-White, Supreme, Travis Scott…</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da tổng hợp cao cấp (Synthetic Leather Overlay + Textile Underlay):</strong> Phần da trắng lót nền, da đen ốp phủ tạo độ tương phản sắc nét đặc trưng của Panda.</li>\n<li><strong>Đệm midsole Foam êm:</strong> Nike sử dụng foam midsole truyền thống mang lại độ nảy và đàn hồi nhẹ nhàng, cảm giác mang gần mặt đất (low-to-the-ground) đặc trưng.</li>\n<li><strong>Đế cao su văn phòng chắc chắn:</strong> Độ bám tốt, chịu mòn cao, phù hợp đi lại hàng ngày.</li>\n<li><strong>Cổ giày có đệm bảo vệ mắt cá chân:</strong> Phần cổ giày được đệm vải mềm, bảo vệ mắt cá và tạo cảm giác ôm chân dễ chịu.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Nike Dunk Low Retro</td></tr>\n<tr><td>SKU</td><td>DD1391-100</td></tr>\n<tr><td>Colorway</td><td>White/Black (Panda)</td></tr>\n<tr><td>Upper</td><td>Leather + Synthetic Overlay</td></tr>\n<tr><td>Midsole</td><td>Foam</td></tr>\n<tr><td>Outsole</td><td>Rubber</td></tr>\n<tr><td>Độ cao cổ</td><td>Low-Top</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n<tr><td>Trọng lượng</td><td>~365g (Size 41)</td></tr>\n</table>\n<h4>Hướng Dẫn Phối Đồ</h4>\n<p>Panda Dunk Low chính là định nghĩa của \"neutral sneaker\" – không có gì không phối được với đôi giày này. Black/White là bộ đôi màu sắc hoàn hảo nhất trong thời trang, giúp Dunk Low Panda hợp với mọi màu sắc trang phục: từ monochrome đen trắng tối giản, đến tông earth tone, pastel, hay cả những outfit nổi bật màu sắc. Đây là đôi giày bạn có thể mang đi học, đi làm văn phòng creative, đi chơi cuối tuần hay tham dự event streetwear.</p>\n<h4>Hướng Dẫn Chọn Size</h4>\n<p>Nike Dunk Low Retro có fit chuẩn (true-to-size). Chọn đúng size giày thường là phù hợp nhất. Nếu bàn chân rộng hoặc mu cao, bạn có thể cân nhắc lên 0.5 size.</p>\n</div>', '0', '1', '1870', '18', '1', '2026-08-18 13:31:27'),
('3', 'SHOE-0003', 'Nike Air Pegasus 40 Running Shoes', 'nike-air-pegasus-40-running-shoes', '6', '1', 'Unisex', '3100000', '3500000', '1900000', '11', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', '<div class=\"product-description\">\n<h3>Nike Air Pegasus 40 – Người Bạn Chạy Bộ Đồng Hành Trung Thành Nhất</h3>\n<p>Đánh dấu cột mốc 40 năm của dòng giày chạy huyền thoại, <strong>Nike Air Pegasus 40</strong> kế thừa toàn bộ DNA hiệu suất của dòng Pegasus trong khi mang đến những cải tiến công nghệ vượt trội. Đây là lựa chọn đầu tiên của hàng triệu runner – từ người mới bắt đầu đến vận động viên marathon chuyên nghiệp – nhờ sự cân bằng hoàn hảo giữa đệm, phản hồi lực và trọng lượng.</p>\n<h4>Di Sản 40 Năm Huyền Thoại</h4>\n<p>Nike Pegasus lần đầu ra mắt năm 1983 và kể từ đó đã đồng hành cùng hàng chục thế hệ runner. Mỗi phiên bản mới đều mang cải tiến đáng kể: Pegasus 36 có React Foam, Pegasus 40 tiếp tục nâng cấp với đế ReactX lớn hơn và phần upper Flyknit thoáng khí hơn bao giờ hết.</p>\n<h4>Công Nghệ Đột Phá</h4>\n<ul>\n<li><strong>Đệm Nike ReactX lớn hơn 13%:</strong> So với thế hệ trước, lớp ReactX Foam ở midsole được mở rộng thêm 13% tại vùng gót và mũi chân, mang lại độ êm ái vượt trội và phản hồi lực tốt hơn sau mỗi bước chạy.</li>\n<li><strong>Đệm khí Nike Air-Zoom ở mũi chân:</strong> Túi khí Zoom Air được đặt ngay tại vùng mũi chân giúp đẩy bàn chân về phía trước trong mỗi bước chạy, tăng tốc độ và hiệu suất.</li>\n<li><strong>Upper Flyknit thoáng khí:</strong> Được dệt theo công nghệ Flyknit độc quyền của Nike, phần upper ôm sát bàn chân như một đôi tất, đồng thời thoáng khí tối đa để tản nhiệt trong những buổi chạy dài.</li>\n<li><strong>Đế ngoài cao su Carbon Rubber:</strong> Đặt tại các điểm chịu lực cao (gót và mũi chân), kéo dài tuổi thọ của giày đáng kể.</li>\n<li><strong>Hệ thống dây buộc Flywire:</strong> Dây Flywire tích hợp trong hệ thống buộc dây giúp ôm chắc bàn chân, tránh tình trạng trượt gót khi chạy.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Nike Air Zoom Pegasus 40</td></tr>\n<tr><td>SKU</td><td>DV3853-102</td></tr>\n<tr><td>Loại chạy</td><td>Road Running (Đường nhựa)</td></tr>\n<tr><td>Upper</td><td>Flyknit Engineered Mesh</td></tr>\n<tr><td>Midsole</td><td>Nike ReactX Foam + Air Zoom</td></tr>\n<tr><td>Outsole</td><td>Carbon Rubber + Waffle Pattern</td></tr>\n<tr><td>Độ cao gót (Drop)</td><td>10mm</td></tr>\n<tr><td>Trọng lượng</td><td>~280g (Size 42)</td></tr>\n<tr><td>Phù hợp</td><td>Chạy ngày thường, Easy Run, Long Run</td></tr>\n</table>\n<h4>Ai Phù Hợp Với Pegasus 40?</h4>\n<p>Pegasus 40 là lựa chọn lý tưởng cho runner ở mọi trình độ. Nếu bạn chạy từ 15km đến 70km/tuần và cần một đôi giày đáng tin cậy cho mọi buổi tập – easy run, tempo run, hay long run – thì Pegasus 40 là câu trả lời hoàn hảo. Với bàn chân trung tính hoặc hơi sấp nhẹ (mild overpronation), Pegasus 40 sẽ hỗ trợ tốt nhờ thiết kế neutral-to-support của mình.</p>\n</div>', '0', '1', '2405', '20', '1', '2026-08-18 13:31:27'),
('4', 'SHOE-0004', 'Nike Air Max 90 Infrared Heritage', 'nike-air-max-90-infrared-heritage', '8', '1', 'Unisex', '3450000', '3900000', '2100000', '12', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=800', '<div class=\"product-description\">\n<h3>Nike Air Max 90 Infrared Heritage – Kiệt Tác Thiết Kế Không Bao Giờ Lỗi Thời</h3>\n<p>Được thiết kế bởi Tinker Hatfield năm 1990 với nguồn cảm hứng từ những chiếc xe đua và kiến trúc công nghiệp, <strong>Nike Air Max 90</strong> đã thay đổi mãi mãi cách nhìn của thế giới về giày thể thao. Lần đầu tiên trong lịch sử, đệm Air không chỉ nằm ẩn bên trong mà được phô diễn hoàn toàn ở mặt ngoài của giày – một tuyên ngôn táo bạo về sự tự hào công nghệ của Nike.</p>\n<h4>Thiết Kế Huyền Thoại & Câu Chuyện Infrared</h4>\n<p>Colorway Infrared (Đen/Trắng/Đỏ Cam) là colorway original của Air Max 90 khi ra mắt năm 1990, và đến nay vẫn là phiên bản được tìm kiếm nhiều nhất. Tên gọi \"Infrared\" đến từ màu đỏ cam rực rỡ – màu của tia hồng ngoại – được sử dụng để tô điểm cho đơn vị Air-Sole đặc trưng, logo Swoosh và một số chi tiết accent khác.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Đơn vị Air-Sole lớn nhất từ trước đến nay (thời điểm 1990):</strong> Max Air unit tại gót cung cấp khả năng giảm chấn vượt trội, cho cảm giác bước đi mềm mại như đang đi trên mây.</li>\n<li><strong>Upper đa chất liệu (Multi-Material):</strong> Kết hợp da thật, da tổng hợp và lưới tạo nên sự phân lớp phức tạp đặc trưng của AM90. Mỗi lớp vật liệu có chức năng riêng: da cho độ bền, lưới cho thoáng khí.</li>\n<li><strong>Waffled Rubber Outsole:</strong> Đế cao su với họa tiết waffle độc quyền của Nike, cung cấp độ bám và linh hoạt đa hướng.</li>\n<li><strong>Thiết kế ống cổ thấp:</strong> Cho phép bàn chân cử động tự nhiên thoải mái, không bị hạn chế.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Nike Air Max 90</td></tr>\n<tr><td>SKU</td><td>CT1685-100</td></tr>\n<tr><td>Colorway</td><td>White/Black/Infrared</td></tr>\n<tr><td>Upper</td><td>Leather + Mesh Multi-material</td></tr>\n<tr><td>Midsole</td><td>Max Air Unit</td></tr>\n<tr><td>Outsole</td><td>Waffle Rubber</td></tr>\n<tr><td>Năm ra mắt original</td><td>1990</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ</td></tr>\n<tr><td>Trọng lượng</td><td>~410g (Size 42)</td></tr>\n</table>\n<h4>Hướng Dẫn Phối Đồ</h4>\n<p>Air Max 90 Infrared mang một năng lượng retro-bold không gì sánh được. Phối với quần cargo, bomber jacket hay windbreaker cho look 90s nostalgia cực chất. Contrast màu sắc mạnh (đỏ cam vs đen vs trắng) giúp AM90 trở thành focal point của outfit, vì vậy hãy để phần còn lại của trang phục đơn giản hơn để tránh lộn xộn.</p>\n</div>', '1', '1', '3116', '14', '1', '2026-08-18 13:31:27'),
('5', 'SHOE-0005', 'Nike Calm Slide Sandal Đen', 'nike-calm-slide-sandal-den', '12', '1', 'Unisex', '1250000', '1400000', '750000', '10', 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?q=80&w=800', '<div class=\"product-description\">\n<h3>Nike Calm Slide – Thư Giãn Tối Thượng Với Sự Đơn Giản Hoàn Hảo</h3>\n<p><strong>Nike Calm Slide</strong> là minh chứng cho triết lý \"less is more\" trong thiết kế giày dép. Ra mắt năm 2023, đây là dòng dép đúc quai ngang đầu tiên của Nike tập trung hoàn toàn vào trải nghiệm thư giãn tối đa, với đệm SolarSoft đặc biệt dày hơn bình thường để mang lại cảm giác nhẹ nhàng như đi trên gối bông.</p>\n<h4>Triết Lý Thiết Kế</h4>\n<p>Nike Calm được sinh ra từ insight đơn giản: sau một ngày dài vận động với những đôi giày performance, bàn chân cần được thư giãn hoàn toàn. Calm Slide tối giản mọi thứ xuống chỉ còn phần cần thiết nhất: một đế đệm dày và một quai ngang vừa vặn. Không hơn, không kém.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Đệm SolarSoft siêu dày:</strong> Lớp foam SolarSoft độc quyền của Nike dày hơn 40% so với dép thông thường, mang lại độ êm ái bất ngờ với từng bước đi.</li>\n<li><strong>Đế ngoài nhựa TPR cứng:</strong> Chịu mài mòn, không trơn trượt, phù hợp cả đi trong nhà và những khu vực sàn ướt như hồ bơi, spa, phòng tắm.</li>\n<li><strong>Quai ngang ép khuôn liền:</strong> Quai được ép khuôn trực tiếp từ cùng vật liệu foam với đế, không có đường may hay kết nối cơ học có thể hỏng theo thời gian.</li>\n<li><strong>Màu đen toàn thân (Triple Black):</strong> Dễ vệ sinh, chống bám bụi bẩn hiệu quả.</li>\n</ul>\n<h4>Hướng Dẫn Sử Dụng</h4>\n<p>Calm Slide là lựa chọn hoàn hảo cho: sau tập gym, đi dạo nhẹ nhàng quanh nhà hay khu phố, đi ra biển hay hồ bơi, mặc đồ thể thao loungewear ở nhà, hay thậm chí phối cùng quần jogger và áo tee cho look \"athleisure\" thoải mái.</p>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Nike Calm Slide</td></tr>\n<tr><td>Màu sắc</td><td>Black/Black</td></tr>\n<tr><td>Upper/Quai</td><td>Foam ép khuôn liền</td></tr>\n<tr><td>Đệm</td><td>SolarSoft Foam</td></tr>\n<tr><td>Đế ngoài</td><td>TPR Rubber</td></tr>\n<tr><td>Chiều dày đế</td><td>~30mm</td></tr>\n<tr><td>Phù hợp</td><td>Nam (có size nữ riêng)</td></tr>\n</table>\n<h4>Hướng Dẫn Chọn Size</h4>\n<p>Nike Calm Slide có khoảng size rộng, đơn vị tính theo số nguyên. Chọn đúng size thường hoặc lên 1 size nếu bàn chân dài hơn mức bình thường.</p>\n</div>', '0', '1', '2761', '22', '1', '2026-08-18 13:31:27'),
('6', 'SHOE-0006', 'Adidas Samba OG White Black Gum', 'adidas-samba-og-white-black-gum', '8', '2', 'Unisex', '2750000', '3000000', '1700000', '8', 'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?q=80&w=800', '<div class=\"product-description\">\n<h3>Adidas Samba OG – Huyền Thoại Sân Cỏ Thống Trị Đường Phố Thế Kỷ 21</h3>\n<p>Được tạo ra năm 1950 để phục vụ cầu thủ bóng đá tập luyện trên nền đất cứng và sân trong nhà, <strong>Adidas Samba</strong> đã có hành trình 70+ năm đáng kinh ngạc để trở thành một trong những đôi giày streetwear được săn đón nhất năm 2023-2024. Samba OG là phiên bản trung thành nhất với thiết kế gốc, giữ lại mọi chi tiết đặc trưng: mũi T-toe, đế gum và vệt sọc Adidas three-stripe cổ điển.</p>\n<h4>Từ Sân Cỏ Đến Runway Thế Giới</h4>\n<p>Trong thập kỷ 70-80, Samba là đôi giày thường ngày của người Đức, người Anh và toàn châu Âu. Nó xuất hiện ở các sân bóng, pub, concert và cả trong MV của các ban nhạc rock. Rồi Samba dần đi vào bóng tối, chỉ để thức dậy mãnh liệt hơn vào năm 2022 khi được các ngôi sao thời trang như Bella Hadid, Kendall Jenner và hàng loạt influencer toàn cầu lăng xê.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da thật cao cấp:</strong> Mặt da mịn màng với độ bóng vừa phải, bền và dễ vệ sinh. Lớp lót da phụ bên trong tạo độ ấm áp cho chân.</li>\n<li><strong>Mũi T-toe đặc trưng (T-Toe Overlay):</strong> Chi tiết da phụ hình chữ T ở mũi giày – dấu ấn nhận dạng độc nhất vô nhị của Samba không thể nhầm lẫn.</li>\n<li><strong>Đế Gum tự nhiên:</strong> Đế cao su gum màu vàng/nâu tự nhiên tạo độ bám tốt trên sàn nhà, đường nhựa và trong phòng gym. Đây cũng là chi tiết thời trang đặc trưng tạo sự tương phản đẹp mắt với phần thân trắng-đen.</li>\n<li><strong>Three-Stripes & Shell Toe:</strong> Dải sọc Adidas truyền thống và mũi giày hơi vuông cong đặc trưng của dòng retro.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Adidas Samba OG</td></tr>\n<tr><td>SKU</td><td>B75806</td></tr>\n<tr><td>Colorway</td><td>Cloud White/Core Black/Gum</td></tr>\n<tr><td>Upper</td><td>Full-Grain Leather</td></tr>\n<tr><td>Midsole</td><td>EVA Foam</td></tr>\n<tr><td>Outsole</td><td>Natural Gum Rubber</td></tr>\n<tr><td>Năm ra mắt original</td><td>1950</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n<tr><td>Trọng lượng</td><td>~310g (Size 41)</td></tr>\n</table>\n<h4>Hướng Dẫn Phối Đồ</h4>\n<p><strong>European Minimalism:</strong> Chino trắng/be, polo shirt và Samba – look này chưa bao giờ lỗi thời ở châu Âu và đang hot trở lại. <strong>Korean Casual:</strong> Wide-leg pants, áo dệt kim hoặc áo sơ mi với Samba là công thức thời trang Hàn Quốc được yêu thích nhất hiện nay. <strong>Retro Sport:</strong> Track suit, áo bomber vintage với Samba cho vibe 70s cực chất.</p>\n</div>', '0', '1', '660', '19', '1', '2026-08-18 13:31:27'),
('7', 'SHOE-0007', 'Adidas Ultraboost Light Running', 'adidas-ultraboost-light-running', '6', '2', 'Unisex', '3950000', '4500000', '2500000', '12', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '<div class=\"product-description\">\n<h3>Adidas Ultraboost Light – Đỉnh Cao Công Nghệ Chạy Bộ Của Adidas</h3>\n<p><strong>Adidas Ultraboost</strong> là sản phẩm công nghệ tiêu biểu nhất của Adidas kể từ khi ra mắt năm 2015. Với phiên bản <strong>Ultraboost Light</strong> mới nhất, Adidas đã giảm 30% trọng lượng của midsole Boost trong khi vẫn duy trì 100% năng lượng phản hồi – một thành tựu kỹ thuật vượt bậc trong ngành giày chạy bộ hiện đại.</p>\n<h4>Công Nghệ BOOST – Cuộc Cách Mạng Đệm Giày</h4>\n<p>BOOST Foam được tạo ra từ hàng nghìn hạt TPU (Thermoplastic Polyurethane) phồng lên và hợp nhất với nhau. Không giống foam thông thường nén lại và mất năng lượng dưới lực tác động, BOOST nén và nảy lại ngay lập tức, trả lại 80%+ năng lượng cho mỗi bước chạy. Ultraboost Light sử dụng BOOST tái chế với công thức mới nhẹ hơn đáng kể.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Midsole BOOST Light (Nhẹ hơn 30%):</strong> Công thức BOOST mới sử dụng hạt TPU nhỏ hơn, nhẹ hơn nhưng vẫn giữ nguyên khả năng phản hồi năng lượng đỉnh cao.</li>\n<li><strong>Upper Primeknit+ OG:</strong> Vải dệt Primeknit thế hệ mới với độ co giãn đa chiều, ôm sát bàn chân như bàn tay. Bề mặt có texture nhỏ tạo cảm giác cao cấp và hỗ trợ thông thoáng.</li>\n<li><strong>Đế ngoài Continental™ Rubber:</strong> Cao su Continental – thương hiệu lốp xe nổi tiếng – hợp tác với Adidas để tạo ra đế giày bám đường trong mọi điều kiện thời tiết, kể cả khi ướt.</li>\n<li><strong>Hệ thống Torsion System:</strong> Thanh TPU giữa đế giúp ổn định bàn chân và tối ưu hóa cơ học chuyển động trong khi chạy.</li>\n<li><strong>Heel Counter cứng:</strong> Phần gót cứng giúp ổn định, chống pronation nhẹ và bảo vệ gân Achilles.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Adidas Ultraboost Light</td></tr>\n<tr><td>SKU</td><td>HQ6339</td></tr>\n<tr><td>Loại chạy</td><td>Road Running, Daily Training</td></tr>\n<tr><td>Upper</td><td>Primeknit+ OG</td></tr>\n<tr><td>Midsole</td><td>BOOST Light Foam</td></tr>\n<tr><td>Outsole</td><td>Continental™ Rubber</td></tr>\n<tr><td>Drop</td><td>10mm</td></tr>\n<tr><td>Trọng lượng</td><td>~270g (Size 42)</td></tr>\n</table>\n<h4>So Sánh Với Các Dòng Giày Khác</h4>\n<p>Nếu Pegasus là \"người bạn đáng tin cậy\", thì Ultraboost là \"người bạn sang chảnh\". Cảm giác đệm của Ultraboost mềm mại và lún sâu hơn Pegasus, phù hợp hơn cho easy run và recovery run. Tuy nhiên với những runner cần responsiveness cao cho tempo run hay race, Pegasus hoặc Vaporfly sẽ phù hợp hơn.</p>\n</div>', '1', '1', '3176', '8', '1', '2026-08-18 13:31:27'),
('8', 'SHOE-0008', 'Adidas Stan Smith Classic Green', 'adidas-stan-smith-classic-green', '9', '2', 'Unisex', '2150000', '2400000', '1300000', '10', 'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?q=80&w=800', '<div class=\"product-description\">\n<h3>Adidas Stan Smith – Biểu Tượng Tennis Trắng Thuần Khiết Vượt Thời Gian</h3>\n<p>Mang tên nhà vô địch Grand Slam người Mỹ Stanley Roger Smith, <strong>Adidas Stan Smith</strong> lần đầu xuất hiện năm 1965 dưới tên \"Robert Haillet\" trước khi chính thức đổi tên năm 1973. Với thiết kế cực kỳ tối giản – trắng toàn thân, 3 lỗ thông khí hình tròn và vệt sọc đục lỗ thay vì in nổi – Stan Smith là đôi giày \"anti-design\" nhưng lại trở thành một trong những thiết kế biểu tượng nhất mọi thời đại.</p>\n<h4>Kỷ Lục & Di Sản</h4>\n<p>Adidas Stan Smith là một trong những đôi giày bán chạy nhất lịch sử với hơn 70 triệu đôi đã được tiêu thụ tính đến nay. Năm 2014, Adidas đình chỉ sản xuất trong vài tháng để tạo khan hiếm có chủ đích, và khi tái ra mắt, cả thế giới thời trang đã điên đảo. Stan Smith là đôi giày duy nhất xuất hiện trên runway của Stella McCartney, Raf Simons, Pharrell Williams và không biết bao nhiêu designer đình đám.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da thật (Full-Grain Leather):</strong> Da bò cao cấp, mịn màng và bền bỉ. Lớp da trắng nguyên khối tạo nền cho phần sọc xanh lá (Green Heel Tab) và gót giày in hình khuôn mặt Stan Smith.</li>\n<li><strong>Đệm Ortholite® bên trong:</strong> Lót trong Ortholite kháng khuẩn, chống mùi, êm ái và thoáng khí vượt trội so với lót thông thường.</li>\n<li><strong>Đế EVA cứng chắc:</strong> Đế EVA mỏng, sát đất tạo cảm giác ổn định và nhẹ nhàng. Đây là thiết kế tennis court cổ điển không có nhiều đệm nhưng cho cảm giác linh hoạt tuyệt vời.</li>\n<li><strong>3 lỗ thông khí hình tròn:</strong> Chi tiết nhận dạng đặc trưng nhất của Stan Smith – 3 hàng lỗ tròn thay thế cho three-stripe thông thường, đồng thời có chức năng thoáng khí thực sự.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Adidas Stan Smith</td></tr>\n<tr><td>SKU</td><td>FX5502</td></tr>\n<tr><td>Colorway</td><td>Cloud White/Cloud White/Green</td></tr>\n<tr><td>Upper</td><td>Full-Grain Leather</td></tr>\n<tr><td>Midsole</td><td>EVA</td></tr>\n<tr><td>Lót trong</td><td>Ortholite® Insole</td></tr>\n<tr><td>Năm ra mắt</td><td>1965 (đổi tên 1973)</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n<tr><td>Trọng lượng</td><td>~295g (Size 41)</td></tr>\n</table>\n</div>', '0', '1', '2557', '17', '1', '2026-08-18 13:31:27'),
('9', 'SHOE-0009', 'Adidas Superstar 80s Core Black', 'adidas-superstar-80s-core-black', '5', '2', 'Unisex', '2300000', '2600000', '1400000', '12', 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?q=80&w=800', '<div class=\"product-description\">\n<h3>Adidas Superstar 80s – Shell Toe Huyền Thoại Của Văn Hoá Hip-Hop</h3>\n<p>Năm 1969, Adidas giới thiệu Superstar như một đôi giày bóng rổ chuyên dụng với chiếc mũi cao su cứng hình vỏ sò (Shell Toe) – một thiết kế kỹ thuật nhằm bảo vệ ngón chân khi va chạm sân đấu. Không ai ngờ rằng chính chi tiết kỹ thuật thuần tuý này sẽ trở thành biểu tượng văn hoá street lớn nhất mọi thời đại khi nhóm Run-DMC lăng xê đôi Superstar (không buộc dây) vào những năm 80.</p>\n<h4>Superstar & Văn Hoá Hip-Hop</h4>\n<p>Năm 1986, Run-DMC phát hành single \"My Adidas\" – bài hát đầu tiên trong lịch sử âm nhạc tôn vinh một thương hiệu giày. Adidas ngay lập tức ký hợp đồng endorsement với nhóm và đây là một trong những thỏa thuận nghe nhạc đầu tiên trong ngành giày. Kể từ đó, Superstar được tôn vinh như \"giày của văn hoá Hip-Hop\" và không bao giờ lỗi thời.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Shell Toe cao su cứng hình vỏ sò:</strong> Không chỉ là chi tiết thẩm mỹ, Shell Toe thực sự bảo vệ ngón chân và giữ dáng mũi giày theo thời gian, tránh bị nhăn hay bẹp.</li>\n<li><strong>Upper da thật + da phụ chống mòn:</strong> Da thật mịn làm nền, các miếng da phụ ở những vị trí chịu lực cao tăng độ bền tổng thể của giày.</li>\n<li><strong>Three-Stripe da:</strong> Phần sọc ba màu trắng được làm bằng da thật, nổi khối rõ ràng, tạo điểm nhấn cao cấp hơn so với những phiên bản hiện đại dùng vải.</li>\n<li><strong>Đế vulcanized thấp:</strong> Kỹ thuật vulcanize đế cao su cổ điển cho độ bền cao và cảm giác sát đất ổn định.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Adidas Superstar 80s</td></tr>\n<tr><td>SKU</td><td>FV2808</td></tr>\n<tr><td>Colorway</td><td>Core Black/Cloud White</td></tr>\n<tr><td>Upper</td><td>Full-Grain Leather</td></tr>\n<tr><td>Midsole</td><td>EVA Foam</td></tr>\n<tr><td>Outsole</td><td>Vulcanized Rubber</td></tr>\n<tr><td>Năm ra mắt</td><td>1969</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n<h4>Hướng Dẫn Phối Đồ</h4>\n<p><strong>Hip-Hop Classic:</strong> Track suit Adidas, cap brim thẳng và Superstar không buộc dây – công thức không bao giờ chết. <strong>Modern Street:</strong> Wide-leg cargo pants, áo graphic tee và Superstar với dây buộc chắc chắn cho look hiện đại hơn. <strong>Smart Casual:</strong> Chino, áo polo và Superstar cho look smart-casual cực clean và tinh tế.</p>\n</div>', '0', '1', '2729', '8', '1', '2026-08-18 13:31:27'),
('10', 'SHOE-0010', 'Adidas Adilette 22 Slides Futuristic', 'adidas-adilette-22-slides-futuristic', '12', '2', 'Unisex', '1450000', '1650000', '850000', '12', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=800', '<div class=\"product-description\">\n<h3>Adidas Adilette 22 – Dép Đúc Tương Lai Từ Công Nghệ In 3D</h3>\n<p>Không phải chiếc dép đúc thông thường, <strong>Adidas Adilette 22</strong> là một tuyên ngôn về tương lai của thiết kế giày dép. Lấy cảm hứng từ dép Adilette cổ điển ra đời năm 1972, phiên bản 22 được tái tưởng tượng hoàn toàn với phần đế in 3D lattice structure (cấu trúc lưới 3D) – công nghệ từng chỉ xuất hiện trong giày chạy chuyên nghiệp cao cấp.</p>\n<h4>Công Nghệ In 3D Lattice – Tương Lai Của Đế Giày</h4>\n<p>Thay vì foam đặc truyền thống, đế Adilette 22 sử dụng cấu trúc lưới không gian 3D được in ra từ máy in 3D chuyên dụng. Cấu trúc này mang lại:</p>\n<ul>\n<li>Độ đàn hồi và phản hồi tốt hơn đáng kể so với foam thông thường.</li>\n<li>Trọng lượng nhẹ hơn vì có rất nhiều khoảng rỗng trong cấu trúc.</li>\n<li>Thông thoáng khí tự nhiên từ chính cấu trúc của đế.</li>\n<li>Hình thức thẩm mỹ futuristic cực kỳ độc đáo, không đôi dép nào trông giống Adilette 22.</li>\n</ul>\n<h4>Chi Tiết Thiết Kế</h4>\n<ul>\n<li><strong>Quai ngang thể thao dày:</strong> Quai ngang rộng, đệm bên trong mềm mại, không gây đau hay cứa vào mu bàn chân dù đi cả ngày.</li>\n<li><strong>Logo Trefoil embossed:</strong> Logo 3 lá cỏ của Adidas được dập nổi trực tiếp trên quai, sang trọng và bền hơn logo in thường.</li>\n<li><strong>Màu sắc futuristic:</strong> Phiên bản Core Black với đế lattice đen tạo vẻ ngoài như được lấy ra từ bộ phim khoa học viễn tưởng.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Adidas Adilette 22</td></tr>\n<tr><td>Công nghệ đế</td><td>3D Lattice Structure Foam</td></tr>\n<tr><td>Quai</td><td>Synthetic + Foam Padding</td></tr>\n<tr><td>Màu sắc</td><td>Core Black/Cloud White</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n<tr><td>Chiều dày đế</td><td>~35mm (tại trung tâm)</td></tr>\n</table>\n</div>', '0', '1', '1309', '15', '1', '2026-08-18 13:31:27'),
('11', 'SHOE-0011', 'Nike Air Jordan 1 Retro High OG \'Chicago\'', 'nike-air-jordan-1-retro-high-og-chicago', '7', '3', 'Unisex', '4650000', '5200000', '3100000', '10', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', '<div class=\"product-description\">\n<h3>Nike Air Jordan 1 Retro High OG \'Chicago\' – Đôi Giày Triệu Đô Của Huyền Thoại Bóng Rổ</h3>\n<p>Nếu phải chọn một đôi giày duy nhất đại diện cho lịch sử sneaker culture, đó chỉ có thể là <strong>Air Jordan 1 High \'Chicago\'</strong> – đôi giày gắn liền với buổi ra mắt huyền thoại của Michael Jordan mùa giải NBA 1984-85. Hãy tưởng tượng: NBA đã phạt Jordan 5,000 USD mỗi trận vì mang giày có màu sắc \"không đúng quy định\" – và Nike đã trả toàn bộ số tiền phạt đó. Sự kiện này đã tạo ra marketing buzz lớn nhất lịch sử giày thể thao.</p>\n<h4>Lịch Sử Đằng Sau Đôi Giày</h4>\n<p>Air Jordan 1 được thiết kế bởi Peter Moore năm 1984-85 với brief cực kỳ đơn giản: tạo ra một đôi giày cho Michael Jordan mà cả thế giới sẽ nhớ đến. Colorway Chicago – White/Black/Varsity Red – lấy màu sắc đặc trưng của đội bóng rổ Chicago Bulls. Kể từ đó, AJ1 Chicago đã được retro lại nhiều lần với giá resale leo thang chóng mặt sau mỗi lần ra mắt.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da thật cao cấp (Tumbled Leather + Perforated Toe Box):</strong> Da tumbled mềm mại tự nhiên, mũi giày có lỗ thông khí hình tròn nhỏ phân tán nhiệt.</li>\n<li><strong>Đệm Air-Sole ở gót:</strong> Đơn vị khí nén nguyên bản từ thiết kế 1985 được giữ nguyên trong phiên bản Retro.</li>\n<li><strong>Cổ cao với dây buộc chắc (High-Top):</strong> Thiết kế cổ cao hỗ trợ mắt cá chân, lý tưởng cho người vừa mang giày cổ cao trên sân bóng rổ.</li>\n<li><strong>Wings Logo Swoosh:</strong> Logo đôi cánh Nike trên cổ giày – chi tiết độc quyền chỉ có trên Air Jordan 1 gốc.</li>\n<li><strong>Jumpman + Nike Air Branding:</strong> Đế mang logo \"NIKE AIR\" – chi tiết chỉ có ở phiên bản OG (Original), không có ở các phiên bản hiện đại thông thường.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Air Jordan 1 Retro High OG</td></tr>\n<tr><td>SKU</td><td>555088-101</td></tr>\n<tr><td>Colorway</td><td>White/Black/Varsity Red (Chicago)</td></tr>\n<tr><td>Upper</td><td>Tumbled Leather + Perforated Toe</td></tr>\n<tr><td>Midsole</td><td>Foam + Air-Sole Unit</td></tr>\n<tr><td>Độ cao cổ</td><td>High-Top</td></tr>\n<tr><td>Năm thiết kế gốc</td><td>1985</td></tr>\n<tr><td>Trọng lượng</td><td>~435g (Size 42)</td></tr>\n</table>\n<h4>Giá Trị Đầu Tư</h4>\n<p>AJ1 Chicago OG là một trong số ít đôi giày sneaker mà giá trị chỉ tăng theo thời gian nếu được bảo quản tốt trong hộp gốc, không bị bẩn hay ố vàng. Bản OG luôn có giá resale cao hơn đáng kể so với các phiên bản thông thường. Đây vừa là đôi giày để mang, vừa có thể coi là tài sản đầu tư theo đúng nghĩa đen.</p>\n</div>', '0', '1', '1387', '12', '1', '2026-08-18 13:31:27'),
('12', 'SHOE-0012', 'Air Jordan 1 Low \'Reverse Mocha\' Travis Scott', 'air-jordan-1-low-reverse-mocha-travis-scott', '5', '3', 'Unisex', '5800000', '6500000', '3800000', '11', 'https://images.unsplash.com/photo-1575537302964-96cd47c06b1b?q=80&w=800', '<div class=\"product-description\">\n<h3>Air Jordan 1 Low \'Reverse Mocha\' Travis Scott x Nike – Siêu Phẩm Collab Đắt Giá Nhất Thập Kỷ</h3>\n<p>Khi rapper, producer và sneaker designer lừng danh <strong>Travis Scott</strong> hợp tác cùng Nike, kết quả luôn là những đôi giày làm điên đảo thị trường resale. <strong>Air Jordan 1 Low \'Reverse Mocha\'</strong> đảo ngược hoàn toàn colorway Mocha nổi tiếng trước đó: Sail thay thế nâu làm màu nền, nâu thay thế trắng làm màu overlay – và đặc biệt nhất là logo Swoosh bị lật ngược lại (backwards Swoosh), dấu ấn đặc trưng độc nhất của mọi collab Travis Scott x Nike.</p>\n<h4>Backwards Swoosh – Chữ Ký Của Cactus Jack</h4>\n<p>Ý tưởng Swoosh ngược xuất phát từ ý tưởng \"nhìn thế giới từ góc độ khác\" của Travis. Đây là chi tiết collector nhận ra ngay để phân biệt đôi giày Travis Scott authentic với đôi giày fake. Ngoài ra, collab này còn có lớp vải bên trong màu xanh lá chỉ nhìn thấy khi mở lưỡi gà – một Easter egg dành riêng cho true collector.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper suede cao cấp màu Mocha Brown:</strong> Phần suede mềm mại, sang trọng phủ phần lớn thân giày. Texture của suede tạo chiều sâu thị giác đặc biệt trong ánh sáng tự nhiên.</li>\n<li><strong>Overlay da thật màu Sail:</strong> Phần overlay da thật tạo tương phản nhẹ nhàng, tinh tế với suede nâu.</li>\n<li><strong>Backwards Swoosh bằng da phụ nổi khối:</strong> Swoosh ngược được làm bằng miếng da riêng, nổi rõ trên nền suede.</li>\n<li><strong>Lót trong màu xanh lá (Easter Egg):</strong> Lớp lót bên trong màu xanh lá đặc trưng của Cactus Jack brand.</li>\n<li><strong>Đế Foam AJ1 Low cổ điển:</strong> Thấp hơn phiên bản High-Top, cho cảm giác linh hoạt hơn trong mọi hoạt động hàng ngày.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Air Jordan 1 Low x Travis Scott</td></tr>\n<tr><td>SKU</td><td>DM7866-162</td></tr>\n<tr><td>Colorway</td><td>Sail/Mocha/White (Reverse Mocha)</td></tr>\n<tr><td>Upper</td><td>Suede + Leather</td></tr>\n<tr><td>Collab</td><td>Travis Scott (Cactus Jack)</td></tr>\n<tr><td>Đặc điểm nhận dạng</td><td>Backwards Swoosh, Lót xanh lá</td></tr>\n<tr><td>Năm ra mắt</td><td>2023</td></tr>\n</table>\n</div>', '0', '1', '683', '21', '1', '2026-08-18 13:31:27'),
('13', 'SHOE-0013', 'Air Jordan 4 Retro \'Military Black\'', 'air-jordan-4-retro-military-black', '7', '3', 'Unisex', '5200000', '5800000', '3400000', '10', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', '<div class=\"product-description\">\n<h3>Air Jordan 4 Retro \'Military Black\' – Đẳng Cấp Từ Chiến Trường Đến Runway</h3>\n<p><strong>Air Jordan 4</strong> là đôi giày đầu tiên trong dòng Air Jordan được phân phối toàn cầu (trước đó chỉ có ở Mỹ). Thiết kế bởi Tinker Hatfield năm 1989, AJ4 mang aesthetic khác biệt hoàn toàn với AJ1 và AJ3: lưới thoáng khí, hệ thống dây buộc ở mũi và vây lưng đặc trưng. Colorway \'Military Black\' lấy cảm hứng từ trang bị quân sự với tông đen-xám granite cứng cáp và mạnh mẽ.</p>\n<h4>Thiết Kế Kỹ Thuật Tiên Phong</h4>\n<ul>\n<li><strong>Netting Upper (Lưới thoáng khí):</strong> Lần đầu tiên trong dòng Air Jordan, AJ4 sử dụng lưới plastic bao quanh phần giữa thân, tăng độ thoáng khí đáng kể so với da đặc truyền thống.</li>\n<li><strong>Flight Cable Lacing (Hệ thống dây buộc cải tiến):</strong> Dây buộc mở rộng từ mũi lên cao qua một cổng dây trên mũi giày, tạo sự khóa chắc bàn chân tốt hơn trên sân bóng rổ.</li>\n<li><strong>Visible Air Unit ở gót:</strong> Đơn vị Air được nhìn thấy từ phía sau – thiết kế mang tính showcase công nghệ cao.</li>\n<li><strong>Wing Tabs ở gót:</strong> Hai vây đặc trưng ở hai bên gót giày – chi tiết nhận dạng độc nhất của AJ4.</li>\n<li><strong>Traction Herringbone Outsole:</strong> Họa tiết xương cá phức tạp cho độ bám đa hướng tuyệt vời trên sân bóng rổ.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Air Jordan 4 Retro</td></tr>\n<tr><td>Colorway</td><td>Black/Dark Charcoal/Fire Red</td></tr>\n<tr><td>Upper</td><td>Leather + Netting</td></tr>\n<tr><td>Midsole</td><td>Foam + Visible Air</td></tr>\n<tr><td>Năm thiết kế gốc</td><td>1989</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ</td></tr>\n</table>\n</div>', '0', '1', '2075', '10', '1', '2026-08-18 13:31:27'),
('14', 'SHOE-0014', 'Air Jordan 1 Mid Triple White Clean', 'air-jordan-1-mid-triple-white-clean', '5', '3', 'Unisex', '3650000', '4100000', '2300000', '11', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', '<div class=\"product-description\">\n<h3>Air Jordan 1 Mid Triple White – Sự Tinh Tế Của Sắc Trắng</h3>\n<p><strong>Air Jordan 1 Mid Triple White</strong> là phiên bản lý tưởng cho những ai muốn có vẻ đẹp clean, tối giản và sang trọng của AJ1 mà không cần phải bỏ số tiền lớn cho phiên bản OG. Cổ giày trung bình (Mid) là điểm cân bằng hoàn hảo giữa sự linh hoạt của Low và sự bảo vệ của High, phù hợp cho cả những người mới đến với sneaker culture lẫn OG collector.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da tổng hợp cao cấp (Hoàn toàn màu trắng):</strong> Mặt da láng mịn với màu trắng thuần nhất, không có bất kỳ accent màu nào làm phân tán sự chú ý.</li>\n<li><strong>Đệm Air-Sole ở gót:</strong> Kế thừa từ thiết kế Air Jordan 1 gốc, đệm khí nén hỗ trợ tốt cho hoạt động hàng ngày.</li>\n<li><strong>Lưỡi gà đệm dày:</strong> Phần lưỡi gà được đệm chắc chắn, bảo vệ mu bàn chân và tạo cảm giác khỏe khoắn khi mang.</li>\n<li><strong>Outsole cao su tổng hợp:</strong> Độ bám tốt và bền bỉ cho mọi bề mặt thường gặp hàng ngày.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Air Jordan 1 Mid</td></tr>\n<tr><td>Colorway</td><td>White/White/White</td></tr>\n<tr><td>Upper</td><td>Synthetic Leather</td></tr>\n<tr><td>Độ cao cổ</td><td>Mid-Top</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n</div>', '1', '1', '2998', '13', '1', '2026-08-18 13:31:27'),
('15', 'SHOE-0015', 'Jordan Hydro 8 Slide Quai Dán Đen', 'jordan-hydro-8-slide-quai-dan-den', '12', '3', 'Unisex', '1350000', '1550000', '800000', '13', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '<div class=\"product-description\">\n<h3>Jordan Hydro 8 – Quai Dán Cao Cấp Cho Recovery Sau Tập Luyện</h3>\n<p><strong>Jordan Hydro 8</strong> là dòng dép quai dán chuyên dụng của Jordan Brand, được thiết kế đặc biệt cho các vận động viên bóng rổ sau mỗi buổi tập căng thẳng. Không chỉ là dép thường, Hydro 8 tích hợp các yếu tố thiết kế đặc trưng của Jordan như Jumpman logo, midsole êm ái và chất liệu cao cấp để tạo ra trải nghiệm recovery tối ưu.</p>\n<h4>Công Nghệ & Thiết Kế</h4>\n<ul>\n<li><strong>Quai dán Velcro điều chỉnh được:</strong> Dễ dàng đeo vào/ra, điều chỉnh độ chặt theo kích thước bàn chân. Không cần buộc dây hay căn chỉnh phức tạp.</li>\n<li><strong>Đệm midsole phẳng EVA dày:</strong> Lớp EVA dày cung cấp đệm êm ái, giảm mỏi bàn chân sau tập luyện cường độ cao.</li>\n<li><strong>Upper dệt thoáng khí:</strong> Vật liệu dệt cho phép chân thở và khô nhanh sau khi tắm hay đi bơi.</li>\n<li><strong>Logo Jumpman embossed:</strong> Logo Michael Jordan tư thế slam dunk nổi tiếng được dập nổi trên quai, thể hiện đẳng cấp Jordan Brand.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Jordan Hydro 8</td></tr>\n<tr><td>Loại</td><td>Dép Quai Dán</td></tr>\n<tr><td>Màu sắc</td><td>Black/Anthracite</td></tr>\n<tr><td>Quai</td><td>Velcro + Textile Lining</td></tr>\n<tr><td>Đệm</td><td>EVA Midsole</td></tr>\n<tr><td>Phù hợp</td><td>Nam</td></tr>\n</table>\n</div>', '0', '1', '1250', '13', '1', '2026-08-18 13:31:27'),
('16', 'SHOE-0016', 'New Balance 530 Steel Grey Retro', 'new-balance-530-steel-grey-retro', '6', '4', 'Unisex', '2450000', '2800000', '1500000', '12', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '<div class=\"product-description\">\n<h3>New Balance 530 Steel Grey – Giày Retro Running Bùng Nổ Toàn Cầu 2023-2024</h3>\n<p>Ít ai biết rằng <strong>New Balance 530</strong> ban đầu là một mẫu giày chạy bộ performance thực thụ ra đời cuối thập niên 1990. Với công nghệ ABZORB và ENCAP đặc trưng của New Balance, 530 từng là lựa chọn của nhiều runner chuyên nghiệp. Nhưng điều thú vị hơn là sau 25 năm, đôi giày này đột ngột trở thành mốt streetwear hot nhất năm 2023-2024, được Gen-Z đặt lên ngang hàng với Samba và Dunk Low.</p>\n<h4>Từ Đường Chạy Đến Đường Phố</h4>\n<p>Xu hướng \"dad shoe\" và \"ugly sneaker\" đã biến những đôi giày chạy bộ cũ kỹ thành must-have của thời trang đương đại. NB 530 với profile chunky cổ điển, phối màu earth tone tinh tế và logo N to trên thân đã trở thành \"the it shoe\" được tìm kiếm nhiều nhất trong giới streetwear toàn cầu năm 2023-2024.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Công nghệ đệm ABZORB® tại gót:</strong> Vật liệu đệm độc quyền của NB hấp thụ lực tác động xuất sắc, đặc biệt hiệu quả ở vùng gót nơi tiếp xúc đất đầu tiên.</li>\n<li><strong>Hệ thống ổn định ENCAP® tại midsole:</strong> Lớp foam EVA mềm được bọc trong vỏ polyurethane cứng, kết hợp đệm mềm và ổn định cứng trong một hệ thống duy nhất.</li>\n<li><strong>Upper vải mesh thoáng + da phụ:</strong> Vải mesh tạo thoáng khí, các miếng da phụ overlay tạo độ bền và thẩm mỹ chunk cổ điển.</li>\n<li><strong>Đế ngoài carbon rubber chịu mòn:</strong> Cao su carbon đặt tại vùng chịu lực cao, kéo dài đáng kể tuổi thọ.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>New Balance 530</td></tr>\n<tr><td>SKU</td><td>MR530SG</td></tr>\n<tr><td>Colorway</td><td>Steel Grey/White/Castlerock</td></tr>\n<tr><td>Upper</td><td>Mesh + Leather Overlay</td></tr>\n<tr><td>Midsole</td><td>ABZORB + ENCAP</td></tr>\n<tr><td>Outsole</td><td>Carbon Rubber</td></tr>\n<tr><td>Năm ra mắt gốc</td><td>Cuối thập niên 90</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ</td></tr>\n</table>\n</div>', '0', '1', '2361', '20', '1', '2026-08-18 13:31:27'),
('17', 'SHOE-0017', 'New Balance 550 White Grey Vintage', 'new-balance-550-white-grey-vintage', '5', '4', 'Unisex', '2890000', '3200000', '1750000', '10', 'https://images.unsplash.com/photo-1551107696-a4b0c5a0d9a2?q=80&w=800', '<div class=\"product-description\">\n<h3>New Balance 550 White Grey – Sự Trở Lại Đình Đám Của Giày Bóng Rổ Vintage</h3>\n<p>Nếu không có sự hợp tác với Aimé Leon Dore (ALD) năm 2020, <strong>New Balance 550</strong> có thể vẫn đang nằm trong kho lưu trữ của NB. Nhưng nhờ collab đình đám đó, NB 550 đã bùng cháy trở lại và trở thành một trong những đôi giày được tìm kiếm nhiều nhất trong 3 năm qua – đặc biệt là colorway trắng/xám cổ điển này.</p>\n<h4>Lịch Sử & Sự Hồi Sinh</h4>\n<p>NB 550 là giày bóng rổ low-top từ năm 1989 với thiết kế court basketball chuẩn mực: da bọc toàn thân, đế phẳng, cổ thấp linh hoạt. Collab với ALD 2020 giữ nguyên thiết kế gốc với chất liệu nâng cấp, và từ đó NB 550 chính thức trở thành \"the shoe\" của phong trào prep/old-money aesthetic toàn cầu.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper da thật toàn phần (Full Leather):</strong> NB 550 nổi bật với việc dùng da thật chất lượng cao bao phủ gần như toàn bộ thân giày, mang lại sự sang trọng, bền bỉ và cảm giác mang cao cấp hiếm gặp ở mức giá này.</li>\n<li><strong>Midsole foam êm ái:</strong> Đệm foam truyền thống cho cảm giác gần mặt đất, ổn định và linh hoạt – phong cách court basketball cổ điển.</li>\n<li><strong>Logo N to đặc trưng:</strong> Logo chữ N to màu xám ở hai bên thân giày – chi tiết nhận dạng mạnh mẽ nhất của New Balance.</li>\n<li><strong>Outsole rubber vulcanized:</strong> Đế cao su vulcanize phẳng, độ bám tốt và rất bền theo thời gian.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>New Balance 550</td></tr>\n<tr><td>SKU</td><td>BB550WT1</td></tr>\n<tr><td>Colorway</td><td>White/Grey</td></tr>\n<tr><td>Upper</td><td>Full-Grain Leather</td></tr>\n<tr><td>Năm ra mắt gốc</td><td>1989</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n<h4>Old-Money Aesthetic Với NB 550</h4>\n<p>NB 550 là đôi giày cốt lõi của phong trào \"old money\" và \"coastal grandfather\" aesthetic đang thống trị TikTok và Instagram. Phối với: quần kaki chino màu cream, áo tennis collar, áo rugby stripe hay áo linen button-down. Màu trắng/xám trung tính cho phép phối với hầu hết mọi tông màu trong tủ đồ.</p>\n</div>', '0', '1', '454', '8', '1', '2026-08-18 13:31:27'),
('18', 'SHOE-0018', 'New Balance 2002R Protection Pack Rain Cloud', 'new-balance-2002r-protection-pack-rain-cloud', '8', '4', 'Unisex', '4250000', '4800000', '2700000', '11', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '<div class=\"product-description\">\n<h3>New Balance 2002R Protection Pack Rain Cloud – Đỉnh Cao Nghệ Thuật Giày Chạy Vintage</h3>\n<p><strong>New Balance 2002R</strong> là phiên bản retro của dòng 2002 – một trong những mẫu giày chạy high-performance của NB từ thập niên 2000. Bộ sưu tập <em>Protection Pack</em> được thiết kế với cảm hứng từ trang phục bảo hộ thể thao, sử dụng màu sắc earth tone muted và vật liệu cao cấp để tạo nên những đôi giày chunky luxury sneaker đích thực. Colorway Rain Cloud với tông xám bạc nhạt mờ sương – tên gọi gợi lên hình ảnh những đám mây mưa Bắc Đại Tây Dương.</p>\n<h4>Protection Pack – Dự Án Đặc Biệt Của NB</h4>\n<p>Protection Pack 2002R được ra mắt năm 2020-2021 với một loạt colorway lấy cảm hứng từ hiện tượng tự nhiên: Rain Cloud, Steel, Marble, Smoke... Mỗi colorway không chỉ là màu sắc mà còn là một câu chuyện về vật liệu và texture độc đáo. Đây là dự án đã đưa NB 2002R từ một đôi giày cổ điển thành một biểu tượng luxury streetwear.</p>\n<h4>Công Nghệ Đỉnh Cao</h4>\n<ul>\n<li><strong>Đệm ABZORB® SBS (Shock Barrier System):</strong> Phiên bản nâng cao của ABZORB thông thường, thêm vào một lớp cao su đặc biệt (SBS – Styrene-Butadiene-Styrene) để tăng khả năng hấp thụ lực tác động ở mức độ cao nhất.</li>\n<li><strong>Hệ thống ổn định ENCAP® kép:</strong> Hai lớp ENCAP tại midsole cho độ ổn định và đệm tốt hơn so với các model thông thường.</li>\n<li><strong>Upper Suede cao cấp + Mesh kỹ thuật:</strong> Phần suede mờ mịn tạo texture đặc biệt khi phản chiếu ánh sáng, kết hợp mesh kỹ thuật ở những vùng cần thoáng khí.</li>\n<li><strong>Đế ngoài phức tạp đa tầng:</strong> Cấu trúc đế nhiều tầng với các pattern khác nhau tạo nên profile chunky đặc trưng và độ bám xuất sắc.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>New Balance 2002R</td></tr>\n<tr><td>Bộ sưu tập</td><td>Protection Pack</td></tr>\n<tr><td>Colorway</td><td>Rain Cloud/White Pepper</td></tr>\n<tr><td>Upper</td><td>Suede + Technical Mesh</td></tr>\n<tr><td>Midsole</td><td>ABZORB SBS + Dual ENCAP</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ</td></tr>\n<tr><td>Năm ra mắt phiên bản này</td><td>2021</td></tr>\n</table>\n</div>', '0', '1', '2001', '23', '1', '2026-08-18 13:31:27'),
('19', 'SHOE-0019', 'New Balance 990v5 Made in USA Grey', 'new-balance-990v5-made-in-usa-grey', '6', '4', 'Unisex', '5600000', '6200000', '3600000', '10', 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', '<div class=\"product-description\">\n<h3>New Balance 990v5 Made in USA – Tuyệt Tác Sản Xuất Nội Địa Mỹ</h3>\n<p>Trong thời đại mọi thứ đều được outsource sang châu Á để tiết kiệm chi phí, <strong>New Balance 990v5 Made in USA</strong> là tuyên bố hào hùng về sự tự hào sản xuất nội địa Mỹ. Được sản xuất hoàn toàn tại nhà máy của NB ở Massachusetts và Maine, Mỹ, với ít nhất 70% thành phần nguồn gốc từ Mỹ, 990v5 là đỉnh cao của ngành giày thể thao Mỹ trong cả chất lượng lẫn heritage.</p>\n<h4>Dòng 990 – 40+ Năm Di Sản Bất Hủ</h4>\n<p>New Balance 990 ra đời năm 1982 với mức giá 100 USD – mức giá chưa từng có trong lịch sử giày thể thao lúc bấy giờ. NB định giá cao như vậy vì đây là đôi giày tốt nhất họ có thể làm, sử dụng vật liệu và công nghệ tốt nhất. Và sau 40+ năm, triết lý đó vẫn không thay đổi. 990v5 là phiên bản thứ 5 của dòng huyền thoại này, tiếp tục được sản xuất theo tiêu chuẩn chất lượng cao nhất.</p>\n<h4>Công Nghệ Đỉnh Cao Của NB</h4>\n<ul>\n<li><strong>ENCAP® Midsole System (Toàn bộ chiều dài):</strong> ENCAP chạy từ gót đến mũi, không chỉ ở một điểm như các model thông thường. Đây là hệ thống đệm hoàn chỉnh và hiệu quả nhất của NB.</li>\n<li><strong>Suede premium Pigsuede:</strong> Da lợn suede chất lượng cao – loại suede mềm nhất, bền nhất và cao cấp nhất NB sử dụng. Bề mặt texture đặc biệt, sang trọng khác biệt hoàn toàn so với suede thông thường.</li>\n<li><strong>Mesh nylon kỹ thuật:</strong> Vải nylon technical tại những vùng cần thoáng khí, nhẹ nhàng và chắc chắn.</li>\n<li><strong>Blown rubber outsole:</strong> Cao su blown nhẹ hơn solid rubber nhưng vẫn bền và có độ bám tốt, cân bằng hoàn hảo giữa nhẹ và chắc chắn.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>New Balance 990v5</td></tr>\n<tr><td>SKU</td><td>M990GL5</td></tr>\n<tr><td>Xuất xứ</td><td>Made in USA (Massachusetts)</td></tr>\n<tr><td>Colorway</td><td>Grey/Navy (Flagship Grey)</td></tr>\n<tr><td>Upper</td><td>Pigsuede + Nylon Mesh</td></tr>\n<tr><td>Midsole</td><td>ENCAP Full-Length</td></tr>\n<tr><td>Outsole</td><td>Blown Rubber</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ</td></tr>\n</table>\n<h4>Tại Sao Giá Cao Hơn?</h4>\n<p>990v5 đắt hơn các sneaker thông thường vì toàn bộ quy trình sản xuất diễn ra ở Mỹ với mức lương công nhân Mỹ, tiêu chuẩn lao động Mỹ và vật liệu chất lượng cao nhất. Đây không chỉ là đôi giày – đây là tuyên bố giá trị về chất lượng và đạo đức sản xuất.</p>\n</div>', '1', '1', '2913', '14', '1', '2026-08-18 13:31:27'),
('20', 'SHOE-0020', 'New Balance 1906R Silver Metallic', 'new-balance-1906r-silver-metallic', '6', '4', 'Unisex', '3350000', '3800000', '2050000', '12', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '<div class=\"product-description\">\n<h3>New Balance 1906R Silver Metallic – Giày Chạy Y2K Thống Trị Thời Trang 2024</h3>\n<p><strong>New Balance 1906R</strong> là một trong những success story kỳ diệu nhất của thập kỷ trong ngành giày: từ một mẫu giày chạy performance tầm thường ra đời năm 2006, trải qua nhiều năm im ắng, rồi đột nhiên bùng cháy thành \"the shoe\" của trend Y2K revival năm 2022-2024 nhờ aesthetic chrome/metallic hoàn toàn phù hợp với tái hiện vibe thập niên 2000.</p>\n<h4>Y2K Revival Và 1906R</h4>\n<p>Phiên bản Silver Metallic của 1906R với tông bạc ánh kim loại, chi tiết phản sáng và cấu trúc đế phức tạp đặc trưng đã trở thành một trong những đôi giày được tìm kiếm nhiều nhất trên StockX và GOAT năm 2023. Nó xuất hiện trên chân của các influencer thời trang toàn cầu, trong các editorial fashion magazine và trên cả runway show của các designer đương đại.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>ABZORB® DTS (Dual-Density Technology):</strong> Hệ thống đệm dual-density tiên tiến với hai mật độ foam khác nhau: mềm ở tâm để hấp thụ lực, cứng ở viền để ổn định.</li>\n<li><strong>ENCAP® ở vùng gót:</strong> Lớp ENCAP tại gót cung cấp ổn định và bảo vệ thêm tại điểm tiếp đất.</li>\n<li><strong>N-ergy nanostructured foam:</strong> Công nghệ foam nano độc quyền tạo đệm siêu nhẹ và phản hồi tốt.</li>\n<li><strong>Reflective Metallic Upper:</strong> Vật liệu phản sáng kết hợp với mesh kỹ thuật. Dưới ánh đèn, giày phản chiếu ánh sáng như bạc. Ban ngày, màu xám bạc mờ nhẹ nhàng tinh tế.</li>\n<li><strong>Đế chunky đa tầng phức tạp:</strong> Profile đế chunky đặc trưng với nhiều tầng màu sắc và texture khác nhau – đây chính là nét đẹp Y2K futuristic không thể nhầm lẫn của 1906R.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>New Balance 1906R</td></tr>\n<tr><td>SKU</td><td>M1906RSL</td></tr>\n<tr><td>Colorway</td><td>Silver Metallic/White</td></tr>\n<tr><td>Upper</td><td>Reflective Mesh + Overlay</td></tr>\n<tr><td>Midsole</td><td>ABZORB DTS + ENCAP + N-ergy</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ</td></tr>\n</table>\n</div>', '0', '1', '1149', '20', '1', '2026-08-18 13:31:27'),
('21', 'SHOE-0021', 'Converse Chuck Taylor All Star 1970s High Top Black', 'converse-chuck-taylor-all-star-1970s-high-top-black', '5', '5', 'Unisex', '1850000', '2100000', '1100000', '12', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', '<div class=\"product-description\">\n<h3>Converse Chuck Taylor All Star 1970s High Top Black – Huyền Thoại 100+ Năm Không Tuổi</h3>\n<p>Không có đôi giày nào trên thế giới có lịch sử lâu đời hơn và vẫn còn phù hợp hơn <strong>Converse Chuck Taylor All Star</strong>. Ra đời năm 1917 – hơn 100 năm trước – giày Chuck Taylor không chỉ là một đôi giày, mà là một thiết chế văn hoá. Từ sân bóng rổ đến concert punk rock, từ canvas art đến streetwear hiện đại, Chuck Taylor đã hiện diện ở khắp nơi. Phiên bản <strong>1970s</strong> là phiên bản tái tạo chính xác thiết kế gốc với những chi tiết vintage mà phiên bản hiện đại đã bỏ đi.</p>\n<h4>Sự Khác Biệt Của Phiên Bản 1970s</h4>\n<p>Phiên bản Chuck Taylor 1970s khác với Chuck Taylor thông thường ở những chi tiết chỉ người am hiểu mới nhận ra:</p>\n<ul>\n<li><strong>Mũi giày nhọn hơn (Cupsole Toe Cap cao hơn):</strong> Mũi giày cao su của bản 70s nhọn và cao hơn phiên bản hiện đại, trung thành hơn với thiết kế gốc thập niên 70.</li>\n<li><strong>Upper canvas dày và nặng hơn:</strong> Vải canvas 70s dày và chắc hơn canvas thông thường, mang lại cảm giác cao cấp và bền hơn theo thời gian.</li>\n<li><strong>Lót trong OrthoLite® cao cấp:</strong> Phiên bản 70s có lót trong OrthoLite đệm êm hơn đáng kể so với bản thường – đây là một trong những nâng cấp lớn nhất.</li>\n<li><strong>Nhãn \"Made with Ortholite\" và logo Chuck Taylor gốc:</strong> Chi tiết vintage nhỏ trên lưỡi gà và thân giày được tái tạo chính xác từ thiết kế gốc thập niên 70.</li>\n<li><strong>Đế cao su dày hơn:</strong> Profile đế của 70s cao hơn một chút so với bản thường, tạo nên dáng đứng cao hơn và tỷ lệ đẹp hơn.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Converse Chuck Taylor All Star 1970s</td></tr>\n<tr><td>SKU</td><td>162050C</td></tr>\n<tr><td>Colorway</td><td>Black/Black/Egret</td></tr>\n<tr><td>Upper</td><td>Heavy Canvas (Dày)</td></tr>\n<tr><td>Midsole</td><td>OrthoLite® (Cao cấp hơn bản thường)</td></tr>\n<tr><td>Outsole</td><td>Rubber Cupsole (Dày hơn bản thường)</td></tr>\n<tr><td>Độ cao cổ</td><td>High-Top</td></tr>\n<tr><td>Năm thiết kế gốc</td><td>1917 (tái tạo thập niên 70)</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n</div>', '0', '1', '2759', '8', '1', '2026-08-18 13:31:27'),
('22', 'SHOE-0022', 'Converse Run Star Hike High Top White', 'converse-run-star-hike-high-top-white', '9', '5', 'Unisex', '2650000', '3000000', '1600000', '12', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800', '<div class=\"product-description\">\n<h3>Converse Run Star Hike High Top White – Streetwear Tương Lai Từ DNA Cổ Điển</h3>\n<p>Khi Converse muốn ra một đôi giày mang toàn bộ linh hồn Chuck Taylor nhưng được tái tưởng tượng hoàn toàn cho thế kỷ 21, kết quả là <strong>Run Star Hike</strong>. Đây là đôi giày táo bạo nhất Converse từng làm: vẫn là canvas classic, vẫn là Chuck Star Patch – nhưng đế ngoài cần cứng chunky hiking boot và midsole EVA dày 5cm đã biến đây thành một tuyên bố thời trang khác hoàn toàn.</p>\n<h4>Thiết Kế Đột Phá</h4>\n<p>Run Star Hike ra đời từ sự hợp tác giữa các designer nội bộ Converse với nhà thiết kế thời trang đương đại. Mấu chốt của thiết kế là sự đối lập: canvas mỏng manh trên cùng một đôi giày với đế cứng cáp chunky hiking boot. Sự đối lập này tạo ra một aesthetic vừa cổ điển vừa futuristic, vừa quen thuộc vừa hoàn toàn mới mẻ.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper canvas truyền thống Converse:</strong> Vải canvas đặc trưng của Converse giữ nguyên để tôn trọng DNA cốt lõi của thương hiệu.</li>\n<li><strong>Midsole EVA siêu dày (5cm):</strong> Cao hơn gấp 3 lần so với Chuck Taylor thông thường. Đây chính là chi tiết tạo nên sự ấn tượng mạnh mẽ nhất khi nhìn vào Run Star Hike.</li>\n<li><strong>Đế ngoài Lug chunky (Kiểu hiking boot):</strong> Họa tiết đế sâu, cứng cáp lấy cảm hứng từ đế hiking boot để tạo độ bám mạnh trên mọi địa hình và thêm chiều cao cần thiết để đế chunky không bị trơn trượt.</li>\n<li><strong>Chuck Patch và Star logo nguyên bản:</strong> Mọi chi tiết logo Converse đều được giữ nguyên 100% để khẳng định DNA Converse.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Converse Run Star Hike</td></tr>\n<tr><td>SKU</td><td>168467C</td></tr>\n<tr><td>Colorway</td><td>White/Black/Gum</td></tr>\n<tr><td>Upper</td><td>Classic Canvas</td></tr>\n<tr><td>Midsole</td><td>EVA (Siêu dày 5cm)</td></tr>\n<tr><td>Outsole</td><td>Chunky Lug Rubber</td></tr>\n<tr><td>Độ cao cổ</td><td>High-Top</td></tr>\n<tr><td>Platform height</td><td>~50mm</td></tr>\n</table>\n</div>', '0', '1', '2864', '21', '1', '2026-08-18 13:31:27'),
('23', 'SHOE-0023', 'Converse Chuck 70 Low Top Vintage Parchment', 'converse-chuck-70-low-top-vintage-parchment', '8', '5', 'Unisex', '1750000', '1950000', '1050000', '10', 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?q=80&w=800', '<div class=\"product-description\">\n<h3>Converse Chuck 70 Low Vintage Parchment – Vẻ Đẹp Cổ Điển Được Nâng Cấp</h3>\n<p><strong>Converse Chuck 70</strong> (viết tắt của Chuck Taylor 1970s nhưng được bán riêng với branding khác biệt) là phiên bản \"premium\" của dòng Chuck Taylor, được thiết kế đặc biệt cho những ai yêu thích thẩm mỹ vintage cổ điển nhưng muốn có sự thoải mái và chất lượng cao hơn phiên bản thông thường. Colorway <em>Vintage Parchment</em> với tông vàng kem nhẹ nhàng như tờ giấy cũ chính là màu sắc được yêu thích nhất trong mùa hè 2024.</p>\n<h4>Low-Top – Sự Tự Do Cho Mắt Cá Chân</h4>\n<p>Nếu High-Top Converse là biểu tượng của punk rock và streetwear đô thị, thì Low-Top Chuck 70 là lựa chọn thanh lịch, tinh tế và dễ phối đồ hơn. Thiết kế thấp cổ phù hợp với cả quần short mùa hè lẫn quần jeans ống rộng hay váy midi – đây là đôi giày \"chameleon\" linh hoạt nhất trong tủ đồ.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper Canvas Parchment (Vàng kem vintage):</strong> Màu off-white kem nhẹ nhàng, không quá trắng chói. Tông màu này đứng ở vị trí lý tưởng giữa trắng và cream, vừa clean vừa warm.</li>\n<li><strong>Mũi cao su Cupsole vintage cao hơn:</strong> Giống phiên bản 70s High, Chuck 70 Low có mũi cao su cao và nhọn hơn so với bản thường.</li>\n<li><strong>Lót OrthoLite® đệm êm:</strong> Lót trong cao cấp giảm mỏi chân đáng kể so với Chuck Taylor thường.</li>\n<li><strong>Canvas nặng dày hơn bản thường:</strong> Vải chắc chắn hơn, bền hơn và có texture đặc biệt khi đã \"aged\" theo thời gian.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Converse Chuck 70 Low</td></tr>\n<tr><td>Colorway</td><td>Vintage Parchment/Natural/Black</td></tr>\n<tr><td>Upper</td><td>Heavy Canvas</td></tr>\n<tr><td>Lót trong</td><td>OrthoLite®</td></tr>\n<tr><td>Độ cao cổ</td><td>Low-Top</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n</div>', '0', '1', '1290', '23', '1', '2026-08-18 13:31:27'),
('24', 'SHOE-0024', 'Converse One Star Pro Suede Black', 'converse-one-star-pro-suede-black', '8', '5', 'Unisex', '1950000', '2200000', '1200000', '11', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', '<div class=\"product-description\">\n<h3>Converse One Star Pro Suede Black – Giày Của Văn Hoá Skate Underground</h3>\n<p>Khi Converse muốn tạo ra một đôi giày dành riêng cho văn hoá skateboarding, <strong>One Star</strong> ra đời năm 1974. Không giống Chuck Taylor với logo ngôi sao lớn nằm ở mắt cá chân, One Star có logo ngôi sao tổng thể nằm ở thân giày, và thay vì canvas, nó được làm từ suede – phù hợp hơn với nhu cầu chịu mài mòn của skater. Phiên bản Pro là phiên bản được hoàn thiện cho skateboarding hiện đại.</p>\n<h4>Từ Skate Park Đến Phố</h4>\n<p>One Star nổi tiếng trong cộng đồng skate nhờ độ bám board flip tốt của đế cao su, sự thoải mái và giá thành hợp lý. Nhưng cũng giống như nhiều đôi giày skate khác (Vans Old Skool, DC, Emerica...), One Star dần được chấp nhận rộng rãi trong streetwear vì vẻ đẹp đơn giản, thực dụng và cool ngầm của nó.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Upper Suede cao cấp màu đen:</strong> Da lộn đen mềm mại, texture đặc biệt. Suede của One Star Pro dày và bền hơn suede thông thường để chịu được ma sát của skateboarding.</li>\n<li><strong>Đế Ortholite® tăng đệm:</strong> Lót trong OrthoLite giúp hấp thụ lực tác động khi nhảy và đáp xuống sân – rất quan trọng với skater.</li>\n<li><strong>Cupsole cao su cứng chống mòn:</strong> Đế cao su dày tại các điểm tiếp xúc nhiều nhất với board, bảo vệ phần upper khỏi mài mòn.</li>\n<li><strong>Ngôi sao One Star logo:</strong> Ngôi sao đơn khâu đắp trực tiếp lên thân suede – chi tiết nhận dạng đặc trưng không thể nhầm lẫn của dòng One Star.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Converse One Star Pro</td></tr>\n<tr><td>SKU</td><td>A04594C</td></tr>\n<tr><td>Colorway</td><td>Black/Black/Black</td></tr>\n<tr><td>Upper</td><td>Suede cao cấp</td></tr>\n<tr><td>Lót trong</td><td>OrthoLite®</td></tr>\n<tr><td>Đế</td><td>Vulcanized Rubber Cupsole</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n</div>', '0', '1', '2643', '15', '1', '2026-08-18 13:31:27'),
('25', 'SHOE-0025', 'Converse Chuck Taylor All Star Classic Navy', 'converse-chuck-taylor-all-star-classic-navy', '5', '5', 'Unisex', '1450000', '1650000', '850000', '12', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', '<div class=\"product-description\">\n<h3>Converse Chuck Taylor All Star Classic Navy – Sắc Xanh Hải Quân Trường Tồn Theo Thời Gian</h3>\n<p>Nếu có một màu sắc duy nhất tóm gọn được tinh thần Mỹ trong một đôi giày, đó là Navy Blue – màu xanh hải quân truyền thống. <strong>Converse Chuck Taylor All Star Classic Navy</strong> là một trong những đôi giày bán chạy nhất mọi thời đại, xuất hiện liên tục trong mọi thập kỷ từ 1940 đến nay. Từ những thủy thủ trẻ Mỹ thời chiến đến sinh viên đại học ngày nay – Chuck Taylor Navy là đôi giày của mọi thế hệ.</p>\n<h4>Ý Nghĩa Văn Hoá Của Navy Chuck</h4>\n<p>Màu Navy Blue Converse xuất hiện sớm nhất trong lịch sử dòng giày này và gắn liền với hình ảnh \"American youth\" trong suốt thế kỷ 20. Nó là đôi giày của các ban nhạc garage rock thập niên 60, của phong trào anti-war thập niên 70, của college casual thập niên 80-90 và của gen Z retro-cool ngày nay.</p>\n<h4>Công Nghệ & Chất Liệu</h4>\n<ul>\n<li><strong>Canvas cotton truyền thống:</strong> Vải canvas cotton 100% thoáng khí, nhẹ nhàng và dễ vệ sinh. Màu navy đậm bền màu, không dễ phai.</li>\n<li><strong>Rubber Cupsole trắng classic:</strong> Đế cao su trắng tương phản với thân navy tạo nên look cổ điển clean và crisp đặc trưng.</li>\n<li><strong>Chuck Patch logo bất hủ:</strong> Huy hiệu chuck patch tròn ở mắt cá chân – chi tiết nhận dạng toàn cầu của Converse hơn 100 năm nay.</li>\n<li><strong>Lót in cotton tiêu chuẩn:</strong> Lót in cotton mỏng giúp chân thoáng khí và dễ vệ sinh.</li>\n</ul>\n<h4>Thông Số Kỹ Thuật</h4>\n<table class=\"table table-bordered table-sm\">\n<tr><th>Đặc điểm</th><th>Chi tiết</th></tr>\n<tr><td>Model</td><td>Converse Chuck Taylor All Star</td></tr>\n<tr><td>Colorway</td><td>Navy Blue/White</td></tr>\n<tr><td>Upper</td><td>Cotton Canvas</td></tr>\n<tr><td>Outsole</td><td>Rubber Cupsole</td></tr>\n<tr><td>Độ cao cổ</td><td>Low-Top</td></tr>\n<tr><td>Phù hợp</td><td>Nam, Nữ (Unisex)</td></tr>\n</table>\n<h4>Hướng Dẫn Phối Đồ</h4>\n<p>Chuck Taylor Navy phối đẹp nhất với tông trắng và be – white tee + navy chuck là combo bất bại. Jeans xanh đậm + white linen shirt + navy chuck là look coastal Mỹ hoàn hảo. Pleated khaki trousers + striped breton top + navy chuck là aesthetic Pháp đơn giản và tinh tế không cần cố gắng.</p>\n</div>', '0', '1', '1095', '17', '1', '2026-08-18 13:31:27');

-- ------------------------------------------------------------
-- Table structure for `sale_events`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `sale_events`;
CREATE TABLE `sale_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `banner_image` varchar(500) DEFAULT NULL,
  `hero_banner_image` varchar(500) DEFAULT NULL,
  `hero_banner_title` varchar(255) DEFAULT NULL,
  `hero_banner_subtitle` varchar(500) DEFAULT NULL,
  `show_on_menu` tinyint(1) DEFAULT 1,
  `show_on_homepage_banner` tinyint(1) DEFAULT 1,
  `color_theme` varchar(20) DEFAULT '#ef4444',
  `icon` varchar(100) DEFAULT 'fa-solid fa-fire',
  `icon_image` varchar(500) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `sale_events` (15 rows)
INSERT INTO `sale_events` VALUES
('1', 'SALE 19/8', 'sale-19-8', 'Đại lễ 19/8 – Giảm giá sốc chỉ duy nhất trong 24 giờ ngày 19/8!', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', '', '🇻🇳 SALE 19/8 - ĐẠI TIỆC 1 NGÀY DUY NHẤT', 'Giảm sốc đến 35% toàn bộ sản phẩm + Tặng Voucher 150K', '1', '1', '#dc2626', 'fa-solid fa-flag', '', '2026-08-19 00:00:00', '2026-08-19 23:59:59', '1', '1', '2026-08-18 13:31:27'),
('2', 'Siêu Hội Ngày Đôi 1/1 - Khai Xuân Rực Rỡ', 'sieu-hoi-ngay-doi-1-1', 'Mở màn năm mới 2026 với ưu đãi cực khủng lì xì đầu năm cho mọi hóa đơn mua giày thể thao.', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=1200', 'SIÊU SALE 1/1 TẾT DƯƠNG LỊCH', 'Lì Xì Ngay 100K Cho Đơn Hàng Đầu Tiên', '1', '1', '#b91c1c', 'fa-solid fa-gift', NULL, '2026-01-01 00:00:00', '2026-01-05 23:59:59', '1', '2', '2026-08-18 13:31:27'),
('3', 'Siêu Hội Ngày Đôi 2/2 - Rộn Ràng Đón Tết', 'sieu-hoi-ngay-doi-2-2', 'Sắm giày mới diện Tết Bính Ngọ 2026. Giảm giá sốc các mẫu Sneaker trắng và giày chạy bộ.', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=1200', 'TẾT SALE 2/2 - VUI XUÂN MỚI', 'Đồng Giá Sneaker Hot Từ 1.250.000đ', '1', '1', '#ea580c', 'fa-solid fa-fire', NULL, '2026-02-02 00:00:00', '2026-02-06 23:59:59', '1', '3', '2026-08-18 13:31:27'),
('4', 'Siêu Hội Ngày Đôi 3/3 - Đón Đầu Xu Hướng', 'sieu-hoi-ngay-doi-3-3', 'Lễ hội mua sắm đầu xuân, cập nhật các mẫu giày Samba và Dad Shoes mới nhất.', 'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?q=80&w=800', 'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?q=80&w=1200', 'SIÊU SALE 3/3 - RETRO TREND', 'Giảm Đến 30% Bộ Sưu Tập Adidas & New Balance', '1', '1', '#059669', 'fa-solid fa-bolt', NULL, '2026-03-03 00:00:00', '2026-03-07 23:59:59', '1', '4', '2026-08-18 13:31:27'),
('5', 'Siêu Hội Ngày Đôi 4/4 - Lễ Hội Thể Thao', 'sieu-hoi-ngay-doi-4-4', 'Ưu đãi giày chạy bộ và tập luyện thể thao chuẩn bị cho mùa hè năng động.', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200', 'SPORT FESTIVAL 4/4', 'Giày Chạy Bộ Nike & Ultraboost Giảm 25%', '1', '1', '#2563eb', 'fa-solid fa-person-running', NULL, '2026-04-04 00:00:00', '2026-04-08 23:59:59', '1', '5', '2026-08-18 13:31:27'),
('6', 'Siêu Hội Ngày Đôi 5/5 - Khởi Động Hè Sang', 'sieu-hoi-ngay-doi-5-5', 'Đại tiệc chào hè, giảm sốc các dòng dép Slide bọt đúc và sandal thể thao.', 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?q=80&w=800', 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?q=80&w=1200', 'SUMMER KICKOFF 5/5', 'Dép Quai Ngang & Sandal Sale Kịch Sàn', '1', '1', '#d97706', 'fa-solid fa-sun', NULL, '2026-05-05 00:00:00', '2026-05-09 23:59:59', '1', '6', '2026-08-18 13:31:27'),
('7', 'Siêu Hội Ngày Đôi 6/6 - Giữa Năm Bùng Nổ', 'sieu-hoi-ngay-doi-6-6', 'Đại lễ hội mua sắm giữa năm Mid-Year Sale giảm đến 35% toàn hệ thống.', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=800', 'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?q=80&w=1200', 'MID-YEAR MEGA SALE 6/6', 'Flash Sale Đồng Giá Toàn Hệ Thống', '1', '1', '#7c3aed', 'fa-solid fa-tags', NULL, '2026-06-06 00:00:00', '2026-06-10 23:59:59', '1', '7', '2026-08-18 13:31:27'),
('8', 'Siêu Hội Ngày Đôi 7/7 - Đại Tiệc Sneaker', 'sieu-hoi-ngay-doi-7-7', 'Tháng 7 rực rỡ với hàng loạt siêu phẩm Jordan và Converse giảm kịch sàn.', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=1200', 'SNEAKER FEST 7/7', 'Tặng Kèm Tất Chính Hãng & Miễn Phí Giao Hàng', '1', '1', '#0891b2', 'fa-solid fa-shoe-prints', NULL, '2026-07-07 00:00:00', '2026-07-11 23:59:59', '1', '8', '2026-08-18 13:31:27'),
('9', 'Siêu Hội Ngày Đôi 8/8 - Flash Sale Cực Đỉnh', 'sieu-hoi-ngay-doi-8-8', 'Siêu hội bão giá tháng 8 với các khung giờ Flash Sale săn giày hiệu từ 999K.', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=1200', 'SIÊU BÃO GIÁ 8/8', 'Săn Giày Hàng Hiệu Giá Chỉ Từ 999K', '1', '1', '#db2777', 'fa-solid fa-percent', NULL, '2026-08-08 00:00:00', '2026-08-12 23:59:59', '1', '9', '2026-08-18 13:31:27'),
('10', 'Siêu Hội Ngày Đôi 9/9 - Siêu Mua Sắm Mùa Thu', 'sieu-hoi-ngay-doi-9-9', 'Mở đầu mùa lễ hội mua sắm cuối năm với hàng ngàn voucher giảm giá khủng.', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=1200', 'MEGA SHOPPING 9/9', 'Đón Thu Sang - Săn Giày Xịn', '1', '1', '#4f46e5', 'fa-solid fa-cart-shopping', NULL, '2026-09-09 00:00:00', '2026-09-13 23:59:59', '1', '10', '2026-08-18 13:31:28'),
('11', 'Siêu Hội Ngày Đôi 10/10 - Lễ Hội Hàng Hiệu', 'sieu-hoi-ngay-doi-10-10', 'Ngày hội thương hiệu chính hãng quy tụ các BST giới hạn từ Nike và Adidas.', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=1200', 'BRAND DAY 10/10', 'Bảo Hành 12 Tháng - Đổi Trả Miễn Phí 30 Ngày', '1', '1', '#0284c7', 'fa-solid fa-award', NULL, '2026-10-10 00:00:00', '2026-10-14 23:59:59', '1', '11', '2026-08-18 13:31:28'),
('12', 'Siêu Hội Ngày Đôi 11/11 - Đại Tiệc Độc Thân Toàn Cầu', 'sieu-hoi-ngay-doi-11-11', 'Đại lễ mua sắm Single Day 11/11 lớn nhất hành tinh, giảm đến 50% cho toàn bộ sản phẩm.', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=800', 'https://images.unsplash.com/photo-1552346154-21d32810aba3?q=80&w=1200', 'SINGLE DAY 11/11 - ĐẠI TIỆC TOÀN CẦU', 'Giảm Đến 50% - Độc Quyền Duy Nhất Trong Năm', '1', '1', '#e11d48', 'fa-solid fa-gem', NULL, '2026-11-11 00:00:00', '2026-11-15 23:59:59', '1', '12', '2026-08-18 13:31:28'),
('13', 'Siêu Hội Ngày Đôi 12/12 - Siêu Sale Cuối Năm Xả Kho', 'sieu-hoi-ngay-doi-12-12', 'Xả kho đón Giáng Sinh và Năm Mới 2027 với mức giá tri ân khách hàng cực hời.', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=800', 'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?q=80&w=1200', 'YEAR END SALE 12/12', 'Xả Kho Đón Năm Mới - Tri Ân Khách Hàng', '1', '1', '#16a34a', 'fa-solid fa-tree', NULL, '2026-12-12 00:00:00', '2026-12-16 23:59:59', '1', '13', '2026-08-18 13:31:28'),
('14', 'SALE 18/8', 'sale-18-8', 'Săn deal sớm đón đại lễ – Giảm giá cực sốc duy nhất ngày 18/8!', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1600', '⚡ SALE 18/8 - SĂN DEAL SỚM ĐÓN ĐẠI LỄ', 'Ưu đãi sớm 1 ngày – Giảm đến 30% toàn shop', '1', '1', '#ea580c', 'fa-solid fa-bolt', NULL, '2026-08-18 00:00:00', '2026-08-18 23:59:59', '1', '0', '2026-08-18 15:18:14'),
('15', 'Flash Sale Giờ Vàng (20h - 22h)', 'gio-vang-20h-22h', 'Khung giờ vàng mỗi tối 20:00 - 22:00 giảm sốc đến 40%!', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=1200', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=1600', '⏰ FLASH SALE GIỜ VÀNG 20:00 - 22:00', 'Deal chớp nhoáng mỗi tối – Giá sập sàn số lượng giới hạn!', '1', '1', '#eab308', 'fa-solid fa-clock', NULL, '2026-08-18 20:00:00', '2026-12-31 22:00:00', '1', '0', '2026-08-18 15:18:14');

-- ------------------------------------------------------------
-- Table structure for `saved_vouchers`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `saved_vouchers`;
CREATE TABLE `saved_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_voucher` (`user_id`,`voucher_id`),
  KEY `voucher_id` (`voucher_id`),
  CONSTRAINT `saved_vouchers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_vouchers_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `shipping_provinces`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `shipping_provinces` (64 rows)
INSERT INTO `shipping_provinces` VALUES
('1', 'Hà Nội', '1760', '41000', '3-5 ngày', '1'),
('2', 'TP. Hồ Chí Minh', '135', '17000', '1-2 ngày', '1'),
('3', 'Đà Nẵng', '950', '29000', '2-4 ngày', '1'),
('4', 'Vĩnh Long', '10', '15000', 'Nội thành (1 ngày)', '1'),
('5', 'Cần Thơ', '35', '16000', '1-2 ngày', '1'),
('6', 'Bình Dương', '160', '17000', '1-2 ngày', '1'),
('7', 'Đồng Nai', '170', '18000', '1-2 ngày', '1'),
('8', 'Hải Phòng', '1800', '42000', '3-5 ngày', '1'),
('9', 'Tỉnh/Thành Khác', '1000', '30000', '3-5 ngày', '1'),
('10', 'An Giang', '110', '17000', '1-2 ngày', '1'),
('11', 'Bà Rịa - Vũng Tàu', '210', '18000', '1-2 ngày', '1'),
('12', 'Bắc Giang', '1820', '42000', '3-5 ngày', '1'),
('13', 'Bắc Kạn', '1930', '44000', '3-5 ngày', '1'),
('14', 'Bạc Liêu', '135', '17000', '1-2 ngày', '1'),
('15', 'Bắc Ninh', '1790', '42000', '3-5 ngày', '1'),
('16', 'Bến Tre', '60', '16000', '1-2 ngày', '1'),
('17', 'Bình Định', '720', '26000', '2-4 ngày', '1'),
('18', 'Bình Phước', '240', '19000', '1-2 ngày', '1'),
('19', 'Bình Thuận', '320', '20000', '2-3 ngày', '1'),
('20', 'Cà Mau', '200', '18000', '1-2 ngày', '1'),
('21', 'Cao Bằng', '2030', '45000', '3-5 ngày', '1'),
('22', 'Đắk Lắk', '500', '23000', '2-4 ngày', '1'),
('23', 'Đắk Nông', '420', '21000', '2-3 ngày', '1'),
('24', 'Điện Biên', '2140', '47000', '3-5 ngày', '1'),
('25', 'Đồng Tháp', '55', '16000', '1-2 ngày', '1'),
('26', 'Gia Lai', '620', '24000', '2-4 ngày', '1'),
('27', 'Hà Giang', '2060', '46000', '3-5 ngày', '1'),
('28', 'Hà Nam', '1720', '41000', '3-5 ngày', '1'),
('29', 'Hà Tĩnh', '1370', '36000', '3-5 ngày', '1'),
('30', 'Hải Dương', '1750', '41000', '3-5 ngày', '1'),
('31', 'Hậu Giang', '70', '16000', '1-2 ngày', '1'),
('32', 'Hòa Bình', '1730', '41000', '3-5 ngày', '1'),
('33', 'Hưng Yên', '1740', '41000', '3-5 ngày', '1'),
('34', 'Khánh Hòa', '520', '23000', '2-4 ngày', '1'),
('35', 'Kiên Giang', '160', '17000', '1-2 ngày', '1'),
('36', 'Kon Tum', '670', '25000', '2-4 ngày', '1'),
('37', 'Lai Châu', '2180', '48000', '3-5 ngày', '1'),
('38', 'Lâm Đồng', '380', '21000', '2-3 ngày', '1'),
('39', 'Lạng Sơn', '1910', '44000', '3-5 ngày', '1'),
('40', 'Lào Cai', '2070', '46000', '3-5 ngày', '1'),
('41', 'Long An', '85', '16000', '1-2 ngày', '1'),
('42', 'Nam Định', '1700', '41000', '3-5 ngày', '1'),
('43', 'Nghệ An', '1450', '37000', '3-5 ngày', '1'),
('44', 'Ninh Bình', '1670', '40000', '3-5 ngày', '1'),
('45', 'Ninh Thuận', '410', '21000', '2-3 ngày', '1'),
('46', 'Phú Thọ', '1840', '43000', '3-5 ngày', '1'),
('47', 'Phú Yên', '630', '24000', '2-4 ngày', '1'),
('48', 'Quảng Bình', '1220', '33000', '2-4 ngày', '1'),
('49', 'Quảng Nam', '910', '29000', '2-4 ngày', '1'),
('50', 'Quảng Ngãi', '820', '27000', '2-4 ngày', '1'),
('51', 'Quảng Ninh', '1880', '43000', '3-5 ngày', '1'),
('52', 'Quảng Trị', '1120', '32000', '2-4 ngày', '1'),
('53', 'Sóc Trăng', '90', '16000', '1-2 ngày', '1'),
('54', 'Sơn La', '1920', '44000', '3-5 ngày', '1'),
('55', 'Tây Ninh', '180', '18000', '1-2 ngày', '1'),
('56', 'Thái Bình', '1730', '41000', '3-5 ngày', '1'),
('57', 'Thái Nguyên', '1840', '43000', '3-5 ngày', '1'),
('58', 'Thanh Hóa', '1590', '39000', '3-5 ngày', '1'),
('59', 'Thừa Thiên Huế', '1050', '31000', '2-4 ngày', '1'),
('60', 'Tiền Giang', '45', '16000', '1-2 ngày', '1'),
('61', 'Trà Vinh', '50', '16000', '1-2 ngày', '1'),
('62', 'Tuyên Quang', '1890', '43000', '3-5 ngày', '1'),
('63', 'Vĩnh Phúc', '1810', '42000', '3-5 ngày', '1'),
('64', 'Yên Bái', '1910', '44000', '3-5 ngày', '1');

-- ------------------------------------------------------------
-- Table structure for `site_settings`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `site_settings` (65 rows)
INSERT INTO `site_settings` VALUES
('1', 'site_name', 'SHOES STORE VIETNAM', 'general'),
('2', 'site_logo', '', 'general'),
('3', 'site_description', 'Hệ thống phân phối Sneaker chính hãng hàng đầu.', 'general'),
('4', 'site_keywords', 'giày sneaker, giày chính hãng, nike, adidas, jordan, dép nam nữ', 'general'),
('5', 'contact_address', 'Xóm Chài, Phường Long Châu, Vĩnh Long, Tỉnh Vĩnh Long, 85067, Việt Nam', 'contact'),
('6', 'contact_hotline', '0909.888.999', 'contact'),
('7', 'contact_email', 'contact@shoesvietnam.vn', 'contact'),
('8', 'bank_id', 'ACB', 'payment'),
('9', 'bank_account', '0123456789', 'payment'),
('10', 'bank_name', 'SHOP OWNER', 'payment'),
('11', 'footer_copyright', '© 2026 SHOES STORE VIETNAM. Bản quyền thuộc về Trang Sĩ Giàu.', 'footer'),
('12', 'hero_title', 'BỨT PHÁ PHONG CÁCH', 'cms'),
('13', 'hero_subtitle', 'Siêu Phẩm Sneaker 2026', 'cms'),
('14', 'hero_image', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'cms'),
('15', 'hero_button_text', 'MUA SẮM NGAY', 'cms'),
('16', 'hero_button_link', 'all-products.php', 'cms'),
('17', 'section_hot_title', '🔥 SẢN PHẨM NỔI BẬT', 'cms'),
('18', 'section_new_title', '✨ HÀNG MỚI VỀ', 'cms'),
('19', 'section_sale_title', '💰 ĐANG GIẢM GIÁ SỐC', 'cms'),
('20', 'section_brand_title', '🏆 THƯƠNG HIỆU NỔI BẬT', 'cms'),
('21', 'section_voucher_title', '🎟️ MÃ GIẢM GIÁ KHUYẾN MÃI', 'cms'),
('22', 'service_1_icon', 'fa-solid fa-truck-fast', 'services'),
('23', 'service_1_title', 'Miễn Phí Vận Chuyển', 'services'),
('24', 'service_1_desc', 'Cho đơn hàng từ 500.000đ', 'services'),
('25', 'service_2_icon', 'fa-solid fa-shield-halved', 'services'),
('26', 'service_2_title', '100% Chính Hãng', 'services'),
('27', 'service_2_desc', 'Cam kết hàng Authentic', 'services'),
('28', 'service_3_icon', 'fa-solid fa-rotate-left', 'services'),
('29', 'service_3_title', 'Đổi Trả 30 Ngày', 'services'),
('30', 'service_3_desc', 'Miễn phí nếu lỗi sản phẩm', 'services'),
('31', 'service_4_icon', 'fa-solid fa-headset', 'services'),
('32', 'service_4_title', 'Hỗ Trợ 24/7', 'services'),
('33', 'service_4_desc', 'Tư vấn mọi lúc mọi nơi', 'services'),
('34', 'ship_fee_mekong', '18000', 'general'),
('35', 'ship_fee_southeast', '22000', 'general'),
('36', 'ship_fee_central', '30000', 'general'),
('37', 'ship_fee_north', '35000', 'general'),
('38', 'free_ship_threshold', '500000', 'general'),
('39', 'voucher_banner_image', 'https://images.unsplash.com/photo-1556906781-9a412961c28c?q=80&w=800', 'cms'),
('40', 'voucher_banner_img', 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=1000', 'cms'),
('41', 'voucher_banner_title', 'SĂN VOUCHER ƯU ĐÃI SỐC 2026', 'cms'),
('42', 'voucher_banner_subtitle', 'Lưu mã ngay vào tài khoản của bạn để nhận chiết khấu và freeship cực lớn!', 'cms'),
('43', 'voucher_banner_link', 'all-products.php', 'cms'),
('44', 'marquee_text', '🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC 🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC 🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN TỪ 500.000Đ | 🎁 MÃ WELCOME50K GIẢM NGAY 50K CHO TÀI KHOẢN MỚI | 🏆 CAM KẾT 100% SẢN PHẨM CHÍNH HÃNG AUTHENTIC | ⚡ FLASH SALE GIẢM GIÁ ĐẾN 33% TẤT CẢ SẢN PHẨM HOT 2026 | 🔁 HỖ TRỢ ĐỔI TRẢ 30 NGÀY NẾU LỖI SẢN PHẨM', 'general'),
('49', 'footer_about', 'Hệ thống phân phối Sneaker chính hãng hàng đầu.', 'footer'),
('52', 'contact_phone', '0909.888.999', 'footer'),
('55', 'social_facebook', 'https://facebook.com/shoesvietnam', 'social'),
('56', 'social_instagram', 'https://instagram.com/shoesvietnam', 'social'),
('57', 'social_tiktok', 'https://tiktok.com/@shoesvietnam', 'social'),
('58', 'social_zalo', 'https://zalo.me/0909888999', 'social'),
('59', 'social_youtube', 'https://youtube.com/@shoesvietnam', 'social'),
('86', 'carrier_active', 'GHTK', 'shipping_api'),
('87', 'ghtk_api_token', 'd8E2109bA78796123456789aBcDeF0123456789', 'shipping_api'),
('88', 'ghtk_environment', 'sandbox', 'shipping_api'),
('89', 'ghtk_pick_name', 'Kho Giày Shoes Store Vĩnh Long', 'shipping_api'),
('90', 'ghtk_pick_tel', '0901234567', 'shipping_api'),
('91', 'ghtk_pick_province', 'Vĩnh Long', 'shipping_api'),
('92', 'ghtk_pick_district', 'Thành phố Vĩnh Long', 'shipping_api'),
('93', 'ghtk_pick_ward', 'Phường 1', 'shipping_api'),
('94', 'ghtk_pick_address', 'Số 123 Đường Phạm Hùng, Phường 1', 'shipping_api'),
('95', 'ghn_api_token', '9f32e29e-648b-11ee-b1d4-92d443b7a81c', 'shipping_api'),
('96', 'ghn_shop_id', '123456', 'shipping_api'),
('97', 'ghn_environment', 'sandbox', 'shipping_api'),
('98', 'ghn_from_district_id', '1442', 'shipping_api'),
('99', 'default_shoe_weight', '800', 'shipping_api');

-- ------------------------------------------------------------
-- Table structure for `size_guide_tips`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `size_guide_tips`;
CREATE TABLE `size_guide_tips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `step_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(100) DEFAULT 'fa-solid fa-ruler',
  `image_url` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `size_guide_tips` (4 rows)
INSERT INTO `size_guide_tips` VALUES
('1', '1', 'Chuẩn Bị Dụng Cụ', 'Chuẩn bị 1 tờ giấy trắng lớn hơn bàn chân, 1 cây bút chì hoặc bút bi, và 1 cây thước kẻ (thước dây hoặc thước thẳng). Nên đo vào buổi chiều hoặc tối khi chân nở tối đa.', 'fa-solid fa-pencil', NULL, '1', '1'),
('2', '2', 'Vẽ Khung Bàn Chân', 'Đặt tờ giấy sát tường, đặt bàn chân phẳng lên giấy (gót chân chạm nhẹ vào tường). Dùng bút vẽ bo viền quanh mép bàn chân thật chuẩn xác, giữ bút thẳng đứng 90 độ.', 'fa-solid fa-shoe-prints', NULL, '2', '1'),
('3', '3', 'Đo Chiều Dài & Chiều Rộng', 'Dùng thước đo khoảng cách từ điểm xa nhất của gót chân đến đầu ngón chân dài nhất (ngón cái hoặc ngón trỏ) để lấy chiều dài L (cm). Đo phần rộng nhất của mu bàn chân để lấy chiều rộng W (cm).', 'fa-solid fa-ruler-combined', NULL, '3', '1'),
('4', '4', 'Đối Chiếu Bảng Size', 'Lấy Chiều dài chân L + 0.5cm (độ dư thoải mái) rồi đối chiếu vào bảng size bên dưới. Nếu chân bè ngang hoặc có mu bàn chân dày, nên chọn tăng thêm +1 Size (VD: 40 lên 41).', 'fa-solid fa-circle-check', NULL, '4', '1');

-- ------------------------------------------------------------
-- Table structure for `size_guides`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `size_guides`;
CREATE TABLE `size_guides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) DEFAULT NULL,
  `gender` enum('all','nam','nu','tre_em') DEFAULT 'all',
  `category_type` enum('all','giay','dep') DEFAULT 'all',
  `foot_length_cm` decimal(4,1) NOT NULL,
  `size_eu` varchar(20) NOT NULL,
  `size_us` varchar(20) DEFAULT NULL,
  `size_uk` varchar(20) DEFAULT NULL,
  `size_cm` varchar(20) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sg_brand` (`brand_id`),
  KEY `idx_sg_gender` (`gender`),
  KEY `idx_sg_foot` (`foot_length_cm`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `size_guides` (11 rows)
INSERT INTO `size_guides` VALUES
('1', NULL, 'all', 'all', '22.0', '35', '4.5', '3.5', '22.5', 'Chuẩn size nữ / chân nhỏ', '1', '1', '2026-08-15 13:09:43'),
('2', NULL, 'all', 'all', '22.5', '36', '5.0', '4.0', '23.0', 'Chuẩn size', '2', '1', '2026-08-15 13:09:43'),
('3', NULL, 'all', 'all', '23.0', '37', '5.5', '4.5', '23.5', 'Chuẩn size', '3', '1', '2026-08-15 13:09:43'),
('4', NULL, 'all', 'all', '23.5', '38', '6.0', '5.0', '24.0', 'Chuẩn size', '4', '1', '2026-08-15 13:09:43'),
('5', NULL, 'all', 'all', '24.5', '39', '6.5', '5.5', '24.5', 'Chuẩn size', '5', '1', '2026-08-15 13:09:43'),
('6', NULL, 'all', 'all', '25.0', '40', '7.0', '6.0', '25.0', 'Size phổ biến nam/nữ', '6', '1', '2026-08-15 13:09:43'),
('7', NULL, 'all', 'all', '25.5', '41', '8.0', '7.0', '26.0', 'Size chuẩn nam', '7', '1', '2026-08-15 13:09:43'),
('8', NULL, 'all', 'all', '26.0', '42', '8.5', '7.5', '26.5', 'Size chuẩn nam', '8', '1', '2026-08-15 13:09:43'),
('9', NULL, 'all', 'all', '26.5', '43', '9.5', '8.5', '27.5', 'Size chuẩn nam', '9', '1', '2026-08-15 13:09:43'),
('10', NULL, 'all', 'all', '27.5', '44', '10.0', '9.0', '28.0', 'Chân lớn', '10', '1', '2026-08-15 13:09:43'),
('11', NULL, 'all', 'all', '28.5', '45', '11.0', '10.0', '29.0', 'Chân ngoại cỡ', '11', '1', '2026-08-15 13:09:43');

-- ------------------------------------------------------------
-- Table structure for `social_links`
-- ------------------------------------------------------------
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

-- Dumping data for table `social_links` (5 rows)
INSERT INTO `social_links` VALUES
('1', 'Facebook', 'https://facebook.com/shoesvietnam', 'fa-brands fa-facebook-f', '1', '1'),
('2', 'Instagram', 'https://instagram.com/shoesvietnam', 'fa-brands fa-instagram', '2', '1'),
('3', 'TikTok', 'https://tiktok.com/@shoesvietnam', 'fa-brands fa-tiktok', '3', '1'),
('4', 'Zalo', 'https://zalo.me/0909888999', 'fa-solid fa-comment-dots', '4', '1'),
('5', 'YouTube', 'https://youtube.com/@shoesvietnam', 'fa-brands fa-youtube', '5', '1');

-- ------------------------------------------------------------
-- Table structure for `user_addresses`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_addresses` (10 rows)
INSERT INTO `user_addresses` VALUES
('1', '7', 'Nguyễn Quốc Thái', '0913111222', NULL, 'Số 45 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', '1', '2026-08-18 13:31:27'),
('2', '8', 'Trần Thanh Trúc', '0913222333', NULL, 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', '1', '2026-08-18 13:31:27'),
('3', '9', 'Lê Hoàng Nam', '0913333444', NULL, 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', '1', '2026-08-18 13:31:27'),
('4', '10', 'Phạm Thị Thúy', '0913444555', NULL, 'Số 15 Đại Lộ Hòa Bình, Quận Ninh Kiều, Cần Thơ', '1', '2026-08-18 13:31:27'),
('5', '11', 'Đặng Minh Khôi', '0913555666', NULL, 'Số 99 Trưng Nữ Vương, Phường 1, TP. Vĩnh Long', '1', '2026-08-18 13:31:27'),
('6', '12', 'Võ Thị Kiều Trang', '0913666777', NULL, 'Số 74 Lạch Tray, Quận Ngô Quyền, Hải Phòng', '1', '2026-08-18 13:31:27'),
('7', '13', 'Bùi Tuấn Anh', '0913777888', NULL, 'Số 23 Đại Lộ Bình Dương, TP. Thủ Dầu Một, Bình Dương', '1', '2026-08-18 13:31:27'),
('8', '14', 'Nguyễn Thanh Huyền', '0913888999', NULL, 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', '1', '2026-08-18 13:31:27'),
('9', '15', 'Đoàn Ngọc Phúc', '0913999000', NULL, 'Số 31 Đồng Khởi, TP. Biên Hòa, Đồng Nai', '1', '2026-08-18 13:31:27'),
('10', '16', 'Hoàng Thị Mai', '0913123789', NULL, 'Số 18 Lê Lợi, TP. Huế, Thừa Thiên Huế', '1', '2026-08-18 13:31:27');

-- ------------------------------------------------------------
-- Table structure for `user_vouchers`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `user_vouchers`;
CREATE TABLE `user_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `saved_at` datetime DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_voucher_unique` (`user_id`,`voucher_id`),
  KEY `voucher_id` (`voucher_id`),
  CONSTRAINT `user_vouchers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_vouchers_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table structure for `users`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users` (16 rows)
INSERT INTO `users` VALUES
('1', 'Tổng Quản Trị Viên', 'admin@gmail.com', '0912345678', '087095001234', 'Số 123 Đường Phạm Hùng, Phường 9, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=300', NULL, 'local', '0', '0', 'admin', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('2', 'Nguyễn Ngọc Lan', 'nv_ngoclan@shoesstore.vn', '0908123456', '087198001111', 'Phường 4, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=300', NULL, 'local', '0', '0', 'staff', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('3', 'Trần Quang Huy', 'nv_quanghuy@shoesstore.vn', '0908234567', '087195002222', 'Phường 2, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=300', NULL, 'local', '0', '0', 'staff', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('4', 'Lê Thị Thu Hà', 'nv_thuha@shoesstore.vn', '0908345678', '087197003333', 'Phường 1, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=300', NULL, 'local', '0', '0', 'staff', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('5', 'Phạm Minh Đức', 'nv_minhduc@shoesstore.vn', '0908456789', '087199004444', 'Phường 8, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=300', NULL, 'local', '0', '0', 'staff', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('6', 'Hoàng Quốc Nam', 'nv_hoangnam@shoesstore.vn', '0908567890', '087196005555', 'Phường 3, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=300', NULL, 'local', '0', '0', 'staff', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('7', 'Nguyễn Quốc Thái', 'nguyenquocthai@gmail.com', '0913111222', '079099001122', 'Số 45 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('8', 'Trần Thanh Trúc', 'tranthanhtruc@gmail.com', '0913222333', '001198002233', 'Số 12 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('9', 'Lê Hoàng Nam', 'lehoangnam@gmail.com', '0913333444', '048096003344', 'Số 88 Bạch Đằng, Quận Hải Châu, Đà Nẵng', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('10', 'Phạm Thị Thúy', 'phamthithuy@gmail.com', '0913444555', '092197004455', 'Số 15 Đại Lộ Hòa Bình, Quận Ninh Kiều, Cần Thơ', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('11', 'Đặng Minh Khôi', 'dangminhkhoi@gmail.com', '0913555666', '087095005566', 'Số 99 Trưng Nữ Vương, Phường 1, TP. Vĩnh Long', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('12', 'Võ Thị Kiều Trang', 'vothikieutrang@gmail.com', '0913666777', '031198006677', 'Số 74 Lạch Tray, Quận Ngô Quyền, Hải Phòng', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('13', 'Bùi Tuấn Anh', 'buituananh@gmail.com', '0913777888', '074097007788', 'Số 23 Đại Lộ Bình Dương, TP. Thủ Dầu Một, Bình Dương', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('14', 'Nguyễn Thanh Huyền', 'nguyenthanhhuyen@gmail.com', '0913888999', '056199008899', 'Số 56 Trần Phú, TP. Nha Trang, Khánh Hòa', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('15', 'Đoàn Ngọc Phúc', 'doanngocphuc@gmail.com', '0913999000', '075095009900', 'Số 31 Đồng Khởi, TP. Biên Hòa, Đồng Nai', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27'),
('16', 'Hoàng Thị Mai', 'hoangthimai@gmail.com', '0913123789', '046197001234', 'Số 18 Lê Lợi, TP. Huế, Thừa Thiên Huế', '$2y$10$82UUVJv0l5AfiGw99Z/jxO5tn9E9bkitu47e3/cxl9PWoUj7UvMmq', NULL, 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?q=80&w=300', NULL, 'local', '0', '0', 'customer', '0.00', '0', '1', '2026-08-18 13:31:27', '2026-08-18 13:31:27');

-- ------------------------------------------------------------
-- Table structure for `vouchers`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) DEFAULT NULL,
  `sale_event_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `vouchers` (21 rows)
INSERT INTO `vouchers` VALUES
('1', NULL, NULL, 'CHAOBANMOI', 'Chào bạn mới - Giảm ngay 50K cho đơn từ 500K', 'fixed', '50000', '500000', '50000', '1000', '68', '1', 'new_user', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1'),
('2', NULL, NULL, 'FREESHIP', 'Miễn phí vận chuyển 35K toàn quốc đơn từ 300K', 'freeship', '35000', '300000', '35000', '2000', '142', '3', 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1'),
('3', NULL, NULL, 'VIPMEMBER', 'Tri ân VIP - Giảm 10% tối đa 500K', 'percent', '10', '2000000', '500000', '500', '89', '2', 'general', '2026-01-01 00:00:00', '2026-12-31 23:59:59', '1'),
('4', NULL, '1', 'SALE198', 'SALE QUỐC KHÁNH 19/8 – Giảm 150K cho đơn từ 1.5 triệu', 'fixed', '150000', '1500000', '150000', '500', '55', '1', 'holiday', '2026-08-19 00:00:00', '2026-08-19 23:59:59', '1'),
('5', NULL, '2', 'NGAYDOI11', 'Lì xì 1/1 - Giảm 15% tối đa 300K', 'percent', '15', '1200000', '300000', '500', '49', '1', 'holiday', '2026-01-01 00:00:00', '2026-01-05 23:59:59', '1'),
('6', NULL, '3', 'NGAYDOI22', 'Tết 2/2 - Giảm 80K đơn từ 1.0 triệu', 'fixed', '80000', '1000000', '80000', '500', '59', '1', 'holiday', '2026-02-02 00:00:00', '2026-02-06 23:59:59', '1'),
('7', NULL, '4', 'NGAYDOI33', 'Xuân 3/3 - Giảm 12% tối đa 250K', 'percent', '12', '1000000', '250000', '500', '25', '1', 'holiday', '2026-03-03 00:00:00', '2026-03-07 23:59:59', '1'),
('8', NULL, '5', 'NGAYDOI44', 'Thể thao 4/4 - Giảm 100K giày chạy bộ', 'fixed', '100000', '1500000', '100000', '500', '37', '1', 'holiday', '2026-04-04 00:00:00', '2026-04-08 23:59:59', '1'),
('9', NULL, '6', 'NGAYDOI55', 'Chào hè 5/5 - Giảm 50K dép & sandal', 'fixed', '50000', '600000', '50000', '500', '27', '1', 'holiday', '2026-05-05 00:00:00', '2026-05-09 23:59:59', '1'),
('10', NULL, '7', 'NGAYDOI66', 'Mid-Year 6/6 - Giảm 15% toàn shop', 'percent', '15', '1500000', '350000', '500', '19', '1', 'holiday', '2026-06-06 00:00:00', '2026-06-10 23:59:59', '1'),
('11', NULL, '8', 'NGAYDOI77', 'Sneaker 7/7 - Giảm 120K đơn từ 1.8 triệu', 'fixed', '120000', '1800000', '120000', '500', '59', '1', 'holiday', '2026-07-07 00:00:00', '2026-07-11 23:59:59', '1'),
('12', NULL, '9', 'NGAYDOI88', 'Bão giá 8/8 - Giảm 10% tối đa 400K', 'percent', '10', '1500000', '400000', '500', '37', '1', 'holiday', '2026-08-08 00:00:00', '2026-08-12 23:59:59', '1'),
('13', NULL, '10', 'NGAYDOI99', 'Thu sang 9/9 - Giảm 150K đơn từ 2.0 triệu', 'fixed', '150000', '2000000', '150000', '500', '48', '1', 'holiday', '2026-09-09 00:00:00', '2026-09-13 23:59:59', '1'),
('14', NULL, '11', 'NGAYDOI1010', 'Brand Day 10/10 - Giảm 18% tối đa 500K', 'percent', '18', '2000000', '500000', '500', '60', '1', 'holiday', '2026-10-10 00:00:00', '2026-10-14 23:59:59', '1'),
('15', NULL, '12', 'SIEU1111', 'Single Day 11/11 - Giảm 20% tối đa 600K', 'percent', '20', '2000000', '600000', '500', '48', '1', 'holiday', '2026-11-11 00:00:00', '2026-11-15 23:59:59', '1'),
('16', NULL, '13', 'XAKHO1212', 'Xả kho 12/12 - Giảm ngay 200K đơn từ 2.5 triệu', 'fixed', '200000', '2500000', '200000', '500', '32', '1', 'holiday', '2026-12-12 00:00:00', '2026-12-16 23:59:59', '1'),
('17', NULL, '1', 'QUOCKANH19', 'QUỐC KHÁNH 19/8 – Giảm 20% tối đa 400K', 'percent', '20', '800000', '400000', '300', '0', '2', 'holiday', '2026-08-19 00:00:00', '2026-08-19 23:59:59', '1'),
('18', NULL, '14', 'SALE188', 'SALE 18/8 – Giảm 100K cho đơn từ 1.2 triệu', 'fixed', '100000', '1200000', '100000', '400', '0', '2', 'general', '2026-08-18 00:00:00', '2026-08-18 23:59:59', '1'),
('19', NULL, '14', 'EARLYBIRD18', 'Early Bird 18/8 – Giảm 15% tối đa 300K', 'percent', '15', '600000', '300000', '200', '0', '1', 'general', '2026-08-18 00:00:00', '2026-08-18 23:59:59', '1'),
('20', NULL, '15', 'GIOVANG10', 'Giờ Vàng 20-22h – Giảm 10% (tối đa 200K)', 'percent', '10', '0', '200000', '1000', '0', '3', 'general', '2026-08-18 20:00:00', '2026-12-31 22:00:00', '1'),
('21', NULL, '15', 'FLASHDEAL', 'Flash Deal Giờ Vàng – Giảm 50K đơn từ 800K', 'fixed', '50000', '800000', '50000', '2000', '0', '5', 'general', '2026-08-18 20:00:00', '2026-12-31 22:00:00', '1');

-- ------------------------------------------------------------
-- Table structure for `wishlists`
-- ------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
