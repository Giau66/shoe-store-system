<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phương thức không hợp lệ']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    if ($action === 'add') {
        $recipient_name = $conn->real_escape_string(trim($_POST['recipient_name'] ?? ''));
        $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $province_id = intval($_POST['province_id'] ?? 0);
        $address_detail = $conn->real_escape_string(trim($_POST['address_detail'] ?? ''));
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if (empty($recipient_name) || empty($phone) || empty($address_detail) || $province_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Vui lòng nhập đầy đủ thông tin']);
            exit;
        }

        if ($is_default) {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        } else {
            $res_cnt = $conn->query("SELECT COUNT(*) as cnt FROM user_addresses WHERE user_id = $user_id");
            if ($res_cnt && intval($res_cnt->fetch_assoc()['cnt']) === 0) {
                $is_default = 1;
            }
        }

        $conn->query("INSERT INTO user_addresses (user_id, recipient_name, phone, province_id, address_detail, is_default) VALUES ($user_id, '$recipient_name', '$phone', $province_id, '$address_detail', $is_default)");
        echo json_encode(['success' => true]);

    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $recipient_name = $conn->real_escape_string(trim($_POST['recipient_name'] ?? ''));
        $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $province_id = intval($_POST['province_id'] ?? 0);
        $address_detail = $conn->real_escape_string(trim($_POST['address_detail'] ?? ''));
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        $check = $conn->query("SELECT id FROM user_addresses WHERE id = $id AND user_id = $user_id");
        if (!$check || $check->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Địa chỉ không tồn tại hoặc không có quyền']);
            exit;
        }

        if ($is_default) {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        }

        $conn->query("UPDATE user_addresses SET recipient_name = '$recipient_name', phone = '$phone', province_id = $province_id, address_detail = '$address_detail', is_default = $is_default WHERE id = $id AND user_id = $user_id");
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        $check = $conn->query("SELECT is_default FROM user_addresses WHERE id = $id AND user_id = $user_id");
        $addr = $check ? $check->fetch_assoc() : null;
        
        if (!$addr) {
            echo json_encode(['success' => false, 'error' => 'Địa chỉ không tồn tại hoặc không có quyền']);
            exit;
        }

        $conn->query("DELETE FROM user_addresses WHERE id = $id AND user_id = $user_id");

        if ($addr['is_default']) {
            $conn->query("UPDATE user_addresses SET is_default = 1 WHERE user_id = $user_id LIMIT 1");
        }

        echo json_encode(['success' => true]);

    } elseif ($action === 'set_default') {
        $id = intval($_POST['id'] ?? 0);

        $check = $conn->query("SELECT id FROM user_addresses WHERE id = $id AND user_id = $user_id");
        if (!$check || $check->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Địa chỉ không tồn tại hoặc không có quyền']);
            exit;
        }

        $conn->begin_transaction();
        $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        $conn->query("UPDATE user_addresses SET is_default = 1 WHERE id = $id AND user_id = $user_id");
        $conn->commit();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
exit;
