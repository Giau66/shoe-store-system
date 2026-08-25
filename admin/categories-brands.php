<?php 
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Hàm chuyển đổi Tên tiếng Việt thành Slug chuẩn SEO (VD: "Giày Thể Thao" -> "giay-the-thao")
function createSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $str);
    $str = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $str);
    $str = preg_replace('/[ìíịỉĩ]/u', 'i', $str);
    $str = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $str);
    $str = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $str);
    $str = preg_replace('/[ỳýỵỷỹ]/u', 'y', $str);
    $str = preg_replace('/[đ]/u', 'd', $str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// ========================================================
// 1. XỬ LÝ AJAX TOÀN DIỆN (100% KHÔNG TẢI LẠI TRANG)
// ========================================================

// AJAX 1.1: Bật / Tắt Ẩn Hiện Danh Mục (Zero Reload)
if (isset($_POST['ajax_toggle_category_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $cid = intval($_POST['cat_id'] ?? 0);
    $check = $conn->query("SELECT status, name FROM categories WHERE id = $cid LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $new_st = ($row['status'] == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id = ?");
        $stmt->bind_param('ii', $new_st, $cid);
        $stmt->execute();
        $stmt->close();

        $badge_html = ($new_st == 1)
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Hiển thị</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-eye-slash me-1"></i>Tạm ẩn</span>';

        echo json_encode([
            'success' => true,
            'new_status' => $new_st,
            'badge_html' => $badge_html,
            'message' => ($new_st == 1) ? "Đã hiển thị danh mục \"{$row['name']}\" trên cửa hàng!" : "Đã tạm ẩn danh mục \"{$row['name']}\" khỏi cửa hàng!"
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục.']);
    exit;
}

// AJAX 1.2: Bật / Tắt Ẩn Hiện Thương Hiệu (Zero Reload)
if (isset($_POST['ajax_toggle_brand_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $bid = intval($_POST['brand_id'] ?? 0);
    $check = $conn->query("SELECT status, name FROM brands WHERE id = $bid LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $new_st = ($row['status'] == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE brands SET status = ? WHERE id = ?");
        $stmt->bind_param('ii', $new_st, $bid);
        $stmt->execute();
        $stmt->close();

        $badge_html = ($new_st == 1)
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Hiển thị</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-eye-slash me-1"></i>Tạm ẩn</span>';

        echo json_encode([
            'success' => true,
            'new_status' => $new_st,
            'badge_html' => $badge_html,
            'message' => ($new_st == 1) ? "Đã hiển thị thương hiệu \"{$row['name']}\" trên website & banner!" : "Đã tạm ẩn thương hiệu \"{$row['name']}\" khỏi trang chủ & bộ lọc!"
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thương hiệu.']);
    exit;
}

// AJAX 1.3: Lưu Danh Mục (Thêm mới / Cập nhật có hỗ trợ Danh mục cha con)
if (isset($_POST['ajax_save_category']) || isset($_POST['save_category'])) {
    $is_ajax = isset($_POST['ajax_save_category']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    
    $cat_id      = intval($_POST['cat_id'] ?? 0);
    $parent_id   = intval($_POST['parent_id'] ?? 0);
    if ($parent_id <= 0 || $parent_id == $cat_id) {
        $parent_id = null;
    }
    $name        = trim($_POST['cat_name'] ?? '');
    $slug        = createSlug($name);
    $type        = in_array($_POST['cat_type'] ?? '', ['giay', 'dep']) ? $_POST['cat_type'] : 'giay';
    $gender      = in_array($_POST['cat_gender'] ?? '', ['nam', 'nu', 'unisex']) ? $_POST['cat_gender'] : 'unisex';
    $description = trim($_POST['cat_description'] ?? '');
    $status      = isset($_POST['cat_status']) && ($_POST['cat_status'] == '1' || $_POST['cat_status'] === 'on') ? 1 : 0;
    $image       = '';

    if (empty($name)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên Danh mục!']);
            exit;
        }
        $err = "Vui lòng nhập tên Danh mục!";
    } else {
        // Upload ảnh nếu có
        if (isset($_FILES['cat_image_file']) && $_FILES['cat_image_file']['error'] == 0) {
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES["cat_image_file"]["name"], PATHINFO_EXTENSION);
            $file_name = time() . '_cat_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES["cat_image_file"]["tmp_name"], $upload_dir . $file_name)) {
                $image = 'uploads/' . $file_name;
            }
        }
        if (empty($image)) {
            $image = trim($_POST['cat_image_url'] ?? '');
        }

        if ($cat_id > 0) {
            // Cập nhật
            if (!empty($image)) {
                $stmt = $conn->prepare("UPDATE categories SET parent_id = ?, name = ?, slug = ?, image = ?, description = ?, type = ?, gender = ?, status = ? WHERE id = ?");
                $stmt->bind_param("issssssii", $parent_id, $name, $slug, $image, $description, $type, $gender, $status, $cat_id);
            } else {
                $stmt = $conn->prepare("UPDATE categories SET parent_id = ?, name = ?, slug = ?, description = ?, type = ?, gender = ?, status = ? WHERE id = ?");
                $stmt->bind_param("isssssii", $parent_id, $name, $slug, $description, $type, $gender, $status, $cat_id);
            }
            if ($stmt->execute()) {
                $stmt->close();
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => "Đã cập nhật danh mục {$name} thành công!"]);
                    exit;
                }
                $msg = "Đã cập nhật danh mục <strong>$name</strong>!";
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
                    exit;
                }
                $err = "Lỗi CSDL: " . $stmt->error;
            }
        } else {
            // Thêm mới
            if (empty($image)) {
                $image = 'assets/images/default-cat.png';
            }
            $stmt = $conn->prepare("INSERT INTO categories (parent_id, name, slug, image, description, type, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", $parent_id, $name, $slug, $image, $description, $type, $gender, $status);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $stmt->close();
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => "Đã thêm danh mục mới {$name} thành công!"]);
                    exit;
                }
                $msg = "Đã thêm danh mục mới <strong>$name</strong>!";
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
                    exit;
                }
                $err = "Lỗi CSDL: " . $stmt->error;
            }
        }
    }
}

