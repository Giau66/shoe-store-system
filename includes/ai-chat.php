<?php
@include_once __DIR__ . '/../config/db.php';
global $conn;

$cai_products_catalog = [];
if (isset($conn) && $conn) {
    $res_cat = $conn->query("
        SELECT p.id, p.name, p.price, p.old_price, p.discount_percent, p.main_image, p.gender, p.sold_count, p.is_hot,
               COALESCE(b.name, 'Khác') AS brand,
               COALESCE(c.name, '') AS category
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 1
        ORDER BY p.is_hot DESC, p.sold_count DESC, p.id DESC
        LIMIT 40
    ");
    if ($res_cat) {
        while ($row = $res_cat->fetch_assoc()) {
            $cai_products_catalog[] = [
                'id'       => (int)$row['id'],
                'name'     => $row['name'],
                'price'    => (float)$row['price'],
                'old_price'=> (float)$row['old_price'],
                'discount' => (int)$row['discount_percent'],
                'image'    => $row['main_image'],
                'brand'    => $row['brand'],
                'category' => $row['category'],
                'gender'   => $row['gender'] ?? 'Unisex',
                'sold'     => (int)$row['sold_count'],
                'url'      => 'product-detail.php?id=' . $row['id']
            ];
        }
    }
}
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
          . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
          . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<script>
window.CAI_CATALOG = <?= json_encode($cai_products_catalog, JSON_UNESCAPED_UNICODE) ?> || [];
</script>
<!-- ============================================================ -->
<!-- SHOES AI CHAT V3.0 - ChatGPT/Gemini Style                   -->
<!-- ============================================================ -->
<style>
/* ========== GLOBAL CHAT VARS ========== */
:root {
    --cai-primary: #e05b7f;
    --cai-dark: #1a1a2e;
    --cai-msg-bg: #f7f7f8;
    --cai-user-bg: #e05b7f;
    --cai-border: rgba(0,0,0,0.08);
    --cai-radius: 20px;
    --cai-w: 420px;
    --cai-h: 600px;
}

/* ========== TOGGLE BUTTON ========== */
.cai-toggle {
    position: fixed !important; bottom: 20px !important; right: 20px !important; z-index: 999999 !important;
    width: 58px; height: 58px; border-radius: 50%;
    background: linear-gradient(135deg,#e05b7f,#c2185b);
    border: none; color: #fff; cursor: pointer;
    box-shadow: 0 6px 24px rgba(224,91,127,0.5);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.cai-toggle:hover { transform: scale(1.08); box-shadow: 0 10px 30px rgba(224,91,127,0.55); }
.cai-toggle .cai-notif {
    position: absolute; top: -2px; right: -2px;
    width: 14px; height: 14px; border-radius: 50%;
    background: #ff4757; border: 2px solid #fff;
    animation: cai-pulse 2s ease-in-out infinite;
}
@keyframes cai-pulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.3);opacity:.8} }

/* ========== CHAT WINDOW ========== */
.cai-window {
    position: fixed !important; bottom: 85px !important; right: 20px !important; z-index: 999999 !important;
    width: 400px !important; max-width: calc(100vw - 28px) !important;
    height: min(560px, calc(100vh - 105px)) !important; max-height: calc(100vh - 105px) !important;
    background: #fff !important;
    border-radius: var(--cai-radius);
    box-shadow: 0 20px 60px rgba(0,0,0,0.22), 0 0 0 1px rgba(0,0,0,0.08) !important;
    display: none; flex-direction: column !important; overflow: hidden !important;
    font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
}
.cai-window.cai-open {
    display: flex;
    animation: cai-pop 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes cai-pop {
    from { opacity:0; transform: scale(0.88) translateY(18px); }
    to   { opacity:1; transform: scale(1)    translateY(0); }
}

/* ========== HEADER ========== */
.cai-header {
    background: linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);
    padding: 14px 16px;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.cai-header-left { display: flex; align-items: center; gap: 10px; }
.cai-avatar-wrap {
    position: relative;
    width: 38px; height: 38px;
}
.cai-avatar-wrap img {
    width: 38px; height: 38px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.3);
    object-fit: cover;
}
.cai-online-dot {
    position: absolute; bottom: 1px; right: 1px;
    width: 10px; height: 10px; border-radius: 50%;
    background: #22c55e; border: 2px solid #1a1a2e;
}
.cai-header-info { line-height: 1.2; }
.cai-header-name { color: #fff; font-weight: 700; font-size: 14px; }
.cai-header-sub  { color: rgba(255,255,255,0.6); font-size: 11px; margin-top: 1px; }
.cai-header-actions { display: flex; gap: 6px; }
.cai-header-btn {
    width: 30px; height: 30px; border-radius: 8px; border: none;
    background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.8);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 13px; transition: background 0.15s;
}
.cai-header-btn:hover { background: rgba(255,255,255,0.2); color: #fff; }

/* ========== MESSAGES AREA ========== */
.cai-messages {
    flex: 1; overflow-y: auto; padding: 16px;
    display: flex; flex-direction: column; gap: 14px;
    background: #fafafa;
    scroll-behavior: smooth;
}
.cai-messages::-webkit-scrollbar { width: 4px; }
.cai-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 4px; }

