<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Tính tổng số lượng trong giỏ hàng theo tài khoản đăng nhập
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    try {
        $res_cnt = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total_qty FROM cart_items WHERE user_id = $uid");
        if ($res_cnt && $row_c = $res_cnt->fetch_assoc()) {
            $cart_count = intval($row_c['total_qty']);
        }
    } catch (Throwable $e) {
        // Tự động tạo bảng cart_items nếu CSDL cũ chưa có
        @$conn->query("
            CREATE TABLE IF NOT EXISTS `cart_items` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `product_id` INT NOT NULL,
              `variant_id` INT NOT NULL,
              `quantity` INT NOT NULL DEFAULT 1,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `user_prod_var` (`user_id`, `product_id`, `variant_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $cart_count = 0;
    }
}

// Queries cho Mega Menu
// 1. Thương hiệu
$brands_query = $conn->query("SELECT id, name FROM brands WHERE status = 1 ORDER BY name ASC LIMIT 10");
$mega_brands = [];
if ($brands_query) {
    while ($row = $brands_query->fetch_assoc()) {
        $mega_brands[] = $row;
    }
}

// 2. Danh mục gốc (parent_id IS NULL OR parent_id = 0) và danh mục con (Tự động cập nhật khi thêm danh mục gốc mới)
$all_cats_query = $conn->query("SELECT id, parent_id, name, slug, image, type, gender, sort_order FROM categories WHERE status = 1 ORDER BY sort_order ASC, name ASC");
$mega_root_categories = [];
$sub_cats_map = [];

if ($all_cats_query) {
    $raw_cats = [];
    while ($row = $all_cats_query->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['parent_id'] = $row['parent_id'] ? (int)$row['parent_id'] : 0;
        $raw_cats[] = $row;
    }

    foreach ($raw_cats as $cat) {
        if ($cat['parent_id'] === 0) {
            $cat['subs'] = [];
            $mega_root_categories[$cat['id']] = $cat;
        } else {
            $sub_cats_map[$cat['parent_id']][] = $cat;
        }
    }

    foreach ($sub_cats_map as $pid => $subs) {
        if (isset($mega_root_categories[$pid])) {
            $mega_root_categories[$pid]['subs'] = $subs;
        }
    }
}

if (!function_exists('get_category_icon')) {
    function get_category_icon($cat) {
        $name_lower = mb_strtolower($cat['name'] ?? '', 'UTF-8');
        $gender = strtolower($cat['gender'] ?? '');
        $type = strtolower($cat['type'] ?? '');

        if (strpos($name_lower, 'dép') !== false || strpos($name_lower, 'sandal') !== false || $type === 'dep') {
            return 'fa-solid fa-shoe-prints';
        }
        if (strpos($name_lower, 'nam') !== false || $gender === 'nam') {
            return 'fa-solid fa-person';
        }
        if (strpos($name_lower, 'nữ') !== false || strpos($name_lower, 'nu') !== false || $gender === 'nu') {
            return 'fa-solid fa-person-dress';
        }
        if (strpos($name_lower, 'chạy') !== false || strpos($name_lower, 'thể thao') !== false) {
            return 'fa-solid fa-person-running';
        }
        if (strpos($name_lower, 'bóng rổ') !== false) {
            return 'fa-solid fa-basketball';
        }
        if (strpos($name_lower, 'bóng đá') !== false) {
            return 'fa-solid fa-futbol';
        }
        if (strpos($name_lower, 'phụ kiện') !== false || strpos($name_lower, 'balo') !== false || strpos($name_lower, 'túi') !== false) {
            return 'fa-solid fa-bag-shopping';
        }
        if (strpos($name_lower, 'vớ') !== false || strpos($name_lower, 'tất') !== false) {
            return 'fa-solid fa-socks';
        }
        if (strpos($name_lower, 'trẻ em') !== false || strpos($name_lower, 'bé') !== false || strpos($name_lower, 'kid') !== false) {
            return 'fa-solid fa-child';
        }
        if (strpos($name_lower, 'sneaker') !== false || strpos($name_lower, 'giày') !== false || $type === 'giay') {
            return 'fa-solid fa-shoe-prints';
        }
        return 'fa-solid fa-layer-group';
    }
}

// 5. Sự kiện sale đang active (để hiển thị trên menu)
$active_events = [];
$evt_check = $conn->query("SHOW TABLES LIKE 'sale_events'");
if ($evt_check && $evt_check->num_rows > 0) {
    $events_query = $conn->query("SELECT id, name, slug, color_theme, icon, icon_image FROM sale_events WHERE status = 1 AND show_on_menu = 1 AND start_date <= NOW() AND end_date >= NOW() ORDER BY sort_order ASC");
    if ($events_query) {
        while ($row = $events_query->fetch_assoc()) {
            $active_events[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOESSTORE - Giày Dép Hàng Hiệu</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6.4.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Theme Initializer Script (Prevents Theme Flash) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('app_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=20260816">
    <link rel="stylesheet" href="assets/css/modern-ui.css?v=20260816">
    <link rel="stylesheet" href="assets/css/premium-ui.css?v=20260816">
    <link rel="stylesheet" href="assets/css/vouchers.css?v=20260815">
    <link rel="stylesheet" href="assets/css/theme-dark.css?v=20260815">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20260818">
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/theme-toggle.js?v=20260815" defer></script>
    <script src="assets/js/vouchers.js?v=20260815" defer></script>
    <script src="assets/js/custom-dialogs.js?v=20260815" defer></script>
</head>
<body>

<!-- TOP HEADER -->
<header class="top-header py-3 sticky-top" style="position: relative; z-index: 1030 !important; overflow: visible !important;">
    <div class="container">
        <div class="row align-items-center">
            <!-- Logo & Hamburger Menu Button -->
            <div class="col-6 col-md-3 d-flex align-items-center gap-2">
                <button class="btn btn-sm d-md-none border-0 p-0 me-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" style="font-size: 1.35rem; color: inherit;" title="Mở danh mục menu">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <a href="index.php" class="text-decoration-none brand-logo-text">
                    SHOES<span>STORE</span>
                </a>
            </div>

            <!-- Search Bar -->
            <div class="col-12 col-md-6 order-3 order-md-2 mt-2 mt-md-0">
                <form action="all-products.php" method="GET" class="search-form position-relative">
                    <div class="input-group">
                        <input type="text" name="keyword" id="liveSearchInput" class="form-control search-input rounded-pill pe-5" placeholder="Tìm kiếm giày sneaker, dép, thương hiệu..." autocomplete="off" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        <button class="btn search-btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent me-2" type="submit">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </button>
                    </div>
                    <!-- Live Search Dropdown Container -->
                    <div class="search-live-dropdown shadow-lg rounded-4 overflow-hidden position-absolute w-100 mt-2 d-none" id="searchLiveResults" style="z-index: 100000000; max-height: 540px; overflow-y: auto;"></div>
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="col-6 col-md-3 order-2 order-md-3 text-end d-flex align-items-center justify-content-end gap-2">
                <!-- Theme Toggle Button (Dark / Light) -->
                <button class="header-action-btn theme-toggle-btn border-0 shadow-sm" type="button" title="Chuyển đổi giao diện Sáng / Tối">
                    <i class="fa-solid fa-moon theme-icon-moon"></i>
                    <i class="fa-solid fa-sun theme-icon-sun" style="display: none; color: #fbbf24;"></i>
                </button>

                <!-- User Menu -->
                <div class="dropdown" style="position: relative; z-index: 100000005 !important;">
                    <a href="#" class="header-action-btn text-decoration-none dropdown-toggle-no-caret" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-regular fa-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="z-index: 100000010 !important; min-width: 200px;">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="px-3 py-2 text-center border-bottom mb-1 user-dropdown-header">
                                <span class="fw-bold d-block text-truncate" style="max-width: 170px;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?></span>
                                <small class="text-muted"><?= htmlspecialchars($_SESSION['user_role'] ?? 'customer') ?></small>
                            </li>
                            <li><a class="dropdown-item py-2 fw-bold" href="profile.php"><i class="fa-regular fa-id-badge me-2 w-20px text-center text-primary"></i>Hồ sơ</a></li>
                            <li><a class="dropdown-item py-2 fw-bold" href="profile.php?tab=vouchers"><i class="fa-solid fa-ticket me-2 w-20px text-center text-warning"></i>Ví Voucher</a></li>
                            <li><a class="dropdown-item py-2 fw-bold" href="my-orders.php"><i class="fa-solid fa-box me-2 w-20px text-center text-success"></i>Đơn hàng</a></li>
                            <li><a class="dropdown-item py-2 fw-bold" href="wishlist.php"><i class="fa-regular fa-heart me-2 w-20px text-center text-danger"></i>Yêu thích</a></li>
                            <li><a class="dropdown-item py-2 fw-bold" href="change-password.php"><i class="fa-solid fa-key me-2 w-20px text-center text-warning"></i>Đổi mật khẩu</a></li>
                            
                            <li>
                                <a class="dropdown-item py-2 fw-bold d-flex justify-content-between align-items-center theme-toggle-btn" href="#">
                                    <span><i class="fa-solid fa-circle-half-stroke me-2 w-20px text-center text-info"></i>Giao diện</span>
                                    <span class="badge bg-secondary rounded-pill theme-mode-label">Sáng</span>
                                </a>
                            </li>

                            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-primary fw-bold" href="admin/index.php"><i class="fa-solid fa-shield-halved me-2 w-20px text-center"></i>Trang Quản Trị</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger fw-bold" href="logout.php"><i class="fa-solid fa-sign-out-alt me-2 w-20px text-center"></i>Đăng xuất</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item py-2 fw-bold" href="login.php"><i class="fa-solid fa-sign-in-alt me-2 w-20px text-center text-primary"></i>Đăng nhập</a></li>
                            <li><a class="dropdown-item py-2 fw-bold" href="register.php"><i class="fa-solid fa-user-plus me-2 w-20px text-center text-success"></i>Đăng ký</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 fw-bold d-flex justify-content-between align-items-center theme-toggle-btn" href="#">
                                    <span><i class="fa-solid fa-circle-half-stroke me-2 w-20px text-center text-info"></i>Giao diện</span>
                                    <span class="badge bg-secondary rounded-pill theme-mode-label">Sáng</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Cart -->
                <a href="cart.php" class="header-action-btn text-decoration-none">
                    <i class="fa-solid fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; transform: translate(-30%, -30%) !important;">
                            <?= $cart_count ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- MOBILE OFFCANVAS DRAWER (MENU TRƯỢT DÀNH RIÊNG CHO ĐIỆN THOẠI) -->
<div class="offcanvas offcanvas-start mobile-drawer d-md-none border-0" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
    <div class="mobile-drawer-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-shoe-prints text-warning fs-5"></i>
            <span class="fw-bold fs-6 tracking-wide" id="mobileNavDrawerLabel">SHOESSTORE</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- User Header in Drawer -->
    <div class="p-3 border-bottom bg-light">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 14px;">
                    <?= strtoupper(mb_substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?></div>
                    <small class="text-muted" style="font-size: 11px;"><i class="fa-solid fa-circle-check text-success me-1"></i>Đã đăng nhập</small>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex gap-2">
                <a href="login.php" class="btn btn-dark btn-sm flex-grow-1 fw-bold rounded-3">Đăng Nhập</a>
                <a href="register.php" class="btn btn-outline-dark btn-sm flex-grow-1 fw-bold rounded-3">Đăng Ký</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="offcanvas-body p-2" style="overflow-y: auto;">
        <nav class="d-flex flex-column gap-1">
            <a href="index.php" class="mobile-drawer-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-house text-primary"></i> Trang Chủ
            </a>
            <a href="all-products.php" class="mobile-drawer-link <?= ($current_page == 'all-products.php' && empty($_GET['discount'])) ? 'active' : '' ?>">
                <i class="fa-solid fa-shoe-prints text-success"></i> Tất Cả Sản Phẩm
            </a>

            <!-- Danh Mục Sản Phẩm Dropdown -->
            <div class="accordion accordion-flush" id="accordionMobileCat">
                <div class="accordion-item border-0 bg-transparent">
                    <button class="accordion-button collapsed px-3 py-2 fw-bold text-dark rounded-3 shadow-none bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobileCats" aria-expanded="false" style="font-size: 0.92rem;">
                        <i class="fa-solid fa-layer-group text-warning me-2" style="width: 20px; text-align: center;"></i> Danh Mục Giày Dép
                    </button>
                    <div id="collapseMobileCats" class="accordion-collapse collapse" data-bs-parent="#accordionMobileCat">
                        <div class="accordion-body p-1 ps-4">
                            <?php foreach($mega_root_categories as $root): ?>
                                <a href="all-products.php?category=<?= $root['id'] ?>" class="mobile-drawer-link py-2" style="font-size: 0.88rem;">
                                    <i class="<?= get_category_icon($root) ?> text-secondary"></i> <?= htmlspecialchars($root['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Thương Hiệu Dropdown -->
                <div class="accordion-item border-0 bg-transparent">
                    <button class="accordion-button collapsed px-3 py-2 fw-bold text-dark rounded-3 shadow-none bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobileBrands" aria-expanded="false" style="font-size: 0.92rem;">
                        <i class="fa-regular fa-star text-info me-2" style="width: 20px; text-align: center;"></i> Thương Hiệu Hàng Hiệu
                    </button>
                    <div id="collapseMobileBrands" class="accordion-collapse collapse" data-bs-parent="#accordionMobileCat">
                        <div class="accordion-body p-1 ps-4">
                            <?php foreach($mega_brands as $brand): ?>
                                <a href="all-products.php?brand=<?= $brand['id'] ?>" class="mobile-drawer-link py-2" style="font-size: 0.88rem;">
                                    <i class="fa-solid fa-tag text-secondary"></i> <?= htmlspecialchars($brand['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sự Kiện Sale -->
            <?php if (!empty($active_events)): ?>
                <?php foreach($active_events as $evt): ?>
                    <a href="sale-event.php?slug=<?= urlencode($evt['slug']) ?>" class="mobile-drawer-link">
                        <i class="<?= htmlspecialchars($evt['icon'] ?: 'fa-solid fa-fire') ?>" style="color: <?= htmlspecialchars($evt['color_theme']) ?>;"></i>
                        <span style="color: <?= htmlspecialchars($evt['color_theme']) ?>;"><?= htmlspecialchars($evt['name']) ?></span>
                        <span class="badge bg-danger rounded-pill ms-auto small" style="font-size: 9px;">SALE</span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="all-products.php?discount=1" class="mobile-drawer-link text-danger">
                    <i class="fa-solid fa-fire text-danger"></i> Giảm Giá &amp; Khuyến Mãi
                </a>
            <?php endif; ?>

            <div class="my-2 border-top"></div>

            <a href="size-guide.php" class="mobile-drawer-link <?= ($current_page == 'size-guide.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-ruler-combined text-primary"></i> Hướng Dẫn Chọn Size
            </a>
            <a href="profile.php?tab=vouchers" class="mobile-drawer-link">
                <i class="fa-solid fa-ticket text-warning"></i> Ví Voucher Giảm Giá
            </a>
            <a href="my-orders.php" class="mobile-drawer-link">
                <i class="fa-solid fa-box text-success"></i> Đơn Hàng Của Tôi
            </a>
            <a href="wishlist.php" class="mobile-drawer-link">
                <i class="fa-solid fa-heart text-danger"></i> Danh Sách Yêu Thích
            </a>

            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])): ?>
                <div class="my-2 border-top"></div>
                <a href="admin/index.php" class="mobile-drawer-link text-primary fw-bold">
                    <i class="fa-solid fa-gauge-high text-primary"></i> Trang Quản Trị Admin
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="my-2 border-top"></div>
                <a href="logout.php" class="mobile-drawer-link text-danger">
                    <i class="fa-solid fa-arrow-right-from-bracket text-danger"></i> Đăng Xuất
                </a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- NAVIGATION (DESKTOP) -->
<nav class="main-nav d-none d-md-block" style="position: relative; z-index: 1025 !important; overflow: visible !important;">
    <div class="container position-relative">
        <ul class="nav justify-content-center">
            <li class="nav-item">
                <a class="nav-link nav-link-custom text-uppercase <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">
                    <i class="fa-solid fa-house me-1"></i> Trang Chủ
                </a>
            </li>

            <!-- Mega Menu: Tất Cả Sản Phẩm (click arrow để mở dropdown) -->
            <li class="nav-item mega-dropdown position-static" id="megaDropdownItem">
                <div class="nav-link nav-link-custom mega-dropdown-toggle text-uppercase d-inline-flex align-items-center <?= ($current_page == 'all-products.php') ? 'active' : '' ?>">
                    <a class="text-decoration-none text-reset me-1 d-inline-flex align-items-center" href="all-products.php" style="padding: 0 !important; margin: 0 !important; font-weight: 700;">
                        <i class="fa-solid fa-shoe-prints me-1"></i> Sản Phẩm
                    </a>
                    <span class="mega-arrow-btn d-inline-flex align-items-center" id="megaMenuToggle" title="Mở/đóng menu sản phẩm" style="padding: 0 !important; margin: 0 !important; cursor: pointer;">
                        <i class="fa-solid fa-angle-down"></i>
                    </span>
                </div>
                
                <div class="mega-dropdown-menu">
                    <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
                        <!-- Cột 1: Thương Hiệu -->
                        <div class="col">
                            <div class="mega-column pe-lg-2">
                                <h6 class="mega-title">
                                    <a href="all-products.php" class="text-decoration-none d-flex align-items-center justify-content-between" style="color: inherit;" title="Xem tất cả thương hiệu">
                                        <span><i class="fa-regular fa-star me-2"></i>Thương Hiệu</span>
                                        <i class="fa-solid fa-angle-right small opacity-50"></i>
                                    </a>
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach($mega_brands as $brand): ?>
                                        <li><a href="all-products.php?brand=<?= $brand['id'] ?>" class="mega-link"><?= htmlspecialchars($brand['name']) ?></a></li>
                                    <?php endforeach; ?>
                                    <?php if(empty($mega_brands)): ?>
                                        <li class="text-muted small">Chưa có dữ liệu</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Các Cột Danh Mục Gốc (Tự động cập nhật khi Admin thêm mới danh mục gốc) -->
                        <?php foreach($mega_root_categories as $root): ?>
                            <div class="col">
                                <div class="mega-column px-lg-2 border-start-lg">
                                    <h6 class="mega-title">
                                        <a href="all-products.php?category=<?= $root['id'] ?>" class="text-decoration-none d-flex align-items-center justify-content-between" style="color: inherit;" title="Xem tất cả <?= htmlspecialchars($root['name']) ?>">
                                            <span><i class="<?= get_category_icon($root) ?> me-2"></i><?= htmlspecialchars($root['name']) ?></span>
                                            <i class="fa-solid fa-angle-right small opacity-50"></i>
                                        </a>
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <?php if (!empty($root['subs'])): ?>
                                            <?php foreach($root['subs'] as $sub): ?>
                                                <li><a href="all-products.php?category=<?= $sub['id'] ?>" class="mega-link"><?= htmlspecialchars($sub['name']) ?></a></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li>
                                                <a href="all-products.php?category=<?= $root['id'] ?>" class="mega-link text-primary fw-medium">
                                                    <i class="fa-solid fa-arrow-right me-1 small"></i>Xem tất cả <?= htmlspecialchars($root['name']) ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </li>

            <!-- Menu Sự Kiện Sale (dynamic, tự ẩn khi hết ngày) -->
            <?php if (!empty($active_events)): ?>
                <?php foreach($active_events as $evt):
                    $is_evt_active = ($current_page == 'sale-event.php' && ($_GET['slug'] ?? '') == $evt['slug']);
                ?>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom text-uppercase fw-bold text-decoration-none d-inline-flex align-items-center gap-1 <?= $is_evt_active ? 'active' : '' ?>"
                       href="sale-event.php?slug=<?= urlencode($evt['slug']) ?>"
                    >
                        <?php if (!empty($evt['icon_image'])): ?>
                            <img src="<?= htmlspecialchars($evt['icon_image']) ?>" alt="" style="width: 18px; height: 18px; object-fit: contain; margin-right: 4px;">
                        <?php else: ?>
                            <i class="<?= htmlspecialchars($evt['icon'] ?: 'fa-solid fa-fire') ?> me-1" style="color: <?= htmlspecialchars($evt['color_theme']) ?>;"></i>
                        <?php endif; ?>
                        <span style="color: <?= htmlspecialchars($evt['color_theme']) ?>;"><?= htmlspecialchars($evt['name']) ?></span>
                        <span class="badge ms-1 rounded-pill" style="font-size: 9px; background:<?= htmlspecialchars($evt['color_theme']) ?>; color:#fff; vertical-align:middle; animation: pulse 1.5s infinite;">SALE</span>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
            <!-- Không có sự kiện nào đang active: hiển thị link giảm giá thông thường -->
            <li class="nav-item">
                <a class="nav-link nav-link-custom text-uppercase text-danger fw-bold" href="all-products.php?discount=1">
                    <i class="fa-solid fa-fire me-1"></i> Giảm Giá
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}
</style>

<script>
// Mega menu: click arrow to toggle, click outside to close
(function() {
    const megaItem = document.getElementById('megaDropdownItem');
    const megaToggle = document.getElementById('megaMenuToggle');
    if (!megaItem || !megaToggle) return;

    megaToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        megaItem.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
        if (!megaItem.contains(e.target)) {
            megaItem.classList.remove('show');
        }
    });

    // Close when clicking a mega link
    megaItem.querySelectorAll('.mega-link').forEach(link => {
        link.addEventListener('click', () => megaItem.classList.remove('show'));
    });
})();
</script>

<style>
/* ── Rich Live Search Dropdown Styles ───────────────────────── */
.search-live-dropdown {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
    border-radius: 18px;
    z-index: 999999 !important;
    top: 100%;
    left: 0;
    width: 100%;
    max-height: 520px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.search-live-dropdown::-webkit-scrollbar { width: 5px; }
.search-live-dropdown::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

[data-theme="dark"] .search-live-dropdown {
    background: #181b2e !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
}

.search-section-label {
    font-size: 0.78rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.65rem 1rem 0.35rem;
}
[data-theme="dark"] .search-section-label { color: #94a3b8; }

.brand-portal-pill {
    margin: 0.25rem 1rem 0.5rem;
    padding: 7px 14px;
    background: linear-gradient(135deg, #090a1a, #1e1b4b);
    border-radius: 30px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ffffff !important;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.85rem;
    box-shadow: 0 4px 12px rgba(30, 27, 75, 0.25);
    transition: all 0.2s ease;
}
.brand-portal-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(30, 27, 75, 0.35);
    background: linear-gradient(135deg, #1e1b4b, #3730a3);
}
.brand-portal-pill .brand-badge {
    background: #ffffff;
    color: #090a1a;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.05em;
}

.suggestion-text-link {
    display: flex;
    align-items: center;
    padding: 0.4rem 1rem;
    color: #2563eb !important;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.15s ease;
}
.suggestion-text-link:hover {
    background: #f1f5f9;
    color: #1d4ed8 !important;
    padding-left: 1.25rem;
}
[data-theme="dark"] .suggestion-text-link { color: #60a5fa !important; }
[data-theme="dark"] .suggestion-text-link:hover { background: rgba(255,255,255,0.05); }

.search-product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.65rem 1rem;
    text-decoration: none;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.15s ease;
}
.search-product-item:last-child { border-bottom: none; }
.search-product-item:hover {
    background: #f8fafc;
}
[data-theme="dark"] .search-product-item {
    border-bottom-color: rgba(255, 255, 255, 0.05);
}
[data-theme="dark"] .search-product-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.search-product-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}
[data-theme="dark"] .search-product-thumb {
    background: #111322;
    border-color: rgba(255,255,255,0.08);
}

.search-product-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    margin-bottom: 3px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
[data-theme="dark"] .search-product-title { color: #f1f5f9; }

.search-product-pricing {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}
.search-price-main {
    font-size: 0.95rem;
    font-weight: 800;
    color: #dc2626;
}
.search-price-old {
    font-size: 0.78rem;
    color: #94a3b8;
    text-decoration: line-through;
}
.search-discount-badge {
    font-size: 0.72rem;
    font-weight: 800;
    color: #dc2626;
}
.search-promo-tag {
    font-size: 0.74rem;
    font-weight: 700;
    color: #d97706;
    margin-top: 2px;
}
[data-theme="dark"] .search-promo-tag { color: #fbbf24; }

.search-footer-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    color: #2563eb !important;
    font-weight: 700;
    font-size: 0.85rem;
    text-decoration: none;
    border-top: 1px solid #e2e8f0;
    transition: background 0.15s ease;
}
.search-footer-btn:hover {
    background: #f1f5f9;
    color: #1d4ed8 !important;
}
[data-theme="dark"] .search-footer-btn {
    background: #111322;
    border-top-color: rgba(255, 255, 255, 0.08);
    color: #60a5fa !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearchInput');
    const searchResults = document.getElementById('searchLiveResults');
    let debounceTimer;

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 1) {
                searchResults.classList.add('d-none');
                searchResults.innerHTML = '';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch(`api/search-live.php?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        searchResults.innerHTML = '';
                        
                        const hasBrand = data.brand && data.brand.name;
                        const hasSuggestions = data.suggestions && data.suggestions.length > 0;
                        const hasProducts = data.products && data.products.length > 0;

                        if (!hasBrand && !hasSuggestions && !hasProducts) {
                            searchResults.innerHTML = `
                                <div class="p-4 text-center text-muted">
                                    <i class="fa-solid fa-magnifying-glass fs-3 d-block mb-2 opacity-50"></i>
                                    <div class="fw-bold">Không tìm thấy sản phẩm cho "${query}"</div>
                                    <small class="opacity-75">Hãy thử tìm theo tên hãng (Nike, Adidas, Jordan...) hoặc loại giày (Sneaker, Dép, Chạy bộ)</small>
                                </div>
                            `;
                            searchResults.classList.remove('d-none');
                            return;
                        }

                        let html = '';

                        // 1. Phần "Có phải bạn muốn tìm"
                        if (hasBrand || hasSuggestions) {
                            html += `<div class="search-section-label">Có phải bạn muốn tìm</div>`;
                            
                            // Banner Chuyên trang thương hiệu
                            if (hasBrand) {
                                html += `
                                    <a href="${data.brand.url}" class="brand-portal-pill">
                                        <span class="brand-badge">${data.brand.name.toUpperCase()}</span>
                                        <span>Chuyên trang ${data.brand.name}</span>
                                        <i class="fa-solid fa-chevron-right ms-auto small"></i>
                                    </a>
                                `;
                            }

                            // Các từ khóa gợi ý liên quan
                            if (hasSuggestions) {
                                data.suggestions.forEach(item => {
                                    html += `
                                        <a href="${item.url}" class="suggestion-text-link">
                                            <i class="fa-solid fa-magnifying-glass me-2 opacity-50 small"></i>
                                            <span>${item.text}</span>
                                        </a>
                                    `;
                                });
                            }

                            html += `<hr class="my-2 border-light-subtle">`;
                        }

                        // 2. Phần "Sản phẩm gợi ý"
                        if (hasProducts) {
                            html += `<div class="search-section-label">Sản phẩm gợi ý</div>`;
                            
                            data.products.forEach(p => {
                                const imgUrl = p.main_image || 'assets/images/no-image.png';
                                html += `
                                    <a href="${p.url}" class="search-product-item">
                                        <img src="${imgUrl}" alt="${p.name}" class="search-product-thumb">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="search-product-title">${p.name}</div>
                                            <div class="search-product-pricing">
                                                <span class="search-price-main">${p.formatted_price}</span>
                                                ${p.formatted_old_price ? `<span class="search-price-old">${p.formatted_old_price}</span>` : ''}
                                                ${p.discount_percent > 0 ? `<span class="search-discount-badge">-${p.discount_percent}%</span>` : ''}
                                            </div>
                                            ${p.promo_tag ? `<div class="search-promo-tag">${p.promo_tag}</div>` : ''}
                                        </div>
                                    </a>
                                `;
                            });
                        }

                        // 3. Footer xem tất cả kết quả
                        html += `
                            <a href="all-products.php?keyword=${encodeURIComponent(query)}" class="search-footer-btn">
                                <span>Xem tất cả ${data.total_count} kết quả cho "${query}"</span>
                                <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        `;

                        searchResults.innerHTML = html;
                        searchResults.classList.remove('d-none');
                        searchResults.classList.add('show');
                        searchResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                    });
            }, 180); // 180ms phản hồi siêu tốc
        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('d-none');
                searchResults.classList.remove('show');
                searchResults.style.display = 'none';
            }
        });

        // Mở lại kết quả khi click lại vào ô tìm kiếm nếu có nội dung
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 1 && searchResults.innerHTML.trim() !== '') {
                searchResults.classList.remove('d-none');
                searchResults.classList.add('show');
                searchResults.style.display = 'block';
            }
        });
    }
});
</script>