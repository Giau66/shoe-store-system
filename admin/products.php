<?php 
require_once __DIR__ . '/../config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// ========================================================
// 1. AJAX: ĐỔI TRẠNG THÁI ẨN / HIỆN SẢN PHẨM (ZERO RELOAD)
// ========================================================
if (isset($_POST['ajax_toggle_product_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid = intval($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        $check = $conn->query("SELECT status, name FROM products WHERE id = $pid LIMIT 1");
        if ($check && $row = $check->fetch_assoc()) {
            $new_st = ($row['status'] == 1) ? 0 : 1;
            $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->bind_param('ii', $new_st, $pid);
            $stmt->execute();
            $stmt->close();

            $badge_html = ($new_st == 1) 
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Đang hiện</span>'
                : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-eye-slash me-1"></i>Đang ẩn</span>';

            echo json_encode([
                'success' => true,
                'new_status' => $new_st,
                'badge_html' => $badge_html,
                'message' => ($new_st == 1) ? "Đã hiển thị sản phẩm \"{$row['name']}\" trên web!" : "Đã tạm ẩn sản phẩm \"{$row['name']}\" khỏi trang chủ & cửa hàng!"
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
    exit;
}

// ========================================================
// 2. AJAX: XÓA SẢN PHẨM KHÔNG LOAD LẠI TRANG
// ========================================================
if (isset($_POST['ajax_delete_product'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên (Admin) mới có quyền xóa sản phẩm!']);
        exit();
    }
    
    $del_id = intval($_POST['delete_product_id'] ?? 0);
    if ($del_id > 0) {
        $conn->begin_transaction();
        try {
            // Xóa toàn bộ liên kết hình ảnh, biến thể, giỏ hàng, yêu thích, bình luận
            $conn->query("DELETE FROM product_images WHERE product_id = $del_id");
            $conn->query("DELETE FROM product_variants WHERE product_id = $del_id");
            $conn->query("DELETE FROM cart_items WHERE product_id = $del_id");
            $conn->query("DELETE FROM wishlists WHERE product_id = $del_id");
            $conn->query("DELETE FROM event_products WHERE product_id = $del_id");
            $conn->query("DELETE FROM comments WHERE product_id = $del_id");
            
            $del_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $del_stmt->bind_param('i', $del_id);
            $del_stmt->execute();
            $del_stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi hệ thống thành công!']);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.']);
    }
    exit();
}

include_once 'includes/header.php'; 

$msg = '';
$err = '';

// Nạp Flash Message từ trang thêm / sửa sản phẩm
if (isset($_SESSION['flash_success'])) {
    $msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Lấy danh mục cho Filter (phân cấp và nhóm theo type)
$all_cats = [];
$res_cat = $conn->query("SELECT id, name, type, parent_id FROM categories ORDER BY type ASC, parent_id ASC, name ASC");
if ($res_cat) {
    while($c = $res_cat->fetch_assoc()) {
        $all_cats[] = $c;
    }
}
$cat_tree = ['giay' => [], 'dep' => []];
foreach ($all_cats as $c) {
    if ($c['parent_id'] == 0 || $c['parent_id'] === null) {
        $c['children'] = [];
        $cat_tree[$c['type']][$c['id']] = $c;
    }
}
foreach ($all_cats as $c) {
    if ($c['parent_id'] > 0) {
        if (isset($cat_tree[$c['type']][$c['parent_id']])) {
            $cat_tree[$c['type']][$c['parent_id']]['children'][] = $c;
        }
    }
}

$brands = [];
$res_brand = $conn->query("SELECT id, name FROM brands ORDER BY name ASC");
if ($res_brand) while($b = $res_brand->fetch_assoc()) $brands[] = $b;

// Lọc & Tìm kiếm
$where_clauses = ["1=1"];
$search_query = isset($_GET['search']) ? addslashes(trim($_GET['search'])) : '';
$filter_cat = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$filter_brand = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

if ($search_query !== '') {
    $where_clauses[] = "(p.sku LIKE '%$search_query%' OR p.name LIKE '%$search_query%' OR c.name LIKE '%$search_query%' OR b.name LIKE '%$search_query%')";
}
if ($filter_cat > 0) {
    $cat_ids = [$filter_cat];
    $child_res = $conn->query("SELECT id FROM categories WHERE parent_id = $filter_cat");
    if ($child_res && $child_res->num_rows > 0) {
        while ($cr = $child_res->fetch_assoc()) {
            $cat_ids[] = $cr['id'];
        }
    }
    $ids_str = implode(',', $cat_ids);
    $where_clauses[] = "p.category_id IN ($ids_str)";
}
if ($filter_brand > 0) {
    $where_clauses[] = "p.brand_id = $filter_brand";
}
if ($filter_type === 'giay' || $filter_type === 'dep') {
    $where_clauses[] = "c.type = '$filter_type'";
}
if ($filter_gender === 'nam' || $filter_gender === 'nu' || $filter_gender === 'unisex') {
    $where_clauses[] = "c.gender = '$filter_gender'";
}
if ($filter_status === '1') {
    $where_clauses[] = "p.status = 1";
} elseif ($filter_status === '0') {
    $where_clauses[] = "p.status = 0";
}

$where_sql = implode(' AND ', $where_clauses);

// TRUY VẤN DANH SÁCH SẢN PHẨM
$sql = "SELECT p.*, b.name AS brand_name, c.name AS category_name,
               COALESCE((SELECT SUM(stock_quantity) FROM product_variants WHERE product_id = p.id), 0) AS total_stock
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where_sql
        ORDER BY p.id DESC";
$result = $conn->query($sql);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-boxes-stacked me-2" style="color: var(--active-sage);"></i>Quản Lý Sản Phẩm
        </h4>
        <span class="text-muted small">Quản lý sản phẩm, bật/tắt ẩn hiện tức thì (không load trang), giá bán và tồn kho.</span>
    </div>
    <a href="product-add.php" class="btn fw-bold rounded-3 px-3 shadow-sm" style="background-color: var(--active-sage); color: white;">
        <i class="fa-solid fa-plus me-1"></i> Thêm Sản Phẩm Mới
    </a>
</div>

<!-- THÔNG BÁO FLASH KHI LƯU TỪ FORM -->
<?php if ($msg): ?><div class="alert alert-success shadow-sm fw-bold"><i class="fa-solid fa-circle-check me-2"></i><?= $msg; ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger shadow-sm fw-bold"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $err; ?></div><?php endif; ?>

<!-- BỘ LỌC -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm (Tên, SKU)</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Từ khóa..." value="<?= htmlspecialchars($search_query) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Trạng thái</label>
            <select name="status" class="form-select form-select-sm">
                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>-- Tất cả --</option>
                <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>✅ Đang hiện</option>
                <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>🔒 Đang ẩn</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Danh mục</label>
            <select name="category_id" class="form-select form-select-sm">
                <option value="0">-- Tất cả --</option>
                <?php foreach($cat_tree as $ctype => $parents): ?>
                    <?php if (count($parents) > 0): ?>
                        <optgroup label="<?= $ctype === 'giay' ? 'GIÀY' : 'DÉP' ?>">
                        <?php foreach($parents as $pcat): ?>
                            <option value="<?= $pcat['id'] ?>" <?= $filter_cat == $pcat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pcat['name']) ?></option>
                            <?php foreach($pcat['children'] as $ccat): ?>
                                <option value="<?= $ccat['id'] ?>" <?= $filter_cat == $ccat['id'] ? 'selected' : '' ?>>&nbsp;&nbsp;↳ <?= htmlspecialchars($ccat['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Thương hiệu</label>
            <select name="brand_id" class="form-select form-select-sm">
                <option value="0">-- Tất cả --</option>
                <?php foreach($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $filter_brand == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Loại &amp; Giới tính</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">-- Tất cả loại --</option>
                <option value="giay" <?= $filter_type === 'giay' ? 'selected' : '' ?>>Giày</option>
                <option value="dep" <?= $filter_type === 'dep' ? 'selected' : '' ?>>Dép</option>
            </select>
        </div>
        <div class="col-12 col-md-1">
            <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Lọc</button>
        </div>
    </form>
</div>

<!-- BẢNG SẢN PHẨM -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>Sản Phẩm</th>
                    <th>Mã SKU</th>
                    <th>Danh Mục / Hãng</th>
                    <th>Giá Bán</th>
                    <th>Khuyến Mãi</th>
                    <th>Tổng Tồn</th>
                    <th>Ẩn / Hiện (Nhấn để đổi)</th>
                    <th class="text-end">Thao Tác</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                        $pid = intval($row['id']);
                        $img_src = (strpos($row['main_image'], 'http') === 0) ? $row['main_image'] : '../' . $row['main_image'];
                        ?>
                        <tr id="product-row-<?= $pid; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= $img_src; ?>" class="rounded-3 border shadow-sm me-3" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='../assets/images/default-shoe.png'">
                                    <div>
                                        <strong class="text-dark d-block" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($row['name']); ?></strong>
                                        <div class="d-flex gap-1 mt-1">
                                            <?php if ($row['is_hot']): ?><span class="badge bg-danger-subtle text-danger" style="font-size: 10px;">HOT</span><?php endif; ?>
                                            <?php if ($row['is_new']): ?><span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">NEW</span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-secondary small fw-bold"><?= htmlspecialchars($row['sku']); ?></span></td>
                            <td>
                                <span class="badge bg-light text-dark border d-block mb-1"><?= htmlspecialchars($row['brand_name'] ?? '---'); ?></span>
                                <small class="text-muted"><?= htmlspecialchars($row['category_name'] ?? '---'); ?></small>
                            </td>
                            <td>
                                <?php if ($row['old_price'] > $row['price']): ?>
                                    <small class="text-muted text-decoration-line-through d-block"><?= number_format($row['old_price'], 0, ',', '.'); ?>đ</small>
                                <?php endif; ?>
                                <strong class="text-danger fs-6"><?= number_format($row['price'], 0, ',', '.'); ?>đ</strong>
                            </td>
                            <td>
                                <?php if ($row['discount_percent'] > 0): ?>
                                    <span class="badge bg-danger fw-bold">-<?= $row['discount_percent']; ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted small">Không</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $stock = $row['total_stock']; ?>
                                <?php if ($stock == 0): ?>
                                    <span class="badge bg-danger">Hết hàng</span>
                                <?php elseif ($stock <= 10): ?>
                                    <span class="badge bg-warning text-dark"><?= $stock; ?> đôi (Thấp)</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $stock; ?> đôi</span>
                                <?php endif; ?>
                            </td>
                            <td id="status-cell-<?= $pid; ?>">
                                <button type="button" class="btn p-0 border-0" onclick="toggleProductStatus(<?= $pid; ?>)" title="Nhấp để chuyển đổi Ẩn / Hiện sản phẩm tức thì">
                                    <?php if ($row['status'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Đang hiện</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-eye-slash me-1"></i>Đang ẩn</span>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="product-edit.php?id=<?= $pid; ?>" class="btn btn-outline-primary btn-sm rounded-3 me-1" title="Chỉnh sửa sản phẩm">
                                    <i class="fa-solid fa-pen"></i> Sửa
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-3" onclick="deleteProductAjax(<?= $pid; ?>, '<?= htmlspecialchars(addslashes($row['name']), ENT_QUOTES); ?>')" title="Xóa không load lại trang">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">Không tìm thấy sản phẩm nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

// BẬT / TẮT ẨN HIỆN SẢN PHẨM KHÔNG LOAD LẠI TRANG (LIVE AJAX)
function toggleProductStatus(productId) {
    const formData = new FormData();
    formData.append('ajax_toggle_product_status', '1');
    formData.append('product_id', productId);

    fetch('products.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const cell = document.getElementById('status-cell-' + productId);
            if (cell) {
                cell.innerHTML = `
                    <button type="button" class="btn p-0 border-0" onclick="toggleProductStatus(${productId})" title="Nhấp để chuyển đổi Ẩn / Hiện sản phẩm tức thì">
                        ${data.badge_html}
                    </button>
                `;
            }
            Toast.fire({
                icon: data.new_status == 1 ? 'success' : 'info',
                title: data.message
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ.' });
    });
}

// XÓA SẢN PHẨM KHÔNG LOAD LẠI TRANG (LIVE AJAX)
function deleteProductAjax(productId, productName) {
    Swal.fire({
        title: 'Xác nhận xóa sản phẩm?',
        html: `Bạn có chắc chắn muốn xóa mẫu giày <b>${productName}</b>?<br><small class="text-danger">Toàn bộ 6 góc ảnh và biến thể size sẽ bị xóa theo.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Ngay',
        cancelButtonText: 'Hủy',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_product', '1');
            formData.append('delete_product_id', productId);

            fetch('products.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('product-row-' + productId);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(40px)';
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.getElementById('productTableBody');
                            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">Không tìm thấy sản phẩm nào.</td></tr>';
                            }
                        }, 400);
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể xóa',
                        text: data.message || 'Có lỗi xảy ra khi xóa sản phẩm.'
                    });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    text: 'Không thể kết nối đến máy chủ.'
                });
            });
        }
    });
}
</script>

    </div>
</div>
</body>
</html>