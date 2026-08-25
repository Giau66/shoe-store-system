<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    if (!empty($_POST) || isset($_GET['ajax_get_user_detail'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản trị!']);
        exit();
    }
    header('Location: ../login.php');
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 1. AJAX: THÊM MỚI / SỬA TÀI KHOẢN (LIVE AJAX)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_save_user'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $user_id    = intval($_POST['user_id'] ?? 0);
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $citizen_id = trim($_POST['citizen_id'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $role       = trim($_POST['role'] ?? 'customer');
    $password   = trim($_POST['password'] ?? '');
    $avatar     = '';

    // 1. Kiểm tra Họ và Tên (Không để trống, tối thiểu 2 ký tự)
    if (empty($fullname) || mb_strlen($fullname) < 2) {
        echo json_encode(['success' => false, 'message' => 'Họ và tên không được để trống (tối thiểu 2 ký tự)!']);
        exit();
    }

    // 2. Kiểm tra Email (Chuẩn định dạng email RFC + chống trùng lặp)
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email không đúng định dạng hợp lệ (Ví dụ: user@gmail.com)!']);
        exit();
    }
    $stmt_e = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt_e->bind_param('si', $email, $user_id);
    $stmt_e->execute();
    if ($stmt_e->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email "' . htmlspecialchars($email) . '" đã được sử dụng bởi tài khoản khác!']);
        $stmt_e->close();
        exit();
    }
    $stmt_e->close();

    // 3. Kiểm tra Số Điện Thoại (Chuẩn định dạng VN: 10 số, đầu 03, 05, 07, 08, 09 + chống trùng lặp)
    if (!empty($phone)) {
        if (!preg_match('/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không đúng chuẩn VN (10 số, bắt đầu bằng 03, 05, 07, 08, 09)!']);
            exit();
        }
        $stmt_p = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1");
        $stmt_p->bind_param('si', $phone, $user_id);
        $stmt_p->execute();
        if ($stmt_p->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại "' . htmlspecialchars($phone) . '" đã được sử dụng bởi tài khoản khác!']);
            $stmt_p->close();
            exit();
        }
        $stmt_p->close();
    }

    // 4. Kiểm tra Quyền Hạn (Role): Chỉ được chọn "staff" hoặc "customer"
    $is_target_admin = false;
    if ($user_id > 0) {
        $chk_u = $conn->query("SELECT role FROM users WHERE id = $user_id LIMIT 1");
        if ($chk_u && $r = $chk_u->fetch_assoc()) {
            if ($r['role'] === 'admin') {
                $is_target_admin = true;
                $role = 'admin'; // Không cho phép đổi quyền tài khoản Admin hiện hữu
            }
        }
    }

    if (!$is_target_admin) {
        // Chỉ chấp nhận 2 vai trò: 'staff' (Nhân viên) hoặc 'customer' (Khách hàng)
        if ($role === 'employee' || $role === 'staff') {
            $role = 'staff';
        } else {
            $role = 'customer';
        }
    }

    // 5. Kiểm tra Mật Khẩu (Phương thức đăng nhập qua mật khẩu, mặc định 123456 nếu bỏ trống khi tạo mới)
    if ($user_id === 0) {
        if (empty($password)) {
            $password = '123456';
        } elseif (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu nếu nhập phải có ít nhất 6 ký tự!']);
            exit();
        }
    } else {
        if (!empty($password) && strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu mới nếu đổi phải có ít nhất 6 ký tự!']);
            exit();
        }
    }

    // 6. Xử lý Ảnh đại diện (File tải lên hoặc URL)
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
        $upload_dir = "../uploads/avatars/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES["avatar_file"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $file_name = 'avatar_' . ($user_id ?: 'new_' . time()) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES["avatar_file"]["tmp_name"], $upload_dir . $file_name)) {
                $avatar = 'uploads/avatars/' . $file_name;
            }
        }
    }
    if (empty($avatar) && !empty($_POST['avatar_url'])) {
        $avatar = trim($_POST['avatar_url']);
    }

    // 7. Thực thi Lưu CSDL
    if ($user_id > 0) {
        // CẬP NHẬT TÀI KHOẢN
        $pass_update_sql = !empty($password) ? ", password = '" . password_hash($password, PASSWORD_DEFAULT) . "'" : "";
        $avatar_update_sql = !empty($avatar) ? ", avatar = '" . $conn->real_escape_string($avatar) . "'" : "";
        
        $sql = "UPDATE users SET 
                fullname = ?, 
                email = ?, 
                phone = ?, 
                citizen_id = ?, 
                address = ?, 
                role = ?, 
                auth_provider = 'local'
                $pass_update_sql
                $avatar_update_sql
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssi', $fullname, $email, $phone, $citizen_id, $address, $role, $user_id);
        
        if ($stmt->execute()) {
            // Đồng bộ sang bảng employees nếu là staff/admin
            if ($role === 'staff' || $role === 'admin') {
                $sync = $conn->prepare("INSERT INTO employees (user_id, fullname, email, phone, citizen_id, address)
                                        VALUES (?, ?, ?, ?, ?, ?)
                                        ON DUPLICATE KEY UPDATE fullname = VALUES(fullname), email = VALUES(email), phone = VALUES(phone), citizen_id = VALUES(citizen_id), address = VALUES(address)");
                $sync->bind_param('isssss', $user_id, $fullname, $email, $phone, $citizen_id, $address);
                $sync->execute();
                $sync->close();
            } else {
                $conn->query("DELETE FROM employees WHERE user_id = $user_id");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật thông tin tài khoản "' . htmlspecialchars($fullname) . '" thành công!',
                'user_id' => $user_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        // TẠO MỚI TÀI KHOẢN
        if (empty($avatar)) { $avatar = 'assets/images/default-avatar.png'; }
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, citizen_id, address, role, password, avatar, auth_provider, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'local', 1)");
        $stmt->bind_param('ssssssss', $fullname, $email, $phone, $citizen_id, $address, $role, $pass_hash, $avatar);
        
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;
            if ($role === 'staff') {
                $sync = $conn->prepare("INSERT INTO employees (user_id, fullname, email, phone, citizen_id, address, status)
                                        VALUES (?, ?, ?, ?, ?, ?, 1)");
                $sync->bind_param('isssss', $new_user_id, $fullname, $email, $phone, $citizen_id, $address);
                $sync->execute();
                $sync->close();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã tạo tài khoản mới cho "' . htmlspecialchars($fullname) . '" thành công!',
                'user_id' => $new_user_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi tạo tài khoản: ' . $stmt->error]);
        }
        $stmt->close();
    }
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 2. AJAX: 1-CLICK KHÓA / MỞ KHÓA TÀI KHOẢN (LIVE AJAX)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $uid = intval($_POST['user_id'] ?? 0);
    
    if ($uid <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tài khoản không hợp lệ!']);
        exit();
    }
    if ($uid == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Bạn không thể tự khóa tài khoản Admin đang sử dụng!']);
        exit();
    }
    
    $check = $conn->query("SELECT id, fullname, status, role FROM users WHERE id = $uid LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $new_st = ($row['status'] == 1) ? 0 : 1;
        $conn->query("UPDATE users SET status = $new_st WHERE id = $uid");
        $conn->query("UPDATE employees SET status = $new_st WHERE user_id = $uid");
        
        $st_lbl = ($new_st == 1) ? 'Hoạt động' : 'Đã khóa';
        $msg = ($new_st == 1) 
            ? 'Đã mở khóa tài khoản "' . $row['fullname'] . '"!' 
            : 'Đã khóa tài khoản "' . $row['fullname'] . '"!';
            
        echo json_encode([
            'success'    => true,
            'new_status' => $new_st,
            'status_lbl' => $st_lbl,
            'message'    => $msg
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản!']);
    }
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 3. AJAX: XÓA TÀI KHOẢN (LIVE AJAX + POPUP XÁC NHẬN NỔI)
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['ajax_delete_user'])) {
    header('Content-Type: application/json; charset=utf-8');
    $uid = intval($_POST['user_id'] ?? 0);
    
    if ($uid <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tài khoản không hợp lệ!']);
        exit();
    }
    if ($uid == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Bạn không thể tự xóa tài khoản Admin đang sử dụng!']);
        exit();
    }
    
    $check = $conn->query("SELECT fullname, role FROM users WHERE id = $uid LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        if ($row['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản Quản trị viên (Admin)!']);
            exit();
        }
        
        $fullname = $row['fullname'];
        $conn->query("DELETE FROM employees WHERE user_id = $uid");
        $conn->query("DELETE FROM user_vouchers WHERE user_id = $uid");
        $conn->query("DELETE FROM cart_items WHERE user_id = $uid");
        $conn->query("DELETE FROM users WHERE id = $uid");
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn tài khoản "' . $fullname . '"!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại!']);
    }
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 4. AJAX: LẤY CHI TIẾT TÀI KHOẢN (ĐỂ ĐỔ DỮ LIỆU VÀO MODAL SỬA)
// ═════════════════════════════════════════════════════════════════════
if (isset($_GET['ajax_get_user_detail'])) {
    header('Content-Type: application/json; charset=utf-8');
    $uid = intval($_GET['ajax_get_user_detail']);
    $res = $conn->query("SELECT u.*, e.citizen_id, e.address FROM users u LEFT JOIN employees e ON u.id = e.user_id WHERE u.id = $uid LIMIT 1");
    if ($res && $u = $res->fetch_assoc()) {
        unset($u['password']);
        echo json_encode(['success' => true, 'user' => $u]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản!']);
    }
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// LẤY DỮ LIỆU VÀ THỐNG KÊ TỔNG QUAN
// ═════════════════════════════════════════════════════════════════════
include_once 'includes/header.php';

$sql_users = "SELECT u.*, e.citizen_id, e.address FROM users u LEFT JOIN employees e ON u.id = e.user_id ORDER BY u.id DESC";
$users_res = $conn->query($sql_users);

$users_list = [];
$total_users = 0;
$total_admins = 0;
$total_staff = 0;
$total_customers = 0;
$total_locked = 0;

if ($users_res) {
    while ($row = $users_res->fetch_assoc()) {
        $users_list[] = $row;
        $total_users++;
        if ($row['role'] === 'admin') $total_admins++;
        elseif ($row['role'] === 'staff' || $row['role'] === 'employee') $total_staff++;
        else $total_customers++;

        if (intval($row['status']) === 0) $total_locked++;
    }
}
?>

<style>
/* CSS Nâng Cao Cho Quản Lý Tài Khoản Live AJAX */
.user-stat-card {
    border-radius: 16px;
    padding: 1.1rem 1.3rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: none;
}
.user-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.user-avatar-cell {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    transition: transform 0.2s ease;
}
.user-avatar-cell:hover {
    transform: scale(1.15);
}
.table-user-row {
    transition: background-color 0.2s ease, opacity 0.3s ease;
}
.table-user-row.row-locked {
    background-color: rgba(241, 245, 249, 0.7);
}
.table-user-row.row-locked .user-name {
    color: #64748b;
    text-decoration: line-through;
}
.filter-search-box {
    border-radius: 12px;
    padding: 0.6rem 1rem;
    border: 1px solid #cbd5e1;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-search-box:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}
.role-badge-admin {
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    font-weight: 700;
}
.role-badge-staff {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-weight: 700;
}
.role-badge-customer {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    font-weight: 600;
}
#mapPickerModal {
    z-index: 10070 !important;
}
</style>

<!-- TIÊU ĐỀ TRANG & NÚT THÊM MỚI -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-users-gear text-info me-2"></i>Quản Lý Tài Khoản &amp; Phân Quyền
        </h4>
        <span class="text-muted small">Cấu hình tài khoản, phân quyền Nhân viên / Khách hàng, kiểm tra hợp lệ Email, SĐT và Khóa/Mở khóa Live AJAX.</span>
    </div>
    <button type="button" class="btn btn-info text-white fw-bold rounded-pill px-4 shadow-sm" onclick="openAddUserModal()">
        <i class="fa-solid fa-user-plus me-1"></i> + Tạo Tài Khoản Mới
    </button>
</div>

<!-- CÁC THẺ THỐNG KÊ NHANH -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card user-stat-card shadow-sm bg-white border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Tổng Tài Khoản</span>
                    <h3 class="fw-bold mb-0 text-dark" id="stat-total-users"><?= $total_users ?></h3>
                </div>
                <div class="rounded-circle p-3 bg-info-subtle text-info fs-4">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card user-stat-card shadow-sm bg-white border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Nhân Viên</span>
                    <h3 class="fw-bold mb-0 text-warning" id="stat-total-staff"><?= $total_staff ?></h3>
                </div>
                <div class="rounded-circle p-3 bg-warning-subtle text-warning fs-4">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card user-stat-card shadow-sm bg-white border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Khách Hàng</span>
                    <h3 class="fw-bold mb-0 text-primary" id="stat-total-customers"><?= $total_customers ?></h3>
                </div>
                <div class="rounded-circle p-3 bg-primary-subtle text-primary fs-4">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card user-stat-card shadow-sm bg-white border-start border-4 border-danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Đang Khóa</span>
                    <h3 class="fw-bold mb-0 text-danger" id="stat-total-locked"><?= $total_locked ?></h3>
                </div>
                <div class="rounded-circle p-3 bg-danger-subtle text-danger fs-4">
                    <i class="fa-solid fa-user-lock"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KHUNG BỘ LỌC ĐA TIÊU CHÍ VÀ TÌM KIẾM TỨC THÌ (LIVE FILTER) -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <div class="row g-2 align-items-center">
        <!-- Ô Tìm Kiếm -->
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="userSearchInput" class="form-control bg-light border-start-0 rounded-end-pill filter-search-box" placeholder="Tìm theo Họ tên, Email, Số điện thoại, CCCD..." oninput="filterUsersLive()">
            </div>
        </div>
        <!-- Lọc Quyền Hạn -->
        <div class="col-6 col-md-3">
            <select id="filterRoleSelect" class="form-select rounded-pill" onchange="filterUsersLive()">
                <option value="">-- Tất cả vai trò (Role) --</option>
                <option value="admin">👑 Quản trị viên (Admin)</option>
                <option value="staff">👔 Nhân viên (Staff)</option>
                <option value="customer">👤 Khách hàng (Customer)</option>
            </select>
        </div>
        <!-- Lọc Trạng Thái -->
        <div class="col-6 col-md-3">
            <select id="filterStatusSelect" class="form-select rounded-pill" onchange="filterUsersLive()">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="1">🟢 Đang hoạt động</option>
                <option value="0">🔒 Đang bị khóa</option>
            </select>
        </div>
        <!-- Nút Reset -->
        <div class="col-12 col-md-1 text-end">
            <button type="button" class="btn btn-outline-secondary rounded-pill w-100" onclick="resetUserFilters()" title="Làm mới bộ lọc">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
        </div>
    </div>
    <div class="mt-2 d-flex justify-content-between align-items-center px-1">
        <span class="small text-muted" id="userCounterText">Hiển thị <strong><?= count($users_list) ?></strong> / <?= count($users_list) ?> tài khoản</span>
    </div>
</div>

<!-- BẢNG DANH SÁCH TÀI KHOẢN (LIVE AJAX) -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-5 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="usersTable">
            <thead class="table-dark text-uppercase small">
                <tr>
                    <th class="ps-3">Tài Khoản</th>
                    <th>Email &amp; SĐT</th>
                    <th>Đăng Nhập</th>
                    <th>Quyền Hạn (Role)</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th class="text-end pe-3">Thao Tác</th>
                </tr>
            </thead>
            <tbody id="usersTbody">
                <?php if (!empty($users_list)): ?>
                    <?php foreach($users_list as $u): 
                        $u_st = intval($u['status'] ?? 1);
                        $u_role = $u['role'] ?? 'customer';
                        if ($u_role === 'employee') $u_role = 'staff';
                        $avatar_src = !empty($u['avatar']) ? ((strpos($u['avatar'], 'http') === 0) ? $u['avatar'] : '../' . $u['avatar']) : '../assets/images/default-avatar.png';
                        $search_text = mb_strtolower($u['fullname'] . ' ' . $u['email'] . ' ' . ($u['phone']??'') . ' ' . ($u['citizen_id']??''));
                    ?>
                        <tr id="user-row-<?= $u['id'] ?>" class="table-user-row <?= $u_st === 0 ? 'row-locked' : '' ?>" 
                            data-id="<?= $u['id'] ?>" 
                            data-role="<?= $u_role ?>" 
                            data-status="<?= $u_st ?>" 
                            data-search="<?= htmlspecialchars($search_text) ?>">
                            
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= htmlspecialchars($avatar_src) ?>" class="user-avatar-cell shadow-sm" alt="" onerror="this.src='../assets/images/default-avatar.png'">
                                    <div>
                                        <strong class="text-dark d-block user-name"><?= htmlspecialchars($u['fullname']) ?></strong>
                                        <small class="text-muted"><i class="fa-solid fa-id-card me-1"></i>CCCD: <strong><?= htmlspecialchars($u['citizen_id'] ?: 'Chưa có') ?></strong></small>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <span class="d-block text-dark fw-bold"><i class="fa-solid fa-envelope me-1 text-secondary"></i><?= htmlspecialchars($u['email']) ?></span>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($u['phone'] ?: 'Chưa có SĐT') ?></small>
                            </td>

                            <td>
                                <?php if ($u['auth_provider'] === 'google'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold rounded-pill px-3 py-1">
                                        <i class="fa-brands fa-google me-1"></i>Google OAuth
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-dark text-white fw-bold rounded-pill px-3 py-1">
                                        <i class="fa-solid fa-key me-1 text-warning"></i>Mật khẩu
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($u_role === 'admin'): ?>
                                    <span class="badge role-badge-admin rounded-pill px-3 py-1"><i class="fa-solid fa-shield-halved me-1"></i>ADMIN</span>
                                <?php elseif ($u_role === 'staff'): ?>
                                    <span class="badge role-badge-staff rounded-pill px-3 py-1"><i class="fa-solid fa-user-tie me-1"></i>NHÂN VIÊN</span>
                                <?php else: ?>
                                    <span class="badge role-badge-customer rounded-pill px-3 py-1"><i class="fa-solid fa-user me-1"></i>Khách hàng</span>
                                <?php endif; ?>
                            </td>

                            <td id="user-status-cell-<?= $u['id'] ?>">
                                <?php if ($u_st === 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                        <i class="fa-solid fa-circle-check me-1"></i> Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold">
                                        <i class="fa-solid fa-user-lock me-1"></i> Đã khóa
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <small class="text-muted"><i class="fa-solid fa-calendar-day me-1"></i><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small>
                            </td>

                            <td class="text-end pe-3 text-nowrap">
                                <!-- NÚT 1-CLICK KHÓA / MỞ KHÓA -->
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" 
                                            id="btn-lock-<?= $u['id'] ?>"
                                            class="btn btn-sm <?= $u_st === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?> rounded-pill me-1" 
                                            onclick="ajaxToggleUserStatus(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['fullname'])) ?>')"
                                            title="<?= $u_st === 1 ? 'Khóa tài khoản này' : 'Mở khóa tài khoản này' ?>">
                                        <i class="fa-solid <?= $u_st === 1 ? 'fa-user-lock' : 'fa-user-check' ?>"></i> 
                                        <span><?= $u_st === 1 ? 'Khóa' : 'Mở khóa' ?></span>
                                    </button>
                                <?php endif; ?>

                                <!-- NÚT SỬA -->
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill me-1" onclick="openEditUserModal(<?= $u['id'] ?>)" title="Chỉnh sửa tài khoản">
                                    <i class="fa-solid fa-pen-to-square"></i> Sửa
                                </button>

                                <!-- NÚT XÓA (CÓ POPUP NỔI SWEETALERT2) -->
                                <?php if ($u['id'] != $_SESSION['user_id'] && $u_role !== 'admin'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" onclick="ajaxConfirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['fullname'])) ?>', '<?= htmlspecialchars($u['email']) ?>')" title="Xóa tài khoản">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="emptyUsersRow"><td colspan="7" class="text-center py-4 text-muted">Chưa có tài khoản nào trong hệ thống.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════════════
     MODAL THÊM / SỬA TÀI KHOẢN (LIVE AJAX FORM)
══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="userAjaxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="userModalTitle">
                    <i class="fa-solid fa-user-gear text-info me-2"></i>Thông Tin Tài Khoản
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="userAjaxForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax_save_user" value="1">
                <input type="hidden" name="user_id" id="form_user_id" value="0">
                
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <!-- Cột Trái: Ảnh Đại Diện -->
                        <div class="col-12 col-md-4 text-center border-end pe-md-3 bg-white p-3 rounded-3 shadow-sm">
                            <label class="form-label fw-bold d-block mb-2">Ảnh Đại Diện</label>
                            <div class="d-flex justify-content-center mb-3">
                                <img id="preview_user_avatar" src="../assets/images/default-avatar.png" class="rounded-circle border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;" onerror="this.src='../assets/images/default-avatar.png'">
                            </div>
                            <div class="text-start">
                                <label class="form-label small fw-semibold mb-1">Tải ảnh mới từ máy:</label>
                                <input type="file" name="avatar_file" id="form_avatar_file" class="form-control form-control-sm mb-2" accept="image/*" onchange="previewUserFile(this)">
                                <label class="form-label small fw-semibold mb-1">Hoặc nhập link URL ảnh:</label>
                                <input type="text" name="avatar_url" id="form_user_avatar_url" class="form-control form-control-sm" placeholder="https://..." oninput="previewUserUrl(this.value)">
                            </div>
                        </div>

                        <!-- Cột Phải: Thông Tin Cá Nhân & Quyền -->
                        <div class="col-12 col-md-8 bg-white p-3 rounded-3 shadow-sm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Họ và Tên <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" id="form_fullname" class="form-control fw-bold" placeholder="Nguyễn Văn A" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Số CCCD / CMND</label>
                                    <input type="text" name="citizen_id" id="form_citizen_id" class="form-control" placeholder="12 chữ số...">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Email Đăng Nhập <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="form_email" class="form-control" placeholder="example@gmail.com" required>
                                    <div class="form-text small text-muted">Dùng để đăng nhập vào hệ thống.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Số Điện Thoại <span class="text-muted">(VN 10 số)</span></label>
                                    <input type="tel" name="phone" id="form_phone" class="form-control" placeholder="VD: 0912345678" pattern="^(0|\+84)(3|5|7|8|9)[0-9]{8}$">
                                    <div class="form-text small text-muted">Đầu số hợp lệ: 03, 05, 07, 08, 09.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small d-flex justify-content-between align-items-center mb-1">
                                        <span>Địa Chỉ Thường Trú</span>
                                        <button type="button" class="btn btn-xs btn-outline-danger border-0 py-0 px-2 fw-bold text-decoration-none" onclick="openMapPicker('form_address')" title="Chọn vị trí trên bản đồ">
                                            <i class="fa-solid fa-map-location-dot me-1 text-danger"></i> <span class="text-danger">📍 Chọn trên Maps</span>
                                        </button>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" name="address" id="form_address" class="form-control" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành...">
                                        <button class="btn btn-outline-danger" type="button" onclick="openMapPicker('form_address')" title="Mở bản đồ vị trí Leaflet Maps">
                                            <i class="fa-solid fa-location-dot"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Phân Quyền (Role) <span class="text-danger">*</span></label>
                                    <select name="role" id="form_role" class="form-select fw-bold text-primary" required>
                                        <option value="customer">👤 Khách Hàng (Customer)</option>
                                        <option value="staff">👔 Nhân Viên (Staff)</option>
                                    </select>
                                    <div class="form-text small text-muted" id="roleHelpText">Chỉ được phân quyền Nhân viên hoặc Khách hàng.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small" id="passwordLabel">Mật Khẩu <span class="text-muted small fw-normal">(Mặc định: 123456)</span></label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="form_password" class="form-control" placeholder="Bỏ trống mặc định 123456">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('form_password', this)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text small text-muted" id="passwordHelpText">Bỏ trống hệ thống sẽ tự động gán mật khẩu là <strong>123456</strong>.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top py-3">
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSaveUserSubmit" class="btn btn-info text-white fw-bold rounded-pill px-5 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Tài Khoản
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Cấu hình Toast SweetAlert2 tự biến mất sau 2 giây
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true
});

function getUserModalInstance() {
    const el = document.getElementById('userAjaxModal');
    if (!el) return null;
    return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
}

function initUsersPage() {
    // Bắt sự kiện Submit Form Thêm/Sửa Tài Khoản (Live AJAX)
    const form = document.getElementById('userAjaxForm');
    if (form && !form.dataset.boundSubmit) {
        form.dataset.boundSubmit = 'true';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnSaveUserSubmit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(form);

            fetch('users.php', {
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
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Tài Khoản';
                }

                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    const um = getUserModalInstance();
                    if (um) um.hide();
                    // Reset anti-double-submit guard ngay lập tức
                    if (window.resetFormSubmit) window.resetFormSubmit(form);
                    // Nạp lại bảng không tải lại trang
                    reloadUsersTableLive();
                } else {
                    // Reset anti-double-submit guard để cho phép submit lại
                    if (window.resetFormSubmit) window.resetFormSubmit(form);
                    Swal.fire({
                        icon: 'error',
                        title: 'Thông Báo',
                        text: data.message,
                        confirmButtonColor: '#0ea5e9'
                    });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Tài Khoản';
                }
                // Reset anti-double-submit guard
                if (window.resetFormSubmit) window.resetFormSubmit(form);
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi Kết Nối',
                    text: err.message || 'Không thể kết nối với máy chủ!',
                    confirmButtonColor: '#ef4444'
                });
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUsersPage);
} else {
    initUsersPage();
}

// Mở Modal Tạo Tài Khoản Mới
function openAddUserModal() {
    const um = getUserModalInstance();
    if (!um) return;

    document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus text-info me-2"></i>Tạo Tài Khoản Mới';
    document.getElementById('form_user_id').value = '0';
    document.getElementById('preview_user_avatar').src = '../assets/images/default-avatar.png';
    document.getElementById('form_avatar_file').value = '';
    document.getElementById('form_user_avatar_url').value = '';
    
    document.getElementById('form_fullname').value = '';
    document.getElementById('form_citizen_id').value = '';
    document.getElementById('form_email').value = '';
    document.getElementById('form_phone').value = '';
    document.getElementById('form_address').value = '';
    document.getElementById('form_password').value = '';
    document.getElementById('form_password').required = false;
    document.getElementById('form_password').placeholder = 'Bỏ trống mặc định là 123456';
    document.getElementById('passwordLabel').innerHTML = 'Mật Khẩu <span class="text-muted small fw-normal">(Mặc định: 123456)</span>';
    document.getElementById('passwordHelpText').innerHTML = '<i class="fa-solid fa-circle-info text-info me-1"></i> Bỏ trống hệ thống sẽ tự động gán mật khẩu là <strong>123456</strong>.';

    const roleSelect = document.getElementById('form_role');
    roleSelect.disabled = false;
    // Đảm bảo chỉ có Nhân viên và Khách hàng
    roleSelect.innerHTML = `
        <option value="customer">👤 Khách Hàng (Customer)</option>
        <option value="staff">👔 Nhân Viên (Staff)</option>
    `;
    roleSelect.value = 'customer';
    document.getElementById('roleHelpText').innerText = 'Chỉ được phân quyền Nhân viên hoặc Khách hàng.';

    um.show();
}
window.openAddUserModal = openAddUserModal;

// Mở Modal Chỉnh Sửa Tài Khoản
function openEditUserModal(userId) {
    fetch('users.php?ajax_get_user_detail=' + userId)
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error("Server raw response:", text);
            throw new Error('Lỗi phản hồi máy chủ');
        }
    })
    .then(data => {
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
            return;
        }

        const u = data.user;
        document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-pen text-info me-2"></i>Chỉnh Sửa Tài Khoản: ' + (u.fullname || '');
        document.getElementById('form_user_id').value = u.id;

        const avatarSrc = u.avatar ? (u.avatar.startsWith('http') || u.avatar.startsWith('/') ? u.avatar : '../' + u.avatar) : '../assets/images/default-avatar.png';
        document.getElementById('preview_user_avatar').src = avatarSrc;
        document.getElementById('form_avatar_file').value = '';
        document.getElementById('form_user_avatar_url').value = u.avatar || '';

        document.getElementById('form_fullname').value = u.fullname || '';
        document.getElementById('form_citizen_id').value = u.citizen_id || '';
        document.getElementById('form_email').value = u.email || '';
        document.getElementById('form_phone').value = u.phone || '';
        document.getElementById('form_address').value = u.address || '';
        document.getElementById('form_password').value = '';
        document.getElementById('form_password').required = false;
        document.getElementById('passwordLabel').innerHTML = 'Đổi Mật Khẩu Mới <span class="text-muted">(Tùy chọn)</span>';
        document.getElementById('passwordHelpText').innerText = 'Bỏ trống nếu giữ nguyên mật khẩu cũ.';

        const roleSelect = document.getElementById('form_role');
        if (u.role === 'admin') {
            roleSelect.innerHTML = `<option value="admin">👑 Quản Trị Viên (Admin - Cố định)</option>`;
            roleSelect.value = 'admin';
            roleSelect.disabled = true;
            document.getElementById('roleHelpText').innerText = 'Tài khoản Quản trị viên hệ thống được bảo vệ cố định.';
        } else {
            roleSelect.disabled = false;
            roleSelect.innerHTML = `
                <option value="customer">👤 Khách Hàng (Customer)</option>
                <option value="staff">👔 Nhân Viên (Staff)</option>
            `;
            roleSelect.value = (u.role === 'staff' || u.role === 'employee') ? 'staff' : 'customer';
            document.getElementById('roleHelpText').innerText = 'Chỉ được phân quyền Nhân viên hoặc Khách hàng.';
        }

        const um = getUserModalInstance();
        if (um) um.show();
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể lấy thông tin tài khoản.' });
    });
}
window.openEditUserModal = openEditUserModal;

