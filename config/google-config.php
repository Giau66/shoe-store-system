<?php
// config/google-config.php
// Có thể cấu hình bằng biến môi trường GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET.
// Với XAMPP, nếu chưa dùng biến môi trường thì điền trực tiếp hai giá trị bên dưới.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

@include_once __DIR__ . '/local-config.php';
$googleClientId = getenv('GOOGLE_CLIENT_ID') ?: (defined('LOCAL_GOOGLE_CLIENT_ID') ? LOCAL_GOOGLE_CLIENT_ID : '');
$googleClientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: (defined('LOCAL_GOOGLE_CLIENT_SECRET') ? LOCAL_GOOGLE_CLIENT_SECRET : '');

// Tự xác định redirect URI theo môi trường (localhost vs hosting)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    // ---- MÔI TRƯỜNG LOCALHOST ----
    // QUAN TRỌNG: Đăng ký đúng URL này trong Google Cloud Console
    // Vào: https://console.cloud.google.com/apis/credentials
    // > Chọn OAuth 2.0 Client > Authorized redirect URIs
    // > Thêm: http://localhost/web-shoe/google-callback.php
    $googleRedirectUri = 'http://localhost/web-shoe/google-callback.php';
} else {
    // ---- MÔI TRƯỜNG HOSTING (shoestore.wuaze.com) ----
    // Đã đăng ký trong Google Cloud Console:
    // https://shoestore.wuaze.com/google-callback.php
    $googleRedirectUri = 'https://' . $host . '/google-callback.php';
}
// Cho phép override bằng biến môi trường nếu cần
$googleRedirectUri = getenv('GOOGLE_REDIRECT_URI') ?: $googleRedirectUri;

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', $googleClientId);
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', $googleClientSecret);
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', $googleRedirectUri);
}

// State chống CSRF và giúp phát hiện callback không hợp lệ.
if (empty($_SESSION['google_oauth_state'])) {
    $_SESSION['google_oauth_state'] = bin2hex(random_bytes(24));
}

$google_auth_url = '';
if (GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '') {
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'include_granted_scopes' => 'true',
    'prompt' => 'select_account',
    'state' => $_SESSION['google_oauth_state'],
]);
}
