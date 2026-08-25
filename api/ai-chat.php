<?php
// Suppress ALL PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);
ob_start(); // Buffer any stray output

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Load local config để lấy Gemini API key (XAMPP không có env vars mặc định)
@include_once __DIR__ . '/../config/local-config.php';

// 🔑 GEMINI API KEY - lấy từ env hoặc local-config
$_gemini_key = getenv('GEMINI_API_KEY') ?: (defined('LOCAL_GEMINI_API_KEY') ? LOCAL_GEMINI_API_KEY : '');
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', $_gemini_key);
}
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', 'gemini-3.6-flash');
}

$raw_input   = file_get_contents('php://input');
$input       = !empty($raw_input) ? json_decode($raw_input, true) : ($_POST ?: $_GET);
$userMessage = trim($input['message'] ?? '');
$history     = $input['history']    ?? [];

if (empty($userMessage)) {
    ob_end_clean();
    echo json_encode(['reply' => 'Xin chào! Bạn cần Shoes AI hỗ trợ thông tin gì ạ?', 'products' => []]);
    exit();
}

// ================================================================
// 1. LẤY THÔNG TIN CỬA HÀNG TỪ SITE SETTINGS
// ================================================================
$settings = [];
$set_res  = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($set_res) {
    while ($r = $set_res->fetch_assoc()) $settings[$r['setting_key']] = $r['setting_value'];
}

$site_name    = $settings['site_name']       ?? 'SHOES STORE';
$site_address = $settings['contact_address'] ?? 'TP. Vĩnh Long, Việt Nam';
$site_phone   = $settings['contact_hotline'] ?? '0901.234.567';
$site_email   = $settings['contact_email']   ?? 'support@shoesstore.vn';
$bank_id      = $settings['bank_id']         ?? '';
$bank_account = $settings['bank_account']    ?? '';
$bank_name    = $settings['bank_name']       ?? '';

// ================================================================
// 2. LẤY SẢN PHẨM (tất cả, không giới hạn 35 cái)
// ================================================================
$products_ctx  = [];
$products_full = [];