// 1-Click Khóa / Mở Khóa Tài Khoản (Live AJAX)
function ajaxToggleUserStatus(userId, userName) {
    const formData = new FormData();
    formData.append('ajax_toggle_status', '1');
    formData.append('user_id', userId);

    fetch('users.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.fire({
                icon: 'success',
                title: data.message
            });

            // Cập nhật giao diện dòng ngay tức thì
            const row = document.getElementById('user-row-' + userId);
            const statusCell = document.getElementById('user-status-cell-' + userId);
            const lockBtn = document.getElementById('btn-lock-' + userId);

            if (data.new_status === 1) {
                if (row) {
                    row.classList.remove('row-locked');
                    row.setAttribute('data-status', '1');
                }
                if (statusCell) {
                    statusCell.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Hoạt động</span>`;
                }
                if (lockBtn) {
                    lockBtn.className = 'btn btn-sm btn-outline-warning rounded-pill me-1';
                    lockBtn.innerHTML = '<i class="fa-solid fa-user-lock"></i> <span>Khóa</span>';
                    lockBtn.title = 'Khóa tài khoản này';
                }
            } else {
                if (row) {
                    row.classList.add('row-locked');
                    row.setAttribute('data-status', '0');
                }
                if (statusCell) {
                    statusCell.innerHTML = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold"><i class="fa-solid fa-user-lock me-1"></i> Đã khóa</span>`;
                }
                if (lockBtn) {
                    lockBtn.className = 'btn btn-sm btn-outline-success rounded-pill me-1';
                    lockBtn.innerHTML = '<i class="fa-solid fa-user-check"></i> <span>Mở khóa</span>';
                    lockBtn.title = 'Mở khóa tài khoản này';
                }
            }

            updateStatsCounters();
        } else {
            Swal.fire({ icon: 'error', title: 'Không thể thao tác', text: data.message });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
    });
}

