<?php
require_once 'includes/header.php';

// 1. Pagination setup
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 2. Filter Parameters
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : (isset($_GET['category']) ? (int)$_GET['category'] : 0);
$brand_id    = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : (isset($_GET['brand']) ? (int)$_GET['brand'] : 0);
$keyword     = isset($_GET['keyword']) ? trim($_GET['keyword']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$sort        = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$discount    = isset($_GET['discount']) ? (int)$_GET['discount'] : 0;
$type        = isset($_GET['type']) ? $_GET['type'] : '';
$gender      = isset($_GET['gender']) ? $_GET['gender'] : '';
$in_stock    = isset($_GET['in_stock']) ? (int)$_GET['in_stock'] : 0;
$layout_view = isset($_GET['layout']) ? $_GET['layout'] : 'grid3'; // grid3, grid4, list

// 3. Build WHERE Query
$where = ["p.status = 1"];
$params = [];
$types = "";

if ($category_id > 0) {
    $where[] = "(p.category_id = ? OR c.parent_id = ?)";
    $params[] = $category_id;
    $params[] = $category_id;
    $types .= "ii";
}
if ($brand_id > 0) {
    $where[] = "p.brand_id = ?";
    $params[] = $brand_id;
    $types .= "i";
}
if ($keyword !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}
if ($discount == 1) {
    $where[] = "p.discount_percent > 0";
}
if ($type !== '') {
    $where[] = "c.type = ?";
    $params[] = $type;
    $types .= "s";
}
if ($gender !== '') {
    $where[] = "p.gender = ?";
    $params[] = $gender;
    $types .= "s";
}
if ($in_stock == 1) {
    $where[] = "EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock_quantity > 0)";
}

if ($price_range !== '') {
    $parts = explode('-', $price_range);
    $min = $parts[0] ?? 0;
    $max = $parts[1] ?? '+';
    $where[] = "p.price >= ?";
    $params[] = (int)$min;
    $types .= "i";
    if ($max !== '+') {
        $where[] = "p.price <= ?";
        $params[] = (int)$max;
        $types .= "i";
    }
}

$whereClause = implode(" AND ", $where);

// 4. THUẬT TOÁN GỢI Ý SẢN PHẨM TƯƠNG TỰ (SHOPEE STYLE RECOMMENDATION)
$user_purchased_ids = [];
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    $uid_p = (int)$_SESSION['user_id'];
    $res_po = $conn->query("SELECT DISTINCT od.product_id FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.user_id = $uid_p");
    if ($res_po) {
        while ($r = $res_po->fetch_assoc()) {
            $user_purchased_ids[] = (int)$r['product_id'];
        }
    }
}

$user_viewed_ids = [];
if (!empty($_SESSION['viewed_product_ids']) && is_array($_SESSION['viewed_product_ids'])) {
    $user_viewed_ids = array_map('intval', $_SESSION['viewed_product_ids']);
} elseif (!empty($_COOKIE['viewed_product_ids'])) {
    $decoded_v = json_decode($_COOKIE['viewed_product_ids'], true);
    if (is_array($decoded_v)) {
        $user_viewed_ids = array_map('intval', $decoded_v);
        $_SESSION['viewed_product_ids'] = $user_viewed_ids;
    }
}

$user_searched_keywords = [];
if (!empty($_SESSION['recent_searches']) && is_array($_SESSION['recent_searches'])) {
    $user_searched_keywords = $_SESSION['recent_searches'];
} elseif (!empty($_COOKIE['recent_searches'])) {
    $decoded_s = json_decode($_COOKIE['recent_searches'], true);
    if (is_array($decoded_s)) {
        $user_searched_keywords = $decoded_s;
        $_SESSION['recent_searches'] = $user_searched_keywords;
    }
}

if (!empty($keyword) && mb_strlen($keyword, 'UTF-8') >= 2) {
    $kw_clean_curr = mb_strtolower($keyword, 'UTF-8');
    if (!in_array($kw_clean_curr, $user_searched_keywords)) {
        array_unshift($user_searched_keywords, $kw_clean_curr);
        $user_searched_keywords = array_slice($user_searched_keywords, 0, 10);
        $_SESSION['recent_searches'] = $user_searched_keywords;
        @setcookie('recent_searches', json_encode($user_searched_keywords), time() + 30*86400, '/');
    }
}

// Tổng hợp các sản phẩm đã tương tác (Mua + Xem) để phân tích gu sở thích của người dùng
$interacted_ids = array_unique(array_merge($user_purchased_ids, $user_viewed_ids));
$interest_brand_ids = [];
$interest_category_ids = [];

if (!empty($interacted_ids)) {
    $int_ids_str = implode(',', array_map('intval', $interacted_ids));
    $res_interests = $conn->query("SELECT DISTINCT brand_id, category_id FROM products WHERE id IN ($int_ids_str)");
    if ($res_interests) {
        while ($row = $res_interests->fetch_assoc()) {
            if (!empty($row['brand_id'])) $interest_brand_ids[] = (int)$row['brand_id'];
            if (!empty($row['category_id'])) $interest_category_ids[] = (int)$row['category_id'];
        }
    }
    $interest_brand_ids = array_unique($interest_brand_ids);
    $interest_category_ids = array_unique($interest_category_ids);
}

// Xây dựng thang điểm gợi ý tương tự (Shopee Style Collaborative Recommendation Score):
// 1. Sản phẩm cùng Thương hiệu (Brand) với món đã mua / đã xem: +250 điểm
// 2. Sản phẩm cùng Danh mục (Category) với món đã mua / đã xem: +200 điểm
// 3. Sản phẩm khớp từ khóa tìm kiếm gần đây: +150 điểm
// 4. Sản phẩm đã xem gần đây: +100 điểm
$score_parts = ["0"];

if (!empty($interest_brand_ids)) {
    $b_ids_str = implode(',', $interest_brand_ids);
    $score_parts[] = "CASE WHEN p.brand_id IN ($b_ids_str) THEN 250 ELSE 0 END";
}

if (!empty($interest_category_ids)) {
    $c_ids_str = implode(',', $interest_category_ids);
    $score_parts[] = "CASE WHEN (p.category_id IN ($c_ids_str) OR c.parent_id IN ($c_ids_str)) THEN 200 ELSE 0 END";
}

if (!empty($user_searched_keywords)) {
    $recent_kws = array_slice($user_searched_keywords, 0, 5);
    foreach ($recent_kws as $skw) {
        $skw_clean = $conn->real_escape_string(trim($skw));
        if (mb_strlen($skw_clean, 'UTF-8') >= 2) {
            $score_parts[] = "CASE WHEN (p.name LIKE '%$skw_clean%' OR b.name LIKE '%$skw_clean%' OR c.name LIKE '%$skw_clean%') THEN 150 ELSE 0 END";
        }
    }
}

if (!empty($user_viewed_ids)) {
    $v_ids_str = implode(',', array_unique($user_viewed_ids));
    $score_parts[] = "CASE WHEN p.id IN ($v_ids_str) THEN 100 ELSE 0 END";
}

$interest_score_sql = "(" . implode(" + ", $score_parts) . ")";

// 5. Sorting logic
$final_order_clause = "user_interest_score DESC, p.id DESC";

if ($sort === 'price_asc') {
    $final_order_clause = "p.price ASC, p.id DESC";
} elseif ($sort === 'price_desc') {
    $final_order_clause = "p.price DESC, p.id DESC";
} elseif ($sort === 'hot') {
    $final_order_clause = "p.sold_count DESC, p.is_hot DESC, p.id DESC";
} elseif ($sort === 'view') {
    $final_order_clause = "p.view_count DESC, p.id DESC";
} elseif ($sort === 'rating') {
    $final_order_clause = "avg_rating DESC, p.id DESC";
} elseif ($sort === 'discount_desc') {
    $final_order_clause = "p.discount_percent DESC, p.id DESC";
} elseif ($sort === 'newest') {
    $final_order_clause = "user_interest_score DESC, p.id DESC";
}

