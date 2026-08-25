<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/CarrierShippingService.php';

// Kiểm tra quyền Admin (Chỉ Quản trị viên mới được cấu hình Phí vận chuyển & API hãng vận chuyển)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    if (!empty($_POST)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền cấu hình phí vận chuyển!']);
        exit();
    }
    header('Location: index.php');
    exit();
}

$carrier_service = new CarrierShippingService($conn);

// ═════════════════════════════════════════════════════════════════════
// 1. AJAX: LƯU CẤU HÌNH API ĐƠN VỊ VẬN CHUYỂN & ĐỊA CHỈ KHO HÀNG CHUNG
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_save_carrier_settings'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $carrier_active = trim($_POST['carrier_active'] ?? 'GHTK');
    $ghtk_token     = trim($_POST['ghtk_api_token'] ?? '');
    $ghtk_name      = trim($_POST['ghtk_pick_name'] ?? 'Kho Giày Shoes Store Vĩnh Long');
    $ghtk_tel       = trim($_POST['ghtk_pick_tel'] ?? '0901234567');
    $ghtk_prov      = trim($_POST['ghtk_pick_province'] ?? 'Vĩnh Long');
    $ghtk_dist      = trim($_POST['ghtk_pick_district'] ?? 'Thành phố Vĩnh Long');
    $ghtk_addr      = trim($_POST['ghtk_pick_address'] ?? 'Số 123 Đường Phạm Hùng, Phường 9');
    
    $ghn_token      = trim($_POST['ghn_api_token'] ?? '');
    $ghn_shop_id    = trim($_POST['ghn_shop_id'] ?? '');
    
    $weight         = max(100, intval($_POST['default_shoe_weight'] ?? 800));

    $settings_arr = [
        'carrier_active'      => $carrier_active,
        'ghtk_api_token'      => $ghtk_token,
        'ghtk_environment'    => 'sandbox',
        'ghtk_pick_name'      => $ghtk_name,
        'ghtk_pick_tel'       => $ghtk_tel,
        'ghtk_pick_province'  => $ghtk_prov,
        'ghtk_pick_district'  => $ghtk_dist,
        'ghtk_pick_address'   => $ghtk_addr,
        'ghn_api_token'       => $ghn_token,
        'ghn_shop_id'         => $ghn_shop_id,
        'ghn_environment'     => 'sandbox',
        'default_shoe_weight' => $weight
    ];

    foreach ($settings_arr as $k => $v) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                                VALUES (?, ?, 'shipping_api') 
                                ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $k, $v, $v);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu cấu hình kết nối API & Địa chỉ kho hàng chung thành công!'
    ]);
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 2. AJAX: TEST KẾT NỐI API VẬN CHUYỂN
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_test_carrier_connection'])) {
    header('Content-Type: application/json; charset=utf-8');
    $carrier = trim($_POST['carrier'] ?? 'GHTK');
    $token   = trim($_POST['token'] ?? '');
    $shop_id = trim($_POST['shop_id'] ?? '');

    $res = $carrier_service->testConnection($carrier, $token, 'sandbox', $shop_id);
    echo json_encode($res);
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 3. AJAX: THÊM HOẶC CẬP NHẬT TỈNH THÀNH (ADD / EDIT PROVINCE)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_save_province'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid   = intval($_POST['province_id'] ?? 0);
    $pname = trim($_POST['province_name'] ?? '');
    $fee   = max(0, floatval($_POST['shipping_fee'] ?? 30000));
    $days  = trim($_POST['estimated_days'] ?? '2-4 ngày');
    $st    = intval($_POST['status'] ?? 1);

    if (empty($pname)) {
        echo json_encode(['success' => false, 'message' => 'Tên tỉnh thành không được để trống!']);
        exit();
    }

    if ($pid > 0) {
        $stmt = $conn->prepare("UPDATE shipping_provinces SET province_name = ?, shipping_fee = ?, estimated_days = ?, status = ? WHERE id = ?");
        $stmt->bind_param('sdsii', $pname, $fee, $days, $st, $pid);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => "Đã cập nhật thông tin tỉnh/thành '$pname' thành công!"]);
    } else {
        $chk = $conn->prepare("SELECT id FROM shipping_provinces WHERE province_name = ?");
        $chk->bind_param('s', $pname);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exists) {
            echo json_encode(['success' => false, 'message' => "Tỉnh/thành '$pname' đã tồn tại trong danh sách!"]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO shipping_provinces (province_name, shipping_fee, estimated_days, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sdsi', $pname, $fee, $days, $st);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => "Đã thêm mới tỉnh/thành '$pname' vào biểu cước thành công!"]);
    }
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 4. AJAX: BẬT/TẮT TRẠNG THÁI TỈNH THÀNH (TOGGLE STATUS)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_toggle_province_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid = intval($_POST['province_id'] ?? 0);
    if ($pid > 0) {
        $curr = $conn->query("SELECT status, province_name FROM shipping_provinces WHERE id = $pid")->fetch_assoc();
        if ($curr) {
            $new_st = ($curr['status'] == 1) ? 0 : 1;
            $conn->query("UPDATE shipping_provinces SET status = $new_st WHERE id = $pid");
            echo json_encode([
                'success' => true,
                'new_status' => $new_st,
                'message' => "Đã " . ($new_st == 1 ? "kích hoạt áp dụng" : "tạm khóa") . " cước tỉnh " . $curr['province_name']
            ]);
            exit();
        }
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tỉnh thành!']);
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 5. AJAX: XÓA TỈNH THÀNH (DELETE PROVINCE)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_delete_province'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid = intval($_POST['province_id'] ?? 0);
    if ($pid > 0) {
        $curr = $conn->query("SELECT province_name FROM shipping_provinces WHERE id = $pid")->fetch_assoc();
        if ($curr) {
            $conn->query("DELETE FROM shipping_provinces WHERE id = $pid");
            echo json_encode([
                'success' => true,
                'message' => "Đã xóa tỉnh/thành '{$curr['province_name']}' khỏi danh sách biểu cước!"
            ]);
            exit();
        }
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tỉnh thành cần xóa!']);
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 6. AJAX: CÔNG CỤ TÍNH THỬ CƯỚC REAL-TIME (LIVE CALCULATOR TOOL)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_calculate_test_fee'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $province_name = trim($_POST['province_name'] ?? 'Hà Nội');
    $weight_gram   = max(100, intval($_POST['weight_gram'] ?? 800));

    $res = $carrier_service->calculateAllCarriersFee($province_name, '', '', '', $weight_gram, 0);
    
    // Bổ sung cước theo biểu phí nội bộ CSDL
    $p_esc = $conn->real_escape_string($province_name);
    $local_row = $conn->query("SELECT * FROM shipping_provinces WHERE province_name LIKE '%$p_esc%' LIMIT 1")->fetch_assoc();
    $local_fee = $local_row ? floatval($local_row['shipping_fee']) : 30000;
    $local_days = $local_row ? $local_row['estimated_days'] : '2-4 ngày';

    $res['local'] = [
        'code'           => 'LOCAL',
        'name'           => 'Biểu Phí Nội Bộ (Kho Vĩnh Long)',
        'fee'            => $local_fee,
        'estimated_days' => $local_days,
        'badge'          => 'Cố định theo CSDL',
        'icon'           => 'fa-store text-secondary'
    ];

    echo json_encode($res);
    exit();
}

// ========================================================
// 7. NẠP DỮ LIỆU GIAO DIỆN
// ========================================================
include_once 'includes/header.php';

// Đọc cài đặt API & Địa chỉ kho gửi hàng chung
$carrier_active = $carrier_service->getActiveCarrier();
$ghtk_token     = $carrier_service->getSetting('ghtk_api_token', 'd8E2109bA78796123456789aBcDeF0123456789');
$ghtk_name      = $carrier_service->getSetting('ghtk_pick_name', 'Kho Giày Shoes Store Vĩnh Long');
$ghtk_tel       = $carrier_service->getSetting('ghtk_pick_tel', '0901234567');
$ghtk_prov      = $carrier_service->getSetting('ghtk_pick_province', 'Vĩnh Long');
$ghtk_dist      = $carrier_service->getSetting('ghtk_pick_district', 'Thành phố Vĩnh Long');
$ghtk_addr      = $carrier_service->getSetting('ghtk_pick_address', 'Số 123 Đường Phạm Hùng, Phường 9');

$ghn_token      = $carrier_service->getSetting('ghn_api_token', '9f32e29e-648b-11ee-b1d4-92d443b7a81c');
$ghn_shop_id    = $carrier_service->getSetting('ghn_shop_id', '123456');

$default_weight = intval($carrier_service->getSetting('default_shoe_weight', 800));

// Lấy danh sách 63 tỉnh thành
$provinces_res = $conn->query("SELECT * FROM shipping_provinces ORDER BY id ASC");
$provinces_list = [];
$total_provinces = 0;
$active_provinces = 0;
$inactive_provinces = 0;

if ($provinces_res) {
    while ($r = $provinces_res->fetch_assoc()) {
        if ($r['status'] == 1) $active_provinces++;
        else $inactive_provinces++;

        $provinces_list[] = $r;
        $total_provinces++;
    }
}
?>

<style>
.stat-card-shipping {
    border: none;
    border-radius: 20px;
    background: #fff;
    padding: 1.5rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.stat-card-shipping:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px rgba(0,0,0,0.09);
}
.stat-card-shipping::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: var(--stat-accent, #3b82f6);
}
.carrier-card-option {
    border: 2px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.35rem;
    cursor: pointer;
    transition: all 0.25s ease;
    background: #fff;
    position: relative;
}
.carrier-card-option:hover {
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(59, 130, 246, 0.12);
}
.carrier-card-option.active {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.carrier-card-option input[type="radio"] {
    position: absolute;
    top: 18px;
    right: 18px;
    transform: scale(1.3);
}
.status-filter-chip {
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
}
.status-filter-chip:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.status-filter-chip.active {
    background: #0f172a;
    color: #f8fafc;
    border-color: #0f172a;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}
.calc-card-result {
    border-radius: 16px;
    border: 2px solid #e2e8f0;
    transition: all 0.25s ease;
    background: #fff;
}
.calc-card-result:hover {
    border-color: #3b82f6;
    box-shadow: 0 10px 24px rgba(0,0,0,0.08);
}
</style>

<!-- TIÊU ĐỀ TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-truck-fast text-primary me-2"></i>Quản Lý Cước Vận Chuyển &amp; API Hãng
        </h4>
        <span class="text-muted small">Cấu hình kết nối API <b>GHTK / GHN Express</b>, địa chỉ kho gửi hàng và biểu cước 63 tỉnh thành.</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-dark fw-bold rounded-pill px-3 shadow-sm" onclick="openAddProvinceModal()">
            <i class="fa-solid fa-plus me-1 text-primary"></i> Thêm Tỉnh Thành
        </button>
        <button type="button" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" onclick="document.getElementById('btnSaveCarrierSettings').click()">
            <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Cấu Hình
        </button>
    </div>
</div>

<!-- 4 THẺ THỐNG KÊ TỔNG QUAN (STAT CARDS) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Hãng Đang Kích Hoạt -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-shipping h-100" style="--stat-accent: #3b82f6;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Hãng Kích Hoạt</span>
                    <h4 class="fw-bold text-dark mb-0">
                        <?= ($carrier_active === 'GHN') ? 'GHN Express' : (($carrier_active === 'LOCAL') ? 'Biểu Phí Nội Bộ' : 'GHTK') ?>
                    </h4>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-circle fs-4">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-circle-check me-1"></i> Đang Hoạt Động
                </span>
            </div>
        </div>
    </div>

    <!-- Card 2: Độ Phủ Sóng 63 Tỉnh -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-shipping h-100" style="--stat-accent: #10b981;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Mạng Lưới Giao Hàng</span>
                    <h4 class="fw-bold text-success mb-0"><?= $active_provinces ?> / <?= $total_provinces ?> Tỉnh</h4>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-circle fs-4">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="fa-solid fa-circle-check text-success me-1"></i> <?= $active_provinces ?> tỉnh đang mở áp dụng cước
            </div>
        </div>
    </div>

    <!-- Card 3: Chính Sách Freeship Qua Voucher -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-shipping h-100" style="--stat-accent: #f59e0b;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Miễn Phí Vận Chuyển</span>
                    <h4 class="fw-bold text-warning text-dark mb-0">Qua Voucher</h4>
                </div>
                <div class="p-3 bg-warning-subtle text-warning rounded-circle fs-4">
                    <i class="fa-solid fa-ticket"></i>
                </div>
            </div>
            <div class="mt-3 small">
                <a href="vouchers.php" class="text-primary text-decoration-none fw-semibold">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Quản lý mã giảm giá
                </a>
            </div>
        </div>
    </div>

    <!-- Card 4: Trọng Lượng Đóng Gói -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card-shipping h-100" style="--stat-accent: #8b5cf6;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Quy Chuẩn Giày</span>
                    <h4 class="fw-bold text-primary mb-0"><?= $default_weight ?>g / đôi</h4>
                </div>
                <div class="p-3 bg-purple-subtle text-primary rounded-circle fs-4" style="background: #ede9fe;">
                    <i class="fa-solid fa-weight-scale text-primary"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                Dùng gửi API tính cước theo số lượng
            </div>
        </div>
    </div>
</div>

<!-- THANH CHUYỂN TAB HIỆN ĐẠI -->
<ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="shippingTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-carrier-api" type="button">
            <i class="fa-solid fa-cloud-bolt me-2 text-warning"></i>1. Tích Hợp API Hãng &amp; Địa Chỉ Kho Gửi
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-provinces-list" type="button">
            <i class="fa-solid fa-map-location-dot me-2 text-info"></i>2. Bảng Cước 63 Tỉnh Thành
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-live-calc" type="button">
            <i class="fa-solid fa-calculator me-2 text-success"></i>3. Giả Lập &amp; Tính Thử Cước Real-Time
        </button>
    </li>
</ul>

<div class="tab-content" id="shippingTabsContent">
    
    <!-- ═════════════════════════════════════════════════════════════════════
         TAB 1: TÍCH HỢP API ĐƠN VỊ VẬN CHUYỂN & ĐỊA CHỈ KHO GỬI HÀNG CHUNG
    ══════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade show active" id="tab-carrier-api" role="tabpanel">
        <form id="carrierSettingsForm">
            <input type="hidden" name="ajax_save_carrier_settings" value="1">

            <!-- 1. CHỌN ĐƠN VỊ VẬN CHUYỂN MẶC ĐỊNH -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3">
                    <span class="badge bg-primary rounded-circle p-2 me-2"><i class="fa-solid fa-network-wired"></i></span>
                    Chọn Đơn Vị Vận Chuyển Kích Hoạt (Active Carrier)
                </h6>
                <div class="row g-3">
                    <!-- GHTK Option -->
                    <div class="col-md-4">
                        <div class="carrier-card-option <?= $carrier_active === 'GHTK' ? 'active' : '' ?>" onclick="selectCarrier('GHTK')">
                            <input type="radio" name="carrier_active" id="carrier_ghtk" value="GHTK" <?= $carrier_active === 'GHTK' ? 'checked' : '' ?>>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                                    <i class="fa-solid fa-truck-fast"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">GHTK</h6>
                                    <small class="text-muted">Giao Hàng Tiết Kiệm</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">Hỗ trợ tính cước theo trọng lượng, tự động sinh mã vận đơn, in tem mã vạch barcode và gọi bưu tá lấy hàng tận nơi.</p>
                        </div>
                    </div>

                    <!-- GHN Option -->
                    <div class="col-md-4">
                        <div class="carrier-card-option <?= $carrier_active === 'GHN' ? 'active' : '' ?>" onclick="selectCarrier('GHN')">
                            <input type="radio" name="carrier_active" id="carrier_ghn" value="GHN" <?= $carrier_active === 'GHN' ? 'checked' : '' ?>>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">GHN Express</h6>
                                    <small class="text-muted">Giao Hàng Nhanh</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">Mạng lưới bưu cục toàn quốc, tốc độ giao hàng 1-3 ngày, tích hợp API GHN v2 chuyên nghiệp.</p>
                        </div>
                    </div>

                    <!-- Local Option -->
                    <div class="col-md-4">
                        <div class="carrier-card-option <?= $carrier_active === 'LOCAL' ? 'active' : '' ?>" onclick="selectCarrier('LOCAL')">
                            <input type="radio" name="carrier_active" id="carrier_local" value="LOCAL" <?= $carrier_active === 'LOCAL' ? 'checked' : '' ?>>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-secondary-subtle text-secondary p-3 rounded-circle fs-4">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Biểu Phí Nội Bộ</h6>
                                    <small class="text-muted">Bảng Cước 63 Tỉnh Thành</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">Tính phí cố định theo danh sách 63 Tỉnh Thành trong CSDL mà không cần gọi API bên thứ 3.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. CẤU HÌNH API GHTK & GHN -->
            <div class="row g-4 mb-4">
                <!-- Cấu hình GHTK -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">
                                <span class="badge bg-success rounded-circle p-2 me-2"><i class="fa-solid fa-key"></i></span>
                                Cấu Hình API GHTK
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3 shadow-sm" onclick="testGHTKConnection()">
                                <i class="fa-solid fa-bolt me-1"></i> Kiểm Tra Kết Nối
                            </button>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">API Token GHTK <span class="text-danger">*</span></label>
                            <input type="text" name="ghtk_api_token" id="input_ghtk_token" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($ghtk_token) ?>" placeholder="Nhập API Token GHTK...">
                        </div>
                    </div>
                </div>

                <!-- Cấu hình GHN -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">
                                <span class="badge bg-primary rounded-circle p-2 me-2"><i class="fa-solid fa-key"></i></span>
                                Cấu Hình API GHN Express
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 shadow-sm" onclick="testGHNConnection()">
                                <i class="fa-solid fa-bolt me-1"></i> Kiểm Tra Kết Nối
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-8">
                                <label class="form-label fw-semibold small">API Token GHN</label>
                                <input type="text" name="ghn_api_token" id="input_ghn_token" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($ghn_token) ?>" placeholder="Nhập Token GHN...">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold small">Shop ID GHN</label>
                                <input type="text" name="ghn_shop_id" id="input_ghn_shop_id" class="form-control fw-bold" value="<?= htmlspecialchars($ghn_shop_id) ?>" placeholder="VD: 123456">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. THÔNG TIN KHO LẤY HÀNG GỬI ĐI (DÙNG CHUNG CHO CẢ GHTK, GHN VÀ IN PHIẾU VẬN ĐƠN) -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <span class="badge bg-danger rounded-circle p-2 me-2"><i class="fa-solid fa-warehouse"></i></span>
                        Thông Tin Kho Lấy Hàng Gửi Đi (Kho Shop Vĩnh Long)
                    </h6>
                    <span class="badge bg-light text-muted border small">Dùng chung cho cả GHTK, GHN và In Phiếu Giao Hàng</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tên Kho Hàng / Shop</label>
                        <input type="text" name="ghtk_pick_name" class="form-control fw-semibold" value="<?= htmlspecialchars($ghtk_name) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">SĐT Liên Hệ Kho</label>
                        <input type="text" name="ghtk_pick_tel" class="form-control fw-bold text-success" value="<?= htmlspecialchars($ghtk_tel) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tỉnh / Thành Phố Kho</label>
                        <input type="text" name="ghtk_pick_province" class="form-control" value="<?= htmlspecialchars($ghtk_prov) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Quận / Huyện Kho</label>
                        <input type="text" name="ghtk_pick_district" class="form-control" value="<?= htmlspecialchars($ghtk_dist) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Địa Chỉ Chi Tiết Kho</label>
                        <input type="text" name="ghtk_pick_address" class="form-control" value="<?= htmlspecialchars($ghtk_addr) ?>">
                    </div>
                </div>
            </div>

            <!-- 4. QUY CHUẨN ĐÓNG GÓI & QUẢN LÝ MÃ GIẢM GIÁ (FREESHIP) -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3">
                    <span class="badge bg-warning text-dark rounded-circle p-2 me-2"><i class="fa-solid fa-box"></i></span>
                    Quy Chuẩn Đóng Gói &amp; Chính Sách Miễn Phí Vận Chuyển (Freeship)
                </h6>
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small"><i class="fa-solid fa-weight-scale me-1 text-danger"></i> Trọng Lượng Trung Bình 1 Đôi Giày (Gram)</label>
                        <div class="input-group">
                            <input type="number" name="default_shoe_weight" class="form-control fw-bold text-primary" value="<?= $default_weight ?>" step="50" min="100">
                            <span class="input-group-text">Gram / Đôi</span>
                        </div>
                        <div class="form-text small text-muted">Dùng để gửi lên API tính cước theo số lượng đôi trong giỏ hàng (VD: 800g/đôi).</div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-ticket text-warning fs-5"></i>
                                <strong class="text-dark small">Chính Sách Miễn Phí Vận Chuyển (Freeship)</strong>
                            </div>
                            <p class="small text-muted mb-2">
                                Hệ thống áp dụng miễn phí vận chuyển cho khách hàng thông qua các <strong>Mã giảm giá Freeship</strong>.
                            </p>
                            <a href="vouchers.php" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">
                                <i class="fa-solid fa-ticket me-1"></i> Quản Lý Mã Giảm Giá
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <button type="submit" id="btnSaveCarrierSettings" class="btn btn-primary fw-bold rounded-pill px-5 py-3 shadow-lg fs-6">
                    <i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH VẬN CHUYỂN
                </button>
            </div>
        </form>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════
         TAB 2: BẢNG CƯỚC 63 TỈNH THÀNH
    ══════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="tab-provinces-list" role="tabpanel">
        
        <!-- BẢNG TỈNH THÀNH -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
            
            <!-- TOOLBAR TRÊN BẢNG -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>Danh Sách Biểu Phí 63 Tỉnh Thành Việt Nam
                    </h5>
                    <span class="text-muted small">Cước phí cố định áp dụng khi sử dụng Biểu Phí Nội Bộ hoặc làm dự phòng (fallback).</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary fw-bold rounded-pill px-3 shadow-sm btn-sm" onclick="openAddProvinceModal()">
                        <i class="fa-solid fa-plus me-1 text-white"></i> Thêm Tỉnh Thành Mới
                    </button>
                </div>
            </div>

            <!-- BỘ LỌC TRẠNG THÁI & TÌM KIẾM -->
            <div class="p-3 bg-light rounded-4 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="small fw-bold text-dark me-1"><i class="fa-solid fa-filter text-primary me-1"></i>Trạng thái:</span>
                    <button type="button" class="status-filter-chip active" onclick="filterByStatus('all', this)">Tất cả (<?= $total_provinces ?>)</button>
                    <button type="button" class="status-filter-chip" onclick="filterByStatus('active', this)">🟢 Đang Áp Dụng (<?= $active_provinces ?>)</button>
                    <button type="button" class="status-filter-chip" onclick="filterByStatus('inactive', this)">🔴 Tạm Khóa (<?= $inactive_provinces ?>)</button>
                </div>
                <div style="min-width: 250px;">
                    <input type="text" id="filterProvinceSearch" class="form-control form-control-sm rounded-pill px-3 shadow-sm" placeholder="🔍 Tìm nhanh tên tỉnh..." oninput="filterProvincesTable(this.value)">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="provincesTable">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th class="ps-3" style="width: 60px;">STT</th>
                            <th>Tỉnh / Thành Phố</th>
                            <th>Cước Phí Vận Chuyển (VNĐ)</th>
                            <th>Thời Gian Giao Hàng Dự Kiến</th>
                            <th>Trạng Thái</th>
                            <th class="text-end pe-3">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach($provinces_list as $p): ?>
                            <tr id="prov-row-<?= $p['id'] ?>" data-status="<?= $p['status'] == 1 ? 'active' : 'inactive' ?>">
                                <td class="ps-3 fw-bold text-muted"><?= $idx++ ?></td>
                                <td>
                                    <strong class="text-dark fs-6"><?= htmlspecialchars($p['province_name']) ?></strong>
                                    <?php if ($p['province_name'] === 'Vĩnh Long'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 small">Kho Vĩnh Long</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6"><?= number_format($p['shipping_fee'], 0, ',', '.') ?>đ</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="fa-solid fa-clock me-1 text-warning"></i><?= htmlspecialchars($p['estimated_days']) ?></span>
                                </td>
                                <td>
                                    <?php if ($p['status'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-1 fw-bold">🟢 Đang Áp Dụng</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 py-1 fw-bold">🔴 Tạm Khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold me-1" onclick="openEditProvinceModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fa-solid fa-pen-to-square"></i> Sửa
                                    </button>
                                    <button type="button" class="btn btn-sm <?= $p['status'] == 1 ? 'btn-outline-warning text-dark' : 'btn-outline-success' ?> rounded-pill px-3 fw-bold me-1" onclick="toggleProvinceStatus(<?= $p['id'] ?>)">
                                        <?= $p['status'] == 1 ? '<i class="fa-solid fa-lock me-1"></i>Khóa' : '<i class="fa-solid fa-unlock me-1"></i>Mở' ?>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 fw-bold" onclick="deleteProvince(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['province_name'])) ?>')" title="Xóa tỉnh thành">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════
         TAB 3: CÔNG CỤ GIẢ LẬP & TÍNH THỬ CƯỚC REAL-TIME (LIVE SIMULATOR)
    ══════════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="tab-live-calc" role="tabpanel">
        <div class="row g-4">
            
            <!-- Khung Nhập Dữ Liệu Test -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-calculator text-success me-2"></i>Tính Thử Cước Vận Chuyển
                    </h5>
                    <p class="text-muted small mb-3">Kiểm tra kết quả tính cước real-time và so sánh giữa các hãng vận chuyển.</p>
                    
                    <form id="liveFeeCalcForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Tỉnh / Thành Phố Nhận Hàng:</label>
                            <select id="calc_province_name" class="form-select fw-bold">
                                <?php foreach($provinces_list as $p): ?>
                                    <option value="<?= htmlspecialchars($p['province_name']) ?>" <?= $p['province_name'] === 'TP. Hồ Chí Minh' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['province_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold small text-dark"><i class="fa-solid fa-shoe-prints me-1 text-primary"></i> Số Đôi Giày:</label>
                                <input type="number" id="calc_shoe_qty" class="form-control fw-bold" value="1" min="1" max="20" oninput="updateCalculatedWeight(this.value)">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-dark"><i class="fa-solid fa-weight-scale me-1 text-warning"></i> Trọng Lượng:</label>
                                <div class="input-group">
                                    <input type="number" id="calc_weight_gram" class="form-control fw-bold text-primary" value="<?= $default_weight ?>" step="50" min="100">
                                    <span class="input-group-text small">Gram</span>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success w-100 fw-bold rounded-pill py-3 shadow-sm" onclick="runLiveFeeCalculation()">
                            <i class="fa-solid fa-bolt me-2"></i> TÍNH THỬ CƯỚC NGAY
                        </button>
                    </form>
                </div>
            </div>

            <!-- Khung Hiển Thị Kết Quả So Sánh -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-scale-balanced text-primary me-2"></i>Bảng So Sánh Cước Phí
                    </h5>
                    <p class="text-muted small mb-4">Kết quả đối soát tức thì từ API và CSDL biểu phí 63 tỉnh.</p>

                    <div id="calc_results_container">
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-calculator fa-3x mb-3 text-secondary-subtle"></i>
                            <h6 class="fw-bold">Chưa có dữ liệu tính toán</h6>
                            <p class="small mb-0">Vui lòng chọn thông tin bên trái và bấm <strong>"Tính Thử Cước Ngay"</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL THÊM / SỬA TỈNH THÀNH -->
<div class="modal fade" id="provinceEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="provinceModalTitle"><i class="fa-solid fa-map-pin me-2 text-warning"></i>Chỉnh Sửa Cước Tỉnh Thành</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="provinceEditForm">
                <input type="hidden" name="ajax_save_province" value="1">
                <input type="hidden" name="province_id" id="edit_prov_id" value="0">
                <div class="modal-body p-4 bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tên Tỉnh / Thành Phố (*)</label>
                        <input type="text" name="province_name" id="edit_prov_name" class="form-control fw-bold" placeholder="VD: Cần Thơ, Hà Giang..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Cước Phí Vận Chuyển (VNĐ) (*)</label>
                        <input type="number" name="shipping_fee" id="edit_prov_fee" class="form-control fw-bold text-success" step="1000" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Thời Gian Giao Hàng Ước Tính</label>
                        <input type="text" name="estimated_days" id="edit_prov_days" class="form-control" placeholder="VD: 1-2 ngày, 2-3 ngày...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Trạng Thái Áp Dụng</label>
                        <select name="status" id="edit_prov_status" class="form-select fw-bold">
                            <option value="1">🟢 Đang Áp Dụng</option>
                            <option value="0">🔴 Tạm Khóa</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy Bỏ</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thông Tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toast SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true
});

function selectCarrier(code) {
    document.querySelectorAll('.carrier-card-option').forEach(el => el.classList.remove('active'));
    if (code === 'GHTK') {
        document.getElementById('carrier_ghtk').checked = true;
        document.getElementById('carrier_ghtk').closest('.carrier-card-option').classList.add('active');
    } else if (code === 'GHN') {
        document.getElementById('carrier_ghn').checked = true;
        document.getElementById('carrier_ghn').closest('.carrier-card-option').classList.add('active');
    } else {
        document.getElementById('carrier_local').checked = true;
        document.getElementById('carrier_local').closest('.carrier-card-option').classList.add('active');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // Lưu Cấu Hình API Form
    const form = document.getElementById('carrierSettingsForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveCarrierSettings');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang lưu cấu hình...';

            const formData = new FormData(form);

            fetch('shipping-fees.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH VẬN CHUYỂN';
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Thành Công!', text: data.message, timer: 1800, showConfirmButton: false }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Thông Báo', text: data.message });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> LƯU CẤU HÌNH VẬN CHUYỂN';
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ!' });
            });
        });
    }

    // Form Thêm / Sửa Tỉnh Thành
    const provForm = document.getElementById('provinceEditForm');
    if (provForm) {
        provForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(provForm);
            fetch('shipping-fees.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    const modalEl = document.getElementById('provinceEditModal');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if (modalInst) modalInst.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            });
        });
    }
});

