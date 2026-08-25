<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Hỗ trợ cả Webhook thực tế (SePay, Cassov, Open Banking) và Test Mô phỏng
$raw_input = file_get_contents('php://input');
$input_json = json_decode($raw_input, true) ?: [];

$order_code = trim($_POST['order_code'] ?? $input_json['order_code'] ?? $input_json['code'] ?? $_GET['order_code'] ?? '');
$content    = trim($input_json['content'] ?? $input_json['description'] ?? $input_json['transaction_content'] ?? $_POST['content'] ?? '');
$amount_in  = floatval($input_json['transferAmount'] ?? $input_json['amount'] ?? $_POST['amount'] ?? 0);
$is_simulate= !empty($_POST['simulate']) || !empty($_GET['simulate']) || !empty($input_json['simulate']);

// Nếu nội dung chuyển khoản chứa mã đơn hàng (Ví dụ: "SH2026081512345678" hoặc "SH123456")
if (empty($order_code) && !empty($content)) {
    if (preg_match('/(SH[0-9A-Za-z_-]{4,30})/i', $content, $matches)) {
        $order_code = $matches[1];
    }
}

if (empty($order_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy mã đơn hàng trong dữ liệu giao dịch.'
    ]);
    exit;
}

$order_code_esc = $conn->real_escape_string($order_code);
$stmt = $conn->prepare("SELECT id, order_code, total_money, payment_status, status FROM orders WHERE order_code = ? LIMIT 1");
$stmt->bind_param('s', $order_code_esc);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn hàng không tồn tại trên hệ thống.'
    ]);
    exit;
}

$order_id = intval($order['id']);
$order_total = floatval($order['total_money']);

// Kiểm tra số tiền chuyển nếu không phải mô phỏng
if (!$is_simulate && $amount_in > 0 && $amount_in < $order_total) {
    echo json_encode([
        'success' => false,
        'message' => "Số tiền nhận ($amount_in) nhỏ hơn tổng tiền đơn hàng ($order_total)."
    ]);
    exit;
}

// Cập nhật trạng thái đơn hàng thành đã thanh toán (giữ nguyên trạng thái đơn hàng chờ duyệt)
$update_stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
$update_stmt->bind_param('i', $order_id);
$update_success = $update_stmt->execute();
$update_stmt->close();

if ($update_success) {
    echo json_encode([
        'success' => true,
        'message' => 'Xác nhận thanh toán thành công!',
        'order_code' => $order['order_code'],
        'payment_status' => 'paid',
        'status' => $order['status'],
        'redirect_url' => 'order-success.php?code=' . urlencode($order['order_code'])
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi cập nhật cơ sở dữ liệu: ' . $conn->error
    ]);
}