// AJAX 1.4: Lưu Thương Hiệu / Hãng (Thêm mới / Cập nhật)
if (isset($_POST['ajax_save_brand']) || isset($_POST['save_brand'])) {
    $is_ajax = isset($_POST['ajax_save_brand']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

    $brand_id    = intval($_POST['brand_id'] ?? 0);
    $name        = trim($_POST['brand_name'] ?? '');
    $slug        = createSlug($name);
    $description = trim($_POST['brand_description'] ?? '');
    $status      = isset($_POST['brand_status']) && ($_POST['brand_status'] == '1' || $_POST['brand_status'] === 'on') ? 1 : 0;
    $logo        = '';
    $banner      = '';

    if (empty($name)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên Thương hiệu!']);
            exit;
        }
        $err = "Vui lòng nhập tên Thương hiệu!";
    } else {
        if (isset($_FILES['brand_logo_file']) && $_FILES['brand_logo_file']['error'] == 0) {
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES["brand_logo_file"]["name"], PATHINFO_EXTENSION);
            $file_name = time() . '_brand_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES["brand_logo_file"]["tmp_name"], $upload_dir . $file_name)) {
                $logo = 'uploads/' . $file_name;
            }
        }
        if (empty($logo)) {
            $logo = trim($_POST['brand_logo_url'] ?? '');
        }

        if ($brand_id > 0) {
            // Cập nhật
            if (!empty($logo)) {
                $stmt = $conn->prepare("UPDATE brands SET name = ?, slug = ?, logo = ?, description = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssii", $name, $slug, $logo, $description, $status, $brand_id);
            } else {
                $stmt = $conn->prepare("UPDATE brands SET name = ?, slug = ?, description = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssii", $name, $slug, $description, $status, $brand_id);
            }
            if ($stmt->execute()) {
                $stmt->close();
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => "Đã cập nhật thương hiệu {$name} thành công!"]);
                    exit;
                }
                $msg = "Đã cập nhật thương hiệu <strong>$name</strong>!";
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
                    exit;
                }
                $err = "Lỗi CSDL: " . $stmt->error;
            }
        } else {
            // Thêm mới
            if (empty($logo)) {
                $logo = 'assets/images/default-brand.png';
            }
            $stmt = $conn->prepare("INSERT INTO brands (name, slug, logo, description, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $slug, $logo, $description, $status);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $stmt->close();
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => "Đã thêm thương hiệu mới {$name} thành công!"]);
                    exit;
                }
                $msg = "Đã thêm thương hiệu mới <strong>$name</strong>!";
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $stmt->error]);
                    exit;
                }
                $err = "Lỗi CSDL: " . $stmt->error;
            }
        }
    }
}