// Kiểm tra kết nối GHTK
function testGHTKConnection() {
    const token = document.getElementById('input_ghtk_token').value.trim();

    Swal.fire({
        title: 'Đang kiểm tra kết nối GHTK...',
        text: 'Gửi yêu cầu xác thực token lên máy chủ GHTK...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const fd = new FormData();
    fd.append('ajax_test_carrier_connection', '1');
    fd.append('carrier', 'GHTK');
    fd.append('token', token);

    fetch('shipping-fees.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Kết Nối Thành Công!', text: data.message, confirmButtonColor: '#16a34a' });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi Kết Nối', text: data.message });
        }
    });
}

// Kiểm tra kết nối GHN
function testGHNConnection() {
    const token = document.getElementById('input_ghn_token').value.trim();
    const shop_id = document.getElementById('input_ghn_shop_id').value.trim();

    Swal.fire({
        title: 'Đang kiểm tra kết nối GHN...',
        text: 'Gửi yêu cầu xác thực token lên máy chủ GHN...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const fd = new FormData();
    fd.append('ajax_test_carrier_connection', '1');
    fd.append('carrier', 'GHN');
    fd.append('token', token);
    fd.append('shop_id', shop_id);

    fetch('shipping-fees.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Kết Nối Thành Công!', text: data.message, confirmButtonColor: '#2563eb' });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi Kết Nối', text: data.message });
        }
    });
}

