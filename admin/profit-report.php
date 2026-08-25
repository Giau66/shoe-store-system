<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 1. Thống kê Doanh thu & Lợi nhuận các đơn hàng ĐÃ HOÀN THÀNH
$sql_summary = "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(od.price * od.quantity) as total_revenue,
                    SUM(p.cost_price * od.quantity) as total_cost,
                    SUM(o.discount_amount) as total_discount,
                    SUM(o.shipping_fee) as total_shipping
                FROM orders o
                JOIN order_details od ON o.id = od.order_id
                JOIN products p ON od.product_id = p.id
                WHERE o.status = 'completed'";
$res_sum = $conn->query($sql_summary);
$sum = $res_sum->fetch_assoc();

$total_revenue  = $sum['total_revenue'] ?? 0;
$total_cost     = $sum['total_cost'] ?? 0;
$total_discount = $sum['total_discount'] ?? 0;
$total_profit   = $total_revenue - $total_cost - $total_discount;

// 2. Chi tiết lợi nhuận từng đơn hàng
$sql_orders = "SELECT o.id, o.order_code, o.customer_name, o.created_at, o.total_money,
                      SUM(od.price * od.quantity) as revenue,
                      SUM(p.cost_price * od.quantity) as cost,
                      o.discount_amount
               FROM orders o
               JOIN order_details od ON o.id = od.order_id
               JOIN products p ON od.product_id = p.id
               WHERE o.status = 'completed'
               GROUP BY o.id
               ORDER BY o.id DESC";
$res_orders = $conn->query($sql_orders);

include_once 'includes/header.php'; // Header giao diện admin
?>

<div class="container-fluid my-4">
    <h3 class="fw-bold mb-4 text-uppercase text-primary">
        <i class="fa-solid fa-chart-line me-2"></i>Báo Cáo Doanh Thu & Lợi Nhuận Ròng (P&L)
    </h3>

    <!-- 3 KHUNG TỔNG QUAN CHI TIẾT -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4">
                <span class="small text-uppercase fw-bold opacity-75">TỔNG DOANH THU</span>
                <h2 class="fw-black my-2"><?= number_format($total_revenue, 0, ',', '.'); ?>đ</h2>
                <small>Từ <?= $sum['total_orders'] ?? 0; ?> đơn hàng thành công</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-secondary text-white p-4">
                <span class="small text-uppercase fw-bold opacity-75">TỔNG GIÁ VỐN SẢN PHẨM</span>
                <h2 class="fw-black my-2"><?= number_format($total_cost, 0, ',', '.'); ?>đ</h2>
                <small>Chi phí nhập kho gốc</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-success text-white p-4">
                <span class="small text-uppercase fw-bold opacity-75">LỢI NHUẬN RÒNG THỰC TẾ</span>
                <h2 class="fw-black my-2"><?= number_format($total_profit, 0, ',', '.'); ?>đ</h2>
                <small>Đã trừ Giảm giá / Voucher</small>
            </div>
        </div>
    </div>

    <!-- BẢNG BÁO CÁO BẢNG ĐƠN HÀNG CHI TIẾT -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
        <h5 class="fw-bold mb-3">Chi Tiết Lợi Nhuận Theo Đơn Hàng</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Ngày Đặt</th>
                        <th>Doanh Thu</th>
                        <th>Giá Vốn</th>
                        <th>Giảm Giá</th>
                        <th>Lợi Nhuận Ròng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_orders && $res_orders->num_rows > 0): ?>
                        <?php while ($ord = $res_orders->fetch_assoc()): ?>
                            <?php $profit = $ord['revenue'] - $ord['cost'] - $ord['discount_amount']; ?>
                            <tr>
                                <td class="fw-bold text-primary">#<?= $ord['order_code']; ?></td>
                                <td><?= htmlspecialchars($ord['customer_name']); ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($ord['created_at'])); ?></td>
                                <td class="fw-bold"><?= number_format($ord['revenue'], 0, ',', '.'); ?>đ</td>
                                <td class="text-muted"><?= number_format($ord['cost'], 0, ',', '.'); ?>đ</td>
                                <td class="text-danger">-<?= number_format($ord['discount_amount'], 0, ',', '.'); ?>đ</td>
                                <td class="fw-bold text-success">+<?= number_format($profit, 0, ',', '.'); ?>đ</td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Chưa có dữ liệu đơn hàng hoàn thành.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>