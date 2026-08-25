<?php
// tools/seed_catalog.php
// Script nạp dữ liệu thật 15 thương hiệu & 66+ sản phẩm giày dép nam nữ có giá & ảnh thật

require_once __DIR__ . '/../config/db.php';

echo "=== BẮT ĐẦU NẠP DỮ LIỆU THƯƠNG HIỆU & SẢN PHẨM THẬT ===\n";

// Disable Foreign Key checks for clean reset
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");

// Truncate tables
$tables = ['product_images', 'product_variants', 'products', 'brands', 'categories'];
foreach ($tables as $t) {
    $conn->query("TRUNCATE TABLE `$t`;");
}
echo "✓ Đã dọn dẹp các bảng cũ.\n";

// 1. CHÈN 15 THƯƠNG HIỆU THẬT
$brands = [
    [1, 'Nike', 'nike', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/800px-Logo_NIKE.svg.png', 'Thương hiệu thể thao hàng đầu thế giới từ Mỹ, nổi tiếng với công nghệ Air và thiết kế iconic.'],
    [2, 'Adidas', 'adidas', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/800px-Adidas_Logo.svg.png', 'Thương hiệu thể thao Đức với 3 sọc huyền thoại, tiên phong trong công nghệ Boost & Primeknit.'],
    [3, 'Jordan', 'jordan', 'https://upload.wikimedia.org/wikipedia/en/thumb/3/37/Jumpman_logo.svg/800px-Jumpman_logo.svg.png', 'Thương hiệu giày bóng rổ & thời trang đường phố huyền thoại mang tên Michael Jordan.'],
    [4, 'Puma', 'puma', 'https://upload.wikimedia.org/wikipedia/en/thumb/d/da/Puma_complete_logo.svg/800px-Puma_complete_logo.svg.png', 'Thương hiệu thể thao Đức với phong cách trẻ trung, năng động và dòng da lộn kinh điển.'],
    [5, 'New Balance', 'new-balance', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ea/New_Balance_logo.svg/800px-New_Balance_logo.svg.png', 'Thương hiệu Mỹ nổi tiếng với sự thoải mái, đệm ABZORB & N-ergy, cùng phong cách Dad Shoes.'],
    [6, 'Converse', 'converse', 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/Converse_logo.svg/800px-Converse_logo.svg.png', 'Biểu tượng văn hóa đường phố toàn cầu với dòng Chuck Taylor All Star huyền thoại.'],
    [7, 'Vans', 'vans', 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Vans-logo.svg/800px-Vans-logo.svg.png', 'Thương hiệu trượt ván đường phố đình đám với slogan "Off The Wall" và sọc Jazz cá tính.'],
    [8, 'Birkenstock', 'birkenstock', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/Birkenstock_logo.svg/800px-Birkenstock_logo.svg.png', 'Thương hiệu dép Đức cao cấp hơn 240 năm lịch sử với đế cork định hình bàn chân siêu êm.'],
    [9, 'Crocs', 'crocs', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Crocs_logo.svg/800px-Crocs_logo.svg.png', 'Thương hiệu dép Clog siêu nhẹ toàn cầu từ Mỹ với chất liệu Croslite chống nước và kháng khuẩn.'],
    [10, 'MLB Korea', 'mlb-korea', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Major_League_Baseball_logo.svg/800px-Major_League_Baseball_logo.svg.png', 'Thương hiệu thời trang đường phố Hàn Quốc phong cách Chunky năng động lấy cảm hứng từ giải bóng chày Mỹ.'],
    [11, 'Asics', 'asics', 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b1/Asics_Logo.svg/800px-Asics_Logo.svg.png', 'Thương hiệu thể thao Nhật Bản tiên phong công nghệ GEL đệm giảm chấn vượt trội.'],
    [12, 'Skechers', 'skechers', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Skechers_logo.svg/800px-Skechers_logo.svg.png', 'Thương hiệu giày Mỹ dẫn đầu về sự thoải mái với đệm Memory Foam & Arch Fit.'],
    [13, 'Yeezy', 'yeezy', 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/Yeezy_logo.svg/800px-Yeezy_logo.svg.png', 'Dòng sản phẩm độc đáo mang tầm vóc biểu tượng tương lai hợp tác giữa Kanye West & Adidas.'],
    [14, 'Salomon', 'salomon', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Salomon_logo.svg/800px-Salomon_logo.svg.png', 'Thương hiệu Pháp hàng đầu về giày outdoor, trail running và thiết kế gorpcore đỉnh cao.'],
    [15, 'On Running', 'on-running', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a0/On_Running_logo.svg/800px-On_Running_logo.svg.png', 'Thương hiệu giày chạy bộ Thụy Sĩ đỉnh cao với công nghệ đế CloudTec êm như bước trên mây.']
];

$stmt_b = $conn->prepare("INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`) VALUES (?, ?, ?, ?, ?, 1)");
foreach ($brands as $b) {
    $stmt_b->bind_param("issss", $b[0], $b[1], $b[2], $b[3], $b[4]);
    $stmt_b->execute();
}
echo "✓ Đã thêm " . count($brands) . " thương hiệu thực tế.\n";

// 2. CHÈN DANH MỤC PHÂN CẤP (15 danh mục)
$categories = [
    [1, NULL, 'Giày Nam', 'giay-nam', 'giay', 'nam', 1],
    [2, NULL, 'Giày Nữ', 'giay-nu', 'giay', 'nu', 2],
    [3, NULL, 'Dép Nam', 'dep-nam', 'dep', 'nam', 3],
    [4, NULL, 'Dép Nữ', 'dep-nu', 'dep', 'nu', 4],
    [5, 1, 'Sneaker Nam', 'sneaker-nam', 'giay', 'nam', 1],
    [6, 1, 'Giày Chạy Bộ Nam', 'giay-chay-bo-nam', 'giay', 'nam', 2],
    [7, 1, 'Giày Bóng Rổ', 'giay-bong-ro', 'giay', 'nam', 3],
    [8, 1, 'Giày Thời Trang Nam', 'giay-thoi-trang-nam', 'giay', 'nam', 4],
    [9, 2, 'Sneaker Nữ', 'sneaker-nu', 'giay', 'nu', 1],
    [10, 2, 'Giày Chạy Bộ Nữ', 'giay-chay-bo-nu', 'giay', 'nu', 2],
    [11, 2, 'Giày Thời Trang Nữ', 'giay-thoi-trang-nu', 'giay', 'nu', 3],
    [12, 3, 'Dép Quai Ngang Nam', 'dep-quai-ngang-nam', 'dep', 'nam', 1],
    [13, 3, 'Sandal Nam', 'sandal-nam', 'dep', 'nam', 2],
    [14, 4, 'Dép Quai Ngang Nữ', 'dep-quai-ngang-nu', 'dep', 'nu', 1],
    [15, 4, 'Sandal Nữ', 'sandal-nu', 'dep', 'nu', 2]
];

$stmt_c = $conn->prepare("INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `type`, `gender`, `sort_order`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
foreach ($categories as $c) {
    $stmt_c->bind_param("iissssi", $c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6]);
    $stmt_c->execute();
}
echo "✓ Đã thêm " . count($categories) . " danh mục phân cấp.\n";

// 3. CHÈN 66 SẢN PHẨM GIÀY DÉP THẬT (có giá, giá gốc, giá vốn, % giảm, ảnh thật)
$products = [
    // --- SNEAKER NAM (category_id = 5) ---
    [
        1, 'NK-AF1-WHT', 'Nike Air Force 1 \'07 White', 'nike-air-force-1-07-white', 5, 1, 'Unisex', 2929000, 3500000, 1800000, 16,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/AIR+FORCE+1+%2707.png',
        'Đôi giày huyền thoại Nike Air Force 1 \'07 với chất liệu da thật cao cấp, đệm Air êm ái, thích hợp phối mọi trang phục streetwear.', 1, 0, 1450, 420
    ],
    [
        2, 'NK-DUNK-PANDA', 'Nike Dunk Low Retro Panda', 'nike-dunk-low-retro-panda', 5, 1, 'Unisex', 3100000, 3600000, 1900000, 14,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/b1bcbca4-e853-4df7-b221-ee2a6528fc89/NIKE+DUNK+LOW+RETRO.png',
        'Phối màu Panda đen trắng kinh điển của dòng Nike Dunk Low, lựa chọn cực kỳ thời thượng và dễ phối đồ nhất hiện nay.', 1, 0, 2300, 580
    ],
    [
        3, 'AD-SAMBA-OG', 'Adidas Samba OG White Black', 'adidas-samba-og-white-black', 5, 2, 'Unisex', 2700000, 3100000, 1600000, 13,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7fce7de0e8984e84a447a8bf01187e1c_9366/Giay_Samba_OG_trang_B75806_01_standard.jpg',
        'Adidas Samba OG phong cách Retro chưa bao giờ giảm sức hút, chất da thật mềm mại kết hợp đế cao su bám đường tuyệt vời.', 1, 1, 3800, 920
    ],
    [
        4, 'AD-SUPERSTAR', 'Adidas Superstar Cloud White', 'adidas-superstar-cloud-white', 5, 2, 'Unisex', 2500000, 2900000, 1500000, 14,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/7ed0855435194229a525aad6009a0497_9366/Giay_Superstar_trang_EG4958_01_standard.jpg',
        'Biểu tượng mũi sò Shell-Toe vượt thời gian của Adidas Superstar, chất liệu da cổ điển cùng 3 sọc đen nổi bật.', 0, 0, 1050, 310
    ],
    [
        5, 'NB-574-GRY', 'New Balance 574 Classic Grey', 'new-balance-574-classic-grey', 5, 5, 'Unisex', 2650000, 2900000, 1600000, 9,
        'https://nb.scene7.com/is/image/NB/ml574evg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',
        'Dòng New Balance 574 huyền thoại phối màu Xám Classic, tích hợp bộ đệm ENCAP hỗ trợ tối đa cho việc đi lại cả ngày.', 0, 1, 720, 195
    ],
    [
        6, 'NB-550-WHT', 'New Balance 550 White Grey', 'new-balance-550-white-grey', 5, 5, 'Unisex', 3250000, 3800000, 2000000, 14,
        'https://nb.scene7.com/is/image/NB/bb550pb1_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',
        'New Balance 550 với nguồn gốc từ giày bóng rổ thập niên 80, thiết kế Retro bứt phá cực hot trên toàn thế giới.', 1, 1, 1600, 390
    ],
    [
        7, 'MLB-CHUNKY-BOS', 'MLB Korea Big Ball Chunky A Boston', 'mlb-korea-big-ball-chunky-a-boston', 5, 10, 'Unisex', 2850000, 3300000, 1700000, 14,
        'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',
        'Sneaker MLB Chunky đế cao 6cm tôn dáng đỉnh cao, in logo đội bóng chày Boston Red Sox thời thượng.', 1, 0, 1850, 460
    ],
    [
        8, 'VN-KNU-SKOOL', 'Vans Knu Skool Black White', 'vans-knu-skool-black-white', 5, 7, 'Unisex', 2200000, 2600000, 1300000, 15,
        'https://images.vans.com/is/image/VansBrand/VN0009QC6BT-HERO?$PDP-FULL-IMAGE$',
        'Vans Knu Skool với lưỡi gà mập phồng độc đáo phong cách 90s Y2K, sọc Sidestripe 3D nổi bật.', 0, 1, 1200, 280
    ],
    [
        9, 'YZY-350-ONYX', 'Yeezy Boost 350 V2 Onyx', 'yeezy-boost-350-v2-onyx', 5, 13, 'Unisex', 6200000, 7000000, 4200000, 11,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/c9f28581e6b34b1793faae67010411ed_9366/YEEZY_BOOST_350_V2_DJen_HQ4540_01_standard.jpg',
        'Yeezy Boost 350 V2 Onyx phủ sắc đen quyến rũ, chất liệu vải dệt Primeknit mượt mà cùng đế đệm Boost êm vượt trội.', 1, 0, 2900, 510
    ],
    [
        10, 'AS-GEL-NYC', 'Asics Gel-NYC Cream Oyster Grey', 'asics-gel-nyc-cream-oyster-grey', 5, 11, 'Unisex', 3600000, 4200000, 2200000, 14,
        'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800',
        'Giày Asics Gel-NYC phong cách Techwear & Dad Shoe kết hợp cấu trúc GEL và đệm Solyte êm ái hàng đầu Nhật Bản.', 1, 1, 1400, 320
    ],

    // --- GIÀY CHẠY BỘ NAM (category_id = 6) ---
    [
        11, 'NK-PEGASUS41', 'Nike Air Zoom Pegasus 41', 'nike-air-zoom-pegasus-41', 6, 1, 'Nam', 3600000, 4200000, 2200000, 14,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/4048acb0-2b76-4093-ad34-e4e1b5bb0fae/AIR+ZOOM+PEGASUS+41.png',
        'Dòng giày chạy bộ quốc dân Nike Pegasus 41 trang bị đệm bọt ReactX kết hợp Air Zoom phản hồi lực siêu nhạy.', 1, 1, 980, 220
    ],
    [
        12, 'NK-INVINCIBLE3', 'Nike ZoomX Invincible 3 White', 'nike-zoomx-invincible-3-white', 6, 1, 'Nam', 4800000, 5500000, 3000000, 13,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/60f4e150-1c66-4e55-8025-0d297ff0dfed/INVINCIBLE+3.png',
        'Siêu phẩm chạy bộ Nike Invincible 3 với đệm ZoomX dày tối đa giúp bảo vệ khớp gối và hồi phục năng lượng tuyệt vời.', 1, 0, 1350, 290
    ],
    [
        13, 'AD-ULTRABOOST', 'Adidas Ultraboost Light Black', 'adidas-ultraboost-light-black', 6, 2, 'Nam', 3800000, 4500000, 2300000, 16,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/1f5e1ae9c8e141ff9c4baf1c00df4872_9366/Giay_Ultraboost_Light_DJen_GY9351_01_standard.jpg',
        'Adidas Ultraboost Light với đệm Light Boost nhẹ hơn 30% so với thế hệ trước, trải nghiệm chạy êm mượt vô hạn.', 1, 1, 890, 195
    ],
    [
        14, 'NB-FUELCELL', 'New Balance FuelCell Propel v4', 'new-balance-fuelcell-propel-v4', 6, 5, 'Nam', 2900000, 3400000, 1800000, 15,
        'https://nb.scene7.com/is/image/NB/mfcprv4_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',
        'New Balance FuelCell Propel v4 tích hợp tấm TPU giữa đế giúp bật nảy đà tốt cho cự ly chạy từ 5km đến Marathon.', 0, 1, 410, 95
    ],
    [
        15, 'AS-KAYANO14', 'Asics Gel-Kayano 14 Metallic Plum', 'asics-gel-kayano-14-metallic-plum', 6, 11, 'Nam', 4200000, 4800000, 2600000, 13,
        'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',
        'Mẫu chạy bộ đỉnh cao Asics Gel-Kayano 14 mang tính biểu tượng những năm 2000, êm ái và ổn định bàn chân tuyệt đối.', 1, 1, 1650, 340
    ],
    [
        16, 'ON-MONSTER2', 'On Running Cloudmonster 2 Black', 'on-running-cloudmonster-2-black', 6, 15, 'Nam', 4950000, 5600000, 3100000, 12,
        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800',
        'On Running Cloudmonster 2 sở hữu các đệm mây CloudTec khổng lồ mang đến khả năng đệm tối đa và năng lượng bùng nổ.', 1, 1, 1100, 240
    ],
    [
        17, 'SLM-XT6-GTX', 'Salomon XT-6 Gore-Tex Black', 'salomon-xt-6-gore-tex-black', 6, 14, 'Nam', 5400000, 6200000, 3500000, 13,
        'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800',
        'Salomon XT-6 Gore-Tex chống nước tuyệt đối, công nghệ dây buộc Quicklace tiện lợi cùng đệm ACS nâng đỡ vượt địa hình.', 1, 1, 1890, 410
    ],
    [
        18, 'SK-GOWALK6', 'Skechers Go Walk 6 Black Navy', 'skechers-go-walk-6-black-navy', 6, 12, 'Nam', 1950000, 2300000, 1100000, 15,
        'https://images.unsplash.com/photo-1582588678413-dbf45f4823e9?q=80&w=800',
        'Skechers Go Walk 6 tích hợp đệm ULTRA GO siêu nhẹ và công nghệ đệm Air Cooled Goga Mat cực kỳ êm ái cho đôi chân.', 0, 1, 530, 140
    ],

    // --- GIÀY BÓNG RỔ (category_id = 7) ---
    [
        19, 'JD-1-CHICAGO', 'Air Jordan 1 Retro High OG Chicago', 'air-jordan-1-retro-high-og-chicago', 7, 3, 'Unisex', 5200000, 6000000, 3200000, 13,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cd6b3ec7-e7d0-47c5-86c4-2e53cfe67ed7/AIR+JORDAN+1+RETRO+HIGH+OG.png',
        'Phối màu Chicago đỏ trắng đen huyền thoại của Air Jordan 1, biểu tượng số 1 trong thế giới Sneaker & Bóng rổ.', 1, 0, 4600, 890
    ],
    [
        20, 'JD-4-BRED', 'Air Jordan 4 Retro Bred Reimagined', 'air-jordan-4-retro-bred-reimagined', 7, 3, 'Nam', 5800000, 6500000, 3500000, 11,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e21943c5-47d6-4a50-8d7e-d7a45c4b42a2/JORDAN+4+RETRO.png',
        'Air Jordan 4 Bred Reimagined bằng chất da thật cao cấp láng mịn, form dáng thể thao chuẩn mực cực kỳ đẳng cấp.', 1, 1, 3400, 620
    ],
    [
        21, 'NK-LEBRON21', 'Nike LeBron 21 Akoya', 'nike-lebron-21-akoya', 7, 1, 'Nam', 5500000, 6200000, 3300000, 11,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/3b7df083-c3b1-4a25-9f67-c0c3c7c14be2/LEBRON+XXI.png',
        'Giày bóng rổ Nike LeBron 21 trang bị hệ thống đệm Air Zoom kẹp giữa bọt Cushlon 2.0 tối ưu cú bật nhảy và tiếp đất.', 0, 1, 650, 130
    ],
    [
        22, 'NK-GTCUT3', 'Nike GT Cut 3 Summit White', 'nike-gt-cut-3-summit-white', 7, 1, 'Nam', 4600000, 5200000, 2800000, 12,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/1f66085a-0a2b-47e1-88df-b73f98292881/AIR+JORDAN+1+LOW.png',
        'Dòng giày bóng rổ tốc độ Nike GT Cut 3 tích hợp bọt ZoomX đầu tiên trên sân bóng rổ, giúp xoay đổi hướng tức thì.', 1, 1, 820, 180
    ],

    // --- GIÀY THỜI TRANG NAM (category_id = 8) ---
    [
        23, 'CV-CHUCK70-BLK', 'Converse Chuck 70 High Black', 'converse-chuck-70-high-black', 8, 6, 'Unisex', 2000000, 2300000, 1200000, 13,
        'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw1a6c1ee0/images/a_107/162050C_A_107X1.jpg',
        'Converse Chuck 70 cổ cao chất vải Canvas 12oz dày dặn, đệm lót OrthoLite êm ái cùng đường chỉ khâu Vintage.', 0, 0, 890, 280
    ],
    [
        24, 'VN-OLD-SKOOL', 'Vans Old Skool Black White', 'vans-old-skool-black-white', 8, 7, 'Unisex', 1800000, 2100000, 1000000, 14,
        'https://images.vans.com/is/image/VansBrand/VN000D3HY28-HERO?$PDP-FULL-IMAGE$',
        'Vans Old Skool với đường sọc trắng Jazz kinh điển, da lộn phối vải canvas bền bỉ cho giới trẻ năng động.', 0, 1, 1100, 360
    ],
    [
        25, 'PM-SUEDE-BLK', 'Puma Suede Classic XXI Black', 'puma-suede-classic-xxi-black', 8, 4, 'Unisex', 2100000, 2400000, 1300000, 13,
        'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/374915/01/sv01/fnd/SEA/fmt/png/Suede-Classic-XXI-Sneakers',
        'Puma Suede Classic da lộn mịn đẹp, kiểu dáng retro tối giản chuẩn phong cách hip-hop từ thập niên 80.', 0, 0, 520, 150
    ],
    [
        26, 'NB-2002R-RAIN', 'New Balance 2002R Protection Pack Rain Cloud', 'new-balance-2002r-protection-pack-rain-cloud', 8, 5, 'Nam', 4500000, 5200000, 2800000, 13,
        'https://images.unsplash.com/photo-1539185441755-769473a23570?q=80&w=800',
        'Siêu phẩm New Balance 2002R thiết kế dải da rách đan lớp Protection Pack cá tính, đế N-ergy giảm xóc vượt trội.', 1, 1, 2400, 580
    ],
    [
        27, 'NB-1906R-SLV', 'New Balance 1906R Metallic Silver', 'new-balance-1906r-metallic-silver', 8, 5, 'Nam', 3850000, 4400000, 2400000, 13,
        'https://nb.scene7.com/is/image/NB/m1906ree_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',
        'New Balance 1906R mang đậm tinh thần retro-futuristic những năm 2000, bộ đệm N-ergy kết hợp đệm gót ABZORB.', 1, 1, 1750, 410
    ],

    // --- SNEAKER NỮ (category_id = 9) ---
    [
        28, 'NK-AF1-WMNS', 'Nike Air Force 1 \'07 Women White', 'nike-air-force-1-07-women-white', 9, 1, 'Nữ', 2929000, 3500000, 1800000, 16,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/350e7f3a-979a-402b-9396-a8a998a18e498/W+AIR+FORCE+1+%2707.png',
        'Nike Air Force 1 phiên bản dành riêng cho nữ với thiết kế thanh thoát, màu trắng tinh khôi cực kỳ dễ phối đồ.', 1, 0, 1950, 490
    ],
    [
        29, 'AD-SAMBA-ROSE', 'Adidas Samba OG Women Rose', 'adidas-samba-og-women-rose', 9, 2, 'Nữ', 2800000, 3200000, 1700000, 13,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3aed9c49f77f4c27b267af4c00e60e83_9366/Giay_Samba_OG_Hong_IG4199_01_standard.jpg',
        'Adidas Samba OG phối màu hồng pastel nữ tính kết hợp cùng sọc da trắng ngọt ngào, hot trend thời trang phái đẹp.', 1, 1, 2600, 610
    ],
    [
        30, 'AD-CAMPUS-00S', 'Adidas Campus 00s Core Black Women', 'adidas-campus-00s-core-black-women', 9, 2, 'Nữ', 2600000, 3000000, 1600000, 13,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8b628eb1988941799f9faeab00f607c3_9366/Giay_Campus_00s_DJen_HQ8708_01_standard.jpg',
        'Adidas Campus 00s phom dáng béo mập skate độc đáo, dây giày bản to thời thượng tạo dấu ấn riêng cho các bạn nữ.', 1, 1, 1450, 330
    ],
    [
        31, 'NB-530-SLV', 'New Balance 530 Metallic Silver', 'new-balance-530-metallic-silver', 9, 5, 'Nữ', 2650000, 2900000, 1600000, 9,
        'https://nb.scene7.com/is/image/NB/mr530sg_nb_02_i?$pdpflexf2$&qlt=80&fmt=webp&wid=880&hei=880',
        'New Balance 530 Metallic Silver mang đến vẻ đẹp hoài cổ năng động, chất liệu lưới thoáng khí đệm ABZORB siêu nhẹ.', 0, 1, 780, 210
    ],
    [
        32, 'MLB-LINER-WHT', 'MLB Korea Chunky Liner Mid White Navy', 'mlb-korea-chunky-liner-mid-white-navy', 9, 10, 'Nữ', 3200000, 3700000, 2000000, 14,
        'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',
        'MLB Chunky Liner thiết kế đường viền hiện đại, tôn dáng cao ráo cho các quý cô cá tính.', 1, 1, 1250, 290
    ],
    [
        33, 'PM-PALERMO-PNK', 'Puma Palermo Leather Pink White', 'puma-palermo-leather-pink-white', 9, 4, 'Nữ', 2250000, 2600000, 1350000, 13,
        'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/396463/06/sv01/fnd/SEA/fmt/png/Palermo-Leather-Sneakers',
        'Puma Palermo phối màu hồng pastel ngọt ngào, chất da cao cấp sang trọng cùng đế gum cá tính.', 0, 1, 560, 160
    ],
    [
        34, 'AS-GT2160-SLV', 'Asics GT-2160 Cream Pure Silver', 'asics-gt-2160-cream-pure-silver', 9, 11, 'Nữ', 3300000, 3800000, 2000000, 13,
        'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=800',
        'Asics GT-2160 tone màu kem bạc thời thượng, cấu trúc GEL lót đệm siêu êm ái khi di chuyển liên tục.', 1, 1, 980, 240
    ],

    // --- GIÀY CHẠY BỘ NỮ (category_id = 10) ---
    [
        35, 'NK-AIRMAX90-PNK', 'Nike Air Max 90 Futura Pink', 'nike-air-max-90-futura-pink', 10, 1, 'Nữ', 3200000, 3800000, 1900000, 16,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/5fc0e63e-8a04-4568-8cbb-6097a1479813/W+AIR+MAX+90+FUTURA.png',
        'Air Max 90 Futura biến tấu hiện đại của dòng Air Max 90 kinh điển với sắc hồng ngọt ngào và đệm Air êm ái.', 1, 1, 1150, 270
    ],
    [
        36, 'AD-ULTRABOOST-W', 'Adidas Ultraboost Light Women White', 'adidas-ultraboost-light-women-white', 10, 2, 'Nữ', 3600000, 4300000, 2200000, 16,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/2e0b2be1f3a5471aa9c6ae9700db7e4a_9366/Giay_Ultraboost_Light_trang_GY9352_01_standard.jpg',
        'Giày chạy bộ phái đẹp Ultraboost Light cực kỳ mượt mà, ôm chân chuẩn xác và hỗ trợ vận động tối ưu.', 0, 1, 520, 130
    ],
    [
        37, 'AS-KAYANO30-W', 'Asics Gel-Kayano 30 Women White', 'asics-gel-kayano-30-women-white', 10, 11, 'Nữ', 4100000, 4700000, 2500000, 13,
        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800',
        'Asics Gel-Kayano 30 tích hợp hệ thống 4D GUIDANCE SYSTEM bảo vệ bàn chân chống lật cổ chân khi tập luyện.', 1, 1, 890, 210
    ],
    [
        38, 'ON-TILT-W', 'On Running Cloudtilt Women Quartz', 'on-running-cloudtilt-women-quartz', 10, 15, 'Nữ', 4500000, 5100000, 2800000, 12,
        'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',
        'On Running Cloudtilt với thiết kế siêu nhẹ, xỏ chân nhanh chóng không cần buộc dây, cảm giác êm ái tuyệt vời.', 1, 1, 740, 180
    ],
    [
        39, 'SK-DLITES-WHT', 'Skechers D\'Lites Fresh Start', 'skechers-dlites-fresh-start', 10, 12, 'Nữ', 1850000, 2200000, 1000000, 16,
        'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',
        'Skechers D\'Lites Chunky năng động với lót giày Air-Cooled Memory Foam siêu êm, giảm áp lực tối đa cho bàn chân.', 0, 1, 620, 160
    ],

    // --- GIÀY THỜI TRANG NỮ (category_id = 11) ---
    [
        40, 'CV-RUNSTAR-W', 'Converse Run Star Hike High Black', 'converse-run-star-hike-high-black', 11, 6, 'Nữ', 2600000, 3000000, 1500000, 13,
        'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dw5ea3e8e5/images/a_107/166800C_A_107X1.jpg',
        'Converse Run Star Hike đế ziczac cực ngầu, giúp hack chiều cao 5cm hiệu quả cho các cô nàng cá tính.', 0, 1, 680, 190
    ],
    [
        41, 'CV-ALLSTAR-MOVE', 'Converse Chuck Taylor All Star Move Platform', 'converse-chuck-taylor-all-star-move-platform', 11, 6, 'Nữ', 2100000, 2400000, 1200000, 13,
        'https://www.converse.com/dw/image/v2/BCZC_PRD/on/demandware.static/-/Sites-cnv-master-catalog/default/dwa57cf923/images/a_107/570256C_A_107X1.jpg',
        'Dòng All Star Move siêu nhẹ với đế bánh mì nâng chiều cao uyển chuyển, năng động năng suất cả ngày dài.', 0, 1, 840, 220
    ],
    [
        42, 'AD-GAZELLE-GRN', 'Adidas Gazelle Bold Green Women', 'adidas-gazelle-bold-green-women', 11, 2, 'Nữ', 2200000, 3200000, 1300000, 31,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/3d79d3c8e06c41e5aefaaf6200d3b0a5_9366/Giay_Gazelle_Bold_XAnh_la_IF6828_01_standard.jpg',
        'Gazelle Bold thiết kế 3 tầng đế cao cá tính, tông xanh lá lục bảo retro cực kỳ thời trang và nổi bật.', 1, 1, 2100, 520
    ],
    [
        43, 'AD-SPEZIAL-BLU', 'Adidas Handball Spezial Blue Women', 'adidas-handball-spezial-blue-women', 11, 2, 'Nữ', 2750000, 3200000, 1600000, 14,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/fb80d381395b45fa8a45af9d00a89d7a_9366/Giay_Handball_Spezial_XAnh_BD7633_01_standard.jpg',
        'Adidas Handball Spezial chất da lộn xanh denim cổ điển, biểu tượng thời trang Terracewear mốt nhất hiện nay.', 1, 1, 1850, 450
    ],
    [
        44, 'VN-AUTHENTIC-RED', 'Vans Authentic Core Classics Red', 'vans-authentic-core-classics-red', 11, 7, 'Nữ', 1450000, 1700000, 800000, 15,
        'https://images.vans.com/is/image/VansBrand/VN000EE3RED-HERO?$PDP-FULL-IMAGE$',
        'Vans Authentic sắc đỏ rực rỡ, phom dáng cổ thấp tối giản tinh tế dễ dàng mix&match trang phục dạo phố.', 0, 0, 480, 130
    ],
    [
        45, 'PM-RSX-EFEKT', 'Puma RS-X Efekt Archive White', 'puma-rs-x-efekt-archive-white', 11, 4, 'Nữ', 2700000, 3200000, 1600000, 16,
        'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/393130/01/sv01/fnd/SEA/fmt/png/RS-X-Efekt-Archive-Sneakers',
        'Puma RS-X Efekt dòng Chunky thiết kế tương lai với các mảng phối da ấn tượng cùng đế đệm Running System.', 0, 1, 620, 170
    ],

    // --- DÉP QUAI NGANG NAM (category_id = 12) ---
    [
        46, 'NK-BENASSI', 'Nike Benassi JDI Slide Black', 'nike-benassi-jdi-slide-black', 12, 1, 'Nam', 790000, 950000, 450000, 17,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/80ef498d-3a84-4b11-914d-f6690b0ec498/BENASSI+JDI.png',
        'Dép quai ngang Nike Benassi JDI quai lót bông siêu mềm, đế xốp Phylon êm ái chống trơn trượt.', 0, 0, 520, 240
    ],
    [
        47, 'AD-ADILETTE', 'Adidas Adilette Comfort Slide', 'adidas-adilette-comfort-slide', 12, 2, 'Nam', 850000, 1000000, 480000, 15,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/5bee7957a35a4e63a63eaf3400e0a1fa_9366/Dep_Adilette_Comfort_DJen_GZ5891_01_standard.jpg',
        'Dép Adidas Adilette Comfort trang bị lót lòng đệm Cloudfoam cực êm như mát xa lòng bàn chân.', 0, 1, 430, 190
    ],
    [
        48, 'PM-LEADCAT', 'Puma Leadcat 2.0 Slide Black', 'puma-leadcat-2-slide-black', 12, 4, 'Nam', 650000, 800000, 380000, 19,
        'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_1350,h_1350/global/384139/01/sv01/fnd/SEA/fmt/png/Leadcat-2.0-Slides',
        'Dép quai ngang Puma Leadcat 2.0 phom dáng thể thao tối giản, chất liệu EVA cao cấp siêu nhẹ.', 0, 0, 280, 110
    ],
    [
        49, 'CRC-MELLOW-SLD', 'Crocs Mellow Recovery Slide Black', 'crocs-mellow-recovery-slide-black', 12, 9, 'Nam', 1350000, 1600000, 800000, 16,
        'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800',
        'Dép Crocs Mellow dòng đệm nhung LiteRide nhún êm sâu giúp đôi chân thư giãn tức thì sau giờ tập thể thao.', 1, 1, 890, 230
    ],
    [
        50, 'YZY-SLIDE-BONE', 'Yeezy Slide Bone White', 'yeezy-slide-bone-white', 12, 13, 'Unisex', 3200000, 3800000, 2000000, 16,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg',
        'Yeezy Slide Bone đúc nguyên khối bọt EVA mềm mại, thiết kế răng cưa tối giản hiện đại nhất giới thời trang.', 1, 0, 3100, 720
    ],
    [
        51, 'MLB-SLIDER-MONO', 'MLB Korea Chunky Slider Monogram', 'mlb-korea-chunky-slider-monogram', 12, 10, 'Nam', 1650000, 1950000, 950000, 15,
        'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',
        'Dép quai ngang MLB Korea hoa văn Monogram cao cấp, đế cao 4cm tôn dáng và thời trang đỉnh cao.', 1, 1, 1150, 280
    ],

    // --- SANDAL NAM (category_id = 13) ---
    [
        52, 'NK-CANYON', 'Nike Canyon Sandal Black', 'nike-canyon-sandal-black', 13, 1, 'Nam', 1950000, 2300000, 1100000, 15,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/e10e1a47-3a30-4dd6-8b5c-31a16c5de6a3/NIKE+CANYON+SANDAL.png',
        'Sandal dã ngoại Nike Canyon quai dán linh hoạt, đế gai hãm ma sát cao thích hợp mọi hoạt động outdoor năng động.', 0, 1, 310, 85
    ],
    [
        53, 'BK-ARIZONA-BLK', 'Birkenstock Arizona EVA Black', 'birkenstock-arizona-eva-black', 13, 8, 'Unisex', 1250000, 1500000, 750000, 17,
        'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg',
        'Birkenstock Arizona EVA 2 quai màu đen đúc nguyên khối chống nước 100%, đệm chân Ergonomic uốn lượn thoải mái.', 1, 1, 950, 260
    ],
    [
        54, 'BK-BOSTON-TAUPE', 'Birkenstock Boston Clog Suede Taupe', 'birkenstock-boston-clog-suede-taupe', 13, 8, 'Unisex', 3800000, 4400000, 2400000, 14,
        'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw1bf636e0/560771/560771.jpg',
        'Birkenstock Boston Clog bọc da lộn Taupe sang trọng, đế bần Cork tự nhiên chuẩn mực phong cách Quiet Luxury.', 1, 1, 2100, 490
    ],
    [
        55, 'CRC-ECHO-BLK', 'Crocs Echo Clog All Black', 'crocs-echo-clog-all-black', 13, 9, 'Nam', 1950000, 2300000, 1150000, 15,
        'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',
        'Crocs Echo Clog phong cách Streetwear gai góc cá tính, quai đeo gót êm ái cùng đệm Licker-in LiteRide.', 1, 1, 1420, 330
    ],
    [
        56, 'AD-HYDROTERRA', 'Adidas Terrex Hydroterra Sandal', 'adidas-terrex-hydroterra-sandal', 13, 2, 'Nam', 1800000, 2200000, 1000000, 18,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg',
        'Sandal dã ngoại Adidas Terrex đế cao su Traxion siêu bám đường ướt, chất liệu dây đai tái chế bảo vệ môi trường.', 0, 1, 410, 110
    ],

    // --- DÉP QUAI NGANG NỮ (category_id = 14) ---
    [
        57, 'NK-VICTORI-W', 'Nike Victori One Slide Women', 'nike-victori-one-slide-women', 14, 1, 'Nữ', 750000, 900000, 420000, 17,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9f2aba3e-8e9c-4e97-9cc4-f55af88cceb1/W+NIKE+VICTORI+ONE+SLIDE.png',
        'Dép quai ngang nữ Nike Victori One lót bọt đệm êm mềm mới, quai quấn ôm sát mu bàn chân tạo sự thoải mái dịu nhẹ.', 0, 1, 450, 190
    ],
    [
        58, 'AD-ADILETTE-W', 'Adidas Adilette Aqua Slide Women', 'adidas-adilette-aqua-slide-women', 14, 2, 'Nữ', 650000, 800000, 380000, 19,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/af57441d6ba14166b2b3abfd00bd825b_9366/Dep_Adilette_Aqua_DJen_F35543_01_standard.jpg',
        'Dép đúc Adidas Adilette Aqua nhanh khô chống nước, lý tưởng đi phòng tập, đi biển hay mang ở nhà cực kỳ tiện lợi.', 0, 0, 380, 150
    ],
    [
        59, 'CRC-CLASSIC-WHT', 'Crocs Classic Clog White', 'crocs-classic-clog-white', 14, 9, 'Unisex', 1150000, 1400000, 650000, 18,
        'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?q=80&w=800',
        'Crocs Classic Clog màu trắng huyền thoại, dễ dàng gắn sticker Jibbitz thể hiện cá tính riêng độc đáo.', 1, 1, 1890, 520
    ],
    [
        60, 'YZY-FOAM-ONYX', 'Yeezy Foam Runner Onyx', 'yeezy-foam-runner-onyx', 14, 13, 'Unisex', 3500000, 4200000, 2200000, 17,
        'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/a3e6f98246f44d56965fae740103bdf3_9366/YEEZY_SLIDE_Trang_FZ5897_01_standard.jpg',
        'Yeezy Foam Runner thiết kế điêu khắc tương lai bằng bọt tảo biển EVA siêu thoáng khí và độc lạ nhất hành tinh.', 1, 1, 2600, 640
    ],
    [
        61, 'SK-ARCHFIT-SND', 'Skechers Arch Fit Horizon Sandal', 'skechers-arch-fit-horizon-sandal', 14, 12, 'Nữ', 1450000, 1750000, 800000, 17,
        'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=800',
        'Dép nữ Skechers Arch Fit được thiết kế theo phom bác sĩ bàn chân chứng nhận, hỗ trợ lòm chân giảm mỏi tối ưu.', 0, 1, 380, 110
    ],

    // --- SANDAL NỮ (category_id = 15) ---
    [
        62, 'BK-ARIZONA-WHT', 'Birkenstock Arizona EVA White', 'birkenstock-arizona-eva-white', 15, 8, 'Nữ', 1200000, 1500000, 700000, 20,
        'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw6ef8a6f8/129443/129443.jpg',
        'Birkenstock Arizona EVA tone trắng thanh lịch, chất siêu nhẹ giặt rửa thoải mái và năng động trong mọi chuyến đi.', 1, 1, 880, 240
    ],
    [
        63, 'BK-MAYARI-SLV', 'Birkenstock Mayari Birko-Flor Graceful', 'birkenstock-mayari-birko-flor-graceful', 15, 8, 'Nữ', 2400000, 2800000, 1400000, 14,
        'https://www.birkenstock.com/on/demandware.static/-/Sites-master-catalog/default/dw7f6c31bf/1013083/1013083.jpg',
        'Birkenstock Mayari xỏ ngón quai thanh mảnh duyên dáng, lót bần Cork nâng đỡ lòng bàn chân dịu dàng.', 0, 1, 620, 170
    ],
    [
        64, 'CRC-MEGACRUSH', 'Crocs Mega Crush Clog Bone', 'crocs-mega-crush-clog-bone', 15, 9, 'Nữ', 2450000, 2900000, 1500000, 16,
        'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=800',
        'Crocs Mega Crush đế nâng cao 7cm cực kỳ ấn tượng, chi tiết TPU quanh đế cá tính và quyến rũ cho phái đẹp.', 1, 1, 1750, 430
    ],
    [
        65, 'NK-OFFCOURT-ADJ', 'Nike OffCourt Adjust Slide Women', 'nike-offcourt-adjust-slide-women', 15, 1, 'Nữ', 1100000, 1350000, 650000, 19,
        'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/cf0ac7c9-43db-430f-b65c-4867c8a4e1e0/W+OFFCOURT+ADJUST+SLIDE.png',
        'Dép Nike OffCourt Adjust có quai dán điều chỉnh độ rộng linh hoạt, lót Revive Foam 2 lớp vô cùng thoải mái.', 0, 1, 290, 90
    ],
    [
        66, 'MLB-SANDAL-MONO', 'MLB Korea Chunky Sandal Monogram', 'mlb-korea-chunky-sandal-monogram', 15, 10, 'Nữ', 2350000, 2800000, 1300000, 16,
        'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=800',
        'Sandal nữ MLB Korea đệm quai êm mềm, đế răng cưa cao 5cm tôn nét sang chảnh và hiện đại cho phái nữ.', 1, 1, 920, 250
    ]
];

$stmt_p = $conn->prepare("INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `category_id`, `brand_id`, `gender`, `price`, `old_price`, `cost_price`, `discount_percent`, `main_image`, `description`, `is_hot`, `is_new`, `view_count`, `sold_count`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

foreach ($products as $p) {
    $stmt_p->bind_param("isssiisdddissiiii",
        $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10], $p[11], $p[12], $p[13], $p[14], $p[15], $p[16]
    );
    $stmt_p->execute();
}
echo "✓ Đã thêm " . count($products) . " sản phẩm giày dép thực tế.\n";

// 4. TỰ ĐỘNG PHÁT SINH BIẾN THỂ SIZE & TỒN KHO CHO MỖI SẢN PHẨM
$stmt_v = $conn->prepare("INSERT INTO `product_variants` (`product_id`, `size`, `color`, `stock_quantity`) VALUES (?, ?, ?, ?)");

$color_map = [
    'White' => 'Trắng',
    'Black' => 'Đen',
    'Grey' => 'Xám',
    'Silver' => 'Bạc',
    'Pink' => 'Hồng',
    'Blue' => 'Xanh',
    'Red' => 'Đỏ',
    'Green' => 'Xanh Lá',
    'Taupe' => 'Nâu Tây'
];

$variant_count = 0;
foreach ($products as $p) {
    $prod_id = $p[0];
    $gender  = $p[6];
    $name    = $p[2];

    // Determine sizes based on gender
    if ($gender === 'Nữ') {
        $sizes = ['36', '37', '38', '39', '40'];
    } elseif ($gender === 'Nam') {
        $sizes = ['39', '40', '41', '42', '43', '44'];
    } else {
        $sizes = ['36', '37', '38', '39', '40', '41', '42', '43'];
    }

    // Determine color
    $color = 'Phối Màu';
    foreach ($color_map as $eng => $vie) {
        if (stripos($name, $eng) !== false || stripos($name, $vie) !== false) {
            $color = $vie;
            break;
        }
    }

    foreach ($sizes as $idx => $sz) {
        $stock = rand(5, 25);
        if ($idx === count($sizes) - 1 && rand(0, 1) === 0) {
            $stock = 0; // occasional out of stock for realistic UI testing
        }
        $stmt_v->bind_param("issi", $prod_id, $sz, $color, $stock);
        $stmt_v->execute();
        $variant_count++;
    }
}
echo "✓ Đã tạo $variant_count biến thể kích thước (Size 36-44) và tồn kho.\n";

// 5. TẠO HÌNH ẢNH NHIỀU GÓC ĐỘ (product_images) CHO CÁC SẢN PHẨM KHÁC NHAU
$stmt_img = $conn->prepare("INSERT INTO `product_images` (`product_id`, `image_url`, `sort_order`) VALUES (?, ?, ?)");
$img_count = 0;

foreach ($products as $p) {
    $prod_id = $p[0];
    $main_img = $p[11];
    
    // Always add main image as order 1
    $sort_1 = 1;
    $stmt_img->bind_param("isi", $prod_id, $main_img, $sort_1);
    $stmt_img->execute();
    $img_count++;

    // Add extra multi-angle images
    if ($prod_id == 1) { // Nike AF1
        $extra_imgs = [
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/005e8105-ffad-4e50-94d3-e7f09f061266/AIR+FORCE+1+%2707.png',
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/9a83eb9e-a0e2-41a2-9447-4a008c2a95c9/AIR+FORCE+1+%2707.png',
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/96d03d09-4081-4200-84cf-23579bcf3c95/AIR+FORCE+1+%2707.png'
        ];
    } elseif ($prod_id == 2) { // Nike Dunk Low Panda
        $extra_imgs = [
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/a14704fb-2231-4a1d-a99f-bbd75605d8f6/NIKE+DUNK+LOW+RETRO.png',
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/34dfa8b1-3829-450f-bb08-8f5b40cf326e/NIKE+DUNK+LOW+RETRO.png',
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/75b81a7b-0d04-4530-9b4a-a3a8309b85c1/NIKE+DUNK+LOW+RETRO.png'
        ];
    } elseif ($prod_id == 3) { // Adidas Samba OG
        $extra_imgs = [
            'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/6b763ec253454b52b217a8bf011894d8_9366/Giay_Samba_OG_trang_B75806_02_standard_hover.jpg',
            'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/815915d3fa78486ca9c2a8bf0118a803_9366/Giay_Samba_OG_trang_B75806_04_standard.jpg',
            'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/01f80ef307614d3ca976a8bf0118ca21_9366/Giay_Samba_OG_trang_B75806_41_detail.jpg'
        ];
    } elseif ($prod_id == 19) { // Jordan 1 Chicago
        $extra_imgs = [
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/032fcfc5-d72b-426c-85fa-7fcf1dd12781/AIR+JORDAN+1+RETRO+HIGH+OG.png',
            'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/7d363d66-ebbe-4835-9fa8-1f19fbb1c7a5/AIR+JORDAN+1+RETRO+HIGH+OG.png'
        ];
    } else {
        $extra_imgs = [
            $main_img
        ];
    }

    $sort = 2;
    foreach ($extra_imgs as $e_url) {
        $stmt_img->bind_param("isi", $prod_id, $e_url, $sort);
        $stmt_img->execute();
        $img_count++;
        $sort++;
    }
}
echo "✓ Đã thêm $img_count hình ảnh chi tiết góc độ sản phẩm.\n";

// Re-enable FK checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\n=== HOÀN TẤT NẠP DỮ LIỆU CSDL THÀNH CÔNG ===\n";
