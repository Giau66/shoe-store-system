<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'employee'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
if ($user_role === 'staff') { $user_role = 'employee'; }

// ========================================================
// 1. CÁC ENDPOINT XỬ LÝ AJAX (KHÔNG LOAD LẠI TRANG)
// ========================================================

// AJAX 1.1: Ẩn / Hiện (Duyệt) bình luận
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    $comment_id = intval($_POST['comment_id'] ?? 0);
    if ($comment_id > 0) {
        $curr_res = $conn->query("SELECT status FROM comments WHERE id = $comment_id");
        if ($curr_res && $row = $curr_res->fetch_assoc()) {
            $new_status = ($row['status'] == 1) ? 0 : 1;
            $conn->query("UPDATE comments SET status = $new_status WHERE id = $comment_id");
            
            $badge_html = ($new_status == 1)
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> Hiển thị</span>'
                : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2"><i class="fa-solid fa-eye-slash me-1"></i> Đang ẩn</span>';

            $btn_cls = ($new_status == 1) ? 'btn btn-sm btn-outline-warning rounded-3' : 'btn btn-sm btn-outline-success rounded-3';
            $btn_icon = ($new_status == 1) ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
            $btn_title = ($new_status == 1) ? 'Ẩn bình luận này' : 'Duyệt / Hiển thị bình luận này';

            echo json_encode([
                'success'    => true,
                'comment_id' => $comment_id,
                'new_status' => $new_status,
                'badge_html' => $badge_html,
                'btn_cls'    => $btn_cls,
                'btn_icon'   => $btn_icon,
                'btn_title'  => $btn_title,
                'message'    => ($new_status == 1) ? "Đã duyệt và hiển thị bình luận #$comment_id!" : "Đã ẩn bình luận #$comment_id!"
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bình luận.']);
    exit;
}

// AJAX 1.2: Lưu phản hồi của Shop / Nhân viên
if (isset($_POST['ajax_submit_reply'])) {
    header('Content-Type: application/json; charset=utf-8');
    $comment_id    = intval($_POST['comment_id'] ?? 0);
    $reply_content = trim($_POST['reply_content'] ?? '');
    $staff_id      = intval($_SESSION['user_id']);

    if ($comment_id > 0) {
        $stmt = $conn->prepare("UPDATE comments SET staff_reply = ?, staff_id = ? WHERE id = ?");
        $reply_val = !empty($reply_content) ? $reply_content : null;
        $stmt->bind_param("sii", $reply_val, $staff_id, $comment_id);
        if ($stmt->execute()) {
            $stmt->close();
            
            $reply_html = '';
            if (!empty($reply_content)) {
                $reply_html = '
                <div class="ms-4 mt-2 p-2 rounded bg-warning bg-opacity-10 border-start border-3 border-warning text-dark small" id="reply-box-' . $comment_id . '">
                    <strong><i class="fa-solid fa-reply fa-rotate-180 me-1 text-warning"></i>Phản hồi từ Shop:</strong>
                    <div class="mt-1">' . nl2br(htmlspecialchars($reply_content)) . '</div>
                </div>';
            }

            echo json_encode([
                'success'       => true,
                'comment_id'    => $comment_id,
                'reply_content' => $reply_content,
                'reply_html'    => $reply_html,
                'message'       => 'Đã lưu phản hồi cho khách hàng thành công!'
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $conn->error]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Mã bình luận không hợp lệ.']);
    exit;
}

// AJAX 1.3: Xóa bình luận
if (isset($_POST['ajax_delete_comment'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Chỉ Quản trị viên (Admin) mới có quyền xóa bình luận!']);
        exit;
    }
    $comment_id = intval($_POST['comment_id'] ?? 0);
    if ($comment_id > 0) {
        if ($conn->query("DELETE FROM comments WHERE id = $comment_id")) {
            echo json_encode(['success' => true, 'message' => "Đã xóa bình luận #$comment_id thành công!"]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $conn->error]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Mã bình luận không hợp lệ.']);
    exit;
}

include_once 'includes/header.php';

// ========================================================
// 2. LỌC VÀ TÌM KIẾM BÌNH LUẬN
// ========================================================
$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? 'all';
$filter_rating = $_GET['rating'] ?? 'all';

$where_clauses = ["1=1"];

if (!empty($search)) {
    $search_clean = $conn->real_escape_string($search);
    $where_clauses[] = "(c.user_name LIKE '%$search_clean%' OR c.content LIKE '%$search_clean%' OR p.name LIKE '%$search_clean%' OR c.staff_reply LIKE '%$search_clean%')";
}

if ($filter_status !== 'all') {
    $st_val = intval($filter_status);
    $where_clauses[] = "c.status = $st_val";
}

if ($filter_rating !== 'all') {
    $rt_val = intval($filter_rating);
    $where_clauses[] = "c.rating = $rt_val";
}

$where_sql = implode(' AND ', $where_clauses);

// Lấy danh sách bình luận kèm sản phẩm và nhân viên phản hồi
$query = "
    SELECT c.*, p.name AS product_name, p.main_image AS product_image, p.id AS pid, 
           u.fullname AS staff_name
    FROM comments c
    LEFT JOIN products p ON c.product_id = p.id
    LEFT JOIN users u ON c.staff_id = u.id
    WHERE $where_sql
    ORDER BY c.id DESC
";
$comments_res = $conn->query($query);
$comments = [];
if ($comments_res) {
    while ($row = $comments_res->fetch_assoc()) {
        $comments[] = $row;
    }
}

// Thống kê số lượng
$published_count = 0;
$hidden_count    = 0;
$avg_star        = 0;
$total_stars     = 0;

$stat_res = $conn->query("SELECT status, rating FROM comments");
if ($stat_res) {
    $all_cnt = 0;
    while ($st = $stat_res->fetch_assoc()) {
        $all_cnt++;
        if ($st['status'] == 1) $published_count++; else $hidden_count++;
        $total_stars += intval($st['rating']);
    }
    if ($all_cnt > 0) $avg_star = round($total_stars / $all_cnt, 1);
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- HEADER TRANG -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-uppercase mb-1" style="color: var(--dark-slate);">
            <i class="fa-solid fa-comments me-2" style="color: var(--active-sage);"></i>Quản Lý Bình Luận & Đánh Giá
        </h4>
        <span class="text-muted small">Duyệt hiển thị, ẩn/khóa, phản hồi trực tiếp không cần tải lại trang.</span>
    </div>
</div>

<!-- THỐNG KÊ NHANH -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fa-solid fa-comments fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Tổng Bình Luận</small>
                    <h5 class="fw-bold mb-0"><?= number_format($published_count + $hidden_count); ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Đã Duyệt (Hiển Thị)</small>
                    <h5 class="fw-bold mb-0 text-success"><?= number_format($published_count); ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning me-3">
                    <i class="fa-solid fa-eye-slash fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Đang Ẩn / Khóa</small>
                    <h5 class="fw-bold mb-0 text-warning"><?= number_format($hidden_count); ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger me-3">
                    <i class="fa-solid fa-star fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Đánh Giá Trung Bình</small>
                    <h5 class="fw-bold mb-0 text-danger"><?= $avg_star; ?> / 5 ⭐</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LỌC VÀ TÌM KIẾM -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" class="row g-3 align-items-center">
        <div class="col-md-5 col-12">
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Tìm theo tên khách, sản phẩm, nội dung..." value="<?= htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-3 col-6">
            <select name="status" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="all" <?= $filter_status === 'all' ? 'selected' : ''; ?>>-- Tất cả trạng thái --</option>
                <option value="1" <?= $filter_status === '1' ? 'selected' : ''; ?>>✅ Đã duyệt (Hiển thị)</option>
                <option value="0" <?= $filter_status === '0' ? 'selected' : ''; ?>>🔒 Đang ẩn (Khóa)</option>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <select name="rating" class="form-select bg-light border-0" onchange="this.form.submit()">
                <option value="all" <?= $filter_rating === 'all' ? 'selected' : ''; ?>>-- Tất cả số sao --</option>
                <option value="5" <?= $filter_rating === '5' ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 sao</option>
                <option value="4" <?= $filter_rating === '4' ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 sao</option>
                <option value="3" <?= $filter_rating === '3' ? 'selected' : ''; ?>>⭐⭐⭐ 3 sao</option>
                <option value="2" <?= $filter_rating === '2' ? 'selected' : ''; ?>>⭐⭐ 2 sao</option>
                <option value="1" <?= $filter_rating === '1' ? 'selected' : ''; ?>>⭐ 1 sao</option>
            </select>
        </div>
        <div class="col-md-1 col-12 d-flex gap-1">
            <button type="submit" class="btn btn-dark w-100 rounded-3" title="Lọc dữ liệu"><i class="fa-solid fa-filter"></i></button>
            <a href="comments.php" class="btn btn-outline-secondary rounded-3" title="Đặt lại bộ lọc"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- DANH SÁCH BÌNH LUẬN -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4" style="width: 60px;">ID</th>
                        <th style="width: 240px;">Sản phẩm</th>
                        <th style="width: 170px;">Khách hàng</th>
                        <th style="width: 130px;">Đánh giá</th>
                        <th>Nội dung bình luận & Phản hồi</th>
                        <th style="width: 130px;">Trạng thái</th>
                        <th style="width: 160px;" class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="commentTableBody">
                    <?php if (empty($comments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-comments-slash fs-1 d-block mb-2 opacity-50"></i>
                                Không tìm thấy bình luận nào phù hợp.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <?php 
                            $c_id = intval($c['id']);
                            $img_src = (!empty($c['product_image']) && strpos($c['product_image'], 'http') === 0) ? $c['product_image'] : (!empty($c['product_image']) ? '../' . $c['product_image'] : '../assets/images/default-shoe.png');
                            ?>
                            <tr id="comment-row-<?= $c_id; ?>">
                                <td class="ps-4 fw-bold text-muted">#<?= $c_id; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $img_src; ?>" class="rounded-3 me-2 border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.src='../assets/images/default-shoe.png'">
                                        <div>
                                            <a href="../product-detail.php?id=<?= $c['product_id']; ?>" class="fw-semibold text-dark text-decoration-none d-block text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($c['product_name'] ?? ''); ?>">
                                                <?= htmlspecialchars($c['product_name'] ?? 'Sản phẩm đã xóa'); ?>
                                            </a>
                                            <small class="text-muted">Mã SP: #<?= $c['product_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($c['user_name']); ?></div>
                                    <small class="text-muted d-block"><i class="fa-solid fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($c['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa-<?= $i <= $c['rating'] ? 'solid' : 'regular'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="badge bg-light text-dark border mt-1 fw-bold"><?= $c['rating']; ?> / 5 sao</span>
                                </td>
                                <td>
                                    <div class="p-2 rounded bg-light border-start border-3 border-primary mb-1">
                                        <?= nl2br(htmlspecialchars($c['content'])); ?>
                                    </div>
                                    <div id="reply-container-<?= $c_id; ?>">
                                        <?php if (!empty($c['staff_reply'])): ?>
                                            <div class="ms-4 mt-2 p-2 rounded bg-warning bg-opacity-10 border-start border-3 border-warning text-dark small" id="reply-box-<?= $c_id; ?>">
                                                <strong><i class="fa-solid fa-reply fa-rotate-180 me-1 text-warning"></i>Phản hồi từ Shop:</strong>
                                                <div class="mt-1"><?= nl2br(htmlspecialchars($c['staff_reply'])); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td id="status-cell-<?= $c_id; ?>">
                                    <?php if ($c['status'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                                            <i class="fa-solid fa-circle-check me-1"></i> Hiển thị
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2">
                                            <i class="fa-solid fa-eye-slash me-1"></i> Đang ẩn
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Nút Đi tới vị trí bình luận -->
                                        <a href="../product-detail.php?id=<?= $c['product_id']; ?>#comment-<?= $c_id; ?>" 
                                           class="btn btn-sm btn-outline-info rounded-3" 
                                           title="Xem trên trang sản phẩm">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>

                                        <!-- Nút toggle status Live AJAX -->
                                        <button type="button" id="btn-toggle-<?= $c_id; ?>" 
                                                class="btn btn-sm <?= $c['status'] == 1 ? 'btn-outline-warning' : 'btn-outline-success'; ?> rounded-3" 
                                                onclick="toggleCommentStatusAjax(<?= $c_id; ?>)" 
                                                title="<?= $c['status'] == 1 ? 'Ẩn bình luận này' : 'Duyệt / Hiển thị'; ?>">
                                            <i class="fa-solid <?= $c['status'] == 1 ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                        </button>

                                        <!-- Nút Trả lời Live AJAX -->
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-3" 
                                                onclick="openReplyModal(<?= $c_id; ?>, '<?= htmlspecialchars(addslashes($c['user_name']), ENT_QUOTES); ?>', '<?= htmlspecialchars(addslashes($c['content']), ENT_QUOTES); ?>', '<?= htmlspecialchars(addslashes($c['staff_reply'] ?? ''), ENT_QUOTES); ?>')" 
                                                title="Trả lời bình luận">
                                            <i class="fa-solid fa-reply"></i>
                                        </button>

                                        <?php if ($user_role === 'admin'): ?>
                                            <!-- Nút Xóa Live AJAX -->
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3" 
                                                    onclick="deleteCommentAjax(<?= $c_id; ?>)" 
                                                    title="Xóa bình luận">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL PHẢN HỒI BÌNH LUẬN (CHUNG 1 MODAL TIẾT KIỆM BỘ NHỚ) -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered text-start">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="replyModalTitle">
                    <i class="fa-solid fa-reply me-2 text-warning"></i>Phản Hồi Bình Luận
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="replyForm">
                <input type="hidden" name="ajax_submit_reply" value="1">
                <input type="hidden" name="comment_id" id="reply_comment_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold" id="reply_cust_label">Nội dung từ khách hàng:</label>
                        <div class="p-3 bg-light rounded-3 text-dark fst-italic border" id="reply_cust_content">
                            ...
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nội dung phản hồi của Shop:</label>
                        <textarea name="reply_content" id="reply_content_input" rows="4" class="form-control" placeholder="Nhập lời cảm ơn hoặc giải đáp thắc mắc cho khách hàng..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitReply" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Phản Hồi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toast notification helper (tự động biến mất sau 1.8 giây)
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
});

let replyModal = null;

document.addEventListener("DOMContentLoaded", function() {
    replyModal = new bootstrap.Modal(document.getElementById('replyModal'));

    // Xử lý gửi phản hồi bình luận qua AJAX
    const form = document.getElementById('replyForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitReply');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';

            const formData = new FormData(form);

            fetch('comments.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Phản Hồi';

                if (data.success) {
                    replyModal.hide();
                    const container = document.getElementById('reply-container-' + data.comment_id);
                    if (container) {
                        container.innerHTML = data.reply_html;
                    }
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể lưu',
                        text: data.message || 'Có lỗi xảy ra.'
                    });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Phản Hồi';
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    text: 'Không thể kết nối máy chủ.'
                });
            });
        });
    }
});

// Mở modal Trả lời
function openReplyModal(commentId, userName, userContent, currentReply) {
    document.getElementById('replyModalTitle').innerHTML = '<i class="fa-solid fa-reply me-2 text-warning"></i>Phản Hồi Bình Luận #' + commentId;
    document.getElementById('reply_comment_id').value = commentId;
    document.getElementById('reply_cust_label').innerHTML = 'Nội dung từ khách hàng (<strong>' + userName + '</strong>):';
    document.getElementById('reply_cust_content').innerText = userContent;
    document.getElementById('reply_content_input').value = currentReply;

    replyModal.show();
}

// Ẩn / Hiện bình luận bằng Live AJAX
function toggleCommentStatusAjax(commentId) {
    const btn = document.getElementById('btn-toggle-' + commentId);
    btn.disabled = true;

    const formData = new FormData();
    formData.append('ajax_toggle_status', '1');
    formData.append('comment_id', commentId);

    fetch('comments.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            const statusCell = document.getElementById('status-cell-' + commentId);
            if (statusCell) {
                statusCell.innerHTML = data.badge_html;
            }
            btn.className = data.btn_cls;
            btn.innerHTML = data.btn_icon;
            btn.title = data.btn_title;

            Toast.fire({
                icon: 'success',
                title: data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: data.message || 'Không thể đổi trạng thái.'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        console.error(err);
    });
}

// Xóa bình luận bằng Live AJAX
function deleteCommentAjax(commentId) {
    Swal.fire({
        title: 'Xác nhận xóa bình luận?',
        html: `Bạn có chắc chắn muốn xóa vĩnh viễn bình luận <b>#${commentId}</b> khỏi hệ thống?`,
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
            formData.append('ajax_delete_comment', '1');
            formData.append('comment_id', commentId);

            fetch('comments.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('comment-row-' + commentId);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(50px)';
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.getElementById('commentTableBody');
                            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy bình luận nào phù hợp.</td></tr>';
                            }
                        }, 400);
                    }
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể xóa',
                        text: data.message || 'Có lỗi xảy ra.'
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
