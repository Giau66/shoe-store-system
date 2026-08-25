<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($id <= 0 && !empty($slug)) {
    $stmt_slug = $conn->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
    if ($stmt_slug) {
        $stmt_slug->bind_param("s", $slug);
        $stmt_slug->execute();
        $slug_row = $stmt_slug->get_result()->fetch_assoc();
        if ($slug_row) {
            $id = (int)$slug_row['id'];
        }
        $stmt_slug->close();
    }
}

// Kiểm tra quyền đánh giá
$can_review = false;
$review_reason = '';
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $urole = $_SESSION['user_role'] ?? 'customer';
    
    if (in_array($urole, ['admin', 'staff', 'employee'], true)) {
        $can_review = false;
        $review_reason = 'admin_restricted';
    } else {
        $chk_purchased = $conn->query("
            SELECT 1 
            FROM order_details od 
            JOIN orders o ON od.order_id = o.id 
            WHERE o.user_id = $uid 
              AND od.product_id = $id 
              AND o.status = 'completed' 
            LIMIT 1
        ");
        if ($chk_purchased && $chk_purchased->num_rows > 0) {
            $can_review = true;
        } else {
            $can_review = false;
            $review_reason = 'not_purchased';
        }
    }
}

// =========================================================================
// XỬ LÝ AJAX POST (CHẠY TRƯỚC KHI LOAD HEADER.PHP ĐỂ TRẢ VỀ PURE JSON 100%)
// =========================================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

    // 1. GỬI ĐÁNH GIÁ (CUSTOMER REVIEW)
    if ($action === 'add_comment') {
        if (!isset($_SESSION['user_id'])) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đánh giá!', 'redirect' => 'login.php']);
                exit;
            }
            header('Location: login.php');
            exit;
        }
        if (!$can_review) {
            $msg_err = ($review_reason === 'admin_restricted') 
                ? 'Tài khoản Quản trị viên / Nhân viên không thể thực hiện đánh giá sản phẩm!' 
                : 'Bạn cần mua sản phẩm này và hoàn thành đơn hàng mới được phép đánh giá!';
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $msg_err]);
                exit;
            }
            echo "<script>alert('$msg_err'); window.location.href='product-detail.php?id=$id';</script>";
            exit;
        }
        $rating = (int)($_POST['rating'] ?? 5);
        $content = trim($_POST['content'] ?? '');
        $uid = intval($_SESSION['user_id']);
        $uname = $_SESSION['user_name'] ?? 'Khách hàng';
        
        if ($rating >= 1 && $rating <= 5 && !empty($content)) {
            $stmt_cmt = $conn->prepare("INSERT INTO comments (product_id, user_id, user_name, rating, content) VALUES (?, ?, ?, ?, ?)");
            $stmt_cmt->bind_param("iisss", $id, $uid, $uname, $rating, $content);
            if ($stmt_cmt->execute()) {
                $new_comment_id = $conn->insert_id;
                $stmt_cmt->close();

                if ($is_ajax) {
                    $avg_res = $conn->query("SELECT COUNT(*) as cnt, AVG(rating) as avg_r FROM comments WHERE product_id = $id AND status = 1")->fetch_assoc();
                    $new_total = intval($avg_res['cnt'] ?? 1);
                    $new_avg = round(floatval($avg_res['avg_r'] ?? $rating), 1);

                    $star_html = '';
                    for ($i = 1; $i <= 5; $i++) {
                        $star_html .= '<i class="fa-solid fa-star ' . ($i <= $rating ? '' : 'text-muted opacity-25') . '"></i>';
                    }

                    $comment_html = '
                    <div id="comment-' . $new_comment_id . '" class="comment-card p-3 mb-3 bg-white rounded-4 border shadow-sm comment-target-flash">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold text-dark fs-6">' . htmlspecialchars($uname) . '</span>
                                <span class="badge bg-light text-secondary border ms-2">Khách mua hàng</span>
                            </div>
                            <span class="text-muted small"><i class="fa-regular fa-clock me-1"></i>Vừa xong</span>
                        </div>
                        <div class="star-rating mb-2 small text-warning">
                            ' . $star_html . '
                            <span class="fw-bold text-dark ms-1">' . $rating . '/5</span>
                        </div>
                        <p class="mb-2 text-dark fs-6">' . nl2br(htmlspecialchars($content)) . '</p>
                    </div>';

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success'      => true,
                        'message'      => 'Đánh giá của bạn đã được gửi thành công!',
                        'comment_html' => $comment_html,
                        'new_total'    => $new_total,
                        'new_avg'      => $new_avg
                    ]);
                    exit;
                }
                header("Location: product-detail.php?id=$id#comment-$new_comment_id");
                exit;
            }
        }
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung đánh giá!']);
            exit;
        }
    }

    // 2. PHẢN HỒI BÌNH LUẬN (STAFF / ADMIN REPLY)
    if ($action === 'staff_reply_comment') {
        if (isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'employee', 'staff'], true)) {
            $cmt_id = intval($_POST['comment_id'] ?? 0);
            $reply_txt = trim($_POST['reply_content'] ?? '');
            $staff_id = intval($_SESSION['user_id']);
            
            if ($cmt_id > 0) {
                $stmt_r = $conn->prepare("UPDATE comments SET staff_reply = ?, staff_id = ? WHERE id = ?");
                $reply_val = empty($reply_txt) ? null : $reply_txt;
                $stmt_r->bind_param("sii", $reply_val, $staff_id, $cmt_id);
                if ($stmt_r->execute()) {
                    $stmt_r->close();

                    if ($is_ajax) {
                        $reply_html = '';
                        if (!empty($reply_txt)) {
                            $reply_html = '
                            <div class="comment-reply-box ms-4 mt-3 p-3 bg-light rounded-3 border-start border-4 border-warning shadow-sm" id="reply-display-' . $cmt_id . '">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-warning text-dark me-2 px-2 py-1"><i class="fa-solid fa-reply fa-rotate-180 me-1"></i> Phản hồi từ Shop</span>
                                        <strong class="text-dark small">Hỗ trợ khách hàng</strong>
                                    </div>
                                    <button class="btn btn-sm btn-link text-decoration-none p-0 small" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm' . $cmt_id . '">
                                        <i class="fa-solid fa-pen me-1"></i>Sửa phản hồi
                                    </button>
                                </div>
                                <p class="mb-0 text-dark small fs-6" style="line-height: 1.6;">' . nl2br(htmlspecialchars($reply_txt)) . '</p>
                            </div>';
                        }

                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success'    => true,
                            'comment_id' => $cmt_id,
                            'reply_html' => $reply_html,
                            'message'    => 'Đã lưu phản hồi bình luận thành công!'
                        ]);
                        exit;
                    }
                    header("Location: product-detail.php?id=$id#comment-$cmt_id");
                    exit;
                }
            }
        }
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện phản hồi này!']);
            exit;
        }
    }
}

