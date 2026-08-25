<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền Admin (Chỉ Quản trị viên mới được xem Báo cáo doanh thu & Lợi nhuận gộp toàn shop)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    if (!empty($_POST)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền xem báo cáo tài chính!']);
        exit();
    }
    header('Location: index.php');
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 1. AJAX DRILL-DOWN: KHI BẤM 1 DÒNG BẤT KỲ ĐỂ XEM BIỂU ĐỒ CHI TIẾT
// ═════════════════════════════════════════════════════════════════════

// A. Drill-down Theo Kỳ Thời Gian (Tháng / Quý / Năm)
if (isset($_POST['ajax_drilldown_time'])) {
    header('Content-Type: application/json; charset=utf-8');
    $p_type = $_POST['period_type'] ?? 'month';
    $p_val  = intval($_POST['period_val'] ?? 8);
    $p_year = intval($_POST['year'] ?? date('Y'));

    $labels = []; $revs = []; $profs = []; $costs = [];

    if ($p_type === 'month') {
        $title = "Chi Tiết Từng Ngày Trong Tháng $p_val / $p_year";
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $p_val, $p_year);
        
        $rev_map = []; $cost_map = []; $cnt_map = [];
        $res_r = $conn->query("SELECT DATE(created_at) as d, SUM(total_money) as rev, COUNT(id) as cnt FROM orders WHERE status = 'completed' AND YEAR(created_at) = $p_year AND MONTH(created_at) = $p_val GROUP BY DATE(created_at)");
        if ($res_r) while ($r = $res_r->fetch_assoc()) { $rev_map[$r['d']] = floatval($r['rev']); $cnt_map[$r['d']] = intval($r['cnt']); }

        $res_c = $conn->query("SELECT DATE(o.created_at) as d, SUM(od.quantity * COALESCE(p.cost_price, 0)) as cost FROM orders o JOIN order_details od ON o.id = od.order_id JOIN products p ON od.product_id = p.id WHERE o.status = 'completed' AND YEAR(o.created_at) = $p_year AND MONTH(o.created_at) = $p_val GROUP BY DATE(o.created_at)");
        if ($res_c) while ($r = $res_c->fetch_assoc()) { $cost_map[$r['d']] = floatval($r['cost']); }

        $all_days = array_unique(array_merge(array_keys($rev_map), array_keys($cost_map)));
        sort($all_days);

        $total_period_rev = 0; $total_period_prof = 0; $total_period_orders = 0;
        foreach ($all_days as $d) {
            $rv = $rev_map[$d] ?? 0;
            $cs = $cost_map[$d] ?? 0;
            $pf = $rv - $cs;
            $ct = $cnt_map[$d] ?? 0;

            $labels[] = date('d/m', strtotime($d));
            $revs[]   = $rv;
            $costs[]  = $cs;
            $profs[]  = $pf;
            $total_period_rev    += $rv;
            $total_period_prof   += $pf;
            $total_period_orders += $ct;
        }
    } elseif ($p_type === 'quarter') {
        $quarters_m = [1 => [1,2,3], 2 => [4,5,6], 3 => [7,8,9], 4 => [10,11,12]];
        $m_arr = $quarters_m[$p_val] ?? [1,2,3];
        $title = "Chi Tiết Từng Tháng Trong Quý $p_val / $p_year";
        
        $total_period_rev = 0; $total_period_prof = 0; $total_period_orders = 0;
        foreach ($m_arr as $m) {
            $r_o = $conn->query("SELECT COUNT(id) as cnt, COALESCE(SUM(total_money), 0) as rev FROM orders WHERE status = 'completed' AND YEAR(created_at) = $p_year AND MONTH(created_at) = $m")->fetch_assoc();
            $r_i = $conn->query("SELECT COALESCE(SUM(od.quantity * COALESCE(p.cost_price, 0)), 0) as cost FROM order_details od JOIN orders o ON od.order_id = o.id JOIN products p ON od.product_id = p.id WHERE o.status = 'completed' AND YEAR(o.created_at) = $p_year AND MONTH(o.created_at) = $m")->fetch_assoc();

            $rv = floatval($r_o['rev'] ?? 0);
            $cs = floatval($r_i['cost'] ?? 0);
            $pf = $rv - $cs;
            $ct = intval($r_o['cnt'] ?? 0);

            $labels[] = "Tháng $m";
            $revs[]   = $rv;
            $costs[]  = $cs;
            $profs[]  = $pf;
            $total_period_rev    += $rv;
            $total_period_prof   += $pf;
            $total_period_orders += $ct;
        }
    } else {
        $title = "Chi Tiết 12 Tháng Trong Năm $p_val";
        $total_period_rev = 0; $total_period_prof = 0; $total_period_orders = 0;
        for ($m = 1; $m <= 12; $m++) {
            $r_o = $conn->query("SELECT COUNT(id) as cnt, COALESCE(SUM(total_money), 0) as rev FROM orders WHERE status = 'completed' AND YEAR(created_at) = $p_val AND MONTH(created_at) = $m")->fetch_assoc();
            $r_i = $conn->query("SELECT COALESCE(SUM(od.quantity * COALESCE(p.cost_price, 0)), 0) as cost FROM order_details od JOIN orders o ON od.order_id = o.id JOIN products p ON od.product_id = p.id WHERE o.status = 'completed' AND YEAR(o.created_at) = $p_val AND MONTH(o.created_at) = $m")->fetch_assoc();

            $rv = floatval($r_o['rev'] ?? 0);
            $cs = floatval($r_i['cost'] ?? 0);
            $pf = $rv - $cs;
            $ct = intval($r_o['cnt'] ?? 0);

            $labels[] = "Tháng $m";
            $revs[]   = $rv;
            $costs[]  = $cs;
            $profs[]  = $pf;
            $total_period_rev    += $rv;
            $total_period_prof   += $pf;
            $total_period_orders += $ct;
        }
    }

    echo json_encode([
        'success'      => true,
        'title'        => $title,
        'labels'       => $labels,
        'revenue'      => $revs,
        'profit'       => $profs,
        'cost'         => $costs,
        'total_rev'    => $total_period_rev,
        'total_profit' => $total_period_prof,
        'total_orders' => $total_period_orders
    ]);
    exit();
}

// B. Drill-down Theo Mặt Hàng Sản Phẩm
if (isset($_POST['ajax_drilldown_product'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid  = intval($_POST['product_id'] ?? 0);
    $from = $conn->real_escape_string($_POST['from_date'] ?? date('Y-m-01'));
    $to   = $conn->real_escape_string($_POST['to_date'] ?? date('Y-m-d'));

    $p_info = $conn->query("SELECT p.*, b.name as brand_name, c.name as cat_name FROM products p LEFT JOIN brands b ON p.brand_id=b.id LEFT JOIN categories c ON p.category_id=c.id WHERE p.id = $pid")->fetch_assoc();

    $sql = "SELECT DATE(o.created_at) AS d, 
                   SUM(od.quantity) AS qty, 
                   SUM(od.quantity * od.price) AS rev,
                   SUM(od.quantity * (od.price - COALESCE(p.cost_price, 0))) AS profit
            FROM order_details od 
            JOIN orders o ON od.order_id = o.id 
            JOIN products p ON od.product_id = p.id
            WHERE o.status = 'completed' AND od.product_id = $pid AND DATE(o.created_at) BETWEEN '$from' AND '$to'
            GROUP BY DATE(o.created_at) ORDER BY d ASC";
    $res = $conn->query($sql);

    $labels = []; $qtys = []; $revs = []; $total_p_rev = 0; $total_p_qty = 0; $total_p_profit = 0;
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $labels[] = date('d/m', strtotime($r['d']));
            $qtys[]   = intval($r['qty']);
            $revs[]   = floatval($r['rev']);
            $total_p_rev    += floatval($r['rev']);
            $total_p_qty    += intval($r['qty']);
            $total_p_profit += floatval($r['profit']);
        }
    }

    echo json_encode([
        'success'      => true,
        'product_name' => $p_info['name'] ?? 'Sản phẩm',
        'brand_name'   => $p_info['brand_name'] ?? 'Khác',
        'labels'       => $labels,
        'quantities'   => $qtys,
        'revenue'      => $revs,
        'total_qty'    => $total_p_qty,
        'total_rev'    => $total_p_rev,
        'total_profit' => $total_p_profit
    ]);
    exit();
}

