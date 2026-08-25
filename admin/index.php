<?php 
include_once 'includes/header.php'; 

// 0. TỰ ĐỘNG CẬP NHẬT NHÃN AUTO TOP HOT
if (function_exists('autoUpdateHotProducts')) {
    autoUpdateHotProducts($conn);
}

// 1. THỐNG KÊ TỔNG QUAN
$total_products  = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status = 1")->fetch_assoc()['total'] ?? 0;
$total_orders    = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'] ?? 0;
$pending_orders  = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$total_customers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")->fetch_assoc()['total'] ?? 0;

// Tổng doanh thu & doanh thu hôm nay
$revenue_res   = $conn->query("SELECT SUM(total_money) AS total FROM orders WHERE status = 'completed'");
$total_revenue = $revenue_res->fetch_assoc()['total'] ?? 0;

$today = date('Y-m-d');
$today_revenue_res = $conn->query("SELECT SUM(total_money) AS total FROM orders WHERE status = 'completed' AND DATE(created_at) = '$today'");
$today_revenue = $today_revenue_res->fetch_assoc()['total'] ?? 0;

// Doanh thu hôm qua để tính % tăng trưởng
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterday_rev = $conn->query("SELECT SUM(total_money) AS total FROM orders WHERE status = 'completed' AND DATE(created_at) = '$yesterday'")->fetch_assoc()['total'] ?? 0;

// Số đơn hoàn thành hôm nay
$today_orders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'] ?? 0;

// 2. LẤY 10 ĐƠN HÀNG MỚI NHẤT
$sql_recent = "SELECT o.*, 
               COALESCE(o.customer_name, u.fullname, 'Khách vãng lai') AS buyer_name,
               COALESCE(o.phone, u.phone, 'Chưa có SĐT') AS buyer_phone
               FROM orders o
               LEFT JOIN users u ON o.user_id = u.id
               ORDER BY o.id DESC LIMIT 10";
$recent_orders = $conn->query($sql_recent);

// 3. DOANH THU 7 NGÀY GẦN NHẤT
$chart_days = [];
$chart_daily_revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d_date  = date('Y-m-d', strtotime("-$i days"));
    $d_label = date('d/m', strtotime("-$i days"));
    $chart_days[] = $d_label;
    $d_rev = $conn->query("SELECT SUM(total_money) AS rev FROM orders WHERE status = 'completed' AND DATE(created_at) = '$d_date'")->fetch_assoc()['rev'] ?? 0;
    $chart_daily_revenue[] = floatval($d_rev);
}

// Tính % tăng trưởng doanh thu
$rev_growth = '';
if ($yesterday_rev > 0) {
    $pct = round((($today_revenue - $yesterday_rev) / $yesterday_rev) * 100, 1);
    $rev_growth = ($pct >= 0 ? '+' : '') . $pct . '%';
} else {
    $rev_growth = $today_revenue > 0 ? '+∞' : '0%';
}
?>

<!-- ═══════════════════════════════════════════════════════════
     DASHBOARD CONTENT
