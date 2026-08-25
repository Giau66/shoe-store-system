<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function stripVN($str) {
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ'
    ];
    $str = mb_strtolower($str, 'UTF-8');
    foreach ($unicode as $non => $pattern) {
        $str = preg_replace("/($pattern)/iu", $non, $str);
    }
    return trim($str);
}

$raw_q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($raw_q)) {
    echo json_encode([
        'query' => '',
        'brand' => null,
        'suggestions' => [],
        'products' => [],
        'total_count' => 0
    ]);
    exit;
}

$q_clean = $conn->real_escape_string($raw_q);
$q_norm  = stripVN($raw_q);

// 1. TỪ ĐIỂN TỪ ĐỒNG NGHĨA & DANH MỤC THÔNG MINH (SYNONYMS DICTIONARY)
$synonyms = [
    'giay' => ['air', 'samba', 'dunk', 'jordan', 'chuck', 'pegasus', 'superstar', '550', '530', '1906', 'sneaker'],
    'giay nam' => ['nam', 'air force', 'dunk', 'samba', 'jordan', '550', '530'],
    'giay nu' => ['nu', 'air force', 'dunk', 'samba', '530', 'chuck'],
    'sneaker' => ['air', 'samba', 'dunk', 'jordan', 'chuck', '550', '530'],
    'dep' => ['slide', 'sandal', 'calm', 'adilette', 'hydro', 'dép'],
    'sandal' => ['slide', 'sandal', 'calm', 'adilette', 'hydro'],
    'chay bo' => ['pegasus', 'ultraboost', '530', '1906', 'running'],
    'running' => ['pegasus', 'ultraboost', '530', '1906'],
    'bong ro' => ['jordan', 'dunk', 'force', 'chicago'],
    'trang' => ['white', 'panda', 'stan smith', '550', 'parchment'],
    'den' => ['black', 'panda', 'superstar', 'military', 'suede'],
    'nike' => ['nike', 'air force', 'dunk', 'pegasus', 'calm slide', 'air max'],
    'adidas' => ['adidas', 'samba', 'ultraboost', 'stan smith', 'superstar', 'adilette'],
    'jordan' => ['jordan', 'travis scott', 'chicago', 'retro', 'hydro'],
    'new balance' => ['new balance', '530', '550', '2002r', '990v5', '1906r'],
    'nb' => ['new balance', '530', '550', '2002r', '990v5', '1906r'],
    'converse' => ['converse', '1970s', 'chuck', 'run star', 'one star']
];

$matched_keywords = [$q_clean];
foreach ($synonyms as $synKey => $expandList) {
    if (stripos($q_norm, $synKey) !== false || stripos($synKey, $q_norm) !== false) {
        $matched_keywords = array_merge($matched_keywords, $expandList);
    }
}
$matched_keywords = array_unique($matched_keywords);

