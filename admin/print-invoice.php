<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    die("<div style='text-align:center; padding: 40px; font-family: sans-serif;'>Mã đơn hàng không hợp lệ. <br><a href='orders.php'>Quay lại</a></div>");
}

// Lấy thông tin đơn hàng và tên nhân viên xác nhận / lập phiếu
$sql = "SELECT o.*, u.email as user_email,
               COALESCE(e.fullname, st.fullname, 'Quản Trị Viên') as staff_fullname,
               COALESCE(e.position, 'Nhân viên bán hàng') as staff_position
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users st ON o.staff_id = st.id
        LEFT JOIN employees e ON o.staff_id = e.user_id OR o.staff_id = e.id
        WHERE o.id = $order_id";
$order = $conn->query($sql)->fetch_assoc();

if (!$order) {
    die("<div style='text-align:center; padding: 40px; font-family: sans-serif;'>Không tìm thấy đơn hàng trong hệ thống. <br><a href='orders.php'>Quay lại</a></div>");
}

// Kiểm tra điều kiện: Phiếu chỉ được in khi đơn hàng đã được XÁC NHẬN trở lên
$allowed_print_statuses = ['confirmed', 'shipping', 'completed'];
if (!in_array($order['status'], $allowed_print_statuses, true)) {
    die("
    <!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <title>Không thể in phiếu</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light d-flex align-items-center justify-content-center' style='min-height: 100vh;'>
        <div class='card border-0 shadow rounded-4 p-5 text-center' style='max-width: 550px;'>
            <div class='mb-3 text-warning' style='font-size: 60px;'><i class='fa-solid fa-clock-rotate-left'></i></div>
            <h4 class='fw-bold text-dark mb-2'>Chưa thể in hóa đơn</h4>
            <p class='text-muted mb-4'>Đơn hàng <b>#" . htmlspecialchars($order['order_code']) . "</b> hiện đang ở trạng thái <b>" . ($order['status'] === 'pending' ? 'Chờ xác nhận' : ($order['status'] === 'cancelled' ? 'Đã hủy' : 'Yêu cầu hoàn trả')) . "</b>. <br><span class='text-primary fw-bold'>Phiếu chỉ được phép in khi đơn hàng đã được Xác Nhận (hoặc Đang giao / Hoàn thành).</span></p>
            <div class='d-flex justify-content-center gap-2'>
                <a href='orders.php' class='btn btn-dark fw-bold rounded-pill px-4'>Quay Lại Quản Lý Đơn</a>
            </div>
        </div>
    </body>
    </html>
    ");
}

// Lấy chi tiết món hàng
$items = [];
$res_items = $conn->query("SELECT od.* FROM order_details od WHERE od.order_id = $order_id");
if ($res_items) {
    while($item = $res_items->fetch_assoc()) {
        $items[] = $item;
    }
}

// Lấy thông tin cửa hàng từ site_settings
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

$buyer_name    = !empty($order['customer_name']) ? $order['customer_name'] : 'Khách vãng lai';
$buyer_phone   = !empty($order['phone']) ? $order['phone'] : 'Chưa có SĐT';
$buyer_address = !empty($order['address_detail']) ? $order['address_detail'] : 'Tại cửa hàng';
$staff_name    = !empty($order['staff_fullname']) ? $order['staff_fullname'] : 'Quản Trị Viên';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Phiếu Đơn Hàng #<?= htmlspecialchars($order['order_code'] ?? $order['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            color: #000;
        }
        .invoice-container {
            max-width: 820px;
            margin: 0 auto;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px dashed #334155;
            padding-bottom: 1.5rem;
        }
        .invoice-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin-top: 1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .table-invoice th {
            background-color: #f1f5f9 !important;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
        }
        .signature-box {
            min-height: 90px;
        }
        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .invoice-container { max-width: 100%; padding: 0; border: none; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print text-center p-3 bg-light border-bottom mb-4 shadow-sm">
    <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold rounded-pill me-2">
        <i class="fa-solid fa-print me-1"></i> IN PHIẾU NGAY
    </button>
    <a href="orders.php" class="btn btn-outline-dark px-4 py-2 fw-bold rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> QUAY LẠI
    </a>
</div>

<div class="invoice-container mb-5">
    <div class="invoice-header">
        <h2 class="fw-bold mb-1 text-uppercase tracking-wide"><?= htmlspecialchars($site_name) ?></h2>
        <p class="mb-0 small text-muted">Địa chỉ: <?= htmlspecialchars($contact_address) ?></p>
        <p class="mb-0 small text-muted">Hotline: <?= htmlspecialchars($contact_hotline) ?></p>
        
        <div class="invoice-title text-dark">PHIẾU XUẤT KHO & GIAO HÀNG</div>
        <p class="mb-0 mt-2 fw-bold fs-5 text-danger">Mã Đơn: #<?= htmlspecialchars($order['order_code'] ?? $order['id']) ?></p>
        <p class="mb-0 small text-muted">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?> | Ngày in: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="row mb-4">
        <div class="col-7">
            <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 text-primary"><i class="fa-solid fa-user me-1"></i>Thông tin người nhận</h6>
            <p class="mb-1"><strong>Họ tên:</strong> <?= htmlspecialchars($buyer_name) ?></p>
            <p class="mb-1"><strong>SĐT:</strong> <?= htmlspecialchars($buyer_phone) ?></p>
            <p class="mb-0"><strong>Địa chỉ nhận:</strong> <?= htmlspecialchars($buyer_address) ?></p>
        </div>
        <div class="col-5 text-end">
            <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 text-success"><i class="fa-solid fa-credit-card me-1"></i>Thanh toán</h6>
            <p class="mb-1"><strong>Hình thức:</strong> <?= ($order['payment_method'] === 'BANKING_QR') ? '⚡ Quét mã VietQR' : '💵 Tiền mặt khi nhận (COD)' ?></p>
            <p class="mb-1"><strong>Trạng thái TT:</strong> 
                <strong><?= ($order['payment_status'] === 'paid' || $order['status'] === 'completed' || $order['payment_method'] === 'BANKING_QR') ? 'ĐÃ THANH TOÁN' : 'CHƯA THANH TOÁN' ?></strong>
            </p>
            <p class="mb-0"><strong>Trạng thái đơn:</strong> <?= ($order['status'] === 'confirmed' ? 'Đã xác nhận' : ($order['status'] === 'shipping' ? 'Đang giao hàng' : 'Đã hoàn thành')) ?></p>
        </div>
    </div>

    <table class="table table-bordered table-invoice mb-4 align-middle">
        <thead>
            <tr class="text-center">
                <th width="5%">STT</th>
                <th>Sản phẩm / Mẫu giày</th>
                <th width="12%">Size (EU)</th>
                <th class="text-end" width="18%">Đơn giá</th>
                <th width="10%">SL</th>
                <th class="text-end" width="20%">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach ($items as $idx => $item): 
                $line_total = $item['price'] * $item['quantity'];
                $subtotal += $line_total;
            ?>
            <tr>
                <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                <td>
                    <strong class="text-dark"><?= htmlspecialchars($item['product_name']) ?></strong>
                    <?php if (!empty($item['color'])): ?>
                        <small class="text-muted d-block">Màu: <?= htmlspecialchars($item['color']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-center fw-bold"><?= htmlspecialchars($item['size']) ?></td>
                <td class="text-end"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
                <td class="text-end fw-bold text-dark"><?= number_format($line_total, 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end fw-semibold">Tạm tính tiền hàng:</td>
                <td class="text-end fw-bold"><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
            </tr>
            <tr>
                <td colspan="5" class="text-end fw-semibold">Phí vận chuyển:</td>
                <td class="text-end fw-bold"><?= number_format($order['shipping_fee'] ?? 0, 0, ',', '.') ?>đ</td>
            </tr>
            <?php if ($order['discount_amount'] > 0): ?>
            <tr>
                <td colspan="5" class="text-end fw-semibold text-danger">Giảm giá Voucher:</td>
                <td class="text-end fw-bold text-danger">-<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</td>
            </tr>
            <?php endif; ?>
            <tr class="table-light">
                <td colspan="5" class="text-end fw-bold fs-5 text-uppercase">TỔNG CỘNG THANH TOÁN:</td>
                <td class="text-end fw-bold fs-4 text-danger"><?= number_format($order['total_money'], 0, ',', '.') ?>đ</td>
            </tr>
        </tfoot>
    </table>

    <!-- Khung Chữ Ký: Khách Hàng & Người Lập Phiếu (In tên nhân viên xác nhận) -->
    <div class="row mt-5 pt-3 text-center">
        <div class="col-6">
            <p class="fw-bold mb-1 text-uppercase text-dark">Khách Hàng Nhận Giày</p>
            <p class="text-muted small mb-0">(Ký & ghi rõ họ tên)</p>
            <div class="signature-box"></div>
            <p class="fw-bold text-dark mb-0 fs-6"><?= htmlspecialchars($buyer_name) ?></p>
        </div>
        <div class="col-6">
            <p class="fw-bold mb-1 text-uppercase text-dark">Người Lập Phiếu (Xác Nhận)</p>
            <p class="text-muted small mb-0">(Đã kiểm tra & đóng gói xuất kho)</p>
            <div class="signature-box"></div>
            <!-- In rõ ràng Họ Tên Nhân Viên Xác Nhận Đơn Hàng -->
            <p class="fw-bold text-dark mb-0 fs-6 text-decoration-underline"><?= htmlspecialchars($staff_name) ?></p>
            <small class="text-muted d-block"><?= htmlspecialchars($order['staff_position'] ?? 'Nhân viên bán hàng') ?></small>
        </div>
    </div>

    <div class="text-center mt-5 pt-3 border-top text-muted small">
        <i>Cảm ơn quý khách đã tin tưởng và mua sắm tại <b><?= htmlspecialchars($site_name) ?></b>! Hotline hỗ trợ: <?= htmlspecialchars($contact_hotline) ?></i>
    </div>
</div>

</body>
</html>
