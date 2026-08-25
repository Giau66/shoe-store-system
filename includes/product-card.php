<?php
// Requires $p to be set (product row) and $wishlist_product_ids array
$is_wished = in_array($p['id'], $wishlist_product_ids ?? []);
$detail_url = 'product-detail.php?id=' . $p['id'];
$has_stock = !isset($p['total_stock']) || intval($p['total_stock']) > 0;
$avg_rating = isset($p['avg_rating']) ? round(floatval($p['avg_rating']), 1) : 5.0;
$sold_count = intval($p['sold_count'] ?? 0);

// Tính toán discount_percent an toàn và chính xác
$discount_pct = 0;
if (!empty($p['discount_percent']) && floatval($p['discount_percent']) > 0) {
    $discount_pct = round(floatval($p['discount_percent']));
} elseif (!empty($p['old_price']) && floatval($p['old_price']) > floatval($p['price'])) {
    $discount_pct = round(((floatval($p['old_price']) - floatval($p['price'])) / floatval($p['old_price'])) * 100);
}
?>
<div class="product-card h-100 position-relative <?= !$has_stock ? 'is-out-of-stock' : '' ?>" onclick="if (!event.target.closest('.wishlist-btn') && !event.target.closest('a')) { window.location.href='<?= $detail_url ?>'; }" style="cursor: pointer;">
    <div class="product-image-wrap">
        <!-- Badges góc trái (HOT, NEW, Đã mua, Đã xem, Gợi ý) -->
        <div class="product-badges-wrap">
            <?php if (empty($hide_hot_new)): ?>
                <?php if (!empty($p['is_hot']) && $p['is_hot'] == 1): ?>
                    <span class="badge-hot shadow-sm"><i class="fa-solid fa-fire me-1"></i>HOT</span>
                <?php endif; ?>
                <?php if (!empty($p['is_new']) && $p['is_new'] == 1 && empty($p['is_hot'])): ?>
                    <span class="badge-new shadow-sm">NEW</span>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($p['is_user_purchased'])): ?>
                <span class="badge bg-success shadow-sm px-2 py-1" style="font-size: 10px; border-radius: 6px; letter-spacing: 0.3px;"><i class="fa-solid fa-bag-shopping me-1"></i>Đã Mua</span>
            <?php elseif (!empty($p['is_user_viewed'])): ?>
                <span class="badge bg-primary text-white shadow-sm px-2 py-1" style="font-size: 10px; border-radius: 6px; letter-spacing: 0.3px;"><i class="fa-solid fa-eye me-1"></i>Đã Xem</span>
            <?php elseif (!empty($p['is_similar_recommended'])): ?>
                <span class="badge bg-warning text-dark shadow-sm px-2 py-1" style="font-size: 10px; border-radius: 6px; letter-spacing: 0.3px;"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Gợi Ý</span>
            <?php endif; ?>
        </div>

        <!-- Cụm góc phải: Nút Tim ở trên cùng, % Giảm giá nằm ngay DƯỚI nút Tim -->
        <div class="product-top-right-wrap">
            <button class="wishlist-btn <?= $is_wished ? 'active' : '' ?>" data-id="<?= $p['id'] ?>" title="<?= $is_wished ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' ?>" onclick="event.stopPropagation();">
                <i class="fa-<?= $is_wished ? 'solid' : 'regular' ?> fa-heart"></i>
            </button>

            <?php if ($discount_pct > 0): ?>
                <span class="discount-badge shadow-sm"><i class="fa-solid fa-bolt me-1"></i>-<?= $discount_pct ?>%</span>
            <?php endif; ?>
        </div>

        <?php if (!$has_stock): ?>
            <div class="out-of-stock-overlay">
                <span class="badge bg-secondary px-3 py-2 text-uppercase fw-bold">Tạm Hết Hàng</span>
            </div>
        <?php endif; ?>
        
        <a href="<?= $detail_url ?>" class="product-img-link">
            <img src="<?= htmlspecialchars($p['main_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" class="product-main-img">
        </a>
    </div>

    <div class="product-info">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="brand-name"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></span>
            
            <!-- Trạng thái Còn hàng / Hết hàng -->
            <?php if ($has_stock): ?>
                <span class="badge-stock-in small text-success fw-bold d-inline-flex align-items-center">
                    <i class="fa-solid fa-circle-check me-1"></i> Còn hàng
                </span>
            <?php else: ?>
                <span class="badge-stock-out small text-danger fw-bold d-inline-flex align-items-center">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Hết hàng
                </span>
            <?php endif; ?>
        </div>

        <a href="<?= $detail_url ?>" class="product-title" title="<?= htmlspecialchars($p['name']) ?>">
            <?= htmlspecialchars($p['name']) ?>
        </a>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="product-rating-stars small">
                <i class="fa-solid fa-star text-warning"></i>
                <span class="fw-bold text-dark rating-num ms-1"><?= number_format($avg_rating, 1) ?></span>
            </div>
            <?php if ($sold_count > 0): ?>
                <span class="text-muted small sold-count">Đã bán <?= number_format($sold_count) ?></span>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-baseline justify-content-between flex-wrap mt-auto pt-1">
            <div class="price-box d-flex align-items-baseline">
                <span class="product-price"><?= number_format($p['price'], 0, ',', '.') ?>đ</span>
                <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price']): ?>
                    <span class="old-price ms-2"><?= number_format($p['old_price'], 0, ',', '.') ?>đ</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
