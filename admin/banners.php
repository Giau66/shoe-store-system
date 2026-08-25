<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$upload_dir = '../uploads/banners/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// ========================================================
// 1. CÁC ENDPOINT LIVE AJAX (100% KHÔNG LOAD TRANG)
// ========================================================

// AJAX 1.1: Lưu Banner Hero Trang Chủ
if (isset($_POST['ajax_save_hero_banner'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $banner_id   = intval($_POST['banner_id'] ?? 1);
    $title       = trim($_POST['title'] ?? 'BỨT PHÁ PHONG CÁCH 2026');
    $subtitle    = trim($_POST['subtitle'] ?? '');
    $badge_text  = trim($_POST['badge_text'] ?? 'Siêu Phẩm Sneaker 2026');
    $button_text = trim($_POST['button_text'] ?? 'MUA SẮM NGAY');
    $link_url    = trim($_POST['link_url'] ?? 'all-products.php');
    if (empty($link_url)) $link_url = 'all-products.php';

    $image_url = '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = 'hero_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $filename)) {
            $image_url = 'uploads/banners/' . $filename;
        }
    }
    if (empty($image_url)) {
        $image_url = trim($_POST['image_url'] ?? '');
    }

    // Kiểm tra xem đã có bản ghi hero chưa
    $check = $conn->query("SELECT id, image_url FROM banners WHERE position = 'hero' ORDER BY id ASC LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $hero_id = intval($row['id']);
        if (empty($image_url)) {
            $image_url = $row['image_url'];
        }
        $stmt = $conn->prepare("
            UPDATE banners SET 
                title = ?, subtitle = ?, badge_text = ?, button_text = ?, link_url = ?, image_url = ?, status = 1 
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", $title, $subtitle, $badge_text, $button_text, $link_url, $image_url, $hero_id);
    } else {
        if (empty($image_url)) {
            $image_url = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200';
        }
        $stmt = $conn->prepare("
            INSERT INTO banners (title, subtitle, badge_text, button_text, link_url, image_url, position, status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, 'hero', 1, 1)
        ");
        $stmt->bind_param("ssssss", $title, $subtitle, $badge_text, $button_text, $link_url, $image_url);
    }

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success'   => true,
            'image_url' => $image_url,
            'message'   => 'Đã lưu Banner chính trang chủ thành công!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi cập nhật CSDL: ' . $stmt->error
        ]);
    }
    exit();
}

// AJAX 1.2: Lưu Dải Chữ Chạy Thông Báo Khuyến Mãi (Marquee Bar)
if (isset($_POST['ajax_save_marquee'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $marquee_val = trim($_POST['marquee_text'] ?? '');
    if (empty($marquee_val)) {
        $marquee_val = '🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN TỪ 500.000Đ | 🎁 MÃ WELCOME50K GIẢM NGAY 50K CHO TÀI KHOẢN MỚI | 🏆 CAM KẾT 100% SẢN PHẨM CHÍNH HÃNG AUTHENTIC | ⚡ FLASH SALE GIẢM GIÁ ĐẾN 33% TẤT CẢ SẢN PHẨM HOT 2026 | 🔁 HỖ TRỢ ĐỔI TRẢ 30 NGÀY NẾU LỖI SẢN PHẨM';
    }

    $stmt = $conn->prepare("
        INSERT INTO site_settings (setting_key, setting_value, setting_group) 
        VALUES ('marquee_text', ?, 'general')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->bind_param("s", $marquee_val);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'marquee_text' => $marquee_val,
            'message' => 'Đã lưu dải chữ chạy khuyến mãi thành công!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi lưu cài đặt: ' . $stmt->error
        ]);
    }
    exit();
}

include_once 'includes/header.php';

// ========================================================
// 2. LẤY DỮ LIỆU BANNER HERO & MARQUEE HIỆN TẠI
// ========================================================

// 1. Banner Hero
$hero_q = $conn->query("SELECT * FROM banners WHERE position = 'hero' ORDER BY id ASC LIMIT 1");
$hero = $hero_q ? $hero_q->fetch_assoc() : null;
if (!$hero) {
    $hero = [
        'id'          => 1,
        'title'       => 'BỨT PHÁ PHONG CÁCH 2026',
        'subtitle'    => 'Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi giảm đến 33% hôm nay!',
        'badge_text'  => 'Siêu Phẩm Sneaker 2026',
        'button_text' => 'MUA SẮM NGAY',
        'link_url'    => 'all-products.php',
        'image_url'   => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200'
    ];
}