// Modal Thêm Tỉnh Mới
function openAddProvinceModal() {
    document.getElementById('provinceModalTitle').innerHTML = '<i class="fa-solid fa-plus me-2 text-warning"></i>Thêm Tỉnh / Thành Mới';
    document.getElementById('edit_prov_id').value = '0';
    document.getElementById('edit_prov_name').value = '';
    document.getElementById('edit_prov_fee').value = '30000';
    document.getElementById('edit_prov_days').value = '2-4 ngày';
    document.getElementById('edit_prov_status').value = '1';

    new bootstrap.Modal(document.getElementById('provinceEditModal')).show();
}

// Modal Chỉnh Sửa Tỉnh
function openEditProvinceModal(prov) {
    document.getElementById('provinceModalTitle').innerHTML = '<i class="fa-solid fa-map-pin me-2 text-warning"></i>Chỉnh Sửa: ' + prov.province_name;
    document.getElementById('edit_prov_id').value = prov.id;
    document.getElementById('edit_prov_name').value = prov.province_name;
    document.getElementById('edit_prov_fee').value = prov.shipping_fee;
    document.getElementById('edit_prov_days').value = prov.estimated_days;
    document.getElementById('edit_prov_status').value = prov.status;

    new bootstrap.Modal(document.getElementById('provinceEditModal')).show();
}

