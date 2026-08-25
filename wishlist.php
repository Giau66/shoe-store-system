<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Query wishlists JOIN products JOIN brands
$wishlist_items = [];
$res_w = $conn->query("
    SELECT w.id as wishlist_id, w.created_at, p.*, COALESCE(b.name, 'Chưa phân hãng') as brand_name
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE w.user_id = $user_id
    ORDER BY w.created_at DESC
");
if ($res_w) {
    while ($row = $res_w->fetch_assoc()) {
        $wishlist_items[] = $row;
    }
}

$page_title = "Yêu Thích Của Tôi";
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="fw-bold text-uppercase" style="color: var(--dark-luxury);">Sản Phẩm Yêu Thích</h2>
            <p class="text-muted">Các đôi giày bạn đã lưu lại vào bộ sưu tập cá nhân</p>
        </div>
    </div>

    <?php if (empty($wishlist_items)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
            <div class="card-body">
                <i class="fa-regular fa-heart fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="text-muted fw-bold">Danh sách yêu thích trống!</h5>
                <p class="text-muted small mb-4">Bạn chưa thả tim đôi giày nào. Hãy chọn cho mình những đôi giày ưng ý nhất!</p>
                <a href="all-products.php" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm">
                    <i class="fa-solid fa-cart-shopping me-2"></i>Khám Phá Giày Ngay
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3" id="wishlist-card-<?= $item['id']; ?>">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden bg-white product-card position-relative" onclick="if (!event.target.closest('button')) { window.location.href='product-detail.php?id=<?= $item['id']; ?>'; }" style="cursor: pointer;">
                        <!-- Icon Tim Đỏ Đã Yêu Thích -->
                        <button type="button" class="btn btn-light rounded-circle shadow-sm position-absolute top-0 end-0 m-3 z-3 p-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" onclick="event.stopPropagation(); toggleWishlist(<?= $item['id']; ?>, this)">
                            <i class="fa-solid fa-heart text-danger fs-5"></i>
                        </button>

                        <?php if (!empty($item['discount_percent']) && $item['discount_percent'] > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-3 z-2 font-monospace">-<?= $item['discount_percent']; ?>%</span>
                        <?php endif; ?>

                        <a href="product-detail.php?id=<?= $item['id']; ?>" class="d-block overflow-hidden">
                            <img src="<?= htmlspecialchars($item['main_image']); ?>" class="card-img-top" style="height: 220px; object-fit: cover;" alt="<?= htmlspecialchars($item['name']); ?>">
                        </a>

                        <div class="card-body d-flex flex-column p-3">
                            <small class="text-uppercase fw-bold text-muted mb-1" style="font-size: 11px;"><?= htmlspecialchars($item['brand_name']); ?></small>
                            <h6 class="card-title fw-bold text-truncate mb-2">
                                <a href="product-detail.php?id=<?= $item['id']; ?>" class="text-decoration-none text-dark hover-gold">
                                    <?= htmlspecialchars($item['name']); ?>
                                </a>
                            </h6>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-danger fs-6"><?= number_format($item['price'], 0, ',', '.'); ?>đ</span>
                                    <?php if (!empty($item['old_price']) && $item['old_price'] > $item['price']): ?>
                                        <small class="text-muted text-decoration-line-through d-block" style="font-size: 11px;"><?= number_format($item['old_price'], 0, ',', '.'); ?>đ</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleWishlist(productId, btn) {
    fetch('api/wishlist-toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('wishlist-card-' + productId);
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => card.remove(), 400);
            }
        } else if (data.message) {
            alert(data.message);
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>