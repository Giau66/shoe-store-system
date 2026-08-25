<?php
// includes/sale_helpers.php
// Helper tính toán giá bán sự kiện Sale (Sale Events) đồng bộ toàn hệ thống

if (!function_exists('get_active_sale_event_for_product')) {
    /**
     * Lấy thông tin sự kiện sale đang active cho 1 sản phẩm
     * 
     * @param mysqli $conn
     * @param int $product_id
     * @param float $base_price
     * @return array
     */
    function get_active_sale_event_for_product($conn, $product_id, $base_price = 0) {
        $product_id = intval($product_id);
        $base_price = floatval($base_price);

        if ($product_id <= 0) {
            return [
                'has_sale'         => false,
                'event_id'         => 0,
                'event_name'       => '',
                'event_slug'       => '',
                'color_theme'      => '#ef4444',
                'sale_price'       => $base_price,
                'original_price'   => $base_price,
                'discount_percent' => 0
            ];
        }

        // Nếu chưa có base_price, tự truy vấn từ DB
        if ($base_price <= 0) {
            $p_res = $conn->query("SELECT price FROM products WHERE id = $product_id LIMIT 1");
            if ($p_res && $p_row = $p_res->fetch_assoc()) {
                $base_price = floatval($p_row['price']);
            }
        }

        $stmt = $conn->prepare("
            SELECT ep.sale_price, ep.discount_percent,
                   se.id AS event_id, se.name AS event_name, se.slug AS event_slug, 
                   se.color_theme, se.icon, se.icon_image
            FROM event_products ep
            JOIN sale_events se ON ep.event_id = se.id
            WHERE ep.product_id = ? 
              AND se.status = 1 
              AND se.start_date <= NOW() 
              AND se.end_date >= NOW()
            ORDER BY 
              (CASE 
                WHEN ep.sale_price > 0 THEN ep.sale_price 
                WHEN ep.discount_percent > 0 THEN (? * (1 - ep.discount_percent / 100))
                ELSE ? 
              END) ASC, 
              se.sort_order ASC, 
              se.id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            return [
                'has_sale'         => false,
                'event_id'         => 0,
                'event_name'       => '',
                'event_slug'       => '',
                'color_theme'      => '#ef4444',
                'sale_price'       => $base_price,
                'original_price'   => $base_price,
                'discount_percent' => 0
            ];
        }

        $stmt->bind_param("idd", $product_id, $base_price, $base_price);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            $ep_sale_price = floatval($row['sale_price'] ?? 0);
            $ep_discount   = intval($row['discount_percent'] ?? 0);

            // Tính giá bán thực tế theo sự kiện sale
            if ($ep_sale_price > 0) {
                $final_sale_price = $ep_sale_price;
            } elseif ($ep_discount > 0 && $base_price > 0) {
                $final_sale_price = round($base_price * (1 - $ep_discount / 100));
            } else {
                $final_sale_price = $base_price;
            }

            // Tính % giảm thực tế
            if ($ep_discount <= 0 && $base_price > $final_sale_price && $base_price > 0) {
                $ep_discount = round((1 - ($final_sale_price / $base_price)) * 100);
            }

            return [
                'has_sale'         => ($final_sale_price < $base_price),
                'event_id'         => intval($row['event_id']),
                'event_name'       => $row['event_name'],
                'event_slug'       => $row['event_slug'],
                'color_theme'      => !empty($row['color_theme']) ? $row['color_theme'] : '#ef4444',
                'icon'             => $row['icon'] ?? '',
                'icon_image'       => $row['icon_image'] ?? '',
                'sale_price'       => $final_sale_price,
                'original_price'   => $base_price,
                'discount_percent' => $ep_discount
            ];
        }

        return [
            'has_sale'         => false,
            'event_id'         => 0,
            'event_name'       => '',
            'event_slug'       => '',
            'color_theme'      => '#ef4444',
            'sale_price'       => $base_price,
            'original_price'   => $base_price,
            'discount_percent' => 0
        ];
    }
}

if (!function_exists('get_active_sale_events_map')) {
    /**
     * Lấy map thông tin sale event cho danh sách nhiều sản phẩm (Tối ưu 1 query)
     * 
     * @param mysqli $conn
     * @param array $product_ids
     * @return array [product_id => sale_info]
     */
    function get_active_sale_events_map($conn, array $product_ids) {
        $clean_ids = array_filter(array_map('intval', $product_ids));
        if (empty($clean_ids)) return [];

        $ids_str = implode(',', $clean_ids);
        $sql = "
            SELECT ep.product_id, ep.sale_price, ep.discount_percent,
                   p.price AS base_price,
                   se.id AS event_id, se.name AS event_name, se.slug AS event_slug, 
                   se.color_theme, se.icon
            FROM event_products ep
            JOIN products p ON ep.product_id = p.id
            JOIN sale_events se ON ep.event_id = se.id
            WHERE ep.product_id IN ($ids_str)
              AND se.status = 1 
              AND se.start_date <= NOW() 
              AND se.end_date >= NOW()
            ORDER BY se.sort_order ASC, se.id DESC
        ";

        $res = $conn->query($sql);
        $map = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $pid           = intval($row['product_id']);
                $base_price    = floatval($row['base_price']);
                $ep_sale_price = floatval($row['sale_price'] ?? 0);
                $ep_discount   = intval($row['discount_percent'] ?? 0);

                if ($ep_sale_price > 0) {
                    $final_sale_price = $ep_sale_price;
                } elseif ($ep_discount > 0 && $base_price > 0) {
                    $final_sale_price = round($base_price * (1 - $ep_discount / 100));
                } else {
                    $final_sale_price = $base_price;
                }

                if ($ep_discount <= 0 && $base_price > $final_sale_price && $base_price > 0) {
                    $ep_discount = round((1 - ($final_sale_price / $base_price)) * 100);
                }

                // Nếu sản phẩm chưa có trong map hoặc sự kiện này có giá rẻ hơn -> gán vào map
                if (!isset($map[$pid]) || $final_sale_price < $map[$pid]['sale_price']) {
                    $map[$pid] = [
                        'has_sale'         => ($final_sale_price < $base_price),
                        'event_id'         => intval($row['event_id']),
                        'event_name'       => $row['event_name'],
                        'event_slug'       => $row['event_slug'],
                        'color_theme'      => !empty($row['color_theme']) ? $row['color_theme'] : '#ef4444',
                        'icon'             => $row['icon'] ?? '',
                        'sale_price'       => $final_sale_price,
                        'original_price'   => $base_price,
                        'discount_percent' => $ep_discount
                    ];
                }
            }
        }
        return $map;
    }
}

if (!function_exists('get_user_cart_summary')) {
    /**
     * Helper tính toán thống kê giỏ hàng cho user (đồng bộ giá Sự Kiện Sale)
     * 
     * @param mysqli $conn
     * @param int $user_id
     * @return array
     */
    function get_user_cart_summary($conn, $user_id) {
        $user_id = intval($user_id);
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
            'unique_items'       => $unique_items,
            'total_qty'          => $total_qty,
            'subtotal'           => $subtotal,
            'subtotal_formatted' => number_format($subtotal, 0, ',', '.') . 'đ'
        ];
    }
}

