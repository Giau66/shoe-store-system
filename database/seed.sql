USE `web_shoe`;

-- ========================================================
-- 1. NẠP DANH MỤC SẢN PHẨM (categories)
-- ========================================================
INSERT INTO `categories` (`id`, `name`, `slug`, `image`, `status`) VALUES
(1, 'Giày Nam', 'giay-nam', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff', 1),
(2, 'Giày Nữ', 'giay-nu', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a', 1),
(3, 'Running (Chạy Bộ)', 'giay-chay-bo', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2', 1),
(4, 'Basketball (Bóng Rổ)', 'giay-bong-ro', 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b', 1),
(5, 'Lifestyle (Thời Trang)', 'giay-thoi-trang', 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ========================================================
-- 2. NẠP THƯƠNG HIỆU (brands)
-- ========================================================
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`) VALUES
(1, 'Nike', 'nike', 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg'),
(2, 'Adidas', 'adidas', 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg'),
(3, 'Air Jordan', 'air-jordan', 'https://upload.wikimedia.org/wikipedia/en/3/37/Jumpman_logo.svg'),
(4, 'Puma', 'puma', 'https://upload.wikimedia.org/wikipedia/en/a/ae/Puma_AG.svg'),
(5, 'New Balance', 'new-balance', 'https://upload.wikimedia.org/wikipedia/commons/e/ea/New_Balance_logo.svg'),
(6, 'Converse', 'converse', 'https://upload.wikimedia.org/wikipedia/commons/3/30/Converse_logo.svg')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ========================================================
-- 3. TÀI KHOẢN MẪU (users) - Mật khẩu mặc định: 123456
-- ========================================================
INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `role`, `is_email_verified`, `status`) VALUES
(1, 'Quản Trị Viên (Admin)', 'admin@shoes.com', '0901234567', '$2y$10$e8TvhO2I5fQ5h0qYdO1i8.PqA1t9N7kC3v5O2I5fQ5h0qYdO1i8.P', 'admin', 1, 1),
(2, 'Nhân Viên Bán Hàng', 'staff@shoes.com', '0907654321', '$2y$10$e8TvhO2I5fQ5h0qYdO1i8.PqA1t9N7kC3v5O2I5fQ5h0qYdO1i8.P', 'staff', 1, 1),
(3, 'Trần Văn Khách', 'khachhang@gmail.com', '0912345678', '$2y$10$e8TvhO2I5fQ5h0qYdO1i8.PqA1t9N7kC3v5O2I5fQ5h0qYdO1i8.P', 'customer', 1, 1)
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);

-- ========================================================
-- 4. NẠP SẢN PHẨM GIÀY MẪU (products)
-- ========================================================
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`) VALUES
(1, 'NK-AF1-001', 'Nike Air Force 1 \'07 White', 'nike-air-force-1-07-white', 5, 1, 'Unisex', 2929000, 3500000, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800', 'Đôi giày huyền thoại Nike Air Force 1 \'07 với chất liệu da cao cấp, đệm Air êm ái thích hợp phối mọi trang phục.', 1, 0, 120, 45),

(2, 'AD-SAMBA-01', 'Adidas Samba OG White Black', 'adidas-samba-og-white-black', 5, 2, 'Unisex', 2700000, 3100000, 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800', 'Adidas Samba OG phong cách Retro chưa bao giờ hết hot. Phù hợp cho cả nam và nữ đi chơi, đi làm.', 1, 1, 350, 88),

(3, 'JD-1-RETRO', 'Air Jordan 1 Retro High OG Chicago', 'air-jordan-1-retro-high-og-chicago', 4, 3, 'Nam', 5200000, 6000000, 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?q=80&w=800', 'Phối màu Chicago kinh điển của dòng Air Jordan 1. Lựa chọn số 1 cho các tín đồ Sneakerhead và yêu thích bóng rổ.', 1, 0, 500, 60),

(4, 'NB-530-SG', 'New Balance 530 Metallic Silver', 'new-balance-530-metallic-silver', 3, 5, 'Unisex', 2650000, 2900000, 'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800', 'Giày chạy bộ New Balance 530 mang phong cách Dad Shoe năng động, đệm ABZORB cực êm.', 0, 1, 210, 30),

(5, 'NK-DUNK-PANDA', 'Nike Dunk Low Black White (Panda)', 'nike-dunk-low-black-white-panda', 5, 1, 'Unisex', 3100000, 3600000, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800', 'Nike Dunk Low Panda - Phối màu đen trắng dễ phối đồ nhất mọi thời đại.', 1, 0, 410, 95),

(6, 'AD-ULTRABOOST', 'Adidas Ultraboost Light Running', 'adidas-ultraboost-light-running', 3, 2, 'Nam', 3800000, 4500000, 'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800', 'Công nghệ đệm Boost siêu nhẹ mang lại trải nghiệm êm ái tuyệt đối cho người chạy bộ chuyên nghiệp.', 0, 1, 95, 12),

(7, 'CV-1970S-HI', 'Converse Chuck 70 Vintage Canvas High', 'converse-chuck-70-vintage-canvas-high', 5, 6, 'Unisex', 2000000, 2300000, 'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800', 'Dòng Converse Chuck 70 cổ cao chất vải Canvas dày dặn, đế ngà vintage quyến rũ.', 0, 0, 180, 40),

(8, 'PM-PALERMO', 'Puma Palermo Special Pink White', 'puma-palermo-special-pink-white', 2, 4, 'Nữ', 2250000, 2600000, 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800', 'Puma Palermo phối màu cá tính dành riêng cho các bạn nữ yêu thích sự trẻ trung, nổi bật.', 0, 1, 140, 25)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ========================================================
-- 5. NẠP BIẾN THỂ SIZE & TỒN KHO THỰC TẾ (product_variants)
-- ========================================================
INSERT INTO `product_variants` (`product_id`, `size`, `color`, `stock_quantity`) VALUES
-- Nike Air Force 1
(1, '38', 'Trắng', 10), (1, '39', 'Trắng', 15), (1, '40', 'Trắng', 8), (1, '41', 'Trắng', 0), (1, '42', 'Trắng', 5),
-- Adidas Samba
(2, '37', 'Trắng Đen', 5), (2, '38', 'Trắng Đen', 12), (2, '39', 'Trắng Đen', 20), (2, '40', 'Trắng Đen', 10), (2, '41', 'Trắng Đen', 6),
-- Air Jordan 1
(3, '40', 'Đỏ Đen', 4), (3, '41', 'Đỏ Đen', 6), (3, '42', 'Đỏ Đen', 3), (3, '43', 'Đỏ Đen', 2),
-- New Balance 530
(4, '36', 'Bạc', 8), (4, '37', 'Bạc', 10), (4, '38', 'Bạc', 12), (4, '39', 'Bạc', 5),
-- Nike Dunk Low Panda
(5, '38', 'Đen Trắng', 15), (5, '39', 'Đen Trắng', 20), (5, '40', 'Đen Trắng', 18), (5, '41', 'Đen Trắng', 10),
-- Adidas Ultraboost
(6, '40', 'Đen', 7), (6, '41', 'Đen', 10), (6, '42', 'Đen', 8),
-- Converse Chuck 70
(7, '38', 'Đen', 10), (7, '39', 'Đen', 15), (7, '40', 'Đen', 12), (7, '41', 'Đen', 9),
-- Puma Palermo
(8, '36', 'Hồng', 6), (8, '37', 'Hồng', 10), (8, '38', 'Hồng', 8);

-- ========================================================
-- 6. NẠP MÃ GIẢM GIÁ MẪU (vouchers)
-- ========================================================
INSERT INTO `vouchers` (`code`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `start_date`, `end_date`, `status`) VALUES
('WELCOME50', 'fixed', 50000, 500000, 50000, 100, 12, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
('SHOES10', 'percent', 10, 1000000, 200000, 50, 5, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
('FREESHIP', 'freeship', 30000, 300000, 30000, 200, 34, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1)
ON DUPLICATE KEY UPDATE `discount_value` = VALUES(`discount_value`);