// 6. Count Total Matching Products
$count_sql = "SELECT COUNT(DISTINCT p.id) as total 
              FROM products p 
              JOIN categories c ON p.category_id = c.id 
              JOIN brands b ON p.brand_id = b.id 
              WHERE $whereClause";
$stmt_count = $conn->prepare($count_sql);
if ($types) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// 7. Fetch Products (Ưu tiên sản phẩm đã mua, đã xem, đã tìm kiếm lên đầu tiên khi ở chế độ mặc định)
$sql = "SELECT p.*, b.name as brand_name, c.name as category_name,
               $interest_score_sql as user_interest_score,
               COALESCE(AVG(cm.rating), 5.0) as avg_rating,
               COUNT(cm.id) as review_count,
               (SELECT GROUP_CONCAT(DISTINCT pv.size ORDER BY pv.size ASC SEPARATOR ' ') 
                FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock_quantity > 0) as available_sizes,
               COALESCE((SELECT SUM(pv2.stock_quantity) FROM product_variants pv2 WHERE pv2.product_id = p.id), 0) as total_stock
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN comments cm ON cm.product_id = p.id AND cm.status = 1
        WHERE $whereClause 
        GROUP BY p.id
        ORDER BY $final_order_clause 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$types .= "ii";
$params[] = $offset;
$params[] = $limit;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products_result = $stmt->get_result();

// 7. Fetch Filter Reference Data with Product Counts (Hierarchical Categories)
$res_c = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(p.id) FROM products p WHERE (p.category_id = c.id OR p.category_id IN (SELECT sub.id FROM categories sub WHERE sub.parent_id = c.id)) AND p.status = 1) AS total_prod_count,
           (SELECT COUNT(p2.id) FROM products p2 WHERE p2.category_id = c.id AND p2.status = 1) AS direct_prod_count
    FROM categories c 
    WHERE c.status = 1 
    ORDER BY c.sort_order ASC, c.name ASC
");
$parent_cats = [];
$child_cats = [];
$all_cats_map = [];
if ($res_c) {
    while($c = $res_c->fetch_assoc()) {
        $c['id'] = (int)$c['id'];
        $c['parent_id'] = $c['parent_id'] ? (int)$c['parent_id'] : 0;
        $all_cats_map[$c['id']] = $c;
        if ($c['parent_id'] === 0) {
            $c['children'] = [];
            $parent_cats[$c['id']] = $c;
        } else {
            $child_cats[] = $c;
        }
    }
}
foreach ($child_cats as $child) {
    $pid = $child['parent_id'];
    if (isset($parent_cats[$pid])) {
        $parent_cats[$pid]['children'][] = $child;
    } else {
        $child['children'] = [];
        $parent_cats[$child['id']] = $child;
    }
}

$brands = $conn->query("
    SELECT b.*, COUNT(p.id) as product_count 
    FROM brands b 
    LEFT JOIN products p ON p.brand_id = b.id AND p.status = 1
    WHERE b.status = 1 
    GROUP BY b.id 
    ORDER BY product_count DESC, b.name ASC
");

// 8. Fetch Wishlist for active user
$wishlist_product_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $w_res = $conn->query("SELECT product_id FROM wishlists WHERE user_id=$uid");
    if ($w_res) {
        while ($w = $w_res->fetch_assoc()) {
            $wishlist_product_ids[] = intval($w['product_id']);
        }
    }
}

// 9. Helper to generate preserved URL params
function build_url($params_to_update = []) {
    $query = $_GET;
    foreach ($params_to_update as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    return 'all-products.php' . (!empty($query) ? '?' . http_build_query($query) : '');
}

// 10. Dynamic Page Title / Breadcrumb calculation
$page_title_heading = 'TẤT CẢ SẢN PHẨM';
$page_subtitle = 'Khám phá hơn 100+ mẫu Sneaker chính hãng, cập nhật mẫu mới nhất mỗi ngày';
$active_filter_chips = [];

if ($category_id > 0) {
    $cat_name_q = $conn->query("SELECT name FROM categories WHERE id = $category_id");
    if ($cat_name_q && $c_row = $cat_name_q->fetch_assoc()) {
        $page_title_heading = 'DANH MỤC: ' . mb_strtoupper($c_row['name']);
        $active_filter_chips[] = ['label' => 'Danh mục: ' . $c_row['name'], 'param' => 'category_id'];
    }
}
if ($brand_id > 0) {
    $br_name_q = $conn->query("SELECT name FROM brands WHERE id = $brand_id");
    if ($br_name_q && $b_row = $br_name_q->fetch_assoc()) {
        $page_title_heading = 'THƯƠNG HIỆU ' . mb_strtoupper($b_row['name']);
        $active_filter_chips[] = ['label' => 'Hãng: ' . $b_row['name'], 'param' => 'brand_id'];
    }
}
if ($discount == 1) {
    $page_title_heading = '⚡ SIÊU SALE GIẢM GIÁ ĐỈNH CAO';
    $active_filter_chips[] = ['label' => 'Đang giảm giá', 'param' => 'discount'];
}
if ($keyword !== '') {
    $page_title_heading = 'TÌM KIẾM: "' . htmlspecialchars($keyword) . '"';
    $active_filter_chips[] = ['label' => 'Từ khóa: ' . $keyword, 'param' => 'keyword'];
}
if ($gender !== '') {
    $active_filter_chips[] = ['label' => 'Giới tính: ' . $gender, 'param' => 'gender'];
}
if ($type !== '') {
    $active_filter_chips[] = ['label' => 'Loại: ' . ($type === 'giay' ? 'Giày' : 'Dép'), 'param' => 'type'];
}
if ($price_range !== '') {
    $price_labels = [
        '0-1000000' => 'Dưới 1 triệu',
        '1000000-2000000' => '1 - 2 triệu',
        '2000000-4000000' => '2 - 4 triệu',
        '4000000-+' => 'Trên 4 triệu'
    ];
    $active_filter_chips[] = ['label' => 'Giá: ' . ($price_labels[$price_range] ?? $price_range), 'param' => 'price_range'];
}
if ($in_stock == 1) {
    $active_filter_chips[] = ['label' => 'Còn hàng', 'param' => 'in_stock'];
}

$active_filters_count = count($active_filter_chips);
?>

<style>
/* =========================================================
   ALL PRODUCTS PAGE - MODERN ULTRA-PREMIUM STYLES & EFFECTS
========================================================= */

/* Quick Filter Selection Bar */
.quick-filter-wrapper {
    background: var(--card-white, #ffffff);
    padding: 16px 0;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    margin-bottom: 24px;
}
.quick-filter-strip {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 4px 0;
    scrollbar-width: none;
}
.quick-filter-strip::-webkit-scrollbar { display: none; }
.quick-chip {
    white-space: nowrap;
    padding: 9px 20px;
    border-radius: 50px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.quick-chip:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.quick-chip.active {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.25);
}
[data-theme="dark"] .quick-filter-wrapper {
    background: rgba(18, 26, 45, 0.95) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] .quick-chip {
    background: rgba(255, 255, 255, 0.06) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .quick-chip:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #fff !important;
}
[data-theme="dark"] .quick-chip.active {
    background: linear-gradient(135deg, var(--accent-gold, #c5a059), #d4a24a) !important;
    color: #0f172a !important;
    border-color: transparent !important;
}

/* Sidebar Filters */
.filter-sidebar {
    background: var(--card-white, #ffffff);
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.06);
    position: sticky;
    top: 90px;
    transition: box-shadow 0.3s;
}
.filter-sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 14px;
    margin-bottom: 18px;
    border-bottom: 2px solid #f1f5f9;
}
.filter-sidebar-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--primary-dark, #0f172a);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}
.filter-section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--primary-dark, #1e293b);
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Filter Search Input */
.filter-search-wrap {
    position: relative;
    margin-bottom: 20px;
}
.filter-search-input {
    border-radius: 12px;
    padding: 10px 38px 10px 14px;
    border: 1px solid #e2e8f0;
    font-size: 0.9rem;
    transition: all 0.25s;
    background: #f8fafc;
}
.filter-search-input:focus {
    background: #fff;
    border-color: var(--accent-gold, #c5a059);
    box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.15);
}
.filter-search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 4px 8px;
    transition: color 0.2s;
}
.filter-search-btn:hover { color: var(--accent-gold, #c5a059); }

/* Brand Pill Chips */
.brand-filter-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    max-height: 180px;
    overflow-y: auto;
    padding-right: 4px;
}
.brand-filter-grid::-webkit-scrollbar { width: 4px; }
.brand-filter-grid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.brand-chip {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.brand-chip:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-1px);
}
.brand-chip.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.2);
}
.brand-chip .chip-count {
    font-size: 0.7rem;
    opacity: 0.7;
}