// =========================================================================
// BÂY GIỜ MỚI LOAD GIAO DIỆN HEADER.PHP
// =========================================================================
require_once 'includes/header.php';

if ($id <= 0) {
    echo "<script>alert('Sản phẩm không hợp lệ!'); window.location.href='index.php';</script>";
    exit;
}

// Increment view count
$conn->query("UPDATE products SET view_count = view_count + 1 WHERE id = $id");

// Ghi nhận lịch sử xem sản phẩm vào Session & Cookie để gợi ý ưu tiên
if (!isset($_SESSION['viewed_product_ids']) || !is_array($_SESSION['viewed_product_ids'])) {
    $_SESSION['viewed_product_ids'] = !empty($_COOKIE['viewed_product_ids']) ? json_decode($_COOKIE['viewed_product_ids'], true) ?: [] : [];
}
$v_key = array_search($id, $_SESSION['viewed_product_ids']);
if ($v_key !== false) {
    unset($_SESSION['viewed_product_ids'][$v_key]);
}
array_unshift($_SESSION['viewed_product_ids'], $id);
$_SESSION['viewed_product_ids'] = array_slice($_SESSION['viewed_product_ids'], 0, 30);
@setcookie('viewed_product_ids', json_encode(array_values($_SESSION['viewed_product_ids'])), time() + 30*86400, '/');

// Fetch Product
$stmt = $conn->prepare("
    SELECT p.*, b.name as brand_name, c.name as cat_name 
    FROM products p 
    JOIN brands b ON p.brand_id = b.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "<script>alert('Sản phẩm không tồn tại hoặc đã ngừng kinh doanh!'); window.location.href='index.php';</script>";
    exit;
}

// Kiểm tra xem sản phẩm có đang trong Sự kiện Sale nào đang diễn ra hay không
$sale_event_info = get_active_sale_event_for_product($conn, $id, $product['price']);
$has_sale_event = $sale_event_info['has_sale'];
$display_current_price = $has_sale_event ? $sale_event_info['sale_price'] : floatval($product['price']);
$display_old_price = $has_sale_event 
    ? ($product['price'] > $display_current_price ? $product['price'] : ($product['old_price'] ?: $product['price']))
    : floatval($product['old_price']);
$display_discount_percent = $has_sale_event 
    ? $sale_event_info['discount_percent'] 
    : intval($product['discount_percent']);

// Fetch Variants (Size/Stock)
$variants_res = $conn->query("SELECT * FROM product_variants WHERE product_id = $id AND stock_quantity > 0 ORDER BY size ASC");
$variants = [];
while ($v = $variants_res->fetch_assoc()) {
    $variants[] = $v;
}

// Fetch Comments
$comments_res = $conn->query("SELECT * FROM comments WHERE product_id = $id AND status = 1 ORDER BY created_at DESC");
$total_comments = $comments_res->num_rows;
$avg_rating = 5;
$comments = [];
if ($total_comments > 0) {
    $sum_rating = 0;
    while ($c = $comments_res->fetch_assoc()) {
        $sum_rating += $c['rating'];
        $comments[] = $c;
    }
    $avg_rating = round($sum_rating / $total_comments, 1);
}

// Fetch Applicable Vouchers for this product
$product_brand_id = intval($product['brand_id'] ?? 0);
$prod_event_id = ($sale_info['has_sale'] && !empty($sale_info['event_id'])) ? intval($sale_info['event_id']) : 0;
$evt_where = ($prod_event_id > 0) ? "(sale_event_id IS NULL OR sale_event_id = 0 OR sale_event_id = $prod_event_id)" : "(sale_event_id IS NULL OR sale_event_id = 0) AND event_type != 'holiday'";

$applicable_vouchers = [];
$v_prod_res = $conn->query("
    SELECT * FROM vouchers 
    WHERE status = 1 
      AND (brand_id IS NULL OR brand_id = 0 OR brand_id = $product_brand_id)
      AND $evt_where
      AND (end_date IS NULL OR end_date >= NOW())
    ORDER BY id DESC LIMIT 4
");
if ($v_prod_res) {
    while ($r = $v_prod_res->fetch_assoc()) {
        $applicable_vouchers[] = $r;
    }
}

$user_saved_voucher_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $uv_res = $conn->query("SELECT voucher_id FROM user_vouchers WHERE user_id=$uid AND used_at IS NULL");
    if ($uv_res) {
        while ($r = $uv_res->fetch_assoc()) {
            $user_saved_voucher_ids[] = intval($r['voucher_id']);
        }
    }
}

