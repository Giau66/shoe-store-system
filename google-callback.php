<?php
ob_start();
session_start();
require_once 'config/db.php';
require_once 'config/google-config.php';

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    $_SESSION['login_error'] = 'Google OAuth chưa được cấu hình. Vui lòng tạo config/local-config.php theo file mẫu.';
    header('Location: login.php');
    exit;
}

function googleLoginFail(string $message, array $context = []): void
{
    error_log('[Google OAuth] ' . $message . (!empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''));
    $_SESSION['google_oauth_error'] = $message;
    header('Location: login.php?error=google_failed');
    exit();
}

if (isset($_GET['error'])) {
    $googleError = (string)$_GET['error'];
    $description = (string)($_GET['error_description'] ?? '');
    if ($googleError === 'access_denied') {
        googleLoginFail('Bạn đã hủy hoặc chưa cấp quyền đăng nhập Google.');
    }
    googleLoginFail('Google từ chối yêu cầu đăng nhập: ' . ($description ?: $googleError));
}

if (empty($_GET['code'])) {
    googleLoginFail('Google không trả về mã xác thực.');
}

$receivedState = (string)($_GET['state'] ?? '');
$expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
if ($receivedState === '' || $expectedState === '' || !hash_equals($expectedState, $receivedState)) {
    googleLoginFail('Phiên đăng nhập Google không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
}
unset($_SESSION['google_oauth_state']);

if (!function_exists('curl_init')) {
    googleLoginFail('PHP chưa bật extension cURL. Hãy bật extension=curl trong php.ini rồi khởi động lại Apache.');
}

$secrets_to_try = [trim(GOOGLE_CLIENT_SECRET)];
if (strpos(GOOGLE_CLIENT_SECRET, 'GOCSPX--') === 0) {
    $secrets_to_try[] = 'GOCSPX-' . substr(GOOGLE_CLIENT_SECRET, 8);
} elseif (strpos(GOOGLE_CLIENT_SECRET, 'GOCSPX-') === 0 && strpos(GOOGLE_CLIENT_SECRET, 'GOCSPX--') === false) {
    $secrets_to_try[] = 'GOCSPX--' . substr(GOOGLE_CLIENT_SECRET, 7);
}

$tokenData = null;
$tokenHttpCode = 0;
$lastErrorDetail = '';

foreach ($secrets_to_try as $sec) {
    $tokenFields = [
        'code' => (string)$_GET['code'],
        'client_id' => trim(GOOGLE_CLIENT_ID),
        'client_secret' => $sec,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($tokenFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $tokenResponse = curl_exec($ch);
    $tokenHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tokenCurlError = curl_error($ch);
    curl_close($ch);

    if ($tokenResponse !== false && $tokenHttpCode === 200) {
        $parsed = json_decode((string)$tokenResponse, true);
        if (!empty($parsed['access_token'])) {
            $tokenData = $parsed;
            break;
        }
    } else {
        $parsed = json_decode((string)$tokenResponse, true);
        $lastErrorDetail = $parsed['error_description'] ?? $parsed['error'] ?? $tokenCurlError;
    }
}

if (empty($tokenData['access_token'])) {
    googleLoginFail('Google OAuth lỗi: ' . ($lastErrorDetail ?: 'Không nhận được access token') . '. Callback: ' . GOOGLE_REDIRECT_URI, ['http_code' => $tokenHttpCode]);
}

$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenData['access_token']],
]);
$userInfoResponse = curl_exec($ch);
$userInfoHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$userInfoCurlError = curl_error($ch);
curl_close($ch);

if ($userInfoResponse === false || $userInfoCurlError !== '') {
    googleLoginFail('Không thể lấy thông tin tài khoản Google.', ['curl' => $userInfoCurlError]);
}

$googleUser = json_decode((string)$userInfoResponse, true);
if ($userInfoHttpCode !== 200 || empty($googleUser['sub']) || empty($googleUser['email'])) {
    googleLoginFail('Google không trả về đầy đủ ID và email người dùng.', ['http_code' => $userInfoHttpCode]);
}

$googleId = (string)$googleUser['sub'];
$email = strtolower(trim((string)$googleUser['email']));
$fullname = trim((string)($googleUser['name'] ?? ''));
$avatar = trim((string)($googleUser['picture'] ?? ''));

$stmt = $conn->prepare('SELECT id, fullname, role, status FROM users WHERE email = ? OR google_id = ? LIMIT 1');
if (!$stmt) {
    googleLoginFail('Không thể chuẩn bị truy vấn tài khoản: ' . $conn->error);
}
$stmt->bind_param('ss', $email, $googleId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    if ((int)$user['status'] !== 1) {
        googleLoginFail('Tài khoản này đang bị khóa. Vui lòng liên hệ quản trị viên.');
    }

    $userId = (int)$user['id'];
    $update = $conn->prepare("UPDATE users SET google_id = ?, auth_provider = 'google', is_email_verified = 1, avatar = CASE WHEN avatar IS NULL OR avatar = '' THEN ? ELSE avatar END WHERE id = ?");
    if (!$update) {
        googleLoginFail('Không thể cập nhật tài khoản Google: ' . $conn->error);
    }
    $update->bind_param('ssi', $googleId, $avatar, $userId);
    $update->execute();
    $update->close();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $user['fullname'] ?: $fullname;
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['cart'] = [];

    $cartStmt = $conn->prepare("SELECT c.variant_id, c.quantity, p.id AS product_id, p.name, p.main_image, p.price, p.discount_percent, v.size, v.color FROM cart_items c JOIN products p ON c.product_id = p.id JOIN product_variants v ON c.variant_id = v.id WHERE c.user_id = ?");
    if ($cartStmt) {
        $cartStmt->bind_param('i', $userId);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();
        $raw_cart_items = [];
        $pids = [];
        while ($item = $cartResult->fetch_assoc()) {
            $raw_cart_items[] = $item;
            $pids[] = intval($item['product_id']);
        }
        $cartStmt->close();

        $sale_map = get_active_sale_events_map($conn, $pids);
        foreach ($raw_cart_items as $item) {
            $pid = intval($item['product_id']);
            if (isset($sale_map[$pid]) && $sale_map[$pid]['has_sale']) {
                $price = floatval($sale_map[$pid]['sale_price']);
            } else {
                $price = (float)$item['price'];
            }
            $_SESSION['cart'][] = [
                'product_id' => (int)$item['product_id'],
                'variant_id' => (int)$item['variant_id'],
                'name' => $item['name'],
                'image' => $item['main_image'],
                'size' => $item['size'],
                'color' => $item['color'],
                'price' => $price,
                'quantity' => (int)$item['quantity'],
            ];
        }
    }

    header('Location: index.php?login=google_success');
    exit();
}

$_SESSION['google_signup'] = [
    'google_id' => $googleId,
    'email' => $email,
    'fullname' => $fullname,
    'avatar' => $avatar,
];
header('Location: google-complete-profile.php');
exit();
