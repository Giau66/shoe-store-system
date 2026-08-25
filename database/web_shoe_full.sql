-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: web_shoe
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (1,'BỨT PHÁ PHONG CÁCH 2026','Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi giảm đến 33% hôm nay!','https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200','all-products.php','MUA SẮM NGAY','hero',1,1,'2026-08-04 11:50:22'),(2,'Bộ Sưu Tập Mùa Hè','Dép & Sandal nhẹ nhàng thoải mái cho mọi chuyến đi','https://images.unsplash.com/photo-1603487742131-4160ec999306?q=80&w=800','all-products.php?type=dep','Xem Ngay','promo_left',1,1,'2026-08-04 11:50:22'),(3,'Jordan Collection','Bộ sưu tập Air Jordan chính hãng dành cho tín đồ Sneaker','https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800','all-products.php?brand_id=3','Khám Phá','promo_right',1,1,'2026-08-04 11:50:22');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Nike','nike','https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/800px-Logo_NIKE.svg.png','Thương hiệu thể thao hàng đầu thế giới từ Mỹ, nổi tiếng với công nghệ Air và thiết kế iconic.',1,'2026-08-04 11:51:36'),(2,'Adidas','adidas','https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/800px-Adidas_Logo.svg.png','Thương hiệu thể thao Đức với 3 sọc huyền thoại, tiên phong trong công nghệ Boost & Primeknit.',1,'2026-08-04 11:51:36'),(3,'Jordan','jordan','https://upload.wikimedia.org/wikipedia/en/thumb/3/37/Jumpman_logo.svg/800px-Jumpman_logo.svg.png','Thương hiệu giày bóng rổ & thời trang đường phố huyền thoại mang tên Michael Jordan.',1,'2026-08-04 11:51:36'),(4,'Puma','puma','https://upload.wikimedia.org/wikipedia/en/thumb/d/da/Puma_complete_logo.svg/800px-Puma_complete_logo.svg.png','Thương hiệu thể thao Đức với phong cách trẻ trung, năng động và dòng da lộn kinh điển.',1,'2026-08-04 11:51:36'),(5,'New Balance','new-balance','https://upload.wikimedia.org/wikipedia/commons/thumb/e/ea/New_Balance_logo.svg/800px-New_Balance_logo.svg.png','Thương hiệu Mỹ nổi tiếng với sự thoải mái, đệm ABZORB & N-ergy, cùng phong cách Dad Shoes.',1,'2026-08-04 11:51:36'),(6,'Converse','converse','https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/Converse_logo.svg/800px-Converse_logo.svg.png','Biểu tượng văn hóa đường phố toàn cầu với dòng Chuck Taylor All Star huyền thoại.',1,'2026-08-04 11:51:36'),(7,'Vans','vans','https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Vans-logo.svg/800px-Vans-logo.svg.png','Thương hiệu trượt ván đường phố đình đám với slogan \"Off The Wall\" và sọc Jazz cá tính.',1,'2026-08-04 11:51:36'),(8,'Birkenstock','birkenstock','https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/Birkenstock_logo.svg/800px-Birkenstock_logo.svg.png','Thương hiệu dép Đức cao cấp hơn 240 năm lịch sử với đế cork định hình bàn chân siêu êm.',1,'2026-08-04 11:51:36'),(9,'Crocs','crocs','https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Crocs_logo.svg/800px-Crocs_logo.svg.png','Thương hiệu dép Clog siêu nhẹ toàn cầu từ Mỹ với chất liệu Croslite chống nước và kháng khuẩn.',1,'2026-08-04 11:51:36'),(10,'MLB Korea','mlb-korea','https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Major_League_Baseball_logo.svg/800px-Major_League_Baseball_logo.svg.png','Thương hiệu thời trang đường phố Hàn Quốc phong cách Chunky năng động lấy cảm hứng từ giải bóng chày Mỹ.',1,'2026-08-04 11:51:36'),(11,'Asics','asics','https://upload.wikimedia.org/wikipedia/commons/thumb/b/b1/Asics_Logo.svg/800px-Asics_Logo.svg.png','Thương hiệu thể thao Nhật Bản tiên phong công nghệ GEL đệm giảm chấn vượt trội.',1,'2026-08-04 11:51:36'),(12,'Skechers','skechers','https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Skechers_logo.svg/800px-Skechers_logo.svg.png','Thương hiệu giày Mỹ dẫn đầu về sự thoải mái với đệm Memory Foam & Arch Fit.',1,'2026-08-04 11:51:36'),(13,'Yeezy','yeezy','https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/Yeezy_logo.svg/800px-Yeezy_logo.svg.png','Dòng sản phẩm độc đáo mang tầm vóc biểu tượng tương lai hợp tác giữa Kanye West & Adidas.',1,'2026-08-04 11:51:36'),(14,'Salomon','salomon','https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Salomon_logo.svg/800px-Salomon_logo.svg.png','Thương hiệu Pháp hàng đầu về giày outdoor, trail running và thiết kế gorpcore đỉnh cao.',1,'2026-08-04 11:51:36'),(15,'On Running','on-running','https://upload.wikimedia.org/wikipedia/commons/thumb/a/a0/On_Running_logo.svg/800px-On_Running_logo.svg.png','Thương hiệu giày chạy bộ Thụy Sĩ đỉnh cao với công nghệ đế CloudTec êm như bước trên mây.',1,'2026-08-04 11:51:36');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Giày Nam','giay-nam',NULL,'giay','nam',1,1,'2026-08-04 11:51:36'),(2,NULL,'Giày Nữ','giay-nu',NULL,'giay','nu',2,1,'2026-08-04 11:51:36'),(3,NULL,'Dép Nam','dep-nam',NULL,'dep','nam',3,1,'2026-08-04 11:51:36'),(4,NULL,'Dép Nữ','dep-nu',NULL,'dep','nu',4,1,'2026-08-04 11:51:36'),(5,1,'Sneaker Nam','sneaker-nam',NULL,'giay','nam',1,1,'2026-08-04 11:51:36'),(6,1,'Giày Chạy Bộ Nam','giay-chay-bo-nam',NULL,'giay','nam',2,1,'2026-08-04 11:51:36'),(7,1,'Giày Bóng Rổ','giay-bong-ro',NULL,'giay','nam',3,1,'2026-08-04 11:51:36'),(8,1,'Giày Thời Trang Nam','giay-thoi-trang-nam',NULL,'giay','nam',4,1,'2026-08-04 11:51:36'),(9,2,'Sneaker Nữ','sneaker-nu',NULL,'giay','nu',1,1,'2026-08-04 11:51:36'),(10,2,'Giày Chạy Bộ Nữ','giay-chay-bo-nu',NULL,'giay','nu',2,1,'2026-08-04 11:51:36'),(11,2,'Giày Thời Trang Nữ','giay-thoi-trang-nu',NULL,'giay','nu',3,1,'2026-08-04 11:51:36'),(12,3,'Dép Quai Ngang Nam','dep-quai-ngang-nam',NULL,'dep','nam',1,1,'2026-08-04 11:51:36'),(13,3,'Sandal Nam','sandal-nam',NULL,'dep','nam',2,1,'2026-08-04 11:51:36'),(14,4,'Dép Quai Ngang Nữ','dep-quai-ngang-nu',NULL,'dep','nu',1,1,'2026-08-04 11:51:36'),(15,4,'Sandal Nữ','sandal-nu',NULL,'dep','nu',2,1,'2026-08-04 11:51:36');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (1,1,3,'Trần Văn Khách',5,'Giày cực đẹp, chất da mềm mại, rất vừa chân. Đi rất êm!',NULL,NULL,1,'2026-07-20 03:30:00'),(2,2,4,'Nguyễn Thị Lan',5,'Dunk Low Panda quá đẹp luôn, đúng hàng chính hãng. Giao hàng nhanh!',NULL,NULL,1,'2026-07-22 07:15:00'),(3,3,3,'Trần Văn Khách',4,'Samba OG rất đẹp, hơi cứng ban đầu nhưng đi vài ngày mềm ra.',NULL,NULL,1,'2026-07-25 02:45:00'),(4,12,4,'Nguyễn Thị Lan',5,'Jordan 1 Chicago huyền thoại! Đóng gói cẩn thận, box đẹp.',NULL,NULL,1,'2026-07-26 09:20:00'),(5,28,3,'Trần Văn Khách',5,'Birkenstock đi cực kỳ thoải mái, nhẹ và bền!',NULL,NULL,1,'2026-07-27 04:00:00');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,2,'Nhân Viên Bán Hàng','0907654321','staff@shoes.com','079200012345','456 Lê Đại Hành, Q11, TP.HCM','Nhân viên bán hàng','Ca 1 (08:00 - 16:00)',6000000,2.50,26,0,NULL,0,NULL,0,NULL,NULL,1,'2026-08-04 11:50:22');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,NULL,'Trang Chủ','index.php','fa-solid fa-house','main',1,1,'2026-08-04 11:50:22'),(2,NULL,'Sản Phẩm','all-products.php','fa-solid fa-shoe-prints','main',2,1,'2026-08-04 11:50:22'),(3,NULL,'Giảm Giá','all-products.php?discount=1','fa-solid fa-fire','main',3,1,'2026-08-04 11:50:22');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_details`
--

