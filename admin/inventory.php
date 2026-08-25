<?php 
require_once __DIR__ . '/../config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// ========================================================
// 1. XỬ LÝ AJAX CẬP NHẬT TỒN KHO & XÓA BIẾN THỂ (KHÔNG LOAD LẠI TRANG)
// ========================================================
if (isset($_POST['ajax_update_stock'])) {
    header('Content-Type: application/json; charset=utf-8');
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $new_stock  = intval($_POST['stock_quantity'] ?? 0);
    if ($new_stock < 0) $new_stock = 0;

    if ($variant_id > 0) {
        $stmt = $conn->prepare("UPDATE product_variants SET stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $variant_id);
        if ($stmt->execute()) {
            $stmt->close();
            
            // Tính toán badge trạng thái mới
            $badge_html = '';
            $row_class  = '';
            if ($new_stock == 0) {
                $badge_html = '<span class="badge bg-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-1"></i>Hết hàng</span>';
                $row_class  = 'table-danger';
            } elseif ($new_stock <= 10) {
                $badge_html = '<span class="badge bg-warning text-dark shadow-sm"><i class="fa-solid fa-clock me-1"></i>Sắp hết (' . $new_stock . ')</span>';
                $row_class  = 'table-warning';
            } else {
                $badge_html = '<span class="badge bg-success shadow-sm"><i class="fa-solid fa-check me-1"></i>Đủ hàng (' . $new_stock . ')</span>';
                $row_class  = '';
            }

            echo json_encode([
                'success'    => true,
                'variant_id' => $variant_id,
                'new_stock'  => $new_stock,
                'badge_html' => $badge_html,
                'row_class'  => $row_class,
                'message'    => "Đã cập nhật tồn kho thành công: $new_stock đôi"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mã biến thể không hợp lệ.']);
    }
    exit();
}

if (isset($_POST['ajax_delete_variant'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên mới có quyền xóa biến thể size!']);
        exit();
    }
    $variant_id = intval($_POST['variant_id'] ?? 0);
    if ($variant_id > 0) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM cart_items WHERE variant_id = $variant_id");
            $conn->query("DELETE FROM product_variants WHERE id = $variant_id");
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xóa biến thể size khỏi kho thành công!']);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Không thể xóa biến thể: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mã biến thể không hợp lệ.']);
    }
    exit();
}

include_once 'includes/header.php'; 

// ========================================================
// 2. NẠP THƯƠNG HIỆU & XỬ LÝ BỘ LỌC
// ========================================================
$brands = [];
$b_res = $conn->query("SELECT id, name FROM brands WHERE status = 1 ORDER BY name ASC");
if ($b_res) {
    while ($b = $b_res->fetch_assoc()) {
        $brands[] = $b;
    }
}

$search_query = isset($_GET['search']) ? addslashes(trim($_GET['search'])) : '';
$filter_brand = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';

$where_clauses = ["1=1"];
if ($search_query !== '') {
    $where_clauses[] = "(p.name LIKE '%$search_query%' OR p.sku LIKE '%$search_query%')";
}
if ($filter_brand > 0) {
    $where_clauses[] = "p.brand_id = $filter_brand";
}
if ($stock_status === 'out_of_stock') {
    $where_clauses[] = "pv.stock_quantity = 0";
} elseif ($stock_status === 'low_stock') {
    $where_clauses[] = "(pv.stock_quantity > 0 AND pv.stock_quantity <= 10)";
} elseif ($stock_status === 'in_stock') {
    $where_clauses[] = "pv.stock_quantity > 10";
}

$where_sql = implode(' AND ', $where_clauses);

// ========================================================
// 3. SẮP XẾP VÀ TRUY VẤN
// ========================================================
$sort  = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? '');

$order_sql = "ORDER BY pv.stock_quantity ASC, p.id DESC, CAST(pv.size AS UNSIGNED) ASC";
if ($sort === 'name') {
    $dir = ($order === 'desc') ? 'DESC' : 'ASC';
    $order_sql = "ORDER BY p.name $dir, CAST(pv.size AS UNSIGNED) ASC";
} elseif ($sort === 'size') {
    $dir = ($order === 'desc') ? 'DESC' : 'ASC';
    $order_sql = "ORDER BY CAST(pv.size AS UNSIGNED) $dir, p.name ASC";
} elseif ($sort === 'stock') {
    $dir = ($order === 'desc') ? 'DESC' : 'ASC';
    $order_sql = "ORDER BY pv.stock_quantity $dir, p.name ASC";
} elseif ($sort === 'brand') {
    $dir = ($order === 'desc') ? 'DESC' : 'ASC';
    $order_sql = "ORDER BY b.name $dir, p.name ASC, CAST(pv.size AS UNSIGNED) ASC";
}

$sql = "SELECT pv.*, p.name AS product_name, p.sku, p.main_image, b.name AS brand_name
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE $where_sql
        $order_sql";
$result = $conn->query($sql);

// Helper tạo URL và Icon sắp xếp
function getSortUrl($column, $current_sort, $current_order, $search, $brand_id, $stock_status) {
    $next_order = 'asc';
    if ($current_sort === $column && $current_order === 'asc') {
        $next_order = 'desc';
    }
    
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if ($brand_id > 0) $params['brand_id'] = $brand_id;
    if ($stock_status !== '') $params['stock_status'] = $stock_status;
    $params['sort'] = $column;
    $params['order'] = $next_order;
    
    return '?' . http_build_query($params);
}

function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort === $column) {
        return ($current_order === 'asc') ? ' <i class="fa-solid fa-arrow-up-short-wide text-primary"></i>' : ' <i class="fa-solid fa-arrow-down-wide-short text-primary"></i>';
    }
    return ' <i class="fa-solid fa-sort text-muted" style="opacity: 0.35;"></i>';
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.stock-stepper {
    display: inline-flex;
    align-items: center;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.stock-stepper button {
    border: none;
    background: #f8fafc;
    color: #334155;
    width: 32px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.15s ease;
}
.stock-stepper button:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.stock-stepper input {
    width: 65px;
    height: 34px;
    border: none;
    text-align: center;
    font-weight: 700;
    color: #0f172a;
    outline: none;
}
.stock-stepper input:focus {
    background: #f1f5f9;
}
</style>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-warehouse me-2" style="color: var(--active-sage);"></i>Quản Lý Tồn Kho & Biến Thể Size
        </h4>
        <span class="text-muted small">Cập nhật nhanh tồn kho không cần tải lại trang, lọc theo thương hiệu & size chuẩn.</span>
    </div>
</div>

<!-- BỘ LỌC TÌM KIẾM NÂNG CAO -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <?php if ($sort): ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
        <?php endif; ?>

        <div class="col-12 col-md-4">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Tìm kiếm</label>
            <input type="text" name="search" class="form-control" placeholder="Tên sản phẩm, Mã SKU..." value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-tag me-1 text-primary"></i>Thương hiệu</label>
            <select name="brand_id" class="form-select">
                <option value="0">-- Tất cả thương hiệu --</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $filter_brand == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-boxes-stacked me-1 text-warning"></i>Tình trạng tồn kho</label>
            <select name="stock_status" class="form-select">
                <option value="">-- Tất cả tình trạng --</option>
                <option value="out_of_stock" <?= $stock_status == 'out_of_stock' ? 'selected' : '' ?>>❌ Hết hàng (0 đôi)</option>
                <option value="low_stock" <?= $stock_status == 'low_stock' ? 'selected' : '' ?>>⚠️ Sắp hết (≤ 10 đôi)</option>
                <option value="in_stock" <?= $stock_status == 'in_stock' ? 'selected' : '' ?>>✅ Còn nhiều (> 10 đôi)</option>
            </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark fw-bold flex-grow-1"><i class="fa-solid fa-filter me-1"></i>Lọc</button>
            <a href="inventory.php" class="btn btn-outline-secondary fw-bold" title="Đặt lại bộ lọc"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- BẢNG TỒN KHO -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>
                        <a href="<?= getSortUrl('name', $sort, $order, $search_query, $filter_brand, $stock_status) ?>" class="text-decoration-none text-dark fw-bold d-inline-flex align-items-center gap-1" title="Bấm để sắp xếp theo tên">
                            Sản Phẩm<?= getSortIcon('name', $sort, $order) ?>
                        </a>
                    </th>
                    <th>Mã SKU</th>
                    <th>
                        <a href="<?= getSortUrl('brand', $sort, $order, $search_query, $filter_brand, $stock_status) ?>" class="text-decoration-none text-dark fw-bold d-inline-flex align-items-center gap-1" title="Bấm để sắp xếp theo hãng">
                            Thương Hiệu<?= getSortIcon('brand', $sort, $order) ?>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="<?= getSortUrl('size', $sort, $order, $search_query, $filter_brand, $stock_status) ?>" class="text-decoration-none text-dark fw-bold d-inline-flex align-items-center gap-1" title="Bấm để sắp xếp theo size">
                            Size (EU)<?= getSortIcon('size', $sort, $order) ?>
                        </a>
                    </th>
                    <th class="text-center">Trạng Thái</th>
                    <th class="text-center" style="min-width: 220px;">
                        <a href="<?= getSortUrl('stock', $sort, $order, $search_query, $filter_brand, $stock_status) ?>" class="text-decoration-none text-dark fw-bold d-inline-flex align-items-center gap-1" title="Bấm để sắp xếp theo số lượng">
                            Số Lượng Tồn Kho<?= getSortIcon('stock', $sort, $order) ?>
                        </a>
                    </th>
                    <?php if ($user_role === 'admin'): ?>
                    <th class="text-end">Thao Tác</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                        $img_src = (strpos($row['main_image'], 'http') === 0) ? $row['main_image'] : '../' . $row['main_image'];
                        $stock   = intval($row['stock_quantity']);
                        $var_id  = intval($row['id']);
                        $row_cls = ($stock == 0) ? 'table-danger' : (($stock <= 10) ? 'table-warning' : '');
                        ?>
                        <tr id="variant-row-<?= $var_id ?>" class="<?= $row_cls ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= $img_src; ?>" class="rounded-3 border shadow-sm me-3" style="width: 48px; height: 48px; object-fit: cover;" onerror="this.src='../assets/images/default-shoe.png'">
                                    <strong class="text-dark" style="max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($row['product_name']); ?></strong>
                                </div>
                            </td>
                            <td><span class="text-secondary small fw-bold"><?= htmlspecialchars($row['sku']); ?></span></td>
                            <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($row['brand_name'] ?? 'Khác'); ?></span></td>
                            <td class="text-center">
                                <span class="badge bg-secondary fw-bold px-2 py-1 fs-6">EU <?= htmlspecialchars($row['size']); ?></span>
                            </td>
                            <td class="text-center" id="badge-status-<?= $var_id ?>">
                                <?php if ($stock == 0): ?>
                                    <span class="badge bg-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-1"></i>Hết hàng</span>
                                <?php elseif ($stock <= 10): ?>
                                    <span class="badge bg-warning text-dark shadow-sm"><i class="fa-solid fa-clock me-1"></i>Sắp hết (<?= $stock ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-success shadow-sm"><i class="fa-solid fa-check me-1"></i>Đủ hàng (<?= $stock ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <div class="stock-stepper">
                                        <button type="button" onclick="adjustStock(<?= $var_id ?>, -1)" title="Giảm 1 đôi"><i class="fa-solid fa-minus"></i></button>
                                        <input type="number" id="input-stock-<?= $var_id ?>" value="<?= $stock ?>" min="0" onchange="saveStockDirect(<?= $var_id ?>)">
                                        <button type="button" onclick="adjustStock(<?= $var_id ?>, 1)" title="Tăng 1 đôi"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-dark rounded-3 fw-bold px-2 py-1 shadow-sm" onclick="saveStockDirect(<?= $var_id ?>)" title="Lưu số lượng">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                    </button>
                                </div>
                            </td>
                            <?php if ($user_role === 'admin'): ?>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-3" onclick="deleteVariantAjax(<?= $var_id ?>, '<?= htmlspecialchars(addslashes($row['product_name']), ENT_QUOTES) ?>', '<?= $row['size'] ?>')" title="Xóa biến thể size này">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy biến thể nào trong kho phù hợp.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Cập nhật tồn kho theo nút Stepper +/-
function adjustStock(variantId, changeAmount) {
    const input = document.getElementById('input-stock-' + variantId);
    if (!input) return;
    let currentVal = parseInt(input.value) || 0;
    let newVal = currentVal + changeAmount;
    if (newVal < 0) newVal = 0;
    input.value = newVal;
    saveStockDirect(variantId);
}

// Lưu tồn kho trực tiếp qua AJAX không reload trang
function saveStockDirect(variantId) {
    const input = document.getElementById('input-stock-' + variantId);
    if (!input) return;
    const newStock = parseInt(input.value) || 0;

    const formData = new FormData();
    formData.append('ajax_update_stock', '1');
    formData.append('variant_id', variantId);
    formData.append('stock_quantity', newStock);

    fetch('inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Cập nhật badge trạng thái
            const badgeEl = document.getElementById('badge-status-' + variantId);
            if (badgeEl) {
                badgeEl.innerHTML = data.badge_html;
            }
            // Cập nhật màu dòng
            const rowEl = document.getElementById('variant-row-' + variantId);
            if (rowEl) {
                rowEl.classList.remove('table-danger', 'table-warning');
                if (data.row_class) {
                    rowEl.classList.add(data.row_class);
                }
            }

            // Toast nhẹ nhàng thông báo
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1200,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: data.message || 'Không thể cập nhật tồn kho.'
            });
        }
    })
    .catch(err => {
        console.error(err);
    });
}

// Xóa biến thể size qua AJAX không reload trang
function deleteVariantAjax(variantId, productName, size) {
    Swal.fire({
        title: 'Xác nhận xóa size?',
        html: `Bạn có chắc chắn muốn xóa biến thể <b>Size EU ${size}</b> của mẫu giày <b>${productName}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Ngay',
        cancelButtonText: 'Hủy',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_variant', '1');
            formData.append('variant_id', variantId);

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const rowEl = document.getElementById('variant-row-' + variantId);
                    if (rowEl) {
                        rowEl.style.transition = 'all 0.4s ease';
                        rowEl.style.opacity = '0';
                        rowEl.style.transform = 'translateX(50px)';
                        setTimeout(() => {
                            rowEl.remove();
                            const tbody = document.getElementById('inventoryTableBody');
                            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy biến thể nào trong kho.</td></tr>';
                            }
                        }, 400);
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Đã Xóa!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể xóa',
                        text: data.message || 'Có lỗi xảy ra khi xóa.'
                    });
                }
            })
            .catch(err => {
                console.error(err);
            });
        }
    });
}
</script>

    </div>
</div>
</body>
</html>
