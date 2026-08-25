<?php 
ob_start();
require_once 'config/db.php';
require_once 'config/otp-helper.php';

if (file_exists('config/google-config.php')) {
    require_once 'config/google-config.php';
} else {
    $google_auth_url = '#';
}

$error        = '';
$success      = '';
$active_panel = isset($_GET['tab']) ? $_GET['tab'] : 'login';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'google_failed') {
        $error = $_SESSION['google_oauth_error'] ?? "Đăng nhập bằng Google thất bại hoặc bị hủy!";
        unset($_SESSION['google_oauth_error']);
    } elseif ($_GET['error'] === 'employee_resigned') {
        $error = "⛔ Tài khoản nhân viên này đã NGHỈ VIỆC và không còn quyền truy cập hệ thống!";
    } elseif ($_GET['error'] === 'account_locked') {
        $error = "🔒 Tài khoản của bạn đã bị KHÓA hoặc ngừng hoạt động!";
    }
}

// ── 1. ĐĂNG NHẬP ─────────────────────────────────────────
if (isset($_POST['login_password'])) {
    $active_panel = 'login';
    $account  = $conn->real_escape_string(trim($_POST['account']));
    $password = $_POST['password'];
    if (empty($account) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ Email/SĐT và Mật khẩu!";
    } else {
        $res = $conn->query("SELECT * FROM users WHERE (email='$account' OR phone='$account') LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            // 1. Kiểm tra trạng thái tài khoản người dùng
            if (isset($user['status']) && intval($user['status']) === 0) {
                $error = "🔒 Tài khoản đã bị KHÓA hoặc ngừng hoạt động. Vui lòng liên hệ hỗ trợ!";
            } else {
                // 2. Kiểm tra nếu là nhân viên: đã nghỉ việc thì chặn không cho đăng nhập
                $is_employee_resigned = false;
                if (in_array($user['role'], ['staff', 'employee'])) {
                    $chk_emp = $conn->query("SELECT status, fullname FROM employees WHERE user_id = " . intval($user['id']) . " LIMIT 1");
                    if ($chk_emp && $row_e = $chk_emp->fetch_assoc()) {
                        if (intval($row_e['status']) === 0) {
                            $is_employee_resigned = true;
                        }
                    }
                }

                if ($is_employee_resigned) {
                    $error = "⛔ Tài khoản nhân viên này đã NGHỈ VIỆC và không còn quyền truy cập hệ thống!";
                } elseif ($user['password'] && password_verify($password, $user['password'])) {
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['fullname'];
                    $_SESSION['user_role'] = $user['role'];
                    $uid = intval($user['id']);
                    @$conn->query("CREATE TABLE IF NOT EXISTS `cart_items`(`id` INT AUTO_INCREMENT PRIMARY KEY,`user_id` INT NOT NULL,`product_id` INT NOT NULL,`variant_id` INT NOT NULL,`quantity` INT NOT NULL DEFAULT 1,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY `user_prod_var`(`user_id`,`product_id`,`variant_id`))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    $cart_res = $conn->query("SELECT c.id,c.variant_id,c.quantity,p.id as product_id,p.name,p.main_image,p.price,p.discount_percent,v.size,v.color FROM cart_items c JOIN products p ON c.product_id=p.id JOIN product_variants v ON c.variant_id=v.id WHERE c.user_id=$uid");
                    $_SESSION['cart'] = [];
                    if ($cart_res) {
                        $raw_cart_items = [];
                        $pids = [];
                        while ($item = $cart_res->fetch_assoc()) {
                            $raw_cart_items[] = $item;
                            $pids[] = intval($item['product_id']);
                        }
                        $sale_map = get_active_sale_events_map($conn, $pids);
                        foreach ($raw_cart_items as $item) {
                            $pid = intval($item['product_id']);
                            if (isset($sale_map[$pid]) && $sale_map[$pid]['has_sale']) {
                                $price = floatval($sale_map[$pid]['sale_price']);
                            } else {
                                $price = floatval($item['price']);
                            }
                            $_SESSION['cart'][] = ['product_id'=>$item['product_id'],'variant_id'=>$item['variant_id'],'name'=>$item['name'],'image'=>$item['main_image'],'size'=>$item['size'],'color'=>$item['color'],'price'=>$price,'quantity'=>$item['quantity']];
                        }
                    }
                    if (in_array($user['role'], ['admin', 'staff', 'employee'])) {
                        $_SESSION['admin_login_transition'] = true;
                    }
                    header("Location: " . (in_array($user['role'], ['admin', 'staff', 'employee']) ? 'admin/index.php' : 'index.php'));
                    exit();
                } else { $error = "Mật khẩu không chính xác!"; }
            }
        } else { $error = "Tài khoản không tồn tại!"; }
    }
}

// ── 2. QUÊN MẬT KHẨU ─────────────────────────────────────
if (isset($_POST['send_forgot_otp'])) {
    $active_panel = 'forgot';
    $target = $conn->real_escape_string(trim($_POST['forgot_target']));
    if (empty($target)) { $error = "Vui lòng nhập Email hoặc Số điện thoại!"; }
    else {
        $check = $conn->query("SELECT id FROM users WHERE (email='$target' OR phone='$target') AND status=1");
        if ($check && $check->num_rows > 0) {
            $otp_res = createAndSendOTP($conn, $target, 'forgot');
            if ($otp_res['success']) { $_SESSION['forgot_target']=$target; $_SESSION['forgot_step']=2; $success="Mã xác thực đã được gửi!"; }
            else { $error = $otp_res['message']; }
        } else { $error = "Không tìm thấy tài khoản hợp lệ với thông tin trên!"; }
    }
}
if (isset($_POST['reset_password_submit'])) {
    $active_panel = 'forgot';
    $target   = $_SESSION['forgot_target'] ?? '';
    $otp_code = trim($_POST['otp_code']);
    $new_pass = $_POST['new_password'];
    $conf_pass= $_POST['confirm_password'];
    if (empty($otp_code)||empty($new_pass)) { 
        $error = "Vui lòng nhập đầy đủ thông tin!"; 
    } elseif (strlen($new_pass) < 8) { 
        $error = "Mật khẩu mới phải có tối thiểu 8 ký tự!"; 
    } elseif (!preg_match('/[A-Z]/', $new_pass)) {
        $error = "Mật khẩu mới phải chứa ít nhất 1 chữ in hoa (A-Z)!";
    } elseif (!preg_match('/[a-z]/', $new_pass)) {
        $error = "Mật khẩu mới phải chứa ít nhất 1 chữ in thường (a-z)!";
    } elseif (!preg_match('/[0-9]/', $new_pass)) {
        $error = "Mật khẩu mới phải chứa ít nhất 1 chữ số (0-9)!";
    } elseif (!preg_match('/[\W_]/', $new_pass)) {
        $error = "Mật khẩu mới phải chứa ít nhất 1 ký tự đặc biệt (@, #, $, %, !...)!";
    } elseif (preg_match('/\s/', $new_pass)) {
        $error = "Mật khẩu mới không được chứa khoảng trắng!";
    } elseif ($new_pass !== $conf_pass) { 
        $error = "Mật khẩu xác nhận không trùng khớp!"; 
    } else {
        if (verifyOTPCode($conn,$target,$otp_code,'forgot')) {
            $hashed=password_hash($new_pass,PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed' WHERE email='$target' OR phone='$target'");
            unset($_SESSION['forgot_target'],$_SESSION['forgot_step']);
            $success="✅ Đổi mật khẩu thành công! Vui lòng đăng nhập.";
            $active_panel='login';
        } else { $error="Mã OTP không đúng hoặc đã hết hạn!"; }
    }
}

// ── 3. ĐĂNG KÝ TÀI KHOẢN (KIỂM TRA THÔNG TIN & RÀNG BUỘC MẬT KHẨU) ──
if (isset($_POST['submit_register_info'])) {
    $active_panel = 'register';
    $fullname = $conn->real_escape_string(trim($_POST['fullname']));
    $phone    = $conn->real_escape_string(trim($_POST['phone']));
    $email    = strtolower($conn->real_escape_string(trim($_POST['email'])));
    $password = $_POST['password'];
    $conf     = $_POST['confirm_password'];

    // 1. Kiểm tra Họ và Tên
    if (empty($fullname)) {
        $error = "Vui lòng nhập Họ và Tên!";
    } elseif (mb_strlen($fullname, 'UTF-8') < 2) {
        $error = "Họ và Tên phải có tối thiểu 2 ký tự!";
    } elseif (preg_match('/[0-9]/', $fullname)) {
        $error = "Họ và Tên không được chứa chữ số!";
    }
    // 2. Kiểm tra Số điện thoại (Việt Nam 10 số, bắt đầu 03, 05, 07, 08, 09)
    elseif (!preg_match('/^(0)(3|5|7|8|9)[0-9]{8}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ! Vui lòng nhập SĐT Việt Nam 10 số (bắt đầu bằng 03, 05, 07, 08, 09).";
    }
    // 3. Kiểm tra định dạng Email
    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Địa chỉ Email không đúng định dạng!";
    }
    // 4. Ràng buộc Mật khẩu theo tiêu chuẩn bảo mật cao
    elseif (strlen($password) < 8) {
        $error = "Mật khẩu phải có độ dài từ 8 ký tự trở lên!";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Mật khẩu phải chứa ít nhất 1 chữ in hoa (A-Z)!";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Mật khẩu phải chứa ít nhất 1 chữ in thường (a-z)!";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Mật khẩu phải chứa ít nhất 1 chữ số (0-9)!";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = "Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (ví dụ: @, #, $, %, !...)!";
    } elseif (preg_match('/\s/', $password)) {
        $error = "Mật khẩu không được chứa khoảng trắng!";
    } elseif ($password !== $conf) {
        $error = "Mật khẩu xác nhận không trùng khớp!";
    }
    // 5. Kiểm tra trùng lặp tài khoản trong CSDL
    else {
        $check = $conn->query("SELECT id, email, phone FROM users WHERE email='$email' OR phone='$phone' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $existing = $check->fetch_assoc();
            if ($existing['email'] === $email) {
                $error = "Email này đã được sử dụng! Vui lòng đăng nhập hoặc dùng email khác.";
            } else {
                $error = "Số điện thoại này đã được sử dụng! Vui lòng kiểm tra lại.";
            }
        } else {
            $otp_res = createAndSendOTP($conn, $email, 'register');
            if ($otp_res['success']) {
                $_SESSION['reg_step']     = 2;
                $_SESSION['reg_fullname'] = $fullname;
                $_SESSION['reg_phone']    = $phone;
                $_SESSION['reg_email']    = $email;
                $_SESSION['reg_password'] = $password;
                $success = "Mã xác thực OTP đã được gửi tới email của bạn!";
            } else {
                $error = $otp_res['message'];
            }
        }
    }
}
if (isset($_POST['verify_register_otp'])) {
    $active_panel='register';
    $fullname=$_SESSION['reg_fullname']??''; $phone=$_SESSION['reg_phone']??'';
    $email=$_SESSION['reg_email']??'';       $password=$_SESSION['reg_password']??'';
    $otp_code=trim($_POST['otp_code']);
    if (verifyOTPCode($conn,$email,$otp_code,'register')) {
        $hashed=password_hash($password,PASSWORD_DEFAULT);
        $ef=$conn->real_escape_string($email);$pf=$conn->real_escape_string($phone);$ff=$conn->real_escape_string($fullname);
        if ($conn->query("INSERT INTO users(fullname,phone,email,password,role,status)VALUES('$ff','$pf','$ef','$hashed','customer',1)")) {
            unset($_SESSION['reg_step'],$_SESSION['reg_fullname'],$_SESSION['reg_phone'],$_SESSION['reg_email'],$_SESSION['reg_password']);
            $success="🎉 Đăng ký thành công! Vui lòng đăng nhập."; $active_panel='login';
        } else { $error="Lỗi CSDL: ".$conn->error; }
    } else { $error="Mã OTP không đúng hoặc đã hết hạn!"; }
}
if (isset($_GET['reset'])) { unset($_SESSION['reg_step'],$_SESSION['reg_fullname'],$_SESSION['reg_phone'],$_SESSION['reg_email'],$_SESSION['reg_password']); header("Location: login.php?tab=register"); exit(); }
if (isset($_GET['reset_forgot'])) { unset($_SESSION['forgot_target'],$_SESSION['forgot_step']); header("Location: login.php?tab=forgot"); exit(); }

$reg_step = $_SESSION['reg_step'] ?? 1;

// Fetch shop info
$auth_shop_name = 'SHOES STORE';
$res_sname = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key IN('site_title','site_name','shop_name') AND setting_value!='' LIMIT 1");
if ($res_sname && $row=$res_sname->fetch_assoc()) $auth_shop_name=$row['setting_value'];
$auth_banner_img = '';
$res_b=$conn->query("SELECT image_url FROM banners WHERE position='hero' AND status=1 ORDER BY sort_order ASC LIMIT 1");
if ($res_b && $row=$res_b->fetch_assoc()) $auth_banner_img=$row['image_url'];
if (empty($auth_banner_img)) { $res_p=$conn->query("SELECT main_image FROM products WHERE status=1 ORDER BY sold_count DESC LIMIT 1"); if($res_p&&$row=$res_p->fetch_assoc()) $auth_banner_img=$row['main_image']; }
if (empty($auth_banner_img)) $auth_banner_img='https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800';

include_once 'includes/header.php';
?>

<style>
/* ── Google Font ──────────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

/* ── Page ─────────────────────────────────────────────────────── */
body {
    background: linear-gradient(135deg, #e8e9f8 0%, #f3f4fd 50%, #e9f0fb 100%) !important;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    min-height: 100vh;
}

/* ── Outer wrap: full viewport centering ──────────────────────── */
.auth-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
}

/* ── Main container ───────────────────────────────────────────── */
.auth-box {
    position: relative;
    width: 100%;
    max-width: 960px;
    height: 620px;
    background: #ffffff;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(49, 46, 129, 0.18),
                0 6px 24px rgba(0,0,0,0.06);
    display: flex;
}

/* ══════════════════════════════════════════════════════════════
   FORM PANELS — each 50% wide, absolutely positioned
   ══════════════════════════════════════════════════════════════ */
.auth-form-slot {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 50%;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 2.4rem 2.5rem;
    background: #ffffff;
    /* Scrollbar */
    scrollbar-width: thin;
    scrollbar-color: #e2e8f0 transparent;
}
.auth-form-slot::-webkit-scrollbar { width: 4px; }
.auth-form-slot::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

/* Login form: always on the LEFT */
.slot-login  { left: 0;   z-index: 2; }
/* Register form: always on the RIGHT */
.slot-register { right: 0; z-index: 2; }

/* ══════════════════════════════════════════════════════════════
   IMAGE PANEL — 50% wide, absolute, slides left↔right
   ══════════════════════════════════════════════════════════════ */
.auth-img-panel {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 50%;
    z-index: 20;
    background:
        radial-gradient(circle at 75% 20%, rgba(6,182,212,0.30) 0%, transparent 38%),
        radial-gradient(circle at 25% 80%, rgba(244,63,141,0.22) 0%, transparent 38%),
        linear-gradient(145deg, #11112d 0%, #2d1b69 52%, #102c46 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.75rem;
    overflow: hidden;
    border-radius: 0;

    /* Default: panel sits on the RIGHT (login mode) */
    left: 50%;
    transition: left 0.7s cubic-bezier(0.77, 0, 0.18, 1);
}

/* When register active: panel slides to LEFT */
.auth-box.register-mode .auth-img-panel {
    left: 0;
}

/* Sheen shimmer */
.auth-img-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(115deg, transparent 28%, rgba(255,255,255,0.13) 50%, transparent 72%);
    transform: translateX(-130%);
    animation: shimmer 7s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
}
@keyframes shimmer {
    0%, 30%  { transform: translateX(-130%); }
    70%, 100%{ transform: translateX(130%); }
}

/* Panel content */
.panel-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.panel-logo {
    font-size: 1.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    text-align: center;
    background: linear-gradient(90deg, #fff 0%, #bff4ff 42%, #f9b9dc 78%, #fff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 1.25rem;
    line-height: 1.2;
}

.panel-shoe-img {
    width: 88%;
    max-height: 240px;
    object-fit: contain;
    filter: drop-shadow(0 18px 36px rgba(0,0,0,0.65));
    animation: floatUp 4s ease-in-out infinite alternate;
    margin-bottom: 1.25rem;
}
@keyframes floatUp {
    from { transform: translateY(0)    rotate(-10deg) scale(1);    }
    to   { transform: translateY(-14px) rotate(-8deg) scale(1.04); }
}

.panel-tagline {
    color: rgba(255,255,255,0.72);
    font-size: 0.82rem;
    font-weight: 500;
    text-align: center;
    letter-spacing: 0.04em;
    margin-bottom: 1.5rem;
}

/* The switch call-to-action button on the panel */
.panel-cta-btn {
    display: inline-block;
    padding: 0.6rem 1.75rem;
    border-radius: 30px;
    border: 2px solid rgba(255,255,255,0.65);
    background: transparent;
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.22s ease;
    white-space: nowrap;
}
.panel-cta-btn:hover {
    background: rgba(255,255,255,0.18);
    border-color: #ffffff;
    color: #ffffff;
    transform: translateY(-2px);
}

/* ══════════════════════════════════════════════════════════════
   FORM TYPOGRAPHY & ELEMENTS
   ══════════════════════════════════════════════════════════════ */
.form-title {
    font-size: 1.55rem;
    font-weight: 800;
    color: #11112d;
    margin-bottom: 0.3rem;
    line-height: 1.2;
}
.form-sub {
    font-size: 0.81rem;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 1.3rem;
}

.f-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 0.38rem;
}

.f-input {
    width: 100%;
    padding: 0.65rem 0.9rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 11px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.f-input:focus {
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67,56,202,0.14);
}
.f-input::placeholder { color: #94a3b8; }

/* Password eye wrap */
.eye-wrap { position: relative; }
.eye-wrap .f-input { padding-right: 2.7rem; }
.eye-btn {
    position: absolute;
    right: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.88rem;
    line-height: 1;
    transition: color 0.15s;
}
.eye-btn:hover { color: #4338ca; }

/* Primary button */
.btn-main {
    width: 100%;
    padding: 0.72rem 1rem;
    background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 4px 16px rgba(67,56,202,0.3);
}
.btn-main:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(67,56,202,0.4);
}

/* Social buttons */
.btn-social {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    width: 100%;
    padding: 0.62rem 1rem;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-size: 0.84rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-social:hover {
    border-color: #4338ca;
    color: #4338ca;
    background: #f5f3ff;
    transform: translateY(-1px);
}
.btn-social .sic { font-size: 1.05rem; }

/* Divider */
.auth-divider {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin: 0.9rem 0;
}
.auth-divider::before, .auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e8eaf0;
}
.auth-divider span {
    font-size: 10.5px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    white-space: nowrap;
}

/* Alert */
.auth-msg {
    border-radius: 10px;
    padding: 0.6rem 0.85rem;
    font-size: 0.82rem;
    font-weight: 700;
    margin-bottom: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    line-height: 1.4;
}
.auth-msg.err { background: #ffe4e6; color: #be123c; border: 1px solid #fca5a5; }
.auth-msg.ok  { background: #d1fae5; color: #047857; border: 1px solid #6ee7b7; }

/* Links */
.auth-link {
    color: #4338ca;
    font-weight: 700;
    text-decoration: none;
    font-size: 0.82rem;
    transition: color 0.15s;
}
.auth-link:hover { color: #312e81; text-decoration: underline; }

/* OTP big input */
.otp-input {
    text-align: center;
    font-size: 2.1rem;
    font-weight: 900;
    letter-spacing: 0.35em;
    color: #11112d;
    border: 2px solid #4338ca;
    border-radius: 12px;
    padding: 0.75rem;
}
.otp-input:focus {
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67,56,202,0.18);
    outline: none;
}

/* Password strength & requirements checklist */
.pw-req-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    padding: 8px 12px;
    margin-bottom: 0.85rem;
    font-size: 11px;
}
.pw-req-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    margin-bottom: 2px;
    transition: color 0.2s ease, transform 0.2s ease;
    font-size: 11px;
    line-height: 1.3;
}
.pw-req-item:last-child { margin-bottom: 0; }
.pw-req-item.valid {
    color: #10b981 !important;
    font-weight: 700;
}
.pw-req-item.valid i {
    color: #10b981 !important;
}
.pw-strength-bar {
    height: 4px;
    border-radius: 3px;
    background: #e2e8f0;
    margin-top: 4px;
    overflow: hidden;
}
.pw-strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 3px;
    transition: width 0.3s ease, background-color 0.3s ease;
}

/* ══════════════════════════════════════════════════════════════
   MOBILE
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
    .auth-wrap { padding: 1rem; }
    .auth-box {
        flex-direction: column;
        height: auto;
        max-width: 440px;
    }
    .auth-img-panel {
        position: relative !important;
        left: 0 !important;
        width: 100% !important;
        min-height: 220px;
        border-radius: 18px 18px 0 0;
        padding: 1.6rem 1.5rem;
    }
    .auth-box.register-mode .auth-img-panel { left: 0 !important; }
    .auth-form-slot {
        position: relative !important;
        width: 100% !important;
        left: 0 !important; right: 0 !important;
        max-height: none !important;
        padding: 1.6rem 1.4rem !important;
    }
    .slot-register { display: none; }
    .auth-box.register-mode .slot-login    { display: none !important; }
    .auth-box.register-mode .slot-register { display: flex !important; }
    .panel-shoe-img { max-height: 130px; }
    .panel-logo { font-size: 1.3rem; }
}
@media (max-width: 400px) {
    .auth-form-slot { padding: 1.2rem 1rem !important; }
    .form-title { font-size: 1.3rem; }
}
</style>

<div class="auth-wrap">
<div class="auth-box <?= ($active_panel === 'register') ? 'register-mode' : '' ?>" id="authBox">

    <!-- ═══════════════════ FORM LOGIN (LEFT) ═══════════════════ -->
    <div class="auth-form-slot slot-login" id="slotLogin">

        <?php if ($active_panel === 'forgot'): ?>
        <!-- ── QUÊN MẬT KHẨU ── -->
        <div class="form-title">Khôi phục tài khoản 🔑</div>
        <div class="form-sub">Nhập thông tin để nhận mã đổi mật khẩu</div>

        <?php if ($error): ?>
        <div class="auth-msg err"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="auth-msg ok"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['forgot_step']) && $_SESSION['forgot_step'] == 2): ?>
        <form method="POST">
            <div style="font-size:0.81rem;color:#64748b;margin-bottom:0.8rem;">
                Khôi phục tài khoản: <strong style="color:#4338ca;"><?= htmlspecialchars($_SESSION['forgot_target']??'') ?></strong>
            </div>
            <div class="mb-3">
                <label class="f-label">Mã OTP 6 chữ số <span style="color:#f43f5e">*</span></label>
                <input type="text" name="otp_code" class="f-input otp-input" maxlength="6" required autofocus>
            </div>
            <div class="mb-3">
                <label class="f-label">Mật khẩu mới <span style="color:#f43f5e">*</span></label>
                <div class="eye-wrap">
                    <input type="password" name="new_password" class="f-input" placeholder="Tối thiểu 6 ký tự..." id="np1" minlength="6" required>
                    <button type="button" class="eye-btn" onclick="togglePw('np1',this)"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="f-label">Xác nhận mật khẩu <span style="color:#f43f5e">*</span></label>
                <div class="eye-wrap">
                    <input type="password" name="confirm_password" class="f-input" placeholder="Nhập lại mật khẩu..." id="np2" minlength="6" required>
                    <button type="button" class="eye-btn" onclick="togglePw('np2',this)"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <button type="submit" name="reset_password_submit" class="btn-main mb-2">
                <i class="fa-solid fa-lock me-2"></i>LƯU MẬT KHẨU MỚI
            </button>
            <div class="text-center mt-2">
                <a href="login.php?reset_forgot=1" class="auth-link">Gửi lại mã khác</a>
            </div>
        </form>
        <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="f-label">Email hoặc Số điện thoại <span style="color:#f43f5e">*</span></label>
                <input type="text" name="forgot_target" class="f-input" placeholder="Nhập email hoặc SĐT..." required autofocus>
            </div>
            <button type="submit" name="send_forgot_otp" class="btn-main mb-3">
                <i class="fa-solid fa-paper-plane me-2"></i>GỬI MÃ KHÔI PHỤC
            </button>
        </form>
        <?php endif; ?>
        <div class="text-center mt-1">
            <a href="login.php" class="auth-link">← Quay lại đăng nhập</a>
        </div>

        <?php else: ?>
        <!-- ── ĐĂNG NHẬP ── -->
        <div class="form-title mb-4">Đăng nhập</div>

        <?php if ($error && $active_panel === 'login'): ?>
        <div class="auth-msg err"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success && in_array($active_panel,['login'])): ?>
        <div class="auth-msg ok"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="f-label">Email hoặc Số điện thoại <span style="color:#f43f5e">*</span></label>
                <input type="text" name="account" class="f-input" placeholder="Nhập email hoặc SĐT..." required autofocus>
            </div>
            <div class="mb-1">
                <label class="f-label">Mật khẩu <span style="color:#f43f5e">*</span></label>
                <div class="eye-wrap">
                    <input type="password" name="password" class="f-input" placeholder="Nhập mật khẩu..." required id="loginPw">
                    <button type="button" class="eye-btn" onclick="togglePw('loginPw',this)"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <div class="text-end mb-3">
                <a href="login.php?tab=forgot" class="auth-link" style="font-size:0.79rem;">Quên mật khẩu?</a>
            </div>
            <button type="submit" name="login_password" class="btn-main mb-2">
                <i class="fa-solid fa-right-to-bracket me-2"></i>ĐĂNG NHẬP
            </button>
        </form>

        <div class="auth-divider"><span>Hoặc tiếp tục với</span></div>

        <div class="d-flex flex-column gap-2 mb-3">
            <a href="<?= htmlspecialchars($google_auth_url ?: '#') ?>"
               <?= $google_auth_url ? '' : 'onclick="alert(\'Chưa cấu hình Google OAuth.\');return false;"' ?>
               class="btn-social">
                <i class="fa-brands fa-google sic" style="color:#ea4335;"></i>
                Tiếp tục với Google
            </a>
            <a href="login-otp.php" class="btn-social">
                <i class="fa-solid fa-mobile-screen-button sic" style="color:#4338ca;"></i>
                Đăng nhập bằng OTP
            </a>
        </div>
        <?php endif; ?>

    </div><!-- /slot-login -->

    <!-- ═══════════════════ FORM REGISTER (RIGHT) ═══════════════════ -->
    <div class="auth-form-slot slot-register" id="slotRegister">

        <?php if ($reg_step == 1): ?>
        <div class="form-title mb-4">Đăng ký</div>

        <?php if ($error && $active_panel === 'register'): ?>
        <div class="auth-msg err"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success && $active_panel === 'register'): ?>
        <div class="auth-msg ok"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm" onsubmit="return validateRegisterForm(event)">
            <div class="mb-2">
                <label class="f-label">Họ và Tên <span style="color:#f43f5e">*</span></label>
                <input type="text" name="fullname" id="regFullname" class="f-input" placeholder="Nhập họ tên đầy đủ..." required autofocus value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
            </div>
            <div class="mb-2">
                <label class="f-label">Số điện thoại <span style="color:#f43f5e">*</span></label>
                <input type="tel" name="phone" id="regPhone" class="f-input" placeholder="Nhập SĐT (10 số, bắt đầu 03,05,07,08,09)..." maxlength="10" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="mb-2">
                <label class="f-label">Email <span style="color:#f43f5e">*</span></label>
                <input type="email" name="email" id="regEmail" class="f-input" placeholder="Nhập địa chỉ email..." required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="f-label">Mật khẩu <span style="color:#f43f5e">*</span></label>
                    <div class="eye-wrap">
                        <input type="password" name="password" class="f-input" placeholder="Tối thiểu 8 ký tự" id="rp1" minlength="8" required oninput="checkPasswordCriteria()">
                        <button type="button" class="eye-btn" onclick="togglePw('rp1',this)"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="col-6">
                    <label class="f-label">Xác nhận <span style="color:#f43f5e">*</span></label>
                    <div class="eye-wrap">
                        <input type="password" name="confirm_password" class="f-input" placeholder="Nhập lại mật khẩu" id="rp2" minlength="8" required oninput="checkPasswordCriteria()">
                        <button type="button" class="eye-btn" onclick="togglePw('rp2',this)"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
            </div>

            <!-- Khung kiểm tra độ mạnh & Tiêu chí mật khẩu trực quan -->
            <div class="pw-req-box">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold" style="color:#475569;">Độ mạnh mật khẩu:</span>
                    <span class="fw-bold" id="pwStrengthText" style="color:#94a3b8;font-size:11px;">Chưa nhập</span>
                </div>
                <div class="pw-strength-bar mb-2">
                    <div class="pw-strength-fill" id="pwStrengthFill"></div>
                </div>
                <div class="row g-1">
                    <div class="col-6">
                        <div class="pw-req-item" id="req-len">
                            <i class="fa-regular fa-circle text-muted"></i> <span>Tối thiểu 8 ký tự</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pw-req-item" id="req-case">
                            <i class="fa-regular fa-circle text-muted"></i> <span>Chữ hoa &amp; thường</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pw-req-item" id="req-num">
                            <i class="fa-regular fa-circle text-muted"></i> <span>Ít nhất 1 chữ số</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pw-req-item" id="req-special">
                            <i class="fa-regular fa-circle text-muted"></i> <span>Ký tự đặc biệt (@#$...)</span>
                        </div>
                    </div>
                    <div class="col-12" id="req-match-wrap" style="display:none;">
                        <div class="pw-req-item" id="req-match">
                            <i class="fa-regular fa-circle text-muted"></i> <span>Mật khẩu xác nhận khớp</span>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="submit_register_info" class="btn-main mb-2">
                <i class="fa-solid fa-user-plus me-2"></i>ĐĂNG KÝ TÀI KHOẢN
            </button>
        </form>

        <?php elseif ($reg_step == 2): ?>
        <div class="form-title">Xác minh Email 📧</div>
        <div class="form-sub">Nhập mã 6 số đã gửi tới <strong><?= htmlspecialchars($_SESSION['reg_email']??'') ?></strong></div>

        <?php if ($error): ?><div class="auth-msg err"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="auth-msg ok"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="f-label">Mã OTP 6 chữ số <span style="color:#f43f5e">*</span></label>
                <input type="text" name="otp_code" class="f-input otp-input" maxlength="6" required autofocus>
            </div>
            <button type="submit" name="verify_register_otp" class="btn-main mb-2">
                <i class="fa-solid fa-shield-check me-2"></i>XÁC NHẬN &amp; HOÀN TẤT
            </button>
            <div class="text-center mt-2">
                <a href="login.php?reset=1" class="auth-link">← Nhập lại thông tin</a>
            </div>
        </form>
        <?php endif; ?>

    </div><!-- /slot-register -->

    <!-- ═══════════════════ SLIDING IMAGE PANEL ═══════════════════ -->
    <div class="auth-img-panel" id="imgPanel">
        <div class="panel-inner">
            <div class="panel-logo"><?= htmlspecialchars($auth_shop_name) ?></div>
            <img src="<?= htmlspecialchars($auth_banner_img) ?>"
                 alt="<?= htmlspecialchars($auth_shop_name) ?>"
                 class="panel-shoe-img" id="panelImg">
            <a href="#" class="panel-cta-btn" id="panelCtaBtn">Đăng ký ngay</a>
        </div>
    </div>

</div><!-- /.auth-box -->
</div><!-- /.auth-wrap -->

<script>
const authBox   = document.getElementById('authBox');
const ctaBtn    = document.getElementById('panelCtaBtn');

let currentMode = '<?= $active_panel ?>';

function switchPanel(mode) {
    if (mode === 'login' && '<?= $active_panel ?>' === 'forgot') {
        window.location.href = 'login.php';
        return;
    }
    if (mode === 'register') {
        authBox.classList.add('register-mode');
        ctaBtn.textContent    = 'Đăng nhập ngay';
        ctaBtn.onclick = function(e){ e.preventDefault(); switchPanel('login'); };
        if (window.history) window.history.replaceState({}, '', 'login.php?tab=register');
    } else {
        authBox.classList.remove('register-mode');
        ctaBtn.textContent    = 'Đăng ký ngay';
        ctaBtn.onclick = function(e){ e.preventDefault(); switchPanel('register'); };
        if (window.history && mode !== 'forgot') window.history.replaceState({}, '', 'login.php?tab=login');
    }
    currentMode = mode;

    // Show/hide the correct form slot on mobile
    const sl = document.getElementById('slotLogin');
    const sr = document.getElementById('slotRegister');
    if (window.innerWidth <= 768) {
        if (mode === 'register') {
            sl.style.display = 'none';
            sr.style.display = 'flex';
        } else {
            sl.style.display = 'flex';
            sr.style.display = 'none';
        }
    }
}

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.innerHTML = inp.type === 'password'
        ? '<i class="fa-solid fa-eye"></i>'
        : '<i class="fa-solid fa-eye-slash"></i>';
}

// ── KIỂM TRA TIÊU CHÍ & ĐỘ MẠNH MẬT KHẨU REAL-TIME ──
function checkPasswordCriteria() {
    const pwInput = document.getElementById('rp1');
    const cpwInput = document.getElementById('rp2');
    if (!pwInput) return;

    const pw = pwInput.value;
    const cpw = cpwInput ? cpwInput.value : '';

    const reqLen = pw.length >= 8;
    const reqCase = /[A-Z]/.test(pw) && /[a-z]/.test(pw);
    const reqNum = /[0-9]/.test(pw);
    const reqSpecial = /[\W_]/.test(pw);
    const reqMatch = pw.length > 0 && pw === cpw;

    setReqState('req-len', reqLen, 'Tối thiểu 8 ký tự');
    setReqState('req-case', reqCase, 'Chữ hoa & thường');
    setReqState('req-num', reqNum, 'Ít nhất 1 chữ số');
    setReqState('req-special', reqSpecial, 'Ký tự đặc biệt (@#$...)');

    const matchWrap = document.getElementById('req-match-wrap');
    if (matchWrap) {
        if (cpw.length > 0) {
            matchWrap.style.display = 'block';
            setReqState('req-match', reqMatch, reqMatch ? 'Mật khẩu xác nhận khớp' : 'Mật khẩu chưa khớp');
        } else {
            matchWrap.style.display = 'none';
        }
    }

    let score = 0;
    if (reqLen) score++;
    if (reqCase) score++;
    if (reqNum) score++;
    if (reqSpecial) score++;

    const fill = document.getElementById('pwStrengthFill');
    const text = document.getElementById('pwStrengthText');
    if (!fill || !text) return;

    if (pw.length === 0) {
        fill.style.width = '0%';
        fill.style.backgroundColor = '#e2e8f0';
        text.textContent = 'Chưa nhập';
        text.style.color = '#94a3b8';
    } else if (score <= 1) {
        fill.style.width = '25%';
        fill.style.backgroundColor = '#ef4444';
        text.textContent = 'Yếu';
        text.style.color = '#ef4444';
    } else if (score === 2) {
        fill.style.width = '50%';
        fill.style.backgroundColor = '#f59e0b';
        text.textContent = 'Trung bình';
        text.style.color = '#f59e0b';
    } else if (score === 3) {
        fill.style.width = '75%';
        fill.style.backgroundColor = '#3b82f6';
        text.textContent = 'Khá mạnh';
        text.style.color = '#3b82f6';
    } else if (score === 4) {
        fill.style.width = '100%';
        fill.style.backgroundColor = '#10b981';
        text.textContent = 'Rất mạnh ✓';
        text.style.color = '#10b981';
    }
}

function setReqState(id, isValid, label) {
    const el = document.getElementById(id);
    if (!el) return;
    if (isValid) {
        el.className = 'pw-req-item valid';
        el.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>' + label + '</span>';
    } else {
        el.className = 'pw-req-item';
        el.innerHTML = '<i class="fa-regular fa-circle text-muted"></i> <span>' + label + '</span>';
    }
}

function validateRegisterForm(e) {
    const name = document.getElementById('regFullname') ? document.getElementById('regFullname').value.trim() : '';
    const phone = document.getElementById('regPhone') ? document.getElementById('regPhone').value.trim() : '';
    const email = document.getElementById('regEmail') ? document.getElementById('regEmail').value.trim() : '';
    const pw = document.getElementById('rp1') ? document.getElementById('rp1').value : '';
    const cpw = document.getElementById('rp2') ? document.getElementById('rp2').value : '';

    if (name.length < 2) {
        alert('Họ và Tên phải có tối thiểu 2 ký tự!');
        return false;
    }
    if (/\d/.test(name)) {
        alert('Họ và Tên không được chứa chữ số!');
        return false;
    }
    if (!/^(0)(3|5|7|8|9)[0-9]{8}$/.test(phone)) {
        alert('Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại Việt Nam 10 số (bắt đầu bằng 03, 05, 07, 08, 09).');
        return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Địa chỉ Email không đúng định dạng!');
        return false;
    }
    if (pw.length < 8) {
        alert('Mật khẩu phải có độ dài từ 8 ký tự trở lên!');
        return false;
    }
    if (!/[A-Z]/.test(pw)) {
        alert('Mật khẩu phải chứa ít nhất 1 chữ in hoa (A-Z)!');
        return false;
    }
    if (!/[a-z]/.test(pw)) {
        alert('Mật khẩu phải chứa ít nhất 1 chữ in thường (a-z)!');
        return false;
    }
    if (!/[0-9]/.test(pw)) {
        alert('Mật khẩu phải chứa ít nhất 1 chữ số (0-9)!');
        return false;
    }
    if (!/[\W_]/.test(pw)) {
        alert('Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (ví dụ: @, #, $, %, !...)!');
        return false;
    }
    if (/\s/.test(pw)) {
        alert('Mật khẩu không được chứa khoảng trắng!');
        return false;
    }
    if (pw !== cpw) {
        alert('Mật khẩu xác nhận không trùng khớp!');
        return false;
    }
    return true;
}

// Initialize button state
switchPanel(currentMode);
</script>

<?php include_once 'includes/footer.php'; ?>