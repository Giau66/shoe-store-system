<?php 
ob_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Load user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// 1. CẬP NHẬT THÔNG TIN
if (isset($_POST['update_profile'])) {
    $fullname = $conn->real_escape_string(trim($_POST['fullname']));
    $phone    = $conn->real_escape_string(trim($_POST['phone']));
    $email    = $conn->real_escape_string(trim($_POST['email']));
    $birthday = $conn->real_escape_string(trim($_POST['birthday']));
    $avatar_path = $user['avatar'];

    if (empty($fullname)) {
        $error = "Họ và Tên không được để trống!";
    } else {
        // Kiểm tra tính hợp lệ của ngày sinh
        if (!empty($birthday)) {
            $bDate = DateTime::createFromFormat('Y-m-d', $birthday);
            $today = new DateTime();
            if (!$bDate || $bDate->format('Y-m-d') !== $birthday) {
                $error = "Định dạng ngày sinh không hợp lệ!";
            } elseif ($bDate > $today) {
                $error = "Ngày sinh không hợp lệ: Không thể chọn ngày trong tương lai!";
            } elseif ($bDate < new DateTime('1920-01-01')) {
                $error = "Năm sinh không hợp lệ: Vui lòng nhập năm sinh từ 1920 trở lại đây!";
            }
        }

        if (empty($error) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['avatar']['tmp_name'];
            $fileName      = $_FILES['avatar']['name'];
            $fileSize      = $_FILES['avatar']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = "Chỉ chấp nhận file ảnh có định dạng: JPG, JPEG, PNG, WEBP, GIF!";
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $error = "Kích thước file ảnh không được vượt quá 5MB!";
            } else {
                $uploadDir = 'uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                $destPath    = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $avatar_path = $destPath;
                } else {
                    $error = "Không thể lưu file ảnh đại diện lên máy chủ!";
                }
            }
        }

        if (empty($error)) {
            $birthday_val = !empty($birthday) ? "'$birthday'" : "NULL";
            $update_sql = "UPDATE users 
                           SET fullname = '$fullname', phone = '$phone', email = '$email', birthday = $birthday_val, avatar = '$avatar_path' 
                           WHERE id = $user_id";
            if ($conn->query($update_sql)) {
                $_SESSION['user_name'] = $fullname;
                $success = "Cập nhật thông tin cá nhân thành công!";
                $res = $conn->query("SELECT * FROM users WHERE id = $user_id");
                $user = $res->fetch_assoc();
            } else {
                $error = "Lỗi CSDL: " . $conn->error;
            }
        }
    }
}

// 2. THÊM ĐỊA CHỈ
if (isset($_POST['add_address'])) {
    $recipient_name = $conn->real_escape_string(trim($_POST['recipient_name']));
    $address_phone  = $conn->real_escape_string(trim($_POST['address_phone']));
    $province_id    = (int)$_POST['province_id'];
    $address_detail = $conn->real_escape_string(trim($_POST['address_detail']));
    $is_default     = isset($_POST['is_default']) ? 1 : 0;

    if ($is_default) {
        $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
    } else {
        $check = $conn->query("SELECT id FROM user_addresses WHERE user_id = $user_id");
        if ($check->num_rows == 0) $is_default = 1;
    }

    $sql = "INSERT INTO user_addresses (user_id, recipient_name, phone, province_id, address_detail, is_default) 
            VALUES ($user_id, '$recipient_name', '$address_phone', $province_id, '$address_detail', $is_default)";
    if ($conn->query($sql)) {
        $success = "Thêm địa chỉ thành công!";
    } else {
        $error = "Lỗi thêm địa chỉ!";
    }
}

