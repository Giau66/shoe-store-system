<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    if (!empty($_POST)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản trị!']);
        exit();
    }
    header('Location: ../login.php');
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 1. AJAX: LƯU CẤU HÌNH NỘI DUNG CUỐI TRANG (LIVE AJAX)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_save_footer_settings'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $site_name        = trim($_POST['site_name'] ?? 'SHOES STORE');
    $site_description = trim($_POST['site_description'] ?? '');
    $contact_address  = trim($_POST['contact_address'] ?? '');
    $contact_hotline  = trim($_POST['contact_hotline'] ?? '');
    $contact_email    = trim($_POST['contact_email'] ?? '');
    $footer_copyright = trim($_POST['footer_copyright'] ?? '');

    if (empty($site_name)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Tên Cửa Hàng / Thương Hiệu!']);
        exit();
    }

    // Danh sách cài đặt cần lưu vào bảng site_settings
    $settings_to_save = [
        'site_name'        => $site_name,
        'site_description' => $site_description,
        'footer_about'     => $site_description,
        'contact_address'  => $contact_address,
        'contact_hotline'  => $contact_hotline,
        'contact_phone'    => $contact_hotline,
        'contact_email'    => $contact_email,
        'footer_copyright' => $footer_copyright
    ];

    // Lưu vào bảng site_settings
    foreach ($settings_to_save as $key => $val) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                                VALUES (?, ?, 'footer') 
                                ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $key, $val, $val);
        $stmt->execute();
        $stmt->close();
    }

    // Xử lý lưu các link mạng xã hội vào bảng social_links & site_settings
    $social_inputs = [
        'Facebook'  => ['url' => trim($_POST['social_facebook'] ?? ''),  'icon' => 'fa-brands fa-facebook-f',  'sort' => 1, 'key' => 'social_facebook'],
        'Instagram' => ['url' => trim($_POST['social_instagram'] ?? ''), 'icon' => 'fa-brands fa-instagram',   'sort' => 2, 'key' => 'social_instagram'],
        'TikTok'    => ['url' => trim($_POST['social_tiktok'] ?? ''),    'icon' => 'fa-brands fa-tiktok',      'sort' => 3, 'key' => 'social_tiktok'],
        'Zalo'      => ['url' => trim($_POST['social_zalo'] ?? ''),      'icon' => 'fa-solid fa-comment-dots', 'sort' => 4, 'key' => 'social_zalo'],
        'YouTube'   => ['url' => trim($_POST['social_youtube'] ?? ''),   'icon' => 'fa-brands fa-youtube',     'sort' => 5, 'key' => 'social_youtube']
    ];

    foreach ($social_inputs as $platform => $info) {
        $url = $info['url'];
        $icon = $info['icon'];
        $sort = $info['sort'];
        $status = !empty($url) ? 1 : 0;

        // Lưu vào bảng social_links
        $stmt_s = $conn->prepare("SELECT id FROM social_links WHERE platform = ? LIMIT 1");
        $stmt_s->bind_param('s', $platform);
        $stmt_s->execute();
        $res_s = $stmt_s->get_result();
        $stmt_s->close();

        if ($res_s && $res_s->num_rows > 0) {
            $stmt_u = $conn->prepare("UPDATE social_links SET url = ?, icon = ?, status = ?, sort_order = ? WHERE platform = ?");
            $stmt_u->bind_param('ssiis', $url, $icon, $status, $sort, $platform);
            $stmt_u->execute();
            $stmt_u->close();
        } else {
            $stmt_i = $conn->prepare("INSERT INTO social_links (platform, url, icon, sort_order, status) VALUES (?, ?, ?, ?, ?)");
            $stmt_i->bind_param('sssii', $platform, $url, $icon, $sort, $status);
            $stmt_i->execute();
            $stmt_i->close();
        }

        // Lưu vào site_settings
        $stmt_set = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                                    VALUES (?, ?, 'social') 
                                    ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt_set->bind_param('sss', $info['key'], $url, $url);
        $stmt_set->execute();
        $stmt_set->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu cấu hình nội dung cuối trang (Footer) thành công!'
    ]);
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 2. NẠP DỮ LIỆU ĐỂ HIỂN THỊ TRÊN GIAO DIỆN
// ═════════════════════════════════════════════════════════════════════
include_once 'includes/header.php';

