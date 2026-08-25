<?php 
require_once __DIR__ . '/../config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// ========================================================
// 1. CÁC ENDPOINT AJAX (KHÔNG LOAD LẠI TRANG)
// ========================================================

// AJAX 1.1: Lấy sổ địa chỉ của khách hàng
if (isset($_GET['action']) && $_GET['action'] === 'get_addresses') {
    header('Content-Type: application/json; charset=utf-8');
    $cust_id = intval($_GET['cust_id'] ?? 0);
    $data = [];
    if ($cust_id > 0) {
        $sql = "SELECT ua.*, sp.province_name 
                FROM user_addresses ua 
                LEFT JOIN shipping_provinces sp ON ua.province_id = sp.id 
                WHERE ua.user_id = $cust_id 
                ORDER BY ua.is_default DESC, ua.id DESC";
        $res = $conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) { $data[] = $r; }
        }
    }
    echo json_encode($data);
    exit;
}

// AJAX 1.2: Lấy lịch sử hóa đơn & chi tiết đơn hàng của khách hàng
if (isset($_GET['action']) && $_GET['action'] === 'get_orders') {
    header('Content-Type: application/json; charset=utf-8');
    $cust_id = intval($_GET['cust_id'] ?? 0);
    $orders = [];
    if ($cust_id > 0) {
        $sql = "SELECT o.* 
                FROM orders o 
                WHERE o.user_id = $cust_id 
                ORDER BY o.id DESC";
        $res = $conn->query($sql);
        if ($res) {
            while ($ord = $res->fetch_assoc()) {
                $ord_id = intval($ord['id']);
                $items = [];
                $res_items = $conn->query("SELECT od.*, p.main_image 
                                           FROM order_details od 
                                           LEFT JOIN products p ON od.product_id = p.id 
                                           WHERE od.order_id = $ord_id");
                if ($res_items) {
                    while ($it = $res_items->fetch_assoc()) {
                        $items[] = $it;
                    }
                }
                $ord['items'] = $items;
                $orders[] = $ord;
            }
        }
    }
    echo json_encode($orders);
    exit;
}

// AJAX 1.3: Thêm / Sửa hồ sơ khách hàng (ĐẦY ĐỦ RÀNG BUỘC ĐIỀU KIỆN SĐT, EMAIL, CCCD, NGÀY SINH)
if (isset($_POST['ajax_save_customer'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $cust_id    = intval($_POST['customer_id'] ?? 0);
        $fullname   = trim($_POST['fullname'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = preg_replace('/[\s\-\.]/', '', trim($_POST['phone'] ?? ''));
        $citizen_id = preg_replace('/[\s\-\.]/', '', trim($_POST['citizen_id'] ?? ''));
        $address    = trim($_POST['address'] ?? '');
        $birthday   = trim($_POST['birthday'] ?? '');
        $password   = trim($_POST['password'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        $avatar     = '';

        // 1. KIỂM TRA HỌ TÊN
        if (empty($fullname)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Họ và Tên khách hàng!']);
            exit;
        }
        if (mb_strlen($fullname, 'UTF-8') < 2 || mb_strlen($fullname, 'UTF-8') > 100) {
            echo json_encode(['success' => false, 'message' => 'Họ và tên phải từ 2 đến 100 ký tự!']);
            exit;
        }

        // 2. KIỂM TRA ĐỊNH DẠNG EMAIL
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập địa chỉ Email!']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Địa chỉ Email không đúng định dạng (Ví dụ: tenban@gmail.com)!']);
            exit;
        }
        // Chống trùng Email
        $stmt_chk_e = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if ($stmt_chk_e) {
            $stmt_chk_e->bind_param("si", $email, $cust_id);
            $stmt_chk_e->execute();
            if ($stmt_chk_e->get_result()->num_rows > 0) {
                $stmt_chk_e->close();
                echo json_encode(['success' => false, 'message' => 'Email này đã được sử dụng bởi một tài khoản khác trong hệ thống!']);
                exit;
            }
            $stmt_chk_e->close();
        }

        // 3. KIỂM TRA ĐỊNH DẠNG SỐ ĐIỆN THOẠI
        if (!empty($phone)) {
            // Chuẩn hóa đầu số quốc tế +84 hoặc 84 về 0
            if (strpos($phone, '+84') === 0) {
                $phone = '0' . substr($phone, 3);
            } elseif (strpos($phone, '84') === 0 && strlen($phone) === 11) {
                $phone = '0' . substr($phone, 2);
            }

            // Kiểm tra đúng 10 chữ số di động Việt Nam (03, 05, 07, 08, 09)
            if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
                echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ! (Phải là số di động VN gồm đúng 10 chữ số, bắt đầu bằng 03, 05, 07, 08 hoặc 09)']);
                exit;
            }

            // Chống trùng Số điện thoại
            $stmt_chk_p = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            if ($stmt_chk_p) {
                $stmt_chk_p->bind_param("si", $phone, $cust_id);
                $stmt_chk_p->execute();
                if ($stmt_chk_p->get_result()->num_rows > 0) {
                    $stmt_chk_p->close();
                    echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã được đăng ký cho tài khoản khác!']);
                    exit;
                }
                $stmt_chk_p->close();
            }
        }

        // 4. KIỂM TRA ĐỊNH DẠNG SỐ CCCD / CMND
        if (!empty($citizen_id)) {
            if (!preg_match('/^[0-9]{9}$|^[0-9]{12}$/', $citizen_id)) {
                echo json_encode(['success' => false, 'message' => 'Số CCCD/CMND không hợp lệ! (Phải gồm đúng 9 chữ số CMND hoặc 12 chữ số CCCD, không chứa chữ cái hay ký tự lạ)']);
                exit;
            }

            $stmt_chk_c = $conn->prepare("SELECT id FROM users WHERE citizen_id = ? AND id != ?");
            if ($stmt_chk_c) {
                $stmt_chk_c->bind_param("si", $citizen_id, $cust_id);
                $stmt_chk_c->execute();
                if ($stmt_chk_c->get_result()->num_rows > 0) {
                    $stmt_chk_c->close();
                    echo json_encode(['success' => false, 'message' => 'Số CCCD/CMND này đã tồn tại trên hệ thống!']);
                    exit;
                }
                $stmt_chk_c->close();
            }
        }

        // 5. KIỂM TRA ĐIỀU KIỆN NGÀY SINH
        $birthday_clean = null;
        if (!empty($birthday)) {
            $b_ts = strtotime($birthday);
            $now_ts = time();
            if (!$b_ts) {
                echo json_encode(['success' => false, 'message' => 'Ngày sinh không hợp lệ!']);
                exit;
            }
            if ($b_ts > $now_ts) {
                echo json_encode(['success' => false, 'message' => 'Ngày sinh không thể lớn hơn ngày hiện tại!']);
                exit;
            }
            $birth_year = intval(date('Y', $b_ts));
            if ($birth_year < 1920) {
                echo json_encode(['success' => false, 'message' => 'Năm sinh không hợp lệ (Không được trước năm 1920)!']);
                exit;
            }
            $min_age_ts = strtotime('-6 years');
            if ($b_ts > $min_age_ts) {
                echo json_encode(['success' => false, 'message' => 'Khách hàng phải từ 6 tuổi trở lên!']);
                exit;
            }
            $birthday_clean = date('Y-m-d', $b_ts);
        }

        // Xử lý upload avatar nếu có
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
            $clean_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES["avatar_file"]["name"]));
            $file_name = time() . '_cust_' . $clean_name;
            $target_file = $upload_dir . $file_name;
            if (@move_uploaded_file($_FILES["avatar_file"]["tmp_name"], $target_file)) {
                $avatar = 'uploads/' . $file_name;
            }
        }
        if (empty($avatar) && !empty($avatar_url)) {
            $avatar = $avatar_url;
        }

        // Chuẩn hóa dữ liệu rỗng thành NULL
        $phone_val      = !empty($phone) ? $phone : null;
        $citizen_id_val = !empty($citizen_id) ? $citizen_id : null;
        $address_val    = !empty($address) ? $address : null;
        $birthday_val   = $birthday_clean;

        if ($cust_id > 0) {
            // SỬA HỒ SƠ KHÁCH HÀNG
            if (!empty($password)) {
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                if (!empty($avatar)) {
                    $stmt_up = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, citizen_id = ?, address = ?, birthday = ?, password = ?, avatar = ? WHERE id = ?");
                    $stmt_up->bind_param("ssssssssi", $fullname, $email, $phone_val, $citizen_id_val, $address_val, $birthday_val, $pass_hash, $avatar, $cust_id);
                } else {
                    $stmt_up = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, citizen_id = ?, address = ?, birthday = ?, password = ? WHERE id = ?");
                    $stmt_up->bind_param("sssssssi", $fullname, $email, $phone_val, $citizen_id_val, $address_val, $birthday_val, $pass_hash, $cust_id);
                }
            } else {
                if (!empty($avatar)) {
                    $stmt_up = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, citizen_id = ?, address = ?, birthday = ?, avatar = ? WHERE id = ?");
                    $stmt_up->bind_param("sssssssi", $fullname, $email, $phone_val, $citizen_id_val, $address_val, $birthday_val, $avatar, $cust_id);
                } else {
                    $stmt_up = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, citizen_id = ?, address = ?, birthday = ? WHERE id = ?");
                    $stmt_up->bind_param("ssssssi", $fullname, $email, $phone_val, $citizen_id_val, $address_val, $birthday_val, $cust_id);
                }
            }

            if ($stmt_up && $stmt_up->execute()) {
                $stmt_up->close();
                echo json_encode(['success' => true, 'message' => "Đã cập nhật hồ sơ khách hàng $fullname thành công!"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . ($stmt_up ? $stmt_up->error : $conn->error)]);
            }
        } else {
            // THÊM KHÁCH HÀNG MỚI (Mật khẩu mặc định là 123456)
            $pass_to_use = !empty($password) ? $password : '123456';
            $pass_hash = password_hash($pass_to_use, PASSWORD_DEFAULT);
            if (empty($avatar)) { $avatar = 'assets/images/default-avatar.png'; }

            $stmt_in = $conn->prepare("INSERT INTO users (fullname, email, password, phone, citizen_id, address, birthday, avatar, auth_provider, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'local', 'customer', 1)");
            if ($stmt_in) {
                $stmt_in->bind_param("ssssssss", $fullname, $email, $pass_hash, $phone_val, $citizen_id_val, $address_val, $birthday_val, $avatar);
                if ($stmt_in->execute()) {
                    $new_id = $conn->insert_id;
                    $stmt_in->close();
                    echo json_encode(['success' => true, 'message' => "Đã tạo tài khoản khách hàng $fullname (Mật khẩu: $pass_to_use) thành công!"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi thêm mới CSDL: ' . $stmt_in->error]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị CSDL: ' . $conn->error]);
            }
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    exit;
}

// AJAX 1.4: Khóa / Mở khóa tài khoản khách hàng
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $cust_id = intval($_POST['customer_id'] ?? 0);
    if ($cust_id > 0) {
        $res_st = $conn->query("SELECT status, fullname FROM users WHERE id = $cust_id AND role = 'customer'");
        if ($res_st && $st_user = $res_st->fetch_assoc()) {
            $new_status = ($st_user['status'] == 1) ? 0 : 1;
            $conn->query("UPDATE users SET status = $new_status WHERE id = $cust_id");
            $st_label = ($new_status == 1) ? "Kích hoạt (Mở khóa)" : "Đã khóa";
            echo json_encode([
                'success'    => true, 
                'new_status' => $new_status,
                'message'    => "Đã chuyển tài khoản " . htmlspecialchars($st_user['fullname']) . " sang: $st_label!"
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng.']);
    exit;
}

// AJAX 1.5: Xóa khách hàng
if (isset($_POST['ajax_delete_customer'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Admin mới có quyền xóa khách hàng!']);
        exit;
    }
    $del_id = intval($_POST['customer_id'] ?? 0);
    if ($del_id > 0) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM user_addresses WHERE user_id = $del_id");
            $conn->query("DELETE FROM wishlists WHERE user_id = $del_id");
            $conn->query("DELETE FROM cart_items WHERE user_id = $del_id");
            $conn->query("DELETE FROM users WHERE id = $del_id AND role = 'customer'");
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xóa khách hàng thành công!']);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mã khách hàng không hợp lệ.']);
    }
    exit;
}

include_once 'includes/header.php'; 

// ========================================================
// 2. BỘ LỌC, SẮP XẾP & TÌM KIẾM
// ========================================================
$search_query = isset($_GET['search']) ? addslashes(trim($_GET['search'])) : '';
$sort_order   = strtolower($_GET['sort'] ?? 'name_asc');

$where_clauses = ["u.role = 'customer'"];
if ($search_query !== '') {
    $where_clauses[] = "(u.fullname LIKE '%$search_query%' OR u.email LIKE '%$search_query%' OR u.phone LIKE '%$search_query%' OR u.citizen_id LIKE '%$search_query%')";
}
$where_sql = implode(' AND ', $where_clauses);

// Xử lý sắp xếp bảng chữ cái
$order_by = "u.fullname ASC";
if ($sort_order === 'name_desc') {
    $order_by = "u.fullname DESC";
} elseif ($sort_order === 'spent_desc') {
    $order_by = "total_spent DESC";
} elseif ($sort_order === 'newest') {
    $order_by = "u.id DESC";
}

$sql_cust = "SELECT u.*, 
            COALESCE((SELECT COUNT(o.id) FROM orders o WHERE o.user_id = u.id AND o.status = 'pending'), 0) AS pending_orders,
            COALESCE((SELECT COUNT(o.id) FROM orders o WHERE o.user_id = u.id AND o.status IN ('confirmed', 'shipping')), 0) AS active_orders,
            COALESCE((SELECT COUNT(o.id) FROM orders o WHERE o.user_id = u.id AND o.status = 'completed'), 0) AS completed_orders,
            COALESCE((SELECT COUNT(o.id) FROM orders o WHERE o.user_id = u.id AND o.status = 'cancelled'), 0) AS cancelled_orders,
            COALESCE((SELECT SUM(o.total_money) FROM orders o WHERE o.user_id = u.id AND o.status = 'completed'), 0) AS total_spent
            FROM users u 
            WHERE $where_sql
            ORDER BY $order_by";
$customers_res = $conn->query($sql_cust);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Tối ưu hóa bảng để vừa vặn màn hình chuẩn quản trị */
.table-compact th, .table-compact td {
    padding: 0.65rem 0.75rem !important;
    font-size: 0.88rem;
}
.btn-action-group .btn {
    padding: 0.28rem 0.55rem;
    font-size: 0.8rem;
    border-radius: 6px;
}
.stat-pill {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
}
</style>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-user-group text-success me-2"></i>Quản Lý Khách Hàng & Đơn Hàng
        </h4>
        <span class="text-muted small">Kiểm tra hợp lệ SĐT, Email, CCCD, Ngày sinh, sổ địa chỉ và lịch sử đơn hàng.</span>
    </div>
    <button type="button" class="btn btn-success fw-bold rounded-pill px-3 shadow-sm" onclick="openAddCustomerModal()">
        <i class="fa-solid fa-user-plus me-1"></i> + Thêm Khách Hàng Mới
    </button>
</div>

<!-- BỘ LỌC TÌM KIẾM VÀ SẮP XẾP BẢNG CHỮ CÁI -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
    <form method="GET" class="row g-2 align-items-end" id="filterForm">
        <div class="col-12 col-md-5">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Tìm kiếm khách hàng</label>
            <input type="text" name="search" id="searchInput" class="form-control form-control-sm" placeholder="Tìm theo tên, email, SĐT, số CCCD..." value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-solid fa-arrow-down-a-z me-1 text-success"></i>Sắp xếp danh sách</label>
            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="name_asc" <?= $sort_order === 'name_asc' ? 'selected' : '' ?>>🔤 Tên A ➔ Z (Bảng chữ cái)</option>
                <option value="name_desc" <?= $sort_order === 'name_desc' ? 'selected' : '' ?>>🔤 Tên Z ➔ A (Ngược bảng chữ cái)</option>
                <option value="spent_desc" <?= $sort_order === 'spent_desc' ? 'selected' : '' ?>>💰 Chi tiêu nhiều nhất</option>
                <option value="newest" <?= $sort_order === 'newest' ? 'selected' : '' ?>>🕒 Khách mới nhất</option>
            </select>
        </div>

        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm fw-bold flex-grow-1"><i class="fa-solid fa-filter me-1"></i>Lọc</button>
            <a href="customers.php" class="btn btn-outline-secondary btn-sm fw-bold" title="Đặt lại bộ lọc"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- BẢNG DANH SÁCH KHÁCH HÀNG (VỪA VẶN MÀN HÌNH) -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-5">
    <div class="table-responsive">
        <table class="table table-hover table-compact align-middle mb-0" id="customersTable">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th style="width: 45px;">Ảnh</th>
                    <th>Họ Tên & Ngày Sinh</th>
                    <th>Email & SĐT</th>
                    <th>Số CCCD</th>
                    <th>Đăng Nhập</th>
                    <th>Trạng Thái</th>
                    <th>Đơn Hàng</th>
                    <th>Chi Tiêu</th>
                    <th class="text-end" style="width: 150px;">Thao Tác</th>
                </tr>
            </thead>
            <tbody id="customersTableBody">
                <?php if ($customers_res && $customers_res->num_rows > 0): ?>
                    <?php while($c = $customers_res->fetch_assoc()): ?>
                        <?php 
                        $cust_id = intval($c['id']);
                        $avatar_src = (!empty($c['avatar']) && strpos($c['avatar'], 'http') === 0) ? $c['avatar'] : (!empty($c['avatar']) ? '../' . $c['avatar'] : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=300');
                        $c_status = isset($c['status']) ? intval($c['status']) : 1;
                        $birthday_fmt = !empty($c['birthday']) ? date('d/m/Y', strtotime($c['birthday'])) : 'Chưa nhập';
                        ?>
                        <tr id="cust-row-<?= $cust_id ?>" class="<?= $c_status === 0 ? 'table-secondary opacity-75' : ''; ?>">
                            <td class="text-center">
                                <img src="<?= $avatar_src; ?>" class="rounded-circle border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=300'">
                            </td>
                            <td>
                                <strong class="text-dark d-block" id="cust-name-<?= $cust_id ?>"><?= htmlspecialchars($c['fullname']); ?></strong>
                                <small class="text-muted"><i class="fa-solid fa-cake-candles me-1 text-warning"></i><?= $birthday_fmt; ?></small>
                            </td>
                            <td>
                                <span class="d-block text-dark fw-bold small"><i class="fa-solid fa-envelope me-1 text-secondary"></i><?= htmlspecialchars($c['email']); ?></span>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($c['phone'] ?? 'Chưa có SĐT'); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['citizen_id'] ?? '---'); ?></span>
                            </td>
                            <td>
                                <?php if ($c['auth_provider'] == 'google'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-brands fa-google me-1"></i>Google</span>
                                <?php elseif ($c['auth_provider'] == 'email_otp'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info"><i class="fa-solid fa-envelope me-1"></i>OTP</span>
                                <?php else: ?>
                                    <span class="badge bg-dark text-white"><i class="fa-solid fa-key me-1"></i>Mật khẩu</span>
                                <?php endif; ?>
                            </td>
                            <td id="status-cell-<?= $cust_id ?>">
                                <?php if ($c_status === 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                        <i class="fa-solid fa-user-lock me-1"></i> Đã khóa
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="stat-pill bg-warning-subtle text-dark border border-warning" title="Chờ xác nhận">⏳ <?= $c['pending_orders']; ?></span>
                                    <span class="stat-pill bg-info-subtle text-dark border border-info" title="Đang giao">🚚 <?= $c['active_orders']; ?></span>
                                    <span class="stat-pill bg-success-subtle text-success border border-success" title="Đã mua">✅ <?= $c['completed_orders']; ?></span>
                                    <span class="stat-pill bg-danger-subtle text-danger border border-danger" title="Đã hủy">❌ <?= $c['cancelled_orders']; ?></span>
                                </div>
                            </td>
                            <td>
                                <strong class="text-danger"><?= number_format($c['total_spent'], 0, ',', '.'); ?>đ</strong>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="btn-group btn-action-group shadow-sm">
                                    <!-- NÚT XEM LỊCH SỬ ĐƠN HÀNG CHI TIẾT -->
                                    <button type="button" class="btn btn-outline-success" onclick="viewCustomerOrders(<?= $cust_id ?>, '<?= htmlspecialchars(addslashes($c['fullname']), ENT_QUOTES) ?>')" title="Xem hóa đơn">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </button>
                                    
                                    <!-- NÚT SỬA HỒ SƠ & ĐỊA CHỈ -->
                                    <button type="button" class="btn btn-outline-primary" onclick="openEditCustomerModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8'); ?>)" title="Chỉnh sửa">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </button>

                                    <!-- NÚT KHÓA / MỞ KHÓA TÀI KHOẢN AJAX -->
                                    <button type="button" id="btn-toggle-<?= $cust_id ?>" class="btn <?= $c_status === 1 ? 'btn-outline-warning' : 'btn-outline-success'; ?>" onclick="toggleCustomerStatus(<?= $cust_id ?>)" title="<?= $c_status === 1 ? 'Khóa tài khoản' : 'Mở khóa'; ?>">
                                        <i class="fa-solid <?= $c_status === 1 ? 'fa-user-lock' : 'fa-user-check'; ?>"></i>
                                    </button>

                                    <?php if ($user_role === 'admin'): ?>
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteCustomerAjax(<?= $cust_id ?>, '<?= htmlspecialchars(addslashes($c['fullname']), ENT_QUOTES) ?>')" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">Không tìm thấy khách hàng nào phù hợp.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 1: CHỈNH SỬA & THÊM KHÁCH HÀNG (LIVE AJAX & MAPS) -->
<!-- ======================================================== -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="custModalTitle">Thông Tin Khách Hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax_save_customer" value="1">
                <input type="hidden" name="customer_id" id="form_customer_id" value="0">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Ảnh đại diện -->
                        <div class="col-12 col-md-4 text-center border-end pe-3">
                            <label class="form-label fw-bold d-block">Ảnh Đại Diện</label>
                            <img id="preview_cust_avatar" src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=300" class="rounded-circle border shadow-sm mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                            <input type="file" name="avatar_file" class="form-control form-control-sm mb-2" accept="image/*">
                            <input type="text" name="avatar_url" id="form_cust_avatar_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh...">
                            
                            <!-- Cách thức đăng nhập (Chỉ xem, không sửa) -->
                            <div class="mt-3 text-start bg-light p-2 rounded-3 border">
                                <label class="form-label small fw-bold text-muted mb-1 d-block"><i class="fa-solid fa-shield-halved me-1"></i>Phương thức đăng nhập:</label>
                                <div id="display_auth_provider">
                                    <span class="badge bg-dark text-white">🔒 Mật khẩu (Local)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin cá nhân -->
                        <div class="col-12 col-md-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Họ và Tên <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" id="form_cust_fullname" class="form-control fw-bold" placeholder="VD: Nguyễn Văn A" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Ngày Sinh</label>
                                    <input type="date" name="birthday" id="form_cust_birthday" class="form-control" min="1920-01-01" max="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Email Đăng Nhập <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="form_cust_email" class="form-control" placeholder="VD: email@gmail.com" required>
                                    <small class="text-muted" style="font-size: 11px;">Dùng đăng nhập hệ thống</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Số Điện Thoại</label>
                                    <input type="tel" name="phone" id="form_cust_phone" class="form-control" placeholder="VD: 0912345678" maxlength="11">
                                    <small class="text-muted" style="font-size: 11px;">10 chữ số (03, 05, 07, 08, 09)</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Số CCCD / CMND</label>
                                    <input type="text" name="citizen_id" id="form_cust_citizen_id" class="form-control" placeholder="VD: 038099012345" maxlength="12">
                                    <small class="text-muted" style="font-size: 11px;">Đúng 9 hoặc 12 số</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-danger">Mật Khẩu</label>
                                    <input type="text" name="password" id="form_cust_password" class="form-control" placeholder="Mặc định: 123456">
                                    <small class="text-muted" style="font-size: 11px;">Mặc định 123456 nếu để trống</small>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold small text-dark mb-0"><i class="fa-solid fa-house me-1 text-primary"></i>Địa Chỉ Thường Trú</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold py-0 px-2" onclick="openMapPicker('form_cust_address')">
                                            <i class="fa-solid fa-map-location-dot me-1"></i> 🗺️ Chọn trên bản đồ
                                        </button>
                                    </div>
                                    <input type="text" name="address" id="form_cust_address" class="form-control" placeholder="Số nhà, tên đường, phường/xã...">
                                </div>
                            </div>
                        </div>

                        <!-- DANH SÁCH SỔ ĐỊA CHỈ ĐÃ LƯU CỦA KHÁCH HÀNG -->
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-dark"><i class="fa-solid fa-map-location-dot text-primary me-2"></i>Danh Sách Sổ Địa Chỉ Giao Hàng</h6>
                            <div id="customer_addresses_container" class="row g-3">
                                <!-- JS tự động nạp danh sách địa chỉ -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" id="btnSubmitCust" class="btn btn-success fw-bold px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> LƯU HỒ SƠ KHÁCH HÀNG
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: XEM HÓA ĐƠN & CHI TIẾT SẢN PHẨM ĐÃ MUA -->
<!-- ======================================================== -->
<div class="modal fade" id="ordersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="ordersModalTitle"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Lịch Sử Hóa Đơn Khách Hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Mã Đơn & Ngày Đặt</th>
                                <th>Địa Chỉ Giao</th>
                                <th>Chi Tiết Sản Phẩm</th>
                                <th>Thanh Toán</th>
                                <th>Trạng Thái</th>
                                <th class="text-end">Tổng Tiền</th>
                            </tr>
                        </thead>
                        <tbody id="ordersModalBody">
                            <!-- JS nạp chi tiết đơn hàng -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toast notification helper (tự động biến mất sau 1.8 giây)
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

window.custModal = window.custModal || null;
window.ordersModal = window.ordersModal || null;

function initCustomersPage() {
    const custModalEl = document.getElementById('customerModal');
    if (custModalEl) window.custModal = bootstrap.Modal.getInstance(custModalEl) || new bootstrap.Modal(custModalEl);

    const ordersModalEl = document.getElementById('ordersModal');
    if (ordersModalEl) window.ordersModal = bootstrap.Modal.getInstance(ordersModalEl) || new bootstrap.Modal(ordersModalEl);

    const form = document.getElementById('customerForm');
    if (form && !form.dataset.boundSubmit) {
        form.dataset.boundSubmit = 'true';
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Client-side quick validation
            const email = document.getElementById('form_cust_email').value.trim();
            const phone = document.getElementById('form_cust_phone').value.trim().replace(/[\s\-\.]/g, '');
            const cccd  = document.getElementById('form_cust_citizen_id').value.trim().replace(/[\s\-\.]/g, '');
            const bday  = document.getElementById('form_cust_birthday').value.trim();

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire({ icon: 'warning', title: 'Email không hợp lệ', text: 'Vui lòng nhập đúng định dạng email (VD: name@gmail.com)' });
                return;
            }

            if (phone !== '') {
                let normalizedPhone = phone;
                if (phone.startsWith('+84')) normalizedPhone = '0' + phone.substring(3);
                else if (phone.startsWith('84') && phone.length === 11) normalizedPhone = '0' + phone.substring(2);

                const phoneRegex = /^(03|05|07|08|09)[0-9]{8}$/;
                if (!phoneRegex.test(normalizedPhone)) {
                    Swal.fire({ icon: 'warning', title: 'Số điện thoại không hợp lệ', text: 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 03, 05, 07, 08 hoặc 09.' });
                    return;
                }
            }

            if (cccd !== '') {
                const cccdRegex = /^[0-9]{9}$|^[0-9]{12}$/;
                if (!cccdRegex.test(cccd)) {
                    Swal.fire({ icon: 'warning', title: 'Số CCCD/CMND không hợp lệ', text: 'Số CCCD/CMND phải gồm đúng 9 số (CMND) hoặc 12 số (CCCD), chỉ gồm các chữ số.' });
                    return;
                }
            }

            if (bday !== '') {
                const birthDate = new Date(bday);
                const today = new Date();
                if (birthDate > today) {
                    Swal.fire({ icon: 'warning', title: 'Ngày sinh không hợp lệ', text: 'Ngày sinh không thể lớn hơn ngày hôm nay.' });
                    return;
                }
                if (birthDate.getFullYear() < 1920) {
                    Swal.fire({ icon: 'warning', title: 'Năm sinh không hợp lệ', text: 'Năm sinh không được trước năm 1920.' });
                    return;
                }
            }

            const btn = document.getElementById('btnSubmitCust');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';

            const formData = new FormData(form);

            fetch('customers.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error("Server raw response:", text);
                    return { success: false, message: 'Phản hồi từ máy chủ: ' + text.replace(/<[^>]*>?/gm, '').trim().substring(0, 200) };
                }
            })
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU HỒ SƠ KHÁCH HÀNG';
                
                if (data.success) {
                    custModal.hide();
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    // Tải lại bảng sau 800ms
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể lưu',
                        text: data.message || 'Có lỗi xảy ra, vui lòng kiểm tra lại.'
                    });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> LƯU HỒ SƠ KHÁCH HÀNG';
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    text: err.message || 'Không thể kết nối máy chủ.'
                });
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomersPage);
} else {
    initCustomersPage();
}

// Mở modal Thêm Khách Hàng mới
function openAddCustomerModal() {
    document.getElementById('custModalTitle').innerText = 'Tạo Tài Khoản Khách Hàng Mới';
    document.getElementById('form_customer_id').value = '0';
    document.getElementById('preview_cust_avatar').src = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=300';
    document.getElementById('form_cust_fullname').value = '';
    document.getElementById('form_cust_birthday').value = '';
    document.getElementById('form_cust_citizen_id').value = '';
    document.getElementById('form_cust_email').value = '';
    document.getElementById('form_cust_phone').value = '';
    document.getElementById('form_cust_password').value = '';
    document.getElementById('form_cust_password').placeholder = 'Mặc định: 123456';
    document.getElementById('form_cust_address').value = '';
    document.getElementById('form_cust_avatar_url').value = '';
    document.getElementById('display_auth_provider').innerHTML = '<span class="badge bg-dark text-white"><i class="fa-solid fa-key me-1"></i>Mật khẩu (Local)</span>';
    document.getElementById('customer_addresses_container').innerHTML = '<div class="col-12 text-muted small fst-italic">Khách hàng mới chưa có sổ địa chỉ.</div>';

    custModal.show();
}

// Mở modal Sửa Khách Hàng
function openEditCustomerModal(c) {
    document.getElementById('custModalTitle').innerText = 'Chỉnh Sửa Khách Hàng: ' + c.fullname;
    document.getElementById('form_customer_id').value = c.id;
    var avatarSrc = (c.avatar && c.avatar.indexOf('http') === 0) ? c.avatar : (!empty(c.avatar) ? '../' + c.avatar : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=300');
    document.getElementById('preview_cust_avatar').src = avatarSrc;

    document.getElementById('form_cust_fullname').value = c.fullname || '';
    document.getElementById('form_cust_birthday').value = c.birthday || '';
    document.getElementById('form_cust_citizen_id').value = c.citizen_id || '';
    document.getElementById('form_cust_email').value = c.email || '';
    document.getElementById('form_cust_phone').value = c.phone || '';
    document.getElementById('form_cust_password').value = '';
    document.getElementById('form_cust_password').placeholder = 'Bỏ trống nếu giữ nguyên mật khẩu cũ';
    document.getElementById('form_cust_address').value = c.address || '';
    document.getElementById('form_cust_avatar_url').value = c.avatar || '';

    // Hiển thị cách thức đăng nhập (Read-only)
    var authHtml = '<span class="badge bg-dark text-white"><i class="fa-solid fa-key me-1"></i>Mật khẩu (Local)</span>';
    if (c.auth_provider === 'google') {
        authHtml = '<span class="badge bg-danger text-white"><i class="fa-brands fa-google me-1"></i>Google OAuth</span>';
    } else if (c.auth_provider === 'email_otp') {
        authHtml = '<span class="badge bg-info text-dark"><i class="fa-solid fa-envelope me-1"></i>Email OTP</span>';
    }
    document.getElementById('display_auth_provider').innerHTML = authHtml;

    // Tải sổ địa chỉ qua AJAX
    var addrContainer = document.getElementById('customer_addresses_container');
    addrContainer.innerHTML = '<div class="col-12 text-center small text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Đang tải sổ địa chỉ...</div>';
    
    fetch('customers.php?action=get_addresses&cust_id=' + c.id)
        .then(res => res.json())
        .then(addresses => {
            addrContainer.innerHTML = '';
            if (addresses && addresses.length > 0) {
                addresses.forEach(function(addr) {
                    var defaultBadge = addr.is_default == 1 ? '<span class="badge bg-success ms-1">Mặc định</span>' : '';
                    var phone = addr.phone || 'Chưa có SĐT';
                    var province = addr.province_name || '';
                    var html = `
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm bg-light">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-1 text-dark">${addr.recipient_name} ${defaultBadge}</h6>
                                <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-1 text-success"></i>${phone}</div>
                                ${province ? `<div class="small text-muted mb-1"><i class="fa-solid fa-map me-1 text-primary"></i>${province}</div>` : ''}
                                <div class="small text-dark"><i class="fa-solid fa-location-dot me-1 text-danger"></i>${addr.address_detail}</div>
                            </div>
                        </div>
                    </div>`;
                    addrContainer.innerHTML += html;
                });
            } else {
                addrContainer.innerHTML = '<div class="col-12 text-muted small fst-italic">Khách hàng chưa lưu sổ địa chỉ phụ nào.</div>';
            }
        })
        .catch(err => {
            addrContainer.innerHTML = '<div class="col-12 text-danger small fst-italic">Lỗi tải sổ địa chỉ.</div>';
        });

    custModal.show();
}

// Mở modal Xem Lịch Sử Hóa Đơn & Chi Tiết Sản Phẩm
function viewCustomerOrders(custId, custName) {
    document.getElementById('ordersModalTitle').innerHTML = '<i class="fa-solid fa-file-invoice-dollar me-2"></i>Lịch Sử Hóa Đơn: ' + custName;
    const tbody = document.getElementById('ordersModalBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Đang nạp hóa đơn...</td></tr>';
    
    ordersModal.show();

    fetch('customers.php?action=get_orders&cust_id=' + custId)
        .then(res => res.json())
        .then(orders => {
            tbody.innerHTML = '';
            if (orders && orders.length > 0) {
                orders.forEach(function(ord) {
                    let statusBadge = '';
                    if (ord.status === 'pending') statusBadge = '<span class="badge bg-warning text-dark">⏳ Chờ xác nhận</span>';
                    else if (ord.status === 'confirmed') statusBadge = '<span class="badge bg-info text-dark">⚙️ Đã xác nhận</span>';
                    else if (ord.status === 'shipping') statusBadge = '<span class="badge bg-primary">🚚 Đang giao</span>';
                    else if (ord.status === 'completed') statusBadge = '<span class="badge bg-success">✅ Đã giao hàng</span>';
                    else if (ord.status === 'returning') statusBadge = '<span class="badge bg-danger text-white">🔄 Hoàn trả</span>';
                    else statusBadge = '<span class="badge bg-danger-subtle text-danger">❌ Đã hủy</span>';

                    let paymentBadge = (ord.payment_status === 'paid' || ord.status === 'completed' || ord.payment_method === 'BANKING_QR') 
                        ? '<span class="badge bg-success-subtle text-success border border-success">✅ Đã TT</span>'
                        : (ord.payment_status === 'refunded' ? '<span class="badge bg-danger-subtle text-danger border border-danger">↩️ Hoàn tiền</span>' : '<span class="badge bg-warning-subtle text-dark border border-warning">⏳ Chưa TT</span>');

                    let itemsHtml = '';
                    if (ord.items && ord.items.length > 0) {
                        itemsHtml = '<div class="d-flex flex-column gap-1">';
                        ord.items.forEach(function(it) {
                            let img = (it.product_image && it.product_image.indexOf('http') === 0) ? it.product_image : (it.main_image && it.main_image.indexOf('http') === 0 ? it.main_image : '../' + (it.product_image || it.main_image || 'assets/images/default-shoe.png'));
                            itemsHtml += `
                            <div class="d-flex align-items-center gap-2 py-1 border-bottom border-light">
                                <img src="${img}" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <strong class="small text-dark d-block">${it.product_name}</strong>
                                    <small class="text-muted">Size: <b>EU ${it.size}</b> | SL: <b>${it.quantity}</b> | Giá: <b>${new Intl.NumberFormat('vi-VN').format(it.price)}đ</b></small>
                                </div>
                            </div>`;
                        });
                        itemsHtml += '</div>';
                    } else {
                        itemsHtml = '<i class="text-muted small">Không có chi tiết</i>';
                    }

                    let row = `
                    <tr>
                        <td>
                            <strong class="text-primary fs-6">#${ord.order_code || ord.id}</strong>
                            <small class="d-block text-muted mt-1"><i class="fa-solid fa-clock me-1"></i>${ord.created_at}</small>
                        </td>
                        <td>
                            <strong class="text-dark d-block small">${ord.customer_name || custName}</strong>
                            <small class="text-muted d-block">${ord.phone || ''}</small>
                            <small class="text-muted d-block" style="max-width: 180px;">${ord.address_detail || ''}</small>
                        </td>
                        <td>${itemsHtml}</td>
                        <td>
                            ${paymentBadge}
                            <small class="d-block text-muted mt-1" style="font-size: 11px;">${ord.payment_method === 'BANKING_QR' ? '⚡ VietQR' : '💵 COD'}</small>
                        </td>
                        <td>${statusBadge}</td>
                        <td class="text-end">
                            <strong class="text-danger fs-6">${new Intl.NumberFormat('vi-VN').format(ord.total_money)}đ</strong>
                            ${['confirmed', 'shipping', 'completed'].includes(ord.status) ? `<a href="print-invoice.php?id=${ord.id}" class="btn btn-outline-dark btn-sm rounded-pill d-block mt-1"><i class="fa-solid fa-print me-1"></i>In Phiếu</a>` : ''}
                        </td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Khách hàng này chưa có đơn hàng nào.</td></tr>';
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Lỗi tải hóa đơn.</td></tr>';
        });
}

// Khóa / Mở khóa tài khoản khách hàng qua AJAX
function toggleCustomerStatus(custId) {
    const btn = document.getElementById('btn-toggle-' + custId);
    btn.disabled = true;

    const formData = new FormData();
    formData.append('ajax_toggle_status', '1');
    formData.append('customer_id', custId);

    fetch('customers.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            const statusCell = document.getElementById('status-cell-' + custId);
            const row = document.getElementById('cust-row-' + custId);
            
            if (data.new_status === 1) {
                statusCell.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Hoạt động</span>';
                btn.className = 'btn btn-outline-warning';
                btn.innerHTML = '<i class="fa-solid fa-user-lock"></i>';
                btn.title = 'Khóa tài khoản này';
                row.classList.remove('table-secondary', 'opacity-75');
            } else {
                statusCell.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-user-lock me-1"></i> Đã khóa</span>';
                btn.className = 'btn btn-outline-success';
                btn.innerHTML = '<i class="fa-solid fa-user-check"></i>';
                btn.title = 'Mở khóa tài khoản này';
                row.classList.add('table-secondary', 'opacity-75');
            }

            Toast.fire({
                icon: 'success',
                title: data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: data.message || 'Không thể đổi trạng thái.'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        console.error(err);
    });
}

// Xóa khách hàng qua AJAX
function deleteCustomerAjax(custId, custName) {
    Swal.fire({
        title: 'Xác nhận xóa khách hàng?',
        html: `Bạn có chắc chắn muốn xóa tài khoản <b>${custName}</b>?<br><small class="text-danger">Toàn bộ sổ địa chỉ và dữ liệu tài khoản sẽ bị xóa vĩnh viễn.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Ngay',
        cancelButtonText: 'Hủy',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_customer', '1');
            formData.append('customer_id', custId);

            fetch('customers.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('cust-row-' + custId);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(50px)';
                        setTimeout(() => {
                            row.remove();
                        }, 400);
                    }
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể xóa',
                        text: data.message || 'Có lỗi xảy ra.'
                    });
                }
            })
            .catch(err => {
                console.error(err);
            });
        }
    });
}

function empty(val) {
    return !val || val === '' || val === 'null' || val === undefined;
}
</script>

<?php require_once __DIR__ . '/../includes/map-picker-modal.php'; ?>
    </div>
</div>
</body>
</html>