<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$order_code = $conn->real_escape_string($_GET['code'] ?? $_GET['order_code'] ?? '');

if (!$order_code) {
    header('Location: index.php');
    exit;
}

$res_order = $conn->query("SELECT * FROM orders WHERE order_code = '$order_code' AND user_id = $user_id LIMIT 1");
$order = $res_order ? $res_order->fetch_assoc() : null;

if (!$order) {
    header('Location: index.php');
    exit;
}

// Nếu đơn hàng đã được thanh toán rồi, chuyển hướng thẳng sang trang thành công
if ($order['payment_status'] === 'paid') {
    header("Location: order-success.php?code=" . urlencode($order_code));
    exit;
}

// Lấy thông tin tài khoản ngân hàng từ site_settings
$settings = [];
$res_s = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($res_s) {
    while ($row = $res_s->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$bank_id = !empty($settings['bank_id']) ? $settings['bank_id'] : 'ACB';
$bank_account = !empty($settings['bank_account']) ? $settings['bank_account'] : '0123456789';
$bank_name = !empty($settings['bank_name']) ? $settings['bank_name'] : 'SHOP OWNER';

$amount = floatval($order['total_money']);
$addInfo = $order_code;

// Link VietQR chuẩn
$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$bank_account}-compact2.png?amount={$amount}&addInfo={$addInfo}&accountName=" . urlencode($bank_name);

$page_title = "Xác thực thanh toán QR Code";
require_once __DIR__ . '/includes/header.php';
?>

<style>
.qr-payment-card {
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.06);
    overflow: hidden;
}
.qr-header-gradient {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 24px;
    color: #ffffff;
    text-align: center;
}
.qr-radar-pulse {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #f59e0b;
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
    animation: qr-pulse-anim 1.8s infinite;
    display: inline-block;
}
@keyframes qr-pulse-anim {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    70% { transform: scale(1.2); box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
.qr-status-box {
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 16px;
    padding: 16px;
    transition: all 0.3s ease;
}
.qr-status-box.success {
    background: #f0fdf4;
    border-color: #86efac;
}
.copy-badge-btn {
    cursor: pointer;
    transition: all 0.2s ease;
}
.copy-badge-btn:hover {
    transform: scale(1.05);
}
.qr-image-wrapper {
    position: relative;
    display: inline-block;
    padding: 12px;
    background: #ffffff;
    border: 2px solid #f1f5f9;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
}
.qr-image-wrapper img {
    border-radius: 12px;
    display: block;
    max-width: 270px;
    height: auto;
}
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="qr-payment-card">
                <!-- Header -->
                <div class="qr-header-gradient">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-2 text-uppercase">
                        <i class="fa-solid fa-bolt me-1"></i> VietQR Chuyển Khoản Tự Động
                    </span>
                    <h4 class="fw-bold mb-1">Thanh Toán Đơn Hàng #<?= htmlspecialchars($order_code) ?></h4>
                    <p class="text-white-50 small mb-0">Quét mã bằng ứng dụng Ngân hàng (App Banking) bất kỳ</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <!-- Amount & Countdown -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <div>
                            <span class="text-muted small fw-bold d-block text-uppercase">Số tiền thanh toán:</span>
                            <span class="text-danger fw-bolder fs-2"><?= number_format($amount, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small fw-bold d-block text-uppercase">Hết hạn sau:</span>
                            <span class="badge bg-danger fs-6 fw-bold px-3 py-2 rounded-pill" id="qrCountdownTimer">14:59</span>
                        </div>
                    </div>

                    <!-- QR Code Display -->
                    <div class="text-center mb-4">
                        <div class="qr-image-wrapper">
                            <img src="<?= htmlspecialchars($qr_url) ?>" alt="VietQR Payment Code" class="img-fluid" id="qrCodeImg">
                        </div>
                        <div class="mt-2 text-muted small">
                            <i class="fa-solid fa-camera me-1"></i> Mở App Ngân hàng ➔ Chọn <strong>Quét mã QR</strong>
                        </div>
                    </div>

                    <!-- Live Real-time Status Box -->
                    <div class="qr-status-box mb-4" id="qrStatusBox">
                        <div class="d-flex align-items-center gap-3">
                            <div class="qr-radar-pulse" id="qrStatusDot"></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1 text-dark" id="qrStatusTitle">Đang chờ nhận tiền qua chuyển khoản...</h6>
                                <p class="text-muted small mb-0" id="qrStatusDesc">Hệ thống đang tự động kiểm tra giao dịch mỗi 3 giây. Đơn hàng sẽ tự động hoàn tất ngay khi tiền về.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer Details -->
                    <div class="bg-light p-3 p-md-4 rounded-4 mb-4 border">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <span class="fw-bold text-dark text-uppercase small"><i class="fa-solid fa-building-columns me-1 text-primary"></i>Thông tin chuyển khoản</span>
                            <span class="badge bg-primary-subtle text-primary fw-bold">Chính xác 100%</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Ngân hàng thụ hưởng:</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($bank_id) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Chủ tài khoản:</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($bank_name) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Số tài khoản:</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold fs-5 text-primary"><?= htmlspecialchars($bank_account) ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill copy-badge-btn" onclick="copyText('<?= htmlspecialchars($bank_account) ?>', this)" title="Sao chép số tài khoản">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Nội dung chuyển khoản:</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-danger fs-6"><?= htmlspecialchars($addInfo) ?></span>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill copy-badge-btn" onclick="copyText('<?= htmlspecialchars($addInfo) ?>', this)" title="Sao chép nội dung chuyển khoản">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 small py-2 mb-4">
                        <i class="fa-solid fa-circle-info me-1"></i> <strong>Lưu ý:</strong> Vui lòng ghi chính xác nội dung <strong><?= htmlspecialchars($addInfo) ?></strong> để hệ thống tự động xác nhận trong 5-10 giây.
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-warning py-3 fw-bold rounded-pill text-dark shadow-sm" id="btnManualCheck" onclick="checkPaymentStatus(true)">
                            <i class="fa-solid fa-rotate me-2"></i>Tôi Đã Chuyển Tiền - Kiểm Tra Ngay
                        </button>
                    </div>

                    <!-- Pending Order Safe Notice & Exit Navigation -->
                    <div class="p-3 bg-light rounded-4 border text-center">
                        <div class="small text-muted mb-2">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Đơn hàng đã được lưu an toàn ở trạng thái <strong class="text-dark">Chờ xác nhận</strong>.
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                            <a href="my-orders.php?tab=1" class="btn btn-outline-dark btn-sm rounded-pill fw-bold px-3 py-2">
                                <i class="fa-solid fa-box-archive me-1"></i> Đơn Hàng Của Tôi (Thanh Toán Sau)
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold px-3 py-2">
                                <i class="fa-solid fa-house me-1"></i> Quay Lại Trang Chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const orderCode = '<?= addslashes($order_code) ?>';
    let isPolling = true;
    let pollInterval = null;

    // 1. Đồng hồ đếm ngược 15 phút
    let timeLeft = 15 * 60;
    const timerEl = document.getElementById('qrCountdownTimer');
    let timerInterval = setInterval(updateTimer, 1000);

    function updateTimer() {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = 'Hết phiên';
            timerEl.className = 'badge bg-warning text-dark fs-6 fw-bold px-3 py-2 rounded-pill';
            document.getElementById('qrStatusTitle').textContent = 'Phiên quét mã tạm hết thời gian';
            document.getElementById('qrStatusDesc').innerHTML = 'Đơn hàng <strong>#' + orderCode + '</strong> vẫn đang được lưu an toàn ở trạng thái <strong>Chờ xác nhận</strong>. Quý khách có thể bấm <a href="javascript:void(0)" onclick="restartQRSession()" class="fw-bold text-dark text-decoration-underline">Tạo lại mã QR</a> hoặc vào <strong>Đơn hàng của tôi</strong> để thanh toán lại.';
            isPolling = false;
        } else {
            const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const secs = String(timeLeft % 60).padStart(2, '0');
            timerEl.textContent = `${mins}:${secs}`;
        }
    }

    window.restartQRSession = function() {
        timeLeft = 15 * 60;
        isPolling = true;
        timerEl.className = 'badge bg-danger fs-6 fw-bold px-3 py-2 rounded-pill';
        document.getElementById('qrStatusTitle').textContent = 'Đang chờ nhận tiền qua chuyển khoản...';
        document.getElementById('qrStatusDesc').textContent = 'Hệ thống đang tự động kiểm tra giao dịch mỗi 3 giây. Đơn hàng sẽ tự động hoàn tất ngay khi tiền về.';
        clearInterval(timerInterval);
        timerInterval = setInterval(updateTimer, 1000);
        checkPaymentStatus(false);
    };

    // 2. Hàm kiểm tra trạng thái thanh toán tự động qua API
    window.checkPaymentStatus = function(isManual = false) {
        const btn = document.getElementById('btnManualCheck');
        if (isManual && btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Đang kiểm tra giao dịch...';
        }

        fetch(`api/check-payment-status.php?order_code=${encodeURIComponent(orderCode)}&t=${Date.now()}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.paid) {
                    // 🎉 Thanh toán thành công!
                    isPolling = false;
                    clearInterval(pollInterval);
                    clearInterval(timerInterval);

                    const box = document.getElementById('qrStatusBox');
                    box.className = 'qr-status-box success mb-4';
                    
                    const dot = document.getElementById('qrStatusDot');
                    dot.style.background = '#22c55e';
                    dot.style.boxShadow = '0 0 10px #22c55e';
                    dot.className = 'fa-solid fa-circle-check text-success fs-4';

                    document.getElementById('qrStatusTitle').innerHTML = '<span class="text-success fw-bold">✓ ĐÃ NHẬN TIỀN THÀNH CÔNG!</span>';
                    document.getElementById('qrStatusDesc').textContent = 'Hệ thống đã nhận được tiền từ ngân hàng. Đơn hàng của bạn đang ở trạng thái Chờ xác nhận từ cửa hàng...';

                    if (btn) {
                        btn.className = 'btn btn-success py-3 fw-bold rounded-pill text-white shadow';
                        btn.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Thanh Toán Hoàn Tất!';
                    }

                    setTimeout(() => {
                        window.location.href = data.redirect_url || `order-success.php?code=${encodeURIComponent(orderCode)}`;
                    }, 1600);
                } else if (isManual) {
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-rotate me-2"></i>Tôi Đã Chuyển Tiền - Kiểm Tra Ngay';
                        alert('Hệ thống chưa ghi nhận tiền vào tài khoản. Quý khách vui lòng chờ vài giây để ngân hàng xử lý hoặc kiểm tra lại nội dung chuyển khoản nhé!');
                    }, 600);
                }
            })
            .catch(err => {
                console.error('Lỗi kiểm tra thanh toán:', err);
                if (isManual && btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-rotate me-2"></i>Tôi Đã Chuyển Tiền - Kiểm Tra Ngay';
                }
            });
    };

    // 3. Tự động kiểm tra mỗi 3 giây
    pollInterval = setInterval(() => {
        if (isPolling) {
            checkPaymentStatus(false);
        }
    }, 3000);

    // 4. Hàm sao chép nhanh
    window.copyText = function(text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
        }
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
        }, 1500);
    };

    // 5. Hàm mô phỏng ngân hàng chuyển tiền (dành cho demo / test)
    window.simulateBankTransfer = function() {
        if (!confirm('Bạn có muốn mô phỏng nhận tiền thành công từ ngân hàng cho đơn hàng #' + orderCode + ' này?')) {
            return;
        }
        fetch('api/payment-webhook.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_code: orderCode,
                simulate: true,
                amount: <?= floatval($amount) ?>
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                checkPaymentStatus(false);
            } else {
                alert(data.message || 'Lỗi khi mô phỏng thanh toán.');
            }
        })
        .catch(err => alert('Lỗi kết nối: ' + err));
    };
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>