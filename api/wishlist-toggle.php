<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Vui lòng đăng nhập để lưu sản phẩm yêu thích']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Sản phẩm không hợp lệ']);
    exit;
}

$res = $conn->query("SELECT id FROM wishlists WHERE user_id = $user_id AND product_id = $product_id");
if ($res && $res->num_rows > 0) {
    $conn->query("DELETE FROM wishlists WHERE user_id = $user_id AND product_id = $product_id");
    echo json_encode(['success' => true, 'status' => 'removed', 'action' => 'removed']);
} else {
    $conn->query("INSERT INTO wishlists (user_id, product_id) VALUES ($user_id, $product_id)");
    echo json_encode(['success' => true, 'status' => 'added', 'action' => 'added']);
}
exit;