══════════════════════════════════════════════════════════════ -->

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Doanh Thu -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-purple">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon-wrap purple">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <span class="kpi-badge-success">
                    <i class="fa-solid fa-arrow-trend-up"></i> <?= $rev_growth ?>
                </span>
            </div>
            <div class="kpi-label">Doanh Thu Hôm Nay</div>
            <div class="kpi-value"><?= number_format($today_revenue / 1000000, 1) ?>M</div>
            <div class="kpi-sub">
                <span>Tổng: <strong style="color:#a78bfa;"><?= number_format($total_revenue, 0, ',', '.') ?>đ</strong></span>
            </div>
        </div>
    </div>

    <!-- Tổng Đơn -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-blue">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon-wrap blue">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <?php if ($pending_orders > 0): ?>
                <span class="kpi-badge-warning">
                    <i class="fa-solid fa-clock"></i> <?= $pending_orders ?> chờ
                </span>
                <?php else: ?>
                <span class="kpi-badge-success"><i class="fa-solid fa-check"></i> Ổn</span>
                <?php endif; ?>
            </div>
            <div class="kpi-label">Tổng Đơn Hàng</div>
            <div class="kpi-value"><?= number_format($total_orders) ?></div>
            <div class="kpi-sub">Hôm nay: +<?= $today_orders ?> đơn mới</div>
        </div>
    </div>

    <!-- Sản Phẩm -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-green">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon-wrap green">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <span class="kpi-badge-success"><i class="fa-solid fa-circle-check"></i> Online</span>
            </div>
            <div class="kpi-label">Sản Phẩm Đang Bán</div>
            <div class="kpi-value"><?= number_format($total_products) ?></div>
            <div class="kpi-sub">
                <a href="inventory.php" style="color:#34d399;text-decoration:none;">
                    Kiểm tra kho <i class="fa-solid fa-arrow-right ms-1" style="font-size:10px;"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Khách Hàng -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-pink">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="kpi-icon-wrap pink">
                    <i class="fa-solid fa-users"></i>
                </div>
                <span class="kpi-badge-success"><i class="fa-solid fa-user-plus"></i> Active</span>
            </div>
            <div class="kpi-label">Tổng Khách Hàng</div>
            <div class="kpi-value"><?= number_format($total_customers) ?></div>
            <div class="kpi-sub">Thành viên đăng ký</div>
        </div>
    </div>

</div>

<!-- ── Biểu Đồ Doanh Thu ──────────────────────────────────── -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="section-heading mb-1">
                        <i class="fa-solid fa-chart-line"></i>
                        Doanh Thu 7 Ngày Gần Nhất
                    </h6>
                    <small style="color:var(--text-muted);font-size:11px;">Đơn hàng đã hoàn thành</small>
                </div>
                <?php if ($user_role === 'admin'): ?>
                <a href="statistics.php" class="btn-dark-outline">
                    Xem chi tiết báo cáo <i class="fa-solid fa-angle-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <div style="height: 320px; position: relative;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Bảng Đơn Hàng Mới Nhất ─────────────────────────────── -->
