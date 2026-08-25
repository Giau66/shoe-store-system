<?php 
require_once __DIR__ . '/../../config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// Kiểm tra bổ sung nếu nhân viên đã nghỉ việc thì chặn tuyệt đối và đăng xuất ngay
if ($user_role !== 'admin') {
    $emp_gate = $conn->query("SELECT status FROM employees WHERE user_id = " . intval($_SESSION['user_id']) . " LIMIT 1");
    if ($emp_gate && $eg_row = $emp_gate->fetch_assoc()) {
        if (intval($eg_row['status']) === 0) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role'], $_SESSION['cart']);
            header("Location: ../login.php?error=employee_resigned");
            exit();
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);

// Lấy thông tin user hiện tại
$admin_user_id = $_SESSION['user_id'];
$admin_info = $conn->query("SELECT fullname, email FROM users WHERE id = $admin_user_id")->fetch_assoc();
$admin_name = $admin_info['fullname'] ?? 'Admin';
$admin_initial = mb_strtoupper(mb_substr($admin_name, 0, 1, 'UTF-8'), 'UTF-8');
$admin_role_label = ($user_role === 'admin') ? 'Quản trị viên' : 'Nhân viên';

// Lấy số đơn hàng pending để hiển thị badge
$pending_count = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'")->fetch_assoc()['c'] ?? 0;

// Greeting theo giờ
$hour = (int)date('H');
if ($hour < 6)        $greeting_text = "Chúc ngủ ngon";
elseif ($hour < 12)   $greeting_text = "Chào buổi sáng";
elseif ($hour < 18)   $greeting_text = "Chào buổi chiều";
else                  $greeting_text = "Chào buổi tối";

// Hiệu ứng mở cánh cửa 2 bên khi đăng nhập vào trang quản trị
$show_login_transition = false;
if (!empty($_SESSION['admin_login_transition']) || isset($_GET['login_transition'])) {
    $show_login_transition = true;
    unset($_SESSION['admin_login_transition']);
}
?>
<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOES Admin — Quản Trị Hệ Thống</title>

    <!-- Theme Initializer Script (Prevents Theme Flash) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('admin_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Bootstrap JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Admin Dual Theme CSS -->
    <link rel="stylesheet" href="../assets/css/admin-dark.css?v=20260818_fixed_sidebar">
    <!-- Legacy compatibility -->
    <link rel="stylesheet" href="../assets/css/admin-modern.css?v=20260818_fixed_sidebar">
    <link rel="stylesheet" href="../assets/css/vouchers.css?v=20260815">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css?v=20260818_fixed_sidebar">

    <?php if ($show_login_transition): ?>
    <!-- ── CSS HIỆU ỨNG TRƯỢT 2 BÊN KHI ĐĂNG NHẬP ── -->
    <style>
    .admin-login-curtain {
        position: fixed;
        inset: 0;
        z-index: 9999999;
        pointer-events: none !important;
        display: flex;
        overflow: hidden;
        background: transparent;
    }
    .admin-curtain-half {
        width: 50vw;
        height: 100vh;
        position: absolute;
        top: 0;
        bottom: 0;
        background:
            radial-gradient(circle at 50% 50%, rgba(124, 58, 237, 0.18) 0%, transparent 65%),
            radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.12) 0%, transparent 50%),
            linear-gradient(135deg, #090a14 0%, #15172b 50%, #0d0e1b 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        will-change: transform;
        transition: transform 0.95s cubic-bezier(0.77, 0, 0.175, 1);
        overflow: hidden;
    }
    .admin-curtain-left {
        left: 0;
        transform: translate3d(0, 0, 0);
        border-right: 2px solid rgba(168, 85, 247, 0.6);
        box-shadow: 12px 0 50px rgba(124, 58, 237, 0.4);
    }
    .admin-curtain-right {
        right: 0;
        transform: translate3d(0, 0, 0);
        border-left: 2px solid rgba(6, 182, 212, 0.6);
        box-shadow: -12px 0 50px rgba(6, 182, 212, 0.4);
    }
    .curtain-grid-pattern {
        position: absolute;
        inset: 0;
        background-image: 
            linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
        background-size: 36px 36px;
        pointer-events: none;
    }
    .curtain-ambient-icon {
        font-size: 22vw;
        color: rgba(255, 255, 255, 0.02);
        position: absolute;
        pointer-events: none;
        user-select: none;
    }
    .admin-curtain-left .curtain-ambient-icon { right: -5vw; transform: rotate(-15deg); }
    .admin-curtain-right .curtain-ambient-icon { left: -5vw; transform: rotate(15deg); }

    /* Trung tâm hiển thị chào mừng */
    .admin-curtain-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        pointer-events: none;
        transition: opacity 0.4s ease, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        padding: 2rem;
        width: 90%;
        max-width: 460px;
    }
    .admin-curtain-badge {
        position: relative;
        width: 88px;
        height: 88px;
        border-radius: 26px;
        background: linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        color: #ffffff;
        box-shadow: 0 0 50px rgba(124, 58, 237, 0.7), 0 0 25px rgba(6, 182, 212, 0.5);
        margin-bottom: 1.25rem;
        animation: curtainBadgePulse 1.6s ease-in-out infinite;
    }
    .badge-pulse-ring {
        position: absolute;
        inset: -8px;
        border-radius: 32px;
        border: 2px solid rgba(168, 85, 247, 0.6);
        animation: pulseRing 1.8s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
    }
    @keyframes pulseRing {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.18); opacity: 0; }
        100% { transform: scale(0.95); opacity: 0; }
    }
    @keyframes curtainBadgePulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 40px rgba(124, 58, 237, 0.6); }
        50% { transform: scale(1.05); box-shadow: 0 0 70px rgba(124, 58, 237, 0.9), 0 0 35px rgba(6, 182, 212, 0.8); }
    }
    .admin-curtain-title {
        font-family: 'Inter', sans-serif;
        font-size: 1.5rem;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        background: linear-gradient(90deg, #ffffff, #c4b5fd, #67e8f9, #ffffff);
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0.4rem;
        animation: textShineAnim 2.8s linear infinite;
    }
    @keyframes textShineAnim {
        to { background-position: 200% center; }
    }
    .admin-curtain-sub {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 0.8rem;
    }
    .admin-curtain-sub strong {
        color: #a78bfa;
    }
    .admin-curtain-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 16px;
        border-radius: 20px;
        background: rgba(34, 197, 94, 0.16);
        border: 1px solid rgba(34, 197, 94, 0.45);
        color: #4ade80;
        font-size: 0.82rem;
        font-weight: 600;
        backdrop-filter: blur(8px);
    }

    /* KHI MỞ CỬA: Trượt ra 2 bên */
    .admin-login-curtain.curtain-open .admin-curtain-left {
        transform: translate3d(-100%, 0, 0);
    }
    .admin-login-curtain.curtain-open .admin-curtain-right {
        transform: translate3d(100%, 0, 0);
    }
    .admin-login-curtain.curtain-open .admin-curtain-center {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.65);
    }

    /* TRANG QUẢN TRỊ TRƯỢT VÀO TỪ PHÍA SAU */
    .admin-wrapper.admin-page-entering {
        animation: adminPageSlideInAnim 1.1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        will-change: transform, opacity, filter;
    }
    @keyframes adminPageSlideInAnim {
        0% {
            opacity: 0;
            transform: scale(0.92) translateY(36px);
            filter: blur(8px);
        }
        35% {
            opacity: 0.6;
        }
        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
            filter: blur(0);
        }
    }
    </style>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="../assets/js/admin-modern.js?v=20260818" defer></script>
    <script src="../assets/js/vouchers.js?v=20260818" defer></script>