$res_p = $conn->query("
    SELECT p.id, p.name, p.price, p.old_price, p.discount_percent, p.main_image,
           p.description, p.is_hot, p.is_new, p.sold_count, p.view_count, p.status, p.gender,
           COALESCE(b.name,'Khác') AS brand,
           COALESCE(c.name,'') AS category,
           COALESCE(c.type,'') AS cat_type,
           COALESCE((SELECT SUM(pv.stock_quantity) FROM product_variants pv WHERE pv.product_id=p.id),0) AS total_stock,
           GROUP_CONCAT(DISTINCT pv2.size ORDER BY pv2.size SEPARATOR ', ') AS sizes_available
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv2 ON pv2.product_id = p.id AND pv2.stock_quantity > 0
    WHERE p.status = 1
    GROUP BY p.id
    ORDER BY p.is_hot DESC, p.sold_count DESC, p.view_count DESC, p.id DESC
    LIMIT 60
");

if ($res_p && $res_p->num_rows > 0) {
    while ($row = $res_p->fetch_assoc()) {
        $price_str  = number_format($row['price'], 0, ',', '.') . 'đ';
        $old_price  = $row['old_price'] > 0 ? number_format($row['old_price'], 0, ',', '.') . 'đ' : '';
        $stock_qty  = intval($row['total_stock']);
        $stock_str  = $stock_qty > 0 ? "Còn {$stock_qty} đôi" : 'Hết hàng';
        $sizes_str  = !empty($row['sizes_available']) ? $row['sizes_available'] : 'N/A';
        $hot_str    = $row['is_hot'] ? ' [HOT🔥]' : '';
        $new_str    = $row['is_new'] ? ' [NEW✨]' : '';
        $disc_str   = $row['discount_percent'] > 0 ? " [-{$row['discount_percent']}% còn {$price_str}]" : " [{$price_str}]";
        $gender_str = $row['gender'] === 'nam' ? 'Nam' : ($row['gender'] === 'nu' ? 'Nữ' : 'Unisex');
        $desc_short = mb_substr(strip_tags($row['description'] ?? ''), 0, 120, 'UTF-8');
        $sold       = intval($row['sold_count']);

        $products_ctx[] = "[ID:{$row['id']}] {$row['name']}{$hot_str}{$new_str}{$disc_str} | Hãng: {$row['brand']} | Loại: {$row['category']} | Đối tượng: {$gender_str} | {$stock_str} | Size: {$sizes_str} | Đã bán: {$sold}" . ($desc_short ? " | {$desc_short}" : '');

        $products_full[] = [
            'id'       => intval($row['id']),
            'name'     => $row['name'],
            'price'    => floatval($row['price']),
            'old_price'=> floatval($row['old_price']),
            'discount' => intval($row['discount_percent']),
            'brand'    => $row['brand'],
            'category' => $row['category'],
            'gender'   => $gender_str,
            'stock'    => $stock_qty,
            'sold'     => $sold,
            'image'    => $row['main_image'],
            'url'      => 'product-detail.php?id=' . $row['id'],
        ];
    }
}
$products_text = !empty($products_ctx) ? implode("\n", $products_ctx) : "Hiện chưa có sản phẩm.";

// ================================================================
// 3. LẤY THƯƠNG HIỆU
// ================================================================
$brands_ctx = [];
$res_b = $conn->query("SELECT name, description FROM brands WHERE status = 1 ORDER BY name ASC");
if ($res_b) {
    while ($b = $res_b->fetch_assoc()) {
        $brands_ctx[] = $b['name'] . ($b['description'] ? " ({$b['description']})" : '');
    }
}
$brands_text = !empty($brands_ctx) ? implode(', ', $brands_ctx) : 'Nike, Adidas, Jordan, Puma, Vans, Converse';

// ================================================================
// 4. LẤY DANH MỤC
// ================================================================
$cats_ctx = [];
$res_c = $conn->query("SELECT name FROM categories WHERE status = 1 ORDER BY sort_order ASC");
if ($res_c) {
    while ($c = $res_c->fetch_assoc()) $cats_ctx[] = $c['name'];
}
$cats_text = !empty($cats_ctx) ? implode(', ', $cats_ctx) : 'Giày, Dép, Sandal';

// ================================================================
// 5. LẤY VOUCHER ĐANG HOẠT ĐỘNG
// ================================================================
$vouchers_ctx = [];
$vouchers_data = [];
$res_v = $conn->query("
    SELECT code, title, discount_type, discount_value, min_order_value, max_discount,
           start_date, end_date, usage_limit, used_count
    FROM vouchers
    WHERE status = 1 AND (end_date IS NULL OR end_date >= NOW()) AND (start_date IS NULL OR start_date <= NOW())
    ORDER BY discount_value DESC
    LIMIT 15
");
if ($res_v) {
    while ($v = $res_v->fetch_assoc()) {
        $disc = $v['discount_type'] === 'percent' ? "-{$v['discount_value']}%"
              : ($v['discount_type'] === 'freeship' ? 'Freeship'
              : '-' . number_format($v['discount_value'], 0, ',', '.') . 'đ');
        $min  = $v['min_order_value'] > 0 ? number_format($v['min_order_value'], 0, ',', '.') . 'đ' : '0đ';
        $max  = $v['max_discount'] > 0 ? number_format($v['max_discount'], 0, ',', '.') . 'đ' : '';
        $end  = $v['end_date'] ? date('d/m/Y', strtotime($v['end_date'])) : 'Không giới hạn';
        
        $vouchers_ctx[] = "- Mã {$v['code']}: Giảm {$disc} ({$v['title']}) - Đơn tối thiểu {$min} - HSD: {$end}";
        
        $vouchers_data[] = [
            'code' => $v['code'],
            'title' => $v['title'] ?: 'Mã giảm giá mua sắm',
            'discount' => $disc,
            'min_order' => $min,
            'max_discount' => $max,
            'expiry' => $end
        ];
    }
}
$vouchers_text = !empty($vouchers_ctx) ? implode("\n", $vouchers_ctx) : "Hiện không có voucher nào đang hoạt động.";

function cai_render_voucher_cards($data) {
    if (empty($data)) return "Hiện tại shop chưa có mã giảm giá công khai nào.";
    $html = "<div class='cai-vouchers-list'>";
    foreach (array_slice($data, 0, 4) as $vc) {
        $code_esc = htmlspecialchars($vc['code']);
        $title_esc = htmlspecialchars($vc['title']);
        $disc_esc = htmlspecialchars($vc['discount']);
        $min_esc = htmlspecialchars($vc['min_order']);
        $exp_esc = htmlspecialchars($vc['expiry']);
        $html .= "
        <div class='cai-coupon-card'>
            <div class='cai-coupon-left'>
                <span class='cai-coupon-badge'>{$disc_esc}</span>
                <span class='cai-coupon-code' onclick='caiCopyCode(\"{$code_esc}\", this)' title='Bấm để sao chép'>
                    <code>{$code_esc}</code> <i class='fa-regular fa-copy'></i>
                </span>
            </div>
            <div class='cai-coupon-right'>
                <div class='cai-coupon-title'>{$title_esc}</div>
                <div class='cai-coupon-desc'>Đơn từ {$min_esc} · HSD: {$exp_esc}</div>
            </div>
        </div>";
    }
    $html .= "</div>";
    return $html;
}

// ================================================================
// 6. LẤY SỰ KIỆN SALE ĐANG DIỄN RA
// ================================================================
$events_ctx = [];
$events_data = [];
$res_e = $conn->query("
    SELECT id, name, slug, description, start_date, end_date, icon
    FROM sale_events
    WHERE (status = 1 OR status = 'active') AND start_date <= NOW() AND end_date >= NOW()
    ORDER BY sort_order ASC, start_date DESC LIMIT 5
");
if ($res_e) {
    while ($e = $res_e->fetch_assoc()) {
        $period = date('d/m/Y', strtotime($e['start_date'])) . ' - ' . date('d/m/Y', strtotime($e['end_date']));
        $desc_e = !empty($e['description']) ? trim(strip_tags($e['description'])) : 'Chương trình ưu đãi giảm giá đặc biệt';
        $events_ctx[] = "- Sự kiện {$e['name']} ({$period}): {$desc_e}";
        $events_data[] = [
            'id' => $e['id'],
            'name' => $e['name'],
            'slug' => $e['slug'],
            'period' => $period,
            'desc' => $desc_e,
            'icon' => $e['icon'] ?: 'fa-solid fa-fire'
        ];
    }
}
$events_text = !empty($events_ctx) ? implode("\n", $events_ctx) : "Hiện không có sự kiện sale nào đang diễn ra.";

function cai_render_event_cards($data) {
    if (empty($data)) return "Hiện tại chưa có sự kiện sale nào đang diễn ra.";
    $html = "<div class='cai-events-list'>";
    foreach (array_slice($data, 0, 3) as $ev) {
        $name_esc = htmlspecialchars($ev['name']);
        $period_esc = htmlspecialchars($ev['period']);
        $desc_esc = htmlspecialchars($ev['desc']);
        $slug_esc = htmlspecialchars($ev['slug'] ?: 'sale');
        $html .= "
        <div class='cai-event-card'>
            <div class='cai-event-header'>
                <span class='cai-event-badge'><i class='fa-solid fa-fire me-1'></i>Đang Diễn Ra</span>
                <span class='cai-event-date'><i class='fa-regular fa-clock me-1'></i>{$period_esc}</span>
            </div>
            <div class='cai-event-title'>{$name_esc}</div>
            <div class='cai-event-desc'>{$desc_esc}</div>
            <a href='sale-event.php?slug={$slug_esc}' class='cai-event-link'>Xem deal hot <i class='fa-solid fa-arrow-right ms-1'></i></a>
        </div>";
    }
    $html .= "</div>";
    return $html;
}

// ================================================================
// 7. LẤY HƯỚNG DẪN ĐO CHÂN
// ================================================================
$tips_ctx = [];
$res_t = $conn->query("SELECT step_number, title, description FROM size_guide_tips WHERE status = 1 ORDER BY step_number ASC LIMIT 4");
if ($res_t) {
    while ($t = $res_t->fetch_assoc()) {
        $tips_ctx[] = "Bước {$t['step_number']}: {$t['title']} - {$t['description']}";
    }
}
$tips_text = !empty($tips_ctx) ? implode("\n", $tips_ctx)
           : "Bước 1: Đặt giấy A4 sát tường - Bước 2: Vẽ khung bàn chân - Bước 3: Đo chiều dài (cm) - Bước 4: Đối chiếu bảng size";

// ================================================================
// 8. XÂY DỰNG SYSTEM PROMPT TOÀN DIỆN
// ================================================================
$bank_info = $bank_id ? "Ngân hàng {$bank_id} - STK: {$bank_account} - Chủ TK: {$bank_name}" : "Liên hệ shop để biết thông tin TK";

$system_prompt = <<<PROMPT
Bạn là **Shoes AI** – trợ lý tư vấn thông minh của cửa hàng **{$site_name}**, chuyên phân phối giày dép thời trang cao cấp chính hãng.

## THÔNG TIN CỬA HÀNG
- Tên: **{$site_name}**
- Địa chỉ: {$site_address}
- Hotline & Zalo: **{$site_phone}** (08:00 – 21:30, 7 ngày/tuần kể cả lễ tết)
- Email: {$site_email}
- Thanh toán: COD (trả khi nhận hàng), Chuyển khoản ({$bank_info}), Quét QR MoMo/VNPAY
- Phí vận chuyển: Tính theo từng tỉnh/thành phố khi đặt hàng (hoặc áp dụng mã Voucher Freeship nếu có); giao toàn quốc 2–4 ngày
- Đổi trả miễn phí **30 ngày** nếu lỗi nhà sản xuất; đổi size miễn phí **7 ngày**

## THƯƠNG HIỆU ĐANG BÁN
{$brands_text}

## DANH MỤC SẢN PHẨM
{$cats_text}

## SỰ KIỆN SALE ĐANG DIỄN RA
{$events_text}

## MÃ GIẢM GIÁ (VOUCHER) ĐANG HOẠT ĐỘNG
{$vouchers_text}

## HƯỚNG DẪN ĐO SIZE CHÂN
{$tips_text}
- Size có sẵn: 36 – 44 (tùy sản phẩm, xem trang chi tiết)
- Nếu chân bè hoặc mu cao → Tăng +1 size
- Nữ mang giày nam: trừ 1 size (VD: Nữ 39 → Nam 38)

## DANH SÁCH SẢN PHẨM ĐANG CÓ BÁN (dùng ID để gợi ý)
{$products_text}

## CÁCH PHÂN TÍCH VÀ GỢI Ý SẢN PHẨM
Khi khách nhắc đến bất kỳ tiêu chí nào sau đây, hãy **phân tích kỹ** rồi gợi ý đúng sản phẩm:
- **Giới tính**: nam/nữ/unisex
- **Mục đích**: chạy bộ / bóng rổ / thời trang / đi học / đi làm / đi biển
- **Thương hiệu yêu thích**: Nike, Adidas, Jordan, Puma, Vans, Converse...
- **Ngân sách**: rẻ / tầm trung / cao cấp (so sánh giá sản phẩm)
- **Size**: tự động lọc sản phẩm có size khách cần
- **Tình trạng**: còn hàng / hàng hot / hàng mới

## QUY TẮC TRẢ LỜI
1. **Mỗi câu trả lời PHẢI có**: Đúng 3 câu gợi ý tiếp theo dạng `[FOLLOWUP:câu1||câu2||câu3]` ở cuối. Câu ≤8 từ, phù hợp ngữ cảnh.
2. **Khi gợi ý sản phẩm**: Bắt buộc thêm `[SUGGEST_ID:id1,id2,id3]` vào cuối (tối đa 4 sản phẩm phù hợp nhất). Chỉ gợi ý sản phẩm **còn hàng**.
3. **Ngôn ngữ**: Tiếng Việt, thân thiện, chuyên nghiệp. Dùng emoji hợp lý.
4. **Độ dài**: Tối đa 220 từ. Súc tích, đúng trọng tâm. Ưu tiên dùng danh sách gạch đầu dòng.
5. **Tư vấn thông minh**: Nếu khách chưa rõ nhu cầu → Hỏi thêm 1-2 câu để tư vấn đúng hơn.
6. **Thông tin chính xác**: Tra cứu giá, tồn kho, size từ danh sách sản phẩm bên trên.
7. **Không bịa đặt**: Nếu không có thông tin → Hướng dẫn gọi hotline **{$site_phone}**.
8. **Nhớ ngữ cảnh**: Tiếp nối hội thoại tự nhiên, không hỏi lại điều khách đã nói.
9. **So sánh sản phẩm**: Khi được yêu cầu, so sánh rõ ràng về giá, thương hiệu, tính năng.
10. **Câu hỏi ngoài lề**: Có thể trả lời mọi chủ đề thông thường nhưng ưu tiên dẫn dắt về mua sắm giày.
PROMPT;


// ================================================================
// 9. CHUẨN HÓA LỊCH SỬ HỘI THOẠI MULTI-TURN
// ================================================================
$contents  = [];
$last_role = null;

foreach ($history as $h) {
    if (!empty($h['role']) && !empty($h['text'])) {
        $role = in_array($h['role'], ['model', 'bot', 'assistant']) ? 'model' : 'user';
        $clean_text = trim(preg_replace('/\[SUGGEST_ID:[0-9,\s]+\]/i', '', strip_tags($h['text'])));
        if (empty($clean_text)) continue;
        if (empty($contents) && $role !== 'user') continue;
        if ($role !== $last_role) {
            $contents[] = ['role' => $role, 'parts' => [['text' => $clean_text]]];
            $last_role = $role;
        }
    }
}

// Loại bỏ lượt user cuối cùng trong lịch sử để nhường cho câu hiện tại
if (!empty($contents) && end($contents)['role'] === 'user') {
    array_pop($contents);
}

// Thêm tin nhắn hiện tại của khách, kèm nhắc FOLLOWUP
$userMessageWithHint = $userMessage . "\n\n[Nhớ kết thúc bằng [FOLLOWUP:câu hỏi 1||câu hỏi 2||câu hỏi 3] phù hợp ngữ cảnh]";
$contents[] = ['role' => 'user', 'parts' => [['text' => $userMessageWithHint]]];

// ================================================================
// 10. GỌI GEMINI API
// ================================================================
$ai_reply = null;

if (!empty(GEMINI_API_KEY)) {
    $payload = [
        'system_instruction' => ['parts' => [['text' => $system_prompt]]],
        'contents'           => $contents,
        'generationConfig'   => [
            'temperature'     => 0.6,
            'maxOutputTokens' => 2048,
            'topP'            => 0.9,
            'topK'            => 40,
        ],
    ];

    $models_to_try = ['gemini-3.6-flash', 'gemini-flash-latest', 'gemini-3.7-flash', 'gemini-2.5-flash'];

    foreach ($models_to_try as $model_name) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_name}:generateContent?key=" . GEMINI_API_KEY;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT,        15);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && !empty($response)) {
            $res_data = json_decode($response, true);
            if (isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
                $ai_reply = $res_data['candidates'][0]['content']['parts'][0]['text'];
                break;
            }
        }
    }
}

