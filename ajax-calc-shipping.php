<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/CarrierShippingService.php';

$service = new CarrierShippingService($conn);

$province_id    = intval($_GET['province_id'] ?? $_POST['province_id'] ?? 0);
$province_name  = trim($_GET['province_name'] ?? $_POST['province_name'] ?? '');
$district_name  = trim($_GET['district_name'] ?? $_POST['district_name'] ?? '');
$address_detail = trim($_GET['address_detail'] ?? $_POST['address_detail'] ?? '');
$weight_gram    = intval($_GET['weight'] ?? $_POST['weight'] ?? 800);
$subtotal       = floatval($_GET['subtotal'] ?? $_POST['subtotal'] ?? 0);
$carrier_choice = trim($_GET['carrier'] ?? $_POST['carrier'] ?? 'GHTK');

// Nếu truyền province_id thì query lấy province_name chính xác từ CSDL
if ($province_id > 0) {
    $stmt = $conn->prepare("SELECT province_name FROM shipping_provinces WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $province_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r && !empty($r['province_name'])) {
        $province_name = $r['province_name'];
    }
    $stmt->close();
} elseif (!empty($province_name)) {
    $p_esc = $conn->real_escape_string($province_name);
    $res_p = $conn->query("SELECT province_name FROM shipping_provinces WHERE province_name = '$p_esc' OR '$p_esc' LIKE CONCAT('%', province_name, '%') LIMIT 1");
    if ($res_p && $pr = $res_p->fetch_assoc()) {
        $province_name = $pr['province_name'];
    }
}

if (empty($province_name)) {
    echo json_encode([
        'success'        => false,
        'shipping_fee'   => 30000,
        'carrier_name'   => 'Giao Hàng Tiết Kiệm (GHTK)',
        'carrier_code'   => 'GHTK',
        'estimated_days' => '1-2 ngày',
        'message'        => 'Vui lòng chọn Tỉnh/Thành phố'
    ]);
    exit();
}

// Lấy danh sách cước của cả 2 hãng (GHTK & GHN)
$all_carriers = $service->calculateAllCarriersFee($province_name, $district_name, '', $address_detail, $weight_gram, $subtotal);

$selected_carrier = in_array(strtoupper($carrier_choice), ['GHTK', 'GHN']) ? strtoupper($carrier_choice) : 'GHTK';
$selected_info = $all_carriers['carriers'][$selected_carrier] ?? $all_carriers['carriers']['GHTK'];

echo json_encode([
    'success'             => true,
    'shipping_fee'        => $selected_info['fee'],
    'original_fee'        => $selected_info['original_fee'] ?? $selected_info['fee'],
    'is_freeship'         => !empty($all_carriers['is_freeship']),
    'carrier_code'        => $selected_carrier,
    'carrier_name'        => $selected_info['name'],
    'estimated_days'      => $selected_info['estimated_days'],
    'carriers'            => $all_carriers['carriers'],
    'province_name'       => $province_name
]);
exit();
