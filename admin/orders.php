<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/CarrierShippingService.php';

// 1. AJAX: ĐẨY ĐƠN SANG GHTK / GHN (1-CLICK PUSH ORDER)
if (isset($_POST['ajax_push_carrier_order'])) {
    header('Content-Type: application/json; charset=utf-8');
    $order_id = intval($_POST['order_id'] ?? 0);
    $svc = new CarrierShippingService($conn);
    $res = $svc->createCarrierOrder($order_id);
    if (!empty($res['success'])) {
        $fresh = $conn->query("SELECT confirmed_at, shipping_at, status, payment_status, payment_method FROM orders WHERE id = $order_id")->fetch_assoc();
        $res['status'] = $fresh['status'] ?? 'shipping';
        $res['status_badge'] = getOrderStatusBadgeHtml($res['status']);
        $res['payment_badge'] = getPaymentStatusBadgeHtml($fresh['payment_status'] ?? 'unpaid', $fresh['payment_method'] ?? 'COD');
        $res['shipping_at'] = $fresh['shipping_at'] ?? date('Y-m-d H:i:s');
        $res['confirmed_at'] = $fresh['confirmed_at'] ?? null;
    }
    echo json_encode($res);
    exit();
}

// 2. AJAX: TRA CỨU HÀNH TRÌNH VẬN ĐƠN (LIVE TRACKING)
if (isset($_POST['ajax_track_carrier_order'])) {
    header('Content-Type: application/json; charset=utf-8');
    $code = trim($_POST['tracking_code'] ?? '');
    $svc = new CarrierShippingService($conn);
    $res = $svc->trackShipment($code);
    echo json_encode($res);
    exit();
}

// 3. AJAX: GIẢ LẬP CHUYỂN TRẠM BƯU CỤC (HUB-TO-HUB SIMULATOR)
if (isset($_POST['ajax_update_carrier_step'])) {
    header('Content-Type: application/json; charset=utf-8');
    $code = trim($_POST['tracking_code'] ?? '');
    $step = intval($_POST['step'] ?? 1);
    $svc = new CarrierShippingService($conn);
    $res = $svc->updateCarrierStep($code, $step);
    $track = $svc->trackShipment($code);
    $track['message']        = $res['message'];
    $track['order_id']       = $res['order_id'] ?? 0;
    $track['order_code']     = $res['order_code'] ?? '';
    $track['order_status']   = $res['order_status'] ?? 'shipping';
    $track['payment_status'] = $res['payment_status'] ?? 'unpaid';
    $track['completed_at']   = $res['completed_at'] ?? null;
    $track['status_badge']   = getOrderStatusBadgeHtml($track['order_status']);
    $track['payment_badge']  = getPaymentStatusBadgeHtml($track['payment_status'], 'COD');
    $track['carrier_status_text'] = $res['status_text'] ?? '';
    echo json_encode($track);
    exit();
}

// 4. AJAX: XÁC NHẬN NHANH ĐƠN HÀNG (TỰ ĐỘNG GÁN TÀI KHOẢN ĐANG ĐĂNG NHẬP LÀM NGƯỜI LẬP PHIẾU)
if (isset($_POST['ajax_quick_confirm_order'])) {
    header('Content-Type: application/json; charset=utf-8');
    $order_id = intval($_POST['order_id'] ?? 0);
    $curr_staff_id = intval($_SESSION['user_id'] ?? 0);

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ!']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, order_code, status, staff_id FROM orders WHERE id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $ord = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ord) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng!']);
        exit();
    }

    if ($ord['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => "Đơn hàng #{$ord['order_code']} không ở trạng thái Chờ xác nhận (hiện tại: {$ord['status']})."]);
        exit();
    }

    $target_staff = ($curr_staff_id > 0) ? $curr_staff_id : (($ord['staff_id'] > 0) ? intval($ord['staff_id']) : null);

    $up = $conn->prepare("UPDATE orders SET status = 'confirmed', staff_id = ?, confirmed_at = IFNULL(confirmed_at, NOW()) WHERE id = ? AND status = 'pending'");
    $up->bind_param('ii', $target_staff, $order_id);
    $up->execute();
    $up->close();

    // Lấy tên nhân viên và thời gian xác nhận để trả về frontend
    $staff_name = 'Chưa phân công';
    if ($target_staff > 0) {
        $sq = $conn->prepare("SELECT fullname FROM users WHERE id = ? LIMIT 1");
        $sq->bind_param('i', $target_staff);
        $sq->execute();
        $srow = $sq->get_result()->fetch_assoc();
        $sq->close();
        if ($srow) $staff_name = $srow['fullname'];
    }
    $fresh = $conn->query("SELECT confirmed_at FROM orders WHERE id = $order_id")->fetch_assoc();

    echo json_encode([
        'success'      => true,
        'message'      => "Đã xác nhận đơn hàng #{$ord['order_code']} thành công! Người lập phiếu đã được cập nhật theo tài khoản của bạn.",
        'order_id'     => $order_id,
        'order_code'   => $ord['order_code'],
        'staff_id'     => $target_staff,
        'staff_name'   => $staff_name,
        'confirmed_at' => $fresh['confirmed_at'] ?? null,
        'status_badge' => getOrderStatusBadgeHtml('confirmed'),
        'payment_badge' => getPaymentStatusBadgeHtml('unpaid', $ord['payment_method'] ?? 'COD')
    ]);
    exit();
}

// 4b. AJAX: TỪ CHỐI NHANH ĐƠN HÀNG (TỰ ĐỘNG HOÀN KHO & HOÀN TIỀN NẾU ĐÃ THANH TOÁN QR)
if (isset($_POST['ajax_quick_reject_order'])) {
    header('Content-Type: application/json; charset=utf-8');
    $order_id = intval($_POST['order_id'] ?? 0);
    $reject_reason = trim($_POST['reject_reason'] ?? '');
    $curr_staff_id = intval($_SESSION['user_id'] ?? 0);

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ!']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id, order_code, status, payment_status, payment_method, staff_id FROM orders WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $ord = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ord) throw new RuntimeException('Không tìm thấy đơn hàng trong CSDL.');
        if ($ord['status'] === 'cancelled') throw new RuntimeException("Đơn hàng #{$ord['order_code']} đã ở trạng thái Đã hủy trước đó.");

        $target_staff = ($curr_staff_id > 0) ? $curr_staff_id : (($ord['staff_id'] > 0) ? intval($ord['staff_id']) : null);
        $old_payment_status = $ord['payment_status'] ?? 'unpaid';
        $old_payment_method = $ord['payment_method'] ?? 'COD';

        // Xác định trạng thái thanh toán mới khi từ chối:
        // Nếu đã thanh toán (QR hoặc paid) -> ĐÃ HOÀN TIỀN (refunded)
        $new_payment_status = ($old_payment_status === 'paid' || $old_payment_method === 'BANKING_QR' || $old_payment_status == 1 || $old_payment_status === '1') ? 'refunded' : 'unpaid';
        $reason_text = !empty($reject_reason) ? $reject_reason : 'Shop từ chối đơn hàng';

        // Hoàn lại số lượng tồn kho sản phẩm và giảm sold_count
        $stmt_items = $conn->prepare('SELECT product_id, variant_id, quantity FROM order_details WHERE order_id = ?');
        $stmt_items->bind_param('i', $order_id);
        $stmt_items->execute();
        $items = $stmt_items->get_result();
        while ($it = $items->fetch_assoc()) {
            $pid = (int)$it['product_id']; $vid = (int)$it['variant_id']; $qty = (int)$it['quantity'];
            if ($vid > 0) $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity + $qty WHERE id = $vid AND product_id = $pid");
            $conn->query("UPDATE products SET sold_count = GREATEST(0, sold_count - $qty) WHERE id = $pid");
        }
        $stmt_items->close();

        // Cập nhật trạng thái đơn hàng thành cancelled, payment_status, cancel_reason, staff_id, cancelled_at
        $up = $conn->prepare("UPDATE orders SET status = 'cancelled', payment_status = ?, cancel_reason = ?, staff_id = ?, cancelled_at = IFNULL(cancelled_at, NOW()) WHERE id = ?");
        $up->bind_param('ssii', $new_payment_status, $reason_text, $target_staff, $order_id);
        $up->execute();
        $up->close();

        $conn->commit();

        // Lấy tên nhân viên lập phiếu
        $staff_name = 'Chưa phân công';
        if ($target_staff > 0) {
            $sq = $conn->prepare("SELECT fullname FROM users WHERE id = ? LIMIT 1");
            $sq->bind_param('i', $target_staff);
            $sq->execute();
            $srow = $sq->get_result()->fetch_assoc();
            $sq->close();
            if ($srow) $staff_name = $srow['fullname'];
        }

        $fresh = $conn->query("SELECT cancelled_at FROM orders WHERE id = $order_id")->fetch_assoc();

        $refund_note = ($new_payment_status === 'refunded') ? ' (Đã chuyển trạng thái Đã Hoàn Tiền)' : '';

        echo json_encode([
            'success'            => true,
            'message'            => "Đã từ chối đơn hàng #{$ord['order_code']} thành công! Sản phẩm đã được nhập lại kho{$refund_note}.",
            'order_id'           => $order_id,
            'order_code'         => $ord['order_code'],
            'status'             => 'cancelled',
            'payment_status'     => $new_payment_status,
            'reject_reason'      => $reason_text,
            'staff_id'           => $target_staff,
            'staff_name'         => $staff_name,
            'cancelled_at'       => $fresh['cancelled_at'] ?? null,
            'status_badge'       => getOrderStatusBadgeHtml('cancelled'),
            'payment_badge'      => getPaymentStatusBadgeHtml($new_payment_status, $old_payment_method)
        ]);
        exit();
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit();
    }
}

// 5. AJAX: XỬ LÝ YÊU CẦU HOÀN TRẢ (DUYỆT HOÀN TIỀN & NHẬP KHO HOẶC TỪ CHỐI)
if (isset($_POST['ajax_handle_return_order'])) {
    header('Content-Type: application/json; charset=utf-8');
    $order_id = intval($_POST['order_id'] ?? 0);
    $decision = trim($_POST['decision'] ?? ''); // 'accept' hoặc 'reject'
    $curr_staff_id = intval($_SESSION['user_id'] ?? 0);

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ!']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id, order_code, status, total_money, staff_id FROM orders WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $ord = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ord) throw new RuntimeException('Không tìm thấy đơn hàng trong CSDL.');
        if ($ord['status'] !== 'returning') throw new RuntimeException('Đơn hàng không ở trạng thái Yêu cầu hoàn trả (hiện tại: ' . $ord['status'] . ').');

        $target_staff = ($curr_staff_id > 0) ? $curr_staff_id : (($ord['staff_id'] > 0) ? intval($ord['staff_id']) : null);

        if ($decision === 'accept') {
            // Chấp nhận hoàn trả: Hoàn lại số lượng tồn kho và giảm sold_count
            $stmt_items = $conn->prepare('SELECT product_id, variant_id, quantity FROM order_details WHERE order_id = ?');
            $stmt_items->bind_param('i', $order_id);
            $stmt_items->execute();
            $items = $stmt_items->get_result();
            while ($it = $items->fetch_assoc()) {
                $pid = (int)$it['product_id']; $vid = (int)$it['variant_id']; $qty = (int)$it['quantity'];
                if ($vid > 0) $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity + $qty WHERE id = $vid AND product_id = $pid");
                $conn->query("UPDATE products SET sold_count = GREATEST(0, sold_count - $qty) WHERE id = $pid");
            }
            $stmt_items->close();

            $up = $conn->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'refunded', staff_id = ?, cancelled_at = IFNULL(cancelled_at, NOW()) WHERE id = ?");
            $up->bind_param('ii', $target_staff, $order_id);
            $up->execute();
            $up->close();

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "Đã duyệt hoàn trả cho đơn hàng #{$ord['order_code']}! Sản phẩm đã được nhập lại vào kho và trạng thái thanh toán chuyển sang Đã hoàn tiền."
            ]);
        } elseif ($decision === 'reject') {
            // Từ chối hoàn trả: khôi phục về completed
            $up = $conn->prepare("UPDATE orders SET status = 'completed', staff_id = ? WHERE id = ?");
            $up->bind_param('ii', $target_staff, $order_id);
            $up->execute();
            $up->close();

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "Đã từ chối yêu cầu hoàn trả cho đơn hàng #{$ord['order_code']}! Trạng thái đơn được giữ nguyên là Đã giao thành công."
            ]);
        } else {
            throw new RuntimeException('Quyết định xử lý hoàn trả không hợp lệ.');
        }
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit();
}