// Fetch Size Guide & Tips for this product
$product_size_charts = [];
$sg_prod_res = $conn->query("
    SELECT * FROM size_guides 
    WHERE status = 1 AND (brand_id = $product_brand_id OR brand_id IS NULL OR brand_id = 0)
    ORDER BY (brand_id = $product_brand_id) DESC, foot_length_cm ASC
");
if ($sg_prod_res) {
    while ($r = $sg_prod_res->fetch_assoc()) {
        $product_size_charts[] = $r;
    }
}

$product_guide_tips = [];
$gt_prod_res = $conn->query("SELECT * FROM size_guide_tips WHERE status = 1 ORDER BY step_number ASC, sort_order ASC");
if ($gt_prod_res) {
    while ($r = $gt_prod_res->fetch_assoc()) {
        $product_guide_tips[] = $r;
    }
}

// Fetch Related (Sản phẩm cùng thương hiệu)
$product_brand_id = intval($product['brand_id']);
$related_res = $conn->query("
    SELECT p.*, b.name as brand_name,
           COALESCE(AVG(cm.rating), 5.0) as avg_rating,
           COUNT(cm.id) as review_count,
           COALESCE((SELECT SUM(pv2.stock_quantity) FROM product_variants pv2 WHERE pv2.product_id = p.id), 0) as total_stock
    FROM products p 
    JOIN brands b ON p.brand_id = b.id 
    LEFT JOIN comments cm ON cm.product_id = p.id AND cm.status = 1
    WHERE p.brand_id = $product_brand_id AND p.id != $id AND p.status = 1 
    GROUP BY p.id
    ORDER BY p.is_hot DESC, p.sold_count DESC, p.id DESC 
    LIMIT 4
");

// Fallback: Nếu cùng thương hiệu có ít hơn 4 sản phẩm -> lấy thêm cùng danh mục
$related_products_list = [];
if ($related_res && $related_res->num_rows > 0) {
    while ($r_row = $related_res->fetch_assoc()) {
        $related_products_list[] = $r_row;
    }
}
if (count($related_products_list) < 4) {
    $existing_ids = array_merge([$id], array_column($related_products_list, 'id'));
    $not_in = implode(',', $existing_ids);
    $cat_id = intval($product['category_id']);
    $limit_rem = 4 - count($related_products_list);
    $fb_res = $conn->query("
        SELECT p.*, b.name as brand_name,
               COALESCE(AVG(cm.rating), 5.0) as avg_rating,
               COUNT(cm.id) as review_count,
               COALESCE((SELECT SUM(pv2.stock_quantity) FROM product_variants pv2 WHERE pv2.product_id = p.id), 0) as total_stock
        FROM products p 
        JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN comments cm ON cm.product_id = p.id AND cm.status = 1
        WHERE p.category_id = $cat_id AND p.id NOT IN ($not_in) AND p.status = 1 
        GROUP BY p.id
        ORDER BY p.is_hot DESC, p.sold_count DESC, p.id DESC 
        LIMIT $limit_rem
    ");
    if ($fb_res) {
        while ($fb_row = $fb_res->fetch_assoc()) {
            $related_products_list[] = $fb_row;
        }
    }
}

// Check wishlist
$is_wished = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $check_w = $conn->query("SELECT 1 FROM wishlists WHERE user_id=$uid AND product_id=$id");
    if ($check_w->num_rows > 0) $is_wished = true;
}
// Fetch 6 Angle Images
$angle_names = [
    1 => 'Chính diện',
    2 => 'Mặt trong',
    3 => 'Mặt trên',
    4 => 'Phía sau gót',
    5 => 'Mặt đế',
    6 => 'Cận cảnh'
];

$product_images = array_fill(1, 6, $product['main_image']);
$res_imgs = $conn->query("SELECT image_url, sort_order FROM product_images WHERE product_id = $id ORDER BY sort_order ASC");
if ($res_imgs) {
    while ($img = $res_imgs->fetch_assoc()) {
        $so = (int)$img['sort_order'];
        if ($so >= 2 && $so <= 6 && !empty($img['image_url'])) {
            $product_images[$so] = $img['image_url'];
        }
    }
}
?>

<style>
    :root {
        --primary-dark: #1a1d21;
        --accent-gold: #c5a059;
        --bg-cream: #fcfbf7;
    }
    body { background-color: var(--bg-cream); font-family: 'Inter', sans-serif; }
    
    .product-img-main {
        width: 100%;
        max-height: 420px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        background: #fff;
    }
    .thumb-img {
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    .thumb-img:hover, .thumb-img.active-thumb {
        border-color: var(--accent-gold) !important;
        transform: scale(1.05);
    }
    
    .brand-badge {
        background: var(--primary-dark);
        color: var(--accent-gold);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 15px;
    }
    
    .price-box {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        border-left: 5px solid var(--accent-gold);
        margin: 20px 0;
    }
    .current-price { font-size: 2rem; font-weight: 800; color: #d63031; }
    .old-price { text-decoration: line-through; color: #888; font-size: 1.2rem; margin-left: 15px; }
    .discount-label { background: #d63031; color: white; padding: 3px 10px; border-radius: 5px; font-size: 0.9rem; font-weight: bold; margin-left: 15px; position: relative; top: -5px; }
    
    .size-selector { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
    .size-option {
        display: none;
    }
    .size-label {
        border: 2px solid #ddd;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        background: #fff;
    }
    .size-option:checked + .size-label {
        border-color: var(--accent-gold);
        background: var(--accent-gold);
        color: white;
    }
    
    .qty-input { width: 80px; text-align: center; font-weight: bold; border: 2px solid #ddd; border-radius: 8px; }
    
    .btn-add-cart { background: white; color: var(--primary-dark); border: 2px solid var(--primary-dark); font-weight: 700; padding: 15px; }
    .btn-add-cart:hover { background: var(--primary-dark); color: white; }
    .btn-buy-now { background: var(--accent-gold); color: white; border: 2px solid var(--accent-gold); font-weight: 700; padding: 15px; }
    .btn-buy-now:hover { background: #b08d4b; border-color: #b08d4b; color: white; }
    
    .wishlist-detail-btn {
        background: #fff; border: 2px solid #ddd; color: #ddd; width: 56px; height: 56px; border-radius: 8px; font-size: 1.5rem; transition: all 0.3s;
    }
    .wishlist-detail-btn.active { color: #ff4757; border-color: #ff4757; }
    
    .nav-tabs .nav-link { color: var(--primary-dark); font-weight: 600; border: none; padding: 15px 30px; font-size: 1.1rem; }
    .nav-tabs .nav-link.active { border-bottom: 3px solid var(--accent-gold); color: var(--accent-gold); background: transparent; }
    
    .comment-card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .star-rating { color: #f1c40f; }

    #desc h5 {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin-top: 1.75rem;
        margin-bottom: 0.85rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    #desc ul {
        padding-left: 1.25rem;
        margin-bottom: 1.25rem;
    }
    #desc li {
        margin-bottom: 0.5rem;
        line-height: 1.75;
        color: #334155;
    }
    #desc p {
        line-height: 1.85;
        color: #334155;
        margin-bottom: 1rem;
    }
</style>

<div class="container py-5 mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="all-products.php?category_id=<?= $product['category_id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($product['cat_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Image Gallery (Ảnh chính + 6 góc độ) -->
        <div class="col-lg-5">
            <div class="position-relative mb-3">
                <img id="mainProductImg" src="<?= htmlspecialchars($product_images[1]) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img-main img-fluid border">
            </div>
            
            <!-- 6 Small Thumbnail Images -->
            <div class="d-flex flex-wrap justify-content-between gap-2 py-2">
                <?php foreach ($angle_names as $idx => $angle_label): 
                    $img_url = $product_images[$idx];
                ?>
                    <div style="flex: 1 1 0; min-width: 50px;">
                        <img src="<?= htmlspecialchars($img_url) ?>" 
                             class="img-thumbnail thumb-img rounded-3 shadow-sm <?= $idx === 1 ? 'active-thumb' : '' ?>" 
                             style="width: 100%; height: 68px; object-fit: cover; cursor: pointer;" 
                             onclick="changeMainImage('<?= htmlspecialchars($img_url) ?>', this)"
                             alt="<?= htmlspecialchars($angle_label) ?>"
                             title="<?= htmlspecialchars($angle_label) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="col-lg-7">
            <span class="brand-badge"><?= htmlspecialchars($product['brand_name']) ?></span>
            <h1 class="fw-bold mb-3 text-dark"><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="d-flex align-items-center mb-3">
                <div class="star-rating me-2 fs-5">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="fa-solid fa-star <?= $i <= $avg_rating ? '' : 'text-muted' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="text-muted">(<?= $total_comments ?> đánh giá) | <?= $product['sold_count'] ?> đã bán</span>
            </div>
            
            <?php if ($has_sale_event): ?>
                <!-- BANNER SỰ KIỆN SALE ĐANG DIỄN RA -->
                <div class="sale-event-ribbon mb-3 p-3 rounded-4 shadow-sm text-white d-flex align-items-center justify-content-between flex-wrap gap-2"
                     style="background: linear-gradient(135deg, <?= htmlspecialchars($sale_event_info['color_theme']) ?> 0%, #0f172a 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-white text-danger fw-bold text-uppercase px-2 py-1"><i class="fa-solid fa-bolt-lightning me-1"></i>SỰ KIỆN SALE</span>
                        <a href="sale-event.php?slug=<?= htmlspecialchars($sale_event_info['event_slug']) ?>" class="text-white fw-bold text-decoration-underline">
                            <?= htmlspecialchars($sale_event_info['event_name']) ?>
                        </a>
                    </div>
                    <div class="badge bg-warning text-dark fw-black fs-6 px-3 py-1 rounded-pill">
                        TIẾT KIỆM -<?= $display_discount_percent ?>%
                    </div>
                </div>
            <?php endif; ?>

            <div class="price-box">
                <span class="current-price"><?= number_format($display_current_price, 0, ',', '.') ?>đ</span>
                <?php if ($display_old_price > $display_current_price): ?>
                    <span class="old-price"><?= number_format($display_old_price, 0, ',', '.') ?>đ</span>
                    <span class="discount-label">-<?= $display_discount_percent ?>%</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($applicable_vouchers)): ?>
                <div class="product-voucher-strip d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="fa-solid fa-ticket me-1"></i>ƯU ĐÃI</span>
                        <?php foreach ($applicable_vouchers as $av): 
                            $av_text = ($av['discount_type'] === 'freeship') ? 'Freeship' : (($av['discount_type'] === 'percent') ? ('-' . intval($av['discount_value']) . '%') : ('-' . number_format($av['discount_value']/1000, 0) . 'K'));
                        ?>
                            <span class="product-voucher-tag" onclick="copyVoucherCode('<?= htmlspecialchars($av['code']) ?>', this)" title="Bấm để sao chép mã <?= htmlspecialchars($av['code']) ?>">
                                <i class="fa-solid fa-tag text-warning"></i> <?= htmlspecialchars($av['code']) ?> (<?= $av_text ?>)
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="cart-process.php" method="POST" id="addCartForm">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                
                <?php if (empty($variants)): ?>
                    <div class="alert alert-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Sản phẩm hiện đang hết hàng.</div>
                <?php else: ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">Chọn Size:</h6>
                            <a href="#sizeguide" onclick="document.getElementById('sizeguide-tab').click(); document.getElementById('myTab').scrollIntoView({behavior: 'smooth'}); return false;" class="text-decoration-none small text-warning fw-bold d-inline-flex align-items-center gap-1 cursor-pointer">
                                <i class="fa-solid fa-ruler-combined"></i> Hướng Dẫn Chọn Size
                            </a>
                        </div>
                        <div class="size-selector">
                            <?php foreach($variants as $index => $v): ?>
                                <div>
                                    <input type="radio" name="variant_id" id="size_<?= $v['id'] ?>" class="size-option" value="<?= $v['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                    <label for="size_<?= $v['id'] ?>" class="size-label">
                                        <?= htmlspecialchars($v['size']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4 d-flex align-items-center">
                        <h6 class="fw-bold mb-0 me-3">Số lượng:</h6>
                        <input type="number" name="quantity" class="form-control qty-input" value="1" min="1" max="99" required>
                    </div>
                    
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" name="action" value="add_to_cart" class="btn btn-add-cart flex-grow-1 rounded-pill">
                            <i class="fa-solid fa-cart-plus me-2"></i> THÊM VÀO GIỎ HÀNG
                        </button>
                        <button type="submit" name="action" value="buy_now" class="btn btn-buy-now flex-grow-1 rounded-pill">
                            <i class="fa-solid fa-bag-shopping me-2"></i> MUA NGAY
                        </button>
                        <button type="button" class="btn wishlist-detail-btn d-flex align-items-center justify-content-center <?= $is_wished ? 'active' : '' ?>" id="wishlistBtn" data-id="<?= $id ?>">
                            <i class="fa-<?= $is_wished ? 'solid' : 'regular' ?> fa-heart"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </form>
            
            <div class="mt-4 pt-4 border-top">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Cam kết 100% chính hãng</li>
                    <li class="mb-2"><i class="fa-solid fa-rotate-left text-success me-2"></i> Miễn phí đổi trả trong 30 ngày</li>
                    <li><i class="fa-solid fa-truck-fast text-success me-2"></i> Giao hàng toàn quốc nhanh chóng</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="row mt-5 pt-5">
        <div class="col-12">
            <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab">MÔ TẢ SẢN PHẨM</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button" role="tab">ĐÁNH GIÁ (<?= $total_comments ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sizeguide-tab" data-bs-toggle="tab" data-bs-target="#sizeguide" type="button" role="tab">
                        <i class="fa-solid fa-ruler-combined me-2 text-warning"></i>HƯỚNG DẪN CHỌN SIZE
                    </button>
                </li>
            </ul>
            <div class="tab-content py-4" id="myTabContent">
                <div class="tab-pane fade show active bg-white p-4 rounded-bottom" id="desc" role="tabpanel">
                    <div class="product-description-content fs-6">
                        <?= !empty($product['description']) ? $product['description'] : '<p class="text-muted">Chưa có mô tả chi tiết cho sản phẩm này.</p>' ?>
                    </div>
                </div>
                <div class="tab-pane fade bg-white p-4 rounded-bottom" id="review" role="tabpanel">
                    <div class="row">
                        <div class="col-md-4 border-end text-center mb-4 mb-md-0">
                            <h3 class="display-4 fw-bold text-dark"><?= $avg_rating ?> <small class="fs-4 text-muted">/ 5</small></h3>
                            <div class="star-rating fs-3 mb-2">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="fa-solid fa-star <?= $i <= $avg_rating ? '' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted">Dựa trên <?= $total_comments ?> đánh giá</p>
                        </div>
                        <div class="col-md-8 pl-md-4">
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <?php if ($can_review): ?>
                                    <form id="reviewForm" method="POST" class="mb-5 bg-light p-4 rounded-4 border shadow-sm">
                                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Viết đánh giá của bạn</h5>
                                        <input type="hidden" name="action" value="add_comment">
                                        <input type="hidden" name="ajax" value="1">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Điểm đánh giá:</label>
                                            <select name="rating" id="reviewRating" class="form-select w-auto fw-bold" required>
                                                <option value="5">⭐⭐⭐⭐⭐ 5 Sao - Rất tuyệt vời</option>
                                                <option value="4">⭐⭐⭐⭐ 4 Sao - Tốt</option>
                                                <option value="3">⭐⭐⭐ 3 Sao - Tạm được</option>
                                                <option value="2">⭐⭐ 2 Sao - Không hài lòng</option>
                                                <option value="1">⭐ 1 Sao - Tệ</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Nội dung đánh giá:</label>
                                            <textarea name="content" id="reviewContent" class="form-control" rows="3" required placeholder="Chia sẻ cảm nhận của bạn về mẫu giày này..."></textarea>
                                        </div>
                                        <button type="submit" id="btnSubmitReview" class="btn btn-dark px-4 rounded-pill fw-bold">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Gửi đánh giá
                                        </button>
                                    </form>
                                <?php elseif ($review_reason === 'admin_restricted'): ?>
                                    <div class="alert alert-warning d-flex align-items-center mb-5 rounded-4 shadow-sm border border-warning">
                                        <i class="fa-solid fa-user-shield fa-2x me-3 text-warning"></i>
                                        <div>
                                            <strong class="d-block text-dark fs-6">Tài khoản Quản trị / Nhân viên</strong>
                                            <span class="small text-muted">Tài khoản Admin và Nhân viên không có quyền thực hiện đánh giá sản phẩm.</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info d-flex align-items-center mb-5 rounded-4 shadow-sm border border-info">
                                        <i class="fa-solid fa-bag-shopping fa-2x me-3 text-info"></i>
                                        <div>
                                            <strong class="d-block text-dark fs-6">Chưa thể đánh giá sản phẩm này</strong>
                                            <span class="small text-muted">Bạn cần <b>đặt mua mẫu giày này và hoàn thành đơn hàng</b> mới có thể gửi đánh giá.</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-light d-flex align-items-center mb-5 rounded-4 shadow-sm border">
                                    <i class="fa-solid fa-circle-info fa-2x me-3 text-primary"></i>
                                    <div>Bạn cần <a href="login.php" class="fw-bold text-decoration-none">Đăng nhập</a> và đã mua sản phẩm này để gửi đánh giá.</div>
                                </div>
                            <?php endif; ?>

                            <h5 class="fw-bold mb-4">Các đánh giá & phản hồi mới nhất</h5>
                            <div id="comments-list">
                                <?php if(empty($comments)): ?>
                                    <p class="text-muted" id="no-comments-msg">Chưa có đánh giá nào cho sản phẩm này.</p>
                                <?php else: ?>
                                    <?php foreach($comments as $c): ?>
                                        <div id="comment-<?= $c['id']; ?>" class="comment-card p-3 mb-3 bg-white rounded-4 border shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($c['user_name']) ?></span>
                                                    <span class="badge bg-light text-secondary border ms-2">Khách mua hàng</span>
                                                </div>
                                                <span class="text-muted small"><i class="fa-regular fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                                            </div>
                                            <div class="star-rating mb-2 small text-warning">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="fa-solid fa-star <?= $i <= $c['rating'] ? '' : 'text-muted opacity-25' ?>"></i>
                                                <?php endfor; ?>
                                                <span class="fw-bold text-dark ms-1"><?= $c['rating'] ?>/5</span>
                                            </div>
                                            <p class="mb-2 text-dark fs-6"><?= nl2br(htmlspecialchars($c['content'])) ?></p>

                                            <!-- KHUNG HIỂN THỊ PHẢN HỒI -->
                                            <div id="reply-wrapper-<?= $c['id']; ?>">
                                                <?php if (!empty($c['staff_reply'])): ?>
                                                    <div class="comment-reply-box ms-4 mt-3 p-3 bg-light rounded-3 border-start border-4 border-warning shadow-sm" id="reply-display-<?= $c['id']; ?>">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-warning text-dark me-2 px-2 py-1"><i class="fa-solid fa-reply fa-rotate-180 me-1"></i> Phản hồi từ Shop</span>
                                                                <strong class="text-dark small">Hỗ trợ khách hàng</strong>
                                                            </div>
                                                            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'employee', 'staff'])): ?>
                                                                <button class="btn btn-sm btn-link text-decoration-none p-0 small" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm<?= $c['id']; ?>">
                                                                    <i class="fa-solid fa-pen me-1"></i>Sửa phản hồi
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="mb-0 text-dark small fs-6" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($c['staff_reply'])) ?></p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'employee', 'staff'])): ?>
                                                        <div class="mt-2" id="reply-btn-box-<?= $c['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm<?= $c['id']; ?>">
                                                                <i class="fa-solid fa-reply me-1"></i> Phản hồi bình luận này
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- FORM PHẢN HỒI DÀNH CHO ADMIN / NHÂN VIÊN TRỰC TIẾP -->
                                            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'employee', 'staff'])): ?>
                                                <div class="collapse ms-4 mt-3" id="replyForm<?= $c['id']; ?>">
                                                    <div class="card card-body bg-light border-warning shadow-sm rounded-3">
                                                        <form class="staff-reply-form" data-comment-id="<?= $c['id']; ?>">
                                                            <input type="hidden" name="action" value="staff_reply_comment">
                                                            <input type="hidden" name="ajax" value="1">
                                                            <input type="hidden" name="comment_id" value="<?= $c['id']; ?>">
                                                            <label class="form-label fw-bold text-warning small"><i class="fa-solid fa-reply me-1"></i>Nhập lời phản hồi của Shop:</label>
                                                            <textarea name="reply_content" rows="3" class="form-control mb-2" placeholder="Cảm ơn khách hàng hoặc giải đáp thắc mắc..." required><?= htmlspecialchars($c['staff_reply'] ?? ''); ?></textarea>
                                                            <div class="d-flex justify-content-end gap-2">
                                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#replyForm<?= $c['id']; ?>">Hủy</button>
                                                                <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold"><i class="fa-solid fa-paper-plane me-1"></i>Gửi phản hồi</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: HƯỚNG DẪN CHỌN SIZE -->
                <div class="tab-pane fade bg-white p-4 p-lg-5 rounded-bottom" id="sizeguide" role="tabpanel">
                    <div class="row g-4 mb-4 align-items-center">
                        <div class="col-lg-7">
                            <h4 class="fw-bold text-dark mb-1">
                                <i class="fa-solid fa-ruler text-warning me-2"></i>Bảng Quy Đổi Size Cho Mẫu <?= htmlspecialchars($product['name']) ?>
                            </h4>
                            <p class="text-muted mb-0">Thương hiệu: <strong class="text-primary"><?= htmlspecialchars($product['brand_name']) ?></strong>. Đo chiều dài bàn chân để chọn size vừa vặn và thoải mái nhất.</p>
                        </div>
                        <div class="col-lg-5 text-lg-end">
                            <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#sizeCalculatorModal">
                                <i class="fa-solid fa-calculator me-1"></i> Mở Bảng Tính Size Chi Tiết
                            </button>
                        </div>
                    </div>

                    <!-- BỘ TÍNH SIZE NHANH NGAY TRONG TAB -->
                    <div class="p-3 p-md-4 rounded-4 mb-4 border" style="background: linear-gradient(145deg, #f8fafc, #f1f5f9);">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="fa-solid fa-ruler-horizontal text-warning me-1"></i>Nhập Chiều Dài Bàn Chân Của Bạn (cm):
                                </label>
                                <div class="input-group">
                                    <button class="btn btn-dark fw-bold" type="button" onclick="adjustTabFootCm(-0.5)">-</button>
                                    <input type="number" id="tabFootCmInput" class="form-control text-center fw-bold fs-5" value="24.5" step="0.1" min="18" max="33" oninput="calcTabShoeSize()">
                                    <span class="input-group-text fw-bold bg-white">cm</span>
                                    <button class="btn btn-dark fw-bold" type="button" onclick="adjustTabFootCm(0.5)">+</button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch pt-md-3">
                                    <input class="form-check-input" type="checkbox" id="tabWideFootChk" onchange="calcTabShoeSize()">
                                    <label class="form-check-label fw-bold text-dark small" for="tabWideFootChk">
                                        🦶 Chân Bè / Mu Dày (+1 Size)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 px-3 bg-white rounded-3 border border-warning shadow-sm d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small d-block" style="font-size: 11px;">Size Gợi Ý Cho Bạn:</span>
                                        <span class="fw-bold text-muted small" id="tabResBrand"><?= htmlspecialchars($product['brand_name']) ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="fs-3 fw-black text-primary" id="tabResSizeEu">EU 39</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng Quy Đổi Kích Cỡ -->
                    <div class="table-responsive mb-5 border rounded-4 overflow-hidden shadow-sm">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>Chiều Dài Bàn Chân (cm)</th>
                                    <th class="text-warning">Size EU (Việt Nam)</th>
                                    <th>Size US</th>
                                    <th>Size UK</th>
                                    <th>Lòng Giày (cm)</th>
                                    <th>Gợi Ý Độ Vừa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($product_size_charts)): ?>
                                    <?php foreach($product_size_charts as $sc): ?>
                                    <tr>
                                        <td class="fw-bold text-warning-emphasis"><i class="fa-solid fa-ruler-horizontal me-1"></i><?= $sc['foot_length_cm'] ?> cm</td>
                                        <td><strong class="text-primary fs-5">EU <?= htmlspecialchars($sc['size_eu']) ?></strong></td>
                                        <td><?= htmlspecialchars($sc['size_us'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($sc['size_uk'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($sc['size_cm'] ?: $sc['foot_length_cm']) ?> cm</td>
                                        <td class="text-muted small text-start"><?= htmlspecialchars($sc['note'] ?: 'Chuẩn size theo form hãng') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td class="fw-bold">22.5 cm</td>
                                        <td><strong class="text-primary fs-5">EU 36</strong></td>
                                        <td>5.0</td>
                                        <td>4.0</td>
                                        <td>23.0 cm</td>
                                        <td class="text-muted small">Chuẩn size nữ / chân nhỏ</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">23.5 cm</td>
                                        <td><strong class="text-primary fs-5">EU 38</strong></td>
                                        <td>6.0</td>
                                        <td>5.0</td>
                                        <td>24.0 cm</td>
                                        <td class="text-muted small">Chuẩn size nữ / nam chân nhỏ</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">24.5 cm</td>
                                        <td><strong class="text-primary fs-5">EU 39</strong></td>
                                        <td>6.5</td>
                                        <td>5.5</td>
                                        <td>24.5 cm</td>
                                        <td class="text-muted small">Size phổ thông</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">25.0 cm</td>
                                        <td><strong class="text-primary fs-5">EU 40</strong></td>
                                        <td>7.0</td>
                                        <td>6.0</td>
                                        <td>25.0 cm</td>
                                        <td class="text-muted small">Size chuẩn nam / nữ chân lớn</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">25.5 cm</td>
                                        <td><strong class="text-primary fs-5">EU 41</strong></td>
                                        <td>8.0</td>
                                        <td>7.0</td>
                                        <td>26.0 cm</td>
                                        <td class="text-muted small">Size chuẩn nam</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">26.0 cm</td>
                                        <td><strong class="text-primary fs-5">EU 42</strong></td>
                                        <td>8.5</td>
                                        <td>7.5</td>
                                        <td>26.5 cm</td>
                                        <td class="text-muted small">Size chuẩn nam</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">26.5 cm</td>
                                        <td><strong class="text-primary fs-5">EU 43</strong></td>
                                        <td>9.5</td>
                                        <td>8.5</td>
                                        <td>27.5 cm</td>
                                        <td class="text-muted small">Size nam lớn</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4 Bước Đo Size Nhanh -->
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-ol text-warning me-2"></i>4 Bước Tự Đo Chiều Dài Bàn Chân Tại Nhà</h5>
                    <div class="row g-3">
                        <?php if (!empty($product_guide_tips)): ?>
                            <?php foreach($product_guide_tips as $ptip): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-dark text-warning fw-bold px-2 py-1">0<?= $ptip['step_number'] ?></span>
                                        <strong class="text-dark small"><?= htmlspecialchars($ptip['title']) ?></strong>
                                    </div>
                                    <p class="text-muted small mb-0" style="line-height: 1.5;"><?= htmlspecialchars($ptip['description']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-dark text-warning fw-bold">01</span>
                                        <strong class="text-dark small">Chuẩn Bị</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Đặt tờ giấy A4 phẳng trên sàn, sát mép tường.</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-dark text-warning fw-bold">02</span>
                                        <strong class="text-dark small">Vẽ Khung</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Đặt chân lên giấy, dùng bút viền theo bàn chân vuông góc.</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-dark text-warning fw-bold">03</span>
                                        <strong class="text-dark small">Đo Chiều Dài</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Đo khoảng cách gót đến ngón dài nhất để lấy số cm (L).</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-dark text-warning fw-bold">04</span>
                                        <strong class="text-dark small">Đối Chiếu</strong>
                                    </div>
                                    <p class="text-muted small mb-0">Lấy L + 0.5cm và đối chiếu vào bảng size phía trên.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mẹo Lưu Ý -->
                    <div class="alert alert-warning border-0 rounded-4 p-3 mt-4 mb-0 d-flex align-items-start gap-3">
                        <i class="fa-solid fa-lightbulb fa-2x text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block text-dark mb-1">Mẹo chọn size chuẩn xác từ chuyên gia:</strong>
                            <span class="text-dark small">Nếu bàn chân của bạn có đặc điểm <b>bè ngang</b>, <b>mu bàn chân dày</b> hoặc thường xuyên <b>mang tất thể thao dày</b>, bạn nên chọn <b>tăng thêm +1 Size (Ví dụ: Chân size 40 thì nên chọn size 41)</b> để bước đi thoải mái nhất!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products (Cùng Thương Hiệu) -->
    <?php if (!empty($related_products_list)): ?>
    <div class="mt-5 pt-5 border-top">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h3 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-layer-group text-warning me-2"></i>SẢN PHẨM TƯƠNG TỰ
            </h3>
            <a href="all-products.php?brand=<?= intval($product['brand_id']) ?>" class="btn btn-outline-dark btn-sm rounded-pill fw-bold px-3">
                Xem tất cả <?= htmlspecialchars($product['brand_name'] ?? '') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-3 g-md-4">
            <?php 
            // In product-detail, we need wishlist IDs for product cards
            if (!isset($wishlist_product_ids)) {
                $wishlist_product_ids = [];
                if (isset($_SESSION['user_id'])) {
                    $uid = $_SESSION['user_id'];
                    $w_q = $conn->query("SELECT product_id FROM wishlists WHERE user_id=$uid");
                    if ($w_q) {
                        while ($w = $w_q->fetch_assoc()) {
                            $wishlist_product_ids[] = intval($w['product_id']);
                        }
                    }
                }
            }
            $hide_hot_new = true;
            foreach($related_products_list as $p): 
            ?>
                <div class="col-lg-3 col-md-6 col-12">
                    <?php include 'includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Chuyển đổi 6 góc ảnh sản phẩm mượt mà
function changeMainImage(imgUrl, element) {
    const mainImg = document.getElementById('mainProductImg');
    if (mainImg && imgUrl) {
        mainImg.style.opacity = '0.35';
        mainImg.style.transform = 'scale(0.98)';
        mainImg.style.transition = 'all 0.2s ease';
        setTimeout(() => {
            mainImg.src = imgUrl;
            mainImg.style.opacity = '1';
            mainImg.style.transform = 'scale(1)';
        }, 120);
    }
    document.querySelectorAll('.thumb-img').forEach(t => t.classList.remove('active-thumb'));
    if (element) {
        element.classList.add('active-thumb');
    }
}
window.changeMainImage = changeMainImage;

// Wishlist Toggle Detail
const detailWishlistBtn = document.getElementById('wishlistBtn');
if (detailWishlistBtn) {
    detailWishlistBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const pid = this.dataset.id;
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            alert('Vui lòng đăng nhập để thêm vào yêu thích!');
            window.location.href = 'login.php';
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
            } else if(data.status === 'removed') {
                this.classList.remove('active');
                this.innerHTML = '<i class="fa-regular fa-heart"></i>';
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(err => console.error(err));
    });
}

// Card Wishlist for Related Products
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const pid = this.dataset.id;
        <?php if(!isset($_SESSION['user_id'])): ?>
            alert('Vui lòng đăng nhập để thêm vào yêu thích!');
            window.location.href = 'login.php';
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
                this.innerHTML = '<i class="fa-solid fa-heart text-danger"></i>';
            } else if(data.status === 'removed') {
                this.classList.remove('active');
                this.innerHTML = '<i class="fa-regular fa-heart"></i>';
            }
        })
        .catch(err => console.error(err));
    });
});