<div class="glass-card mb-5">
    <div class="d-flex justify-content-between align-items-center p-4 pb-3">
        <div>
            <h6 class="section-heading mb-1">
                <i class="fa-solid fa-clock-rotate-left"></i>
                10 Đơn Hàng Mới Nhất
            </h6>
            <small style="color:var(--text-muted);font-size:11px;">Cập nhật theo thời gian thực</small>
        </div>
        <a href="orders.php" class="btn-dark-outline">
            Tất cả đơn <i class="fa-solid fa-angle-right"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="dark-table">
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Khách Hàng</th>
                    <th>Điện Thoại</th>
                    <th>Tổng Tiền</th>
                    <th>Thanh Toán</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Đặt</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                    <?php while($ord = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="order-detail.php?id=<?= $ord['id'] ?>" 
                                   style="color:#6d28d9;text-decoration:none;font-weight:800;font-size:0.875rem;">
                                    #<?= htmlspecialchars($ord['order_code'] ?? 'HD-'.sprintf('%05d', $ord['id'])) ?>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6d28d9,#ec4899);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:800;color:white;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                                        <?= mb_strtoupper(mb_substr($ord['buyer_name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                                    </div>
                                    <span style="font-weight:700;font-size:0.875rem;color:#000000;"><?= htmlspecialchars($ord['buyer_name']) ?></span>
                                </div>
                            </td>
                            <td style="color:#334155;font-weight:600;"><?= htmlspecialchars($ord['buyer_phone']) ?></td>
                            <td>
                                <span style="font-weight:800;color:#047857;"><?= number_format($ord['total_money'], 0, ',', '.') ?>đ</span>
                            </td>
                            <td>
                                <span style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:6px;padding:3px 9px;font-size:11.5px;color:#0f172a;font-weight:700;">
                                    <?= htmlspecialchars($ord['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $st = $ord['status'];
                                $badge_map = [
                                    'pending'   => ['Chờ xác nhận', 'status-pending'],
                                    'confirmed' => ['Đã xác nhận',  'status-confirmed'],
                                    'shipping'  => ['Đang giao',    'status-shipping'],
                                    'completed' => ['Hoàn thành',   'status-completed'],
                                ];
                                $b = $badge_map[$st] ?? ['Đã hủy/Hoàn', 'status-cancelled'];
                                echo "<span class=\"status-badge {$b[1]}\">{$b[0]}</span>";
                                ?>
                            </td>
                            <td style="color:#64748b;font-size:0.82rem;font-weight:600;"><?= date('d/m H:i', strtotime($ord['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5" style="color:var(--text-muted);">
                            <i class="fa-solid fa-box-open fs-2 mb-2 d-block" style="opacity:0.3;"></i>
                            Chưa có đơn hàng nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Scripts ────────────────────────────────────────────── -->
<script>
const chartLabels = <?= json_encode($chart_days) ?>;
const chartData   = <?= json_encode($chart_daily_revenue) ?>;

const canvas = document.getElementById('revenueChart');
const ctx = canvas.getContext('2d');

function getChartColors(theme) {
    const isDark = (theme === 'dark');
    return {
        grid: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)',
        ticks: isDark ? '#94a3b8' : '#64748b',
        tooltipBg: isDark ? 'rgba(15, 17, 28, 0.95)' : 'rgba(255, 255, 255, 0.95)',
        tooltipTitle: isDark ? '#a78bfa' : '#6d28d9',
        tooltipBody: isDark ? '#f1f5f9' : '#0f172a',
        tooltipBorder: isDark ? 'rgba(124, 58, 237, 0.4)' : 'rgba(109, 40, 217, 0.2)'
    };
}

const gradientFill = ctx.createLinearGradient(0, 0, 0, 280);
gradientFill.addColorStop(0,   'rgba(124, 58, 237, 0.35)');
gradientFill.addColorStop(0.6, 'rgba(124, 58, 237, 0.08)');
gradientFill.addColorStop(1,   'rgba(124, 58, 237, 0.00)');

let currentThemeMode = document.documentElement.getAttribute('data-theme') || 'light';
let chartColors = getChartColors(currentThemeMode);

const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: chartData,
            borderColor: '#8b5cf6',
            backgroundColor: gradientFill,
            fill: true,
            tension: 0.45,
            borderWidth: 2.5,
            pointBackgroundColor: '#7c3aed',
            pointBorderColor: '#1a1d2e',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 900, easing: 'easeInOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: chartColors.tooltipBg,
                borderColor: chartColors.tooltipBorder,
                borderWidth: 1,
                titleColor: chartColors.tooltipTitle,
                bodyColor: chartColors.tooltipBody,
                padding: 12,
                cornerRadius: 10,
                callbacks: {
                    label: function(ctx) {
                        return ' ' + new Intl.NumberFormat('vi-VN').format(ctx.raw || 0) + 'đ';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { color: chartColors.grid, drawBorder: false },
                ticks: { color: chartColors.ticks, font: { size: 11 } },
                border: { display: false }
            },
            y: {
                beginAtZero: true,
                grid: { color: chartColors.grid, drawBorder: false },
                border: { display: false },
                ticks: {
                    color: chartColors.ticks,
                    font: { size: 11 },
                    callback: function(value) {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                        if (value >= 1000)    return (value / 1000).toFixed(0) + 'K';
                        return value;
                    }
                }
            }
        }
    }
});

// Update chart dynamically when theme changes
window.addEventListener('adminThemeChanged', function(e) {
    const newColors = getChartColors(e.detail.theme);
    revenueChart.options.scales.x.grid.color = newColors.grid;
    revenueChart.options.scales.x.ticks.color = newColors.ticks;
    revenueChart.options.scales.y.grid.color = newColors.grid;
    revenueChart.options.scales.y.ticks.color = newColors.ticks;
    revenueChart.options.plugins.tooltip.backgroundColor = newColors.tooltipBg;
    revenueChart.options.plugins.tooltip.titleColor = newColors.tooltipTitle;
    revenueChart.options.plugins.tooltip.bodyColor = newColors.tooltipBody;
    revenueChart.options.plugins.tooltip.borderColor = newColors.tooltipBorder;
    revenueChart.update();
});
</script>

        </div><!-- /.admin-content -->
    </div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->
</body>
</html>