// C. Drill-down Theo Tài Khoản Khách Hàng
if (isset($_POST['ajax_drilldown_customer'])) {
    header('Content-Type: application/json; charset=utf-8');
    $uid  = intval($_POST['user_id'] ?? 0);
    $from = $conn->real_escape_string($_POST['from_date'] ?? '2020-01-01');
    $to   = $conn->real_escape_string($_POST['to_date'] ?? date('Y-m-d'));

    $u_info = $conn->query("SELECT id, fullname, email, phone FROM users WHERE id = $uid")->fetch_assoc();

    $sql = "SELECT o.id, o.created_at, o.total_money, o.payment_method, SUM(od.quantity) as items_cnt
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            WHERE o.status = 'completed' AND o.user_id = $uid AND DATE(o.created_at) BETWEEN '$from' AND '$to'
            GROUP BY o.id ORDER BY o.created_at ASC";
    $res = $conn->query($sql);

    $labels = []; $spent_arr = []; $orders_list = []; $total_spent = 0; $total_shoes = 0;
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $labels[]    = date('d/m/Y', strtotime($r['created_at']));
            $spent_arr[] = floatval($r['total_money']);
            $total_spent += floatval($r['total_money']);
            $total_shoes += intval($r['items_cnt']);
            $orders_list[] = [
                'order_id'   => $r['id'],
                'date'       => date('d/m/Y H:i', strtotime($r['created_at'])),
                'items'      => intval($r['items_cnt']),
                'total'      => number_format($r['total_money'], 0, ',', '.') . 'đ',
                'pay_method' => $r['payment_method'] ?? 'COD'
            ];
        }
    }

    echo json_encode([
        'success'       => true,
        'customer_name' => $u_info['fullname'] ?? "Khách #$uid",
        'email'         => $u_info['email'] ?? '',
        'labels'        => $labels,
        'spent'         => $spent_arr,
        'total_spent'   => $total_spent,
        'total_orders'  => count($orders_list),
        'total_shoes'   => $total_shoes,
        'orders'        => $orders_list
    ]);
    exit();
}

// ═════════════════════════════════════════════════════════════════════
// 2. NẠP DỮ LIỆU GIAO DIỆN & BỘ LỌC CHÍNH
// ═════════════════════════════════════════════════════════════════════
$active_tab    = $_GET['tab'] ?? 'time'; // 'time', 'products', 'customers'
$filter_preset = $_GET['preset'] ?? 'this_month';
$from_date     = $_GET['from_date'] ?? date('Y-m-01');
$to_date       = $_GET['to_date'] ?? date('Y-m-d');
$selected_year = intval($_GET['year'] ?? date('Y'));
$time_mode     = $_GET['time_mode'] ?? 'monthly'; // 'monthly', 'quarterly', 'yearly'

if (isset($_GET['preset'])) {
    switch ($filter_preset) {
        case 'today':
            $from_date = date('Y-m-d');
            $to_date   = date('Y-m-d');
            break;
        case 'this_week':
            $from_date = date('Y-m-d', strtotime('monday this week'));
            $to_date   = date('Y-m-d');
            break;
        case 'last_7_days':
            $from_date = date('Y-m-d', strtotime('-6 days'));
            $to_date   = date('Y-m-d');
            break;
        case 'this_month':
            $from_date = date('Y-m-01');
            $to_date   = date('Y-m-d');
            break;
        case 'last_month':
            $from_date = date('Y-m-01', strtotime('first day of last month'));
            $to_date   = date('Y-m-t', strtotime('last month'));
            break;
        case 'this_year':
            $from_date = date('Y-01-01');
            $to_date   = date('Y-m-d');
            break;
        case 'all_time':
            $from_date = '2020-01-01';
            $to_date   = date('Y-m-d');
            break;
    }
}

$from_date_esc = $conn->real_escape_string($from_date);
$to_date_esc   = $conn->real_escape_string($to_date);

// Xác định điều kiện lọc cho các thẻ KPI trên cùng để khớp hoàn toàn với tab đang xem
if ($active_tab === 'time') {
    if ($time_mode === 'monthly' || $time_mode === 'quarterly') {
        $kpi_where_orders = "YEAR(created_at) = $selected_year";
        $kpi_where_items  = "YEAR(o.created_at) = $selected_year";
    } else {
        $kpi_where_orders = "1=1";
        $kpi_where_items  = "1=1";
    }
} else {
    $kpi_where_orders = "DATE(created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'";
    $kpi_where_items  = "DATE(o.created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'";
}

// KPI Tổng Toàn Diện (Tính độc lập tránh nhân đôi doanh thu khi JOIN order_details)
$sql_kpi_orders = "SELECT 
                    COUNT(id) AS total_orders,
                    COALESCE(SUM(total_money), 0) AS total_revenue,
                    COUNT(DISTINCT user_id) AS total_buying_customers
                   FROM orders
                   WHERE status = 'completed' AND $kpi_where_orders";
$kpi_res_orders = $conn->query($sql_kpi_orders)->fetch_assoc();

$sql_kpi_items = "SELECT 
                    COALESCE(SUM(od.quantity), 0) AS total_products_sold,
                    COALESCE(SUM(od.quantity * COALESCE(p.cost_price, 0)), 0) AS total_cost
                  FROM order_details od
                  JOIN orders o ON od.order_id = o.id
                  JOIN products p ON od.product_id = p.id
                  WHERE o.status = 'completed' AND $kpi_where_items";
$kpi_res_items = $conn->query($sql_kpi_items)->fetch_assoc();

$total_revenue          = floatval($kpi_res_orders['total_revenue'] ?? 0);
$total_orders           = intval($kpi_res_orders['total_orders'] ?? 0);
$total_buying_customers = intval($kpi_res_orders['total_buying_customers'] ?? 0);
$total_cost             = floatval($kpi_res_items['total_cost'] ?? 0);
$total_products_sold    = intval($kpi_res_items['total_products_sold'] ?? 0);

$gross_profit           = $total_revenue - $total_cost;
$profit_margin          = ($total_revenue > 0) ? round(($gross_profit / $total_revenue) * 100, 1) : 0;
$avg_order_value        = ($total_orders > 0) ? round($total_revenue / $total_orders) : 0;

// ═════════════════════════════════════════════════════════════════════
// 3. TAB 1: THEO THÁNG / QUÝ / NĂM
// ═════════════════════════════════════════════════════════════════════
$time_labels   = [];
$time_revenue  = [];
$time_cost     = [];
$time_profit   = [];
$time_margins  = [];
$time_list     = [];

