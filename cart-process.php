<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']) || isset($_GET['ajax']);

// Bắt buộc phải đăng nhập mới thực hiện giỏ hàng
if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'status' => 'unauthorized',
            'message' => 'Vui lòng đăng nhập để thao tác giỏ hàng!',
            'redirect' => 'login.php'
        ]);
        exit;
    }
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!';
    header('Location: login.php');
    exit;
}

// Tạo bảng cart_items nếu chưa có trong DB
$conn->query("
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `user_prod_var` (`user_id`, `product_id`, `variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper tính toán thống kê giỏ hàng cho user (đồng bộ giá Sự Kiện Sale)
function get_user_cart_summary($conn, $user_id) {
    $res = $conn->query("
        SELECT c.product_id, c.quantity, p.price
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = $user_id
    ");
    $total_qty = 0;
    $unique_items = 0;
    $subtotal = 0;
    $pids = [];
    $rows = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
            $pids[] = intval($r['product_id']);
        }
    }
    $sale_map = get_active_sale_events_map($conn, $pids);
    foreach ($rows as $r) {
        $unique_items++;
        $qty = intval($r['quantity']);
        $total_qty += $qty;
        $pid = intval($r['product_id']);
        $price = (isset($sale_map[$pid]) && $sale_map[$pid]['has_sale']) 
            ? floatval($sale_map[$pid]['sale_price']) 
            : floatval($r['price']);
        $subtotal += $price * $qty;
    }
    return [
        'unique_items' => $unique_items,
        'total_qty' => $total_qty,
        'subtotal' => $subtotal,
        'subtotal_formatted' => number_format($subtotal, 0, ',', '.') . 'đ'
    ];
}

if ($action === 'add_to_cart' || $action === 'buy_now') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $quantity   = intval($_POST['quantity'] ?? 1);

    if ($product_id <= 0 || $quantity <= 0) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Thông tin sản phẩm không hợp lệ.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Thông tin sản phẩm không hợp lệ.';
        $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header('Location: ' . $redirect);
        exit;
    }

    // Nếu người dùng chưa chọn size -> tự chọn variant còn hàng đầu tiên
    if ($variant_id <= 0) {
        $res_first_v = $conn->query("SELECT id FROM product_variants WHERE product_id = $product_id AND stock_quantity > 0 ORDER BY size ASC LIMIT 1");
        if ($res_first_v && $fv = $res_first_v->fetch_assoc()) {
            $variant_id = intval($fv['id']);
        }
    }

    if ($variant_id <= 0) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sản phẩm này tạm thời hết hàng tất cả các size.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Sản phẩm này tạm thời hết hàng tất cả các size.';
        $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'product-detail.php?id=' . $product_id;
        header('Location: ' . $redirect);
        exit;
    }

    // Kiểm tra sản phẩm có tồn tại
    $res_p = $conn->query("SELECT name FROM products WHERE id = $product_id AND status = 1");
    if (!$res_p || $res_p->num_rows === 0) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.';
        header('Location: index.php');
        exit;
    }

    // Kiểm tra số lượng tồn kho của variant
    $res_v = $conn->query("SELECT stock_quantity FROM product_variants WHERE id = $variant_id AND product_id = $product_id");
    $variant = $res_v ? $res_v->fetch_assoc() : null;

    if (!$variant) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Phân loại sản phẩm không hợp lệ.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Phân loại sản phẩm không hợp lệ.';
        header('Location: product-detail.php?id=' . $product_id);
        exit;
    }

    // Kiểm tra số lượng hiện tại đã có trong giỏ CSDL của user
    $res_curr = $conn->query("SELECT quantity FROM cart_items WHERE user_id = $user_id AND product_id = $product_id AND variant_id = $variant_id");
    $current_qty = 0;
    if ($res_curr && $c = $res_curr->fetch_assoc()) {
        $current_qty = intval($c['quantity']);
    }

    $stock_qty = intval($variant['stock_quantity']);
    if (($current_qty + $quantity) > $stock_qty) {
        $msg = "Số lượng trong kho không đủ (Chỉ còn $stock_qty đôi).";
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['flash_error'] = $msg;
        header('Location: product-detail.php?id=' . $product_id);
        exit;
    }

    try {
        $stmt_cart = $conn->prepare("
            INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt_cart->bind_param('iiii', $user_id, $product_id, $variant_id, $quantity);
        $stmt_cart->execute();
        $stmt_cart->close();
    } catch (Throwable $e) {
        error_log('cart-process add_to_cart: ' . $e->getMessage());
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật giỏ hàng.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Không thể cập nhật giỏ hàng.';
        header('Location: login.php');
        exit;
    }

    $summary = get_user_cart_summary($conn, $user_id);

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng!',
            'cart_count' => $summary['total_qty'],
            'unique_items' => $summary['unique_items'],
            'subtotal' => $summary['subtotal'],
            'subtotal_formatted' => $summary['subtotal_formatted'],
            'redirect_url' => ($action === 'buy_now') ? 'checkout.php' : 'cart.php'
        ]);
        exit;
    }

    if ($action !== 'buy_now') {
        $_SESSION['flash_success'] = 'Thêm vào giỏ hàng thành công.';
    }
    if ($action === 'buy_now') {
        header('Location: checkout.php');
    } else {
        header('Location: cart.php');
    }
    exit;
}

if ($action === 'update_quantity') {
    $item_id  = intval($_POST['item_id'] ?? $_POST['cart_index'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($item_id > 0) {
        if ($quantity <= 0) {
            $conn->query("DELETE FROM cart_items WHERE id = $item_id AND user_id = $user_id");
            $msg = 'Đã xóa sản phẩm khỏi giỏ hàng.';
            $_SESSION['flash_success'] = $msg;
        } else {
            // Lấy thông tin variant của item này
            $res_ci = $conn->query("
                SELECT c.variant_id, c.product_id, v.stock_quantity, p.price 
                FROM cart_items c 
                JOIN product_variants v ON c.variant_id = v.id 
                JOIN products p ON c.product_id = p.id
                WHERE c.id = $item_id AND c.user_id = $user_id
            ");
            if ($res_ci && $ci = $res_ci->fetch_assoc()) {
                $stock = intval($ci['stock_quantity']);
                $sale_item_info = get_active_sale_event_for_product($conn, $ci['product_id'], $ci['price']);
                $item_price = $sale_item_info['has_sale'] ? floatval($sale_item_info['sale_price']) : floatval($ci['price']);

                if ($quantity > $stock) {
                    $err_msg = "Số lượng yêu cầu vượt quá tồn kho (Tồn kho: $stock đôi).";
                    if ($is_ajax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => false,
                            'message' => $err_msg,
                            'max_stock' => $stock
                        ]);
                        exit;
                    }
                    $_SESSION['flash_error'] = $err_msg;
                } else {
                    $conn->query("UPDATE cart_items SET quantity = $quantity WHERE id = $item_id AND user_id = $user_id");
                    $msg = 'Cập nhật số lượng thành công.';
                    $_SESSION['flash_success'] = $msg;

                    if ($is_ajax) {
                        $summary = get_user_cart_summary($conn, $user_id);
                        $line_total = $item_price * $quantity;
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => true,
                            'message' => $msg,
                            'item_id' => $item_id,
                            'quantity' => $quantity,
                            'line_total' => $line_total,
                            'line_total_formatted' => number_format($line_total, 0, ',', '.') . 'đ',
                            'cart_count' => $summary['total_qty'],
                            'unique_items' => $summary['unique_items'],
                            'subtotal' => $summary['subtotal'],
                            'subtotal_formatted' => $summary['subtotal_formatted']
                        ]);
                        exit;
                    }
                }
            }
        }
    }

    if ($is_ajax) {
        $summary = get_user_cart_summary($conn, $user_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $msg ?? 'Đã cập nhật giỏ hàng.',
            'item_id' => $item_id,
            'cart_count' => $summary['total_qty'],
            'unique_items' => $summary['unique_items'],
            'subtotal' => $summary['subtotal'],
            'subtotal_formatted' => $summary['subtotal_formatted'],
            'is_empty' => ($summary['unique_items'] === 0)
        ]);
        exit;
    }

    header('Location: cart.php');
    exit;
}

if ($action === 'remove_item') {
    $item_id = intval($_POST['item_id'] ?? $_POST['cart_index'] ?? $_GET['item_id'] ?? 0);
    if ($item_id > 0) {
        $conn->query("DELETE FROM cart_items WHERE id = $item_id AND user_id = $user_id");
        $msg = 'Đã xóa sản phẩm khỏi giỏ hàng.';
        $_SESSION['flash_success'] = $msg;
    }

    if ($is_ajax) {
        $summary = get_user_cart_summary($conn, $user_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $msg ?? 'Đã xóa sản phẩm.',
            'item_id' => $item_id,
            'cart_count' => $summary['total_qty'],
            'unique_items' => $summary['unique_items'],
            'subtotal' => $summary['subtotal'],
            'subtotal_formatted' => $summary['subtotal_formatted'],
            'is_empty' => ($summary['unique_items'] === 0)
        ]);
        exit;
    }

    header('Location: cart.php');
    exit;
}

if ($action === 'clear_cart') {
    $conn->query("DELETE FROM cart_items WHERE user_id = $user_id");
    $msg = 'Đã làm trống giỏ hàng.';
    $_SESSION['flash_success'] = $msg;

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'cart_count' => 0,
            'unique_items' => 0,
            'subtotal' => 0,
            'subtotal_formatted' => '0đ',
            'is_empty' => true
        ]);
        exit;
    }

    header('Location: cart.php');
    exit;
}

header('Location: cart.php');
exit;