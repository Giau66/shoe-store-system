<?php
// config/db.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thiết lập múi giờ Việt Nam (UTC+7) chuẩn toàn hệ thống
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình kết nối Cơ sở dữ liệu (Database Configuration)
// Tự động nạp cấu hình riêng trên Host nếu có file local-config.php
if (file_exists(__DIR__ . '/../local-config.php')) {
    include_once __DIR__ . '/../local-config.php';
}

$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_localhost = ($http_host === 'localhost' || strpos($http_host, '127.0.0.1') !== false || empty($http_host));

if (defined('DB_HOST')) {
    $host     = DB_HOST;
    $username = DB_USER;
    $password = DB_PASS;
    $dbname   = DB_NAME;
} elseif (!$is_localhost) {
    // Tự động nhận diện khi chạy trên Hosting (shoesstore.wuaze.com)
    $host     = getenv('DB_HOST') ?: "sql306.infinityfree.com";
    $username = getenv('DB_USER') ?: "if0_42682393";
    $password = getenv('DB_PASS') ?: (defined('HOST_DB_PASS') ? HOST_DB_PASS : "Shoesstore");
    $dbname   = getenv('DB_NAME') ?: "if0_42682393_shoe_db";
} else {
    // Khi chạy trên máy tính cá nhân (XAMPP localhost)
    $host     = getenv('DB_HOST') ?: "localhost";
    $username = getenv('DB_USER') ?: "root";
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
    $dbname   = getenv('DB_NAME') ?: "web_shoe";
}

try {
    $conn = @new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+07:00'");
} catch (Throwable $e) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #f87171;border-radius:12px;margin:50px auto;max-width:600px;box-shadow:0 10px 25px rgba(0,0,0,0.1);'>
        <h3 style='color:#dc2626;margin-top:0;'>⚠️ Thông báo kết nối CSDL Host</h3>
        <p style='color:#374151;'><b>Chi tiết:</b> " . htmlspecialchars($e->getMessage()) . "</p>
        <p style='color:#6b7280;font-size:14px;'>Đang thử kết nối Host: <code>$host</code> | User: <code>$username</code> | DB: <code>$dbname</code></p>
        <p style='color:#374151;'>Vui lòng mở file <b>config/db.php</b> trên Host điền đúng mật khẩu MySQL của InfinityFree.</p>
    </div>");
}

// Nạp tự động helper tính toán giá sale sự kiện
if (file_exists(__DIR__ . '/../includes/sale_helpers.php')) {
    require_once __DIR__ . '/../includes/sale_helpers.php';
}

// Xóa phiên đăng nhập cũ nếu user_id không còn tồn tại sau khi import/khôi phục CSDL.
// Điều này ngăn lỗi khóa ngoại ở giỏ hàng, yêu thích, địa chỉ và đơn hàng.
if (isset($_SESSION['user_id'])) {
    $session_user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    $session_user = null;
    if ($session_user_id) {
        $stmt_session_user = $conn->prepare(
            "SELECT id, fullname, role, status FROM users WHERE id = ? LIMIT 1"
        );
        if ($stmt_session_user) {
            $stmt_session_user->bind_param('i', $session_user_id);
            $stmt_session_user->execute();
            $session_user = $stmt_session_user->get_result()->fetch_assoc();
            $stmt_session_user->close();
        }
    }

    if (!$session_user || (int)$session_user['status'] !== 1) {
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_role'],
            $_SESSION['cart']
        );
        $_SESSION['flash_error'] = 'Phiên đăng nhập không còn hợp lệ. Vui lòng đăng nhập lại.';
    } else {
        // Kiểm tra nếu tài khoản là nhân viên nhưng đã bị chuyển sang trạng thái "Đã nghỉ việc"
        $is_employee_resigned = false;
        if (in_array($session_user['role'], ['staff', 'employee'])) {
            $stmt_emp_chk = $conn->prepare("SELECT status FROM employees WHERE user_id = ? LIMIT 1");
            if ($stmt_emp_chk) {
                $stmt_emp_chk->bind_param('i', $session_user_id);
                $stmt_emp_chk->execute();
                $emp_row = $stmt_emp_chk->get_result()->fetch_assoc();
                $stmt_emp_chk->close();
                if ($emp_row && (int)$emp_row['status'] === 0) {
                    $is_employee_resigned = true;
                }
            }
        }

        if ($is_employee_resigned) {
            unset(
                $_SESSION['user_id'],
                $_SESSION['user_name'],
                $_SESSION['user_role'],
                $_SESSION['cart']
            );
            $_SESSION['flash_error'] = 'Tài khoản nhân viên này đã nghỉ việc và không còn quyền truy cập hệ thống.';
        } else {
            // Làm mới thông tin quyền từ CSDL để session không bị sai sau khi cập nhật tài khoản.
            $_SESSION['user_id'] = (int)$session_user['id'];
            $_SESSION['user_name'] = $session_user['fullname'];
            $_SESSION['user_role'] = $session_user['role'];
        }
    }
}