// Toast notification helper (tự động tắt sau 1.8 giây)
const DetailToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

// 1. GỬI ĐÁNH GIÁ SẢN PHẨM KHÔNG LOAD LẠI TRANG (LIVE AJAX)
document.addEventListener("DOMContentLoaded", function() {
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitReview');
            const content = document.getElementById('reviewContent').value.trim();

            if (!content) {
                DetailToast.fire({ icon: 'warning', title: 'Vui lòng nhập nội dung đánh giá!' });
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang gửi...';

            const formData = new FormData(reviewForm);

            fetch('product-detail.php?id=<?= $id ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi đánh giá';

                if (data.success) {
                    const noMsg = document.getElementById('no-comments-msg');
                    if (noMsg) noMsg.remove();

                    const list = document.getElementById('comments-list');
                    if (list) {
                        list.insertAdjacentHTML('afterbegin', data.comment_html);
                    }

                    // Reset form
                    document.getElementById('reviewContent').value = '';

                    // Cập nhật số lượng review tab
                    const revTab = document.getElementById('review-tab');
                    if (revTab && data.new_total) {
                        revTab.innerText = `ĐÁNH GIÁ (${data.new_total})`;
                    }

                    DetailToast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Thông báo',
                            text: data.message || 'Không thể gửi đánh giá.'
                        });
                    }
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi đánh giá';
                console.error(err);
                DetailToast.fire({ icon: 'error', title: 'Lỗi kết nối máy chủ!' });
            });
        });
    }

    // 2. PHẢN HỒI BÌNH LUẬN (ADMIN / NHÂN VIÊN) KHÔNG LOAD LẠI TRANG
    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.staff-reply-form');
        if (!form) return;

        e.preventDefault();
        const cmtId = form.dataset.commentId;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
        }

        const formData = new FormData(form);

        fetch('product-detail.php?id=<?= $id ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi phản hồi';
            }

            if (data.success) {
                const wrapper = document.getElementById('reply-wrapper-' + cmtId);
                if (wrapper) {
                    wrapper.innerHTML = data.reply_html;
                }
                
                // Ẩn collapse form
                const collapseEl = document.getElementById('replyForm' + cmtId);
                if (collapseEl) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, { toggle: false });
                    bsCollapse.hide();
                }

                DetailToast.fire({
                    icon: 'success',
                    title: data.message
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: data.message || 'Không thể lưu phản hồi.'
                });
            }
        })
        .catch(err => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi phản hồi';
            }
            console.error(err);
            DetailToast.fire({ icon: 'error', title: 'Lỗi kết nối máy chủ!' });
        });
    });

    // 3. THÊM VÀO GIỎ HÀNG KHÔNG LOAD LẠI TRANG (LIVE AJAX ADD TO CART)
    const addCartForm = document.getElementById('addCartForm');
    if (addCartForm) {
        addCartForm.addEventListener('submit', function(e) {
            const submitter = e.submitter;
            const actionVal = submitter ? submitter.value : 'add_to_cart';

            if (actionVal === 'add_to_cart') {
                e.preventDefault();

                const addBtn = this.querySelector('.btn-add-cart');
                const origHtml = addBtn.innerHTML;
                addBtn.disabled = true;
                addBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang thêm...';

                const formData = new FormData(addCartForm);
                formData.append('action', 'add_to_cart');
                formData.append('ajax', '1');

                fetch('cart-process.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    addBtn.disabled = false;
                    addBtn.innerHTML = origHtml;

                    if (data.success) {
                        // Cập nhật số lượng giỏ hàng trên navbar
                        const badges = document.querySelectorAll('#cart-badge, .badge-cart-count, .cart-count-badge');
                        badges.forEach(b => {
                            b.innerText = data.total_qty || (parseInt(b.innerText || 0) + 1);
                            b.style.display = 'inline-block';
                        });

                        Swal.fire({
                            title: 'Đã Thêm Vào Giỏ Hàng!',
                            text: 'Mẫu giày đã được thêm vào giỏ hàng của bạn thành công.',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonColor: '#1e293b',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: '<i class="fa-solid fa-cart-shopping me-1"></i> Xem Giỏ Hàng',
                            cancelButtonText: 'Tiếp tục mua sắm',
                            timer: 4000,
                            timerProgressBar: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'cart.php';
                            }
                        });
                    } else {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Thông báo',
                                text: data.message || 'Không thể thêm sản phẩm vào giỏ hàng.'
                            });
                        }
                    }
                })
                .catch(err => {
                    addBtn.disabled = false;
                    addBtn.innerHTML = origHtml;
                    console.error(err);
                    DetailToast.fire({ icon: 'error', title: 'Lỗi kết nối máy chủ!' });
                });
            }
        });
    }
});
</script>

