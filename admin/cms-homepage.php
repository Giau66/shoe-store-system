<?php 
include_once 'includes/header.php'; 

if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
} 

$msg = '';
$err = '';

// ========================================================
// 1. XỬ LÝ LƯU CẤU HÌNH TỪ FORM CẬP NHẬT
// ========================================================
if (isset($_POST['save_cms'])) {
    $settings = $_POST['settings'] ?? [];

    // Combine marquee messages if any
    $marquee_messages = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["marquee_msg_$i"])) {
            $marquee_messages[] = $_POST["marquee_msg_$i"];
        }
    }
    if (!empty($marquee_messages)) {
        $settings['marquee_text'] = implode(' | ', $marquee_messages);
    }

    // Xử lý upload các file ảnh banner
    $upload_dir = "../uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['settings_files'])) {
        foreach ($_FILES['settings_files']['name'] as $key => $filename) {
            if ($_FILES['settings_files']['error'][$key] == 0) {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $new_filename = time() . '_cms_' . $key . '.' . $ext;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['settings_files']['tmp_name'][$key], $target_file)) {
                    $settings[$key] = 'uploads/' . $new_filename;
                }
            }
        }
    }

    // Lưu từng Key - Value vào CSDL (INSERT ... ON DUPLICATE KEY UPDATE)
    foreach ($settings as $key => $val) {
        $key_clean = addslashes($key);
        $val_clean = addslashes($val);
        $sql = "INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                VALUES ('$key_clean', '$val_clean', 'cms') 
                ON DUPLICATE KEY UPDATE setting_value = '$val_clean'";
        $conn->query($sql);
    }

    $msg = "Đã cập nhật toàn bộ giao diện & chữ trên Trang Chủ thành công!";
}

