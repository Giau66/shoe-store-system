<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/CarrierShippingService.php';

$order_id = intval($_GET['order_id'] ?? 0);
$tracking_code = trim($_GET['tracking_code'] ?? '');

if ($order_id <= 0 && empty($tracking_code)) {
    die("Thiếu thông tin đơn hàng để in phiếu.");
}

$sql = "SELECT o.*, p.province_name FROM orders o 
        LEFT JOIN shipping_provinces p ON o.province_id = p.id ";
if ($order_id > 0) {
    $sql .= "WHERE o.id = $order_id LIMIT 1";
} else {
    $code_clean = $conn->real_escape_string($tracking_code);
    $sql .= "WHERE o.tracking_code = '$code_clean' LIMIT 1";
}

$res = $conn->query($sql);
if (!$res || !$order = $res->fetch_assoc()) {
    die("Không tìm thấy thông tin đơn hàng!");
}

$order_id = intval($order['id']);
$carrier_service = new CarrierShippingService($conn);
$carrier = $order['shipping_carrier'] ?: $carrier_service->getActiveCarrier();
$carrier_name = ($carrier === 'GHN') ? 'GIAO HÀNG NHANH (GHN EXPRESS)' : 'GIAO HÀNG TIẾT KIỆM (GHTK)';
$tracking_code = $order['tracking_code'] ?: ('GHTK' . date('ymd') . '.' . strtoupper(substr(md5($order['order_code']), 0, 6)));

