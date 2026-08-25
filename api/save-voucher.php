<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'require_login' => true,
        'message' => 'Vui lòng đăng nhập để lưu voucher vào tài khoản của bạn.'
    ]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$voucher_id = isset($_POST['voucher_id']) ? intval($_POST['voucher_id']) : 0;
$voucher_code = isset($_POST['voucher_code']) ? trim($_POST['voucher_code']) : '';

if ($voucher_id <= 0 && !empty($voucher_code)) {
    $code_esc = $conn->real_escape_string($voucher_code);
    $v_res = $conn->query("SELECT id FROM vouchers WHERE code = '$code_esc' AND status = 1 LIMIT 1");
    if ($v_res && $v_res->num_rows > 0) {
        $voucher_id = intval($v_res->fetch_assoc()['id']);
    }
}

if ($voucher_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã bị vô hiệu hóa.']);
    exit;
}

// Kiểm tra voucher tồn tại & thời hạn
$v_query = $conn->query("SELECT * FROM vouchers WHERE id = $voucher_id AND status = 1 LIMIT 1");
if (!$v_query || $v_query->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn.']);
    exit;
}
$v = $v_query->fetch_assoc();
$now = date('Y-m-d H:i:s');
if ($v['end_date'] && $now > $v['end_date']) {
    echo json_encode(['success' => false, 'message' => 'Rất tiếc, mã giảm giá này đã hết hạn sử dụng.']);
    exit;
}

// Kiểm tra nếu là voucher người mới mà user đã có đơn hàng
if ($v['event_type'] === 'new_user') {
    $res_uo = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id = $user_id AND status != 'cancelled'");
    $user_order_cnt = $res_uo ? intval($res_uo->fetch_assoc()['cnt']) : 0;
    if ($user_order_cnt > 0) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá này chỉ dành riêng cho khách hàng mới / đơn hàng đầu tiên.']);
        exit;
    }
}

// Kiểm tra xem user đã lưu chưa
$uv_check = $conn->query("SELECT id, used_at FROM user_vouchers WHERE user_id = $user_id AND voucher_id = $voucher_id LIMIT 1");
if ($uv_check && $uv_check->num_rows > 0) {
    $row = $uv_check->fetch_assoc();
    if ($row['used_at'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này cho đơn hàng trước đó.']);
        exit;
    }
    echo json_encode(['success' => true, 'status' => 'already_saved', 'message' => 'Mã giảm giá đã có trong tài khoản của bạn!']);
    exit;
}

// Tiến hành lưu
$stmt = $conn->prepare("INSERT INTO user_vouchers (user_id, voucher_id, saved_at) VALUES (?, ?, NOW())");
if ($stmt) {
    $stmt->bind_param('ii', $user_id, $voucher_id);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'status' => 'saved',
            'voucher_code' => $v['code'],
            'message' => 'Lưu voucher thành công! Bạn có thể sử dụng khi thanh toán.'
        ]);
        $stmt->close();
        exit;
    }
    $stmt->close();
}

echo json_encode(['success' => false, 'message' => 'Không thể lưu voucher. Vui lòng thử lại sau.']);
exit;