if ($time_mode === 'monthly') {
    for ($m = 1; $m <= 12; $m++) {
        $time_labels[] = "Tháng $m";

        $r_o = $conn->query("SELECT COUNT(id) as cnt, COALESCE(SUM(total_money), 0) as rev FROM orders WHERE status = 'completed' AND YEAR(created_at) = $selected_year AND MONTH(created_at) = $m")->fetch_assoc();
        $r_i = $conn->query("SELECT COALESCE(SUM(od.quantity), 0) as sold, COALESCE(SUM(od.quantity * COALESCE(p.cost_price, 0)), 0) as cost FROM order_details od JOIN orders o ON od.order_id = o.id JOIN products p ON od.product_id = p.id WHERE o.status = 'completed' AND YEAR(o.created_at) = $selected_year AND MONTH(o.created_at) = $m")->fetch_assoc();

        $rev    = floatval($r_o['rev'] ?? 0);
        $cost   = floatval($r_i['cost'] ?? 0);
        $prof   = $rev - $cost;
        $cnt    = intval($r_o['cnt'] ?? 0);
        $sold   = intval($r_i['sold'] ?? 0);
        $margin = ($rev > 0) ? round(($prof / $rev) * 100, 1) : 0;

        $time_revenue[] = $rev;
        $time_cost[]    = $cost;
        $time_profit[]  = $prof;
        $time_margins[] = $margin;

        $time_list[] = [
            'period_type' => 'month',
            'period_val'  => $m,
            'year'        => $selected_year,
            'period_name' => "Tháng $m / $selected_year",
            'orders'      => $cnt,
            'sold'        => $sold,
            'revenue'     => $rev,
            'cost'        => $cost,
            'profit'      => $prof,
            'margin'      => $margin
        ];
    }
} elseif ($time_mode === 'quarterly') {
    $quarters_def = [
        1 => ['name' => 'Quý 1 (T1 - T3)', 'months' => [1, 2, 3]],
        2 => ['name' => 'Quý 2 (T4 - T6)', 'months' => [4, 5, 6]],
        3 => ['name' => 'Quý 3 (T7 - T9)', 'months' => [7, 8, 9]],
        4 => ['name' => 'Quý 4 (T10 - T12)', 'months' => [10, 11, 12]]
    ];

    foreach ($quarters_def as $q_num => $q_info) {
        $time_labels[] = "Quý $q_num";
        $m_in = implode(',', $q_info['months']);

        $r_o = $conn->query("SELECT COUNT(id) as cnt, COALESCE(SUM(total_money), 0) as rev FROM orders WHERE status = 'completed' AND YEAR(created_at) = $selected_year AND MONTH(created_at) IN ($m_in)")->fetch_assoc();
        $r_i = $conn->query("SELECT COALESCE(SUM(od.quantity), 0) as sold, COALESCE(SUM(od.quantity * COALESCE(p.cost_price, 0)), 0) as cost FROM order_details od JOIN orders o ON od.order_id = o.id JOIN products p ON od.product_id = p.id WHERE o.status = 'completed' AND YEAR(o.created_at) = $selected_year AND MONTH(o.created_at) IN ($m_in)")->fetch_assoc();

        $rev    = floatval($r_o['rev'] ?? 0);
        $cost   = floatval($r_i['cost'] ?? 0);
        $prof   = $rev - $cost;
        $cnt    = intval($r_o['cnt'] ?? 0);
        $sold   = intval($r_i['sold'] ?? 0);
        $margin = ($rev > 0) ? round(($prof / $rev) * 100, 1) : 0;

        $time_revenue[] = $rev;
        $time_cost[]    = $cost;
        $time_profit[]  = $prof;
        $time_margins[] = $margin;

        $time_list[] = [
            'period_type' => 'quarter',
            'period_val'  => $q_num,
            'year'        => $selected_year,
            'period_name' => $q_info['name'] . " / $selected_year",
            'orders'      => $cnt,
            'sold'        => $sold,
            'revenue'     => $rev,
            'cost'        => $cost,
            'profit'      => $prof,
            'margin'      => $margin
        ];
    }
} else {
    // Từng năm
    $res_y_o = $conn->query("SELECT YEAR(created_at) AS y, COUNT(id) AS cnt, COALESCE(SUM(total_money), 0) AS rev FROM orders WHERE status = 'completed' GROUP BY YEAR(created_at) ORDER BY y ASC");
    $res_y_i = $conn->query("SELECT YEAR(o.created_at) AS y, COALESCE(SUM(od.quantity), 0) AS sold, COALESCE(SUM(od.quantity * COALESCE(p.cost_price, 0)), 0) AS cost FROM order_details od JOIN orders o ON od.order_id = o.id JOIN products p ON od.product_id = p.id WHERE o.status = 'completed' GROUP BY YEAR(o.created_at) ORDER BY y ASC");
    
    $y_costs = []; $y_solds = [];
    if ($res_y_i) while ($r = $res_y_i->fetch_assoc()) { $y_costs[$r['y']] = floatval($r['cost']); $y_solds[$r['y']] = intval($r['sold']); }

    if ($res_y_o) {
        while ($r_y = $res_y_o->fetch_assoc()) {
            $y_val  = intval($r_y['y']);
            $y_name = "Năm " . $y_val;
            $time_labels[] = $y_name;
            $rev    = floatval($r_y['rev']);
            $cost   = floatval($y_costs[$y_val] ?? 0);
            $prof   = $rev - $cost;
            $cnt    = intval($r_y['cnt']);
            $sold   = intval($y_solds[$y_val] ?? 0);
            $margin = ($rev > 0) ? round(($prof / $rev) * 100, 1) : 0;

            $time_revenue[] = $rev;
            $time_cost[]    = $cost;
            $time_profit[]  = $prof;
            $time_margins[] = $margin;

            $time_list[] = [
                'period_type' => 'year',
                'period_val'  => $y_val,
                'year'        => $y_val,
                'period_name' => $y_name,
                'orders'      => $cnt,
                'sold'        => $sold,
                'revenue'     => $rev,
                'cost'        => $cost,
                'profit'      => $prof,
                'margin'      => $margin
            ];
        }
    }
}

