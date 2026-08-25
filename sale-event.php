<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) { header('Location: index.php'); exit(); }

// Lấy thông tin sự kiện
$stmt = $conn->prepare("SELECT * FROM sale_events WHERE slug = ? AND status = 1 LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    http_response_code(404);
    include 'includes/header.php';
    echo '<div class="container py-5 text-center"><h2>Sự kiện không tồn tại hoặc đã kết thúc.</h2><a href="index.php" class="btn btn-primary mt-3">Về Trang Chủ</a></div>';
    include 'includes/footer.php';
    exit();
}

$event_id   = intval($event['id']);
$now        = new DateTime();
$start_dt   = new DateTime($event['start_date']);
$end_dt     = new DateTime($event['end_date']);
$is_active  = ($now >= $start_dt && $now <= $end_dt);
$is_upcoming = $now < $start_dt;

// Lấy sản phẩm trong sự kiện
$products_res = $conn->query("
    SELECT p.*, ep.sale_price AS event_sale_price, ep.discount_percent AS event_discount,
           b.name AS brand_name
    FROM event_products ep
    JOIN products p ON ep.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE ep.event_id = $event_id AND p.status = 1
    ORDER BY ep.sort_order ASC, p.sold_count DESC
");
$event_products = [];
if ($products_res) {
    while ($r = $products_res->fetch_assoc()) $event_products[] = $r;
}

// Lấy voucher của sự kiện
$user_saved_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $uv_res = $conn->query("SELECT voucher_id FROM user_vouchers WHERE user_id=$uid");
    if ($uv_res) while ($r = $uv_res->fetch_assoc()) $user_saved_ids[] = intval($r['voucher_id']);
}
$vouchers_res = $conn->query("
    SELECT v.*, b.name AS brand_name
    FROM vouchers v
    LEFT JOIN brands b ON v.brand_id = b.id
    WHERE v.sale_event_id = $event_id AND v.status = 1 AND (v.end_date IS NULL OR v.end_date >= NOW())
    ORDER BY v.id ASC
");
$event_vouchers = [];
if ($vouchers_res) while ($r = $vouchers_res->fetch_assoc()) $event_vouchers[] = $r;

include 'includes/header.php';
$color = htmlspecialchars($event['color_theme'] ?? '#ef4444');
$event_name = htmlspecialchars($event['name']);
?>

<style>
:root { --event-color: <?= $color ?>; }

/* ===== HERO BANNER SỰ KIỆN ===== */
.event-hero {
    position: relative;
    min-height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}
.event-hero-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    filter: brightness(0.85) contrast(1.05);
    transform: scale(1.02);
    transition: transform 0.6s ease;
}
.event-hero::after {
    content: "";
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.35) 0%, rgba(15, 23, 42, 0.65) 60%, rgba(15, 23, 42, 0.9) 100%);
    pointer-events: none;
    z-index: 1;
}
.event-hero-content { position: relative; z-index: 2; text-align: center; padding: 2.5rem 2rem; }
.event-title-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--event-color);
    color: #fff; font-weight: 900;
    font-size: 1.05rem; letter-spacing: 2px;
    padding: 0.55rem 1.8rem; border-radius: 100px;
    margin-bottom: 1.2rem; text-transform: uppercase;
    box-shadow: 0 6px 25px rgba(0,0,0,0.5);
    animation: pulse-badge 1.8s ease-in-out infinite;
}
@keyframes pulse-badge {
    0%, 100% { box-shadow: 0 4px 20px rgba(0,0,0,0.4), 0 0 0 0 rgba(239,68,68,0.4); }
    50% { box-shadow: 0 4px 20px rgba(0,0,0,0.4), 0 0 0 16px rgba(239,68,68,0); }
}
.event-hero-title { 
    font-size: clamp(2.2rem, 5.5vw, 3.8rem); 
    font-weight: 900; 
    color: #fff; 
    line-height: 1.15; 
    margin-bottom: 0.85rem;
    text-shadow: 0 3px 18px rgba(0,0,0,0.7);
}
.event-hero-sub { 
    color: rgba(255,255,255,0.9); 
    font-size: 1.15rem; 
    max-width: 650px; 
    margin: 0 auto 1.8rem;
    text-shadow: 0 2px 10px rgba(0,0,0,0.8);
}

