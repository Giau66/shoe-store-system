<?php 
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// =========================================================================
// 1. CÁC ENDPOINT AJAX DÀNH CHO LỊCH LÀM VIỆC (THAO TÁC KHÔNG LOAD LẠI TRANG)
// =========================================================================

// AJAX 1.1: Lấy chi tiết lịch làm việc để chỉnh sửa
if (isset($_GET['ajax_get_schedule'])) {
    header('Content-Type: application/json; charset=utf-8');
    $sch_id = intval($_GET['sch_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT s.*, e.fullname, e.position, e.avatar 
        FROM employee_schedules s 
        JOIN employees e ON s.employee_id = e.id 
        WHERE s.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $sch_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        echo json_encode(['success' => true, 'data' => $res]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy ca làm việc.']);
    exit;
}

// AJAX 1.2: Lưu ca làm việc (Thêm mới hoặc Cập nhật)
if (isset($_POST['ajax_save_schedule'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền sắp xếp ca làm việc!']);
        exit;
    }

    $sch_id      = intval($_POST['sch_id'] ?? 0);
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $day_of_week = trim($_POST['day_of_week'] ?? 'Thứ Hai');
    $shift_name  = trim($_POST['shift_name'] ?? 'Ca Sáng (08:00 - 16:00)');
    $start_time  = trim($_POST['start_time'] ?? '08:00');
    $end_time    = trim($_POST['end_time'] ?? '16:00');
    $status      = trim($_POST['status'] ?? 'active');
    $note        = trim($_POST['note'] ?? '');
    $apply_all   = isset($_POST['apply_weekdays']) && $_POST['apply_weekdays'] == '1';

    if ($employee_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhân viên!']);
        exit;
    }

    if ($apply_all && $sch_id == 0) {
        // Gán nhanh ca làm việc từ Thứ Hai đến Thứ Bảy
        $weekdays = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
        $stmt_ins = $conn->prepare("
            INSERT INTO employee_schedules (employee_id, day_of_week, shift_name, start_time, end_time, status, note, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        foreach ($weekdays as $d) {
            $stmt_ins->bind_param("issssss", $employee_id, $d, $shift_name, $start_time, $end_time, $status, $note);
            $stmt_ins->execute();
        }
        $stmt_ins->close();

        echo json_encode(['success' => true, 'message' => 'Đã gán lịch làm việc cả tuần (Thứ Hai - Thứ Bảy) cho nhân viên thành công!']);
        exit;
    }

    if ($sch_id > 0) {
        // Cập nhật
        $stmt_u = $conn->prepare("
            UPDATE employee_schedules SET 
                employee_id = ?, day_of_week = ?, shift_name = ?, start_time = ?, end_time = ?, status = ?, note = ?
            WHERE id = ?
        ");
        $stmt_u->bind_param("issssssi", $employee_id, $day_of_week, $shift_name, $start_time, $end_time, $status, $note, $sch_id);
        if ($stmt_u->execute()) {
            $stmt_u->close();
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật ca làm việc thành công!']);
            exit;
        }
    } else {
        // Thêm mới 1 ca
        $stmt_new = $conn->prepare("
            INSERT INTO employee_schedules (employee_id, day_of_week, shift_name, start_time, end_time, status, note, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt_new->bind_param("issssss", $employee_id, $day_of_week, $shift_name, $start_time, $end_time, $status, $note);
        if ($stmt_new->execute()) {
            $stmt_new->close();
            echo json_encode(['success' => true, 'message' => 'Đã tạo ca làm việc mới thành công!']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu ca làm việc.']);
    exit;
}

// AJAX 1.3: Chuyển trạng thái ca làm việc
if (isset($_POST['ajax_toggle_schedule_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $sch_id = intval($_POST['sch_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? 'active');

    $stmt = $conn->prepare("UPDATE employee_schedules SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $sch_id);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái ca làm việc!']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không thể đổi trạng thái.']);
    exit;
}

// AJAX 1.4: Xóa ca làm việc
if (isset($_POST['ajax_delete_schedule'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền xóa ca làm việc!']);
        exit;
    }

    $sch_id = intval($_POST['sch_id'] ?? 0);
    if ($conn->query("DELETE FROM employee_schedules WHERE id = $sch_id")) {
        echo json_encode(['success' => true, 'message' => "Đã xóa ca làm việc #$sch_id!"]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa.']);
    exit;
}

include_once 'includes/header.php';

// =========================================================================
// 2. LỌC & TÌM KIẾM LỊCH LÀM VIỆC
// =========================================================================
$filter_emp   = intval($_GET['employee_id'] ?? 0);
$filter_day   = trim($_GET['day'] ?? 'all');
$filter_shift = trim($_GET['shift'] ?? 'all');
$filter_stat  = trim($_GET['status'] ?? 'all');
$filter_view  = trim($_GET['view'] ?? 'matrix'); // 'matrix' (ma trận tuần) hoặc 'list' (danh sách)

$where = ["1=1"];
if ($filter_emp > 0) {
    $where[] = "s.employee_id = $filter_emp";
}
if ($filter_day !== 'all') {
    $d_clean = $conn->real_escape_string($filter_day);
    $where[] = "(s.day_of_week = '$d_clean' OR s.day_of_week LIKE '%" . substr($d_clean, -1) . "%')";
}
if ($filter_shift !== 'all') {
    $sh_clean = $conn->real_escape_string($filter_shift);
    $where[] = "s.shift_name LIKE '%$sh_clean%'";
}
if ($filter_stat !== 'all') {
    $st_clean = $conn->real_escape_string($filter_stat);
    $where[] = "s.status = '$st_clean'";
}

$where_sql = implode(' AND ', $where);

// Lấy danh sách nhân viên
$all_emps = [];
$e_res = $conn->query("SELECT id, fullname, position, department, work_shift, avatar, phone FROM employees WHERE status = 1 ORDER BY id ASC");
if ($e_res) {
    while ($r = $e_res->fetch_assoc()) {
        $all_emps[] = $r;
    }
}

// Lấy danh sách ca làm việc
$schedules_res = $conn->query("
    SELECT s.*, e.fullname, e.position, e.department, e.avatar, e.phone 
    FROM employee_schedules s 
    JOIN employees e ON s.employee_id = e.id 
    WHERE $where_sql 
    ORDER BY s.employee_id ASC, s.id ASC
");
$schedules = [];
if ($schedules_res) {
    while ($r = $schedules_res->fetch_assoc()) {
        $schedules[] = $r;
    }
}

// Bảng chuẩn 7 ngày trong tuần
$standard_days = [
    'Thứ Hai'  => ['aliases' => ['Thứ Hai', 'Thứ 2', 'Monday'], 'short' => 'T2', 'bg' => 'rgba(59, 130, 246, 0.08)', 'color' => '#2563eb'],
    'Thứ Ba'   => ['aliases' => ['Thứ Ba', 'Thứ 3', 'Tuesday'], 'short' => 'T3', 'bg' => 'rgba(16, 185, 129, 0.08)', 'color' => '#059669'],
    'Thứ Tư'   => ['aliases' => ['Thứ Tư', 'Thứ 4', 'Wednesday'], 'short' => 'T4', 'bg' => 'rgba(245, 158, 11, 0.08)', 'color' => '#d97706'],
    'Thứ Năm'  => ['aliases' => ['Thứ Năm', 'Thứ 5', 'Thursday'], 'short' => 'T5', 'bg' => 'rgba(139, 92, 246, 0.08)', 'color' => '#7c3aed'],
    'Thứ Sáu'  => ['aliases' => ['Thứ Sáu', 'Thứ 6', 'Friday'], 'short' => 'T6', 'bg' => 'rgba(6, 182, 212, 0.08)', 'color' => '#0891b2'],
    'Thứ Bảy'  => ['aliases' => ['Thứ Bảy', 'Thứ 7', 'Saturday'], 'short' => 'T7', 'bg' => 'rgba(236, 72, 153, 0.08)', 'color' => '#db2777'],
    'Chủ Nhật' => ['aliases' => ['Chủ Nhật', 'Chủ nhật', 'Sunday'], 'short' => 'CN', 'bg' => 'rgba(239, 68, 68, 0.08)', 'color' => '#dc2626']
];

function normalizeDayName($dayStr) {
    global $standard_days;
    foreach ($standard_days as $std => $info) {
        foreach ($info['aliases'] as $alias) {
            if (stripos($dayStr, $alias) !== false || $dayStr === $alias) {
                return $std;
            }
        }
    }
    return 'Thứ Hai';
}

// Xây dựng ma trận lịch làm việc theo [Nhân viên ID][Ngày chuẩn]
$matrix = [];
foreach ($all_emps as $e) {
    $matrix[$e['id']] = [];
    foreach (array_keys($standard_days) as $sd) {
        $matrix[$e['id']][$sd] = [];
    }
}

$total_shifts_active = 0;
$total_shifts_off    = 0;
$shift_counts = ['Sáng' => 0, 'Chiều' => 0, 'Tối' => 0, 'Hành chính' => 0];

foreach ($schedules as $s) {
    $normDay = normalizeDayName($s['day_of_week']);
    $eId = intval($s['employee_id']);
    if (isset($matrix[$eId][$normDay])) {
        $matrix[$eId][$normDay][] = $s;
    }

    if ($s['status'] === 'off') {
        $total_shifts_off++;
    } else {
        $total_shifts_active++;
        if (stripos($s['shift_name'], 'Sáng') !== false) $shift_counts['Sáng']++;
        elseif (stripos($s['shift_name'], 'Chiều') !== false) $shift_counts['Chiều']++;
        elseif (stripos($s['shift_name'], 'Tối') !== false) $shift_counts['Tối']++;
        else $shift_counts['Hành chính']++;
    }
}
?>

<style>
/* ── Modern Timetable & Schedule Styles ───────────────────────── */
.schedule-hero-card {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
    border-radius: 24px;
    padding: 1.75rem 2rem;
    color: #ffffff;
    box-shadow: 0 12px 36px rgba(49, 46, 129, 0.25);
    position: relative;
    overflow: hidden;
    margin-bottom: 1.75rem;
}
.schedule-hero-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 380px;
    height: 380px;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.35), transparent 70%);
    pointer-events: none;
}
.stat-mini-pill {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 16px;
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #ffffff;
}
.stat-mini-pill i {
    font-size: 1.4rem;
}

/* Schedule Matrix Table */
.matrix-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.matrix-table {
    margin-bottom: 0;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.matrix-table th {
    padding: 1rem 0.75rem;
    font-weight: 800;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-bottom: 2px solid #e2e8f0;
    text-align: center;
    vertical-align: middle;
}
.matrix-table td {
    padding: 0.75rem 0.5rem;
    vertical-align: top;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f8fafc;
    min-width: 130px;
}
.matrix-table tr:hover td {
    background-color: rgba(248, 250, 252, 0.75);
}
.emp-col-header {
    min-width: 220px !important;
    text-align: left !important;
    position: sticky;
    left: 0;
    background: #ffffff;
    z-index: 2;
    box-shadow: 4px 0 10px rgba(0,0,0,0.02);
}
.matrix-table td:first-child {
    position: sticky;
    left: 0;
    background: #ffffff;
    z-index: 2;
    box-shadow: 4px 0 10px rgba(0,0,0,0.02);
}

/* Shift Badges */
.shift-item-badge {
    border-radius: 12px;
    padding: 8px 10px;
    margin-bottom: 6px;
    font-size: 0.78rem;
    display: block;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    cursor: pointer;
    position: relative;
}
.shift-item-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}
.shift-morning {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.shift-afternoon {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}
.shift-night {
    background: #faf5ff;
    color: #7e22ce;
    border-color: #e9d5ff;
}
.shift-office {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
}
.shift-off {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
    border-style: dashed;
}
.shift-actions-btn {
    opacity: 0;
    transition: opacity 0.2s ease;
}
.shift-item-badge:hover .shift-actions-btn {
    opacity: 1;
}

/* Tab Pills */
.nav-tab-pill {
    padding: 8px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.88rem;
    color: #475569;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.nav-tab-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.nav-tab-pill.active {
    background: #4338ca;
    color: #ffffff;
    border-color: #4338ca;
    box-shadow: 0 4px 14px rgba(67, 56, 202, 0.3);
}
</style>

<!-- ══════════════════════════════════════════════════════════════
     HERO BANNER & QUICK STATS
══════════════════════════════════════════════════════════════════ -->
<div class="schedule-hero-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background:rgba(255,255,255,0.15);font-size:0.8rem;font-weight:600;">
                <i class="fa-solid fa-calendar-check text-warning"></i> Quản Lý Lịch Trực &amp; Phân Ca Nhân Sự 2026
            </div>
            <h3 class="fw-bold mb-1">Lịch Làm Việc &amp; Bảng Phân Ca</h3>
            <p class="mb-0 text-white-50" style="font-size:0.9rem;">
                Theo dõi ca làm việc tuần, ma trận thời khóa biểu và trạng thái trực ban của toàn bộ nhân viên cửa hàng.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($user_role === 'admin'): ?>
            <button class="btn btn-warning rounded-pill px-4 fw-bold shadow" onclick="openQuickBatchModal()">
                <i class="fa-solid fa-bolt me-1"></i> Phân Ca Nhanh Cả Tuần
            </button>
            <button class="btn btn-light rounded-pill px-4 fw-bold shadow" onclick="openAddScheduleModal()">
                <i class="fa-solid fa-plus me-1"></i> Thêm Ca Mới
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mt-3 pt-3 border-top border-white border-opacity-10">
        <div class="col-lg-3 col-6">
            <div class="stat-mini-pill">
                <i class="fa-solid fa-users text-info"></i>
                <div>
                    <div style="font-size:1.15rem;font-weight:800;"><?= count($all_emps) ?> Nhân sự</div>
                    <small class="text-white-50">Tổng số nhân viên trực</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="stat-mini-pill">
                <i class="fa-solid fa-sun text-warning"></i>
                <div>
                    <div style="font-size:1.15rem;font-weight:800;"><?= $shift_counts['Sáng'] ?> Ca Sáng</div>
                    <small class="text-white-50">Khung giờ 08:00 - 16:00</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="stat-mini-pill">
                <i class="fa-solid fa-moon text-purple" style="color:#c084fc;"></i>
                <div>
                    <div style="font-size:1.15rem;font-weight:800;"><?= $shift_counts['Chiều'] + $shift_counts['Tối'] ?> Ca Chiều/Tối</div>
                    <small class="text-white-50">Khung giờ 14:00 - 22:00</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="stat-mini-pill">
                <i class="fa-solid fa-mug-hot text-emerald" style="color:#34d399;"></i>
                <div>
                    <div style="font-size:1.15rem;font-weight:800;"><?= $total_shifts_off ?> Ca Nghỉ Tuần</div>
                    <small class="text-white-50">Ngày nghỉ quy định</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     BỘ LỌC TÌM KIẾM & ĐIỀU KHIỂN
══════════════════════════════════════════════════════════════════ -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" class="row g-2 align-items-center">
        <!-- Lọc theo nhân viên -->
        <div class="col-lg-3 col-md-6 col-12">
            <label class="form-label text-muted small mb-1 fw-bold"><i class="fa-solid fa-user me-1"></i> Nhân viên:</label>
            <select name="employee_id" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="0">-- Tất cả nhân viên (<?= count($all_emps) ?>) --</option>
                <?php foreach ($all_emps as $ae): ?>
                    <option value="<?= $ae['id']; ?>" <?= $filter_emp == $ae['id'] ? 'selected' : ''; ?>>
                        👤 <?= htmlspecialchars($ae['fullname']); ?> (<?= htmlspecialchars($ae['position']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Lọc theo ngày -->
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label text-muted small mb-1 fw-bold"><i class="fa-regular fa-calendar me-1"></i> Thứ / Ngày:</label>
            <select name="day" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="all" <?= $filter_day === 'all' ? 'selected' : ''; ?>>-- Cả tuần (7 ngày) --</option>
                <?php foreach (array_keys($standard_days) as $sd): ?>
                    <option value="<?= $sd; ?>" <?= $filter_day === $sd ? 'selected' : ''; ?>><?= $sd; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Lọc theo ca -->
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label text-muted small mb-1 fw-bold"><i class="fa-solid fa-clock me-1"></i> Ca làm việc:</label>
            <select name="shift" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="all" <?= $filter_shift === 'all' ? 'selected' : ''; ?>>-- Tất cả các ca --</option>
                <option value="Sáng" <?= $filter_shift === 'Sáng' ? 'selected' : ''; ?>>☀️ Ca Sáng (08:00 - 16:00)</option>
                <option value="Chiều" <?= $filter_shift === 'Chiều' ? 'selected' : ''; ?>>⛅ Ca Chiều (14:00 - 22:00)</option>
                <option value="Tối" <?= $filter_shift === 'Tối' ? 'selected' : ''; ?>>🌙 Ca Tối (16:00 - 22:00)</option>
                <option value="Hành" <?= $filter_shift === 'Hành' ? 'selected' : ''; ?>>🏢 Ca Hành Chính</option>
            </select>
        </div>

        <!-- Lọc theo trạng thái -->
        <div class="col-lg-2 col-md-6 col-6">
            <label class="form-label text-muted small mb-1 fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Trạng thái:</label>
            <select name="status" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="all" <?= $filter_stat === 'all' ? 'selected' : ''; ?>>-- Tất cả --</option>
                <option value="active" <?= $filter_stat === 'active' ? 'selected' : ''; ?>>🟢 Đang trực</option>
                <option value="off" <?= $filter_stat === 'off' ? 'selected' : ''; ?>>🏖️ Nghỉ tuần</option>
                <option value="leave" <?= $filter_stat === 'leave' ? 'selected' : ''; ?>>🟡 Nghỉ phép</option>
            </select>
        </div>

        <!-- Chế độ xem & Thao tác -->
        <div class="col-lg-3 col-md-6 col-12 d-flex gap-2 align-items-end pt-lg-3">
            <button type="submit" class="btn btn-dark rounded-3 px-3 w-100 fw-bold">
                <i class="fa-solid fa-filter me-1"></i> Lọc
            </button>
            <a href="employee-schedule.php" class="btn btn-outline-secondary rounded-3 px-3" title="Đặt lại bộ lọc">
                <i class="fa-solid fa-rotate-left"></i>
            </a>
        </div>
    </form>
</div>

<!-- ══════════════════════════════════════════════════════════════
     CHẾ ĐỘ XEM TAB: MA TRẬN TUẦN & BẢNG DANH SÁCH
══════════════════════════════════════════════════════════════════ -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="d-flex gap-2">
        <button class="nav-tab-pill <?= $filter_view === 'matrix' ? 'active' : '' ?>" onclick="switchScheduleView('matrix')">
            <i class="fa-solid fa-table-cells"></i> Ma Trận Tuần (Thời Khóa Biểu)
        </button>
        <button class="nav-tab-pill <?= $filter_view === 'list' ? 'active' : '' ?>" onclick="switchScheduleView('list')">
            <i class="fa-solid fa-list-ul"></i> Danh Sách Chi Tiết (<?= count($schedules) ?> ca)
        </button>
    </div>
    <div class="text-muted small">
        <i class="fa-solid fa-circle-info text-primary me-1"></i> Nhấp vào từng ca trực để xem chi tiết hoặc bấm Sửa/Đổi ca.
    </div>
</div>

<!-- VIEW 1: MA TRẬN THỜI KHÓA BIỂU TUẦN (WEEKLY MATRIX) -->
<div id="scheduleMatrixView" class="<?= $filter_view === 'matrix' ? '' : 'd-none' ?>">
    <div class="matrix-card">
        <div class="table-responsive">
            <table class="matrix-table">
                <thead>
                    <tr class="bg-light text-secondary">
                        <th class="emp-col-header">Nhân Viên / Vị Trí</th>
                        <?php foreach ($standard_days as $dayName => $dayInfo): ?>
                            <th style="background: <?= $dayInfo['bg'] ?>; color: <?= $dayInfo['color'] ?>;">
                                <div class="fs-6"><?= $dayName ?></div>
                                <span class="badge rounded-pill text-uppercase" style="background: <?= $dayInfo['color'] ?>; color: #fff; font-size: 0.65rem;">
                                    <?= $dayInfo['short'] ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $displayedEmps = ($filter_emp > 0) ? array_filter($all_emps, fn($e) => $e['id'] == $filter_emp) : $all_emps;
                    foreach ($displayedEmps as $emp): 
                        $empId = $emp['id'];
                    ?>
                        <tr>
                            <!-- Cột Thông Tin Nhân Viên -->
                            <td class="emp-col-header">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= htmlspecialchars($emp['avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150') ?>" 
                                         alt="<?= htmlspecialchars($emp['fullname']) ?>" 
                                         class="rounded-circle object-fit-cover shadow-sm" style="width: 42px; height: 42px;">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-0 fw-bold text-dark text-truncate" style="font-size: 0.9rem;"><?= htmlspecialchars($emp['fullname']) ?></h6>
                                        <small class="text-primary fw-semibold d-block text-truncate" style="font-size: 0.75rem;"><?= htmlspecialchars($emp['position']) ?></small>
                                        <small class="text-muted" style="font-size: 0.7rem;"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($emp['phone']) ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- 7 Cột Ngày Trong Tuần -->
                            <?php foreach (array_keys($standard_days) as $dayName): 
                                $dayShifts = $matrix[$empId][$dayName] ?? [];
                            ?>
                                <td>
                                    <?php if (empty($dayShifts)): ?>
                                        <div class="text-center py-3 text-muted" style="opacity: 0.35;">
                                            <i class="fa-solid fa-minus"></i>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($dayShifts as $s): 
                                            $isOff = ($s['status'] === 'off');
                                            $badgeClass = 'shift-office';
                                            $icon = 'fa-solid fa-briefcase';

                                            if ($isOff) {
                                                $badgeClass = 'shift-off';
                                                $icon = 'fa-solid fa-mug-hot';
                                            } elseif (stripos($s['shift_name'], 'Sáng') !== false) {
                                                $badgeClass = 'shift-morning';
                                                $icon = 'fa-solid fa-sun';
                                            } elseif (stripos($s['shift_name'], 'Chiều') !== false) {
                                                $badgeClass = 'shift-afternoon';
                                                $icon = 'fa-solid fa-cloud-sun';
                                            } elseif (stripos($s['shift_name'], 'Tối') !== false) {
                                                $badgeClass = 'shift-night';
                                                $icon = 'fa-solid fa-moon';
                                            }
                                        ?>
                                            <div class="shift-item-badge <?= $badgeClass ?>" onclick="editSchedule(<?= $s['id'] ?>)">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold"><i class="<?= $icon ?> me-1"></i><?= htmlspecialchars($s['shift_name']) ?></span>
                                                    <?php if ($user_role === 'admin'): ?>
                                                    <div class="shift-actions-btn">
                                                        <button class="btn btn-link text-danger p-0 ms-1 border-0" onclick="event.stopPropagation(); deleteSchedule(<?= $s['id'] ?>)" title="Xóa ca này">
                                                            <i class="fa-solid fa-trash-can" style="font-size:0.75rem;"></i>
                                                        </button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center opacity-75" style="font-size: 0.72rem;">
                                                    <span><i class="fa-regular fa-clock me-1"></i><?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?></span>
                                                    <span class="badge <?= $isOff ? 'bg-secondary' : 'bg-success' ?> rounded-pill" style="font-size: 0.65rem;">
                                                        <?= $isOff ? 'Nghỉ' : 'Trực' ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($s['note'])): ?>
                                                    <small class="d-block text-truncate mt-1 opacity-75 fst-italic" style="font-size: 0.68rem;" title="<?= htmlspecialchars($s['note']) ?>">
                                                        💬 <?= htmlspecialchars($s['note']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if ($user_role === 'admin'): ?>
                                    <button class="btn btn-sm btn-outline-primary border-0 w-100 rounded-2 py-1 mt-1 opacity-25 hover-opacity-100" 
                                            style="font-size:0.7rem;" 
                                            onclick="openAddForEmpDay(<?= $empId ?>, '<?= $dayName ?>')" title="Thêm ca cho ngày này">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VIEW 2: DANH SÁCH CHI TIẾT (LIST VIEW) -->
<div id="scheduleListView" class="<?= $filter_view === 'list' ? '' : 'd-none' ?>">
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nhân Viên</th>
                        <th>Thứ / Ngày</th>
                        <th>Ca Làm Việc</th>
                        <th>Khung Giờ</th>
                        <th>Trạng Thái</th>
                        <th>Ghi Chú</th>
                        <?php if ($user_role === 'admin'): ?>
                        <th class="text-end pe-4">Thao Tác</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-calendar-xmark fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                Không tìm thấy ca làm việc nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $s): 
                            $isOff = ($s['status'] === 'off');
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $s['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= htmlspecialchars($s['avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150') ?>" 
                                             class="rounded-circle object-fit-cover shadow-sm" style="width: 36px; height: 36px;">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['fullname']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($s['position']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-pill">
                                        <i class="fa-regular fa-calendar me-1"></i><?= htmlspecialchars($s['day_of_week']) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($s['shift_name']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-pill">
                                        <i class="fa-regular fa-clock me-1"></i><?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isOff): ?>
                                        <span class="badge bg-secondary rounded-pill px-3 py-2">🏖️ Nghỉ tuần</span>
                                    <?php elseif ($s['status'] === 'leave'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2">🟡 Nghỉ phép</span>
                                    <?php else: ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2">🟢 Đang trực</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($s['note'] ?: '—') ?>
                                </td>
                                <?php if ($user_role === 'admin'): ?>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" onclick="editSchedule(<?= $s['id'] ?>)">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Sửa
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="deleteSchedule(<?= $s['id'] ?>)">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL THÊM / SỬA CA LÀM VIỆC (AJAX MODAL)
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold" id="scheduleModalTitle">
                    <i class="fa-solid fa-calendar-plus me-2 text-warning"></i> Thêm Ca Trực Mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleForm" onsubmit="handleSaveSchedule(event)">
                <input type="hidden" name="ajax_save_schedule" value="1">
                <input type="hidden" name="sch_id" id="form_sch_id" value="0">

                <div class="modal-body p-4">
                    <!-- Chọn nhân viên -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Nhân viên nhận ca <span class="text-danger">*</span></label>
                        <select name="employee_id" id="form_employee_id" class="form-select rounded-3" required>
                            <option value="">-- Chọn nhân viên --</option>
                            <?php foreach ($all_emps as $ae): ?>
                                <option value="<?= $ae['id']; ?>">👤 <?= htmlspecialchars($ae['fullname']); ?> (<?= htmlspecialchars($ae['position']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ngày trong tuần & Tên ca -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Ngày trong tuần</label>
                            <select name="day_of_week" id="form_day_of_week" class="form-select rounded-3">
                                <?php foreach (array_keys($standard_days) as $sd): ?>
                                    <option value="<?= $sd; ?>"><?= $sd; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Tên ca làm</label>
                            <input type="text" name="shift_name" id="form_shift_name" class="form-control rounded-3" value="Ca Sáng (08:00 - 16:00)" required>
                        </div>
                    </div>

                    <!-- Giờ làm việc -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Giờ bắt đầu</label>
                            <input type="time" name="start_time" id="form_start_time" class="form-control rounded-3" value="08:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Giờ kết thúc</label>
                            <input type="time" name="end_time" id="form_end_time" class="form-control rounded-3" value="16:00" required>
                        </div>
                    </div>

                    <!-- Trạng thái & Ghi chú -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Trạng thái ca</label>
                        <select name="status" id="form_status" class="form-select rounded-3">
                            <option value="active">🟢 Đang trực (Áp dụng)</option>
                            <option value="off">🏖️ Nghỉ tuần</option>
                            <option value="leave">🟡 Nghỉ phép có báo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Ghi chú phân công</label>
                        <textarea name="note" id="form_note" class="form-control rounded-3" rows="2" placeholder="Ví dụ: Phụ trách quầy tư vấn, kiểm kê kho..."></textarea>
                    </div>

                    <!-- Tùy chọn gán cả tuần (chỉ khi thêm mới) -->
                    <div class="form-check p-3 bg-light rounded-3" id="applyWeekdaysContainer">
                        <input class="form-check-input" type="checkbox" name="apply_weekdays" value="1" id="form_apply_weekdays">
                        <label class="form-check-label small fw-bold text-dark" for="form_apply_weekdays">
                            ⚡ Tự động gán ca này cho tất cả các ngày trong tuần (Thứ Hai $\rightarrow$ Thứ Bảy)
                        </label>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-save me-1"></i> Lưu Ca Làm Việc
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     SCRIPTS & AJAX LOGIC
══════════════════════════════════════════════════════════════════ -->
<script>
window.scheduleModalObj = window.scheduleModalObj || null;

(function initSchedulePage() {
    const modalEl = document.getElementById('scheduleModal');
    if (modalEl) {
        window.scheduleModalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    }
})();

window.switchScheduleView = function(viewType) {
    const matrixView = document.getElementById('scheduleMatrixView');
    const listView = document.getElementById('scheduleListView');
    
    document.querySelectorAll('.nav-tab-pill').forEach(btn => btn.classList.remove('active'));

    if (viewType === 'matrix') {
        if (matrixView) matrixView.classList.remove('d-none');
        if (listView) listView.classList.add('d-none');
    } else {
        if (matrixView) matrixView.classList.add('d-none');
        if (listView) listView.classList.remove('d-none');
    }

    if (window.event && window.event.target) {
        const btn = window.event.target.closest('.nav-tab-pill');
        if (btn) btn.classList.add('active');
    }
};

window.openAddScheduleModal = function() {
    const form = document.getElementById('scheduleForm');
    if (form) form.reset();
    document.getElementById('form_sch_id').value = '0';
    document.getElementById('scheduleModalTitle').innerHTML = '<i class="fa-solid fa-calendar-plus me-2 text-warning"></i> Thêm Ca Trực Mới';
    const c = document.getElementById('applyWeekdaysContainer');
    if (c) c.style.display = 'block';
    if (window.scheduleModalObj) window.scheduleModalObj.show();
};

window.openQuickBatchModal = function() {
    window.openAddScheduleModal();
    const chk = document.getElementById('form_apply_weekdays');
    if (chk) chk.checked = true;
};

window.openAddForEmpDay = function(empId, dayName) {
    window.openAddScheduleModal();
    document.getElementById('form_employee_id').value = empId;
    document.getElementById('form_day_of_week').value = dayName;
};

window.editSchedule = function(schId) {
    fetch(`employee-schedule.php?ajax_get_schedule=1&sch_id=${schId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                document.getElementById('form_sch_id').value = d.id;
                document.getElementById('form_employee_id').value = d.employee_id;
                document.getElementById('form_day_of_week').value = d.day_of_week;
                document.getElementById('form_shift_name').value = d.shift_name;
                document.getElementById('form_start_time').value = d.start_time.substring(0, 5);
                document.getElementById('form_end_time').value = d.end_time.substring(0, 5);
                document.getElementById('form_status').value = d.status;
                document.getElementById('form_note').value = d.note || '';

                const c = document.getElementById('applyWeekdaysContainer');
                if (c) c.style.display = 'none';
                document.getElementById('scheduleModalTitle').innerHTML = `<i class="fa-solid fa-pen-to-square me-2 text-info"></i> Sửa Ca Trực #${d.id} - ${d.fullname}`;
                if (window.scheduleModalObj) window.scheduleModalObj.show();
            } else {
                Swal.fire('Lỗi', res.message, 'error');
            }
        })
        .catch(err => Swal.fire('Lỗi', 'Không thể kết nối máy chủ.', 'error'));
};

window.handleSaveSchedule = function(e) {
    e.preventDefault();
    const form = document.getElementById('scheduleForm');
    const formData = new FormData(form);

    fetch('employee-schedule.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            if (window.scheduleModalObj) window.scheduleModalObj.hide();
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                if (window.loadAdminPage) {
                    window.loadAdminPage(window.location.href, false);
                } else {
                    window.location.reload();
                }
            });
        } else {
            Swal.fire('Thất bại', res.message, 'error');
        }
    })
    .catch(err => Swal.fire('Lỗi', 'Có lỗi khi gửi dữ liệu.', 'error'));
};

window.deleteSchedule = function(schId) {
    Swal.fire({
        title: 'Xóa ca làm việc này?',
        text: 'Ca trực sẽ bị gỡ khỏi lịch phân công của nhân viên.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Đồng ý xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_schedule', '1');
            formData.append('sch_id', schId);

            fetch('employee-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        if (window.loadAdminPage) {
                            window.loadAdminPage(window.location.href, false);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire('Lỗi', res.message, 'error');
                }
            });
        }
    });
};
</script>

<?php include_once 'includes/footer.php'; ?>