// ═════════════════════════════════════════════════════════════════════
// 4. TAB 2: THEO MẶT HÀNG & THƯƠNG HIỆU (KÈM BỘ LỌC THỜI GIAN)
// ═════════════════════════════════════════════════════════════════════
$sql_products_stats = "SELECT 
                        p.id, p.name, p.main_image, p.price, p.cost_price,
                        COALESCE(b.name, 'Hãng khác') as brand_name,
                        COALESCE(c.name, 'Chưa phân loại') as cat_name,
                        COALESCE(SUM(od.quantity), 0) AS total_sold,
                        COALESCE(SUM(od.quantity * od.price), 0) AS total_revenue,
                        COALESCE(SUM(od.quantity * (od.price - COALESCE(p.cost_price, 0))), 0) AS total_profit,
                        MAX(o.created_at) AS last_sold_date
                       FROM products p
                       LEFT JOIN brands b ON p.brand_id = b.id
                       LEFT JOIN categories c ON p.category_id = c.id
                       JOIN order_details od ON od.product_id = p.id
                       JOIN orders o ON od.order_id = o.id AND o.status = 'completed' AND DATE(o.created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'
                       GROUP BY p.id
                       ORDER BY total_revenue DESC";
$products_stats_res = $conn->query($sql_products_stats);
$products_stats_list = [];
$top5_p_names = []; $top5_p_revenues = []; $c_p = 0;

if ($products_stats_res) {
    while ($r = $products_stats_res->fetch_assoc()) {
        $r['margin'] = ($r['total_revenue'] > 0) ? round(($r['total_profit'] / $r['total_revenue']) * 100, 1) : 0;
        $products_stats_list[] = $r;

        if ($c_p < 5) {
            $top5_p_names[]    = (mb_strlen($r['name']) > 24) ? mb_substr($r['name'], 0, 22) . '...' : $r['name'];
            $top5_p_revenues[] = floatval($r['total_revenue']);
            $c_p++;
        }
    }
}

// Doanh thu theo thương hiệu trong kỳ
$sql_brand = "SELECT 
                COALESCE(b.name, 'Hãng khác') AS brand_name,
                COALESCE(SUM(od.quantity), 0) AS total_sold,
                COALESCE(SUM(od.quantity * od.price), 0) AS total_revenue
              FROM products p
              LEFT JOIN brands b ON p.brand_id = b.id
              JOIN order_details od ON od.product_id = p.id
              JOIN orders o ON od.order_id = o.id AND o.status = 'completed' AND DATE(o.created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'
              GROUP BY b.id, b.name
              ORDER BY total_revenue DESC";
$brand_res = $conn->query($sql_brand);
$brand_labels = []; $brand_revenue = [];
if ($brand_res) {
    while ($r = $brand_res->fetch_assoc()) {
        $brand_labels[]  = $r['brand_name'];
        $brand_revenue[] = floatval($r['total_revenue']);
    }
}

// ═════════════════════════════════════════════════════════════════════
// 5. TAB 3: THEO TÀI KHOẢN KHÁCH HÀNG & THEO THỜI GIAN
// ═════════════════════════════════════════════════════════════════════
$sql_customers_stats = "SELECT 
                            u.id, u.fullname, u.email, u.phone, u.avatar, u.created_at as register_date,
                            COUNT(DISTINCT o.id) as orders_count,
                            COALESCE(SUM(o.total_money), 0) as total_spent,
                            COALESCE(SUM(od.quantity), 0) as total_items_bought,
                            MAX(o.created_at) as last_order_date
                        FROM users u
                        JOIN orders o ON u.id = o.user_id AND o.status = 'completed' AND DATE(o.created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'
                        LEFT JOIN order_details od ON o.id = od.order_id
                        GROUP BY u.id
                        ORDER BY total_spent DESC";
$customers_stats_res = $conn->query($sql_customers_stats);
$customers_stats_list = [];
$top5_cust_names = []; $top5_cust_spent = []; $c_k = 0;

$top1_cust_name  = 'Chưa có';
$top1_cust_spent = 0;

if ($customers_stats_res) {
    while ($r = $customers_stats_res->fetch_assoc()) {
        $spent = floatval($r['total_spent']);
        $customers_stats_list[] = $r;

        if ($c_k === 0) {
            $top1_cust_name  = $r['fullname'] ?: 'Khách #' . $r['id'];
            $top1_cust_spent = $spent;
        }

        if ($c_k < 5) {
            $top5_cust_names[] = $r['fullname'] ?: 'Khách #' . $r['id'];
            $top5_cust_spent[] = $spent;
            $c_k++;
        }
    }
}
$total_cust_count   = count($customers_stats_list);
$avg_spend_per_cust = ($total_cust_count > 0) ? round($total_revenue / $total_cust_count) : 0;
$avg_shoes_per_cust = ($total_cust_count > 0) ? round($total_products_sold / $total_cust_count, 1) : 0;

// Diễn biến lượt khách mua theo thời gian
$sql_cust_trend = "SELECT 
                    DATE(o.created_at) AS order_day,
                    COUNT(DISTINCT o.user_id) AS active_cust,
                    COUNT(DISTINCT o.id) AS order_cnt,
                    SUM(o.total_money) AS day_rev
                   FROM orders o
                   WHERE o.status = 'completed' AND DATE(o.created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'
                   GROUP BY DATE(o.created_at)
                   ORDER BY order_day ASC";
$cust_trend_res = $conn->query($sql_cust_trend);
$cust_trend_labels = []; $cust_trend_counts = []; $cust_trend_revs = [];
if ($cust_trend_res) {
    while ($r_ct = $cust_trend_res->fetch_assoc()) {
        $cust_trend_labels[] = date('d/m', strtotime($r_ct['order_day']));
        $cust_trend_counts[] = intval($r_ct['active_cust']);
        $cust_trend_revs[]   = floatval($r_ct['day_rev']);
    }
}

// ═════════════════════════════════════════════════════════════════════
// 6. XUẤT FILE EXCEL (CSV CÓ DẤU TIẾNG VIỆT UTF-8)
// ═════════════════════════════════════════════════════════════════════
if (isset($_GET['export_csv']) && $_GET['export_csv'] == 1) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Bao_Cao_' . $active_tab . '_' . date('Ymd_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    if ($active_tab === 'time') {
        fputcsv($output, ['BÁO CÁO DOANH THU THEO THỜI GIAN (' . strtoupper($time_mode) . ')']);
        fputcsv($output, ['Kỳ Báo Cáo', 'Số Đơn Hoàn Tất', 'Số Đôi Giày Đã Bán', 'Doanh Thu (VNĐ)', 'Giá Vốn (VNĐ)', 'Lợi Nhuận Gộp (VNĐ)', 'Biên Lãi (%)']);
        foreach ($time_list as $row) {
            fputcsv($output, [
                $row['period_name'],
                $row['orders'],
                $row['sold'],
                number_format($row['revenue'], 0, '', ''),
                number_format($row['cost'], 0, '', ''),
                number_format($row['profit'], 0, '', ''),
                $row['margin'] . '%'
            ]);
        }
    } elseif ($active_tab === 'products') {
        fputcsv($output, ['BÁO CÁO DOANH SỐ THEO MẶT HÀNG SẢN PHẨM (KỲ: ' . $from_date . ' ĐẾN ' . $to_date . ')']);
        fputcsv($output, ['STT', 'Tên Sản Phẩm', 'Thương Hiệu', 'Danh Mục', 'Giá Bán (VNĐ)', 'Đã Bán (Đôi)', 'Doanh Thu (VNĐ)', 'Lợi Nhuận Gộp (VNĐ)', 'Biên Lãi (%)', 'Lần Bán Gần Nhất']);
        $idx = 1;
        foreach ($products_stats_list as $p) {
            fputcsv($output, [
                $idx++,
                $p['name'],
                $p['brand_name'],
                $p['cat_name'],
                number_format($p['price'], 0, '', ''),
                $p['total_sold'],
                number_format($p['total_revenue'], 0, '', ''),
                number_format($p['total_profit'], 0, '', ''),
                $p['margin'] . '%',
                $p['last_sold_date'] ? date('d/m/Y H:i', strtotime($p['last_sold_date'])) : '—'
            ]);
        }
    } else {
        fputcsv($output, ['BÁO CÁO DOANH SỐ THEO TÀI KHOẢN KHÁCH HÀNG (KỲ: ' . $from_date . ' ĐẾN ' . $to_date . ')']);
        fputcsv($output, ['Hạng', 'Họ Và Tên', 'Email', 'SĐT', 'Số Đơn Mua', 'Số Giày Mua', 'Tổng Chi Tiêu (VNĐ)', 'Lần Mua Cuối']);
        $idx = 1;
        foreach ($customers_stats_list as $c) {
            fputcsv($output, [
                $idx++,
                $c['fullname'],
                $c['email'],
                $c['phone'] ?? '',
                $c['orders_count'],
                $c['total_items_bought'],
                number_format($c['total_spent'], 0, '', ''),
                $c['last_order_date'] ? date('d/m/Y H:i', strtotime($c['last_order_date'])) : '—'
            ]);
        }
    }
    fclose($output);
    exit();
}

include_once 'includes/header.php';
?>

<style>
.stat-kpi-card {
    border: none;
    border-radius: 20px;
    background: #ffffff;
    padding: 1.4rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.stat-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.07);
}
.stat-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: var(--kpi-color, #3b82f6);
}
.kpi-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}
.nav-tab-pill {
    padding: 10px 24px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.9rem;
    color: #475569;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.nav-tab-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.nav-tab-pill.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2);
}
.preset-chip {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    transition: all 0.2s ease;
}
.preset-chip:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.preset-chip.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.analytics-card {
    border: none;
    border-radius: 20px;
    background: #ffffff;
    padding: 1.5rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
}
.clickable-row {
    cursor: pointer;
    transition: all 0.2s ease;
}
.clickable-row:hover {
    background: #eff6ff !important;
}
.clickable-row.row-selected {
    background: #dbeafe !important;
    border-left: 4px solid #2563eb !important;
}
.drilldown-box {
    border: 2px dashed #93c5fd;
    border-radius: 20px;
    background: #f8fafc;
    padding: 1.5rem;
    display: none;
    animation: fadeInDown 0.35s ease forwards;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
@media print {
    .no-print, .admin-sidebar, .header-navbar { display: none !important; }
    .print-area { width: 100% !important; padding: 0 !important; }
    .analytics-card, .stat-kpi-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
}
</style>

<!-- ══════════════════ TIÊU ĐỀ TRANG & BỘ LỌC TOÀN CỤC ══════════════════ -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-chart-pie text-primary me-2"></i>Báo Cáo &amp; Thống Kê Doanh Thu Đa Chiều
        </h4>
        <span class="text-muted small">Phân tích theo <b>Tháng / Quý / Năm</b>, <b>Mặt Hàng Sản Phẩm</b> và <b>Tài Khoản Khách Hàng</b>.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="statistics.php?tab=<?= $active_tab ?>&time_mode=<?= $time_mode ?>&year=<?= $selected_year ?>&preset=<?= urlencode($filter_preset) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&export_csv=1" class="btn btn-outline-success fw-bold rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Xuất Excel Tab Này
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-print me-1"></i> In Báo Cáo
        </button>
    </div>
</div>

<div class="print-area">

<!-- ══════════════════ 5 THẺ KPI TỔNG KẾT TOÀN DIỆN ══════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl">
        <div class="stat-kpi-card h-100" style="--kpi-color: #2563eb;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Tổng Doanh Thu</span>
                    <h4 class="fw-bold text-dark mb-0"><?= number_format($total_revenue, 0, ',', '.') ?>đ</h4>
                </div>
                <div class="kpi-icon-box bg-primary-subtle text-primary"><i class="fa-solid fa-sack-dollar"></i></div>
            </div>
            <div class="small text-muted mt-2"><i class="fa-solid fa-receipt text-primary me-1"></i> AOV: <strong><?= number_format($avg_order_value, 0, ',', '.') ?>đ</strong> / đơn</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl">
        <div class="stat-kpi-card h-100" style="--kpi-color: #16a34a;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Lợi Nhuận Gộp</span>
                    <h4 class="fw-bold text-success mb-0"><?= number_format($gross_profit, 0, ',', '.') ?>đ</h4>
                </div>
                <div class="kpi-icon-box bg-success-subtle text-success"><i class="fa-solid fa-coins"></i></div>
            </div>
            <div class="small text-muted mt-2"><span class="badge bg-success-subtle text-success fw-bold me-1">Biên lãi: <?= $profit_margin ?>%</span></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl">
        <div class="stat-kpi-card h-100" style="--kpi-color: #ea580c;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Giá Vốn Nhập</span>
                    <h4 class="fw-bold text-dark mb-0"><?= number_format($total_cost, 0, ',', '.') ?>đ</h4>
                </div>
                <div class="kpi-icon-box bg-warning-subtle text-warning"><i class="fa-solid fa-boxes-packing text-warning"></i></div>
            </div>
            <div class="small text-muted mt-2">Chiếm <strong><?= $total_revenue > 0 ? round(($total_cost / $total_revenue) * 100, 1) : 0 ?>%</strong> doanh thu</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl">
        <div class="stat-kpi-card h-100" style="--kpi-color: #06b6d4;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Đơn Hoàn Tất</span>
                    <h4 class="fw-bold text-dark mb-0"><?= number_format($total_orders) ?> đơn</h4>
                </div>
                <div class="kpi-icon-box bg-info-subtle text-info"><i class="fa-solid fa-truck-fast"></i></div>
            </div>
            <div class="small text-muted mt-2">Đã bán: <strong><?= number_format($total_products_sold) ?> đôi</strong></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl">
        <div class="stat-kpi-card h-100" style="--kpi-color: #8b5cf6;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Khách Hàng Mua</span>
                    <h4 class="fw-bold text-dark mb-0"><?= number_format($total_buying_customers) ?> khách</h4>
                </div>
                <div class="kpi-icon-box bg-purple-subtle text-primary" style="background: #ede9fe;"><i class="fa-solid fa-users text-primary"></i></div>
            </div>
            <div class="small text-muted mt-2">Có đơn hoàn tất</div>
        </div>
    </div>
</div>

<!-- ══════════════════ 3 TAB ĐIỀU HƯỚNG BÁO CÁO CHÍNH ══════════════════ -->
<div class="d-flex flex-wrap gap-2 mb-4 border-bottom pb-3 no-print">
    <a href="statistics.php?tab=time&time_mode=<?= $time_mode ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>" class="nav-tab-pill <?= $active_tab === 'time' ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-days text-warning"></i> 1. Theo Tháng / Quý / Năm
    </a>
    <a href="statistics.php?tab=products&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>" class="nav-tab-pill <?= $active_tab === 'products' ? 'active' : '' ?>">
        <i class="fa-solid fa-shoe-prints text-info"></i> 2. Theo Mặt Hàng Sản Phẩm
    </a>
    <a href="statistics.php?tab=customers&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>" class="nav-tab-pill <?= $active_tab === 'customers' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-tag text-success"></i> 3. Theo Tài Khoản Khách Hàng
    </a>
</div>

<!-- ═════════════════════════════════════════════════════════════════════
     TAB 1: THỐNG KÊ THEO THÁNG / QUÝ / NĂM (NHƯ CŨ + BẤM DÒNG XEM DRILLDOWN)
══════════════════════════════════════════════════════════════════════ -->
<?php if ($active_tab === 'time'): ?>
    
    <!-- Bộ Lọc Chu Kỳ Tháng / Quý / Năm + Chọn Từ ngày đến ngày -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4 no-print">
        <form method="GET" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <input type="hidden" name="tab" value="time">

            <div class="d-flex align-items-center gap-2">
                <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-sliders text-primary me-1"></i>Xem theo:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="statistics.php?tab=time&time_mode=monthly&year=<?= $selected_year ?>" class="btn btn-outline-dark fw-bold <?= $time_mode === 'monthly' ? 'active' : '' ?>">
                        🗓️ Theo 12 Tháng
                    </a>
                    <a href="statistics.php?tab=time&time_mode=quarterly&year=<?= $selected_year ?>" class="btn btn-outline-dark fw-bold <?= $time_mode === 'quarterly' ? 'active' : '' ?>">
                        📊 Theo 4 Quý
                    </a>
                    <a href="statistics.php?tab=time&time_mode=yearly" class="btn btn-outline-dark fw-bold <?= $time_mode === 'yearly' ? 'active' : '' ?>">
                        📈 Từng Năm
                    </a>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <?php if ($time_mode !== 'yearly'): ?>
                    <span class="small fw-bold text-muted">Năm:</span>
                    <select name="year" class="form-select form-select-sm fw-bold w-auto me-2" onchange="this.form.submit()">
                        <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 2 Biểu Đồ Tổng Quan Tháng / Quý / Năm -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="analytics-card h-100">
                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-column text-primary me-2"></i>Doanh Thu &amp; Giá Vốn</h5>
                <span class="text-muted small d-block mb-3"><?= ($time_mode === 'monthly') ? "Diễn biến 12 tháng năm $selected_year" : (($time_mode === 'quarterly') ? "So sánh 4 quý năm $selected_year" : "Tăng trưởng các năm") ?></span>
                <div style="height: 270px; position: relative;"><canvas id="timePeriodChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="analytics-card h-100">
                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-line text-success me-2"></i>Biên Lợi Nhuận (%)</h5>
                <span class="text-muted small d-block mb-3">Tỷ suất sinh lời theo từng chu kỳ</span>
                <div style="height: 270px; position: relative;"><canvas id="timeMarginChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Bảng Dữ Liệu Kỳ Báo Cáo (Click vào 1 dòng để xem Drilldown) -->
    <div class="analytics-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-table-list text-success me-2"></i>Bảng Tổng Hợp Kỳ Báo Cáo</h5>
                <span class="text-muted small">💡 <b>Gợi ý</b>: Bấm vào một dòng bất kỳ trong bảng để xem biểu đồ bóc tách chi tiết của kỳ đó bên dưới!</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="timeTable">
                <thead class="table-dark text-uppercase">
                    <tr>
                        <th class="ps-3">Kỳ Báo Cáo (Bấm để xem)</th>
                        <th class="text-center">Số Đơn Hoàn Tất</th>
                        <th class="text-center">Số Đôi Giày Bán</th>
                        <th class="text-end">Doanh Thu Thuần</th>
                        <th class="text-end">Giá Vốn Nhập</th>
                        <th class="text-end">Lợi Nhuận Gộp</th>
                        <th class="text-end pe-3">Biên Lãi (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sum_orders = 0; $sum_sold = 0; $sum_rev = 0; $sum_cost = 0; $sum_prof = 0;
                    foreach ($time_list as $row): 
                        $sum_orders += $row['orders'];
                        $sum_sold   += $row['sold'];
                        $sum_rev    += $row['revenue'];
                        $sum_cost   += $row['cost'];
                        $sum_prof   += $row['profit'];
                    ?>
                        <tr class="clickable-row" onclick="loadDrilldownTime('<?= $row['period_type'] ?>', <?= $row['period_val'] ?>, <?= $row['year'] ?>, this)">
                            <td class="ps-3 fw-bold text-primary">
                                <i class="fa-solid fa-chart-simple me-2 text-primary"></i><?= htmlspecialchars($row['period_name']) ?>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= number_format($row['orders']) ?></span></td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= number_format($row['sold']) ?> đôi</span></td>
                            <td class="text-end fw-bold text-dark"><?= number_format($row['revenue'], 0, ',', '.') ?>đ</td>
                            <td class="text-end text-muted"><?= number_format($row['cost'], 0, ',', '.') ?>đ</td>
                            <td class="text-end fw-bold text-success">+<?= number_format($row['profit'], 0, ',', '.') ?>đ</td>
                            <td class="text-end pe-3">
                                <span class="badge <?= $row['margin'] >= 20 ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-2"><?= $row['margin'] ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3 text-uppercase text-dark">TỔNG CỘNG:</td>
                        <td class="text-center text-dark"><?= number_format($sum_orders) ?> đơn</td>
                        <td class="text-center text-primary"><?= number_format($sum_sold) ?> đôi</td>
                        <td class="text-end text-primary fs-6"><?= number_format($sum_rev, 0, ',', '.') ?>đ</td>
                        <td class="text-end text-muted"><?= number_format($sum_cost, 0, ',', '.') ?>đ</td>
                        <td class="text-end text-success fs-6">+<?= number_format($sum_prof, 0, ',', '.') ?>đ</td>
                        <td class="text-end pe-3 text-success"><?= $sum_rev > 0 ? round(($sum_prof / $sum_rev) * 100, 1) : 0 ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- KHUNG HIỂN THỊ BIỂU ĐỒ DRILL-DOWN CHI TIẾT KHI BẤM DÒNG -->
    <div id="drilldownTimeContainer" class="drilldown-box mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold text-uppercase mb-1">🔍 Phân Tích Bóc Tách Chi Tiết</span>
                <h5 class="fw-bold text-dark mb-0" id="drilldownTimeTitle">Biểu Đồ Chi Tiết Kỳ</h5>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="closeDrilldown('drilldownTimeContainer')">
                <i class="fa-solid fa-xmark me-1"></i> Đóng
            </button>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="drilldownTimeChart"></canvas>
        </div>
    </div>

<!-- ═════════════════════════════════════════════════════════════════════
     TAB 2: THỐNG KÊ THEO MẶT HÀNG SẢN PHẨM & THEO THỜI GIAN
══════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'products'): ?>

    <!-- Bộ Lọc Thời Gian Riêng Biệt Cho Tab Sản Phẩm -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4 no-print">
        <form method="GET" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <input type="hidden" name="tab" value="products">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-clock-rotate-left text-primary me-1"></i>Thời gian bán:</span>
                <a href="statistics.php?tab=products&preset=today" class="preset-chip <?= $filter_preset === 'today' ? 'active' : '' ?>">☀️ Hôm nay</a>
                <a href="statistics.php?tab=products&preset=this_week" class="preset-chip <?= $filter_preset === 'this_week' ? 'active' : '' ?>">📅 Tuần này</a>
                <a href="statistics.php?tab=products&preset=this_month" class="preset-chip <?= $filter_preset === 'this_month' ? 'active' : '' ?>">🗓️ Tháng này</a>
                <a href="statistics.php?tab=products&preset=this_year" class="preset-chip <?= $filter_preset === 'this_year' ? 'active' : '' ?>">📈 Năm nay</a>
                <a href="statistics.php?tab=products&preset=all_time" class="preset-chip <?= $filter_preset === 'all_time' ? 'active' : '' ?>">🌐 Tất cả</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="date" name="from_date" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($from_date) ?>">
                <span class="small text-muted">-</span>
                <input type="date" name="to_date" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($to_date) ?>">
                <button type="submit" class="btn btn-sm btn-dark fw-bold rounded-pill px-3">Lọc</button>
            </div>
        </form>
    </div>

    <!-- 2 BIỂU ĐỒ TỔNG QUAN THEO THỜI GIAN CHO MẶT HÀNG -->
    <div class="row g-4 mb-4">
        <!-- Top 5 Sản Phẩm Bán Chạy Trong Kỳ -->
        <div class="col-12 col-xl-7">
            <div class="analytics-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-trophy text-warning me-2"></i>Top 5 Sản Phẩm Doanh Thu Cao Nhất</h5>
                        <span class="text-muted small">Trong kỳ: <?= date('d/m/Y', strtotime($from_date)) ?> - <?= date('d/m/Y', strtotime($to_date)) ?></span>
                    </div>
                </div>
                <div style="height: 270px; position: relative;"><canvas id="topProductsBarChart"></canvas></div>
            </div>
        </div>

        <!-- Cơ Cấu Doanh Thu Theo Thương Hiệu Trong Kỳ -->
        <div class="col-12 col-xl-5">
            <div class="analytics-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-crown text-danger me-2"></i>Doanh Thu Theo Thương Hiệu</h5>
                        <span class="text-muted small">Tỷ trọng đóng góp của từng thương hiệu</span>
                    </div>
                </div>
                <div style="height: 270px; position: relative;" class="d-flex align-items-center justify-content-center">
                    <canvas id="brandDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng Thống Kê Mặt Hàng Có Cột Lần Bán Gần Nhất -->
    <div class="analytics-card mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Bảng Thống Kê Doanh Số Chi Tiết Theo Mặt Hàng</h5>
                <span class="text-muted small">💡 <b>Gợi ý</b>: Bấm vào một dòng giày bất kỳ để xem biểu đồ diễn biến bán hàng của riêng mẫu giày đó!</span>
            </div>
            <div style="min-width: 250px;" class="no-print">
                <input type="text" id="filterProductSearch" class="form-control form-control-sm rounded-pill px-3 shadow-sm" placeholder="🔍 Tìm nhanh tên giày / hãng..." oninput="filterProductsTable(this.value)">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="productsStatTable">
                <thead class="table-dark text-uppercase">
                    <tr>
                        <th class="ps-3" style="width: 50px;">STT</th>
                        <th>Hình Ảnh &amp; Tên Giày (Bấm để xem)</th>
                        <th>Thương Hiệu</th>
                        <th>Danh Mục</th>
                        <th class="text-end">Giá Bán</th>
                        <th class="text-center">Đã Bán</th>
                        <th class="text-end">Tổng Doanh Thu</th>
                        <th class="text-end">Lợi Nhuận Gộp</th>
                        <th class="text-end">Biên Lãi</th>
                        <th class="text-end pe-3">Lần Bán Gần Nhất</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products_stats_list)): ?>
                        <?php $idx = 1; foreach ($products_stats_list as $p): ?>
                            <?php $img = (strpos($p['main_image'], 'http') === 0) ? $p['main_image'] : '../' . $p['main_image']; ?>
                            <tr class="clickable-row" onclick="loadDrilldownProduct(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', this)">
                                <td class="ps-3 fw-bold text-muted"><?= $idx++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= htmlspecialchars($img) ?>" class="rounded-3 border" style="width: 42px; height: 42px; object-fit: cover;">
                                        <div>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($p['name']) ?></strong>
                                            <small class="text-muted">Giá vốn: <?= number_format($p['cost_price'], 0, ',', '.') ?>đ</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['brand_name']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                                <td class="text-end fw-semibold text-dark"><?= number_format($p['price'], 0, ',', '.') ?>đ</td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace fw-bold">
                                        <?= number_format($p['total_sold']) ?> đôi
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark"><?= number_format($p['total_revenue'], 0, ',', '.') ?>đ</td>
                                <td class="text-end fw-bold text-success">+<?= number_format($p['total_profit'], 0, ',', '.') ?>đ</td>
                                <td class="text-end">
                                    <span class="badge <?= $p['margin'] >= 25 ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-2"><?= $p['margin'] ?>%</span>
                                </td>
                                <td class="text-end pe-3 text-muted">
                                    <?= $p['last_sold_date'] ? date('d/m/Y H:i', strtotime($p['last_sold_date'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">Chưa có sản phẩm nào phát sinh doanh số trong khoảng thời gian này.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- KHUNG DRILL-DOWN SẢN PHẨM -->
    <div id="drilldownProductContainer" class="drilldown-box mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="badge bg-info text-dark rounded-pill px-3 py-1 fw-bold text-uppercase mb-1">👟 Diễn Biến Doanh Số Mặt Hàng</span>
                <h5 class="fw-bold text-dark mb-0" id="drilldownProductTitle">Chi Tiết Sản Phẩm</h5>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="closeDrilldown('drilldownProductContainer')">
                <i class="fa-solid fa-xmark me-1"></i> Đóng
            </button>
        </div>
        <div style="height: 300px; position: relative;">
            <canvas id="drilldownProductChart"></canvas>
        </div>
    </div>

<!-- ═════════════════════════════════════════════════════════════════════
     TAB 3: THEO TÀI KHOẢN KHÁCH HÀNG & THEO THỜI GIAN
══════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'customers'): ?>

    <!-- Bộ Lọc Thời Gian Trực Tiếp Tại Tab Khách Hàng -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4 no-print">
        <form method="GET" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <input type="hidden" name="tab" value="customers">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-clock-rotate-left text-primary me-1"></i>Lọc theo kỳ:</span>
                <a href="statistics.php?tab=customers&preset=today" class="preset-chip <?= $filter_preset === 'today' ? 'active' : '' ?>">☀️ Hôm nay</a>
                <a href="statistics.php?tab=customers&preset=this_week" class="preset-chip <?= $filter_preset === 'this_week' ? 'active' : '' ?>">📅 Tuần này</a>
                <a href="statistics.php?tab=customers&preset=this_month" class="preset-chip <?= $filter_preset === 'this_month' ? 'active' : '' ?>">🗓️ Tháng này</a>
                <a href="statistics.php?tab=customers&preset=this_year" class="preset-chip <?= $filter_preset === 'this_year' ? 'active' : '' ?>">📈 Năm nay</a>
                <a href="statistics.php?tab=customers&preset=all_time" class="preset-chip <?= $filter_preset === 'all_time' ? 'active' : '' ?>">🌐 Tất cả</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="date" name="from_date" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($from_date) ?>">
                <span class="small text-muted">-</span>
                <input type="date" name="to_date" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($to_date) ?>">
                <button type="submit" class="btn btn-sm btn-dark fw-bold rounded-pill px-3">Lọc</button>
            </div>
        </form>
    </div>

    <!-- 4 THẺ CHỈ SỐ KHÁCH HÀNG TRONG KỲ -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Khách Hàng Đã Mua</span>
                        <h4 class="fw-bold text-primary mb-0"><?= number_format($total_cust_count) ?> khách</h4>
                    </div>
                    <span class="badge bg-primary-subtle text-primary fs-4 p-2 rounded-circle"><i class="fa-solid fa-users"></i></span>
                </div>
                <div class="small text-muted mt-2">
                    Trong kỳ: <?= date('d/m', strtotime($from_date)) ?> - <?= date('d/m', strtotime($to_date)) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Chi Tiêu TB / Khách</span>
                        <h4 class="fw-bold text-success mb-0"><?= number_format($avg_spend_per_cust, 0, ',', '.') ?>đ</h4>
                    </div>
                    <span class="badge bg-success-subtle text-success fs-4 p-2 rounded-circle"><i class="fa-solid fa-wallet"></i></span>
                </div>
                <div class="small text-muted mt-2">
                    Giá trị trung bình mỗi khách
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Số Giày TB / Khách</span>
                        <h4 class="fw-bold text-dark mb-0"><?= $avg_shoes_per_cust ?> đôi</h4>
                    </div>
                    <span class="badge bg-warning-subtle text-warning fs-4 p-2 rounded-circle"><i class="fa-solid fa-shoe-prints text-warning"></i></span>
                </div>
                <div class="small text-muted mt-2">
                    Trung bình mua trong kỳ
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Top 1 Mua Nhiều Nhất</span>
                        <h5 class="fw-bold text-danger mb-0 text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($top1_cust_name) ?>"><?= htmlspecialchars($top1_cust_name) ?></h5>
                    </div>
                    <span class="badge bg-danger-subtle text-danger fs-4 p-2 rounded-circle"><i class="fa-solid fa-trophy"></i></span>
                </div>
                <div class="small text-muted mt-2">
                    Đã mua: <strong class="text-danger"><?= number_format($top1_cust_spent, 0, ',', '.') ?>đ</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 BIỂU ĐỒ CHI TIẾT THEO KHÁCH HÀNG & THỜI GIAN -->
    <div class="row g-4 mb-4">
        <!-- Top 5 Khách Hàng Chi Tiêu Cao Nhất -->
        <div class="col-12 col-xl-6">
            <div class="analytics-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-check text-primary me-2"></i>Top 5 Khách Hàng Mua Nhiều Nhất Trong Kỳ</h5>
                        <span class="text-muted small">So sánh tổng tiền mua sắm của các khách hàng hàng đầu</span>
                    </div>
                </div>
                <div style="height: 260px; position: relative;"><canvas id="topCustomersBarChart"></canvas></div>
            </div>
        </div>

        <!-- Biến Động Khách Mua & Doanh Thu Theo Thời Gian -->
        <div class="col-12 col-xl-6">
            <div class="analytics-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-line text-success me-2"></i>Lượng Khách &amp; Doanh Thu Theo Thời Gian</h5>
                        <span class="text-muted small">Diễn biến số lượt khách phát sinh mua hàng trong kỳ</span>
                    </div>
                </div>
                <div style="height: 260px; position: relative;"><canvas id="customerTimeTrendChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Bảng Thống Kê Chi Tiết Khách Hàng -->
    <div class="analytics-card mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-users text-primary me-2"></i>Bảng Xếp Hạng Doanh Số Chi Tiết Theo Khách Hàng</h5>
                <span class="text-muted small">💡 <b>Gợi ý</b>: Bấm vào một khách hàng bất kỳ để xem lịch sử mua sắm và các đơn hàng chi tiết của khách đó!</span>
            </div>
            <div style="min-width: 250px;" class="no-print">
                <input type="text" id="filterCustomerSearch" class="form-control form-control-sm rounded-pill px-3 shadow-sm" placeholder="🔍 Tìm tên / email / SĐT khách..." oninput="filterCustomersTable(this.value)">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="customersStatTable">
                <thead class="table-dark text-uppercase">
                    <tr>
                        <th class="ps-3" style="width: 50px;">Hạng</th>
                        <th>Tài Khoản Khách Hàng (Bấm để xem)</th>
                        <th>Thông Tin Liên Hệ</th>
                        <th class="text-center">Số Đơn Mua</th>
                        <th class="text-center">Số Giày Mua</th>
                        <th class="text-end">Tổng Chi Tiêu (VNĐ)</th>
                        <th class="text-end pe-3">Lần Mua Gần Nhất</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers_stats_list)): ?>
                        <?php $rank = 1; foreach ($customers_stats_list as $c): ?>
                            <tr class="clickable-row" onclick="loadDrilldownCustomer(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['fullname'])) ?>', this)">
                                <td class="ps-3">
                                    <span class="badge <?= $rank <= 3 ? 'bg-warning text-dark' : 'bg-light text-dark border' ?> rounded-circle p-2" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">
                                        <?= $rank++ ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 14px;">
                                            <?= mb_strtoupper(mb_substr($c['fullname'] ?: 'K', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($c['fullname']) ?></strong>
                                            <small class="text-muted">Mã KH: #<?= $c['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark"><?= htmlspecialchars($c['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['phone'] ?? 'Chưa cập nhật') ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace fw-bold px-2 py-1"><?= number_format($c['orders_count']) ?> đơn</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace fw-bold"><?= number_format($c['total_items_bought']) ?> đôi</span>
                                </td>
                                <td class="text-end fw-bold text-dark fs-6"><?= number_format($c['total_spent'], 0, ',', '.') ?>đ</td>
                                <td class="text-end pe-3 text-muted"><?= $c['last_order_date'] ? date('d/m/Y H:i', strtotime($c['last_order_date'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có khách hàng nào phát sinh đơn hoàn tất trong khoảng thời gian này.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- KHUNG DRILL-DOWN KHÁCH HÀNG -->
    <div id="drilldownCustomerContainer" class="drilldown-box mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="badge bg-success rounded-pill px-3 py-1 fw-bold text-uppercase mb-1">👤 Hồ Sơ &amp; Lịch Sử Chi Tiêu</span>
                <h5 class="fw-bold text-dark mb-0" id="drilldownCustomerTitle">Lịch Sử Mua Hàng</h5>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="closeDrilldown('drilldownCustomerContainer')">
                <i class="fa-solid fa-xmark me-1"></i> Đóng
            </button>
        </div>
        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div style="height: 270px; position: relative;"><canvas id="drilldownCustomerChart"></canvas></div>
            </div>
            <div class="col-12 col-lg-5">
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-receipt text-primary me-1"></i>Các đơn hàng hoàn tất:</h6>
                <div class="table-responsive" style="max-height: 230px; overflow-y: auto;">
                    <table class="table table-sm table-bordered bg-white small mb-0" id="drilldownCustomerOrdersTable">
                        <thead class="table-light">
                            <tr><th>Mã Đơn</th><th>Ngày Đặt</th><th>Số Giày</th><th class="text-end">Tổng Tiền</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

</div><!-- /.print-area -->

<!-- ══════════════════ JAVASCRIPT: KHỞI TẠO BIỂU ĐỒ & DRILLDOWN ══════════════════ -->
<script>
(function() {
    window.drillChartTime = window.drillChartTime || null;
    window.drillChartProduct = window.drillChartProduct || null;
    window.drillChartCustomer = window.drillChartCustomer || null;

    function renderAllCharts() {
        if (typeof Chart === 'undefined') {
            setTimeout(renderAllCharts, 50);
            return;
        }

        <?php if ($active_tab === 'time'): ?>
        const elTime = document.getElementById('timePeriodChart');
        if (elTime) {
            if (window.mainTimeChartInstance) {
                try { window.mainTimeChartInstance.destroy(); } catch(e) {}
            }
            const ctxTime = elTime.getContext('2d');
            window.mainTimeChartInstance = new Chart(ctxTime, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($time_labels) ?>,
                    datasets: [
                        { label: 'Doanh Thu Thuần', data: <?= json_encode($time_revenue) ?>, backgroundColor: '#2563eb', borderRadius: 6 },
                        { label: 'Lợi Nhuận Gộp', data: <?= json_encode($time_profit) ?>, backgroundColor: '#16a34a', borderRadius: 6 },
                        { label: 'Giá Vốn Hàng Bán', data: <?= json_encode($time_cost) ?>, backgroundColor: 'rgba(234, 88, 12, 0.3)', borderColor: '#ea580c', borderWidth: 2, borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: 'Inter', weight: '600' } } },
                        tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(ctx.raw) + ' đ' } }
                    },
                    scales: { y: { ticks: { callback: (v) => v >= 1000000 ? (v/1000000).toFixed(1)+' Tr' : v } } }
                }
            });
        }

        const elMargin = document.getElementById('timeMarginChart');
        if (elMargin) {
            if (window.mainMarginChartInstance) {
                try { window.mainMarginChartInstance.destroy(); } catch(e) {}
            }
            const ctxMargin = elMargin.getContext('2d');
            window.mainMarginChartInstance = new Chart(ctxMargin, {
                type: 'line',
                data: {
                    labels: <?= json_encode($time_labels) ?>,
                    datasets: [{
                        label: 'Biên Lãi (%)',
                        data: <?= json_encode($time_margins) ?>,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.15)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => 'Biên Lãi: ' + ctx.raw + '%' } }
                    },
                    scales: { y: { min: 0, ticks: { callback: (v) => v + '%' } } }
                }
            });
        }
        <?php endif; ?>

        <?php if ($active_tab === 'products'): ?>
        const elTopP = document.getElementById('topProductsBarChart');
        if (elTopP) {
            if (window.mainTopPChartInstance) {
                try { window.mainTopPChartInstance.destroy(); } catch(e) {}
            }
            const ctxTopP = elTopP.getContext('2d');
            window.mainTopPChartInstance = new Chart(ctxTopP, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(!empty($top5_p_names) ? $top5_p_names : ['Chưa có']) ?>,
                    datasets: [{
                        label: 'Doanh Thu',
                        data: <?= json_encode(!empty($top5_p_revenues) ? $top5_p_revenues : [0]) ?>,
                        backgroundColor: ['#2563eb', '#16a34a', '#8b5cf6', '#ea580c', '#06b6d4'],
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (c) => 'Doanh Thu: ' + new Intl.NumberFormat('vi-VN').format(c.raw) + ' đ' } }
                    },
                    scales: { x: { ticks: { callback: (v) => v >= 1000000 ? (v/1000000).toFixed(1)+' Tr' : v } } }
                }
            });
        }

        const elBrand = document.getElementById('brandDonutChart');
        if (elBrand) {
            if (window.mainBrandChartInstance) {
                try { window.mainBrandChartInstance.destroy(); } catch(e) {}
            }
            const ctxBrand = elBrand.getContext('2d');
            window.mainBrandChartInstance = new Chart(ctxBrand, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(!empty($brand_labels) ? $brand_labels : ['Chưa có']) ?>,
                    datasets: [{
                        data: <?= json_encode(!empty($brand_revenue) ? $brand_revenue : [0]) ?>,
                        backgroundColor: ['#2563eb', '#16a34a', '#8b5cf6', '#ea580c', '#06b6d4', '#f59e0b', '#ec4899'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'right', labels: { usePointStyle: true, font: { family: 'Inter', size: 11 } } },
                        tooltip: { callbacks: { label: (c) => c.label + ': ' + new Intl.NumberFormat('vi-VN').format(c.raw) + ' đ' } }
                    }
                }
            });
        }
        <?php endif; ?>

        <?php if ($active_tab === 'customers'): ?>
        const elCustBar = document.getElementById('topCustomersBarChart');
        if (elCustBar) {
            if (window.mainCustBarChartInstance) {
                try { window.mainCustBarChartInstance.destroy(); } catch(e) {}
            }
            const ctxCustBar = elCustBar.getContext('2d');
            window.mainCustBarChartInstance = new Chart(ctxCustBar, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(!empty($top5_cust_names) ? $top5_cust_names : ['Chưa có']) ?>,
                    datasets: [{
                        label: 'Tổng Chi Tiêu',
                        data: <?= json_encode(!empty($top5_cust_spent) ? $top5_cust_spent : [0]) ?>,
                        backgroundColor: ['#2563eb', '#16a34a', '#8b5cf6', '#ea580c', '#06b6d4'],
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (c) => 'Đã Mua: ' + new Intl.NumberFormat('vi-VN').format(c.raw) + ' đ' } }
                    },
                    scales: { x: { ticks: { callback: (v) => v >= 1000000 ? (v/1000000).toFixed(1)+' Tr' : v } } }
                }
            });
        }

        const elCustTrend = document.getElementById('customerTimeTrendChart');
        if (elCustTrend) {
            if (window.mainCustTrendChartInstance) {
                try { window.mainCustTrendChartInstance.destroy(); } catch(e) {}
            }
            const ctxCustTrend = elCustTrend.getContext('2d');
            window.mainCustTrendChartInstance = new Chart(ctxCustTrend, {
                type: 'line',
                data: {
                    labels: <?= json_encode(!empty($cust_trend_labels) ? $cust_trend_labels : [date('d/m')]) ?>,
                    datasets: [
                        {
                            label: 'Số Khách Mua Hàng',
                            data: <?= json_encode(!empty($cust_trend_counts) ? $cust_trend_counts : [0]) ?>,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Doanh Thu (VNĐ)',
                            data: <?= json_encode(!empty($cust_trend_revs) ? $cust_trend_revs : [0]) ?>,
                            borderColor: '#16a34a',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [4, 4],
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, font: { family: 'Inter', size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    if (ctx.datasetIndex === 0) return 'Khách Mua: ' + ctx.raw + ' người';
                                    return 'Doanh Thu: ' + new Intl.NumberFormat('vi-VN').format(ctx.raw) + ' đ';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { type: 'linear', display: true, position: 'left', ticks: { stepSize: 1, precision: 0 } },
                        y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: (v) => v >= 1000000 ? (v/1000000).toFixed(1)+' Tr' : v } }
                    }
                }
            });
        }
        <?php endif; ?>
    }

    // Thực thi ngay lập tức
    renderAllCharts();
})();

