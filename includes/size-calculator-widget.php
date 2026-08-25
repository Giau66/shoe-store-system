<?php
// Load Size Guides for widget if not already fetched
if (!isset($widget_size_guides)) {
    $widget_size_guides = [];
    $w_sg_res = $conn->query("
        SELECT sg.*, b.name AS brand_name 
        FROM size_guides sg 
        LEFT JOIN brands b ON sg.brand_id = b.id 
        WHERE sg.status = 1 
        ORDER BY sg.foot_length_cm ASC, sg.sort_order ASC
    ");
    if ($w_sg_res) {
        while ($row = $w_sg_res->fetch_assoc()) {
            $widget_size_guides[] = $row;
        }
    }
}

// Load Brands for dropdown
if (!isset($widget_brands)) {
    $widget_brands = [];
    $w_b_res = $conn->query("SELECT id, name FROM brands WHERE status = 1 ORDER BY name ASC");
    if ($w_b_res) {
        while ($b = $w_b_res->fetch_assoc()) $widget_brands[] = $b;
    }
}
?>

<style>
/* =========================================================
   FLOATING SHOE SIZE CALCULATOR WIDGET (CHATBOX STYLE)
========================================================= */
.size-calc-toggle {
    position: fixed;
    bottom: 24px;
    left: 24px;
    z-index: 9980;
    height: 52px;
    padding: 0 20px 0 16px;
    border-radius: 50px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border: 2px solid rgba(245, 158, 11, 0.4);
    color: #ffffff;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.size-calc-toggle:hover {
    transform: scale(1.06) translateY(-2px);
    box-shadow: 0 12px 30px rgba(245, 158, 11, 0.35);
    border-color: #f59e0b;
    color: #fbbf24;
}
.size-calc-toggle i {
    color: #f59e0b;
    font-size: 18px;
    transition: transform 0.3s ease;
}
.size-calc-toggle:hover i {
    transform: rotate(15deg);
}

/* Chatbox Style Window */
.size-calc-window {
    position: fixed;
    bottom: 88px;
    left: 24px;
    z-index: 9985;
    width: 390px;
    max-width: calc(100vw - 32px);
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.08);
    display: none;
    flex-direction: column;
    overflow: hidden;
    font-family: inherit;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.size-calc-window.is-open {
    display: flex;
    animation: sizeCalcPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes sizeCalcPop {
    from { opacity: 0; transform: scale(0.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Widget Header */
.size-calc-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #ffffff;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.size-calc-title {
    font-weight: 800;
    font-size: 15px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.size-calc-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.2s;
}
.size-calc-close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

/* Widget Body */
.size-calc-body {
    padding: 20px;
    max-height: 480px;
    overflow-y: auto;
    background: #f8fafc;
}
.size-calc-body::-webkit-scrollbar {
    width: 5px;
}
.size-calc-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* Input group styling */
.widget-input-label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 6px;
    display: block;
}
.widget-cm-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 6px 10px;
}
.widget-cm-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 800;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.widget-cm-btn:hover {
    background: #e2e8f0;
}
.widget-cm-input {
    flex: 1;
    border: none;
    text-align: center;
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
    outline: none;
    width: 100%;
}

/* Result Bubble */
.widget-result-bubble {
    background: #ffffff;
    border: 2px solid #f59e0b;
    border-radius: 18px;
    padding: 16px;
    text-align: center;
    margin-top: 16px;
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.12);
}
.widget-res-size {
    font-size: 2.5rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
    letter-spacing: -0.5px;
}
.widget-res-chips {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.widget-chip {
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}
.widget-tip-note {
    font-size: 11.5px;
    color: #92400e;
    background: #fef3c7;
    padding: 8px 12px;
    border-radius: 10px;
    margin-top: 12px;
    text-align: left;
    line-height: 1.45;
}

/* Widget Footer */
.size-calc-footer {
    padding: 12px 20px;
    background: #ffffff;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    text-align: center;
}
.size-calc-footer a {
    color: #0f172a;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}
.size-calc-footer a:hover {
    color: #d97706;
    text-decoration: underline;
}

/* Dark Mode */
[data-theme="dark"] .size-calc-window {
    background: #111827 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .size-calc-body {
    background: #0b1120 !important;
}
[data-theme="dark"] .widget-cm-box {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
}
[data-theme="dark"] .widget-cm-btn {
    background: #334155 !important;
    color: #ffffff !important;
}
[data-theme="dark"] .widget-cm-input {
    background: transparent !important;
    color: #ffffff !important;
}
[data-theme="dark"] .widget-result-bubble {
    background: #1e293b !important;
}
[data-theme="dark"] .widget-res-size {
    color: #ffffff !important;
}
[data-theme="dark"] .widget-chip {
    background: #334155 !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .size-calc-footer {
    background: #111827 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] .size-calc-footer a {
    color: #fbbf24 !important;
}

/* Mobile Adjustments */
@media(max-width: 480px) {
    .size-calc-toggle {
        left: 12px;
        bottom: 16px;
        height: 46px;
        padding: 0 14px 0 12px;
        font-size: 13px;
    }
    .size-calc-window {
        left: 8px;
        right: 8px;
        width: auto;
        bottom: 74px;
    }
}
</style>

<!-- FLOATING SIZE CALCULATOR TOGGLE BUTTON -->
<button class="size-calc-toggle" id="sizeCalcToggleBtn" onclick="toggleSizeCalcWidget()" title="Mở máy tính gợi ý size giày">
    <i class="fa-solid fa-ruler-combined"></i>
    <span>Tính Size</span>
</button>

<!-- FLOATING CHATBOX-STYLE WINDOW -->
<div class="size-calc-window" id="sizeCalcWindow">
    <!-- Header -->
    <div class="size-calc-header">
        <h6 class="size-calc-title">
            <i class="fa-solid fa-wand-magic-sparkles text-warning"></i>
            Gợi Ý Size Giày Chuẩn
        </h6>
        <button class="size-calc-close-btn" onclick="toggleSizeCalcWidget()" title="Đóng">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Body -->
    <div class="size-calc-body">
        <!-- 1. Chiều dài chân -->
        <div class="mb-3">
            <label class="widget-input-label">Chiều Dài Bàn Chân (cm)</label>
            <div class="widget-cm-box">
                <button type="button" class="widget-cm-btn" onclick="adjustWidgetCm(-0.5)">-</button>
                <input type="number" id="widgetFootCm" class="widget-cm-input" value="24.5" step="0.1" min="18" max="33" oninput="runWidgetCalculator()">
                <span class="fw-bold text-muted small pe-1">cm</span>
                <button type="button" class="widget-cm-btn" onclick="adjustWidgetCm(0.5)">+</button>
            </div>
            <small class="text-muted" style="font-size: 10.5px;">Đo từ gót chân đến ngón dài nhất</small>
        </div>

        <!-- 2. Hãng giày -->
        <div class="mb-3">
            <label class="widget-input-label">Thương Hiệu</label>
            <select id="widgetBrandSelect" class="form-select form-select-sm fw-semibold rounded-3" onchange="runWidgetCalculator()">
                <option value="0">Tất Cả (Form Chuẩn)</option>
                <?php foreach($widget_brands as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 3. Tùy chọn chân bè -->
        <div class="form-check form-switch p-2 bg-white rounded-3 border mb-3" style="font-size: 12.5px;">
            <input class="form-check-input ms-0 me-2" type="checkbox" id="widgetWideFoot" onchange="runWidgetCalculator()">
            <label class="form-check-label fw-bold text-dark" for="widgetWideFoot">
                🦶 Chân Bè Ngang / Mu Dày (+1 Size)
            </label>
        </div>

        <!-- 4. Result Bubble -->
        <div class="widget-result-bubble">
            <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 10px;">Size Đề Xuất Dành Cho Bạn</span>
            <div class="widget-res-size" id="wResSizeEu">EU 39</div>
            <div class="fw-bold text-warning small mt-1" id="wResBrandName">Form Chuẩn Quốc Tế</div>

            <div class="widget-res-chips">
                <span class="widget-chip" id="wResSizeUs">US: <strong>6.5</strong></span>
                <span class="widget-chip" id="wResSizeUk">UK: <strong>5.5</strong></span>
                <span class="widget-chip" id="wResSizeInsole">Lòng: <strong>24.5 cm</strong></span>
            </div>

            <div class="widget-tip-note" id="wResNoteText">
                <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                Vừa vặn thoải mái cho bàn chân 24.5 cm.
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="size-calc-footer">
        <a href="size-guide.php">
            <i class="fa-solid fa-book-open me-1"></i> Xem Cẩm Nang Hướng Dẫn Đo Chân &amp; Bảng Size →
        </a>
    </div>
</div>

<script>
(function() {
    const WIDGET_SIZE_DATA = <?= json_encode($widget_size_guides) ?>;
    let widgetIsOpen = false;

    window.toggleSizeCalcWidget = function() {
        const win = document.getElementById('sizeCalcWindow');
        widgetIsOpen = !widgetIsOpen;
        if (widgetIsOpen) {
            win.classList.add('is-open');
            runWidgetCalculator();
        } else {
            win.classList.remove('is-open');
        }
    };

    window.adjustWidgetCm = function(delta) {
        const input = document.getElementById('widgetFootCm');
        let cur = parseFloat(input.value) || 24.5;
        cur = Math.round((cur + delta) * 10) / 10;
        if (cur >= 18 && cur <= 33) {
            input.value = cur;
            runWidgetCalculator();
        }
    };

    window.runWidgetCalculator = function() {
        let footCm = parseFloat(document.getElementById('widgetFootCm').value);
        const brandId = document.getElementById('widgetBrandSelect').value;
        const isWide = document.getElementById('widgetWideFoot').checked;

        if (!footCm || footCm < 15 || footCm > 35) return;

        let calcLength = footCm;
        if (isWide) calcLength += 0.5;

        let matched = null;
        let closestDiff = 999;

        WIDGET_SIZE_DATA.forEach(item => {
            if (brandId !== '0' && item.brand_id && item.brand_id != brandId) return;

            const diff = Math.abs(parseFloat(item.foot_length_cm) - calcLength);
            if (diff < closestDiff) {
                closestDiff = diff;
                matched = item;
            }
        });

        if (!matched) {
            const estEu = Math.round((calcLength + 1.5) * 1.5);
            matched = {
                size_eu: estEu.toString(),
                size_us: (estEu - 33).toString(),
                size_uk: (estEu - 34).toString(),
                size_cm: (calcLength + 0.5).toFixed(1),
                brand_name: 'Form Chuẩn',
                note: 'Gợi ý chuẩn kích thước'
            };
        }

        document.getElementById('wResSizeEu').innerText = 'EU ' + matched.size_eu;
        document.getElementById('wResBrandName').innerText = (matched.brand_name || 'Form Chuẩn') + (isWide ? ' (Đã cộng size chân bè)' : '');
        document.getElementById('wResSizeUs').innerHTML = 'US: <strong>' + (matched.size_us || '-') + '</strong>';
        document.getElementById('wResSizeUk').innerHTML = 'UK: <strong>' + (matched.size_uk || '-') + '</strong>';
        document.getElementById('wResSizeInsole').innerHTML = 'Lòng: <strong>' + (matched.size_cm || matched.foot_length_cm) + ' cm</strong>';

        let note = "Vừa vặn thoải mái cho bàn chân " + footCm + " cm.";
        if (isWide) {
            note = "Đã tính thêm độ dư thoải mái cho bàn chân bè / mu dày.";
        } else if (matched.note) {
            note = matched.note;
        }
        document.getElementById('wResNoteText').innerHTML = '<i class="fa-solid fa-lightbulb text-warning me-1"></i> ' + note;
    };
})();
</script>
