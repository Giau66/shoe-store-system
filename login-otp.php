<?php 
ob_start();
require_once 'config/db.php';
require_once 'config/otp-helper.php';

$error = '';
$success = '';

if (isset($_GET['reset'])) {
    unset($_SESSION['login_otp_step'], $_SESSION['login_otp_target']);
    header("Location: login-otp.php");
    exit();
}

// BƯỚC 1: KIỂM TRA TÀI KHOẢN CÓ TRONG CSDL MỚI CHO GỬI OTP
if (isset($_POST['send_login_otp'])) {
    $target = $conn->real_escape_string(trim($_POST['target_input']));

    if (empty($target)) {
        $error = "Vui lòng nhập Email hoặc Số điện thoại!";
    } else {
        $check = $conn->query("SELECT id, status FROM users WHERE (email = '$target' OR phone = '$target') LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $userRow = $check->fetch_assoc();
            if (isset($userRow['status']) && intval($userRow['status']) === 0) {
                $error = "🔒 Tài khoản của bạn đã bị KHÓA. Vui lòng liên hệ hỗ trợ!";
            } else {
                $otp_res = createAndSendOTP($conn, $target, 'login');
                if ($otp_res['success']) {
                    $_SESSION['login_otp_step']   = 2;
                    $_SESSION['login_otp_target'] = $target;
                    $success = "Mã xác thực đã được gửi thành công!";
                } else { 
                    $error = $otp_res['message']; 
                }
            }
        } else {
            $error = "Tài khoản chưa tồn tại trong hệ thống! Vui lòng kiểm tra lại.";
        }
    }
}

// BƯỚC 2: XÁC THỰC MÃ OTP VÀ ĐĂNG NHẬP
if (isset($_POST['verify_login_otp'])) {
    $target   = $_SESSION['login_otp_target'] ?? '';
    $otp_code = trim($_POST['otp_code']);

    if (empty($target) || empty($otp_code)) {
        $error = "Vui lòng nhập mã xác thực!";
    } else {
        if (verifyOTPCode($conn, $target, $otp_code, 'login')) {
            $sql = "SELECT * FROM users WHERE (email = '$target' OR phone = '$target') AND status = 1 LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $user = $res->fetch_assoc();
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];

                unset($_SESSION['login_otp_step'], $_SESSION['login_otp_target']);
                if (in_array($user['role'], ['admin', 'staff', 'employee'])) {
                    $_SESSION['admin_login_transition'] = true;
                }
                header("Location: " . (in_array($user['role'], ['admin', 'staff', 'employee']) ? 'admin/index.php' : 'index.php'));
                exit();
            }
        } else {
            $error = "Mã xác thực không chính xác hoặc đã hết hạn!";
        }
    }
}

$step = $_SESSION['login_otp_step'] ?? 1;

include_once 'includes/header.php'; 
?>

<!-- PREMIUM DARK/GOLD DESIGN -->
<style>
    body {
        background-color: #fcfbf7 !important;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }
    .auth-card {
        background-color: #ffffff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 10px 30px rgba(26, 29, 33, 0.05);
        border-radius: 12px;
    }
    .btn-gold {
        background-color: #c5a059;
        color: #ffffff;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-gold:hover {
        background-color: #b39150;
        color: #ffffff;
    }
    .form-control:focus {
        border-color: #c5a059;
        box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.25);
    }
    .text-gold {
        color: #c5a059 !important;
    }
    .text-dark-theme {
        color: #1a1d21 !important;
    }
</style>

<div class="container my-5" style="max-width: 450px;">
    <div class="card auth-card p-4">
        <h3 class="fw-bold text-center mb-1 text-uppercase text-dark-theme">Đăng Nhập Mã Xác Thực</h3>
        <p class="text-center text-muted small mb-4">Xác thực nhanh qua mã gửi tới Email / SĐT</p>

        <?php if ($error): ?><div class="alert alert-danger py-2 small fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i><?= $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success py-2 small fw-bold"><i class="fa-solid fa-circle-check me-1"></i><?= $success; ?></div><?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark-theme">Email hoặc Số điện thoại <span class="text-danger">*</span></label>
                    <input type="text" name="target_input" class="form-control" required autofocus>
                </div>
                <button type="submit" name="send_login_otp" class="btn btn-gold btn-lg w-100 fw-bold rounded-3 shadow-sm my-2">
                    <i class="fa-paper-plane fa-solid me-1"></i> GỬI MÃ XÁC THỰC
                </button>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="text-center mb-3">
                    <span class="text-muted small">Mã xác thực đăng nhập đã gửi tới:</span>
                    <strong class="d-block text-gold fs-6 mt-1"><?= htmlspecialchars($_SESSION['login_otp_target'] ?? ''); ?></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark-theme">Nhập 6 số mã xác thực <span class="text-danger">*</span></label>
                    <input type="text" name="otp_code" class="form-control text-center fw-bold fs-4" maxlength="6" required autofocus>
                </div>
                <button type="submit" name="verify_login_otp" class="btn btn-gold btn-lg w-100 fw-bold rounded-3 shadow-sm mb-2">
                    XÁC NHẬN & ĐĂNG NHẬP
                </button>
                <a href="login-otp.php?reset=1" class="btn btn-link w-100 text-muted small text-decoration-none text-center d-block">Nhập lại tài khoản khác</a>
            </form>
        <?php endif; ?>

        <div class="text-center mt-4 border-top pt-3" style="border-color: #e5e5e5 !important;">
            <a href="login.php" class="btn btn-link text-decoration-none fw-bold text-muted small"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại Đăng nhập mật khẩu</a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>