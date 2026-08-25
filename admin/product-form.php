<?php
ob_start(); // Buffer tất cả output — giúp header() redirect luôn hoạt động dù hosting đã gửi output
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit    = ($product_id > 0);

$msg = '';
$err = '';

// NẠP DANH SÁCH THƯƠNG HIỆU VÀ DANH MỤC
$brands     = $conn->query("SELECT * FROM brands WHERE status = 1 ORDER BY name ASC");
$categories = $conn->query("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");

// DỮ LIỆU MẶC ĐỊNH
$product = [
    'sku' => '', 'name' => '', 'brand_id' => 0, 'category_id' => 0,
    'cost_price' => 0, 'old_price' => 0, 'discount_percent' => 0, 'price' => 0,
    'main_image' => '', 'description' => ''
];
$sub_images = [2 => '', 3 => '', 4 => '', 5 => '', 6 => ''];
$size_stocks = [];
for ($sz = 36; $sz <= 44; $sz++) {
    $size_stocks[$sz] = 10;
}

// CHẾ ĐỘ SỬA -> LẤY TỪ CSDL
if ($is_edit) {
    $stmt_p = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt_p->bind_param("i", $product_id);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    if ($res_p && $res_p->num_rows > 0) {
        $product = $res_p->fetch_assoc();
        $stmt_p->close();

        $var_res = $conn->query("SELECT size, stock_quantity FROM product_variants WHERE product_id = $product_id");
        if ($var_res) {
            while ($v = $var_res->fetch_assoc()) {
                $size_stocks[intval($v['size'])] = intval($v['stock_quantity']);
            }
        }

        $img_res = $conn->query("SELECT image_url, sort_order FROM product_images WHERE product_id = $product_id ORDER BY sort_order ASC");
        if ($img_res) {
            while ($img = $img_res->fetch_assoc()) {
                $order = intval($img['sort_order']);
                if ($order >= 2 && $order <= 6) {
                    $sub_images[$order] = $img['image_url'];
                }
            }
        }
    } else {
        $stmt_p->close();
        include_once 'includes/header.php';
        echo "<div class='alert alert-danger fw-bold m-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Sản phẩm không tồn tại!</div>";
        echo "</div></div></body></html>";
        exit();
    }
}

