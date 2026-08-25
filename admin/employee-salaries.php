<?php 
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// =========================================================================
// HÀM TÍNH HOA HỒNG TỰ ĐỘNG TỪ CÁC ĐƠN HÀNG NHÂN VIÊN ĐÃ XÁC NHẬN / HOÀN THÀNH
// =========================================================================
function getEmployeeOrderCommissionData($conn, $employee_id, $month_year) {
    $emp_res = $conn->query("SELECT user_id, commission_rate FROM employees WHERE id = $employee_id LIMIT 1");
    if (!$emp_res || !($emp = $emp_res->fetch_assoc())) {
        return [
            'confirmed_orders_count' => 0,
            'confirmed_sales_total'  => 0,
            'commission_rate'        => 3.00,
            'commission_amount'      => 0
        ];
    }

    $user_id = intval($emp['user_id'] ?? 0);
    $rate = floatval($emp['commission_rate'] ?? 3.00);
    if ($rate <= 0) $rate = 3.00;

    $parts = explode('/', $month_year);
    $m = intval($parts[0] ?? date('m'));
    $y = intval($parts[1] ?? date('Y'));

    $user_condition = ($user_id > 0) ? "OR o.staff_id = $user_id" : "";
    $sql = "
        SELECT 
            COUNT(DISTINCT o.id) AS confirmed_orders_count,
            COALESCE(SUM(od.quantity * od.price), COALESCE(SUM(o.subtotal), 0)) AS confirmed_sales_total
        FROM orders o
        LEFT JOIN order_details od ON o.id = od.order_id
        WHERE (o.staff_id = $employee_id $user_condition)
          AND o.status IN ('confirmed', 'shipping', 'completed')
          AND (
              (o.confirmed_at IS NOT NULL AND MONTH(o.confirmed_at) = $m AND YEAR(o.confirmed_at) = $y)
              OR (o.confirmed_at IS NULL AND MONTH(o.created_at) = $m AND YEAR(o.created_at) = $y)
          )
    ";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;

    $cnt = intval($row['confirmed_orders_count'] ?? 0);
    $sales = floatval($row['confirmed_sales_total'] ?? 0);
    $comm_amount = round($sales * ($rate / 100));

    return [
        'confirmed_orders_count' => $cnt,
        'confirmed_sales_total'  => $sales,
        'commission_rate'        => $rate,
        'commission_amount'      => $comm_amount
    ];
}

// =========================================================================
// 1. CÁC ENDPOINT AJAX DÀNH CHO BẢNG LƯƠNG (100% KHÔNG TẢI LẠI TRANG)
// =========================================================================