// ═════════════════════════════════════════════════════════════════════
// CÁC HÀM XỬ LÝ DRILLDOWN TƯƠNG TÁC THỜI GIAN THỰC
// ═════════════════════════════════════════════════════════════════════

// A. Drilldown Thời Gian
window.loadDrilldownTime = function(pType, pVal, year, rowEl) {
    document.querySelectorAll('#timeTable tbody tr').forEach(r => r.classList.remove('row-selected'));
    if (rowEl) rowEl.classList.add('row-selected');

    const container = document.getElementById('drilldownTimeContainer');
    if (!container) return;
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    const fd = new FormData();
    fd.append('ajax_drilldown_time', '1');
    fd.append('period_type', pType);
    fd.append('period_val', pVal);
    fd.append('year', year);

    fetch('statistics.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const titleEl = document.getElementById('drilldownTimeTitle');
            if (titleEl) {
                titleEl.innerHTML = `${data.title} <span class="badge bg-success ms-2 fs-6">${new Intl.NumberFormat('vi-VN').format(data.total_rev)}đ</span>`;
            }

            const canvasEl = document.getElementById('drilldownTimeChart');
            if (!canvasEl) return;
            const ctx = canvasEl.getContext('2d');
            if (window.drillChartTime) {
                try { window.drillChartTime.destroy(); } catch(e) {}
            }

            window.drillChartTime = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Doanh Thu (VNĐ)', data: data.revenue, backgroundColor: '#2563eb', borderRadius: 6 },
                        { label: 'Lợi Nhuận (VNĐ)', data: data.profit, backgroundColor: '#16a34a', borderRadius: 6 },
                        { label: 'Giá Vốn (VNĐ)', data: data.cost, backgroundColor: 'rgba(234, 88, 12, 0.3)', borderColor: '#ea580c', borderWidth: 2, borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(c.raw) + ' đ' } }
                    }
                }
            });
        }
    });
};