// 2. Dòng chữ chạy (Marquee text)
$marquee_q = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'marquee_text' LIMIT 1");
$current_marquee = ($marquee_q && $m_row = $marquee_q->fetch_assoc()) ? $m_row['setting_value'] : '🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN TỪ 500.000Đ | 🎁 MÃ WELCOME50K GIẢM NGAY 50K CHO TÀI KHOẢN MỚI | 🏆 CAM KẾT 100% SẢN PHẨM CHÍNH HÃNG AUTHENTIC | ⚡ FLASH SALE GIẢM GIÁ ĐẾN 33% TẤT CẢ SẢN PHẨM HOT 2026 | 🔁 HỖ TRỢ ĐỔI TRẢ 30 NGÀY NẾU LỖI SẢN PHẨM';
$marquee_items = array_filter(array_map('trim', explode('|', $current_marquee)));
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* CSS TỐI ƯU GIAO DIỆN QUẢN TRỊ BANNER & DÒNG CHỮ CHẠY */
.banner-admin-card {
    border-radius: 18px;
    border: 1px solid rgba(0,0,0,0.06);
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
}

/* LIVE PREVIEW HERO BANNER */
.live-hero-preview {
    background: linear-gradient(135deg, #11112d 0%, #24205c 52%, #102c46 100%);
    border-radius: 16px;
    padding: 2.2rem 2rem;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(17, 17, 45, 0.35);
    border: 1px solid rgba(255,255,255,0.12);
}
.live-hero-preview::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at 80% 20%, rgba(6,182,212,0.25), transparent 45%),
                radial-gradient(circle at 20% 80%, rgba(244,63,141,0.18), transparent 40%);
}
.live-hero-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 30px;
    display: inline-block;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
}
.live-hero-title {
    font-size: 1.85rem;
    font-weight: 900;
    line-height: 1.2;
    margin: 12px 0 8px;
    background: linear-gradient(90deg, #ffffff, #bff4ff 45%, #f9b9dc 80%, #ffffff);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: -0.5px;
}
.live-hero-subtitle {
    font-size: 0.92rem;
    color: rgba(255,255,255,0.85);
    line-height: 1.6;
    margin-bottom: 18px;
    max-width: 480px;
}
.live-hero-btn {
    background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.88rem;
    padding: 10px 24px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.35);
    border: none;
    text-decoration: none;
}
.live-hero-img {
    max-height: 230px;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 20px 30px rgba(0,0,0,0.6));
    transition: transform 0.4s ease;
}
.live-hero-img:hover {
    transform: translateY(-8px) rotate(-3deg) scale(1.05);
}