// 2. TÌM KIẾM THƯƠNG HIỆU PHÙ HỢP (BRAND PORTAL)
$brand_data = null;
$brand_res = $conn->query("
    SELECT id, name 
    FROM brands 
    WHERE name LIKE '%$q_clean%' OR '$q_clean' LIKE CONCAT('%', name, '%') OR '$q_norm' LIKE CONCAT('%', LOWER(name), '%')
    LIMIT 1
");
if ($brand_res && ($b = $brand_res->fetch_assoc())) {
    $brand_data = [
        'id' => $b['id'],
        'name' => $b['name'],
        'url' => "all-products.php?brand=" . $b['id']
    ];
}

// 3. TẠO CÁC TỪ KHÓA GỢI Ý THÔNG MINH (CÓ PHẢI BẠN MUỐN TÌM)
$suggestions = [];

// Gợi ý theo thương hiệu nếu có
if ($brand_data) {
    $bName = $brand_data['name'];
    $suggestions[] = ['text' => "Giày $bName Nam Chính Hãng", 'url' => "all-products.php?keyword=" . urlencode("Giày $bName Nam")];
    $suggestions[] = ['text' => "Giày $bName Sneaker Bán Chạy", 'url' => "all-products.php?keyword=" . urlencode("Giày $bName Sneaker")];
    $suggestions[] = ['text' => "Dép $bName Êm Chân", 'url' => "all-products.php?keyword=" . urlencode("Dép $bName")];
}

// Gợi ý theo danh mục
$cat_res = $conn->query("SELECT id, name FROM categories WHERE name LIKE '%$q_clean%' OR name LIKE '%$q_norm%' LIMIT 3");
if ($cat_res) {
    while ($c = $cat_res->fetch_assoc()) {
        $suggestions[] = [
            'text' => $c['name'],
            'url'  => 'all-products.php?category=' . $c['id']
        ];
    }
}

// Gợi ý từ khóa hot mặc định nếu chưa đủ
if (count($suggestions) < 3) {
    $hot_defaults = [
        'Giày Nike Air Force 1 All White',
        'Giày Adidas Samba OG Cổ Điển',
        'Giày Jordan 1 Retro Chicago',
        'Giày New Balance 530 Hot Trend',
        'Giày Converse 1970s High Top'
    ];
    foreach ($hot_defaults as $hd) {
        if (count($suggestions) < 4) {
            $suggestions[] = ['text' => $hd, 'url' => "all-products.php?keyword=" . urlencode($hd)];
        }
    }
}

// 4. TRUY VẤN SẢN PHẨM KHỚP / GẦN TRÙNG TÊN
$where_parts = [];
foreach ($matched_keywords as $kw) {
    $kw_esc = $conn->real_escape_string($kw);
    if (mb_strlen($kw_esc) >= 2) {
        $where_parts[] = "p.name LIKE '%$kw_esc%'";
        $where_parts[] = "b.name LIKE '%$kw_esc%'";
        $where_parts[] = "c.name LIKE '%$kw_esc%'";
        $where_parts[] = "p.sku LIKE '%$kw_esc%'";
    }
}
$where_condition = !empty($where_parts) ? implode(' OR ', $where_parts) : "p.name LIKE '%$q_clean%'";

$sql_products = "
    SELECT 
        p.id, p.name, p.sku, p.main_image, p.price, p.old_price, p.discount_percent, p.slug,
        b.name AS brand_name,
        c.name AS category_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND ($where_condition)
    ORDER BY 
        CASE 
            WHEN p.name LIKE '$q_clean%' THEN 1
            WHEN p.name LIKE '%$q_clean%' THEN 2
            WHEN b.name LIKE '%$q_clean%' THEN 3
            ELSE 4
        END,
        p.sold_count DESC, p.id DESC
    LIMIT 6
";

$prod_res = $conn->query($sql_products);
$products = [];

$promo_tags = [
    '🔥 Giá siêu tốt',
    '🎁 Tặng tất cao cấp',
    '⚡ Freeship toàn quốc',
    '✨ Bán chạy nhất 2026',
    '👑 Sneaker Hot Trend'
];

$tag_idx = 0;
if ($prod_res && $prod_res->num_rows > 0) {
    while ($p = $prod_res->fetch_assoc()) {
        $current_price = floatval($p['price']);
        $old_price = floatval($p['old_price'] ?? 0);
        $discount_pct = intval($p['discount_percent'] ?? 0);

        if ($old_price <= 0 && $discount_pct > 0) {
            $old_price = round($current_price / (1 - ($discount_pct / 100)));
        } elseif ($old_price > $current_price && $discount_pct <= 0) {
            $discount_pct = round((($old_price - $current_price) / $old_price) * 100);
        }

        $tag = $promo_tags[$tag_idx % count($promo_tags)];
        $tag_idx++;

        $products[] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'sku' => $p['sku'],
            'main_image' => $p['main_image'],
            'price' => $current_price,
            'old_price' => $old_price,
            'discount_percent' => $discount_pct,
            'formatted_price' => number_format($current_price, 0, ',', '.') . '₫',
            'formatted_old_price' => ($old_price > $current_price) ? number_format($old_price, 0, ',', '.') . '₫' : '',
            'brand_name' => $p['brand_name'] ?? '',
            'category_name' => $p['category_name'] ?? '',
            'promo_tag' => $tag,
            'url' => 'product-detail.php?id=' . $p['id']
        ];
    }
}

// 5. NẾU KHÔNG CÓ KẾT QUẢ TRÙNG NÀO (VÍ DỤ TỪ KHÓA LẠ) -> LẤY TOP SẢN PHẨM BÁN CHẠY NHẤT LÀM GỢI Ý
if (empty($products)) {
    $fallback_res = $conn->query("
        SELECT p.id, p.name, p.sku, p.main_image, p.price, p.old_price, p.discount_percent, p.slug, b.name as brand_name, c.name as category_name 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 1 
        ORDER BY p.sold_count DESC, p.view_count DESC 
        LIMIT 5
    ");
    if ($fallback_res) {
        while ($p = $fallback_res->fetch_assoc()) {
            $current_price = floatval($p['price']);
            $old_price = floatval($p['old_price'] ?? 0);
            $discount_pct = intval($p['discount_percent'] ?? 0);
            if ($old_price <= 0 && $discount_pct > 0) {
                $old_price = round($current_price / (1 - ($discount_pct / 100)));
            }

            $products[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'sku' => $p['sku'],
                'main_image' => $p['main_image'],
                'price' => $current_price,
                'old_price' => $old_price,
                'discount_percent' => $discount_pct,
                'formatted_price' => number_format($current_price, 0, ',', '.') . '₫',
                'formatted_old_price' => ($old_price > $current_price) ? number_format($old_price, 0, ',', '.') . '₫' : '',
                'brand_name' => $p['brand_name'] ?? '',
                'category_name' => $p['category_name'] ?? '',
                'promo_tag' => '✨ Gợi ý bán chạy nhất',
                'url' => 'product-detail.php?id=' . $p['id']
            ];
        }
    }
}

$total_count = count($products);

echo json_encode([
    'query' => $raw_q,
    'brand' => $brand_data,
    'suggestions' => array_slice($suggestions, 0, 4),
    'products' => $products,
    'total_count' => $total_count
], JSON_UNESCAPED_UNICODE);
exit;