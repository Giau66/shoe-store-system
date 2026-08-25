<?php
// Query site_settings
$settings = [];
$settings_query = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($settings_query) {
    while ($row = $settings_query->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Default values if not set
$site_name = $settings['site_name'] ?? 'SHOES STORE';
$site_desc = !empty($settings['footer_about']) ? $settings['footer_about'] : ($settings['site_description'] ?? 'Thương hiệu Giày & Dép uy tín, chất lượng hàng đầu.');
$contact_phone = !empty($settings['contact_hotline']) ? $settings['contact_hotline'] : ($settings['contact_phone'] ?? '0912.345.678');
$contact_email = $settings['contact_email'] ?? 'support@shoesstore.vn';
$contact_address = $settings['contact_address'] ?? 'Số 123 Đường Phạm Hùng, Phường 9, TP. Vĩnh Long';
$copyright = !empty($settings['footer_copyright']) ? $settings['footer_copyright'] : ($settings['copyright_text'] ?? ('© ' . date('Y') . ' SHOES STORE. Tất cả quyền được bảo lưu.'));

// Query social_links
$social_links = [];
$social_query = $conn->query("SELECT platform, url, icon FROM social_links WHERE status = 1 ORDER BY sort_order ASC");
if ($social_query) {
    while ($row = $social_query->fetch_assoc()) {
        $social_links[] = $row;
    }
}
?>

<footer class="site-footer mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Col 1: Store Info -->
            <div class="col-12 col-md-6 col-lg-3">
                <a href="index.php" class="text-decoration-none brand-logo-text mb-3 d-inline-block">
                    <?= htmlspecialchars($site_name) ?>
                </a>
                <p class="text-muted small mt-2 pe-md-3">
                    <?= htmlspecialchars($site_desc) ?>
                </p>
                <div class="mt-4">
                    <img src="assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid" style="max-height: 35px;" onerror="this.style.display='none'">
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Về Chúng Tôi</h5>
                <ul class="list-unstyled">
                    <li><a href="about.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Giới thiệu</a></li>
                    <li><a href="contact.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Liên hệ</a></li>
                    <li><a href="terms.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Điều khoản dịch vụ</a></li>
                    <li><a href="privacy.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Chính sách bảo mật</a></li>
                </ul>
            </div>

            <!-- Col 3: Customer Support -->
            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Hỗ Trợ Khách Hàng</h5>
                <ul class="list-unstyled">
                    <li><a href="shipping-policy.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Chính sách vận chuyển</a></li>
                    <li><a href="return-policy.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Chính sách đổi trả</a></li>
                    <li><a href="size-guide.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Hướng dẫn chọn size</a></li>
                    <li><a href="faq.php" class="footer-link"><i class="fa-solid fa-angle-right me-2 small"></i>Câu hỏi thường gặp (FAQ)</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact & Social -->
            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Liên Hệ</h5>
                <ul class="list-unstyled text-muted small mb-4">
                    <li class="mb-3 d-flex">
                        <i class="fa-solid fa-location-dot mt-1 me-3 text-warning"></i>
                        <span><?= htmlspecialchars($contact_address) ?></span>
                    </li>
                    <li class="mb-3 d-flex">
                        <i class="fa-solid fa-phone mt-1 me-3 text-warning"></i>
                        <span><?= htmlspecialchars($contact_phone) ?></span>
                    </li>
                    <li class="mb-3 d-flex">
                        <i class="fa-solid fa-envelope mt-1 me-3 text-warning"></i>
                        <span><?= htmlspecialchars($contact_email) ?></span>
                    </li>
                </ul>
                
                <div class="social-links d-flex">
                    <?php foreach($social_links as $link): ?>
                        <a href="<?= htmlspecialchars($link['url']) ?>" class="social-icon" target="_blank" rel="noopener noreferrer" title="<?= htmlspecialchars($link['platform']) ?>">
                            <i class="<?= htmlspecialchars($link['icon']) ?>"></i>
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($social_links)): ?>
                        <!-- Fallback social icons if db is empty -->
                        <a href="#" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="#" class="social-icon"><i class="fa-brands fa-youtube"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="copyright-bar mt-4">
        <div class="container text-center">
            <?= htmlspecialchars($copyright) ?>
        </div>
    </div>
</footer>

<!-- AI Chat Widget -->
<?php 
$ai_chat_path = __DIR__ . '/ai-chat.php';
if (file_exists($ai_chat_path)) {
    include_once $ai_chat_path; 
}
?>

<!-- MOBILE BOTTOM NAVIGATION BAR (THANH ĐIỀU HƯỚNG CỐ ĐỊNH DƯỚI ĐÁY TRÊN ĐIỆN THOẠI) -->
<?php
$footer_current_page = basename($_SERVER['PHP_SELF']);
$footer_cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $it) {
        $footer_cart_count += (isset($it['quantity']) ? (int)$it['quantity'] : 1);
    }
}
?>
<div class="mobile-bottom-nav d-md-none">
    <a href="index.php" class="mobile-bottom-nav-item <?= ($footer_current_page == 'index.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i>
        <span>Trang Chủ</span>
    </a>
    <a href="all-products.php" class="mobile-bottom-nav-item <?= ($footer_current_page == 'all-products.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-shoe-prints"></i>
        <span>Sản Phẩm</span>
    </a>
    <a href="cart.php" class="mobile-bottom-nav-item <?= ($footer_current_page == 'cart.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-cart-shopping"></i>
        <span>Giỏ Hàng</span>
        <?php if ($footer_cart_count > 0): ?>
            <span class="mobile-nav-badge" id="mobileBottomCartBadge"><?= $footer_cart_count ?></span>
        <?php endif; ?>
    </a>
    <a href="wishlist.php" class="mobile-bottom-nav-item <?= ($footer_current_page == 'wishlist.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-heart"></i>
        <span>Yêu Thích</span>
    </a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="profile.php" class="mobile-bottom-nav-item <?= (in_array($footer_current_page, ['profile.php', 'my-orders.php'])) ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i>
            <span>Cá Nhân</span>
        </a>
    <?php else: ?>
        <a href="login.php" class="mobile-bottom-nav-item <?= ($footer_current_page == 'login.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-right-to-bracket"></i>
            <span>Đăng Nhập</span>
        </a>
    <?php endif; ?>
</div>

<!-- Bootstrap 5.3.2 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Global Password Toggle JS Script -->
<script>
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

<!-- Global Center Popups for Session Flash Messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showCenterAlert === 'function') {
        showCenterAlert('success', 'Thành công!', <?= json_encode($_SESSION['flash_success'], JSON_UNESCAPED_UNICODE) ?>, 2200);
    }
});
</script>
<?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showCenterAlert === 'function') {
        showCenterAlert('error', 'Thông báo!', <?= json_encode($_SESSION['flash_error'], JSON_UNESCAPED_UNICODE) ?>, 2500);
    }
});
</script>
<?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
</body>
</html>