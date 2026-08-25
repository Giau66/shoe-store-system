<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phương thức không hợp lệ']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để đánh giá']);
    exit;
}

$user_id    = intval($_SESSION['user_id']);
$user_role  = $_SESSION['user_role'] ?? 'customer';

// 1. QUẢN TRỊ VIÊN / NHÂN VIÊN KHÔNG ĐƯỢC ĐÁNH GIÁ
if (in_array($user_role, ['admin', 'staff', 'employee'], true)) {
    echo json_encode(['success' => false, 'error' => 'Tài khoản Quản trị viên / Nhân viên không thể thực hiện đánh giá sản phẩm.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating     = isset($_POST['rating'])     ? intval($_POST['rating'])     : 5;
$content    = isset($_POST['content'])    ? trim($_POST['content'])        : '';

if ($product_id <= 0 || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Vui lòng nhập nội dung đánh giá hợp lệ']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $rating = 5;
}

// 2. CHỈ CHO PHÉP ĐÁNH GIÁ NẾU ĐÃ MUA SẢN PHẨM VÀ ĐƠN HÀNG ĐÃ HOÀN THÀNH (COMPLETED)
$chk_order = $conn->query("
    SELECT 1 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    WHERE o.user_id = $user_id 
      AND od.product_id = $product_id 
      AND o.status = 'completed' 
    LIMIT 1
");

if (!$chk_order || $chk_order->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần mua sản phẩm này và hoàn thành đơn hàng mới được phép đánh giá.']);
    exit;
}

// Lấy tên người dùng
$res_u = $conn->query("SELECT fullname FROM users WHERE id = $user_id");
$user_name = 'Khách hàng';
if ($res_u && $u = $res_u->fetch_assoc()) {
    $user_name = $u['fullname'];
}

$u_name_esc  = $conn->real_escape_string($user_name);
$content_esc = $conn->real_escape_string($content);

$sql_ins = "INSERT INTO comments (product_id, user_id, user_name, rating, content, status, created_at) 
            VALUES ($product_id, $user_id, '$u_name_esc', $rating, '$content_esc', 1, NOW())";

if ($conn->query($sql_ins)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Lỗi CSDL: ' . $conn->error]);
}
exit;