// Auto-heal vouchers table brand_id column
try {
    $check_brand_col = $conn->query("SHOW COLUMNS FROM `vouchers` LIKE 'brand_id'");
    if ($check_brand_col && $check_brand_col->num_rows == 0) {
        $conn->query("ALTER TABLE `vouchers` ADD COLUMN `brand_id` INT DEFAULT NULL AFTER `id`");
    }

    // Auto-heal users table citizen_id & address columns if missing
    $check_cit_col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'citizen_id'");
    if ($check_cit_col && $check_cit_col->num_rows == 0) {
        $conn->query("ALTER TABLE `users` ADD COLUMN `citizen_id` VARCHAR(50) DEFAULT NULL AFTER `phone`");
    }
    $check_addr_col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'address'");
    if ($check_addr_col && $check_addr_col->num_rows == 0) {
        $conn->query("ALTER TABLE `users` ADD COLUMN `address` VARCHAR(255) DEFAULT NULL AFTER `citizen_id`");
    }
    $check_bday_col = $conn->query("SHOW COLUMNS FROM `users` LIKE 'birthday'");
    if ($check_bday_col && $check_bday_col->num_rows == 0) {
        $conn->query("ALTER TABLE `users` ADD COLUMN `birthday` DATE DEFAULT NULL AFTER `address`");
    }

    // Auto-heal product_variants unique key for product_id + size
    try {
        $conn->query("ALTER TABLE `product_variants` ADD UNIQUE KEY `product_size_unique` (`product_id`, `size`)");
    } catch (Exception $e_pv) {}

    // Auto-heal employees table avatar column if missing
    $check_emp_av = $conn->query("SHOW COLUMNS FROM `employees` LIKE 'avatar'");
    if ($check_emp_av && $check_emp_av->num_rows == 0) {
        $conn->query("ALTER TABLE `employees` ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL AFTER `address`");
    }

    // Auto-heal employees & employee_schedules tables if missing
    $conn->query("CREATE TABLE IF NOT EXISTS `employees` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT DEFAULT NULL,
      `fullname` VARCHAR(100) NOT NULL,
      `email` VARCHAR(100) DEFAULT NULL,
      `phone` VARCHAR(20) DEFAULT NULL,
      `citizen_id` VARCHAR(20) DEFAULT NULL,
      `address` VARCHAR(255) DEFAULT NULL,
      `avatar` VARCHAR(500) DEFAULT NULL,
      `position` VARCHAR(100) DEFAULT 'Nhân viên bán hàng',
      `work_shift` VARCHAR(100) DEFAULT 'Ca 1 (07:30 - 12:00)',
      `base_salary` DECIMAL(12,0) DEFAULT 5000000,
      `commission_rate` FLOAT DEFAULT 2.5,
      `work_days` INT DEFAULT 26,
      `off_days` INT DEFAULT 0,
      `off_dates_detail` TEXT DEFAULT NULL,
      `bonus` DECIMAL(12,0) DEFAULT 0,
      `bonus_reason` VARCHAR(255) DEFAULT NULL,
      `fine` DECIMAL(12,0) DEFAULT 0,
      `fine_reason` VARCHAR(255) DEFAULT NULL,
      `notes` TEXT DEFAULT NULL,
      `status` TINYINT(1) DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `employee_schedules` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `employee_id` INT NOT NULL,
      `day_of_week` VARCHAR(20) NOT NULL,
      `shift_name` VARCHAR(100) DEFAULT 'Ca Sáng (07:30 - 12:00)',
      `start_time` TIME DEFAULT '07:30:00',
      `end_time` TIME DEFAULT '12:00:00',
      `note` VARCHAR(255) DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

if (!function_exists('autoUpdateHotProducts')) {
    function autoUpdateHotProducts($conn, $top_percent = 15) {
        $conn->query("UPDATE products SET is_hot = 0");

        $total_res = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status = 1");
        $total_products = $total_res ? $total_res->fetch_assoc()['total'] : 0;

        if ($total_products <= 0) return;

        $top_count = ceil(($total_products * $top_percent) / 100);
        if ($top_count < 1) $top_count = 1;

        $sql_top = "SELECT p.id, 
                    (COALESCE(SUM(od.quantity), 0) * 10 + p.view_count) AS hot_score
                    FROM products p
                    LEFT JOIN order_details od ON p.id = od.product_id
                    LEFT JOIN orders o ON od.order_id = o.id AND o.status = 'completed'
                    WHERE p.status = 1
                    GROUP BY p.id
                    ORDER BY hot_score DESC, p.view_count DESC
                    LIMIT $top_count";

        $res_top = $conn->query($sql_top);
        $hot_ids = [];
        if ($res_top) {
            while ($row = $res_top->fetch_assoc()) {
                $hot_ids[] = $row['id'];
            }
        }

        if (!empty($hot_ids)) {
            $ids_string = implode(',', $hot_ids);
            $conn->query("UPDATE products SET is_hot = 1 WHERE id IN ($ids_string)");
        }
    }
}

if (!function_exists('checkAndApplyVoucher')) {
    function checkAndApplyVoucher($conn, $voucher_code, $order_subtotal, $user_id = 0) {
        $voucher_code = $conn->real_escape_string(strtoupper(trim($voucher_code)));
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT * FROM vouchers WHERE code = '$voucher_code' AND status = 1";
        $res = $conn->query($sql);

        if (!$res || $res->num_rows == 0) {
            return ['success' => false, 'message' => 'Mã Voucher không tồn tại hoặc đã bị khóa!'];
        }

        $v = $res->fetch_assoc();

        if ($v['start_date'] && $now < $v['start_date']) {
            return ['success' => false, 'message' => 'Chương trình khuyến mãi chưa bắt đầu!'];
        }
        if ($v['end_date'] && $now > $v['end_date']) {
            return ['success' => false, 'message' => 'Voucher này đã hết hạn sử dụng!'];
        }

        if ($v['usage_limit'] > 0 && $v['used_count'] >= $v['usage_limit']) {
            return ['success' => false, 'message' => 'Voucher này đã hết lượt sử dụng!'];
        }

        if ($order_subtotal < $v['min_order_value']) {
            return ['success' => false, 'message' => 'Đơn hàng chưa đạt mức tối thiểu ' . number_format($v['min_order_value'], 0, ',', '.') . 'đ để áp dụng mã!'];
        }

        if ($user_id > 0) {
            $user_used_res = $conn->query("SELECT COUNT(*) AS total FROM user_vouchers WHERE user_id = $user_id AND voucher_id = " . $v['id'] . " AND used_at IS NOT NULL");
            $user_used_count = $user_used_res ? $user_used_res->fetch_assoc()['total'] : 0;
            if ($user_used_count >= $v['per_user_limit']) {
                return ['success' => false, 'message' => 'Tài khoản của bạn đã dùng hết số lượt cho mã giảm giá này!'];
            }
            
            // Check new_user event type
            if ($v['event_type'] == 'new_user') {
                $order_count_res = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE user_id = $user_id AND status != 'cancelled'");
                $order_count = $order_count_res ? $order_count_res->fetch_assoc()['total'] : 0;
                if ($order_count > 0) {
                    return ['success' => false, 'message' => 'Mã giảm giá này chỉ dành cho khách hàng mới!'];
                }
            }
        } else {
             if ($v['event_type'] == 'new_user') {
                 return ['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng mã dành cho khách hàng mới!'];
             }
        }

        $discount_amount = 0;
        if ($v['discount_type'] == 'fixed') {
            $discount_amount = $v['discount_value'];
        } elseif ($v['discount_type'] == 'percent') {
            $discount_amount = ($order_subtotal * $v['discount_value']) / 100;
            if ($v['max_discount'] > 0 && $discount_amount > $v['max_discount']) {
                $discount_amount = $v['max_discount'];
            }
        } elseif ($v['discount_type'] == 'freeship') {
            $discount_amount = $v['discount_value'];
        }

        if ($discount_amount > $order_subtotal && $v['discount_type'] != 'freeship') {
            $discount_amount = $order_subtotal;
        }

        return [
            'success'         => true,
            'voucher_id'      => $v['id'],
            'voucher_code'    => $v['code'],
            'discount_type'   => $v['discount_type'],
            'discount_amount' => $discount_amount,
            'message'         => 'Áp dụng mã giảm giá thành công! Giảm ' . number_format($discount_amount, 0, ',', '.') . 'đ'
        ];
    }
}

// Nạp helper tính giá Sự kiện Sale toàn hệ thống
require_once __DIR__ . '/../includes/sale_helpers.php';
?>