// ===== XỬ LÝ SUBMIT FORM =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đọc dữ liệu từ form
    $sku              = trim($_POST['sku'] ?? '');
    $name             = trim($_POST['name'] ?? '');
    $brand_id         = intval($_POST['brand_id'] ?? 0);
    $category_id      = intval($_POST['category_id'] ?? 0);
    $cost_price       = floatval($_POST['cost_price'] ?? 0);
    $old_price        = floatval($_POST['old_price'] ?? 0);
    $price            = floatval($_POST['price'] ?? 0);
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $description      = trim($_POST['description'] ?? '');

    // Tự động điền giá nếu thiếu
    if ($old_price > 0 && $price <= 0) {
        $price = ($discount_percent > 0) ? round($old_price * (1 - $discount_percent / 100)) : $old_price;
    } elseif ($price > 0 && $old_price <= 0) {
        $old_price = $price;
    }

    // Tự sinh SKU nếu để trống
    if (empty($sku)) {
        $sku = 'SHOE-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    }

    // Xử lý upload 6 góc ảnh
    $upload_dir = __DIR__ . '/../uploads/';
    @mkdir($upload_dir, 0777, true);

    $main_image     = $product['main_image'];
    $new_sub_images = $sub_images;

    for ($i = 1; $i <= 6; $i++) {
        $uploaded_path = '';
        if (isset($_FILES["image_file_$i"]) && $_FILES["image_file_$i"]['error'] == 0) {
            $fname = time() . "_angle{$i}_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES["image_file_$i"]["name"]));
            if (@move_uploaded_file($_FILES["image_file_$i"]["tmp_name"], $upload_dir . $fname)) {
                $uploaded_path = 'uploads/' . $fname;
            }
        } elseif (!empty($_POST["image_url_$i"])) {
            $uploaded_path = trim($_POST["image_url_$i"]);
        }
        if ($uploaded_path !== '') {
            if ($i == 1) $main_image = $uploaded_path;
            else $new_sub_images[$i] = $uploaded_path;
        }
    }

    // Ảnh fallback nếu trống
    if (empty($main_image)) {
        $main_image = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800';
    }

    // Giữ lại dữ liệu đã nhập khi có lỗi (không bị mất)
    $product['sku']              = $sku;
    $product['name']             = $name;
    $product['brand_id']         = $brand_id;
    $product['category_id']      = $category_id;
    $product['cost_price']       = $cost_price;
    $product['old_price']        = $old_price;
    $product['price']            = $price;
    $product['discount_percent'] = $discount_percent;
    $product['description']      = $description;
    $product['main_image']       = $main_image;
    for ($sz = 36; $sz <= 44; $sz++) {
        $size_stocks[$sz] = isset($_POST["stock_size_$sz"]) ? intval($_POST["stock_size_$sz"]) : 10;
    }
    $sub_images = $new_sub_images;

    // ===== VALIDATION =====
    if (empty($name)) {
        $err = "Vui lòng nhập Tên sản phẩm!";
    } elseif ($price <= 0) {
        $err = "Vui lòng nhập Giá Bán (phải lớn hơn 0)!";
    } else {
        // Kiểm tra trùng SKU
        if ($is_edit) {
            $stmt_sku = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1");
            $stmt_sku->bind_param("si", $sku, $product_id);
        } else {
            $stmt_sku = $conn->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
            $stmt_sku->bind_param("s", $sku);
        }
        $stmt_sku->execute();
        $is_sku_dup = ($stmt_sku->get_result()->num_rows > 0);
        $stmt_sku->close();

        if ($is_sku_dup) {
            $err = "Mã SKU '<b>" . htmlspecialchars($sku) . "</b>' đã tồn tại! Vui lòng dùng SKU khác.";
        } else {
            // Đảm bảo brand_id & category_id hợp lệ
            $chk_b = $conn->query("SELECT id FROM brands WHERE id = $brand_id LIMIT 1");
            if (!$chk_b || $chk_b->num_rows == 0) {
                $fb = $conn->query("SELECT id FROM brands ORDER BY id ASC LIMIT 1")->fetch_assoc();
                $brand_id = $fb ? intval($fb['id']) : 1;
            }
            $chk_c = $conn->query("SELECT id FROM categories WHERE id = $category_id LIMIT 1");
            if (!$chk_c || $chk_c->num_rows == 0) {
                $fc = $conn->query("SELECT id FROM categories ORDER BY id ASC LIMIT 1")->fetch_assoc();
                $category_id = $fc ? intval($fc['id']) : 1;
            }

            // Sinh slug
            $slug = mb_strtolower($name, 'UTF-8');
            $slug = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $slug);
            $slug = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $slug);
            $slug = preg_replace('/[ìíịỉĩ]/u', 'i', $slug);
            $slug = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $slug);
            $slug = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $slug);
            $slug = preg_replace('/[ỳýỵỷỹ]/u', 'y', $slug);
            $slug = preg_replace('/[đ]/u', 'd', $slug);
            $slug = trim(preg_replace('/[\s-]+/', '-', preg_replace('/[^a-z0-9\s-]/', '', $slug)), '-');
            if (empty($slug)) $slug = 'product-' . time();

            // Tránh trùng slug
            if ($is_edit) {
                $stmt_sl = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1");
                $stmt_sl->bind_param("si", $slug, $product_id);
            } else {
                $stmt_sl = $conn->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
                $stmt_sl->bind_param("s", $slug);
            }
            $stmt_sl->execute();
            if ($stmt_sl->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $stmt_sl->close();

            // ===== LƯU VÀO DATABASE (TRANSACTION) =====
            $conn->begin_transaction();
            try {
                if ($is_edit) {
                    $st = $conn->prepare("UPDATE products SET sku=?, name=?, slug=?, brand_id=?, category_id=?, cost_price=?, old_price=?, price=?, discount_percent=?, main_image=?, description=? WHERE id=?");
                    if (!$st) throw new RuntimeException($conn->error);
                    $st->bind_param("sssiidddissi", $sku, $name, $slug, $brand_id, $category_id, $cost_price, $old_price, $price, $discount_percent, $main_image, $description, $product_id);
                    if (!$st->execute()) throw new RuntimeException($st->error);
                    $st->close();
                } else {
                    $st = $conn->prepare("INSERT INTO products (sku,name,slug,brand_id,category_id,cost_price,old_price,price,discount_percent,main_image,description,is_hot,is_new,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,0,1,1)");
                    if (!$st) throw new RuntimeException($conn->error);
                    $st->bind_param("sssiidddiss", $sku, $name, $slug, $brand_id, $category_id, $cost_price, $old_price, $price, $discount_percent, $main_image, $description);
                    if (!$st->execute()) throw new RuntimeException($st->error);
                    $product_id = $conn->insert_id;
                    $st->close();
                }

                if ($product_id < 1) throw new RuntimeException("Không xác định được ID sản phẩm.");

                // Lưu ảnh phụ
                $conn->query("DELETE FROM product_images WHERE product_id = $product_id");
                $st_img = $conn->prepare("INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?,?,?)");
                if ($st_img) {
                    foreach ($new_sub_images as $so => $iu) {
                        $iu = trim($iu);
                        if (!empty($iu)) {
                            $st_img->bind_param("isi", $product_id, $iu, $so);
                            $st_img->execute();
                        }
                    }
                    $st_img->close();
                }

                // Lưu tồn kho (ON DUPLICATE KEY để tránh lỗi FK với giỏ hàng)
                $st_var = $conn->prepare("INSERT INTO product_variants (product_id, size, stock_quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE stock_quantity=VALUES(stock_quantity)");
                if ($st_var) {
                    for ($sz = 36; $sz <= 44; $sz++) {
                        $sv  = isset($_POST["stock_size_$sz"]) ? intval($_POST["stock_size_$sz"]) : 10;
                        $szs = (string)$sz;
                        $st_var->bind_param("isi", $product_id, $szs, $sv);
                        $st_var->execute();
                    }
                    $st_var->close();
                }

                $conn->commit();

                // Lưu flash message và redirect
                $_SESSION['flash_success'] = $is_edit
                    ? "Đã cập nhật sản phẩm <b>" . htmlspecialchars($name) . "</b> thành công!"
                    : "Đã thêm sản phẩm <b>" . htmlspecialchars($name) . "</b> vào hệ thống!";

                // Xóa buffer và redirect — ob_start() đảm bảo header() luôn hoạt động
                ob_end_clean();
                header("Location: products.php");
                exit();

            } catch (Throwable $db_e) {
                $conn->rollback();
                $err = "Lỗi lưu CSDL: " . $db_e->getMessage();
            }
        }
    }
}
// ===== KẾT THÚC XỬ LÝ POST =====