// AJAX 1.5: Xóa Danh Mục
if (isset($_POST['ajax_delete_category']) || isset($_POST['confirm_delete_category'])) {
    $del_cat_id = intval($_POST['delete_cat_id'] ?? $_POST['cat_id'] ?? 0);
    if ($del_cat_id > 0) {
        $conn->query("UPDATE products SET category_id = NULL WHERE category_id = $del_cat_id");
        $conn->query("UPDATE categories SET parent_id = NULL WHERE parent_id = $del_cat_id");
        if ($conn->query("DELETE FROM categories WHERE id = $del_cat_id")) {
            if (isset($_POST['ajax_delete_category'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Đã xóa danh mục thành công!']);
                exit;
            }
            $msg = "Đã xóa danh mục thành công!";
        } else {
            if (isset($_POST['ajax_delete_category'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $conn->error]);
                exit;
            }
            $err = "Lỗi khi xóa: " . $conn->error;
        }
    }
}

// AJAX 1.6: Xóa Thương Hiệu
if (isset($_POST['ajax_delete_brand']) || isset($_POST['confirm_delete_brand'])) {
    $del_brand_id = intval($_POST['delete_brand_id'] ?? $_POST['brand_id'] ?? 0);
    if ($del_brand_id > 0) {
        $conn->query("UPDATE products SET brand_id = NULL WHERE brand_id = $del_brand_id");
        if ($conn->query("DELETE FROM brands WHERE id = $del_brand_id")) {
            if (isset($_POST['ajax_delete_brand'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Đã xóa thương hiệu thành công!']);
                exit;
            }
            $msg = "Đã xóa thương hiệu thành công!";
        } else {
            if (isset($_POST['ajax_delete_brand'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $conn->error]);
                exit;
            }
            $err = "Lỗi khi xóa: " . $conn->error;
        }
    }
}

include_once 'includes/header.php';

// ========================================================
// 2. TRUY VẤN DANH SÁCH & ĐẾM SỐ LƯỢNG SẢN PHẨM
// ========================================================
$sql_cats = "SELECT c.*, p_parent.name AS parent_name, COUNT(p.id) AS product_count 
             FROM categories c 
             LEFT JOIN categories p_parent ON c.parent_id = p_parent.id
             LEFT JOIN products p ON c.id = p.category_id 
             GROUP BY c.id 
             ORDER BY COALESCE(c.parent_id, c.id) ASC, c.parent_id ASC, c.id ASC";
$categories_res = $conn->query($sql_cats);

// Danh mục cấp cha cho dropdown chọn danh mục cha
$parent_cats_list = [];
$p_res = $conn->query("SELECT id, name, parent_id FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC");
if ($p_res) {
    while ($pr = $p_res->fetch_assoc()) {
        $parent_cats_list[] = $pr;
    }
}

$sql_brands = "SELECT b.*, COUNT(p.id) AS product_count 
               FROM brands b 
               LEFT JOIN products p ON b.id = p.brand_id 
               GROUP BY b.id ORDER BY b.id DESC";
$brands_res = $conn->query($sql_brands);
?>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-tags text-primary me-2"></i>Quản Lý Danh Mục &amp; Thương Hiệu
        </h4>
        <span class="text-muted small">Quản lý danh mục cha/con và hãng giày. Bật/tắt ẩn hiện tức thì (không load trang), tự động đồng bộ sang trang chủ và bộ lọc sản phẩm.</span>
    </div>
</div>

<!-- TABS CHUYỂN ĐỔI BẢNG DANH MỤC VÀ THƯƠNG HIỆU -->
<ul class="nav nav-pills mb-4 gap-2" id="manageTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold px-4 py-2 rounded-3" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-panel" type="button" role="tab" onclick="switchTab('categories')">
            <i class="fa-solid fa-list me-2"></i>1. Danh Mục Sản Phẩm (<?= $categories_res ? $categories_res->num_rows : 0; ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2 rounded-3" id="brands-tab" data-bs-toggle="tab" data-bs-target="#brands-panel" type="button" role="tab" onclick="switchTab('brands')">
            <i class="fa-solid fa-copyright me-2"></i>2. Thương Hiệu / Hãng Giày (<?= $brands_res ? $brands_res->num_rows : 0; ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="manageTabContent">

    <!-- ======================================================== -->
    <!-- TAB 1: QUẢN LÝ DANH MỤC SẢN PHẨM -->
    <!-- ======================================================== -->
    <div class="tab-pane fade show active" id="categories-panel" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-uppercase mb-0 text-dark">
                    <i class="fa-solid fa-layer-group me-2 text-success"></i>Danh Sách Danh Mục Sản Phẩm (Hỗ trợ phân cấp Cha - Con)
                </h5>
                <button type="button" class="btn btn-success fw-bold rounded-3 px-3 shadow-sm" onclick="openAddCatModal()">
                    <i class="fa-solid fa-plus me-1"></i> + Thêm Danh Mục Mới
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th style="width: 70px;">Ảnh</th>
                            <th>Tên Danh Mục</th>
                            <th>Cấp Độ / Danh Mục Cha</th>
                            <th>Slug URL</th>
                            <th>Sản Phẩm</th>
                            <th>Ẩn / Hiện (Nhấn để đổi)</th>
                            <th class="text-end" style="width: 140px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <?php if ($categories_res && $categories_res->num_rows > 0): ?>
                            <?php while($c = $categories_res->fetch_assoc()): 
                                $cid = intval($c['id']);
                                $img = (strpos($c['image'] ?? '', 'http') === 0) ? $c['image'] : '../' . (!empty($c['image']) ? $c['image'] : 'assets/images/default-cat.png');
                                $is_child = !empty($c['parent_id']) && $c['parent_id'] > 0;
                            ?>
                                <tr id="cat-row-<?= $cid; ?>" class="<?= $is_child ? 'bg-light bg-opacity-50' : '' ?>">
                                    <td>
                                        <img src="<?= $img; ?>" class="rounded-3 border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='../assets/images/default-cat.png'">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($is_child): ?>
                                                <span class="text-muted me-2" style="font-size: 14px;">↳</span>
                                                <strong class="text-primary fs-6"><?= htmlspecialchars($c['name']); ?></strong>
                                            <?php else: ?>
                                                <strong class="text-dark fs-6"><?= htmlspecialchars($c['name']); ?></strong>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 240px;"><?= htmlspecialchars($c['description'] ?? 'Chưa có mô tả'); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($is_child): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1">
                                                <i class="fa-solid fa-folder-tree me-1"></i>Con của: <b><?= htmlspecialchars($c['parent_name'] ?? 'Danh mục cha') ?></b>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-dark text-white rounded-pill px-2 py-1">
                                                <i class="fa-solid fa-folder me-1"></i>Danh Mục Gốc
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="text-success fw-bold"><?= htmlspecialchars($c['slug']); ?></code></td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary fw-bold fs-6"><?= $c['product_count']; ?> đôi</span></td>
                                    <td id="cat-status-cell-<?= $cid; ?>">
                                        <button type="button" class="btn p-0 border-0" onclick="toggleCategoryStatus(<?= $cid; ?>)" title="Nhấp để Ẩn/Hiện danh mục tức thì">
                                            <?php if ($c['status'] == 1): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Hiển thị</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-eye-slash me-1"></i>Tạm ẩn</span>
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-3 me-1 btn-edit-cat" onclick="openEditCatModal(this)" data-cat='<?= htmlspecialchars(json_encode($c), ENT_QUOTES, "UTF-8"); ?>' title="Chỉnh sửa">
                                            <i class="fa-solid fa-pen"></i> Sửa
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-3" onclick="triggerDeleteCat(<?= $cid; ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES); ?>')" title="Xóa">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có danh mục nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: QUẢN LÝ THƯƠNG HIỆU / HÃNG GIÀY -->
    <!-- ======================================================== -->
    <div class="tab-pane fade" id="brands-panel" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-uppercase mb-0 text-dark">
                    <i class="fa-solid fa-shoe-prints me-2 text-primary"></i>Danh Sách Thương Hiệu / Hãng Giày
                </h5>
                <button type="button" class="btn btn-primary fw-bold rounded-3 px-3 shadow-sm" onclick="openAddBrandModal()">
                    <i class="fa-solid fa-plus me-1"></i> + Thêm Hãng Mới
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th style="width: 70px;">Logo</th>
                            <th>Tên Thương Hiệu</th>
                            <th>Slug URL</th>
                            <th>Mô Tả Hãng</th>
                            <th>Sản Phẩm Đang Có</th>
                            <th>Ẩn / Hiện (Nhấn để đổi)</th>
                            <th class="text-end" style="width: 140px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="brandTableBody">
                        <?php if ($brands_res && $brands_res->num_rows > 0): ?>
                            <?php while($b = $brands_res->fetch_assoc()): 
                                $bid = intval($b['id']);
                                $logo = (strpos($b['logo'] ?? '', 'http') === 0) ? $b['logo'] : '../' . (!empty($b['logo']) ? $b['logo'] : 'assets/images/default-brand.png');
                            ?>
                                <tr id="brand-row-<?= $bid; ?>">
                                    <td>
                                        <img src="<?= $logo; ?>" class="rounded border p-1 bg-white" style="width: 44px; height: 44px; object-fit: contain;" onerror="this.src='../assets/images/default-brand.png'">
                                    </td>
                                    <td><strong class="text-dark fs-6"><?= htmlspecialchars($b['name']); ?></strong></td>
                                    <td><code class="text-primary fw-bold"><?= htmlspecialchars($b['slug']); ?></code></td>
                                    <td><small class="text-muted text-truncate d-block" style="max-width: 240px;"><?= htmlspecialchars($b['description'] ?? 'Chưa có mô tả'); ?></small></td>
                                    <td><span class="badge bg-success-subtle text-success border border-success fw-bold fs-6"><?= $b['product_count']; ?> đôi</span></td>
                                    <td id="brand-status-cell-<?= $bid; ?>">
                                        <button type="button" class="btn p-0 border-0" onclick="toggleBrandStatus(<?= $bid; ?>)" title="Nhấp để Ẩn/Hiện thương hiệu tức thì">
                                            <?php if ($b['status'] == 1): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Hiển thị</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-eye-slash me-1"></i>Tạm ẩn</span>
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-3 me-1 btn-edit-brand" onclick="openEditBrandModal(this)" data-brand='<?= htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8"); ?>' title="Chỉnh sửa">
                                            <i class="fa-solid fa-pen"></i> Sửa
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-3" onclick="triggerDeleteBrand(<?= $bid; ?>, '<?= htmlspecialchars($b['name'], ENT_QUOTES); ?>')" title="Xóa">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có thương hiệu nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ======================================================== -->
<!-- MODAL THÊM / SỬA DANH MỤC -->
<!-- ======================================================== -->
<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title fw-bold" id="catModalTitle">Thông Tin Danh Mục</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="catForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax_save_category" value="1">
                <input type="hidden" name="cat_id" id="form_cat_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Tên Danh Mục: <span class="text-danger">*</span></label>
                        <input type="text" name="cat_name" id="form_cat_name" class="form-control fw-bold" placeholder="VD: Giày Nam, Giày Nữ, Giày Thể Thao..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Danh Mục Cha (Thuộc danh mục lớn nào?):</label>
                        <select name="parent_id" id="form_parent_id" class="form-select">
                            <option value="0">-- Là Danh Mục Gốc (Cấp cao nhất) --</option>
                            <?php foreach ($parent_cats_list as $pc): ?>
                                <option value="<?= $pc['id'] ?>">📂 <?= htmlspecialchars($pc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Chọn nếu muốn danh mục này là danh mục con (Sub-category).</small>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark small">Loại:</label>
                            <select name="cat_type" id="form_cat_type" class="form-select form-select-sm">
                                <option value="giay">Giày</option>
                                <option value="dep">Dép</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark small">Giới tính:</label>
                            <select name="cat_gender" id="form_cat_gender" class="form-select form-select-sm">
                                <option value="unisex">Unisex (Chung)</option>
                                <option value="nam">Nam</option>
                                <option value="nu">Nữ</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Ảnh Đại Diện Danh Mục:</label>
                        <input type="file" name="cat_image_file" class="form-control form-control-sm mb-2" accept="image/*">
                        <input type="text" name="cat_image_url" id="form_cat_image_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Mô Tả Danh Mục:</label>
                        <textarea name="cat_description" id="form_cat_description" class="form-control" rows="3" placeholder="Mô tả ngắn về danh mục này..."></textarea>
                    </div>
                    <div class="form-check form-switch p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="cat_status" id="form_cat_status" value="1" checked>
                        <label class="form-check-label fw-bold text-success" for="form_cat_status">Cho phép hiển thị trên Website &amp; Danh mục chọn</label>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary fw-bold rounded-pill px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitCat" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Danh Mục
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL THÊM / SỬA THƯƠNG HIỆU -->
<!-- ======================================================== -->
<div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title fw-bold" id="brandModalTitle">Thông Tin Thương Hiệu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="brandForm" enctype="multipart/form-data">
                <input type="hidden" name="ajax_save_brand" value="1">
                <input type="hidden" name="brand_id" id="form_brand_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Tên Thương Hiệu / Hãng: <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" id="form_brand_name" class="form-control fw-bold" placeholder="VD: Nike, Adidas, Puma, Jordan..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Logo Thương Hiệu:</label>
                        <input type="file" name="brand_logo_file" class="form-control form-control-sm mb-2" accept="image/*">
                        <input type="text" name="brand_logo_url" id="form_brand_logo_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh logo...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Mô Tả Thương Hiệu:</label>
                        <textarea name="brand_description" id="form_brand_description" class="form-control" rows="3" placeholder="Mô tả về thương hiệu..."></textarea>
                    </div>
                    <div class="form-check form-switch p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="brand_status" id="form_brand_status" value="1" checked>
                        <label class="form-check-label fw-bold text-primary" for="form_brand_status">Cho phép hiển thị trên Website &amp; Bộ lọc thương hiệu</label>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary fw-bold rounded-pill px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitBrand" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thương Hiệu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JAVASCRIPT XỬ LÝ SỰ KIỆN LIVE AJAX (100% ZERO RELOAD) -->
<script>
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

function getCatModal() {
    const el = document.getElementById('catModal');
    if (!el) return null;
    return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
}

function getBrandModal() {
    const el = document.getElementById('brandModal');
    if (!el) return null;
    return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
}

// Chuyển đổi Tab linh hoạt
function switchTab(tabName) {
    const catsTab = document.getElementById('categories-tab');
    const brandsTab = document.getElementById('brands-tab');
    const catsPanel = document.getElementById('categories-panel');
    const brandsPanel = document.getElementById('brands-panel');

    if (tabName === 'categories') {
        if (catsTab) catsTab.classList.add('active');
        if (brandsTab) brandsTab.classList.remove('active');
        if (catsPanel) {
            catsPanel.classList.add('show', 'active');
        }
        if (brandsPanel) {
            brandsPanel.classList.remove('show', 'active');
        }
    } else {
        if (brandsTab) brandsTab.classList.add('active');
        if (catsTab) catsTab.classList.remove('active');
        if (brandsPanel) {
            brandsPanel.classList.add('show', 'active');
        }
        if (catsPanel) {
            catsPanel.classList.remove('show', 'active');
        }
    }
}
window.switchTab = switchTab;

function initCategoryBrandEvents() {
    // Submit Form Danh Mục qua Live AJAX
    const catForm = document.getElementById('catForm');
    if (catForm && !catForm.dataset.boundSubmit) {
        catForm.dataset.boundSubmit = 'true';
        catForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitCat');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(catForm);

            fetch('categories-brands.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error("Server raw response:", text);
                    return { success: false, message: 'Phản hồi từ máy chủ: ' + text.replace(/<[^>]*>?/gm, '').trim().substring(0, 200) };
                }
            })
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Danh Mục';
                }

                if (data.success) {
                    const cm = getCatModal();
                    if (cm) cm.hide();
                    Toast.fire({ icon: 'success', title: data.message });
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    Swal.fire({ icon: 'warning', title: 'Thông báo', text: data.message });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Danh Mục';
                }
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: err.message || 'Không thể kết nối máy chủ.' });
            });
        });
    }

    // Submit Form Thương Hiệu qua Live AJAX
    const brandForm = document.getElementById('brandForm');
    if (brandForm && !brandForm.dataset.boundSubmit) {
        brandForm.dataset.boundSubmit = 'true';
        brandForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitBrand');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
            }

            const formData = new FormData(brandForm);

            fetch('categories-brands.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error("Server raw response:", text);
                    return { success: false, message: 'Phản hồi từ máy chủ: ' + text.replace(/<[^>]*>?/gm, '').trim().substring(0, 200) };
                }
            })
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thương Hiệu';
                }

                if (data.success) {
                    const bm = getBrandModal();
                    if (bm) bm.hide();
                    Toast.fire({ icon: 'success', title: data.message });
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    Swal.fire({ icon: 'warning', title: 'Thông báo', text: data.message });
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thương Hiệu';
                }
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: err.message || 'Không thể kết nối máy chủ.' });
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", initCategoryBrandEvents);
} else {
    initCategoryBrandEvents();
}

