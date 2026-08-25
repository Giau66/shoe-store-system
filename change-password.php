<?php 
ob_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Mật khẩu hiện tại không chính xác!";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không trùng khớp!";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $new_hash, $user_id);
        if ($stmt->execute()) { $stmt->close();
            $success = "Đổi mật khẩu thành công!";
        } else { $stmt->close();
            $error = 'Lỗi khi cập nhật mật khẩu!';
        }
    }
}

include_once 'includes/header.php';
?>

<style>
    body {
        background-color: #fcfbf7 !important;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }
    .auth-card {
        background-color: #ffffff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 10px 30px rgba(26, 29, 33, 0.05);
        border-radius: 16px;
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
    .text-gold {
        color: #c5a059 !important;
    }

    /* Password eye wrap (Đồng bộ với login.php / register) */
    .eye-wrap {
        position: relative;
    }
    .eye-wrap .form-control {
        padding-right: 2.75rem;
        border-radius: 12px;
        min-height: 46px;
    }
    .eye-btn {
        position: absolute;
        right: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        padding: 0;
        color: #94a3b8;
        cursor: pointer;
        font-size: 0.95rem;
        line-height: 1;
        transition: color 0.2s ease, transform 0.2s ease;
        z-index: 5;
    }
    .eye-btn:hover {
        color: #4338ca;
        transform: translateY(-50%) scale(1.1);
    }
    [data-theme="dark"] .eye-btn:hover {
        color: #a78bfa;
    }
</style>

<div class="container my-5" style="max-width: 500px;">
    <div class="card auth-card p-4">
        <h4 class="fw-bold text-center mb-4"><i class="fa-solid fa-key me-2 text-gold"></i> ĐỔI MẬT KHẨU</h4>

        <?php if ($error): ?><div class="alert alert-danger py-2 small fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i><?= $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success py-2 small fw-bold"><i class="fa-solid fa-circle-check me-1"></i><?= $success; ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                <div class="eye-wrap">
                    <input type="password" name="current_password" id="currentPw" class="form-control" placeholder="Nhập mật khẩu hiện tại..." required autofocus>
                    <button type="button" class="eye-btn" onclick="togglePw('currentPw', this)" title="Hiện/ẩn mật khẩu">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Mật khẩu mới <span class="text-danger">*</span></label>
                <div class="eye-wrap">
                    <input type="password" name="new_password" id="newPw" class="form-control" placeholder="Tối thiểu 6 ký tự..." minlength="6" required>
                    <button type="button" class="eye-btn" onclick="togglePw('newPw', this)" title="Hiện/ẩn mật khẩu">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small">Xác nhận Mật khẩu mới <span class="text-danger">*</span></label>
                <div class="eye-wrap">
                    <input type="password" name="confirm_password" id="confirmPw" class="form-control" placeholder="Nhập lại mật khẩu mới..." minlength="6" required>
                    <button type="button" class="eye-btn" onclick="togglePw('confirmPw', this)" title="Hiện/ẩn mật khẩu">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="change_password" class="btn btn-gold btn-lg w-100 fw-bold rounded-3 shadow-sm mb-3">
                <i class="fa-solid fa-lock me-2"></i> CẬP NHẬT MẬT KHẨU
            </button>
        </form>
        
        <div class="text-center">
            <a href="profile.php" class="text-muted small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại trang hồ sơ</a>
        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.innerHTML = inp.type === 'password'
        ? '<i class="fa-solid fa-eye"></i>'
        : '<i class="fa-solid fa-eye-slash"></i>';
}
</script>

<?php include_once 'includes/footer.php'; ?>
