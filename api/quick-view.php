<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID sản phẩm không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("
    SELECT p.*, b.name AS brand_name, c.name AS category_name,
           COALESCE(AVG(cm.rating), 5.0) AS avg_rating,
           COUNT(cm.id) AS review_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN brands b ON p.brand_id = b.id
    LEFT JOIN comments cm ON cm.product_id = p.id AND cm.status = 1
    WHERE p.id = ? AND p.status = 1
    GROUP BY p.id
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy sản phẩm hoặc sản phẩm đã ngừng kinh doanh']);
    exit;
}

// Lấy danh sách biến thể (size, color, stock)
$variants_res = $conn->query("SELECT id, size, color, stock_quantity FROM product_variants WHERE product_id = $id ORDER BY size ASC");
$variants = [];
$total_stock = 0;
if ($variants_res) {
    while ($v = $variants_res->fetch_assoc()) {
        $variants[] = [
            'id' => intval($v['id']),
            'size' => $v['size'],
            'color' => $v['color'],
            'stock' => intval($v['stock_quantity'])
        ];
        $total_stock += intval($v['stock_quantity']);
    }
}

// Lấy album ảnh phụ
$images_res = $conn->query("SELECT image_url FROM product_images WHERE product_id = $id ORDER BY sort_order ASC");
$gallery = [$product['main_image']];
if ($images_res) {
    while ($img = $images_res->fetch_assoc()) {
        if (!in_array($img['image_url'], $gallery)) {
            $gallery[] = $img['image_url'];
        }
    }
}

echo json_encode([
    'status' => 'success',
    'product' => [
        'id' => intval($product['id']),
        'name' => $product['name'],
        'slug' => $product['slug'],
        'sku' => $product['sku'],
        'price' => floatval($product['price']),
        'old_price' => floatval($product['old_price'] ?? 0),
        'discount_percent' => intval($product['discount_percent'] ?? 0),
        'main_image' => $product['main_image'],
        'brand_name' => $product['brand_name'],
        'category_name' => $product['category_name'],
        'gender' => $product['gender'],
        'description' => strip_tags(mb_substr($product['description'] ?? '', 0, 220)) . '...',
        'avg_rating' => round(floatval($product['avg_rating']), 1),
        'review_count' => intval($product['review_count']),
        'total_stock' => $total_stock,
        'variants' => $variants,
        'gallery' => $gallery
    ]
]);