// ================================================================
// 11. XỬ LÝ KẾT QUẢ TRẢ VỀ TỪ GEMINI
// ================================================================
$suggested_products = [];
$follow_up_questions = [];

if ($ai_reply !== null) {
    // 1. Lọc sản phẩm gợi ý từ thẻ [SUGGEST_ID:...]
    if (preg_match('/\[SUGGEST_ID:([0-9,\s]+)\]/i', $ai_reply, $matches)) {
        $ids = array_map('intval', array_filter(array_map('trim', explode(',', $matches[1]))));
        foreach ($products_full as $p) {
            if (in_array($p['id'], $ids) && count($suggested_products) < 3) {
                $suggested_products[] = $p;
            }
        }
        $ai_reply = preg_replace('/\s*\[SUGGEST_ID:[0-9,\s]+\]/i', '', $ai_reply);
    }

    // 2. Nếu Gemini quên ghi thẻ mà ghi (ID 1) hoặc (ID: 1) trong text -> Tự động nhận diện để đính kèm card
    if (empty($suggested_products)) {
        if (preg_match_all('/\(ID:?\s*(\d+)\)/i', $ai_reply, $idMatches)) {
            $extracted_ids = array_map('intval', $idMatches[1]);
            foreach ($products_full as $p) {
                if (in_array($p['id'], $extracted_ids) && count($suggested_products) < 3) {
                    $suggested_products[] = $p;
                }
            }
        }
    }

    // 3. Lọc gợi ý câu hỏi tiếp theo [FOLLOWUP:...]
    if (preg_match('/\[FOLLOWUP:([^\]]+)\]/i', $ai_reply, $fmatch)) {
        $raw_followups = explode('||', $fmatch[1]);
        foreach ($raw_followups as $fq) {
            $fq = trim($fq);
            if (!empty($fq) && mb_strlen($fq, 'UTF-8') <= 60) $follow_up_questions[] = $fq;
        }
        $ai_reply = preg_replace('/\s*\[FOLLOWUP:[^\]]+\]/i', '', $ai_reply);
    }

    // 4. Nếu chưa có followup -> Gợi ý mặc định thông minh theo ngữ cảnh
    if (empty($follow_up_questions)) {
        $m_lower = mb_strtolower($userMessage, 'UTF-8');
        if (strpos($m_lower, 'size') !== false || strpos($m_lower, 'chân') !== false) {
            $follow_up_questions = ['Size 40 tương đương cm nào?', 'Giày cho chân bè', 'Giày đang có sale'];
        } elseif (strpos($m_lower, 'nam') !== false) {
            $follow_up_questions = ['Giày sneaker nam hot', 'Giày chạy bộ nam', 'Giày nam dưới 500k'];
        } elseif (strpos($m_lower, 'nữ') !== false || strpos($m_lower, 'nu') !== false) {
            $follow_up_questions = ['Giày sneaker nữ đẹp', 'Giày nữ đi học', 'Giày nữ đang giảm giá'];
        } elseif (strpos($m_lower, 'adidas') !== false || strpos($m_lower, 'nike') !== false || strpos($m_lower, 'jordan') !== false) {
            $follow_up_questions = ['So sánh Nike và Adidas', 'Giày hot nhất cửa hàng', 'Mã giảm giá hôm nay'];
        } else {
            $follow_up_questions = ['👟 Giày hot nhất shop', '📏 Hướng dẫn chọn size', '🎁 Mã giảm giá hôm nay'];
        }
    }

    // 5. Render Markdown đơn giản → HTML
    $ai_reply = preg_replace('/\*\*([^*]+)\*\*/u', '<b>$1</b>', $ai_reply);
    $ai_reply = preg_replace('/\*([^*]+)\*/u',     '<i>$1</i>', $ai_reply);
    $ai_reply = preg_replace('/^#{1,3} (.+)$/mu',  '<strong>$1</strong>', $ai_reply);
    $ai_reply = str_replace(["\r\n", "\n"], '<br>', $ai_reply);
    $ai_reply = preg_replace('/(<br>){3,}/', '<br><br>', $ai_reply);

    ob_end_clean();
    echo json_encode([
        'reply'       => $ai_reply,
        'products'    => $suggested_products,
        'suggestions' => $follow_up_questions,
        'source'      => 'gemini'
    ]);
    exit();
}