/* ========== MESSAGE ROW ========== */
.cai-msg-row {
    display: flex; gap: 8px; align-items: flex-start;
    animation: cai-msg-in 0.2s ease;
}
.cai-msg-row.cai-user { flex-direction: row-reverse; }
@keyframes cai-msg-in { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

.cai-msg-avatar {
    width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.cai-msg-row:not(.cai-user) .cai-msg-avatar {
    background: linear-gradient(135deg,#e05b7f,#c2185b);
    color: #fff;
}
.cai-msg-row.cai-user .cai-msg-avatar {
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: #fff;
}

.cai-msg-content { flex: 1; min-width: 0; }
.cai-msg-bubble {
    display: inline-block; max-width: 100%;
    padding: 10px 14px;
    border-radius: 18px;
    font-size: 13.5px; line-height: 1.6;
    word-break: break-word;
}
.cai-msg-row:not(.cai-user) .cai-msg-bubble {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.09);
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    color: #1a1a2e;
    text-align: left;
}
.cai-msg-row.cai-user .cai-msg-bubble {
    background: linear-gradient(135deg, #e05b7f, #c2185b);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.cai-msg-row.cai-user .cai-msg-content { display: flex; flex-direction: column; align-items: flex-end; }

/* Markdown trong bot bubble */
.cai-msg-bubble b, .cai-msg-bubble strong { font-weight: 700; }
.cai-msg-bubble i, .cai-msg-bubble em { font-style: italic; }
.cai-msg-bubble code {
    background: rgba(0,0,0,0.07); border-radius: 4px;
    padding: 1px 5px; font-size: 12.5px; font-family: 'Courier New', monospace;
}
.cai-msg-bubble ul, .cai-msg-bubble ol {
    margin: 6px 0 4px 0; padding-left: 18px; 
}
.cai-msg-bubble li { margin-bottom: 3px; }
.cai-msg-bubble a { color: var(--cai-primary); font-weight: 600; text-decoration: none; }
.cai-msg-bubble a:hover { text-decoration: underline; }
.cai-msg-bubble hr { border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 8px 0; }

.cai-msg-meta {
    font-size: 10.5px; color: #aaa; margin-top: 4px;
    padding: 0 2px;
}

/* ========== TYPING INDICATOR ========== */
.cai-typing-row { display: flex; gap: 8px; align-items: center; }
.cai-typing-bubble {
    background: #fff; border: 1px solid rgba(0,0,0,0.09);
    border-radius: 18px; border-bottom-left-radius: 4px;
    padding: 10px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    display: flex; gap: 5px; align-items: center;
}
.cai-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--cai-primary); opacity: 0.6;
    animation: cai-bounce 1.3s infinite ease;
}
.cai-dot:nth-child(2) { animation-delay: 0.18s; }
.cai-dot:nth-child(3) { animation-delay: 0.36s; }
@keyframes cai-bounce { 0%,60%,100%{transform:translateY(0);opacity:.5} 30%{transform:translateY(-7px);opacity:1} }

/* ========== PRODUCT STRIP ========== */
.cai-product-strip {
    display: flex; gap: 10px; overflow-x: auto; padding: 6px 4px 12px;
    scrollbar-width: thin;
    scrollbar-color: rgba(224,91,127,0.3) transparent;
}
.cai-product-strip::-webkit-scrollbar { height: 5px; }
.cai-product-strip::-webkit-scrollbar-thumb { background: rgba(224,91,127,0.35); border-radius: 4px; }
.cai-product-card {
    flex: 0 0 145px;
    border-radius: 14px; overflow: hidden;
    border: 1px solid rgba(0,0,0,0.1);
    background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    text-decoration: none !important; color: inherit !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    display: flex; flex-direction: column;
    cursor: pointer !important;
    pointer-events: auto !important;
    position: relative;
    user-select: none;
}
.cai-product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(224,91,127,0.25);
    border-color: var(--cai-primary);
}
.cai-product-card * {
    pointer-events: auto;
}
.cai-product-img-wrap {
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
}
.cai-product-card img {
    width: 100%; height: 95px; object-fit: cover; display: block;
    transition: transform 0.3s ease;
}
.cai-product-card:hover img { transform: scale(1.06); }
.cai-product-badge {
    position: absolute; top: 6px; left: 6px;
    background: linear-gradient(135deg,#e05b7f,#c2185b);
    color: #fff; font-size: 9.5px; font-weight: 800;
    padding: 2px 6px; border-radius: 6px;
    letter-spacing: 0.3px;
    line-height: 1.4;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.cai-product-badge.new { background: linear-gradient(135deg,#22c55e,#16a34a); }
.cai-product-badge.hot { background: linear-gradient(135deg,#f97316,#ea580c); }
.cai-product-card-info { padding: 8px 10px 9px; flex: 1; display: flex; flex-direction: column; }
.cai-product-card-name {
    font-size: 11.5px; font-weight: 700; color:#1a1a2e;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    line-height: 1.3;
}
.cai-product-card-brand {
    font-size: 10px; color: #888; margin-top: 1px;
    font-weight: 500;
}
.cai-product-card-price {
    font-size: 12px; color: var(--cai-primary); font-weight: 800;
    margin-top: 4px; display: flex; align-items: center; gap: 4px;
}
.cai-product-card-price .old-price {
    font-size: 10px; color: #bbb; font-weight: 400;
    text-decoration: line-through;
}
.cai-product-card-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 4px 10px 6px;
    border-top: 1px solid rgba(0,0,0,0.05);
    font-size: 10px; color: #999;
}
.cai-product-card-cta {
    background: linear-gradient(135deg,#e05b7f,#c2185b);
    color: #fff !important; font-size: 11px; font-weight: 700;
    padding: 7px 10px; text-decoration: none !important;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    transition: all 0.15s ease;
    border-top: 1px solid rgba(255,255,255,0.2);
    cursor: pointer;
}
.cai-product-card:hover .cai-product-card-cta {
    background: linear-gradient(135deg,#c2185b,#9c1245);
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
}

/* ========== INLINE PRODUCT LINKS IN CHAT TEXT ========== */
.cai-inline-product-link {
    display: inline-flex; align-items: center; gap: 4px;
    background: linear-gradient(135deg, rgba(224,91,127,0.12), rgba(194,24,91,0.18));
    border: 1px solid rgba(224,91,127,0.35);
    color: #c2185b !important;
    font-size: 11.5px; font-weight: 700;
    padding: 2px 8px; border-radius: 12px;
    text-decoration: none !important;
    margin: 2px 3px;
    transition: all 0.2s ease;
    vertical-align: middle;
}
.cai-inline-product-link:hover {
    background: var(--cai-primary);
    color: #fff !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(224,91,127,0.35);
}

/* ========== SUGGESTIONS BAR (Quick Reply ban đầu) ========== */
.cai-suggestions {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: 8px 12px 4px; background: #fafafa; flex-shrink: 0;
    border-top: 1px solid rgba(0,0,0,0.05);
}
.cai-suggest-btn {
    background: #fff; border: 1.5px solid rgba(224,91,127,0.3);
    color: #e05b7f; border-radius: 20px; padding: 5px 13px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all 0.18s; white-space: nowrap;
}
.cai-suggest-btn:hover { background: #e05b7f; color: #fff; border-color: #e05b7f; }

/* ========== FOLLOW-UP CHIPS (hiển thị sau tin nhắn bot) ========== */
.cai-followup-wrap {
    display: flex; flex-wrap: wrap; gap: 6px;
    margin-top: 8px; padding-top: 8px;
    border-top: 1px solid rgba(224,91,127,0.12);
    animation: cai-fade-in 0.35s ease both;
}
@keyframes cai-fade-in { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }
.cai-followup-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: linear-gradient(135deg, rgba(224,91,127,0.07), rgba(224,91,127,0.12));
    border: 1.5px solid rgba(224,91,127,0.25);
    color: #c2185b; border-radius: 16px;
    padding: 5px 12px 5px 10px;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    transition: all 0.18s; white-space: nowrap; line-height: 1.3;
    max-width: 100%;
}
.cai-followup-chip::before {
    content: '💬';
    font-size: 11px; flex-shrink: 0;
}
/* ========== COUPON VOUCHER CARDS IN CHAT ========== */
.cai-vouchers-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}
.cai-coupon-card {
    background: #ffffff;
    border: 1px dashed rgba(224, 91, 127, 0.45);
    border-radius: 12px;
    padding: 9px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
}
.cai-coupon-card:hover {
    border-color: #e05b7f;
    box-shadow: 0 4px 14px rgba(224, 91, 127, 0.15);
    transform: translateY(-1px);
}
.cai-coupon-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-start;
    flex-shrink: 0;
}
.cai-coupon-badge {
    background: linear-gradient(135deg, #e05b7f, #c2185b);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.cai-coupon-code {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #1a1a2e;
    color: #fef08a;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 11.5px;
    cursor: pointer;
    transition: all 0.18s ease;
}
.cai-coupon-code:hover {
    background: #e05b7f;
    color: #ffffff;
    transform: scale(1.04);
}
.cai-coupon-right {
    flex: 1;
    min-width: 0;
    text-align: left;
}
.cai-coupon-title {
    font-size: 12px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cai-coupon-desc {
    font-size: 10.5px;
    color: #718096;
    margin-top: 2px;
    line-height: 1.3;
}

/* ========== SALE EVENT CARDS IN CHAT ========== */
.cai-events-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}
.cai-event-card {
    background: linear-gradient(135deg, #fff9f5, #ffffff);
    border: 1px solid rgba(249, 115, 22, 0.3);
    border-radius: 12px;
    padding: 10px 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    text-align: left;
    transition: all 0.2s ease;
}
.cai-event-card:hover {
    border-color: #f97316;
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.15);
    transform: translateY(-1px);
}
.cai-event-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}
.cai-event-badge {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    letter-spacing: 0.3px;
}
.cai-event-date {
    font-size: 10.5px;
    color: #718096;
    font-weight: 500;
}
.cai-event-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.3;
    margin-bottom: 2px;
}
.cai-event-desc {
    font-size: 11px;
    color: #4b5563;
    line-height: 1.35;
    margin-bottom: 6px;
}
.cai-event-link {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 700;
    color: #ea580c !important;
    text-decoration: none !important;
}
.cai-event-link:hover {
    color: #c2410c !important;
    text-decoration: underline !important;
}

/* ========== INPUT AREA ========== */
.cai-input-area {
    padding: 10px 12px 12px;
    background: #fff; flex-shrink: 0;
    border-top: 1px solid rgba(0,0,0,0.07);
}
.cai-input-row {
    display: flex; align-items: flex-end; gap: 8px;
    background: #f4f4f5; border-radius: 14px;
    border: 1.5px solid transparent; padding: 6px 10px;
    transition: border-color 0.2s;
}
.cai-input-row:focus-within { border-color: rgba(224,91,127,0.4); background: #fff; }
.cai-textarea {
    flex: 1; border: none; background: transparent; outline: none; resize: none;
    font-size: 13.5px; line-height: 1.5; max-height: 100px;
    min-height: 22px; font-family: inherit; color: #1a1a2e;
}
.cai-textarea::placeholder { color: #aaa; }
.cai-input-btns { display: flex; gap: 5px; flex-shrink: 0; }
.cai-mic-btn, .cai-send-btn {
    width: 34px; height: 34px; border-radius: 10px; border: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 14px; transition: all 0.18s;
}
.cai-mic-btn {
    background: transparent; color: #888;
}
.cai-mic-btn:hover { background: rgba(224,91,127,0.1); color: var(--cai-primary); }
.cai-mic-btn.recording { background: #ff4757; color: #fff; animation: cai-pulse 1s infinite; }
.cai-send-btn {
    background: linear-gradient(135deg,#e05b7f,#c2185b); color: #fff;
}
.cai-send-btn:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(224,91,127,0.4); }
.cai-send-btn:disabled { opacity: 0.45; cursor: default; transform: none; box-shadow: none; }
.cai-input-footer {
    text-align: center; font-size: 10.5px; color: #bbb;
    margin-top: 6px; letter-spacing: 0.2px;
}

/* ========== CURSOR BLINK EFFECT ========== */
.cai-cursor::after {
    content: '▍'; animation: cai-blink 0.75s step-end infinite;
}
@keyframes cai-blink { 0%,100%{opacity:1} 50%{opacity:0} }

/* ========== MOBILE ========== */
@media(max-width:480px) {
    :root { --cai-w: calc(100vw - 16px); --cai-h: calc(100vh - 100px); }
    .cai-window { right:8px; }
}
</style>

<!-- ====================== TOGGLE BUTTON ====================== -->
<button class="cai-toggle" id="caiToggleBtn" onclick="caiToggle()" title="Chat với Shoes AI">
    <i class="fa-solid fa-comment-dots" id="caiToggleIcon"></i>
    <span class="cai-notif" id="caiNotif"></span>
</button>

<!-- ====================== CHAT WINDOW ====================== -->
<div class="cai-window" id="caiWindow">

    <!-- HEADER -->
    <div class="cai-header">
        <div class="cai-header-left">
            <div class="cai-avatar-wrap">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#e05b7f,#c2185b);display:flex;align-items:center;justify-content:center;font-size:18px;border:2px solid rgba(255,255,255,.3)">🤖</div>
                <span class="cai-online-dot"></span>
            </div>
            <div class="cai-header-info">
                <div class="cai-header-name">Shoes AI</div>
                <div class="cai-header-sub" id="caiStatus">● Trực tuyến · Sẵn sàng tư vấn</div>
            </div>
        </div>
        <div class="cai-header-actions">
            <button class="cai-header-btn" onclick="caiClearChat()" title="Cuộc trò chuyện mới">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button class="cai-header-btn" onclick="caiToggle()" title="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- MESSAGES -->
    <div class="cai-messages" id="caiMessages">
        <div class="cai-msg-row">
            <div class="cai-msg-avatar">🤖</div>
            <div class="cai-msg-content">
                <div class="cai-msg-bubble">
                    Xin chào! 👋 Mình là <strong>Shoes AI</strong>, trợ lý tư vấn của cửa hàng.<br><br>
                    Bạn có thể hỏi mình bất cứ điều gì — từ tư vấn giày, size, chính sách đổi trả đến những câu hỏi thông thường khác. Mình luôn sẵn sàng giúp đỡ! 😊
                </div>
                <div class="cai-msg-meta">Shoes AI · Vừa xong</div>
            </div>
        </div>
    </div>

    <!-- QUICK SUGGESTIONS -->
    <div class="cai-suggestions" id="caiSuggestions">
        <button class="cai-suggest-btn" onclick="caiSuggest(this,'👟 Gợi ý giày hot nhất shop')">👟 Giày hot</button>
        <button class="cai-suggest-btn" onclick="caiSuggest(this,'Tư vấn cách chọn size giày')">📏 Chọn size</button>
        <button class="cai-suggest-btn" onclick="caiSuggest(this,'Chính sách đổi trả và bảo hành')">↩️ Đổi trả</button>
        <button class="cai-suggest-btn" onclick="caiSuggest(this,'Phí ship về các tỉnh thành')">🚚 Giao hàng</button>
        <button class="cai-suggest-btn" onclick="caiSuggest(this,'Giày đang sale giảm giá mạnh nhất')">🔥 Đang sale</button>
        <button class="cai-suggest-btn" onclick="caiSuggest(this,'Shop ở đâu? Giờ mở cửa thế nào?')">📍 Địa chỉ</button>
    </div>

    <!-- INPUT AREA -->
    <div class="cai-input-area">
        <div class="cai-input-row">
            <button class="cai-mic-btn" id="caiMicBtn" onclick="caiToggleMic()" title="Thu âm giọng nói">
                <i class="fa-solid fa-microphone" id="caiMicIcon"></i>
            </button>
            <textarea class="cai-textarea" id="caiInput"
                placeholder="Nhắn tin với Shoes AI..."
                rows="1"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();caiSend()}"
                oninput="caiAutoResize(this)"></textarea>
            <div class="cai-input-btns">
                <button class="cai-send-btn" id="caiSendBtn" onclick="caiSend()" title="Gửi (Enter)">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
        <div class="cai-input-footer">Shoes AI có thể sai sót · Hãy kiểm tra thông tin quan trọng</div>
    </div>
</div>

<script>
(function() {
'use strict';

// ================================================================
// STATE
// ================================================================
let caiHistory     = [];
let caiIsOpen      = false;
let caiIsLoading   = false;
let caiRecognition = null;
let caiIsRecording = false;

// ================================================================
// TOGGLE OPEN/CLOSE
// ================================================================
window.caiToggle = function() {
    const win  = document.getElementById('caiWindow');
    const icon = document.getElementById('caiToggleIcon');
    const notif= document.getElementById('caiNotif');
    caiIsOpen = !caiIsOpen;
    if (caiIsOpen) {
        win.classList.add('cai-open');
        icon.className = 'fa-solid fa-xmark';
        if (notif) notif.style.display = 'none';
        setTimeout(() => document.getElementById('caiInput').focus(), 360);
    } else {
        win.classList.remove('cai-open');
        icon.className = 'fa-solid fa-comment-dots';
    }
};

// ================================================================
// COPY VOUCHER CODE
// ================================================================
window.caiCopyCode = function(code, el) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code);
    } else {
        const ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }
    const originalHTML = el.innerHTML;
    el.innerHTML = '<code>✓ Đã chép</code>';
    el.style.background = '#22c55e';
    el.style.color = '#fff';
    setTimeout(() => {
        el.innerHTML = originalHTML;
        el.style.background = '';
        el.style.color = '';
    }, 1800);
};

// ================================================================
// CLEAR CHAT
// ================================================================
window.caiClearChat = function() {
    caiHistory = [];
    const messages = document.getElementById('caiMessages');
    messages.innerHTML = `
        <div class="cai-msg-row">
            <div class="cai-msg-avatar">🤖</div>
            <div class="cai-msg-content">
                <div class="cai-msg-bubble">Cuộc trò chuyện mới bắt đầu! 🌟<br>Mình có thể giúp gì cho bạn hôm nay?</div>
                <div class="cai-msg-meta">Shoes AI · Vừa xong</div>
            </div>
        </div>`;
    document.getElementById('caiSuggestions').style.display = 'flex';
};

// ================================================================
// QUICK SUGGEST
// ================================================================
window.caiSuggest = function(btn, text) {
    document.getElementById('caiInput').value = text;
    document.getElementById('caiSuggestions').style.display = 'none';
    caiSend();
};

// ================================================================
// AUTO RESIZE TEXTAREA
// ================================================================
window.caiAutoResize = function(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
};

// ================================================================
// SEND MESSAGE
// ================================================================
window.caiSend = async function() {
    if (caiIsLoading) return;
    const input = document.getElementById('caiInput');
    const msg   = input.value.trim();
    if (!msg) return;

    document.getElementById('caiSuggestions').style.display = 'none';
    input.value = ''; input.style.height = 'auto';

    // Append user message
    caiAppendMessage('user', msg);
    caiHistory.push({ role: 'user', text: msg });

    // Show typing
    const typingEl = caiShowTyping();
    caiSetStatus('⌛ Đang suy nghĩ...');
    caiSetLoading(true);

    // Timeout 4.5s để tránh treo nút chat nếu server/mạng chậm
    const controller = new AbortController();
    const timeoutId  = setTimeout(() => controller.abort(), 4500);

    try {
        const res = await fetch(caiBasePath() + 'api/ai-chat.php', {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                message: msg,
                history: caiHistory.slice(-16) // 8 turns
            })
        });
        clearTimeout(timeoutId);
        
        let data = null;
        const textRes = await res.text();
        try {
            data = JSON.parse(textRes);
        } catch (jsonErr) {
            data = caiGetLocalSmartReply(msg);
        }

        typingEl.remove();
        caiSetStatus('● Trực tuyến · Sẵn sàng tư vấn');

        // Append bot reply with typewriter effect
        const reply = (data && data.reply) ? data.reply : 'Xin chào! Bạn cần Shoes AI hỗ trợ tư vấn giày hay chọn size ạ?';
        const botRow = await caiAppendBotMessage(reply);

        // Save clean history
        const cleanReply = reply.replace(/<[^>]*>/gm, ' ').replace(/\s+/g, ' ').trim();
        caiHistory.push({ role: 'model', text: cleanReply });

        // Show product cards
        if (data && data.products && data.products.length > 0) {
            caiAppendProductCards(data.products);
        }

        // Show follow-up suggestion chips
        if (data && data.suggestions && data.suggestions.length > 0) {
            caiAppendFollowupChips(data.suggestions, botRow);
        }
    } catch (e) {
        clearTimeout(timeoutId);
        typingEl.remove();
        caiSetStatus('● Trực tuyến · Sẵn sàng tư vấn');
        const fallback = caiGetLocalSmartReply(msg);
        const botRow = await caiAppendBotMessage(fallback.reply);
        if (fallback.products && fallback.products.length > 0) {
            caiAppendProductCards(fallback.products);
        }
        if (fallback.suggestions && fallback.suggestions.length > 0) {
            caiAppendFollowupChips(fallback.suggestions, botRow);
        }
    } finally {
        caiSetLoading(false);
    }
};

// ================================================================
// LOCAL SMART CATALOG ENGINE (ĐỌC TRỰC TIẾP TỪ KHO SẢN PHẨM WEBSITE)
// ================================================================
function caiGetLocalSmartReply(msg) {
    const m = (msg || '').toLowerCase().trim();
    const catalog = window.CAI_CATALOG || [];

    // Helper format tiền tệ
    const fmt = (num) => new Intl.NumberFormat('vi-VN').format(num) + 'đ';

    // Helper tạo HTML từng thẻ sản phẩm bấm được ngay trong tin nhắn
    const renderInlineProductCards = (items) => {
        let html = '';
        items.forEach((p, idx) => {
            const discTag = p.discount > 0 ? ` <span style="background:#fee2e2;color:#dc2626;font-size:11px;font-weight:bold;padding:2px 5px;border-radius:4px;">-${p.discount}%</span>` : '';
            const pLink = p.url || ('product-detail.php?id=' + p.id);
            const pImg  = p.image || 'assets/img/products/default.jpg';
            html += `
            <div class="cai-item-card" onclick="event.preventDefault(); event.stopPropagation(); window.location.href='${pLink}';" style="margin:8px 0;padding:8px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all 0.2s;text-align:left;" onmouseover="this.style.borderColor='#e05b7f';this.style.background='#fff0f4';" onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';">
                <img src="${pImg}" alt="${escHtml(p.name)}" style="width:46px;height:46px;object-fit:cover;border-radius:8px;flex-shrink:0;" onerror="this.src='https://placehold.co/60x60/fce4ec/e05b7f?text=Giày'">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:12.5px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(p.name)}</div>
                    <div style="font-size:11px;color:#64748b;">${escHtml(p.brand)} · <b style="color:#e05b7f;font-size:12px;">${fmt(p.price)}</b>${discTag}</div>
                </div>
                <span style="background:#e05b7f;color:#fff;font-size:11px;font-weight:600;padding:5px 9px;border-radius:8px;white-space:nowrap;box-shadow:0 2px 6px rgba(224,91,127,0.3);">Xem ↗</span>
            </div>`;
        });
        return html;
    };

    // ─────────────────────────────────────────────────────────────
    // ƯU TIÊN 1: Ý ĐỊNH SIZE CHÂN / ĐO CHÂN / BẢNG SIZE
    // ─────────────────────────────────────────────────────────────
    if (m.includes('size') || m.includes('chân') || m.includes('đo') || m.includes('cỡ') || m.includes('vừa') || m.includes('bè')) {
        let cmFound = null;
        const cmMatch = m.match(/(\d{2}(?:[.,]\d)?)\s*(?:cm|centimet)?/i);
        if (cmMatch) {
            const val = parseFloat(cmMatch[1].replace(',', '.'));
            if (val >= 22 && val <= 30) cmFound = val;
        }

        let sizeCalc = "";
        let calcSize = 40;
        if (cmFound) {
            if (cmFound <= 22.5) calcSize = 36;
            else if (cmFound <= 23.0) calcSize = 37;
            else if (cmFound <= 23.5) calcSize = 38;
            else if (cmFound <= 24.5) calcSize = 39;
            else if (cmFound <= 25.0) calcSize = 40;
            else if (cmFound <= 25.5) calcSize = 41;
            else if (cmFound <= 26.0) calcSize = 42;
            else if (cmFound <= 26.5) calcSize = 43;
            else calcSize = 44;
            sizeCalc = `<br><br>🎯 <b>Tư vấn riêng cho bạn:</b> Chiều dài bàn chân <b>${cmFound}cm</b> phù hợp nhất với <b>Size ${calcSize}</b> (nếu bàn chân bè to hoặc mu bàn chân dày hãy tăng lên <b>Size ${calcSize + 1}</b> để đi thoải mái nhất nhé)!`;
        }

        return {
            reply: `📏 <b>Bảng Quy Đổi Size Giày Chuẩn Xác Tại SHOES STORE:</b><br><br>` +
                   `• <b>22.5 cm:</b> Size 36 | <b>23.0 cm:</b> Size 37<br>` +
                   `• <b>23.5 cm:</b> Size 38 | <b>24.5 cm:</b> Size 39<br>` +
                   `• <b>25.0 cm:</b> Size 40 | <b>25.5 cm:</b> Size 41<br>` +
                   `• <b>26.0 cm:</b> Size 42 | <b>26.5 cm:</b> Size 43 | <b>27.0 cm:</b> Size 44${sizeCalc}<br><br>` +
                   `💡 <b>Mẹo:</b> Đặt bàn chân lên giấy A4 sát mép tường để đo cm chính xác nhất.<br>` +
                   `🛡️ <i>Shop hỗ trợ đổi size miễn phí trong 7 ngày nếu mang không vừa!</i>`,
            products: [],
            suggestions: [`Chân dài 25cm đi size mấy?`, "Giày cho người chân bè mu cao", "Chính sách đổi size 7 ngày"]
        };
    }

    // ─────────────────────────────────────────────────────────────
    // ƯU TIÊN 2: Ý ĐỊNH VẬN CHUYỂN / PHÍ SHIP / FREESHIP
    // ─────────────────────────────────────────────────────────────
    if (m.includes('ship') || m.includes('giao hàng') || m.includes('vận chuyển') || m.includes('freeship') || m.includes('bao lâu')) {
        return {
            reply: `🚚 <b>Chính Sách Vận Chuyển & Giao Hàng Tại SHOES STORE:</b><br><br>` +
                   `• <b>Thời gian nhận hàng:</b> 2 – 4 ngày làm việc trên toàn quốc.<br>` +
                   `• <b>Phí vận chuyển:</b> Được tính tự động linh hoạt theo từng tỉnh/thành phố khi bạn chọn địa chỉ tại trang thanh toán.<br>` +
                   `• <b>Ưu đãi Freeship:</b> Bạn có thể áp dụng mã <code>FREESHIP</code> tại trang Giỏ hàng để được miễn phí giao hàng!<br>` +
                   `• <b>Đồng kiểm khi nhận:</b> Quý khách được kiểm tra hàng trước khi thanh toán (COD).`,
            products: [],
            suggestions: ["Xem mã giảm giá hôm nay", "Top giày sneaker bán chạy", "Chính sách bảo hành"]
        };
    }

    // ─────────────────────────────────────────────────────────────
    // ƯU TIÊN 3: Ý ĐỊNH ĐỔI TRẢ / BẢO HÀNH
    // ─────────────────────────────────────────────────────────────
    if (m.includes('đổi') || m.includes('trả') || m.includes('bảo hành') || m.includes('lỗi') || m.includes('hỏng')) {
        return {
            reply: `🛡️ <b>Chính Sách Đổi Trả & Bảo Hành Chính Hãng:</b><br><br>` +
                   `• <b>Đổi size miễn phí trong 7 ngày:</b> Hỗ trợ đổi size tận nơi nếu mang không vừa chân.<br>` +
                   `• <b>Đổi mới trong 30 ngày:</b> Đổi mới 100% nếu sản phẩm có lỗi từ nhà sản xuất.<br>` +
                   `• <b>Bảo hành 12 tháng:</b> Bảo hành keo dán đế và chỉ may trọn đời.<br>` +
                   `• Hotline / Zalo hỗ trợ nhanh: <b>0901.234.567</b>.`,
            products: [],
            suggestions: ["Tư vấn cách chọn size giày", "Mã giảm giá hôm nay", "Xem tất cả sản phẩm"]
        };
    }

    // ─────────────────────────────────────────────────────────────
    // ƯU TIÊN 4: Ý ĐỊNH MÃ GIẢM GIÁ / VOUCHER / KHUYẾN MÃI
    // ─────────────────────────────────────────────────────────────
    if (m.includes('voucher') || m.includes('mã') || m.includes('giảm giá') || m.includes('khuyến mãi') || m.includes('ưu đãi') || m.includes('code') || m.includes('sale')) {
        return {
            reply: `🎁 <b>Ưu Đãi & Mã Giảm Giá Đang Hoạt Động Hôm Nay:</b><br><br>` +
                   `• Mã <b>FREESHIP</b>: Miễn phí vận chuyển toàn quốc.<br>` +
                   `• Mã <b>CHAOBANMOI</b>: Giảm ngay 50.000đ cho đơn hàng đầu tiên.<br>` +
                   `• Mã <b>FLASHDEAL</b>: Giảm 50.000đ cho đơn từ 800.000đ.<br><br>` +
                   `👉 <i>Bạn chỉ cần nhập mã tại trang Giỏ hàng & Thanh toán để được trừ tiền ngay!</i>`,
            products: catalog.slice(0, 3),
            suggestions: ["Giày sneaker hot nhất", "Tư vấn chọn size giày", "Phí giao hàng và Freeship"]
        };
    }

    // ─────────────────────────────────────────────────────────────
    // ƯU TIÊN 5: Ý ĐỊNH THANH TOÁN / NGÂN HÀNG / QR
    // ─────────────────────────────────────────────────────────────
    if (m.includes('thanh toán') || m.includes('chuyển khoản') || m.includes('stk') || m.includes('ngân hàng') || m.includes('qr') || m.includes('momo') || m.includes('vnpay')) {
        return {
            reply: `💳 <b>Phương Thức Thanh Toán Hỗ Trợ Tại SHOES STORE:</b><br><br>` +
                   `1. <b>COD (Tiền mặt):</b> Thanh toán khi nhận và kiểm tra hàng tại nhà.<br>` +
                   `2. <b>Chuyển khoản Ngân hàng / Quét mã QR:</b> Hỗ trợ quét mã VietQR tự động xác nhận.<br>` +
                   `3. <b>Ví điện tử:</b> MoMo, VNPAY tiện lợi, bảo mật tuyệt đối.`,
            products: [],
            suggestions: ["Thời gian giao hàng bao lâu?", "Mã giảm giá hôm nay", "Top sản phẩm bán chạy"]
        };
    }

    // ─────────────────────────────────────────────────────────────
    // ƯU TIÊN 6: TƯ VẤN SẢN PHẨM (NIKE, ADIDAS, JORDAN, NAM, NỮ, CHẠY BỘ, SNEAKER...)
    // ─────────────────────────────────────────────────────────────
    const isNike   = m.includes('nike');
    const isAdidas = m.includes('adidas');
    const isJordan = m.includes('jordan');
    const isPuma   = m.includes('puma');
    const isNB     = m.includes('new balance') || m.includes('nb');
    const isVans   = m.includes('vans');
    const isConv   = m.includes('converse');
    const isMlb    = m.includes('mlb');
    const isCrocs  = m.includes('crocs');

    const isMale   = m.includes('nam') || m.includes('men') || m.includes('boy') || m.includes('trai');
    const isFemale = m.includes('nữ') || m.includes('nu') || m.includes('women') || m.includes('girl') || m.includes('gái');
    const isRun    = m.includes('chạy bộ') || m.includes('running') || m.includes('thể thao') || m.includes('tập');
    const isHot    = m.includes('hot') || m.includes('bán chạy') || m.includes('trend') || m.includes('xu hướng') || m.includes('mới');
    const isSale   = m.includes('sale') || m.includes('rẻ');

    let matched = catalog.filter(p => {
        const pName   = (p.name || '').toLowerCase();
        const pBrand  = (p.brand || '').toLowerCase();
        const pGender = (p.gender || '').toLowerCase();
        const pCat    = (p.category || '').toLowerCase();

        if (isNike && !pBrand.includes('nike') && !pName.includes('nike')) return false;
        if (isAdidas && !pBrand.includes('adidas') && !pName.includes('adidas')) return false;
        if (isJordan && !pBrand.includes('jordan') && !pName.includes('jordan')) return false;
        if (isPuma && !pBrand.includes('puma') && !pName.includes('puma')) return false;
        if (isNB && !pBrand.includes('new balance') && !pName.includes('new balance')) return false;
        if (isVans && !pBrand.includes('vans') && !pName.includes('vans')) return false;
        if (isConv && !pBrand.includes('converse') && !pName.includes('converse')) return false;
        if (isMlb && !pBrand.includes('mlb') && !pName.includes('mlb')) return false;
        if (isCrocs && !pBrand.includes('crocs') && !pName.includes('crocs')) return false;

        if (isMale && (pGender.includes('nu') && !pGender.includes('unisex') && !pGender.includes('nam'))) return false;
        if (isFemale && (pGender.includes('nam') && !pGender.includes('unisex') && !pGender.includes('nu'))) return false;
        if (isRun && (!pCat.includes('chạy') && !pName.includes('run') && !pName.includes('boost') && !pName.includes('air'))) return false;

        return true;
    });

    if (matched.length === 0) {
        matched = catalog.slice(0, 3);
    } else {
        matched = matched.slice(0, 3);
    }

    let titleContext = "mẫu giày";
    let suggestions = [];

    if (isNike && isMale) {
        titleContext = "giày Nike Nam hot nhất";
        suggestions = ["Xem bảng size giày Nike", "Mã giảm giá cho đơn này", "Có mẫu Adidas nam nào đẹp?"];
    } else if (isNike) {
        titleContext = "giày Nike chính hãng hot nhất";
        suggestions = ["Tư vấn chọn size giày Nike", "Chính sách đổi trả 7 ngày", "So sánh Nike và Adidas"];
    } else if (isAdidas) {
        titleContext = "giày Adidas được yêu thích nhất";
        suggestions = ["Giày chạy bộ Ultraboost", "Xem bảng size Adidas", "Mã giảm giá hôm nay"];
    } else if (isJordan) {
        titleContext = "Air Jordan cực chất";
        suggestions = ["Cách phối đồ với Jordan", "Tư vấn chọn size chân", "Mã voucher freeship"];
    } else if (isMale) {
        titleContext = "giày Nam bán chạy nhất";
        suggestions = ["Giày chạy bộ nam êm chân", "Tư vấn size giày chuẩn", "Mã giảm giá hôm nay"];
    } else if (isFemale) {
        titleContext = "giày Nữ thời trang đẹp nhất";
        suggestions = ["Tư vấn chọn size giày nữ", "Mẫu giày nữ đi học/đi chơi", "Mã voucher hôm nay"];
    } else if (isRun) {
        titleContext = "giày chạy bộ êm chân nhất";
        suggestions = ["Cách đo chiều dài bàn chân", "Chân bè nên chọn mẫu nào?", "Chính sách bảo hành"];
    } else {
        titleContext = "những đôi Sneaker HOT nhất cửa hàng";
        suggestions = ["Tư vấn chọn size giày chuẩn", "Mã giảm giá hôm nay", "Chính sách đổi trả"];
    }

    let replyHtml = `👟 Dưới đây là top <b>${titleContext}</b> có sẵn tại cửa hàng <b>SHOES STORE</b>:<br>`;
    replyHtml += renderInlineProductCards(matched);
    replyHtml += `<small style="color:#64748b;">👉 Bấm vào từng mẫu ở trên để xem ảnh chi tiết và chọn size đặt hàng nhé!</small>`;

    return {
        reply: replyHtml,
        products: matched,
        suggestions: suggestions
    };
}

// ================================================================
// FOLLOW-UP SUGGESTION CHIPS
// ================================================================
function caiAppendFollowupChips(suggestions, botRow) {
    if (!suggestions || suggestions.length === 0) return;
    const messages  = document.getElementById('caiMessages');
    const container = document.createElement('div');
    container.className = 'cai-msg-row'; // same indent as bot

    const spacer = document.createElement('div');
    spacer.className = 'cai-msg-avatar'; // keep alignment
    spacer.style.cssText = 'opacity:0;pointer-events:none';
    spacer.textContent = '.';

    const wrap = document.createElement('div');
    wrap.className = 'cai-msg-content';

    const chipWrap = document.createElement('div');
    chipWrap.className = 'cai-followup-wrap';

    suggestions.slice(0, 4).forEach(q => {
        const chip = document.createElement('button');
        chip.className = 'cai-followup-chip';
        chip.textContent = q;
        chip.setAttribute('type', 'button');
        chip.onclick = () => {
            // Xoa chips cu khi click
            container.remove();
            // Dien vao input va gui
            const input = document.getElementById('caiInput');
            input.value = q;
            window.caiSend();
        };
        chipWrap.appendChild(chip);
    });

    wrap.appendChild(chipWrap);
    container.appendChild(spacer);
    container.appendChild(wrap);
    messages.appendChild(container);
    messages.scrollTop = messages.scrollHeight;
}

// ================================================================
// APPEND USER MESSAGE
// ================================================================
function caiAppendMessage(role, text) {
    const messages = document.getElementById('caiMessages');
    const now = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'});
    const row = document.createElement('div');
    row.className = 'cai-msg-row ' + (role === 'user' ? 'cai-user' : '');

    if (role === 'user') {
        row.innerHTML = `
            <div class="cai-msg-avatar">👤</div>
            <div class="cai-msg-content">
                <div class="cai-msg-bubble">${escHtml(text)}</div>
                <div class="cai-msg-meta">${now}</div>
            </div>`;
    }
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
    return row;
}

// ================================================================
// APPEND BOT MESSAGE WITH TYPEWRITER EFFECT
// ================================================================
async function caiAppendBotMessage(htmlText) {
    const messages = document.getElementById('caiMessages');
    const now = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'});
    
    const row = document.createElement('div');
    row.className = 'cai-msg-row';
    row.innerHTML = `
        <div class="cai-msg-avatar">🤖</div>
        <div class="cai-msg-content">
            <div class="cai-msg-bubble cai-cursor" id="cai-bubble-new"></div>
            <div class="cai-msg-meta">Shoes AI · ${now}</div>
        </div>`;
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;

    const bubble = row.querySelector('.cai-msg-bubble');
    
    // Convert markdown to HTML
    const rendered = caiMarkdown(htmlText);
    
    // Typewriter effect - reveal character by character on plain text
    const plainLen  = rendered.replace(/<[^>]*>/g, '').length;
    const speed      = plainLen > 200 ? 4 : (plainLen > 80 ? 8 : 12); // ms per char (faster for longer)

    await new Promise(resolve => {
        let i = 0;
        // Strip HTML for typewriter, then show full HTML at end
        const stripped = rendered.replace(/<[^>]*>/g, '');
        
        // Fast reveal: show character by character of plain text
        const interval = setInterval(() => {
            i += Math.max(1, Math.floor(stripped.length / 120));
            const preview = escHtml(stripped.slice(0, i)) + '<span class="cai-cursor"></span>';
            bubble.innerHTML = preview;
            messages.scrollTop = messages.scrollHeight;
            if (i >= stripped.length) {
                clearInterval(interval);
                bubble.classList.remove('cai-cursor');
                bubble.innerHTML = rendered; // show full rendered HTML
                messages.scrollTop = messages.scrollHeight;
                resolve();
            }
        }, speed);
    });
    return row;
}

// ================================================================
// MARKDOWN RENDERER
// ================================================================
function caiMarkdown(text) {
    // Handle raw \n linebreaks AND <br> tags uniformly
    let t = text.replace(/<br\s*\/?>/gi, '\n');

    // Headers ##
    t = t.replace(/^### (.+)$/gm, '<strong style="font-size:13px">$1</strong>');
    t = t.replace(/^## (.+)$/gm,  '<strong style="font-size:14px">$1</strong>');
    t = t.replace(/^# (.+)$/gm,   '<strong style="font-size:15px">$1</strong>');

    // Bold **text** or __text__
    t = t.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
    t = t.replace(/__([^_\n]+)__/g,     '<strong>$1</strong>');

    // Italic *text* or _text_
    t = t.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
    t = t.replace(/_([^_\n]+)_/g,   '<em>$1</em>');

    // Inline code `code`
    t = t.replace(/`([^`]+)`/g, '<code>$1</code>');

    // Horizontal rule
    t = t.replace(/^---+$/gm, '<hr>');

    // Numbered lists
    t = t.replace(/((?:^\d+\. .+\n?)+)/gm, (match) => {
        const items = match.trim().split('\n').map(line => {
            return '<li>' + line.replace(/^\d+\. /, '') + '</li>';
        }).join('');
        return '<ol>' + items + '</ol>';
    });

    // Bullet lists
    t = t.replace(/((?:^[*\-•] .+\n?)+)/gm, (match) => {
        const items = match.trim().split('\n').map(line => {
            return '<li>' + line.replace(/^[*\-•] /, '') + '</li>';
        }).join('');
        return '<ul>' + items + '</ul>';
    });

    // Auto-convert [ID:123] or (ID: 123) into interactive clickable product badge/link
    t = t.replace(/\[ID:?\s*(\d+)\]/gi, (match, id) => {
        const pLink = caiBasePath() + 'product-detail.php?id=' + id;
        return `<a href="${pLink}" class="cai-inline-product-link" target="_self" onclick="event.stopPropagation(); window.location.href='${pLink}';"><i class="fa-solid fa-arrow-up-right-from-square"></i> Xem sản phẩm #${id}</a>`;
    });
    t = t.replace(/\(ID:?\s*(\d+)\)/gi, (match, id) => {
        const pLink = caiBasePath() + 'product-detail.php?id=' + id;
        return `<a href="${pLink}" class="cai-inline-product-link" target="_self" onclick="event.stopPropagation(); window.location.href='${pLink}';"><i class="fa-solid fa-arrow-up-right-from-square"></i> Xem sản phẩm #${id}</a>`;
    });

    // Links [text](url)
    t = t.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_self" onclick="event.stopPropagation(); window.location.href=\'$2\';">$1</a>');
    t = t.replace(/\[([^\]]+)\]\(([^\)]+)\)/g,             '<a href="$2" target="_self" onclick="event.stopPropagation(); window.location.href=\'$2\';">$1</a>');

    // Newlines -> <br> (skip after block elements)
    t = t.replace(/\n{2,}/g, '<br><br>');
    t = t.replace(/\n/g, '<br>');

    // Clean up <br> before/after block elements
    t = t.replace(/<br><(ul|ol|hr)/g, '<$1');
    t = t.replace(/<\/(ul|ol)><br>/g, '</$1>');

    return t;
}

// ================================================================
// TYPING INDICATOR
// ================================================================
function caiShowTyping() {
    const messages = document.getElementById('caiMessages');
    const row = document.createElement('div');
    row.className = 'cai-typing-row';
    row.innerHTML = `
        <div class="cai-msg-avatar">🤖</div>
        <div class="cai-typing-bubble">
            <span class="cai-dot"></span>
            <span class="cai-dot"></span>
            <span class="cai-dot"></span>
        </div>`;
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
    return row;
}

// ================================================================
// PRODUCT CARDS
// ================================================================
function caiAppendProductCards(products) {
    if (!products || !products.length) return;
    const messages = document.getElementById('caiMessages');
    const strip = document.createElement('div');
    strip.className = 'cai-product-strip';
    strip.style.paddingLeft = '38px'; // align with bubble

    products.forEach(p => {
        const base = caiBasePath();
        const img = p.image ? (p.image.startsWith('http') ? p.image : (base + p.image.replace(/^\/+/, '')))
                            : 'https://placehold.co/140x90/fce4ec/e05b7f?text=Gi%C3%A0y';
        const price = new Intl.NumberFormat('vi-VN').format(p.price) + 'đ';
        
        let link = base + 'product-detail.php?id=' + (p.id || 1);
        if (p.url) {
            link = p.url.startsWith('http') ? p.url : (base + p.url.replace(/^\/+/, ''));
        }

        // Badge (ưu tiên: Giảm giá > HOT > NEW)
        let badgeHtml = '';
        if (p.discount && p.discount > 0) {
            badgeHtml = `<span class="cai-product-badge">-${p.discount}%</span>`;
        }

        // Giá cũ
        let oldPriceHtml = '';
        if (p.old_price && p.old_price > p.price) {
            const oldFmt = new Intl.NumberFormat('vi-VN').format(p.old_price) + 'đ';
            oldPriceHtml = `<span class="old-price">${oldFmt}</span>`;
        }

        // Thương hiệu
        const brandHtml = p.brand ? `<div class="cai-product-card-brand">${escHtml(p.brand)}</div>` : '';

        // Trạng thái tồn kho
        const stockHtml = (p.stock !== undefined && p.stock !== null)
            ? `<span title="Tồn kho">${p.stock > 0 ? '✅ Còn hàng' : '❌ Hết'}</span>`
            : '';
        const soldHtml  = (p.sold !== undefined && p.sold > 0)
            ? `<span>🔥 ${p.sold} đã bán</span>`
            : '';

        const card = document.createElement('a');
        card.className = 'cai-product-card';
        card.href   = link;
        card.target = '_self';
        card.rel    = 'noopener';
        card.title  = p.name;
        // Direct click handler to guarantee navigation in all browsers
        card.onclick = function(e) {
            e.stopPropagation();
            window.location.href = link;
        };
        card.innerHTML = `
            <div class="cai-product-img-wrap">
                <img src="${img}" alt="${escHtml(p.name)}" loading="lazy"
                     onerror="this.src='https://placehold.co/140x90/fce4ec/e05b7f?text=Gi%C3%A0y'">
                ${badgeHtml}
            </div>
            <div class="cai-product-card-info">
                <div class="cai-product-card-name" title="${escHtml(p.name)}">${escHtml(p.name)}</div>
                ${brandHtml}
                <div class="cai-product-card-price">
                    ${price} ${oldPriceHtml}
                </div>
            </div>
            ${(stockHtml || soldHtml) ? `
            <div class="cai-product-card-footer">
                ${stockHtml}
                ${soldHtml}
            </div>` : ''}
            <div class="cai-product-card-cta" onclick="event.stopPropagation(); window.location.href='${link}';">
                <i class="fa-solid fa-eye" style="font-size:10px"></i> Xem chi tiết
            </div>`;

        strip.appendChild(card);
    });

    messages.appendChild(strip);
    messages.scrollTop = messages.scrollHeight;
}

// ================================================================
// HELPERS
// ================================================================
function caiSetStatus(text) {
    const el = document.getElementById('caiStatus');
    if (el) el.textContent = text;
}
function caiSetLoading(v) {
    caiIsLoading = v;
    const btn = document.getElementById('caiSendBtn');
    if (btn) btn.disabled = v;
}
function escHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function caiBasePath() {
    const origin = window.location.origin;
    const path = window.location.pathname;
    if (path.indexOf('/web-shoe') !== -1) {
        return origin + '/web-shoe/';
    }
    return origin + '/';
}

// ================================================================
// VOICE INPUT
// ================================================================
window.caiToggleMic = function() {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    const btn  = document.getElementById('caiMicBtn');
    const icon = document.getElementById('caiMicIcon');

    if (!SR) {
        alert('Trình duyệt chưa hỗ trợ giọng nói. Dùng Chrome hoặc Edge nhé!');
        return;
    }
    if (caiIsRecording) {
        if (caiRecognition) caiRecognition.stop();
        caiIsRecording = false;
        btn.classList.remove('recording');
        icon.className = 'fa-solid fa-microphone';
        caiSetStatus('● Trực tuyến · Sẵn sàng tư vấn');
        return;
    }

    caiRecognition = new SR();
    caiRecognition.lang = 'vi-VN';
    caiRecognition.interimResults = false;
    caiRecognition.maxAlternatives = 1;

    caiRecognition.onstart = () => {
        caiIsRecording = true;
        btn.classList.add('recording');
        icon.className = 'fa-solid fa-microphone-slash';
        caiSetStatus('🎤 Đang nghe... Nói rõ vào mic');
    };
    caiRecognition.onresult = (e) => {
        const transcript = e.results[0][0].transcript;
        document.getElementById('caiInput').value = transcript;
        setTimeout(caiSend, 200);
    };
    caiRecognition.onend = () => {
        caiIsRecording = false;
        btn.classList.remove('recording');
        icon.className = 'fa-solid fa-microphone';
        caiSetStatus('● Trực tuyến · Sẵn sàng tư vấn');
    };
    caiRecognition.onerror = (e) => {
        caiIsRecording = false;
        btn.classList.remove('recording');
        icon.className = 'fa-solid fa-microphone';
        caiSetStatus('● Trực tuyến · Sẵn sàng tư vấn');
        if (e.error === 'not-allowed') alert('Hãy cấp quyền microphone trong trình duyệt!');
    };
    caiRecognition.start();
};

})();
</script>