// Lấy tất cả cài đặt từ bảng site_settings
$settings = [];
$settings_query = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($settings_query) {
    while ($row = $settings_query->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Lấy social_links từ bảng social_links
$social_data = [
    'Facebook'  => '',
    'Instagram' => '',
    'TikTok'    => '',
    'Zalo'      => '',
    'YouTube'   => ''
];
$social_query = $conn->query("SELECT platform, url FROM social_links");
if ($social_query) {
    while ($row = $social_query->fetch_assoc()) {
        $social_data[$row['platform']] = $row['url'];
    }
}

// Gán giá trị mặc định nếu chưa có
$site_name        = $settings['site_name'] ?? 'SHOES STORE';
$site_description = !empty($settings['footer_about']) ? $settings['footer_about'] : ($settings['site_description'] ?? 'Thương hiệu Sneaker hàng đầu mang đến trải nghiệm thời trang dịu nhẹ, thanh lịch và chất lượng cam kết chính hãng.');
$contact_address  = $settings['contact_address'] ?? 'TP. Vĩnh Long, Việt Nam';
$contact_hotline  = !empty($settings['contact_hotline']) ? $settings['contact_hotline'] : ($settings['contact_phone'] ?? '0901.234.567');
$contact_email    = $settings['contact_email'] ?? 'support@shoesstore.vn';
$footer_copyright = $settings['footer_copyright'] ?? '© 2026 SHOES STORE. Thiết kế bởi Trang Sỉ Giàu.';

$social_facebook  = $social_data['Facebook'] ?: ($settings['social_facebook'] ?? 'https://facebook.com/shoesstore');
$social_instagram = $social_data['Instagram'] ?: ($settings['social_instagram'] ?? 'https://instagram.com/shoesstore');
$social_tiktok    = $social_data['TikTok'] ?: ($settings['social_tiktok'] ?? 'https://tiktok.com/@shoesstore');
$social_zalo      = $social_data['Zalo'] ?: ($settings['social_zalo'] ?? 'https://zalo.me/0901234567');
$social_youtube   = $social_data['YouTube'] ?: ($settings['social_youtube'] ?? 'https://youtube.com/@shoesstore');
?>

<style>
/* CSS Nâng Cao Cho Quản Lý Cấu Hình Cuối Trang */
.footer-preview-card {
    background: linear-gradient(135deg, #0b1120 0%, #111827 100%);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    position: sticky;
    top: 90px;
}
.preview-title {
    color: #fbbf24;
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.preview-link {
    color: #94a3b8;
    text-decoration: none;
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.82rem;
    transition: color 0.2s;
}
.preview-link:hover {
    color: #fbbf24;
}
.preview-social-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 6px;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.preview-social-btn:hover {
    background: var(--warning-gold, #f59e0b);
    color: #000;
    transform: translateY(-2px);
}
.preview-copyright-bar {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 0.9rem;
    margin-top: 1.2rem;
    color: #64748b;
    font-size: 0.78rem;
    text-align: center;
}
.setting-section-card {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: #fff;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.setting-section-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
}
</style>

<!-- TIÊU ĐỀ TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-shoe-prints text-warning me-2"></i>Cấu Hình Nội Dung Cuối Trang (Footer Website)
        </h4>
        <span class="text-muted small">Tùy chỉnh toàn bộ thông tin xuất hiện ở chân trang: Tên shop, mô tả, địa chỉ, hotline, email, mạng xã hội và bản quyền (100% Live AJAX).</span>
    </div>
    <div>
        <button type="button" class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm" onclick="document.getElementById('btnSaveFooterSettings').click()">
            <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Cấu Hình
        </button>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- CỘT TRÁI: FORM CHỈNH SỬA NỘI DUNG CUỐI TRANG -->
    <div class="col-12 col-xl-7">
        <form id="footerSettingsForm">
            <input type="hidden" name="ajax_save_footer_settings" value="1">

            <!-- 1. THƯƠNG HIỆU & GIỚI THIỆU CHÂN TRANG -->
            <div class="card setting-section-card shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                    <span class="badge bg-primary rounded-circle p-2 me-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-store"></i>
                    </span>
                    1. Tên Thương Hiệu & Giới Thiệu Chân Trang
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Tên Cửa Hàng / Thương Hiệu Chân Trang <span class="text-danger">*</span></label>
                        <input type="text" name="site_name" id="input_site_name" class="form-control fw-bold fs-6" value="<?= htmlspecialchars($site_name) ?>" placeholder="VD: SHOES STORE" required oninput="updateLiveFooterPreview()">
                        <div class="form-text small text-muted">Hiển thị ở cột đầu tiên của chân trang và tiêu đề bản quyền.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Mô Tả Giới Thiệu Ngắn (Footer Description)</label>
                        <textarea name="site_description" id="input_site_description" class="form-control" rows="3" placeholder="Mô tả ngắn gọn về cửa hàng, phong cách, cam kết chính hãng..." oninput="updateLiveFooterPreview()"><?= htmlspecialchars($site_description) ?></textarea>
                        <div class="form-text small text-muted">Đoạn văn ngắn giới thiệu uy tín thương hiệu dưới logo chân trang.</div>
                    </div>
                </div>
            </div>

            <!-- 2. THÔNG TIN LIÊN HỆ CHÂN TRANG -->
            <div class="card setting-section-card shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                    <span class="badge bg-success rounded-circle p-2 me-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-address-book"></i>
                    </span>
                    2. Thông Tin Liên Hệ Chân Trang
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small d-flex justify-content-between align-items-center mb-1">
                            <span>Địa Chỉ Cửa Hàng / Showroom <span class="text-danger">*</span></span>
                            <button type="button" class="btn btn-xs btn-outline-danger border-0 py-0 px-2 fw-bold text-decoration-none" onclick="openMapPicker('input_contact_address')" title="Chọn vị trí trên bản đồ">
                                <i class="fa-solid fa-map-location-dot me-1 text-danger"></i> <span class="text-danger">📍 Chọn trên Maps</span>
                            </button>
                        </label>
                        <div class="input-group">
                            <input type="text" name="contact_address" id="input_contact_address" class="form-control fw-semibold" value="<?= htmlspecialchars($contact_address) ?>" placeholder="Số nhà, tên đường, phường/xã, TP. Vĩnh Long..." required oninput="updateLiveFooterPreview()">
                            <button class="btn btn-outline-danger" type="button" onclick="openMapPicker('input_contact_address')" title="Mở bản đồ vị trí">
                                <i class="fa-solid fa-location-dot"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Hotline / Số Điện Thoại</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-success"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" name="contact_hotline" id="input_contact_hotline" class="form-control fw-bold text-success" value="<?= htmlspecialchars($contact_hotline) ?>" placeholder="VD: 0901.234.567" oninput="updateLiveFooterPreview()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Email Hỗ Trợ Khách Hàng</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="contact_email" id="input_contact_email" class="form-control fw-bold" value="<?= htmlspecialchars($contact_email) ?>" placeholder="support@shoesstore.vn" oninput="updateLiveFooterPreview()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. LIÊN KẾT MẠNG XÃ HỘI CHÂN TRANG -->
            <div class="card setting-section-card shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                    <span class="badge bg-warning text-dark rounded-circle p-2 me-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-share-nodes"></i>
                    </span>
                    3. Liên Kết Mạng Xã Hội Chân Trang (Social Links)
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa-brands fa-facebook text-primary me-1"></i> Link Facebook</label>
                        <input type="url" name="social_facebook" id="input_social_facebook" class="form-control" value="<?= htmlspecialchars($social_facebook) ?>" placeholder="https://facebook.com/..." oninput="updateLiveFooterPreview()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa-brands fa-instagram text-danger me-1"></i> Link Instagram</label>
                        <input type="url" name="social_instagram" id="input_social_instagram" class="form-control" value="<?= htmlspecialchars($social_instagram) ?>" placeholder="https://instagram.com/..." oninput="updateLiveFooterPreview()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><i class="fa-brands fa-tiktok text-dark me-1"></i> Link TikTok</label>
                        <input type="url" name="social_tiktok" id="input_social_tiktok" class="form-control" value="<?= htmlspecialchars($social_tiktok) ?>" placeholder="https://tiktok.com/@..." oninput="updateLiveFooterPreview()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><i class="fa-solid fa-comment-dots text-info me-1"></i> Link Zalo</label>
                        <input type="url" name="social_zalo" id="input_social_zalo" class="form-control" value="<?= htmlspecialchars($social_zalo) ?>" placeholder="https://zalo.me/..." oninput="updateLiveFooterPreview()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><i class="fa-brands fa-youtube text-danger me-1"></i> Link YouTube</label>
                        <input type="url" name="social_youtube" id="input_social_youtube" class="form-control" value="<?= htmlspecialchars($social_youtube) ?>" placeholder="https://youtube.com/@..." oninput="updateLiveFooterPreview()">
                    </div>
                </div>
            </div>

            <!-- 4. DÒNG CHỮ BẢN QUYỀN (COPYRIGHT) -->
            <div class="card setting-section-card shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                    <span class="badge bg-dark rounded-circle p-2 me-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-copyright"></i>
                    </span>
                    4. Bản Quyền Chân Trang (Copyright Text)
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Dòng Chữ Bản Quyền Dưới Cùng</label>
                        <input type="text" name="footer_copyright" id="input_footer_copyright" class="form-control fw-bold text-dark" value="<?= htmlspecialchars($footer_copyright) ?>" placeholder="© 2026 SHOES STORE. Tất cả quyền được bảo lưu." oninput="updateLiveFooterPreview()">
                        <div class="form-text small text-muted">Hiển thị ở thanh dải màu tối dưới đáy cùng của website.</div>
                    </div>
                </div>
            </div>

            <!-- NÚT LƯU FORM -->
            <div class="d-flex justify-content-end mb-4">
                <button type="submit" id="btnSaveFooterSettings" class="btn btn-warning text-dark fw-bold rounded-pill px-5 py-3 shadow-lg fs-6">
                    <i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH CUỐI TRANG
                </button>
            </div>
        </form>
    </div>

    <!-- CỘT PHẢI: KHUNG XEM TRƯỚC CHÂN TRANG THỜI GIAN THỰC (REAL-TIME LIVE PREVIEW) -->
    <div class="col-12 col-xl-5">
        <div class="card footer-preview-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
                <span class="fw-bold text-warning small">
                    <i class="fa-solid fa-eye me-1"></i> XEM TRƯỚC CHÂN TRANG (LIVE PREVIEW)
                </span>
                <span class="badge bg-success rounded-pill px-2 py-1 small">Thời gian thực</span>
            </div>

            <div class="row g-3">
                <!-- Cột 1: Thông tin shop -->
                <div class="col-12 mb-2">
                    <h5 class="fw-bold text-white mb-2" id="preview_site_name">
                        <?= htmlspecialchars($site_name) ?>
                    </h5>
                    <p class="text-white-50 small mb-2" id="preview_site_description" style="line-height: 1.5;">
                        <?= htmlspecialchars($site_description) ?>
                    </p>
                    <div class="mt-2">
                        <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid opacity-75" style="max-height: 28px;" onerror="this.style.display='none'">
                    </div>
                </div>

                <!-- Cột 2: Danh mục liên kết nhanh mẫu -->
                <div class="col-6 mb-2">
                    <div class="preview-title">Về Chúng Tôi</div>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Giới thiệu</span>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Liên hệ</span>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Điều khoản</span>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Chính sách bảo mật</span>
                </div>

                <div class="col-6 mb-2">
                    <div class="preview-title">Hỗ Trợ</div>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Vận chuyển</span>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Đổi trả</span>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Hướng dẫn size</span>
                    <span class="preview-link"><i class="fa-solid fa-angle-right me-1 small"></i>Câu hỏi FAQ</span>
                </div>

                <!-- Cột 3: Liên hệ & Mạng xã hội -->
                <div class="col-12 mt-2 pt-2 border-top border-secondary">
                    <div class="preview-title">Liên Hệ Trực Tiếp</div>
                    <ul class="list-unstyled text-white-50 small mb-3">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="fa-solid fa-location-dot mt-1 me-2 text-warning"></i>
                            <span id="preview_contact_address"><?= htmlspecialchars($contact_address) ?></span>
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="fa-solid fa-phone me-2 text-warning"></i>
                            <span id="preview_contact_hotline" class="fw-bold text-white"><?= htmlspecialchars($contact_hotline) ?></span>
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="fa-solid fa-envelope me-2 text-warning"></i>
                            <span id="preview_contact_email"><?= htmlspecialchars($contact_email) ?></span>
                        </li>
                    </ul>

                    <div class="preview-title mb-2">Mạng Xã Hội</div>
                    <div class="d-flex flex-wrap" id="preview_social_container">
                        <span class="preview-social-btn" id="prev_soc_fb" title="Facebook"><i class="fa-brands fa-facebook-f"></i></span>
                        <span class="preview-social-btn" id="prev_soc_ig" title="Instagram"><i class="fa-brands fa-instagram"></i></span>
                        <span class="preview-social-btn" id="prev_soc_tt" title="TikTok"><i class="fa-brands fa-tiktok"></i></span>
                        <span class="preview-social-btn" id="prev_soc_zl" title="Zalo"><i class="fa-solid fa-comment-dots"></i></span>
                        <span class="preview-social-btn" id="prev_soc_yt" title="YouTube"><i class="fa-brands fa-youtube"></i></span>
                    </div>
                </div>
            </div>

            <!-- Thanh Copyright dưới cùng -->
            <div class="preview-copyright-bar" id="preview_footer_copyright">
                <?= htmlspecialchars($footer_copyright) ?>
            </div>
        </div>
    </div>
</div>

<script>
// Toast SweetAlert2 tự động biến mất sau 2 giây
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true
});

// Cập nhật Live Preview khi gõ phím
function updateLiveFooterPreview() {
    const siteName = document.getElementById('input_site_name').value.trim() || 'SHOES STORE';
    const siteDesc = document.getElementById('input_site_description').value.trim() || 'Thương hiệu Giày & Dép uy tín, chất lượng hàng đầu.';
    const address  = document.getElementById('input_contact_address').value.trim() || 'Chưa cập nhật địa chỉ';
    const hotline  = document.getElementById('input_contact_hotline').value.trim() || 'Chưa cập nhật hotline';
    const email    = document.getElementById('input_contact_email').value.trim() || 'support@shoesstore.vn';
    const copy     = document.getElementById('input_footer_copyright').value.trim() || '© 2026 SHOES STORE. Tất cả quyền được bảo lưu.';

    document.getElementById('preview_site_name').innerText = siteName;
    document.getElementById('preview_site_description').innerText = siteDesc;
    document.getElementById('preview_contact_address').innerText = address;
    document.getElementById('preview_contact_hotline').innerText = hotline;
    document.getElementById('preview_contact_email').innerText = email;
    document.getElementById('preview_footer_copyright').innerText = copy;

    // Toggle icon mạng xã hội theo giá trị input
    const fb = document.getElementById('input_social_facebook').value.trim();
    const ig = document.getElementById('input_social_instagram').value.trim();
    const tt = document.getElementById('input_social_tiktok').value.trim();
    const zl = document.getElementById('input_social_zalo').value.trim();
    const yt = document.getElementById('input_social_youtube').value.trim();

    document.getElementById('prev_soc_fb').style.opacity = fb ? '1' : '0.25';
    document.getElementById('prev_soc_ig').style.opacity = ig ? '1' : '0.25';
    document.getElementById('prev_soc_tt').style.opacity = tt ? '1' : '0.25';
    document.getElementById('prev_soc_zl').style.opacity = zl ? '1' : '0.25';
    document.getElementById('prev_soc_yt').style.opacity = yt ? '1' : '0.25';
}

// Bắt sự kiện Submit Form (100% Live AJAX)
document.addEventListener("DOMContentLoaded", function() {
    updateLiveFooterPreview();

    const form = document.getElementById('footerSettingsForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSaveFooterSettings');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang lưu cấu hình...';

            const formData = new FormData(form);

            fetch('site-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH CUỐI TRANG';

                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Thông Báo',
                        text: data.message,
                        confirmButtonColor: '#f59e0b'
                    });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH CUỐI TRANG';
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi Kết Nối',
                    text: 'Không thể kết nối máy chủ!',
                    confirmButtonColor: '#ef4444'
                });
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/map-picker-modal.php'; ?>
<?php include_once 'includes/footer.php'; ?>