</head>
<body>

<?php if ($show_login_transition): ?>
<!-- ═══════════════════════════════════════════════════════════
     HIỆU ỨNG CÁNH CỬA TRƯỢT 2 BÊN KHI ĐĂNG NHẬP VÀO ADMIN
══════════════════════════════════════════════════════════════ -->
<div class="admin-login-curtain" id="adminLoginCurtain">
    <!-- Cánh cửa bên trái -->
    <div class="admin-curtain-half admin-curtain-left">
        <div class="curtain-grid-pattern"></div>
        <i class="fa-solid fa-shield-halved curtain-ambient-icon"></i>
    </div>

    <!-- Cánh cửa bên phải -->
    <div class="admin-curtain-half admin-curtain-right">
        <div class="curtain-grid-pattern"></div>
        <i class="fa-solid fa-shoe-prints curtain-ambient-icon"></i>
    </div>

    <!-- Huy hiệu & Thông tin trung tâm -->
    <div class="admin-curtain-center">
        <div class="admin-curtain-badge">
            <i class="fa-solid fa-shield-halved"></i>
            <div class="badge-pulse-ring"></div>
        </div>
        <div class="admin-curtain-title">HỆ THỐNG QUẢN TRỊ</div>
        <div class="admin-curtain-sub">
            <span>Chào mừng trở lại, <strong><?= htmlspecialchars($admin_name) ?></strong></span>
        </div>
        <div class="admin-curtain-status">
            <i class="fa-solid fa-circle-check"></i> Đăng nhập thành công · Đang vào trang...
        </div>
    </div>