// Xóa Tài Khoản (Popup Nổi SweetAlert2 Xác Nhận)
function ajaxConfirmDeleteUser(userId, userName, userEmail) {
    Swal.fire({
        title: 'Xóa vĩnh viễn tài khoản?',
        html: `Bạn có chắc chắn muốn xóa tài khoản <strong class="text-danger">${userName}</strong> (<span class="text-muted">${userEmail}</span>)?<br><small class="text-danger fw-semibold">⚠️ Hành động này sẽ xóa dữ liệu liên quan và không thể hoàn tác!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xác Nhận Xóa',
        cancelButtonText: 'Hủy Bỏ',
        customClass: {
            popup: 'rounded-4 shadow-lg'
        }
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_user', '1');
            formData.append('user_id', userId);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });

                    const row = document.getElementById('user-row-' + userId);
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            filterUsersLive();
                            updateStatsCounters();
                        }, 300);
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể xóa tài khoản.' });
            });
        }
    });
}

// Bộ Lọc Đa Tiêu Chí và Tìm Kiếm Tức Thì (Live Filter 0ms)
function filterUsersLive() {
    const kw = document.getElementById('userSearchInput').value.toLowerCase().trim();
    const roleFilter = document.getElementById('filterRoleSelect').value;
    const statusFilter = document.getElementById('filterStatusSelect').value;

    const rows = document.querySelectorAll('.table-user-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const search = row.getAttribute('data-search') || '';
        const role = row.getAttribute('data-role') || '';
        const status = row.getAttribute('data-status') || '';

        const matchKw = !kw || search.includes(kw);
        const matchRole = !roleFilter || (role === roleFilter);
        const matchStatus = (statusFilter === '') || (status === statusFilter);

        if (matchKw && matchRole && matchStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const counter = document.getElementById('userCounterText');
    if (counter) {
        counter.innerHTML = `Hiển thị <strong>${visibleCount}</strong> / ${rows.length} tài khoản`;
    }
}

// Làm mới bộ lọc
function resetUserFilters() {
    document.getElementById('userSearchInput').value = '';
    document.getElementById('filterRoleSelect').value = '';
    document.getElementById('filterStatusSelect').value = '';
    filterUsersLive();
}

// Cập nhật số liệu thống kê trên các thẻ
function updateStatsCounters() {
    const rows = document.querySelectorAll('.table-user-row');
    let total = rows.length;
    let staff = 0;
    let customers = 0;
    let locked = 0;

    rows.forEach(r => {
        const role = r.getAttribute('data-role');
        const st = r.getAttribute('data-status');
        if (role === 'staff') staff++;
        if (role === 'customer') customers++;
        if (st === '0') locked++;
    });

    const elTotal = document.getElementById('stat-total-users');
    const elStaff = document.getElementById('stat-total-staff');
    const elCust = document.getElementById('stat-total-customers');
    const elLock = document.getElementById('stat-total-locked');

    if (elTotal) elTotal.innerText = total;
    if (elStaff) elStaff.innerText = staff;
    if (elCust) elCust.innerText = customers;
    if (elLock) elLock.innerText = locked;
}

// Tải lại bảng người dùng ngầm qua AJAX (Không load lại toàn trang)
function reloadUsersTableLive() {
    fetch('users.php')
    .then(res => res.text())
    .then(htmlText => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');
        
        const newTbody = doc.getElementById('usersTbody');
        if (newTbody) {
            document.getElementById('usersTbody').innerHTML = newTbody.innerHTML;
        }

        // Cập nhật lại số liệu thống kê
        ['stat-total-users', 'stat-total-staff', 'stat-total-customers', 'stat-total-locked'].forEach(id => {
            const newEl = doc.getElementById(id);
            const oldEl = document.getElementById(id);
            if (newEl && oldEl) oldEl.innerText = newEl.innerText;
        });

        filterUsersLive();
    });
}

// Preview avatar khi chọn file
function previewUserFile(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview_user_avatar').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview avatar khi gõ link URL
function previewUserUrl(url) {
    if (url.trim()) {
        document.getElementById('preview_user_avatar').src = url.trim();
    }
}

// Ẩn / Hiện mật khẩu
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="fa-solid fa-eye-slash text-danger"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
    }
}

// Export tất cả hàm ra window để đảm bảo onclick hoạt động trong mọi tình huống
window.openAddUserModal        = openAddUserModal;
window.openEditUserModal       = openEditUserModal;
window.ajaxToggleUserStatus    = ajaxToggleUserStatus;
window.ajaxConfirmDeleteUser   = ajaxConfirmDeleteUser;
window.filterUsersLive         = filterUsersLive;
window.resetUserFilters        = resetUserFilters;
window.updateStatsCounters     = updateStatsCounters;
window.reloadUsersTableLive    = reloadUsersTableLive;
window.previewUserFile         = previewUserFile;
window.previewUserUrl          = previewUserUrl;
window.togglePasswordVisibility = togglePasswordVisibility;
</script>

<?php include_once __DIR__ . '/../includes/map-picker-modal.php'; ?>
<?php include_once 'includes/footer.php'; ?>