/* Category Hierarchical Accordion Tree */
.category-tree-wrapper {
    max-height: 280px;
    overflow-y: auto;
    padding-right: 4px;
}
.category-tree-wrapper::-webkit-scrollbar { width: 4px; }
.category-tree-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.category-tree-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    color: #334155;
    text-decoration: none;
    font-size: 0.88rem;
    transition: all 0.2s;
}
.category-tree-link:hover {
    background: #f8fafc;
    color: var(--accent-gold, #c5a059);
    padding-left: 15px;
}
.category-tree-link.active {
    background: rgba(197, 160, 89, 0.12);
    color: var(--accent-gold, #c5a059);
    font-weight: 700;
}
.cat-arrow-btn {
    border: none;
    background: transparent;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.cat-arrow-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.cat-arrow-btn .arrow-icon {
    font-size: 11px;
    transition: transform 0.25s ease;
}
.cat-arrow-btn[aria-expanded="true"] .arrow-icon,
.cat-arrow-btn .arrow-icon.rotated {
    transform: rotate(90deg);
}
.subcat-tree-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 10px;
    border-radius: 6px;
    color: #64748b;
    text-decoration: none;
    font-size: 0.82rem;
    transition: all 0.2s;
}
.subcat-tree-link:hover {
    background: #f1f5f9;
    color: #0f172a;
    padding-left: 14px;
}
.subcat-tree-link.active {
    background: #0f172a;
    color: #fff;
    font-weight: 600;
}
.subcat-tree-link.active .badge {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

/* Price Radio Cards */
.price-radio-card {
    display: block;
    margin-bottom: 6px;
    cursor: pointer;
}
.price-radio-card input { display: none; }
.price-radio-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    transition: all 0.2s;
}
.price-radio-card:hover .price-radio-box {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.price-radio-card input:checked + .price-radio-box {
    border-color: var(--accent-gold, #c5a059);
    background: rgba(197, 160, 89, 0.08);
    color: var(--accent-gold, #c5a059);
    box-shadow: 0 0 0 1px var(--accent-gold, #c5a059);
}

/* Segmented Buttons */
.segmented-btn-group {
    display: flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}
.segmented-btn {
    flex: 1;
    text-align: center;
    padding: 6px 4px;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s;
}
.segmented-btn:hover { color: #0f172a; }
.segmented-btn.active {
    background: #fff;
    color: #0f172a;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

/* Toolbar Control Bar */
.products-toolbar {
    background: var(--card-white, #ffffff);
    padding: 16px 20px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    border: 1px solid rgba(0,0,0,0.06);
    margin-bottom: 24px;
}
.active-filters-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}
.active-filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    color: #0f172a;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 50px;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.2s;
}
.active-filter-tag:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}
.active-filter-tag i { font-size: 0.75rem; }

/* Layout Switcher */
.layout-btn-group .btn {
    padding: 6px 12px;
    border-radius: 8px;
    color: #64748b;
    border-color: #e2e8f0;
    transition: all 0.2s;
}
.layout-btn-group .btn.active, .layout-btn-group .btn:hover {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
}

/* =========================================================
   PRODUCT CARDS SYSTEM & HOVER MICRO-ANIMATIONS
========================================================= */
.product-card {
    background: var(--card-white, #ffffff);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
}
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 35px rgba(0,0,0,0.12);
    border-color: rgba(197, 160, 89, 0.35);
}

.product-image-wrap {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
    background: #f8fafc;
}
.product-main-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
}
.product-card:hover .product-main-img {
    transform: scale(1.08) rotate(1deg);
}

/* Badges */
.product-badges-wrap {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 3;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.discount-badge {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: 0.5px;
}
.badge-hot {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    animation: pulse 1.5s infinite;
}
.badge-new {
    background: linear-gradient(135deg, #06b6d4, #0284c7);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: 0.5px;
}

/* Cụm góc phải: Nút Tim trên cùng, % Giảm giá bên dưới nút tim */
.product-top-right-wrap {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 5;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}
.product-top-right-wrap .wishlist-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
    opacity: 1 !important;
}
.product-top-right-wrap .wishlist-btn:hover {
    transform: scale(1.15) !important;
    background: #fff;
    color: #ef4444;
}
.product-top-right-wrap .wishlist-btn.active {
    color: #ef4444 !important;
    background: #fff;
}
.product-top-right-wrap .discount-badge {
    position: relative !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    color: #fff !important;
    font-size: 0.72rem !important;
    font-weight: 800 !important;
    padding: 3px 8px !important;
    border-radius: 20px !important;
    letter-spacing: 0.3px !important;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35) !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    z-index: 5 !important;
}
.wishlist-btn:hover { color: #ef4444; }
.quick-view-btn:hover { color: var(--accent-gold, #c5a059); }

/* Sizes Strip Preview on Hover */
.product-sizes-strip {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(8px);
    padding: 6px 10px;
    display: flex;
    align-items: center;
    gap: 5px;
    overflow-x: auto;
    scrollbar-width: none;
    transform: translateY(100%);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 3;
}
.product-sizes-strip::-webkit-scrollbar { display: none; }
.product-card:hover .product-sizes-strip {
    transform: translateY(0);
    opacity: 1;
}
.sizes-title {
    color: #94a3b8;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}
.size-chip {
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    white-space: nowrap;
}

/* Product Info */
.product-info {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.brand-name {
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.product-title {
    font-weight: 700;
    color: var(--primary-dark, #0f172a);
    text-decoration: none;
    font-size: 0.95rem;
    line-height: 1.35;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s;
}
.product-title:hover { color: var(--accent-gold, #c5a059); }
.product-price {
    font-weight: 900;
    color: #0f172a;
    font-size: 1.15rem;
}
.old-price {
    color: #94a3b8;
    text-decoration: line-through;
    font-size: 0.85rem;
}

/* List View Customization */
.products-container.view-list .product-grid-col {
    width: 100% !important;
}
.products-container.view-list .product-card {
    flex-direction: row;
    align-items: center;
    padding: 12px;
}
.products-container.view-list .product-image-wrap {
    width: 160px;
    height: 160px;
    padding-top: 0;
    border-radius: 12px;
    flex-shrink: 0;
}
.products-container.view-list .product-info {
    padding: 0 20px;
}

/* Stagger Animation */
@keyframes cardFadeInUp {
    0% { opacity: 0; transform: translateY(25px); }
    100% { opacity: 1; transform: translateY(0); }
}
.product-grid-col {
    animation: cardFadeInUp 0.5s ease backwards;
}

/* Quick View Modal Styles */
.modal-quickview .modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}
.quickview-img-main {
    width: 100%;
    height: 380px;
    object-fit: cover;
    border-radius: 14px;
}
.quickview-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
}
.quickview-thumb.active, .quickview-thumb:hover {
    border-color: var(--accent-gold, #c5a059);
    transform: scale(1.05);
}
.size-select-btn {
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.2s;
    cursor: pointer;
}
.size-select-btn:hover { border-color: #0f172a; }
.size-select-btn.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
}
.size-select-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    text-decoration: line-through;
}

/* Toast Floating */
.custom-toast {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 9999;
    background: #0f172a;
    color: #fff;
    padding: 14px 22px;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 0.92rem;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}
.custom-toast.show {
    transform: translateY(0);
    opacity: 1;
}
.products-loading-state {
    opacity: 0.45;
    pointer-events: none;
    transition: opacity 0.2s ease;
    filter: blur(0.6px);
}
</style>

<!-- 1. THANH LỰA CHỌN NHANH (QUICK SELECTION FILTER BAR) -->
<div id="quickFilterWrapper" class="quick-filter-wrapper shadow-sm">
    <div class="container">
        <div class="quick-filter-strip align-items-center">
            <a href="all-products.php" class="quick-chip <?= (empty($_GET) || (count($_GET) === 1 && isset($_GET['layout']))) ? 'active' : '' ?>">
                🔥 Tất Cả
            </a>

            <!-- DROPDOWN CHỌN DANH MỤC TRÊN MŨI TÊN (CÓ PHÂN CẤP CHA - CON) -->
            <div class="dropdown d-inline-flex flex-shrink-0">
                <button class="quick-chip dropdown-toggle <?= $category_id > 0 ? 'active' : '' ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-layer-group me-1"></i> <?= ($category_id > 0 && isset($all_cats_map[$category_id])) ? htmlspecialchars($all_cats_map[$category_id]['name']) : 'Danh Mục' ?>
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-4 py-2" style="min-width: 250px; max-height: 380px; overflow-y: auto;">
                    <li><a class="dropdown-item fw-bold <?= $category_id === 0 ? 'active' : '' ?>" href="<?= build_url(['category_id' => '']) ?>">📁 Tất Cả Danh Mục</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($parent_cats as $pcat): ?>
                        <li>
                            <a class="dropdown-item fw-bold d-flex justify-content-between align-items-center <?= $category_id === $pcat['id'] ? 'active text-primary' : '' ?>" href="<?= build_url(['category_id' => $pcat['id']]) ?>">
                                <span>📂 <?= htmlspecialchars($pcat['name']) ?></span>
                                <span class="badge bg-light text-muted rounded-pill"><?= $pcat['total_prod_count'] ?></span>
                            </a>
                        </li>
                        <?php foreach ($pcat['children'] as $ccat): ?>
                            <li>
                                <a class="dropdown-item ps-4 small d-flex justify-content-between align-items-center <?= $category_id === $ccat['id'] ? 'active text-primary' : '' ?>" href="<?= build_url(['category_id' => $ccat['id']]) ?>">
                                    <span>&nbsp;&nbsp;↳ <?= htmlspecialchars($ccat['name']) ?></span>
                                    <span class="badge bg-light text-muted rounded-pill"><?= $ccat['direct_prod_count'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>

            <a href="all-products.php?discount=1" class="quick-chip <?= $discount == 1 ? 'active' : '' ?>">
                ⚡ Giảm Giá
            </a>
            <a href="all-products.php?sort=hot" class="quick-chip <?= $sort === 'hot' ? 'active' : '' ?>">
                ⭐ Bán Chạy
            </a>
            <a href="all-products.php?type=giay" class="quick-chip <?= $type === 'giay' ? 'active' : '' ?>">
                👟 Giày
            </a>
            <a href="all-products.php?type=dep" class="quick-chip <?= $type === 'dep' ? 'active' : '' ?>">
                🩴 Dép
            </a>
            <a href="all-products.php?gender=Nam" class="quick-chip <?= $gender === 'Nam' ? 'active' : '' ?>">
                👨 Nam
            </a>
            <a href="all-products.php?gender=Nữ" class="quick-chip <?= $gender === 'Nữ' ? 'active' : '' ?>">
                👩 Nữ
            </a>
            <a href="all-products.php?price_range=0-1000000" class="quick-chip <?= $price_range === '0-1000000' ? 'active' : '' ?>">
                🏷️ Dưới 1 Triệu
            </a>
            <a href="all-products.php?price_range=4000000-+" class="quick-chip <?= $price_range === '4000000-+' ? 'active' : '' ?>">
                💎 Trên 4 Triệu
            </a>
        </div>
    </div>
</div>

<!-- 2. MAIN PRODUCTS SECTION -->
<div class="container py-4 py-lg-5">
    <div class="row g-4">
        
        <!-- SIDEBAR FILTERS (DESKTOP) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="filter-sidebar">
                <form id="productFilterForm" action="all-products.php" method="GET" onsubmit="event.preventDefault(); triggerLiveFilter();">
                    <?php if($discount): ?>
                        <input type="hidden" name="discount" value="1">
                    <?php endif; ?>
                    <?php if(!empty($layout_view)): ?>
                        <input type="hidden" name="layout" value="<?= htmlspecialchars($layout_view) ?>">
                    <?php endif; ?>
                    
                    <div class="filter-sidebar-header">
                        <h4 class="filter-sidebar-title"><i class="fa-solid fa-sliders text-warning me-2"></i>Bộ Lọc</h4>
                        <?php if ($active_filters_count > 0): ?>
                            <a href="all-products.php" class="text-danger small fw-bold text-decoration-none">
                                <i class="fa-solid fa-rotate-left me-1"></i>Xóa hết (<?= $active_filters_count ?>)
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Tìm kiếm tên sản phẩm -->
                    <div class="filter-search-wrap">
                        <input type="text" name="keyword" class="form-control filter-search-input" placeholder="Tìm tên sản phẩm, mã..." value="<?= htmlspecialchars($keyword) ?>">
                        <button type="submit" class="filter-search-btn" title="Tìm kiếm">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>

                    <!-- Lọc Thương Hiệu (Brand Chips) -->
                    <div class="mb-4">
                        <div class="filter-section-title">
                            <span><i class="fa-solid fa-crown text-warning me-1"></i> Thương Hiệu</span>
                            <?php if ($brand_id > 0): ?>
                                <a href="<?= build_url(['brand_id' => '']) ?>" class="small text-danger text-decoration-none">Bỏ chọn</a>
                            <?php endif; ?>
                        </div>
                        <div class="brand-filter-grid">
                            <a href="<?= build_url(['brand_id' => '']) ?>" class="brand-chip <?= $brand_id === 0 ? 'active' : '' ?>">Tất cả</a>
                            <?php 
                            $brands->data_seek(0);
                            while($b = $brands->fetch_assoc()): 
                                $is_b_active = ($brand_id == $b['id']);
                            ?>
                                <a href="<?= build_url(['brand_id' => $is_b_active ? '' : $b['id']]) ?>" class="brand-chip <?= $is_b_active ? 'active' : '' ?>">
                                    <?= htmlspecialchars($b['name']) ?>
                                    <span class="chip-count">(<?= $b['product_count'] ?>)</span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Lọc Danh Mục (Categories Phân Cấp Cha - Con Có Mũi Tên) -->
                    <div class="mb-4">
                        <div class="filter-section-title">
                            <span><i class="fa-solid fa-layer-group text-primary me-1"></i> Danh Mục</span>
                            <?php if ($category_id > 0): ?>
                                <a href="<?= build_url(['category_id' => '']) ?>" class="small text-danger text-decoration-none">Bỏ chọn</a>
                            <?php endif; ?>
                        </div>
                        <div class="category-tree-wrapper">
                            <div class="mb-1">
                                <a href="<?= build_url(['category_id' => '']) ?>" class="category-tree-link <?= $category_id === 0 ? 'active' : '' ?>">
                                    <span class="fw-bold"><i class="fa-solid fa-boxes-stacked me-2 text-warning"></i>Tất cả danh mục</span>
                                    <span class="badge bg-light text-dark rounded-pill"><?= $total_rows ?></span>
                                </a>
                            </div>
                            <?php foreach ($parent_cats as $pcat): 
                                $has_children = !empty($pcat['children']);
                                $is_p_active = ($category_id === $pcat['id']);
                                $is_child_active = false;
                                if ($has_children) {
                                    foreach ($pcat['children'] as $chk_c) {
                                        if ($category_id === $chk_c['id']) {
                                            $is_child_active = true;
                                            break;
                                        }
                                    }
                                }
                                $should_expand = ($is_p_active || $is_child_active);
                            ?>
                                <div class="category-tree-group mb-1">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <a href="<?= build_url(['category_id' => $is_p_active ? '' : $pcat['id']]) ?>" class="category-tree-link flex-grow-1 <?= $is_p_active ? 'active' : '' ?>">
                                            <span class="fw-semibold"><i class="fa-solid fa-folder-open text-primary me-2"></i><?= htmlspecialchars($pcat['name']) ?></span>
                                            <span class="badge bg-light text-muted rounded-pill"><?= $pcat['total_prod_count'] ?></span>
                                        </a>
                                        <?php if ($has_children): ?>
                                            <button type="button" class="btn btn-sm cat-arrow-btn p-1 ms-1 text-muted" data-bs-toggle="collapse" data-bs-target="#subcat-tree-<?= $pcat['id'] ?>" aria-expanded="<?= $should_expand ? 'true' : 'false' ?>" title="Mở rộng / thu gọn danh mục con">
                                                <i class="fa-solid fa-chevron-right arrow-icon <?= $should_expand ? 'rotated' : '' ?>"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($has_children): ?>
                                        <div class="collapse <?= $should_expand ? 'show' : '' ?> ps-3 mt-1" id="subcat-tree-<?= $pcat['id'] ?>">
                                            <?php foreach ($pcat['children'] as $ccat): 
                                                $is_c_active = ($category_id === $ccat['id']);
                                            ?>
                                                <div class="my-1">
                                                    <a href="<?= build_url(['category_id' => $is_c_active ? '' : $ccat['id']]) ?>" class="subcat-tree-link <?= $is_c_active ? 'active' : '' ?>">
                                                        <span><i class="fa-solid fa-arrow-turn-down me-1 opacity-50" style="transform: rotate(-90deg);"></i> <?= htmlspecialchars($ccat['name']) ?></span>
                                                        <span class="badge bg-light text-muted rounded-pill"><?= $ccat['direct_prod_count'] ?></span>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Lọc Loại Sản Phẩm & Giới Tính -->
                    <div class="mb-4">
                        <label class="filter-section-title"><span><i class="fa-solid fa-shoe-prints text-info me-1"></i> Phân Loại</span></label>
                        <div class="segmented-btn-group mb-2">
                            <a href="<?= build_url(['type' => '']) ?>" class="segmented-btn <?= empty($type) ? 'active' : '' ?>">Tất cả</a>
                            <a href="<?= build_url(['type' => 'giay']) ?>" class="segmented-btn <?= $type === 'giay' ? 'active' : '' ?>">👟 Giày</a>
                            <a href="<?= build_url(['type' => 'dep']) ?>" class="segmented-btn <?= $type === 'dep' ? 'active' : '' ?>">🩴 Dép</a>
                        </div>
                        <div class="segmented-btn-group">
                            <a href="<?= build_url(['gender' => '']) ?>" class="segmented-btn <?= empty($gender) ? 'active' : '' ?>">Tất cả</a>
                            <a href="<?= build_url(['gender' => 'Nam']) ?>" class="segmented-btn <?= $gender === 'Nam' ? 'active' : '' ?>">👨 Nam</a>
                            <a href="<?= build_url(['gender' => 'Nữ']) ?>" class="segmented-btn <?= $gender === 'Nữ' ? 'active' : '' ?>">👩 Nữ</a>
                            <a href="<?= build_url(['gender' => 'Unisex']) ?>" class="segmented-btn <?= $gender === 'Unisex' ? 'active' : '' ?>">🚻 Unisex</a>
                        </div>
                    </div>

                    <!-- Lọc Khoảng Giá (Price Radio Cards) -->
                    <div class="mb-4">
                        <div class="filter-section-title">
                            <span><i class="fa-solid fa-tag text-success me-1"></i> Mức Giá</span>
                            <?php if (!empty($price_range)): ?>
                                <a href="<?= build_url(['price_range' => '']) ?>" class="small text-danger text-decoration-none">Bỏ chọn</a>
                            <?php endif; ?>
                        </div>
                        <?php
                        $price_options = [
                            '' => ['label' => 'Tất cả mức giá', 'icon' => 'fa-coins'],
                            '0-1000000' => ['label' => 'Dưới 1.000.000đ', 'icon' => 'fa-wallet'],
                            '1000000-2000000' => ['label' => '1.000.000đ - 2.000.000đ', 'icon' => 'fa-money-bill-wave'],
                            '2000000-4000000' => ['label' => '2.000.000đ - 4.000.000đ', 'icon' => 'fa-gem'],
                            '4000000-+' => ['label' => 'Trên 4.000.000đ', 'icon' => 'fa-crown']
                        ];
                        foreach($price_options as $val => $opt):
                        ?>
                        <label class="price-radio-card">
                            <input type="radio" name="price_range" value="<?= $val ?>" <?= $price_range === $val ? 'checked' : '' ?> onchange="triggerLiveFilter()">
                            <div class="price-radio-box">
                                <span><i class="fa-solid <?= $opt['icon'] ?> me-2 opacity-50"></i><?= $opt['label'] ?></span>
                                <i class="fa-solid fa-check <?= $price_range === $val ? 'text-warning' : 'opacity-0' ?>"></i>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tùy Chọn Thêm (Còn hàng / Giảm giá) -->
                    <div class="mb-4 pt-2 border-top">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="chkInStock" <?= $in_stock ? 'checked' : '' ?> onchange="triggerLiveFilter()">
                            <label class="form-check-label fw-semibold small" for="chkInStock">Chỉ hiện sản phẩm còn hàng</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="discount" value="1" id="chkDiscountOnly" <?= $discount ? 'checked' : '' ?> onchange="triggerLiveFilter()">
                            <label class="form-check-label fw-semibold small text-danger" for="chkDiscountOnly">🔥 Chỉ hiện sản phẩm giảm giá</label>
                        </div>
                    </div>

                    <a href="all-products.php" class="btn btn-outline-dark w-100 rounded-pill fw-bold">
                        <i class="fa-solid fa-rotate-left me-1"></i> Đặt Lại Bộ Lọc
                    </a>
                </form>
            </div>
        </div>

        <!-- MAIN PRODUCTS CONTENT AREA -->
        <div class="col-12 col-lg-9">
            
            <!-- TOOLBAR TOP CONTROLS -->
            <div id="productsToolbar" class="products-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Mobile Filter Button Trigger -->
                        <button class="btn btn-dark d-lg-none rounded-pill px-3 py-2 fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileFilterOffcanvas">
                            <i class="fa-solid fa-sliders me-1"></i> Bộ Lọc <?= $active_filters_count > 0 ? "($active_filters_count)" : '' ?>
                        </button>
                        <span class="text-muted fw-semibold small d-none d-sm-inline">
                            Hiển thị <strong><?= $products_result->num_rows ?></strong> / <?= number_format($total_rows) ?> sản phẩm
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Layout Switcher (Grid 3 / Grid 4 / List) -->
                        <div class="btn-group layout-btn-group d-none d-md-inline-flex" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm <?= $layout_view === 'grid3' ? 'active' : '' ?>" title="Lưới 3 cột" onclick="switchLayout('grid3')">
                                <i class="fa-solid fa-table-cells"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm <?= $layout_view === 'grid4' ? 'active' : '' ?>" title="Lưới 4 cột" onclick="switchLayout('grid4')">
                                <i class="fa-solid fa-table-cells-large"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm <?= $layout_view === 'list' ? 'active' : '' ?>" title="Danh sách" onclick="switchLayout('list')">
                                <i class="fa-solid fa-list"></i>
                            </button>
                        </div>

                        <!-- Sorting Dropdown -->
                        <div class="d-flex align-items-center">
                            <label class="text-nowrap fw-bold small me-2 text-muted d-none d-sm-inline">Sắp xếp:</label>
                            <select class="form-select form-select-sm rounded-pill fw-semibold border-secondary border-opacity-25" style="min-width: 170px;" onchange="changeProductSort(this.value)">
                                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>✨ Gợi ý & Mới nhất</option>
                                <option value="hot" <?= $sort == 'hot' ? 'selected' : '' ?>>🔥 Bán chạy nhất</option>
                                <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>⭐ Đánh giá cao nhất</option>
                                <option value="view" <?= $sort == 'view' ? 'selected' : '' ?>>👁️ Xem nhiều nhất</option>
                                <option value="discount_desc" <?= $sort == 'discount_desc' ? 'selected' : '' ?>>⚡ Giảm giá nhiều nhất</option>
                                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>💵 Giá: Thấp đến Cao</option>
                                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>💎 Giá: Cao đến Thấp</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Active Filter Tags Bar -->
                <?php if ($active_filters_count > 0): ?>
                <div class="active-filters-bar">
                    <span class="small fw-bold text-muted me-1"><i class="fa-solid fa-filter me-1"></i>Đang lọc:</span>
                    <?php foreach($active_filter_chips as $chip): ?>
                        <a href="<?= build_url([$chip['param'] => '']) ?>" class="active-filter-tag" title="Bỏ lọc">
                            <?= htmlspecialchars($chip['label']) ?>
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endforeach; ?>
                    <a href="all-products.php" class="text-danger small fw-bold text-decoration-none ms-auto">
                        <i class="fa-solid fa-trash-can me-1"></i>Xóa tất cả
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- PRODUCT GRID / LIST CONTAINER -->
            <?php
            $col_class = 'col-12 col-sm-6 col-md-6 col-lg-4';
            if ($layout_view === 'grid4') {
                $col_class = 'col-12 col-sm-6 col-md-4 col-xl-3';
            } elseif ($layout_view === 'list') {
                $col_class = 'col-12';
            }
            ?>
            <div id="productsContainer" class="products-container <?= $layout_view === 'list' ? 'view-list' : '' ?>">
                <div class="row g-3 g-md-4">
                    <?php if ($products_result->num_rows > 0): ?>
                        <?php 
                        $card_index = 0;
                        while($p = $products_result->fetch_assoc()): 
                            $card_index++;
                            $p_id_int = intval($p['id']);
                            $p['is_user_purchased'] = in_array($p_id_int, $user_purchased_ids);
                            $p['is_user_viewed'] = in_array($p_id_int, $user_viewed_ids);
                            $p['is_similar_recommended'] = (intval($p['user_interest_score'] ?? 0) >= 200 && !$p['is_user_purchased'] && !$p['is_user_viewed']);
                            $p['is_user_searched'] = (intval($p['user_interest_score'] ?? 0) >= 150 && !$p['is_user_purchased'] && !$p['is_user_viewed'] && empty($p['is_similar_recommended']));
                        ?>
                            <div class="<?= $col_class ?> product-grid-col" style="animation-delay: <?= min($card_index * 0.04, 0.6) ?>s;">
                                <?php include 'includes/product-card.php'; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <div class="py-5 px-3 bg-white rounded-4 shadow-sm border">
                                <div class="mb-3 text-warning">
                                    <i class="fa-solid fa-shoe-prints fa-4x opacity-50"></i>
                                </div>
                                <h3 class="fw-bold text-dark mb-2">Không tìm thấy sản phẩm phù hợp</h3>
                                <p class="text-muted mb-4 max-w-500 mx-auto">
                                    Không có sản phẩm nào khớp với tiêu chí tìm kiếm hoặc bộ lọc hiện tại của bạn.
                                </p>
                                <a href="all-products.php" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Xem Tất Cả Sản Phẩm
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PAGINATION -->
            <div id="paginationWrapper">
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-5">
                        <ul class="pagination justify-content-center flex-wrap gap-1">
                            <?php if($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" href="<?= build_url(['page' => $page - 1]) ?>" aria-label="Trang trước">
                                        <i class="fa-solid fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_p = max(1, $page - 2);
                            $end_p   = min($total_pages, $page + 2);
                            if ($start_p > 1) {
                                echo '<li class="page-item"><a class="page-link shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" href="'.build_url(['page' => 1]).'">1</a></li>';
                                if ($start_p > 2) echo '<li class="page-item disabled"><span class="page-link border-0">...</span></li>';
                            }
                            for($i = $start_p; $i <= $end_p; $i++): 
                            ?>
                                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                    <a class="page-link shadow-sm rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;" href="<?= build_url(['page' => $i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; 
                            if ($end_p < $total_pages) {
                                if ($end_p < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link border-0">...</span></li>';
                                echo '<li class="page-item"><a class="page-link shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" href="'.build_url(['page' => $total_pages]).'">'.$total_pages.'</a></li>';
                            }
                            ?>
                            
                            <?php if($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" href="<?= build_url(['page' => $page + 1]) ?>" aria-label="Trang kế tiếp">
                                        <i class="fa-solid fa-angle-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- 3. OFFCANVAS BỘ LỌC CHO GIAO DIỆN MOBILE -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileFilterOffcanvas" aria-labelledby="mobileFilterLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="mobileFilterLabel"><i class="fa-solid fa-sliders text-warning me-2"></i>Bộ Lọc Sản Phẩm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <!-- Render filter form for mobile -->
        <form id="mobileProductFilterForm" action="all-products.php" method="GET" onsubmit="event.preventDefault(); triggerMobileFilter();">
            <?php if($discount): ?><input type="hidden" name="discount" value="1"><?php endif; ?>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Tìm Kiếm</label>
                <input type="text" name="keyword" class="form-control" placeholder="Tên sản phẩm..." value="<?= htmlspecialchars($keyword) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Thương Hiệu</label>
                <select name="brand_id" class="form-select">
                    <option value="">Tất cả thương hiệu</option>
                    <?php 
                    $brands->data_seek(0);
                    while($b = $brands->fetch_assoc()): 
                    ?>
                        <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?> (<?= $b['product_count'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Danh Mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($parent_cats as $pcat): ?>
                        <option value="<?= $pcat['id'] ?>" <?= $category_id == $pcat['id'] ? 'selected' : '' ?>>
                            📂 <?= htmlspecialchars($pcat['name']) ?> (<?= $pcat['total_prod_count'] ?>)
                        </option>
                        <?php foreach ($pcat['children'] as $ccat): ?>
                            <option value="<?= $ccat['id'] ?>" <?= $category_id == $ccat['id'] ? 'selected' : '' ?>>
                                &nbsp;&nbsp;↳ <?= htmlspecialchars($ccat['name']) ?> (<?= $ccat['direct_prod_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Mức Giá</label>
                <select name="price_range" class="form-select">
                    <option value="">Tất cả mức giá</option>
                    <option value="0-1000000" <?= $price_range === '0-1000000' ? 'selected' : '' ?>>Dưới 1 triệu</option>
                    <option value="1000000-2000000" <?= $price_range === '1000000-2000000' ? 'selected' : '' ?>>1 - 2 triệu</option>
                    <option value="2000000-4000000" <?= $price_range === '2000000-4000000' ? 'selected' : '' ?>>2 - 4 triệu</option>
                    <option value="4000000-+" <?= $price_range === '4000000-+' ? 'selected' : '' ?>>Trên 4 triệu</option>
                </select>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-dark fw-bold rounded-pill py-2">Áp Dụng Bộ Lọc</button>
                <a href="all-products.php" class="btn btn-outline-secondary rounded-pill py-2">Xóa Bộ Lọc</a>
            </div>
        </form>
    </div>
</div>

<!-- 4. QUICK VIEW PRODUCT MODAL (XEM NHANH SẢN PHẨM) -->
<div class="modal fade modal-quickview" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0" id="quickViewContent">
                <!-- Nội dung được load qua AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. FLOATING TOAST NOTIFICATION -->
<div id="floatingToast" class="custom-toast">
    <i id="toastIcon" class="fa-solid fa-circle-check text-warning fa-lg"></i>
    <span id="toastMessage">Thông báo</span>
</div>

<script>
// ==========================================
// ALL PRODUCTS PAGE INTERACTIVE JS SCRIPTS (AJAX LIVE FILTER)
// ==========================================

// 1. Toast Notification Handler
function showToast(message, iconClass = 'fa-solid fa-circle-check text-warning') {
    const toast = document.getElementById('floatingToast');
    const msgEl = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    if (!toast || !msgEl || !iconEl) return;

    msgEl.textContent = message;
    iconEl.className = iconClass + ' fa-lg';
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3200);
}

// 2. Wishlist & Card Events Binding
function initProductCardEvents() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        // Tránh gán đúp sự kiện
        if (btn.dataset.bound) return;
        btn.dataset.bound = 'true';

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const pid = this.dataset.id;
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                showToast('Vui lòng đăng nhập để lưu sản phẩm yêu thích!', 'fa-solid fa-lock text-danger');
                setTimeout(() => { window.location.href = 'login.php'; }, 1200);
                return;
            <?php endif; ?>
            
            fetch('api/wishlist-toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + pid
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'added') {
                    this.classList.add('active');
                    this.innerHTML = '<i class="fa-solid fa-heart"></i>';
                    showToast('Đã thêm sản phẩm vào danh sách Yêu thích! ❤️', 'fa-solid fa-heart text-danger');
                } else if(data.status === 'removed') {
                    this.classList.remove('active');
                    this.innerHTML = '<i class="fa-regular fa-heart"></i>';
                    showToast('Đã bỏ sản phẩm khỏi danh sách Yêu thích.');
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'fa-solid fa-triangle-exclamation text-danger');
                }
            })
            .catch(err => console.error(err));
        });
    });
}

// 3. CORE: BỘ MÁY LỌC AJAX TỨC THÌ (KHÔNG LOAD LẠI TRANG, GIỮ NGUYÊN VỊ TRÍ)
let isAjaxFiltering = false;

window.loadFilteredProducts = function(targetUrl, isPopState = false) {
    if (isAjaxFiltering) return;
    isAjaxFiltering = true;

    const container = document.getElementById('productsContainer');
    if (container) {
        container.classList.add('products-loading-state');
    }

    fetch(targetUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Lưu trạng thái con trỏ và focus của ô tìm kiếm nếu người dùng đang gõ
        const activeEl = document.activeElement;
        const isDesktopInputActive = activeEl && activeEl.name === 'keyword' && activeEl.closest('#productFilterForm');
        const isMobileInputActive  = activeEl && activeEl.name === 'keyword' && activeEl.closest('#mobileProductFilterForm');
        const cursorPos = (activeEl && activeEl.selectionStart !== undefined) ? activeEl.selectionStart : null;
        const currentVal = activeEl ? activeEl.value : '';

        // 1. Cập nhật Thanh lựa chọn nhanh trên cùng (Quick Chips)
        const newQuick = doc.getElementById('quickFilterWrapper');
        const oldQuick = document.getElementById('quickFilterWrapper');
        if (newQuick && oldQuick) {
            oldQuick.innerHTML = newQuick.innerHTML;
        }

        // 2. Cập nhật Sidebar bộ lọc (Desktop)
        const newSidebar = doc.getElementById('productFilterForm');
        const oldSidebar = document.getElementById('productFilterForm');
        if (newSidebar && oldSidebar) {
            oldSidebar.innerHTML = newSidebar.innerHTML;
            bindSearchInputDebounce(oldSidebar);
            if (isDesktopInputActive) {
                const restoredInput = oldSidebar.querySelector('input[name="keyword"]');
                if (restoredInput) {
                    restoredInput.value = currentVal;
                    restoredInput.focus();
                    if (cursorPos !== null) {
                        restoredInput.setSelectionRange(cursorPos, cursorPos);
                    }
                }
            }
        }

        // 3. Cập nhật Thanh công cụ & Thẻ đang lọc (Toolbar)
        const newToolbar = doc.getElementById('productsToolbar');
        const oldToolbar = document.getElementById('productsToolbar');
        if (newToolbar && oldToolbar) {
            oldToolbar.innerHTML = newToolbar.innerHTML;
        }

        // 4. Cập nhật Danh sách sản phẩm (Product Grid / List)
        const newGrid = doc.getElementById('productsContainer');
        const oldGrid = document.getElementById('productsContainer');
        if (newGrid && oldGrid) {
            oldGrid.className = newGrid.className;
            oldGrid.innerHTML = newGrid.innerHTML;
        }

        // 5. Cập nhật Phân trang (Pagination)
        const newPag = doc.getElementById('paginationWrapper');
        const oldPag = document.getElementById('paginationWrapper');
        if (newPag && oldPag) {
            oldPag.innerHTML = newPag.innerHTML;
        }

        // 6. Cập nhật Bộ lọc Mobile (Offcanvas)
        const newMobile = doc.querySelector('#mobileFilterOffcanvas .offcanvas-body');
        const oldMobile = document.querySelector('#mobileFilterOffcanvas .offcanvas-body');
        if (newMobile && oldMobile) {
            oldMobile.innerHTML = newMobile.innerHTML;
            const mobileForm = oldMobile.querySelector('#mobileProductFilterForm');
            if (mobileForm) bindSearchInputDebounce(mobileForm);
            if (isMobileInputActive) {
                const restoredMobileInput = oldMobile.querySelector('input[name="keyword"]');
                if (restoredMobileInput) {
                    restoredMobileInput.value = currentVal;
                    restoredMobileInput.focus();
                    if (cursorPos !== null) {
                        restoredMobileInput.setSelectionRange(cursorPos, cursorPos);
                    }
                }
            }
        }

        // 7. Cập nhật URL trình duyệt (Giữ nguyên trang, không refresh)
        if (!isPopState) {
            window.history.pushState(null, '', targetUrl);
        }

        // Re-bind các sự kiện
        initProductCardEvents();
    })
    .catch(err => {
        console.error('Lỗi lọc AJAX:', err);
        window.location.href = targetUrl;
    })
    .finally(() => {
        isAjaxFiltering = false;
        if (container) {
            container.classList.remove('products-loading-state');
        }
    });
};

// 4. Trigger Live Filter từ Desktop Sidebar
window.triggerLiveFilter = function() {
    const form = document.getElementById('productFilterForm');
    if (!form) return;
    const formData = new FormData(form);
    const params = new URLSearchParams();

    // Giữ các tham số hiện tại như sort và layout
    const currentUrl = new URL(window.location.href);
    if (currentUrl.searchParams.get('sort')) {
        params.set('sort', currentUrl.searchParams.get('sort'));
    }
    if (currentUrl.searchParams.get('layout')) {
        params.set('layout', currentUrl.searchParams.get('layout'));
    }

    for (const [key, value] of formData.entries()) {
        if (value !== '' && value !== null && key !== 'layout' && key !== 'sort') {
            params.set(key, value);
        }
    }
    params.delete('page'); // Reset về trang 1
    const newUrl = 'all-products.php' + (params.toString() ? '?' + params.toString() : '');
    loadFilteredProducts(newUrl);
};

// 5. Trigger Live Filter từ Mobile Offcanvas
window.triggerMobileFilter = function() {
    const form = document.getElementById('mobileProductFilterForm');
    if (!form) return;
    const formData = new FormData(form);
    const params = new URLSearchParams();

    const currentUrl = new URL(window.location.href);
    if (currentUrl.searchParams.get('sort')) {
        params.set('sort', currentUrl.searchParams.get('sort'));
    }
    if (currentUrl.searchParams.get('layout')) {
        params.set('layout', currentUrl.searchParams.get('layout'));
    }

    for (const [key, value] of formData.entries()) {
        if (value !== '' && value !== null) {
            params.set(key, value);
        }
    }
    params.delete('page');

    // Đóng Offcanvas mobile
    const offcanvasEl = document.getElementById('mobileFilterOffcanvas');
    if (offcanvasEl) {
        const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (bsOffcanvas) bsOffcanvas.hide();
    }

    const newUrl = 'all-products.php' + (params.toString() ? '?' + params.toString() : '');
    loadFilteredProducts(newUrl);
};

// 6. Sorting Handler (AJAX)
function changeProductSort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page');
    loadFilteredProducts(url.toString());
}

// 7. Layout Switcher (Grid 3, Grid 4, List - AJAX)
function switchLayout(layoutName) {
    const url = new URL(window.location.href);
    url.searchParams.set('layout', layoutName);
    loadFilteredProducts(url.toString());
}

// 8. Quick View Modal Opener via AJAX
function openQuickView(productId) {
    const modalEl = document.getElementById('quickViewModal');
    const contentEl = document.getElementById('quickViewContent');
    const bsModal = new bootstrap.Modal(modalEl);
    
    contentEl.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="text-muted mt-2 small">Đang tải thông tin sản phẩm...</p>
        </div>
    `;
    bsModal.show();

    fetch('api/quick-view.php?id=' + productId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const p = data.product;
                const formattedPrice = new Intl.NumberFormat('vi-VN').format(p.price) + 'đ';
                const formattedOldPrice = p.old_price > p.price ? (new Intl.NumberFormat('vi-VN').format(p.old_price) + 'đ') : '';
                
                let sizesHtml = '';
                let firstVariantId = 0;
                if (p.variants && p.variants.length > 0) {
                    p.variants.forEach((v, idx) => {
                        const disabled = v.stock <= 0;
                        if (idx === 0 && !disabled) firstVariantId = v.id;
                        sizesHtml += `
                            <button type="button" class="size-select-btn ${disabled ? 'disabled' : ''} ${idx === 0 && !disabled ? 'active' : ''}" 
                                    data-variant-id="${v.id}" data-stock="${v.stock}" 
                                    ${disabled ? 'disabled' : ''} onclick="selectQuickViewSize(this)">
                                ${v.size}
                            </button>
                        `;
                    });
                }

                let galleryHtml = '';
                if (p.gallery && p.gallery.length > 1) {
                    p.gallery.forEach((img, idx) => {
                        galleryHtml += `
                            <img src="${img}" alt="" class="quickview-thumb ${idx === 0 ? 'active' : ''}" onclick="changeQuickViewMainImg('${img}', this)">
                        `;
                    });
                }

                contentEl.innerHTML = `
                    <div class="row g-4 align-items-center">
                        <div class="col-md-6 text-center">
                            <img src="${p.main_image}" id="qvMainImage" alt="${p.name}" class="quickview-img-main shadow-sm mb-3">
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                ${galleryHtml}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span class="badge bg-light text-dark fw-bold px-3 py-1 mb-2 border text-uppercase">${p.brand_name || 'Chính hãng'}</span>
                            <h3 class="fw-bold text-dark mb-2">${p.name}</h3>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="text-warning small">
                                    <i class="fa-solid fa-star"></i>
                                    <span class="fw-bold text-dark ms-1">${p.avg_rating}</span>
                                </div>
                                <span class="text-muted small">• ${p.review_count} đánh giá</span>
                                <span class="text-muted small">• Đã bán ${p.sold_count || 0}</span>
                            </div>
                            
                            <div class="d-flex align-items-baseline gap-2 mb-3">
                                <span class="fs-3 fw-black text-danger">${formattedPrice}</span>
                                ${formattedOldPrice ? `<span class="text-decoration-line-through text-muted">${formattedOldPrice}</span>` : ''}
                                ${p.discount_percent > 0 ? `<span class="badge bg-danger">-${p.discount_percent}%</span>` : ''}
                            </div>

                            <p class="text-muted small mb-3">${p.description}</p>

                            <div class="mb-4">
                                <label class="fw-bold small text-uppercase mb-2 d-block">Chọn Size Giày:</label>
                                <div class="d-flex flex-wrap gap-2" id="qvSizesContainer">
                                    ${sizesHtml || '<span class="text-muted small">Liên hệ để chọn size</span>'}
                                </div>
                            </div>

                            <form action="cart-process.php" method="POST" class="d-flex gap-2">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="${p.id}">
                                <input type="hidden" name="variant_id" id="qvVariantInput" value="${firstVariantId}">
                                <input type="hidden" name="quantity" value="1">
                                
                                <button type="submit" class="btn btn-dark fw-bold rounded-pill px-4 py-2 flex-grow-1 shadow" ${p.total_stock <= 0 ? 'disabled' : ''}>
                                    <i class="fa-solid fa-cart-plus me-1"></i> ${p.total_stock <= 0 ? 'Tạm Hết Hàng' : 'Thêm Vào Giỏ Hàng'}
                                </button>
                                <a href="product-detail.php?id=${p.id}" class="btn btn-outline-secondary rounded-pill px-3 py-2" title="Xem trang chi tiết đầy đủ">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </form>
                        </div>
                    </div>
                `;
            } else {
                contentEl.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
            }
        })
        .catch(err => {
            contentEl.innerHTML = `<div class="alert alert-danger">Không thể tải thông tin sản phẩm. Vui lòng thử lại sau!</div>`;
        });
}

function selectQuickViewSize(btn) {
    document.querySelectorAll('#qvSizesContainer .size-select-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('qvVariantInput').value = btn.dataset.variantId;
}

function changeQuickViewMainImg(imgUrl, thumb) {
    document.getElementById('qvMainImage').src = imgUrl;
    document.querySelectorAll('.quickview-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// 9. Debounce tìm kiếm theo từ khóa
function bindSearchInputDebounce(containerEl) {
    const input = containerEl.querySelector('input[name="keyword"]');
    if (!input) return;
    let debounceTimer;
    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            triggerLiveFilter();
        }, 450);
    });
}

// 10. Lắng nghe toàn cục: Chặn F5 reload trang cho toàn bộ liên kết lọc & phân trang
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (!link) return;

    // Bỏ qua nếu là link sản phẩm, wishlist, giỏ hàng, modal, hoặc link ngoài
    if (link.closest('.product-card') || link.classList.contains('wishlist-btn') || link.hasAttribute('data-bs-toggle') || link.getAttribute('target') === '_blank') {
        return;
    }

    // Kiểm tra xem link có thuộc các khu vực bộ lọc hay phân trang không
    const isFilterLink = link.closest('#quickFilterWrapper') ||
                         link.closest('#productFilterForm') ||
                         link.closest('#productsToolbar') ||
                         link.closest('#paginationWrapper') ||
                         link.closest('#mobileFilterOffcanvas');

    if (isFilterLink) {
        const href = link.getAttribute('href');
        if (href && (href.startsWith('all-products.php') || href.startsWith('?') || href === 'all-products.php')) {
            e.preventDefault();
            loadFilteredProducts(href);
        }
    }
});

// 11. Hỗ trợ nút Back / Forward trên trình duyệt
window.addEventListener('popstate', function() {
    loadFilteredProducts(window.location.href, true);
});

// Khởi tạo ban đầu
document.addEventListener("DOMContentLoaded", function() {
    initProductCardEvents();
    const filterForm = document.getElementById('productFilterForm');
    if (filterForm) {
        bindSearchInputDebounce(filterForm);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>