// 6. AJAX HOẶC POST: CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG, PHÂN CÔNG NV & HOA HỒNG (ZERO RELOAD)
function getOrderStatusBadgeHtml($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark border border-warning fw-bold">⏳ Chờ xác nhận</span>';
        case 'confirmed':
            return '<span class="badge bg-info text-dark border border-info fw-bold">⚙️ Đã xác nhận</span>';
        case 'shipping':
            return '<span class="badge bg-primary border border-primary fw-bold">🚚 Đang giao hàng</span>';
        case 'completed':
            return '<span class="badge bg-success border border-success fw-bold">✅ Hoàn thành</span>';
        case 'returning':
            return '<span class="badge bg-danger text-white border border-danger fw-bold"><i class="fa-solid fa-rotate-left me-1"></i>Yêu cầu hoàn trả</span>';
        case 'cancelled':
        default:
            return '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">❌ Đã hủy / Từ chối</span>';
    }
}

function getPaymentStatusBadgeHtml($payment_status, $payment_method = 'COD') {
    $method_text = ($payment_method === 'BANKING_QR') ? '⚡ VietQR' : '💵 COD';
    if ($payment_status === 'paid' || $payment_status == 1 || $payment_status === '1') {
        $badge = '<span class="badge bg-success-subtle text-success border border-success fw-bold">✅ Đã thanh toán</span>';
    } elseif ($payment_status === 'refunded') {
        $badge = '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">↩️ Đã hoàn tiền</span>';
    } else {
        $badge = '<span class="badge bg-warning-subtle text-dark border border-warning fw-bold">⏳ Chưa TT</span>';
    }
    return $badge . '<small class="d-block text-muted mt-1" style="font-size: 11px;">' . $method_text . '</small>';
}

if (isset($_POST['ajax_update_order_status']) || isset($_POST['update_order_status'])) {
    $is_ajax        = isset($_POST['ajax_update_order_status']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    $order_id       = intval($_POST['order_id'] ?? 0);
    $status         = $_POST['status'] ?? 'pending';
    $payment_status = $_POST['payment_status'] ?? 'unpaid';
    $staff_id_param = intval($_POST['staff_id'] ?? 0);

    $allowed_statuses = ['pending', 'confirmed', 'shipping', 'completed', 'returning', 'cancelled'];
    $allowed_payment_statuses = ['unpaid', 'paid', 'refunded'];

    if (!in_array($status, $allowed_statuses, true)) $status = 'pending';
    if (!in_array($payment_status, $allowed_payment_statuses, true)) $payment_status = 'unpaid';

    if ($order_id <= 0) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ.']);
            exit();
        }
        $err = 'Mã đơn hàng không hợp lệ.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('SELECT status, payment_method, payment_status, total_money, order_code, staff_id FROM orders WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$old) throw new RuntimeException('Không tìm thấy đơn hàng trong CSDL.');
            $old_status         = $old['status'];
            $old_method         = $old['payment_method'] ?? 'COD';
            $old_payment_status = $old['payment_status'] ?? 'unpaid';
            $order_code         = $old['order_code'];
            $total_money        = floatval($old['total_money'] ?? 0);

            // Tự động gán tài khoản đang đăng nhập duyệt đơn làm người lập phiếu nếu chưa chọn hoặc khi xác nhận đơn
            $current_user_id = intval($_SESSION['user_id'] ?? 0);
            if ($staff_id_param <= 0) {
                if (in_array($status, ['confirmed', 'shipping', 'completed'], true) && $current_user_id > 0 && empty($old['staff_id'])) {
                    $staff_id_param = $current_user_id;
                } elseif (!empty($old['staff_id'])) {
                    $staff_id_param = intval($old['staff_id']);
                } elseif ($current_user_id > 0) {
                    $staff_id_param = $current_user_id;
                }
            }

            // 1. ĐÃ NHẬN HÀNG (Hoàn thành) -> Luôn là ĐÃ THANH TOÁN
            if ($status === 'completed') {
                $payment_status = 'paid';
            }
            // 2. YÊU CẦU TRẢ HÀNG / HOÀN TRẢ -> Luôn là ĐÃ HOÀN TIỀN
            elseif ($status === 'returning') {
                $payment_status = 'refunded';
            }
            // 3. ĐƠN HỦY: Nếu là đơn QR HOÀN TIỀN
            elseif ($status === 'cancelled') {
                if ($old_method === 'BANKING_QR' || $old_payment_status === 'paid' || $payment_status === 'paid') {
                    $payment_status = 'refunded';
                } else {
                    $payment_status = 'unpaid';
                }
            }
            // 4. Nếu là đơn chuyển khoản QR và được xác nhận / giao hàng -> ĐÃ THANH TOÁN
            elseif ($old_method === 'BANKING_QR' && in_array($status, ['confirmed', 'shipping', 'completed'], true)) {
                $payment_status = 'paid';
            }

            // Chỉ hoàn kho ở lần đầu chuyển sang cancelled
            if ($status === 'cancelled' && $old_status !== 'cancelled') {
                $stmt_items = $conn->prepare('SELECT product_id, variant_id, quantity FROM order_details WHERE order_id = ?');
                $stmt_items->bind_param('i', $order_id);
                $stmt_items->execute();
                $items = $stmt_items->get_result();
                while ($it = $items->fetch_assoc()) {
                    $pid = (int)$it['product_id']; $vid = (int)$it['variant_id']; $qty = (int)$it['quantity'];
                    if ($vid > 0) $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity + $qty WHERE id = $vid AND product_id = $pid");
                    $conn->query("UPDATE products SET sold_count = GREATEST(0, sold_count - $qty) WHERE id = $pid");
                }
                $stmt_items->close();
            }

            // Nếu phục hồi đơn đã hủy, trừ lại tồn kho
            if ($old_status === 'cancelled' && $status !== 'cancelled') {
                $stmt_items = $conn->prepare('SELECT od.product_id, od.variant_id, od.quantity, p.name, v.stock_quantity FROM order_details od JOIN products p ON p.id=od.product_id JOIN product_variants v ON v.id=od.variant_id WHERE od.order_id=? FOR UPDATE');
                $stmt_items->bind_param('i', $order_id);
                $stmt_items->execute();
                $items = $stmt_items->get_result();
                while ($it = $items->fetch_assoc()) {
                    if ((int)$it['stock_quantity'] < (int)$it['quantity']) throw new RuntimeException('Không đủ tồn kho để phục hồi đơn: ' . $it['name']);
                    $pid = (int)$it['product_id']; $vid = (int)$it['variant_id']; $qty = (int)$it['quantity'];
                    if ($vid > 0) $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity - $qty WHERE id = $vid AND product_id = $pid");
                    $conn->query("UPDATE products SET sold_count = sold_count + $qty WHERE id = $pid");
                }
                $stmt_items->close();
            }

            // Cập nhật mốc thời gian tự động
            $confirmed_sql = in_array($status, ['confirmed', 'shipping', 'completed'], true) ? "COALESCE(confirmed_at, NOW())" : "confirmed_at";
            $shipping_sql  = in_array($status, ['shipping', 'completed'], true) ? "COALESCE(shipping_at, NOW())" : "shipping_at";
            $completed_sql = ($status === 'completed') ? "COALESCE(completed_at, NOW())" : "completed_at";
            $cancelled_sql = ($status === 'cancelled') ? "COALESCE(cancelled_at, NOW())" : "cancelled_at";

            $staff_id_val = ($staff_id_param > 0) ? $staff_id_param : null;

            $stmt_up = $conn->prepare("
                UPDATE orders 
                SET status = ?, 
                    payment_status = ?, 
                    staff_id = ?, 
                    confirmed_at = $confirmed_sql, 
                    shipping_at = $shipping_sql, 
                    completed_at = $completed_sql, 
                    cancelled_at = $cancelled_sql 
                WHERE id = ?
            ");
            $stmt_up->bind_param('ssii', $status, $payment_status, $staff_id_val, $order_id);
            $stmt_up->execute();
            $stmt_up->close();

            // Lấy tên nhân viên lập phiếu
            $staff_name = 'Chưa phân công';
            if ($staff_id_val > 0) {
                $staff_q = $conn->query("SELECT fullname FROM users WHERE id = $staff_id_val LIMIT 1");
                if ($staff_q && $st_r = $staff_q->fetch_assoc()) {
                    $staff_name = $st_r['fullname'];
                }
            }

            // TỰ ĐỘNG TÍNH & CỘNG HOA HỒNG (3% CHO NV KHI HOÀN THÀNH)
            if ($status === 'completed' && $old_status !== 'completed' && $staff_id_val > 0) {
                $emp_info = $conn->query("SELECT id, fullname, commission_rate FROM employees WHERE user_id = $staff_id_val OR id = $staff_id_val LIMIT 1")->fetch_assoc();
                if ($emp_info) {
                    $raw_comm   = floatval($emp_info['commission_rate'] ?? 3.0);
                    $rate_mult  = ($raw_comm > 1) ? ($raw_comm / 100) : ($raw_comm > 0 ? $raw_comm : 0.03);
                    $commission = round($total_money * $rate_mult);
                    $emp_id_db  = intval($emp_info['id']);
                    
                    if ($commission > 0) {
                        $reason_str = "Hoa hồng đơn #$order_code (+" . number_format($commission, 0, ',', '.') . "đ)";
                        $conn->query("UPDATE employees SET bonus = bonus + $commission, bonus_reason = CONCAT(COALESCE(bonus_reason, ''), '; $reason_str') WHERE id = $emp_id_db");
                    }
                }
            }

            $conn->commit();

            // Lấy lại các mốc thời gian sau cập nhật
            $fresh_ord = $conn->query("SELECT confirmed_at, shipping_at, completed_at, cancelled_at FROM orders WHERE id = $order_id")->fetch_assoc();

            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'            => true,
                    'message'            => "Đã cập nhật đơn hàng #$order_code thành công!",
                    'order_id'           => $order_id,
                    'order_code'         => $order_code,
                    'status'             => $status,
                    'status_badge'       => getOrderStatusBadgeHtml($status),
                    'payment_status'     => $payment_status,
                    'payment_badge'      => getPaymentStatusBadgeHtml($payment_status, $old_method),
                    'staff_id'           => $staff_id_val,
                    'staff_name'         => $staff_name,
                    'confirmed_at'       => $fresh_ord['confirmed_at'] ?? null,
                    'shipping_at'        => $fresh_ord['shipping_at'] ?? null,
                    'completed_at'       => $fresh_ord['completed_at'] ?? null,
                    'cancelled_at'       => $fresh_ord['cancelled_at'] ?? null
                ]);
                exit();
            }

            $msg = "Đã cập nhật đơn hàng <strong>#$order_code</strong> thành công!";
        } catch (Throwable $e) {
            $conn->rollback(); 
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Không thể cập nhật đơn hàng: ' . $e->getMessage()]);
                exit();
            }
            $err = 'Không thể cập nhật đơn hàng: ' . $e->getMessage();
        }
    }
}