// B. Drilldown Mặt Hàng
window.loadDrilldownProduct = function(pid, pName, rowEl) {
    document.querySelectorAll('#productsStatTable tbody tr').forEach(r => r.classList.remove('row-selected'));
    if (rowEl) rowEl.classList.add('row-selected');

    const container = document.getElementById('drilldownProductContainer');
    if (!container) return;
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    const fd = new FormData();
    fd.append('ajax_drilldown_product', '1');
    fd.append('product_id', pid);
    fd.append('from_date', '<?= $from_date ?>');
    fd.append('to_date', '<?= $to_date ?>');

    fetch('statistics.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const titleEl = document.getElementById('drilldownProductTitle');
            if (titleEl) {
                titleEl.innerHTML = `${data.product_name} <span class="badge bg-primary ms-2">${data.total_qty} đôi bán ra (${new Intl.NumberFormat('vi-VN').format(data.total_rev)}đ)</span>`;
            }

            const canvasEl = document.getElementById('drilldownProductChart');
            if (!canvasEl) return;
            const ctx = canvasEl.getContext('2d');
            if (window.drillChartProduct) {
                try { window.drillChartProduct.destroy(); } catch(e) {}
            }

            window.drillChartProduct = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Doanh Thu (VNĐ)',
                            data: data.revenue,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Số Lượng Đôi Bán',
                            data: data.quantities,
                            borderColor: '#ea580c',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointRadius: 5,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { type: 'linear', position: 'left' },
                        y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
                    }
                }
            });
        }
    });
};

