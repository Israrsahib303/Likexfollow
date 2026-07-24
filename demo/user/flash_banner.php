<?php
// user/flash_banner.php
// Beast9 - Flash Deal System (v22.0 - Fixed JS Alerts + History Redirect)

// --- SAFETY CHECKS ---
if (!isset($db) && isset($GLOBALS['db'])) { $db = $GLOBALS['db']; }
if (!isset($db) || !is_object($db)) { return; } 

// --- HELPER: CLEAN TEXT ---
if (!function_exists('clean_text')) {
    function clean_text($str) {
        if (!$str) return '';
        $s = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// --- HELPER: SMART TIME FORMATTER ---
if (!function_exists('smart_time')) {
    function smart_time($val) {
        if (empty($val)) return 'Instant';
        if (!is_numeric($val)) return clean_text($val); 
        
        $mins = (int)$val;
        if ($mins < 60) return $mins . ' Mins';
        if ($mins == 60) return '1 Hour';
        if ($mins % 60 == 0) return ($mins / 60) . ' Hours';
        if ($mins > 1440) return round($mins / 1440, 1) . ' Days';
        
        return floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
    }
}

// --- 1. CURRENCY LOGIC ---
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$c_rate = 1; 
$c_sym = '$';

if (function_exists('getCurrencyList')) {
    $curr_list = getCurrencyList(); 
    $selected_curr = $curr_list[$curr_code] ?? $curr_list['PKR'] ?? null;
    if ($selected_curr) {
        $c_rate = $selected_curr['rate'];
        $c_sym  = $selected_curr['symbol'];
    }
}

// --- 2. FETCH DEAL ---
try {
    $flash = $db->query("
        SELECT f.*, 
               s.name as service_name, 
               s.description as svc_desc, 
               s.avg_time, 
               s.has_refill, 
               s.has_cancel, 
               s.min, 
               s.max
        FROM flash_sales f
        LEFT JOIN smm_services s ON (f.item_id = s.id)
        WHERE f.status='active' AND f.end_time > NOW() AND f.type = 'smm'
        LIMIT 1
    ")->fetch();
} catch (Exception $e) { return; }

if ($flash):
    // --- 3. DATA PREPARATION ---
    $deal_id = $flash['id'];
    $item_id = $flash['item_id'];
    
    // Names & Desc
    $raw_name = !empty($flash['service_name']) ? $flash['service_name'] : $flash['item_name'];
    $item_name = clean_text($raw_name);
    $item_desc = clean_text($flash['svc_desc']);

    // Specs
    $min_qty = !empty($flash['min']) ? $flash['min'] : 100;
    $max_qty = !empty($flash['max']) ? $flash['max'] : 10000;
    $has_refill = isset($flash['has_refill']) ? $flash['has_refill'] : 0;
    $has_cancel = isset($flash['has_cancel']) ? $flash['has_cancel'] : 0;
    $avg_time = smart_time($flash['avg_time']);

    // Prices
    $price_old = $flash['original_price'] * $c_rate;
    $price_new = $flash['discounted_price'] * $c_rate;
    if ($price_new <= 0) { $price_new = 0.01; } 
    
    $percent   = ($price_old > 0) ? round((($price_old - $price_new) / $price_old) * 100) : 0;
    $saved_amt = $price_old - $price_new;

    // Icon
    $icon = "flash.png"; 
    $name_lower = strtolower($item_name);
    if(strpos($name_lower, 'netflix')!==false) $icon = "net-flix-ultra-4k-screens-69126007908d3.jpeg";
    elseif(strpos($name_lower, 'instagram')!==false) $icon = "Instagram.png";
    elseif(strpos($name_lower, 'tiktok')!==false) $icon = "TikTok.png";
    elseif(strpos($name_lower, 'youtube')!==false) $icon = "Youtube.png";
    elseif(strpos($name_lower, 'spotify')!==false) $icon = "Spotify.png";
    elseif(strpos($name_lower, 'facebook')!==false) $icon = "Facebook.png";
    elseif(strpos($name_lower, 'telegram')!==false) $icon = "Telegram.png";
    elseif(strpos($name_lower, 'pubg')!==false) $icon = "pubg.png";
    
    $img_path = "../assets/img/icons/" . $icon;
    
    // Receipt Data
    $settings = $GLOBALS['settings'] ?? [];
    $site_name = $settings['site_name'] ?? 'SubHub';
    $wa_number = $settings['whatsapp_number'] ?? 'Support';
    $site_logo = $settings['site_logo'] ?? 'logo.png';
    $logo_url = "../assets/img/" . $site_logo;
?>

<style>
    /* Animations */
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); } 70% { box-shadow: 0 0 0 15px rgba(79, 70, 229, 0); } 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); } }
    @keyframes spinSlow { 100% { transform: rotate(360deg); } }

    /* --- MAIN CARD --- */
    .flash-wrapper {
        background: #fff;
        border-radius: 24px;
        position: relative;
        margin-bottom: 40px;
        box-shadow: 0 20px 50px -15px rgba(0,0,0,0.1);
        border: 1px solid rgba(79, 70, 229, 0.1);
        overflow: hidden;
        transition: transform 0.3s;
    }
    .flash-wrapper:hover { transform: translateY(-3px); }
    
    .flash-bg-pattern {
        position: absolute; inset: 0; opacity: 0.03; z-index: 0;
        background-image: radial-gradient(#4f46e5 1px, transparent 1px);
        background-size: 20px 20px;
    }

    .flash-wrapper::after {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px;
        background: linear-gradient(90deg, #6366f1, #d946ef, #f59e0b);
        z-index: 5;
    }

    .flash-card {
        padding: 30px;
        display: flex; align-items: center; gap: 30px;
        position: relative; z-index: 10;
    }

    /* 1. Timer */
    .flash-timer {
        min-width: 100px;
        text-align: center;
        background: #fff;
        border: 2px solid #eef2ff;
        border-radius: 20px;
        padding: 12px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.03);
    }
    .t-icon { font-size: 1.2rem; margin-bottom: 5px; display: block; animation: spinSlow 4s linear infinite; }
    .t-val { font-size: 1.3rem; font-weight: 800; color: #4f46e5; line-height: 1; font-variant-numeric: tabular-nums; }
    .t-lbl { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-top: 5px; }

    /* 2. Info Section */
    .flash-info { flex: 1; }
    
    .deal-badge {
        background: #fee2e2; color: #ef4444; font-size: 0.7rem; font-weight: 800;
        padding: 5px 12px; border-radius: 50px; display: inline-flex; align-items: center; gap: 6px;
        margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;
        border: 1px solid #fecaca;
    }
    .deal-badge i { animation: pulseGlow 1s infinite; }

    .flash-title {
        font-size: 1.6rem; font-weight: 900; color: #1e293b; margin: 0 0 12px 0;
        line-height: 1.2; letter-spacing: -0.5px;
    }

    /* Price Layout */
    .price-container { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; }
    
    .price-top-row { display: flex; align-items: center; gap: 8px; }
    .lbl-orig { font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; }
    .val-orig { font-size: 0.95rem; color: #dc2626; text-decoration: line-through; font-weight: 700; opacity: 0.9; }
    
    .price-main-row { display: flex; align-items: center; gap: 10px; }
    .val-new { font-size: 2rem; font-weight: 900; color: #16a34a; letter-spacing: -1px; line-height: 1; }
    .val-off { background: #dcfce7; color: #15803d; font-weight: 800; font-size: 0.8rem; padding: 4px 8px; border-radius: 8px; transform: rotate(-3deg); }

    /* 3. Actions */
    .flash-actions { display: flex; flex-direction: column; gap: 10px; min-width: 170px; }
    .btn-claim-deal {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white; border: none; padding: 14px 20px; border-radius: 14px;
        font-weight: 800; font-size: 1rem; cursor: pointer;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        text-transform: uppercase; letter-spacing: 0.5px; animation: pulseGlow 2s infinite; 
    }
    .btn-claim-deal:hover { transform: translateY(-4px); box-shadow: 0 15px 35px -5px rgba(79, 70, 229, 0.5); }
    .btn-share-deal {
        background: #fff; border: 2px solid #f1f5f9; color: #64748b;
        padding: 10px; border-radius: 14px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s;
    }
    .btn-share-deal:hover { border-color: #4f46e5; color: #4f46e5; background: #f8fafc; }

    @media (max-width: 768px) {
        .flash-card { flex-direction: column; text-align: center; padding: 25px; gap: 20px; }
        .flash-timer { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; }
        .price-container { align-items: center; }
        .flash-actions { width: 100%; }
        .val-new { font-size: 1.8rem; }
    }

    /* === 🛍️ POPUP MODAL (Purple Live Border) === */
    .f-overlay {
        display: none; 
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(5px); 
        z-index: 2147483647 !important;
        justify-content: center; align-items: center; padding: 15px;
        opacity: 0; transition: opacity 0.3s;
    }
    .f-overlay.active { display: flex; opacity: 1; }

    /* Elastic Pop-up Animation */
    @keyframes modalPop {
        0% { transform: scale(0.5); opacity: 0; }
        60% { transform: scale(1.05); opacity: 1; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* PURPLE BORDER ANIMATION */
    @keyframes purpleGlowRotate {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Main Box Container */
    .f-box {
        width: 100%; max-width: 350px; 
        border-radius: 20px; 
        box-shadow: 0 30px 60px -12px rgba(0,0,0,0.5);
        z-index: 2147483647;
        position: relative;
        /* THE LIVE PURPLE BORDER MAGIC */
        padding: 3px; /* Border Thickness */
        background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899, #8b5cf6, #6366f1);
        background-size: 300% 300%;
        animation: purpleGlowRotate 3s ease infinite;
    }

    .f-overlay.active .f-box {
        animation: modalPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    /* Inner White Content */
    .f-inner {
        background: #fff; width: 100%; height: 100%;
        border-radius: 17px; /* Match parent minus padding */
        overflow: hidden;
        display: flex; flex-direction: column; max-height: 90vh;
    }

    .f-head { padding: 12px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fff; flex-shrink: 0; }
    .f-body { padding: 15px 18px; background: #fdfdfe; overflow-y: auto; }

    /* Marquee Text */
    .marquee-container { flex: 1; min-width: 0; overflow: hidden; white-space: nowrap; position: relative; }
    .marquee-wrapper { display: flex; overflow: hidden; width: 100%; }
    .marquee-content { white-space: nowrap; animation: scroll 15s linear infinite; padding-right: 20px; font-weight: 800; font-size: 0.8rem; color: #1e293b; }
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

    /* Specs Grid */
    .spec-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; }
    .spec-row { display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; }
    .row-blue { background: #eff6ff; border-color: #bfdbfe; }   
    .row-purple { background: #f3e8ff; border-color: #e9d5ff; } 
    .row-green { background: #dcfce7; border-color: #bbf7d0; }  
    .row-red { background: #fee2e2; border-color: #fecaca; }    
    .spec-left { display: flex; align-items: center; gap: 8px; }
    .spec-icon { color: #6366f1; font-size: 0.85rem; width: 16px; text-align: center; }
    .spec-name { font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .spec-badge { background: #fff; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; box-shadow: 0 2px 5px rgba(0,0,0,0.05); min-width: 50px; text-align: center; }
    .text-blue { color: #0369a1; }
    .text-green { color: #16a34a; }
    .text-red { color: #dc2626; }
    .text-dark { color: #1e293b; }

    /* Description Toggle */
    .desc-btn { width: 100%; background: #fff; border: 1px dashed #cbd5e1; color: #64748b; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; margin-bottom: 12px; transition: 0.2s; }
    .desc-btn:hover { border-color: #6366f1; color: #6366f1; }
    .desc-content { display: none; background: #f1f5f9; padding: 10px; border-radius: 8px; font-size: 0.75rem; color: #334155; margin-bottom: 12px; line-height: 1.4; max-height: 100px; overflow-y: auto; border: 1px solid #e2e8f0; }

    .f-input-group { margin-bottom: 12px; }
    .f-lbl { display: block; font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 4px; }
    .f-input { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.9rem; font-weight: 600; outline: none; transition: 0.2s; color: #1e293b; }
    .f-input:focus { border-color: #4f46e5; background: #fff; }

    /* === 🎫 RECEIPT CARD === */
    #receiptNode { position: fixed; left: -9999px; top: 0; width: 380px; background: #fff; font-family: 'Outfit', sans-serif; overflow: hidden; border-radius: 0; color: #1e293b; z-index: 2147483647; }
    .rc-header { padding: 25px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); position: relative; text-align: center; mask-image: radial-gradient(circle at bottom, transparent 6px, black 6.5px); mask-size: 20px 100%; mask-position: bottom; mask-repeat: repeat-x; padding-bottom: 40px; }
    .rc-stamp { position: absolute; right: 20px; top: 60px; z-index: 0; border: 3px dashed #ef4444; color: #ef4444; padding: 5px 15px; font-size: 1.5rem; font-weight: 900; text-transform: uppercase; transform: rotate(-15deg); opacity: 0.15; pointer-events: none; border-radius: 10px; }
    .rc-body { padding: 20px 25px 30px; background: #fff; position: relative; }
    .rc-prod { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; position: relative; z-index: 2; }
    .rc-img { width: 60px; height: 60px; border-radius: 12px; border: 2px solid #e2e8f0; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .rc-title { font-size: 1rem; font-weight: 800; line-height: 1.3; color: #1e293b; }
    .rc-meta { font-size: 0.75rem; color: #64748b; margin-top: 4px; font-weight: 600; }
    .rc-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
    .rc-row td { padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
    .rc-lbl { font-size: 0.8rem; color: #64748b; font-weight: 700; }
    .rc-val { font-size: 0.9rem; color: #1e293b; font-weight: 800; text-align: right; }
    .val-red-strike { color: #dc2626 !important; text-decoration: line-through; font-weight: 900 !important; font-size: 0.95rem; opacity: 0.9; }
    .val-saved { background:#dcfce7; color:#15803d; padding:4px 8px; border-radius:6px; font-size:0.8rem; font-weight:800; }
    .rc-total { margin-top: 15px; padding-top: 15px; border-top: 2px solid #1e293b; display: flex; justify-content: space-between; align-items: center; }
    .rc-total-lbl { font-size: 1.1rem; font-weight: 900; color: #1e293b; text-transform:uppercase; letter-spacing:0.5px; }
    .rc-total-val { font-size: 1.8rem; font-weight: 900; color: #4f46e5; }
    .rc-footer { margin-top: 25px; text-align: center; opacity: 0.6; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }

    /* --- 🔔 BEAST CUSTOM ALERTS (TOASTS) --- */
    #beast-toast-container {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2147483647; /* MAX Z-INDEX */
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }

    .beast-toast {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 12px 20px;
        border-radius: 50px;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 90vw;
        animation: toastSlideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        border: 2px solid;
        pointer-events: auto;
        cursor: pointer;
    }

    .beast-toast.error {
        border-color: #fecaca;
        background: linear-gradient(135deg, #fff 0%, #fff1f2 100%);
    }

    .beast-toast.success {
        border-color: #bbf7d0;
        background: linear-gradient(135deg, #fff 0%, #f0fdf4 100%);
    }

    .toast-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .error .toast-icon { background: #fee2e2; color: #ef4444; animation: shakeIcon 0.5s ease-in-out; }
    .success .toast-icon { background: #dcfce7; color: #16a34a; animation: bounceIcon 0.5s infinite alternate; }

    .toast-msg {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.3;
    }

    /* Animations */
    @keyframes toastSlideIn {
        0% { transform: translateY(-100px) scale(0.8); opacity: 0; }
        100% { transform: translateY(0) scale(1); opacity: 1; }
    }

    @keyframes toastSlideOut {
        0% { transform: translateY(0) scale(1); opacity: 1; }
        100% { transform: translateY(-50px) scale(0.8); opacity: 0; }
    }

    @keyframes shakeIcon {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-3px) rotate(-5deg); }
        75% { transform: translateX(3px) rotate(5deg); }
    }

    @keyframes bounceIcon {
        from { transform: scale(1); }
        to { transform: scale(1.1); }
    }
</style>

<div class="flash-wrapper" id="flash-banner">
    <div class="flash-bg-pattern"></div>
    <div class="flash-card">
        
        <div class="flash-timer">
            <span class="t-icon">⏳</span>
            <div class="t-val" id="flash-countdown">...</div>
            <div class="t-lbl">Time Left</div>
        </div>

        <div class="flash-info">
            <div class="deal-badge"><i class="fa-solid fa-fire"></i> Best Offer Today</div>
            <h3 class="flash-title"><?= htmlspecialchars($item_name) ?></h3>
            
            <div class="price-container">
                <div class="price-top-row">
                    <span class="lbl-orig">Original Rate:</span>
                    <span class="val-orig"><?= $c_sym ?> <?= number_format($price_old, 2) ?></span>
                </div>
                <div class="price-main-row">
                    <span class="val-new"><?= $c_sym ?> <?= number_format($price_new, 2) ?></span>
                    <span class="val-off"><?= $percent ?>% OFF</span>
                </div>
            </div>
        </div>

        <div class="flash-actions">
            <button onclick="openFlashModal()" class="btn-claim-deal">
                Claim Now <i class="fa-solid fa-arrow-right"></i>
            </button>
            <button onclick="genShareCard()" class="btn-share-deal">
                <i class="fa-solid fa-share-nodes"></i> Share
            </button>
        </div>
    </div>
</div>

<div class="f-overlay" id="checkoutModal">
    <div class="f-box">
        <div class="f-inner">
            
            <div class="f-head">
                <h3 style="margin:0; font-size:1rem; font-weight:800; color:#1e293b;">⚡ Final Order</h3>
                <button onclick="closeModal('checkoutModal')" style="border:none; background:transparent; font-size:1.4rem; color:#94a3b8; cursor:pointer;">&times;</button>
            </div>

            <div class="f-body">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px; background:#f8fafc; padding:8px; border-radius:12px; border:1px solid #e2e8f0;">
                    <img src="<?= $img_path ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                    
                    <div class="marquee-container" style="flex:1; overflow:hidden;">
                        <div class="marquee-wrapper">
                            <div class="marquee-content"><?= htmlspecialchars($item_name) ?>&nbsp;&nbsp;&nbsp;&nbsp;</div>
                            <div class="marquee-content"><?= htmlspecialchars($item_name) ?>&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        </div>
                        <div style="font-size:0.7rem; color:#6366f1; font-weight:700;">
                            <?= $c_sym . ' ' . number_format($price_new, 2) ?> / 1000
                        </div>
                    </div>
                </div>

                <form id="flashForm" onsubmit="submitFlashOrder(event)">
                    <input type="hidden" name="deal_id" value="<?= $flash['id'] ?>">
                    <input type="hidden" name="type" value="smm">
                    <input type="hidden" name="item_id" value="<?= $item_id ?>">

                    <div class="spec-list">
                        <div class="spec-row row-blue">
                            <div class="spec-left"><i class="fa-solid fa-clock spec-icon"></i> <span class="spec-name">Avg Time</span></div>
                            <div class="spec-badge text-blue"><?= $avg_time ?></div>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                            <div class="spec-row row-purple">
                                <div class="spec-left"><i class="fa-solid fa-arrow-down spec-icon"></i> <span class="spec-name">Min</span></div>
                                <div class="spec-badge text-dark"><?= number_format($min_qty) ?></div>
                            </div>
                            <div class="spec-row row-purple">
                                <div class="spec-left"><i class="fa-solid fa-arrow-up spec-icon"></i> <span class="spec-name">Max</span></div>
                                <div class="spec-badge text-dark"><?= number_format($max_qty) ?></div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                            <div class="spec-row row-green">
                                <div class="spec-left"><i class="fa-solid fa-rotate spec-icon"></i> <span class="spec-name">Refill</span></div>
                                <div class="spec-badge <?= $has_refill?'text-green':'text-red' ?>"><?= $has_refill?'Yes':'No' ?></div>
                            </div>
                            <div class="spec-row row-red">
                                <div class="spec-left"><i class="fa-solid fa-ban spec-icon"></i> <span class="spec-name">Cancel</span></div>
                                <div class="spec-badge <?= $has_cancel?'text-green':'text-red' ?>"><?= $has_cancel?'Yes':'No' ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(!empty($item_desc)): ?>
                        <button type="button" class="desc-btn" onclick="toggleDesc()">
                            <i class="fa-solid fa-file-lines"></i> View Description
                        </button>
                        <div class="desc-content" id="dealDesc">
                            <?= nl2br($item_desc) ?>
                        </div>
                    <?php endif; ?>

                    <div class="f-input-group">
                        <label class="f-lbl">Link / Username</label>
                        <input type="text" name="link" class="f-input" placeholder="https://..." required>
                    </div>

                    <div style="margin-bottom:15px; display:flex; gap:10px;">
                        <div style="flex:1">
                            <label class="f-lbl">Quantity</label>
                            <input type="number" name="quantity" id="dealQty" class="f-input" 
                                   value="1000" 
                                   min="<?= $min_qty ?>" 
                                   max="<?= $max_qty ?>" 
                                   oninput="calcTotal()">
                        </div>
                        <div style="flex:1; text-align:right; align-self:center;">
                            <label class="f-lbl">Total Pay</label>
                            <div id="dealTotal" style="font-size:1.2rem; font-weight:900; color:#4f46e5;">...</div>
                        </div>
                    </div>

                    <button type="submit" style="width:100%; background:#1e293b; color:white; padding:12px; border:none; border-radius:10px; font-weight:800; font-size:0.9rem; cursor:pointer; display:flex; justify-content:center; align-items:center; gap:8px; transition:0.2s;">
                        Confirm Order <i class="fa-solid fa-bolt"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="f-overlay" id="successModal">
    <div class="f-box">
        <div class="f-inner" style="text-align:center; padding:30px 20px; justify-content:center;">
            <canvas id="confetti-canvas" style="position:absolute; inset:0; pointer-events:none;"></canvas>
            <div style="width:70px; height:70px; background:#dcfce7; color:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 15px; animation:pulseGlow 2s infinite;">
                <i class="fa-solid fa-check"></i>
            </div>
            <h2 style="font-size:1.5rem; font-weight:900; color:#1e293b; margin:0 0 8px 0;">Congratulations! 🎉</h2>
            <p style="color:#64748b; font-size:0.9rem; margin-bottom:25px;">Your flash order has been placed successfully.</p>
            <button onclick="window.location.assign('smm_history.php')" style="background:#4f46e5; color:white; padding:10px 25px; border-radius:50px; border:none; font-weight:800; cursor:pointer;">Awesome</button>
        </div>
    </div>
</div>

<div id="receiptNode">
    <div class="rc-header">
        <div style="position:relative; z-index:2;">
            <img src="<?= $logo_url ?>" style="height:60px; background:white; padding:8px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.2); display:block; margin:0 auto;">
        </div>
    </div>
    <div class="rc-body">
        <div class="rc-stamp">⚡ FLASH SALE</div> 

        <div class="rc-prod">
            <img src="<?= $img_path ?>" class="rc-img">
            <div>
                <div class="rc-title"><?= htmlspecialchars($item_name) ?></div>
                <div class="rc-meta">SMM Service</div>
            </div>
        </div>

        <table class="rc-table">
            <tr class="rc-row">
                <td class="rc-lbl" style="color:#dc2626; font-weight:800;">Original Price</td>
                <td class="rc-val val-red-strike"><?= $c_sym . ' ' . number_format($price_old, 2) ?></td>
            </tr>
            <tr class="rc-row">
                <td class="rc-lbl">You Saved</td>
                <td class="rc-val"><span class="val-saved">SAVED <?= $c_sym . ' ' . number_format($saved_amt, 2) ?></span></td>
            </tr>
            <tr class="rc-row">
                <td class="rc-lbl">Support</td>
                <td class="rc-val" style="font-size:0.8rem;"><?= htmlspecialchars($wa_number) ?></td>
            </tr>
        </table>

        <div class="rc-total">
            <span class="rc-total-lbl">Total Paid</span>
            <span class="rc-total-val"><?= $c_sym . ' ' . number_format($price_new, 2) ?></span>
        </div>

        <div class="rc-footer">
            Verified Order • <?= date('d M Y h:i A') ?> • <?= $site_name ?>
        </div>
    </div>
</div>

<div id="beast-toast-container"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
// 1. Timezone Fix
const endTime = new Date("<?= date('c', strtotime($flash['end_time'])) ?>").getTime();
setInterval(() => {
    const now = new Date().getTime();
    const diff = endTime - now;
    if (diff < 0) { document.getElementById('flash-banner').style.display='none'; return; }
    
    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const s = Math.floor((diff % (1000 * 60)) / 1000);
    document.getElementById('flash-countdown').innerHTML = `${h}h ${m}m ${s}s`;
}, 1000);

// --- HEADER HIDE LOGIC ---
function toggleHeader(show) {
    const selectors = ['header', 'nav', '.navbar', '.app-header', '.top-bar', '#header', '.main-header'];
    selectors.forEach(sel => {
        const els = document.querySelectorAll(sel);
        els.forEach(el => el.style.display = show ? '' : 'none');
    });
}

function openFlashModal() { 
    document.getElementById('checkoutModal').classList.add('active'); 
    toggleHeader(false); // Hide Header
    calcTotal(); 
}

function closeModal(id) { 
    document.getElementById(id).classList.remove('active'); 
    toggleHeader(true); // Show Header
}

// 3. Toggle Description
function toggleDesc() {
    var x = document.getElementById("dealDesc");
    if (x.style.display === "block") { x.style.display = "none"; } else { x.style.display = "block"; }
}

// 4. Price Calc
const baseRate = <?= $price_new ?>;
const currSym = "<?= $c_sym ?>";

function calcTotal() {
    const qtyInput = document.getElementById('dealQty');
    if(!qtyInput) return;
    
    const qty = parseInt(qtyInput.value) || 0;
    let total = (qty / 1000) * baseRate;
    if(!isFinite(total) || total < 0) total = 0;
    document.getElementById('dealTotal').innerText = currSym + " " + total.toFixed(2);
}

// --- ✨ NEW CUSTOM ALERT FUNCTION (SELF HEALING) ---
function showBeastAlert(msg, type = 'error') {
    let container = document.getElementById('beast-toast-container');
    
    // Auto-Repair: Create container if missing
    if(!container) {
        container = document.createElement('div');
        container.id = 'beast-toast-container';
        document.body.appendChild(container);
    }
    
    // Create Elements
    const toast = document.createElement('div');
    toast.className = `beast-toast ${type}`;
    
    // Icons based on type
    let iconHTML = '';
    if(type === 'error') iconHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
    else if(type === 'success') iconHTML = '<i class="fa-solid fa-check"></i>';
    else iconHTML = '<i class="fa-solid fa-bell"></i>';

    toast.innerHTML = `
        <div class="toast-icon">${iconHTML}</div>
        <div class="toast-msg">${msg}</div>
    `;

    // Append
    container.appendChild(toast);

    // Remove after 3.5 seconds
    setTimeout(() => {
        toast.style.animation = 'toastSlideOut 0.5s forwards';
        setTimeout(() => toast.remove(), 500);
    }, 3500);
}

// 5. Submit Order (UPDATED & ROBUST)
async function submitFlashOrder(e) {
    e.preventDefault();
    
    const btn = e.target.querySelector('button');
    const oldText = btn.innerHTML;
    
    const qtyInput = document.getElementById('dealQty');
    const qty = parseInt(qtyInput.value);
    const min = parseInt(qtyInput.min);
    const max = parseInt(qtyInput.max);

    // Custom Validation Alerts
    if(qty < min) {
        showBeastAlert(`Oops! Minimum quantity is ${min} 🥺`, 'error');
        return;
    }
    if(qty > max) {
        showBeastAlert(`Whoa! Maximum quantity is ${max} 🤯`, 'error');
        return;
    }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'; 
    btn.disabled = true;

    try {
        const formData = new FormData(e.target);
        
        // Ensure path is relative
        const req = await fetch('flash_deal_action.php', { 
            method: 'POST', 
            body: formData 
        });
        
        // Handle non-JSON responses (like 404 or PHP fatal errors)
        if (!req.ok) throw new Error("Server Error: " + req.status);
        
        const res = await req.json();

        if(res.status === 'success') {
            closeModal('checkoutModal');
            document.getElementById('successModal').classList.add('active');
            toggleHeader(false); 
            
            // Safe Confetti Call
            if(typeof confetti !== 'undefined') {
                var canvas = document.getElementById('confetti-canvas');
                var myConfetti = confetti.create(canvas, { resize: true });
                myConfetti({ particleCount: 150, spread: 80, origin: { y: 0.6 } });
            }
            
            showBeastAlert("Yay! Order Placed Successfully 🎉", "success");

        } else {
            showBeastAlert(res.msg + " 😢", 'error');
            btn.innerHTML = oldText; 
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err); // Debug log
        showBeastAlert("Connection Error! Check internet 📶", 'error');
        btn.innerHTML = oldText; 
        btn.disabled = false;
    }
}

// 6. Share Card
function genShareCard() {
    const node = document.getElementById('receiptNode');
    node.style.left = '0'; node.style.zIndex = '2147483647'; 

    html2canvas(node, { scale:3, useCORS:true, backgroundColor: null }).then(c => {
        const a = document.createElement('a');
        a.download = 'Flash-Receipt.png';
        a.href = c.toDataURL('image/png');
        a.click();
        node.style.left = '-9999px';
    });
}
</script>
<?php endif; ?>