// ================================================================
// 12. BỘ PHẢN HỒI THÔNG MINH DỰ PHÒNG (INTENT CLASSIFICATION)
// ================================================================
$msgLower = mb_strtolower($userMessage, 'UTF-8');
$reply    = '';

// 1. Địa chỉ cửa hàng
if (preg_match('/\b(địa chỉ|ở đâu|cửa hàng|shop ở|shop có chi nhánh|vị trí)\b/iu', $msgLower)) {
    $reply = "📍 <b>Địa chỉ cửa hàng {$site_name}:</b><br>{$site_address}<br>⏰ <b>Giờ mở cửa:</b> 08:00 – 21:30 mỗi ngày (kể cả Lễ, Tết).<br>📞 Hotline / Zalo: <b>{$site_phone}</b>";
    $follow_up_questions = ['Giày sneaker hot nhất', 'Mã giảm giá hôm nay', 'Chính sách đổi trả'];
}
// 2. Hotline / Liên hệ
elseif (preg_match('/\b(hotline|liên hệ|số điện thoại|sđt|zalo|email|gọi cho shop)\b/iu', $msgLower)) {
    $reply = "📞 <b>Kênh liên hệ hỗ trợ chính thức:</b><br>- Hotline & Zalo: <b>{$site_phone}</b> (08:00 – 21:30)<br>- Email: <b>{$site_email}</b><br>- Địa chỉ: {$site_address}";
    $follow_up_questions = ['Tư vấn chọn size', 'Xem mã giảm giá', 'Thời gian giao hàng'];
}
// 3. Phí ship / Giao hàng
elseif (preg_match('/\b(ship|phí ship|vận chuyển|giao hàng|bao lâu nhận|ship cod|phí giao|freeship|miễn phí ship)\b/iu', $msgLower)) {
    $reply = "🚚 <b>Chính sách vận chuyển {$site_name}:</b><br>- <b>Phí vận chuyển:</b> Được tính tự động theo từng tỉnh/thành phố khi bạn chọn địa chỉ tại trang thanh toán.<br>- <b>Ưu đãi Freeship:</b> Bạn có thể áp dụng các mã Voucher Freeship trong danh sách mã giảm giá của shop.<br>- <b>Thời gian giao hàng:</b> 2 – 4 ngày làm việc toàn quốc.<br>- <b>Kiểm tra hàng:</b> Khách hàng được đồng kiểm trước khi nhận và thanh toán (COD).";
    $follow_up_questions = ['Mã voucher freeship', 'Xem tất cả sản phẩm', 'Chính sách đổi trả'];
}
// 4. Đổi trả / Bảo hành
elseif (preg_match('/\b(đổi|trả|bảo hành|đổi size|lỗi sản phẩm)\b/iu', $msgLower)) {
    $reply = "↩️ <b>Chính sách Đổi Trả & Bảo Hành:</b><br>- <b>Đổi trả miễn phí trong 30 ngày</b> nếu sản phẩm có lỗi từ nhà sản xuất.<br>- <b>Đổi size miễn phí trong 7 ngày</b> nếu không vừa chân.<br>- Hỗ trợ nhanh chóng qua Hotline / Zalo: <b>{$site_phone}</b>.";
    $follow_up_questions = ['Cách đo size chân', 'Giày sneaker hot', 'Mã giảm giá hôm nay'];
}
// 5. Thanh toán / Tài khoản ngân hàng
elseif (preg_match('/\b(thanh toán|stk|số tài khoản|ngân hàng|chuyển khoản|banking|qr)\b/iu', $msgLower)) {
    $reply = "💳 <b>Phương thức thanh toán hỗ trợ:</b><br>- <b>COD:</b> Thanh toán tiền mặt khi nhận hàng.<br>- <b>Chuyển khoản / Quét mã QR:</b> {$bank_info}.<br>- <b>MoMo / VNPAY:</b> Quét mã tức thì.";
    $follow_up_questions = ['Kiểm tra đơn hàng', 'Mã giảm giá hôm nay', 'Phí vận chuyển'];
}
// 6. Voucher / Mã giảm giá
elseif (preg_match('/\b(voucher|mã giảm|khuyến mãi|mã|code|ưu đãi|chiết khấu|coupon)\b/iu', $msgLower)) {
    if (!empty($vouchers_data)) {
        $reply = "🎁 <b>Danh sách Mã Giảm Giá Đang Hoạt Động:</b><br><small style='color:#718096'>Chạm vào mã để sao chép nhanh khi thanh toán:</small>" . cai_render_voucher_cards($vouchers_data);
    } else {
        $reply = "Hiện tại shop chưa có mã giảm giá công khai nào. Bạn liên hệ hotline <b>{$site_phone}</b> để nhận ưu đãi riêng nhé!";
    }
    $follow_up_questions = ['Sự kiện sale đang diễn ra', 'Giày bán chạy nhất', 'Cách nhập mã voucher'];
}
// 7. Sự kiện Sale / Flash Sale / Giờ vàng
elseif (preg_match('/\b(sự kiện|event|sale|giờ vàng|flash sale|giảm giá sốc)\b/iu', $msgLower)) {
    if (!empty($events_data)) {
        $reply = "🔥 <b>Các Chương Trình Sale Đang Diễn Ra:</b><br><small style='color:#718096'>Ưu đãi có hạn, hãy săn ngay kẻo lỡ:</small>" . cai_render_event_cards($events_data);
    } else {
        $reply = "Hiện tại shop đang chuẩn bị các chương trình sale mới. Bạn theo dõi trang chủ để săn deal hot nhé!";
    }
    $follow_up_questions = ['Mã giảm giá hôm nay', 'Giày nam hot nhất', 'Giày nữ bán chạy'];
}
// 8. Tư vấn size chân (khi khách hỏi về số đo / size)
elseif (preg_match('/\b(size|cỡ|đo chân|bảng size|chân dài|chân bè|mu bàn chân)\b/iu', $msgLower)) {
    // Đoạn logic quy đổi cm -> size nếu khách nhập số cm
    $cm_found = null;
    if (preg_match('/(\d{2}(?:[.,]\d)?)\s*(?:cm|centimet)?/i', $msgLower, $cm_match)) {
        $val = floatval(str_replace(',', '.', $cm_match[1]));
        if ($val >= 22 && $val <= 30) $cm_found = $val;
    }

    $size_calc = "";
    if ($cm_found) {
        $sz = 36;
        if ($cm_found <= 22.5) $sz = 36;
        elseif ($cm_found <= 23.0) $sz = 37;
        elseif ($cm_found <= 23.5) $sz = 38;
        elseif ($cm_found <= 24.5) $sz = 39;
        elseif ($cm_found <= 25.0) $sz = 40;
        elseif ($cm_found <= 25.5) $sz = 41;
        elseif ($cm_found <= 26.0) $sz = 42;
        elseif ($cm_found <= 26.5) $sz = 43;
        else $sz = 44;
        $size_calc = "<br>🎯 Chiều dài bàn chân <b>{$cm_found}cm</b> của bạn phù hợp nhất với <b>Size {$sz}</b> (nếu chân bè hãy tăng lên 1 size nhé)!";
    }

    $reply = "📏 <b>Bảng Hướng Dẫn Chọn Size Chuẩn:</b><br>- Size 36: 22.5cm · Size 37: 23.0cm · Size 38: 23.5cm<br>- Size 39: 24.5cm · Size 40: 25.0cm · Size 41: 25.5cm<br>- Size 42: 26.0cm · Size 43: 26.5cm · Size 44: 27.0cm{$size_calc}<br>💡 Đổi size miễn phí trong 7 ngày nếu không vừa!";
    $follow_up_questions = ['Giày cho chân bè', 'Giày sneaker nam hot', 'Giày sneaker nữ hot'];
}
// 9. Tìm kiếm và tư vấn sản phẩm thông minh
else {
    $where_parts = ["p.status = 1"];
    $is_male_query   = (bool)preg_match('/\b(nam|men|boy|trai)\b/iu', $msgLower);
    $is_female_query = (bool)preg_match('/\b(nữ|nu|women|girl|gái)\b/iu', $msgLower);
    $is_hot_query    = (bool)preg_match('/\b(hot|bán chạy|xu hướng|trend|nổi bật|ưa chuộng|thịnh hành)\b/iu', $msgLower);

    // Case-insensitive gender filter using LOWER()
    if ($is_male_query) {
        $where_parts[] = "(LOWER(p.gender) IN ('nam', 'unisex') OR p.gender IS NULL OR p.name LIKE '%Nam%')";
    } elseif ($is_female_query) {
        $where_parts[] = "(LOWER(p.gender) IN ('nu', 'nữ', 'unisex') OR p.gender IS NULL OR p.name LIKE '%Nữ%')";
    }

    // Lọc thương hiệu nếu có
    if (preg_match('/\b(nike|adidas|jordan|puma|converse|vans|new balance|mlb|crocs|birkenstock|skechers|asics|on running|salomon)\b/iu', $msgLower, $brand_match)) {
        $b_name = $conn->real_escape_string($brand_match[1]);
        $where_parts[] = "(b.name LIKE '%{$b_name}%' OR p.name LIKE '%{$b_name}%')";
    }

    $where_sql = "WHERE " . implode(" AND ", $where_parts);
    $order_sql = $is_hot_query ? "ORDER BY p.is_hot DESC, p.sold_count DESC, p.view_count DESC, p.id DESC" : "ORDER BY p.sold_count DESC, p.id DESC";

    $res_fb = $conn->query("
        SELECT p.id, p.name, p.price, p.old_price, p.discount_percent, p.main_image, COALESCE(b.name,'Khác') as brand_name
        FROM products p 
        LEFT JOIN brands b ON p.brand_id=b.id 
        LEFT JOIN categories c ON p.category_id=c.id 
        {$where_sql} 
        {$order_sql}
        LIMIT 3
    ");

    // Safety net: nếu không tìm được theo gender/brand → lấy top sản phẩm bán chạy
    if (!$res_fb || $res_fb->num_rows === 0) {
        $res_fb = $conn->query("
            SELECT p.id, p.name, p.price, p.old_price, p.discount_percent, p.main_image, COALESCE(b.name,'Khác') as brand_name
            FROM products p LEFT JOIN brands b ON p.brand_id=b.id
            WHERE p.status = 1
            ORDER BY p.is_hot DESC, p.sold_count DESC, p.view_count DESC LIMIT 3
        ");
    }

    if ($res_fb && $res_fb->num_rows > 0) {
        $title_intent = $is_male_query ? "giày Nam nổi bật và được ưa chuộng nhất" : ($is_female_query ? "giày Nữ thời trang và bán chạy nhất" : "mẫu giày phù hợp nhất với bạn");
        $reply = "Dưới đây là các mẫu <b>{$title_intent}</b> tại <b>{$site_name}</b>:<br>";
        while ($p = $res_fb->fetch_assoc()) {
            $pf = number_format($p['price'], 0, ',', '.') . 'đ';
            $disc_badge = $p['discount_percent'] > 0 ? " <small style='color:#ef4444;font-weight:bold;'>(-{$p['discount_percent']}%)</small>" : "";
            $suggested_products[] = [
                'id'       => $p['id'],
                'name'     => $p['name'],
                'price'    => $p['price'],
                'old_price'=> $p['old_price'],
                'discount' => $p['discount_percent'],
                'image'    => $p['main_image'],
                'url'      => 'product-detail.php?id=' . $p['id']
            ];
            $reply .= "👟 <b>{$p['name']}</b> ({$p['brand_name']}) – <span style='color:#e05b7f;font-weight:bold;'>{$pf}</span>{$disc_badge}<br>";
        }
        $reply .= "Bấm vào từng mẫu bên dưới để xem chi tiết, chọn size và đặt hàng nhé!";
        $follow_up_questions = ['Bảng hướng dẫn chọn size', 'Mã giảm giá hôm nay', 'Chính sách bảo hành'];
    } else {
        $reply = "Chào bạn! 👋 Mình là <b>{$site_name} AI</b> – trợ lý tư vấn của <b>{$site_name}</b>.<br>Bạn cần tư vấn mẫu giày nào (Nike, Adidas, Jordan...), hỏi về size chân hay mã giảm giá hôm nay ạ?";
        $follow_up_questions = ['👟 Giày chạy bộ êm chân', '🔥 Top sản phẩm bán chạy', '🎁 Mã giảm giá hôm nay'];
    }
}

if (empty($follow_up_questions)) {
    $follow_up_questions = ['👟 Giày sneaker hot', '📏 Hướng dẫn chọn size', '🎁 Mã giảm giá hôm nay'];
}

ob_end_clean();
echo json_encode([
    'reply'       => $reply,
    'products'    => $suggested_products,
    'suggestions' => $follow_up_questions,
    'source'      => 'fallback'
]);
exit();