// ========================================================
// 2. XỬ LÝ BỘ LỌC VÀ TÌM KIẾM
// ========================================================
$where_clauses = ["1=1"];
$search_query          = isset($_GET['search']) ? addslashes(trim($_GET['search'])) : '';
$filter_status         = isset($_GET['status']) ? addslashes($_GET['status']) : '';
$filter_customer_id    = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$filter_payment_method = isset($_GET['payment_method']) ? addslashes($_GET['payment_method']) : '';
$filter_payment_status = isset($_GET['payment_status']) ? addslashes($_GET['payment_status']) : '';
$date_start            = isset($_GET['date_start']) ? addslashes($_GET['date_start']) : '';
$date_end              = isset($_GET['date_end']) ? addslashes($_GET['date_end']) : '';

if ($search_query !== '') {
    $where_clauses[] = "(o.order_code LIKE '%$search_query%' OR o.customer_name LIKE '%$search_query%' OR o.phone LIKE '%$search_query%')";
}
if ($filter_status !== '') {
    if ($filter_status === 'returning') {
        $where_clauses[] = "(o.status = 'returning' OR (o.status = 'cancelled' AND (o.payment_status = 'refunded' OR (o.return_reason IS NOT NULL AND o.return_reason != ''))))";
    } elseif ($filter_status === 'cancelled') {
        $where_clauses[] = "(o.status = 'cancelled' AND o.payment_status != 'refunded' AND (o.return_reason IS NULL OR o.return_reason = ''))";
    } else {
        $where_clauses[] = "o.status = '$filter_status'";
    }
}
if ($filter_customer_id > 0) {
    $where_clauses[] = "o.user_id = $filter_customer_id";
}
if ($filter_payment_method !== '') {
    $where_clauses[] = "o.payment_method = '$filter_payment_method'";
}
if ($filter_payment_status !== '') {
    $where_clauses[] = "o.payment_status = '$filter_payment_status'";
}
if ($date_start !== '') {
    $where_clauses[] = "DATE(o.created_at) >= '$date_start'";
}
if ($date_end !== '') {
    $where_clauses[] = "DATE(o.created_at) <= '$date_end'";
}

$where_sql = implode(' AND ', $where_clauses);

// Lấy danh sách khách hàng để đưa vào bộ lọc
$customers_list = [];
$c_res = $conn->query("SELECT id, fullname, phone, email FROM users WHERE role = 'customer' ORDER BY fullname ASC");
if ($c_res) {
    while($c_row = $c_res->fetch_assoc()) {
        $customers_list[] = $c_row;
    }
}

