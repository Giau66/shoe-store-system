<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

// Bỏ dấu tiếng Việt chuẩn xác
function unaccent_vietnamese($str) {
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D' => 'Đ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
    ];
    foreach ($unicode as $nonUnicode => $uni) {
        $str = preg_replace("/($uni)/u", $nonUnicode, $str);
    }
    return $str;
}

// Tạo slug độc nhất (Unique Slug) - Không bao giờ bị Duplicate Entry
function generateUniqueEventSlug($conn, $name, $requestedSlug = '', $eventId = 0) {
    $base = trim($requestedSlug);
    if (empty($base)) {
        $base = trim($name);
    }
    $clean = unaccent_vietnamese($base);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $clean), '-'));
    if (empty($slug)) {
        $slug = 'event-' . time();
    }
    
    $checkSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM sale_events WHERE slug = ? AND id != ? LIMIT 1");
        $stmt->bind_param("si", $checkSlug, $eventId);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res && $res->num_rows > 0);
        $stmt->close();
        
        if (!$exists) {
            return $checkSlug;
        }
        $counter++;
        $checkSlug = $slug . '-' . $counter;
    }
}

// ============================================================
// AJAX ENDPOINTS (100% KHÔNG LOAD TRANG)
// ============================================================

// 1. AJAX Lưu Sự Kiện (Thêm mới hoặc Cập nhật)
if (isset($_POST['ajax_save_event'])) {
    header('Content-Type: application/json; charset=utf-8');

    $event_id    = intval($_POST['event_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $raw_slug    = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $banner_img  = trim($_POST['banner_image'] ?? '');
    $hero_img    = trim($_POST['hero_banner_image'] ?? '');
    $hero_title  = trim($_POST['hero_banner_title'] ?? '');
    $hero_sub    = trim($_POST['hero_banner_subtitle'] ?? '');
    $show_menu   = (isset($_POST['show_on_menu']) && ($_POST['show_on_menu'] == '1' || $_POST['show_on_menu'] === 'on')) ? 1 : 0;
    $show_banner = (isset($_POST['show_on_homepage_banner']) && ($_POST['show_on_homepage_banner'] == '1' || $_POST['show_on_homepage_banner'] === 'on')) ? 1 : 0;
    $color       = trim($_POST['color_theme'] ?? '#ef4444');
    $icon        = trim($_POST['icon'] ?? 'fa-solid fa-fire');
    $icon_img    = trim($_POST['icon_image'] ?? '');
    $start_raw   = trim($_POST['start_date'] ?? '');
    $end_raw     = trim($_POST['end_date'] ?? '');
    $sort_order  = intval($_POST['sort_order'] ?? 0);
    $status      = (isset($_POST['status']) && ($_POST['status'] == '1' || $_POST['status'] === 'on')) ? 1 : 0;

    $start_date  = !empty($start_raw) ? str_replace('T', ' ', $start_raw) : date('Y-m-d H:i:s');
    if (!empty($end_raw)) {
        $end_date = str_replace('T', ' ', $end_raw);
        if (strtotime($end_date) <= strtotime($start_date)) {
            $end_date = date('Y-m-d 23:59:59', strtotime($start_date . ' +30 days'));
        }
    } else {
        $end_date = date('Y-m-d 23:59:59', strtotime($start_date . ' +30 days'));
    }

    // XỬ LÝ UPLOAD FILE ẢNH BANNER & ICON
    $upload_dir = __DIR__ . "/../uploads/banners/";
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['banner_image_file']) && $_FILES['banner_image_file']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['banner_image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (in_array($file_ext, $allowed)) {
            $new_name = 'event_banner_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['banner_image_file']['tmp_name'], $upload_dir . $new_name)) {
                $banner_img = 'uploads/banners/' . $new_name;
            }
        }
    }

    if (isset($_FILES['icon_image_file']) && $_FILES['icon_image_file']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['icon_image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (in_array($file_ext, $allowed)) {
            $new_name = 'event_icon_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['icon_image_file']['tmp_name'], $upload_dir . $new_name)) {
                $icon_img = 'uploads/banners/' . $new_name;
            }
        }
    }

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Tên sự kiện!']);
        exit();
    }

    // Tự động sinh Unique Slug chống trùng lặp
    $slug = generateUniqueEventSlug($conn, $name, $raw_slug, $event_id);

    try {
        if ($event_id > 0) {
            // CẬP NHẬT SỰ KIỆN
            $stmt = $conn->prepare("
                UPDATE sale_events SET 
                    name = ?, slug = ?, description = ?, banner_image = ?,
                    hero_banner_image = ?, hero_banner_title = ?, hero_banner_subtitle = ?,
                    show_on_menu = ?, show_on_homepage_banner = ?, color_theme = ?, 
                    icon = ?, icon_image = ?, start_date = ?, end_date = ?, sort_order = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssssssiisssssiii",
                $name, $slug, $description, $banner_img,
                $hero_img, $hero_title, $hero_sub,
                $show_menu, $show_banner, $color,
                $icon, $icon_img, $start_date, $end_date, $sort_order, $status, $event_id
            );
            if ($stmt->execute()) {
                $stmt->close();
                echo json_encode([
                    'success' => true,
                    'is_new'  => false,
                    'event_id' => $event_id,
                    'slug'    => $slug,
                    'name'    => $name,
                    'message' => 'Đã cập nhật sự kiện "' . $name . '" thành công!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
            }
        } else {
            // TẠO MỚI SỰ KIỆN
            $stmt = $conn->prepare("
                INSERT INTO sale_events (name, slug, description, banner_image, hero_banner_image, hero_banner_title, hero_banner_subtitle, show_on_menu, show_on_homepage_banner, color_theme, icon, icon_image, start_date, end_date, sort_order, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssssiisssssii",
                $name, $slug, $description, $banner_img,
                $hero_img, $hero_title, $hero_sub,
                $show_menu, $show_banner, $color,
                $icon, $icon_img, $start_date, $end_date, $sort_order, $status
            );
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $stmt->close();
                echo json_encode([
                    'success' => true,
                    'is_new'  => true,
                    'event_id' => $new_id,
                    'slug'    => $slug,
                    'name'    => $name,
                    'message' => 'Đã tạo sự kiện mới "' . $name . '" thành công!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
            }
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    exit();
}

// 2. AJAX 1-Click Bật / Tắt Trạng Thái Sự Kiện
if (isset($_POST['ajax_toggle_event'])) {
    header('Content-Type: application/json; charset=utf-8');
    $eid = intval($_POST['event_id'] ?? 0);
    $check = $conn->query("SELECT id, name, status, start_date, end_date FROM sale_events WHERE id = $eid LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $new_status = ($row['status'] == 1) ? 0 : 1;
        $conn->query("UPDATE sale_events SET status = $new_status WHERE id = $eid");
        
        $now = time();
        $st = strtotime($row['start_date']);
        $en = strtotime($row['end_date']);
        
        $status_class = ($new_status && $now >= $st && $now <= $en) ? 'success' : (($new_status && $now < $st) ? 'info' : 'secondary');
        $status_lbl   = ($new_status && $now >= $st && $now <= $en) ? 'Đang diễn ra' : (($new_status && $now < $st) ? 'Sắp diễn ra' : 'Đang ẩn');

        echo json_encode([
            'success'      => true,
            'new_status'   => $new_status,
            'status_class' => $status_class,
            'status_lbl'   => $status_lbl,
            'message'      => $new_status == 1 ? 'Đã kích hoạt sự kiện "' . $row['name'] . '"!' : 'Đã tạm dừng sự kiện "' . $row['name'] . '"!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện!']);
    }
    exit();
}

// 3. AJAX Xóa Sự Kiện
if (isset($_POST['ajax_delete_event'])) {
    header('Content-Type: application/json; charset=utf-8');
    $del_id = intval($_POST['delete_id'] ?? 0);
    if ($del_id > 0) {
        $check = $conn->query("SELECT name FROM sale_events WHERE id = $del_id LIMIT 1");
        $name = ($check && $r = $check->fetch_assoc()) ? $r['name'] : 'Sự kiện';
        
        $conn->query("DELETE FROM event_products WHERE event_id = $del_id");
        $conn->query("UPDATE vouchers SET sale_event_id = NULL WHERE sale_event_id = $del_id");
        $conn->query("DELETE FROM sale_events WHERE id = $del_id");

        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sự kiện "' . $name . '" thành công!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ!']);
    }
    exit();
}

// 4. AJAX Thêm Sản Phẩm Vào Sự Kiện
if (isset($_POST['ajax_add_product'])) {
    header('Content-Type: application/json; charset=utf-8');
    $eid_ref    = intval($_POST['event_id_ref'] ?? 0);
    $prod_id    = intval($_POST['product_id'] ?? 0);
    $disc_pct   = floatval($_POST['discount_percent'] ?? 0);
    $sale_price = floatval($_POST['sale_price'] ?? 0);

    if ($eid_ref > 0 && $prod_id > 0) {
        $p_check = $conn->query("SELECT price, name FROM products WHERE id = $prod_id LIMIT 1");
        $p_row = ($p_check) ? $p_check->fetch_assoc() : null;
        $base_price = $p_row ? floatval($p_row['price']) : 0;
        $p_name = $p_row ? $p_row['name'] : 'Sản phẩm';

        // Tự động tính giá bán sự kiện nếu nhập % giảm
        if ($disc_pct > 0 && ($sale_price <= 0 || empty($_POST['sale_price'])) && $base_price > 0) {
            $sale_price = round($base_price * (1 - ($disc_pct / 100)));
        } elseif ($sale_price > 0 && $disc_pct <= 0 && $base_price > 0) {
            $disc_pct = max(0, min(100, round((1 - ($sale_price / $base_price)) * 100)));
        } elseif ($sale_price <= 0 && $disc_pct <= 0) {
            $sale_price = $base_price;
            $disc_pct = 0;
        }

        $sp_val = $sale_price;

        $conn->query("INSERT INTO event_products (event_id, product_id, sale_price, discount_percent, event_price)
                      VALUES ($eid_ref, $prod_id, $sp_val, $disc_pct, $sp_val)
                      ON DUPLICATE KEY UPDATE sale_price = $sp_val, discount_percent = $disc_pct, event_price = $sp_val");

        echo json_encode([
            'success' => true, 
            'message' => 'Đã thêm "' . $p_name . '" vào sự kiện! Giá bán sự kiện: ' . number_format($sp_val, 0, ',', '.') . 'đ (-' . $disc_pct . '%)'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm hợp lệ!']);
    }
    exit();
}

// 5. AJAX Xóa Sản Phẩm Khỏi Sự Kiện
if (isset($_POST['ajax_remove_product'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ep_id = intval($_POST['ep_id'] ?? 0);
    if ($ep_id > 0) {
        $conn->query("DELETE FROM event_products WHERE id = $ep_id");
        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi sự kiện!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ!']);
    }
    exit();
}

// 6. AJAX Gắn Voucher Vào Sự Kiện
if (isset($_POST['ajax_link_voucher'])) {
    header('Content-Type: application/json; charset=utf-8');
    $eid_ref = intval($_POST['event_id_ref'] ?? 0);
    $vid_lnk = intval($_POST['voucher_id'] ?? 0);
    if ($eid_ref > 0 && $vid_lnk > 0) {
        $conn->query("UPDATE vouchers SET sale_event_id = $eid_ref WHERE id = $vid_lnk");
        echo json_encode(['success' => true, 'message' => 'Đã gắn Voucher vào sự kiện!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn voucher hợp lệ!']);
    }
    exit();
}

// 7. AJAX Bỏ Gắn Voucher Khỏi Sự Kiện
if (isset($_POST['ajax_unlink_voucher'])) {
    header('Content-Type: application/json; charset=utf-8');
    $vid_ref = intval($_POST['voucher_id'] ?? 0);
    if ($vid_ref > 0) {
        $conn->query("UPDATE vouchers SET sale_event_id = NULL WHERE id = $vid_ref");
        echo json_encode(['success' => true, 'message' => 'Đã gỡ voucher khỏi sự kiện!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ!']);
    }
    exit();
}

// 8. AJAX Lấy Chi Tiết Sự Kiện (Sản phẩm & Vouchers)
if (isset($_GET['ajax_get_event_detail'])) {
    header('Content-Type: application/json; charset=utf-8');
    $eid = intval($_GET['ajax_get_event_detail']);
    $ev = $conn->query("SELECT * FROM sale_events WHERE id = $eid")->fetch_assoc();
    if (!$ev) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện!']);
        exit();
    }

    $products = [];
    $p_res = $conn->query("
        SELECT ep.*, p.name, p.main_image, p.price, b.name AS brand_name
        FROM event_products ep 
        JOIN products p ON ep.product_id = p.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE ep.event_id = $eid 
        ORDER BY ep.sort_order ASC, ep.id DESC
    ");
    if ($p_res) while($r = $p_res->fetch_assoc()) $products[] = $r;

    $vouchers = [];
    $v_res = $conn->query("SELECT v.*, b.name AS brand_name FROM vouchers v LEFT JOIN brands b ON v.brand_id = b.id WHERE v.sale_event_id = $eid ORDER BY v.id DESC");
    if ($v_res) while($r = $v_res->fetch_assoc()) $vouchers[] = $r;

    $linkable_vouchers = [];
    $lv_res = $conn->query("SELECT id, code, title FROM vouchers WHERE (sale_event_id IS NULL OR sale_event_id = 0) AND status = 1 ORDER BY id DESC");
    if ($lv_res) while($r = $lv_res->fetch_assoc()) $linkable_vouchers[] = $r;

    echo json_encode([
        'success'           => true,
        'event'             => $ev,
        'products'          => $products,
        'vouchers'          => $vouchers,
        'linkable_vouchers' => $linkable_vouchers
    ]);
    exit();
}

include_once 'includes/header.php';

// ============================================================
// TRUY VẤN DỮ LIỆU BAN ĐẦU
// ============================================================
$events_list = [];
$r = $conn->query("SELECT se.*, 
    (SELECT COUNT(*) FROM event_products ep WHERE ep.event_id = se.id) AS prod_count,
    (SELECT COUNT(*) FROM vouchers v WHERE v.sale_event_id = se.id) AS voucher_count
    FROM sale_events se 
    ORDER BY se.sort_order ASC, se.id DESC");
if ($r) while ($row = $r->fetch_assoc()) $events_list[] = $row;

$all_products = [];
$r_p = $conn->query("SELECT p.id, p.name, p.price, b.name AS brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.status = 1 ORDER BY p.name ASC LIMIT 500");
if ($r_p) while ($row = $r_p->fetch_assoc()) $all_products[] = $row;

$linkable_vouchers = [];
$r_v = $conn->query("SELECT id, code, title FROM vouchers WHERE (sale_event_id IS NULL OR sale_event_id = 0) AND status = 1 ORDER BY id DESC");
if ($r_v) while ($row = $r_v->fetch_assoc()) $linkable_vouchers[] = $row;

$active_edit_id = intval($_GET['edit'] ?? ($events_list[0]['id'] ?? 0));

// Thống kê nhanh
$now_time = time();
$count_active = 0;
$count_upcoming = 0;
$count_hidden = 0;
foreach ($events_list as $e_chk) {
    $st = strtotime($e_chk['start_date']);
    $en = strtotime($e_chk['end_date']);
    if ($e_chk['status'] && $now_time >= $st && $now_time <= $en) {
        $count_active++;
    } elseif ($e_chk['status'] && $now_time < $st) {
        $count_upcoming++;
    } else {
        $count_hidden++;
    }
}
?>

<div class="content-wrapper">
    <!-- TOP HEADER & QUICK STATS -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-3 border-bottom">
        <div>
            <h4 class="fw-bold text-uppercase mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-calendar-star text-warning"></i> Quản Lý Sự Kiện Sale &amp; Khuyến Mãi
            </h4>
            <div class="small text-muted">Điều khiển toàn diện các đợt Sale, Flash Sale Giờ Vàng, Sản phẩm giảm giá &amp; Kho Voucher</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm" onclick="initNewEventForm()">
                <i class="fa-solid fa-plus-circle me-1"></i> Tạo Sự Kiện Mới
            </button>
            <a href="../sale-event.php?slug=sale-19-8" target="_blank" class="btn btn-outline-dark rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Xem Trang Sale Phía Khách
            </a>
            <button type="button" class="btn btn-light border rounded-pill px-3" onclick="location.reload()" title="Tải lại trang">
                <i class="fa-solid fa-rotate"></i>
            </button>
        </div>
    </div>

    <!-- KPI BADGE BAR -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <div class="text-muted small fw-bold text-uppercase">Tổng Sự Kiện</div>
                <div class="fs-4 fw-black text-dark mt-1" id="kpiTotalEvents"><?= count($events_list) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-success border-4">
                <div class="text-muted small fw-bold text-uppercase">🟢 Đang Diễn Ra</div>
                <div class="fs-4 fw-black text-success mt-1" id="kpiActiveEvents"><?= $count_active ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-info border-4">
                <div class="text-muted small fw-bold text-uppercase">🔵 Sắp Diễn Ra</div>
                <div class="fs-4 fw-black text-info mt-1" id="kpiUpcomingEvents"><?= $count_upcoming ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-secondary border-4">
                <div class="text-muted small fw-bold text-uppercase">⚪ Đang Tắt / Ẩn</div>
                <div class="fs-4 fw-black text-secondary mt-1" id="kpiHiddenEvents"><?= $count_hidden ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- CỘT TRÁI: DANH SÁCH TẤT CẢ SỰ KIỆN KÈM ĐẦY ĐỦ NÚT ĐIỀU KHIỂN -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-dark text-white rounded-top-4 py-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold fs-6"><i class="fa-solid fa-list-check me-2 text-warning"></i>Danh Sách Sự Kiện (<span id="eventCountBadge"><?= count($events_list) ?></span>)</span>
                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1 fw-bold" onclick="initNewEventForm()">
                            <i class="fa-solid fa-plus me-1"></i> Thêm Mới
                        </button>
                    </div>
                    <!-- Ô Tìm Kiếm Sự Kiện -->
                    <div class="position-relative">
                        <input type="text" id="eventSearchInput" class="form-control rounded-pill pe-5 bg-white border-0" placeholder="🔍 Tìm tên sự kiện, ngày..." oninput="filterEventsList()">
                    </div>
                </div>

                <!-- BỘ LỌC TRẠNG THÁI NHANH -->
                <div class="px-3 pt-2 pb-1 bg-light border-bottom d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-2 py-0 filter-tab-btn active" data-filter="all" onclick="filterByStatus('all', this)" style="font-size: 11px;">Tất cả</button>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0 filter-tab-btn" data-filter="success" onclick="filterByStatus('success', this)" style="font-size: 11px;">🟢 Đang chạy</button>
                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-0 filter-tab-btn" data-filter="info" onclick="filterByStatus('info', this)" style="font-size: 11px;">🔵 Sắp tới</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 filter-tab-btn" data-filter="secondary" onclick="filterByStatus('secondary', this)" style="font-size: 11px;">⚪ Đang tắt</button>
                </div>

                <div class="card-body p-2" id="eventsListContainer" style="max-height: 800px; overflow-y: auto;">
                    <?php if (empty($events_list)): ?>
                        <div class="text-center text-muted py-5 small" id="noEventNotice">Chưa có sự kiện nào. Hãy tạo sự kiện đầu tiên!</div>
                    <?php endif; ?>
                    
                    <?php foreach($events_list as $ev):
                        $now = time();
                        $st  = strtotime($ev['start_date']);
                        $en  = strtotime($ev['end_date']);
                        $status_class = ($ev['status'] && $now >= $st && $now <= $en) ? 'success' : (($ev['status'] && $now < $st) ? 'info' : 'secondary');
                        $status_lbl   = ($ev['status'] && $now >= $st && $now <= $en) ? '🟢 Đang diễn ra' : (($ev['status'] && $now < $st) ? '🔵 Sắp diễn ra' : '⚪ Đang ẩn/Tắt');
                    ?>
                    <!-- CARD SỰ KIỆN VỚI ĐẦY ĐỦ NÚT ĐIỀU KHIỂN -->
                    <div class="event-item-card card border rounded-4 p-3 mb-2 shadow-sm <?= ($active_edit_id == $ev['id']) ? 'active-event-item' : '' ?>" 
                         id="event-card-<?= $ev['id'] ?>"
                         data-id="<?= $ev['id'] ?>"
                         data-status-type="<?= $status_class ?>"
                         data-search="<?= strtolower(htmlspecialchars($ev['name'] . ' ' . $ev['slug'] . ' ' . date('d/m/Y', $st) . ' ' . date('d/m/Y', $en))) ?>"
                         style="transition: all 0.2s ease;">
                        
                        <!-- DÒNG 1: ICON + TIÊU ĐỀ + STATUS BADGE -->
                        <div class="d-flex gap-2 align-items-start mb-2">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                                 id="event-icon-box-<?= $ev['id'] ?>"
                                 style="width: 42px; height: 42px; background: <?= htmlspecialchars($ev['color_theme']) ?>22; border: 1px solid <?= htmlspecialchars($ev['color_theme']) ?>44;">
                                <i class="<?= htmlspecialchars($ev['icon']) ?>" id="event-icon-i-<?= $ev['id'] ?>" style="color: <?= htmlspecialchars($ev['color_theme']) ?>; font-size: 1.25rem;"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-dark fs-6" id="event-name-<?= $ev['id'] ?>" style="line-height: 1.3;"><?= htmlspecialchars($ev['name']) ?></div>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    <span class="badge bg-<?= $status_class ?> rounded-pill" id="event-status-badge-<?= $ev['id'] ?>" style="font-size: 10px; padding: 4px 8px;"><?= $status_lbl ?></span>
                                    <span class="badge bg-light text-dark border rounded-pill" style="font-size: 10px;">Thứ tự: <?= $ev['sort_order'] ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- DÒNG 2: THÔNG TIN NGÀY VÀ ĐẾM SẢN PHẨM/VOUCHER -->
                        <div class="bg-light rounded-3 p-2 mb-2 small d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <div class="text-muted" id="event-date-<?= $ev['id'] ?>">
                                <i class="fa-regular fa-calendar me-1 text-primary"></i><?= date('d/m/Y H:i', strtotime($ev['start_date'])) ?> – <?= date('d/m/Y H:i', strtotime($ev['end_date'])) ?>
                            </div>
                            <div class="d-flex gap-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">👟 <?= $ev['prod_count'] ?? 0 ?> SP</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">🎫 <?= $ev['voucher_count'] ?? 0 ?> Voucher</span>
                            </div>
                        </div>
                        
                        <!-- DÒNG 3: THANH NÚT ĐIỀU KHIỂN ĐẦY ĐỦ (FULL CONTROLS) -->
                        <div class="d-flex gap-1 pt-1 border-top align-items-center justify-content-between">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold flex-grow-1" onclick="loadEventDetail(<?= $ev['id'] ?>)" title="Mở biểu mẫu chỉnh sửa sự kiện này">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Sửa / Chọn
                            </button>
                            <a href="../sale-event.php?slug=<?= urlencode($ev['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" title="Xem trang sự kiện của khách">
                                <i class="fa-solid fa-eye"></i> Xem
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" onclick="ajaxToggleEvent(<?= $ev['id'] ?>)" title="Bật / Tắt kích hoạt sự kiện">
                                <i class="fa-solid fa-power-off"></i> Bật/Tắt
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" onclick="ajaxDeleteEvent(<?= $ev['id'] ?>, '<?= addslashes(htmlspecialchars($ev['name'])) ?>')" title="Xóa vĩnh viễn sự kiện này">
                                <i class="fa-solid fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: FORM CHỈNH SỬA & QUẢN LÝ SẢN PHẨM / VOUCHER (ĐẦY ĐỦ NÚT ĐIỀU KHIỂN) -->
        <div class="col-12 col-lg-7 col-xl-8">
            <!-- THANH ĐIỀU HƯỚNG TABS -->
            <ul class="nav nav-pills mb-3 fw-bold gap-2 bg-white p-2 rounded-4 shadow-sm border" id="eventMainTabs">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4 py-2" id="tab-info-btn" onclick="switchEventTab('info')">
                        <i class="fa-solid fa-sliders me-1 text-warning"></i> 1. Thông Tin Sự Kiện
                    </button>
                </li>
                <li class="nav-item" id="tab-products-li" style="display: <?= $active_edit_id > 0 ? 'block' : 'none' ?>;">
                    <button class="nav-link rounded-pill px-4 py-2" id="tab-products-btn" onclick="switchEventTab('products')">
                        <i class="fa-solid fa-boxes-stacked me-1 text-primary"></i> 2. Sản Phẩm Tham Gia <span class="badge bg-primary rounded-pill ms-1" id="tabProductCountBadge">0</span>
                    </button>
                </li>
                <li class="nav-item" id="tab-vouchers-li" style="display: <?= $active_edit_id > 0 ? 'block' : 'none' ?>;">
                    <button class="nav-link rounded-pill px-4 py-2" id="tab-vouchers-btn" onclick="switchEventTab('vouchers')">
                        <i class="fa-solid fa-ticket me-1 text-danger"></i> 3. Voucher Sự Kiện <span class="badge bg-warning text-dark rounded-pill ms-1" id="tabVoucherCountBadge">0</span>
                    </button>
                </li>
            </ul>

            <!-- ========================================== -->
            <!-- TAB 1: FORM THÔNG TIN SỰ KIỆN -->
            <!-- ========================================== -->
            <div id="tab-content-info" class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <!-- FORM HEADER WITH ACTION BUTTONS -->
                <div class="card-header bg-dark text-white rounded-top-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold fs-6">
                        <i class="fa-solid fa-pen-to-square me-2 text-warning" id="formHeaderIcon"></i>
                        <span id="formHeaderTitle">Tạo Sự Kiện Mới</span>
                    </span>
                    <!-- THANH NÚT ĐIỀU KHIỂN TRÊN ĐẦU FORM -->
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <a href="#" id="btnPreviewEventPage" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold" style="display: none;">
                            <i class="fa-solid fa-eye me-1"></i> Xem Trang Khách
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="initNewEventForm()">
                            <i class="fa-solid fa-plus me-1"></i> Tạo Mới
                        </button>
                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold shadow-sm" onclick="document.getElementById('eventAjaxForm').requestSubmit()">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Ngay
                        </button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form id="eventAjaxForm" data-ajax-form="1" enctype="multipart/form-data">
                        <input type="hidden" name="ajax_save_event" value="1">
                        <input type="hidden" name="event_id" id="form_event_id" value="0">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Tên Sự Kiện <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="form_name" class="form-control form-control-lg fw-bold" placeholder="VD: SALE 19/8 hoặc Flash Sale Giờ Vàng" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Thứ Tự Ưu Tiên (Sort)</label>
                                <input type="number" name="sort_order" id="form_sort_order" class="form-control form-control-lg" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Đường Dẫn Slug URL</label>
                                <input type="text" name="slug" id="form_slug" class="form-control font-monospace" placeholder="sale-19-8">
                                <div class="form-text small text-muted">Hệ thống tự động chống trùng lặp và làm sạch dấu tiếng Việt.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Màu Chủ Đề Sự Kiện</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="color_theme" id="form_color_theme" class="form-control form-control-color shadow-sm" style="width: 55px; height: 40px; cursor: pointer;" value="#ef4444" onchange="updateEventColorPreview(this.value)">
                                    <input type="text" class="form-control font-monospace fw-bold" id="form_color_hex" value="#ef4444" readonly>
                                </div>
                            </div>

                            <!-- KHUNG CHỌN ICON ĐẠI DIỆN TRỰC QUAN -->
                            <div class="col-12">
                                <label class="form-label fw-bold mb-1"><i class="fa-solid fa-icons text-warning me-1"></i> Biểu Tượng / Icon Đại Diện Sự Kiện</label>
                                <input type="hidden" name="icon" id="form_icon_val" value="fa-solid fa-fire">
                                
                                <div class="card p-3 bg-light border rounded-3 mb-2">
                                    <ul class="nav nav-pills mb-3 gap-2" id="iconTypeTab" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active py-1 px-3 fw-bold small rounded-pill" id="tab-visual-icon-btn" data-bs-toggle="pill" data-bs-target="#tab-visual-icon" type="button">
                                                <i class="fa-solid fa-shapes me-1"></i> Chọn Hình Biểu Tượng Sẵn Có
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link py-1 px-3 fw-bold small rounded-pill" id="tab-custom-icon-btn" data-bs-toggle="pill" data-bs-target="#tab-custom-icon" type="button">
                                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Hoặc Tải Lên Tệp Ảnh Icon Riêng
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="iconTypeTabContent">
                                        <div class="tab-pane fade show active" id="tab-visual-icon" role="tabpanel">
                                            <div class="d-flex flex-wrap gap-2 icon-picker-grid" style="max-height: 180px; overflow-y: auto; padding: 4px;">
                                                <?php
                                                $preset_icons = [
                                                    ['icon' => 'fa-solid fa-fire', 'name' => '🔥 Rực Lửa'],
                                                    ['icon' => 'fa-solid fa-bolt', 'name' => '⚡ Tia Chớp'],
                                                    ['icon' => 'fa-solid fa-flag', 'name' => '🇻🇳 Đại Lễ / Cờ'],
                                                    ['icon' => 'fa-solid fa-gift', 'name' => '🎁 Quà Tặng'],
                                                    ['icon' => 'fa-solid fa-tag', 'name' => '🏷️ Thẻ Sale'],
                                                    ['icon' => 'fa-solid fa-shoe-prints', 'name' => '👟 Sneaker'],
                                                    ['icon' => 'fa-solid fa-bag-shopping', 'name' => '🛍️ Mua Sắm'],
                                                    ['icon' => 'fa-solid fa-gem', 'name' => '💎 Hàng Hiệu'],
                                                    ['icon' => 'fa-solid fa-rocket', 'name' => '🚀 Siêu Tốc'],
                                                    ['icon' => 'fa-solid fa-star', 'name' => '⭐ Ngôi Sao'],
                                                    ['icon' => 'fa-solid fa-crown', 'name' => '👑 Hoàng Gia'],
                                                    ['icon' => 'fa-solid fa-bullseye', 'name' => '🎯 Săn Deal'],
                                                    ['icon' => 'fa-solid fa-clock', 'name' => '⏰ Giờ Vàng'],
                                                    ['icon' => 'fa-solid fa-burst', 'name' => '💥 Bùng Nổ'],
                                                    ['icon' => 'fa-solid fa-trophy', 'name' => '🏆 Vô Địch'],
                                                    ['icon' => 'fa-solid fa-cart-shopping', 'name' => '🛒 Giỏ Hàng'],
                                                    ['icon' => 'fa-solid fa-truck-fast', 'name' => '🚚 Freeship'],
                                                    ['icon' => 'fa-solid fa-money-bill-wave', 'name' => '💸 Hoàn Tiền'],
                                                    ['icon' => 'fa-solid fa-heart', 'name' => '❤️ Yêu Thích'],
                                                    ['icon' => 'fa-solid fa-bell', 'name' => '🔔 Thông Báo'],
                                                    ['icon' => 'fa-solid fa-wand-magic-sparkles', 'name' => '✨ Kỳ Diệu'],
                                                    ['icon' => 'fa-solid fa-percent', 'name' => '% Giảm Giá'],
                                                    ['icon' => 'fa-solid fa-badge-check', 'name' => '🛡️ Đảm Bảo']
                                                ];
                                                foreach($preset_icons as $pi):
                                                ?>
                                                    <button type="button" class="btn btn-sm btn-icon-choice btn-outline-secondary d-flex align-items-center gap-1 rounded-pill px-3 py-1" onclick="selectVisualIcon('<?= $pi['icon'] ?>', this)">
                                                        <i class="<?= $pi['icon'] ?>"></i>
                                                        <span class="small"><?= $pi['name'] ?></span>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="tab-custom-icon" role="tabpanel">
                                            <div class="row align-items-center g-3">
                                                <div class="col-md-8">
                                                    <input type="file" name="icon_image_file" class="form-control form-control-sm mb-2" accept="image/*" onchange="previewSelectedImage(this, 'preview_icon_img')">
                                                    <input type="text" name="icon_image" id="input_icon_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh icon..." oninput="previewUrlImage(this.value, 'preview_icon_img')">
                                                </div>
                                                <div class="col-md-4 text-center">
                                                    <div class="border rounded-3 p-2 bg-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                                                        <img id="preview_icon_img" src="" alt="Icon Preview" style="max-height: 40px; max-width: 40px; object-fit: contain; display: none;">
                                                        <span id="preview_icon_placeholder" class="text-muted"><i class="fa-regular fa-image"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ngày &amp; Giờ Bắt Đầu <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date" id="form_start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ngày &amp; Giờ Kết Thúc <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_date" id="form_end_date" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Mô Tả Ngắn Sự Kiện</label>
                                <textarea name="description" id="form_description" class="form-control" rows="2" placeholder="Mô tả sự kiện, quyền lợi khách hàng..."></textarea>
                            </div>

                            <!-- BANNER TRANG SỰ KIỆN -->
                            <div class="col-12"><hr class="my-2"><strong class="small text-uppercase text-muted fw-bold"><i class="fa-solid fa-image me-1 text-primary"></i> Ảnh Banner Header Trang Sự Kiện</strong></div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-12 col-md-7">
                                            <label class="form-label fw-semibold small mb-1">Tải lên tệp ảnh Banner mới:</label>
                                            <input type="file" name="banner_image_file" class="form-control mb-2" accept="image/*" onchange="previewSelectedImage(this, 'preview_banner_img')">
                                            <label class="form-label fw-semibold small mb-1">Hoặc nhập đường dẫn URL ảnh:</label>
                                            <input type="text" name="banner_image" id="input_banner_url" class="form-control form-control-sm" placeholder="VD: uploads/banners/sale.jpg hoặc https://..." oninput="previewUrlImage(this.value, 'preview_banner_img')">
                                        </div>
                                        <div class="col-12 col-md-5 text-center">
                                            <div class="border rounded-3 p-2 bg-white d-flex flex-column align-items-center justify-content-center shadow-sm" style="min-height: 120px; max-height: 160px; overflow: hidden;">
                                                <img id="preview_banner_img" src="" alt="Banner Preview" style="max-height: 110px; max-width: 100%; object-fit: contain; display: none;">
                                                <span id="preview_banner_placeholder" class="text-muted small">
                                                    <i class="fa-solid fa-panorama fa-2x mb-1 d-block opacity-50"></i>Chưa có ảnh banner
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TÙY CHỌN HIỂN THỊ -->
                            <div class="col-12">
                                <div class="d-flex gap-4 flex-wrap bg-light p-3 rounded-3 border">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="show_on_homepage_banner" id="chkHpBanner" value="1" checked>
                                        <label class="form-check-label fw-semibold" for="chkHpBanner">Hiển thị trong Banner Trượt Trang Chủ</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="show_on_menu" id="chkMenu" value="1" checked>
                                        <label class="form-check-label fw-semibold" for="chkMenu">Hiển thị trên Menu Navigation</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="status" id="chkStatus" value="1" checked>
                                        <label class="form-check-label fw-bold text-success" for="chkStatus">Kích Hoạt Sự Kiện</label>
                                    </div>
                                </div>
                            </div>

                            <!-- THANH NÚT ĐIỀU KHIỂN CUỐI FORM -->
                            <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-between align-items-center flex-wrap">
                                <div class="d-flex gap-2">
                                    <button type="submit" id="btnSaveEvent" class="btn btn-warning fw-bold rounded-pill px-5 py-2 shadow">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Sự Kiện
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2" onclick="initNewEventForm()">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Nhập Lại
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 py-2" id="btnDeleteCurrentEvent" style="display: none;" onclick="deleteCurrentLoadedEvent()">
                                        <i class="fa-solid fa-trash me-1"></i> Xóa Sự Kiện Này
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: QUẢN LÝ SẢN PHẨM SỰ KIỆN (LIVE AJAX) -->
            <!-- ========================================== -->
            <div id="tab-content-products" style="display: none;">
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-dark text-white rounded-top-4 fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-box-open me-2 text-warning"></i>Thêm Sản Phẩm Vào Sự Kiện</span>
                        <span class="badge bg-warning text-dark font-monospace" id="prodEventNameLabel">Sự kiện</span>
                    </div>
                    <div class="card-body p-4">
                        <form id="addProductEventForm" class="row g-3">
                            <input type="hidden" name="ajax_add_product" value="1">
                            <input type="hidden" name="event_id_ref" id="add_prod_event_id" value="0">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small">Chọn Sản Phẩm</label>
                                <select name="product_id" id="sel_product_id" class="form-select" required onchange="onSelectEventProduct(this)">
                                    <option value="">-- Chọn sản phẩm --</option>
                                    <?php foreach($all_products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>">[<?= htmlspecialchars($p['brand_name']??'SP') ?>] <?= htmlspecialchars($p['name']) ?> — <?= number_format($p['price'],0,',','.') ?>đ</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small">% Giảm (%)</label>
                                <input type="number" step="1" min="0" max="100" name="discount_percent" id="add_prod_disc_pct" class="form-control fw-bold text-danger text-center" placeholder="VD: 25" oninput="onInputEventDiscountPercent(this.value)">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Giá Bán Sự Kiện (VNĐ)</label>
                                <input type="number" name="sale_price" id="add_prod_sale_price" class="form-control fw-bold text-success" placeholder="Tự tính từ % giảm" oninput="onInputEventSalePrice(this.value)">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" id="btnAddProdSubmit" class="btn btn-warning fw-bold w-100 rounded-pill py-2 shadow-sm"><i class="fa-solid fa-plus me-1"></i> Thêm</button>
                            </div>
                            <div class="col-12 mt-1">
                                <div class="p-2 bg-light rounded-3 border small" id="priceCalculationHint">
                                    <i class="fa-solid fa-calculator text-warning me-1"></i> Chọn sản phẩm và nhập <strong>% Giảm</strong> để hệ thống tự động tính ra <strong>Giá Bán Sự Kiện</strong>.
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-list me-2 text-primary"></i>Sản Phẩm Đang Tham Gia Sự Kiện</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6" id="badgeProdCount">0 sản phẩm</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="eventProductsTable">
                            <thead class="table-dark">
                                <tr><th>Sản Phẩm</th><th>Giá Gốc</th><th>% Giảm Nhập</th><th>Giá Bán Sự Kiện</th><th class="text-end pe-3">Thao Tác</th></tr>
                            </thead>
                            <tbody id="eventProductsTbody">
                                <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có sản phẩm nào trong sự kiện này.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: QUẢN LÝ VOUCHER SỰ KIỆN (LIVE AJAX) -->
            <!-- ========================================== -->
            <div id="tab-content-vouchers" style="display: none;">
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-dark text-white rounded-top-4 fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-ticket me-2 text-warning"></i>Gắn Voucher Vào Sự Kiện</span>
                        <a href="vouchers.php" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3">
                            <i class="fa-solid fa-plus me-1"></i> Tạo Thêm Voucher Mới
                        </a>
                    </div>
                    <div class="card-body p-4">
                        <form id="linkVoucherEventForm" class="row g-3">
                            <input type="hidden" name="ajax_link_voucher" value="1">
                            <input type="hidden" name="event_id_ref" id="link_v_event_id" value="0">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Chọn Voucher Chưa Gắn Sự Kiện</label>
                                <select name="voucher_id" id="sel_linkable_voucher" class="form-select form-select-lg" required>
                                    <option value="">-- Chọn voucher --</option>
                                    <?php foreach($linkable_vouchers as $lv): ?>
                                    <option value="<?= $lv['id'] ?>">[<?= htmlspecialchars($lv['code']) ?>] <?= htmlspecialchars($lv['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" id="btnLinkVoucherSubmit" class="btn btn-warning fw-bold w-100 rounded-pill py-2 shadow-sm"><i class="fa-solid fa-link me-1"></i> Gắn Vào Sự Kiện</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-ticket me-2 text-warning"></i>Voucher Dành Riêng Cho Sự Kiện Này</span>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6" id="badgeVoucherCount">0 voucher</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="eventVouchersTable">
                            <thead class="table-dark">
                                <tr><th>Mã Voucher</th><th>Tiêu Đề</th><th>Mức Giảm</th><th>Đơn Tối Thiểu</th><th>HSD</th><th class="text-end pe-3">Thao Tác</th></tr>
                            </thead>
                            <tbody id="eventVouchersTbody">
                                <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có voucher nào gắn vào sự kiện này.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

let currentActiveEventId = <?= $active_edit_id ?>;
let selectedProductBasePrice = 0;

// Lọc danh sách sự kiện tức thì (Live Search Filter)
function filterEventsList() {
    const kw = document.getElementById('eventSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.event-item-card');
    rows.forEach(r => {
        const searchVal = r.getAttribute('data-search') || '';
        r.style.display = (!kw || searchVal.includes(kw)) ? 'block' : 'none';
    });
}

// Lọc theo Tab trạng thái
function filterByStatus(statusType, btnEl) {
    document.querySelectorAll('.filter-tab-btn').forEach(b => {
        b.classList.remove('active', 'btn-dark', 'btn-success', 'btn-info', 'btn-secondary');
        const f = b.getAttribute('data-filter');
        if (f === 'all') b.classList.add('btn-outline-dark');
        else if (f === 'success') b.classList.add('btn-outline-success');
        else if (f === 'info') b.classList.add('btn-outline-info');
        else if (f === 'secondary') b.classList.add('btn-outline-secondary');
    });

    btnEl.classList.add('active');
    if (statusType === 'all') btnEl.classList.add('btn-dark');
    else if (statusType === 'success') btnEl.classList.add('btn-success');
    else if (statusType === 'info') btnEl.classList.add('btn-info');
    else if (statusType === 'secondary') btnEl.classList.add('btn-secondary');

    const rows = document.querySelectorAll('.event-item-card');
    rows.forEach(r => {
        const cardStatus = r.getAttribute('data-status-type') || '';
        if (statusType === 'all' || cardStatus === statusType) {
            r.style.display = 'block';
        } else {
            r.style.display = 'none';
        }
    });
}

// Chuyển Tab bên phải
function switchEventTab(tabName) {
    ['info', 'products', 'vouchers'].forEach(t => {
        const content = document.getElementById('tab-content-' + t);
        const btn = document.getElementById('tab-' + t + '-btn');
        if (content) content.style.display = (t === tabName) ? 'block' : 'none';
        if (btn) {
            if (t === tabName) {
                btn.className = 'nav-link active rounded-pill px-4 py-2';
            } else {
                btn.className = 'nav-link rounded-pill px-4 py-2';
            }
        }
    });
}

// Khởi tạo Form Sự Kiện Mới
function initNewEventForm() {
    currentActiveEventId = 0;
    document.getElementById('formHeaderTitle').innerText = 'Tạo Sự Kiện Mới';
    document.getElementById('formHeaderIcon').className = 'fa-solid fa-plus me-2 text-warning';
    document.getElementById('btnPreviewEventPage').style.display = 'none';
    document.getElementById('btnDeleteCurrentEvent').style.display = 'none';

    document.getElementById('form_event_id').value = '0';
    document.getElementById('form_name').value = '';
    document.getElementById('form_slug').value = '';
    document.getElementById('form_sort_order').value = '0';
    document.getElementById('form_color_theme').value = '#ef4444';
    document.getElementById('form_color_hex').value = '#ef4444';
    document.getElementById('form_description').value = '';
    document.getElementById('input_banner_url').value = '';
    document.getElementById('input_icon_url').value = '';
    document.getElementById('form_icon_val').value = 'fa-solid fa-fire';

    const now = new Date();
    const startISO = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    const futureDate = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));
    futureDate.setHours(23, 59, 59, 0);
    const endISO = new Date(futureDate.getTime() - (futureDate.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

    document.getElementById('form_start_date').value = startISO;
    document.getElementById('form_end_date').value = endISO;

    document.getElementById('chkHpBanner').checked = true;
    document.getElementById('chkMenu').checked = true;
    document.getElementById('chkStatus').checked = true;

    // Reset previews
    previewUrlImage('', 'preview_banner_img');
    previewUrlImage('', 'preview_icon_img');

    // Ẩn tabs phụ khi tạo mới
    document.getElementById('tab-products-li').style.display = 'none';
    document.getElementById('tab-vouchers-li').style.display = 'none';
    switchEventTab('info');

    // Bỏ active item ở list bên trái
    document.querySelectorAll('.event-item-card').forEach(r => r.classList.remove('active-event-item'));
}

// Nạp chi tiết sự kiện qua AJAX (Live Detail)
function loadEventDetail(eventId) {
    currentActiveEventId = eventId;

    // Highlight card
    document.querySelectorAll('.event-item-card').forEach(r => {
        if (r.getAttribute('data-id') == eventId) {
            r.classList.add('active-event-item');
        } else {
            r.classList.remove('active-event-item');
        }
    });

    fetch('sale-events.php?ajax_get_event_detail=' + eventId)
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
            return;
        }

        const ev = data.event;
        document.getElementById('formHeaderTitle').innerText = 'Chỉnh Sửa: ' + ev.name;
        document.getElementById('formHeaderIcon').className = 'fa-solid fa-pen-to-square me-2 text-warning';
        
        const previewBtn = document.getElementById('btnPreviewEventPage');
        previewBtn.href = '../sale-event.php?slug=' + encodeURIComponent(ev.slug);
        previewBtn.style.display = 'inline-block';

        const delBtn = document.getElementById('btnDeleteCurrentEvent');
        delBtn.style.display = 'inline-block';

        const prodLabel = document.getElementById('prodEventNameLabel');
        if (prodLabel) prodLabel.innerText = ev.name;

        document.getElementById('form_event_id').value = ev.id;
        document.getElementById('form_name').value = ev.name || '';
        document.getElementById('form_slug').value = ev.slug || '';
        document.getElementById('form_sort_order').value = ev.sort_order || 0;
        document.getElementById('form_color_theme').value = ev.color_theme || '#ef4444';
        document.getElementById('form_color_hex').value = ev.color_theme || '#ef4444';
        document.getElementById('form_description').value = ev.description || '';
        document.getElementById('input_banner_url').value = ev.banner_image || '';
        document.getElementById('input_icon_url').value = ev.icon_image || '';
        document.getElementById('form_icon_val').value = ev.icon || 'fa-solid fa-fire';

        document.getElementById('form_start_date').value = ev.start_date ? ev.start_date.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('form_end_date').value = ev.end_date ? ev.end_date.replace(' ', 'T').slice(0, 16) : '';

        document.getElementById('chkHpBanner').checked = (ev.show_on_homepage_banner == 1);
        document.getElementById('chkMenu').checked = (ev.show_on_menu == 1);
        document.getElementById('chkStatus').checked = (ev.status == 1);

        previewUrlImage(ev.banner_image || '', 'preview_banner_img');
        previewUrlImage(ev.icon_image || '', 'preview_icon_img');

        // Bật hiển thị các tab phụ
        document.getElementById('tab-products-li').style.display = 'block';
        document.getElementById('tab-vouchers-li').style.display = 'block';
        document.getElementById('add_prod_event_id').value = ev.id;
        document.getElementById('link_v_event_id').value = ev.id;

        // Render bảng sản phẩm
        renderProductsTable(data.products || []);
        
        // Render bảng vouchers & dropdown linkable
        renderVouchersTable(data.vouchers || []);
        renderLinkableVouchersDropdown(data.linkable_vouchers || []);
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
    });
}

function deleteCurrentLoadedEvent() {
    if (currentActiveEventId > 0) {
        const name = document.getElementById('form_name').value || 'Sự kiện này';
        ajaxDeleteEvent(currentActiveEventId, name);
    }
}

// Xử lý chọn sản phẩm và tự động tính toán giá
function onSelectEventProduct(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    selectedProductBasePrice = opt ? (parseFloat(opt.getAttribute('data-price')) || 0) : 0;
    
    const discInput = document.getElementById('add_prod_disc_pct');
    const discVal = parseFloat(discInput.value) || 0;
    if (discVal > 0 && selectedProductBasePrice > 0) {
        onInputEventDiscountPercent(discVal);
    } else if (selectedProductBasePrice > 0) {
        document.getElementById('add_prod_sale_price').value = selectedProductBasePrice;
        document.getElementById('priceCalculationHint').innerHTML = `<i class="fa-solid fa-tag text-primary me-1"></i> <span class="text-dark fw-bold">Giá gốc: ${selectedProductBasePrice.toLocaleString('vi-VN')}đ</span> (Nhập % giảm để tự tính giá sale)`;
    } else {
        document.getElementById('add_prod_sale_price').value = '';
        document.getElementById('priceCalculationHint').innerHTML = `<i class="fa-solid fa-calculator text-warning me-1"></i> Chọn sản phẩm và nhập <strong>% Giảm</strong> để hệ thống tự động tính ra <strong>Giá Bán Sự Kiện</strong>.`;
    }
}

// Khi người dùng gõ % giảm giá
function onInputEventDiscountPercent(val) {
    const pct = parseFloat(val) || 0;
    const hint = document.getElementById('priceCalculationHint');
    const saleInput = document.getElementById('add_prod_sale_price');
    
    if (selectedProductBasePrice > 0 && pct >= 0 && pct <= 100) {
        const salePrice = Math.round(selectedProductBasePrice * (1 - (pct / 100)));
        saleInput.value = salePrice;
        hint.innerHTML = `<span class="text-muted">Giá gốc: <strong>${selectedProductBasePrice.toLocaleString('vi-VN')}đ</strong></span> <i class="fa-solid fa-arrow-right mx-2 text-muted"></i> Giảm <strong class="text-danger">-${pct}%</strong> <i class="fa-solid fa-arrow-right mx-2 text-muted"></i> <span class="text-success fw-bold fs-6">Giá bán sự kiện: ${salePrice.toLocaleString('vi-VN')}đ</span>`;
    } else if (selectedProductBasePrice > 0) {
        saleInput.value = selectedProductBasePrice;
        hint.innerHTML = `<span class="text-dark fw-bold">Giá gốc: ${selectedProductBasePrice.toLocaleString('vi-VN')}đ</span>`;
    }
}

// Khi người dùng gõ trực tiếp giá sale sự kiện
function onInputEventSalePrice(val) {
    const salePrice = parseFloat(val) || 0;
    const hint = document.getElementById('priceCalculationHint');
    const discInput = document.getElementById('add_prod_disc_pct');
    
    if (selectedProductBasePrice > 0 && salePrice > 0 && salePrice <= selectedProductBasePrice) {
        const pct = Math.round((1 - (salePrice / selectedProductBasePrice)) * 100);
        discInput.value = pct;
        hint.innerHTML = `<span class="text-muted">Giá gốc: <strong>${selectedProductBasePrice.toLocaleString('vi-VN')}đ</strong></span> <i class="fa-solid fa-arrow-right mx-2 text-muted"></i> Giảm <strong class="text-danger">-${pct}%</strong> <i class="fa-solid fa-arrow-right mx-2 text-muted"></i> <span class="text-success fw-bold fs-6">Giá bán sự kiện: ${salePrice.toLocaleString('vi-VN')}đ</span>`;
    } else if (selectedProductBasePrice > 0 && salePrice > selectedProductBasePrice) {
        discInput.value = 0;
        hint.innerHTML = `<span class="text-warning fw-bold">Giá bán cao hơn giá gốc (${selectedProductBasePrice.toLocaleString('vi-VN')}đ)</span>`;
    }
}

function renderProductsTable(products) {
    document.getElementById('tabProductCountBadge').innerText = products.length;
    const badge = document.getElementById('badgeProdCount');
    if (badge) badge.innerText = products.length + ' sản phẩm';

    const tbody = document.getElementById('eventProductsTbody');
    if (!products.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Chưa có sản phẩm nào trong sự kiện này.</td></tr>';
        return;
    }

    let html = '';
    products.forEach(p => {
        const origPrice = parseFloat(p.price) || 0;
        let discPct = parseInt(p.discount_percent) || 0;
        let salePrice = parseFloat(p.sale_price) || 0;

        if (salePrice <= 0 && discPct > 0 && origPrice > 0) {
            salePrice = Math.round(origPrice * (1 - (discPct / 100)));
        } else if (salePrice <= 0) {
            salePrice = origPrice;
        }

        if (discPct <= 0 && origPrice > salePrice && origPrice > 0) {
            discPct = Math.round((1 - (salePrice / origPrice)) * 100);
        }

        const salePriceText = Number(salePrice).toLocaleString('vi-VN') + 'đ';
        const discBadge = discPct > 0 ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold fs-6">-${discPct}%</span>` : '<span class="text-muted small">0%</span>';
        const imgSrc = p.main_image ? (p.main_image.startsWith('http') || p.main_image.startsWith('/') ? p.main_image : '../' + p.main_image) : '../assets/images/no-image.png';

        html += `
        <tr id="ep-row-${p.id}">
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${imgSrc}" class="rounded-3 border shadow-sm" style="width:46px;height:46px;object-fit:cover;" alt="">
                    <div>
                        <div class="fw-bold text-dark" style="font-size:0.875rem;">${p.name}</div>
                        <small class="text-muted"><i class="fa-solid fa-tag me-1"></i>${p.brand_name || 'Sneaker'}</small>
                    </div>
                </div>
            </td>
            <td class="text-muted fw-bold">${origPrice.toLocaleString('vi-VN')}đ</td>
            <td>${discBadge}</td>
            <td class="fw-bold text-success fs-6">${salePriceText}</td>
            <td class="text-end pe-3">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="ajaxRemoveProductFromEvent(${p.id})">
                    <i class="fa-solid fa-trash me-1"></i> Xóa Khỏi Event
                </button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderVouchersTable(vouchers) {
    document.getElementById('tabVoucherCountBadge').innerText = vouchers.length;
    const badge = document.getElementById('badgeVoucherCount');
    if (badge) badge.innerText = vouchers.length + ' voucher';

    const tbody = document.getElementById('eventVouchersTbody');
    if (!vouchers.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có voucher nào gắn vào sự kiện này.</td></tr>';
        return;
    }

    let html = '';
    vouchers.forEach(v => {
        const discText = (v.discount_type === 'percent') ? (v.discount_value + '%') : (Number(v.discount_value).toLocaleString('vi-VN') + 'đ');
        const endDate = v.end_date ? new Date(v.end_date).toLocaleDateString('vi-VN') : 'Không hạn';

        html += `
        <tr id="ev-row-${v.id}">
            <td><span class="badge bg-dark text-warning font-monospace px-3 py-2 fs-6">${v.code}</span></td>
            <td><strong>${v.title || ''}</strong></td>
            <td class="fw-bold text-danger">${discText}</td>
            <td>${Number(v.min_order_value || 0).toLocaleString('vi-VN')}đ</td>
            <td>${endDate}</td>
            <td class="text-end pe-3">
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="ajaxUnlinkVoucherFromEvent(${v.id})" title="Gỡ khỏi sự kiện">
                    <i class="fa-solid fa-unlink me-1"></i> Gỡ Voucher
                </button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderLinkableVouchersDropdown(linkables) {
    const sel = document.getElementById('sel_linkable_voucher');
    let html = '<option value="">-- Chọn voucher --</option>';
    linkables.forEach(lv => {
        html += `<option value="${lv.id}">[${lv.code}] ${lv.title}</option>`;
    });
    sel.innerHTML = html;
}

// 1-Click Bật / Tắt Sự Kiện
function ajaxToggleEvent(eventId) {
    const formData = new FormData();
    formData.append('ajax_toggle_event', '1');
    formData.append('event_id', eventId);

    fetch('sale-events.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('event-status-badge-' + eventId);
            if (badge) {
                badge.className = 'badge bg-' + data.status_class + ' rounded-pill';
                badge.innerText = data.status_lbl;
            }
            const card = document.getElementById('event-card-' + eventId);
            if (card) {
                card.setAttribute('data-status-type', data.status_class);
            }
            Toast.fire({ icon: 'success', title: data.message });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    });
}

// Xóa Sự Kiện
function ajaxDeleteEvent(eventId, eventName) {
    Swal.fire({
        title: 'Xóa sự kiện?',
        html: `Bạn có chắc chắn muốn xóa vĩnh viễn sự kiện <strong class="text-danger">${eventName}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Xóa vĩnh viễn',
        cancelButtonText: 'Hủy'
    }).then(res => {
        if (res.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_event', '1');
            formData.append('delete_id', eventId);

            fetch('sale-events.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('event-card-' + eventId);
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => { row.remove(); }, 300);
                    }
                    if (currentActiveEventId == eventId) {
                        initNewEventForm();
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            });
        }
    });
}

// Xóa sản phẩm khỏi sự kiện
function ajaxRemoveProductFromEvent(epId) {
    const formData = new FormData();
    formData.append('ajax_remove_product', '1');
    formData.append('ep_id', epId);

    fetch('sale-events.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('ep-row-' + epId);
            if (row) row.remove();
            if (currentActiveEventId > 0) loadEventDetail(currentActiveEventId);
            Toast.fire({ icon: 'success', title: data.message });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    });
}

// Gỡ voucher khỏi sự kiện
function ajaxUnlinkVoucherFromEvent(vId) {
    const formData = new FormData();
    formData.append('ajax_unlink_voucher', '1');
    formData.append('voucher_id', vId);

    fetch('sale-events.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('ev-row-' + vId);
            if (row) row.remove();
            if (currentActiveEventId > 0) loadEventDetail(currentActiveEventId);
            Toast.fire({ icon: 'success', title: data.message });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    });
}

function selectVisualIcon(iconClass, btn) {
    document.getElementById('form_icon_val').value = iconClass;
    document.querySelectorAll('.btn-icon-choice').forEach(b => {
        b.className = 'btn btn-sm btn-icon-choice btn-outline-secondary d-flex align-items-center gap-1 rounded-pill px-3 py-1';
    });
    btn.className = 'btn btn-sm btn-icon-choice btn-warning border-dark fw-bold d-flex align-items-center gap-1 rounded-pill px-3 py-1';
}

function updateEventColorPreview(hex) {
    document.getElementById('form_color_hex').value = hex;
}

function previewSelectedImage(input, previewImgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById(previewImgId);
            const placeholder = document.getElementById(previewImgId.replace('_img', '_placeholder'));
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewUrlImage(url, previewImgId) {
    const img = document.getElementById(previewImgId);
    const placeholder = document.getElementById(previewImgId.replace('_img', '_placeholder'));
    url = (url || '').trim();
    if (url) {
        let src = (url.startsWith('http') || url.startsWith('/')) ? url : ('../' + url);
        if (img) {
            img.src = src;
            img.style.display = 'block';
        }
        if (placeholder) placeholder.style.display = 'none';
    } else {
        if (img) img.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
    }
}

// Event Listeners cho Submit Forms
(function initSaleEventsForms() {
    let isSavingEvent = false;

    // 1. Submit Event Form
    const eventForm = document.getElementById('eventAjaxForm');
    if (eventForm && !eventForm.dataset.listenerAttached) {
        eventForm.dataset.listenerAttached = '1';
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isSavingEvent) return; // Chống double-submit bằng cờ
            isSavingEvent = true;

            const btn = document.getElementById('btnSaveEvent');
            const origHtml = btn ? btn.innerHTML : '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Sự Kiện';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(eventForm);

            fetch('sale-events.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                isSavingEvent = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }

                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    // Nạp lại danh sách sự kiện bên trái
                    fetch('sale-events.php')
                    .then(r => r.text())
                    .then(htmlText => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlText, 'text/html');
                        const newContainer = doc.getElementById('eventsListContainer');
                        if (newContainer) {
                            document.getElementById('eventsListContainer').innerHTML = newContainer.innerHTML;
                        }
                        loadEventDetail(data.event_id);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                isSavingEvent = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
            });
        });
    }

    // 2. Submit Add Product Form
    let isAddingProd = false;
    const addProdForm = document.getElementById('addProductEventForm');
    if (addProdForm && !addProdForm.dataset.listenerAttached) {
        addProdForm.dataset.listenerAttached = '1';
        addProdForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isAddingProd) return;
            isAddingProd = true;

            const btn = document.getElementById('btnAddProdSubmit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            }

            const formData = new FormData(addProdForm);
            fetch('sale-events.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                isAddingProd = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Thêm';
                }
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    if (currentActiveEventId > 0) loadEventDetail(currentActiveEventId);
                    addProdForm.reset();
                    document.getElementById('add_prod_event_id').value = currentActiveEventId;
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(() => {
                isAddingProd = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Thêm';
                }
            });
        });
    }

    // 3. Submit Link Voucher Form
    let isLinkingVoucher = false;
    const linkVoucherForm = document.getElementById('linkVoucherEventForm');
    if (linkVoucherForm && !linkVoucherForm.dataset.listenerAttached) {
        linkVoucherForm.dataset.listenerAttached = '1';
        linkVoucherForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isLinkingVoucher) return;
            isLinkingVoucher = true;

            const btn = document.getElementById('btnLinkVoucherSubmit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            }

            const formData = new FormData(linkVoucherForm);
            fetch('sale-events.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                isLinkingVoucher = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-link me-1"></i> Gắn Vào Sự Kiện';
                }
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    if (currentActiveEventId > 0) loadEventDetail(currentActiveEventId);
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(() => {
                isLinkingVoucher = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-link me-1"></i> Gắn Vào Sự Kiện';
                }
            });
        });
    }

    // Khởi tạo load sự kiện đầu tiên
    if (typeof currentActiveEventId !== 'undefined' && currentActiveEventId > 0) {
        loadEventDetail(currentActiveEventId);
    } else if (typeof initNewEventForm === 'function') {
        initNewEventForm();
    }
})();
</script>

<style>
.active-event-item {
    background-color: rgba(234, 179, 8, 0.12) !important;
    border: 2px solid #eab308 !important;
    box-shadow: 0 4px 14px rgba(234, 179, 8, 0.25) !important;
}
.event-item-card {
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
}
.event-item-card:hover {
    border-color: #eab308 !important;
    background-color: rgba(0, 0, 0, 0.015);
}
</style>

<?php include_once 'includes/footer.php'; ?>