// Danh sách sản phẩm
$items = [];
$res_items = $conn->query("SELECT od.*, p.name as product_name, v.size, v.color 
                           FROM order_details od 
                           JOIN products p ON od.product_id = p.id 
                           JOIN product_variants v ON od.variant_id = v.id 
                           WHERE od.order_id = $order_id");
if ($res_items) {
    while ($it = $res_items->fetch_assoc()) {
        $items[] = $it;
    }
}

// Thông tin kho gửi
$sender_name = $carrier_service->getSetting('ghtk_pick_name', 'Kho Giày Shoes Store Vĩnh Long');
$sender_phone = $carrier_service->getSetting('ghtk_pick_tel', '0901.234.567');
$sender_addr = $carrier_service->getSetting('ghtk_pick_address', 'Số 123 Đường Phạm Hùng, Phường 9, TP. Vĩnh Long, Tỉnh Vĩnh Long');

$is_paid = ($order['payment_status'] === 'paid' || $order['payment_method'] === 'BANKING_QR');
$cod_amount = $is_paid ? 0 : floatval($order['total_money']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Phiếu Giao Hàng - <?= htmlspecialchars($tracking_code) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #000;
            padding: 20px;
        }
        .shipping-label-container {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #000;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .carrier-header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .carrier-logo-text {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }
        .barcode-box {
            text-align: center;
            border: 1px dashed #000;
            padding: 8px;
            margin-bottom: 12px;
            background: #fafafa;
        }
        .address-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .cod-box {
            border: 2px solid #000;
            background: #f8fafc;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        .table-items th, .table-items td {
            font-size: 13px;
            padding: 4px 6px;
            border: 1px solid #000;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .shipping-label-container {
                box-shadow: none;
                border: 2px solid #000;
                width: 100%;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="text-center mb-3 no-print">
    <button class="btn btn-dark fw-bold px-4 py-2 me-2 shadow-sm" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> In Phiếu Gửi Hàng (Ctrl + P)
    </button>
    <button class="btn btn-light border fw-bold px-4 py-2" onclick="window.close()">
        <i class="fa-solid fa-xmark me-1"></i> Đóng Cửa Sổ
    </button>
</div>

<div class="shipping-label-container">
    <!-- HEADER ĐƠN VỊ VẬN CHUYỂN -->
    <div class="carrier-header d-flex justify-content-between align-items-center">
        <div>
            <div class="carrier-logo-text text-uppercase text-danger">
                <i class="fa-solid fa-truck-fast me-1"></i> <?= htmlspecialchars($carrier_name) ?>
            </div>
            <div class="small fw-bold text-muted">Dịch vụ: Chuyển phát tiêu chuẩn - Thu hộ COD</div>
        </div>
        <div class="text-end">
            <span class="badge bg-dark fs-6 px-3 py-2">ĐƠN HÀNG: #<?= htmlspecialchars($order['order_code']) ?></span>
            <div class="small mt-1 text-muted">Ngày: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
        </div>
    </div>

    <!-- MÃ VẠCH VẬN ĐƠN (BARCODE) -->
    <div class="barcode-box">
        <svg id="barcode"></svg>
        <div class="fw-bold fs-5 tracking-text"><?= htmlspecialchars($tracking_code) ?></div>
        <div class="small text-muted">Quét mã vạch để phân loại bưu kiện tự động</div>
    </div>

    <!-- THÔNG TIN NGƯỜI GỬI & NGƯỜI NHẬN -->
    <div class="row g-2 mb-2">
        <div class="col-6">
            <div class="address-box h-100">
                <strong class="d-block text-uppercase small text-muted mb-1"><i class="fa-solid fa-arrow-up-from-bracket me-1 text-primary"></i> TỪ (NGƯỜI GỬI):</strong>
                <div class="fw-bold fs-6"><?= htmlspecialchars($sender_name) ?></div>
                <div class="small mb-1"><i class="fa-solid fa-phone me-1"></i> SĐT: <strong><?= htmlspecialchars($sender_phone) ?></strong></div>
                <div class="small text-muted" style="line-height: 1.3;"><?= htmlspecialchars($sender_addr) ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="address-box h-100 bg-light">
                <strong class="d-block text-uppercase small text-danger mb-1"><i class="fa-solid fa-location-dot me-1"></i> ĐẾN (NGƯỜI NHẬN):</strong>
                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($order['customer_name']) ?></div>
                <div class="small mb-1 text-danger"><i class="fa-solid fa-phone me-1"></i> SĐT: <strong><?= htmlspecialchars($order['phone']) ?></strong></div>
                <div class="small fw-semibold text-dark" style="line-height: 1.3;">
                    <?= htmlspecialchars($order['address_detail']) ?>, <?= htmlspecialchars($order['province_name']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH HÀNG HÓA -->
    <div class="mb-2">
        <div class="fw-bold small mb-1 text-uppercase"><i class="fa-solid fa-box-open me-1"></i> Nội dung hàng hóa (Mở xem hàng):</div>
        <table class="table table-bordered table-items mb-0">
            <thead>
                <tr class="table-light">
                    <th>STT</th>
                    <th>Tên Sản Phẩm &amp; Phân Loại</th>
                    <th class="text-center" style="width: 60px;">SL</th>
                    <th class="text-end" style="width: 100px;">Thành Tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $stt = 1; foreach($items as $item): ?>
                    <tr>
                        <td class="text-center"><?= $stt++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <span class="text-muted">(Size: <?= htmlspecialchars($item['size']) ?>, Màu: <?= htmlspecialchars($item['color']) ?>)</span>
                        </td>
                        <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
                        <td class="text-end"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- TIỀN THU HỘ COD & GHI CHÚ -->
    <div class="row g-2 mb-2">
        <div class="col-6">
            <div class="cod-box h-100">
                <div class="small text-uppercase text-muted">TIỀN THU HỘ (COD)</div>
                <?php if ($cod_amount > 0): ?>
                    <div class="fs-4 text-danger fw-black"><?= number_format($cod_amount, 0, ',', '.') ?> VNĐ</div>
                    <div class="small text-danger fw-bold">(Thu đúng số tiền trên)</div>
                <?php else: ?>
                    <div class="fs-4 text-success fw-black">0 VNĐ (ĐÃ THANH TOÁN)</div>
                    <div class="small text-success fw-bold">KHÔNG THU TIỀN KHÁCH HÀNG</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-6">
            <div class="address-box h-100">
                <div class="small fw-bold text-uppercase text-muted">Ghi chú chuyển phát:</div>
                <div class="small text-danger fw-bold mt-1">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> Cho khách xem hàng, không thử. Hàng dễ vỡ xin nhẹ tay!
                </div>
                <?php if (!empty($order['note'])): ?>
                    <div class="small text-muted mt-1">Ghi chú của khách: <i><?= htmlspecialchars($order['note']) ?></i></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CHỮ KÝ XÁC NHẬN -->
    <div class="row text-center mt-3 pt-2 border-top border-dark small">
        <div class="col-6">
            <strong>Chữ ký người gửi</strong>
            <div style="height: 45px;"></div>
            <span>(Đã ký &amp; đóng gói)</span>
        </div>
        <div class="col-6">
            <strong>Chữ ký người nhận</strong>
            <div style="height: 45px;"></div>
            <span>(Ký và ghi rõ họ tên)</span>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    JsBarcode("#barcode", "<?= htmlspecialchars($tracking_code) ?>", {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 40,
        displayValue: false
    });
});
</script>

</body>
</html>
