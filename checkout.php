<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/sale_helpers.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để tiếp tục thanh toán.';
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Luôn đọc giỏ hàng trực tiếp từ CSDL để tránh session cũ hoặc sai variant.
$cart = [];
$cart_stmt = $conn->prepare("SELECT c.id AS item_id, c.product_id, c.variant_id, c.quantity,
                                   p.name, p.main_image AS image, p.price, p.discount_percent,
                                   v.size, v.color, v.stock_quantity
                            FROM cart_items c
                            JOIN products p ON p.id = c.product_id AND p.status = 1
                            JOIN product_variants v ON v.id = c.variant_id AND v.product_id = c.product_id
                            WHERE c.user_id = ? ORDER BY c.id ASC");
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$raw_cart = [];
$prod_ids = [];
while ($item = $cart_result->fetch_assoc()) {
    $raw_cart[] = $item;
    $prod_ids[] = intval($item['product_id']);
}
$cart_stmt->close();

$sale_events_map = get_active_sale_events_map($conn, $prod_ids);
$subtotal = 0;
foreach ($raw_cart as $item) {
    $pid = intval($item['product_id']);
    if (isset($sale_events_map[$pid]) && $sale_events_map[$pid]['has_sale']) {
        $item['price']           = floatval($sale_events_map[$pid]['sale_price']);
        $item['sale_event_name'] = $sale_events_map[$pid]['event_name'];
        $item['sale_color']      = $sale_events_map[$pid]['color_theme'];
    } else {
        $item['price']           = (float)$item['price'];
        $item['sale_event_name'] = '';
        $item['sale_color']      = '';
    }
    $item['quantity'] = (int)$item['quantity'];
    $subtotal += $item['price'] * $item['quantity'];
    $cart[] = $item;
}

if (empty($cart)) {
    unset($_SESSION['cart']);
    $_SESSION['flash_error'] = 'Giỏ hàng đang trống hoặc sản phẩm không còn khả dụng.';
    header('Location: cart.php');
    exit;
}

// Giữ session tương thích với các trang cũ, nhưng CSDL vẫn là nguồn dữ liệu chính.
$_SESSION['cart'] = $cart;

// Lấy thông tin user
$res_user = $conn->query("SELECT fullname, phone FROM users WHERE id = $user_id");
$user = $res_user ? $res_user->fetch_assoc() : ['fullname' => '', 'phone' => ''];

// Lấy danh sách địa chỉ của user
$addresses = [];
$res_addr = $conn->query("SELECT * FROM user_addresses WHERE user_id = $user_id ORDER BY is_default DESC, id DESC");
if ($res_addr) {
    while ($addr = $res_addr->fetch_assoc()) {
        $addresses[] = $addr;
    }
}

// Lấy danh sách tỉnh thành (để tính phí ship)
$provinces = [];
$res_prov = $conn->query("SELECT * FROM shipping_provinces WHERE status = 1 ORDER BY province_name ASC");
if ($res_prov) {
    while ($prov = $res_prov->fetch_assoc()) {
        $provinces[] = $prov;
    }
}

// Lấy danh sách voucher hợp lệ và voucher đã lưu của user
$current_date = date('Y-m-d H:i:s');

// Phân tích giỏ hàng để lấy danh sách sale_event_id và brand_id của sản phẩm
$cart_event_ids = [];
$cart_brand_ids = [];
if (!empty($prod_ids)) {
    $pids_str = implode(',', $prod_ids);
    $res_ep = $conn->query("
        SELECT DISTINCT ep.event_id 
        FROM event_products ep 
        JOIN sale_events se ON ep.event_id = se.id 
        WHERE ep.product_id IN ($pids_str) 
          AND se.status = 1 
          AND se.start_date <= NOW() 
          AND se.end_date >= NOW()
    ");
    if ($res_ep) {
        while ($ep_row = $res_ep->fetch_assoc()) {
            $cart_event_ids[] = intval($ep_row['event_id']);
        }
    }
    $res_pb = $conn->query("SELECT DISTINCT brand_id FROM products WHERE id IN ($pids_str)");
    if ($res_pb) {
        while ($pb_row = $res_pb->fetch_assoc()) {
            if (!empty($pb_row['brand_id'])) $cart_brand_ids[] = intval($pb_row['brand_id']);
        }
    }
}

// Kiểm tra user có phải là người mới không (chưa có đơn hàng nào không bị hủy)
$res_user_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id = $user_id AND status != 'cancelled'");
$user_order_count = $res_user_orders ? intval($res_user_orders->fetch_assoc()['cnt']) : 0;
$is_new_user = ($user_order_count === 0);

$vouchers = [];
$res_vouchers = $conn->query("
    SELECT v.*, b.name as brand_name, se.name as event_name 
    FROM vouchers v 
    LEFT JOIN brands b ON v.brand_id = b.id
    LEFT JOIN sale_events se ON v.sale_event_id = se.id
    WHERE v.status = 1 
      AND (v.start_date IS NULL OR v.start_date <= '$current_date') 
      AND (v.end_date IS NULL OR v.end_date >= '$current_date')
      AND (v.usage_limit IS NULL OR v.usage_limit = 0 OR v.used_count < v.usage_limit)
    ORDER BY (v.min_order_value <= $subtotal) DESC, v.id DESC
");
if ($res_vouchers) {
    while ($v = $res_vouchers->fetch_assoc()) {
        $vouchers[] = $v;
    }
}

// Xử lý submit đặt hàng
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['place_order'])) {
    $conn->begin_transaction();
    try {
        $address_id = $_POST['address_id'] ?? '';
        
        $customer_name = '';
        $phone = '';
        $province_id = 0;
        $address_detail = '';

        if ($address_id === 'new' || empty($address_id)) {
            $customer_name = trim($_POST['new_name'] ?? $user['fullname']);
            $phone = trim($_POST['new_phone'] ?? $user['phone']);
            $province_id = intval($_POST['new_province_id'] ?? 0);
            $address_detail = trim($_POST['new_address_detail'] ?? '');
            
            if (empty($customer_name) || empty($phone) || empty($province_id) || empty($address_detail)) {
                throw new Exception('Vui lòng điền đầy đủ thông tin giao hàng.');
            }
            
            if (isset($_POST['save_address'])) {
                $c_name = $conn->real_escape_string($customer_name);
                $c_phone = $conn->real_escape_string($phone);
                $c_detail = $conn->real_escape_string($address_detail);
                $conn->query("INSERT INTO user_addresses (user_id, recipient_name, phone, province_id, address_detail, is_default) VALUES ($user_id, '$c_name', '$c_phone', $province_id, '$c_detail', 0)");
            }
        } else {
            // Lấy thông tin từ địa chỉ đã chọn
            $addr_id = intval($address_id);
            $res_a = $conn->query("SELECT * FROM user_addresses WHERE id = $addr_id AND user_id = $user_id");
            $addr = $res_a ? $res_a->fetch_assoc() : null;
            if (!$addr) throw new Exception('Địa chỉ chọn không hợp lệ.');
            
            $customer_name = $addr['recipient_name'];
            $phone = $addr['phone'];
            $province_id = intval($addr['province_id']);
            $address_detail = $addr['address_detail'];
        }

        // Tính phí ship bằng CarrierShippingService
        require_once __DIR__ . '/includes/CarrierShippingService.php';
        $carrier_service = new CarrierShippingService($conn);
        $active_carrier = $carrier_service->getActiveCarrier();

        $prov_name = '';
        $res_pfee = $conn->query("SELECT province_name, shipping_fee FROM shipping_provinces WHERE id = $province_id");
        if ($res_pfee && $prov_row = $res_pfee->fetch_assoc()) {
            $prov_name = $prov_row['province_name'];
        }

        $shipping_carrier = !empty($_POST['shipping_carrier']) ? strtoupper(trim($_POST['shipping_carrier'])) : $active_carrier;
        if (!in_array($shipping_carrier, ['GHTK', 'GHN', 'LOCAL'])) $shipping_carrier = 'GHTK';

        $cart_weight = count($cart) * intval($carrier_service->getSetting('default_shoe_weight', 800));
        $calc_res = $carrier_service->calculateFee($prov_name, '', '', $address_detail, $cart_weight, $subtotal, $shipping_carrier);
        $shipping_fee = floatval($calc_res['fee'] ?? 30000);

        // Phí vận chuyển được tính chuẩn theo hãng / tỉnh thành
        // Miễn phí vận chuyển chỉ áp dụng khi khách hàng dùng mã giảm giá Freeship


        // Áp dụng voucher
        $voucher_code = null;
        $discount_amount = 0;
        $voucher_id = 0;
        if (!empty($_POST['voucher_code'])) {
            $code = $conn->real_escape_string(trim($_POST['voucher_code']));
            $res_vc = $conn->query("SELECT * FROM vouchers WHERE code = '$code' AND status = 1 AND (start_date IS NULL OR start_date <= '$current_date') AND (end_date IS NULL OR end_date >= '$current_date')");
            $v = $res_vc ? $res_vc->fetch_assoc() : null;
            
            if (!$v) {
                throw new Exception("Mã giảm giá $code không tồn tại hoặc đã hết hạn.");
            }
            if ($subtotal < floatval($v['min_order_value'])) {
                throw new Exception("Đơn hàng chưa đạt giá trị tối thiểu " . number_format($v['min_order_value'], 0, ',', '.') . "đ để dùng mã $code.");
            }
            if ($v['event_type'] === 'new_user') {
                $res_uo = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id = $user_id AND status != 'cancelled'");
                $user_order_cnt = $res_uo ? intval($res_uo->fetch_assoc()['cnt']) : 0;
                if ($user_order_cnt > 0) {
                    throw new Exception("Mã ưu đãi $code chỉ dành riêng cho tài khoản mới / đơn hàng đầu tiên.");
                }
            }
            if (!empty($v['sale_event_id'])) {
                if (!in_array(intval($v['sale_event_id']), $cart_event_ids)) {
                    throw new Exception("Mã ưu đãi $code chỉ áp dụng cho sản phẩm trong Sự Kiện Sale tương ứng.");
                }
            }
            if (!empty($v['brand_id'])) {
                if (!in_array(intval($v['brand_id']), $cart_brand_ids)) {
                    throw new Exception("Mã ưu đãi $code chỉ áp dụng cho các sản phẩm thương hiệu tương ứng.");
                }
            }
            if ($v['usage_limit'] > 0 && $v['used_count'] >= $v['usage_limit']) {
                throw new Exception("Mã ưu đãi $code đã hết lượt sử dụng.");
            }

            $vid = intval($v['id']);
            $res_uv = $conn->query("SELECT COUNT(*) as cnt FROM user_vouchers WHERE user_id = $user_id AND voucher_id = $vid AND used_at IS NOT NULL");
            $user_used = $res_uv ? intval($res_uv->fetch_assoc()['cnt']) : 0;
            $per_user_limit = intval($v['per_user_limit'] ?? 1);
            if ($user_used >= $per_user_limit) {
                throw new Exception("Bạn đã sử dụng hết số lần cho phép của mã $code.");
            }

            $voucher_code = $v['code'];
            $voucher_id = $vid;
            $disc_val = floatval($v['discount_value']);
            $max_disc = floatval($v['max_discount'] ?? 0);

            if ($v['discount_type'] === 'fixed') {
                $discount_amount = $disc_val;
            } elseif ($v['discount_type'] === 'percent') {
                $discount_amount = $subtotal * ($disc_val / 100);
                if ($max_disc > 0 && $discount_amount > $max_disc) {
                    $discount_amount = $max_disc;
                }
            } elseif ($v['discount_type'] === 'freeship') {
                $discount_amount = min($shipping_fee, $max_disc > 0 ? $max_disc : $shipping_fee);
            }
        }

        $total_money = $subtotal + $shipping_fee - $discount_amount;
        if ($total_money < 0) $total_money = 0;

        $payment_method = $_POST['payment_method'] ?? 'COD';
        if (!in_array($payment_method, ['COD', 'BANKING_QR'])) {
            $payment_method = 'COD';
        }

        $note = trim($_POST['note'] ?? '');
        $order_code = 'SH' . date('YmdHis') . rand(10, 99);

        // Escape string data for Insert
        $c_name_esc   = $conn->real_escape_string($customer_name);
        $c_phone_esc  = $conn->real_escape_string($phone);
        $c_addr_esc   = $conn->real_escape_string($address_detail);
        $note_esc     = $conn->real_escape_string($note);
        $v_code_sql   = $voucher_code ? "'" . $conn->real_escape_string($voucher_code) . "'" : "NULL";

        // Insert order with shipping_carrier
        $sql_order = "INSERT INTO orders (order_code, user_id, customer_name, phone, address_detail, province_id, shipping_carrier, shipping_fee, subtotal, discount_amount, voucher_code, total_money, payment_method, payment_status, status, note, created_at) 
                      VALUES ('$order_code', $user_id, '$c_name_esc', '$c_phone_esc', '$c_addr_esc', $province_id, '$shipping_carrier', $shipping_fee, $subtotal, $discount_amount, $v_code_sql, $total_money, '$payment_method', 'unpaid', 'pending', '$note_esc', NOW())";
        
        if (!$conn->query($sql_order)) {
            throw new Exception('Lỗi tạo đơn hàng: ' . $conn->error);
        }
        $order_id = $conn->insert_id;

        // Insert order details & Deduct stock
        foreach ($cart as $item) {
            $pid   = intval($item['product_id']);
            $vid   = intval($item['variant_id']);
            $qty   = intval($item['quantity']);
            $price = floatval($item['price']);
            $pname = $conn->real_escape_string($item['name']);
            $pimg  = $conn->real_escape_string($item['image']);
            $psize = $conn->real_escape_string($item['size']);
            $pcolor= $conn->real_escape_string($item['color'] ?? '');

            // Check stock
            $res_stk = $conn->query("SELECT stock_quantity FROM product_variants WHERE id = $vid AND product_id = $pid AND stock_quantity >= $qty FOR UPDATE");
            if (!$res_stk || $res_stk->num_rows === 0) {
                throw new Exception('Sản phẩm ' . $item['name'] . ' - Size ' . $item['size'] . ' không đủ số lượng tồn kho.');
            }

            // Deduct variant stock & update product sold count
            if (!$conn->query("UPDATE product_variants SET stock_quantity = stock_quantity - $qty WHERE id = $vid AND product_id = $pid AND stock_quantity >= $qty") || $conn->affected_rows !== 1) {
                throw new Exception('Không thể cập nhật tồn kho cho sản phẩm ' . $item['name'] . '.');
            }
            $conn->query("UPDATE products SET sold_count = sold_count + $qty WHERE id = $pid");

            // Fetch product cost_price if available
            $cost_price = 0;
            $res_cp = $conn->query("SELECT cost_price FROM products WHERE id = $pid");
            if ($res_cp && $cp = $res_cp->fetch_assoc()) {
                $cost_price = floatval($cp['cost_price']);
            }

            $sql_detail = "INSERT INTO order_details (order_id, product_id, variant_id, product_name, product_image, size, color, quantity, price, cost_price) 
                           VALUES ($order_id, $pid, $vid, '$pname', '$pimg', '$psize', '$pcolor', $qty, $price, $cost_price)";
            if (!$conn->query($sql_detail)) {
                throw new Exception('Lỗi lưu chi tiết đơn hàng: ' . $conn->error);
            }
        }

        // Record Voucher usage if applied
        if ($voucher_id > 0) {
            $conn->query("INSERT INTO user_vouchers (user_id, voucher_id, saved_at, used_at) VALUES ($user_id, $voucher_id, NOW(), NOW()) ON DUPLICATE KEY UPDATE used_at = NOW()");
            $conn->query("UPDATE vouchers SET used_count = used_count + 1 WHERE id = $voucher_id");
        }

        $conn->commit();
        
        // Clear cart in DB & Session
        $conn->query("DELETE FROM cart_items WHERE user_id = $user_id");
        unset($_SESSION['cart']);
        
        if ($payment_method === 'BANKING_QR') {
            header("Location: payment-qr.php?code=" . urlencode($order_code));
        } else {
            header("Location: order-success.php?code=" . urlencode($order_code));
        }
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Tính toán cước phí và nhà vận chuyển ban đầu để render khớp 100%
require_once __DIR__ . '/includes/CarrierShippingService.php';
$carrier_service = new CarrierShippingService($conn);
$active_carrier = $carrier_service->getActiveCarrier();
if (!in_array($active_carrier, ['GHTK', 'GHN'])) $active_carrier = 'GHTK';

$cart_weight = max(100, count($cart) * intval($carrier_service->getSetting('default_shoe_weight', 800)));

$initial_prov_id = 0;
$initial_prov_name = '';
$initial_addr_detail = '';

if (!empty($addresses)) {
    $initial_prov_id = intval($addresses[0]['province_id']);
    $initial_addr_detail = $addresses[0]['address_detail'];
    foreach ($provinces as $p) {
        if (intval($p['id']) === $initial_prov_id) {
            $initial_prov_name = $p['province_name'];
            break;
        }
    }
} else {
    // Mặc định Tỉnh Vĩnh Long (Kho Shop tại Vĩnh Long)
    $initial_prov_name = 'Vĩnh Long';
    foreach ($provinces as $p) {
        if ($p['province_name'] === 'Vĩnh Long' || intval($p['id']) === 4) {
            $initial_prov_id = intval($p['id']);
            $initial_prov_name = $p['province_name'];
            break;
        }
    }
}

$initial_all_carriers = $carrier_service->calculateAllCarriersFee($initial_prov_name, '', '', $initial_addr_detail, $cart_weight, $subtotal);
$initial_ghtk_fee = $initial_all_carriers['carriers']['GHTK']['fee'] ?? 15000;
$initial_ghn_fee = $initial_all_carriers['carriers']['GHN']['fee'] ?? 19000;
$initial_shipping_fee = ($active_carrier === 'GHN') ? $initial_ghn_fee : $initial_ghtk_fee;
$initial_total = max(0, $subtotal + $initial_shipping_fee);

$page_title = "Thanh toán đơn hàng";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4 text-dark font-weight-bold text-uppercase">Thanh Toán Đơn Hàng</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger fw-bold shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" id="checkout-form">
        <div class="row g-4">
            <!-- Left side: Order form -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-white">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-location-dot me-2 text-warning"></i>Thông tin giao hàng</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($addresses)): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Chọn địa chỉ đã lưu trong tài khoản:</label>
                                <select class="form-select fw-bold" name="address_id" id="address_id" onchange="toggleNewAddress()">
                                    <?php foreach ($addresses as $addr): 
                                        $pname = '';
                                        foreach ($provinces as $p) {
                                            if (intval($p['id']) === intval($addr['province_id'])) {
                                                $pname = $p['province_name'];
                                                break;
                                            }
                                        }
                                    ?>
                                        <option value="<?= $addr['id'] ?>" data-province="<?= $addr['province_id'] ?>" data-province-name="<?= htmlspecialchars($pname) ?>" data-address-detail="<?= htmlspecialchars($addr['address_detail']) ?>">
                                            <?= htmlspecialchars($addr['recipient_name']) ?> - <?= htmlspecialchars($addr['phone']) ?> - <?= htmlspecialchars($addr['address_detail']) ?><?= !empty($pname) ? ' (' . htmlspecialchars($pname) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new">-- Thêm địa chỉ nhận hàng mới --</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="address_id" id="address_id" value="new">
                        <?php endif; ?>

                        <div id="new-address-form" style="<?= !empty($addresses) ? 'display: none;' : '' ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Tên người nhận (*)</label>
                                    <input type="text" class="form-control" name="new_name" value="<?= empty($addresses) ? htmlspecialchars($user['fullname']) : '' ?>" <?= empty($addresses) ? 'required' : '' ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Số điện thoại (*)</label>
                                    <input type="text" class="form-control" name="new_phone" value="<?= empty($addresses) ? htmlspecialchars($user['phone']) : '' ?>" <?= empty($addresses) ? 'required' : '' ?>>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tỉnh / Thành phố (*)</label>
                                <select class="form-select fw-bold" name="new_province_id" id="new_province_id" onchange="updateShippingFee()">
                                    <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                    <?php foreach ($provinces as $prov): 
                                        $is_default_vl = ($prov['province_name'] === 'Vĩnh Long' || $prov['id'] == 4);
                                    ?>
                                        <option value="<?= $prov['id'] ?>" data-fee="<?= $prov['shipping_fee'] ?>" <?= $is_default_vl ? 'selected' : '' ?>><?= htmlspecialchars($prov['province_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0">Địa chỉ chi tiết (Số nhà, đường, phường/xã, quận/huyện) (*)</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" onclick="openMapPicker('new_address_detail', 'new_province_id')">
                                        <i class="fa-solid fa-map-location-dot me-1 text-primary"></i> 🗺️ Chọn trên bản đồ
                                    </button>
                                </div>
                                <textarea class="form-control" name="new_address_detail" id="new_address_detail" rows="2" placeholder="VD: Số 123 Đường Nguyễn Trãi, Phường 2, Quận 5" <?= empty($addresses) ? 'required' : '' ?>></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="save_address" value="1" id="save_address" checked>
                                <label class="form-check-label fw-bold" for="save_address">Lưu địa chỉ này vào hồ sơ cho lần mua sau</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Ghi chú đơn hàng (Tùy chọn)</label>
                            <textarea class="form-control" name="note" rows="3" placeholder="Ghi chú thêm cho nhân viên giao hàng..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- CHỌN ĐƠN VỊ VẬN CHUYỂN (GHTK vs GHN) -->
                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-white">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-truck-fast me-2 text-warning"></i>Đơn vị vận chuyển</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- GHTK Card -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-light carrier-checkout-card <?= $active_carrier === 'GHTK' ? 'active border-success bg-success-subtle' : '' ?>" id="card_carrier_ghtk" onclick="selectCheckoutCarrier('GHTK')" style="cursor: pointer; transition: all 0.2s ease;">
                                    <div class="form-check p-0 m-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <input class="form-check-input ms-0 mt-0" type="radio" name="shipping_carrier" id="carrier_ghtk" value="GHTK" <?= $active_carrier === 'GHTK' ? 'checked' : '' ?> onchange="updateShippingFee()">
                                                <label class="form-check-label fw-bold text-dark mb-0" for="carrier_ghtk">
                                                    <i class="fa-solid fa-truck-fast text-success me-1"></i> GHTK
                                                </label>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small fw-bold" id="ghtk_badge">Tiết kiệm</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 ps-4">
                                            <small class="text-muted" id="ghtk_time_text">1-2 ngày</small>
                                            <strong class="text-danger fs-6" id="ghtk_fee_text"><?= ($initial_ghtk_fee == 0) ? '0đ (Freeship)' : (number_format($initial_ghtk_fee, 0, ',', '.') . 'đ') ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GHN Card -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-light carrier-checkout-card <?= $active_carrier === 'GHN' ? 'active border-primary bg-primary-subtle' : '' ?>" id="card_carrier_ghn" onclick="selectCheckoutCarrier('GHN')" style="cursor: pointer; transition: all 0.2s ease;">
                                    <div class="form-check p-0 m-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <input class="form-check-input ms-0 mt-0" type="radio" name="shipping_carrier" id="carrier_ghn" value="GHN" <?= $active_carrier === 'GHN' ? 'checked' : '' ?> onchange="updateShippingFee()">
                                                <label class="form-check-label fw-bold text-dark mb-0" for="carrier_ghn">
                                                    <i class="fa-solid fa-paper-plane text-primary me-1"></i> GHN Express
                                                </label>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-bold" id="ghn_badge">Giao nhanh 24h</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 ps-4">
                                            <small class="text-muted" id="ghn_time_text">Trong 24h</small>
                                            <strong class="text-danger fs-6" id="ghn_fee_text"><?= ($initial_ghn_fee == 0) ? '0đ (Freeship)' : (number_format($initial_ghn_fee, 0, ',', '.') . 'đ') ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-white">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-credit-card me-2 text-warning"></i>Phương thức thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3 p-3 border rounded-3 bg-light payment-method-card active border-primary bg-primary-subtle" id="card_payment_cod" onclick="selectPaymentMethod('COD')" style="cursor: pointer; transition: all 0.2s ease;">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="payment_cod" value="COD" checked onchange="selectPaymentMethod('COD')">
                            <label class="form-check-label fw-bold ms-2 text-dark" for="payment_cod">
                                💵 Thanh toán khi nhận hàng (COD)
                            </label>
                            <div class="text-muted small ms-4 mt-1">Khách hàng kiểm tra hàng và thanh toán tiền mặt trực tiếp cho shipper.</div>
                        </div>
                        <div class="form-check p-3 border rounded-3 bg-light payment-method-card" id="card_payment_qr" onclick="selectPaymentMethod('BANKING_QR')" style="cursor: pointer; transition: all 0.2s ease;">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="payment_qr" value="BANKING_QR" onchange="selectPaymentMethod('BANKING_QR')">
                            <label class="form-check-label fw-bold ms-2 text-dark" for="payment_qr">
                                📱 Chuyển khoản ngân hàng qua VietQR
                            </label>
                            <div class="text-muted small ms-4 mt-1">Mã QR động sẽ tự nhảy đúng số tiền & nội dung sau khi đặt hàng thành công.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side: Order summary -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 rounded-4 bg-white sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4 border-bottom pb-3"><i class="fa-solid fa-cart-flatbed me-2 text-warning"></i>Tóm tắt đơn hàng</h5>
                        
                        <div class="mb-4" style="max-height: 280px; overflow-y: auto;">
                            <?php foreach ($cart as $item): ?>
                            <div class="d-flex mb-3 align-items-center border-bottom pb-2">
                                <img src="<?= htmlspecialchars($item['image']) ?>" class="rounded-3 me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fs-6 fw-bold"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-muted">Size: <strong><?= htmlspecialchars($item['size']) ?></strong> <?= !empty($item['color']) ? '| ' . htmlspecialchars($item['color']) : '' ?> x <strong><?= $item['quantity'] ?></strong></small>
                                    <?php if (!empty($item['sale_event_name'])): ?>
                                        <div class="mt-1">
                                            <span class="badge" style="background: <?= htmlspecialchars($item['sale_color']) ?>; color: #fff; font-size: 10px;">
                                                <i class="fa-solid fa-bolt me-1"></i><?= htmlspecialchars($item['sale_event_name']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end fw-bold text-danger">
                                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Professional Interactive Voucher Section -->
                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-ticket me-1 text-warning"></i>Ưu Đãi / Voucher Giảm Giá</span>
                                <span class="badge bg-warning text-dark" id="voucher-status-badge" style="display: none;">Đã áp dụng</span>
                            </label>
                            
                            <!-- Trigger button opening interactive voucher modal -->
                            <div class="checkout-voucher-trigger" id="checkoutVoucherTrigger" data-bs-toggle="modal" data-bs-target="#checkoutVoucherModal">
                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                    <div class="p-2 rounded-3 bg-warning text-dark flex-shrink-0">
                                        <i class="fa-solid fa-ticket-simple fs-5"></i>
                                    </div>
                                    <div class="text-truncate" id="voucherTriggerText">
                                        <div class="fw-bold text-dark fs-6" id="selectedVoucherName">Chọn hoặc nhập mã ưu đãi</div>
                                        <div class="small text-muted text-truncate" id="selectedVoucherDesc">Xem danh sách <?= count($vouchers) ?> mã giảm giá khả dụng</div>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted flex-shrink-0 ms-2"></i>
                            </div>

                            <!-- Hidden inputs carrying applied voucher details -->
                            <input type="hidden" name="voucher_code" id="voucher_code" value="">
                            <input type="hidden" id="voucher_type" value="">
                            <input type="hidden" id="voucher_val" value="0">
                            <input type="hidden" id="voucher_max" value="0">
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tạm tính tiền hàng</span>
                            <span class="fw-bold text-dark" id="display-subtotal" data-val="<?= $subtotal ?>"><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Phí vận chuyển</span>
                            <span class="fw-bold text-dark" id="display-shipping" data-val="<?= $initial_shipping_fee ?>"><?= ($initial_shipping_fee == 0) ? '0đ (Miễn phí)' : (number_format($initial_shipping_fee, 0, ',', '.') . 'đ') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-muted" id="carrier-info-row">
                            <span class="small"><i class="fa-solid fa-truck-fast text-primary me-1"></i>Hãng vận chuyển:</span>
                            <span class="badge bg-light text-dark border small fw-bold" id="display-carrier-text"><?= $active_carrier === 'GHN' ? 'Giao Hàng Nhanh (GHN Express)' : 'Giao Hàng Tiết Kiệm (GHTK)' ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="discount-row" style="display: none !important;">
                            <span class="fw-bold"><i class="fa-solid fa-tag me-1"></i>Giảm giá Voucher (<span id="applied-voucher-label"></span>)</span>
                            <span class="fw-bold" id="display-discount">-0đ</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4 align-items-center">
                            <span class="fw-bold fs-5 text-dark">Tổng Thanh Toán</span>
                            <span class="fw-bold fs-3 text-danger" id="display-total"><?= number_format($initial_total, 0, ',', '.') ?>đ</span>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-warning w-100 py-3 fw-bold text-dark rounded-3 shadow">
                            <i class="fa-solid fa-circle-check me-2"></i> XÁC NHẬN ĐẶT HÀNG
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL CHỌN VOUCHER THANH TOÁN (TIỆN LỢI + TÍNH TOÁN TIỀN TIẾT KIỆM) -->
<div class="modal fade" id="checkoutVoucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-ticket text-warning fs-4"></i>
                    <h5 class="modal-title fw-bold text-white mb-0">CHỌN SHOESSTORE VOUCHER</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4 bg-light" style="max-height: 68vh; overflow-y: auto;">
                <!-- Manual Code Input -->
                <div class="bg-white p-3 rounded-4 shadow-sm mb-4 border">
                    <label class="form-label fw-bold text-dark small mb-2"><i class="fa-solid fa-gift text-warning me-1"></i>Nhập mã giảm giá khác:</label>
                    <div class="input-group">
                        <input type="text" id="manualVoucherInput" class="form-control fw-bold font-monospace text-uppercase" placeholder="VD: SHOES50K, FREESHIP..." style="letter-spacing: 1.2px;">
                        <button type="button" class="btn btn-warning fw-bold px-4" onclick="applyManualVoucherCode()">
                            Áp Dụng
                        </button>
                    </div>
                    <div id="manualVoucherError" class="text-danger small fw-bold mt-1" style="display: none;"></div>
                </div>

                <!-- Voucher list -->
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-layer-group me-1 text-primary"></i>Danh sách mã giảm giá của bạn</h6>
                
                <?php if (empty($vouchers)): ?>
                    <div class="text-center py-5 bg-white rounded-4 border">
                        <i class="fa-solid fa-ticket-simple fa-3x text-muted mb-3 opacity-50"></i>
                        <p class="text-muted fw-bold">Hiện không có mã giảm giá nào đang diễn ra.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3" id="checkoutVouchersContainer">
                        <?php foreach ($vouchers as $v): 
                            $is_min_order_ok = ($subtotal >= floatval($v['min_order_value']));
                            $needed_more = max(0, floatval($v['min_order_value']) - $subtotal);
                            
                            $is_new_user_ok = ($v['event_type'] !== 'new_user' || $is_new_user);
                            $is_event_ok = (empty($v['sale_event_id']) || in_array(intval($v['sale_event_id']), $cart_event_ids));
                            $is_brand_ok = (empty($v['brand_id']) || in_array(intval($v['brand_id']), $cart_brand_ids));

                            $is_eligible = ($is_min_order_ok && $is_new_user_ok && $is_event_ok && $is_brand_ok);

                            $ineligible_msg = '';
                            if (!$is_min_order_ok) {
                                $ineligible_msg = '<i class="fa-solid fa-circle-exclamation me-1"></i>Mua thêm <strong>' . number_format($needed_more, 0, ',', '.') . 'đ</strong> để sử dụng mã này';
                            } elseif (!$is_new_user_ok) {
                                $ineligible_msg = '<i class="fa-solid fa-user-lock me-1"></i>Chỉ áp dụng cho tài khoản mới / đơn hàng đầu tiên';
                            } elseif (!$is_event_ok) {
                                $ineligible_msg = '<i class="fa-solid fa-calendar-xmark me-1"></i>Chỉ áp dụng cho sản phẩm trong Sự Kiện Sale ' . (!empty($v['event_name']) ? ('"' . htmlspecialchars($v['event_name']) . '"') : '');
                            } elseif (!$is_brand_ok) {
                                $ineligible_msg = '<i class="fa-solid fa-ban me-1"></i>Chỉ áp dụng cho sản phẩm thương hiệu ' . htmlspecialchars($v['brand_name'] ?? '');
                            }

                            $vtype = $v['discount_type'];

                            // Calculate estimated discount
                            $est_discount = 0;
                            if ($vtype === 'fixed') {
                                $est_discount = floatval($v['discount_value']);
                            } elseif ($vtype === 'percent') {
                                $est_discount = $subtotal * (floatval($v['discount_value']) / 100);
                                if (floatval($v['max_discount']) > 0 && $est_discount > floatval($v['max_discount'])) {
                                    $est_discount = floatval($v['max_discount']);
                                }
                            } elseif ($vtype === 'freeship') {
                                $est_discount = 30000;
                            }

                            if ($vtype === 'freeship') {
                                $theme_class = 'voucher-theme-emerald';
                                $stub_icon = 'fa-solid fa-truck-fast';
                                $stub_val = 'FREE';
                                $stub_label = 'FREESHIP';
                                $disc_badge = 'Miễn phí vận chuyển';
                            } elseif ($vtype === 'percent') {
                                $theme_class = 'voucher-theme-gold';
                                $stub_icon = 'fa-solid fa-percent';
                                $stub_val = intval($v['discount_value']) . '%';
                                $stub_label = 'GIẢM GIÁ';
                                $disc_badge = 'Giảm ' . intval($v['discount_value']) . '%';
                            } else {
                                $theme_class = 'voucher-theme-crimson';
                                $stub_icon = 'fa-solid fa-tag';
                                $stub_val = (floatval($v['discount_value']) >= 1000) ? (intval($v['discount_value'] / 1000) . 'K') : number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                $stub_label = 'GIẢM TIỀN';
                                $disc_badge = 'Giảm ' . number_format($v['discount_value'], 0, ',', '.') . 'đ';
                            }
                        ?>
                            <div class="col-12 checkout-voucher-card-col">
                                <div class="voucher-ticket <?= $theme_class ?> <?= !$is_eligible ? 'opacity-50 grayscale' : '' ?> m-0 position-relative shadow-sm" style="border: 1.5px solid <?= $is_eligible ? '#e2e8f0' : '#f1f5f9' ?>;">
                                    <!-- Cuống vé -->
                                    <div class="voucher-ticket-stub">
                                        <i class="<?= $stub_icon ?> voucher-stub-icon"></i>
                                        <div class="voucher-stub-value"><?= $stub_val ?></div>
                                        <div class="voucher-stub-label"><?= $stub_label ?></div>
                                    </div>
                                    
                                    <!-- Divider -->
                                    <div class="voucher-ticket-divider">
                                        <div class="voucher-notch voucher-notch-top" style="background-color: #f8fafc;"></div>
                                        <div class="voucher-notch voucher-notch-bottom" style="background-color: #f8fafc;"></div>
                                    </div>

                                    <!-- Body -->
                                    <div class="voucher-ticket-body">
                                        <div class="voucher-info-wrapper">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="voucher-badge-type">
                                                    <?= $disc_badge ?>
                                                </span>
                                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($v['code']) ?></span>
                                            </div>
                                            <h6 class="voucher-title text-dark mb-1"><?= htmlspecialchars($v['title']) ?></h6>
                                            <div class="voucher-conditions small">
                                                Đơn tối thiểu: <strong><?= number_format($v['min_order_value'], 0, ',', '.') ?>đ</strong>
                                                <span class="mx-1">•</span>
                                                HSD: <strong><?= date('d/m/Y', strtotime($v['end_date'])) ?></strong>
                                            </div>
                                            <?php if (!$is_eligible): ?>
                                                <div class="text-danger small fw-bold mt-1">
                                                    <?= $ineligible_msg ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-success small fw-bold mt-1">
                                                    <i class="fa-solid fa-sparkles me-1"></i>Tiết kiệm ngay <strong><?= number_format($est_discount, 0, ',', '.') ?>đ</strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="voucher-action-area">
                                            <?php if ($is_eligible): ?>
                                                <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 shadow-sm btn-select-voucher"
                                                        data-code="<?= htmlspecialchars($v['code']) ?>"
                                                        data-type="<?= $v['discount_type'] ?>"
                                                        data-val="<?= $v['discount_value'] ?>"
                                                        data-max="<?= $v['max_discount'] ?>"
                                                        data-title="<?= htmlspecialchars($v['title']) ?>"
                                                        data-badge="<?= $disc_badge ?>"
                                                        onclick="selectCheckoutVoucher(this)">
                                                    Áp Dụng
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary btn-sm rounded-pill fw-bold px-3 opacity-50" disabled>
                                                    Chưa đủ điều kiện
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer bg-white d-flex justify-content-between px-4 py-3">
                <button type="button" class="btn btn-outline-danger rounded-pill fw-bold px-3" onclick="clearCheckoutVoucher()">
                    <i class="fa-solid fa-trash-can me-1"></i> Bỏ chọn mã
                </button>
                <button type="button" class="btn btn-dark rounded-pill fw-bold px-4" data-bs-dismiss="modal">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const provincesData = {
        <?php foreach ($provinces as $prov): ?>
            "<?= $prov['id'] ?>": <?= $prov['shipping_fee'] ?>,
        <?php endforeach; ?>
    };

    function toggleNewAddress() {
        const addressSelect = document.getElementById('address_id');
        const newAddressForm = document.getElementById('new-address-form');
        const isNew = addressSelect.value === 'new';
        
        if (newAddressForm) {
            newAddressForm.style.display = isNew ? 'block' : 'none';
            const inputs = newAddressForm.querySelectorAll('input[type="text"], textarea, select');
            inputs.forEach(input => {
                if (input.name !== 'save_address') {
                    input.required = isNew;
                }
            });
        }
        
        updateShippingFee();
    }

    function selectPaymentMethod(method) {
        document.querySelectorAll('.payment-method-card').forEach(el => el.classList.remove('active', 'border-primary', 'bg-primary-subtle'));
        if (method === 'BANKING_QR') {
            const qrRadio = document.getElementById('payment_qr');
            if (qrRadio) qrRadio.checked = true;
            const qrCard = document.getElementById('card_payment_qr');
            if (qrCard) qrCard.classList.add('active', 'border-primary', 'bg-primary-subtle');
        } else {
            const codRadio = document.getElementById('payment_cod');
            if (codRadio) codRadio.checked = true;
            const codCard = document.getElementById('card_payment_cod');
            if (codCard) codCard.classList.add('active', 'border-primary', 'bg-primary-subtle');
        }
    }

    function selectCheckoutCarrier(carrier) {
        document.querySelectorAll('.carrier-checkout-card').forEach(el => el.classList.remove('active', 'border-primary', 'bg-primary-subtle', 'border-success', 'bg-success-subtle'));
        if (carrier === 'GHN') {
            const r = document.getElementById('carrier_ghn');
            if (r) r.checked = true;
            const card = document.getElementById('card_carrier_ghn');
            if (card) card.classList.add('active', 'border-primary', 'bg-primary-subtle');
        } else {
            const r = document.getElementById('carrier_ghtk');
            if (r) r.checked = true;
            const card = document.getElementById('card_carrier_ghtk');
            if (card) card.classList.add('active', 'border-success', 'bg-success-subtle');
        }
        updateShippingFee();
    }

    function updateShippingFee() {
        const addressSelect = document.getElementById('address_id');
        let provId = 0;
        let provName = '';
        let addressDetail = '';
        
        if (!addressSelect || addressSelect.value === 'new' || addressSelect.value === '') {
            const provSelect = document.getElementById('new_province_id');
            if (provSelect && provSelect.value) {
                provId = parseInt(provSelect.value) || 0;
                provName = provSelect.options[provSelect.selectedIndex] ? provSelect.options[provSelect.selectedIndex].text : '';
                if (provName.includes('--')) provName = '';
            }
            const addrInput = document.getElementById('new_address_detail');
            if (addrInput) addressDetail = addrInput.value;
        } else if (addressSelect && addressSelect.selectedIndex >= 0) {
            const selectedOption = addressSelect.options[addressSelect.selectedIndex];
            if (selectedOption) {
                provId = parseInt(selectedOption.getAttribute('data-province')) || 0;
                provName = selectedOption.getAttribute('data-province-name') || '';
                addressDetail = selectedOption.getAttribute('data-address-detail') || '';
            }
        }

        // Mặc định Vĩnh Long nếu chưa chọn
        if (!provId && !provName) {
            provId = 4;
            provName = 'Vĩnh Long';
        }

        const selectedCarrierInput = document.querySelector('input[name="shipping_carrier"]:checked');
        const selectedCarrier = selectedCarrierInput ? selectedCarrierInput.value : 'GHTK';
        const subtotal = parseFloat(document.getElementById('display-subtotal').getAttribute('data-val')) || 0;
        const weight = <?= $cart_weight ?>;

        // Tính cước tức thời local (0ms response)
        const baseFee = (provId && provincesData[provId]) ? provincesData[provId] : 15000;
        const instantFee = (selectedCarrier === 'GHN') ? (baseFee + 4000) : baseFee;
        
        const shipEl = document.getElementById('display-shipping');
        if (shipEl) {
            shipEl.innerText = (instantFee === 0) ? '0đ (Miễn phí)' : (new Intl.NumberFormat('vi-VN').format(instantFee) + 'đ');
            shipEl.setAttribute('data-val', instantFee);
        }
        
        const ghtkEl = document.getElementById('ghtk_fee_text');
        if (ghtkEl) ghtkEl.innerText = (baseFee === 0) ? '0đ (Freeship)' : (new Intl.NumberFormat('vi-VN').format(baseFee) + 'đ');
        const ghnEl = document.getElementById('ghn_fee_text');
        if (ghnEl) ghnEl.innerText = ((baseFee + 4000) === 0) ? '0đ (Freeship)' : (new Intl.NumberFormat('vi-VN').format(baseFee + 4000) + 'đ');

        const carrierEl = document.getElementById('display-carrier-text');
        if (carrierEl) {
            carrierEl.innerText = (selectedCarrier === 'GHN') ? 'Giao Hàng Nhanh (GHN Express)' : 'Giao Hàng Tiết Kiệm (GHTK)';
        }

        // Tính lại tổng tiền ngay tức thì
        calculateTotal();

        // Gửi AJAX đồng bộ với API
        fetch(`ajax-calc-shipping.php?province_id=${provId}&province_name=${encodeURIComponent(provName)}&address_detail=${encodeURIComponent(addressDetail)}&weight=${weight}&subtotal=${subtotal}&carrier=${selectedCarrier}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                const fee = data.shipping_fee;
                if (shipEl) {
                    shipEl.innerText = (fee === 0) ? '0đ (Miễn phí)' : (new Intl.NumberFormat('vi-VN').format(fee) + 'đ');
                    shipEl.setAttribute('data-val', fee);
                }
                
                if (carrierEl) {
                    carrierEl.innerText = `${data.carrier_name} (${data.estimated_days})`;
                }

                if (data.carriers) {
                    if (data.carriers.GHTK && ghtkEl) {
                        const ghtkF = data.carriers.GHTK.fee;
                        ghtkEl.innerText = (ghtkF === 0) ? '0đ (Freeship)' : (new Intl.NumberFormat('vi-VN').format(ghtkF) + 'đ');
                        const ghtkTime = document.getElementById('ghtk_time_text');
                        if (ghtkTime) ghtkTime.innerText = data.carriers.GHTK.estimated_days;
                    }
                    if (data.carriers.GHN && ghnEl) {
                        const ghnF = data.carriers.GHN.fee;
                        ghnEl.innerText = (ghnF === 0) ? '0đ (Freeship)' : (new Intl.NumberFormat('vi-VN').format(ghnF) + 'đ');
                        const ghnTime = document.getElementById('ghn_time_text');
                        if (ghnTime) ghnTime.innerText = data.carriers.GHN.estimated_days;
                    }
                }
                calculateTotal();
            }
        })
        .catch(err => {
            calculateTotal();
        });
    }

    // Interactive Checkout Voucher Selector Logic
    function selectCheckoutVoucher(btn) {
        const code = btn.getAttribute('data-code');
        const type = btn.getAttribute('data-type');
        const val = btn.getAttribute('data-val');
        const max = btn.getAttribute('data-max');
        const title = btn.getAttribute('data-title');
        const badge = btn.getAttribute('data-badge');

        document.getElementById('voucher_code').value = code;
        document.getElementById('voucher_type').value = type;
        document.getElementById('voucher_val').value = val;
        document.getElementById('voucher_max').value = max;

        // Update Trigger UI
        const trigger = document.getElementById('checkoutVoucherTrigger');
        trigger.classList.add('has-applied');
        document.getElementById('selectedVoucherName').innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>${code}</span> — ${badge}`;
        document.getElementById('selectedVoucherDesc').innerText = title;
        document.getElementById('applied-voucher-label').innerText = code;
        document.getElementById('voucher-status-badge').style.display = 'inline-block';

        // Close modal
        const modalEl = document.getElementById('checkoutVoucherModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        calculateTotal();
        if (window.showVoucherToast) {
            showVoucherToast(`Đã áp dụng mã giảm giá <strong>${code}</strong> thành công!`, 'success');
        }
    }

    function clearCheckoutVoucher() {
        document.getElementById('voucher_code').value = '';
        document.getElementById('voucher_type').value = '';
        document.getElementById('voucher_val').value = '0';
        document.getElementById('voucher_max').value = '0';

        const trigger = document.getElementById('checkoutVoucherTrigger');
        trigger.classList.remove('has-applied');
        document.getElementById('selectedVoucherName').innerText = 'Chọn hoặc nhập mã ưu đãi';
        document.getElementById('selectedVoucherDesc').innerText = 'Xem danh sách mã giảm giá khả dụng';
        document.getElementById('voucher-status-badge').style.display = 'none';

        const modalEl = document.getElementById('checkoutVoucherModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        calculateTotal();
    }

    function applyManualVoucherCode() {
        const input = document.getElementById('manualVoucherInput');
        const errDiv = document.getElementById('manualVoucherError');
        const code = input.value.trim().toUpperCase();
        
        if (!code) {
            errDiv.innerText = 'Vui lòng nhập mã giảm giá!';
            errDiv.style.display = 'block';
            return;
        }

        // Check if code matches any in modal
        const matchingBtn = document.querySelector(`.btn-select-voucher[data-code="${code}"]`);
        if (matchingBtn) {
            errDiv.style.display = 'none';
            selectCheckoutVoucher(matchingBtn);
        } else {
            errDiv.innerText = 'Mã không tồn tại hoặc đơn hàng chưa đủ điều kiện áp dụng.';
            errDiv.style.display = 'block';
        }
    }

    function calculateTotal() {
        const subtotal = parseFloat(document.getElementById('display-subtotal').getAttribute('data-val')) || 0;
        const shippingAttr = document.getElementById('display-shipping').getAttribute('data-val');
        let shipping = (shippingAttr !== null && !isNaN(parseFloat(shippingAttr))) ? parseFloat(shippingAttr) : 15000;
        
        const voucherCode = document.getElementById('voucher_code').value;
        const type = document.getElementById('voucher_type').value;
        const val = parseFloat(document.getElementById('voucher_val').value) || 0;
        const max = parseFloat(document.getElementById('voucher_max').value) || 0;
        let discount = 0;
        
        if (voucherCode && type) {
            if (type === 'fixed') {
                discount = val;
            } else if (type === 'percent') {
                discount = subtotal * (val / 100);
                if (max > 0 && discount > max) discount = max;
            } else if (type === 'freeship') {
                discount = shipping;
                if (max > 0 && discount > max) discount = max;
            }
            
            document.getElementById('discount-row').style.setProperty('display', 'flex', 'important');
            document.getElementById('display-discount').innerText = '-' + new Intl.NumberFormat('vi-VN').format(discount) + 'đ';
        } else {
            document.getElementById('discount-row').style.setProperty('display', 'none', 'important');
        }
        
        let total = subtotal + shipping - discount;
        if (total < 0) total = 0;
        
        document.getElementById('display-total').innerText = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateShippingFee();
    });
</script>

<?php require_once __DIR__ . '/includes/map-picker-modal.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>