<?php
require_once 'includes/header.php';

// Fetch settings
$settings_query = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$site_settings = [];
if ($settings_query) {
    while ($row = $settings_query->fetch_assoc()) {
        $site_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Fetch Hero Banner
$banners_query = $conn->query("SELECT * FROM banners WHERE position='hero' AND status=1 ORDER BY sort_order ASC LIMIT 1");
$hero_banner = $banners_query ? $banners_query->fetch_assoc() : null;

// Fetch Top 7 Best Seller Shoes for 3D Skewed Accordion
$hero_shoes = $conn->query("
    SELECT p.*, COALESCE(b.name, 'Chưa phân hãng') AS brand_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.status = 1 
    ORDER BY p.sold_count DESC, p.id DESC LIMIT 7
");

// Fetch All Active Brands for Static Grid and Brand Showcase Blocks
$brands_res = $conn->query("SELECT * FROM brands WHERE status=1 ORDER BY id ASC");
$all_brands = [];
if ($brands_res) {
    while ($b = $brands_res->fetch_assoc()) {
        $all_brands[] = $b;
    }
}

// Fetch Vouchers (Homepage featured + All for modal - Chỉ lấy voucher chung & người mới, voucher ngày lễ/sự kiện chỉ nằm ở trang Sự kiện)
$vouchers_query = $conn->query("SELECT v.*, b.name AS brand_name FROM vouchers v LEFT JOIN brands b ON v.brand_id = b.id WHERE v.status=1 AND (v.end_date IS NULL OR v.end_date > NOW()) AND (v.event_type != 'holiday' AND (v.sale_event_id IS NULL OR v.sale_event_id = 0)) ORDER BY v.id DESC LIMIT 4");
$all_vouchers_query = $conn->query("SELECT v.*, b.name AS brand_name FROM vouchers v LEFT JOIN brands b ON v.brand_id = b.id WHERE v.status=1 AND (v.end_date IS NULL OR v.end_date > NOW()) AND (v.event_type != 'holiday' AND (v.sale_event_id IS NULL OR v.sale_event_id = 0)) ORDER BY v.id DESC");

$user_saved_voucher_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $uv_res = $conn->query("SELECT voucher_id FROM user_vouchers WHERE user_id=$uid AND used_at IS NULL");
    if ($uv_res) {
        while ($uv = $uv_res->fetch_assoc()) {
            $user_saved_voucher_ids[] = intval($uv['voucher_id']);
        }
    }
}

// Voucher banner settings
$v_banner_img = $site_settings['voucher_banner_img'] ?? 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=1000';
$v_banner_title = $site_settings['voucher_banner_title'] ?? 'SĂN VOUCHER ƯU ĐÃI SỐC 2026';
$v_banner_subtitle = $site_settings['voucher_banner_subtitle'] ?? 'Lưu mã ngay vào tài khoản của bạn để nhận chiết khấu và freeship cực lớn!';
$v_banner_link = $site_settings['voucher_banner_link'] ?? 'all-products.php';

// Fetch active sale events for homepage banner
$hp_events = [];
$evt_tbl_check = $conn->query("SHOW TABLES LIKE 'sale_events'");
if ($evt_tbl_check && $evt_tbl_check->num_rows > 0) {
    $evt_q = $conn->query("SELECT * FROM sale_events WHERE status=1 AND (show_on_homepage_banner=1 OR show_on_homepage_banner IS NULL) AND start_date<=NOW() AND end_date>=NOW() ORDER BY sort_order ASC, id DESC");
    if ($evt_q) while ($row = $evt_q->fetch_assoc()) $hp_events[] = $row;
}

// Fetch Top 7 Bestselling products based on actual sold_count
$hot_products_query = $conn->query("
    SELECT p.*, COALESCE(b.name, 'Chưa phân hãng') AS brand_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.status=1 
    ORDER BY p.sold_count DESC, p.view_count DESC, p.id DESC LIMIT 7
");

// Fetch NEW products
$new_products_query = $conn->query("
    SELECT p.*, COALESCE(b.name, 'Chưa phân hãng') AS brand_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.is_new=1 AND p.status=1 
    ORDER BY p.id DESC LIMIT 8
");

// Fetch SALE products
$sale_products_query = $conn->query("
    SELECT p.*, COALESCE(b.name, 'Chưa phân hãng') AS brand_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.discount_percent > 0 AND p.status=1 
    ORDER BY p.discount_percent DESC LIMIT 8
");

// Fetch Wishlist IDs if logged in
$wishlist_product_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $wishlist_query = $conn->query("SELECT product_id FROM wishlists WHERE user_id=$uid");
    if ($wishlist_query) {
        while ($w = $wishlist_query->fetch_assoc()) {
            $wishlist_product_ids[] = $w['product_id'];
        }
    }
}
?>

<style>
    :root {
        --primary-soft: #3b7a70;
        --primary-dark-soft: #234842;
        --accent-gold: #c59b6c;
        --bg-cream: #f8f9fa;
        --card-white: #ffffff;
    }
    body {
        background-color: var(--bg-cream);
        font-family: 'Inter', 'Segoe UI', sans-serif;
        max-width: 100%;
        overflow-x: hidden !important;
    }
    .hero-section {
        background: linear-gradient(135deg, #2b5b54 0%, #1d3b36 100%);
        color: white;
        padding: 50px 0;
        position: relative;
        overflow: hidden;
    }
    .hero-title {
        color: var(--accent-gold);
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 15px;
    }
    .hero-btn {
        background-color: var(--accent-gold);
        color: #ffffff;
        font-weight: bold;
        border: none;
        padding: 12px 30px;
        transition: all 0.3s ease;
    }
    .hero-btn:hover {
        background-color: white;
        color: var(--primary-dark-soft);
    }
    .floating-shoe {
        animation: float 4s ease-in-out infinite;
        max-width: 100%;
        max-height: 280px;
        filter: drop-shadow(0 20px 30px rgba(0,0,0,0.3));
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(-5deg); }
    }

    /* Dải chữ chạy (Marquee) xoay vòng khuyến mãi */
    .announcement-bar {
        background: #1f3833;
        color: #f1f5f9;
        overflow: hidden;
        white-space: nowrap;
        border-top: 1px solid rgba(197, 155, 108, 0.3);
        border-bottom: 2px solid var(--accent-gold);
        padding: 10px 0;
    }
    .announcement-track {
        display: inline-flex;
        animation: textMarquee 30s linear infinite;
        gap: 50px;
    }
    .announcement-track:hover {
        animation-play-state: paused;
    }
    @keyframes textMarquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    
    /* === TOP 7 BÁN CHẠY — Full-bleed Breakout Accordion === */
    .shoe-breakout-section {
        position: relative;
        background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 40%, #0c2a3a 100%);
        padding: 0;
        overflow: visible;
    }
    .shoe-breakout-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(600px circle at 20% 30%, rgba(124,58,237,0.15), transparent 45%),
            radial-gradient(500px circle at 80% 70%, rgba(6,182,212,0.12), transparent 40%);
        pointer-events: none;
    }
    .shoe-breakout-header {
        padding: 30px 0 20px;
        position: relative;
        z-index: 2;
    }
    .shoe-breakout-strip {
        display: flex;
        height: 420px;
        width: 100%;
        gap: 0;
        position: relative;
        z-index: 1;
    }
    .shoe-panel {
        position: relative;
        flex: 1;
        height: 100%;
        overflow: visible;
        cursor: pointer;
        transition: flex 0.55s cubic-bezier(0.22, 0.68, 0, 1.0);
        clip-path: polygon(12% 0%, 100% 0%, 88% 100%, 0% 100%);
        margin-left: -20px;
    }
    .shoe-panel:first-child {
        margin-left: 0;
        clip-path: polygon(0% 0%, 100% 0%, 88% 100%, 0% 100%);
    }
    .shoe-panel:last-child {
        clip-path: polygon(12% 0%, 100% 0%, 100% 100%, 0% 100%);
    }

    /* Nền panel sản phẩm: Bình thường hiển thị SÁNG VÀ RÕ NÉT (brightness 0.92, contrast 1.08) */
    .shoe-panel-bg {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        transition: transform 0.5s ease, filter 0.5s ease, opacity 0.5s ease;
        filter: brightness(0.92) contrast(1.08) saturate(1.15);
    }
    .shoe-panel:hover .shoe-panel-bg {
        transform: scale(1.1);
        filter: brightness(0.35) contrast(1.1) blur(3px);
    }

    /* Ảnh giày: Khi chưa lia chuột vào thì KHÔNG hiển thị (opacity: 0). Khi lia chuột vào mới hiển thị (opacity: 1) xoay nghiêng & lòi lên */
    .shoe-panel-shoe {
        position: absolute;
        bottom: 5px;
        left: 50%;
        width: 85%;
        max-height: 80%;
        object-fit: contain;
        transform: translateX(-50%) rotate(0deg) scale(0.7);
        filter: drop-shadow(0 8px 15px rgba(0,0,0,0.5));
        transition: transform 0.5s cubic-bezier(0.22, 0.68, 0, 1.0), filter 0.5s ease, opacity 0.4s ease;
        z-index: 5;
        opacity: 0;
        pointer-events: none;
    }
    .shoe-panel:hover .shoe-panel-shoe {
        transform: translateX(-50%) rotate(-22deg) scale(1.25) translateY(-35px);
        filter: drop-shadow(0 30px 50px rgba(0,0,0,0.8)) drop-shadow(0 0 25px rgba(197,155,108,0.5));
        opacity: 1;
    }

    /* Tên + Giá chỉ hiện khi hover */
    .shoe-panel-info {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        text-align: center;
        z-index: 6;
        opacity: 0;
        transition: all 0.4s ease 0.1s;
        white-space: nowrap;
    }
    .shoe-panel:hover .shoe-panel-info {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    .shoe-panel-price {
        font-size: 1.3rem;
        font-weight: 800;
        color: #fff;
        text-shadow: 0 2px 15px rgba(0,0,0,0.8);
    }

    /* Hover mở rộng / thu hẹp các panel xung quanh */
    .shoe-breakout-strip:hover .shoe-panel {
        flex: 0.5;
    }
    .shoe-breakout-strip .shoe-panel:hover {
        flex: 3;
    }

    /* === RESPONSIVE === */
    @media (max-width: 1199px) {
        .shoe-breakout-strip { height: 380px; }
        .shoe-panel-shoe { width: 90%; }
    }
    @media (max-width: 991px) {
        .shoe-breakout-strip { height: 340px; }
        .shoe-panel { clip-path: polygon(8% 0%, 100% 0%, 92% 100%, 0% 100%); margin-left: -12px; }
        .shoe-panel:first-child { clip-path: polygon(0% 0%, 100% 0%, 92% 100%, 0% 100%); }
        .shoe-panel:last-child { clip-path: polygon(8% 0%, 100% 0%, 100% 100%, 0% 100%); }
        .shoe-panel-shoe { transform: translateX(-50%) rotate(0deg) scale(0.7); }
        .shoe-panel:hover .shoe-panel-shoe { transform: translateX(-50%) rotate(-18deg) scale(1.15) translateY(-25px); opacity: 1; }
    }
    @media (max-width: 767px) {
        .shoe-breakout-strip {
            height: 280px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            gap: 8px;
            padding: 0 15px;
        }
        .shoe-breakout-strip::-webkit-scrollbar { height: 3px; }
        .shoe-breakout-strip::-webkit-scrollbar-thumb { background: rgba(197,155,108,0.6); border-radius: 3px; }
        .shoe-panel {
            min-width: 200px;
            flex: 0 0 200px !important;
            clip-path: none !important;
            margin-left: 0 !important;
            border-radius: 16px;
            overflow: hidden;
            scroll-snap-align: start;
        }
        .shoe-breakout-strip:hover .shoe-panel { flex: 0 0 200px !important; }
        .shoe-panel-shoe {
            opacity: 0;
            transform: translateX(-50%) rotate(0deg) scale(0.75);
        }
        .shoe-panel:hover .shoe-panel-shoe,
        .shoe-panel:active .shoe-panel-shoe {
            opacity: 1;
            transform: translateX(-50%) rotate(-15deg) scale(1.08) translateY(-12px);
        }
        .shoe-panel-info { opacity: 1; transform: translateX(-50%) translateY(0); }
    }

    /* Brand Infinite Marquee Carousel (Chạy Vòng Tròn & Khung Bằng Nhau) */
    .brand-marquee-container {
        overflow: hidden;
        width: 100%;
        position: relative;
        padding: 10px 0;
        mask-image: linear-gradient(to right, transparent 0%, black 4%, black 96%, transparent 100%);
        -webkit-mask-image: linear-gradient(to right, transparent 0%, black 4%, black 96%, transparent 100%);
    }
    .brand-marquee-track {
        display: flex;
        gap: 20px;
        width: max-content;
        animation: brandLoop 25s linear infinite;
    }
    .brand-marquee-track:hover {
        animation-play-state: paused;
    }
    @keyframes brandLoop {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .brand-marquee-card {
        flex: 0 0 210px;
        width: 210px;
        height: 140px;
        background: #ffffff;
        border-radius: 18px;
        padding: 18px 18px 14px 18px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
        transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .brand-marquee-card:hover {
        transform: translateY(-7px) scale(1.04);
        border-color: var(--accent-gold);
        box-shadow: 0 14px 32px rgba(197, 155, 108, 0.3);
    }
    .brand-marquee-card img {
        width: 100%;
        max-height: 70px;
        object-fit: contain;
        transition: transform 0.3s ease;
    }
    .brand-marquee-card:hover img {
        transform: scale(1.08);
    }
    .brand-marquee-card .brand-card-name {
        font-size: 14px;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        transition: color 0.3s ease;
    }
    .brand-marquee-card:hover .brand-card-name {
        color: var(--primary-soft);
    }

    /* ===== BRAND GAME SHOWCASE — phong cách giới thiệu nhân vật game ===== */
    .brand-showcase-box {
        background: #0d1117;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.06);
        box-shadow: 0 25px 70px rgba(0,0,0,0.35);
        padding: 0;
        margin-bottom: 70px;
        margin-top: 20px;
        overflow: visible; /* cho phép giày trào ra */
        position: relative;
    }

    /* Panel chính: panel tối chứa 3 cột */
    .brand-game-panel {
        display: flex;
        min-height: 270px;
        position: relative;
        overflow: visible;
        border-radius: 20px 20px 0 0;
    }

    /* Cột trái: thông tin thương hiệu */
    .brand-game-left {
        flex: 0 0 210px;
        background: linear-gradient(160deg, var(--accent-gold) 0%, #d4a24a 60%, #b8882e 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 35px 28px;
        position: relative;
        z-index: 2;
        clip-path: polygon(0 0, 88% 0, 100% 100%, 0 100%);
        opacity: 0;
        transform: translateX(-80px);
        transition: opacity 0.75s cubic-bezier(0.22,0.68,0,1.05), transform 0.75s cubic-bezier(0.22,0.68,0,1.05);
    }
    .brand-game-left.slide-in { opacity: 1; transform: translateX(0); }

    .brand-game-num {
        font-size: 5rem;
        font-weight: 900;
        color: rgba(0,0,0,0.12);
        line-height: 1;
        letter-spacing: -4px;
        margin-bottom: 4px;
        font-family: 'Impact', sans-serif;
    }
    .brand-game-label {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 3px;
        color: rgba(0,0,0,0.5);
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .brand-game-name {
        font-size: 1.5rem;
        font-weight: 900;
        color: #1a0800;
        text-transform: uppercase;
        letter-spacing: 2px;
        line-height: 1.1;
        margin-bottom: 14px;
    }
    .brand-game-logo {
        max-height: 36px;
        max-width: 90px;
        object-fit: contain;
        filter: brightness(0) opacity(0.45);
    }

    /* Cột giữa: giày breakout */
    .brand-game-center {
        flex: 0 0 300px;
        position: relative;
        overflow: visible;
        z-index: 10;
        background: transparent;
    }
    /* Nền tối tím cho cột giữa */
    .brand-game-center::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, #0a0a1a 0%, #111827 100%);
        /* Sọc chéo trang trí */
        background-image:
            linear-gradient(180deg, #0a0a1a 0%, #111827 100%),
            repeating-linear-gradient(
                -52deg,
                transparent 0, transparent 12px,
                rgba(255,255,255,0.025) 12px, rgba(255,255,255,0.025) 24px
            );
        background-blend-mode: normal, overlay;
        pointer-events: none;
    }

    /* Giày vươn lên khỏi khung */
    .brand-game-shoe {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        height: 360px; /* cao hơn panel 90px → breakout */
        width: auto;
        max-width: 290px;
        object-fit: contain;
        z-index: 15;
        filter:
            drop-shadow(-8px -12px 30px rgba(197,155,108,0.35))
            drop-shadow(0 25px 45px rgba(0,0,0,0.95));
        transition:
            transform 0.5s cubic-bezier(0.22,0.68,0,1.05),
            opacity 0.35s ease,
            filter 0.4s ease;
        cursor: pointer;
    }
    .brand-game-shoe:hover {
        transform: translateX(-50%) translateY(-14px) rotate(-6deg) scale(1.06);
        filter:
            drop-shadow(-12px -18px 45px rgba(197,155,108,0.55))
            drop-shadow(0 35px 55px rgba(0,0,0,0.95));
    }
    /* Ánh sáng hào quang dưới giày */
    .brand-game-glow {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 180px;
        height: 18px;
        background: radial-gradient(ellipse, rgba(197,155,108,0.6) 0%, transparent 70%);
        z-index: 12;
        pointer-events: none;
        border-radius: 50%;
    }

    /* Cột phải: mô tả collection */
    .brand-game-right {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 35px 40px;
        position: relative;
        z-index: 2;
        background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
        opacity: 0;
        transform: translateX(80px);
        transition: opacity 0.75s cubic-bezier(0.22,0.68,0,1.05) 0.15s, transform 0.75s cubic-bezier(0.22,0.68,0,1.05) 0.15s;
        border-radius: 0 20px 0 0;
    }
    .brand-game-right.slide-in { opacity: 1; transform: translateX(0); }

    .brand-game-badge {
        display: inline-block;
        background: linear-gradient(90deg, var(--accent-gold), #d4a24a);
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        padding: 5px 14px;
        border-radius: 30px;
        margin-bottom: 14px;
    }
    .brand-game-title {
        font-size: 1.75rem;
        font-weight: 900;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 12px;
        line-height: 1.15;
    }
    .brand-game-desc {
        font-size: 0.87rem;
        color: rgba(255,255,255,0.5);
        line-height: 1.75;
        margin-bottom: 16px;
    }
    .brand-game-price {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--accent-gold);
        letter-spacing: 0.5px;
    }

    /* Footer: thumbnail + nút */
    .brand-game-footer {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 24px 20px;
        background: #080c14;
        border-radius: 0 0 20px 20px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    .brand-thumb-arrow {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.7);
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.25s ease;
        flex-shrink: 0;
        font-size: 12px;
    }
    .brand-thumb-arrow:hover {
        background: var(--accent-gold);
        border-color: var(--accent-gold);
        color: #fff;
    }
    .brand-thumbs-strip {
        display: flex;
        gap: 8px;
        flex: 1;
        overflow: hidden;
    }
    .brand-thumb {
        flex: 0 0 56px;
        width: 56px;
        height: 56px;
        border-radius: 10px;
        border: 2px solid rgba(255,255,255,0.08);
        overflow: hidden;
        cursor: pointer;
        transition: all 0.28s ease;
        opacity: 0.45;
        background: #1a1a2e;
    }
    .brand-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    .brand-thumb:hover { opacity: 0.8; border-color: rgba(197,155,108,0.6); transform: translateY(-3px); }
    .brand-thumb.active { opacity: 1; border-color: var(--accent-gold); box-shadow: 0 0 14px rgba(197,155,108,0.5); transform: translateY(-4px); }
    .brand-thumb:hover img, .brand-thumb.active img { transform: scale(1.1); }

    .brand-game-btn {
        background: linear-gradient(90deg, var(--accent-gold), #d4a24a);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        padding: 10px 24px;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        white-space: nowrap;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(197,155,108,0.3);
    }
    .brand-game-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(197,155,108,0.45); color: #fff; }

    /* Đảo chiều (reverse) */
    .brand-showcase-box.reverse .brand-game-panel { flex-direction: row-reverse; }
    .brand-showcase-box.reverse .brand-game-left {
        clip-path: polygon(12% 0, 100% 0, 100% 100%, 0 100%);
        transform: translateX(80px);
    }
    .brand-showcase-box.reverse .brand-game-left.slide-in { transform: translateX(0); }
    .brand-showcase-box.reverse .brand-game-right {
        transform: translateX(-80px);
        border-radius: 20px 0 0 0;
    }
    .brand-showcase-box.reverse .brand-game-right.slide-in { transform: translateX(0); }
    .brand-showcase-box.reverse .brand-game-footer { flex-direction: row-reverse; }

    /* Sản phẩm nổi lên */
    .brand-products-row > .col-product {
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .brand-products-row.products-visible > .col-product:nth-child(1) { opacity:1; transform:none; transition-delay:0s; }
    .brand-products-row.products-visible > .col-product:nth-child(2) { opacity:1; transform:none; transition-delay:0.12s; }
    .brand-products-row.products-visible > .col-product:nth-child(3) { opacity:1; transform:none; transition-delay:0.24s; }
    .brand-products-row.products-visible > .col-product:nth-child(4) { opacity:1; transform:none; transition-delay:0.36s; }

    /* Product row padding bên trong dark box */
    .brand-game-products { padding: 24px 24px 0; }

    @media (max-width: 991px) {
        .brand-game-center { flex: 0 0 220px; }
        .brand-game-shoe { height: 280px; max-width: 210px; }
        .brand-game-left { flex: 0 0 170px; }
        .brand-game-title { font-size: 1.3rem; }
    }
    @media (max-width: 767px) {
        .brand-game-panel { flex-direction: column !important; border-radius: 20px 20px 0 0; }
        .brand-game-left { flex: none; clip-path: none !important; padding: 22px; transform: none !important; opacity: 1; }
        .brand-game-center { flex: none; min-height: 220px; }
        .brand-game-shoe { height: 240px; max-width: 200px; }
        .brand-game-right { flex: none; padding: 22px; border-radius: 0 !important; transform: none !important; opacity: 1; }
        .brand-game-footer { flex-wrap: wrap; }
        .brand-showcase-box { margin-top: 0; }
    }

    .section-title {
        text-align: center;
        margin-bottom: 35px;
        font-weight: 800;
        color: #2b5b54;
        position: relative;
        padding-bottom: 15px;
        text-uppercase;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--accent-gold);
    }

    /* ===== VOUCHER SECTION (BANNER + CARD DEALING + LƯU TÀI KHOẢN) ===== */
    .voucher-section-wrapper {
        background: linear-gradient(135deg, #0b1120 0%, #111827 100%);
        border-radius: 24px;
        padding: 32px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        position: relative;
    }

    .voucher-banner-box {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        min-height: 380px;
        height: 100%;
        background-size: cover;
        background-position: center center;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 30px;
        border: 1px solid rgba(255,255,255,0.12);
        box-shadow: 0 12px 35px rgba(0,0,0,0.35);
    }
    .voucher-banner-box::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(11,17,32,0.15) 0%, rgba(11,17,32,0.92) 100%);
        z-index: 1;
    }
    .voucher-banner-content {
        position: relative;
        z-index: 2;
    }

    /* Container Thẻ Chia Bài (Card Dealing) */
    .voucher-deal-container {
        position: relative;
        perspective: 1000px;
        min-height: 320px;
    }

    .voucher-deal-card {
        position: relative;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-left: 5px solid #ec4899;
        border-radius: 16px;
        padding: 18px 22px;
        margin-bottom: 14px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        /* 3D Card Dealing Animation initial state */
        opacity: 0;
        transform: translateY(-70px) rotate(-8deg) scale(0.85);
        transform-origin: top center;
        transition: opacity 0.65s ease, transform 0.65s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Animation khi chia bài */
    .voucher-deal-container.dealt .voucher-deal-card {
        opacity: 1;
        transform: translateY(0) rotate(0deg) scale(1);
    }

    /* Đánh số thứ tự chia bài (Staggered deal) */
    .voucher-deal-container.dealt .voucher-deal-card:nth-child(1) { transition-delay: 0.1s; }
    .voucher-deal-container.dealt .voucher-deal-card:nth-child(2) { transition-delay: 0.25s; }
    .voucher-deal-container.dealt .voucher-deal-card:nth-child(3) { transition-delay: 0.4s; }
    .voucher-deal-container.dealt .voucher-deal-card:nth-child(4) { transition-delay: 0.55s; }

    .voucher-deal-card:hover {
        transform: translateY(-4px) scale(1.015) !important;
        border-color: #f9a8d4;
        box-shadow: 0 15px 35px rgba(236,72,153,0.25);
    }

    .btn-save-voucher.saved {
        background: #10b981 !important;
        border-color: #10b981 !important;
        color: #ffffff !important;
        opacity: 0.95;
        cursor: default;
    }

    /* ===== HIỆU ỨNG XÉ VÉ (TEAR / RIP ANIMATION) ===== */
    .voucher-deal-card.ripping {
        position: relative;
        pointer-events: none;
        animation: ripShake 0.25s ease-in-out forwards;
    }
    .voucher-deal-card.ripping::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        right: 140px;
        width: 3px;
        background: repeating-linear-gradient(
            to bottom,
            transparent 0, transparent 4px,
            #f9a8d4 4px, #f9a8d4 8px
        );
        z-index: 20;
        box-shadow: 0 0 8px rgba(249,168,212,0.8);
    }
    @keyframes ripShake {
        0% { transform: scale(1) rotate(0deg); }
        25% { transform: scale(1.02) rotate(-1.5deg) translateX(-4px); }
        50% { transform: scale(1.02) rotate(1.5deg) translateX(4px); }
        75% { transform: scale(1.01) rotate(-1deg); }
        100% { transform: scale(1) rotate(0deg); }
    }
    .voucher-deal-card.rip-away {
        animation: ripAwayTear 0.55s cubic-bezier(0.55, 0.085, 0.68, 0.53) forwards !important;
    }
    @keyframes ripAwayTear {
        0% {
            opacity: 1;
            transform: scale(1) translateY(0) rotate(0deg);
        }
        30% {
            opacity: 0.9;
            transform: scale(0.98) translateY(-12px) rotate(-4deg);
        }
        100% {
            opacity: 0;
            transform: scale(0.65) translateY(120px) rotate(22deg);
    }
    .voucher-deal-card.slide-in-new {
        animation: slideInNew 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards !important;
    }
    @keyframes slideInNew {
        0% { opacity: 0; transform: translateY(40px) scale(0.9); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    .service-card {
        background: var(--card-white);
        padding: 25px 20px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        height: 100%;
        transition: transform 0.3s;
        border: 1px solid #e2e8f0;
    }
    .service-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-gold);
    }
    .service-icon {
        font-size: 2.5rem;
        color: var(--accent-gold);
        margin-bottom: 15px;
    }

    /* === SCROLL REVEAL ANIMATION === */
    .reveal {
        opacity: 0;
        transform: translateY(48px);
        transition: opacity 0.7s cubic-bezier(0.22,0.68,0,1.0), transform 0.7s cubic-bezier(0.22,0.68,0,1.0);
    }
    .reveal.reveal-left {
        transform: translateX(-60px);
    }
    .reveal.reveal-right {
        transform: translateX(60px);
    }
    .reveal.reveal-scale {
        transform: scale(0.88);
    }
    .reveal.visible {
        opacity: 1;
        transform: translateY(0) translateX(0) scale(1);
    }
    .reveal-stagger > * {
        opacity: 0;
        transform: translateY(36px);
        transition: opacity 0.6s cubic-bezier(0.22,0.68,0,1.0), transform 0.6s cubic-bezier(0.22,0.68,0,1.0);
    }
    .reveal-stagger.visible > *:nth-child(1) { transition-delay: 0s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(2) { transition-delay: 0.1s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(3) { transition-delay: 0.2s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(4) { transition-delay: 0.3s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(5) { transition-delay: 0.4s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(6) { transition-delay: 0.5s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(7) { transition-delay: 0.6s; opacity: 1; transform: none; }
    .reveal-stagger.visible > *:nth-child(8) { transition-delay: 0.7s; opacity: 1; transform: none; }
</style>

<!-- 1. HERO BANNER CHÍNH -->
<section class="hero-section">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 text-uppercase mb-3"><?= htmlspecialchars($hero_banner['badge_text'] ?? 'Siêu Phẩm Sneaker 2026') ?></span>
                <h1 class="hero-title display-4"><?= htmlspecialchars($hero_banner['title'] ?? 'BỨT PHÁ PHONG CÁCH') ?></h1>
                <p class="lead mb-4 opacity-75"><?= htmlspecialchars($hero_banner['subtitle'] ?? 'Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi giảm đến 33% hôm nay!') ?></p>
                <a href="<?= htmlspecialchars($hero_banner['link_url'] ?? 'all-products.php') ?>" class="btn hero-btn btn-lg rounded-pill shadow">
                    <?= htmlspecialchars($hero_banner['button_text'] ?? 'MUA SẮM NGAY') ?> <i class="fa-solid fa-cart-shopping ms-2"></i>
                </a>
            </div>
            <div class="col-lg-6 text-center">
                <img src="<?= htmlspecialchars($hero_banner['image_url'] ?? 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800') ?>" alt="Hero Image" class="img-fluid floating-shoe rounded-4">
            </div>
        </div>
    </div>
</section>

<!-- DẢI CHỮ CHẠY XOAY VÒNG KHUYẾN MÃI (MARQUEE TEXT BAR) -->
<?php 
$raw_marquee = $site_settings['marquee_text'] ?? '🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN TỪ 500.000Đ | 🎁 MÃ WELCOME50K GIẢM NGAY 50K CHO TÀI KHOẢN MỚI | 🏆 CAM KẾT 100% SẢN PHẨM CHÍNH HÃNG AUTHENTIC | ⚡ FLASH SALE GIẢM GIÁ ĐẾN 33% TẤT CẢ SẢN PHẨM HOT 2026 | 🔁 HỖ TRỢ ĐỔI TRẢ 30 NGÀY NẾU LỖI SẢN PHẨM';
$marquee_items = array_filter(array_map('trim', explode('|', $raw_marquee)));
?>
<div class="announcement-bar">
    <div class="announcement-track">
        <?php for($repeat=0; $repeat<3; $repeat++): ?>
            <?php foreach($marquee_items as $m_item): ?>
                <span class="fw-bold text-uppercase" style="font-size: 13px;"><?= htmlspecialchars($m_item) ?></span>
                <span class="text-warning">✦</span>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>
<!-- EVENT BANNERS CAROUSEL SLIDER (Banner Trượt Sự Kiện Khuyến Mãi) -->
<?php if (!empty($hp_events)): ?>
<section class="position-relative overflow-hidden my-3" style="background: #080c16;">
    <div class="container-fluid px-3 px-md-4">
        <div id="eventBannerCarousel" class="carousel slide carousel-fade shadow-lg rounded-4 overflow-hidden border border-white border-opacity-10" data-bs-ride="carousel" data-bs-interval="4500">
            <?php if (count($hp_events) > 1): ?>
            <div class="carousel-indicators mb-2">
                <?php foreach($hp_events as $idx => $ev): ?>
                    <button type="button" data-bs-target="#eventBannerCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $idx + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="carousel-inner">
                <?php foreach($hp_events as $idx => $ev): 
                    $ev_img = !empty($ev['banner_image']) ? $ev['banner_image'] : (!empty($ev['hero_banner_image']) ? $ev['hero_banner_image'] : '');
                    $ev_color = htmlspecialchars($ev['color_theme'] ?? '#ef4444');
                ?>
                <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                    <div style="position:relative; min-height: 280px; display:flex; align-items:center; justify-content:center; overflow:hidden; background: linear-gradient(135deg, <?= $ev_color ?>44 0%, #0b1120 70%);">
                        <?php if (!empty($ev_img)): ?>
                        <div style="position:absolute; inset:0; background-image:url('<?= htmlspecialchars($ev_img) ?>'); background-size:cover; background-position:center; filter: brightness(0.85) contrast(1.05); transform: scale(1.01);"></div>
                        <div style="position:absolute; inset:0; background: linear-gradient(90deg, rgba(11,17,32,0.88) 0%, rgba(11,17,32,0.45) 50%, rgba(11,17,32,0.85) 100%); pointer-events:none;"></div>
                        <?php else: ?>
                        <div style="position:absolute; inset:0; background: radial-gradient(circle at 70% 30%, <?= $ev_color ?>33, transparent 55%), linear-gradient(135deg, #0f172a, #1e293b);"></div>
                        <?php endif; ?>

                        <div style="position:relative; z-index:2; text-align:center; padding: 2.2rem 1.5rem; max-width: 900px;" class="mx-auto">
                            <a href="sale-event.php?slug=<?= urlencode($ev['slug']) ?>" class="text-decoration-none d-block">
                                <div class="badge rounded-pill px-3 py-2 fw-bold text-uppercase mb-2 shadow" style="background: <?= $ev_color ?>; color: #fff; font-size: 0.85rem; letter-spacing: 1.5px;">
                                    <?php if (!empty($ev['icon_image'])): ?>
                                        <img src="<?= htmlspecialchars($ev['icon_image']) ?>" alt="" style="width: 18px; height: 18px; object-fit: contain; vertical-align: -2px;" class="me-1">
                                    <?php else: ?>
                                        <i class="<?= htmlspecialchars($ev['icon'] ?: 'fa-solid fa-fire') ?> me-1"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($ev['name']) ?>
                                </div>
                                <h2 style="font-size: clamp(1.6rem, 4.5vw, 2.8rem); font-weight: 900; color: #ffffff; line-height: 1.2; margin-bottom: 0.6rem; text-shadow: 0 4px 18px rgba(0,0,0,0.8);">
                                    <?= htmlspecialchars($ev['hero_banner_title'] ?: $ev['name']) ?>
                                </h2>
                                <?php if (!empty($ev['hero_banner_subtitle']) || !empty($ev['description'])): ?>
                                <p style="color: rgba(255,255,255,0.9); font-size: clamp(0.95rem, 2vw, 1.15rem); max-width: 700px; margin: 0 auto 1.2rem; text-shadow: 0 2px 8px rgba(0,0,0,0.8);">
                                    <?= htmlspecialchars($ev['hero_banner_subtitle'] ?: $ev['description']) ?>
                                </p>
                                <?php endif; ?>
                                <div class="d-inline-flex align-items-center gap-2">
                                    <span class="btn fw-bold rounded-pill px-4 py-2 shadow" style="background: linear-gradient(135deg, <?= $ev_color ?>, #f59e0b); color: #fff; border: none; font-size: 0.95rem;">
                                        <i class="fa-solid fa-bolt me-1"></i> Săn Deal Ngay <i class="fa-solid fa-chevron-right ms-1 small"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($hp_events) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#eventBannerCarousel" data-bs-slide="prev" style="width: 48px; opacity: 0.85;">
                <span class="p-2 rounded-circle bg-dark bg-opacity-50 text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"><i class="fa-solid fa-chevron-left"></i></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#eventBannerCarousel" data-bs-slide="next" style="width: 48px; opacity: 0.85;">
                <span class="p-2 rounded-circle bg-dark bg-opacity-50 text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"><i class="fa-solid fa-chevron-right"></i></span>
            </button>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- 2. TOP 7 SẢN PHẨM BÁN CHẠY NHẤT (Full-Bleed Breakout Panels) -->
<section class="shoe-breakout-section">
    <div class="container-fluid px-4 px-lg-5 shoe-breakout-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-white text-uppercase mb-0">
                    <i class="fa-solid fa-fire text-warning me-2"></i>🔥 TOP 7 BÁN CHẠY NHẤT
                </h5>
                <small class="text-white-50 d-md-none"><i class="fa-solid fa-hand-pointer me-1"></i>Vuốt ngang để xem</small>
            </div>
            <a href="all-products.php?sort=hot" class="text-warning text-decoration-none small fw-bold">Xem Tất Cả <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
    <div class="shoe-breakout-strip">
        <?php if ($hero_shoes && $hero_shoes->num_rows > 0): ?>
            <?php while($hero = $hero_shoes->fetch_assoc()): ?>
                <div class="shoe-panel" onclick="window.location.href='product-detail.php?id=<?= $hero['id']; ?>'">
                    <div class="shoe-panel-bg" style="background-image: url('<?= htmlspecialchars($hero['main_image']); ?>');"></div>
                    <img src="<?= htmlspecialchars($hero['main_image']); ?>" alt="<?= htmlspecialchars($hero['name']); ?>" class="shoe-panel-shoe" loading="lazy">
                    <div class="shoe-panel-info">
                        <div class="shoe-panel-price"><?= number_format($hero['price'], 0, ',', '.'); ?>đ</div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>

<div class="container-fluid px-4 px-lg-5 my-5"><!-- Full-width wrapper -->

    <!-- 3. THƯƠNG HIỆU NỔI BẬT (CHẠY VÒNG TRÒN VÔ TẬN & KHUNG BẰNG NHAU) -->
    <?php if (!empty($all_brands)): ?>
        <div class="mb-5 reveal">
            <h4 class="section-title"><?= htmlspecialchars($site_settings['section_brand_title'] ?? 'THƯƠNG HIỆU NỔI BẬT') ?></h4>
            <div class="brand-marquee-container py-2">
                <div class="brand-marquee-track">
                    <?php 
                    // Lặp 4 lần danh sách thương hiệu để chuỗi chạy vòng tròn không bị ngắt quãng
                    for ($loop = 0; $loop < 4; $loop++): 
                    ?>
                        <?php foreach($all_brands as $b): ?>
                            <a href="all-products.php?brand_id=<?= $b['id'] ?>" class="text-decoration-none">
                                <div class="brand-marquee-card">
                                    <img src="<?= htmlspecialchars($b['logo']) ?>" alt="<?= htmlspecialchars($b['name']) ?>" title="<?= htmlspecialchars($b['name']) ?>">
                                    <span class="brand-card-name"><?= htmlspecialchars($b['name']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. VOUCHER KHUYẾN MÃI (BANNER + LƯU TÀI KHOẢN + HIỆU ỨNG XÉ + LUÔN HIỂN THỊ 4 MÃ) -->
    <?php if ($all_vouchers_query && $all_vouchers_query->num_rows > 0): ?>
        <?php 
        $all_vouchers_query->data_seek(0);
        $unsaved_vouchers = [];
        $saved_vouchers = [];
        while($v = $all_vouchers_query->fetch_assoc()) {
            $vid = intval($v['id']);
            $v['is_saved'] = in_array($vid, $user_saved_voucher_ids);
            if ($v['is_saved']) {
                $saved_vouchers[] = $v;
            } else {
                $unsaved_vouchers[] = $v;
            }
        }
        $vouchers_to_render = array_merge($unsaved_vouchers, $saved_vouchers);
        
        // Tính toán danh sách 4 thẻ hiển thị ban đầu:
        // Ưu tiên các thẻ chưa lưu. Nếu ít hơn 4 thẻ chưa lưu (ví dụ 3), lấy thêm các thẻ đã lưu ở dưới để bù đủ 4 slot!
        $num_unsaved = count($unsaved_vouchers);
        $needed_saved_fill = max(0, 4 - $num_unsaved);
        
        $initial_visible_ids = [];
        for ($i = 0; $i < min(4, $num_unsaved); $i++) {
            $initial_visible_ids[] = intval($unsaved_vouchers[$i]['id']);
        }
        for ($i = 0; $i < min($needed_saved_fill, count($saved_vouchers)); $i++) {
            $initial_visible_ids[] = intval($saved_vouchers[$i]['id']);
        }
        ?>
        <div class="mb-5 reveal" id="voucherSectionReveal">
            <div class="voucher-section-wrapper p-3 p-md-4 rounded-4" style="background: linear-gradient(145deg, #0b1120 0%, #1e293b 100%); border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
                <div class="row g-4 align-items-stretch">
                    <!-- Cột trái: Banner Quảng Cáo (DB site_settings) -->
                    <div class="col-12 col-lg-5">
                        <div class="voucher-banner-box rounded-4 h-100 position-relative overflow-hidden p-4 d-flex flex-column justify-content-end" style="background-image: url('<?= htmlspecialchars($v_banner_img) ?>'); background-size: cover; background-position: center; min-height: 380px;">
                            <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(11,17,32,0.2) 0%, rgba(11,17,32,0.95) 100%);"></div>
                            <div class="voucher-banner-content text-white position-relative" style="z-index: 2;">
                                <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-solid fa-fire text-danger me-1"></i> KHUYẾN MÃI ĐẶC BIỆT
                                </span>
                                <h3 class="fw-black text-uppercase mb-2 text-white" style="letter-spacing: 1px; font-weight: 900; font-size: 1.7rem;">
                                    <?= htmlspecialchars($v_banner_title) ?>
                                </h3>
                                <p class="text-white-50 small mb-4" style="line-height: 1.6;">
                                    <?= htmlspecialchars($v_banner_subtitle) ?>
                                </p>
                                <a href="<?= htmlspecialchars($v_banner_link) ?>" class="btn btn-warning rounded-pill px-4 py-2 fw-bold shadow">
                                    Mua Sắm Khám Phá <i class="fa-solid fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: Thẻ Voucher dạng Ticket Card -->
                    <div class="col-12 col-lg-7 d-flex flex-column justify-content-between">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <div>
                                <h4 class="fw-bold text-white mb-0 fs-5">
                                    <i class="fa-solid fa-ticket text-warning me-2"></i><?= htmlspecialchars($site_settings['section_voucher_title'] ?? 'MÃ GIẢM GIÁ KHUYẾN MÃI') ?>
                                </h4>
                                <span class="small text-white-50">Lưu ngay mã ưu đãi vào tài khoản để áp dụng khi thanh toán</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-light btn-sm rounded-pill fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#allVouchersModal">
                                    <i class="fa-solid fa-layer-group me-1 text-warning"></i> Tất cả (<?= count($vouchers_to_render) ?>)
                                </button>
                            </div>
                        </div>

                        <!-- Container Thẻ Voucher (Luôn duy trì 4 chỗ hiển thị) -->
                        <div class="voucher-deal-container" id="voucherDealContainer">
                            <?php 
                            $deal_idx = 0;
                            foreach($vouchers_to_render as $v):
                                $vid = intval($v['id']);
                                $is_saved = $v['is_saved'];
                                
                                // Phân loại màu sắc & hiển thị
                                if ($v['discount_type'] === 'freeship') {
                                    $theme_class = 'voucher-theme-emerald';
                                    $stub_icon = 'fa-solid fa-truck-fast';
                                    $stub_val = 'FREE';
                                    $stub_label = 'VẬN CHUYỂN';
                                    $disc_badge = 'Miễn phí vận chuyển';
                                } elseif ($v['discount_type'] === 'percent') {
                                    $theme_class = 'voucher-theme-gold';
                                    $stub_icon = 'fa-solid fa-percent';
                                    $stub_val = intval($v['discount_value']) . '%';
                                    $stub_label = 'GIẢM GIÁ';
                                    $disc_badge = 'Giảm ' . intval($v['discount_value']) . '%';
                                    if (floatval($v['max_discount']) > 0) {
                                        $disc_badge .= ' (Max ' . number_format($v['max_discount'], 0, ',', '.') . 'đ)';
                                    }
                                } else {
                                    $theme_class = 'voucher-theme-crimson';
                                    $stub_icon = 'fa-solid fa-tag';
                                    $stub_val = (floatval($v['discount_value']) >= 1000) ? (intval($v['discount_value'] / 1000) . 'K') : number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                    $stub_label = 'GIẢM TIỀN';
                                    $disc_badge = 'Giảm ' . number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                }

                                $applies_to = !empty($v['brand_name']) ? 'Thương hiệu: <strong class="text-warning">' . htmlspecialchars($v['brand_name']) . '</strong>' : 'Áp dụng: <strong class="text-info">Tất cả sản phẩm</strong>';
                                $deal_idx++;

                                $is_initially_visible = in_array($vid, $initial_visible_ids);
                                $display_style = $is_initially_visible ? 'display: block;' : 'display: none;';
                            ?>
                                <div class="voucher-deal-card p-0 mb-3" data-deal-index="<?= $deal_idx ?>" data-saved="<?= $is_saved ? '1' : '0' ?>" style="<?= $display_style ?> border: none; background: transparent; box-shadow: none;">
                                    <div class="voucher-ticket dark-theme <?= $theme_class ?> m-0">
                                        <!-- Cuống vé -->
                                        <div class="voucher-ticket-stub">
                                            <i class="<?= $stub_icon ?> voucher-stub-icon"></i>
                                            <div class="voucher-stub-value"><?= $stub_val ?></div>
                                            <div class="voucher-stub-label"><?= $stub_label ?></div>
                                        </div>
                                        
                                        <!-- Đường cắt vé có rãnh bán nguyệt -->
                                        <div class="voucher-ticket-divider">
                                            <div class="voucher-notch voucher-notch-top"></div>
                                            <div class="voucher-notch voucher-notch-bottom"></div>
                                        </div>

                                        <!-- Thân vé -->
                                        <div class="voucher-ticket-body">
                                            <div class="voucher-info-wrapper">
                                                <span class="voucher-badge-type">
                                                    <i class="fa-solid fa-sparkles me-1"></i><?= $disc_badge ?>
                                                </span>
                                                <h6 class="voucher-title text-white mb-1"><?= htmlspecialchars($v['title'] ?? 'Ưu đãi đặc biệt') ?></h6>
                                                <div class="voucher-conditions text-white-50 small">
                                                    Đơn tối thiểu: <strong class="text-warning"><?= number_format($v['min_order_value'], 0, ',', '.') ?>đ</strong>
                                                    <span class="mx-1">•</span>
                                                    HSD: <strong><?= date('d/m/Y', strtotime($v['end_date'])) ?></strong>
                                                    <div class="mt-1 small"><?= $applies_to ?></div>
                                                </div>
                                            </div>

                                            <div class="voucher-action-area">
                                                <div class="voucher-code-badge" data-code="<?= htmlspecialchars($v['code']) ?>" title="Nhấn để sao chép mã">
                                                    <?= htmlspecialchars($v['code']) ?> <i class="fa-regular fa-copy ms-1 opacity-75"></i>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <?php if ($is_saved): ?>
                                                        <button class="btn-voucher-action saved btn-save-voucher" disabled>
                                                            <i class="fa-solid fa-check"></i> Đã Lưu
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-voucher-action btn-voucher-save btn-save-voucher" data-voucher-id="<?= $v['id'] ?>" data-voucher-code="<?= htmlspecialchars($v['code']) ?>">
                                                            <i class="fa-solid fa-bookmark"></i> Lưu Mã
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL XEM TẤT CẢ VOUCHER (CHUYÊN NGHIỆP + BỘ LỌC + TÌM KIẾM) -->
    <div class="modal fade" id="allVouchersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: #0f172a; color: #ffffff;">
                <div class="modal-header border-bottom border-secondary bg-dark px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded-3 bg-warning text-dark fw-bold">
                            <i class="fa-solid fa-ticket-simple fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0">KHO MÃ GIẢM GIÁ & ƯU ĐÃI</h5>
                            <span class="small text-white-50">Lưu ngay vào ví voucher của bạn để áp dụng giảm giá khi mua hàng</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background: #0b1120; max-height: 75vh; overflow-y: auto;">
                    <!-- Filter Toolbar -->
                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-warning">
                                    <i class="fa-solid fa-search"></i>
                                </span>
                                <input type="text" id="modalVoucherSearch" class="form-control bg-dark border-secondary text-white" placeholder="Tìm theo mã hoặc tên ưu đãi...">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 text-md-end">
                            <div class="btn-group rounded-pill p-1 bg-dark border border-secondary" role="group" id="voucherFilterBtns">
                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold active" onclick="filterModalVouchers('all', this)">Tất cả</button>
                                <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-pill px-3 fw-bold" onclick="filterModalVouchers('percent', this)">Giảm %</button>
                                <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-pill px-3 fw-bold" onclick="filterModalVouchers('fixed', this)">Tiền mặt</button>
                                <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-pill px-3 fw-bold" onclick="filterModalVouchers('freeship', this)">Freeship</button>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Grid List -->
                    <div class="row g-3" id="modalVouchersList">
                        <?php if ($all_vouchers_query && $all_vouchers_query->num_rows > 0): ?>
                            <?php 
                            $all_vouchers_query->data_seek(0);
                            while($v = $all_vouchers_query->fetch_assoc()): 
                                $vid = intval($v['id']);
                                $is_saved = in_array($vid, $user_saved_voucher_ids);
                                $vtype = $v['discount_type'];

                                if ($vtype === 'freeship') {
                                    $theme_class = 'voucher-theme-emerald';
                                    $stub_icon = 'fa-solid fa-truck-fast';
                                    $stub_val = 'FREE';
                                    $stub_label = 'SHIP';
                                    $disc_badge = 'Miễn phí vận chuyển';
                                } elseif ($vtype === 'percent') {
                                    $theme_class = 'voucher-theme-gold';
                                    $stub_icon = 'fa-solid fa-percent';
                                    $stub_val = intval($v['discount_value']) . '%';
                                    $stub_label = 'GIẢM GIÁ';
                                    $disc_badge = 'Giảm ' . intval($v['discount_value']) . '%';
                                    if (floatval($v['max_discount']) > 0) {
                                        $disc_badge .= ' (Max ' . number_format($v['max_discount'], 0, ',', '.') . 'đ)';
                                    }
                                } else {
                                    $theme_class = 'voucher-theme-crimson';
                                    $stub_icon = 'fa-solid fa-tag';
                                    $stub_val = (floatval($v['discount_value']) >= 1000) ? (intval($v['discount_value'] / 1000) . 'K') : number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                    $stub_label = 'GIẢM TIỀN';
                                    $disc_badge = 'Giảm ' . number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                }
                                $applies_to = !empty($v['brand_name']) ? 'Thương hiệu: <strong class="text-warning">' . htmlspecialchars($v['brand_name']) . '</strong>' : 'Áp dụng: <strong class="text-info">Tất cả sản phẩm</strong>';
                            ?>
                                <div class="col-12 col-lg-6 modal-voucher-col" data-type="<?= $vtype ?>">
                                    <div class="voucher-ticket dark-theme <?= $theme_class ?> m-0 h-100">
                                        <!-- Cuống vé -->
                                        <div class="voucher-ticket-stub">
                                            <i class="<?= $stub_icon ?> voucher-stub-icon"></i>
                                            <div class="voucher-stub-value"><?= $stub_val ?></div>
                                            <div class="voucher-stub-label"><?= $stub_label ?></div>
                                        </div>
                                        
                                        <!-- Đường cắt vé có rãnh bán nguyệt -->
                                        <div class="voucher-ticket-divider">
                                            <div class="voucher-notch voucher-notch-top"></div>
                                            <div class="voucher-notch voucher-notch-bottom"></div>
                                        </div>

                                        <!-- Thân vé -->
                                        <div class="voucher-ticket-body">
                                            <div class="voucher-info-wrapper">
                                                <span class="voucher-badge-type">
                                                    <i class="fa-solid fa-gift me-1"></i><?= $disc_badge ?>
                                                </span>
                                                <h6 class="voucher-title text-white mb-1"><?= htmlspecialchars($v['title']) ?></h6>
                                                <div class="voucher-conditions text-white-50 small">
                                                    Đơn từ: <strong class="text-warning"><?= number_format($v['min_order_value'], 0, ',', '.') ?>đ</strong>
                                                    <span class="mx-1">•</span>
                                                    HSD: <strong><?= date('d/m/Y', strtotime($v['end_date'])) ?></strong>
                                                    <div class="mt-1 small"><?= $applies_to ?></div>
                                                </div>
                                            </div>

                                            <div class="voucher-action-area">
                                                <div class="voucher-code-badge" data-code="<?= htmlspecialchars($v['code']) ?>" title="Nhấn để sao chép">
                                                    <?= htmlspecialchars($v['code']) ?> <i class="fa-regular fa-copy ms-1 opacity-75"></i>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <?php if ($is_saved): ?>
                                                        <button class="btn-voucher-action saved btn-save-voucher" disabled>
                                                            <i class="fa-solid fa-check"></i> Đã Lưu
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-voucher-action btn-voucher-save btn-save-voucher" data-voucher-id="<?= $v['id'] ?>" data-voucher-code="<?= htmlspecialchars($v['code']) ?>">
                                                            <i class="fa-solid fa-bookmark"></i> Lưu Mã
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 text-center text-white-50 py-5">
                                <i class="fa-solid fa-ticket-simple fa-3x mb-3 text-secondary"></i>
                                <h6>Hiện chưa có voucher nào đang hoạt động.</h6>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function filterModalVouchers(type, btn) {
        // Toggle active button
        const btns = document.querySelectorAll('#voucherFilterBtns button');
        btns.forEach(b => {
            b.classList.remove('btn-warning', 'active');
            b.classList.add('btn-outline-light', 'border-0');
        });
        btn.classList.add('btn-warning', 'active');
        btn.classList.remove('btn-outline-light', 'border-0');

        // Filter cards
        const cards = document.querySelectorAll('#modalVouchersList .modal-voucher-col');
        cards.forEach(card => {
            if (type === 'all' || card.dataset.type === type) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    </script>

    <!-- 5. BRAND GAME SHOWCASE — giày breakout phong cách giới thiệu game -->
    <?php if (!empty($all_brands)): ?>
        <?php $brand_index = 0; ?>
        <?php foreach ($all_brands as $brand): ?>
            <?php 
            $b_id = intval($brand['id']);
            $brand_products_res = $conn->query("
                SELECT p.*, b.name as brand_name 
                FROM products p 
                JOIN brands b ON p.brand_id = b.id 
                WHERE p.brand_id = $b_id AND p.status = 1 
                ORDER BY p.sold_count DESC, p.id DESC LIMIT 4
            ");
            if ($brand_products_res && $brand_products_res->num_rows > 0):
                $brand_prods = [];
                while($bp = $brand_products_res->fetch_assoc()) $brand_prods[] = $bp;
                $hero = $brand_prods[0];
                $is_reverse = ($brand_index % 2 !== 0) ? 'reverse' : '';
                $brand_num = str_pad($brand_index + 1, 2, '0', STR_PAD_LEFT);
            ?>
            <div class="brand-showcase-box <?= $is_reverse ?>" data-brand-block>

                <!-- PANEL TỐI chính: trái | giữa | phải -->
                <div class="brand-game-panel">

                    <!-- Cột trái: số + thương hiệu -->
                    <div class="brand-game-left" data-slide="desc">
                        <div class="brand-game-num"><?= $brand_num ?></div>
                        <div class="brand-game-label">THƯƠNG HIỆU</div>
                        <div class="brand-game-name"><?= htmlspecialchars($brand['name']) ?></div>
                        <img class="brand-game-logo" src="<?= htmlspecialchars($brand['logo']) ?>" alt="<?= htmlspecialchars($brand['name']) ?>">
                    </div>

                    <!-- Cột giữa: giày breakout -->
                    <div class="brand-game-center">
                        <div class="brand-game-glow"></div>
                        <img class="brand-game-shoe" loading="lazy"
                             src="<?= htmlspecialchars($hero['main_image']) ?>"
                             alt="<?= htmlspecialchars($hero['name']) ?>"
                             data-hero-img>
                    </div>

                    <!-- Cột phải: mô tả collection -->
                    <div class="brand-game-right" data-slide="image">
                        <span class="brand-game-badge">Chính Hãng</span>
                        <h4 class="brand-game-title"><?= htmlspecialchars($brand['name']) ?> Collection</h4>
                        <p class="brand-game-desc"><?= htmlspecialchars($brand['description'] ?: 'Bộ sưu tập giày ' . $brand['name'] . ' chính hãng cao cấp, mang đến phong cách đỉnh cao và chất lượng vượt trội.') ?></p>
                        <div class="brand-game-price" data-price-display>
                            Từ <?= number_format($hero['price'], 0, ',', '.') ?>đ
                        </div>
                    </div>
                </div>

                <!-- FOOTER: thumbnails + nút -->
                <div class="brand-game-footer">
                    <button class="brand-thumb-arrow" data-dir="prev" aria-label="Trước">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="brand-thumbs-strip">
                        <?php foreach($brand_prods as $i => $bp): ?>
                        <div class="brand-thumb <?= $i === 0 ? 'active' : '' ?>"
                             data-hero-src="<?= htmlspecialchars($bp['main_image']) ?>"
                             data-price="<?= number_format($bp['price'], 0, ',', '.') ?>đ"
                             data-product-url="product-detail.php?id=<?= $bp['id'] ?>"
                             title="<?= htmlspecialchars($bp['name']) ?>">
                            <img src="<?= htmlspecialchars($bp['main_image']) ?>" alt="<?= htmlspecialchars($bp['name']) ?>" loading="lazy">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="brand-thumb-arrow" data-dir="next" aria-label="Tiếp">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <a href="all-products.php?brand_id=<?= $b_id ?>" class="brand-game-btn">
                        Khám Phá <?= htmlspecialchars($brand['name']) ?> <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>

                <!-- Lưới sản phẩm (4 sản phẩm phía dưới) -->
                <div class="brand-game-products">
                    <div class="row g-4 brand-products-row" data-products>
                        <?php foreach($brand_prods as $p): ?>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-product">
                                <?php include 'includes/product-card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php $brand_index++; endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- 6. SẢN PHẨM NỔI BẬT (HOT) -->
    <?php if ($hot_products_query && $hot_products_query->num_rows > 0): ?>
        <div class="mb-5 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h4 class="fw-bold text-uppercase mb-0 text-dark">
                    <i class="fa-solid fa-fire text-danger me-2"></i><?= htmlspecialchars($site_settings['section_hot_title'] ?? 'SẢN PHẨM NỔI BẬT') ?>
                </h4>
                <a href="all-products.php?sort=hot" class="btn btn-outline-dark btn-sm rounded-pill fw-bold">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="row g-4 reveal-stagger">
                <?php while($p = $hot_products_query->fetch_assoc()): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <?php include 'includes/product-card.php'; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 7. HÀNG MỚI VỀ (NEW) -->
    <?php if ($new_products_query && $new_products_query->num_rows > 0): ?>
        <div class="mb-5 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h4 class="fw-bold text-uppercase mb-0 text-dark">
                    <i class="fa-solid fa-sparkles text-warning me-2"></i><?= htmlspecialchars($site_settings['section_new_title'] ?? 'HÀNG MỚI VỀ') ?>
                </h4>
                <a href="all-products.php?sort=latest" class="btn btn-outline-dark btn-sm rounded-pill fw-bold">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="row g-4 reveal-stagger">
                <?php while($p = $new_products_query->fetch_assoc()): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <?php include 'includes/product-card.php'; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 8. ĐANG GIẢM GIÁ SỐC (SALE) -->
    <?php if ($sale_products_query && $sale_products_query->num_rows > 0): ?>
        <div class="mb-5 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h4 class="fw-bold text-uppercase mb-0 text-danger">
                    <i class="fa-solid fa-tags me-2"></i><?= htmlspecialchars($site_settings['section_sale_title'] ?? 'ĐANG GIẢM GIÁ SỐC') ?>
                </h4>
                <a href="all-products.php?discount=1" class="btn btn-outline-danger btn-sm rounded-pill fw-bold">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="row g-4 reveal-stagger">
                <?php while($p = $sale_products_query->fetch_assoc()): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <?php include 'includes/product-card.php'; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 9. KHUNG 4 CAM KẾT & MIỄN PHÍ VẬN CHUYỂN (CUỐI TRANG) -->
    <div class="my-5 pt-3 reveal">
        <div class="row g-4 reveal-stagger">
            <?php for($i=1; $i<=4; $i++): ?>
                <?php 
                $icon = $site_settings["service_{$i}_icon"] ?? 'fa-solid fa-check';
                $title = $site_settings["service_{$i}_title"] ?? '';
                $desc = $site_settings["service_{$i}_desc"] ?? '';
                if ($title):
                ?>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="service-card">
                        <i class="<?= htmlspecialchars($icon) ?> service-icon"></i>
                        <h6 class="fw-bold text-uppercase mb-2"><?= htmlspecialchars($title) ?></h6>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($desc) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>

</div>

<script>
// === SCROLL REVEAL — lặp lại khi lướt lên rồi xuống ===
(function() {
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            } else {
                entry.target.classList.remove('visible');
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal, .reveal-stagger').forEach(el => revealObserver.observe(el));
})();

// === BRAND GAME BLOCK: slide-in & sản phẩm — lặp lại khi lướt lên/xuống ===
(function() {
    const slideObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const block = entry.target.closest('[data-brand-block]');
            if (!block) return;
            const imgEl  = block.querySelector('[data-slide="image"]');
            const descEl = block.querySelector('[data-slide="desc"]');
            if (entry.isIntersecting) {
                if (imgEl)  imgEl.classList.add('slide-in');
                if (descEl) descEl.classList.add('slide-in');
            } else {
                if (imgEl)  imgEl.classList.remove('slide-in');
                if (descEl) descEl.classList.remove('slide-in');
            }
        });
    }, { threshold: 0.18, rootMargin: '0px 0px -30px 0px' });

    // Observe .brand-game-panel (thay thế .brand-split-header cũ)
    document.querySelectorAll('.brand-game-panel').forEach(el => slideObserver.observe(el));

    const productsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('products-visible');
            } else {
                entry.target.classList.remove('products-visible');
            }
        });
    }, { threshold: 0.10, rootMargin: '0px 0px -20px 0px' });

    document.querySelectorAll('.brand-products-row').forEach(el => productsObserver.observe(el));
})();

// === BRAND THUMBNAIL: click đổi giày hero ===
document.querySelectorAll('.brand-thumb').forEach(thumb => {
    thumb.addEventListener('click', function() {
        const block  = this.closest('[data-brand-block]');
        const heroImg = block.querySelector('[data-hero-img]');
        const priceEl = block.querySelector('[data-price-display]');
        const strip  = this.closest('.brand-thumbs-strip');

        // Fade-out giày cũ
        heroImg.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        heroImg.style.opacity  = '0';
        heroImg.style.transform = 'translateX(-50%) translateY(18px) scale(0.9)';

        setTimeout(() => {
            heroImg.src = this.dataset.heroSrc;
            heroImg.onload = () => {
                heroImg.style.opacity  = '1';
                heroImg.style.transform = 'translateX(-50%)';
            };
            // Cập nhật giá
            if (priceEl && this.dataset.price) {
                priceEl.textContent = 'Từ ' + this.dataset.price;
            }
        }, 270);

        // Active state
        strip.querySelectorAll('.brand-thumb').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});

// === VOUCHER CARD OBSERVER ===
(function() {
    const dealContainer = document.getElementById('voucherDealContainer');
    if (!dealContainer) return;

    const dealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                dealContainer.classList.add('dealt');
            } else {
                dealContainer.classList.remove('dealt');
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    const voucherSection = document.getElementById('voucherSectionReveal') || dealContainer;
    dealObserver.observe(voucherSection);
})();

// === LƯU VOUCHER VÀO TÀI KHOẢN VỚI HIỆU ỨNG XÉ VÉ (RIP ANIMATION) ===
document.addEventListener('click', function(e) {
    const saveBtn = e.target.closest('.btn-save-voucher:not(.saved)');
    if (!saveBtn) return;

    e.preventDefault();
    const voucherId = saveBtn.dataset.voucherId;
    const voucherCode = saveBtn.dataset.voucherCode;
    const card = saveBtn.closest('.voucher-deal-card');

    if (!voucherId) return;

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';

    const formData = new FormData();
    formData.append('voucher_id', voucherId);
    formData.append('voucher_code', voucherCode);

    fetch('api/save-voucher.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.require_login) {
            alert(data.message || 'Vui lòng đăng nhập để lưu voucher!');
            window.location.href = 'login.php';
            return;
        }

        if (data.success) {
            // Cập nhật tất cả các nút của voucher này (trang chính + modal)
            document.querySelectorAll(`.btn-save-voucher[data-voucher-id="${voucherId}"]`).forEach(b => {
                b.classList.add('saved', 'btn-success');
                b.classList.remove('btn-warning');
                b.disabled = true;
                b.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã Lưu';
                const parentCard = b.closest('.voucher-deal-card');
                if (parentCard) parentCard.setAttribute('data-saved', '1');
            });

            // Nếu thẻ nằm trong khung chính → Kích hoạt hiệu ứng xé vé
            if (card) {
                card.classList.add('ripping');

                setTimeout(() => {
                    card.classList.add('rip-away');

                    setTimeout(() => {
                        card.setAttribute('data-saved', '1');
                        card.style.display = 'none';
                        
                        const container = document.getElementById('voucherDealContainer');
                        if (container) {
                            const allCards = Array.from(container.querySelectorAll('.voucher-deal-card'));
                            
                            // Lọc các thẻ chưa lưu và các thẻ đã lưu
                            const unsavedCards = allCards.filter(c => c.getAttribute('data-saved') === '0');
                            const savedCards = allCards.filter(c => c.getAttribute('data-saved') === '1');

                            // Đặt mục tiêu duy trì LUÔN HIỂN THỊ ĐỦ 4 THẺ (ưu tiên thẻ chưa lưu trước, nếu chưa lưu ít hơn 4 thì lấy thêm thẻ đã lưu ở dưới để bù đủ 4 slot)
                            let targetVisible = [];
                            if (unsavedCards.length >= 4) {
                                targetVisible = unsavedCards.slice(0, 4);
                            } else {
                                const neededSaved = 4 - unsavedCards.length;
                                targetVisible = [...unsavedCards, ...savedCards.slice(0, neededSaved)];
                            }

                            // Cập nhật trạng thái hiển thị của từng thẻ
                            allCards.forEach(c => {
                                if (targetVisible.includes(c)) {
                                    if (c.style.display === 'none') {
                                        c.style.display = 'block';
                                        c.classList.remove('ripping', 'rip-away');
                                        c.style.opacity = '1';
                                        c.style.transform = 'none';
                                        c.classList.add('slide-in-new');
                                        setTimeout(() => c.classList.remove('slide-in-new'), 600);
                                    }
                                } else {
                                    c.style.display = 'none';
                                }
                            });
                        }

                        // Hiển thị Toast thông báo
                        showToastNotice(data.message || 'Lưu voucher thành công!');
                    }, 550);
                }, 250);
            } else {
                showToastNotice(data.message || 'Lưu voucher thành công!');
            }
        } else {
            alert(data.message || 'Không thể lưu voucher!');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-bookmark me-1"></i> Lưu Mã';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Đã xảy ra lỗi kết nối. Vui lòng thử lại!');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fa-solid fa-bookmark me-1"></i> Lưu Mã';
    });
});

function showToastNotice(msg) {
    const toastMsg = document.createElement('div');
    toastMsg.className = 'position-fixed bottom-0 end-0 p-3';
    toastMsg.style.zIndex = '999999';
    toastMsg.innerHTML = `
        <div class="toast show align-items-center text-white bg-success border-0 shadow-lg rounded-3" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="fa-solid fa-circle-check me-2"></i> ${msg}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.body.appendChild(toastMsg);
    setTimeout(() => toastMsg.remove(), 4000);
}

// Sao chép mã voucher
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const code = this.dataset.code;
        navigator.clipboard.writeText(code).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép';
            this.classList.replace('btn-outline-warning', 'btn-warning');
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.replace('btn-warning', 'btn-outline-warning');
            }, 2000);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>