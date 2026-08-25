<?php 
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ========================================================
// 0. CÁC ENDPOINT LIVE AJAX (100% KHÔNG LOAD TRANG)
// ========================================================

// Helper tính toán thống kê voucher
function getVoucherStats($conn) {
    $stats = ['total' => 0, 'active' => 0, 'hidden' => 0, 'used_total' => 0, 'expired' => 0];
    $stat_res = $conn->query("
        SELECT 
            COUNT(*) as total_vouchers,
            SUM(CASE WHEN status = 1 AND (end_date IS NULL OR end_date >= NOW()) THEN 1 ELSE 0 END) as active_vouchers,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as hidden_vouchers,
            SUM(used_count) as total_used,
            SUM(CASE WHEN end_date IS NOT NULL AND end_date < NOW() THEN 1 ELSE 0 END) as expired_vouchers
        FROM vouchers
    ");
    if ($stat_res && $st = $stat_res->fetch_assoc()) {
        $stats['total'] = intval($st['total_vouchers'] ?? 0);
        $stats['active'] = intval($st['active_vouchers'] ?? 0);
        $stats['hidden'] = intval($st['hidden_vouchers'] ?? 0);
        $stats['used_total'] = intval($st['total_used'] ?? 0);
        $stats['expired'] = intval($st['expired_vouchers'] ?? 0);
    }
    return $stats;
}

// AJAX 0.1: Lưu Voucher (Thêm mới hoặc Chỉnh sửa)
if (isset($_POST['ajax_save_voucher'])) {
    header('Content-Type: application/json; charset=utf-8');

    $voucher_id      = intval($_POST['voucher_id'] ?? 0);
    $code            = strtoupper(trim($_POST['code'] ?? ''));
    $title           = trim($_POST['title'] ?? '');
    $discount_type   = trim($_POST['discount_type'] ?? 'fixed');
    $discount_value  = floatval($_POST['discount_value'] ?? 0);
    $max_discount    = floatval($_POST['max_discount'] ?? 0);
    $min_order_value = floatval($_POST['min_order_value'] ?? 0);
    $usage_limit     = intval($_POST['usage_limit'] ?? 100);
    $per_user_limit  = intval($_POST['per_user_limit'] ?? 1);
    $event_type      = trim($_POST['event_type'] ?? 'general');
    $brand_id        = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $sale_event_id   = !empty($_POST['sale_event_id']) ? intval($_POST['sale_event_id']) : null;
    if ($event_type !== 'holiday') {
        $sale_event_id = null; // Mã chung & người mới không gắn cứng vào sự kiện, áp dụng toàn sàn
    }
    $status          = (isset($_POST['status']) && ($_POST['status'] == '1' || $_POST['status'] === 'on')) ? 1 : 0;

    $start_raw       = trim($_POST['start_date'] ?? '');
    $end_raw         = trim($_POST['end_date'] ?? '');

    $start_date      = !empty($start_raw) ? str_replace('T', ' ', $start_raw) : date('Y-m-d H:i:s');
    if (!empty($end_raw)) {
        $end_date    = str_replace('T', ' ', $end_raw);
        // Tự động bảo vệ: nếu ngày kết thúc <= ngày bắt đầu thì mặc định +30 ngày
        if (strtotime($end_date) <= strtotime($start_date)) {
            $end_date = date('Y-m-d 23:59:59', strtotime($start_date . ' +30 days'));
        }
    } else {
        $end_date    = date('Y-m-d 23:59:59', strtotime($start_date . ' +30 days'));
    }

    if (empty($code) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ Mã Voucher và Tiêu đề!']);
        exit();
    }

    if ($voucher_id > 0) {
        // CẬP NHẬT VOUCHER
        $stmt = $conn->prepare("
            UPDATE vouchers SET 
                code = ?, title = ?, discount_type = ?, discount_value = ?, 
                max_discount = ?, min_order_value = ?, usage_limit = ?, per_user_limit = ?, 
                event_type = ?, brand_id = ?, sale_event_id = ?, start_date = ?, end_date = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssdddiisssssii",
            $code, $title, $discount_type, $discount_value, 
            $max_discount, $min_order_value, $usage_limit, $per_user_limit, 
            $event_type, $brand_id, $sale_event_id, $start_date, $end_date, $status, $voucher_id
        );
        if ($stmt->execute()) {
            $stmt->close();
            $stats = getVoucherStats($conn);
            echo json_encode([
                'success' => true,
                'is_new'  => false,
                'voucher_id' => $voucher_id,
                'message' => 'Đã cập nhật Voucher "' . $code . '" thành công!',
                'stats'   => $stats
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
        }
    } else {
        // THÊM MỚI VOUCHER
        $check = $conn->prepare("SELECT id FROM vouchers WHERE code = ? LIMIT 1");
        $check->bind_param("s", $code);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Mã Voucher "' . $code . '" đã tồn tại, vui lòng chọn mã khác!']);
            exit();
        }
        $check->close();

        $stmt = $conn->prepare("
            INSERT INTO vouchers (code, title, discount_type, discount_value, max_discount, min_order_value, usage_limit, per_user_limit, event_type, brand_id, sale_event_id, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssdddiisssssi",
            $code, $title, $discount_type, $discount_value, 
            $max_discount, $min_order_value, $usage_limit, $per_user_limit, 
            $event_type, $brand_id, $sale_event_id, $start_date, $end_date, $status
        );
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            $stats = getVoucherStats($conn);
            echo json_encode([
                'success' => true,
                'is_new'  => true,
                'voucher_id' => $new_id,
                'message' => 'Đã tạo mới Voucher "' . $code . '" thành công!',
                'stats'   => $stats
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
        }
    }
    exit();
}

// AJAX 0.2: 1-Click Toggle Ẩn / Hiện Voucher
if (isset($_POST['ajax_toggle_voucher_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $vid = intval($_POST['voucher_id'] ?? 0);
    $check = $conn->query("SELECT id, code, status, end_date FROM vouchers WHERE id = $vid LIMIT 1");
    if ($check && $v = $check->fetch_assoc()) {
        $new_status = ($v['status'] == 1) ? 0 : 1;
        $conn->query("UPDATE vouchers SET status = $new_status WHERE id = $vid");
        
        $is_expired = (!empty($v['end_date']) && strtotime($v['end_date']) < time());
        
        $badge_html = '';
        if ($new_status == 1 && !$is_expired) {
            $badge_html = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold" style="cursor: pointer; transition: all 0.2s;" onclick="toggleVoucherStatus('.$vid.', event)" title="Nhấp để ẩn voucher này"><i class="fa-solid fa-eye me-1"></i>Đang hiện</span>';
        } elseif ($new_status == 1 && $is_expired) {
            $badge_html = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-bold" style="cursor: pointer; transition: all 0.2s;" onclick="toggleVoucherStatus('.$vid.', event)" title="Nhấp để chuyển trạng thái"><i class="fa-solid fa-clock-rotate-left me-1"></i>Hết hạn</span>';
        } else {
            $badge_html = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2 fw-bold" style="cursor: pointer; transition: all 0.2s;" onclick="toggleVoucherStatus('.$vid.', event)" title="Nhấp để hiện voucher này"><i class="fa-solid fa-eye-slash me-1"></i>Đang ẩn</span>';
        }
        
        $stats = getVoucherStats($conn);
        echo json_encode([
            'success'    => true,
            'new_status' => $new_status,
            'badge_html' => $badge_html,
            'message'    => $new_status == 1 ? 'Đã hiện Voucher "' . $v['code'] . '" trên trang chủ!' : 'Đã tạm ẩn Voucher "' . $v['code'] . '" khỏi cửa hàng!',
            'stats'      => $stats
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy Voucher!']);
    }
    exit();
}

// AJAX 0.3: Xóa Voucher không load trang
if (isset($_POST['ajax_delete_voucher'])) {
    header('Content-Type: application/json; charset=utf-8');
    $del_id = intval($_POST['delete_voucher_id'] ?? 0);
    if ($del_id > 0) {
        $check = $conn->query("SELECT code FROM vouchers WHERE id = $del_id LIMIT 1");
        $code = ($check && $row = $check->fetch_assoc()) ? $row['code'] : 'Voucher';
        
        if ($conn->query("DELETE FROM vouchers WHERE id = $del_id")) {
            $stats = getVoucherStats($conn);
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa vĩnh viễn Voucher "' . $code . '" khỏi hệ thống!',
                'stats'   => $stats
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID Voucher không hợp lệ!']);
    }
    exit();
}

include_once 'includes/header.php';

// ========================================================
// 1. TRUY VẤN DỮ LIỆU BAN ĐẦU
// ========================================================
$stats = getVoucherStats($conn);

$sql_vouchers = "SELECT v.*, b.name AS brand_name, se.name AS sale_event_name 
                 FROM vouchers v 
                 LEFT JOIN brands b ON v.brand_id = b.id 
                 LEFT JOIN sale_events se ON v.sale_event_id = se.id 
                 ORDER BY v.id DESC";
$vouchers_res = $conn->query($sql_vouchers);

$brands_res = $conn->query("SELECT id, name FROM brands WHERE status=1 ORDER BY name ASC");
$brands_list = [];
if ($brands_res) {
    while ($b = $brands_res->fetch_assoc()) {
        $brands_list[] = $b;
    }
}
?>

<div class="content-wrapper">
    <!-- HEADER TRANG -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
                <i class="fa-solid fa-ticket text-warning me-2"></i>Quản Lý Voucher &amp; Khuyến Mãi
            </h4>
        </div>
        <button type="button" class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm" id="btnOpenAddModal" onclick="openAddVoucherModal()">
            <i class="fa-solid fa-plus me-1"></i> Tạo Voucher Mới
        </button>
    </div>

    <!-- 4 METRIC STATS CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Tổng Số Voucher</span>
                        <h3 class="fw-black mb-0 text-dark mt-1" id="stat_total"><?= number_format($stats['total']) ?></h3>
                    </div>
                    <div class="p-3 rounded-4 bg-primary-subtle text-primary">
                        <i class="fa-solid fa-ticket-simple fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Đang Hiện (Hoạt Động)</span>
                        <h3 class="fw-black mb-0 text-success mt-1" id="stat_active"><?= number_format($stats['active']) ?></h3>
                    </div>
                    <div class="p-3 rounded-4 bg-success-subtle text-success">
                        <i class="fa-solid fa-eye fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Đang Ẩn</span>
                        <h3 class="fw-black mb-0 text-secondary mt-1" id="stat_hidden"><?= number_format($stats['hidden']) ?></h3>
                    </div>
                    <div class="p-3 rounded-4 bg-secondary-subtle text-secondary">
                        <i class="fa-solid fa-eye-slash fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Đã Hết Hạn</span>
                        <h3 class="fw-black mb-0 text-danger mt-1" id="stat_expired"><?= number_format($stats['expired']) ?></h3>
                    </div>
                    <div class="p-3 rounded-4 bg-danger-subtle text-danger">
                        <i class="fa-solid fa-clock-rotate-left fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH VOUCHER & BỘ LỌC ĐA TIÊU CHÍ (LIVE FILTER) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
        
        <!-- TOOLBAR BỘ LỌC ĐA NĂNG (100% LIVE AJAX / CLIENT-SIDE) -->
        <div class="card-header bg-white border-bottom p-3">
            <div class="row g-2 align-items-center">
                <!-- Tìm kiếm -->
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="filterKeyword" class="form-control border-start-0" placeholder="Tìm theo mã, tiêu đề..." oninput="applyLiveFilters()">
                    </div>
                </div>

                <!-- Lọc Trạng Thái -->
                <div class="col-6 col-md-2">
                    <select id="filterStatus" class="form-select form-select-sm fw-bold" onchange="applyLiveFilters()">
                        <option value="all">📁 Tất cả trạng thái</option>
                        <option value="active">✅ Đang hiện</option>
                        <option value="hidden">🔒 Đang ẩn</option>
                        <option value="expired">⌛ Đã hết hạn</option>
                    </select>
                </div>

                <!-- Lọc Hình Thức Giảm -->
                <div class="col-6 col-md-2">
                    <select id="filterDiscountType" class="form-select form-select-sm" onchange="applyLiveFilters()">
                        <option value="all">🏷️ Tất cả hình thức</option>
                        <option value="percent"> % Phần trăm (%)</option>
                        <option value="fixed">💵 Tiền cố định (VNĐ)</option>
                        <option value="freeship">🚚 Miễn phí vận chuyển</option>
                    </select>
                </div>

                <!-- Lọc Loại Sự Kiện -->
                <div class="col-6 col-md-2">
                    <select id="filterEventType" class="form-select form-select-sm" onchange="applyLiveFilters()">
                        <option value="all">🎉 Tất cả sự kiện</option>
                        <option value="general">🏷️ Khuyến mãi chung</option>
                        <option value="new_user">🎁 Khách hàng mới</option>
                        <option value="holiday">🎊 Ngày lễ / Đôi</option>
                    </select>
                </div>

                <!-- Lọc Thương Hiệu -->
                <div class="col-6 col-md-2">
                    <select id="filterBrand" class="form-select form-select-sm" onchange="applyLiveFilters()">
                        <option value="all">🏆 Tất cả hãng</option>
                        <option value="0">Tất cả sản phẩm</option>
                        <?php foreach ($brands_list as $br): ?>
                            <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nút Reset Lọc -->
                <div class="col-12 col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill w-100" onclick="resetLiveFilters()" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="adminVouchersTable">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Mã Voucher</th>
                        <th>Tiêu Đề &amp; Áp Dụng</th>
                        <th>Loại Sự Kiện</th>
                        <th>Mức Giảm</th>
                        <th>Đơn Tối Thiểu</th>
                        <th style="min-width: 130px;">Tiến Độ Sử Dụng</th>
                        <th>Thời Hạn</th>
                        <th>Trạng Thái (1-Click Ẩn/Hiện)</th>
                        <th class="text-end pe-3">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="vouchersTableBody">
                    <?php 
                    $vouchers_data_map = [];
                    if ($vouchers_res && $vouchers_res->num_rows > 0): 
                    ?>
                        <?php while($v = $vouchers_res->fetch_assoc()): ?>
                            <?php 
                            $v_id = (int)$v['id'];
                            $vouchers_data_map[$v_id] = $v;

                            $event_type = $v['event_type'] ?? 'general';
                            $max_discount = floatval($v['max_discount'] ?? 0);
                            $min_order = floatval($v['min_order_value'] ?? 0);
                            $used_cnt = intval($v['used_count'] ?? 0);
                            $limit_cnt = intval($v['usage_limit'] ?? 1);
                            $percent_used = $limit_cnt > 0 ? min(100, round(($used_cnt / $limit_cnt) * 100)) : 0;
                            $is_expired = (!empty($v['end_date']) && strtotime($v['end_date']) < time());
                            
                            $status_flag = $is_expired ? 'expired' : ($v['status'] == 1 ? 'active' : 'hidden');
                            $brand_flag = !empty($v['brand_id']) ? $v['brand_id'] : '0';
                            ?>
                            <tr id="voucher-row-<?= $v['id'] ?>" 
                                data-id="<?= $v['id'] ?>"
                                data-status="<?= $status_flag ?>"
                                data-discount-type="<?= htmlspecialchars($v['discount_type']) ?>"
                                data-event-type="<?= htmlspecialchars($event_type) ?>"
                                data-brand-id="<?= $brand_flag ?>"
                                data-search="<?= strtolower(htmlspecialchars($v['code'] . ' ' . $v['title'])) ?>">
                                
                                <td class="ps-3">
                                    <div class="voucher-code-badge" data-code="<?= htmlspecialchars($v['code']) ?>" title="Bấm để sao chép mã">
                                        <?= htmlspecialchars($v['code']); ?> <i class="fa-regular fa-copy ms-1 opacity-75"></i>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($v['title'] ?? 'Khuyến Mãi'); ?></strong>
                                    <small class="text-muted">
                                        <?= !empty($v['brand_name']) ? 'Thương hiệu: <strong class="text-warning">' . htmlspecialchars($v['brand_name']) . '</strong>' : 'Áp dụng: <strong>Tất cả SP</strong>'; ?>
                                        <?php if (!empty($v['sale_event_name'])): ?>
                                            | Event: <span class="badge bg-secondary-subtle text-dark"><?= htmlspecialchars($v['sale_event_name']) ?></span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($event_type == 'new_user'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold">🎁 Khách Mới</span>
                                    <?php elseif ($event_type == 'holiday'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger fw-bold">🎉 Ngày Lễ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-dark border fw-bold">🏷️ Chung</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['discount_type'] == 'fixed'): ?>
                                        <strong class="text-danger fs-6">-<?= number_format($v['discount_value'], 0, ',', '.'); ?>đ</strong>
                                    <?php elseif ($v['discount_type'] == 'freeship'): ?>
                                        <strong class="text-success fs-6">🚚 Miễn Phí Vận Chuyển</strong>
                                    <?php else: ?>
                                        <strong class="text-danger fs-6">-<?= $v['discount_value']; ?>%</strong>
                                        <?php if ($max_discount > 0): ?>
                                            <small class="d-block text-muted" style="font-size: 11px;">Tối đa <?= number_format($max_discount, 0, ',', '.'); ?>đ</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-secondary">
                                    <?= number_format($min_order, 0, ',', '.'); ?>đ
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between small fw-bold mb-1">
                                        <span><?= number_format($used_cnt) ?> / <?= number_format($limit_cnt) ?></span>
                                        <span class="text-muted"><?= $percent_used ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?= $percent_used >= 90 ? 'bg-danger' : ($percent_used >= 60 ? 'bg-warning' : 'bg-primary') ?>" style="width: <?= $percent_used ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted d-block"><i class="fa-regular fa-calendar-check me-1"></i><?= $v['start_date'] ? date('d/m/Y', strtotime($v['start_date'])) : 'Không hạn'; ?></small>
                                    <small class="d-block <?= $is_expired ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <i class="fa-regular fa-calendar-xmark me-1"></i><?= $v['end_date'] ? date('d/m/Y', strtotime($v['end_date'])) : 'Vĩnh viễn'; ?>
                                        <?= $is_expired ? '(Hết hạn)' : '' ?>
                                    </small>
                                </td>
                                <td id="status-cell-<?= $v['id'] ?>">
                                    <?php if ($v['status'] == 1 && !$is_expired): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold" style="cursor: pointer; transition: all 0.2s;" onclick="toggleVoucherStatus(<?= $v['id'] ?>, event)" title="Nhấp để ẩn voucher này">
                                            <i class="fa-solid fa-eye me-1"></i>Đang hiện
                                        </span>
                                    <?php elseif ($is_expired): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-bold" style="cursor: pointer; transition: all 0.2s;" onclick="toggleVoucherStatus(<?= $v['id'] ?>, event)" title="Nhấp để chuyển trạng thái">
                                            <i class="fa-solid fa-clock-rotate-left me-1"></i>Hết hạn
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2 fw-bold" style="cursor: pointer; transition: all 0.2s;" onclick="toggleVoucherStatus(<?= $v['id'] ?>, event)" title="Nhấp để hiện voucher này">
                                            <i class="fa-solid fa-eye-slash me-1"></i>Đang ẩn
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill me-1 btn-edit-voucher" onclick="openEditVoucherModalById(<?= $v['id'] ?>)">
                                        <i class="fa-solid fa-pen"></i> Sửa
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="ajaxDeleteVoucher(<?= $v['id']; ?>, '<?= addslashes(htmlspecialchars($v['code'])); ?>')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr id="noVoucherRow"><td colspan="9" class="text-center py-5 text-muted">Chưa có mã Voucher nào trong CSDL.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL THÊM / SỬA VOUCHER VỚI LIVE REAL-TIME PREVIEW & AJAX SUBMIT -->
<div class="modal fade" id="voucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-ticket text-warning fs-4"></i>
                    <h5 class="modal-title fw-bold text-white mb-0" id="vModalTitle">Tạo Voucher Khuyến Mãi Mới</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="voucherAjaxForm">
                <input type="hidden" name="ajax_save_voucher" value="1">
                <input type="hidden" name="voucher_id" id="form_voucher_id" value="0">
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        <!-- Left Form Column -->
                        <div class="col-12 col-lg-7">
                            <div class="bg-white p-4 rounded-4 shadow-sm border">
                                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-sliders me-2 text-warning"></i>Thông Tin Cấu Hình</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label fw-bold small">Mã Voucher (Code) *</label>
                                        <input type="text" name="code" id="form_code" class="form-control fw-bold text-uppercase font-monospace" placeholder="VD: SALE50K" required oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <label class="form-label fw-bold small">Tiêu Đề Voucher *</label>
                                        <input type="text" name="title" id="form_title" class="form-control fw-bold" placeholder="VD: Giảm 50k cho đơn từ 500k" required oninput="updateLivePreview()">
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold small">Loại Sự Kiện *</label>
                                        <select name="event_type" id="form_event_type" class="form-select fw-bold" onchange="toggleEventTypeRules(); updateLivePreview();">
                                            <option value="general">🏷️ Khuyến Mãi Chung (Áp dụng tất cả)</option>
                                            <option value="holiday">🎉 Ngày Lễ / Đôi (Chỉ gắn vào Sự Kiện)</option>
                                            <option value="new_user">🎁 Khách Hàng Mới (Chỉ tài khoản mới)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold small">Hình Thức Giảm *</label>
                                        <select name="discount_type" id="form_discount_type" class="form-select fw-bold" onchange="togglePercentCap(); updateLivePreview();">
                                            <option value="fixed">💵 Tiền Cố Định (VNĐ)</option>
                                            <option value="percent"> % Phần Trăm (%)</option>
                                            <option value="freeship">🚚 Miễn Phí Vận Chuyển</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold small">Áp Dụng Brand</label>
                                        <select name="brand_id" id="form_brand_id" class="form-select fw-bold" onchange="updateLivePreview()">
                                            <option value="">Tất cả sản phẩm</option>
                                            <?php foreach ($brands_list as $br): ?>
                                                <option value="<?= $br['id']; ?>"><?= htmlspecialchars($br['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold small">Mức Giảm *</label>
                                        <input type="number" step="0.1" name="discount_value" id="form_discount_value" class="form-control fw-bold text-danger" placeholder="50000 hoặc 10" required oninput="updateLivePreview()">
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold small">Đơn Tối Thiểu (VNĐ)</label>
                                        <input type="number" name="min_order_value" id="form_min_order_value" class="form-control fw-bold" value="0" oninput="updateLivePreview()">
                                    </div>

                                    <div class="col-12 col-md-4" id="max_discount_box" style="display: none;">
                                        <label class="form-label fw-bold small">Giảm Tối Đa (VNĐ)</label>
                                        <input type="number" name="max_discount" id="form_max_discount" class="form-control" value="0" oninput="updateLivePreview()">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small">Tổng Số Lượt Dùng *</label>
                                        <input type="number" name="usage_limit" id="form_usage_limit" class="form-control fw-bold" value="100" required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small">Giới Hạn / 1 Khách *</label>
                                        <input type="number" name="per_user_limit" id="form_per_user_limit" class="form-control fw-bold" value="1" required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small">Ngày Bắt Đầu</label>
                                        <input type="datetime-local" name="start_date" id="form_start_date" class="form-control" onchange="validateDates(); updateLivePreview();">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small">Ngày Kết Thúc</label>
                                        <input type="datetime-local" name="end_date" id="form_end_date" class="form-control" onchange="validateDates(); updateLivePreview();">
                                    </div>

                                    <div class="col-12" id="box_sale_event_id" style="display: none;">
                                        <label class="form-label fw-bold small text-danger" id="lbl_sale_event_id"><i class="fa-solid fa-calendar-star me-1"></i>Gắn vào Sự Kiện Sale *</label>
                                        <select name="sale_event_id" id="form_sale_event_id" class="form-select" onchange="updateLivePreview()">
                                            <option value="">-- Chọn sự kiện sale áp dụng --</option>
                                            <?php
                                             $se_opt = $conn->query("SELECT id, name FROM sale_events WHERE status=1 ORDER BY sort_order ASC");
                                             if ($se_opt) while ($se = $se_opt->fetch_assoc()): ?>
                                             <option value="<?= $se['id'] ?>"><?= htmlspecialchars($se['name']) ?></option>
                                             <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-text small" id="event_type_helper_text">
                                            <span class="text-success fw-bold">🏷️ Khuyến mãi chung:</span> Voucher hiển thị trên trang chủ và áp dụng được cho tất cả sản phẩm.
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="status" id="form_status" value="1" checked>
                                            <label class="form-check-label fw-bold text-success" for="form_status">Kích hoạt voucher ngay sau khi lưu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: LIVE REAL-TIME PREVIEW -->
                        <div class="col-12 col-lg-5">
                            <div class="bg-white p-4 rounded-4 shadow-sm border h-100 d-flex flex-column">
                                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                    <i class="fa-solid fa-eye me-2 text-primary"></i>Xem Trước Trực Quan (Live Preview)
                                </h6>
                                <p class="text-muted small mb-3">Thẻ vé này sẽ hiển thị đúng như khách hàng nhìn thấy trên trang chủ &amp; modal:</p>

                                <div class="my-auto">
                                    <!-- Light Preview Ticket -->
                                    <span class="small fw-bold text-muted d-block mb-1">Giao diện sáng (Checkout / Profile):</span>
                                    <div id="livePreviewTicketLight" class="voucher-ticket voucher-theme-gold mb-3">
                                        <div class="voucher-ticket-stub">
                                            <i class="fa-solid fa-percent voucher-stub-icon" id="previewLightIcon"></i>
                                            <div class="voucher-stub-value" id="previewLightVal">10%</div>
                                            <div class="voucher-stub-label" id="previewLightLabel">GIẢM GIÁ</div>
                                        </div>
                                        <div class="voucher-ticket-divider">
                                            <div class="voucher-notch voucher-notch-top" style="background: #ffffff;"></div>
                                            <div class="voucher-notch voucher-notch-bottom" style="background: #ffffff;"></div>
                                        </div>
                                        <div class="voucher-ticket-body">
                                            <div class="voucher-info-wrapper">
                                                <span class="voucher-badge-type" id="previewLightBadge">Giảm giá</span>
                                                <h6 class="voucher-title text-dark mb-1" id="previewLightTitle">Tiêu Đề Ưu Đãi</h6>
                                                <div class="voucher-conditions text-muted small" id="previewLightCond">Đơn từ: 0đ • HSD: Không thời hạn</div>
                                            </div>
                                            <div class="voucher-action-area">
                                                <div class="voucher-code-badge" id="previewLightCode">VOUCHER</div>
                                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold" style="font-size: 11px;">Lưu Mã</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dark Preview Ticket -->
                                    <span class="small fw-bold text-muted d-block mb-1">Giao diện tối (Trang chủ / Sự kiện):</span>
                                    <div id="livePreviewTicketDark" class="voucher-ticket dark-theme voucher-theme-gold mb-2">
                                        <div class="voucher-ticket-stub">
                                            <i class="fa-solid fa-percent voucher-stub-icon" id="previewDarkIcon"></i>
                                            <div class="voucher-stub-value" id="previewDarkVal">10%</div>
                                            <div class="voucher-stub-label" id="previewDarkLabel">GIẢM GIÁ</div>
                                        </div>
                                        <div class="voucher-ticket-divider">
                                            <div class="voucher-notch voucher-notch-top" style="background: #1e293b;"></div>
                                            <div class="voucher-notch voucher-notch-bottom" style="background: #1e293b;"></div>
                                        </div>
                                        <div class="voucher-ticket-body">
                                            <div class="voucher-info-wrapper">
                                                <span class="voucher-badge-type" id="previewDarkBadge">Giảm giá</span>
                                                <h6 class="voucher-title text-white mb-1" id="previewDarkTitle">Tiêu Đề Ưu Đãi</h6>
                                                <div class="voucher-conditions text-white-50 small" id="previewDarkCond">Đơn từ: 0đ • HSD: Không thời hạn</div>
                                            </div>
                                            <div class="voucher-action-area">
                                                <div class="voucher-code-badge" id="previewDarkCode">VOUCHER</div>
                                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold" style="font-size: 11px;">Lưu Mã</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-top text-center">
                                    <small class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Tự động đồng bộ chuẩn mẫu trên trang chủ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" id="btnSubmitVoucher" class="btn btn-warning rounded-pill px-5 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> LƯU VOUCHER
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Khởi tạo bản đồ dữ liệu voucher an toàn
window.vouchersData = <?= json_encode($vouchers_data_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || {};

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

function getVoucherModal() {
    const modalEl = document.getElementById('voucherModal');
    if (!modalEl) return null;
    return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
}

// ========================================================
// 1. BỘ LỌC ĐA TIÊU CHÍ CLIENT-SIDE (LIVE FILTER ZERO RELOAD)
// ========================================================
function applyLiveFilters() {
    const kw = document.getElementById('filterKeyword').value.toLowerCase().trim();
    const status = document.getElementById('filterStatus').value;
    const discType = document.getElementById('filterDiscountType').value;
    const evtType = document.getElementById('filterEventType').value;
    const brand = document.getElementById('filterBrand').value;

    const rows = document.querySelectorAll('#vouchersTableBody tr');
    let visibleCount = 0;

    rows.forEach(r => {
        if (r.id === 'noVoucherRow') return;

        const rowSearch = r.getAttribute('data-search') || '';
        const rowStatus = r.getAttribute('data-status') || '';
        const rowDiscType = r.getAttribute('data-discount-type') || '';
        const rowEvtType = r.getAttribute('data-event-type') || '';
        const rowBrand = r.getAttribute('data-brand-id') || '0';

        const matchKw = !kw || rowSearch.includes(kw);
        const matchStatus = (status === 'all') || (rowStatus === status);
        const matchDisc = (discType === 'all') || (rowDiscType === discType);
        const matchEvt = (evtType === 'all') || (rowEvtType === evtType);
        const matchBrand = (brand === 'all') || (rowBrand === brand);

        if (matchKw && matchStatus && matchDisc && matchEvt && matchBrand) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });
}

function resetLiveFilters() {
    document.getElementById('filterKeyword').value = '';
    document.getElementById('filterStatus').value = 'all';
    document.getElementById('filterDiscountType').value = 'all';
    document.getElementById('filterEventType').value = 'all';
    document.getElementById('filterBrand').value = 'all';
    applyLiveFilters();
}

// ========================================================
// 2. 1-CLICK LIVE AJAX TOGGLE STATUS
// ========================================================
function toggleVoucherStatus(vId, event) {
    if (event) event.stopPropagation();
    
    const cell = document.getElementById('status-cell-' + vId);
    const row = document.getElementById('voucher-row-' + vId);
    if (!cell) return;
    
    cell.style.opacity = '0.5';
    cell.style.pointerEvents = 'none';

    const formData = new FormData();
    formData.append('ajax_toggle_voucher_status', '1');
    formData.append('voucher_id', vId);

    fetch('vouchers.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        cell.style.opacity = '1';
        cell.style.pointerEvents = 'auto';

        if (data.success) {
            cell.innerHTML = data.badge_html;
            if (row) {
                row.setAttribute('data-status', data.new_status == 1 ? 'active' : 'hidden');
            }
            if (window.vouchersData && window.vouchersData[vId]) {
                window.vouchersData[vId].status = data.new_status;
            }
            if (data.stats) {
                updateStatsCards(data.stats);
            }
            Toast.fire({ icon: 'success', title: data.message });
            applyLiveFilters();
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    })
    .catch(err => {
        cell.style.opacity = '1';
        cell.style.pointerEvents = 'auto';
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
    });
}

// ========================================================
// 3. XÓA VOUCHER QUA LIVE AJAX POPUP (ZERO RELOAD)
// ========================================================
function ajaxDeleteVoucher(vId, vCode) {
    Swal.fire({
        title: 'Xác nhận xóa Voucher?',
        html: `Bạn có chắc chắn muốn xóa vĩnh viễn voucher <strong class="text-danger font-monospace fs-5">${vCode}</strong> khỏi CSDL?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Vĩnh Viễn',
        cancelButtonText: 'Hủy bỏ',
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_voucher', '1');
            formData.append('delete_voucher_id', vId);

            fetch('vouchers.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('voucher-row-' + vId);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            row.remove();
                            delete window.vouchersData[vId];
                            applyLiveFilters();
                        }, 400);
                    }
                    if (data.stats) {
                        updateStatsCards(data.stats);
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
            });
        }
    });
}

// Cập nhật 4 thẻ Metric Stats
function updateStatsCards(stats) {
    if (!stats) return;
    if (document.getElementById('stat_total')) document.getElementById('stat_total').innerText = Number(stats.total).toLocaleString('vi-VN');
    if (document.getElementById('stat_active')) document.getElementById('stat_active').innerText = Number(stats.active).toLocaleString('vi-VN');
    if (document.getElementById('stat_hidden')) document.getElementById('stat_hidden').innerText = Number(stats.hidden).toLocaleString('vi-VN');
    if (document.getElementById('stat_expired')) document.getElementById('stat_expired').innerText = Number(stats.expired).toLocaleString('vi-VN');
}

function togglePercentCap() {
    var type = document.getElementById('form_discount_type').value;
    document.getElementById('max_discount_box').style.display = (type === 'percent') ? 'block' : 'none';
}

function validateDates() {
    var startInput = document.getElementById('form_start_date');
    var endInput = document.getElementById('form_end_date');
    if (startInput.value && endInput.value) {
        var s = new Date(startInput.value);
        var e = new Date(endInput.value);
        if (e <= s) {
            var autoEnd = new Date(s.getTime() + (30 * 24 * 60 * 60 * 1000));
            autoEnd.setHours(23, 59, 59, 0);
            endInput.value = new Date(autoEnd.getTime() - (autoEnd.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        }
    }
}

function updateLivePreview() {
    var code = document.getElementById('form_code').value.toUpperCase().trim() || 'VOUCHER';
    var title = document.getElementById('form_title').value.trim() || 'Tiêu Đề Ưu Đãi';
    var type = document.getElementById('form_discount_type').value;
    var val = parseFloat(document.getElementById('form_discount_value').value) || 0;
    var minOrder = parseFloat(document.getElementById('form_min_order_value').value) || 0;
    var maxDisc = parseFloat(document.getElementById('form_max_discount').value) || 0;
    var endDate = document.getElementById('form_end_date').value;

    var themeClass = 'voucher-theme-gold';
    var iconClass = 'fa-solid fa-percent';
    var stubVal = '0%';
    var stubLabel = 'GIẢM GIÁ';
    var badgeText = 'Giảm giá';

    if (type === 'freeship') {
        themeClass = 'voucher-theme-emerald';
        iconClass = 'fa-solid fa-truck-fast';
        stubVal = 'FREE';
        stubLabel = 'SHIP';
        badgeText = 'Miễn phí vận chuyển';
    } else if (type === 'percent') {
        themeClass = 'voucher-theme-gold';
        iconClass = 'fa-solid fa-percent';
        stubVal = val + '%';
        stubLabel = 'GIẢM GIÁ';
        badgeText = 'Giảm ' + val + '%';
        if (maxDisc > 0) badgeText += ' (Max ' + new Intl.NumberFormat('vi-VN').format(maxDisc) + 'đ)';
    } else {
        themeClass = 'voucher-theme-crimson';
        iconClass = 'fa-solid fa-tag';
        stubVal = (val >= 1000) ? (Math.round(val / 1000) + 'K') : new Intl.NumberFormat('vi-VN').format(val) + 'đ';
        stubLabel = 'GIẢM TIỀN';
        badgeText = 'Giảm ' + new Intl.NumberFormat('vi-VN').format(val) + 'đ';
    }

    var condText = 'Đơn từ: ' + new Intl.NumberFormat('vi-VN').format(minOrder) + 'đ';
    if (endDate) {
        var d = new Date(endDate);
        condText += ' • HSD: ' + d.toLocaleDateString('vi-VN');
    } else {
        condText += ' • HSD: Không thời hạn';
    }

    ['Light', 'Dark'].forEach(function(target) {
        var ticket = document.getElementById('livePreviewTicket' + target);
        if (ticket) {
            ticket.className = 'voucher-ticket ' + (target === 'Dark' ? 'dark-theme ' : '') + themeClass + ' mb-3';
            document.getElementById('preview' + target + 'Icon').className = iconClass + ' voucher-stub-icon';
            document.getElementById('preview' + target + 'Val').innerText = stubVal;
            document.getElementById('preview' + target + 'Label').innerText = stubLabel;
            document.getElementById('preview' + target + 'Badge').innerText = badgeText;
            document.getElementById('preview' + target + 'Title').innerText = title;
            document.getElementById('preview' + target + 'Cond').innerText = condText;
            document.getElementById('preview' + target + 'Code').innerText = code;
        }
    });
}

function toggleEventTypeRules() {
    var evtType = document.getElementById('form_event_type').value;
    var saleBox = document.getElementById('box_sale_event_id');
    var saleSelect = document.getElementById('form_sale_event_id');
    var helperText = document.getElementById('event_type_helper_text');

    if (evtType === 'holiday') {
        if (saleBox) saleBox.style.display = 'block';
        if (helperText) {
            helperText.innerHTML = '🎉 <strong class="text-danger">Ngày lễ / Sự kiện:</strong> Voucher chỉ hiển thị trong trang Sự kiện và chỉ áp dụng cho sản phẩm trong sự kiện tương ứng.';
        }
    } else if (evtType === 'new_user') {
        if (saleBox) saleBox.style.display = 'none';
        if (saleSelect) saleSelect.value = '';
        if (helperText) {
            helperText.innerHTML = '🎁 <strong class="text-primary">Khách hàng mới:</strong> Voucher chỉ áp dụng cho tài khoản mới tạo (chưa có đơn hàng nào).';
        }
    } else {
        if (saleBox) saleBox.style.display = 'none';
        if (saleSelect) saleSelect.value = '';
        if (helperText) {
            helperText.innerHTML = '🏷️ <strong class="text-success">Khuyến mãi chung:</strong> Voucher hiển thị trên trang chủ và áp dụng được cho tất cả sản phẩm.';
        }
    }
}

function openAddVoucherModal() {
    document.getElementById('vModalTitle').innerText = 'Tạo Voucher Khuyến Mãi Mới';
    document.getElementById('form_voucher_id').value = '0';
    document.getElementById('form_code').value = '';
    document.getElementById('form_title').value = '';
    document.getElementById('form_event_type').value = 'general';
    document.getElementById('form_discount_type').value = 'fixed';
    document.getElementById('form_brand_id').value = '';
    document.getElementById('form_discount_value').value = '';
    document.getElementById('form_min_order_value').value = '0';
    document.getElementById('form_max_discount').value = '0';
    document.getElementById('form_usage_limit').value = '100';
    document.getElementById('form_per_user_limit').value = '1';
    
    var now = new Date();
    var localStart = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    var futureDate = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));
    futureDate.setHours(23, 59, 59, 0);
    var localEnd = new Date(futureDate.getTime() - (futureDate.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

    document.getElementById('form_start_date').value = localStart;
    document.getElementById('form_end_date').value = localEnd;
    document.getElementById('form_status').checked = true;
    document.getElementById('form_sale_event_id').value = '';

    togglePercentCap();
    toggleEventTypeRules();
    updateLivePreview();

    const modal = getVoucherModal();
    if (modal) modal.show();
}

function openEditVoucherModalById(vId) {
    const v = window.vouchersData[vId];
    if (!v) {
        console.error("Voucher not found in map:", vId);
        return;
    }

    document.getElementById('vModalTitle').innerText = 'Chỉnh Sửa Voucher: ' + v.code;
    document.getElementById('form_voucher_id').value = v.id;
    document.getElementById('form_code').value = v.code;
    document.getElementById('form_title').value = v.title || '';
    document.getElementById('form_event_type').value = v.event_type || 'general';
    document.getElementById('form_discount_type').value = v.discount_type || 'fixed';
    document.getElementById('form_brand_id').value = v.brand_id || '';
    document.getElementById('form_discount_value').value = v.discount_value || 0;
    document.getElementById('form_min_order_value').value = v.min_order_value || 0;
    document.getElementById('form_max_discount').value = v.max_discount || 0;
    document.getElementById('form_usage_limit').value = v.usage_limit || 100;
    document.getElementById('form_per_user_limit').value = v.per_user_limit || 1;
    document.getElementById('form_start_date').value = v.start_date ? v.start_date.replace(' ', 'T').slice(0, 16) : '';
    document.getElementById('form_end_date').value = v.end_date ? v.end_date.replace(' ', 'T').slice(0, 16) : '';
    document.getElementById('form_status').checked = (v.status == 1);
    document.getElementById('form_sale_event_id').value = v.sale_event_id || '';

    togglePercentCap();
    toggleEventTypeRules();
    updateLivePreview();

    const modal = getVoucherModal();
    if (modal) modal.show();
}

// ========================================================
// 4. SUBMIT FORM QUA LIVE AJAX (ZERO RELOAD)
// ========================================================
(function initVouchersPage() {
    const form = document.getElementById('voucherAjaxForm');
    if (form && !form.dataset.boundSubmit) {
        form.dataset.boundSubmit = 'true';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitVoucher');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(form);

            fetch('vouchers.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU VOUCHER';
                }

                if (data.success) {
                    const modal = getVoucherModal();
                    if (modal) modal.hide();
                    
                    Toast.fire({ icon: 'success', title: data.message });
                    
                    // Nạp lại dữ liệu bảng và vouchersData qua fetch HTML
                    fetch('vouchers.php')
                    .then(r => r.text())
                    .then(htmlText => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlText, 'text/html');
                        const newTbody = doc.getElementById('vouchersTableBody');
                        if (newTbody) {
                            document.getElementById('vouchersTableBody').innerHTML = newTbody.innerHTML;
                            applyLiveFilters();
                        }
                        // Update window.vouchersData from new script block
                        const scripts = doc.querySelectorAll('script');
                        scripts.forEach(s => {
                            if (s.textContent.includes('window.vouchersData =')) {
                                try {
                                    eval(s.textContent);
                                } catch(e) {}
                            }
                        });
                        if (data.stats) {
                            updateStatsCards(data.stats);
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU VOUCHER';
                }
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
            });
        });
    }
})();
</script>

<?php include_once 'includes/footer.php'; ?>