LOCK TABLES `order_details` WRITE;
/*!40000 ALTER TABLE `order_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_verifications`
--

DROP TABLE IF EXISTS `otp_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_verifications`
--

LOCK TABLES `otp_verifications` WRITE;
/*!40000 ALTER TABLE `otp_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png',1),(2,1,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/005e8105-ffad-4e50-94d3-e7f09f061266/AIR+FORCE+1+%2707.png',2),(3,1,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9a83eb9e-a0e2-41a2-9447-4a008c2a95c9/AIR+FORCE+1+%2707.png',3),(4,1,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/96d03d09-4081-4200-84cf-23579bcf3c95/AIR+FORCE+1+%2707.png',4),(5,2,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png',1),(6,2,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/a14704fb-2231-4a1d-a99f-bbd75605d8f6/NIKE+DUNK+LOW+RETRO.png',2),(7,2,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/34dfa8b1-3829-450f-bb08-8f5b40cf326e/NIKE+DUNK+LOW+RETRO.png',3),(8,2,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/75b81a7b-0d04-4530-9b4a-a3a8309b85c1/NIKE+DUNK+LOW+RETRO.png',4),(9,3,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7fce7de0e8984e84a447a8bf01187e1c_9366/Giay_Samba_OG_trang_B75806_01_standard.jpg',1),(10,3,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/6b763ec253454b52b217a8bf011894d8_9366/Giay_Samba_OG_trang_B75806_02_standard_hover.jpg',2),(11,3,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/815915d3fa78486ca9c2a8bf0118a803_9366/Giay_Samba_OG_trang_B75806_04_standard.jpg',3),(12,3,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/01f80ef307614d3ca976a8bf0118ca21_9366/Giay_Samba_OG_trang_B75806_41_detail.jpg',4),(13,4,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg',1),(14,4,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg',2),(15,5,'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',1),(16,5,'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',2),(17,6,'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',1),(18,6,'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',2),(19,7,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',1),(20,7,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',2),(21,8,'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$',1),(22,8,'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$',2),(23,9,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg',1),(24,9,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg',2),(25,10,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800',1),(26,10,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800',2),(27,11,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png',1),(28,11,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png',2),(29,12,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png',1),(30,12,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png',2),(31,13,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg',1),(32,13,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg',2),(33,14,'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',1),(34,14,'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',2),(35,15,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',1),(36,15,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',2),(37,16,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800',1),(38,16,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800',2),(39,17,'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800',1),(40,17,'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800',2),(41,18,'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800',1),(42,18,'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800',2),(43,19,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cd6b3ec7-e7d0-47c5-86c4-2e53cfe67ed7/AIR+JORDAN+1+RETRO+HIGH+OG.png',1),(44,19,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/032fcfc5-d72b-426c-85fa-7fcf1dd12781/AIR+JORDAN+1+RETRO+HIGH+OG.png',2),(45,19,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/7d363d66-ebbe-4835-9fa8-1f19fbb1c7a5/AIR+JORDAN+1+RETRO+HIGH+OG.png',3),(46,20,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e21943c5-47d6-4a50-8d7e-d7a45c4b42a2/JORDAN+4+RETRO.png',1),(47,20,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e21943c5-47d6-4a50-8d7e-d7a45c4b42a2/JORDAN+4+RETRO.png',2),(48,21,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png',1),(49,21,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png',2),(50,22,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png',1),(51,22,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png',2),(52,23,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg',1),(53,23,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg',2),(54,24,'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$',1),(55,24,'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$',2),(56,25,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers',1),(57,25,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers',2),(58,26,'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800',1),(59,26,'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800',2),(60,27,'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',1),(61,27,'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',2),(62,28,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png',1),(63,28,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png',2),(64,29,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3aed9c49f77f4c27b267af4c00e60e83_9366/Giay_Samba_OG_Hong_IG4199_01_standard.jpg',1),(65,29,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3aed9c49f77f4c27b267af4c00e60e83_9366/Giay_Samba_OG_Hong_IG4199_01_standard.jpg',2),(66,30,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg',1),(67,30,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg',2),(68,31,'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',1),(69,31,'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',2),(70,32,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',1),(71,32,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',2),(72,33,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers',1),(73,33,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers',2),(74,34,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800',1),(75,34,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800',2),(76,35,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png',1),(77,35,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png',2),(78,36,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg',1),(79,36,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg',2),(80,37,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800',1),(81,37,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800',2),(82,38,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',1),(83,38,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',2),(84,39,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',1),(85,39,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',2),(86,40,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg',1),(87,40,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg',2),(88,41,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg',1),(89,41,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg',2),(90,42,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg',1),(91,42,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg',2),(92,43,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg',1),(93,43,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg',2),(94,44,'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$',1),(95,44,'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$',2),(96,45,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers',1),(97,45,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers',2),(98,46,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png',1),(99,46,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png',2),(100,47,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg',1),(101,47,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg',2),(102,48,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides',1),(103,48,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides',2),(104,49,'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800',1),(105,49,'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800',2),(106,50,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg',1),(107,50,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg',2),(108,51,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',1),(109,51,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',2),(110,52,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png',1),(111,52,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png',2),(112,53,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg',1),(113,53,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg',2),(114,54,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg',1),(115,54,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg',2),(116,55,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',1),(117,55,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',2),(118,56,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg',1),(119,56,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg',2),(120,57,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png',1),(121,57,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png',2),(122,58,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg',1),(123,58,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg',2),(124,59,'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800',1),(125,59,'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800',2),(126,60,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg',1),(127,60,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg',2),(128,61,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',1),(129,61,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',2),(130,62,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg',1),(131,62,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg',2),(132,63,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg',1),(133,63,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg',2),(134,64,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',1),(135,64,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',2),(136,65,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png',1),(137,65,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png',2),(138,66,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',1),(139,66,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',2);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=409 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,'36','Trắng',19),(2,1,'37','Trắng',21),(3,1,'38','Trắng',9),(4,1,'39','Trắng',13),(5,1,'40','Trắng',13),(6,1,'41','Trắng',13),(7,1,'42','Trắng',16),(8,1,'43','Trắng',10),(9,2,'36','Phối Màu',7),(10,2,'37','Phối Màu',11),(11,2,'38','Phối Màu',5),(12,2,'39','Phối Màu',16),(13,2,'40','Phối Màu',17),(14,2,'41','Phối Màu',15),(15,2,'42','Phối Màu',19),(16,2,'43','Phối Màu',0),(17,3,'36','Trắng',12),(18,3,'37','Trắng',18),(19,3,'38','Trắng',6),(20,3,'39','Trắng',12),(21,3,'40','Trắng',16),(22,3,'41','Trắng',12),(23,3,'42','Trắng',15),(24,3,'43','Trắng',0),(25,4,'36','Trắng',23),(26,4,'37','Trắng',7),(27,4,'38','Trắng',15),(28,4,'39','Trắng',16),(29,4,'40','Trắng',14),(30,4,'41','Trắng',24),(31,4,'42','Trắng',23),(32,4,'43','Trắng',16),(33,5,'36','Xám',13),(34,5,'37','Xám',19),(35,5,'38','Xám',19),(36,5,'39','Xám',5),(37,5,'40','Xám',21),(38,5,'41','Xám',8),(39,5,'42','Xám',8),(40,5,'43','Xám',13),(41,6,'36','Trắng',23),(42,6,'37','Trắng',14),(43,6,'38','Trắng',5),(44,6,'39','Trắng',21),(45,6,'40','Trắng',10),(46,6,'41','Trắng',11),(47,6,'42','Trắng',17),(48,6,'43','Trắng',11),(49,7,'36','Phối Màu',11),(50,7,'37','Phối Màu',9),(51,7,'38','Phối Màu',20),(52,7,'39','Phối Màu',9),(53,7,'40','Phối Màu',13),(54,7,'41','Phối Màu',7),(55,7,'42','Phối Màu',10),(56,7,'43','Phối Màu',21),(57,8,'36','Trắng',11),(58,8,'37','Trắng',22),(59,8,'38','Trắng',6),(60,8,'39','Trắng',8),(61,8,'40','Trắng',22),(62,8,'41','Trắng',13),(63,8,'42','Trắng',20),(64,8,'43','Trắng',0),(65,9,'36','Phối Màu',18),(66,9,'37','Phối Màu',8),(67,9,'38','Phối Màu',16),(68,9,'39','Phối Màu',13),(69,9,'40','Phối Màu',21),(70,9,'41','Phối Màu',6),(71,9,'42','Phối Màu',5),(72,9,'43','Phối Màu',0),(73,10,'36','Xám',15),(74,10,'37','Xám',25),(75,10,'38','Xám',21),(76,10,'39','Xám',12),(77,10,'40','Xám',5),(78,10,'41','Xám',17),(79,10,'42','Xám',11),(80,10,'43','Xám',0),(81,11,'39','Phối Màu',16),(82,11,'40','Phối Màu',21),(83,11,'41','Phối Màu',21),(84,11,'42','Phối Màu',9),(85,11,'43','Phối Màu',12),(86,11,'44','Phối Màu',25),(87,12,'39','Trắng',6),(88,12,'40','Trắng',7),(89,12,'41','Trắng',22),(90,12,'42','Trắng',23),(91,12,'43','Trắng',23),(92,12,'44','Trắng',6),(93,13,'39','Đen',9),(94,13,'40','Đen',22),(95,13,'41','Đen',19),(96,13,'42','Đen',6),(97,13,'43','Đen',15),(98,13,'44','Đen',0),(99,14,'39','Phối Màu',24),(100,14,'40','Phối Màu',6),(101,14,'41','Phối Màu',8),(102,14,'42','Phối Màu',10),(103,14,'43','Phối Màu',23),(104,14,'44','Phối Màu',0),(105,15,'39','Phối Màu',22),(106,15,'40','Phối Màu',14),(107,15,'41','Phối Màu',6),(108,15,'42','Phối Màu',24),(109,15,'43','Phối Màu',21),(110,15,'44','Phối Màu',0),(111,16,'39','Đen',13),(112,16,'40','Đen',20),(113,16,'41','Đen',24),(114,16,'42','Đen',22),(115,16,'43','Đen',13),(116,16,'44','Đen',0),(117,17,'39','Đen',10),(118,17,'40','Đen',5),(119,17,'41','Đen',19),(120,17,'42','Đen',11),(121,17,'43','Đen',17),(122,17,'44','Đen',8),(123,18,'39','Đen',23),(124,18,'40','Đen',21),(125,18,'41','Đen',20),(126,18,'42','Đen',6),(127,18,'43','Đen',19),(128,18,'44','Đen',0),(129,19,'36','Phối Màu',20),(130,19,'37','Phối Màu',15),(131,19,'38','Phối Màu',10),(132,19,'39','Phối Màu',5),(133,19,'40','Phối Màu',6),(134,19,'41','Phối Màu',25),(135,19,'42','Phối Màu',10),(136,19,'43','Phối Màu',0),(137,20,'39','Đỏ',8),(138,20,'40','Đỏ',19),(139,20,'41','Đỏ',16),(140,20,'42','Đỏ',10),(141,20,'43','Đỏ',13),(142,20,'44','Đỏ',0),(143,21,'39','Phối Màu',8),(144,21,'40','Phối Màu',19),(145,21,'41','Phối Màu',15),(146,21,'42','Phối Màu',8),(147,21,'43','Phối Màu',5),(148,21,'44','Phối Màu',5),(149,22,'39','Trắng',6),(150,22,'40','Trắng',9),(151,22,'41','Trắng',23),(152,22,'42','Trắng',23),(153,22,'43','Trắng',19),(154,22,'44','Trắng',14),(155,23,'36','Đen',16),(156,23,'37','Đen',23),(157,23,'38','Đen',17),(158,23,'39','Đen',24),(159,23,'40','Đen',9),(160,23,'41','Đen',9),(161,23,'42','Đen',23),(162,23,'43','Đen',0),(163,24,'36','Trắng',18),(164,24,'37','Trắng',25),(165,24,'38','Trắng',6),(166,24,'39','Trắng',12),(167,24,'40','Trắng',16),(168,24,'41','Trắng',8),(169,24,'42','Trắng',21),(170,24,'43','Trắng',11),(171,25,'36','Đen',21),(172,25,'37','Đen',8),(173,25,'38','Đen',8),(174,25,'39','Đen',19),(175,25,'40','Đen',14),(176,25,'41','Đen',20),(177,25,'42','Đen',23),(178,25,'43','Đen',11),(179,26,'39','Phối Màu',25),(180,26,'40','Phối Màu',17),(181,26,'41','Phối Màu',6),(182,26,'42','Phối Màu',14),(183,26,'43','Phối Màu',8),(184,26,'44','Phối Màu',19),(185,27,'39','Bạc',21),(186,27,'40','Bạc',21),(187,27,'41','Bạc',6),(188,27,'42','Bạc',5),(189,27,'43','Bạc',12),(190,27,'44','Bạc',19),(191,28,'36','Trắng',15),(192,28,'37','Trắng',20),(193,28,'38','Trắng',9),(194,28,'39','Trắng',20),(195,28,'40','Trắng',0),(196,29,'36','Phối Màu',6),(197,29,'37','Phối Màu',20),(198,29,'38','Phối Màu',6),(199,29,'39','Phối Màu',22),(200,29,'40','Phối Màu',0),(201,30,'36','Đen',7),(202,30,'37','Đen',13),(203,30,'38','Đen',16),(204,30,'39','Đen',9),(205,30,'40','Đen',0),(206,31,'36','Bạc',13),(207,31,'37','Bạc',7),(208,31,'38','Bạc',5),(209,31,'39','Bạc',13),(210,31,'40','Bạc',6),(211,32,'36','Trắng',15),(212,32,'37','Trắng',16),(213,32,'38','Trắng',15),(214,32,'39','Trắng',18),(215,32,'40','Trắng',16),(216,33,'36','Trắng',9),(217,33,'37','Trắng',6),(218,33,'38','Trắng',17),(219,33,'39','Trắng',7),(220,33,'40','Trắng',0),(221,34,'36','Bạc',25),(222,34,'37','Bạc',18),(223,34,'38','Bạc',18),(224,34,'39','Bạc',19),(225,34,'40','Bạc',0),(226,35,'36','Hồng',19),(227,35,'37','Hồng',5),(228,35,'38','Hồng',20),(229,35,'39','Hồng',6),(230,35,'40','Hồng',13),(231,36,'36','Trắng',10),(232,36,'37','Trắng',19),(233,36,'38','Trắng',17),(234,36,'39','Trắng',25),(235,36,'40','Trắng',0),(236,37,'36','Trắng',12),(237,37,'37','Trắng',5),(238,37,'38','Trắng',7),(239,37,'39','Trắng',10),(240,37,'40','Trắng',14),(241,38,'36','Phối Màu',17),(242,38,'37','Phối Màu',24),(243,38,'38','Phối Màu',8),(244,38,'39','Phối Màu',10),(245,38,'40','Phối Màu',0),(246,39,'36','Phối Màu',10),(247,39,'37','Phối Màu',19),(248,39,'38','Phối Màu',6),(249,39,'39','Phối Màu',9),(250,39,'40','Phối Màu',0),(251,40,'36','Đen',15),(252,40,'37','Đen',24),(253,40,'38','Đen',21),(254,40,'39','Đen',14),(255,40,'40','Đen',14),(256,41,'36','Phối Màu',19),(257,41,'37','Phối Màu',21),(258,41,'38','Phối Màu',11),(259,41,'39','Phối Màu',22),(260,41,'40','Phối Màu',9),(261,42,'36','Xanh Lá',11),(262,42,'37','Xanh Lá',20),(263,42,'38','Xanh Lá',23),(264,42,'39','Xanh Lá',25),(265,42,'40','Xanh Lá',10),(266,43,'36','Xanh',10),(267,43,'37','Xanh',6),(268,43,'38','Xanh',5),(269,43,'39','Xanh',23),(270,43,'40','Xanh',16),(271,44,'36','Đỏ',17),(272,44,'37','Đỏ',25),(273,44,'38','Đỏ',15),(274,44,'39','Đỏ',22),(275,44,'40','Đỏ',14),(276,45,'36','Trắng',21),(277,45,'37','Trắng',16),(278,45,'38','Trắng',10),(279,45,'39','Trắng',19),(280,45,'40','Trắng',0),(281,46,'39','Đen',9),(282,46,'40','Đen',25),(283,46,'41','Đen',15),(284,46,'42','Đen',22),(285,46,'43','Đen',21),(286,46,'44','Đen',0),(287,47,'39','Phối Màu',21),(288,47,'40','Phối Màu',10),(289,47,'41','Phối Màu',22),(290,47,'42','Phối Màu',9),(291,47,'43','Phối Màu',13),(292,47,'44','Phối Màu',8),(293,48,'39','Đen',23),(294,48,'40','Đen',15),(295,48,'41','Đen',6),(296,48,'42','Đen',7),(297,48,'43','Đen',5),(298,48,'44','Đen',18),(299,49,'39','Đen',6),(300,49,'40','Đen',22),(301,49,'41','Đen',24),(302,49,'42','Đen',13),(303,49,'43','Đen',8),(304,49,'44','Đen',0),(305,50,'36','Trắng',12),(306,50,'37','Trắng',22),(307,50,'38','Trắng',11),(308,50,'39','Trắng',15),(309,50,'40','Trắng',23),(310,50,'41','Trắng',20),(311,50,'42','Trắng',7),(312,50,'43','Trắng',7),(313,51,'39','Phối Màu',12),(314,51,'40','Phối Màu',24),(315,51,'41','Phối Màu',21),(316,51,'42','Phối Màu',22),(317,51,'43','Phối Màu',12),(318,51,'44','Phối Màu',0),(319,52,'39','Đen',8),(320,52,'40','Đen',23),(321,52,'41','Đen',20),(322,52,'42','Đen',6),(323,52,'43','Đen',12),(324,52,'44','Đen',8),(325,53,'36','Đen',14),(326,53,'37','Đen',13),(327,53,'38','Đen',8),(328,53,'39','Đen',21),(329,53,'40','Đen',18),(330,53,'41','Đen',11),(331,53,'42','Đen',10),(332,53,'43','Đen',12),(333,54,'36','Nâu Tây',17),(334,54,'37','Nâu Tây',24),(335,54,'38','Nâu Tây',6),(336,54,'39','Nâu Tây',10),(337,54,'40','Nâu Tây',5),(338,54,'41','Nâu Tây',6),(339,54,'42','Nâu Tây',23),(340,54,'43','Nâu Tây',12),(341,55,'39','Đen',7),(342,55,'40','Đen',7),(343,55,'41','Đen',21),(344,55,'42','Đen',14),(345,55,'43','Đen',14),(346,55,'44','Đen',0),(347,56,'39','Phối Màu',25),(348,56,'40','Phối Màu',5),(349,56,'41','Phối Màu',5),(350,56,'42','Phối Màu',9),(351,56,'43','Phối Màu',16),(352,56,'44','Phối Màu',0),(353,57,'36','Phối Màu',22),(354,57,'37','Phối Màu',18),(355,57,'38','Phối Màu',25),(356,57,'39','Phối Màu',8),(357,57,'40','Phối Màu',12),(358,58,'36','Phối Màu',18),(359,58,'37','Phối Màu',9),(360,58,'38','Phối Màu',21),(361,58,'39','Phối Màu',17),(362,58,'40','Phối Màu',0),(363,59,'36','Trắng',16),(364,59,'37','Trắng',6),(365,59,'38','Trắng',25),(366,59,'39','Trắng',22),(367,59,'40','Trắng',25),(368,59,'41','Trắng',20),(369,59,'42','Trắng',24),(370,59,'43','Trắng',15),(371,60,'36','Phối Màu',16),(372,60,'37','Phối Màu',6),(373,60,'38','Phối Màu',15),(374,60,'39','Phối Màu',13),(375,60,'40','Phối Màu',8),(376,60,'41','Phối Màu',13),(377,60,'42','Phối Màu',17),(378,60,'43','Phối Màu',17),(379,61,'36','Phối Màu',9),(380,61,'37','Phối Màu',23),(381,61,'38','Phối Màu',9),(382,61,'39','Phối Màu',19),(383,61,'40','Phối Màu',9),(384,62,'36','Trắng',23),(385,62,'37','Trắng',10),(386,62,'38','Trắng',7),(387,62,'39','Trắng',20),(388,62,'40','Trắng',19),(389,63,'36','Phối Màu',17),(390,63,'37','Phối Màu',19),(391,63,'38','Phối Màu',10),(392,63,'39','Phối Màu',17),(393,63,'40','Phối Màu',0),(394,64,'36','Phối Màu',15),(395,64,'37','Phối Màu',13),(396,64,'38','Phối Màu',10),(397,64,'39','Phối Màu',13),(398,64,'40','Phối Màu',0),(399,65,'36','Phối Màu',10),(400,65,'37','Phối Màu',14),(401,65,'38','Phối Màu',23),(402,65,'39','Phối Màu',24),(403,65,'40','Phối Màu',8),(404,66,'36','Phối Màu',15),(405,66,'37','Phối Màu',18),(406,66,'38','Phối Màu',16),(407,66,'39','Phối Màu',5),(408,66,'40','Phối Màu',0);
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'NK-AF1-WHT','Nike Air Force 1 \'07 White','nike-air-force-1-07-white',5,1,'Unisex',2929000,3500000,1800000,16,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png','Đôi giày huyền thoại Nike Air Force 1 \'07 với chất liệu da thật cao cấp, đệm Air êm ái, thích hợp phối mọi trang phục streetwear.',1,0,1450,420,1,'2026-08-04 11:51:36'),(2,'NK-DUNK-PANDA','Nike Dunk Low Retro Panda','nike-dunk-low-retro-panda',5,1,'Unisex',3100000,3600000,1900000,14,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png','Phối màu Panda đen trắng kinh điển của dòng Nike Dunk Low, lựa chọn cực kỳ thời thượng và dễ phối đồ nhất hiện nay.',1,0,2300,580,1,'2026-08-04 11:51:36'),(3,'AD-SAMBA-OG','Adidas Samba OG White Black','adidas-samba-og-white-black',5,2,'Unisex',2700000,3100000,1600000,13,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7fce7de0e8984e84a447a8bf01187e1c_9366/Giay_Samba_OG_trang_B75806_01_standard.jpg','Adidas Samba OG phong cách Retro chưa bao giờ giảm sức hút, chất da thật mềm mại kết hợp đế cao su bám đường tuyệt vời.',1,1,3800,920,1,'2026-08-04 11:51:36'),(4,'AD-SUPERSTAR','Adidas Superstar Cloud White','adidas-superstar-cloud-white',5,2,'Unisex',2500000,2900000,1500000,14,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg','Biểu tượng mũi sò Shell-Toe vượt thời gian của Adidas Superstar, chất liệu da cổ điển cùng 3 sọc đen nổi bật.',0,0,1050,310,1,'2026-08-04 11:51:36'),(5,'NB-574-GRY','New Balance 574 Classic Grey','new-balance-574-classic-grey',5,5,'Unisex',2650000,2900000,1600000,9,'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880','Dòng New Balance 574 huyền thoại phối màu Xám Classic, tích hợp bộ đệm ENCAP hỗ trợ tối đa cho việc đi lại cả ngày.',0,1,720,195,1,'2026-08-04 11:51:36'),(6,'NB-550-WHT','New Balance 550 White Grey','new-balance-550-white-grey',5,5,'Unisex',3250000,3800000,2000000,14,'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880','New Balance 550 với nguồn gốc từ giày bóng rổ thập niên 80, thiết kế Retro bứt phá cực hot trên toàn thế giới.',1,1,1600,390,1,'2026-08-04 11:51:36'),(7,'MLB-CHUNKY-BOS','MLB Korea Big Ball Chunky A Boston','mlb-korea-big-ball-chunky-a-boston',5,10,'Unisex',2850000,3300000,1700000,14,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800','Sneaker MLB Chunky đế cao 6cm tôn dáng đỉnh cao, in logo đội bóng chày Boston Red Sox thời thượng.',1,0,1850,460,1,'2026-08-04 11:51:36'),(8,'VN-KNU-SKOOL','Vans Knu Skool Black White','vans-knu-skool-black-white',5,7,'Unisex',2200000,2600000,1300000,15,'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$','Vans Knu Skool với lưỡi gà mập phồng độc đáo phong cách 90s Y2K, sọc Sidestripe 3D nổi bật.',0,1,1200,280,1,'2026-08-04 11:51:36'),(9,'YZY-350-ONYX','Yeezy Boost 350 V2 Onyx','yeezy-boost-350-v2-onyx',5,13,'Unisex',6200000,7000000,4200000,11,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg','Yeezy Boost 350 V2 Onyx phủ sắc đen quyến rũ, chất liệu vải dệt Primeknit mượt mà cùng đế đệm Boost êm vượt trội.',1,0,2900,510,1,'2026-08-04 11:51:36'),(10,'AS-GEL-NYC','Asics Gel-NYC Cream Oyster Grey','asics-gel-nyc-cream-oyster-grey',5,11,'Unisex',3600000,4200000,2200000,14,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800','Giày Asics Gel-NYC phong cách Techwear & Dad Shoe kết hợp cấu trúc GEL và đệm Solyte êm ái hàng đầu Nhật Bản.',1,1,1400,320,1,'2026-08-04 11:51:36'),(11,'NK-PEGASUS41','Nike Air Zoom Pegasus 41','nike-air-zoom-pegasus-41',6,1,'Nam',3600000,4200000,2200000,14,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png','Dòng giày chạy bộ quốc dân Nike Pegasus 41 trang bị đệm bọt ReactX kết hợp Air Zoom phản hồi lực siêu nhạy.',1,1,980,220,1,'2026-08-04 11:51:36'),(12,'NK-INVINCIBLE3','Nike ZoomX Invincible 3 White','nike-zoomx-invincible-3-white',6,1,'Nam',4800000,5500000,3000000,13,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png','Siêu phẩm chạy bộ Nike Invincible 3 với đệm ZoomX dày tối đa giúp bảo vệ khớp gối và hồi phục năng lượng tuyệt vời.',1,0,1350,290,1,'2026-08-04 11:51:36'),(13,'AD-ULTRABOOST','Adidas Ultraboost Light Black','adidas-ultraboost-light-black',6,2,'Nam',3800000,4500000,2300000,16,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg','Adidas Ultraboost Light với đệm Light Boost nhẹ hơn 30% so với thế hệ trước, trải nghiệm chạy êm mượt vô hạn.',1,1,890,195,1,'2026-08-04 11:51:36'),(14,'NB-FUELCELL','New Balance FuelCell Propel v4','new-balance-fuelcell-propel-v4',6,5,'Nam',2900000,3400000,1800000,15,'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880','New Balance FuelCell Propel v4 tích hợp tấm TPU giữa đế giúp bật nảy đà tốt cho cự ly chạy từ 5km đến Marathon.',0,1,410,95,1,'2026-08-04 11:51:36'),(15,'AS-KAYANO14','Asics Gel-Kayano 14 Metallic Plum','asics-gel-kayano-14-metallic-plum',6,11,'Nam',4200000,4800000,2600000,13,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800','Mẫu chạy bộ đỉnh cao Asics Gel-Kayano 14 mang tính biểu tượng những năm 2000, êm ái và ổn định bàn chân tuyệt đối.',1,1,1650,340,1,'2026-08-04 11:51:36'),(16,'ON-MONSTER2','On Running Cloudmonster 2 Black','on-running-cloudmonster-2-black',6,15,'Nam',4950000,5600000,3100000,12,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800','On Running Cloudmonster 2 sở hữu các đệm mây CloudTec khổng lồ mang đến khả năng đệm tối đa và năng lượng bùng nổ.',1,1,1100,240,1,'2026-08-04 11:51:36'),(17,'SLM-XT6-GTX','Salomon XT-6 Gore-Tex Black','salomon-xt-6-gore-tex-black',6,14,'Nam',5400000,6200000,3500000,13,'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800','Salomon XT-6 Gore-Tex chống nước tuyệt đối, công nghệ dây buộc Quicklace tiện lợi cùng đệm ACS nâng đỡ vượt địa hình.',1,1,1890,410,1,'2026-08-04 11:51:36'),(18,'SK-GOWALK6','Skechers Go Walk 6 Black Navy','skechers-go-walk-6-black-navy',6,12,'Nam',1950000,2300000,1100000,15,'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800','Skechers Go Walk 6 tích hợp đệm ULTRA GO siêu nhẹ và công nghệ đệm Air Cooled Goga Mat cực kỳ êm ái cho đôi chân.',0,1,530,140,1,'2026-08-04 11:51:36'),(19,'JD-1-CHICAGO','Air Jordan 1 Retro High OG Chicago','air-jordan-1-retro-high-og-chicago',7,3,'Unisex',5200000,6000000,3200000,13,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cd6b3ec7-e7d0-47c5-86c4-2e53cfe67ed7/AIR+JORDAN+1+RETRO+HIGH+OG.png','Phối màu Chicago đỏ trắng đen huyền thoại của Air Jordan 1, biểu tượng số 1 trong thế giới Sneaker & Bóng rổ.',1,0,4600,890,1,'2026-08-04 11:51:36'),(20,'JD-4-BRED','Air Jordan 4 Retro Bred Reimagined','air-jordan-4-retro-bred-reimagined',7,3,'Nam',5800000,6500000,3500000,11,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e21943c5-47d6-4a50-8d7e-d7a45c4b42a2/JORDAN+4+RETRO.png','Air Jordan 4 Bred Reimagined bằng chất da thật cao cấp láng mịn, form dáng thể thao chuẩn mực cực kỳ đẳng cấp.',1,1,3400,620,1,'2026-08-04 11:51:36'),(21,'NK-LEBRON21','Nike LeBron 21 Akoya','nike-lebron-21-akoya',7,1,'Nam',5500000,6200000,3300000,11,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png','Giày bóng rổ Nike LeBron 21 trang bị hệ thống đệm Air Zoom kẹp giữa bọt Cushlon 2.0 tối ưu cú bật nhảy và tiếp đất.',0,1,650,130,1,'2026-08-04 11:51:36'),(22,'NK-GTCUT3','Nike GT Cut 3 Summit White','nike-gt-cut-3-summit-white',7,1,'Nam',4600000,5200000,2800000,12,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png','Dòng giày bóng rổ tốc độ Nike GT Cut 3 tích hợp bọt ZoomX đầu tiên trên sân bóng rổ, giúp xoay đổi hướng tức thì.',1,1,820,180,1,'2026-08-04 11:51:36'),(23,'CV-CHUCK70-BLK','Converse Chuck 70 High Black','converse-chuck-70-high-black',8,6,'Unisex',2000000,2300000,1200000,13,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg','Converse Chuck 70 cổ cao chất vải Canvas 12oz dày dặn, đệm lót OrthoLite êm ái cùng đường chỉ khâu Vintage.',0,0,890,280,1,'2026-08-04 11:51:36'),(24,'VN-OLD-SKOOL','Vans Old Skool Black White','vans-old-skool-black-white',8,7,'Unisex',1800000,2100000,1000000,14,'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$','Vans Old Skool với đường sọc trắng Jazz kinh điển, da lộn phối vải canvas bền bỉ cho giới trẻ năng động.',0,1,1100,360,1,'2026-08-04 11:51:36'),(25,'PM-SUEDE-BLK','Puma Suede Classic XXI Black','puma-suede-classic-xxi-black',8,4,'Unisex',2100000,2400000,1300000,13,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers','Puma Suede Classic da lộn mịn đẹp, kiểu dáng retro tối giản chuẩn phong cách hip-hop từ thập niên 80.',0,0,520,150,1,'2026-08-04 11:51:36'),(26,'NB-2002R-RAIN','New Balance 2002R Protection Pack Rain Cloud','new-balance-2002r-protection-pack-rain-cloud',8,5,'Nam',4500000,5200000,2800000,13,'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800','Siêu phẩm New Balance 2002R thiết kế dải da rách đan lớp Protection Pack cá tính, đế N-ergy giảm xóc vượt trội.',1,1,2400,580,1,'2026-08-04 11:51:36'),(27,'NB-1906R-SLV','New Balance 1906R Metallic Silver','new-balance-1906r-metallic-silver',8,5,'Nam',3850000,4400000,2400000,13,'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880','New Balance 1906R mang đậm tinh thần retro-futuristic những năm 2000, bộ đệm N-ergy kết hợp đệm gót ABZORB.',1,1,1750,410,1,'2026-08-04 11:51:36'),(28,'NK-AF1-WMNS','Nike Air Force 1 \'07 Women White','nike-air-force-1-07-women-white',9,1,'Nữ',2929000,3500000,1800000,16,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png','Nike Air Force 1 phiên bản dành riêng cho nữ với thiết kế thanh thoát, màu trắng tinh khôi cực kỳ dễ phối đồ.',1,0,1950,490,1,'2026-08-04 11:51:36'),(29,'AD-SAMBA-ROSE','Adidas Samba OG Women Rose','adidas-samba-og-women-rose',9,2,'Nữ',2800000,3200000,1700000,13,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3aed9c49f77f4c27b267af4c00e60e83_9366/Giay_Samba_OG_Hong_IG4199_01_standard.jpg','Adidas Samba OG phối màu hồng pastel nữ tính kết hợp cùng sọc da trắng ngọt ngào, hot trend thời trang phái đẹp.',1,1,2600,610,1,'2026-08-04 11:51:36'),(30,'AD-CAMPUS-00S','Adidas Campus 00s Core Black Women','adidas-campus-00s-core-black-women',9,2,'Nữ',2600000,3000000,1600000,13,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg','Adidas Campus 00s phom dáng béo mập skate độc đáo, dây giày bản to thời thượng tạo dấu ấn riêng cho các bạn nữ.',1,1,1450,330,1,'2026-08-04 11:51:36'),(31,'NB-530-SLV','New Balance 530 Metallic Silver','new-balance-530-metallic-silver',9,5,'Nữ',2650000,2900000,1600000,9,'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880','New Balance 530 Metallic Silver mang đến vẻ đẹp hoài cổ năng động, chất liệu lưới thoáng khí đệm ABZORB siêu nhẹ.',0,1,780,210,1,'2026-08-04 11:51:36'),(32,'MLB-LINER-WHT','MLB Korea Chunky Liner Mid White Navy','mlb-korea-chunky-liner-mid-white-navy',9,10,'Nữ',3200000,3700000,2000000,14,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800','MLB Chunky Liner thiết kế đường viền hiện đại, tôn dáng cao ráo cho các quý cô cá tính.',1,1,1250,290,1,'2026-08-04 11:51:36'),(33,'PM-PALERMO-PNK','Puma Palermo Leather Pink White','puma-palermo-leather-pink-white',9,4,'Nữ',2250000,2600000,1350000,13,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers','Puma Palermo phối màu hồng pastel ngọt ngào, chất da cao cấp sang trọng cùng đế gum cá tính.',0,1,560,160,1,'2026-08-04 11:51:36'),(34,'AS-GT2160-SLV','Asics GT-2160 Cream Pure Silver','asics-gt-2160-cream-pure-silver',9,11,'Nữ',3300000,3800000,2000000,13,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800','Asics GT-2160 tone màu kem bạc thời thượng, cấu trúc GEL lót đệm siêu êm ái khi di chuyển liên tục.',1,1,980,240,1,'2026-08-04 11:51:36'),(35,'NK-AIRMAX90-PNK','Nike Air Max 90 Futura Pink','nike-air-max-90-futura-pink',10,1,'Nữ',3200000,3800000,1900000,16,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png','Air Max 90 Futura biến tấu hiện đại của dòng Air Max 90 kinh điển với sắc hồng ngọt ngào và đệm Air êm ái.',1,1,1150,270,1,'2026-08-04 11:51:36'),(36,'AD-ULTRABOOST-W','Adidas Ultraboost Light Women White','adidas-ultraboost-light-women-white',10,2,'Nữ',3600000,4300000,2200000,16,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg','Giày chạy bộ phái đẹp Ultraboost Light cực kỳ mượt mà, ôm chân chuẩn xác và hỗ trợ vận động tối ưu.',0,1,520,130,1,'2026-08-04 11:51:36'),(37,'AS-KAYANO30-W','Asics Gel-Kayano 30 Women White','asics-gel-kayano-30-women-white',10,11,'Nữ',4100000,4700000,2500000,13,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800','Asics Gel-Kayano 30 tích hợp hệ thống 4D GUIDANCE SYSTEM bảo vệ bàn chân chống lật cổ chân khi tập luyện.',1,1,890,210,1,'2026-08-04 11:51:36'),(38,'ON-TILT-W','On Running Cloudtilt Women Quartz','on-running-cloudtilt-women-quartz',10,15,'Nữ',4500000,5100000,2800000,12,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800','On Running Cloudtilt với thiết kế siêu nhẹ, xỏ chân nhanh chóng không cần buộc dây, cảm giác êm ái tuyệt vời.',1,1,740,180,1,'2026-08-04 11:51:36'),(39,'SK-DLITES-WHT','Skechers D\'Lites Fresh Start','skechers-dlites-fresh-start',10,12,'Nữ',1850000,2200000,1000000,16,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800','Skechers D\'Lites Chunky năng động với lót giày Air-Cooled Memory Foam siêu êm, giảm áp lực tối đa cho bàn chân.',0,1,620,160,1,'2026-08-04 11:51:36'),(40,'CV-RUNSTAR-W','Converse Run Star Hike High Black','converse-run-star-hike-high-black',11,6,'Nữ',2600000,3000000,1500000,13,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg','Converse Run Star Hike đế ziczac cực ngầu, giúp hack chiều cao 5cm hiệu quả cho các cô nàng cá tính.',0,1,680,190,1,'2026-08-04 11:51:36'),(41,'CV-ALLSTAR-MOVE','Converse Chuck Taylor All Star Move Platform','converse-chuck-taylor-all-star-move-platform',11,6,'Nữ',2100000,2400000,1200000,13,'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg','Dòng All Star Move siêu nhẹ với đế bánh mì nâng chiều cao uyển chuyển, năng động năng suất cả ngày dài.',0,1,840,220,1,'2026-08-04 11:51:36'),(42,'AD-GAZELLE-GRN','Adidas Gazelle Bold Green Women','adidas-gazelle-bold-green-women',11,2,'Nữ',2200000,3200000,1300000,31,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg','Gazelle Bold thiết kế 3 tầng đế cao cá tính, tông xanh lá lục bảo retro cực kỳ thời trang và nổi bật.',1,1,2100,520,1,'2026-08-04 11:51:36'),(43,'AD-SPEZIAL-BLU','Adidas Handball Spezial Blue Women','adidas-handball-spezial-blue-women',11,2,'Nữ',2750000,3200000,1600000,14,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg','Adidas Handball Spezial chất da lộn xanh denim cổ điển, biểu tượng thời trang Terracewear mốt nhất hiện nay.',1,1,1850,450,1,'2026-08-04 11:51:36'),(44,'VN-AUTHENTIC-RED','Vans Authentic Core Classics Red','vans-authentic-core-classics-red',11,7,'Nữ',1450000,1700000,800000,15,'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$','Vans Authentic sắc đỏ rực rỡ, phom dáng cổ thấp tối giản tinh tế dễ dàng mix&match trang phục dạo phố.',0,0,480,130,1,'2026-08-04 11:51:36'),(45,'PM-RSX-EFEKT','Puma RS-X Efekt Archive White','puma-rs-x-efekt-archive-white',11,4,'Nữ',2700000,3200000,1600000,16,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers','Puma RS-X Efekt dòng Chunky thiết kế tương lai với các mảng phối da ấn tượng cùng đế đệm Running System.',0,1,620,170,1,'2026-08-04 11:51:36'),(46,'NK-BENASSI','Nike Benassi JDI Slide Black','nike-benassi-jdi-slide-black',12,1,'Nam',790000,950000,450000,17,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png','Dép quai ngang Nike Benassi JDI quai lót bông siêu mềm, đế xốp Phylon êm ái chống trơn trượt.',0,0,520,240,1,'2026-08-04 11:51:36'),(47,'AD-ADILETTE','Adidas Adilette Comfort Slide','adidas-adilette-comfort-slide',12,2,'Nam',850000,1000000,480000,15,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg','Dép Adidas Adilette Comfort trang bị lót lòng đệm Cloudfoam cực êm như mát xa lòng bàn chân.',0,1,430,190,1,'2026-08-04 11:51:36'),(48,'PM-LEADCAT','Puma Leadcat 2.0 Slide Black','puma-leadcat-2-slide-black',12,4,'Nam',650000,800000,380000,19,'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides','Dép quai ngang Puma Leadcat 2.0 phom dáng thể thao tối giản, chất liệu EVA cao cấp siêu nhẹ.',0,0,280,110,1,'2026-08-04 11:51:36'),(49,'CRC-MELLOW-SLD','Crocs Mellow Recovery Slide Black','crocs-mellow-recovery-slide-black',12,9,'Nam',1350000,1600000,800000,16,'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800','Dép Crocs Mellow dòng đệm nhung LiteRide nhún êm sâu giúp đôi chân thư giãn tức thì sau giờ tập thể thao.',1,1,890,230,1,'2026-08-04 11:51:36'),(50,'YZY-SLIDE-BONE','Yeezy Slide Bone White','yeezy-slide-bone-white',12,13,'Unisex',3200000,3800000,2000000,16,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg','Yeezy Slide Bone đúc nguyên khối bọt EVA mềm mại, thiết kế răng cưa tối giản hiện đại nhất giới thời trang.',1,0,3100,720,1,'2026-08-04 11:51:36'),(51,'MLB-SLIDER-MONO','MLB Korea Chunky Slider Monogram','mlb-korea-chunky-slider-monogram',12,10,'Nam',1650000,1950000,950000,15,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800','Dép quai ngang MLB Korea hoa văn Monogram cao cấp, đế cao 4cm tôn dáng và thời trang đỉnh cao.',1,1,1150,280,1,'2026-08-04 11:51:36'),(52,'NK-CANYON','Nike Canyon Sandal Black','nike-canyon-sandal-black',13,1,'Nam',1950000,2300000,1100000,15,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png','Sandal dã ngoại Nike Canyon quai dán linh hoạt, đế gai hãm ma sát cao thích hợp mọi hoạt động outdoor năng động.',0,1,310,85,1,'2026-08-04 11:51:36'),(53,'BK-ARIZONA-BLK','Birkenstock Arizona EVA Black','birkenstock-arizona-eva-black',13,8,'Unisex',1250000,1500000,750000,17,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg','Birkenstock Arizona EVA 2 quai màu đen đúc nguyên khối chống nước 100%, đệm chân Ergonomic uốn lượn thoải mái.',1,1,950,260,1,'2026-08-04 11:51:36'),(54,'BK-BOSTON-TAUPE','Birkenstock Boston Clog Suede Taupe','birkenstock-boston-clog-suede-taupe',13,8,'Unisex',3800000,4400000,2400000,14,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg','Birkenstock Boston Clog bọc da lộn Taupe sang trọng, đế bần Cork tự nhiên chuẩn mực phong cách Quiet Luxury.',1,1,2100,490,1,'2026-08-04 11:51:36'),(55,'CRC-ECHO-BLK','Crocs Echo Clog All Black','crocs-echo-clog-all-black',13,9,'Nam',1950000,2300000,1150000,15,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800','Crocs Echo Clog phong cách Streetwear gai góc cá tính, quai đeo gót êm ái cùng đệm Licker-in LiteRide.',1,1,1420,330,1,'2026-08-04 11:51:36'),(56,'AD-HYDROTERRA','Adidas Terrex Hydroterra Sandal','adidas-terrex-hydroterra-sandal',13,2,'Nam',1800000,2200000,1000000,18,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg','Sandal dã ngoại Adidas Terrex đế cao su Traxion siêu bám đường ướt, chất liệu dây đai tái chế bảo vệ môi trường.',0,1,410,110,1,'2026-08-04 11:51:36'),(57,'NK-VICTORI-W','Nike Victori One Slide Women','nike-victori-one-slide-women',14,1,'Nữ',750000,900000,420000,17,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png','Dép quai ngang nữ Nike Victori One lót bọt đệm êm mềm mới, quai quấn ôm sát mu bàn chân tạo sự thoải mái dịu nhẹ.',0,1,450,190,1,'2026-08-04 11:51:36'),(58,'AD-ADILETTE-W','Adidas Adilette Aqua Slide Women','adidas-adilette-aqua-slide-women',14,2,'Nữ',650000,800000,380000,19,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg','Dép đúc Adidas Adilette Aqua nhanh khô chống nước, lý tưởng đi phòng tập, đi biển hay mang ở nhà cực kỳ tiện lợi.',0,0,380,150,1,'2026-08-04 11:51:36'),(59,'CRC-CLASSIC-WHT','Crocs Classic Clog White','crocs-classic-clog-white',14,9,'Unisex',1150000,1400000,650000,18,'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800','Crocs Classic Clog màu trắng huyền thoại, dễ dàng gắn sticker Jibbitz thể hiện cá tính riêng độc đáo.',1,1,1890,520,1,'2026-08-04 11:51:36'),(60,'YZY-FOAM-ONYX','Yeezy Foam Runner Onyx','yeezy-foam-runner-onyx',14,13,'Unisex',3500000,4200000,2200000,17,'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg','Yeezy Foam Runner thiết kế điêu khắc tương lai bằng bọt tảo biển EVA siêu thoáng khí và độc lạ nhất hành tinh.',1,1,2600,640,1,'2026-08-04 11:51:36'),(61,'SK-ARCHFIT-SND','Skechers Arch Fit Horizon Sandal','skechers-arch-fit-horizon-sandal',14,12,'Nữ',1450000,1750000,800000,17,'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800','Dép nữ Skechers Arch Fit được thiết kế theo phom bác sĩ bàn chân chứng nhận, hỗ trợ lòm chân giảm mỏi tối ưu.',0,1,380,110,1,'2026-08-04 11:51:36'),(62,'BK-ARIZONA-WHT','Birkenstock Arizona EVA White','birkenstock-arizona-eva-white',15,8,'Nữ',1200000,1500000,700000,20,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg','Birkenstock Arizona EVA tone trắng thanh lịch, chất siêu nhẹ giặt rửa thoải mái và năng động trong mọi chuyến đi.',1,1,880,240,1,'2026-08-04 11:51:36'),(63,'BK-MAYARI-SLV','Birkenstock Mayari Birko-Flor Graceful','birkenstock-mayari-birko-flor-graceful',15,8,'Nữ',2400000,2800000,1400000,14,'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg','Birkenstock Mayari xỏ ngón quai thanh mảnh duyên dáng, lót bần Cork nâng đỡ lòng bàn chân dịu dàng.',0,1,620,170,1,'2026-08-04 11:51:36'),(64,'CRC-MEGACRUSH','Crocs Mega Crush Clog Bone','crocs-mega-crush-clog-bone',15,9,'Nữ',2450000,2900000,1500000,16,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800','Crocs Mega Crush đế nâng cao 7cm cực kỳ ấn tượng, chi tiết TPU quanh đế cá tính và quyến rũ cho phái đẹp.',1,1,1750,430,1,'2026-08-04 11:51:36'),(65,'NK-OFFCOURT-ADJ','Nike OffCourt Adjust Slide Women','nike-offcourt-adjust-slide-women',15,1,'Nữ',1100000,1350000,650000,19,'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png','Dép Nike OffCourt Adjust có quai dán điều chỉnh độ rộng linh hoạt, lót Revive Foam 2 lớp vô cùng thoải mái.',0,1,290,90,1,'2026-08-04 11:51:36'),(66,'MLB-SANDAL-MONO','MLB Korea Chunky Sandal Monogram','mlb-korea-chunky-sandal-monogram',15,10,'Nữ',2350000,2800000,1300000,16,'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800','Sandal nữ MLB Korea đệm quai êm mềm, đế răng cưa cao 5cm tôn nét sang chảnh và hiện đại cho phái nữ.',1,1,920,250,1,'2026-08-04 11:51:36');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_provinces`
--

DROP TABLE IF EXISTS `shipping_provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `province_name` varchar(100) NOT NULL,
  `shipping_fee` decimal(12,0) NOT NULL DEFAULT 30000,
  `estimated_days` varchar(50) DEFAULT '2-4 ngày',
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `province_name` (`province_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_provinces`
--

LOCK TABLES `shipping_provinces` WRITE;
/*!40000 ALTER TABLE `shipping_provinces` DISABLE KEYS */;
INSERT INTO `shipping_provinces` VALUES (1,'Hà Nội',20000,'1-2 ngày',1),(2,'TP. Hồ Chí Minh',25000,'2-3 ngày',1),(3,'Đà Nẵng',25000,'2-3 ngày',1),(4,'Vĩnh Long',15000,'1-2 ngày',1),(5,'Cần Thơ',20000,'2-3 ngày',1),(6,'Bình Dương',22000,'2-3 ngày',1),(7,'Đồng Nai',22000,'2-3 ngày',1),(8,'Hải Phòng',25000,'2-3 ngày',1),(9,'Tỉnh/Thành Khác',35000,'3-5 ngày',1);
/*!40000 ALTER TABLE `shipping_provinces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'site_name','SHOES STORE','general'),(2,'site_logo','','general'),(3,'site_description','Thương hiệu Sneaker hàng đầu mang đến trải nghiệm thời trang dịu nhẹ, thanh lịch và chất lượng cam kết chính hãng.','general'),(4,'site_keywords','giày sneaker, giày chính hãng, nike, adidas, jordan, dép nam nữ','general'),(5,'contact_address','TP. Vĩnh Long, Việt Nam','contact'),(6,'contact_hotline','0901.234.567','contact'),(7,'contact_email','support@shoesstore.vn','contact'),(8,'bank_id','ACB','payment'),(9,'bank_account','0123456789','payment'),(10,'bank_name','SHOP OWNER','payment'),(11,'footer_copyright','© 2026 SHOES STORE. Thiết kế bởi Trang Sỉ Giàu.','footer'),(12,'hero_title','BỨT PHÁ PHONG CÁCH','cms'),(13,'hero_subtitle','Siêu Phẩm Sneaker 2026','cms'),(14,'hero_image','https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800','cms'),(15,'hero_button_text','MUA SẮM NGAY','cms'),(16,'hero_button_link','all-products.php','cms'),(17,'section_hot_title','🔥 SẢN PHẨM NỔI BẬT','cms'),(18,'section_new_title','✨ HÀNG MỚI VỀ','cms'),(19,'section_sale_title','💰 ĐANG GIẢM GIÁ SỐC','cms'),(20,'section_brand_title','🏆 THƯƠNG HIỆU NỔI BẬT','cms'),(21,'section_voucher_title','🎟️ MÃ GIẢM GIÁ KHUYẾN MÃI','cms'),(22,'service_1_icon','fa-solid fa-truck-fast','services'),(23,'service_1_title','Miễn Phí Vận Chuyển','services'),(24,'service_1_desc','Cho đơn hàng từ 500.000đ','services'),(25,'service_2_icon','fa-solid fa-shield-halved','services'),(26,'service_2_title','100% Chính Hãng','services'),(27,'service_2_desc','Cam kết hàng Authentic','services'),(28,'service_3_icon','fa-solid fa-rotate-left','services'),(29,'service_3_title','Đổi Trả 30 Ngày','services'),(30,'service_3_desc','Miễn phí nếu lỗi sản phẩm','services'),(31,'service_4_icon','fa-solid fa-headset','services'),(32,'service_4_title','Hỗ Trợ 24/7','services'),(33,'service_4_desc','Tư vấn mọi lúc mọi nơi','services');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_links`
--

DROP TABLE IF EXISTS `social_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_links`
--

LOCK TABLES `social_links` WRITE;
/*!40000 ALTER TABLE `social_links` DISABLE KEYS */;
INSERT INTO `social_links` VALUES (1,'Facebook','https://facebook.com/shoesstore','fa-brands fa-facebook-f',1,1),(2,'Instagram','https://instagram.com/shoesstore','fa-brands fa-instagram',2,1),(3,'TikTok','https://tiktok.com/@shoesstore','fa-brands fa-tiktok',3,1),(4,'Zalo','https://zalo.me/0901234567','fa-solid fa-comment-dots',4,1),(5,'YouTube','https://youtube.com/@shoesstore','fa-brands fa-youtube',5,1);
/*!40000 ALTER TABLE `social_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (1,3,'Trần Văn Khách','0912345678',4,'123 Đường Phạm Hùng, Phường 1, TP. Vĩnh Long',1,'2026-08-04 11:50:22'),(2,3,'Trần Văn Khách','0912345678',2,'456 Đường Nguyễn Huệ, Quận 1, TP. HCM',0,'2026-08-04 11:50:22'),(3,4,'Nguyễn Thị Lan','0933456789',1,'789 Đường Láng, Đống Đa, Hà Nội',1,'2026-08-04 11:50:22');
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vouchers`
--

DROP TABLE IF EXISTS `user_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vouchers`
--

LOCK TABLES `user_vouchers` WRITE;
/*!40000 ALTER TABLE `user_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Quản Trị Viên','admin@shoes.com','0901234567','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'1995-01-15','local',1,0,'admin',0.00,0,1,'2026-08-04 11:50:22','2026-08-04 11:50:22'),(2,'Nhân Viên Bán Hàng','staff@shoes.com','0907654321','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'1998-06-20','local',1,0,'staff',0.00,0,1,'2026-08-04 11:50:22','2026-08-04 11:50:22'),(3,'Trần Văn Khách','khachhang@gmail.com','0912345678','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2000-03-10','local',1,0,'customer',0.00,0,1,'2026-08-04 11:50:22','2026-08-04 11:50:22'),(4,'Nguyễn Thị Lan','lanng@gmail.com','0933456789','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'1999-11-05','local',1,0,'customer',0.00,0,1,'2026-08-04 11:50:22','2026-08-04 11:50:22');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES (1,NULL,'WELCOME50','Chào mừng khách hàng mới - Giảm 50K','fixed',50000,500000,50000,100,12,1,'new_user','2026-01-01 00:00:00','2026-12-31 23:59:59',1),(2,1,'NIKE15','Ưu đãi Nike - Giảm 15% cho sản phẩm Nike','percent',15,1000000,300000,50,4,1,'general','2026-01-01 00:00:00','2026-12-31 23:59:59',1),(3,2,'ADIDAS100K','Ưu đãi Adidas - Giảm 100K cho sản phẩm Adidas','fixed',100000,1200000,100000,60,8,1,'general','2026-01-01 00:00:00','2026-12-31 23:59:59',1),(4,NULL,'FREESHIP','Miễn phí vận chuyển toàn quốc','freeship',35000,300000,35000,200,34,3,'general','2026-01-01 00:00:00','2026-12-31 23:59:59',1),(5,NULL,'SUMMER2026','Mùa hè sôi động - Giảm 15%','percent',15,2000000,500000,80,10,1,'holiday','2026-06-01 00:00:00','2026-08-31 23:59:59',1),(6,3,'JORDAN200K','Bóng rổ Jordan - Giảm 200K cho giày Jordan','fixed',200000,2500000,200000,30,2,1,'general','2026-01-01 00:00:00','2026-12-31 23:59:59',1);
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-04 18:52:01
