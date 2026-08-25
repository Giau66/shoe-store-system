<?php 
ob_start();
require_once 'config/db.php';

// Chỉ cho phép truy cập nếu có session đăng ký từ Google
if (!isset($_SESSION['google_signup'])) {
    header("Location: login.php");
    exit();
}

$google_data = $_SESSION['google_signup'];
$error = '';

if (isset($_POST['complete_profile'])) {
    $fullname = $conn->real_escape_string(trim($_POST['fullname']));
    $phone    = $conn->real_escape_string(trim($_POST['phone']));
    $birthday = $conn->real_escape_string(trim($_POST['birthday']));
    
    $google_id = $conn->real_escape_string($google_data['google_id']);
    $email     = $conn->real_escape_string($google_data['email']);
    $avatar    = $conn->real_escape_string($google_data['avatar']);

    if (empty($fullname) || empty($phone) || empty($birthday)) {
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc!";
    } elseif (!preg_match('/^[0-9]{9,11}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ!";
    } else {
        // Kiểm tra tính hợp lệ của ngày sinh
        $bDate = DateTime::createFromFormat('Y-m-d', $birthday);
        $today = new DateTime();
        if (!$bDate || $bDate->format('Y-m-d') !== $birthday) {
            $error = "Định dạng ngày sinh không hợp lệ!";
        } elseif ($bDate > $today) {
            $error = "Ngày sinh không thể là ngày trong tương lai!";
        } elseif ($bDate < new DateTime('1920-01-01')) {
            $error = "Năm sinh không hợp lệ (phải từ năm 1920 trở lại đây)!";
        }

        if (empty($error)) {
            // Check phone exists
            $check_phone = $conn->query("SELECT id FROM users WHERE phone = '$phone'");
            if ($check_phone && $check_phone->num_rows > 0) {
                $error = "Số điện thoại này đã được sử dụng ở tài khoản khác!";
            } else {
                // Insert user
                $sql = "INSERT INTO users (fullname, email, phone, birthday, google_id, avatar, role, auth_provider, status, is_email_verified) 
                        VALUES ('$fullname', '$email', '$phone', '$birthday', '$google_id', '$avatar', 'customer', 'google', 1, 1)";
                        
                if ($conn->query($sql)) {
                    $user_id = $conn->insert_id;
                    
                    // Set session
                    unset($_SESSION['google_signup']);
                    $_SESSION['user_id']   = $user_id;
                    $_SESSION['user_name'] = $fullname;
                    $_SESSION['user_role'] = 'customer';
                    
                    header("Location: index.php?login=google_success");
                    exit();
                } else {
                    $error = "Đã xảy ra lỗi: " . $conn->error;
                }
            }
        }
    }
}

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
    .avatar-wrapper {
        width: 100px;
        height: 100px;
        margin: 0 auto 20px;
        border-radius: 50%;
        padding: 4px;
        background: linear-gradient(135deg, #c5a059, #1a1d21);
    }
    .avatar-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
    }
</style>

<div class="container my-5" style="max-width: 500px;">
    <div class="card auth-card p-4">
        <div class="text-center">
            <h3 class="fw-bold mb-1 text-uppercase text-dark-theme">Hoàn tất hồ sơ</h3>
            <p class="text-muted small mb-4">Xin chào, hãy bổ sung thông tin để hoàn tất đăng ký!</p>
            
            <div class="avatar-wrapper">
                <img src="<?= htmlspecialchars($google_data['avatar'] ?: 'assets/images/default-avatar.png') ?>" alt="Avatar">
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-danger py-2 small fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i><?= $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-dark-theme">Email (Từ Google)</label>
                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($google_data['email']) ?>" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold small text-dark-theme">Họ tên đầy đủ <span class="text-danger">*</span></label>
                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($google_data['fullname']) ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-dark-theme">Số điện thoại <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-bold small text-dark-theme d-flex justify-content-between">
                    <span>Ngày sinh <span class="text-danger">*</span></span>
                    <span class="text-muted fw-normal" style="font-size: 11px;">(Từ năm 1920 đến nay)</span>
                </label>
                <input type="date" name="birthday" class="form-control" min="1920-01-01" max="<?= date('Y-m-d') ?>" required>
            </div>

            <button type="submit" name="complete_profile" class="btn btn-gold btn-lg w-100 fw-bold rounded-3 shadow-sm mb-3">
                HOÀN TẤT ĐĂNG KÝ
            </button>
            
            <div class="text-center">
                <a href="logout.php" class="text-muted small text-decoration-none">Hủy bỏ và quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
