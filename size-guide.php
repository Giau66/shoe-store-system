<?php
require_once 'includes/header.php';

// 1. Lấy danh sách thương hiệu có trong bảng size_guides
$brands_res = $conn->query("
    SELECT DISTINCT b.id, b.name 
    FROM brands b 
    JOIN size_guides sg ON sg.brand_id = b.id 
    WHERE sg.status = 1 
    ORDER BY b.name ASC
");
$chart_brands = [];
if ($brands_res) {
    while ($b = $brands_res->fetch_assoc()) $chart_brands[] = $b;
}

// 2. Lấy toàn bộ dữ liệu bảng quy đổi size
$all_size_guides = [];
$sg_res = $conn->query("
    SELECT sg.*, b.name AS brand_name 
    FROM size_guides sg 
    LEFT JOIN brands b ON sg.brand_id = b.id 
    WHERE sg.status = 1 
    ORDER BY sg.foot_length_cm ASC, sg.sort_order ASC
");
if ($sg_res) {
    while ($r = $sg_res->fetch_assoc()) {
        $all_size_guides[] = $r;
    }
}

// 3. Lấy danh sách các bước hướng dẫn đo bàn chân
$guide_tips = [];
$gt_res = $conn->query("SELECT * FROM size_guide_tips WHERE status = 1 ORDER BY step_number ASC, sort_order ASC");
if ($gt_res) {
    while ($t = $gt_res->fetch_assoc()) {
        $guide_tips[] = $t;
    }
}
?>

<style>
/* =========================================================
   SIZE GUIDE PAGE STYLES
========================================================= */
.size-guide-hero {
    background: radial-gradient(circle at 15% 20%, rgba(197, 160, 89, 0.2), transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(239, 68, 68, 0.15), transparent 45%),
                linear-gradient(135deg, #090d16 0%, #111827 50%, #0f172a 100%);
    color: #fff;
    padding: 60px 0 45px;
    position: relative;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.size-guide-hero-badge {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.35);
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}
.size-guide-title {
    font-size: clamp(2rem, 4.5vw, 3rem);
    font-weight: 900;
    letter-spacing: -0.5px;
    line-height: 1.2;
    margin-bottom: 12px;
}
.size-guide-subtitle {
    color: #94a3b8;
    font-size: 1.05rem;
    max-width: 680px;
}

/* Calculator Card */
.calculator-card {
    background: var(--card-white, #ffffff);
    border-radius: 24px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 15px 40px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-top: -35px;
    position: relative;
    z-index: 10;
}
.calc-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #fff;
    padding: 24px 30px;
}
.calc-body {
    padding: 30px;
}
.calc-result-box {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 20px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s;
}
.calc-result-box.active {
    background: linear-gradient(145deg, #fffbeb, #fef3c7);
    border-color: #f59e0b;
    border-style: solid;
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
}
.calc-main-size {
    font-size: 3.5rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
    letter-spacing: -1px;
}
.calc-sub-sizes {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}
.sub-size-chip {
    background: #fff;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    color: #475569;
}

/* 4 Measuring Steps */
.step-card {
    background: var(--card-white, #ffffff);
    border-radius: 20px;
    padding: 28px 24px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 8px 25px rgba(0,0,0,0.03);
    height: 100%;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.step-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 35px rgba(0,0,0,0.08);
    border-color: #f59e0b;
}
.step-number-badge {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #0f172a;
    color: #f59e0b;
    font-weight: 900;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}
.step-icon-wrap {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: #f1f5f9;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 18px;
    transition: all 0.3s;
}
.step-card:hover .step-icon-wrap {
    background: #f59e0b;
    color: #fff;
    transform: scale(1.08);
}
.step-title {
    font-weight: 800;
    font-size: 1.15rem;
    color: var(--primary-dark, #0f172a);
    margin-bottom: 10px;
}
.step-desc {
    color: #64748b;
    font-size: 0.92rem;
    line-height: 1.6;
    margin-bottom: 0;
}

/* Size Chart Table */
.size-table-card {
    background: var(--card-white, #ffffff);
    border-radius: 24px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    overflow: hidden;
}
.size-table thead th {
    background: #0f172a;
    color: #fff;
    font-weight: 700;
    font-size: 0.88rem;
    padding: 14px 16px;
    border: none;
    text-align: center;
}
.size-table tbody td {
    padding: 14px 16px;
    text-align: center;
    font-size: 0.92rem;
    border-bottom: 1px solid #f1f5f9;
}
.size-table tbody tr:hover td {
    background-color: #f8fafc;
}
.size-table .col-eu {
    font-weight: 900;
    font-size: 1.1rem;
    color: #2563eb;
    background: rgba(37, 99, 235, 0.04);
}
.size-table .col-foot {
    font-weight: 800;
    color: #d97706;
}

/* Brand Filter Pills */
.brand-tab-btn {
    padding: 9px 22px;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
    cursor: pointer;
}
.brand-tab-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.brand-tab-btn.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.25);
}

/* Dark Mode Overrides */
[data-theme="dark"] .calculator-card,
[data-theme="dark"] .step-card,
[data-theme="dark"] .size-table-card {
    background: #111827 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] .calc-result-box {
    background: #0b1120 !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
}
[data-theme="dark"] .calc-result-box.active {
    background: rgba(245, 158, 11, 0.1) !important;
    border-color: #f59e0b !important;
}
[data-theme="dark"] .calc-main-size {
    color: #f8fafc !important;
}
[data-theme="dark"] .step-title,
[data-theme="dark"] .table-title {
    color: #f8fafc !important;
}
[data-theme="dark"] .step-icon-wrap {
    background: #1e293b !important;
    color: #f8fafc !important;
}
[data-theme="dark"] .sub-size-chip {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .brand-tab-btn {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .brand-tab-btn.active {
    background: #f59e0b !important;
    color: #0f172a !important;
}
[data-theme="dark"] .size-table tbody td {
    border-color: rgba(255, 255, 255, 0.06) !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .size-table tbody tr:hover td {
    background: rgba(255, 255, 255, 0.03) !important;
}
[data-theme="dark"] .size-table .col-eu {
    background: rgba(37, 99, 235, 0.12) !important;
    color: #60a5fa !important;
}
</style>

<!-- 1. HERO BANNER HEADER -->
<section class="size-guide-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="size-guide-hero-badge">
                    <i class="fa-solid fa-ruler-combined"></i> CẨM NANG CHỌN SIZE 2026
                </div>
                <h1 class="size-guide-title">
                    Bảng Quy Đổi &amp; Hướng Dẫn Chọn Size Giày Chuẩn Xác
                </h1>
                <p class="size-guide-subtitle">
                    Đo bàn chân dễ dàng tại nhà chỉ với 3 phút. Tra cứu size giày chính hãng Nike, Adidas, Puma, MLB, Converse, Vans chuẩn từng milimet.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="#sizeCalculator" class="btn btn-warning btn-lg fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa-solid fa-calculator me-2"></i>Tính Size Nhanh
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. INTERACTIVE SMART SIZE CALCULATOR -->
<div class="container" id="sizeCalculator">
    <div class="calculator-card">
        <div class="calc-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i>Công Cụ Gợi Ý Size Giày Chuẩn 99%</h4>
                <p class="small text-white-50 mb-0">Nhập độ dài chân của bạn để hệ thống tự động quy đổi size chính xác</p>
            </div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                <i class="fa-solid fa-shield-check me-1"></i> Tự Động Quy Đổi EU / US / UK
            </span>
        </div>

        <div class="calc-body">
            <div class="row g-4 align-items-center">
                <!-- Inputs -->
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">1. Chiều dài bàn chân (cm)</label>
                            <div class="input-group input-group-lg">
                                <input type="number" id="calcFootCm" class="form-control fw-bold fs-4 text-dark" placeholder="VD: 24.5" step="0.1" min="20" max="32" value="24.5">
                                <span class="input-group-text fw-bold bg-light">cm</span>
                            </div>
                            <small class="text-muted">Đo từ gót chân đến đầu ngón chân dài nhất</small>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">2. Thương hiệu cần mua</label>
                            <select id="calcBrand" class="form-select form-select-lg fw-semibold">
                                <option value="0">Tất cả (Chuẩn Chung)</option>
                                <?php foreach($chart_brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">3. Đối tượng</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-dark flex-grow-1 fw-bold gender-btn active" data-gender="all">Tất cả</button>
                                <button type="button" class="btn btn-outline-dark flex-grow-1 fw-bold gender-btn" data-gender="nam">Nam</button>
                                <button type="button" class="btn btn-outline-dark flex-grow-1 fw-bold gender-btn" data-gender="nu">Nữ</button>
                            </div>
                        </div>

                        <div class="col-sm-6 d-flex align-items-end">
                            <div class="form-check form-switch p-3 bg-light rounded-3 border w-100 mb-0">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="calcWideFoot">
                                <label class="form-check-label fw-bold text-dark small" for="calcWideFoot">
                                    🦶 Chân Bè Ngang / Mu Chân Dày
                                </label>
                            </div>
                        </div>

                        <div class="col-12 pt-2">
                            <button type="button" onclick="runSizeCalculator()" class="btn btn-dark btn-lg w-100 fw-bold rounded-pill shadow-sm">
                                <i class="fa-solid fa-bolt text-warning me-2"></i>GỢI Ý SIZE GIÀY PHÙ HỢP
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Result Box -->
                <div class="col-lg-5">
                    <div class="calc-result-box active" id="calcResultDisplay">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Size Giày Đề Xuất Dành Cho Bạn</span>
                        <div class="calc-main-size" id="resSizeEu">EU 39</div>
                        <div class="fw-bold text-warning mt-1" id="resBrandName">Form Chuẩn Quốc Tế</div>
                        
                        <div class="calc-sub-sizes">
                            <span class="sub-size-chip" id="resSizeUs">US: <strong>6.5</strong></span>
                            <span class="sub-size-chip" id="resSizeUk">UK: <strong>5.5</strong></span>
                            <span class="sub-size-chip" id="resSizeInsole">Lòng giày: <strong>24.5 cm</strong></span>
                        </div>

                        <div class="alert alert-warning border-0 rounded-3 mt-3 mb-0 small text-start p-2" id="resNoteTip">
                            <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                            <span id="resNoteText">Độ vừa vặn tối ưu. Nếu bạn thích mang giày ôm chân có thể giữ nguyên size 39.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. 4 BƯỚC ĐO SIZE BÀN CHÂN TẠI NHÀ -->
<section class="py-5">
    <div class="container py-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">Quy Trình Chuẩn</span>
            <h2 class="fw-bold text-dark">4 Bước Tự Đo Chiều Dài Bàn Chân Tại Nhà</h2>
            <p class="text-muted">Chỉ cần 1 tờ giấy A4, 1 chiếc bút chì và 1 cây thước kẻ để tìm được size giày chính xác 100%</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($guide_tips)): ?>
                <?php foreach($guide_tips as $tip): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number-badge">
                            0<?= $tip['step_number'] ?>
                        </div>
                        <div class="step-icon-wrap">
                            <i class="<?= htmlspecialchars($tip['icon'] ?: 'fa-solid fa-ruler') ?>"></i>
                        </div>
                        <h4 class="step-title"><?= htmlspecialchars($tip['title']) ?></h4>
                        <p class="step-desc"><?= nl2br(htmlspecialchars($tip['description'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default 4 Steps Fallback -->
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number-badge">01</div>
                        <div class="step-icon-wrap"><i class="fa-solid fa-pencil"></i></div>
                        <h4 class="step-title">Chuẩn Bị Dụng Cụ</h4>
                        <p class="step-desc">Chuẩn bị 1 tờ giấy A4 trắng lớn hơn bàn chân, 1 cây bút chì và 1 cây thước kẻ thẳng.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number-badge">02</div>
                        <div class="step-icon-wrap"><i class="fa-solid fa-shoe-prints"></i></div>
                        <h4 class="step-title">Vẽ Khung Bàn Chân</h4>
                        <p class="step-desc">Đặt chân phẳng lên giấy, gót chân tựa sát tường. Giữ bút vuông góc 90 độ và vẽ men theo mép chân.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number-badge">03</div>
                        <div class="step-icon-wrap"><i class="fa-solid fa-ruler-combined"></i></div>
                        <h4 class="step-title">Đo Chiều Dài L</h4>
                        <p class="step-desc">Dùng thước đo khoảng cách từ điểm gót xa nhất đến ngón chân dài nhất để có chiều dài L (cm).</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number-badge">04</div>
                        <div class="step-icon-wrap"><i class="fa-solid fa-circle-check"></i></div>
                        <h4 class="step-title">Tra Bảng Size</h4>
                        <p class="step-desc">Lấy L + 0.5cm (độ dư êm ái) và tra bảng quy đổi phía dưới để chọn size giày hoàn hảo.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. BẢNG TRA CỨU QUY ĐỔI SIZE THEO HÃNG -->
<section class="py-5 bg-light" id="sizeTableSection">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill text-uppercase mb-1">Tra Cứu Chi Tiết</span>
                <h2 class="fw-bold text-dark mb-0">Bảng Quy Đổi Kích Thước (Size Chart)</h2>
            </div>
            
            <!-- Quick Filter Brands -->
            <div class="d-flex gap-2 flex-wrap" id="brandPillGroup">
                <button type="button" class="brand-tab-btn active" data-filter="all">Tất Cả</button>
                <button type="button" class="brand-tab-btn" data-filter="general">Chuẩn Chung</button>
                <?php foreach($chart_brands as $b): ?>
                    <button type="button" class="brand-tab-btn" data-filter="brand_<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="size-table-card">
            <div class="table-responsive">
                <table class="table size-table align-middle mb-0" id="fullSizeTable">
                    <thead>
                        <tr>
                            <th>Thương Hiệu / Phân Loại</th>
                            <th>Chiều Dài Bàn Chân</th>
                            <th class="text-warning">Size EU (Việt Nam)</th>
                            <th>Size US</th>
                            <th>Size UK</th>
                            <th>Lòng Giày (CM)</th>
                            <th>Lời Khuyên Khi Chọn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_size_guides)): ?>
                            <?php foreach($all_size_guides as $row): 
                                $filter_tag = empty($row['brand_id']) ? 'general' : 'brand_' . $row['brand_id'];
                            ?>
                            <tr data-brand-tag="<?= $filter_tag ?>" data-gender-tag="<?= htmlspecialchars($row['gender']) ?>">
                                <td>
                                    <strong><?= htmlspecialchars($row['brand_name'] ?: 'Chuẩn Chung') ?></strong>
                                    <span class="badge bg-light text-muted border rounded-pill ms-1" style="font-size:10px;">
                                        <?= strtoupper($row['gender']) ?>
                                    </span>
                                </td>
                                <td class="col-foot"><i class="fa-solid fa-ruler-horizontal me-1"></i><?= $row['foot_length_cm'] ?> cm</td>
                                <td class="col-eu">EU <?= htmlspecialchars($row['size_eu']) ?></td>
                                <td><?= htmlspecialchars($row['size_us'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['size_uk'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['size_cm'] ?: $row['foot_length_cm']) ?> cm</td>
                                <td class="text-muted small text-start">
                                    <?= htmlspecialchars($row['note'] ?: 'Chuẩn size theo form hãng') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- 5. MẸO VÀNG CHỌN SIZE GIÀY -->
<section class="py-5">
    <div class="container py-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <h2 class="fw-bold text-dark">5 Bí Quyết Vàng Khi Chọn Size Giày Online</h2>
            <p class="text-muted">Đảm bảo mang vừa vặn, không đau chân và tôn dáng chuẩn nhất</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                    <div class="text-warning fs-3 mb-3"><i class="fa-solid fa-clock"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Đo Chân Vào Cuối Ngày</h5>
                    <p class="text-muted small mb-0">Sau một ngày dài vận động, bàn chân thường nở to nhất. Đo vào buổi chiều/tối sẽ giúp bạn chọn size thoải mái nhất, không bị chật mũi.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                    <div class="text-warning fs-3 mb-3"><i class="fa-solid fa-socks"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Mang Kèm Tất (Vớ) Thường Dùng</h5>
                    <p class="text-muted small mb-0">Khi đo chân, hãy mang loại tất mà bạn dự định sẽ đi cùng đôi giày đó (tất cổ cao, tất dày thể thao...) để kích thước thực tế chính xác nhất.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                    <div class="text-warning fs-3 mb-3"><i class="fa-solid fa-arrows-left-right"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Chọn Theo Bàn Chân Lớn Hơn</h5>
                    <p class="text-muted small mb-0">Hai bàn chân người thường không đều nhau 100%. Luôn đo cả 2 chân và lấy số đo của bên bàn chân dài/to hơn làm chuẩn.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                    <div class="text-warning fs-3 mb-3"><i class="fa-solid fa-person-running"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Giày Chạy Bộ &amp; Thể Thao (+0.5 Size)</h5>
                    <p class="text-muted small mb-0">Khi chạy hoặc tập gym, bàn chân trượt về phía trước nhiều. Nên chọn tăng thêm 0.5 đến 1 size so với giày thời trang dạo phố thông thường.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                    <div class="text-warning fs-3 mb-3"><i class="fa-solid fa-rotate-left"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Đổi Size Miễn Phí Tại Shop</h5>
                    <p class="text-muted small mb-0">Yên tâm mua sắm: Nếu nhận giày mà chưa vừa chân, Shop hỗ trợ đổi size linh hoạt trong 7 ngày trên toàn quốc.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="p-4 bg-dark text-white rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-warning fs-3 mb-3"><i class="fa-solid fa-headset"></i></div>
                        <h5 class="fw-bold mb-2">Cần Tư Vấn Thêm?</h5>
                        <p class="text-white-50 small mb-0">Liên hệ ngay hotline hoặc chat Zalo với đội ngũ CSKH để được tư vấn form giày theo từng mẫu cụ thể.</p>
                    </div>
                    <div class="mt-4">
                        <a href="all-products.php" class="btn btn-warning fw-bold rounded-pill w-100">
                            Khám Phá Sản Phẩm Ngay <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JAVASCRIPT CALCULATOR LOGIC & TAB FILTER -->
<script>
const SIZE_DATABASE = <?= json_encode($all_size_guides) ?>;
let selectedGender = 'all';

// Gender Buttons
document.querySelectorAll('.gender-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active', 'btn-dark'));
        this.classList.add('active');
        selectedGender = this.dataset.gender;
        runSizeCalculator();
    });
});

// Calculate Size Function
function runSizeCalculator() {
    let footCm = parseFloat(document.getElementById('calcFootCm').value);
    const brandId = document.getElementById('calcBrand').value;
    const isWide = document.getElementById('calcWideFoot').checked;

    if (!footCm || footCm < 15 || footCm > 35) {
        alert('Vui lòng nhập chiều dài bàn chân hợp lệ từ 18 cm đến 33 cm!');
        return;
    }

    // Nếu chân bè, tăng thêm 0.5cm vào thuật toán
    let calcLength = footCm;
    if (isWide) {
        calcLength += 0.5;
    }

    // Tìm trong database size_guides
    let matched = null;
    let closestDiff = 999;

    SIZE_DATABASE.forEach(item => {
        // Lọc brand nếu có chọn
        if (brandId !== '0' && item.brand_id && item.brand_id != brandId) {
            return;
        }
        // Lọc gender nếu chọn
        if (selectedGender !== 'all' && item.gender !== 'all' && item.gender !== selectedGender) {
            return;
        }

        const diff = Math.abs(parseFloat(item.foot_length_cm) - calcLength);
        if (diff < closestDiff) {
            closestDiff = diff;
            matched = item;
        }
    });

    // Fallback nếu không có dòng nào khớp
    if (!matched) {
        // Thuật toán chuẩn EU = (Chiều dài + 1.5) * 1.5
        const estEu = Math.round((calcLength + 1.5) * 1.5);
        matched = {
            size_eu: estEu.toString(),
            size_us: (estEu - 33).toString(),
            size_uk: (estEu - 34).toString(),
            size_cm: (calcLength + 0.5).toFixed(1),
            brand_name: 'Form Chuẩn Quốc Tế',
            note: 'Gợi ý theo chuẩn công thức quốc tế'
        };
    }

    // Hiển thị kết quả
    document.getElementById('resSizeEu').innerText = 'EU ' + matched.size_eu;
    document.getElementById('resBrandName').innerText = (matched.brand_name || 'Form Chuẩn') + (isWide ? ' (Đã cộng size chân bè)' : '');
    document.getElementById('resSizeUs').innerHTML = 'US: <strong>' + (matched.size_us || '-') + '</strong>';
    document.getElementById('resSizeUk').innerHTML = 'UK: <strong>' + (matched.size_uk || '-') + '</strong>';
    document.getElementById('resSizeInsole').innerHTML = 'Lòng giày: <strong>' + (matched.size_cm || matched.foot_length_cm) + ' cm</strong>';

    let note = "Size giày vừa vặn thoải mái cho chân " + footCm + " cm.";
    if (isWide) {
        note = "Đã tính thêm độ dư cho bàn chân bè ngang. Giày sẽ không bị bó mu chân.";
    } else if (matched.note) {
        note = matched.note;
    }
    document.getElementById('resNoteText').innerText = note;
}

// Brand Filter Tabs for Table
document.querySelectorAll('.brand-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.brand-tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const filter = this.dataset.filter;
        const rows = document.querySelectorAll('#fullSizeTable tbody tr');

        rows.forEach(r => {
            if (filter === 'all') {
                r.style.display = '';
            } else if (filter === 'general' && r.dataset.brandTag === 'general') {
                r.style.display = '';
            } else if (r.dataset.brandTag === filter) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    });
});

// Chạy tính toán mẫu ngay khi vào trang
window.addEventListener('DOMContentLoaded', runSizeCalculator);
</script>

<?php require_once 'includes/footer.php'; ?>