<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}

$sal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($sal_id <= 0) {
    die("<div style='text-align:center; padding: 40px; font-family: sans-serif;'>Mã phiếu lương không hợp lệ. <br><a href='employee-salaries.php'>Quay lại</a></div>");
}

// Lấy thông tin phiếu lương & nhân viên
$stmt = $conn->prepare("
    SELECT s.*, e.fullname, e.phone, e.email, e.citizen_id, e.birthday, e.address, e.avatar, e.created_at AS emp_created_at
    FROM employee_salaries s
    JOIN employees e ON s.employee_id = e.id
    WHERE s.id = ? LIMIT 1
");
$stmt->bind_param("i", $sal_id);
$stmt->execute();
$sal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sal) {
    die("<div style='text-align:center; padding: 40px; font-family: sans-serif;'>Không tìm thấy phiếu lương trong hệ thống. <br><a href='employee-salaries.php'>Quay lại</a></div>");
}

// Lấy cài đặt website
$settings = [];
$res_set = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_group IN ('general', 'contact')");
if ($res_set) {
    while ($row = $res_set->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$site_name = $settings['site_name'] ?? 'SHOES STORE';
$contact_address = $settings['contact_address'] ?? 'TP. Vĩnh Long, Việt Nam';
$contact_hotline = $settings['contact_hotline'] ?? '0901.234.567';

$base_salary     = floatval($sal['base_salary']);
$work_days       = intval($sal['work_days']);
$off_days        = intval($sal['off_days']);
$allowance       = floatval($sal['allowance']);
$comm_amount     = floatval($sal['commission_amount'] ?? 0);
$comm_rate       = floatval($sal['commission_rate'] ?? 3.00);
$orders_cnt      = intval($sal['confirmed_orders_count'] ?? 0);
$sales_total     = floatval($sal['confirmed_sales_total'] ?? 0);
$bonus           = floatval($sal['bonus'] ?? 0);
$bonus_reason    = trim($sal['bonus_reason'] ?? '');
$fine            = floatval($sal['fine'] ?? 0);
$fine_reason     = trim($sal['fine_reason'] ?? '');
$total_salary    = floatval($sal['total_salary']);

$work_salary     = round($base_salary / 26 * $work_days);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Lương Nhân Viên - <?= htmlspecialchars($sal['fullname']) ?> (<?= htmlspecialchars($sal['month_year']) ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 20px 0;
        }
        .salary-slip-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        }
        .salary-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px dashed #94a3b8;
            padding-bottom: 1.5rem;
        }
        .salary-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .table-slip th {
            background-color: #f1f5f9 !important;
            font-weight: 700;
            color: #334155;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .signature-box {
            min-height: 85px;
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .salary-slip-container {
                max-width: 100% !important;
                padding: 1.5rem !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .table-slip th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

<!-- THANH CÔNG CỤ IN & XUẤT FILE TRÊN ĐẦU TRANG -->
<div class="no-print text-center p-3 bg-white border-bottom mb-4 shadow-sm">
    <div class="d-inline-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm">
            <i class="fa-solid fa-print me-1"></i> In Ra Giấy / Lưu File PDF
        </button>
        <a href="employee-salaries.php" class="btn btn-outline-dark px-4 py-2 fw-bold rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Quay Lại Bảng Lương
        </a>
    </div>
    <div class="text-muted small mt-2">
        <i class="fa-solid fa-circle-info me-1"></i> Để in ra file: Trong hộp thoại Print, chọn máy in là <b>"Save as PDF"</b> hoặc <b>"Microsoft Print to PDF"</b> rồi bấm <b>Print</b>.
    </div>
</div>

<!-- KHUNG PHIẾU LƯƠNG CHUẨN A4 -->
<div class="salary-slip-container mb-5">
    <!-- HEADER PHIẾU -->
    <div class="salary-header">
        <h3 class="fw-bold mb-1 text-uppercase text-dark"><?= htmlspecialchars($site_name) ?></h3>
        <p class="mb-0 small text-muted">Địa chỉ: <?= htmlspecialchars($contact_address) ?></p>
        <p class="mb-0 small text-muted">Hotline: <?= htmlspecialchars($contact_hotline) ?></p>
        
        <div class="salary-title">PHIẾU THANH TOÁN TIỀN LƯƠNG NHÂN VIÊN</div>
        <div class="mt-2">
            <span class="badge bg-dark text-white px-3 py-2 fs-6">KỲ LƯƠNG THÁNG: <?= htmlspecialchars($sal['month_year']) ?></span>
        </div>
        <p class="mb-0 mt-2 small text-muted">Mã phiếu lương: <b>#SAL-<?= str_pad($sal['id'], 5, '0', STR_PAD_LEFT) ?></b> | Ngày in phiếu: <?= date('d/m/Y H:i') ?></p>
    </div>

    <!-- THÔNG TIN NHÂN SỰ -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 text-primary" style="font-size: 13px;">
                <i class="fa-solid fa-id-card me-1"></i> Thông Tin Nhân Viên
            </h6>
            <p class="mb-1"><strong>Họ và tên:</strong> <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($sal['fullname']) ?></span></p>
            <p class="mb-1"><strong>Mã nhân viên:</strong> #<?= $sal['employee_id'] ?></p>
            <p class="mb-1"><strong>Số CCCD/CMND:</strong> <?= htmlspecialchars($sal['citizen_id'] ?: 'Chưa cập nhật') ?></p>
            <p class="mb-0"><strong>Địa chỉ:</strong> <?= htmlspecialchars($sal['address'] ?: 'Chưa cập nhật') ?></p>
        </div>
        <div class="col-6 text-end">
            <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 text-success" style="font-size: 13px;">
                <i class="fa-solid fa-circle-check me-1"></i> Tình Trạng Chi Trả
            </h6>
            <p class="mb-1"><strong>Số điện thoại:</strong> <?= htmlspecialchars($sal['phone'] ?: 'Chưa có') ?></p>
            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($sal['email']) ?></p>
            <p class="mb-1"><strong>Trạng thái:</strong> 
                <strong class="<?= $sal['status'] === 'paid' ? 'text-success' : 'text-warning' ?>">
                    <?= $sal['status'] === 'paid' ? '✅ ĐÃ THANH TOÁN' : '🔒 CHƯA THANH TOÁN' ?>
                </strong>
            </p>
            <p class="mb-0"><strong>Ngày chi trả:</strong> <?= $sal['payment_date'] ? date('d/m/Y', strtotime($sal['payment_date'])) : 'Chưa chi trả' ?></p>
        </div>
    </div>

    <!-- BẢNG CHI TIẾT CÁC KHOẢN THU NHẬP VÀ KHẤU TRỪ -->
    <table class="table table-bordered table-slip align-middle mb-4">
        <thead>
            <tr class="text-center">
                <th width="8%">STT</th>
                <th>Khoản Mục Thu Nhập &amp; Khấu Trừ</th>
                <th width="35%">Căn Cứ / Chi Tiết Tính</th>
                <th width="22%" class="text-end">Số Tiền (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. Lương cơ bản định mức -->
            <tr>
                <td class="text-center fw-bold">1</td>
                <td><strong>Lương cơ bản định mức</strong></td>
                <td>Định mức 26 ngày công chuẩn / tháng</td>
                <td class="text-end font-monospace"><?= number_format($base_salary, 0, ',', '.') ?> đ</td>
            </tr>

            <!-- 2. Lương theo ngày công thực tế -->
            <tr>
                <td class="text-center fw-bold">2</td>
                <td><strong>Lương ngày công thực tế</strong></td>
                <td><?= $work_days ?> ngày làm việc (Đơn giá: <?= number_format(round($base_salary / 26), 0, ',', '.') ?> đ/ngày)</td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($work_salary, 0, ',', '.') ?> đ</td>
            </tr>

            <!-- 3. Phụ cấp cố định -->
            <tr>
                <td class="text-center fw-bold">3</td>
                <td><strong>Phụ cấp ăn trưa &amp; xăng xe</strong></td>
                <td>Hỗ trợ phúc lợi cố định hàng tháng</td>
                <td class="text-end font-monospace text-success fw-bold">+<?= number_format($allowance, 0, ',', '.') ?> đ</td>
            </tr>

            <!-- 4. Hoa hồng đơn hàng duyệt (Tự động) -->
            <tr>
                <td class="text-center fw-bold">4</td>
                <td>
                    <strong class="text-primary">Hoa hồng xác nhận đơn hàng</strong>
                    <small class="d-block text-muted">Thưởng doanh số đơn duyệt trong tháng</small>
                </td>
                <td>
                    <?= $orders_cnt ?> đơn đã duyệt | Doanh số: <?= number_format($sales_total, 0, ',', '.') ?> đ (Tỷ lệ <?= $comm_rate ?>%)
                </td>
                <td class="text-end font-monospace text-primary fw-bold">+<?= number_format($comm_amount, 0, ',', '.') ?> đ</td>
            </tr>

            <!-- 5. Tiền thưởng thêm (Chỉ có Tăng) -->
            <?php if ($bonus > 0 || !empty($bonus_reason)): ?>
            <tr>
                <td class="text-center fw-bold">5</td>
                <td>
                    <strong class="text-success">Tiền thưởng thêm (+)</strong>
                    <small class="d-block text-muted">Khoản cộng thêm tăng thu nhập</small>
                </td>
                <td><?= htmlspecialchars($bonus_reason ?: 'Thưởng hoàn thành xuất sắc nhiệm vụ') ?></td>
                <td class="text-end font-monospace text-success fw-bold">+<?= number_format($bonus, 0, ',', '.') ?> đ</td>
            </tr>
            <?php endif; ?>

            <!-- 6. Tiền phạt / Khấu trừ (Chỉ có Giảm) -->
            <?php if ($fine > 0 || !empty($fine_reason) || $off_days > 0): ?>
            <tr>
                <td class="text-center fw-bold"><?= ($bonus > 0 || !empty($bonus_reason)) ? '6' : '5' ?></td>
                <td>
                    <strong class="text-danger">Tiền phạt / Khấu trừ (-)</strong>
                    <small class="d-block text-muted">Khoản khấu trừ giảm thu nhập</small>
                </td>
                <td><?= htmlspecialchars($fine_reason ?: ($off_days > 0 ? "Khấu trừ $off_days ngày nghỉ" : 'Vi phạm nội quy')) ?></td>
                <td class="text-end font-monospace text-danger fw-bold">-<?= number_format($fine, 0, ',', '.') ?> đ</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-light">
                <td colspan="3" class="text-end fw-bold fs-5 text-uppercase">
                    TỔNG LƯƠNG THỰC LÃNH CHI TRẢ:
                </td>
                <td class="text-end fw-bold fs-4 text-success font-monospace">
                    <?= number_format($total_salary, 0, ',', '.') ?> đ
                </td>
            </tr>
        </tfoot>
    </table>

    <?php if (!empty($sal['note'])): ?>
        <div class="p-2 mb-4 bg-light rounded border text-muted small">
            <strong>Ghi chú:</strong> <?= htmlspecialchars($sal['note']) ?>
        </div>
    <?php endif; ?>

    <!-- KHUNG CHỮ KÝ 3 BÊN -->
    <div class="row mt-5 pt-3 text-center">
        <div class="col-4">
            <p class="fw-bold mb-1 text-uppercase text-dark small">Người Lập Phiếu</p>
            <p class="text-muted small mb-0" style="font-size: 11px;">(Ký &amp; ghi rõ họ tên)</p>
            <div class="signature-box"></div>
            <p class="fw-bold text-dark mb-0 small"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Kế Toán Lương') ?></p>
        </div>
        <div class="col-4">
            <p class="fw-bold mb-1 text-uppercase text-dark small">Nhân Viên Nhận Lương</p>
            <p class="text-muted small mb-0" style="font-size: 11px;">(Đã nhận đủ số tiền)</p>
            <div class="signature-box"></div>
            <p class="fw-bold text-dark mb-0 small"><?= htmlspecialchars($sal['fullname']) ?></p>
        </div>
        <div class="col-4">
            <p class="fw-bold mb-1 text-uppercase text-dark small">Ban Giám Đốc Duyệt</p>
            <p class="text-muted small mb-0" style="font-size: 11px;">(Ký tên &amp; đóng dấu)</p>
            <div class="signature-box"></div>
            <p class="fw-bold text-dark mb-0 small">Giám Đốc / Quản Lý</p>
        </div>
    </div>

    <div class="text-center mt-5 pt-3 border-top text-muted small">
        <i>Mọi thắc mắc về bảng lương và hoa hồng xin vui lòng liên hệ phòng Kế toán &amp; Nhân sự trong vòng 03 ngày làm việc.</i>
    </div>
</div>

</body>
</html>