function openAddCatModal() {
    const cm = getCatModal();
    if (!cm) return;

    document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-plus me-2 text-warning"></i>Thêm Danh Mục Sản Phẩm Mới';
    document.getElementById('form_cat_id').value = '0';
    document.getElementById('form_cat_name').value = '';
    document.getElementById('form_parent_id').value = '0';
    document.getElementById('form_cat_type').value = 'giay';
    document.getElementById('form_cat_gender').value = 'unisex';
    document.getElementById('form_cat_image_url').value = '';
    document.getElementById('form_cat_description').value = '';
    document.getElementById('form_cat_status').checked = true;

    cm.show();
}
window.openAddCatModal = openAddCatModal;

function openEditCatModal(btnOrData) {
    let c = null;
    if (btnOrData instanceof HTMLElement) {
        c = JSON.parse(btnOrData.getAttribute('data-cat') || '{}');
    } else {
        c = btnOrData;
    }
    if (!c || !c.id) return;

    const cm = getCatModal();
    if (!cm) return;

    document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Sửa Danh Mục: ' + (c.name || '');
    document.getElementById('form_cat_id').value = c.id;
    document.getElementById('form_cat_name').value = c.name || '';
    document.getElementById('form_parent_id').value = c.parent_id || '0';
    document.getElementById('form_cat_type').value = c.type || 'giay';
    document.getElementById('form_cat_gender').value = c.gender || 'unisex';
    document.getElementById('form_cat_image_url').value = c.image || '';
    document.getElementById('form_cat_description').value = c.description || '';
    document.getElementById('form_cat_status').checked = (c.status == 1);

    cm.show();
}
window.openEditCatModal = openEditCatModal;