/* LIVE PREVIEW MARQUEE TRACK */
.live-marquee-bar {
    background: linear-gradient(90deg, #0f102b, #1d1848, #0c2939);
    border: 1px solid rgba(6,182,212,0.35);
    border-radius: 12px;
    padding: 12px 0;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(15, 16, 43, 0.25);
    white-space: nowrap;
    position: relative;
}
.live-marquee-track {
    display: inline-flex;
    align-items: center;
    gap: 24px;
    animation: liveMarqueeAnim 22s linear infinite;
}
.live-marquee-track:hover {
    animation-play-state: paused;
}
@keyframes liveMarqueeAnim {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.emoji-quick-chip {
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 4px 12px;
    transition: all 0.2s;
    user-select: none;
}
.emoji-quick-chip:hover {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
    transform: translateY(-1px);
}
</style>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i>Quản Lý Banner &amp; Dải Chữ Chạy Trang Chủ
        </h4>
        <span class="text-muted small">Cố định 2 phần: Banner Hero lớn trên cùng &amp; Dải chữ chạy thông báo xoay vòng khuyến mãi. Thao tác lưu tức thì 100% không load lại trang.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="../index.php" class="btn btn-outline-dark fw-bold rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Xem Trang Chủ Web
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ======================================================== -->
    <!-- MỤC 1: BANNER CHÍNH TRANG CHỦ (HERO BANNER CỐ ĐỊNH) -->
    <!-- ======================================================== -->
    <div class="col-12">
        <div class="banner-admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fw-bold text-uppercase mb-0 text-dark">
                    <span class="badge bg-primary rounded-circle me-2 px-2 py-1">1</span>
                    Banner Chính Trang Chủ (Hero Banner)
                </h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                    <i class="fa-solid fa-circle-check me-1"></i> Hiển thị cố định
                </span>
            </div>

            <!-- XEM TRƯỚC TRỰC QUAN (LIVE PREVIEW) -->
            <div class="mb-4">
                <label class="form-label fw-bold text-muted small text-uppercase mb-2">
                    <i class="fa-solid fa-eye me-1 text-primary"></i> Xem trước hiển thị trực tiếp (Live Preview):
                </label>
                <div class="live-hero-preview">
                    <div class="row align-items-center">
                        <div class="col-lg-7 col-md-7 col-12 mb-3 mb-md-0 position-relative" style="z-index: 2;">
                            <span class="live-hero-badge" id="preview_badge"><?= htmlspecialchars($hero['badge_text'] ?: 'Siêu Phẩm Sneaker 2026') ?></span>
                            <h3 class="live-hero-title" id="preview_title"><?= htmlspecialchars($hero['title'] ?: 'BỨT PHÁ PHONG CÁCH 2026') ?></h3>
                            <p class="live-hero-subtitle" id="preview_subtitle"><?= htmlspecialchars($hero['subtitle'] ?: 'Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi giảm đến 33% hôm nay!') ?></p>
                            <a href="javascript:void(0)" class="live-hero-btn shadow">
                                <span id="preview_button_text"><?= htmlspecialchars($hero['button_text'] ?: 'MUA SẮM NGAY') ?></span>
                                <i class="fa-solid fa-cart-shopping"></i>
                            </a>
                        </div>
                        <div class="col-lg-5 col-md-5 col-12 text-center position-relative" style="z-index: 2;">
                            <?php 
                            $hero_img = $hero['image_url'];
                            $hero_img_src = (strpos($hero_img, 'http') === 0) ? $hero_img : '../' . $hero_img;
                            ?>
                            <img src="<?= htmlspecialchars($hero_img_src) ?>" alt="Hero Shoe" class="live-hero-img" id="preview_img" onerror="this.src='https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1200'">
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM CHỈNH SỬA NỘI DUNG VÀ HÌNH ẢNH BANNER -->
            <form id="heroBannerForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax_save_hero_banner" value="1">
                <input type="hidden" name="banner_id" value="<?= $hero['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-bold text-dark small">Nhãn nổi bật (Badge Text): <span class="text-danger">*</span></label>
                        <input type="text" name="badge_text" id="input_badge" class="form-control fw-bold" value="<?= htmlspecialchars($hero['badge_text'] ?: 'Siêu Phẩm Sneaker 2026') ?>" placeholder="VD: Siêu Phẩm Sneaker 2026..." oninput="updateHeroPreview()" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-bold text-dark small">Tiêu đề lớn (Title Heading): <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="input_title" class="form-control fw-bold" value="<?= htmlspecialchars($hero['title'] ?: 'BỨT PHÁ PHONG CÁCH 2026') ?>" placeholder="VD: BỨT PHÁ PHONG CÁCH..." oninput="updateHeroPreview()" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark small">Mô tả phụ (Subtitle / Giới thiệu ngắn):</label>
                        <textarea name="subtitle" id="input_subtitle" class="form-control" rows="2" placeholder="VD: Sở hữu các mẫu Sneaker chính hãng đỉnh cao..." oninput="updateHeroPreview()"><?= htmlspecialchars($hero['subtitle']) ?></textarea>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-bold text-dark small">Chữ trên nút bấm (Button Text):</label>
                        <input type="text" name="button_text" id="input_button_text" class="form-control fw-bold" value="<?= htmlspecialchars($hero['button_text'] ?: 'MUA SẮM NGAY') ?>" placeholder="VD: MUA SẮM NGAY, XEM NGAY..." oninput="updateHeroPreview()">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-bold text-dark small">Đường dẫn khi bấm nút (Link URL):</label>
                        <input type="text" name="link_url" id="input_link_url" class="form-control font-monospace" value="<?= htmlspecialchars($hero['link_url'] ?: 'all-products.php') ?>" placeholder="VD: all-products.php, sale-event.php...">
                    </div>

                    <!-- CHỌN ẢNH BANNER HERO -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-dark small mb-1">
                                <i class="fa-solid fa-image text-primary me-1"></i> Hình ảnh Banner Giày Sneaker:
                            </label>
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6 col-12">
                                    <label class="form-label small text-muted mb-1">Cách 1: Tải file ảnh từ máy tính (Khuyên dùng PNG trong suốt / JPG đẹp):</label>
                                    <input type="file" name="image_file" id="input_img_file" class="form-control form-control-sm" accept="image/*" onchange="previewLocalImage(this)">
                                </div>
                                <div class="col-md-6 col-12">
                                    <label class="form-label small text-muted mb-1">Cách 2: Hoặc dán link URL hình ảnh:</label>
                                    <input type="text" name="image_url" id="input_img_url" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($hero['image_url']) ?>" placeholder="https://... hoặc uploads/..." oninput="previewUrlImage(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end pt-2">
                        <button type="submit" id="btnSaveHero" class="btn btn-success fw-bold rounded-pill px-5 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> LƯU BANNER TRANG CHỦ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MỤC 2: DÒNG CHỮ CHẠY XOAY VÒNG KHUYẾN MÃI (MARQUEE BAR) -->
    <!-- ======================================================== -->
    <div class="col-12 mb-5">
        <div class="banner-admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fw-bold text-uppercase mb-0 text-dark">
                    <span class="badge bg-warning text-dark rounded-circle me-2 px-2 py-1">2</span>
                    Dải Chữ Chạy Thông Báo Khuyến Mãi (Marquee Text Bar)
                </h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                    <i class="fa-solid fa-bolt me-1"></i> Chạy ngang liên tục
                </span>
            </div>

            <!-- XEM TRƯỚC DẢI CHỮ CHẠY TỨC THÌ (LIVE MARQUEE PREVIEW) -->
            <div class="mb-4">
                <label class="form-label fw-bold text-muted small text-uppercase mb-2">
                    <i class="fa-solid fa-play me-1 text-warning"></i> Xem trước hiệu ứng chạy ngang (Live Animation):
                </label>
                <div class="live-marquee-bar">
                    <div class="live-marquee-track" id="preview_marquee_track">
                        <?php for($loop=0; $loop<4; $loop++): ?>
                            <?php foreach($marquee_items as $item): ?>
                                <span class="fw-bold text-uppercase text-white small"><?= htmlspecialchars($item) ?></span>
                                <span class="text-warning">✦</span>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- FORM CHỈNH SỬA DẢI CHỮ CHẠY -->
            <form id="marqueeForm">
                <input type="hidden" name="ajax_save_marquee" value="1">

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                        <label class="form-label fw-bold text-dark small mb-0">Nội dung thông báo (Phân tách các thông điệp bằng dấu gạch đứng " | "):</label>
                        <div class="d-flex gap-1 flex-wrap">
                            <span class="emoji-quick-chip" onclick="insertEmoji('🚚 MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC ')">🚚 Freeship</span>
                            <span class="emoji-quick-chip" onclick="insertEmoji('🎁 MÃ GIẢM GIÁ 50K ')">🎁 Voucher</span>
                            <span class="emoji-quick-chip" onclick="insertEmoji('🏆 CAM KẾT 100% CHÍNH HÃNG ')">🏆 Chính Hãng</span>
                            <span class="emoji-quick-chip" onclick="insertEmoji('⚡ FLASH SALE GIẢM ĐẾN 33% ')">⚡ Flash Sale</span>
                            <span class="emoji-quick-chip" onclick="insertEmoji('🔁 HỖ TRỢ ĐỔI TRẢ 30 NGÀY ')">🔁 Đổi Trả</span>
                            <span class="emoji-quick-chip" onclick="insertEmoji(' | ')"><b>|</b> (Ngăn cách)</span>
                        </div>
                    </div>
                    <textarea name="marquee_text" id="input_marquee_text" class="form-control font-monospace" rows="4" placeholder="VD: 🚚 MIỄN PHÍ VẬN CHUYỂN | 🎁 MÃ WELCOME50K GIẢM 50K | 🏆 CAM KẾT CHÍNH HÃNG..." oninput="updateMarqueePreview()"><?= htmlspecialchars($current_marquee) ?></textarea>
                    <small class="text-muted mt-1 d-block">
                        <i class="fa-solid fa-circle-info me-1 text-primary"></i> <b>Mẹo:</b> Mỗi đoạn thông báo đặt giữa 2 dấu gạch đứng <code>|</code> sẽ tự động được thêm ngôi sao vàng <code>✦</code> ngăn cách trên trang chủ.
                    </small>
                </div>

                <div class="text-end">
                    <button type="submit" id="btnSaveMarquee" class="btn btn-warning fw-bold rounded-pill px-5 shadow-sm text-dark">
                        <i class="fa-solid fa-floppy-disk me-1"></i> LƯU DÒNG CHỮ CHẠY
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- JAVASCRIPT LIVE PREVIEW & 100% LIVE AJAX (ZERO RELOAD) -->
<script>
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

// Cập nhật Live Preview Hero Banner
function updateHeroPreview() {
    const badge = document.getElementById('input_badge').value.trim() || 'Siêu Phẩm Sneaker 2026';
    const title = document.getElementById('input_title').value.trim() || 'BỨT PHÁ PHONG CÁCH 2026';
    const subtitle = document.getElementById('input_subtitle').value.trim() || 'Sở hữu các mẫu Sneaker chính hãng đỉnh cao với ưu đãi hôm nay!';
    const btnText = document.getElementById('input_button_text').value.trim() || 'MUA SẮM NGAY';

    document.getElementById('preview_badge').innerText = badge;
    document.getElementById('preview_title').innerText = title;
    document.getElementById('preview_subtitle').innerText = subtitle;
    document.getElementById('preview_button_text').innerText = btnText;
}

function previewUrlImage(url) {
    if (url.trim()) {
        const cleanUrl = url.trim().startsWith('http') ? url.trim() : '../' + url.trim();
        document.getElementById('preview_img').src = cleanUrl;
    }
}

function previewLocalImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview_img').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Cập nhật Live Preview Marquee
function updateMarqueePreview() {
    const raw = document.getElementById('input_marquee_text').value.trim();
    const track = document.getElementById('preview_marquee_track');
    if (!raw) return;

    const items = raw.split('|').map(s => s.trim()).filter(s => s.length > 0);
    let html = '';
    for (let loop = 0; loop < 4; loop++) {
        items.forEach(item => {
            html += `<span class="fw-bold text-uppercase text-white small">${escapeHtml(item)}</span><span class="text-warning">✦</span>`;
        });
    }
    track.innerHTML = html;
}

function insertEmoji(text) {
    const textarea = document.getElementById('input_marquee_text');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const val = textarea.value;
    textarea.value = val.substring(0, start) + text + val.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    updateMarqueePreview();
}

function escapeHtml(string) {
    return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener("DOMContentLoaded", function() {
    // 1. Submit Form Hero Banner qua Live AJAX
    const heroForm = document.getElementById('heroBannerForm');
    if (heroForm) {
        heroForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveHero');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu banner...';

            const formData = new FormData(heroForm);

            fetch('banners.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU BANNER TRANG CHỦ';

                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    if (data.image_url) {
                        document.getElementById('input_img_url').value = data.image_url;
                        previewUrlImage(data.image_url);
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU BANNER TRANG CHỦ';
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
            });
        });
    }

    // 2. Submit Form Marquee Text qua Live AJAX
    const marqueeForm = document.getElementById('marqueeForm');
    if (marqueeForm) {
        marqueeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveMarquee');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu dòng chữ chạy...';

            const formData = new FormData(marqueeForm);

            fetch('banners.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU DÒNG CHỮ CHẠY';

                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU DÒNG CHỮ CHẠY';
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
            });
        });
    }
});
</script>

    </div>
</div>
</body>
</html>