/* ===== COUNTDOWN ===== */
.countdown-wrap { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.countdown-box {
    background: rgba(15,23,42,0.65);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 14px; padding: 0.8rem 1.4rem;
    min-width: 80px; text-align: center;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}
.countdown-num { font-size: 2.2rem; font-weight: 900; color: #fff; line-height: 1; display: block; }
.countdown-lbl { font-size: 0.7rem; color: rgba(255,255,255,0.75); text-transform: uppercase; letter-spacing: 1px; }

/* ===== PRODUCTS GRID ===== */
.event-products-section { padding: 3rem 0; }
.event-product-card {
    border-radius: 16px; overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
}
.event-product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(0,0,0,0.14); }
.event-product-img { position: relative; height: 220px; overflow: hidden; }
.event-product-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.event-product-card:hover .event-product-img img { transform: scale(1.06); }
.event-discount-badge {
    position: absolute; top: 10px; left: 10px;
    background: var(--event-color); color: #fff;
    font-weight: 800; font-size: 0.8rem;
    padding: 4px 10px; border-radius: 20px;
}
.event-sale-price { color: var(--event-color); font-size: 1.25rem; font-weight: 800; }
.event-orig-price { text-decoration: line-through; color: #94a3b8; font-size: 0.9rem; }

/* ===== VOUCHER SECTION ===== */
.event-voucher-section { background: linear-gradient(135deg, #0b1120, #111827); border-radius: 24px; padding: 2rem; margin: 2rem 0; }
.event-voucher-card {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(255,255,255,0.1);
    border-left: 5px solid var(--event-color);
    border-radius: 14px; padding: 1.2rem 1.5rem;
    margin-bottom: 12px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    transition: transform 0.2s, border-color 0.2s;
}
.event-voucher-card:hover { transform: translateX(4px); border-color: var(--event-color); }
</style>

<!-- HERO BANNER -->
<?php 
$event_banner = !empty($event['banner_image']) ? $event['banner_image'] : (!empty($event['hero_banner_image']) ? $event['hero_banner_image'] : '');
?>
<section class="event-hero">
    <?php if (!empty($event_banner)): ?>
    <div class="event-hero-bg" style="background-image: url('<?= htmlspecialchars($event_banner) ?>');"></div>
    <?php else: ?>
    <div class="event-hero-bg" style="background: radial-gradient(circle at 30% 50%, <?= $color ?>44, transparent 60%), linear-gradient(135deg, #0f172a, #1e293b); filter: none;"></div>
    <?php endif; ?>

    <div class="event-hero-content">
        <div class="event-title-badge">
            <?php if (!empty($event['icon_image'])): ?>
                <img src="<?= htmlspecialchars($event['icon_image']) ?>" alt="" style="width: 22px; height: 22px; object-fit: contain;">
            <?php elseif (!empty($event['icon'])): ?>
                <i class="<?= htmlspecialchars($event['icon']) ?>"></i>
            <?php endif; ?>
            <span><?= $event_name ?></span>
        </div>
        <h1 class="event-hero-title">
            <?= htmlspecialchars($event['hero_banner_title'] ?: $event['name']) ?>
        </h1>
        <?php if (!empty($event['hero_banner_subtitle'])): ?>
        <p class="event-hero-sub"><?= htmlspecialchars($event['hero_banner_subtitle']) ?></p>
        <?php endif; ?>

        <!-- COUNTDOWN -->
        <?php if ($is_active): ?>
        <p class="text-white-50 small mb-2"><i class="fa-solid fa-hourglass-half text-warning me-1"></i> Sự kiện đang diễn ra • Kết thúc sau:</p>
        <div class="countdown-wrap" id="eventCountdown" data-target="<?= htmlspecialchars($event['end_date']) ?>" data-mode="ending">
            <div class="countdown-box"><span class="countdown-num" id="cd-days">00</span><span class="countdown-lbl">Ngày</span></div>
            <div class="countdown-box"><span class="countdown-num" id="cd-hours">00</span><span class="countdown-lbl">Giờ</span></div>
            <div class="countdown-box"><span class="countdown-num" id="cd-mins">00</span><span class="countdown-lbl">Phút</span></div>
            <div class="countdown-box"><span class="countdown-num" id="cd-secs">00</span><span class="countdown-lbl">Giây</span></div>
        </div>
        <?php elseif ($is_upcoming): ?>
        <p class="text-warning small mb-2 fw-bold"><i class="fa-solid fa-clock text-warning me-1"></i> Sự kiện sắp diễn ra • Bắt đầu sau:</p>
        <div class="countdown-wrap" id="eventCountdown" data-target="<?= htmlspecialchars($event['start_date']) ?>" data-mode="starting">
            <div class="countdown-box"><span class="countdown-num" id="cd-days">00</span><span class="countdown-lbl">Ngày</span></div>
            <div class="countdown-box"><span class="countdown-num" id="cd-hours">00</span><span class="countdown-lbl">Giờ</span></div>
            <div class="countdown-box"><span class="countdown-num" id="cd-mins">00</span><span class="countdown-lbl">Phút</span></div>
            <div class="countdown-box"><span class="countdown-num" id="cd-secs">00</span><span class="countdown-lbl">Giây</span></div>
        </div>
        <div class="small text-white-50 mt-2"><i class="fa-regular fa-calendar-check me-1"></i> Thời gian mở bán: <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></div>
        <?php else: ?>
        <div class="badge bg-secondary fs-6 px-4 py-2 rounded-pill mt-2"><i class="fa-solid fa-flag-checkered me-1"></i> Sự kiện đã kết thúc</div>
        <?php endif; ?>
    </div>
</section>

<!-- MAIN CONTENT -->
<div class="container py-4">

    <!-- SẢN PHẨM SỰ KIỆN -->
    <div class="event-products-section">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-black mb-1" style="color: var(--event-color);">
                    <i class="fa-solid fa-tags me-2"></i>Sản Phẩm Sự Kiện
                </h2>
                <span class="text-muted small"><?= count($event_products) ?> sản phẩm được chọn lọc riêng cho sự kiện này</span>
            </div>
            <a href="all-products.php" class="btn btn-outline-secondary btn-sm rounded-pill">Xem Tất Cả Sản Phẩm</a>
        </div>

        <?php if (!empty($event_products)): ?>
        <div class="row g-3">
            <?php foreach($event_products as $p):
                $original_price = floatval($p['price']);
                $discount = intval($p['event_discount'] ?? ($p['discount_percent'] ?? 0));
                $display_price = !empty($p['event_sale_price']) ? floatval($p['event_sale_price']) : 0;

                // Tự động tính giá bán sự kiện từ giá gốc và % giảm nhập vào
                if ($display_price <= 0 && $discount > 0 && $original_price > 0) {
                    $display_price = round($original_price * (1 - ($discount / 100)));
                } elseif ($display_price <= 0) {
                    $display_price = $original_price;
                }

                // Tự động tính % giảm nếu chưa có
                if ($discount <= 0 && $original_price > $display_price && $original_price > 0) {
                    $discount = round((1 - ($display_price / $original_price)) * 100);
                }
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="event-product-card">
                    <div class="event-product-img">
                        <a href="product-detail.php?slug=<?= htmlspecialchars($p['slug']) ?>">
                            <img src="<?= htmlspecialchars($p['main_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                        </a>
                        <?php if ($discount > 0): ?>
                        <div class="event-discount-badge">-<?= $discount ?>%</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <div class="small text-muted mb-1"><?= htmlspecialchars($p['brand_name'] ?? '') ?></div>
                        <h6 class="fw-bold mb-2 lh-sm" style="font-size: 0.9rem;">
                            <a href="product-detail.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($p['name']) ?>
                            </a>
                        </h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="event-sale-price"><?= number_format($display_price, 0, ',', '.') ?>đ</span>
                            <?php if ($display_price < $original_price): ?>
                            <span class="event-orig-price"><?= number_format($original_price, 0, ',', '.') ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <a href="product-detail.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="btn btn-sm w-100 mt-2 rounded-pill fw-bold text-white" style="background: var(--event-color); border: none;">
                            <i class="fa-solid fa-shopping-bag me-1"></i> Mua Ngay
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-box-open fa-3x mb-3 opacity-50"></i>
            <p>Chưa có sản phẩm nào được thêm vào sự kiện này.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- VOUCHER SỰ KIỆN -->
    <?php if (!empty($event_vouchers)): ?>
    <div class="event-voucher-section p-4 rounded-4 mt-5" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(10px);">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="fa-solid fa-ticket-simple fs-4" style="color: var(--event-color);"></i>
            <h4 class="fw-bold text-white mb-0">Mã Giảm Giá Sự Kiện</h4>
        </div>
        <p class="text-white-50 small mb-4">Voucher độc quyền dành riêng cho sự kiện <strong><?= htmlspecialchars($event_name) ?></strong>. Lưu ngay kẻo hết lượt!</p>

        <div class="row g-3">
            <?php foreach($event_vouchers as $vc):
                $vid = intval($vc['id']);
                $is_saved = in_array($vid, $user_saved_ids);
                $vtype = $vc['discount_type'];

                if ($vtype === 'freeship') {
                    $theme_class = 'voucher-theme-emerald';
                    $stub_icon = 'fa-solid fa-truck-fast';
                    $stub_val = 'FREE';
                    $stub_label = 'FREESHIP';
                    $disc_badge = 'Miễn phí vận chuyển';
                } elseif ($vtype === 'percent') {
                    $theme_class = 'voucher-theme-gold';
                    $stub_icon = 'fa-solid fa-percent';
                    $stub_val = intval($vc['discount_value']).'%';
                    $stub_label = 'GIẢM GIÁ';
                    $disc_badge = 'Giảm '.intval($vc['discount_value']).'%';
                    if (floatval($vc['max_discount']) > 0) {
                        $disc_badge .= ' (Max '.number_format($vc['max_discount'],0,',','.').'đ)';
                    }
                } else {
                    $theme_class = 'voucher-theme-crimson';
                    $stub_icon = 'fa-solid fa-tag';
                    $stub_val = (floatval($vc['discount_value']) >= 1000) ? (intval($vc['discount_value']/1000).'K') : number_format($vc['discount_value'],0,',','.').'đ';
                    $stub_label = 'GIẢM TIỀN';
                    $disc_badge = 'Giảm '.number_format($vc['discount_value'],0,',','.').'đ';
                }
            ?>
            <div class="col-12 col-lg-6">
                <div class="voucher-ticket dark-theme <?= $theme_class ?> m-0 h-100">
                    <!-- Cuống vé -->
                    <div class="voucher-ticket-stub" style="background: linear-gradient(135deg, var(--event-color, #ec4899) 0%, rgba(15,23,42,0.9) 100%);">
                        <i class="<?= $stub_icon ?> voucher-stub-icon"></i>
                        <div class="voucher-stub-value"><?= $stub_val ?></div>
                        <div class="voucher-stub-label"><?= $stub_label ?></div>
                    </div>
                    
                    <!-- Đường rãnh xé vé -->
                    <div class="voucher-ticket-divider">
                        <div class="voucher-notch voucher-notch-top"></div>
                        <div class="voucher-notch voucher-notch-bottom"></div>
                    </div>

                    <!-- Thân vé -->
                    <div class="voucher-ticket-body">
                        <div class="voucher-info-wrapper">
                            <span class="voucher-badge-type" style="background: rgba(255,255,255,0.1); color: var(--event-color);">
                                <i class="fa-solid fa-sparkles me-1"></i><?= $disc_badge ?>
                            </span>
                            <h6 class="voucher-title text-white mb-1"><?= htmlspecialchars($vc['title'] ?? '') ?></h6>
                            <div class="voucher-conditions text-white-50 small">
                                Đơn tối thiểu: <strong class="text-warning"><?= number_format($vc['min_order_value'],0,',','.') ?>đ</strong>
                                <span class="mx-1">•</span>
                                HSD: <strong><?= date('d/m/Y', strtotime($vc['end_date'])) ?></strong>
                            </div>
                        </div>

                        <div class="voucher-action-area">
                            <div class="voucher-code-badge" data-code="<?= htmlspecialchars($vc['code']) ?>" title="Nhấn để sao chép mã">
                                <?= htmlspecialchars($vc['code']) ?> <i class="fa-regular fa-copy ms-1 opacity-75"></i>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($is_saved): ?>
                                    <button class="btn-voucher-action saved btn-save-voucher" disabled>
                                        <i class="fa-solid fa-check"></i> Đã Lưu
                                    </button>
                                <?php else: ?>
                                    <button class="btn-voucher-action btn-voucher-save btn-save-voucher"
                                            data-voucher-id="<?= $vid ?>"
                                            data-voucher-code="<?= htmlspecialchars($vc['code']) ?>">
                                        <i class="fa-solid fa-bookmark"></i> Lưu Mã
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<script>
// Countdown timer (Đếm ngược trực tiếp sự kiện bắt đầu hoặc kết thúc)
(function() {
    const wrap = document.getElementById('eventCountdown');
    if (!wrap) return;
    const targetStr = (wrap.dataset.target || wrap.dataset.end || '').replace(' ', 'T');
    const targetTime = new Date(targetStr).getTime();
    const mode = wrap.dataset.mode || 'ending';

    function tick() {
        const now = Date.now();
        const diff = targetTime - now;
        if (diff <= 0) { 
            if (mode === 'starting') {
                wrap.innerHTML = '<div class="badge bg-success text-white fs-6 px-4 py-2 rounded-pill"><i class="fa-solid fa-bolt me-1"></i> Sự kiện đã chính thức bắt đầu! Hãy tải lại trang để săn ưu đãi.</div>';
                setTimeout(() => { window.location.reload(); }, 2000);
            } else {
                wrap.innerHTML = '<span class="badge bg-secondary text-white fs-6 px-4 py-2 rounded-pill">Sự kiện đã kết thúc</span>';
            }
            return; 
        }
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        
        const elDays = document.getElementById('cd-days');
        const elHours = document.getElementById('cd-hours');
        const elMins = document.getElementById('cd-mins');
        const elSecs = document.getElementById('cd-secs');

        if (elDays) elDays.textContent  = String(d).padStart(2,'0');
        if (elHours) elHours.textContent = String(h).padStart(2,'0');
        if (elMins) elMins.textContent  = String(m).padStart(2,'0');
        if (elSecs) elSecs.textContent  = String(s).padStart(2,'0');
    }
    tick(); 
    setInterval(tick, 1000);
})();

// Lưu voucher
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-save-voucher:not([disabled])');
    if (!btn) return;
    e.preventDefault();
    const voucherId = btn.dataset.voucherId;
    const voucherCode = btn.dataset.voucherCode;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';

    const fd = new FormData();
    fd.append('voucher_id', voucherId);
    fd.append('voucher_code', voucherCode);

    fetch('api/save-voucher.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.require_login) { alert('Vui lòng đăng nhập để lưu voucher!'); window.location.href = 'login.php'; return; }
            if (data.success) {
                btn.classList.replace('btn-warning', 'btn-success');
                btn.classList.add('saved');
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã Lưu';

                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3'; toast.style.zIndex = '999999';
                toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 shadow-lg rounded-3"><div class="d-flex"><div class="toast-body fw-bold"><i class="fa-solid fa-circle-check me-2"></i>${data.message || 'Lưu voucher thành công!'}</div><button type="button" class="btn-close btn-close-white me-2 m-auto"></button></div></div>`;
                document.body.appendChild(toast);
                toast.querySelector('.btn-close').addEventListener('click', () => toast.remove());
                setTimeout(() => toast.remove(), 4000);
            } else {
                alert(data.message || 'Không thể lưu voucher!');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-bookmark me-1"></i> Lưu Mã';
            }
        })
        .catch(() => { alert('Lỗi kết nối!'); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-bookmark me-1"></i> Lưu Mã'; });
});

// Copy code
document.querySelectorAll('.copy-voucher-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        navigator.clipboard.writeText(this.dataset.code).then(() => {
            const orig = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-check"></i>';
            setTimeout(() => this.innerHTML = orig, 2000);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