// Bật / Tắt Tỉnh
function toggleProvinceStatus(pid) {
    const fd = new FormData();
    fd.append('ajax_toggle_province_status', '1');
    fd.append('province_id', pid);

    fetch('shipping-fees.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.fire({ icon: 'success', title: data.message });
            setTimeout(() => location.reload(), 700);
        }
    });
}

// Xóa Tỉnh
function deleteProvince(pid, pname) {
    Swal.fire({
        title: 'Xóa tỉnh "' + pname + '"?',
        text: 'Bạn có chắc chắn muốn xóa tỉnh này khỏi bảng cước vận chuyển?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Xóa Ngay',
        cancelButtonText: 'Hủy'
    }).then((res) => {
        if (res.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_delete_province', '1');
            fd.append('province_id', pid);

            fetch('shipping-fees.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    setTimeout(() => location.reload(), 700);
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            });
        }
    });
}

// Lọc Tỉnh theo Trạng thái (Đang Áp Dụng / Tạm Khóa)
function filterByStatus(status, btnEl) {
    document.querySelectorAll('.status-filter-chip').forEach(el => el.classList.remove('active'));
    btnEl.classList.add('active');

    const rows = document.querySelectorAll('#provincesTable tbody tr');
    rows.forEach(r => {
        const rStatus = r.getAttribute('data-status');
        if (status === 'all') {
            r.style.display = '';
        } else {
            r.style.display = (rStatus === status) ? '' : 'none';
        }
    });
}