<!-- MODAL BẢNG TÍNH SIZE CHI TIẾT -->
<div class="modal fade" id="sizeCalculatorModal" tabindex="-1" aria-labelledby="sizeCalcModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 px-4">
                <h5 class="modal-title fw-bold" id="sizeCalcModalLabel">
                    <i class="fa-solid fa-calculator text-warning me-2"></i>Bảng Tính &amp; Gợi Ý Size Giày Chuẩn Xác 99%
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6">
                        <div class="p-3 bg-white rounded-4 border shadow-sm">
                            <label class="form-label fw-bold text-dark mb-2">1. Chiều dài bàn chân của bạn (cm):</label>
                            <div class="input-group mb-2">
                                <button class="btn btn-dark fw-bold px-3" type="button" onclick="adjustModalFootCm(-0.5)">-</button>
                                <input type="number" id="modalFootCmInput" class="form-control text-center fw-bold fs-4" value="24.5" step="0.1" min="18" max="33" oninput="calcModalShoeSize()">
                                <span class="input-group-text fw-bold bg-light">cm</span>
                                <button class="btn btn-dark fw-bold px-3" type="button" onclick="adjustModalFootCm(0.5)">+</button>
                            </div>
                            <small class="text-muted d-block mb-3">Đo khoảng cách từ gót chân đến ngón dài nhất.</small>

                            <div class="form-check form-switch p-2 px-3 bg-light rounded-3 border mb-3">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="modalWideFootChk" onchange="calcModalShoeSize()">
                                <label class="form-check-label fw-bold text-dark small" for="modalWideFootChk">
                                    🦶 Chân Bè Ngang / Mu Bàn Chân Dày (+1 Size)
                                </label>
                            </div>

                            <button type="button" class="btn btn-warning w-100 fw-bold rounded-pill shadow-sm" onclick="calcModalShoeSize()">
                                <i class="fa-solid fa-bolt me-1"></i> TÍNH SIZE NGAY
                            </button>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="p-4 bg-white rounded-4 border border-warning shadow-sm text-center">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Size Đề Xuất Phù Hợp</span>
                            <div class="display-4 fw-black text-primary" id="mResSizeEu">EU 39</div>
                            <div class="fw-bold text-warning small mt-1" id="mResBrand"><?= htmlspecialchars($product['brand_name']) ?></div>

                            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                                <span class="badge bg-light text-dark border px-3 py-2">US: <strong id="mResSizeUs">6.5</strong></span>
                                <span class="badge bg-light text-dark border px-3 py-2">UK: <strong id="mResSizeUk">5.5</strong></span>
                                <span class="badge bg-light text-dark border px-3 py-2">Lòng: <strong id="mResSizeInsole">24.5 cm</strong></span>
                            </div>

                            <div class="alert alert-warning border-0 rounded-3 mt-3 mb-0 small text-start p-2" id="mResNoteText">
                                <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                                Vừa vặn thoải mái cho bàn chân 24.5 cm.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white p-3 px-4 d-flex justify-content-between">
                <a href="size-guide.php" class="small text-muted text-decoration-none fw-bold">
                    <i class="fa-solid fa-book-open text-warning me-1"></i> Xem Cẩm Nang Hướng Dẫn Đo Chân &amp; Bảng Quy Đổi Chi Tiết →
                </a>
                <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Đã Hiểu</button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes commentTargetGlow {
    0% { background-color: #fef08a !important; transform: scale(1.02); box-shadow: 0 0 25px rgba(234, 179, 8, 0.6); }
    50% { background-color: #fef9c3 !important; transform: scale(1.01); }
    100% { background-color: #ffffff !important; transform: scale(1); }
}
.comment-target-flash {
    animation: commentTargetGlow 3s ease-out forwards !important;
    border: 2px solid #eab308 !important;
}
</style>

<?php require_once 'includes/footer.php'; ?>