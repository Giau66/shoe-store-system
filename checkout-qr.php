<?php
ob_start();
session_start();
require_once 'config/db.php';

$code  = $_GET['code'] ?? '';
$total = intval($_GET['total'] ?? 0);

// Lấy thông tin tài khoản ngân hàng từ site_settings
$settings = [];
$res_s = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = 'payment' OR setting_key LIKE 'bank_%'");
if ($res_s) {
    while ($row = $res_s->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$bank_id = !empty($settings['bank_id']) ? $settings['bank_id'] : 'ACB';
$bank_account = !empty($settings['bank_account']) ? $settings['bank_account'] : '0123456789';
$bank_name = !empty($settings['bank_name']) ? $settings['bank_name'] : 'SHOP OWNER';

// Link VietQR API Động
$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$bank_account}-compact2.png?amount={$total}&addInfo={$code}&accountName=" . urlencode($bank_name);

include_once 'includes/header.php';
?>

<div class="container my-5 text-center" style="max-width: 500px;">
    <div class="card border-0 shadow-lg rounded-4 p-4 bg-white">
        <h4 class="fw-bold text-success mb-2"><i class="fa-solid fa-circle-check me-2"></i>Đặt Hàng Thành Công!</h4>
        <p class="text-muted small mb-3">Vui lòng mở App Ngân hàng và quét mã VietQR bên dưới để hoàn tất thanh toán cho đơn hàng <b>#<?= htmlspecialchars($code); ?></b></p>

        <!-- MÃ QR NGÂN HÀNG THẬT TỰ ĐỘNG KHỔ 100% -->
        <div class="p-3 bg-light rounded-4 border my-3">
            <img src="<?= $qr_url; ?>" class="img-fluid rounded-3 shadow-sm" alt="Mã VietQR Thanh Toán" style="max-width: 280px;">
        </div>

        <div class="bg-light p-3 rounded-3 mb-3 border text-start">
            <h6 class="fw-bold border-bottom pb-2 mb-2 text-uppercase small text-muted"><i class="fa-solid fa-building-columns me-1"></i>Thông tin chuyển khoản</h6>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Ngân hàng:</span>
                <span class="fw-bold text-dark small"><?= htmlspecialchars($bank_id) ?> (Ngân hàng Á Châu)</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Chủ tài khoản:</span>
                <span class="fw-bold text-dark small"><?= htmlspecialchars($bank_name) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Số tài khoản:</span>
                <div>
                    <span class="fw-bold text-primary small me-1"><?= htmlspecialchars($bank_account) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($bank_account) ?>'); alert('Đã sao chép số tài khoản!');">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Nội dung chuyển khoản:</span>
                <div>
                    <span class="fw-bold text-danger small me-1"><?= htmlspecialchars($code) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($code) ?>'); alert('Đã sao chép nội dung chuyển khoản!');">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>

        <a href="my-orders.php" class="btn btn-dark btn-lg w-100 fw-bold rounded-pill shadow-sm">XEM TRẠNG THÁI ĐƠN HÀNG</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>