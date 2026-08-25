<?php
// includes/order-timeline.php
// Expects $order array to be passed in

if (!isset($order)) return;

$status = $order['status'];
?>
<style>
.order-timeline {
    position: relative;
    padding: 20px 0;
    margin-bottom: 20px;
}
.timeline-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin-bottom: 10px;
}
.timeline-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 10%;
    right: 10%;
    height: 3px;
    background-color: #e9ecef;
    z-index: 1;
}
.timeline-step {
    position: relative;
    z-index: 2;
    text-align: center;
    width: 25%;
}
.timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    border: 3px solid #fff;
    font-size: 16px;
    transition: all 0.3s;
}
.timeline-step.completed .timeline-icon {
    background-color: #28a745;
    color: #fff;
}
.timeline-step.active .timeline-icon {
    background-color: var(--accent-color, #c5a059);
    color: #fff;
    box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.2);
}
.timeline-label {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
}
.timeline-time {
    font-size: 11px;
    color: #868e96;
    margin-top: 4px;
}
@media (max-width: 768px) {
    .timeline-label { font-size: 10px; }
    .timeline-icon { width: 30px; height: 30px; font-size: 12px; }
    .timeline-steps::before { top: 15px; }
}
</style>

<div class="order-timeline">
    <?php if ($status === 'cancelled'): ?>
        <?php if (!empty($order['return_reason'])): ?>
            <div class="p-3 bg-success-subtle border border-success-subtle rounded-4 mb-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px; min-width: 42px;">
                    <i class="fa-solid fa-rotate-left fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <strong class="text-success fs-6"><i class="fa-solid fa-circle-check me-1"></i>Đã duyệt hoàn trả &amp; hoàn tiền</strong>
                        <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= !empty($order['cancelled_at']) ? date('d/m/Y H:i', strtotime($order['cancelled_at'])) : date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                    </div>
                    <div class="small text-dark mt-1">Lý do hoàn trả: <span class="fw-semibold text-secondary"><?= htmlspecialchars($order['return_reason']) ?></span></div>
                    <div class="small text-muted">Cửa hàng đã hoàn tiền thành công vào tài khoản của quý khách và thu hồi sản phẩm vào kho.</div>
                </div>
            </div>
        <?php elseif (($order['payment_status'] ?? '') === 'refunded'): ?>
            <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-4 mb-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px; min-width: 42px;">
                    <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <strong class="text-danger fs-6"><i class="fa-solid fa-circle-check me-1"></i>Đơn hàng đã hủy &amp; đã hoàn tiền</strong>
                        <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= !empty($order['cancelled_at']) ? date('d/m/Y H:i', strtotime($order['cancelled_at'])) : date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                    </div>
                    <div class="small text-dark mt-1">Lý do: <span class="fw-semibold text-danger"><?= htmlspecialchars($order['cancel_reason'] ?: 'Cửa hàng từ chối đơn hàng') ?></span></div>
                    <div class="small text-muted">Do đơn hàng đã được thanh toán trước, số tiền thanh toán đã được hoàn trả lại cho quý khách.</div>
                </div>
            </div>
        <?php else: ?>
            <div class="p-3 bg-light border rounded-4 mb-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px; min-width: 42px;">
                    <i class="fa-solid fa-ban fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <strong class="text-secondary fs-6">Đơn hàng đã được hủy</strong>
                        <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= !empty($order['cancelled_at']) ? date('d/m/Y H:i', strtotime($order['cancelled_at'])) : date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                    </div>
                    <div class="small text-dark mt-1">Lý do hủy: <span class="text-muted"><?= htmlspecialchars($order['cancel_reason'] ?: 'Không có lý do cụ thể') ?></span></div>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($status === 'returning'): ?>
        <div class="p-3 bg-warning-subtle border border-warning-subtle rounded-4 mb-3 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px; min-width: 42px;">
                <i class="fa-solid fa-clock-rotate-left fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <strong class="text-dark fs-6">Yêu cầu hoàn trả đang được xử lý</strong>
                    <small class="text-muted"><i class="fa-solid fa-hourglass-half me-1"></i>Chờ duyệt trong 24h</small>
                </div>
                <div class="small text-dark mt-1">Lý do yêu cầu: <span class="fw-semibold text-danger"><?= htmlspecialchars($order['return_reason'] ?: 'Khách yêu cầu trả hàng') ?></span></div>
                <div class="small text-muted">Shop đang kiểm tra thông tin và sẽ liên hệ hỗ trợ bạn giải quyết sớm nhất.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="timeline-steps">
            <!-- Step 1: Đã Đặt Hàng -->
            <?php 
            $step1_completed = in_array($status, ['pending', 'confirmed', 'shipping', 'completed']);
            $step1_active = $status === 'pending';
            ?>
            <div class="timeline-step <?= $step1_completed && !$step1_active ? 'completed' : '' ?> <?= $step1_active ? 'active' : '' ?>">
                <div class="timeline-icon">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div class="timeline-label">Đã Đặt Hàng</div>
                <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
            </div>

            <!-- Step 2: Đã Xác Nhận (Chuẩn bị hàng) -->
            <?php 
            $step2_completed = in_array($status, ['confirmed', 'shipping', 'completed']);
            $step2_active = $status === 'confirmed';
            ?>
            <div class="timeline-step <?= $step2_completed && !$step2_active ? 'completed' : '' ?> <?= $step2_active ? 'active' : '' ?>">
                <div class="timeline-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div class="timeline-label">Đã Xác Nhận</div>
                <div class="timeline-time">
                    <?= $order['confirmed_at'] ? date('d/m/Y H:i', strtotime($order['confirmed_at'])) : '' ?>
                </div>
            </div>

            <!-- Step 3: Đang Giao Hàng -->
            <?php 
            $step3_completed = in_array($status, ['shipping', 'completed']);
            $step3_active = $status === 'shipping';
            ?>
            <div class="timeline-step <?= $step3_completed && !$step3_active ? 'completed' : '' ?> <?= $step3_active ? 'active' : '' ?>">
                <div class="timeline-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div class="timeline-label">Đang Giao Hàng</div>
                <div class="timeline-time">
                    <?= $order['shipping_at'] ? date('d/m/Y H:i', strtotime($order['shipping_at'])) : '' ?>
                </div>
            </div>

            <!-- Step 4: Đã Giao -->
            <?php 
            $step4_completed = $status === 'completed';
            $step4_active = $status === 'completed'; // stays active/completed color
            ?>
            <div class="timeline-step <?= $step4_completed ? 'completed' : '' ?>">
                <div class="timeline-icon">
                    <i class="fa-solid fa-house-circle-check"></i>
                </div>
                <div class="timeline-label">Đã Giao</div>
                <div class="timeline-time">
                    <?= $order['completed_at'] ? date('d/m/Y H:i', strtotime($order['completed_at'])) : '' ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>