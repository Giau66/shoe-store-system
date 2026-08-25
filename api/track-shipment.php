<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/CarrierShippingService.php';

$svc = new CarrierShippingService($conn);
$code = trim($_GET['code'] ?? $_POST['code'] ?? '');
$action = trim($_GET['action'] ?? $_POST['action'] ?? 'track');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Mã vận đơn không được để trống!']);
    exit();
}

// Cập nhật trạm bưu cục (Simulator)
if ($action === 'update_step') {
    $step = intval($_GET['step'] ?? $_POST['step'] ?? 1);
    $up_res = $svc->updateCarrierStep($code, $step);
    if ($up_res['success']) {
        $track = $svc->trackShipment($code);
        $track['message'] = $up_res['message'];
        echo json_encode($track);
        exit();
    }
    echo json_encode($up_res);
    exit();
}

// Mặc định: Tra cứu
$res = $svc->trackShipment($code);
echo json_encode($res);
exit();
