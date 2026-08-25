<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';

// Kiểm tra đăng nhập: nếu chưa đăng nhập -> chuyển sang trang login.php
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để xem giỏ hàng của bạn.';
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Tự động khởi tạo bảng cart_items nếu CSDL cũ chưa có
@$conn->query("
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `user_prod_var` (`user_id`, `product_id`, `variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Lấy danh sách sản phẩm trong giỏ hàng từ CSDL của tài khoản này
$cart_res = $conn->query("
    SELECT c.id as item_id, c.variant_id, c.quantity, p.id as product_id, p.name, p.main_image, p.price, p.discount_percent, v.size, v.color, v.stock_quantity
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    JOIN product_variants v ON c.variant_id = v.id
    WHERE c.user_id = $user_id
    ORDER BY c.id DESC
");

$raw_cart = [];
$prod_ids = [];
if ($cart_res) {
    while ($row = $cart_res->fetch_assoc()) {
        $raw_cart[] = $row;
        $prod_ids[] = intval($row['product_id']);
    }
}

// Nạp map các Sự kiện Sale đang diễn ra
$sale_events_map = get_active_sale_events_map($conn, $prod_ids);

$cart = [];
$subtotal = 0;
foreach ($raw_cart as $row) {
    $pid = intval($row['product_id']);
    if (isset($sale_events_map[$pid]) && $sale_events_map[$pid]['has_sale']) {
        $row['final_price']     = floatval($sale_events_map[$pid]['sale_price']);
        $row['original_price']  = floatval($row['price']);
        $row['sale_event_name'] = $sale_events_map[$pid]['event_name'];
        $row['sale_color']      = $sale_events_map[$pid]['color_theme'];
        $row['sale_discount']   = $sale_events_map[$pid]['discount_percent'];
    } else {
        $row['final_price']     = floatval($row['price']);
        $row['original_price']  = floatval($row['price']);
        $row['sale_event_name'] = '';
        $row['sale_color']      = '';
        $row['sale_discount']   = 0;
    }
    $cart[] = $row;
}

// Cập nhật session cart để đồng bộ cho checkout
$_SESSION['cart'] = [];
foreach ($cart as $item) {
    $_SESSION['cart'][] = [
        'product_id' => $item['product_id'],
        'variant_id' => $item['variant_id'],
        'name'       => $item['name'],
        'image'      => $item['main_image'],
        'size'       => $item['size'],
        'color'      => $item['color'],
        'price'      => $item['final_price'],
        'quantity'   => $item['quantity']
    ];
}

$page_title = "Giỏ hàng của tôi";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Giỏ hàng</li>
        </ol>
    </nav>

    <h2 class="mb-4 text-dark font-weight-bold text-uppercase"><i class="fa-solid fa-cart-shopping me-2 text-warning"></i>Giỏ Hàng Của Bạn</h2>

    <!-- Live Toast Container for Cart -->
    <div id="cartLiveAlert" class="d-none"></div>

    <div id="cartMainContainer">
        <?php if (empty($cart)): ?>
            <div class="text-center py-5 shadow-sm bg-white rounded-4 border">
                <i class="fa-solid fa-cart-flatbed-empty text-muted mb-3" style="font-size: 80px;"></i>
                <h4 class="fw-bold text-dark">Giỏ hàng của bạn đang trống</h4>
                <p class="text-muted mb-4">Hãy nhanh tay chọn cho mình những đôi giày chính hãng ưng ý nhất!</p>
                <a href="all-products.php" class="btn btn-warning btn-lg fw-bold rounded-pill px-4 text-dark shadow">
                    <i class="fa-solid fa-shoe-prints me-2"></i>Khám Phá Sản Phẩm Ngay
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-3 rounded-4 overflow-hidden bg-white">
                        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-dark" id="cartItemsHeader">Danh sách sản phẩm (<span id="uniqueCartCount"><?= count($cart) ?></span>)</h5>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill fw-bold px-3" onclick="clearCartAjax()">
                                <i class="fa-solid fa-trash-can me-1"></i> Xóa tất cả
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" id="cartItemsTable">
                                    <thead class="table-light text-uppercase small">
                                        <tr>
                                            <th scope="col" class="ps-4" colspan="2">Sản phẩm</th>
                                            <th scope="col">Đơn giá</th>
                                            <th scope="col" style="width: 140px;">Số lượng</th>
                                            <th scope="col">Thành tiền</th>
                                            <th scope="col" class="pe-4 text-end">Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart as $item): 
                                            $line_total = $item['final_price'] * $item['quantity'];
                                            $subtotal += $line_total;
                                        ?>
                                        <tr id="cart_row_<?= $item['item_id'] ?>">
                                            <td class="ps-4" style="width: 85px;">
                                                <img src="<?= htmlspecialchars($item['main_image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid rounded-3 border" style="width: 75px; height: 75px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <h6 class="mb-1"><a href="product-detail.php?id=<?= $item['product_id'] ?>" class="text-dark text-decoration-none fw-bold"><?= htmlspecialchars($item['name']) ?></a></h6>
                                                <small class="text-muted d-block">Size: <strong class="text-dark"><?= htmlspecialchars($item['size']) ?></strong> <?= !empty($item['color']) ? '| Màu: ' . htmlspecialchars($item['color']) : '' ?></small>
                                                <small class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Còn hàng (Tồn: <?= $item['stock_quantity'] ?>)</small>
                                            </td>
                                            <td class="fw-bold">
                                                <div class="text-danger fs-6"><?= number_format($item['final_price'], 0, ',', '.') ?>đ</div>
                                                <?php if (!empty($item['sale_event_name'])): ?>
                                                    <div class="small text-muted text-decoration-line-through"><?= number_format($item['original_price'], 0, ',', '.') ?>đ</div>
                                                    <span class="badge" style="background: <?= htmlspecialchars($item['sale_color']) ?>; color: #fff; font-size: 10px;">
                                                        <i class="fa-solid fa-bolt me-1"></i><?= htmlspecialchars($item['sale_event_name']) ?> (-<?= $item['sale_discount'] ?>%)
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm rounded-3">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="changeCartItemQty(<?= $item['item_id'] ?>, -1, '<?= htmlspecialchars(addslashes($item['name']), ENT_QUOTES) ?>')">-</button>
                                                    <input type="number" class="form-control text-center fw-bold cart-qty-input" 
                                                           id="cart_qty_<?= $item['item_id'] ?>" 
                                                           value="<?= $item['quantity'] ?>" 
                                                           min="1" 
                                                           max="<?= $item['stock_quantity'] ?>" 
                                                           data-stock="<?= $item['stock_quantity'] ?>"
                                                           onchange="changeCartItemQty(<?= $item['item_id'] ?>, 0, '<?= htmlspecialchars(addslashes($item['name']), ENT_QUOTES) ?>', this.value)">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="changeCartItemQty(<?= $item['item_id'] ?>, 1, '<?= htmlspecialchars(addslashes($item['name']), ENT_QUOTES) ?>')">+</button>
                                                </div>
                                            </td>
                                            <td class="fw-bold text-danger fs-6" id="line_total_<?= $item['item_id'] ?>"><?= number_format($line_total, 0, ',', '.') ?>đ</td>
                                            <td class="pe-4 text-end">
                                                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle p-2 shadow-sm" 
                                                        onclick="deleteCartItemAjax(<?= $item['item_id'] ?>, '<?= htmlspecialchars(addslashes($item['name']), ENT_QUOTES) ?>')" title="Xóa">
                                                    <i class="fa-solid fa-xmark fs-6"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total summary -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-4 bg-white sticky-top" style="top: 20px;">
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold mb-4 border-bottom pb-3 text-uppercase text-dark"><i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>Tổng Đơn Hàng</h5>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Tạm tính tiền hàng:</span>
                                <span class="fw-bold fs-6 text-dark" id="cartSubtotalVal"><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Phí giao hàng:</span>
                                <span class="fw-bold text-success">Tính khi thanh toán</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4 align-items-center">
                                <span class="fw-bold fs-5 text-dark">Tổng tiền dự kiến:</span>
                                <span class="fw-bold fs-3 text-danger" id="cartTotalVal"><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                            </div>
                            
                            <a href="checkout.php" class="btn btn-warning w-100 py-3 fw-bold text-dark rounded-3 shadow text-uppercase">
                                 Tiến Hành Thanh Toán <i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                            <a href="all-products.php" class="btn btn-outline-dark w-100 mt-2 py-2 fw-bold rounded-3">
                                <i class="fa-solid fa-cart-plus me-1"></i> Tiếp tục chọn đồ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ==========================================
// LIVE AJAX CART ENGINE (KHÔNG LOAD LẠI TRANG)
// ==========================================

// 1. Cập nhật Badge số lượng giỏ hàng trên thanh Header
function updateHeaderCartBadge(totalQty) {
    const badge = document.querySelector('header a[href="cart.php"] .badge');
    if (badge) {
        if (totalQty > 0) {
            badge.textContent = totalQty;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// 2. Hiển thị thông báo Toast góc màn hình
function showCartToast(message, iconType = 'success') {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0'
            }
        });
        Toast.fire({
            icon: iconType,
            title: message
        });
    }
}

// 3. Thay đổi số lượng sản phẩm (Tăng / Giảm / Nhập trực tiếp)
let isUpdatingCart = false;
function changeCartItemQty(itemId, delta, itemName = 'sản phẩm', manualVal = null) {
    if (isUpdatingCart) return;

    const input = document.getElementById('cart_qty_' + itemId);
    if (!input) return;

    let currentVal = parseInt(input.value) || 1;
    let maxStock = parseInt(input.dataset.stock) || 999;
    let targetVal = (manualVal !== null) ? parseInt(manualVal) : (currentVal + delta);

    if (isNaN(targetVal) || targetVal < 1) {
        // Nếu giảm về 0 -> hỏi xóa sản phẩm
        deleteCartItemAjax(itemId, itemName);
        input.value = currentVal;
        return;
    }

    if (targetVal > maxStock) {
        showCartToast('Số lượng trong kho chỉ còn ' + maxStock + ' đôi!', 'warning');
        input.value = maxStock;
        targetVal = maxStock;
    } else {
        input.value = targetVal;
    }

    isUpdatingCart = true;
    const lineTotalEl = document.getElementById('line_total_' + itemId);
    if (lineTotalEl) lineTotalEl.style.opacity = '0.5';

    const formData = new FormData();
    formData.append('action', 'update_quantity');
    formData.append('item_id', itemId);
    formData.append('quantity', targetVal);
    formData.append('ajax', '1');

    fetch('cart-process.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (lineTotalEl && data.line_total_formatted) {
                lineTotalEl.textContent = data.line_total_formatted;
            }
            const subtotalEl = document.getElementById('cartSubtotalVal');
            const totalEl = document.getElementById('cartTotalVal');
            if (subtotalEl && data.subtotal_formatted) subtotalEl.textContent = data.subtotal_formatted;
            if (totalEl && data.subtotal_formatted) totalEl.textContent = data.subtotal_formatted;

            updateHeaderCartBadge(data.cart_count);
        } else {
            showCartToast(data.message || 'Không thể cập nhật số lượng!', 'error');
            input.value = currentVal;
        }
    })
    .catch(err => {
        console.error('Lỗi cập nhật giỏ hàng:', err);
    })
    .finally(() => {
        isUpdatingCart = false;
        if (lineTotalEl) lineTotalEl.style.opacity = '1';
    });
}

// 4. Xóa 1 sản phẩm khỏi giỏ hàng qua AJAX
function deleteCartItemAjax(itemId, itemName = 'sản phẩm này') {
    if (typeof Swal === 'undefined') {
        if (confirm('Bạn có chắc muốn xóa ' + itemName + ' khỏi giỏ hàng?')) {
            executeRemoveItem(itemId);
        }
        return;
    }

    Swal.fire({
        title: 'Xóa khỏi giỏ hàng?',
        html: `Bạn có chắc muốn xóa <b>${itemName}</b> khỏi giỏ hàng không?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Xóa ngay',
        cancelButtonText: 'Giữ lại',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'rounded-4 shadow-lg border-0',
            confirmButton: 'rounded-pill px-4 py-2 fw-bold',
            cancelButton: 'rounded-pill px-4 py-2 fw-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            executeRemoveItem(itemId);
        }
    });
}