// AJAX 1.1: Tính toán tự động hoa hồng đơn hàng của nhân viên trong tháng
if (isset($_GET['ajax_calc_commission'])) {
    header('Content-Type: application/json; charset=utf-8');
    $employee_id = intval($_GET['employee_id'] ?? 0);
    $month_year  = trim($_GET['month_year'] ?? date('m/Y'));
    $data = getEmployeeOrderCommissionData($conn, $employee_id, $month_year);
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// AJAX 1.2: Lấy chi tiết phiếu lương để xem / in phiếu / chỉnh sửa
if (isset($_GET['ajax_get_salary'])) {
    header('Content-Type: application/json; charset=utf-8');
    $sal_id = intval($_GET['sal_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT s.*, e.fullname, e.phone, e.email, e.citizen_id, e.avatar, e.address 
        FROM employee_salaries s 
        JOIN employees e ON s.employee_id = e.id 
        WHERE s.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $sal_id);
    $stmt->execute();
    $sal = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($sal) {
        $comm_data = getEmployeeOrderCommissionData($conn, intval($sal['employee_id']), $sal['month_year']);
        $sal['live_commission'] = $comm_data;
        echo json_encode(['success' => true, 'data' => $sal]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu lương.']);
    exit;
}

// AJAX 1.3: Lọc & Lấy danh sách bảng lương qua AJAX (Zero Reload)
if (isset($_GET['ajax_filter_salaries'])) {
    header('Content-Type: application/json; charset=utf-8');
    $f_emp   = intval($_GET['employee_id'] ?? 0);
    $f_month = trim($_GET['month_year'] ?? 'all');
    $f_stat  = trim($_GET['status'] ?? 'all');
    $f_search = trim($_GET['search'] ?? '');

    $where = ["1=1"];
    if ($f_emp > 0) {
        $where[] = "s.employee_id = $f_emp";
    }
    if ($f_month !== 'all' && !empty($f_month)) {
        $m_clean = $conn->real_escape_string($f_month);
        $where[] = "s.month_year = '$m_clean'";
    }
    if ($f_stat !== 'all') {
        $st_clean = $conn->real_escape_string($f_stat);
        $where[] = "s.status = '$st_clean'";
    }
    if (!empty($f_search)) {
        $s_clean = $conn->real_escape_string($f_search);
        $where[] = "(e.fullname LIKE '%$s_clean%' OR e.phone LIKE '%$s_clean%' OR s.month_year LIKE '%$s_clean%')";
    }

    $where_sql = implode(' AND ', $where);
    $res = $conn->query("
        SELECT s.*, e.fullname, e.avatar, e.phone 
        FROM employee_salaries s 
        JOIN employees e ON s.employee_id = e.id 
        WHERE $where_sql 
        ORDER BY s.id DESC
    ");

    $list = [];
    $tot_payout = 0; $paid_payout = 0; $unpaid_payout = 0; $tot_comm = 0;

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
            $tot_payout += floatval($r['total_salary']);
            $tot_comm += floatval($r['commission_amount'] ?? 0);
            if ($r['status'] === 'paid') {
                $paid_payout += floatval($r['total_salary']);
            } else {
                $unpaid_payout += floatval($r['total_salary']);
            }
        }
    }

    echo json_encode([
        'success'       => true,
        'data'          => $list,
        'total_payout'  => $tot_payout,
        'paid_payout'   => $paid_payout,
        'unpaid_payout' => $unpaid_payout,
        'total_comm'    => $tot_comm,
        'count'         => count($list)
    ]);
    exit;
}

// AJAX 1.4: Lưu thông tin bảng lương (Thêm mới hoặc Chỉnh sửa)
if (isset($_POST['ajax_save_salary'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền điều chỉnh bảng lương!']);
        exit;
    }

    $sal_id          = intval($_POST['sal_id'] ?? 0);
    $employee_id     = intval($_POST['employee_id'] ?? 0);
    $month_year      = trim($_POST['month_year'] ?? date('m/Y'));
    $base_salary     = max(0, floatval($_POST['base_salary'] ?? 0));
    $work_days       = max(0, min(31, intval($_POST['work_days'] ?? 26)));
    $off_days        = max(0, min(31, intval($_POST['off_days'] ?? 0)));
    $allowance       = max(0, floatval($_POST['allowance'] ?? 0));
    $commission_rate = max(0, floatval($_POST['commission_rate'] ?? 3.00));
    $commission_amount = max(0, floatval($_POST['commission_amount'] ?? 0));
    $confirmed_orders_count = max(0, intval($_POST['confirmed_orders_count'] ?? 0));
    $confirmed_sales_total  = max(0, floatval($_POST['confirmed_sales_total'] ?? 0));
    
    // TIỀN THƯỞNG: Chỉ có TĂNG (+)
    $bonus           = max(0, floatval($_POST['bonus'] ?? 0));
    $bonus_reason    = trim($_POST['bonus_reason'] ?? '');
    
    // TIỀN PHẠT/TRỪ: Chỉ có GIẢM (-)
    $fine            = max(0, floatval($_POST['fine'] ?? 0));
    $fine_reason     = trim($_POST['fine_reason'] ?? '');
    
    $status          = trim($_POST['status'] ?? 'unpaid');
    $payment_date    = !empty($_POST['payment_date']) ? trim($_POST['payment_date']) : null;
    $note            = trim($_POST['note'] ?? '');

    // Tự động phạt theo ngày nghỉ nếu chưa nhập lý do phạt
    if ($off_days > 0 && ($fine <= 0 || empty($fine_reason))) {
        $auto_fine = $off_days * 100000;
        $fine = $auto_fine;
        $fine_reason = "Khấu trừ " . $off_days . " ngày nghỉ (" . number_format($auto_fine, 0, ',', '.') . "đ)";
    }

    // Công thức tính tổng thực lãnh:
    // Tổng = (Lương cơ bản / 26 * Ngày làm) + Phụ cấp + Thưởng (+) + Hoa hồng đơn hàng (+) - Tiền phạt (-)
    $work_salary = round($base_salary / 26 * $work_days);
    $total_salary = $work_salary + $allowance + $bonus + $commission_amount - $fine;
    if ($total_salary < 0) $total_salary = 0;

    if ($status === 'paid' && empty($payment_date)) {
        $payment_date = date('Y-m-d');
    }

    if ($employee_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhân viên!']);
        exit;
    }

    if ($sal_id > 0) {
        // Cập nhật
        $stmt = $conn->prepare("
            UPDATE employee_salaries SET 
                employee_id = ?, month_year = ?, base_salary = ?, work_days = ?, off_days = ?, 
                allowance = ?, commission_rate = ?, commission_amount = ?, confirmed_orders_count = ?, confirmed_sales_total = ?,
                bonus = ?, bonus_reason = ?, fine = ?, fine_reason = ?, 
                total_salary = ?, status = ?, payment_date = ?, note = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "isdiidddiddsdsdsssi", 
            $employee_id, $month_year, $base_salary, $work_days, $off_days, 
            $allowance, $commission_rate, $commission_amount, $confirmed_orders_count, $confirmed_sales_total,
            $bonus, $bonus_reason, $fine, $fine_reason, 
            $total_salary, $status, $payment_date, $note, $sal_id
        );
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'sal_id' => $sal_id, 'message' => 'Đã cập nhật bảng lương thành công!']);
            exit;
        }
    } else {
        // Thêm mới
        $stmt = $conn->prepare("
            INSERT INTO employee_salaries 
            (employee_id, month_year, base_salary, work_days, off_days, allowance, commission_rate, commission_amount, confirmed_orders_count, confirmed_sales_total, bonus, bonus_reason, fine, fine_reason, total_salary, status, payment_date, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                base_salary = VALUES(base_salary), work_days = VALUES(work_days), off_days = VALUES(off_days), 
                allowance = VALUES(allowance), commission_rate = VALUES(commission_rate), commission_amount = VALUES(commission_amount),
                confirmed_orders_count = VALUES(confirmed_orders_count), confirmed_sales_total = VALUES(confirmed_sales_total),
                bonus = VALUES(bonus), fine = VALUES(fine), total_salary = VALUES(total_salary)
        ");
        $stmt->bind_param(
            "isdiidddiddsdsdsss", 
            $employee_id, $month_year, $base_salary, $work_days, $off_days, 
            $allowance, $commission_rate, $commission_amount, $confirmed_orders_count, $confirmed_sales_total,
            $bonus, $bonus_reason, $fine, $fine_reason, 
            $total_salary, $status, $payment_date, $note
        );
        if ($stmt->execute()) {
            $new_sal_id = $conn->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'sal_id' => $new_sal_id, 'message' => 'Đã khởi tạo bảng lương thành công!']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $conn->error]);
    exit;
}

// AJAX 1.5: Khởi tạo bảng lương hàng loạt cho tất cả nhân viên trong tháng
if (isset($_POST['ajax_generate_all_salaries'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền này!']);
        exit;
    }

    $gen_month = trim($_POST['month_year'] ?? date('m/Y'));
    $emps = $conn->query("SELECT * FROM employees WHERE status = 1");
    $created_count = 0;

    if ($emps) {
        while ($emp = $emps->fetch_assoc()) {
            $eid = intval($emp['id']);
            $base = floatval($emp['base_salary'] ?? 6000000);
            $wd = 26;
            $od = 0;
            $allow = 500000;
            
            $comm_data = getEmployeeOrderCommissionData($conn, $eid, $gen_month);
            $comm_rate = $comm_data['commission_rate'];
            $comm_amount = $comm_data['commission_amount'];
            $orders_cnt = $comm_data['confirmed_orders_count'];
            $sales_tot = $comm_data['confirmed_sales_total'];

            $total = ($base / 26 * $wd) + $allow + $comm_amount;

            $conn->query("
                INSERT INTO employee_salaries 
                (employee_id, month_year, base_salary, work_days, off_days, allowance, commission_rate, commission_amount, confirmed_orders_count, confirmed_sales_total, bonus, fine, total_salary, status)
                VALUES 
                ($eid, '$gen_month', $base, $wd, $od, $allow, $comm_rate, $comm_amount, $orders_cnt, $sales_tot, 0, 0, $total, 'unpaid')
                ON DUPLICATE KEY UPDATE
                    commission_amount = $comm_amount,
                    confirmed_orders_count = $orders_cnt,
                    confirmed_sales_total = $sales_tot,
                    total_salary = (base_salary / 26 * work_days) + allowance + bonus + $comm_amount - fine
            ");
            $created_count++;
        }
    }

    echo json_encode(['success' => true, 'message' => "Đã đồng bộ & tính hoa hồng đơn hàng tháng {$gen_month} cho {$created_count} nhân viên!"]);
    exit;
}

// AJAX 1.6: Đổi trạng thái thanh toán (Đã thanh toán / Chưa thanh toán)
if (isset($_POST['ajax_toggle_payment_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền cập nhật thanh toán!']);
        exit;
    }

    $sal_id = intval($_POST['sal_id'] ?? 0);
    $res = $conn->query("SELECT status, total_salary, employee_id FROM employee_salaries WHERE id = $sal_id");
    if ($res && $row = $res->fetch_assoc()) {
        $new_st = ($row['status'] === 'paid') ? 'unpaid' : 'paid';
        $pay_date = ($new_st === 'paid') ? date('Y-m-d') : null;

        $stmt = $conn->prepare("UPDATE employee_salaries SET status = ?, payment_date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_st, $pay_date, $sal_id);
        $stmt->execute();
        $stmt->close();

        $badge_html = ($new_st === 'paid')
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Đã trả</span>'
            : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-clock me-1"></i> Chưa trả</span>';

        echo json_encode([
            'success'    => true,
            'new_status' => $new_st,
            'badge_html' => $badge_html,
            'message'    => ($new_st === 'paid') ? 'Đã xác nhận thanh toán lương!' : 'Đã chuyển về trạng thái chưa thanh toán.'
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu lương.']);
    exit;
}

// AJAX 1.7: Xóa phiếu lương
if (isset($_POST['ajax_delete_salary'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền xóa phiếu lương!']);
        exit;
    }

    $sal_id = intval($_POST['sal_id'] ?? 0);
    if ($conn->query("DELETE FROM employee_salaries WHERE id = $sal_id")) {
        echo json_encode(['success' => true, 'message' => "Đã xóa phiếu lương #$sal_id!"]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không thể xóa phiếu lương.']);
    exit;
}

include_once 'includes/header.php';

// =========================================================================
// 2. DỮ LIỆU BAN ĐẦU CHO TRANG QUẢN LÝ LƯƠNG
// =========================================================================
$filter_emp   = intval($_GET['employee_id'] ?? 0);
$filter_month = trim($_GET['month_year'] ?? date('m/Y'));
$filter_stat  = trim($_GET['status'] ?? 'all');

// Lấy danh sách nhân viên
$all_emps = [];
$e_res = $conn->query("SELECT id, fullname, avatar FROM employees WHERE status = 1 ORDER BY fullname ASC");
if ($e_res) {
    while ($r = $e_res->fetch_assoc()) {
        $all_emps[] = $r;
    }
}

// Danh sách các tháng có dữ liệu lương
$all_months = [];
$m_res = $conn->query("SELECT DISTINCT month_year FROM employee_salaries ORDER BY id DESC");
if ($m_res) {
    while ($r = $m_res->fetch_assoc()) {
        $all_months[] = $r['month_year'];
    }
}
if (!in_array(date('m/Y'), $all_months)) {
    array_unshift($all_months, date('m/Y'));
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* CSS TỐI ƯU GIAO DIỆN GỌN GÀNG TRONG 1 TRANG, KHÔNG LỌT RA NGOÀI */
.sal-page-container {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}
.sal-stat-card {
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.06);
    background: #fff;
    padding: 12px 16px;
    transition: all 0.2s ease;
}
.sal-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.sal-table th {
    font-size: 11.5px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    background-color: #f8fafc !important;
    color: #475569;
    padding: 10px 12px;
    vertical-align: middle;
}
.sal-table td {
    font-size: 13px;
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.btn-action-icon {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 12px;
}
.sal-filter-bar {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 12px 14px;
}
.form-select-compact, .form-control-compact {
    font-size: 13px;
    border-radius: 8px;
    padding: 6px 10px;
}

/* CSS CHUYÊN BIỆT KHI IN / XUẤT FILE PDF TRỰC TIẾP TỪ MODAL */
@media print {
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .sal-page-container, nav, header, aside, .sidebar, .navbar, .modal-backdrop, .no-print, .modal-header, .modal-footer, .btn-close {
        display: none !important;
    }
    #salarySlipModal {
        position: static !important;
        display: block !important;
        opacity: 1 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #salarySlipModal .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
        transform: none !important;
    }
    #salarySlipModal .modal-content {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: #fff !important;
    }
    #salarySlipModal .modal-body {
        padding: 0 !important;
        background: #fff !important;
    }
    .printable-slip-wrapper {
        border: none !important;
        background: #fff !important;
        padding: 0 !important;
    }
}
</style>

<div class="sal-page-container">

    <!-- HEADER TRANG -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="fw-bold text-uppercase mb-0 text-dark">
                <i class="fa-solid fa-money-check-dollar me-2 text-success"></i>Quản Lý Lương & Bảng Lương Nhân Viên
            </h5>
            <small class="text-muted">Tự động tính hoa hồng đơn hàng, thưởng (+), phạt (-) và in phiếu lương chi tiết.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="employees.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold">
                <i class="fa-solid fa-users me-1"></i> Hồ Sơ
            </a>
            <a href="employee-schedule.php" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm fw-semibold">
                <i class="fa-solid fa-calendar-week me-1"></i> Lịch Làm Việc
            </a>
            <?php if ($user_role === 'admin'): ?>
                <button class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold shadow-sm" onclick="triggerAutoGenerateSalary()">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Quét &amp; Tính Lương Tháng
                </button>
                <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" onclick="openAddSalaryModal()">
                    <i class="fa-solid fa-plus me-1"></i> Lập Phiếu Lương
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4 THỐNG KÊ NHANH GỌN TRÊN 1 HÀNG -->
    <div class="row g-2 mb-3">
        <div class="col-xl-3 col-md-6 col-6">
            <div class="sal-stat-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Tổng Quỹ Lương</small>
                        <h6 class="fw-bold mb-0 text-primary mt-1" id="stat_total_payout">0 đ</h6>
                    </div>
                    <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-vault fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="sal-stat-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Đã Thanh Toán</small>
                        <h6 class="fw-bold mb-0 text-success mt-1" id="stat_paid_payout">0 đ</h6>
                    </div>
                    <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="sal-stat-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Chưa Thanh Toán</small>
                        <h6 class="fw-bold mb-0 text-warning mt-1" id="stat_unpaid_payout">0 đ</h6>
                    </div>
                    <div class="rounded-circle p-2 bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="sal-stat-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Tổng Hoa Hồng Đã Duyệt</small>
                        <h6 class="fw-bold mb-0 text-info mt-1" id="stat_total_comm">0 đ</h6>
                    </div>
                    <div class="rounded-circle p-2 bg-info bg-opacity-10 text-info">
                        <i class="fa-solid fa-chart-line fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BỘ LỌC TÌM KIẾM NHANH (LIVE AJAX ZERO RELOAD) -->
    <div class="sal-filter-bar shadow-sm mb-3">
        <form id="filterForm" class="row g-2 align-items-center" onsubmit="event.preventDefault(); loadSalariesLive();">
            <div class="col-lg-4 col-md-5 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="filter_search" class="form-control form-control-compact border-0 bg-light" placeholder="Tìm tên nhân viên, SĐT, kỳ lương..." oninput="loadSalariesLive()">
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-6">
                <select id="filter_employee_id" class="form-select form-select-compact bg-light border-0" onchange="loadSalariesLive()">
                    <option value="0">-- Tất cả nhân viên --</option>
                    <?php foreach ($all_emps as $ae): ?>
                        <option value="<?= $ae['id']; ?>" <?= $filter_emp == $ae['id'] ? 'selected' : ''; ?>>
                            👤 <?= htmlspecialchars($ae['fullname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-2 col-6">
                <select id="filter_month_year" class="form-select form-select-compact bg-light border-0" onchange="loadSalariesLive()">
                    <option value="all">Tất cả tháng</option>
                    <?php foreach ($all_months as $m): ?>
                        <option value="<?= $m; ?>" <?= $m === $filter_month ? 'selected' : ''; ?>>Tháng <?= $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-2 col-6">
                <select id="filter_status" class="form-select form-select-compact bg-light border-0" onchange="loadSalariesLive()">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="paid">✅ Đã thanh toán</option>
                    <option value="unpaid">🔒 Chưa thanh toán</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-12 col-6 text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 rounded-3" onclick="resetFilters()" title="Đặt lại bộ lọc">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- BẢNG DANH SÁCH BẢNG LƯƠNG (GỌN GÀNG, KHÔNG LỌT RA NGOÀI) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-width: 100%;">
                <table class="table table-hover align-middle mb-0 text-nowrap sal-table">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 220px;">Nhân viên</th>
                            <th style="width: 100px;">Kỳ lương</th>
                            <th style="width: 160px;">Lương &amp; Ngày công</th>
                            <th style="width: 170px;">Hoa hồng đơn duyệt</th>
                            <th style="width: 140px;">Thưởng (+) / Phạt (-)</th>
                            <th style="width: 130px;" class="text-success">Thực lãnh</th>
                            <th style="width: 110px;">Trạng thái</th>
                            <th class="text-end pe-3" style="width: 130px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="salaryTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-spinner fa-spin fs-3 text-primary mb-2"></i>
                                <div>Đang tải dữ liệu bảng lương...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- MODAL LẬP / CHỈNH SỬA PHIẾU LƯƠNG (TỰ ĐỘNG ĐIỀN HOA HỒNG) -->
<div class="modal fade" id="salaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h6 class="modal-title fw-bold" id="salaryModalTitle">
                    <i class="fa-solid fa-money-bill-wave me-2 text-warning"></i>Thiết Lập Phiếu Lương Nhân Viên
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="salaryForm">
                <input type="hidden" name="ajax_save_salary" value="1">
                <input type="hidden" name="sal_id" id="form_sal_id" value="0">
                <input type="hidden" name="confirmed_orders_count" id="sal_confirmed_orders_count" value="0">
                <input type="hidden" name="confirmed_sales_total" id="sal_confirmed_sales_total" value="0">
                
                <div class="modal-body p-3 p-md-4">
                    <div class="row g-2 g-md-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold text-dark small">Chọn nhân viên: <span class="text-danger">*</span></label>
                            <select name="employee_id" id="sal_employee_id" class="form-select form-select-sm" onchange="autoFetchOrderCommission()" required>
                                <option value="">-- Chọn nhân viên --</option>
                                <?php foreach ($all_emps as $ae): ?>
                                    <option value="<?= $ae['id']; ?>"><?= htmlspecialchars($ae['fullname']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold text-dark small">Kỳ lương (Tháng/Năm):</label>
                            <input type="text" name="month_year" id="sal_month_year" class="form-control form-control-sm" value="<?= date('m/Y'); ?>" placeholder="MM/YYYY" onchange="autoFetchOrderCommission()" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold text-dark small">Lương cơ bản (VNĐ):</label>
                            <input type="number" name="base_salary" id="sal_base_salary" class="form-control form-control-sm" value="6000000" min="0" step="50000" oninput="calcLiveSalary()" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold text-dark small">Phụ cấp (Ăn trưa, xăng xe...):</label>
                            <input type="number" name="allowance" id="sal_allowance" class="form-control form-control-sm" value="500000" min="0" step="50000" oninput="calcLiveSalary()">
                        </div>
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-bold text-dark small">Số ngày công (Chuẩn 26 ngày):</label>
                            <input type="number" name="work_days" id="sal_work_days" class="form-control form-control-sm" value="26" min="0" max="31" oninput="calcLiveSalary()" required>
                        </div>
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-bold text-dark small">Số ngày nghỉ trong tháng:</label>
                            <input type="number" name="off_days" id="sal_off_days" class="form-control form-control-sm" value="0" min="0" max="31" oninput="autoDeductOffDays(this.value)">
                        </div>

                        <!-- HOA HỒNG ĐƠN HÀNG XÁC NHẬN (TỰ ĐỘNG QUÉT & ĐIỀN) -->
                        <div class="col-12">
                            <div class="p-2 p-md-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-primary mb-0 small">
                                        <i class="fa-solid fa-chart-line me-1"></i> Hoa hồng đơn hàng nhân viên đã xác nhận:
                                    </label>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold" style="font-size: 11px;" onclick="autoFetchOrderCommission()">
                                        <i class="fa-solid fa-rotate me-1"></i> ⚡ Tự động quét từ đơn hàng
                                    </button>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-4 col-12">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white small">% Hoa hồng:</span>
                                            <input type="number" name="commission_rate" id="sal_commission_rate" class="form-control" value="3.00" step="0.1" min="0" max="100" oninput="recalcCommissionFromRate()">
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-12">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fw-bold text-success small">Tiền hoa hồng (+):</span>
                                            <input type="number" name="commission_amount" id="sal_commission_amount" class="form-control fw-bold text-success" value="0" min="0" step="1000" oninput="calcLiveSalary()">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 small text-muted" id="commission_stat_text" style="font-size: 12px;">
                                    <i class="fa-solid fa-info-circle me-1"></i> Chọn nhân viên và kỳ lương để tự động quét doanh số và tính hoa hồng.
                                </div>
                            </div>
                        </div>

                        <!-- THƯỞNG: CHỈ CÓ TĂNG (+) -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold text-success d-flex justify-content-between small">
                                <span><i class="fa-solid fa-plus-circle me-1"></i> Tiền Thưởng thêm (VNĐ):</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">+ TĂNG THU NHẬP</span>
                            </label>
                            <input type="number" name="bonus" id="sal_bonus" class="form-control form-control-sm border-success-subtle" value="0" min="0" step="10000" oninput="calcLiveSalary()">
                            <input type="text" name="bonus_reason" id="sal_bonus_reason" class="form-control form-control-sm mt-1" placeholder="Lý do thưởng (Chuyên cần, hoàn thành tốt...)">
                        </div>

                        <!-- PHẠT: CHỈ CÓ GIẢM (-) -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold text-danger d-flex justify-content-between small">
                                <span><i class="fa-solid fa-minus-circle me-1"></i> Tiền Phạt / Khấu trừ (VNĐ):</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">- GIẢM TRỪ LƯƠNG</span>
                            </label>
                            <input type="number" name="fine" id="sal_fine" class="form-control form-control-sm border-danger-subtle" value="0" min="0" step="10000" oninput="calcLiveSalary()">
                            <input type="text" name="fine_reason" id="sal_fine_reason" class="form-control form-control-sm mt-1" placeholder="Lý do phạt / khấu trừ ngày nghỉ">
                        </div>

                        <div class="col-md-6 col-6">
                            <label class="form-label fw-bold text-dark small">Trạng thái thanh toán:</label>
                            <select name="status" id="sal_status" class="form-select form-select-sm">
                                <option value="unpaid">🔒 Chưa thanh toán</option>
                                <option value="paid">✅ Đã thanh toán</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-bold text-dark small">Ngày chi trả:</label>
                            <input type="date" name="payment_date" id="sal_payment_date" class="form-control form-control-sm">
                        </div>

                        <!-- CARD HIỂN THỊ TỔNG LƯƠNG THỰC LÃNH TỨC THÌ -->
                        <div class="col-12">
                            <div class="p-2 p-md-3 bg-success bg-opacity-10 border border-success rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="text-muted small d-block" style="font-size: 11px;">TỔNG THỰC LÃNH TÍNH TOÁN:</span>
                                    <strong class="fs-5 text-success" id="calc_total_display">0 đ</strong>
                                </div>
                                <div class="text-end small text-muted" id="calc_formula_breakdown" style="font-size: 11px;">
                                    (Lương CB / 26 * Công) + Phụ cấp + Hoa hồng (+) + Thưởng (+) - Phạt (-)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2 p-md-3">
                    <button type="button" class="btn btn-sm btn-secondary fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitSal" class="btn btn-sm btn-success rounded-3 px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Phiếu Lương
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL XEM VÀ IN PHIẾU LƯƠNG CHI TIẾT -->
<div class="modal fade" id="salarySlipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>Phiếu Lương Chi Tiết Nhân Viên</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4 bg-white" id="salarySlipBody">
                <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fs-3 text-muted"></i></div>
            </div>
            <div class="modal-footer bg-light p-2 p-md-3 d-flex flex-wrap justify-content-between gap-2">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-3 shadow-sm" onclick="openPrintSalaryPage(activeSalarySlipId)">
                        <i class="fa-solid fa-print me-1"></i> In Ra File / Lưu PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3" onclick="window.print()">
                        <i class="fa-solid fa-paperclip me-1"></i> In Ngay
                    </button>
                </div>
                <button type="button" class="btn btn-sm btn-secondary fw-bold rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
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

window.salModal = window.salModal || null;
window.slipModal = window.slipModal || null;
window.activeSalarySlipId = window.activeSalarySlipId || 0;

function initEmployeeSalaries() {
    const salModalEl = document.getElementById('salaryModal');
    if (salModalEl) window.salModal = bootstrap.Modal.getInstance(salModalEl) || new bootstrap.Modal(salModalEl);

    const slipModalEl = document.getElementById('salarySlipModal');
    if (slipModalEl) window.slipModal = bootstrap.Modal.getInstance(slipModalEl) || new bootstrap.Modal(slipModalEl);

    // Tải dữ liệu ban đầu 100% qua AJAX ngay lập tức
    loadSalariesLive();

    // Submit form thêm / sửa phiếu lương 100% Live AJAX (Không load trang)
    const form = document.getElementById('salaryForm');
    if (form && !form.dataset.boundSubmit) {
        form.dataset.boundSubmit = 'true';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitSal');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(form);

            fetch('employee-salaries.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Phiếu Lương';
                }

                if (data.success) {
                    if (window.salModal) window.salModal.hide();
                    Toast.fire({ icon: 'success', title: data.message });
                    loadSalariesLive(); // Cập nhật lại danh sách và thống kê tức thì
                } else {
                    Swal.fire({ icon: 'warning', title: 'Thông báo', text: data.message });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Phiếu Lương';
                }
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
            });
        });
    }
}

// Chạy ngay lập tức không phụ thuộc DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmployeeSalaries);
} else {
    initEmployeeSalaries();
}

// TẢI VÀ RENDER DANH SÁCH BẢNG LƯƠNG 100% LIVE AJAX (ZERO RELOAD)
function loadSalariesLive() {
    const empId = document.getElementById('filter_employee_id').value;
    const monthYear = document.getElementById('filter_month_year').value;
    const status = document.getElementById('filter_status').value;
    const search = document.getElementById('filter_search').value.trim();

    const tbody = document.getElementById('salaryTableBody');

    fetch(`employee-salaries.php?ajax_filter_salaries=1&employee_id=${empId}&month_year=${encodeURIComponent(monthYear)}&status=${status}&search=${encodeURIComponent(search)}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Cập nhật thống kê nhanh
            document.getElementById('stat_total_payout').innerText = Number(data.total_payout).toLocaleString('vi-VN') + ' đ';
            document.getElementById('stat_paid_payout').innerText = Number(data.paid_payout).toLocaleString('vi-VN') + ' đ';
            document.getElementById('stat_unpaid_payout').innerText = Number(data.unpaid_payout).toLocaleString('vi-VN') + ' đ';
            document.getElementById('stat_total_comm').innerText = Number(data.total_comm).toLocaleString('vi-VN') + ' đ';

            // Render bảng
            if (data.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-file-invoice-dollar fs-1 d-block mb-2 opacity-50"></i>
                            Không tìm thấy bản ghi lương nào phù hợp.
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            data.data.forEach(s => {
                const sid = s.id;
                const avt = s.avatar ? (s.avatar.startsWith('http') ? s.avatar : '../' + s.avatar) : '../assets/images/default-avatar.png';
                const commAmt = Number(s.commission_amount || 0);
                const ordersCnt = Number(s.confirmed_orders_count || 0);
                const isPaid = s.status === 'paid';

                let bonusFineHtml = '';
                if (Number(s.bonus) > 0) {
                    bonusFineHtml += `<span class="badge bg-success-subtle text-success border border-success-subtle d-block mb-1" style="font-size: 11px;">+${Number(s.bonus).toLocaleString('vi-VN')} đ (Thưởng)</span>`;
                }
                if (Number(s.fine) > 0) {
                    bonusFineHtml += `<span class="badge bg-danger-subtle text-danger border border-danger-subtle d-block" style="font-size: 11px;">-${Number(s.fine).toLocaleString('vi-VN')} đ (Phạt)</span>`;
                }
                if (Number(s.bonus) === 0 && Number(s.fine) === 0) {
                    bonusFineHtml = '<span class="text-muted small">-</span>';
                }

                html += `
                    <tr id="sal-row-${sid}">
                        <td class="ps-3">
                            <div class="d-flex align-items-center">
                                <img src="${avt}" class="rounded-circle me-2 border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;" onerror="this.src='../assets/images/default-avatar.png'">
                                <div>
                                    <strong class="text-dark d-block text-truncate" style="max-width: 140px;">${s.fullname}</strong>
                                    <small class="text-muted">Mã: #${s.employee_id}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                <i class="fa-solid fa-calendar-day text-primary me-1"></i>${s.month_year}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">${Number(s.base_salary).toLocaleString('vi-VN')} đ</div>
                            <small class="text-primary fw-semibold">${s.work_days}/26 công</small>
                        </td>
                        <td>
                            ${(commAmt > 0 || ordersCnt > 0) ? `
                                <div class="text-success fw-bold">
                                    <i class="fa-solid fa-chart-line me-1"></i>+${commAmt.toLocaleString('vi-VN')} đ
                                </div>
                                <small class="text-muted">${ordersCnt} đơn duyệt (${s.commission_rate}%)</small>
                            ` : '<span class="text-muted small">0 đ (0 đơn)</span>'}
                        </td>
                        <td>
                            ${bonusFineHtml}
                        </td>
                        <td>
                            <strong class="text-success fs-6">${Number(s.total_salary).toLocaleString('vi-VN')} đ</strong>
                        </td>
                        <td id="status-cell-${sid}">
                            <button type="button" class="btn p-0 border-0" onclick="toggleSalaryPayment(${sid})" title="Nhấp để đổi trạng thái">
                                ${isPaid ? `
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Đã trả
                                    </span>
                                ` : `
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                        <i class="fa-solid fa-clock me-1"></i> Chưa trả
                                    </span>
                                `}
                            </button>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-inline-flex gap-1">
                                <!-- Xem / In modal -->
                                <button type="button" class="btn btn-action-icon btn-outline-info" onclick="viewSalarySlip(${sid})" title="Xem Phiếu Lương Chi Tiết">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </button>
                                <!-- In trang chuẩn A4 / Xuất PDF -->
                                <a href="print-salary.php?id=${sid}" class="btn btn-action-icon btn-outline-dark" title="In Ra File / Xuất PDF (Chuẩn A4)">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <?php if ($user_role === 'admin'): ?>
                                    <button type="button" class="btn btn-action-icon btn-outline-secondary" onclick="editSalary(${sid})" title="Sửa Bảng Lương">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-action-icon btn-outline-danger" onclick="deleteSalary(${sid})" title="Xóa Phiếu Lương">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Lỗi tải dữ liệu.</td></tr>`;
    });
}

function resetFilters() {
    document.getElementById('filter_search').value = '';
    document.getElementById('filter_employee_id').value = '0';
    document.getElementById('filter_month_year').value = 'all';
    document.getElementById('filter_status').value = 'all';
    loadSalariesLive();
}

// Tự động tính hoa hồng đơn hàng từ Server
function autoFetchOrderCommission() {
    const empId = document.getElementById('sal_employee_id').value;
    const monthYear = document.getElementById('sal_month_year').value;
    const statText = document.getElementById('commission_stat_text');

    if (!empId) {
        statText.innerHTML = '<i class="fa-solid fa-circle-exclamation text-warning me-1"></i> Vui lòng chọn nhân viên để tính hoa hồng.';
        return;
    }

    statText.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary me-1"></i> Đang quét các đơn hàng nhân viên đã xác nhận...';

    fetch(`employee-salaries.php?ajax_calc_commission=1&employee_id=${empId}&month_year=${encodeURIComponent(monthYear)}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('sal_commission_rate').value = data.commission_rate;
            document.getElementById('sal_commission_amount').value = data.commission_amount;
            document.getElementById('sal_confirmed_orders_count').value = data.confirmed_orders_count;
            document.getElementById('sal_confirmed_sales_total').value = data.confirmed_sales_total;

            statText.innerHTML = `
                <span class="text-success fw-bold">
                    <i class="fa-solid fa-circle-check me-1"></i> Đã duyệt: <b>${data.confirmed_orders_count}</b> đơn (Doanh số: <b>${Number(data.confirmed_sales_total).toLocaleString('vi-VN')} đ</b>) ➔ Hoa hồng: <b>+${Number(data.commission_amount).toLocaleString('vi-VN')} đ</b> (${data.commission_rate}%)
                </span>
            `;
            calcLiveSalary();
        }
    })
    .catch(err => {
        console.error(err);
        statText.innerHTML = '<span class="text-danger">Lỗi khi tính hoa hồng.</span>';
    });
}

function recalcCommissionFromRate() {
    const salesTotal = parseFloat(document.getElementById('sal_confirmed_sales_total').value) || 0;
    const rate = parseFloat(document.getElementById('sal_commission_rate').value) || 0;
    const commAmt = Math.round(salesTotal * (rate / 100));
    document.getElementById('sal_commission_amount').value = commAmt;
    calcLiveSalary();
}

function calcLiveSalary() {
    const base = Math.max(0, parseFloat(document.getElementById('sal_base_salary').value) || 0);
    const work = Math.max(0, parseInt(document.getElementById('sal_work_days').value) || 26);
    const allow = Math.max(0, parseFloat(document.getElementById('sal_allowance').value) || 0);
    const comm = Math.max(0, parseFloat(document.getElementById('sal_commission_amount').value) || 0);
    const bonus = Math.max(0, parseFloat(document.getElementById('sal_bonus').value) || 0);
    const fine = Math.max(0, parseFloat(document.getElementById('sal_fine').value) || 0);

    const workSalary = Math.round(base / 26 * work);
    let total = workSalary + allow + comm + bonus - fine;
    if (total < 0) total = 0;

    document.getElementById('calc_total_display').innerText = Math.round(total).toLocaleString('vi-VN') + ' đ';
    document.getElementById('calc_formula_breakdown').innerHTML = `
        Công: ${workSalary.toLocaleString('vi-VN')}đ + Cấp: ${allow.toLocaleString('vi-VN')}đ + HH: <b class="text-success">+${comm.toLocaleString('vi-VN')}đ</b> + Thưởng: <b class="text-success">+${bonus.toLocaleString('vi-VN')}đ</b> - Phạt: <b class="text-danger">-${fine.toLocaleString('vi-VN')}đ</b>
    `;
}

function autoDeductOffDays(offDays) {
    const od = parseInt(offDays) || 0;
    if (od > 0) {
        document.getElementById('sal_work_days').value = Math.max(0, 26 - od);
        document.getElementById('sal_fine').value = od * 100000;
        document.getElementById('sal_fine_reason').value = `Khấu trừ ${od} ngày nghỉ`;
    } else {
        document.getElementById('sal_work_days').value = 26;
        document.getElementById('sal_fine').value = 0;
        document.getElementById('sal_fine_reason').value = '';
    }
    calcLiveSalary();
}

function openAddSalaryModal() {
    document.getElementById('salaryModalTitle').innerHTML = '<i class="fa-solid fa-money-bill-wave me-2 text-warning"></i>Lập Phiếu Lương Nhân Viên Mới';
    document.getElementById('form_sal_id').value = '0';
    document.getElementById('sal_employee_id').value = document.getElementById('filter_employee_id').value > 0 ? document.getElementById('filter_employee_id').value : '';
    document.getElementById('sal_month_year').value = '<?= date('m/Y'); ?>';
    document.getElementById('sal_base_salary').value = '6000000';
    document.getElementById('sal_allowance').value = '500000';
    document.getElementById('sal_work_days').value = '26';
    document.getElementById('sal_off_days').value = '0';
    document.getElementById('sal_commission_rate').value = '3.00';
    document.getElementById('sal_commission_amount').value = '0';
    document.getElementById('sal_confirmed_orders_count').value = '0';
    document.getElementById('sal_confirmed_sales_total').value = '0';
    document.getElementById('sal_bonus').value = '0';
    document.getElementById('sal_bonus_reason').value = '';
    document.getElementById('sal_fine').value = '0';
    document.getElementById('sal_fine_reason').value = '';
    document.getElementById('sal_status').value = 'unpaid';
    document.getElementById('sal_payment_date').value = '';
    
    salModal.show();
    if (document.getElementById('sal_employee_id').value) {
        autoFetchOrderCommission();
    } else {
        calcLiveSalary();
    }
}

function editSalary(salId) {
    fetch(`employee-salaries.php?ajax_get_salary=1&sal_id=${salId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const s = data.data;
            document.getElementById('salaryModalTitle').innerHTML = `<i class="fa-solid fa-file-pen me-2 text-warning"></i>Sửa Phiếu Lương: ${s.fullname} (${s.month_year})`;
            document.getElementById('form_sal_id').value = s.id;
            document.getElementById('sal_employee_id').value = s.employee_id;
            document.getElementById('sal_month_year').value = s.month_year;
            document.getElementById('sal_base_salary').value = s.base_salary;
            document.getElementById('sal_allowance').value = s.allowance;
            document.getElementById('sal_work_days').value = s.work_days;
            document.getElementById('sal_off_days').value = s.off_days;
            document.getElementById('sal_commission_rate').value = s.commission_rate || 3.00;
            document.getElementById('sal_commission_amount').value = s.commission_amount || 0;
            document.getElementById('sal_confirmed_orders_count').value = s.confirmed_orders_count || 0;
            document.getElementById('sal_confirmed_sales_total').value = s.confirmed_sales_total || 0;
            document.getElementById('sal_bonus').value = s.bonus;
            document.getElementById('sal_bonus_reason').value = s.bonus_reason || '';
            document.getElementById('sal_fine').value = s.fine;
            document.getElementById('sal_fine_reason').value = s.fine_reason || '';
            document.getElementById('sal_status').value = s.status;
            document.getElementById('sal_payment_date').value = s.payment_date || '';
            
            document.getElementById('commission_stat_text').innerHTML = `
                <span class="text-success fw-bold">
                    <i class="fa-solid fa-circle-check me-1"></i> Đã xác nhận: <b>${s.confirmed_orders_count || 0}</b> đơn hàng (Doanh số: <b>${Number(s.confirmed_sales_total || 0).toLocaleString('vi-VN')} đ</b>) ➔ Hoa hồng: <b>+${Number(s.commission_amount || 0).toLocaleString('vi-VN')} đ</b>
                </span>
            `;
            calcLiveSalary();

            salModal.show();
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    })
    .catch(err => console.error(err));
}

function viewSalarySlip(salId) {
    activeSalarySlipId = salId;
    const body = document.getElementById('salarySlipBody');
    body.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fs-3 text-muted"></i></div>';
    slipModal.show();

    fetch(`employee-salaries.php?ajax_get_salary=1&sal_id=${salId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const s = data.data;
            const commAmt = Number(s.commission_amount || 0);
            const ordersCnt = Number(s.confirmed_orders_count || 0);
            const salesTot = Number(s.confirmed_sales_total || 0);
            const baseSal = Number(s.base_salary || 0);
            const workDays = Number(s.work_days || 26);
            const workSal = Math.round(baseSal / 26 * workDays);
            const allowance = Number(s.allowance || 0);
            const bonus = Number(s.bonus || 0);
            const fine = Number(s.fine || 0);
            const totalSal = Number(s.total_salary || 0);

            body.innerHTML = `
                <div class="printable-slip-wrapper p-3 border rounded-3 bg-white">
                    <div class="text-center border-bottom pb-3 mb-3">
                        <h5 class="fw-bold text-uppercase mb-1 text-dark">PHIẾU THANH TOÁN TIỀN LƯƠNG NHÂN VIÊN</h5>
                        <span class="badge bg-dark text-white px-3 py-1">Kỳ Lương: ${s.month_year}</span>
                        <div class="text-muted small mt-1">Mã phiếu: #SAL-${String(s.id).padStart(5, '0')}</div>
                    </div>
                    
                    <div class="row g-2 mb-3 small">
                        <div class="col-6"><strong>Họ và tên:</strong> <span class="fw-bold text-dark">${s.fullname}</span></div>
                        <div class="col-6"><strong>Mã nhân viên:</strong> #${s.employee_id}</div>
                        <div class="col-6"><strong>Số điện thoại:</strong> ${s.phone || '-'}</div>
                        <div class="col-6"><strong>Số CCCD:</strong> ${s.citizen_id || '-'}</div>
                        <div class="col-12"><strong>Địa chỉ:</strong> ${s.address || '-'}</div>
                    </div>

                    <table class="table table-bordered bg-white text-dark mb-3 small align-middle">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th>Khoản mục thu nhập &amp; khấu trừ</th>
                                <th>Chi tiết / Căn cứ tính</th>
                                <th class="text-end" style="width: 150px;">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Lương cơ bản định mức</td>
                                <td>Định mức 26 ngày công</td>
                                <td class="text-end font-monospace">${baseSal.toLocaleString('vi-VN')} đ</td>
                            </tr>
                            <tr>
                                <td><strong>Lương ngày công thực tế</strong></td>
                                <td>${workDays} ngày đi làm thực tế</td>
                                <td class="text-end font-monospace fw-bold">${workSal.toLocaleString('vi-VN')} đ</td>
                            </tr>
                            <tr>
                                <td>Phụ cấp ăn trưa &amp; xăng xe</td>
                                <td>Phúc lợi cố định hàng tháng</td>
                                <td class="text-end text-success font-monospace">+${allowance.toLocaleString('vi-VN')} đ</td>
                            </tr>
                            ${commAmt > 0 || ordersCnt > 0 ? `
                            <tr class="table-primary">
                                <td><strong class="text-primary">Hoa hồng đơn hàng xác nhận</strong></td>
                                <td>${ordersCnt} đơn đã duyệt (Doanh số: ${salesTot.toLocaleString('vi-VN')} đ, tỷ lệ ${s.commission_rate}%)</td>
                                <td class="text-end text-primary font-monospace fw-bold">+${commAmt.toLocaleString('vi-VN')} đ</td>
                            </tr>` : ''}
                            ${bonus > 0 ? `
                            <tr>
                                <td><span class="text-success fw-bold"><i class="fa-solid fa-plus-circle me-1"></i> Tiền thưởng thêm (Tăng)</span></td>
                                <td>${s.bonus_reason || 'Thưởng hoàn thành tốt nhiệm vụ'}</td>
                                <td class="text-end text-success font-monospace fw-bold">+${bonus.toLocaleString('vi-VN')} đ</td>
                            </tr>` : ''}
                            ${fine > 0 ? `
                            <tr>
                                <td><span class="text-danger fw-bold"><i class="fa-solid fa-minus-circle me-1"></i> Tiền phạt / Khấu trừ (Giảm)</span></td>
                                <td>${s.fine_reason || 'Nghỉ không lương / vi phạm nội quy'}</td>
                                <td class="text-end text-danger font-monospace fw-bold">-${fine.toLocaleString('vi-VN')} đ</td>
                            </tr>` : ''}
                            <tr class="table-success fw-bold">
                                <td colspan="2" class="text-uppercase">TỔNG LƯƠNG THỰC LÃNH:</td>
                                <td class="text-end font-monospace fs-6 text-success">${totalSal.toLocaleString('vi-VN')} đ</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                        <div>Trạng thái: <strong>${s.status === 'paid' ? '✅ Đã thanh toán' : '🔒 Chưa thanh toán'}</strong></div>
                        <div>Ngày chi trả: <strong>${s.payment_date || 'Chưa chi trả'}</strong></div>
                    </div>
                </div>
            `;
        } else {
            body.innerHTML = `<div class="alert alert-danger mb-0">${data.message}</div>`;
        }
    })
    .catch(err => {
        console.error(err);
        body.innerHTML = '<div class="alert alert-danger mb-0">Lỗi khi tải phiếu lương.</div>';
    });
}

function openPrintSalaryPage(salId) {
    if (!salId) return;
    const printUrl = `print-salary.php?id=${salId}`;
    window.open(printUrl, '_blank', 'width=900,height=800,menubar=no,toolbar=no,location=no,status=no');
}

function triggerAutoGenerateSalary() {
    const curMonth = document.getElementById('filter_month_year').value !== 'all' ? document.getElementById('filter_month_year').value : '<?= date('m/Y'); ?>';
    Swal.fire({
        title: 'Quét hoa hồng & Tính lương?',
        html: `Hệ thống sẽ tự động quét tất cả các đơn hàng nhân viên đã duyệt trong tháng <b>${curMonth}</b> và khởi tạo / cập nhật bảng lương cho toàn bộ nhân sự.<br><br>Bạn có muốn thực hiện ngay không?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Tính Hoa Hồng &amp; Lương',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_generate_all_salaries', '1');
            formData.append('month_year', curMonth);

            fetch('employee-salaries.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    loadSalariesLive();
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => console.error(err));
        }
    });
}

function toggleSalaryPayment(salId) {
    const formData = new FormData();
    formData.append('ajax_toggle_payment_status', '1');
    formData.append('sal_id', salId);

    fetch('employee-salaries.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.fire({ icon: 'success', title: data.message });
            loadSalariesLive();
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    })
    .catch(err => console.error(err));
}

function deleteSalary(salId) {
    Swal.fire({
        title: 'Xác nhận xóa phiếu lương?',
        html: `Bạn có chắc muốn xóa phiếu lương <b>#${salId}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Phiếu',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_salary', '1');
            formData.append('sal_id', salId);

            fetch('employee-salaries.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('sal-row-' + salId);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(40px)';
                        setTimeout(() => {
                            loadSalariesLive();
                        }, 300);
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => console.error(err));
        }
    });
}
</script>

    </div>
</div>
</body>
</html>
