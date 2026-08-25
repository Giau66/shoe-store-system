<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';

$order_code = $conn->real_escape_string($_GET['code'] ?? '');

if (!$order_code) {
    header('Location: index.php');
    exit;
}

$res_order = $conn->query("SELECT * FROM orders WHERE order_code = '$order_code'");
$order = $res_order ? $res_order->fetch_assoc() : null;

if (!$order) {
    header('Location: index.php');
    exit;
}

// Lấy chi tiết đơn hàng
$order_id = intval($order['id']);
$order_details = [];
$res_det = $conn->query("SELECT * FROM order_details WHERE order_id = $order_id");
if ($res_det) {
    while ($item = $res_det->fetch_assoc()) {
        $order_details[] = $item;
    }
}

$page_title = "Đặt hàng thành công";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden bg-white">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <i class="fa-solid fa-circle-check text-success" style="font-size: 80px; color: var(--gold-accent) !important;"></i>
                    </div>
                    <h2 class="fw-bold mb-2 text-uppercase" style="color: var(--dark-luxury);">ĐẶT HÀNG THÀNH CÔNG!</h2>
                    <p class="text-muted lead mb-4">Cảm ơn bạn đã mua sắm tại cửa hàng của chúng tôi.</p>
                    
                    <div class="bg-light p-4 rounded-4 text-start mb-4 border">
                        <div class="row mb-3 border-bottom pb-2">
                            <div class="col-sm-4 text-muted">Mã đơn hàng:</div>
                            <div class="col-sm-8 fw-bold text-danger">#<?= htmlspecialchars($order['order_code']) ?></div>
                        </div>
                        <div class="row mb-3 border-bottom pb-2">
                            <div class="col-sm-4 text-muted">Phương thức thanh toán:</div>
                            <div class="col-sm-8 fw-bold text-dark">
                                <?= $order['payment_method'] === 'BANKING_QR' ? 'Chuyển khoản (VietQR)' : 'Thanh toán khi nhận hàng (COD)' ?>
                            </div>
                        </div>
                        <div class="row mb-3 border-bottom pb-2">
                            <div class="col-sm-4 text-muted">Trạng thái đơn hàng:</div>
                            <div class="col-sm-8 fw-bold">
                                <span class="badge bg-warning text-dark px-3 py-2">⏳ Chờ xác nhận</span>
                            </div>
                        </div>
                        <div class="row mb-3 border-bottom pb-2">
                            <div class="col-sm-4 text-muted">Trạng thái thanh toán:</div>
                            <div class="col-sm-8 fw-bold">
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="badge bg-success px-3 py-2">✓ Đã thanh toán (VietQR)</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark px-3 py-2">⏳ Chưa thanh toán</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-3 border-bottom pb-2">
                            <div class="col-sm-4 text-muted">Tổng thanh toán:</div>
                            <div class="col-sm-8 fw-bold text-danger fs-5"><?= number_format($order['total_money'], 0, ',', '.') ?>đ</div>
                        </div>
                        
                        <h6 class="fw-bold mb-3 mt-4 text-uppercase small text-muted"><i class="fa-solid fa-boxes-packing me-1"></i>Sản phẩm đã mua:</h6>
                        <?php foreach ($order_details as $item): ?>
                            <div class="d-flex align-items-center mb-3 p-2 bg-white rounded-3 border">
                                <img src="<?= htmlspecialchars($item['product_image']) ?>" class="rounded-3 me-3" style="width: 55px; height: 55px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="mb-0 fw-bold text-dark"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <small class="text-muted">Size: <strong><?= htmlspecialchars($item['size']) ?></strong> <?= !empty($item['color']) ? '| Màu: ' . htmlspecialchars($item['color']) : '' ?> x <strong><?= $item['quantity'] ?></strong></small>
                                </div>
                                <div class="fw-bold text-danger me-2"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-outline-dark px-4 py-2 rounded-pill fw-bold">Tiếp tục mua sắm</a>
                        <a href="my-orders.php" class="btn btn-warning px-4 py-2 fw-bold rounded-pill text-dark shadow">Xem đơn hàng của tôi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>