function executeRemoveItem(itemId) {
    const row = document.getElementById('cart_row_' + itemId);
    if (row) {
        row.style.transition = 'all 0.35s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(40px)';
    }

    const formData = new FormData();
    formData.append('action', 'remove_item');
    formData.append('item_id', itemId);
    formData.append('ajax', '1');

    fetch('cart-process.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (row) row.remove();
            
            showCartToast('Đã xóa sản phẩm khỏi giỏ hàng!', 'success');
            updateHeaderCartBadge(data.cart_count);

            if (data.is_empty) {
                renderEmptyCart();
            } else {
                const countEl = document.getElementById('uniqueCartCount');
                if (countEl) countEl.textContent = data.unique_items;
                const subtotalEl = document.getElementById('cartSubtotalVal');
                const totalEl = document.getElementById('cartTotalVal');
                if (subtotalEl && data.subtotal_formatted) subtotalEl.textContent = data.subtotal_formatted;
                if (totalEl && data.subtotal_formatted) totalEl.textContent = data.subtotal_formatted;
            }
        }
    })
    .catch(err => {
        console.error('Lỗi xóa sản phẩm:', err);
    });
}

// 5. Xóa toàn bộ giỏ hàng qua AJAX
function clearCartAjax() {
    if (typeof Swal === 'undefined') {
        if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm trong giỏ hàng?')) {
            executeClearCart();
        }
        return;
    }

    Swal.fire({
        title: 'Xóa toàn bộ giỏ hàng?',
        text: 'Tất cả sản phẩm đã chọn sẽ bị xóa khỏi giỏ hàng của bạn.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Xóa tất cả',
        cancelButtonText: 'Hủy bỏ',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4 shadow-lg border-0',
            confirmButton: 'rounded-pill px-4 py-2 fw-bold',
            cancelButton: 'rounded-pill px-4 py-2 fw-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            executeClearCart();
        }
    });
}

function executeClearCart() {
    const formData = new FormData();
    formData.append('action', 'clear_cart');
    formData.append('ajax', '1');

    fetch('cart-process.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showCartToast('Đã làm trống giỏ hàng!', 'success');
            updateHeaderCartBadge(0);
            renderEmptyCart();
        }
    })
    .catch(err => {
        console.error('Lỗi làm trống giỏ hàng:', err);
    });
}

// 6. Hiển thị giao diện giỏ hàng trống khi xóa hết
function renderEmptyCart() {
    const container = document.getElementById('cartMainContainer');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5 shadow-sm bg-white rounded-4 border animate__animated animate__fadeIn">
                <i class="fa-solid fa-cart-flatbed-empty text-muted mb-3" style="font-size: 80px;"></i>
                <h4 class="fw-bold text-dark">Giỏ hàng của bạn đang trống</h4>
                <p class="text-muted mb-4">Hãy nhanh tay chọn cho mình những đôi giày chính hãng ưng ý nhất!</p>
                <a href="all-products.php" class="btn btn-warning btn-lg fw-bold rounded-pill px-4 text-dark shadow">
                    <i class="fa-solid fa-shoe-prints me-2"></i>Khám Phá Sản Phẩm Ngay
                </a>
            </div>
        `;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>