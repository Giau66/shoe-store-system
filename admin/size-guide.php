<?php
require_once 'includes/header.php';

if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

// Xử lý Thao tác CRUD
$msg = '';
$err = '';
$active_tab = $_GET['tab'] ?? 'charts'; // 'charts' hoặc 'tips'

// 1. Thêm / Sửa Dòng Quy Đổi Size
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_size_row'])) {
    $row_id        = intval($_POST['row_id'] ?? 0);
    $brand_id      = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : 'NULL';
    $gender        = trim($_POST['gender'] ?? 'all');
    $foot_length   = floatval($_POST['foot_length_cm'] ?? 0);
    $size_eu       = trim($_POST['size_eu'] ?? '');
    $size_us       = trim($_POST['size_us'] ?? '');
    $size_uk       = trim($_POST['size_uk'] ?? '');
    $size_cm       = trim($_POST['size_cm'] ?? '');
    $note          = trim($_POST['note'] ?? '');
    $sort_order    = intval($_POST['sort_order'] ?? 0);
    $status        = isset($_POST['status']) ? 1 : 0;

    if ($foot_length <= 0 || empty($size_eu)) {
        $err = "Vui lòng nhập Chiều dài chân (cm) và Size EU!";
    } else {
        $eu_esc = $conn->real_escape_string($size_eu);
        $us_esc = $conn->real_escape_string($size_us);
        $uk_esc = $conn->real_escape_string($size_uk);
        $cm_esc = $conn->real_escape_string($size_cm);
        $nt_esc = $conn->real_escape_string($note);
        $gn_esc = $conn->real_escape_string($gender);

        if ($row_id > 0) {
            $sql = "UPDATE size_guides SET 
                    brand_id = $brand_id, gender = '$gn_esc', foot_length_cm = $foot_length,
                    size_eu = '$eu_esc', size_us = '$us_esc', size_uk = '$uk_esc', size_cm = '$cm_esc',
                    note = '$nt_esc', sort_order = $sort_order, status = $status
                    WHERE id = $row_id";
            $conn->query($sql);
            $msg = "Đã cập nhật dòng quy đổi Size EU $size_eu thành công!";
        } else {
            $sql = "INSERT INTO size_guides (brand_id, gender, foot_length_cm, size_eu, size_us, size_uk, size_cm, note, sort_order, status)
                    VALUES ($brand_id, '$gn_esc', $foot_length, '$eu_esc', '$us_esc', '$uk_esc', '$cm_esc', '$nt_esc', $sort_order, $status)";
            $conn->query($sql);
            $msg = "Đã thêm mới dòng quy đổi Size EU $size_eu thành công!";
        }
    }
}

// 2. Xóa Dòng Quy Đổi Size
if (isset($_GET['del_row'])) {
    $del_id = intval($_GET['del_row']);
    $conn->query("DELETE FROM size_guides WHERE id = $del_id");
    header("Location: size-guide.php?tab=charts&msg=deleted");
    exit;
}