// Tìm kiếm nhanh tên tỉnh
function filterProvincesTable(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#provincesTable tbody tr');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
}

// Cập nhật số gram khi đổi số lượng đôi ở Live Calculator
function updateCalculatedWeight(qty) {
    const q = Math.max(1, parseInt(qty) || 1);
    const weightPerShoe = parseInt(document.querySelector('input[name="default_shoe_weight"]')?.value || 800);
    document.getElementById('calc_weight_gram').value = q * weightPerShoe;
}

// Chạy Live Calculator
function runLiveFeeCalculation() {
    const prov = document.getElementById('calc_province_name').value;
    const weight = document.getElementById('calc_weight_gram').value;

    const container = document.getElementById('calc_results_container');
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <div class="small fw-bold text-muted">Đang kết nối API GHTK & GHN để tính cước...</div>
        </div>
    `;

    const fd = new FormData();
    fd.append('ajax_calculate_test_fee', '1');
    fd.append('province_name', prov);
    fd.append('weight_gram', weight);

    fetch('shipping-fees.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const ghtk = data.carriers.GHTK;
            const ghn = data.carriers.GHN;
            const local = data.local;

            container.innerHTML = `
                <div class="row g-3">
                    <!-- GHTK Result -->
                    <div class="col-12">
                        <div class="calc-card-result p-3 border-success-subtle bg-success-subtle bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-truck-fast text-success fs-5"></i>
                                    <strong class="text-dark fs-6">${ghtk.name}</strong>
                                    <span class="badge bg-success-subtle text-success border border-success small">${ghtk.badge}</span>
                                </div>
                                <div class="text-end">
                                    <div class="fs-4 fw-bold text-success">
                                        ${new Intl.NumberFormat('vi-VN').format(ghtk.fee)}đ
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted d-flex justify-content-between">
                                <span><i class="fa-solid fa-clock me-1 text-warning"></i> Dự kiến: <b>${ghtk.estimated_days}</b></span>
                                <span>Trọng lượng: <b>${weight}g</b></span>
                            </div>
                        </div>
                    </div>

                    <!-- GHN Result -->
                    <div class="col-12">
                        <div class="calc-card-result p-3 border-primary-subtle bg-primary-subtle bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-paper-plane text-primary fs-5"></i>
                                    <strong class="text-dark fs-6">${ghn.name}</strong>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">${ghn.badge}</span>
                                </div>
                                <div class="text-end">
                                    <div class="fs-4 fw-bold text-primary">
                                        ${new Intl.NumberFormat('vi-VN').format(ghn.fee)}đ
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted d-flex justify-content-between">
                                <span><i class="fa-solid fa-clock me-1 text-warning"></i> Dự kiến: <b>${ghn.estimated_days}</b></span>
                                <span>Tốc độ: <b>Hỏa tốc 24h</b></span>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu Phí Nội Bộ -->
                    <div class="col-12">
                        <div class="calc-card-result p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-store text-secondary fs-5"></i>
                                    <strong class="text-dark fs-6">${local.name}</strong>
                                    <span class="badge bg-secondary-subtle text-dark border small">${local.badge}</span>
                                </div>
                                <div class="text-end">
                                    <div class="fs-4 fw-bold text-dark">
                                        ${new Intl.NumberFormat('vi-VN').format(local.fee)}đ
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted d-flex justify-content-between">
                                <span><i class="fa-solid fa-map-location-dot me-1 text-danger"></i> Tỉnh: <b>${prov}</b></span>
                                <span>Dự kiến: <b>${local.estimated_days}</b></span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `<div class="alert alert-danger">${data.message || 'Lỗi tính cước!'}</div>`;
        }
    })
    .catch(err => {
        container.innerHTML = `<div class="alert alert-danger">Lỗi kết nối máy chủ khi tính cước!</div>`;
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
