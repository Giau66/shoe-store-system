<?php
include_once 'includes/header.php';

$curr_uid = intval($_SESSION['user_id']);

// Lấy thông tin user hiện tại
$user_info = $conn->query("SELECT * FROM users WHERE id = $curr_uid")->fetch_assoc() ?? [];
$emp_info  = $conn->query("SELECT * FROM employees WHERE user_id = $curr_uid")->fetch_assoc() ?? [];

// Thống kê doanh số bán hàng của nhân viên này
$sales_res = $conn->query("SELECT 
    COALESCE(SUM(o.total_money), 0) AS total_sales,
    COALESCE(SUM(od.quantity), 0) AS total_products_sold
    FROM orders o 
    LEFT JOIN order_details od ON o.id = od.order_id 
    WHERE o.staff_id = $curr_uid AND o.status = 'completed'");

$sales_data = $sales_res ? $sales_res->fetch_assoc() : [];
$total_sales = floatval($sales_data['total_sales'] ?? 0);
$total_products_sold = intval($sales_data['total_products_sold'] ?? 0);

$base_sal = floatval($emp_info['base_salary'] ?? 5000000);
$comm_rate = floatval($emp_info['commission_rate'] ?? 2.5);
$calc_commission = $total_sales * ($comm_rate / 100);
$off_days = intval($emp_info['off_days'] ?? 0);
$work_days = 26 - $off_days;
$daily_salary = ($base_sal / 26) * $work_days;
$bonus = floatval($emp_info['bonus'] ?? 0);
$fine = floatval($emp_info['fine'] ?? ($off_days * 100000));
$net_salary = $daily_salary + $calc_commission + $bonus - $fine;

// Lấy thời khóa biểu lịch làm việc của nhân viên
$emp_id = intval($emp_info['id'] ?? 0);
$schedules = [];
if ($emp_id > 0) {
    $sch_res = $conn->query("SELECT * FROM employee_schedules WHERE employee_id = $emp_id");
    if ($sch_res) {
        while ($row_s = $sch_res->fetch_assoc()) {
            $schedules[$row_s['day_of_week']] = $row_s;
        }
    }
}

$days_list = [
    'Thu 2' => 'Thứ Hai',
    'Thu 3' => 'Thứ Ba',
    'Thu 4' => 'Thứ Tư',
    'Thu 5' => 'Thứ Năm',
    'Thu 6' => 'Thứ Sáu',
    'Thu 7' => 'Thứ Bảy',
    'Chu Nhat' => 'Chủ Nhật'
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: var(--bg-dark-slate);">
            <i class="fa-solid fa-id-badge me-2" style="color: var(--active-sage);"></i>Hồ Sơ Cá Nhân & Bảng Lương Nhân Viên
        </h4>
        <span class="text-muted small">Xem chi tiết thu nhập, hoa hồng bán hàng, ngày nghỉ & thời khóa biểu lịch làm việc của bạn.</span>
    </div>
    <?php if ($user_role === 'admin'): ?>
    <a href="employees.php" class="btn btn-warning text-dark fw-bold rounded-3 px-3 shadow-sm">
        <i class="fa-solid fa-users-gear me-1"></i> Quản Lý Nhân Viên
    </a>
    <?php endif; ?>
</div>

<div class="row g-4 mb-5">
    <!-- CỘT TRÁI: THÔNG TIN BẢN THÂN -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white h-100">
            <?php 
            $avatar = $user_info['avatar'] ?? $emp_info['avatar'] ?? '';
            $avatar_src = (!empty($avatar) && strpos($avatar, 'http') === 0) ? $avatar : (!empty($avatar) ? '../' . $avatar : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=300');
            ?>
            <img src="<?= htmlspecialchars($avatar_src) ?>" class="rounded-circle border shadow-sm mx-auto mb-3" style="width: 110px; height: 110px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=300'">
            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user_info['fullname'] ?? 'Chưa cập nhật tên') ?></h5>
            <span class="badge text-uppercase px-3 py-2 rounded-pill mb-3" style="background-color: var(--active-sage); color: white;"><?= htmlspecialchars($emp_info['position'] ?? 'Quản Trị / Nhân Viên') ?></span>

            <div class="text-start border-top pt-3 mt-2">
                <p class="mb-2 text-muted small"><i class="fa-solid fa-envelope me-2 text-danger"></i>Email: <strong class="text-dark"><?= htmlspecialchars($user_info['email'] ?? 'N/A') ?></strong></p>
                <p class="mb-2 text-muted small"><i class="fa-solid fa-phone me-2 text-success"></i>Số điện thoại: <strong class="text-dark"><?= htmlspecialchars($user_info['phone'] ?? 'Chưa cập nhật') ?></strong></p>
                <p class="mb-2 text-muted small"><i class="fa-solid fa-id-card me-2 text-info"></i>Số CCCD: <strong class="text-dark"><?= htmlspecialchars($emp_info['citizen_id'] ?? 'Chưa cập nhật') ?></strong></p>
                <p class="mb-2 text-muted small"><i class="fa-solid fa-location-dot me-2 text-warning"></i>Địa chỉ: <strong class="text-dark"><?= htmlspecialchars($emp_info['address'] ?? 'Chưa cập nhật') ?></strong></p>
                <p class="mb-0 text-muted small"><i class="fa-solid fa-clock me-2 text-primary"></i>Ca làm cố định: <strong class="text-dark fw-bold"><?= htmlspecialchars($emp_info['work_shift'] ?? 'Ca 1 (07:30 - 12:00)') ?></strong></p>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: BẢNG TÍNH LƯƠNG & HOA HỒNG THỰC NHẬN -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--bg-dark-slate);">
                <i class="fa-solid fa-calculator me-2 text-success"></i>Chi Tiết Thu Nhập Tháng Này
            </h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted d-block mb-1">Lương Cơ Bản (26 công)</small>
                        <h5 class="fw-bold text-dark mb-0"><?= number_format($base_sal, 0, ',', '.') ?>đ</h5>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted d-block mb-1">Hoa Hồng Bán Hàng</small>
                        <h5 class="fw-bold text-success mb-0">+<?= number_format($calc_commission, 0, ',', '.') ?>đ</h5>
                        <small class="text-muted" style="font-size: 11px;"><?= $comm_rate ?>% / <?= number_format($total_sales, 0, ',', '.') ?>đ</small>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 bg-light rounded-3 border text-center">
                        <small class="text-muted d-block mb-1">Tiền Phạt Nghỉ (100k/ngày)</small>
                        <h5 class="fw-bold text-danger mb-0">-<?= number_format($fine, 0, ',', '.') ?>đ</h5>
                        <small class="text-muted" style="font-size: 11px;"><?= $off_days ?> ngày nghỉ</small>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 rounded-3 border text-center text-white" style="background-color: var(--bg-dark-slate);">
                        <small class="text-warning fw-bold d-block mb-1">THỰC NHẬN BÁO CÁO</small>
                        <h4 class="fw-bold text-white mb-0"><?= number_format($net_salary, 0, ',', '.') ?>đ</h4>
                    </div>
                </div>
            </div>

            <!-- CHI TIẾT TỰ ĐỘNG PHẠT NGHỈ & THƯỞNG -->
            <div class="alert alert-warning border-0 rounded-3 mb-3">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><b>Quy định điểm danh / nghỉ việc</b>: Khi bạn xin nghỉ 1 ngày, hệ thống sẽ tự động trừ <b>100.000đ</b> vào tiền phạt và tự động ghi nhận lý do trừ tiền.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle small mb-0">
                    <thead class="table-light text-uppercase text-secondary">
                        <tr>
                            <th>Mục Thu Nhập / Khấu Trừ</th>
                            <th>Giá Trị</th>
                            <th>Ghi Chú & Lý Do Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Lương ngày công làm thực tế</strong></td>
                            <td class="text-dark fw-bold"><?= number_format($daily_salary, 0, ',', '.') ?>đ</td>
                            <td>Đã làm <?= $work_days ?>/26 ngày công chuẩn.</td>
                        </tr>
                        <tr>
                            <td><strong>Hoa hồng bán đôi giày (Sneaker)</strong></td>
                            <td class="text-success fw-bold">+<?= number_format($calc_commission, 0, ',', '.') ?>đ</td>
                            <td>Bán được <?= number_format($total_products_sold) ?> sản phẩm (Tổng doanh số <?= number_format($total_sales, 0, ',', '.') ?>đ x <?= $comm_rate ?>%).</td>
                        </tr>
                        <tr>
                            <td><strong>Trừ tiền nghỉ việc (Auto 100k/ngày)</strong></td>
                            <td class="text-danger fw-bold">-<?= number_format($fine, 0, ',', '.') ?>đ</td>
                            <td><?= htmlspecialchars($emp_info['fine_reason'] ?? "Tự động trừ $off_days ngày nghỉ") ?> (Chi tiết ngày nghỉ: <?= htmlspecialchars($emp_info['off_dates_detail'] ?? 'Không có') ?>)</td>
                        </tr>
                        <?php if ($bonus > 0): ?>
                        <tr>
                            <td><strong>Tiền thưởng bổ sung</strong></td>
                            <td class="text-success fw-bold">+<?= number_format($bonus, 0, ',', '.') ?>đ</td>
                            <td>Lý do thưởng: <?= htmlspecialchars($emp_info['bonus_reason'] ?? 'Thưởng chuyên cần / thành tích') ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- THỜI KHÓA BIỂU LỊCH LÀM VIỆC THEO TUẦN -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <h5 class="fw-bold mb-3" style="color: var(--bg-dark-slate);">
        <i class="fa-solid fa-calendar-days me-2 text-warning"></i>Thời Khóa Biểu & Lịch Làm Việc Tuần Của Bạn
    </h5>
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle mb-0">
            <thead class="table-dark text-uppercase small" style="background-color: var(--bg-dark-slate);">
                <tr>
                    <?php foreach($days_list as $day_code => $day_name): ?>
                        <th><?= $day_name ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach($days_list as $day_code => $day_name): 
                        $sch = $schedules[$day_code] ?? null;
                    ?>
                        <td style="min-width: 130px; vertical-align: top;" class="p-3 bg-light">
                            <?php if ($sch && !empty($sch['shift_name']) && $sch['shift_name'] !== 'Nghỉ'): ?>
                                <span class="badge text-uppercase d-block mb-2 py-2 fs-6" style="background-color: var(--active-sage); color: white;"><?= htmlspecialchars($sch['shift_name']) ?></span>
                                <small class="text-muted d-block fw-bold mb-1">
                                    <i class="fa-solid fa-clock me-1 text-warning"></i>
                                    <?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?>
                                </small>
                                <?php if (!empty($sch['note'])): ?>
                                    <span class="badge bg-warning-subtle text-dark d-block border border-warning" style="font-size: 11px;"><?= htmlspecialchars($sch['note']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary text-uppercase d-block py-2">Nghỉ Ca</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

    </div>
</div>
</body>
</html>