// 2.1 SỬA ĐỊA CHỈ
if (isset($_POST['edit_address'])) {
    $addr_id        = (int)$_POST['address_id'];
    $recipient_name = $conn->real_escape_string(trim($_POST['recipient_name']));
    $address_phone  = $conn->real_escape_string(trim($_POST['address_phone']));
    $province_id    = (int)$_POST['province_id'];
    $address_detail = $conn->real_escape_string(trim($_POST['address_detail']));
    $is_default     = isset($_POST['is_default']) ? 1 : 0;

    if ($is_default) {
        $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
    }

    $sql = "UPDATE user_addresses 
            SET recipient_name = '$recipient_name', phone = '$address_phone', province_id = $province_id, address_detail = '$address_detail', is_default = $is_default 
            WHERE id = $addr_id AND user_id = $user_id";
    if ($conn->query($sql)) {
        $success = "Cập nhật địa chỉ thành công!";
    } else {
        $error = "Lỗi cập nhật địa chỉ!";
    }
}

// 3. ĐẶT MẶC ĐỊNH
if (isset($_POST['set_default'])) {
    $addr_id = (int)$_POST['address_id'];
    $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
    $conn->query("UPDATE user_addresses SET is_default = 1 WHERE id = $addr_id AND user_id = $user_id");
    $success = "Đã thay đổi địa chỉ mặc định!";
}

// 4. XÓA ĐỊA CHỈ
if (isset($_POST['delete_address'])) {
    $addr_id = (int)$_POST['address_id'];
    $conn->query("DELETE FROM user_addresses WHERE id = $addr_id AND user_id = $user_id");
    $success = "Đã xóa địa chỉ!";
}

// Lấy danh sách tỉnh thành
$provinces = [];
$res_prov = $conn->query("SELECT * FROM shipping_provinces WHERE status = 1 ORDER BY id ASC");
if ($res_prov) {
    while ($p = $res_prov->fetch_assoc()) {
        $provinces[] = $p;
    }
}

// Lấy danh sách địa chỉ
$addresses = [];
$res_addr = $conn->query("SELECT a.*, p.province_name FROM user_addresses a LEFT JOIN shipping_provinces p ON a.province_id = p.id WHERE a.user_id = $user_id ORDER BY a.is_default DESC, a.id DESC");
if ($res_addr) {
    while ($a = $res_addr->fetch_assoc()) {
        $addresses[] = $a;
    }
}