include_once 'includes/header.php';
?>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid <?= $is_edit ? 'fa-pen-to-square' : 'fa-plus'; ?> me-2" style="color: var(--active-sage);"></i>
            <?= $is_edit ? 'Chỉnh Sửa Sản Phẩm #' . $product_id : 'Thêm Sản Phẩm Mới'; ?>
        </h4>
        <span class="text-muted small">Cập nhật hình ảnh, thông số kỹ thuật, giá và tồn kho theo từng Size.</span>
    </div>
    <a href="products.php" class="btn btn-outline-secondary fw-bold rounded-3 px-3 shadow-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay Lại Danh Sách
    </a>
</div>

<!-- THÔNG BÁO -->
<?php if ($err): ?>
<div class="alert alert-danger shadow-sm fw-bold"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $err; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="mb-5" id="productForm">
    <div class="row g-4">
        <!-- CỘT TRÁI -->
        <div class="col-12 col-lg-7">
            <!-- THÔNG TIN CƠ BẢN -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--dark-slate);">
                    <i class="fa-solid fa-circle-info me-2 text-primary"></i>Thông Tin Cơ Bản
                </h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small text-muted">Mã SKU</label>
                        <input type="text" name="sku" class="form-control fw-bold" placeholder="VD: NIK-AF1-01" value="<?= htmlspecialchars($product['sku']); ?>">
                        <small class="text-muted">Để trống sẽ tự sinh SKU</small>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small text-muted">Tên Sản Phẩm / Mẫu Giày <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control fw-bold" placeholder="VD: Giày Nike Air Force 1 '07..." value="<?= htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small text-muted">Hãng / Thương Hiệu</label>
                        <select name="brand_id" class="form-select">
                            <?php
                            // Reset result pointer
                            if ($brands) {
                                $brands->data_seek(0);
                                while ($b = $brands->fetch_assoc()) {
                                    $sel = ($product['brand_id'] == $b['id']) ? 'selected' : '';
                                    echo "<option value=\"{$b['id']}\" $sel>" . htmlspecialchars($b['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small text-muted">Danh Mục Sản Phẩm</label>
                        <select name="category_id" class="form-select">
                            <?php
                            if ($categories) {
                                $categories->data_seek(0);
                                while ($c = $categories->fetch_assoc()) {
                                    $sel = ($product['category_id'] == $c['id']) ? 'selected' : '';
                                    echo "<option value=\"{$c['id']}\" $sel>" . htmlspecialchars($c['name']) . " (" . strtoupper($c['type'] ?? 'giay') . ")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- GIÁ BÁN -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--dark-slate);">
                    <i class="fa-solid fa-tag me-2 text-success"></i>Giá Bán &amp; Khuyến Mãi
                </h5>
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold small text-muted">Giá Vốn (Gốc) VNĐ</label>
                        <input type="number" name="cost_price" id="cost_price" class="form-control" min="0" placeholder="0" value="<?= $product['cost_price']; ?>">
                        <small class="text-muted" style="font-size: 11px;">Tính lãi / lỗ trong báo cáo</small>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold small text-dark">Giá Cũ (Niêm Yết) VNĐ</label>
                        <input type="number" name="old_price" id="old_price" class="form-control fw-bold" min="0" placeholder="VD: 1500000" value="<?= $product['old_price'] > 0 ? $product['old_price'] : ''; ?>">
                        <small class="text-muted" style="font-size: 11px;">Giá gạch ngang (nếu có)</small>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold small text-muted">Giảm Giá (%)</label>
                        <div class="input-group">
                            <input type="number" name="discount_percent" id="discount_percent" class="form-control fw-bold" min="0" max="100" placeholder="0" value="<?= $product['discount_percent']; ?>">
                            <span class="input-group-text fw-bold">%</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold small text-primary">Giá Bán Thực Tế VNĐ <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="price" class="form-control fw-bold text-danger border-primary shadow-sm" min="0" placeholder="VD: 1200000" value="<?= $product['price'] > 0 ? $product['price'] : ''; ?>" required>
                        <small class="text-primary" style="font-size: 11px;">Giá khách thanh toán</small>
                    </div>
                </div>
            </div>

            <!-- TỒN KHO SIZE -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--dark-slate);">
                    <i class="fa-solid fa-boxes-stacked me-2 text-warning"></i>Tồn Kho Theo Từng Size (EU 36 - 44)
                </h5>
                <div class="row g-2 text-center">
                    <?php for ($sz = 36; $sz <= 44; $sz++): ?>
                        <div class="col-4 col-sm-3 col-md-4 col-xl-4">
                            <div class="p-2 border rounded-3 bg-light">
                                <span class="badge bg-secondary mb-1">Size <?= $sz; ?></span>
                                <input type="number" name="stock_size_<?= $sz; ?>" class="form-control form-control-sm text-center fw-bold" min="0" value="<?= $size_stocks[$sz] ?? 10; ?>">
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- MÔ TẢ -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--dark-slate);">
                    <i class="fa-solid fa-align-left me-2 text-info"></i>Mô Tả Sản Phẩm
                </h5>
                <textarea name="description" class="form-control" rows="5" placeholder="Nhập mô tả sản phẩm, chất liệu, tính năng nổi bật..."><?= htmlspecialchars($product['description']); ?></textarea>
            </div>
        </div>

        <!-- CỘT PHẢI: ẢNH & NÚT LƯU -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--dark-slate);">
                    <i class="fa-solid fa-images me-2 text-danger"></i>6 Góc Ảnh Sản Phẩm
                </h5>
                <p class="small text-muted mb-3">Tải lên hoặc dán link ảnh cho 6 góc chụp của đôi giày:</p>

                <!-- Góc 1: Ảnh chính -->
                <div class="mb-3 p-3 border rounded-3 bg-light">
                    <label class="form-label fw-bold small text-primary">
                        <i class="fa-solid fa-star me-1"></i> Góc 1: Ảnh Đại Diện Chính <span class="text-danger">*</span>
                    </label>
                    <?php if (!empty($product['main_image'])): ?>
                        <?php $p_img = (strpos($product['main_image'], 'http') === 0) ? $product['main_image'] : '../' . $product['main_image']; ?>
                        <div class="text-center mb-2">
                            <img src="<?= $p_img; ?>" class="rounded-3 border shadow-sm" style="max-height: 120px; max-width: 100%; object-fit: cover;" id="preview_img_1">
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-2" id="preview_wrap_1" style="display:none;">
                            <img src="" class="rounded-3 border shadow-sm" id="preview_img_1" style="max-height: 120px; max-width: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image_file_1" class="form-control form-control-sm mb-1" accept="image/*" onchange="previewFile(this, 'preview_img_1', 'preview_wrap_1')">
                    <input type="text" name="image_url_1" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh chính..." value="<?= htmlspecialchars($product['main_image']); ?>" onblur="previewUrl(this, 'preview_img_1', 'preview_wrap_1')">
                </div>

                <!-- Góc 2 -> 6 -->
                <?php
                $angle_names = [
                    2 => 'Góc 2: Mặt nghiêng / Hông giày',
                    3 => 'Góc 3: Mặt trên / Mũi giày',
                    4 => 'Góc 4: Mặt sau / Gót giày',
                    5 => 'Góc 5: Đế giày (Outsole)',
                    6 => 'Góc 6: On-feet / Chi tiết lót trong'
                ];
                ?>
                <div class="row g-2">
                    <?php for ($i = 2; $i <= 6; $i++): ?>
                        <div class="col-12">
                            <div class="p-2 border rounded-3 bg-light">
                                <label class="form-label small fw-bold text-dark mb-1 d-block"><?= $angle_names[$i]; ?></label>
                                <?php if (!empty($sub_images[$i])): ?>
                                    <?php $si = (strpos($sub_images[$i], 'http') === 0) ? $sub_images[$i] : '../' . $sub_images[$i]; ?>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <img src="<?= $si; ?>" class="rounded border" id="preview_img_<?= $i; ?>" style="width: 45px; height: 45px; object-fit: cover;">
                                        <small class="text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($sub_images[$i]); ?></small>
                                    </div>
                                <?php else: ?>
                                    <div style="display:none;" id="preview_wrap_<?= $i; ?>">
                                        <img src="" id="preview_img_<?= $i; ?>" class="rounded border mb-1" style="width: 45px; height: 45px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex gap-1">
                                    <input type="file" name="image_file_<?= $i; ?>" class="form-control form-control-sm" accept="image/*" onchange="previewFile(this, 'preview_img_<?= $i; ?>', 'preview_wrap_<?= $i; ?>')">
                                    <input type="text" name="image_url_<?= $i; ?>" class="form-control form-control-sm" placeholder="URL..." value="<?= htmlspecialchars($sub_images[$i]); ?>" onblur="previewUrl(this, 'preview_img_<?= $i; ?>', 'preview_wrap_<?= $i; ?>')">
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- NÚT LƯU -->
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" name="save_product" id="btnSave" class="btn fw-bold w-100 py-3 rounded-3 shadow-sm" style="background-color: var(--active-sage); color: white; font-size: 1.05rem;">
                        <i class="fa-solid fa-floppy-disk me-2"></i><?= $is_edit ? 'LƯU CẬP NHẬT SẢN PHẨM' : 'LƯU & THÊM SẢN PHẨM MỚI'; ?>
                    </button>
                    <a href="products.php" class="btn btn-light w-100 mt-2 text-muted fw-bold">Hủy bỏ</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($err): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Chưa Thể Lưu!',
            html: '<?= addslashes($err); ?>',
            confirmButtonColor: '#ef4444'
        });
    }
});
</script>
<?php endif; ?>

