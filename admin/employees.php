<?php 
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// =========================================================================
// 1. CÁC ENDPOINT AJAX DÀNH CHO QUẢN LÝ NHÂN VIÊN (KHÔNG LOAD LẠI TRANG)
// =========================================================================

// AJAX 1.1: Lấy chi tiết nhân viên để xem hồ sơ chi tiết / chỉnh sửa
if (isset($_GET['ajax_get_employee'])) {
    header('Content-Type: application/json; charset=utf-8');
    $emp_id = intval($_GET['emp_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT e.*, u.auth_provider, u.created_at AS user_created_at 
        FROM employees e 
        LEFT JOIN users u ON e.user_id = u.id 
        WHERE e.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $emp = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($emp) {
        // Lấy danh sách lịch làm việc của nhân viên
        $schedules = [];
        $sch_res = $conn->query("
            SELECT * FROM employee_schedules 
            WHERE employee_id = $emp_id 
            ORDER BY FIELD(day_of_week, 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'), start_time ASC
        ");
        if ($sch_res) {
            while ($sr = $sch_res->fetch_assoc()) {
                $schedules[] = $sr;
            }
        }

        // Lấy các kỳ lương gần nhất
        $salaries = [];
        $sal_res = $conn->query("
            SELECT * FROM employee_salaries 
            WHERE employee_id = $emp_id 
            ORDER BY id DESC LIMIT 5
        ");
        if ($sal_res) {
            while ($sl = $sal_res->fetch_assoc()) {
                $salaries[] = $sl;
            }
        }

        echo json_encode([
            'success'        => true, 
            'data'           => $emp,
            'schedules'      => $schedules,
            'salaries'       => $salaries,
            'schedule_count' => count($schedules),
            'latest_salary'  => $salaries[0] ?? null
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin nhân viên!']);
    exit;
}

// AJAX 1.2: Lưu thông tin nhân viên (Thêm mới hoặc Cập nhật)
if (isset($_POST['ajax_save_employee'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $emp_id     = intval($_POST['emp_id'] ?? 0);
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $citizen_id = trim($_POST['citizen_id'] ?? '');
    $birthday   = trim($_POST['birthday'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $status     = intval($_POST['status'] ?? 1);
    $notes      = trim($_POST['notes'] ?? '');
    $custom_pass= trim($_POST['password'] ?? '');

    // 1. Kiểm tra Họ và Tên
    if (empty($fullname)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Họ và Tên nhân viên!']);
        exit;
    }
    if (mb_strlen($fullname, 'UTF-8') < 2) {
        echo json_encode(['success' => false, 'message' => 'Họ và tên nhân viên quá ngắn!']);
        exit;
    }

    // 2. Kiểm tra Email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Địa chỉ Email không hợp lệ (Ví dụ: nhanvien@gmail.com)!']);
        exit;
    }

    // 3. Chuẩn hóa & Kiểm tra Số điện thoại
    $phone_clean = preg_replace('/[\s\-\.]/', '', $phone);
    if (strpos($phone_clean, '+84') === 0) {
        $phone_clean = '0' . substr($phone_clean, 3);
    } elseif (strpos($phone_clean, '84') === 0 && strlen($phone_clean) === 11) {
        $phone_clean = '0' . substr($phone_clean, 2);
    }

    if (empty($phone_clean)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Số điện thoại nhân viên!']);
        exit;
    }
    if (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone_clean)) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ! (Phải gồm 10 chữ số di động VN, bắt đầu bằng 03, 05, 07, 08, 09)']);
        exit;
    }

    // 4. Chuẩn hóa & Kiểm tra CCCD/CMND
    $citizen_id_clean = preg_replace('/[\s\-\.]/', '', $citizen_id);
    if (!empty($citizen_id_clean) && !preg_match('/^[0-9]{9}$|^[0-9]{12}$/', $citizen_id_clean)) {
        echo json_encode(['success' => false, 'message' => 'Số CCCD/CMND không hợp lệ! (Phải gồm đúng 9 số CMND hoặc 12 số CCCD)']);
        exit;
    }

    // 5. Kiểm tra Ngày sinh
    $birthday_val = null;
    if (!empty($birthday)) {
        $b_time = strtotime($birthday);
        if (!$b_time || $b_time > time() || date('Y', $b_time) < 1920) {
            echo json_encode(['success' => false, 'message' => 'Ngày sinh không hợp lệ!']);
            exit;
        }
        $age = (int)date('Y') - (int)date('Y', $b_time);
        if ($age < 15) {
            echo json_encode(['success' => false, 'message' => 'Nhân viên phải từ đủ 15 tuổi trở lên!']);
            exit;
        }
        $birthday_val = date('Y-m-d', $b_time);
    }

    // 6. Xử lý Upload Avatar
    $avatar = '';
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES["avatar_file"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $file_name = time() . '_emp_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES["avatar_file"]["tmp_name"], $upload_dir . $file_name)) {
                $avatar = 'uploads/' . $file_name;
            }
        }
    }
    if (empty($avatar) && !empty($_POST['avatar_url'])) {
        $avatar = trim($_POST['avatar_url']);
    }

    // 7. Kiểm tra trùng Email, SĐT, CCCD với tài khoản khác trong hệ thống
    $emp_data = ($emp_id > 0) ? $conn->query("SELECT user_id, avatar FROM employees WHERE id = $emp_id")->fetch_assoc() : null;
    $user_id  = intval($emp_data['user_id'] ?? 0);
    if (empty($avatar) && $emp_data) {
        $avatar = $emp_data['avatar'];
    }

    // Check trùng Email
    $stmt_e = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_e->bind_param("si", $email, $user_id);
    $stmt_e->execute();
    if ($stmt_e->get_result()->num_rows > 0) {
        $stmt_e->close();
        echo json_encode(['success' => false, 'message' => 'Email này đã được sử dụng bởi một tài khoản khác!']);
        exit;
    }
    $stmt_e->close();

    // Check trùng Số điện thoại
    $stmt_p = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $stmt_p->bind_param("si", $phone_clean, $user_id);
    $stmt_p->execute();
    if ($stmt_p->get_result()->num_rows > 0) {
        $stmt_p->close();
        echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã được đăng ký cho tài khoản khác!']);
        exit;
    }
    $stmt_p->close();

    // Check trùng CCCD
    if (!empty($citizen_id_clean)) {
        $stmt_c = $conn->prepare("SELECT id FROM users WHERE citizen_id = ? AND id != ?");
        $stmt_c->bind_param("si", $citizen_id_clean, $user_id);
        $stmt_c->execute();
        if ($stmt_c->get_result()->num_rows > 0) {
            $stmt_c->close();
            echo json_encode(['success' => false, 'message' => 'Số CCCD/CMND này đã tồn tại trên hệ thống!']);
            exit;
        }
        $stmt_c->close();
    }

    // 8. Thực hiện Lưu vào CSDL
    $phone = $phone_clean;
    $citizen_id = !empty($citizen_id_clean) ? $citizen_id_clean : null;

    if ($emp_id > 0) {
        // CẬP NHẬT
        $stmt_u = $conn->prepare("
            UPDATE employees SET 
                fullname = ?, email = ?, phone = ?, citizen_id = ?, birthday = ?, address = ?, 
                avatar = ?, status = ?, notes = ?
            WHERE id = ?
        ");
        $stmt_u->bind_param("sssssssisi", $fullname, $email, $phone, $citizen_id, $birthday_val, $address, $avatar, $status, $notes, $emp_id);
        $stmt_u->execute();
        $stmt_u->close();

        // Đồng bộ users
        if ($user_id > 0) {
            if (!empty($custom_pass)) {
                $hash = password_hash($custom_pass, PASSWORD_DEFAULT);
                $stmt_usr = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, citizen_id = ?, address = ?, avatar = ?, password = ?, status = ?, role = 'staff' WHERE id = ?");
                $stmt_usr->bind_param("sssssssii", $fullname, $email, $phone, $citizen_id, $address, $avatar, $hash, $status, $user_id);
            } else {
                $stmt_usr = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, citizen_id = ?, address = ?, avatar = ?, status = ?, role = 'staff' WHERE id = ?");
                $stmt_usr->bind_param("ssssssii", $fullname, $email, $phone, $citizen_id, $address, $avatar, $status, $user_id);
            }
            $stmt_usr->execute();
            $stmt_usr->close();
        }

        echo json_encode([
            'success' => true, 
            'emp_id'  => $emp_id,
            'message' => "Đã cập nhật hồ sơ nhân viên {$fullname} thành công!"
        ]);
        exit;
    } else {
        // THÊM MỚI
        if (empty($avatar)) { $avatar = 'assets/images/default-avatar.png'; }
        $pass_use = !empty($custom_pass) ? $custom_pass : '123456';
        $pass_hash = password_hash($pass_use, PASSWORD_DEFAULT);

        $stmt_new_u = $conn->prepare("
            INSERT INTO users (fullname, email, password, phone, citizen_id, address, avatar, auth_provider, status, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'local', ?, 'staff')
        ");
        $stmt_new_u->bind_param("sssssssi", $fullname, $email, $pass_hash, $phone, $citizen_id, $address, $avatar, $status);
        if ($stmt_new_u->execute()) {
            $new_user_id = $conn->insert_id;
            $stmt_new_u->close();

            $stmt_new_e = $conn->prepare("
                INSERT INTO employees (user_id, fullname, email, phone, citizen_id, birthday, address, avatar, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_new_e->bind_param("isssssssis", $new_user_id, $fullname, $email, $phone, $citizen_id, $birthday_val, $address, $avatar, $status, $notes);
            $stmt_new_e->execute();
            $new_emp_id = $conn->insert_id;
            $stmt_new_e->close();

            // Khởi tạo bản ghi lương mặc định cho nhân viên mới
            $cur_m = date('m/Y');
            $conn->query("
                INSERT IGNORE INTO employee_salaries 
                (employee_id, month_year, base_salary, work_days, off_days, allowance, commission_rate, total_salary, status)
                VALUES ($new_emp_id, '$cur_m', 6000000, 26, 0, 500000, 3.00, 6500000, 'unpaid')
            ");

            echo json_encode([
                'success' => true, 
                'emp_id'  => $new_emp_id,
                'message' => "Đã thêm mới nhân viên {$fullname} thành công (Mật khẩu mặc định: {$pass_use})!"
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi tạo tài khoản: ' . $conn->error]);
            exit;
        }
    }
}

// AJAX 1.3: Chuyển đổi trạng thái Đang làm / Đã nghỉ việc
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $emp_id = intval($_POST['emp_id'] ?? 0);
    $res = $conn->query("SELECT status, fullname, user_id FROM employees WHERE id = $emp_id");
    if ($res && $row = $res->fetch_assoc()) {
        $new_status = ($row['status'] == 1) ? 0 : 1;
        $conn->query("UPDATE employees SET status = $new_status WHERE id = $emp_id");
        if (!empty($row['user_id'])) {
            $conn->query("UPDATE users SET status = $new_status WHERE id = " . intval($row['user_id']));
        }
        $st_label = ($new_status == 1) ? 'Đang làm việc' : 'Đã nghỉ việc';
        $badge_html = ($new_status == 1) 
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> Đang làm việc</span>' 
            : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2"><i class="fa-solid fa-circle-xmark me-1"></i> Đã nghỉ việc</span>';
        
        echo json_encode([
            'success' => true, 
            'new_status' => $new_status,
            'badge_html' => $badge_html,
            'message' => "Đã chuyển trạng thái của {$row['fullname']} sang [{$st_label}]!"
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên.']);
    exit;
}

// AJAX 1.4: Xóa nhân viên
if (isset($_POST['ajax_delete_employee'])) {
    header('Content-Type: application/json; charset=utf-8');
    $emp_id = intval($_POST['emp_id'] ?? 0);
    $res = $conn->query("SELECT user_id, fullname FROM employees WHERE id = $emp_id");
    if ($res && $row = $res->fetch_assoc()) {
        $uid = intval($row['user_id']);
        
        $conn->query("DELETE FROM employee_schedules WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employee_salaries WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employees WHERE id = $emp_id");
        if ($uid > 0) {
            $conn->query("DELETE FROM users WHERE id = $uid AND role = 'staff'");
        }

        echo json_encode(['success' => true, 'message' => "Đã xóa vĩnh viễn hồ sơ nhân viên {$row['fullname']}!"]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên.']);
    exit;
}

include_once 'includes/header.php';

// =========================================================================
// 2. LỌC & TÌM KIẾM HỒ SƠ NHÂN VIÊN
// =========================================================================
$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? 'all';

$where = ["1=1"];
if (!empty($search)) {
    $s_clean = $conn->real_escape_string($search);
    $where[] = "(e.fullname LIKE '%$s_clean%' OR e.phone LIKE '%$s_clean%' OR e.email LIKE '%$s_clean%' OR e.citizen_id LIKE '%$s_clean%' OR e.address LIKE '%$s_clean%')";
}
if ($filter_status !== 'all') {
    $st_val = intval($filter_status);
    $where[] = "e.status = $st_val";
}

$where_sql = implode(' AND ', $where);
$query = "
    SELECT e.*, u.auth_provider 
    FROM employees e 
    LEFT JOIN users u ON e.user_id = u.id 
    WHERE $where_sql 
    ORDER BY e.status DESC, e.id DESC
";
$employees_res = $conn->query($query);
$employees = [];
if ($employees_res) {
    while ($r = $employees_res->fetch_assoc()) {
        $employees[] = $r;
    }
}

// Thống kê nhanh
$total_emp  = $conn->query("SELECT COUNT(*) as c FROM employees")->fetch_assoc()['c'] ?? 0;
$active_emp = $conn->query("SELECT COUNT(*) as c FROM employees WHERE status = 1")->fetch_assoc()['c'] ?? 0;
$off_emp    = $total_emp - $active_emp;
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-users-gear me-2 text-primary"></i>Quản Lý Hồ Sơ Nhân Viên
        </h4>
        <span class="text-muted small">Quản lý danh sách nhân sự, thông tin liên hệ, CCCD, bản đồ vị trí và tài khoản.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="employee-schedule.php" class="btn btn-outline-info rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-calendar-week me-1"></i> Xem Lịch Làm Việc
        </a>
        <a href="employee-salaries.php" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-money-check-dollar me-1"></i> Bảng Lương & Thưởng
        </a>
        <button class="btn btn-primary rounded-pill px-3 fw-bold shadow-sm" onclick="openAddEmployeeModal()">
            <i class="fa-solid fa-user-plus me-1"></i> Thêm Nhân Viên Mới
        </button>
    </div>
</div>

<!-- THỐNG KÊ NHANH -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-12">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fa-solid fa-users fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Tổng Số Nhân Viên</small>
                    <h5 class="fw-bold mb-0 text-primary"><?= number_format($total_emp); ?> nhân sự</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                    <i class="fa-solid fa-user-check fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Đang Làm Việc</small>
                    <h5 class="fw-bold mb-0 text-success"><?= number_format($active_emp); ?> nhân sự</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger me-3">
                    <i class="fa-solid fa-user-xmark fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Đã Nghỉ Việc</small>
                    <h5 class="fw-bold mb-0 text-danger"><?= number_format($off_emp); ?> nhân sự</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BỘ LỌC VÀ TÌM KIẾM -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" class="row g-3 align-items-center">
        <div class="col-md-8 col-12">
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Tìm kiếm theo Họ tên, SĐT, Email, CCCD, Địa chỉ..." value="<?= htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-3 col-6">
            <select name="status" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="all" <?= $filter_status === 'all' ? 'selected' : ''; ?>>-- Tất cả trạng thái --</option>
                <option value="1" <?= $filter_status === '1' ? 'selected' : ''; ?>>✅ Đang làm việc</option>
                <option value="0" <?= $filter_status === '0' ? 'selected' : ''; ?>>🔒 Đã nghỉ việc</option>
            </select>
        </div>
        <div class="col-md-1 col-6 d-flex gap-1">
            <button type="submit" class="btn btn-dark w-100 rounded-3" title="Lọc"><i class="fa-solid fa-filter"></i></button>
            <a href="employees.php" class="btn btn-outline-secondary rounded-3" title="Đặt lại"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- BẢNG DANH SÁCH NHÂN VIÊN -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="table-layout: auto;">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3" style="min-width: 220px;">Nhân viên</th>
                        <th style="min-width: 190px;">Liên hệ &amp; CCCD</th>
                        <th style="min-width: 200px;">Địa chỉ thường trú</th>
                        <th class="text-center" style="min-width: 120px;">Trạng thái</th>
                        <th class="text-end pe-3" style="min-width: 210px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-user-slash fs-1 d-block mb-2 opacity-50"></i>
                                Không tìm thấy hồ sơ nhân viên nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $e): 
                            $eid = intval($e['id']);
                            $avatar_url = !empty($e['avatar']) ? (strpos($e['avatar'], 'http') === 0 ? $e['avatar'] : '../' . $e['avatar']) : '../assets/images/default-avatar.png';
                        ?>
                            <tr id="emp-row-<?= $eid; ?>">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= $avatar_url; ?>" class="rounded-circle border shadow-sm flex-shrink-0" style="width: 42px; height: 42px; object-fit: cover;" onerror="this.src='../assets/images/default-avatar.png'">
                                        <div>
                                            <strong class="text-dark d-block" style="font-size: 14px;"><?= htmlspecialchars($e['fullname']); ?></strong>
                                            <span class="badge bg-light text-muted border px-2 py-0" style="font-size: 10px;">Mã NV: #<?= $eid; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-dark fw-bold"><i class="fa-solid fa-phone text-primary me-1"></i><?= htmlspecialchars($e['phone'] ?: 'Chưa có SĐT'); ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 180px;"><i class="fa-solid fa-envelope text-secondary me-1"></i><?= htmlspecialchars($e['email']); ?></div>
                                    <div class="small text-muted" style="font-size: 11px;"><i class="fa-solid fa-id-card text-muted me-1"></i><?= htmlspecialchars($e['citizen_id'] ?: 'Chưa có CCCD'); ?></div>
                                </td>
                                <td>
                                    <span class="text-dark small text-truncate d-inline-block" style="max-width: 220px;" title="<?= htmlspecialchars($e['address'] ?: ''); ?>">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($e['address'] ?: 'Chưa cập nhật'); ?>
                                    </span>
                                    <?php if (!empty($e['birthday'])): ?>
                                        <div class="small text-muted" style="font-size: 11px;"><i class="fa-solid fa-cake-candles text-warning me-1"></i><?= date('d/m/Y', strtotime($e['birthday'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" id="status-cell-<?= $eid; ?>">
                                    <?php if ($e['status'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-circle-check me-1"></i> Đang làm việc
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-circle-xmark me-1"></i> Đã nghỉ việc
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Xem Chi Tiết Hồ Sơ -->
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-2 px-2 py-1" onclick="viewEmployeeDetail(<?= $eid; ?>)" title="Xem hồ sơ chi tiết">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <!-- Đi tới Lịch làm việc -->
                                        <a href="employee-schedule.php?employee_id=<?= $eid; ?>" class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1" title="Lịch làm việc">
                                            <i class="fa-solid fa-calendar-week"></i>
                                        </a>
                                        <!-- Đi tới Bảng lương -->
                                        <a href="employee-salaries.php?employee_id=<?= $eid; ?>" class="btn btn-sm btn-outline-success rounded-2 px-2 py-1" title="Bảng lương">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        </a>
                                        <!-- Bật / Tắt trạng thái -->
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-2 px-2 py-1" onclick="toggleEmployeeStatus(<?= $eid; ?>)" title="Đổi trạng thái">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                        <!-- Sửa -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1" onclick="editEmployee(<?= $eid; ?>)" title="Sửa hồ sơ">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <!-- Xóa -->
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1" onclick="deleteEmployee(<?= $eid; ?>)" title="Xóa nhân viên">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL THÊM / SỬA NHÂN VIÊN (CÓ CHỌN VỊ TRÍ TRÊN BẢN ĐỒ) -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="employeeModalTitle">
                    <i class="fa-solid fa-user-plus me-2 text-warning"></i>Thêm Mới Hồ Sơ Nhân Viên
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="employeeForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax_save_employee" value="1">
                <input type="hidden" name="emp_id" id="form_emp_id" value="0">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Họ và Tên nhân viên: <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" id="emp_fullname" class="form-control" placeholder="Ví dụ: Nguyễn Văn An" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Email đăng nhập: <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="emp_email" class="form-control" placeholder="Ví dụ: an.nguyen@shoes.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Số điện thoại: <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="emp_phone" class="form-control" placeholder="10 số di động (03, 05, 07, 08, 09)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Số CCCD / CMND:</label>
                            <input type="text" name="citizen_id" id="emp_citizen_id" class="form-control" placeholder="9 hoặc 12 chữ số">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Ngày sinh:</label>
                            <input type="date" name="birthday" id="emp_birthday" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Mật khẩu tài khoản:</label>
                            <input type="password" name="password" id="emp_password" class="form-control" placeholder="Mặc định: 123456 (để trống nếu không đổi)">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark">Địa chỉ thường trú &amp; Vị trí:</label>
                            <div class="input-group">
                                <input type="text" name="address" id="emp_address" class="form-control" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành...">
                                <button type="button" class="btn btn-outline-danger fw-bold" onclick="openMapPicker('emp_address')">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> Bản đồ
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Trạng thái làm việc:</label>
                            <select name="status" id="emp_status" class="form-select">
                                <option value="1">✅ Đang làm việc</option>
                                <option value="0">🔒 Đã nghỉ việc</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark">Ảnh đại diện (Avatar):</label>
                            <input type="file" name="avatar_file" id="emp_avatar_file" class="form-control" accept="image/*">
                            <input type="hidden" name="avatar_url" id="emp_avatar_url" value="">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark">Ghi chú thêm:</label>
                            <textarea name="notes" id="emp_notes" rows="2" class="form-control" placeholder="Ghi chú về thông tin hợp đồng, trình độ, kinh nghiệm..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitEmp" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Hồ Sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL XEM CHI TIẾT HỒ SƠ NHÂN VIÊN (SIÊU ĐẦY ĐỦ, 100% AJAX) -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-id-card-clip me-2 text-warning"></i>Chi Tiết Hồ Sơ Nhân Viên Toàn Diện
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light" id="viewEmployeeBody">
                <div class="text-center py-5">
                    <i class="fa-solid fa-spinner fa-spin fs-2 text-primary mb-2"></i>
                    <div class="text-muted fw-bold">Đang tải toàn bộ dữ liệu hồ sơ nhân viên...</div>
                </div>
            </div>
            <div class="modal-footer bg-white p-3 border-top d-flex justify-content-between">
                <div id="viewModalFooterLeft"></div>
                <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- TÍCH HỢP MODAL CHỌN VỊ TRÍ TRÊN BẢN ĐỒ MAPS -->
<?php include_once __DIR__ . '/../includes/map-picker-modal.php'; ?>

<script>
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

function getEmpModal() {
    const el = document.getElementById('employeeModal');
    if (!el) return null;
    return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
}

function getViewModal() {
    const el = document.getElementById('viewEmployeeModal');
    if (!el) return null;
    return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
}

function initEmployeesPage() {
    const form = document.getElementById('employeeForm');
    if (form && !form.dataset.boundSubmit) {
        form.dataset.boundSubmit = 'true';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitEmp');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(form);

            fetch('employees.php', {
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
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Hồ Sơ';
                }

                if (data.success) {
                    const em = getEmpModal();
                    if (em) em.hide();
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thông báo',
                        text: data.message || 'Không thể lưu hồ sơ.'
                    });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Hồ Sơ';
                }
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: err.message || 'Không thể kết nối máy chủ.' });
            });
        });
    }
}

// Chạy khởi tạo ngay lập tức và khi DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmployeesPage);
} else {
    initEmployeesPage();
}

// Mở modal Thêm nhân viên
function openAddEmployeeModal() {
    const em = getEmpModal();
    if (!em) return;

    document.getElementById('employeeModalTitle').innerHTML = '<i class="fa-solid fa-user-plus me-2 text-warning"></i>Thêm Mới Hồ Sơ Nhân Viên';
    document.getElementById('form_emp_id').value = '0';
    document.getElementById('emp_fullname').value = '';
    document.getElementById('emp_email').value = '';
    document.getElementById('emp_phone').value = '';
    document.getElementById('emp_citizen_id').value = '';
    document.getElementById('emp_birthday').value = '';
    document.getElementById('emp_password').value = '';
    document.getElementById('emp_password').placeholder = 'Mặc định: 123456';
    document.getElementById('emp_address').value = '';
    document.getElementById('emp_status').value = '1';
    document.getElementById('emp_notes').value = '';
    document.getElementById('emp_avatar_file').value = '';
    document.getElementById('emp_avatar_url').value = '';

    em.show();
}
window.openAddEmployeeModal = openAddEmployeeModal;

// Mở modal Sửa nhân viên (Không reload trang)
function editEmployee(empId) {
    fetch(`employees.php?ajax_get_employee=1&emp_id=${empId}`)
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
        if (data.success) {
            const e = data.data;
            document.getElementById('employeeModalTitle').innerHTML = `<i class="fa-solid fa-user-pen me-2 text-warning"></i>Chỉnh Sửa Hồ Sơ: ${e.fullname}`;
            document.getElementById('form_emp_id').value = e.id;
            document.getElementById('emp_fullname').value = e.fullname || '';
            document.getElementById('emp_email').value = e.email || '';
            document.getElementById('emp_phone').value = e.phone || '';
            document.getElementById('emp_citizen_id').value = e.citizen_id || '';
            document.getElementById('emp_birthday').value = e.birthday || '';
            document.getElementById('emp_password').value = '';
            document.getElementById('emp_password').placeholder = 'Để trống nếu giữ nguyên mật khẩu cũ';
            document.getElementById('emp_address').value = e.address || '';
            document.getElementById('emp_status').value = e.status;
            document.getElementById('emp_notes').value = e.notes || '';
            document.getElementById('emp_avatar_file').value = '';
            document.getElementById('emp_avatar_url').value = e.avatar || '';

            const vm = getViewModal();
            if (vm) vm.hide();
            const em = getEmpModal();
            if (em) em.show();
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể nạp thông tin nhân viên.' });
    });
}
window.editEmployee = editEmployee;

// Xem toàn bộ hồ sơ chi tiết nhân viên (100% AJAX, Không reload)
function viewEmployeeDetail(empId) {
    const vm = getViewModal();
    if (!vm) return;

    const body = document.getElementById('viewEmployeeBody');
    const footerLeft = document.getElementById('viewModalFooterLeft');
    if (body) {
        body.innerHTML = `
            <div class="text-center py-5">
                <i class="fa-solid fa-spinner fa-spin fs-2 text-primary mb-2"></i>
                <div class="text-muted fw-bold">Đang tải hồ sơ nhân viên...</div>
            </div>
        `;
    }
    if (footerLeft) footerLeft.innerHTML = '';
    vm.show();

    fetch(`employees.php?ajax_get_employee=1&emp_id=${empId}`)
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
        if (data.success) {
            const e = data.data;
            const schedules = data.schedules || [];
            const salaries  = data.salaries || [];
            const avt = e.avatar ? (e.avatar.startsWith('http') ? e.avatar : '../' + e.avatar) : '../assets/images/default-avatar.png';
            
            // Tính tuổi
            let ageText = 'Chưa có ngày sinh';
            if (e.birthday) {
                const birthYear = new Date(e.birthday).getFullYear();
                const currentYear = new Date().getFullYear();
                ageText = (currentYear - birthYear) + ' tuổi (' + e.birthday + ')';
            }

            // Google Maps URL
            const mapQuery = encodeURIComponent(e.address || 'Vietnam');
            const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${mapQuery}`;

            // HTML Lịch làm việc
            let schedulesHtml = '';
            if (schedules.length === 0) {
                schedulesHtml = '<div class="text-muted small py-2"><i class="fa-solid fa-mug-hot me-1"></i> Chưa xếp ca làm việc nào trong tuần.</div>';
            } else {
                schedulesHtml = '<div class="row g-2">';
                schedules.forEach(s => {
                    const stColor = s.status === 'active' ? 'success' : (s.status === 'leave' ? 'warning' : 'secondary');
                    const stText = s.status === 'active' ? 'Đang trực' : (s.status === 'leave' ? 'Nghỉ phép' : 'Chờ duyệt');
                    schedulesHtml += `
                        <div class="col-md-6 col-12">
                            <div class="p-2 bg-white rounded-3 border-start border-4 border-${stColor} shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark small d-block"><i class="fa-regular fa-calendar me-1 text-primary"></i>${s.day_of_week}</strong>
                                    <span class="text-muted" style="font-size: 12px;">${s.shift_name}</span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-dark text-white" style="font-size: 11px;">${s.start_time.substring(0, 5)} - ${s.end_time.substring(0, 5)}</span>
                                    <span class="badge bg-${stColor}-subtle text-${stColor} d-block mt-1" style="font-size: 10px;">${stText}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                schedulesHtml += '</div>';
            }

            // HTML Lương gần nhất
            let salariesHtml = '';
            if (salaries.length === 0) {
                salariesHtml = '<div class="text-muted small py-2"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Chưa có bản ghi lương nào được khởi tạo.</div>';
            } else {
                salariesHtml = '<div class="table-responsive"><table class="table table-sm table-bordered bg-white mb-0 text-nowrap"><thead class="table-light small"><tr><th>Kỳ lương</th><th>Lương CB</th><th>Ngày công</th><th>Thực lãnh</th><th>Thanh toán</th></tr></thead><tbody class="small">';
                salaries.forEach(sl => {
                    const isPaid = sl.status === 'paid';
                    salariesHtml += `
                        <tr>
                            <td><strong>${sl.month_year}</strong></td>
                            <td>${Number(sl.base_salary).toLocaleString('vi-VN')} đ</td>
                            <td>${sl.work_days}/26 ngày</td>
                            <td class="fw-bold text-success">${Number(sl.total_salary).toLocaleString('vi-VN')} đ</td>
                            <td>${isPaid ? '<span class="badge bg-success-subtle text-success">Đã thanh toán</span>' : '<span class="badge bg-warning-subtle text-warning">Chưa thanh toán</span>'}</td>
                        </tr>
                    `;
                });
                salariesHtml += '</tbody></table></div>';
            }

            if (body) {
                body.innerHTML = `
                    <div class="row g-4">
                        <!-- CỘT TRÁI: AVATAR & THÔNG TIN CỐT LÕI -->
                        <div class="col-lg-4 col-md-5 text-center">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                                <img src="${avt}" class="rounded-circle border shadow-sm mx-auto mb-3" style="width: 120px; height: 120px; object-fit: cover;" onerror="this.src='../assets/images/default-avatar.png'">
                                <h5 class="fw-bold text-dark mb-1">${e.fullname}</h5>
                                <span class="badge bg-light text-muted border rounded-pill px-3 py-1 mb-2">Mã nhân viên: #${e.id}</span>
                                
                                <div class="my-2" id="detail-status-badge-${e.id}">
                                    ${e.status == 1 
                                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> Đang làm việc</span>'
                                        : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2"><i class="fa-solid fa-circle-xmark me-1"></i> Đã nghỉ việc</span>'}
                                </div>

                                <hr class="my-3">

                                <div class="text-start small">
                                    <div class="mb-2"><i class="fa-solid fa-phone text-primary me-2"></i><strong>SĐT:</strong> <a href="tel:${e.phone || ''}" class="text-decoration-none fw-bold">${e.phone || 'Chưa cập nhật'}</a></div>
                                    <div class="mb-2"><i class="fa-solid fa-envelope text-danger me-2"></i><strong>Email:</strong> <a href="mailto:${e.email}" class="text-decoration-none">${e.email}</a></div>
                                    <div class="mb-2"><i class="fa-solid fa-id-card text-secondary me-2"></i><strong>CCCD:</strong> <span class="fw-semibold">${e.citizen_id || 'Chưa cập nhật'}</span></div>
                                    <div class="mb-2"><i class="fa-solid fa-cake-candles text-warning me-2"></i><strong>Ngày sinh:</strong> ${ageText}</div>
                                    <div class="mb-2"><i class="fa-solid fa-clock text-info me-2"></i><strong>Ngày gia nhập:</strong> ${e.created_at || 'Mới'}</div>
                                </div>
                            </div>
                        </div>

                        <!-- CỘT PHẢI: ĐỊA CHỈ & BẢN ĐỒ & LỊCH & LƯƠNG -->
                        <div class="col-lg-8 col-md-7">
                            <!-- ĐỊA CHỈ VÀ NÚT XEM TRÊN BẢN ĐỒ -->
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                                <h6 class="fw-bold text-uppercase text-muted small mb-2"><i class="fa-solid fa-map-location-dot text-danger me-2"></i>Địa Chỉ &amp; Vị Trí</h6>
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-2 bg-light rounded-3">
                                    <div>
                                        <strong class="text-dark d-block">${e.address || 'Chưa cập nhật địa chỉ cụ thể'}</strong>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="${googleMapsUrl}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill fw-bold">
                                            <i class="fa-solid fa-location-arrow me-1"></i> Xem trên Google Maps
                                        </a>
                                    </div>
                                </div>
                                ${e.notes ? `<div class="mt-2 small text-muted fst-italic"><b>Ghi chú:</b> ${e.notes}</div>` : ''}
                            </div>

                            <!-- LỊCH LÀM VIỆC TRONG TUẦN -->
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-uppercase text-muted small mb-0"><i class="fa-solid fa-calendar-week text-primary me-2"></i>Lịch Làm Việc (${schedules.length} ca)</h6>
                                    <a href="employee-schedule.php?employee_id=${e.id}" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold">Quản lý lịch <i class="fa-solid fa-arrow-right ms-1"></i></a>
                                </div>
                                ${schedulesHtml}
                            </div>

                            <!-- LỊCH SỬ BẢNG LƯƠNG -->
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-uppercase text-muted small mb-0"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Lịch Sử Bảng Lương</h6>
                                    <a href="employee-salaries.php?employee_id=${e.id}" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold text-success">Xem tất cả lương <i class="fa-solid fa-arrow-right ms-1"></i></a>
                                </div>
                                ${salariesHtml}
                            </div>
                        </div>
                    </div>
                `;
            }

            if (footerLeft) {
                footerLeft.innerHTML = `
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm" onclick="editEmployee(${e.id})">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Chỉnh Sửa Hồ Sơ
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 fw-bold" onclick="toggleEmployeeStatus(${e.id})">
                            <i class="fa-solid fa-power-off me-1"></i> Đổi Trạng Thái
                        </button>
                        <a href="employee-schedule.php?employee_id=${e.id}" class="btn btn-outline-info rounded-pill px-3 fw-bold">
                            <i class="fa-solid fa-calendar-days me-1"></i> Lịch Làm Việc
                        </a>
                        <a href="employee-salaries.php?employee_id=${e.id}" class="btn btn-outline-success rounded-pill px-3 fw-bold">
                            <i class="fa-solid fa-money-check-dollar me-1"></i> Bảng Lương
                        </a>
                    </div>
                `;
            }
        } else {
            if (body) body.innerHTML = `<div class="alert alert-danger mb-0">${data.message}</div>`;
        }
    })
    .catch(err => {
        console.error(err);
        if (body) body.innerHTML = '<div class="alert alert-danger mb-0">Lỗi khi tải thông tin hồ sơ nhân viên.</div>';
    });
}
window.viewEmployeeDetail = viewEmployeeDetail;

// Bật / Tắt trạng thái Đang làm / Đã nghỉ (Live AJAX)
function toggleEmployeeStatus(empId) {
    const formData = new FormData();
    formData.append('ajax_toggle_status', '1');
    formData.append('emp_id', empId);

    fetch('employees.php', {
        method: 'POST',
        body: formData
    })
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
        if (data.success) {
            const cell = document.getElementById('status-cell-' + empId);
            if (cell) { cell.innerHTML = data.badge_html; }
            const detailBadge = document.getElementById('detail-status-badge-' + empId);
            if (detailBadge) { detailBadge.innerHTML = data.badge_html; }
            Toast.fire({ icon: 'success', title: data.message });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể đổi trạng thái nhân viên.' });
    });
}
window.toggleEmployeeStatus = toggleEmployeeStatus;

// Xóa nhân viên Live AJAX (Không load lại trang)
function deleteEmployee(empId) {
    Swal.fire({
        title: 'Xác nhận xóa nhân viên?',
        html: `Bạn có chắc muốn xóa vĩnh viễn nhân viên <b>#${empId}</b> cùng toàn bộ lịch làm việc và bảng lương?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Vĩnh Viễn',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_employee', '1');
            formData.append('emp_id', empId);

            fetch('employees.php', {
                method: 'POST',
                body: formData
            })
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
                if (data.success) {
                    const row = document.getElementById('emp-row-' + empId);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(50px)';
                        setTimeout(() => row.remove(), 400);
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Không thể xóa', text: data.message });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể xóa nhân viên.' });
            });
        }
    });
}
window.deleteEmployee = deleteEmployee;
</script>

    </div>
</div>
</body>
</html>