</div>

<script>
(function() {
    function startCurtainEffect() {
        const curtain = document.getElementById('adminLoginCurtain');
        const wrapper = document.querySelector('.admin-wrapper');
        if (!curtain) return;

        if (wrapper) {
            wrapper.classList.add('admin-page-entering');
        }

        // Bước 1: Sau 380ms, 2 cánh cửa bắt đầu trượt ra 2 bên
        setTimeout(function() {
            curtain.classList.add('curtain-open');
        }, 380);

        // Bước 2: Sau 1350ms, ẩn hoàn toàn overlay và dọn dẹp khỏi DOM
        setTimeout(function() {
            curtain.style.opacity = '0';
            curtain.style.transition = 'opacity 0.35s ease';
            setTimeout(function() {
                curtain.remove();
                if (wrapper) {
                    wrapper.classList.remove('admin-page-entering');
                }
            }, 350);
        }, 1350);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startCurtainEffect);
    } else {
        startCurtainEffect();
    }
})();
</script>
<?php endif; ?>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Toggle -->
<button class="btn d-md-none position-fixed m-3 z-3" id="sidebarToggle"
        style="top:0;left:0;background:var(--grad-purple);color:white;border:none;border-radius:10px;width:38px;height:38px;padding:0;box-shadow:0 4px 12px rgba(124,58,237,0.4);">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="admin-wrapper">

    <!-- ══════════ SIDEBAR ══════════ -->
    <aside class="admin-sidebar" id="sidebar">

        <!-- Brand -->
        <a href="index.php" class="sidebar-brand text-decoration-none">
            <div class="sidebar-brand-icon">
                <i class="fa-solid fa-shoe-prints"></i>
            </div>
            <div class="sidebar-brand-text">
                <h6>SHOES ADMIN</h6>
                <small>System Suite 2026</small>
            </div>
            <button class="btn btn-sm d-md-none ms-auto border-0 p-0" id="sidebarClose"
                    style="color:rgba(255,255,255,0.4);font-size:1rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </a>

        <!-- Nav -->
        <div class="sidebar-nav flex-column">
            <div class="sidebar-section-label">Chính</div>
            <ul class="nav nav-pills flex-column">
                <li>
                    <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="nav-link <?= strpos($current_page, 'order') !== false ? 'active' : ''; ?>">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Quản Lý Đơn Hàng
                        <?php if ($pending_count > 0): ?>
                            <span class="ms-auto badge rounded-pill" style="background:rgba(245,158,11,0.25);color:#fbbf24;font-size:10px;"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="nav-link <?= strpos($current_page, 'product') !== false ? 'active' : ''; ?>">
                        <i class="fa-solid fa-boxes-stacked"></i> Quản Lý Sản Phẩm
                    </a>
                </li>
                <li>
                    <a href="inventory.php" class="nav-link <?= strpos($current_page, 'inventory') !== false ? 'active' : ''; ?>">
                        <i class="fa-solid fa-warehouse"></i> Tồn Kho
                    </a>
                </li>
                <li>
                    <a href="customers.php" class="nav-link <?= $current_page == 'customers.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-group"></i> Khách Hàng
                    </a>
                </li>
                <li>
                    <a href="comments.php" class="nav-link <?= $current_page == 'comments.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-comments"></i> Bình Luận
                    </a>
                </li>
            </ul>

            <?php if ($user_role === 'admin'): ?>
            <div class="sidebar-section-label">Quản Trị</div>
            <ul class="nav nav-pills flex-column">
                <li>
                    <a href="employees.php" class="nav-link <?= $current_page == 'employees.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users-gear"></i> Hồ Sơ Nhân Viên
                    </a>
                </li>
                <li>
                    <a href="employee-schedule.php" class="nav-link <?= $current_page == 'employee-schedule.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-week"></i> Lịch Làm Việc
                    </a>
                </li>
                <li>
                    <a href="employee-salaries.php" class="nav-link <?= $current_page == 'employee-salaries.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-money-check-dollar"></i> Lương &amp; Bảng Lương
                    </a>
                </li>
                <li>
                    <a href="categories-brands.php" class="nav-link <?= $current_page == 'categories-brands.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-tags"></i> Danh Mục &amp; Thương Hiệu
                    </a>
                </li>
                <li>
                    <a href="banners.php" class="nav-link <?= $current_page == 'banners.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-images"></i> Banner
                    </a>
                </li>
                <li>
                    <a href="vouchers.php" class="nav-link <?= $current_page == 'vouchers.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-ticket"></i> Voucher
                    </a>
                </li>
                <li>
                    <a href="sale-events.php" class="nav-link <?= $current_page == 'sale-events.php' ? 'active' : ''; ?>"
                       style="<?= $current_page == 'sale-events.php' ? '' : 'color:#fcd34d;' ?>">
                        <i class="fa-solid fa-calendar-star"></i> Sự Kiện Sale
                    </a>
                </li>
                <li>
                    <a href="shipping-fees.php" class="nav-link <?= $current_page == 'shipping-fees.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-truck-ramp-box"></i> Phí Vận Chuyển
                    </a>
                </li>
                <li>
                    <a href="size-guide.php" class="nav-link <?= $current_page == 'size-guide.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-ruler-combined"></i> Hướng Dẫn Chọn Size
                    </a>
                </li>
            </ul>

            <div class="sidebar-section-label">Hệ Thống</div>
            <ul class="nav nav-pills flex-column">
                <li>
                    <a href="users.php" class="nav-link <?= $current_page == 'users.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users-gear"></i> Tài Khoản &amp; Quyền
                    </a>
                </li>
                <li>
                    <a href="statistics.php" class="nav-link <?= $current_page == 'statistics.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Thống Kê Doanh Thu
                    </a>
                </li>
                <li>
                    <a href="site-settings.php" class="nav-link <?= $current_page == 'site-settings.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-gear"></i> Cấu Hình Website
                    </a>
                </li>
            </ul>
            <?php else: ?>
            <div class="sidebar-section-label">Cá Nhân</div>
            <ul class="nav nav-pills flex-column">
                <li>
                    <a href="my-profile.php" class="nav-link <?= $current_page == 'my-profile.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-id-badge"></i> Lương &amp; Lịch Làm Việc
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="sidebar-footer mt-auto">
            <div class="sidebar-user-card mb-2">
                <div class="sidebar-user-avatar"><?= $admin_initial ?></div>
                <div class="sidebar-user-info">
                    <span><?= htmlspecialchars($admin_name) ?></span>
                    <small><?= $admin_role_label ?></small>
                </div>
                <span class="glow-dot ms-auto"></span>
            </div>
            <div class="sidebar-footer-actions">
                <a href="../index.php" class="btn-sidebar-store">
                    <i class="fa-solid fa-store me-1"></i>Xem Web
                </a>
                <a href="../logout.php" class="btn-sidebar-logout">
                    <i class="fa-solid fa-right-from-bracket me-1"></i>Đăng xuất
                </a>
            </div>
        </div>

    </aside>
    <!-- ══════════ END SIDEBAR ══════════ -->

    <!-- ══════════ MAIN ══════════ -->
    <div class="admin-main">

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-greeting">
                <h5><?= $greeting_text ?>, <?= htmlspecialchars(explode(' ', $admin_name)[0]) ?>! ✨</h5>
                <p><?= date('l, d/m/Y', strtotime('now')) ?></p>
            </div>

            <div class="topbar-search d-none d-lg-flex">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Tìm kiếm đơn hàng, sản phẩm...">
            </div>

            <div class="topbar-actions">
                <?php if ($user_role === 'admin'): ?>
                <a href="product-add.php" class="btn-purple d-none d-md-inline-flex" style="font-size:0.8rem;padding:0.45rem 0.95rem;">
                    <i class="fa-solid fa-plus"></i> Thêm sản phẩm
                </a>
                <?php endif; ?>

                <!-- Theme Switcher Button -->
                <button type="button" class="topbar-icon-btn" id="themeToggleBtn" title="Chuyển chế độ Sáng / Tối">
                    <i class="fa-solid fa-sun" id="themeBtnIcon"></i>
                </button>

                <!-- Notifications Button -->
                <a href="orders.php" class="topbar-icon-btn" title="Đơn hàng chờ xử lý">
                    <i class="fa-solid fa-bell" style="font-size:0.9rem;"></i>
                    <?php if ($pending_count > 0): ?>
                    <span class="topbar-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
                </a>

                <!-- User Profile Badge -->
                <div class="topbar-user" title="<?= htmlspecialchars($admin_name) ?>">
                    <div class="topbar-user-avatar"><?= $admin_initial ?></div>
                    <span class="topbar-user-name"><?= htmlspecialchars(explode(' ', $admin_name)[0]) ?></span>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="admin-content">

    <script>
        // --- 1. THEME SWITCHER LOGIC ---
        const themeBtn     = document.getElementById('themeToggleBtn');
        const themeIcon    = document.getElementById('themeBtnIcon');

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('admin_theme', theme);
            if (themeIcon) {
                if (theme === 'light') {
                    themeIcon.className = 'fa-solid fa-moon';
                    themeBtn.setAttribute('title', 'Chuyển sang chế độ Tối');
                } else {
                    themeIcon.className = 'fa-solid fa-sun';
                    themeBtn.setAttribute('title', 'Chuyển sang chế độ Sáng');
                }
            }
            // Fire custom event for charts to update colors
            window.dispatchEvent(new CustomEvent('adminThemeChanged', { detail: { theme: theme } }));
        }

        // Initialize button icon based on current active theme
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(currentTheme);

        themeBtn?.addEventListener('click', function() {
            var active = document.documentElement.getAttribute('data-theme');
            var nextTheme = (active === 'light') ? 'dark' : 'light';
            applyTheme(nextTheme);
        });

        // --- 2. SIDEBAR MOBILE TOGGLE ---
        const sidebar       = document.getElementById('sidebar');
        const overlay       = document.getElementById('sidebarOverlay');
        const btnOpen       = document.getElementById('sidebarToggle');
        const btnClose      = document.getElementById('sidebarClose');

        function openSidebar()  { sidebar.classList.add('show');  overlay.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('show'); overlay.classList.remove('show'); }

        btnOpen?.addEventListener('click', openSidebar);
        btnClose?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

        // --- 3. UNIVERSAL PASSWORD TOGGLE ---
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.toggle-password-btn, .toggle-password');
            if (btn) {
                e.preventDefault();
                var container = btn.closest('.input-group') || btn.parentElement;
                var input = container ? container.querySelector('input') : null;
                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        btn.innerHTML = '<i class="fa-solid fa-eye-slash text-danger"></i>';
                    } else {
                        input.type = 'password';
                        btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
                    }
                }
            }
        });
    </script>
