<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Handle POST Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $order_id = intval($_POST['order_id'] ?? 0);

        if ($action === 'cancel' && !empty($_POST['cancel_reason'])) {
            $reason = trim($_POST['cancel_reason']);
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
                $stmt->bind_param('ii', $order_id, $user_id);
                $stmt->execute();
                $current = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$current || $current['status'] !== 'pending') throw new Exception('Đơn hàng không thể hủy ở trạng thái hiện tại.');

                $items = $conn->query("SELECT product_id, variant_id, quantity FROM order_details WHERE order_id = $order_id");
                while ($items && $it = $items->fetch_assoc()) {
                    $pid = (int)$it['product_id']; $vid = (int)$it['variant_id']; $qty = (int)$it['quantity'];
                    if ($vid > 0) $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity + $qty WHERE id = $vid AND product_id = $pid");
                    $conn->query("UPDATE products SET sold_count = GREATEST(0, sold_count - $qty) WHERE id = $pid");
                }
                $up = $conn->prepare("UPDATE orders SET status='cancelled', cancel_reason=?, cancelled_at=NOW() WHERE id=? AND user_id=? AND status='pending'");
                $up->bind_param('sii', $reason, $order_id, $user_id);
                $up->execute();
                if ($up->affected_rows !== 1) throw new Exception('Không thể hủy đơn hàng.');
                $up->close();
                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                $_SESSION['flash_error'] = $e->getMessage();
            }
        } elseif ($action === 'return') {
            $reason_type = trim($_POST['return_reason_type'] ?? '');
            $custom_reason = trim($_POST['return_reason'] ?? '');
            $final_reason = ($reason_type === 'Khác' && !empty($custom_reason)) ? $custom_reason : (!empty($reason_type) ? $reason_type : $custom_reason);

            if (!empty($final_reason)) {
                $reason_esc = $conn->real_escape_string($final_reason);
                $res = $conn->query("SELECT completed_at, created_at FROM orders WHERE id = $order_id AND user_id = $user_id AND status = 'completed'");
                if ($res && $order = $res->fetch_assoc()) {
                    $conn->query("UPDATE orders SET status = 'returning', return_reason = '$reason_esc' WHERE id = $order_id AND user_id = $user_id AND status = 'completed'");
                    $_SESSION['flash_success'] = "Đã gửi yêu cầu hoàn trả thành công! Shop sẽ liên hệ hỗ trợ bạn trong thời gian sớm nhất.";
                }
            }
        } elseif ($action === 'buy_again') {
            $res_det = $conn->query("SELECT od.product_id, od.variant_id, od.quantity, pv.stock_quantity, p.status
                                     FROM order_details od
                                     JOIN product_variants pv ON pv.id = od.variant_id
                                     JOIN products p ON p.id = od.product_id
                                     WHERE od.order_id = $order_id");
            if ($res_det) {
                while ($item = $res_det->fetch_assoc()) {
                    if ((int)$item['status'] !== 1 || (int)$item['stock_quantity'] <= 0) continue;
                    $pid = (int)$item['product_id']; $vid = (int)$item['variant_id'];
                    $qty = min((int)$item['quantity'], (int)$item['stock_quantity']);
                    $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
                                           VALUES (?, ?, ?, ?)
                                           ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)");
                    $stock = (int)$item['stock_quantity'];
                    $stmt->bind_param('iiiii', $user_id, $pid, $vid, $qty, $stock);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header('Location: cart.php');
            exit;
        }
        
        $tab = $_GET['tab'] ?? '1';
        header("Location: my-orders.php?tab=" . urlencode($tab));
        exit;
    }
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : '1';

// Determine status filter based on tab
$status_filter = "status = 'pending'";
if ($tab === '2') {
    $status_filter = "status IN ('confirmed', 'shipping')";
} elseif ($tab === '3') {
    $status_filter = "status = 'completed'";
} elseif ($tab === '4') {
    $status_filter = "(status = 'returning' OR (status = 'cancelled' AND (payment_status = 'refunded' OR (return_reason IS NOT NULL AND return_reason != ''))))";
} elseif ($tab === '5') {
    $status_filter = "(status = 'cancelled' AND payment_status != 'refunded' AND (return_reason IS NULL OR return_reason = ''))";
}

// Fetch Orders
$orders = [];
$res_o = $conn->query("SELECT * FROM orders WHERE user_id = $user_id AND $status_filter ORDER BY created_at DESC");
if ($res_o) {
    while ($row = $res_o->fetch_assoc()) {
        $orders[] = $row;
    }
}

$page_title = "Đơn Hàng Của Tôi";
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-uppercase" style="color: var(--dark-luxury);">Đơn Hàng Của Tôi</h2>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold <?= $tab === '1' ? 'active bg-dark text-warning border border-warning' : 'bg-light text-dark' ?>" href="my-orders.php?tab=1">Chờ Xác Nhận</a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold <?= $tab === '2' ? 'active bg-dark text-warning border border-warning' : 'bg-light text-dark' ?>" href="my-orders.php?tab=2">Chờ Giao Hàng</a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold <?= $tab === '3' ? 'active bg-dark text-warning border border-warning' : 'bg-light text-dark' ?>" href="my-orders.php?tab=3">Đã Giao</a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold <?= $tab === '4' ? 'active bg-dark text-warning border border-warning' : 'bg-light text-dark' ?>" href="my-orders.php?tab=4">Trả Hàng / Hoàn Tiền</a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold <?= $tab === '5' ? 'active bg-dark text-warning border border-warning' : 'bg-light text-dark' ?>" href="my-orders.php?tab=5">Đã Hủy</a>
        </li>
    </ul>

    <div class="orders-list">
        <?php if (empty($orders)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="fa-solid fa-box-open fa-3x mb-3 text-muted"></i>
                <h5 class="text-muted fw-bold">Chưa có đơn hàng nào trong mục này</h5>
                <div class="mt-3">
                    <a href="all-products.php" class="btn btn-warning fw-bold rounded-pill px-4">Khám Phá Sản Phẩm Ngay</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $order_id = intval($order['id']);
                $details = [];
                $res_det = $conn->query("SELECT * FROM order_details WHERE order_id = $order_id");
                if ($res_det) {
                    while ($d = $res_det->fetch_assoc()) {
                        $details[] = $d;
                    }
                }
                
                $status_labels = [
                    'pending' => ($order['payment_method'] === 'BANKING_QR' && $order['payment_status'] === 'unpaid')
                        ? ['text' => '⏳ Chờ thanh toán QR', 'class' => 'bg-warning text-dark']
                        : (($order['payment_method'] === 'BANKING_QR' && $order['payment_status'] === 'paid')
                            ? ['text' => '⏳ Chờ xác nhận (Đã TT QR)', 'class' => 'bg-warning text-dark']
                            : ['text' => '⏳ Chờ xác nhận', 'class' => 'bg-warning text-dark']),
                    'confirmed' => ['text' => '⚙️ Đã xác nhận (Chuẩn bị hàng)', 'class' => 'bg-info text-dark'],
                    'shipping' => ['text' => '🚚 Đang giao hàng', 'class' => 'bg-primary text-white'],
                    'completed' => ['text' => '✅ Đã giao thành công', 'class' => 'bg-success text-white'],
                    'returning' => ['text' => '🔄 Đang yêu cầu hoàn trả', 'class' => 'bg-danger text-white'],
                    'cancelled' => ['text' => '❌ Đã hủy đơn', 'class' => 'bg-secondary text-white']
                ];
                $status = $status_labels[$order['status']] ?? ['text' => $order['status'], 'class' => 'bg-secondary'];
                if ($order['status'] === 'cancelled') {
                    if (!empty($order['return_reason'])) {
                        $status = ['text' => '✓ Đã duyệt hoàn trả & Hoàn tiền', 'class' => 'bg-success text-white'];
                    } elseif ($order['payment_status'] === 'refunded') {
                        $status = ['text' => '❌ Đã hủy (Đã hoàn tiền)', 'class' => 'bg-danger text-white'];
                    }
                }

                $can_return = ($order['status'] === 'completed');
                if ($can_return && !empty($order['completed_at'])) {
                    $can_return = (strtotime($order['completed_at']) >= strtotime('-30 days'));
                } elseif ($can_return) {
                    $can_return = (strtotime($order['created_at']) >= strtotime('-30 days'));
                }
                ?>
                <div class="card mb-4 shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div>
                            <strong class="text-dark">Mã ĐH: <span class="text-danger">#<?= htmlspecialchars($order['order_code']) ?></span></strong>
                            <span class="text-muted ms-3 small"><i class="fa-solid fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <span class="badge <?= $status['class'] ?> fs-6 px-3 py-2 fw-bold"><?= $status['text'] ?></span>
                    </div>
                    
                    <!-- Timeline tiến trình đơn hàng -->
                    <div class="p-3 bg-light border-bottom">
                        <?php include 'includes/order-timeline.php'; ?>
                    </div>

                    <div class="card-body p-0">
                        <?php foreach ($details as $item): ?>
                            <div class="d-flex p-3 border-bottom align-items-center">
                                <img src="<?= htmlspecialchars($item['product_image'] ?: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=200') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="rounded-3 me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($item['product_name']) ?></h6>
                                    <div class="text-muted small mb-1">
                                        Size: <span class="fw-bold text-dark"><?= htmlspecialchars($item['size']) ?></span> 
                                        <?= !empty($item['color']) ? '| Màu: <span class="fw-bold text-dark">' . htmlspecialchars($item['color']) . '</span>' : '' ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge bg-secondary">x<?= intval($item['quantity']) ?></span>
                                        <span class="fw-bold text-danger fs-6"><?= number_format($item['price'], 0, ',', '.') ?>đ</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
                        <div>
                            <div class="mb-1">
                                <span class="text-muted">Tổng thanh toán:</span> 
                                <span class="fw-bold text-danger fs-4 ms-2"><?= number_format($order['total_money'], 0, ',', '.') ?>đ</span>
                            </div>
                            <div class="small text-muted">
                                Hình thức: <strong class="text-dark"><?= $order['payment_method'] === 'BANKING_QR' ? 'Chuyển khoản QR' : 'Thanh toán COD khi nhận hàng' ?></strong> - 
                                <span class="fw-bold <?= ($order['payment_status'] === 'paid') ? 'text-success' : (($order['payment_status'] === 'refunded') ? 'text-danger' : 'text-warning') ?>">
                                    <?= ($order['payment_status'] === 'paid') ? '✓ Đã thanh toán' : (($order['payment_status'] === 'refunded') ? '↩️ Đã hoàn tiền' : '⏳ Chưa thanh toán') ?>
                                </span>
                            </div>
                            <?php if (!empty($order['tracking_code'])): ?>
                                <div class="mt-1 small">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold font-monospace">
                                        <i class="fa-solid fa-truck-fast me-1"></i><?= htmlspecialchars($order['shipping_carrier'] ?: 'GHTK') ?>: <?= htmlspecialchars($order['tracking_code']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 mt-md-0 d-flex gap-2">
                            <?php if (!empty($order['tracking_code'])): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 rounded-pill" onclick="trackCustomerShipment('<?= htmlspecialchars($order['tracking_code']) ?>')">
                                    <i class="fa-solid fa-route me-1"></i> Tra Cứu Vận Đơn
                                </button>
                            <?php endif; ?>

                            <?php if ($order['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $order['id'] ?>">Hủy đơn hàng</button>
                            <?php endif; ?>
                            
                            <?php if ($order['payment_method'] === 'BANKING_QR' && $order['payment_status'] === 'unpaid' && $order['status'] !== 'cancelled'): ?>
                                <a href="payment-qr.php?code=<?= urlencode($order['order_code']) ?>" class="btn btn-warning btn-sm fw-bold px-3 rounded-pill text-dark">Thanh toán QR ngay</a>
                            <?php endif; ?>
                            
                            <?php if ($can_return): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#returnModal<?= $order['id'] ?>">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Yêu cầu trả hàng
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($order['status'], ['completed', 'cancelled'])): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="buy_again">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn btn-dark btn-sm fw-bold px-3 rounded-pill" style="background-color: var(--gold-accent); border:none;">Mua lại đơn này</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Modal Hủy đơn -->
                <?php if ($order['status'] === 'pending'): ?>
                <div class="modal fade" id="cancelModal<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <form method="POST">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold text-danger">Hủy Đơn Hàng #<?= htmlspecialchars($order['order_code']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Vui lòng nhập lý do hủy đơn (*):</label>
                                        <textarea name="cancel_reason" class="form-control rounded-3" rows="3" placeholder="VD: Đổi ý chọn mẫu khác, muốn thay đổi địa chỉ..." required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-light fw-bold rounded-pill" data-bs-dismiss="modal">Đóng</button>
                                    <button type="submit" class="btn btn-danger fw-bold rounded-pill px-4">Xác nhận Hủy Đơn</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Modal Trả hàng -->
                <?php if ($can_return): ?>
                <div class="modal fade" id="returnModal<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <form method="POST">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-rotate-left me-2"></i>Yêu Cầu Trả Hàng #<?= htmlspecialchars($order['order_code']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Lý do hoàn trả (*):</label>
                                        <select name="return_reason_type" class="form-select rounded-3 mb-2" required onchange="var customBox = document.getElementById('customReason<?= $order['id'] ?>'); if(this.value==='Khác') { customBox.classList.remove('d-none'); customBox.setAttribute('required', 'required'); } else { customBox.classList.add('d-none'); customBox.removeAttribute('required'); }">
                                            <option value="Sản phẩm bị lỗi / hỏng do nhà sản xuất">1. Sản phẩm bị lỗi, rách hoặc hỏng do nhà sản xuất</option>
                                            <option value="Giao sai mẫu mã / sai kích thước (size)">2. Giao sai mẫu mã, sai kích thước (size), sai màu</option>
                                            <option value="Hàng không đúng mô tả / mang không vừa chân">3. Hàng không đúng mô tả trên web, mang không vừa</option>
                                            <option value="Hộp / bao bì bị móp méo, hư hỏng nặng khi giao">4. Hộp giày, bao bì bị hư hỏng nặng khi giao</option>
                                            <option value="Khác">5. Lý do khác (Nhập chi tiết cụ thể bên dưới)</option>
                                        </select>
                                        <textarea name="return_reason" id="customReason<?= $order['id'] ?>" class="form-control rounded-3 d-none" rows="3" placeholder="Vui lòng mô tả chi tiết lý do muốn hoàn trả sản phẩm..."></textarea>
                                    </div>
                                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                                        <i class="fa-solid fa-shield-halved me-1 text-primary"></i> <strong>Chính sách đổi trả:</strong> Hỗ trợ đổi trả miễn phí trong 30 ngày đối với lỗi NSX hoặc 7 ngày đổi size. Shop sẽ liên hệ xác minh sớm nhất.
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-light fw-bold rounded-pill" data-bs-dismiss="modal">Đóng</button>
                                    <button type="submit" class="btn btn-danger fw-bold rounded-pill px-4">Gửi Yêu Cầu Hoàn Trả</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function trackCustomerShipment(trackingCode) {
    Swal.fire({
        title: 'Đang tra cứu vận đơn...',
        text: 'Đang kết nối hệ thống bưu cục ' + trackingCode + '...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('api/track-shipment.php?code=' + encodeURIComponent(trackingCode))
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let timelineHtml = '<div class="timeline text-start p-2" style="max-height: 320px; overflow-y: auto;">';
            data.timeline.forEach((step) => {
                const color = step.done ? 'text-success' : (step.current ? 'text-primary fw-bold' : 'text-muted');
                const icon = step.done ? 'fa-circle-check text-success' : (step.current ? 'fa-truck-fast text-primary fa-bounce' : 'fa-circle-dot text-muted');
                timelineHtml += `
                    <div class="d-flex mb-3 align-items-start border-bottom pb-2">
                        <div class="me-3 pt-1"><i class="fa-solid ${icon} fs-5"></i></div>
                        <div>
                            <div class="fw-bold ${color}">${step.status} <small class="text-muted fw-normal ms-2">(${step.time})</small></div>
                            <div class="small text-muted">${step.desc}</div>
                        </div>
                    </div>
                `;
            });
            timelineHtml += '</div>';

            Swal.fire({
                title: `<i class="fa-solid fa-truck-fast text-primary me-2"></i>Tra Cứu Vận Đơn: ${data.tracking_code}`,
                html: `
                    <div class="text-start p-2 bg-light rounded-3 mb-3 border small">
                        <div><strong>Đơn vị vận chuyển:</strong> <span class="badge bg-primary">${data.carrier_name}</span></div>
                        <div><strong>Giao đến:</strong> ${data.customer_name} (${data.phone})</div>
                        <div><strong>Địa chỉ:</strong> ${data.address}</div>
                    </div>
                    ${timelineHtml}
                `,
                width: 650,
                confirmButtonText: 'Đóng',
                confirmButtonColor: '#3b82f6'
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Thông Báo', text: data.message });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không thể kết nối máy chủ tra cứu!' });
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>