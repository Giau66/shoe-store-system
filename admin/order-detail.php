<?php 
include_once 'includes/header.php'; 

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();

if (!$order) {
    echo "<div class='alert alert-danger'>Hóa đơn không tồn tại!</div>";
    include_once 'includes/footer.php';
    exit();
}

$items = $conn->query("SELECT * FROM order_details WHERE order_id = $order_id");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-uppercase mb-0">Chi Tiết Hóa Đơn #<?= $order['order_code']; ?></h4>
    <div>
        <button onclick="window.print()" class="btn btn-dark fw-bold rounded-3 me-2"><i class="fa-solid fa-print me-1"></i> In Hóa Đơn</button>
        <a href="orders.php" class="btn btn-outline-secondary fw-bold rounded-3"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại</a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-5 bg-white" id="invoicePrintArea">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
        <div>
            <h3 class="fw-bold text-success mb-1">SHOES STORE</h3>
            <p class="text-muted small mb-0">Hóa Đơn Bán Hàng Trực Tuyến</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-uppercase text-danger mb-1">Mã Hóa Đơn: #<?= $order['order_code']; ?></h5>
            <small class="text-muted">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-uppercase text-muted">Thông Tin Khách Hàng:</h6>
            <p class="mb-1 fw-bold text-dark fs-5"><?= htmlspecialchars($order['customer_name']); ?></p>
            <p class="mb-1 text-secondary"><i class="fa-solid fa-phone me-2"></i><?= htmlspecialchars($order['phone']); ?></p>
            <p class="mb-1 text-secondary"><i class="fa-solid fa-envelope me-2"></i><?= htmlspecialchars($order['email']); ?></p>
            <p class="mb-0 text-secondary"><i class="fa-solid fa-location-dot me-2"></i><?= htmlspecialchars($order['address_detail']); ?></p>
        </div>
        <div class="col-6 text-end">
            <h6 class="fw-bold text-uppercase text-muted">Phương Thức Thanh Toán:</h6>
            <span class="badge bg-secondary fs-6 mb-2"><?= $order['payment_method']; ?></span>
            <p class="mb-0 text-muted">Trạng thái đơn: <strong class="text-uppercase text-primary"><?= $order['status']; ?></strong></p>
        </div>
    </div>

    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light text-uppercase small">
            <tr>
                <th>Sản Phẩm</th>
                <th class="text-center">Size</th>
                <th class="text-center">Đơn Giá</th>
                <th class="text-center">Số Lượng</th>
                <th class="text-end">Thành Tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php while($it = $items->fetch_assoc()): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($it['product_name']); ?></td>
                    <td class="text-center fw-bold">Size <?= $it['size']; ?></td>
                    <td class="text-center"><?= number_format($it['price'], 0, ',', '.'); ?>đ</td>
                    <td class="text-center fw-bold">x<?= $it['quantity']; ?></td>
                    <td class="text-end fw-bold text-danger"><?= number_format($it['price'] * $it['quantity'], 0, ',', '.'); ?>đ</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="row justify-content-end">
        <div class="col-5">
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Phí vận chuyển:</span><strong class="text-success">30.000đ</strong></div>
            <div class="d-flex justify-content-between border-top pt-2"><span class="fs-5 fw-bold">TỔNG CỘNG:</span><span class="fs-4 fw-bold text-danger"><?= number_format($order['total_money'], 0, ',', '.'); ?>đ</span></div>
        </div>
    </div>
</div>

    </div>
</div>
</body>
</html>