// Lấy danh sách Ví Voucher của người dùng
$user_vouchers = [];
$uv_query = $conn->query("
    SELECT uv.id as uv_id, uv.saved_at, uv.used_at, v.*, b.name as brand_name
    FROM user_vouchers uv
    JOIN vouchers v ON uv.voucher_id = v.id
    LEFT JOIN brands b ON v.brand_id = b.id
    WHERE uv.user_id = $user_id
    ORDER BY uv.used_at ASC, (v.end_date IS NULL OR v.end_date >= NOW()) DESC, uv.saved_at DESC
");
if ($uv_query) {
    while ($row = $uv_query->fetch_assoc()) {
        $user_vouchers[] = $row;
    }
}

$tab_param = $_GET['tab'] ?? '';
$active_tab = in_array($tab_param, ['address', 'vouchers']) ? $tab_param : 'info';

include_once 'includes/header.php';
?>

<style>
    body {
        background-color: #fcfbf7 !important;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }
    .profile-card {
        background-color: #ffffff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 10px 30px rgba(26, 29, 33, 0.05);
        border-radius: 12px;
    }
    .btn-gold {
        background-color: #c5a059;
        color: #ffffff;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-gold:hover {
        background-color: #b39150;
        color: #ffffff;
    }
    .text-gold {
        color: #c5a059 !important;
    }
    .nav-profile-tabs .nav-link {
        color: #666;
        border: 1px solid transparent;
        font-weight: 600;
        border-radius: 8px 8px 0 0;
    }
    .nav-profile-tabs .nav-link.active {
        color: #1a1d21;
        background-color: #ffffff;
        border-color: #e5e5e5;
        border-bottom-color: #ffffff;
    }
    .avatar-wrapper {
        width: 140px;
        height: 140px;
        margin: 0 auto 15px;
        border-radius: 50%;
        padding: 4px;
        background: linear-gradient(135deg, #c5a059, #1a1d21);
    }
    .avatar-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .address-box {
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .address-box.default {
        border-color: #c5a059;
        background-color: #fdfaf5;
    }
</style>

<div class="container my-5" style="max-width: 950px;">
    <div class="row g-4">
        
        <div class="col-md-4">
            <div class="card profile-card p-4 h-100 text-center">
                <div class="avatar-wrapper">
                    <?php 
                        $user_avatar = !empty($user['avatar']) && file_exists($user['avatar']) 
                                       ? $user['avatar'] 
                                       : (!empty($user['avatar']) ? $user['avatar'] : 'assets/images/default-avatar.png');
                    ?>
                    <img id="avatar-preview" src="<?= htmlspecialchars($user_avatar); ?>" alt="Avatar">
                </div>

                <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['fullname']); ?></h5>
                <p class="text-muted small mb-3"><?= htmlspecialchars($user['email'] ?? $user['phone'] ?? ''); ?></p>
                
                <div class="border-top pt-3 text-start small text-muted">
                    <p class="mb-1"><i class="fa-solid fa-clock me-2 text-gold"></i>Ngày tham gia: <strong><?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')); ?></strong></p>
                    <p class="mb-0"><i class="fa-solid fa-shield-check me-2 text-success"></i>Trạng thái: <span class="badge bg-success">Hoạt động</span></p>
                </div>
                
                <div class="mt-4 border-top pt-3">
                    <a href="my-orders.php" class="btn btn-outline-primary btn-sm w-100 mb-2"><i class="fa-solid fa-box me-1"></i> Lịch Sử Đơn Hàng</a>
                    <a href="change-password.php" class="btn btn-outline-dark btn-sm w-100 mb-2"><i class="fa-solid fa-key me-1"></i> Đổi Mật Khẩu</a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm w-100"><i class="fa-solid fa-right-from-bracket me-1"></i> Đăng Xuất</a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card profile-card p-4">
                
                <?php if ($error): ?><div class="alert alert-danger py-2 small fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i><?= $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success py-2 small fw-bold"><i class="fa-solid fa-circle-check me-1"></i><?= $success; ?></div><?php endif; ?>

                <ul class="nav nav-tabs nav-profile-tabs mb-4 border-bottom" id="profileTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link <?= $active_tab == 'info' ? 'active' : '' ?>" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-panel" type="button">
                            <i class="fa-solid fa-user me-1"></i> Hồ Sơ
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $active_tab == 'address' ? 'active' : '' ?>" id="address-tab" data-bs-toggle="tab" data-bs-target="#address-panel" type="button">
                            <i class="fa-solid fa-location-dot me-1"></i> Địa Chỉ
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $active_tab == 'vouchers' ? 'active' : '' ?>" id="vouchers-tab" data-bs-toggle="tab" data-bs-target="#vouchers-panel" type="button">
                            <i class="fa-solid fa-ticket me-1 text-warning"></i> Ví Voucher 
                            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($user_vouchers) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabContent">
                    
                    <!-- TAB 1: THÔNG TIN -->
                    <div class="tab-pane fade <?= $active_tab == 'info' ? 'show active' : '' ?>" id="info-panel">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Thay đổi Ảnh đại diện</label>
                                <input type="file" name="avatar" class="form-control" accept="image/*" onchange="previewImage(this)">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Họ và Tên <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small">Địa chỉ Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold small d-flex justify-content-between">
                                    <span>Ngày sinh</span>
                                    <span class="text-muted fw-normal" style="font-size: 12px;">(Từ năm 1920 đến nay)</span>
                                </label>
                                <input type="date" name="birthday" class="form-control" 
                                       min="1920-01-01" max="<?= date('Y-m-d'); ?>" 
                                       value="<?= htmlspecialchars($user['birthday'] ?? ''); ?>">
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-gold w-100 fw-bold rounded-3">
                                LƯU THAY ĐỔI
                            </button>
                        </form>
                    </div>

                    <!-- TAB 2: ĐỊA CHỈ -->
                    <div class="tab-pane fade <?= $active_tab == 'address' ? 'show active' : '' ?>" id="address-panel">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Địa chỉ của tôi</h6>
                            <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="fa-solid fa-plus me-1"></i> Thêm Địa Chỉ
                            </button>
                        </div>

                        <?php if (empty($addresses)): ?>
                            <div class="text-center text-muted py-4 bg-light rounded-4 border">
                                <i class="fa-solid fa-map-location-dot fa-3x text-muted mb-2 opacity-50"></i>
                                <p class="mb-0 fw-semibold">Bạn chưa có địa chỉ nhận hàng nào.</p>
                                <small class="text-muted">Bấm nút "Thêm Địa Chỉ" ở trên để lưu địa chỉ giao hàng.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-box <?= $addr['is_default'] ? 'default' : '' ?> rounded-4 p-3 mb-3 bg-white shadow-sm border position-relative">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark">
                                                <?= htmlspecialchars($addr['recipient_name']); ?>
                                                <?php if ($addr['is_default']): ?>
                                                    <span class="badge bg-danger ms-2 small rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Mặc định</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-1 text-muted small"><i class="fa-solid fa-phone me-1 text-primary"></i> <?= htmlspecialchars($addr['phone']); ?></p>
                                            <p class="mb-0 text-dark small"><i class="fa-solid fa-map-location-dot me-1 text-danger"></i> <?= htmlspecialchars($addr['address_detail']); ?>, <strong><?= htmlspecialchars($addr['province_name']); ?></strong></p>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                                    onclick="openEditAddressModal(<?= htmlspecialchars(json_encode($addr), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Sửa
                                            </button>
                                            <?php if (!$addr['is_default']): ?>
                                                <form method="POST" class="d-inline-block m-0">
                                                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                                    <button type="submit" name="set_default" class="btn btn-sm btn-outline-dark rounded-pill px-3">Đặt mặc định</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline-block m-0" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                                <button type="submit" name="delete_address" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Xóa địa chỉ">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                    </div>

                    <!-- TAB 3: VÍ VOUCHER CỦA TÔI -->
                    <div class="tab-pane fade <?= $active_tab == 'vouchers' ? 'show active' : '' ?>" id="vouchers-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-ticket text-warning me-1"></i>Mã giảm giá đã lưu của bạn</h6>
                                <small class="text-muted">Các ưu đãi bạn đã lưu và sẵn sàng áp dụng khi thanh toán đơn hàng.</small>
                            </div>
                            <a href="index.php#voucherSectionReveal" class="btn btn-outline-dark btn-sm rounded-pill fw-bold px-3">
                                <i class="fa-solid fa-plus me-1"></i> Săn thêm mã
                            </a>
                        </div>

                        <?php if (empty($user_vouchers)): ?>
                            <div class="text-center py-5 bg-light rounded-4 border">
                                <i class="fa-solid fa-ticket-simple fa-3x text-muted mb-3 opacity-50"></i>
                                <h6 class="fw-bold text-dark">Ví voucher của bạn đang trống</h6>
                                <p class="text-muted small mb-3">Hãy ghé thăm trang chủ và các sự kiện để lưu ngay những mã giảm giá cực hời nhé!</p>
                                <a href="index.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-4 text-dark shadow-sm">
                                    <i class="fa-solid fa-fire me-1"></i> Khám phá ưu đãi ngay
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($user_vouchers as $uv): 
                                    $is_used = !empty($uv['used_at']);
                                    $is_expired = (!empty($uv['end_date']) && strtotime($uv['end_date']) < time());
                                    $is_valid = (!$is_used && !$is_expired);
                                    $vtype = $uv['discount_type'];

                                    if ($vtype === 'freeship') {
                                        $theme_class = 'voucher-theme-emerald';
                                        $stub_icon = 'fa-solid fa-truck-fast';
                                        $stub_val = 'FREE';
                                        $stub_label = 'SHIP';
                                        $disc_badge = 'Miễn phí vận chuyển';
                                    } elseif ($vtype === 'percent') {
                                        $theme_class = 'voucher-theme-gold';
                                        $stub_icon = 'fa-solid fa-percent';
                                        $stub_val = intval($uv['discount_value']) . '%';
                                        $stub_label = 'GIẢM GIÁ';
                                        $disc_badge = 'Giảm ' . intval($uv['discount_value']) . '%';
                                    } else {
                                        $theme_class = 'voucher-theme-crimson';
                                        $stub_icon = 'fa-solid fa-tag';
                                        $stub_val = (floatval($uv['discount_value']) >= 1000) ? (intval($uv['discount_value'] / 1000) . 'K') : number_format($uv['discount_value'], 0, ',', '.') . 'đ';
                                        $stub_label = 'GIẢM TIỀN';
                                        $disc_badge = 'Giảm ' . number_format($uv['discount_value'], 0, ',', '.') . 'đ';
                                    }
                                ?>
                                    <div class="col-12">
                                        <div class="voucher-ticket <?= $theme_class ?> <?= !$is_valid ? 'opacity-50 grayscale' : '' ?> m-0 position-relative shadow-sm" style="border: 1.5px solid <?= $is_valid ? '#e2e8f0' : '#f1f5f9' ?>;">
                                            <!-- Cuống vé -->
                                            <div class="voucher-ticket-stub">
                                                <i class="<?= $stub_icon ?> voucher-stub-icon"></i>
                                                <div class="voucher-stub-value"><?= $stub_val ?></div>
                                                <div class="voucher-stub-label"><?= $stub_label ?></div>
                                            </div>
                                            
                                            <!-- Divider -->
                                            <div class="voucher-ticket-divider">
                                                <div class="voucher-notch voucher-notch-top" style="background-color: #ffffff;"></div>
                                                <div class="voucher-notch voucher-notch-bottom" style="background-color: #ffffff;"></div>
                                            </div>

                                            <!-- Body -->
                                            <div class="voucher-ticket-body">
                                                <div class="voucher-info-wrapper">
                                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                        <span class="voucher-badge-type"><?= $disc_badge ?></span>
                                                        <?php if ($is_used): ?>
                                                            <span class="badge bg-secondary text-white">Đã dùng ngày <?= date('d/m/Y', strtotime($uv['used_at'])) ?></span>
                                                        <?php elseif ($is_expired): ?>
                                                            <span class="badge bg-danger text-white">Đã hết hạn</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success border border-success fw-bold">Khả dụng</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <h6 class="voucher-title text-dark mb-1"><?= htmlspecialchars($uv['title']) ?></h6>
                                                    <div class="voucher-conditions small">
                                                        Đơn tối thiểu: <strong class="text-dark"><?= number_format($uv['min_order_value'], 0, ',', '.') ?>đ</strong>
                                                        <span class="mx-1">•</span>
                                                        HSD: <strong><?= date('d/m/Y', strtotime($uv['end_date'])) ?></strong>
                                                        <?php if (!empty($uv['brand_name'])): ?>
                                                            <span class="mx-1">•</span> Áp dụng: <strong class="text-warning"><?= htmlspecialchars($uv['brand_name']) ?></strong>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="voucher-action-area">
                                                    <div class="voucher-code-badge" data-code="<?= htmlspecialchars($uv['code']) ?>" title="Sao chép mã">
                                                        <?= htmlspecialchars($uv['code']) ?> <i class="fa-regular fa-copy ms-1 opacity-75"></i>
                                                    </div>
                                                    <?php if ($is_valid): ?>
                                                        <a href="all-products.php" class="btn btn-voucher-action btn-voucher-use text-decoration-none">
                                                            <i class="fa-solid fa-bag-shopping"></i> Dùng ngay
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Thêm Địa Chỉ Mới (Centered, Modern, Beautiful) -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <div class="modal-header bg-dark text-white py-3 px-4">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fa-solid fa-map-location-dot text-warning me-2"></i>Thêm Địa Chỉ Nhận Hàng Mới
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3 bg-white p-3 rounded-3 shadow-sm border">
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark">Họ và Tên người nhận <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control rounded-3" placeholder="VD: Nguyễn Văn A" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                            <input type="tel" name="address_phone" class="form-control rounded-3" placeholder="VD: 0912345678" pattern="^(0|\+84)(3|5|7|8|9)[0-9]{8}$" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-dark">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <select name="province_id" id="profile_province_id" class="form-select rounded-3 fw-semibold text-primary" required>
                                <option value="">-- Chọn Tỉnh / Thành phố nhận hàng --</option>
                                <?php foreach ($provinces as $prov): ?>
                                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['province_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                <label class="form-label fw-bold small text-dark mb-0">Địa chỉ cụ thể (Số nhà, đường, xã/phường...)</label>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" onclick="openMapPicker('profile_address_detail', 'profile_province_id')">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> 🗺️ Chọn trên bản đồ Maps
                                </button>
                            </div>
                            <textarea name="address_detail" id="profile_address_detail" class="form-control rounded-3" rows="2" placeholder="VD: 123 Đường Nguyễn Huệ, Phường 1, TP..." required></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default_check">
                                <label class="form-check-label small fw-bold text-dark" for="is_default_check">
                                    Đặt làm địa chỉ giao hàng mặc định
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_address" class="btn btn-warning text-dark rounded-pill px-5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> LƯU ĐỊA CHỈ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa Địa Chỉ (Centered, Modern, Beautiful) -->
<div class="modal fade" id="editAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <input type="hidden" name="address_id" id="edit_address_id" value="0">
                <div class="modal-header bg-dark text-white py-3 px-4">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fa-solid fa-pen-to-square text-warning me-2"></i>Chỉnh Sửa Địa Chỉ Nhận Hàng
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3 bg-white p-3 rounded-3 shadow-sm border">
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark">Họ và Tên người nhận <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" id="edit_recipient_name" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold small text-dark">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                            <input type="tel" name="address_phone" id="edit_address_phone" class="form-control rounded-3" pattern="^(0|\+84)(3|5|7|8|9)[0-9]{8}$" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-dark">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <select name="province_id" id="edit_province_id" class="form-select rounded-3 fw-semibold text-primary" required>
                                <option value="">-- Chọn Tỉnh / Thành phố nhận hàng --</option>
                                <?php foreach ($provinces as $prov): ?>
                                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['province_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                <label class="form-label fw-bold small text-dark mb-0">Địa chỉ cụ thể (Số nhà, đường, xã/phường...)</label>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" onclick="openMapPicker('edit_address_detail', 'edit_province_id')">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> 🗺️ Chọn trên bản đồ Maps
                                </button>
                            </div>
                            <textarea name="address_detail" id="edit_address_detail" class="form-control rounded-3" rows="2" required></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="edit_is_default_check">
                                <label class="form-check-label small fw-bold text-dark" for="edit_is_default_check">
                                    Đặt làm địa chỉ giao hàng mặc định
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="edit_address" class="btn btn-warning text-dark rounded-pill px-5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> CẬP NHẬT ĐỊA CHỈ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/map-picker-modal.php'; ?>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function openEditAddressModal(addr) {
    if (!addr) return;
    document.getElementById('edit_address_id').value = addr.id || 0;
    document.getElementById('edit_recipient_name').value = addr.recipient_name || '';
    document.getElementById('edit_address_phone').value = addr.phone || '';
    document.getElementById('edit_province_id').value = addr.province_id || '';
    document.getElementById('edit_address_detail').value = addr.address_detail || '';
    document.getElementById('edit_is_default_check').checked = (addr.is_default == 1);

    var modalEl = document.getElementById('editAddressModal');
    var modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalObj.show();
}
</script>

<?php include_once 'includes/footer.php'; ?>