// Lấy danh sách nhân viên để chọn phân công xác nhận đơn
$staff_list = [];
$st_res = $conn->query("
    SELECT e.id as emp_id, e.user_id, e.fullname, e.position, e.commission_rate 
    FROM employees e 
    WHERE e.status = 1 
    ORDER BY e.fullname ASC
");
if ($st_res) {
    while($st_row = $st_res->fetch_assoc()) {
        $staff_list[] = $st_row;
    }
}

// ========================================================
// 3. TRUY VẤN DANH SÁCH HÓA ĐƠN
// ========================================================
$sql_orders = "SELECT o.*, u.fullname as staff_name, e.fullname as emp_name
               FROM orders o
               LEFT JOIN users u ON o.staff_id = u.id
               LEFT JOIN employees e ON (o.staff_id = e.user_id OR o.staff_id = e.id)
               WHERE $where_sql
               ORDER BY o.id DESC";
$orders_res = $conn->query($sql_orders);

// Nạp danh sách món hàng cho hóa đơn
$order_items = [];
$res_items = $conn->query("SELECT od.*, p.main_image 
                           FROM order_details od JOIN products p ON od.product_id = p.id");
if ($res_items) {
    while($item = $res_items->fetch_assoc()) {
        $order_items[$item['order_id']][] = $item;
    }
}

$msg = $_SESSION['flash_success'] ?? '';
$err = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

include_once 'includes/header.php';
?>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-file-invoice-dollar me-2" style="color: var(--active-sage);"></i>Quản Lý Hóa Đơn &amp; Tiến Trình Giao Hàng
        </h4>
        <span class="text-muted small">Cập nhật trạng thái đơn hàng, phân công nhân viên lập phiếu &amp; in hóa đơn đã xác nhận.</span>
    </div>
</div>

<!-- THÔNG BÁO -->
<?php if (!empty($msg)): ?><div class="alert alert-success shadow-sm fw-bold no-print"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if (!empty($err)): ?><div class="alert alert-danger shadow-sm fw-bold no-print"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($err); ?></div><?php endif; ?>

<!-- BỘ LỌC TÌM KIẾM NÂNG CAO (2 HÀNG RÕ RÀNG) -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 no-print">
    <form method="GET" class="row g-3 align-items-end">
        <!-- Hàng 1: Tìm kiếm, Khách hàng, Phương thức thanh toán -->
        <div class="col-12 col-md-4">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Tìm kiếm</label>
            <input type="text" name="search" class="form-control" placeholder="Mã đơn, Tên khách hàng, SĐT..." value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-user me-1 text-info"></i>Lọc theo Khách hàng</label>
            <select name="customer_id" class="form-select">
                <option value="">-- Tất cả khách hàng --</option>
                <?php foreach ($customers_list as $cust): ?>
                    <option value="<?= $cust['id'] ?>" <?= $filter_customer_id === intval($cust['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cust['fullname']) ?> (<?= htmlspecialchars($cust['phone'] ?: $cust['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-money-bill-wave me-1 text-success"></i>Phương thức thanh toán</label>
            <select name="payment_method" class="form-select">
                <option value="">-- Tất cả phương thức --</option>
                <option value="COD" <?= $filter_payment_method === 'COD' ? 'selected' : '' ?>>💵 Tiền mặt khi nhận (COD)</option>
                <option value="BANKING_QR" <?= $filter_payment_method === 'BANKING_QR' ? 'selected' : '' ?>>⚡ Quét mã VietQR / Chuyển khoản</option>
            </select>
        </div>

        <!-- Hàng 2: Trạng thái đơn, Trạng thái thanh toán, Từ ngày, Đến ngày, Nút lọc -->
        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-list-check me-1 text-warning"></i>Trạng thái đơn hàng</label>
            <select name="status" class="form-select">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>⏳ Chờ xác nhận</option>
                <option value="confirmed" <?= $filter_status == 'confirmed' ? 'selected' : '' ?>>⚙️ Đã xác nhận</option>
                <option value="shipping" <?= $filter_status == 'shipping' ? 'selected' : '' ?>>🚚 Đang giao hàng</option>
                <option value="completed" <?= $filter_status == 'completed' ? 'selected' : '' ?>>✅ Hoàn thành (Đã nhận)</option>
                <option value="returning" <?= $filter_status == 'returning' ? 'selected' : '' ?>>🔄 Trả hàng / Hoàn tiền</option>
                <option value="cancelled" <?= $filter_status == 'cancelled' ? 'selected' : '' ?>>❌ Đã hủy</option>
            </select>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-receipt me-1 text-secondary"></i>Trạng thái thanh toán</label>
            <select name="payment_status" class="form-select">
                <option value="">-- Tất cả --</option>
                <option value="paid" <?= $filter_payment_status == 'paid' ? 'selected' : '' ?>>✅ Đã thanh toán</option>
                <option value="unpaid" <?= $filter_payment_status == 'unpaid' ? 'selected' : '' ?>>⏳ Chưa thanh toán</option>
                <option value="refunded" <?= $filter_payment_status == 'refunded' ? 'selected' : '' ?>>↩️ Đã hoàn tiền</option>
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-dark"><i class="fa-regular fa-calendar me-1"></i>Từ ngày</label>
            <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($date_start) ?>">
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-dark"><i class="fa-regular fa-calendar-check me-1"></i>Đến ngày</label>
            <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($date_end) ?>">
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark fw-bold flex-grow-1"><i class="fa-solid fa-filter me-1"></i>Lọc</button>
            <a href="orders.php" class="btn btn-outline-secondary fw-bold" title="Đặt lại bộ lọc"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<style>
.orders-table-wrapper {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    padding: 1.25rem;
}
.orders-table-compact {
    font-size: 13px;
    width: 100%;
}
.orders-table-compact th {
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 10px 8px;
    vertical-align: middle;
    background: #f8fafc;
}
.orders-table-compact td {
    padding: 9px 8px;
    vertical-align: middle;
}
.btn-action-sm {
    padding: 4px 8px;
    font-size: 11.5px;
    font-weight: 600;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.btn-action-sm:hover {
    transform: translateY(-1px);
}
.order-badge-pill {
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    padding: 3px 6px;
    display: inline-block;
}
</style>

<!-- BẢNG DANH SÁCH HÓA ĐƠN -->
<div class="orders-table-wrapper mb-5 no-print">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 orders-table-compact">
            <thead>
                <tr class="text-secondary text-uppercase">
                    <th style="min-width: 130px;">Mã Đơn / Vận Đơn</th>
                    <th style="min-width: 150px;">Khách Hàng</th>
                    <th style="min-width: 110px;">Tổng Tiền</th>
                    <th style="min-width: 140px;">Trạng Thái &amp; TT</th>
                    <th style="min-width: 140px;">Lập Phiếu &amp; Ngày</th>
                    <th class="text-end" style="min-width: 210px;">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_res && $orders_res->num_rows > 0): ?>
                    <?php while($ord = $orders_res->fetch_assoc()): ?>
                        <?php 
                        $buyer_name    = !empty($ord['customer_name']) ? $ord['customer_name'] : 'Khách vãng lai';
                        $buyer_phone   = !empty($ord['phone']) ? $ord['phone'] : 'Chưa có SĐT';
                        $buyer_address = !empty($ord['address_detail']) ? $ord['address_detail'] : 'Tại cửa hàng';
                        $assigned_staff = !empty($ord['emp_name']) ? $ord['emp_name'] : (!empty($ord['staff_name']) ? $ord['staff_name'] : 'Chưa phân công');
                        
                        $items = $order_items[$ord['id']] ?? [];

                        $ord_data = array_merge($ord, [
                            'buyer_name'    => $buyer_name,
                            'buyer_phone'   => $buyer_phone,
                            'buyer_address' => $buyer_address,
                            'assigned_staff'=> $assigned_staff,
                            'items'         => $items
                        ]);

                        $is_printable = in_array($ord['status'], ['confirmed', 'shipping', 'completed'], true);
                        ?>
                        <tr id="order_row_<?= $ord['id'] ?>" data-order-id="<?= $ord['id'] ?>">
                            <td class="col-order-code">
                                <strong class="text-dark">#<?= htmlspecialchars($ord['order_code']); ?></strong>
                                <div class="col-tracking-code-wrap mt-1">
                                    <?php if (!empty($ord['tracking_code'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">
                                            <i class="fa-solid fa-truck-fast me-1"></i><?= htmlspecialchars($ord['shipping_carrier'] ?: 'GHTK') ?>: <?= htmlspecialchars($ord['tracking_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="col-buyer-info">
                                <strong class="text-dark d-block text-truncate" style="max-width: 150px;"><?= htmlspecialchars($buyer_name); ?></strong>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($buyer_phone); ?></small>
                                <div class="col-return-reason-wrap">
                                    <?php if ($ord['status'] === 'returning' && !empty($ord['return_reason'])): ?>
                                        <div class="small text-danger fw-bold mt-1 return-reason-banner" style="max-width: 180px; font-size: 10px;">
                                            <i class="fa-solid fa-rotate-left me-1"></i><?= htmlspecialchars($ord['return_reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="col-total-money"><strong class="text-danger"><?= number_format($ord['total_money'], 0, ',', '.'); ?>đ</strong></td>
                            <td class="col-order-status">
                                <?php 
                                $row_status_badge = getOrderStatusBadgeHtml($ord['status']);
                                if ($ord['status'] === 'cancelled') {
                                    if (!empty($ord['return_reason'])) {
                                        $row_status_badge = '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold"><i class="fa-solid fa-rotate-left me-1"></i>Đã duyệt hoàn trả</span>';
                                    } elseif ($ord['payment_status'] === 'refunded') {
                                        $row_status_badge = '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold"><i class="fa-solid fa-ban me-1"></i>Đã hủy (Hoàn tiền)</span>';
                                    } else {
                                        $row_status_badge = '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">❌ Đã hủy / Từ chối</span>';
                                    }
                                }
                                ?>
                                <div><?= $row_status_badge ?></div>
                                <div class="mt-1"><?= getPaymentStatusBadgeHtml($ord['payment_status'], $ord['payment_method']) ?></div>
                            </td>
                            <td class="col-assigned-staff">
                                <span class="small text-muted d-block"><i class="fa-solid fa-clock me-1"></i><?= date('d/m H:i', strtotime($ord['created_at'])); ?></span>
                                <small class="fw-bold <?= $assigned_staff !== 'Chưa phân công' ? 'text-primary' : 'text-muted' ?>">
                                    <i class="fa-solid fa-user-tie me-1"></i><span class="staff-name-text"><?= htmlspecialchars($assigned_staff); ?></span>
                                </small>
                            </td>
                            <td class="col-actions text-end">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                    <span class="custom-action-buttons">
                                        <?php if (!empty($ord['tracking_code'])): ?>
                                            <button type="button" class="btn btn-outline-danger btn-action-sm btn-print-label" onclick="openPrintModal('../print-shipping-label.php?order_id=<?= $ord['id'] ?>', 'In Tem Mã Vạch <?= htmlspecialchars($ord['shipping_carrier'] ?: 'GHTK') ?>')" title="In Tem Mã Vạch Barcode">
                                                <i class="fa-solid fa-barcode"></i> Tem
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-action-sm btn-track-carrier" onclick="openLiveTracking('<?= htmlspecialchars($ord['tracking_code']) ?>')" title="Tra cứu hành trình">
                                                <i class="fa-solid fa-route"></i>
                                            </button>
                                        <?php elseif ($ord['status'] === 'confirmed'): ?>
                                            <button type="button" class="btn btn-warning btn-action-sm text-dark fw-bold shadow-sm btn-push-carrier" onclick="pushCarrierOrder(<?= $ord['id'] ?>)" title="Đẩy đơn sang hãng vận chuyển">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Đẩy Hãng
                                            </button>
                                        <?php elseif ($ord['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-primary btn-action-sm fw-bold shadow-sm btn-quick-confirm" onclick="quickConfirmOrder(<?= $ord['id'] ?>, '<?= htmlspecialchars($ord['order_code']) ?>')" title="Xác nhận đơn">
                                                <i class="fa-solid fa-check"></i> Xác Nhận
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-action-sm fw-bold shadow-sm btn-quick-reject" onclick="quickRejectOrder(<?= $ord['id'] ?>, '<?= htmlspecialchars($ord['order_code']) ?>', '<?= $ord['payment_status'] ?>', '<?= $ord['payment_method'] ?>')" title="Từ chối đơn hàng">
                                                <i class="fa-solid fa-xmark"></i> Từ Chối
                                            </button>
                                        <?php elseif ($ord['status'] === 'returning'): ?>
                                            <button type="button" class="btn btn-danger btn-action-sm fw-bold shadow-sm btn-handle-return" onclick="openReturnDecisionModal(<?= $ord['id'] ?>, '<?= htmlspecialchars($ord['order_code']) ?>', '<?= htmlspecialchars(addslashes($ord['return_reason'] ?: 'Khách yêu cầu trả hàng')) ?>')" title="Xử lý hoàn trả">
                                                <i class="fa-solid fa-rotate-left"></i> Trả Hàng
                                            </button>
                                        <?php endif; ?>
                                    </span>

                                    <span class="invoice-print-wrap">
                                        <?php if ($is_printable): ?>
                                            <button type="button" class="btn btn-outline-dark btn-action-sm btn-print-inv" onclick="openPrintModal('print-invoice.php?id=<?= $ord['id'] ?>', 'In Hóa Đơn Xuất Kho #<?= htmlspecialchars($ord['order_code']) ?>')" title="In Phiếu Đơn Hàng">
                                                <i class="fa-solid fa-print"></i> In
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-light text-muted btn-action-sm border btn-print-inv-disabled" disabled title="Cần xác nhận đơn để in">
                                                <i class="fa-solid fa-lock"></i> In
                                            </button>
                                        <?php endif; ?>
                                    </span>

                                    <button type="button" class="btn btn-outline-success btn-action-sm btn-view-detail" data-order='<?= htmlspecialchars(json_encode($ord_data), ENT_QUOTES, "UTF-8"); ?>' title="Xem chi tiết đơn hàng">
                                        <i class="fa-solid fa-eye"></i> Xem
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Không tìm thấy đơn hàng phù hợp.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL CHI TIẾT HÓA ĐƠN -->
<!-- ======================================================== -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white rounded-top-4" style="background-color: var(--bg-dark-slate);">
                <h5 class="modal-title fw-bold" id="detailModalTitle"><i class="fa-solid fa-file-invoice me-2"></i>Chi Tiết Hóa Đơn</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- KHUNG TIÊU ĐỀ -->
                <div class="text-center mb-4 border-bottom pb-3">
                    <h3 class="fw-bold text-uppercase mb-1"><i class="fa-solid fa-shoe-prints me-2 text-warning"></i>SHOES STORE SYSTEM</h3>
                    <h4 class="fw-bold text-danger mt-3 mb-0" id="receipt_code">ĐƠN HÀNG</h4>
                </div>

                <!-- CẢNH BÁO YÊU CẦU HOÀN TRẢ TỪ KHÁCH (NẾU CÓ) -->
                <div id="receipt_return_alert" class="alert shadow-sm rounded-4 mb-4 d-none">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-1" id="receipt_return_title"><i class="fa-solid fa-rotate-left me-2"></i>Khách Hàng Yêu Cầu Hoàn Trả Đơn Hàng</h6>
                            <p class="mb-0 small" id="receipt_return_reason_text">Lý do: ...</p>
                        </div>
                        <div class="d-flex gap-2" id="receipt_return_buttons">
                            <button type="button" class="btn btn-sm btn-danger fw-bold rounded-pill shadow-sm" onclick="if(currentViewingOrder) handleReturnOrder(currentViewingOrder.id, 'accept')">
                                <i class="fa-solid fa-check me-1"></i> Duyệt Hoàn Tiền & Nhập Kho
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-pill" onclick="if(currentViewingOrder) handleReturnOrder(currentViewingOrder.id, 'reject')">
                                <i class="fa-solid fa-xmark me-1"></i> Từ Chối Hoàn Trả
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4 MỐC THỜI GIAN TIẾN TRÌNH ĐƠN HÀNG -->
                <div class="card border-0 bg-light p-3 rounded-4 mb-4">
                    <h6 class="fw-bold text-uppercase text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-timeline me-2 text-primary"></i>Mốc Thời Gian Đơn Hàng</h6>
                    <div class="row g-3 text-center">
                        <div class="col-12 col-md-3">
                            <div class="p-2 border rounded-3 bg-white h-100">
                                <small class="text-muted d-block fw-bold mb-1">1. ĐẶT HÀNG</small>
                                <span class="fw-bold text-primary small d-block" id="time_created">...</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="p-2 border rounded-3 bg-white h-100">
                                <small class="text-muted d-block fw-bold mb-1">2. XÁC NHẬN</small>
                                <span class="fw-bold text-warning small d-block" id="time_confirmed">...</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="p-2 border rounded-3 bg-white h-100">
                                <small class="text-muted d-block fw-bold mb-1">3. ĐANG GIAO</small>
                                <span class="fw-bold text-info small d-block" id="time_shipping">...</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="p-2 border rounded-3 bg-white h-100">
                                <small class="text-muted d-block fw-bold mb-1">4. HOÀN THÀNH</small>
                                <span class="fw-bold text-success small d-block" id="time_completed">...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 border-end pe-3">
                        <h6 class="fw-bold text-uppercase text-primary border-bottom pb-2"><i class="fa-solid fa-user me-2"></i>Thông Tin Khách Hàng</h6>
                        <p class="mb-1">Họ và Tên: <strong class="text-dark" id="receipt_buyer_name">...</strong></p>
                        <p class="mb-1">Số Điện Thoại: <strong class="text-dark" id="receipt_buyer_phone">...</strong></p>
                        <p class="mb-0">Địa Chỉ Giao: <span class="text-dark" id="receipt_buyer_address">...</span></p>
                    </div>

                    <div class="col-12 col-md-6 ps-md-3">
                        <h6 class="fw-bold text-uppercase text-success border-bottom pb-2"><i class="fa-solid fa-credit-card me-2"></i>Thanh Toán & Phí</h6>
                        <p class="mb-1">Phương Thức: <span class="badge bg-light text-dark border" id="receipt_payment_method">...</span></p>
                        <p class="mb-1">Trạng Thái TT: <span id="receipt_payment_status_badge">...</span></p>
                        <p class="mb-1">Phí Vận Chuyển: <span id="receipt_shipping_fee">...</span></p>
                        <p class="mb-0">Giảm Giá: <span id="receipt_discount" class="text-danger">...</span></p>
                    </div>
                </div>

                <!-- THÔNG TIN VẬN CHUYỂN HÃNG (GHTK / GHN) -->
                <div class="card border-0 bg-primary-subtle p-3 rounded-4 mb-4 border border-primary-subtle">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                        <h6 class="fw-bold text-uppercase text-primary mb-0"><i class="fa-solid fa-truck-fast me-2"></i>Vận Chuyển Hãng (GHTK / GHN)</h6>
                        <div id="receipt_carrier_actions" class="d-flex gap-2 align-items-center"></div>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-12 col-md-4">
                            <span class="text-muted d-block">Đơn vị giao hàng:</span>
                            <strong class="text-dark fs-6" id="receipt_carrier_name">---</strong>
                        </div>
                        <div class="col-12 col-md-4">
                            <span class="text-muted d-block">Mã vận đơn (Tracking Code):</span>
                            <strong class="text-primary font-monospace fs-6" id="receipt_tracking_code">Chưa tạo vận đơn</strong>
                        </div>
                        <div class="col-12 col-md-4">
                            <span class="text-muted d-block">Tiến độ bưu kiện:</span>
                            <span class="badge bg-white text-dark border" id="receipt_carrier_status">Chờ điều phối</span>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-boxes-stacked me-2"></i>Chi Tiết Sản Phẩm</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-uppercase small text-center">
                            <tr>
                                <th>#</th>
                                <th>Hình Ảnh</th>
                                <th>Sản Phẩm</th>
                                <th>Size</th>
                                <th>Màu</th>
                                <th>Đơn Giá</th>
                                <th>SL</th>
                                <th class="text-end">Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody id="receipt_items_body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end fw-bold fs-5">TỔNG CỘNG ĐƠN HÀNG:</td>
                                <td class="text-end fw-bold fs-4 text-danger" id="receipt_total_money">0đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- FORM CẬP NHẬT TRẠNG THÁI & PHÂN CÔNG NHÂN VIÊN -->
                <form method="POST" id="order_detail_form" class="bg-light p-4 rounded-4 border">
                    <input type="hidden" name="order_id" id="form_order_id" value="0">
                    <h6 class="fw-bold text-uppercase mb-3 text-dark"><i class="fa-solid fa-sliders me-2 text-warning"></i>Cập Nhật Đơn Hàng & Người Lập Phiếu</h6>
                    
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small"><i class="fa-solid fa-list-check me-1 text-warning"></i>Trạng Thái Tiến Trình:</label>
                            <select name="status" id="form_order_status" class="form-select fw-bold text-primary">
                                <option value="pending">⏳ Chờ xác nhận</option>
                                <option value="confirmed">⚙️ Đã xác nhận (Chuẩn bị hàng)</option>
                                <option value="shipping">🚚 Đang giao hàng</option>
                                <option value="completed">✅ Đã nhận hàng (Hoàn thành)</option>
                                <option value="returning">🔄 Trả hàng / Hoàn tiền</option>
                                <option value="cancelled">❌ Đã hủy đơn</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small"><i class="fa-solid fa-receipt me-1 text-secondary"></i>Trạng Thái Thanh Toán:</label>
                            <select name="payment_status" id="form_payment_status" class="form-select fw-bold">
                                <option value="unpaid">⏳ Chưa thanh toán</option>
                                <option value="paid">✅ Đã thanh toán</option>
                                <option value="refunded">↩️ Đã hoàn tiền</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small"><i class="fa-solid fa-user-tie me-1 text-success"></i>Người Lập Phiếu (Xác nhận):</label>
                            <select name="staff_id" id="form_staff_id" class="form-select fw-bold">
                                <option value="0">-- Tự động theo tài khoản đang duyệt --</option>
                                <?php foreach ($staff_list as $st_user): ?>
                                    <?php 
                                    $s_uid = intval($st_user['user_id'] ?: $st_user['emp_id']); 
                                    $comm_display = floatval($st_user['commission_rate'] ?? 3.0);
                                    ?>
                                    <option value="<?= $s_uid ?>">
                                        <?= htmlspecialchars($st_user['fullname']) ?> (HH: <?= $comm_display ?>%)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 text-end mt-3">
                            <button type="submit" name="update_order_status" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Cập Nhật Đơn Hàng
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Đóng</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger fw-bold px-3 d-none" id="btnModalPrintLabel" onclick="if(currentViewingOrder) openPrintModal('../print-shipping-label.php?order_id=' + currentViewingOrder.id, 'In Tem Mã Vạch ' + (currentViewingOrder.shipping_carrier || 'GHTK'))">
                        <i class="fa-solid fa-barcode me-1"></i> In Tem Mã Vạch
                    </button>
                    <button type="button" class="btn btn-outline-dark fw-bold px-4" id="btnGoToPrint" onclick="if(currentViewingOrder) openPrintModal('print-invoice.php?id=' + currentViewingOrder.id, 'In Hóa Đơn Xuất Kho #' + currentViewingOrder.order_code)">
                        <i class="fa-solid fa-print me-1"></i> In Hóa Đơn Xuất Kho
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.formatDate = function(str) {
    if (!str) return '---';
    var d = new Date(str);
    if (isNaN(d.getTime())) return str;
    return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN');
};

window.escHtml = function(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

window.currentViewingOrder = window.currentViewingOrder || null;

// Khởi tạo các sự kiện cho trang đơn hàng
(function initOrdersPage() {
    // 1. Lắng nghe thay đổi trạng thái đơn trong modal
    const statusSelect = document.getElementById('form_order_status');
    const paymentSelect = document.getElementById('form_payment_status');
    if (statusSelect && paymentSelect && !statusSelect.dataset.boundChange) {
        statusSelect.dataset.boundChange = 'true';
        statusSelect.addEventListener('change', function() {
            var newStatus = this.value;
            if (newStatus === 'completed') {
                paymentSelect.value = 'paid';
            } else if (newStatus === 'returning') {
                paymentSelect.value = 'refunded';
            } else if (newStatus === 'cancelled') {
                if (window.currentViewingOrder && (window.currentViewingOrder.payment_method === 'BANKING_QR' || window.currentViewingOrder.payment_status === 'paid' || window.currentViewingOrder.payment_status == 1)) {
                    paymentSelect.value = 'refunded';
                } else {
                    paymentSelect.value = 'unpaid';
                }
            }
        });
    }

    // 2. Submit form cập nhật đơn hàng
    const orderForm = document.getElementById('order_detail_form');
    if (orderForm && !orderForm.dataset.boundSubmit) {
        orderForm.dataset.boundSubmit = 'true';
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const fd = new FormData(this);
            fd.append('ajax_update_order_status', '1');

            fetch('orders.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }

                if (data.success) {
                    if (window.currentViewingOrder && window.currentViewingOrder.id == data.order_id) {
                        window.currentViewingOrder.status = data.status;
                        window.currentViewingOrder.payment_status = data.payment_status;
                        window.currentViewingOrder.staff_id = data.staff_id;
                        window.currentViewingOrder.staff_name = data.staff_name;
                        window.currentViewingOrder.assigned_staff = data.staff_name;
                        if (data.confirmed_at) window.currentViewingOrder.confirmed_at = data.confirmed_at;
                        if (data.shipping_at) window.currentViewingOrder.shipping_at = data.shipping_at;
                        if (data.completed_at) window.currentViewingOrder.completed_at = data.completed_at;
                        if (data.cancelled_at) window.currentViewingOrder.cancelled_at = data.cancelled_at;

                        document.getElementById('time_confirmed').innerText = data.confirmed_at ? window.formatDate(data.confirmed_at) : '---';
                        document.getElementById('time_shipping').innerText = data.shipping_at ? window.formatDate(data.shipping_at) : '---';
                        document.getElementById('time_completed').innerText = data.completed_at ? window.formatDate(data.completed_at) : (data.cancelled_at ? '❌ Hủy: ' + window.formatDate(data.cancelled_at) : '---');

                        if (data.payment_status === 'refunded') {
                            document.getElementById('receipt_payment_status_badge').innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">↩️ Đã hoàn tiền</span>';
                        } else if (data.payment_status === 'paid') {
                            document.getElementById('receipt_payment_status_badge').innerHTML = '<span class="badge bg-success-subtle text-success border border-success fw-bold">✅ Đã thanh toán</span>';
                        } else {
                            document.getElementById('receipt_payment_status_badge').innerHTML = '<span class="badge bg-warning-subtle text-dark border border-warning fw-bold">⏳ Chưa thanh toán</span>';
                        }
                    }

                    const rowEl = document.getElementById('order_row_' + data.order_id);
                    if (rowEl) {
                        const statusCell = rowEl.querySelector('.col-order-status');
                        const staffCell = rowEl.querySelector('.col-assigned-staff .staff-name-text');
                        const viewBtn = rowEl.querySelector('.btn-view-detail');

                        if (statusCell) {
                            statusCell.innerHTML = `<div>${data.status_badge}</div><div class="mt-1">${data.payment_badge}</div>`;
                        }
                        if (staffCell) staffCell.innerText = data.staff_name;
                        if (viewBtn && window.currentViewingOrder) {
                            viewBtn.setAttribute('data-order', JSON.stringify(window.currentViewingOrder));
                        }
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Đã Cập Nhật!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi Cập Nhật', text: data.message });
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Lỗi kết nối máy chủ!' });
            });
        });
    }
})();

// Event Delegation toàn cục cho nút Xem / Sửa Hóa Đơn (Hoạt động 100% không phụ thuộc reload)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-view-detail');
    if (!btn) return;

    try {
        var ord = JSON.parse(btn.getAttribute('data-order'));
        window.currentViewingOrder = ord;
        
        document.getElementById('receipt_code').innerText = 'ĐƠN HÀNG #' + ord.order_code;

        document.getElementById('time_created').innerText = window.formatDate(ord.created_at);
        document.getElementById('time_confirmed').innerText = ord.confirmed_at ? window.formatDate(ord.confirmed_at) : '---';
        document.getElementById('time_shipping').innerText = ord.shipping_at ? window.formatDate(ord.shipping_at) : '---';
        document.getElementById('time_completed').innerText = ord.completed_at ? window.formatDate(ord.completed_at) : (ord.cancelled_at ? '❌ Hủy: ' + window.formatDate(ord.cancelled_at) : '---');

        document.getElementById('receipt_buyer_name').innerText = ord.buyer_name;
        document.getElementById('receipt_buyer_phone').innerText = ord.buyer_phone;
        document.getElementById('receipt_buyer_address').innerText = ord.buyer_address;

        document.getElementById('receipt_payment_method').innerText = (ord.payment_method === 'BANKING_QR') ? '⚡ Quét mã VietQR' : '💵 Tiền mặt COD';
        document.getElementById('receipt_shipping_fee').innerText = new Intl.NumberFormat('vi-VN').format(ord.shipping_fee || 0) + 'đ';
        document.getElementById('receipt_discount').innerText = '-' + new Intl.NumberFormat('vi-VN').format(ord.discount_amount || 0) + 'đ';

        var returnAlert = document.getElementById('receipt_return_alert');
        var returnTitle = document.getElementById('receipt_return_title');
        var returnReasonText = document.getElementById('receipt_return_reason_text');
        var returnButtons = document.getElementById('receipt_return_buttons');

        if (ord.status === 'returning') {
            returnAlert.className = 'alert alert-danger border-danger shadow-sm rounded-4 mb-4';
            returnTitle.className = 'fw-bold mb-1 text-danger';
            returnTitle.innerHTML = '<i class="fa-solid fa-rotate-left me-2"></i>Khách Hàng Đang Yêu Cầu Hoàn Trả Đơn Hàng';
            returnReasonText.className = 'mb-0 small text-dark';
            returnReasonText.innerHTML = '<strong>Lý do từ khách hàng:</strong> ' + (ord.return_reason ? window.escHtml(ord.return_reason) : 'Khách yêu cầu hoàn trả');
            if (returnButtons) returnButtons.style.setProperty('display', 'flex', 'important');
        } else if (ord.return_reason && ord.return_reason.trim() !== '') {
            if (ord.status === 'cancelled' || ord.payment_status === 'refunded') {
                returnAlert.className = 'alert alert-success border-success shadow-sm rounded-4 mb-4';
                returnTitle.className = 'fw-bold mb-1 text-success';
                returnTitle.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Đơn Hàng Đã Được Duyệt Hoàn Trả &amp; Nhập Kho';
                returnReasonText.className = 'mb-0 small text-dark';
                returnReasonText.innerHTML = '<strong>Lý do khách trả:</strong> ' + window.escHtml(ord.return_reason) + ' — <span class="badge bg-success-subtle text-success border border-success px-2 py-0">Đã hoàn tiền &amp; nhập kho</span>';
                if (returnButtons) returnButtons.style.setProperty('display', 'none', 'important');
            } else if (ord.status === 'completed') {
                returnAlert.className = 'alert alert-secondary border-secondary shadow-sm rounded-4 mb-4';
                returnTitle.className = 'fw-bold mb-1 text-secondary';
                returnTitle.innerHTML = '<i class="fa-solid fa-ban me-2"></i>Shop Đã Từ Chối Yêu Cầu Hoàn Trả';
                returnReasonText.className = 'mb-0 small text-muted';
                returnReasonText.innerHTML = '<strong>Lý do khách từng yêu cầu:</strong> ' + window.escHtml(ord.return_reason) + ' — <span class="text-dark fw-bold">Đơn hàng tiếp tục duy trì Hoàn tất</span>';
                if (returnButtons) returnButtons.style.setProperty('display', 'none', 'important');
            } else {
                returnAlert.className = 'alert shadow-sm rounded-4 mb-4 d-none';
            }
        } else {
            returnAlert.className = 'alert shadow-sm rounded-4 mb-4 d-none';
        }

        var carrierName = ord.shipping_carrier || 'GHTK';
        document.getElementById('receipt_carrier_name').innerText = (carrierName === 'GHN') ? 'Giao Hàng Nhanh (GHN)' : (carrierName === 'LOCAL' ? 'Vận Chuyển Tiêu Chuẩn' : 'Giao Hàng Tiết Kiệm (GHTK)');
        
        var actionsContainer = document.getElementById('receipt_carrier_actions');
        var btnPrintLabel = document.getElementById('btnModalPrintLabel');

        if (ord.tracking_code) {
            document.getElementById('receipt_tracking_code').innerHTML = `<span class="badge bg-primary fs-6 px-3 py-1"><i class="fa-solid fa-barcode me-1"></i>${ord.tracking_code}</span>`;
            document.getElementById('receipt_carrier_status').innerText = ord.carrier_status_text || 'Đang luân chuyển';
            
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill" onclick="openLiveTracking('${ord.tracking_code}')">
                    <i class="fa-solid fa-route me-1"></i> Tra Cứu Lịch Trình
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill" onclick="openPrintModal('../print-shipping-label.php?order_id=${ord.id}', 'Tem Vận Chuyển Barcode A6')">
                    <i class="fa-solid fa-print me-1"></i> In Tem Barcode
                </button>
            `;

            if (btnPrintLabel) {
                btnPrintLabel.href = '../print-shipping-label.php?order_id=' + ord.id;
                btnPrintLabel.classList.remove('d-none');
            }
        } else {
            document.getElementById('receipt_tracking_code').innerText = 'Chưa tạo vận đơn hãng';
            
            if (ord.status === 'confirmed' || ord.status === 'shipping') {
                document.getElementById('receipt_carrier_status').innerText = (ord.status === 'shipping') ? 'Đang giao hàng - Bấm tạo vận đơn để lấy mã' : 'Đã xác nhận - Sẵn sàng đẩy đơn';
                actionsContainer.innerHTML = `
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill shadow-sm" onclick="pushCarrierOrder(${ord.id})">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> 1-Click Đẩy Hãng GHTK/GHN
                    </button>
                `;
            } else if (ord.status === 'pending') {
                document.getElementById('receipt_carrier_status').innerText = 'Chờ xác nhận đơn hàng';
                actionsContainer.innerHTML = `
                    <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill shadow-sm" onclick="quickConfirmOrder(${ord.id}, '${ord.order_code}')">
                        <i class="fa-solid fa-check me-1"></i> Xác Nhận Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill shadow-sm" onclick="quickRejectOrder(${ord.id}, '${ord.order_code}', '${ord.payment_status}', '${ord.payment_method}')">
                        <i class="fa-solid fa-xmark me-1"></i> Từ Chối Đơn
                    </button>
                    <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 align-self-center small">
                        <i class="fa-solid fa-lock me-1"></i> Xác nhận trước khi đẩy hãng
                    </span>
                `;
            } else {
                document.getElementById('receipt_carrier_status').innerText = '---';
                actionsContainer.innerHTML = '';
            }

            if (btnPrintLabel) btnPrintLabel.classList.add('d-none');
        }

        if (ord.payment_status === 'refunded' || ord.status === 'returning' || (ord.status === 'cancelled' && (ord.payment_method === 'BANKING_QR' || ord.payment_status === 'paid'))) {
            document.getElementById('receipt_payment_status_badge').innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">↩️ Đã hoàn tiền</span>';
        } else if (ord.payment_status === 'paid' || ord.payment_status === '1' || ord.payment_status === 1 || ord.status === 'completed' || ord.payment_method === 'BANKING_QR') {
            document.getElementById('receipt_payment_status_badge').innerHTML = '<span class="badge bg-success-subtle text-success border border-success fw-bold">✅ Đã thanh toán</span>';
        } else {
            document.getElementById('receipt_payment_status_badge').innerHTML = '<span class="badge bg-warning-subtle text-dark border border-warning fw-bold">⏳ Chưa thanh toán</span>';
        }

        var tbody = document.getElementById('receipt_items_body');
        tbody.innerHTML = '';
        
        if (ord.items && ord.items.length > 0) {
            ord.items.forEach(function(item, idx) {
                var imgSrc = (item.product_image && item.product_image.indexOf('http') === 0) ? item.product_image : (item.main_image && item.main_image.indexOf('http') === 0 ? item.main_image : '../' + item.main_image);
                var price = new Intl.NumberFormat('vi-VN').format(item.price) + 'đ';
                var subtotal = new Intl.NumberFormat('vi-VN').format(item.price * item.quantity) + 'đ';

                var row = `<tr>
                    <td class="text-center">${idx + 1}</td>
                    <td class="text-center"><img src="${imgSrc}" class="rounded border" style="width: 45px; height: 45px; object-fit: cover;"></td>
                    <td class="fw-bold text-dark">${item.product_name}</td>
                    <td class="text-center fw-bold"><span class="badge bg-secondary">EU ${item.size}</span></td>
                    <td class="text-center">${item.color || '-'}</td>
                    <td class="text-center">${price}</td>
                    <td class="text-center fw-bold">${item.quantity}</td>
                    <td class="text-end fw-bold text-danger">${subtotal}</td>
                </tr>`;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Chưa có chi tiết sản phẩm.</td></tr>';
        }

        document.getElementById('receipt_total_money').innerText = new Intl.NumberFormat('vi-VN').format(ord.total_money) + 'đ';

        document.getElementById('form_order_id').value = ord.id;
        document.getElementById('form_order_status').value = ord.status || 'pending';
        document.getElementById('form_staff_id').value = ord.staff_id || 0;
        
        var ps = ord.payment_status;
        if (ord.status === 'completed') {
            ps = 'paid';
        } else if (ord.status === 'returning' || (ord.status === 'cancelled' && (ord.payment_method === 'BANKING_QR' || ord.payment_status === 'paid'))) {
            ps = 'refunded';
        } else if (ps == '1' || ps == 1 || ord.payment_method === 'BANKING_QR') {
            ps = 'paid';
        } else if (ps == '0' || ps == 0) {
            ps = 'unpaid';
        }
        document.getElementById('form_payment_status').value = ps || 'unpaid';

        var btnPrint = document.getElementById('btnGoToPrint');
        if (['confirmed', 'shipping', 'completed'].includes(ord.status)) {
            btnPrint.href = 'print-invoice.php?id=' + ord.id;
            btnPrint.classList.remove('disabled', 'btn-light');
            btnPrint.classList.add('btn-outline-dark');
            btnPrint.innerHTML = '<i class="fa-solid fa-print me-1"></i> In Hóa Đơn Xuất Kho';
        } else {
            btnPrint.href = 'javascript:void(0);';
            btnPrint.classList.remove('btn-outline-dark');
            btnPrint.classList.add('disabled', 'btn-light');
            btnPrint.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Cần xác nhận đơn để in';
        }

        const modalEl = document.getElementById('orderDetailModal');
        const modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalObj.show();
    } catch (err) {
        console.error('Lỗi khi mở modal hóa đơn:', err);
    }
});

// Hàm Xác Nhận Nhanh Đơn Hàng (100% Live AJAX, Zero Reload)
window.quickConfirmOrder = function(orderId, orderCode) {
    Swal.fire({
        title: 'Xác Nhận Đơn Hàng #' + orderCode + '?',
        text: 'Hệ thống sẽ chuyển trạng thái đơn sang "Đã xác nhận" và tự động cập nhật bạn làm Người Lập Phiếu.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Xác Nhận Đơn',
        cancelButtonText: 'Hủy Bỏ'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Đang xử lý xác nhận...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const fd = new FormData();
            fd.append('ajax_quick_confirm_order', '1');
            fd.append('order_id', orderId);

            fetch('orders.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const rowEl = document.getElementById('order_row_' + orderId);
                    if (rowEl) {
                        const statusCell   = rowEl.querySelector('.col-order-status');
                        const staffCell    = rowEl.querySelector('.col-assigned-staff .staff-name-text');
                        const customActions = rowEl.querySelector('.custom-action-buttons');
                        const printWrap    = rowEl.querySelector('.invoice-print-wrap');
                        const viewBtn      = rowEl.querySelector('.btn-view-detail');

                        // Cập nhật badge trạng thái trong bảng
                        if (statusCell && data.status_badge && data.payment_badge) {
                            statusCell.innerHTML = `<div>${data.status_badge}</div><div class="mt-1">${data.payment_badge}</div>`;
                        } else if (statusCell) {
                            statusCell.innerHTML = `<div><span class="badge bg-info text-dark border border-info fw-bold">⚙️ Đã xác nhận</span></div><div class="mt-1">${rowEl.querySelector('.col-order-status div:last-child')?.outerHTML || ''}</div>`;
                        }

                        if (staffCell && data.staff_name) staffCell.innerText = data.staff_name;

                        if (customActions) {
                            customActions.innerHTML = `
                                <button type="button" class="btn btn-warning btn-action-sm text-dark fw-bold shadow-sm btn-push-carrier" onclick="pushCarrierOrder(${orderId})" title="Đẩy đơn sang hãng vận chuyển">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Đẩy Hãng
                                </button>
                            `;
                        }
                        if (printWrap) {
                            printWrap.innerHTML = `
                                <button type="button" class="btn btn-outline-dark btn-action-sm btn-print-inv" onclick="openPrintModal('print-invoice.php?id=${orderId}', 'In Hóa Đơn Xuất Kho #${orderCode}')" title="In Phiếu Đơn Hàng">
                                    <i class="fa-solid fa-print"></i> In
                                </button>
                            `;
                        }

                        // ĐồNG BỘ data-order vào nút Xem để modal hiển thị đúng
                        if (viewBtn) {
                            try {
                                let od = JSON.parse(viewBtn.getAttribute('data-order') || '{}');
                                od.status       = 'confirmed';
                                od.staff_id     = data.staff_id || od.staff_id;
                                od.staff_name   = data.staff_name || od.staff_name;
                                od.assigned_staff = data.staff_name || od.assigned_staff;
                                if (data.confirmed_at) od.confirmed_at = data.confirmed_at;
                                viewBtn.setAttribute('data-order', JSON.stringify(od));
                            } catch(e) {}
                        }

                        // Đồng bộ currentViewingOrder nếu modal đang mở cho đơn này
                        if (window.currentViewingOrder && window.currentViewingOrder.id == orderId) {
                            window.currentViewingOrder.status = 'confirmed';
                            if (data.staff_id)     window.currentViewingOrder.staff_id = data.staff_id;
                            if (data.staff_name)   window.currentViewingOrder.staff_name = data.staff_name;
                            if (data.confirmed_at) window.currentViewingOrder.confirmed_at = data.confirmed_at;
                            const tconf = document.getElementById('time_confirmed');
                            if (tconf && data.confirmed_at) tconf.innerText = window.formatDate(data.confirmed_at);
                            const sel = document.getElementById('form_order_status');
                            if (sel) sel.value = 'confirmed';
                        }
                    }

                    const pendingBadges = document.querySelectorAll('.badge-pending-count');
                    pendingBadges.forEach(b => {
                        let cnt = parseInt(b.innerText) || 0;
                        if (cnt > 1) b.innerText = cnt - 1;
                        else b.remove();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Đã Xác Nhận Đơn Hàng!',
                        text: data.message,
                        timer: 1600,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Không Thể Xác Nhận', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Lỗi kết nối máy chủ!' });
            });
        }
    });
};

// Hàm Từ Chối Nhanh Đơn Hàng (100% Live AJAX, Zero Reload, Tự Động Hoàn Kho & Hoàn Tiền nếu đã TT)
window.quickRejectOrder = function(orderId, orderCode, paymentStatus, paymentMethod) {
    const isPaid = (paymentStatus === 'paid' || paymentMethod === 'BANKING_QR' || paymentStatus == 1 || paymentStatus === '1');
    const paidNotice = isPaid 
        ? `<div class="alert alert-warning py-2 px-3 small border-0 rounded-3 mb-3 text-start">
             <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>
             <strong>Lưu ý:</strong> Đơn hàng này <strong>Đã Thanh Toán</strong>. Khi từ chối, đơn sẽ chuyển sang <strong>Đã Hủy</strong>, sản phẩm được nhập lại vào kho và hệ thống tự động đánh dấu <strong>Đã Hoàn Tiền</strong>.
           </div>`
        : `<p class="text-muted small mb-3 text-start">Sản phẩm sẽ tự động được nhập lại vào kho và đơn hàng chuyển sang trạng thái <strong>Đã hủy / Từ chối</strong>.</p>`;

    Swal.fire({
        title: `<i class="fa-solid fa-ban text-danger me-2"></i>Từ Chối Đơn Hàng #${orderCode}?`,
        html: `
            ${paidNotice}
            <div class="text-start">
                <label class="form-label small fw-bold text-dark mb-1">Chọn hoặc nhập lý do từ chối (*):</label>
                <select id="swal_reject_preset" class="form-select form-select-sm mb-2" onchange="var custom = document.getElementById('swal_reject_custom'); if(this.value==='Khác'){ custom.classList.remove('d-none'); custom.focus(); } else { custom.classList.add('d-none'); }">
                    <option value="Hết hàng trong kho (hết size / màu)">1. Hết hàng trong kho (hết size / màu)</option>
                    <option value="Không thể liên lạc được với khách hàng qua SĐT">2. Không thể liên lạc được với khách qua SĐT</option>
                    <option value="Khách hàng liên hệ yêu cầu hủy đơn">3. Khách hàng liên hệ yêu cầu hủy đơn</option>
                    <option value="Địa chỉ nhận hàng không hợp lệ / ngoài khu vực giao">4. Địa chỉ nhận hàng không hợp lệ / ngoài vùng giao</option>
                    <option value="Nghi vấn đơn hàng ảo / thông tin không chính xác">5. Nghi vấn đơn hàng ảo / spam</option>
                    <option value="Khác">6. Lý do khác (Nhập tùy ý bên dưới)...</option>
                </select>
                <textarea id="swal_reject_custom" class="form-control form-control-sm d-none" rows="2" placeholder="Nhập lý do từ chối cụ thể..."></textarea>
            </div>
        `,
        icon: isPaid ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-xmark me-1"></i> Xác Nhận Từ Chối',
        cancelButtonText: 'Đóng',
        focusConfirm: false,
        preConfirm: () => {
            const presetEl = document.getElementById('swal_reject_preset');
            const customEl = document.getElementById('swal_reject_custom');
            const preset = presetEl ? presetEl.value : '';
            const custom = customEl ? customEl.value.trim() : '';
            const reason = (preset === 'Khác') ? custom : preset;
            if (preset === 'Khác' && !custom) {
                Swal.showValidationMessage('Vui lòng nhập lý do từ chối cụ thể.');
                return false;
            }
            return reason || preset;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const finalReason = result.value || 'Shop từ chối đơn hàng';

            Swal.fire({
                title: 'Đang xử lý từ chối đơn...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const fd = new FormData();
            fd.append('ajax_quick_reject_order', '1');
            fd.append('order_id', orderId);
            fd.append('reject_reason', finalReason);

            fetch('orders.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const rowEl = document.getElementById('order_row_' + orderId);
                    if (rowEl) {
                        const statusCell = rowEl.querySelector('.col-order-status');
                        const customActions = rowEl.querySelector('.custom-action-buttons');
                        const staffCell = rowEl.querySelector('.col-assigned-staff .staff-name-text');
                        const viewBtn = rowEl.querySelector('.btn-view-detail');

                        if (statusCell) {
                            statusCell.innerHTML = `<div>${data.status_badge}</div><div class="mt-1">${data.payment_badge}</div>`;
                        }
                        if (staffCell && data.staff_name) staffCell.innerText = data.staff_name;
                        if (customActions) customActions.innerHTML = '';

                        if (viewBtn) {
                            try {
                                let od = JSON.parse(viewBtn.getAttribute('data-order') || '{}');
                                od.status = 'cancelled';
                                od.payment_status = data.payment_status;
                                od.cancel_reason = data.reject_reason;
                                od.staff_id = data.staff_id || od.staff_id;
                                od.staff_name = data.staff_name || od.staff_name;
                                od.assigned_staff = data.staff_name || od.assigned_staff;
                                if (data.cancelled_at) od.cancelled_at = data.cancelled_at;
                                viewBtn.setAttribute('data-order', JSON.stringify(od));
                            } catch(e) {}
                        }
                    }

                    if (window.currentViewingOrder && window.currentViewingOrder.id == orderId) {
                        window.currentViewingOrder.status = 'cancelled';
                        window.currentViewingOrder.payment_status = data.payment_status;
                        window.currentViewingOrder.cancel_reason = data.reject_reason;
                        if (data.staff_id) window.currentViewingOrder.staff_id = data.staff_id;
                        if (data.staff_name) window.currentViewingOrder.staff_name = data.staff_name;
                        if (data.cancelled_at) window.currentViewingOrder.cancelled_at = data.cancelled_at;

                        const tcomp = document.getElementById('time_completed');
                        if (tcomp && data.cancelled_at) tcomp.innerText = '❌ Hủy: ' + window.formatDate(data.cancelled_at);
                        const sel = document.getElementById('form_order_status');
                        if (sel) sel.value = 'cancelled';
                        const psel = document.getElementById('form_payment_status');
                        if (psel) psel.value = data.payment_status;
                        const ac = document.getElementById('receipt_carrier_actions');
                        if (ac) ac.innerHTML = '';
                    }

                    const pendingBadges = document.querySelectorAll('.badge-pending-count');
                    pendingBadges.forEach(b => {
                        let cnt = parseInt(b.innerText) || 0;
                        if (cnt > 1) b.innerText = cnt - 1;
                        else b.remove();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Đã Từ Chối Đơn Hàng!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi Xử Lý', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Lỗi kết nối máy chủ!' });
            });
        }
    });
};

// Modal Quyết Định Xử Lý Hoàn Trả (Duyệt hoặc Từ chối)
window.openReturnDecisionModal = function(orderId, orderCode, returnReason) {
    Swal.fire({
        title: `<i class="fa-solid fa-rotate-left text-danger me-2"></i>Xử Lý Trả Hàng #${orderCode}`,
        html: `
            <div class="text-start p-3 bg-light rounded-3 border mb-3">
                <div class="small text-muted mb-1">Lý do khách hàng yêu cầu hoàn trả:</div>
                <div class="fw-bold text-danger">${returnReason || 'Khách yêu cầu hoàn trả'}</div>
            </div>
            <p class="text-muted small mb-0">Bạn muốn <strong>Duyệt Hoàn Tiền & Nhập Kho</strong> hay <strong>Từ Chối</strong> yêu cầu này?</p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Duyệt Hoàn Tiền & Nhập Kho',
        denyButtonText: '<i class="fa-solid fa-xmark me-1"></i> Từ Chối Hoàn Trả',
        cancelButtonText: 'Đóng',
        confirmButtonColor: '#dc2626',
        denyButtonColor: '#475569'
    }).then((res) => {
        if (res.isConfirmed) {
            window.handleReturnOrder(orderId, 'accept');
        } else if (res.isDenied) {
            window.handleReturnOrder(orderId, 'reject');
        }
    });
};

// Hàm gửi lệnh Xử lý Hoàn trả sang Backend (100% Live AJAX, Zero Reload)
window.handleReturnOrder = function(orderId, decision) {
    Swal.fire({
        title: 'Đang xử lý yêu cầu...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const fd = new FormData();
    fd.append('ajax_handle_return_order', '1');
    fd.append('order_id', orderId);
    fd.append('decision', decision);

    fetch('orders.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const rowEl = document.getElementById('order_row_' + orderId);
            if (rowEl) {
                const statusCell = rowEl.querySelector('.col-order-status');
                const customActions = rowEl.querySelector('.custom-action-buttons');
                const returnBanner = rowEl.querySelector('.return-reason-banner');

                if (decision === 'accept') {
                    if (statusCell) statusCell.innerHTML = '<div><span class="badge bg-danger-subtle text-danger border border-danger fw-bold">❌ Đã hủy / Hoàn tiền</span></div><div class="mt-1"><span class="badge bg-danger-subtle text-danger border border-danger fw-bold">↩️ Đã hoàn tiền</span></div>';
                } else {
                    if (statusCell) statusCell.innerHTML = '<div><span class="badge bg-success border border-success fw-bold">✅ Hoàn thành</span></div><div class="mt-1"><span class="badge bg-success-subtle text-success border border-success fw-bold">✅ Đã thanh toán</span></div>';
                }
                if (customActions) customActions.innerHTML = '';
                if (returnBanner) returnBanner.remove();
            }

            const returnAlert = document.getElementById('receipt_return_alert');
            const returnTitle = document.getElementById('receipt_return_title');
            const returnButtons = document.getElementById('receipt_return_buttons');
            if (returnAlert && returnTitle) {
                if (decision === 'accept') {
                    returnAlert.className = 'alert alert-success border-success shadow-sm rounded-4 mb-4';
                    returnTitle.className = 'fw-bold mb-1 text-success';
                    returnTitle.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Đơn Hàng Đã Được Duyệt Hoàn Trả &amp; Nhập Kho';
                } else {
                    returnAlert.className = 'alert alert-secondary border-secondary shadow-sm rounded-4 mb-4';
                    returnTitle.className = 'fw-bold mb-1 text-secondary';
                    returnTitle.innerHTML = '<i class="fa-solid fa-ban me-2"></i>Shop Đã Từ Chối Yêu Cầu Hoàn Trả';
                }
                if (returnButtons) returnButtons.style.setProperty('display', 'none', 'important');
            }

            Swal.fire({
                icon: 'success',
                title: 'Thành Công!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi Xử Lý', text: data.message });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Lỗi kết nối máy chủ!' });
    });
};

// Hàm 1-Click Đẩy Đơn Sang Hãng Vận Chuyển (100% Live AJAX, Zero Reload)
window.pushCarrierOrder = function(orderId) {
    Swal.fire({
        title: 'Đẩy Đơn Sang Hãng Vận Chuyển?',
        text: 'Hệ thống sẽ kết nối API tạo vận đơn, sinh mã tracking và lên lịch bưu tá lấy hàng.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-truck-fast me-1"></i> Xác Nhận Đẩy Đơn',
        cancelButtonText: 'Hủy Bỏ'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Đang kết nối hãng vận chuyển...',
                text: 'Vui lòng chờ trong giây lát...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const fd = new FormData();
            fd.append('ajax_push_carrier_order', '1');
            fd.append('order_id', orderId);

            fetch('orders.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const rowEl = document.getElementById('order_row_' + orderId);
                    if (rowEl) {
                        const codeWrap      = rowEl.querySelector('.col-tracking-code-wrap');
                        const customActions  = rowEl.querySelector('.custom-action-buttons');
                        const statusCell     = rowEl.querySelector('.col-order-status');
                        const viewBtn        = rowEl.querySelector('.btn-view-detail');

                        if (codeWrap) {
                            codeWrap.innerHTML = `
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">
                                    <i class="fa-solid fa-truck-fast me-1"></i>${data.carrier}: ${data.tracking_code}
                                </span>
                            `;
                        }
                        if (customActions) {
                            customActions.innerHTML = `
                                <button type="button" class="btn btn-outline-danger btn-action-sm btn-print-label" onclick="openPrintModal('../print-shipping-label.php?order_id=${orderId}', 'In Tem Mã Vạch ${data.carrier}')" title="In Tem Mã Vạch Barcode">
                                    <i class="fa-solid fa-barcode"></i> Tem
                                </button>
                                <button type="button" class="btn btn-outline-info btn-action-sm btn-track-carrier" onclick="openLiveTracking('${data.tracking_code}')" title="Tra cứu hành trình">
                                    <i class="fa-solid fa-route"></i>
                                </button>
                            `;
                        }

                        // Cập nhật badge trạng thái → Đang giao hàng
                        if (statusCell) {
                            const payHtml = statusCell.querySelector('div:last-child')?.outerHTML || '';
                            statusCell.innerHTML = `<div><span class="badge bg-primary border border-primary fw-bold">🚚 Đang giao hàng</span></div><div class="mt-1">${payHtml}</div>`;
                        }

                        // Đồng bộ data-order vào nút Xem
                        if (viewBtn) {
                            try {
                                let od = JSON.parse(viewBtn.getAttribute('data-order') || '{}');
                                od.status           = 'shipping';
                                od.tracking_code    = data.tracking_code;
                                od.shipping_carrier = data.carrier;
                                od.carrier_status_text = 'Đã tạo vận đơn - Chờ bưu tá lấy hàng';
                                viewBtn.setAttribute('data-order', JSON.stringify(od));
                            } catch(e) {}
                        }

                        // Đồng bộ currentViewingOrder nếu modal đang mở
                        if (window.currentViewingOrder && window.currentViewingOrder.id == orderId) {
                            window.currentViewingOrder.status           = 'shipping';
                            window.currentViewingOrder.tracking_code    = data.tracking_code;
                            window.currentViewingOrder.shipping_carrier = data.carrier;
                            const sel = document.getElementById('form_order_status');
                            if (sel) sel.value = 'shipping';
                            const trackEl = document.getElementById('receipt_tracking_code');
                            const csEl    = document.getElementById('receipt_carrier_status');
                            if (trackEl) trackEl.innerHTML = `<span class="badge bg-primary fs-6 px-3 py-1"><i class="fa-solid fa-barcode me-1"></i>${data.tracking_code}</span>`;
                            if (csEl)    csEl.innerText = 'Đã tạo vận đơn - Chờ bưu tá lấy hàng';
                            // Cập nhật khu vực actions trong modal
                            const ac = document.getElementById('receipt_carrier_actions');
                            if (ac) {
                                ac.innerHTML = `
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill" onclick="openLiveTracking('${data.tracking_code}')">
                                        <i class="fa-solid fa-route me-1"></i> Tra Cứu Lịch Trình
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill" onclick="openPrintModal('../print-shipping-label.php?order_id=${orderId}', 'Tem Vận Chuyển Barcode A6')">
                                        <i class="fa-solid fa-print me-1"></i> In Tem Barcode
                                    </button>
                                `;
                            }
                        }
                    }

                    const trackingEl = document.getElementById('receipt_tracking_code');
                    const carrierStatusEl = document.getElementById('receipt_carrier_status');
                    if (trackingEl && !(window.currentViewingOrder && window.currentViewingOrder.id == orderId)) {
                        trackingEl.innerHTML = `<span class="badge bg-primary fs-6 px-3 py-1"><i class="fa-solid fa-barcode me-1"></i>${data.tracking_code}</span>`;
                    }
                    if (carrierStatusEl && !(window.currentViewingOrder && window.currentViewingOrder.id == orderId)) {
                        carrierStatusEl.innerText = 'Đã tạo vận đơn';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Đẩy Đơn Thành Công!',
                        html: `
                            <div class="p-3 bg-light rounded-3 text-start mb-3 border">
                                <div class="mb-2"><strong>Đơn vị:</strong> <span class="badge bg-primary">${data.carrier}</span></div>
                                <div class="mb-2"><strong>Mã Vận Đơn:</strong> <span class="text-danger fw-bold font-monospace fs-5">${data.tracking_code}</span></div>
                                <div><strong>Tiền thu hộ COD:</strong> <span class="text-success fw-bold">${new Intl.NumberFormat('vi-VN').format(data.cod_amount || 0)}đ</span></div>
                            </div>
                            <p class="small text-muted mb-0">Bưu tá sẽ đến kho lấy hàng theo lịch hẹn.</p>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-solid fa-barcode me-1"></i> In Tem Mã Vạch Ngay',
                        cancelButtonText: 'Đóng',
                        confirmButtonColor: '#dc2626'
                    }).then((btnRes) => {
                        if (btnRes.isConfirmed) {
                            window.openPrintModal('../' + data.label_url, 'In Tem Mã Vạch Giao Hàng ' + data.carrier);
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Không Thể Tạo Vận Đơn', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Lỗi kết nối máy chủ!' });
            });
        }
    });
};

// Hàm Mở Modal Xem Trước & In Trực Tiếp Trên Trang (Không Mở Tab Rác)
window.openPrintModal = function(url, title = 'Xem Trước Phiếu In') {
    const modalEl = document.getElementById('printPreviewModal');
    if (!modalEl) {
        window.open(url, 'shoe_print_window');
        return;
    }
    document.getElementById('printPreviewTitle').innerHTML = `<i class="fa-solid fa-print me-2 text-warning"></i>${title}`;
    document.getElementById('printPreviewIframe').src = url;
    const modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalObj.show();
};

window.triggerIframePrint = function() {
    const ifr = document.getElementById('printPreviewIframe');
    if (ifr && ifr.contentWindow) {
        ifr.contentWindow.focus();
        ifr.contentWindow.print();
    }
};

// Render nội dung tra cứu và thanh giả lập bưu cục
window.renderLiveTrackingContent = function(data) {
    let timelineHtml = '<div class="timeline text-start p-2" style="max-height: 280px; overflow-y: auto;">';
    (data.timeline || []).forEach((step) => {
        const color = step.done ? (step.current ? 'text-primary fw-bold' : 'text-success') : 'text-muted';
        const icon = step.done ? (step.current ? 'fa-truck-fast text-primary fa-bounce' : 'fa-circle-check text-success') : 'fa-circle-dot text-muted';
        timelineHtml += `
            <div class="d-flex mb-3 align-items-start border-bottom pb-2">
                <div class="me-3 pt-1"><i class="fa-solid ${icon} fs-5"></i></div>
                <div>
                    <div class="fw-bold ${color}">${step.status} <small class="text-muted fw-normal ms-2">(${step.time})</small></div>
                    <div class="small text-muted">${step.desc}</div>
                </div>
            </div>
        `;
    });
    timelineHtml += '</div>';

    const currStep = data.current_step || 1;

    let simulatorBar = `
        <div class="p-3 bg-white rounded-3 border mb-3 text-start shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-dark"><i class="fa-solid fa-gamepad text-warning me-1"></i> [DEMO] Bấm Để Chuyển Trạm Bưu Cục Tức Thì:</span>
                <span class="badge bg-primary">Trạm ${currStep}/5</span>
            </div>
            <div class="d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-sm ${currStep === 1 ? 'btn-success fw-bold' : 'btn-outline-secondary'} rounded-pill" onclick="simulateHubStep('${data.tracking_code}', 1)">1. Kho Vĩnh Long</button>
                <button type="button" class="btn btn-sm ${currStep === 2 ? 'btn-success fw-bold' : 'btn-outline-secondary'} rounded-pill" onclick="simulateHubStep('${data.tracking_code}', 2)">2. Kho Phân Loại</button>
                <button type="button" class="btn btn-sm ${currStep === 3 ? 'btn-success fw-bold' : 'btn-outline-secondary'} rounded-pill" onclick="simulateHubStep('${data.tracking_code}', 3)">3. Bưu Cục Đích</button>
                <button type="button" class="btn btn-sm ${currStep === 4 ? 'btn-success fw-bold' : 'btn-outline-secondary'} rounded-pill" onclick="simulateHubStep('${data.tracking_code}', 4)">4. Đang Giao</button>
                <button type="button" class="btn btn-sm ${currStep === 5 ? 'btn-success fw-bold' : 'btn-outline-secondary'} rounded-pill" onclick="simulateHubStep('${data.tracking_code}', 5)">5. Giao Thành Công</button>
            </div>
        </div>
    `;

    return `
        <div class="text-start p-2 bg-light rounded-3 mb-2 border small">
            <div><strong>Hãng vận chuyển:</strong> <span class="badge bg-primary">${data.carrier_name}</span></div>
            <div><strong>Người nhận:</strong> ${data.customer_name} (${data.phone})</div>
            <div><strong>Địa chỉ:</strong> ${data.address}</div>
        </div>
        ${simulatorBar}
        <div class="text-start fw-bold small text-dark mb-1"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>Lịch Trình Chi Tiết:</div>
        ${timelineHtml}
    `;
};

window.hasChangedCarrierStatus = false;

window.simulateHubStep = function(trackingCode, step) {
    const fd = new FormData();
    fd.append('ajax_update_carrier_step', '1');
    fd.append('tracking_code', trackingCode);
    fd.append('step', step);

    fetch('orders.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.hasChangedCarrierStatus = true;
            const container = document.getElementById('swal_tracking_content_area');
            if (container) {
                container.innerHTML = window.renderLiveTrackingContent(data);
            }

            // Đồng bộ dữ liệu ra Bảng danh sách ngoài trang
            if (data.order_id) {
                const rowEl = document.getElementById('order_row_' + data.order_id);
                if (rowEl) {
                    const statusCell = rowEl.querySelector('.col-order-status');
                    const viewBtn    = rowEl.querySelector('.btn-view-detail');
                    if (statusCell && data.status_badge && data.payment_badge) {
                        statusCell.innerHTML = `<div>${data.status_badge}</div><div class="mt-1">${data.payment_badge}</div>`;
                    }
                    if (viewBtn) {
                        try {
                            let od = JSON.parse(viewBtn.getAttribute('data-order') || '{}');
                            od.status              = data.order_status;
                            od.payment_status      = data.payment_status;
                            od.carrier_status_text = data.carrier_status_text;
                            if (data.completed_at) od.completed_at = data.completed_at;
                            viewBtn.setAttribute('data-order', JSON.stringify(od));
                        } catch(e) {}
                    }
                }
            }

            // Đồng bộ dữ liệu vào Modal Chi Tiết Đơn Hàng nếu đang mở
            const stSel = document.getElementById('form_order_status');
            const pmSel = document.getElementById('form_payment_status');
            const csEl  = document.getElementById('receipt_carrier_status');
            if (stSel && data.order_status) stSel.value = data.order_status;
            if (pmSel && data.payment_status) pmSel.value = data.payment_status;
            if (csEl && data.carrier_status_text) csEl.innerText = data.carrier_status_text;

            if (data.order_status === 'completed') {
                const pmBadge = document.getElementById('receipt_payment_status_badge');
                if (pmBadge) pmBadge.innerHTML = '<span class="badge bg-success-subtle text-success border border-success fw-bold">✅ Đã thanh toán</span>';
                const timeComp = document.getElementById('time_completed');
                if (timeComp && data.completed_at) timeComp.innerText = window.formatDate(data.completed_at);
            }

            if (window.currentViewingOrder && (window.currentViewingOrder.id == data.order_id || window.currentViewingOrder.tracking_code == trackingCode)) {
                window.currentViewingOrder.status              = data.order_status;
                window.currentViewingOrder.payment_status      = data.payment_status;
                window.currentViewingOrder.carrier_status_text = data.carrier_status_text;
                if (data.completed_at) window.currentViewingOrder.completed_at = data.completed_at;
            }

            Swal.fire({
                icon: 'success',
                title: (step === 5) ? 'ĐÃ GIAO THÀNH CÔNG!' : 'Đã Cập Nhật Trạm!',
                text: data.message,
                timer: 1600,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    })
    .catch(err => {
        console.error('Lỗi khi chuyển trạm:', err);
    });
};

// Hàm Tra Cứu Lịch Trình Bưu Cục (Live Tracking)
window.openLiveTracking = function(trackingCode) {
    window.hasChangedCarrierStatus = false;
    Swal.fire({
        title: 'Đang tra cứu hành trình...',
        text: 'Kết nối máy chủ hãng vận chuyển để lấy dữ liệu bưu cục...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const fd = new FormData();
    fd.append('ajax_track_carrier_order', '1');
    fd.append('tracking_code', trackingCode);

    fetch('orders.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: `<i class="fa-solid fa-route text-primary me-2"></i>Hành Trình: ${data.tracking_code}`,
                html: `<div id="swal_tracking_content_area">${window.renderLiveTrackingContent(data)}</div>`,
                width: 680,
                confirmButtonText: 'Đóng',
                confirmButtonColor: '#3b82f6'
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi Tra Cứu', text: data.message });
        }
    });
};
</script>

<!-- MODAL XEM TRƯỚC VÀ IN TEM / PHIẾU TRỰC TIẾP TRÊN TRANG (KHÔNG MỞ TAB MỚI) -->
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="printPreviewTitle"><i class="fa-solid fa-print me-2 text-warning"></i>Xem Trước Phiếu In</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-secondary-subtle" style="height: 520px;">
                <iframe id="printPreviewIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer bg-white border-top py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="triggerIframePrint()">
                    <i class="fa-solid fa-print me-1"></i> In Ngay (Print)
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>