// C. Drilldown Khách Hàng
window.loadDrilldownCustomer = function(uid, cName, rowEl) {
    document.querySelectorAll('#customersStatTable tbody tr').forEach(r => r.classList.remove('row-selected'));
    if (rowEl) rowEl.classList.add('row-selected');

    const container = document.getElementById('drilldownCustomerContainer');
    if (!container) return;
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    const fd = new FormData();
    fd.append('ajax_drilldown_customer', '1');
    fd.append('user_id', uid);
    fd.append('from_date', '<?= $from_date ?>');
    fd.append('to_date', '<?= $to_date ?>');

    fetch('statistics.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const titleEl = document.getElementById('drilldownCustomerTitle');
            if (titleEl) {
                titleEl.innerHTML = `${data.customer_name} <span class="badge bg-success ms-2">${data.total_orders} đơn (${new Intl.NumberFormat('vi-VN').format(data.total_spent)}đ)</span>`;
            }

            const canvasEl = document.getElementById('drilldownCustomerChart');
            if (!canvasEl) return;
            const ctx = canvasEl.getContext('2d');
            if (window.drillChartCustomer) {
                try { window.drillChartCustomer.destroy(); } catch(e) {}
            }

            window.drillChartCustomer = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Giá Trị Đơn Hàng (VNĐ)',
                        data: data.spent,
                        backgroundColor: '#16a34a',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: { callbacks: { label: (c) => 'Đơn: ' + new Intl.NumberFormat('vi-VN').format(c.raw) + ' đ' } }
                    }
                }
            });

            const tbody = document.querySelector('#drilldownCustomerOrdersTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                (data.orders || []).forEach(o => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>#${o.order_id}</strong></td>
                            <td>${o.date}</td>
                            <td class="text-center">${o.items} đôi</td>
                            <td class="text-end fw-bold text-success">${o.total}</td>
                        </tr>
                    `;
                });
            }
        }
    });
};

window.closeDrilldown = function(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
    document.querySelectorAll('tr.clickable-row').forEach(r => r.classList.remove('row-selected'));
};

window.filterProductsTable = function(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#productsStatTable tbody tr');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
};

window.filterCustomersTable = function(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#customersStatTable tbody tr');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
};
</script>

<?php include_once 'includes/footer.php'; ?>