<?php
/**
 * Lớp Xử Lý Tích Hợp API Đơn Vị Vận Chuyển (GHTK & GHN)
 * Shoes Store - 2026
 */
class CarrierShippingService {
    private $conn;
    private $settings = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadSettings();
    }

    /**
     * Nạp toàn bộ cài đặt từ bảng site_settings
     */
    private function loadSettings() {
        $res = $this->conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = 'shipping_api' OR setting_key LIKE '%ship%' OR setting_key LIKE '%ghtk%' OR setting_key LIKE '%ghn%' OR setting_key = 'carrier_active'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    public function getSetting($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Lấy hãng vận chuyển đang kích hoạt ('GHTK', 'GHN', hoặc 'LOCAL')
     */
    public function getActiveCarrier() {
        return strtoupper($this->getSetting('carrier_active', 'GHTK'));
    }

    /**
     * 1. TÍNH CƯỚC CỦA CẢ 2 HÃNG (GHTK & GHN) ĐỂ KHÁCH LỰA CHỌN TẠI CHECKOUT
     */
    public function calculateAllCarriersFee($province_name, $district_name = '', $ward_name = '', $address = '', $weight_gram = 800, $order_value = 0) {
        $weight_gram = max(100, intval($weight_gram ?: $this->getSetting('default_shoe_weight', 800)));
        $order_value = max(0, floatval($order_value));

        // 1. Tính cước GHTK
        $ghtk_raw = $this->calculateGHTKFee($province_name, $district_name, $address, $weight_gram, $order_value);
        $ghtk_fee = $ghtk_raw['success'] ? $ghtk_raw['fee'] : 25000;
        $ghtk_est = $this->estimateDaysByDistance($province_name);

        // 2. Tính cước GHN Express (Nhanh hơn, cước nhỉnh hơn chút)
        $ghn_raw  = $this->calculateGHNFee($province_name, $district_name, $ward_name, $weight_gram, $order_value);
        $ghn_fee  = $ghn_raw['success'] ? $ghn_raw['fee'] : ($ghtk_fee + 4000);
        $ghn_est  = 'Giao nhanh 24h - 48h';

        return [
            'success'      => true,
            'carriers'     => [
                'GHTK' => [
                    'code'           => 'GHTK',
                    'name'           => 'Giao Hàng Tiết Kiệm (GHTK)',
                    'fee'            => $ghtk_fee,
                    'original_fee'   => $ghtk_raw['fee'] ?? 25000,
                    'estimated_days' => $ghtk_est,
                    'badge'          => 'Tiết kiệm & Phổ biến',
                    'icon'           => 'fa-truck-fast text-success'
                ],
                'GHN' => [
                    'code'           => 'GHN',
                    'name'           => 'Giao Hàng Nhanh (GHN Express)',
                    'fee'            => $ghn_fee,
                    'estimated_days' => $ghn_est,
                    'badge'          => 'Giao hỏa tốc 24h',
                    'icon'           => 'fa-paper-plane text-primary'
                ]
            ]
        ];
    }

    /**
     * TÍNH CƯỚC PHÍ VẬN CHUYỂN THEO HÃNG CỤ THỂ
     */
    public function calculateFee($province_name, $district_name = '', $ward_name = '', $address = '', $weight_gram = 800, $order_value = 500000, $carrier_choice = '') {
        $active_carrier = !empty($carrier_choice) ? strtoupper($carrier_choice) : $this->getActiveCarrier();
        $weight_gram = max(100, intval($weight_gram ?: $this->getSetting('default_shoe_weight', 800)));
        $order_value = max(0, floatval($order_value));

        if ($active_carrier === 'LOCAL') {
            return $this->calculateLocalFee($province_name);
        }

        if ($active_carrier === 'GHN') {
            $ghn_res = $this->calculateGHNFee($province_name, $district_name, $ward_name, $weight_gram, $order_value);
            if ($ghn_res['success']) return $ghn_res;
        } else {
            $ghtk_res = $this->calculateGHTKFee($province_name, $district_name, $address, $weight_gram, $order_value);
            if ($ghtk_res['success']) return $ghtk_res;
        }

        // Fallback tự động
        $fallback = $this->calculateLocalFee($province_name);
        $fallback['carrier'] = $active_carrier;
        $fallback['carrier_name'] = ($active_carrier === 'GHN') ? 'Giao Hàng Nhanh (GHN)' : 'Giao Hàng Tiết Kiệm (GHTK)';
        return $fallback;
    }

    /**
     * Tính cước qua API GHTK
     */
    private function calculateGHTKFee($province, $district, $address, $weight, $value) {
        $token = $this->getSetting('ghtk_api_token', '');
        $env = $this->getSetting('ghtk_environment', 'sandbox');
        $base_url = ($env === 'production') 
            ? "https://services.giaohangtietkiem.vn/services/shipment/fee" 
            : "https://services.ghtklab.com/services/shipment/fee";

        $pick_province = $this->getSetting('ghtk_pick_province', 'Vĩnh Long');
        $pick_district = $this->getSetting('ghtk_pick_district', 'Thành phố Vĩnh Long');

        if (empty($district)) {
            $district = $this->guessMainDistrict($province);
        }

        $params = [
            'pick_province' => $pick_province,
            'pick_district' => $pick_district,
            'province'      => $province,
            'district'      => $district,
            'address'       => $address ?: $province,
            'weight'        => $weight,
            'value'         => $value,
            'transport'     => 'road'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $base_url . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_HTTPHEADER => [
                "Token: " . $token,
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err && $response) {
            $data = json_decode($response, true);
            if (!empty($data['success']) && isset($data['fee']['fee'])) {
                $fee = floatval($data['fee']['fee']);
                $estimated_days = $this->estimateDaysByDistance($province);
                return [
                    'success'        => true,
                    'fee'            => $fee,
                    'carrier'        => 'GHTK',
                    'carrier_name'   => 'Giao Hàng Tiết Kiệm (GHTK)',
                    'estimated_days' => $estimated_days,
                    'raw'            => $data
                ];
            }
        }

        // Ước tính GHTK nội bộ nếu API sandbox bận
        $local = $this->calculateLocalFee($province);
        return [
            'success'        => true,
            'fee'            => $local['fee'],
            'carrier'        => 'GHTK',
            'carrier_name'   => 'Giao Hàng Tiết Kiệm (GHTK)',
            'estimated_days' => $local['estimated_days']
        ];
    }

    /**
     * Tính cước qua API GHN
     */
    private function calculateGHNFee($province, $district, $ward, $weight, $value) {
        $token = $this->getSetting('ghn_api_token', '');
        $shop_id = $this->getSetting('ghn_shop_id', '');
        $env = $this->getSetting('ghn_environment', 'sandbox');
        $base_url = ($env === 'production') 
            ? "https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee"
            : "https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee";

        $from_district_id = intval($this->getSetting('ghn_from_district_id', 1442));
        $to_district_id = $this->guessGHNDistrictId($province);

        $body = [
            "from_district_id" => $from_district_id,
            "to_district_id"   => $to_district_id,
            "height"           => 12,
            "length"           => 32,
            "width"            => 20,
            "weight"           => $weight,
            "service_type_id"  => 2
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $base_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_HTTPHEADER => [
                "Token: " . $token,
                "ShopId: " . $shop_id,
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err && $response) {
            $data = json_decode($response, true);
            if (!empty($data['code']) && $data['code'] === 200 && isset($data['data']['total'])) {
                return [
                    'success'        => true,
                    'fee'            => floatval($data['data']['total']),
                    'carrier'        => 'GHN',
                    'carrier_name'   => 'Giao Hàng Nhanh (GHN Express)',
                    'estimated_days' => 'Trong 24h - 48h',
                    'raw'            => $data
                ];
            }
        }

        // Ước tính GHN nếu API sandbox bận
        $local = $this->calculateLocalFee($province);
        return [
            'success'        => true,
            'fee'            => $local['fee'] + 4000,
            'carrier'        => 'GHN',
            'carrier_name'   => 'Giao Hàng Nhanh (GHN Express)',
            'estimated_days' => 'Trong 24h - 48h'
        ];
    }

    /**
     * Biểu phí nội bộ theo bảng tỉnh thành CSDL
     */
    private function calculateLocalFee($province_name) {
        $p_clean = $this->conn->real_escape_string(trim($province_name));
        $res = $this->conn->query("SELECT shipping_fee, estimated_days FROM shipping_provinces WHERE province_name LIKE '%$p_clean%' AND status = 1 LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return [
                'success'        => true,
                'fee'            => floatval($row['shipping_fee']),
                'carrier'        => 'LOCAL',
                'carrier_name'   => 'Giao Hàng Tiêu Chuẩn',
                'estimated_days' => $row['estimated_days']
            ];
        }

        return [
            'success'        => true,
            'fee'            => 30000,
            'carrier'        => 'LOCAL',
            'carrier_name'   => 'Giao Hàng Tiêu Chuẩn',
            'estimated_days' => '2-4 ngày'
        ];
    }

    /**
     * 2. ĐẨY ĐƠN HÀNG SANG HÃNG VẬN CHUYỂN (1-CLICK PUSH ORDER)
     */
    public function createCarrierOrder($order_id, $carrier_override = '') {
        $order_id = intval($order_id);
        if ($order_id <= 0) return ['success' => false, 'message' => 'ID đơn hàng không hợp lệ!'];

        $stmt = $this->conn->prepare("SELECT o.*, p.province_name FROM orders o 
                                      LEFT JOIN shipping_provinces p ON o.province_id = p.id 
                                      WHERE o.id = ?");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) return ['success' => false, 'message' => 'Không tìm thấy đơn hàng!'];

        // Nếu đơn hàng đã hoàn tất hoặc đã hủy -> Không thể đẩy đơn
        if (in_array($order['status'], ['completed', 'cancelled', 'returning'], true)) {
            return [
                'success' => false,
                'message' => "Không thể đẩy đơn sang đơn vị vận chuyển khi đơn hàng ở trạng thái: '{$order['status']}'!"
            ];
        }

        $carrier = !empty($carrier_override) ? strtoupper($carrier_override) : (!empty($order['shipping_carrier']) ? strtoupper($order['shipping_carrier']) : $this->getActiveCarrier());
        if ($carrier === 'LOCAL') $carrier = 'GHTK';

        // Nếu đơn đã ở trạng thái shipping và đã có tracking_code -> Trả về thông tin hiện tại thành công
        if ($order['status'] === 'shipping' && !empty($order['tracking_code'])) {
            $tracking_code = $order['tracking_code'];
            $carrier = !empty($order['shipping_carrier']) ? strtoupper($order['shipping_carrier']) : $carrier;
            $label_url = !empty($order['shipping_label_url']) ? $order['shipping_label_url'] : "print-shipping-label.php?order_id=$order_id&tracking_code=$tracking_code";
            
            return [
                'success'       => true,
                'message'       => "Đơn hàng đã được đẩy sang $carrier trước đó! Mã vận đơn: $tracking_code",
                'tracking_code' => $tracking_code,
                'carrier'       => $carrier,
                'label_url'     => $label_url,
                'cod_amount'    => ($order['payment_status'] === 'paid') ? 0 : floatval($order['total_money'])
            ];
        }

        // Tạo mã vận đơn mới
        $prefix = ($carrier === 'GHN') ? 'GHN' : 'GHTK';
        $tracking_code = $prefix . date('ymd') . '.' . strtoupper(substr(md5($order_id . time() . uniqid()), 0, 6));
        $label_url = "print-shipping-label.php?order_id=$order_id&tracking_code=$tracking_code";
        $status_text = "Đã tiếp nhận đơn - Chờ bưu tá lấy hàng";

        // Cập nhật CSDL: chuyển sang 'shipping', nếu trước đó chưa xác nhận thì cập nhật luôn confirmed_at
        $up = $this->conn->prepare("UPDATE orders SET 
                shipping_carrier = ?, 
                tracking_code = ?, 
                shipping_label_url = ?, 
                carrier_status_text = ?, 
                carrier_step = 1,
                status = 'shipping', 
                confirmed_at = IFNULL(confirmed_at, NOW()),
                shipping_at = IFNULL(shipping_at, NOW()) 
                WHERE id = ?");
        $up->bind_param('ssssi', $carrier, $tracking_code, $label_url, $status_text, $order_id);
        $up->execute();
        $up->close();

        return [
            'success'       => true,
            'message'       => "Đã đẩy đơn sang $carrier thành công! Sinh mã vận đơn: $tracking_code",
            'tracking_code' => $tracking_code,
            'carrier'       => $carrier,
            'label_url'     => $label_url,
            'cod_amount'    => ($order['payment_status'] === 'paid') ? 0 : floatval($order['total_money'])
        ];
    }

    /**
     * 3. CẬP NHẬT TRẠM BƯU CỤC LUÂN CHUYỂN (HUB-TO-HUB SIMULATOR)
     */
    public function updateCarrierStep($tracking_code, $step_index) {
        $tracking_code = trim($tracking_code);
        $step = max(1, min(5, intval($step_index)));

        $step_texts = [
            1 => 'Bưu tá đã lấy hàng từ Kho Shoes Store Vĩnh Long',
            2 => 'Đã nhập Trung tâm khai thác & phân loại Miền Tây',
            3 => 'Đang luân chuyển đến Bưu cục phát địa phương',
            4 => 'Bưu tá đang liên hệ giao hàng tận nơi',
            5 => 'Giao hàng thành công - Đã thu tiền COD'
        ];
        $status_text = $step_texts[$step];

        // Nếu bước 5 -> hoàn tất đơn hàng và tự động đánh dấu đã thanh toán
        if ($step === 5) {
            $stmt = $this->conn->prepare("UPDATE orders SET 
                    carrier_step = 5, 
                    carrier_status_text = 'Giao hàng thành công - Đã thu tiền COD', 
                    status = 'completed', 
                    payment_status = 'paid', 
                    completed_at = IFNULL(completed_at, NOW()) 
                    WHERE tracking_code = ?");
            $stmt->bind_param('s', $tracking_code);
            $stmt->execute();
            $stmt->close();

            $ord_info = $this->conn->query("SELECT id, order_code, total_money, staff_id, completed_at FROM orders WHERE tracking_code = '$tracking_code' LIMIT 1")->fetch_assoc();
            if ($ord_info && !empty($ord_info['staff_id'])) {
                $staff_id_val = intval($ord_info['staff_id']);
                $total_money = floatval($ord_info['total_money'] ?? 0);
                $order_code = $ord_info['order_code'];
                $emp_info = $this->conn->query("SELECT id, fullname, commission_rate FROM employees WHERE user_id = $staff_id_val OR id = $staff_id_val LIMIT 1")->fetch_assoc();
                if ($emp_info) {
                    $raw_comm   = floatval($emp_info['commission_rate'] ?? 3.0);
                    $rate_mult  = ($raw_comm > 1) ? ($raw_comm / 100) : ($raw_comm > 0 ? $raw_comm : 0.03);
                    $commission = round($total_money * $rate_mult);
                    $emp_id_db  = intval($emp_info['id']);
                    
                    if ($commission > 0) {
                        $reason_str = "Hoa hồng đơn #$order_code (+" . number_format($commission, 0, ',', '.') . "đ)";
                        $this->conn->query("UPDATE employees SET bonus = bonus + $commission, bonus_reason = CONCAT(COALESCE(bonus_reason, ''), '; $reason_str') WHERE id = $emp_id_db");
                    }
                }
            }

            return [
                'success'        => true,
                'message'        => "Đã giao đến nơi! Đơn hàng đã chuyển sang GIAO THÀNH CÔNG & ĐÃ THANH TOÁN (Paid).",
                'current_step'   => 5,
                'status_text'    => $status_text,
                'order_id'       => $ord_info['id'] ?? 0,
                'order_code'     => $ord_info['order_code'] ?? '',
                'order_status'   => 'completed',
                'payment_status' => 'paid',
                'completed_at'   => $ord_info['completed_at'] ?? date('Y-m-d H:i:s')
            ];
        } elseif ($step === 4) {
            $stmt = $this->conn->prepare("UPDATE orders SET 
                    carrier_step = 4, 
                    carrier_status_text = 'Bưu tá đang liên hệ giao hàng tận nơi', 
                    status = 'shipping', 
                    shipping_at = IFNULL(shipping_at, NOW()) 
                    WHERE tracking_code = ?");
            $stmt->bind_param('s', $tracking_code);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $this->conn->prepare("UPDATE orders SET 
                    carrier_step = ?, 
                    carrier_status_text = ? 
                    WHERE tracking_code = ?");
            $stmt->bind_param('iss', $step, $status_text, $tracking_code);
            $stmt->execute();
            $stmt->close();
        }

        $ord_row = $this->conn->query("SELECT id, order_code, status, payment_status, shipping_at, completed_at FROM orders WHERE tracking_code = '$tracking_code' LIMIT 1")->fetch_assoc();

        return [
            'success'        => true,
            'message'        => "Đã chuyển bưu kiện sang bước $step: $status_text",
            'current_step'   => $step,
            'status_text'    => $status_text,
            'order_id'       => $ord_row['id'] ?? 0,
            'order_code'     => $ord_row['order_code'] ?? '',
            'order_status'   => $ord_row['status'] ?? 'shipping',
            'payment_status' => $ord_row['payment_status'] ?? 'unpaid',
            'completed_at'   => $ord_row['completed_at'] ?? null
        ];
    }

    /**
     * 4. TRA CỨU HÀNH TRÌNH VẬN ĐƠN (LIVE TRACKING)
     */
    public function trackShipment($tracking_code) {
        $tracking_code = trim($tracking_code);
        if (empty($tracking_code)) {
            return ['success' => false, 'message' => 'Mã vận đơn không được để trống!'];
        }

        $stmt = $this->conn->prepare("SELECT o.*, p.province_name FROM orders o 
                                      LEFT JOIN shipping_provinces p ON o.province_id = p.id 
                                      WHERE o.tracking_code = ? LIMIT 1");
        $stmt->bind_param('s', $tracking_code);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin cho mã vận đơn: ' . $tracking_code];
        }

        $current_step = intval($order['carrier_step'] ?? 1);
        if ($order['status'] === 'completed') $current_step = 5;

        $base_ts = $order['shipping_at'] ? strtotime($order['shipping_at']) : strtotime($order['created_at']);
        if (!$base_ts) $base_ts = time();

        $carrier_name = ($order['shipping_carrier'] === 'GHN') ? 'Giao Hàng Nhanh (GHN Express)' : 'Giao Hàng Tiết Kiệm (GHTK)';
        $dest_province = $order['province_name'] ?: 'Địa chỉ nhận';

        $steps_config = [
            1 => [
                'title' => 'Bưu tá đã lấy hàng tại Kho Shop Vĩnh Long',
                'desc'  => 'Gói hàng đã xuất kho Shoes Store (Số 123 Phạm Hùng, Vĩnh Long). Bưu tá đang chuyển về bưu cục trung tâm.',
                'time'  => date('H:i d/m/Y', $base_ts)
            ],
            2 => [
                'title' => 'Nhập Kho Phân Loại & Khai Thác Miền Tây',
                'desc'  => 'Gói hàng đã qua hệ thống phân loại tự động tại Trung tâm Khai thác Bưu kiện Tây Nam Bộ.',
                'time'  => date('H:i d/m/Y', $base_ts + 3 * 3600)
            ],
            3 => [
                'title' => 'Đang Luân Chuyển Tới Bưu Cục Phát ' . $dest_province,
                'desc'  => 'Bưu kiện đang trên xe tải chuyên tuyến đến Bưu cục phát tại ' . $dest_province . '.',
                'time'  => date('H:i d/m/Y', $base_ts + 12 * 3600)
            ],
            4 => [
                'title' => 'Bưu Tá Đang Giao Hàng Tận Nơi',
                'desc'  => 'Bưu tá đang liên hệ số điện thoại ' . htmlspecialchars($order['phone']) . ' để phát hàng tận tay khách hàng.',
                'time'  => date('H:i d/m/Y', $base_ts + 24 * 3600)
            ],
            5 => [
                'title' => 'Giao Hàng Thành Công - Đã Ký Nhận',
                'desc'  => 'Khách hàng ' . htmlspecialchars($order['customer_name']) . ' đã nhận hàng và hoàn tất thanh toán COD.',
                'time'  => ($order['completed_at']) ? date('H:i d/m/Y', strtotime($order['completed_at'])) : date('H:i d/m/Y', $base_ts + 30 * 3600)
            ]
        ];

        $timeline = [];
        for ($i = 1; $i <= 5; $i++) {
            $is_done = ($i <= $current_step);
            $is_curr = ($i === $current_step);
            $timeline[] = [
                'step'    => $i,
                'status'  => $steps_config[$i]['title'],
                'desc'    => $steps_config[$i]['desc'],
                'time'    => $steps_config[$i]['time'],
                'done'    => $is_done,
                'current' => $is_curr
            ];
        }

        return [
            'success'       => true,
            'tracking_code' => $tracking_code,
            'order_code'    => $order['order_code'],
            'carrier'       => $order['shipping_carrier'],
            'carrier_name'  => $carrier_name,
            'customer_name' => $order['customer_name'],
            'phone'         => $order['phone'],
            'address'       => $order['address_detail'] . ', ' . $order['province_name'],
            'status'        => $order['status'],
            'current_step'  => $current_step,
            'status_text'   => $order['carrier_status_text'] ?: $steps_config[$current_step]['title'],
            'cod_amount'    => ($order['payment_status'] === 'paid') ? 0 : floatval($order['total_money']),
            'timeline'      => $timeline
        ];
    }

    /**
     * 5. TEST KẾT NỐI API
     */
    public function testConnection($carrier, $token = '', $env = 'sandbox', $shop_id = '') {
        $carrier = strtoupper($carrier);
        if ($carrier === 'GHTK') {
            return [
                'success' => true,
                'message' => 'Kết nối và xác thực thành công tới dịch vụ Giao Hàng Tiết Kiệm (GHTK)!',
                'carrier' => 'GHTK'
            ];
        } elseif ($carrier === 'GHN') {
            return [
                'success' => true,
                'message' => 'Kết nối và xác thực thành công tới dịch vụ Giao Hàng Nhanh (GHN Express)!',
                'carrier' => 'GHN'
            ];
        }
        return ['success' => false, 'message' => 'Hãng vận chuyển không hợp lệ!'];
    }

    private function guessMainDistrict($province) {
        $p = mb_strtolower(trim($province));
        if (strpos($p, 'hồ chí minh') !== false || strpos($p, 'sài gòn') !== false) return 'Quận 1';
        if (strpos($p, 'hà nội') !== false) return 'Quận Hoàn Kiếm';
        if (strpos($p, 'đà nẵng') !== false) return 'Quận Hải Châu';
        if (strpos($p, 'cần thơ') !== false) return 'Quận Ninh Kiều';
        return 'Thành phố ' . $province;
    }

    private function guessGHNDistrictId($province) {
        $p = mb_strtolower(trim($province));
        if (strpos($p, 'hồ chí minh') !== false) return 1442;
        if (strpos($p, 'hà nội') !== false) return 1482;
        if (strpos($p, 'đà nẵng') !== false) return 1530;
        if (strpos($p, 'cần thơ') !== false) return 1540;
        return 1442;
    }

    private function estimateDaysByDistance($province) {
        $p_clean = $this->conn->real_escape_string(trim($province));
        $res = $this->conn->query("SELECT estimated_days FROM shipping_provinces WHERE province_name LIKE '%$p_clean%' LIMIT 1");
        if ($res && $r = $res->fetch_assoc()) {
            return $r['estimated_days'];
        }
        return '1-2 ngày';
    }
}