// ========================================================
// 2. NẠP DỮ LIỆU CẤU HÌNH HIỆN TẠI TỪ CSDL
// ========================================================
$cms = [];
$res_cms = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($res_cms) {
    while ($row = $res_cms->fetch_assoc()) {
        $cms[$row['setting_key']] = $row['setting_value'];
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-sliders text-warning me-2"></i>Quản Lý & Cấu Hình Trang Chủ CMS
        </h4>
        <span class="text-muted small">Tùy chỉnh toàn bộ Chữ, Tiêu đề, Subtitle, Banner ảnh và Thông tin liên hệ hiển thị trên Trang chủ.</span>
    </div>
</div>

<!-- THÔNG BÁO -->
<?php if ($msg): ?><div class="alert alert-success shadow-sm fw-bold"><i class="fa-solid fa-circle-check me-2"></i><?= $msg; ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger shadow-sm fw-bold"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $err; ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="mb-5">

    <!-- TABS ĐIỀU HƯỚNG CÁC KHU VỰC CẤU HÌNH -->
    <ul class="nav nav-pills mb-4 gap-2" id="cmsTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold px-3 py-2 rounded-3" id="hero-tab" data-bs-toggle="tab" data-bs-target="#hero-panel" type="button">
                🖼️ Banner Hero
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-3 py-2 rounded-3" id="marquee-tab" data-bs-toggle="tab" data-bs-target="#marquee-panel" type="button">
                📢 Quảng cáo xoay tròn (Marquee)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-3 py-2 rounded-3" id="features-tab" data-bs-toggle="tab" data-bs-target="#features-panel" type="button">
                🚚 Cam Kết & Dịch Vụ
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-3 py-2 rounded-3" id="titles-tab" data-bs-toggle="tab" data-bs-target="#titles-panel" type="button">
                📌 Tiêu Đề Các Block
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-3 py-2 rounded-3" id="footer-tab" data-bs-toggle="tab" data-bs-target="#footer-panel" type="button">
                📞 Footer Liên Hệ
            </button>
        </li>
    </ul>

    <div class="tab-content" id="cmsTabContent">

        <!-- TAB 1: BANNER HERO -->
        <div class="tab-pane fade show active" id="hero-panel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-image me-2"></i>Cấu Hình Banner Đỉnh Trang (Hero Slider)</h5>
                
                <div class="row g-3">
                    <div class="col-12 col-md-4 text-center border-end pe-3">
                        <label class="form-label fw-bold d-block">Ảnh Banner Hero</label>
                        <?php $hero_img_src = (strpos($cms['hero_image'] ?? '', 'http') === 0) ? $cms['hero_image'] : '../' . ($cms['hero_image'] ?? 'assets/images/hero-banner.jpg'); ?>
                        <img id="preview_hero_image" src="<?= $hero_img_src; ?>" class="img-fluid rounded-3 border shadow-sm mb-3" style="max-height: 180px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=600'">
                        
                        <label class="form-label fw-bold text-success small mb-1">Tải ảnh banner mới:</label>
                        <input type="file" name="settings_files[hero_image]" class="form-control form-control-sm mb-2" accept="image/*" onchange="previewCmsImage(this, 'preview_hero_image')">
                        <input type="text" name="settings[hero_image]" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh..." value="<?= htmlspecialchars($cms['hero_image'] ?? ''); ?>">
                    </div>

                    <div class="col-12 col-md-8">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Dòng Tiêu Đề Chính (Hero Title) <span class="text-danger">*</span></label>
                                <input type="text" name="settings[hero_title]" class="form-control fw-bold fs-5 text-dark" value="<?= htmlspecialchars($cms['hero_title'] ?? ''); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Dòng Mô Tả Ngắn (Subtitle)</label>
                                <textarea name="settings[hero_subtitle]" class="form-control" rows="2"><?= htmlspecialchars($cms['hero_subtitle'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Chữ Trên Nút Bấm</label>
                                <input type="text" name="settings[hero_btn_text]" class="form-control fw-bold text-primary" value="<?= htmlspecialchars($cms['hero_btn_text'] ?? $cms['hero_button_text'] ?? 'MUA SẮM NGAY'); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Đường Dẫn Nút Bấm (Link)</label>
                                <input type="text" name="settings[hero_btn_link]" class="form-control" value="<?= htmlspecialchars($cms['hero_btn_link'] ?? $cms['hero_button_link'] ?? 'shop.php'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: QUẢNG CÁO XOAY TRÒN (MARQUEE) -->
        <div class="tab-pane fade" id="marquee-panel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-danger"><i class="fa-solid fa-bullhorn me-2"></i>Quảng cáo xoay tròn (Marquee)</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung cuộn (Toàn bộ)</label>
                    <textarea name="settings[marquee_text]" class="form-control" rows="3" placeholder="Nhập text cuộn ở đây..."><?= htmlspecialchars($cms['marquee_text'] ?? ''); ?></textarea>
                    <div class="form-text">Bạn có thể tự nhập toàn bộ chữ cuộn, hoặc nhập từng phần bên dưới để hệ thống tự nối lại.</div>
                </div>

                <hr>
                <h6 class="fw-bold mb-3">Hoặc tạo từ các thông điệp nhỏ (sẽ tự động nối bằng ' | '):</h6>
                <div class="row g-2">
                    <?php for($i=1; $i<=5; $i++): ?>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold">Thông điệp <?= $i; ?></label>
                        <input type="text" name="marquee_msg_<?= $i; ?>" class="form-control form-control-sm" placeholder="VD: Khuyến mãi <?= $i; ?>...">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- TAB 3: CAM KẾT & DỊCH VỤ -->
        <div class="tab-pane fade" id="features-panel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-success"><i class="fa-solid fa-truck-fast me-2"></i>Cam Kết & Dịch Vụ</h5>
                
                <div class="row g-3">
                    <?php 
                    $feature_defaults = [
                        1 => ['icon' => 'fa-truck', 'title' => 'Miễn Phí Giao Hàng', 'desc' => 'Cho đơn hàng từ 500.000đ'],
                        2 => ['icon' => 'fa-shield-halved', 'title' => '100% Chính Hãng', 'desc' => 'Cam kết hoàn tiền 200% nếu giả'],
                        3 => ['icon' => 'fa-rotate-left', 'title' => 'Đổi Trả 7 Ngày', 'desc' => 'Đổi size dễ dàng tận nhà'],
                        4 => ['icon' => 'fa-headset', 'title' => 'Hỗ Trợ 24/7', 'desc' => 'Hotline chăm sóc khách hàng']
                    ];
                    for ($i = 1; $i <= 4; $i++): 
                    ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <h6 class="fw-bold text-primary mb-2">Dịch Vụ <?= $i; ?></h6>
                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Icon (Font Awesome class)</label>
                                    <input type="text" name="settings[service_<?= $i; ?>_icon]" class="form-control form-control-sm" value="<?= htmlspecialchars($cms["service_{$i}_icon"] ?? $feature_defaults[$i]['icon']); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Tiêu Đề Card</label>
                                    <input type="text" name="settings[service_<?= $i; ?>_title]" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($cms["service_{$i}_title"] ?? $feature_defaults[$i]['title']); ?>">
                                </div>
                                <div>
                                    <label class="form-label fw-bold small">Dòng Mô Tả Ngắn</label>
                                    <input type="text" name="settings[service_<?= $i; ?>_desc]" class="form-control form-control-sm" value="<?= htmlspecialchars($cms["service_{$i}_desc"] ?? $feature_defaults[$i]['desc']); ?>">
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- TAB 4: TIÊU ĐỀ CÁC BLOCK SẢN PHẨM & VOUCHER -->
        <div class="tab-pane fade" id="titles-panel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-dark"><i class="fa-solid fa-heading me-2 text-warning"></i>Tiêu Đề Các Block</h5>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Tiêu Đề Mục Sản Phẩm Hot</label>
                        <input type="text" name="settings[section_hot_title]" class="form-control fw-bold text-danger" value="<?= htmlspecialchars($cms['section_hot_title'] ?? 'SẢN PHẨM BÁN CHẠY (TOP HOT)'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Tiêu Đề Mục Mẫu Mới</label>
                        <input type="text" name="settings[section_new_title]" class="form-control fw-bold text-primary" value="<?= htmlspecialchars($cms['section_new_title'] ?? 'MẪU GIÀY MỚI VỀ KHO'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Tiêu Đề Mục Khuyến Mãi (Sale)</label>
                        <input type="text" name="settings[section_sale_title]" class="form-control fw-bold text-success" value="<?= htmlspecialchars($cms['section_sale_title'] ?? 'SIÊU KHUYẾN MÃI (GIẢM GIÁ ĐẾN 50%)'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Tiêu Đề Mục Thương Hiệu</label>
                        <input type="text" name="settings[section_brand_title]" class="form-control fw-bold text-success" value="<?= htmlspecialchars($cms['section_brand_title'] ?? 'THƯƠNG HIỆU NỔI BẬT'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Tiêu Đề Mục Voucher</label>
                        <input type="text" name="settings[section_voucher_title]" class="form-control fw-bold text-warning" value="<?= htmlspecialchars($cms['section_voucher_title'] ?? 'VOUCHER ƯU ĐÃI KHUYẾN MÃI'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: THÔNG TIN LIÊN HỆ & CHÂN TRANG FOOTER -->
        <div class="tab-pane fade" id="footer-panel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-info"><i class="fa-solid fa-phone me-2"></i>Footer Liên Hệ</h5>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Hotline Hỗ Trợ Khách Hàng</label>
                        <input type="text" name="settings[contact_hotline]" class="form-control fw-bold text-danger" value="<?= htmlspecialchars($cms['contact_hotline'] ?? '0912.345.678'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Email Liên Hệ</label>
                        <input type="email" name="settings[contact_email]" class="form-control fw-bold" value="<?= htmlspecialchars($cms['contact_email'] ?? 'support@shoes.vn'); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Địa Chỉ Cửa Hàng / Showroom</label>
                        <input type="text" name="settings[contact_address]" class="form-control" value="<?= htmlspecialchars($cms['contact_address'] ?? 'Long Châu, TP. Vĩnh Long'); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Footer Copyright</label>
                        <input type="text" name="settings[footer_copyright]" class="form-control" value="<?= htmlspecialchars($cms['footer_copyright'] ?? '© 2026 SHOES. All rights reserved.'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Link Facebook Fanpage</label>
                        <input type="text" name="settings[social_facebook]" class="form-control" value="<?= htmlspecialchars($cms['social_facebook'] ?? 'https://facebook.com'); ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Số Zalo Tư Vấn</label>
                        <input type="text" name="settings[social_zalo]" class="form-control" value="<?= htmlspecialchars($cms['social_zalo'] ?? '0912345678'); ?>">
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- NÚT LƯU TOÀN BỘ CẤU HÌNH CMS -->
    <div class="mt-4 text-end">
        <button type="submit" name="save_cms" class="btn btn-warning btn-lg fw-bold px-5 rounded-3 shadow">
            <i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH TRANG CHỦ
        </button>
    </div>

</form>

<script>
function previewCmsImage(input, targetId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

    </div>
</div>
</body>
</html>