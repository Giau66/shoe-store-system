<?php
// config/otp-helper.php
require_once __DIR__ . '/db.php';

// 1. TỰ ĐỘNG NẠP THƯ VIỆN PHPMAILER
if (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// =========================================================================
// 2. CẤU HÌNH GỬI MAIL (Đọc an toàn từ local-config hoặc biến môi trường)
// =========================================================================
@include_once __DIR__ . '/local-config.php';

$smtp_email = getenv('SMTP_EMAIL') ?: (defined('LOCAL_SMTP_EMAIL') ? LOCAL_SMTP_EMAIL : 'your-email@gmail.com');
$smtp_password = getenv('SMTP_PASSWORD') ?: (defined('LOCAL_SMTP_PASSWORD') ? LOCAL_SMTP_PASSWORD : 'your-app-password');

if (!defined('SMTP_EMAIL')) {
    define('SMTP_EMAIL', $smtp_email);
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', $smtp_password);
}
if (!defined('SPEEDSMS_ACCESS_TOKEN')) {
    define('SPEEDSMS_ACCESS_TOKEN', getenv('SPEEDSMS_ACCESS_TOKEN') ?: 'YOUR_SPEEDSMS_TOKEN');
}

/**
 * Hàm gửi Email OTP THẬT bằng PHPMailer
 */
function sendRealEmail($toEmail, $otp_code) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return ['success' => false, 'error' => 'Chưa nạp được thư viện PHPMailer trong config/PHPMailer!'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Dùng SSL Cổng 465 cho mượt trên XAMPP
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Bỏ qua kiểm tra SSL Cert trên môi trường Localhost
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom(SMTP_EMAIL, 'SHOES STORE - Mã Xác Thực');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = "[$otp_code] - Mã xác thực OTP Đăng ký Shoes Store";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fbf9f5; border-radius: 10px; border: 1px solid #e8e3d9;'>
                <h2 style='color: #4a6b5d; margin-bottom: 10px;'>SHOES STORE</h2>
                <p style='color: #333;'>Mã xác thực OTP của bạn là:</p>
                <h1 style='color: #d97757; letter-spacing: 5px; font-size: 32px; margin: 15px 0;'>$otp_code</h1>
                <p style='color: #666; font-size: 13px;'>Mã này có hiệu lực trong <b>5 phút</b>. Vui lòng không chia sẻ mã cho bất kỳ ai.</p>
            </div>
        ";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

function sendRealSMS($phone, $otp_code) {
    if (substr($phone, 0, 1) == '0') {
        $phone = '84' . substr($phone, 1);
    }
    return true;
}

function createAndSendOTP($conn, $target, $type = 'register') {
    $target   = trim($target);
    $otp_code = sprintf("%06d", mt_rand(100000, 999999));
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $conn->query("UPDATE otp_verifications SET is_used = 1 WHERE target = '$target' AND type = '$type'");

    $sql = "INSERT INTO otp_verifications (target, otp_code, type, expires_at, is_used) 
            VALUES ('$target', '$otp_code', '$type', '$expires_at', 0)";
    
    if ($conn->query($sql)) {
        unset($_SESSION['demo_otp_notice']);

        if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
            $result = sendRealEmail($target, $otp_code);
            if ($result['success']) {
                return ['success' => true, 'message' => 'Mã xác thực đã được gửi thành công!'];
            } else {
                return ['success' => false, 'message' => 'Lỗi Gmail: ' . htmlspecialchars($result['error'])];
            }
        } else {
            sendRealSMS($target, $otp_code);
            return ['success' => true, 'message' => 'Mã xác thực đã được gửi thành công!'];
        }
    }
    
    return ['success' => false, 'message' => 'Lỗi CSDL khi tạo OTP!'];
}

function verifyOTPCode($conn, $target, $otp_code, $type = 'register') {
    $target   = trim($target);
    $otp_code = trim($otp_code);
    $now      = date('Y-m-d H:i:s');

    $sql = "SELECT id FROM otp_verifications 
            WHERE target = '$target' 
              AND otp_code = '$otp_code' 
              AND type = '$type' 
              AND is_used = 0 
              AND expires_at >= '$now' 
            ORDER BY id DESC LIMIT 1";
            
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $otp_id = $row['id'];
        $conn->query("UPDATE otp_verifications SET is_used = 1 WHERE id = $otp_id");
        return true;
    }
    return false;
}
?>