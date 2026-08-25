<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/db.php';

$order_code = trim($_GET['order_code'] ?? $_GET['code'] ?? $_POST['order_code'] ?? '');

if (empty($order_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu mã đơn hàng.',
        'paid' => false
    ]);
    exit;
}

$order_code_esc = $conn->real_escape_string($order_code);
$stmt = $conn->prepare("SELECT id, order_code, user_id, total_money, payment_method, payment_status, status FROM orders WHERE order_code = ? LIMIT 1");
$stmt->bind_param('s', $order_code_esc);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy đơn hàng.',
        'paid' => false
    ]);
    exit;
}

$isPaid = ($order['payment_status'] === 'paid');

// Nếu đơn chưa paid, thử kiểm tra trực tiếp từ API SePay (Bỏ qua rào cản Webhook của Host)
if (!$isPaid) {
    // 1. Lấy SePay API Key từ hằng số hoặc site_settings
    $sepay_api_key = 'MSDUSKUKDRAQOGOJPIHZCRQFPV8LE2MGRTTFBTOCKQ3C5PGZEE6BAWJM8NJYON7V';
    if (empty($sepay_api_key)) {
        $res_api_k = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'sepay_api_key' LIMIT 1");
        if ($res_api_k && $row_k = $res_api_k->fetch_assoc()) {
            $sepay_api_key = trim($row_k['setting_value']);
        }
    }

    if (!empty($sepay_api_key)) {
        $ch = curl_init("https://my.sepay.vn/userapi/transactions/list?limit=20");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $sepay_api_key",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $api_res_raw = curl_exec($ch);
        curl_close($ch);

        if ($api_res_raw) {
            $api_data = json_decode($api_res_raw, true);
            if (!empty($api_data['transactions']) && is_array($api_data['transactions'])) {
                foreach ($api_data['transactions'] as $tx) {
                    $tx_content = $tx['transaction_content'] ?? '';
                    $amount_in = floatval($tx['amount_in'] ?? 0);
                    
                    // Nếu nội dung chuyển khoản chứa mã đơn hàng và là tiền vào
                    if ($amount_in > 0 && stripos($tx_content, $order_code) !== false) {
                        $order_id_int = intval($order['id']);
                        $conn->query("UPDATE orders SET payment_status = 'paid' WHERE id = $order_id_int");
                        $isPaid = true;
                        $order['payment_status'] = 'paid';
                        break;
                    }
                }
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'order_code' => $order['order_code'],
    'payment_method' => $order['payment_method'],
    'payment_status' => $order['payment_status'],
    'status' => $order['status'],
    'paid' => $isPaid,
    'total_money' => floatval($order['total_money']),
    'redirect_url' => 'order-success.php?code=' . urlencode($order['order_code'])
]);
