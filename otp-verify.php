<?php 
ob_start();
include_once 'includes/header.php'; 

if (!isset($_SESSION['otp_target'])) {
    header('Location: login.php');
    exit();
}
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
    .letter-spacing {
        letter-spacing: 0.5rem;
    }
</style>

<div class="container my-5" style="max-width: 450px;">
    <div class="card auth-card p-4 text-center">
        <h4 class="fw-bold mb-2 text-dark-theme">XÁC MINH MÃ</h4>
        <p class="text-muted small">Mã xác minh đã được gửi đến:<br><strong class="text-gold"><?= htmlspecialchars($_SESSION['otp_target']); ?></strong></p>

        <?php if (isset($_SESSION['otp_error'])): ?>
            <div class="alert alert-danger py-2 small"><i class="fa-solid fa-circle-exclamation me-1"></i><?= $_SESSION['otp_error']; unset($_SESSION['otp_error']); ?></div>
        <?php endif; ?>

        <form action="login-process.php" method="POST">
            <input type="hidden" name="action" value="verify_otp">
            <div class="mb-4">
                <input type="text" name="otp_code" class="form-control form-control-lg text-center fs-3 fw-bold letter-spacing" placeholder="------" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn btn-gold btn-lg w-100 fw-bold py-2 mb-3">XÁC NHẬN MÃ</button>
        </form>

        <a href="login.php" class="text-muted small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại Đăng nhập</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>