<script>
// Preview ảnh khi chọn file
function previewFile(input, imgId, wrapId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById(imgId);
            const wrap = document.getElementById(wrapId);
            if (img) { img.src = e.target.result; }
            if (wrap) { wrap.style.display = ''; }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview ảnh khi dán URL
function previewUrl(input, imgId, wrapId) {
    const val = input.value.trim();
    if (val) {
        const img = document.getElementById(imgId);
        const wrap = document.getElementById(wrapId);
        if (img) { img.src = val; }
        if (wrap) { wrap.style.display = ''; }
    }
}

// Tính giá tự động 2 chiều
(function() {
    const oldP = document.getElementById('old_price');
    const disc = document.getElementById('discount_percent');
    const price = document.getElementById('price');
    const btn   = document.getElementById('btnSave');
    const form  = document.getElementById('productForm');

    if (!oldP || !disc || !price) return;

    oldP.addEventListener('input', function() {
        const o = parseFloat(this.value) || 0;
        const d = parseFloat(disc.value) || 0;
        const p = parseFloat(price.value) || 0;
        if (o > 0 && d > 0) price.value = Math.round(o * (1 - d / 100));
        else if (o > 0 && p <= 0) price.value = o;
        else if (o > 0 && p > 0 && p < o) disc.value = Math.round(((o - p) / o) * 100);
    });

    disc.addEventListener('input', function() {
        const o = parseFloat(oldP.value) || 0;
        let d = parseFloat(this.value) || 0;
        d = Math.max(0, Math.min(100, d));
        this.value = d;
        if (o > 0) price.value = Math.round(o * (1 - d / 100));
    });

    price.addEventListener('input', function() {
        const p = parseFloat(this.value) || 0;
        const o = parseFloat(oldP.value) || 0;
        if (o > 0 && p > 0) disc.value = p >= o ? 0 : Math.round(((o - p) / o) * 100);
        else if (o <= 0 && p > 0) { oldP.value = p; disc.value = 0; }
    });

    // Khi submit: hiển thị loading trên nút
    if (form && btn) {
        form.addEventListener('submit', function() {
            const p = parseFloat(price.value) || 0;
            const o = parseFloat(oldP.value) || 0;
            if (p > 0 && o <= 0) oldP.value = p;
            else if (o > 0 && p <= 0) price.value = o;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang lưu...';
        });
    }
})();
</script>

    </div>
</div>
</body>
</html>