function openAddBrandModal() {
    const bm = getBrandModal();
    if (!bm) return;

    document.getElementById('brandModalTitle').innerHTML = '<i class="fa-solid fa-plus me-2 text-warning"></i>Thêm Thương Hiệu / Hãng Mới';
    document.getElementById('form_brand_id').value = '0';
    document.getElementById('form_brand_name').value = '';
    document.getElementById('form_brand_logo_url').value = '';
    document.getElementById('form_brand_description').value = '';
    document.getElementById('form_brand_status').checked = true;

    bm.show();
}
window.openAddBrandModal = openAddBrandModal;

function openEditBrandModal(btnOrData) {
    let b = null;
    if (btnOrData instanceof HTMLElement) {
        b = JSON.parse(btnOrData.getAttribute('data-brand') || '{}');
    } else {
        b = btnOrData;
    }
    if (!b || !b.id) return;

    const bm = getBrandModal();
    if (!bm) return;

    document.getElementById('brandModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Sửa Thương Hiệu: ' + (b.name || '');
    document.getElementById('form_brand_id').value = b.id;
    document.getElementById('form_brand_name').value = b.name || '';
    document.getElementById('form_brand_logo_url').value = b.logo || '';
    document.getElementById('form_brand_description').value = b.description || '';
    document.getElementById('form_brand_status').checked = (b.status == 1);

    bm.show();
}
window.openEditBrandModal = openEditBrandModal;

function toggleCategoryStatus(catId) {
    const formData = new FormData();
    formData.append('ajax_toggle_category_status', '1');
    formData.append('cat_id', catId);

    fetch('categories-brands.php', {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error("Server raw response:", text);
            throw new Error('Lỗi phản hồi máy chủ');
        }
    })
    .then(data => {
        if (data.success) {
            const cell = document.getElementById('cat-status-cell-' + catId);
            if (cell) {
                cell.innerHTML = `
                    <button type="button" class="btn p-0 border-0" onclick="toggleCategoryStatus(${catId})" title="Nhấp để Ẩn/Hiện danh mục tức thì">
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
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ.' });
    });
}
window.toggleCategoryStatus = toggleCategoryStatus;

function toggleBrandStatus(brandId) {
    const formData = new FormData();
    formData.append('ajax_toggle_brand_status', '1');
    formData.append('brand_id', brandId);

    fetch('categories-brands.php', {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error("Server raw response:", text);
            throw new Error('Lỗi phản hồi máy chủ');
        }
    })
    .then(data => {
        if (data.success) {
            const cell = document.getElementById('brand-status-cell-' + brandId);
            if (cell) {
                cell.innerHTML = `
                    <button type="button" class="btn p-0 border-0" onclick="toggleBrandStatus(${brandId})" title="Nhấp để Ẩn/Hiện thương hiệu tức thì">
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
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ.' });
    });
}
window.toggleBrandStatus = toggleBrandStatus;

// Xóa danh mục qua SweetAlert2 & Live AJAX
function triggerDeleteCat(cId, cName) {
    Swal.fire({
        title: 'Xóa danh mục sản phẩm?',
        html: `Bạn có chắc muốn xóa danh mục <b>${cName}</b>?<br><small class="text-danger">Các sản phẩm thuộc danh mục này sẽ chuyển về "Chưa phân loại".</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Danh Mục',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_category', '1');
            formData.append('cat_id', cId);

            fetch('categories-brands.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error("Server raw response:", text);
                    throw new Error('Lỗi phản hồi máy chủ');
                }
            })
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('cat-row-' + cId);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(40px)';
                        setTimeout(() => row.remove(), 300);
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể xóa danh mục.' });
            });
        }
    });
}
window.triggerDeleteCat = triggerDeleteCat;

// Xóa thương hiệu qua SweetAlert2 & Live AJAX
function triggerDeleteBrand(bId, bName) {
    Swal.fire({
        title: 'Xóa thương hiệu / hãng giày?',
        html: `Bạn có chắc muốn xóa thương hiệu <b>${bName}</b>?<br><small class="text-danger">Các mẫu giày thuộc hãng này sẽ chuyển về "Chưa phân hãng".</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Xóa Thương Hiệu',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_delete_brand', '1');
            formData.append('brand_id', bId);

            fetch('categories-brands.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error("Server raw response:", text);
                    throw new Error('Lỗi phản hồi máy chủ');
                }
            })
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('brand-row-' + bId);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(40px)';
                        setTimeout(() => row.remove(), 300);
                    }
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Lỗi', text: data.message });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể xóa thương hiệu.' });
            });
        }
    });
}
window.triggerDeleteBrand = triggerDeleteBrand;
</script>

    </div>
</div>
</body>
</html>