// 3. Thêm / Sửa Bước Hướng Dẫn Đo Size
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_tip_step'])) {
    $step_id    = intval($_POST['step_id'] ?? 0);
    $step_num   = intval($_POST['step_number'] ?? 1);
    $title      = trim($_POST['title'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $icon       = trim($_POST['icon'] ?? 'fa-solid fa-ruler');
    $sort_order = intval($_POST['sort_order'] ?? 0);

    if (empty($title) || empty($desc)) {
        $err = "Vui lòng nhập đầy đủ Tiêu đề và Nội dung hướng dẫn!";
    } else {
        $t_esc = $conn->real_escape_string($title);
        $d_esc = $conn->real_escape_string($desc);
        $i_esc = $conn->real_escape_string($icon);

        if ($step_id > 0) {
            $sql = "UPDATE size_guide_tips SET step_number = $step_num, title = '$t_esc', description = '$d_esc', icon = '$i_esc', sort_order = $sort_order WHERE id = $step_id";
            $conn->query($sql);
            $msg = "Đã cập nhật Bước $step_num thành công!";
        } else {
            $sql = "INSERT INTO size_guide_tips (step_number, title, description, icon, sort_order) VALUES ($step_num, '$t_esc', '$d_esc', '$i_esc', $sort_order)";
            $conn->query($sql);
            $msg = "Đã thêm Bước $step_num mới thành công!";
        }
    }
}

// 4. Xóa Bước Hướng Dẫn
if (isset($_GET['del_tip'])) {
    $del_id = intval($_GET['del_tip']);
    $conn->query("DELETE FROM size_guide_tips WHERE id = $del_id");
    header("Location: size-guide.php?tab=tips&msg=deleted");
    exit;
}

// Lấy danh sách thương hiệu để lọc & gán
$brands_list = [];
$b_res = $conn->query("SELECT id, name FROM brands WHERE status = 1 ORDER BY name ASC");
if ($b_res) {
    while ($b = $b_res->fetch_assoc()) $brands_list[] = $b;
}

// Lấy danh sách bảng quy đổi
$filter_brand = isset($_GET['filter_brand']) ? intval($_GET['filter_brand']) : -1;
$where_chart = "1=1";
if ($filter_brand >= 0) {
    if ($filter_brand === 0) $where_chart .= " AND (brand_id IS NULL OR brand_id = 0)";
    else $where_chart .= " AND brand_id = $filter_brand";
}
$size_charts = $conn->query("SELECT sg.*, b.name AS brand_name FROM size_guides sg LEFT JOIN brands b ON sg.brand_id = b.id WHERE $where_chart ORDER BY sg.foot_length_cm ASC, sg.sort_order ASC");

// Lấy danh sách các bước hướng dẫn
$guide_tips = $conn->query("SELECT * FROM size_guide_tips ORDER BY step_number ASC, sort_order ASC");

// Lấy dữ liệu sửa nếu có
$edit_row = null;
if (isset($_GET['edit_row'])) {
    $er_id = intval($_GET['edit_row']);
    $er_q = $conn->query("SELECT * FROM size_guides WHERE id = $er_id");
    if ($er_q && $er_q->num_rows > 0) $edit_row = $er_q->fetch_assoc();
}

$edit_tip = null;
if (isset($_GET['edit_tip'])) {
    $et_id = intval($_GET['edit_tip']);
    $et_q = $conn->query("SELECT * FROM size_guide_tips WHERE id = $et_id");
    if ($et_q && $et_q->num_rows > 0) $edit_tip = $et_q->fetch_assoc();
}
?>

<div class="content-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-ruler-combined text-warning me-2"></i>Quản Lý Hướng Dẫn Chọn Size Giày</h3>
        <p class="text-muted mb-0">Quản lý bảng quy đổi kích thước EU, US, UK, CM và các bước hướng dẫn đo bàn chân cho khách hàng.</p>
    </div>
    <div>
        <a href="../size-guide.php" class="btn btn-outline-dark rounded-pill fw-bold">
            <i class="fa-solid fa-eye me-1"></i> Xem Trang Khách Hàng
        </a>
    </div>
</div>

<?php if (!empty($msg) || ($_GET['msg'] ?? '') === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $msg ?: 'Đã xóa mục thành công!' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= $err ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- TABS NAVIGATION -->
<ul class="nav nav-pills mb-4 gap-2" id="sizeGuideTabs">
    <li class="nav-item">
        <a class="nav-link rounded-pill fw-bold px-4 <?= $active_tab === 'charts' ? 'active bg-dark' : 'bg-white border text-dark' ?>" href="?tab=charts">
            <i class="fa-solid fa-table-list me-2"></i>Bảng Quy Đổi Kích Cỡ (Size Chart)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill fw-bold px-4 <?= $active_tab === 'tips' ? 'active bg-dark' : 'bg-white border text-dark' ?>" href="?tab=tips">
            <i class="fa-solid fa-list-ol me-2"></i>Các Bước Hướng Dẫn Đo Chân
        </a>
    </li>
</ul>

<div class="row g-4">

    <!-- TAB 1: QUẢN LÝ BẢNG QUY ĐỔI SIZE GIÀY -->
    <?php if ($active_tab === 'charts'): ?>
    
    <!-- Cột Trái: Form Thêm / Sửa Dòng Quy Đổi -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-dark text-white rounded-top-4 py-3 fw-bold">
                <i class="fa-solid fa-<?= $edit_row ? 'pen-to-square' : 'plus-circle' ?> text-warning me-2"></i>
                <?= $edit_row ? 'Chỉnh Sửa Size EU ' . htmlspecialchars($edit_row['size_eu']) : 'Thêm Dòng Quy Đổi Mới' ?>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="size-guide.php?tab=charts">
                    <input type="hidden" name="save_size_row" value="1">
                    <input type="hidden" name="row_id" value="<?= $edit_row['id'] ?? 0 ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Thương Hiệu Áp Dụng</label>
                        <select name="brand_id" class="form-select">
                            <option value="">-- Chuẩn chung (Tất cả hãng) --</option>
                            <?php foreach($brands_list as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= isset($edit_row['brand_id']) && $edit_row['brand_id'] == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Chọn "Chuẩn chung" nếu áp dụng cho mọi thương hiệu</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Đối Tượng / Giới Tính</label>
                        <select name="gender" class="form-select">
                            <option value="all" <?= ($edit_row['gender'] ?? '') === 'all' ? 'selected' : '' ?>>Tất cả (Unisex)</option>
                            <option value="nam" <?= ($edit_row['gender'] ?? '') === 'nam' ? 'selected' : '' ?>>Nam (Men)</option>
                            <option value="nu" <?= ($edit_row['gender'] ?? '') === 'nu' ? 'selected' : '' ?>>Nữ (Women)</option>
                            <option value="tre_em" <?= ($edit_row['gender'] ?? '') === 'tre_em' ? 'selected' : '' ?>>Trẻ em (Kids)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Chiều Dài Chân (cm) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="foot_length_cm" class="form-control fw-bold" placeholder="VD: 24.5" value="<?= htmlspecialchars($edit_row['foot_length_cm'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Size EU <span class="text-danger">*</span></label>
                            <input type="text" name="size_eu" class="form-control fw-bold text-primary" placeholder="VD: 39" value="<?= htmlspecialchars($edit_row['size_eu'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Size US</label>
                            <input type="text" name="size_us" class="form-control" placeholder="6.5" value="<?= htmlspecialchars($edit_row['size_us'] ?? '') ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Size UK</label>
                            <input type="text" name="size_uk" class="form-control" placeholder="5.5" value="<?= htmlspecialchars($edit_row['size_uk'] ?? '') ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Lòng Giày CM</label>
                            <input type="text" name="size_cm" class="form-control" placeholder="24.5" value="<?= htmlspecialchars($edit_row['size_cm'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi Chú / Lời Khuyên</label>
                        <input type="text" name="note" class="form-control" placeholder="VD: Chân bè nên tăng +1 size" value="<?= htmlspecialchars($edit_row['note'] ?? '') ?>">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Thứ Tự</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= intval($edit_row['sort_order'] ?? 0) ?>">
                        </div>
                        <div class="col-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="status" id="chkRowStatus" <?= !isset($edit_row['status']) || $edit_row['status'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="chkRowStatus">Kích hoạt</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-dark fw-bold rounded-pill flex-grow-1">
                            <i class="fa-solid fa-save me-1"></i> <?= $edit_row ? 'Lưu Thay Đổi' : 'Thêm Size' ?>
                        </button>
                        <?php if ($edit_row): ?>
                            <a href="size-guide.php?tab=charts" class="btn btn-outline-secondary rounded-pill">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cột Phải: Bảng Danh Sách Size -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-table me-2 text-warning"></i>Bảng Quy Đổi Size Hiện Có</h5>
                
                <!-- Lọc Theo Thương Hiệu -->
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-semibold text-muted text-nowrap">Lọc Hãng:</span>
                    <select class="form-select form-select-sm rounded-pill" onchange="window.location.href='size-guide.php?tab=charts&filter_brand='+this.value">
                        <option value="-1" <?= $filter_brand === -1 ? 'selected' : '' ?>>Tất cả thương hiệu</option>
                        <option value="0" <?= $filter_brand === 0 ? 'selected' : '' ?>>Chuẩn chung</option>
                        <?php foreach($brands_list as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $filter_brand == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Hãng / Đối Tượng</th>
                            <th>Chiều Dài Chân</th>
                            <th class="text-primary">Size EU</th>
                            <th>Size US</th>
                            <th>Size UK</th>
                            <th>Lòng Giày CM</th>
                            <th>Ghi Chú</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($size_charts && $size_charts->num_rows > 0): ?>
                            <?php while($row = $size_charts->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['brand_name'] ?: 'Chuẩn Chung') ?></strong>
                                    <br><span class="badge bg-light text-dark rounded-pill" style="font-size:10px;"><?= strtoupper($row['gender']) ?></span>
                                </td>
                                <td><span class="badge bg-warning text-dark fw-bold fs-6"><?= $row['foot_length_cm'] ?> cm</span></td>
                                <td><strong class="text-primary fs-5"><?= htmlspecialchars($row['size_eu']) ?></strong></td>
                                <td><?= htmlspecialchars($row['size_us'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['size_uk'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['size_cm'] ?: '-') ?> cm</td>
                                <td class="text-muted small text-start"><?= htmlspecialchars($row['note'] ?: '-') ?></td>
                                <td>
                                    <a href="size-guide.php?tab=charts&edit_row=<?= $row['id'] ?>&filter_brand=<?= $filter_brand ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="size-guide.php?tab=charts&del_row=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Bạn có chắc chắn muốn xóa dòng size này?')" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-ruler fa-2x mb-2 d-block opacity-50"></i>
                                    Chưa có dữ liệu size nào khớp với bộ lọc.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 2: QUẢN LÝ CÁC BƯỚC HƯỚNG DẪN ĐO CHÂN -->
    <?php if ($active_tab === 'tips'): ?>
    
    <!-- Cột Trái: Form Thêm / Sửa Bước Hướng Dẫn -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-dark text-white rounded-top-4 py-3 fw-bold">
                <i class="fa-solid fa-<?= $edit_tip ? 'pen-to-square' : 'plus-circle' ?> text-warning me-2"></i>
                <?= $edit_tip ? 'Chỉnh Sửa Bước ' . $edit_tip['step_number'] : 'Thêm Bước Hướng Dẫn Mới' ?>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="size-guide.php?tab=tips">
                    <input type="hidden" name="save_tip_step" value="1">
                    <input type="hidden" name="step_id" value="<?= $edit_tip['id'] ?? 0 ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bước Thứ Mấy <span class="text-danger">*</span></label>
                        <input type="number" name="step_number" class="form-control fw-bold" min="1" max="10" value="<?= intval($edit_tip['step_number'] ?? ($guide_tips->num_rows + 1)) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu Đề Bước <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="VD: Chuẩn Bị Dụng Cụ Đo" value="<?= htmlspecialchars($edit_tip['title'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Biểu Tượng (FontAwesome class)</label>
                        <input type="text" name="icon" class="form-control font-monospace" placeholder="fa-solid fa-pencil" value="<?= htmlspecialchars($edit_tip['icon'] ?? 'fa-solid fa-ruler') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội Dung Chi Tiết <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Mô tả cụ thể cách đo chân..." required><?= htmlspecialchars($edit_tip['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-dark fw-bold rounded-pill flex-grow-1">
                            <i class="fa-solid fa-save me-1"></i> <?= $edit_tip ? 'Lưu Bước' : 'Thêm Bước' ?>
                        </button>
                        <?php if ($edit_tip): ?>
                            <a href="size-guide.php?tab=tips" class="btn btn-outline-secondary rounded-pill">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cột Phải: Danh Sách Các Bước Hướng Dẫn -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check me-2 text-warning"></i>Quy Trình Các Bước Đo Size</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php if ($guide_tips && $guide_tips->num_rows > 0): ?>
                        <?php while($tip = $guide_tips->fetch_assoc()): ?>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border d-flex gap-3 align-items-start position-relative">
                                <div class="bg-dark text-warning p-3 rounded-3 text-center flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="<?= htmlspecialchars($tip['icon'] ?: 'fa-solid fa-ruler') ?> fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1">
                                        <span class="badge bg-warning text-dark me-2">Bước <?= $tip['step_number'] ?></span>
                                        <?= htmlspecialchars($tip['title']) ?>
                                    </h6>
                                    <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($tip['description'])) ?></p>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <a href="size-guide.php?tab=tips&edit_tip=<?= $tip['id'] ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="size-guide.php?tab=tips&del_tip=<?= $tip['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Bạn có chắc muốn xóa bước này?')" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
