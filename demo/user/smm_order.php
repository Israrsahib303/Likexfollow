<?php
include '_smm_header.php';
$user_id = (int)$_SESSION['user_id'];

// --- 1. CURRENCY & SETTINGS ---
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$curr_rate = 1;
$curr_sym = 'Rs';

if ($curr_code != 'PKR') {
    $curr_rate = getCurrencyRate($curr_code);
    $symbols = ['PKR'=>'Rs','USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','SAR'=>'﷼','AED'=>'د.إ'];
    $curr_symbol = $symbols[$curr_code] ?? $curr_code;
} else {
    $curr_symbol = 'Rs';
}

$site_logo = $GLOBALS['settings']['site_logo'] ?? '';
$site_name = $GLOBALS['settings']['site_name'] ?? 'SubHub';

// --- 2. DYNAMIC FILTER RULES ---
$filter_rules = [
    'high_quality' => 'hq, vip, premium, high quality, real, active',
    'instant'      => 'instant, fast, speed, auto',
    'non_drop'     => 'non-drop, nondrop, stable, guarantee, refill',
    'refill'       => ', r30, r60, r90, r365, lifetime', 
    'no_refill'    => 'no refill, no-refill'
];

try {
    $stmt_rules = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'smm_filter_keywords'");
    $stmt_rules->execute();
    $db_rules_json = $stmt_rules->fetchColumn();
    if ($db_rules_json) {
        $db_rules = json_decode($db_rules_json, true);
        if(is_array($db_rules)) {
            $filter_rules = array_merge($filter_rules, $db_rules);
        }
    }
} catch (Exception $e) {}

// --- DATA FETCHING & LOGIC ---
try {
    // 1. Initialize Arrays
    $grouped_apps = [];
    $db_app_icons = []; 
    $app_master_list = [];

    // 2. Fetch All Active Sub-Categories (Defines the Main Apps)
    try {
        $stmt_sub = $db->query("SELECT * FROM smm_sub_categories WHERE is_active=1 ORDER BY sort_order ASC");
        $sub_rows = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sub_rows as $row) {
            $mainApp = trim($row['main_app']);
            if(empty($mainApp)) continue;

            // Save Uploaded Icon if found (Priority to rows that have icons)
            if (!empty($row['main_cat_icon'])) {
                $db_app_icons[$mainApp] = $row['main_cat_icon'];
            }

            // Initialize App in Master List
            if (!isset($grouped_apps[$mainApp])) {
                $grouped_apps[$mainApp] = [
                    'services' => [],
                    'filters' => []
                ];
            }

            // Add Sub-Category Filter
            $grouped_apps[$mainApp]['filters'][] = [
                'name' => $row['sub_cat_name'],
                'icon' => $row['sub_cat_icon'], 
                'keys' => strtolower($row['keywords'])
            ];
        }
    } catch (PDOException $e) {
        // DB Error Handling
    }

    // 3. Fetch Services
    $stmt = $db->query("SELECT s.*, p.api_url as provider_api FROM smm_services s LEFT JOIN smm_providers p ON s.provider_id = p.id WHERE s.is_active = 1 ORDER BY s.category ASC, s.service_rate ASC");
    $all_services = $stmt->fetchAll();
    
    $services_json = [];
    
    // Default Fallback Icons
    $main_app_icons = [
        'Instagram' => 'Instagram.png',
        'TikTok' => 'TikTok.png',
        'Youtube' => 'Youtube.png',
        'Facebook' => 'Facebook.png',
        'Twitter' => 'Twitter.png',
        'Spotify' => 'Spotify.png',
        'Telegram' => 'Telegram.png',
        'Snapchat' => 'Snapchat.png',
        'Linkedin' => 'default.png',
        'Website' => 'website.png'
    ];

    foreach ($all_services as $s) {
        $full_cat = trim($s['category']);
        $app_name = null; 
        
        // Strict Matching: Service MUST match a Main App defined in DB
        foreach ($grouped_apps as $kApp => $data) {
            if (stripos($full_cat, $kApp) !== false) {
                $app_name = $kApp;
                break;
            }
        }
        
        // If service matches an existing app, add it. Otherwise, IGNORE it (Deletes Others).
        if ($app_name) {
            
            // 🔥 🔥 YAHAN PE CUSTOM DISCOUNT LOGIC APPLY HOGA 🔥 🔥
            // Helper function ko call kiya hai (from helpers.php)
            $custom_rate = get_final_user_price($user_id, $s['provider_id'], $s['category'], $s['id'], $s['service_rate']);
            $s['service_rate'] = $custom_rate;
            
            $grouped_apps[$app_name]['services'][] = $s; 
            
            // Build JSON Data
            $is_comment = (stripos($s['name'], 'Comment') !== false || stripos($s['category'], 'Comment') !== false);
            $has_drip = (isset($s['dripfeed']) && $s['dripfeed'] == 1) ? 1 : 0;
            
            $services_json[$s['id']] = [
                'id'      => $s['id'],
                'rate'    => (float)$s['service_rate'], 
                'min'     => (int)$s['min'],
                'max'     => (int)$s['max'],
                'avg'     => formatSmmAvgTime($s['avg_time']),
                'avg_raw' => (int)$s['avg_time'], 
                'refill' => (bool)$s['has_refill'],
                'cancel' => (bool)$s['has_cancel'],
                'drip'    => $has_drip,
                'type'    => $s['service_type'] ?? 'Default',
                'name'    => sanitize($s['name']),
                'cat'     => sanitize($full_cat),
                'desc'    => nl2br($s['description'] ?? 'No details.'),
                'is_comment' => $is_comment,
                'app'     => $app_name
            ];
        }
    }
    
    // Sort Apps Alphabetically or leave DB Sort? Let's keep DB Sort implicit but clean up keys
    // ksort($grouped_apps); // Optional: Uncomment to sort A-Z

} catch (Exception $e) { $error = $e->getMessage(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>

    <script>
        window.currConfig = { code: "<?=$curr_code?>", rate: <?=$curr_rate?>, sym: "<?=$curr_symbol?>" };
        window.svcData = <?= json_encode($services_json) ?>;
        window.appsData = <?= json_encode($grouped_apps) ?>;
        window.mainIcons = <?= json_encode($main_app_icons) ?>;
        window.filterRules = <?= json_encode($filter_rules) ?>; 
    </script>

    <style>
    /* ---  APPLE DESIGN SYSTEM VARIABLES --- */
    :root {
        --ios-bg: #F2F2F7;         
        --ios-card: #FFFFFF;
        --ios-text: #000000;
        --ios-text-secondary: #8E8E93;
        --ios-blue: #007AFF;
        --ios-purple: #5856D6;
        --ios-green: #34C759;
        --ios-red: #FF3B30;
        
        --glass-bg: rgba(255, 255, 255, 0.75);
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
        --shadow-lg: 0 20px 40px -10px rgba(0,0,0,0.15);
        
        --radius-m: 16px;
        --radius-l: 24px;
        --radius-xl: 32px;
        
        --font-stack: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }
    
    body {
        margin: 0; padding: 0;
        font-family: var(--font-stack);
        background-color: var(--ios-bg);
        color: var(--ios-text);
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
        width: 100%;
        padding-bottom: 80px; /* Space for footer */
    }

    /* SCROLLBAR HIDING */
    ::-webkit-scrollbar { width: 0px; background: transparent; }

    .app-wrapper {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        width: 100%;
    }

    /* --- SEARCH BAR --- */
    .search-box { 
        position: relative; margin-bottom: 24px; width: 100%; 
        transition: transform 0.2s ease;
    }
    .search-box input {
        width: 100%; padding: 12px 20px 12px 42px;
        background: rgba(118, 118, 128, 0.12);
        border: none; border-radius: 12px;
        font-size: 17px; color: var(--ios-text); 
        backdrop-filter: blur(10px);
    }
    .search-box input:focus { background: #FFFFFF; box-shadow: 0 0 0 2px var(--ios-blue); }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--ios-text-secondary); font-size: 16px; }

    /* --- VIEW TRANSITIONS --- */
    .view-section { display: none; opacity: 0; transform: scale(0.98); transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
    .view-section.active { display: block; opacity: 1; transform: scale(1); }

    h3 { 
        font-size: 22px; font-weight: 700; margin: 0 0 16px 0; 
        color: var(--ios-text); display:flex; align-items:center; 
    }

    /* --- APP GRID --- */
    .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 20px 12px; }
    .app-card {
        background: transparent;
        display: flex; flex-direction: column; align-items: center;
        cursor: pointer; transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        padding: 5px;
    }
    .app-card:hover { transform: translateY(-3px); }
    .app-icon-wrap {
        width: 64px; height: 64px; border-radius: 16px;
        background: var(--ios-card);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 8px; overflow: hidden;
    }
    .app-card img { width: 100%; height: 100%; object-fit: cover; }
    .app-name { font-size: 12px; font-weight: 500; color: var(--ios-text); text-align: center; }

    /* --- NAVIGATION HEADER (Aligned) --- */
    .nav-header { 
        display: flex; align-items: center; gap: 12px; margin-bottom: 20px; 
    }
    .back-btn {
        width: 36px; height: 36px; border-radius: 50%; 
        background: rgba(118, 118, 128, 0.12); border: none; color: var(--ios-blue); 
        font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; 
        backdrop-filter: blur(5px);
        flex-shrink: 0;
    }
    .nav-app-icon {
        width: 32px; height: 32px; border-radius: 8px; object-fit: cover;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .nav-title {
        font-size: 20px; font-weight: 700; color: var(--ios-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* --- SUB-CATEGORY --- */
    .big-white-box {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 16px; padding-bottom: 20px;
    }
    .ios-img-btn { 
        width: 100%; border-radius: var(--radius-m); cursor: pointer; 
        box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s; background: #fff;
    }

    /* --- FILTERS (BIGGER & AUTO SCROLL) --- */
    .filter-wrapper {
        display: flex; gap: 12px; overflow-x: auto; padding: 4px 4px 20px 4px;
        -webkit-overflow-scrolling: touch; align-items: center;
        scrollbar-width: none; /* Firefox */
    }
    .filter-wrapper::-webkit-scrollbar { display: none; }
    
    .filter-btn {
        height: 52px; width: auto; cursor: pointer; 
        transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
        opacity: 0.7; filter: grayscale(100%); border-radius: 16px; flex-shrink: 0;
    }
    .filter-btn:hover, .filter-btn.active { 
        opacity: 1; transform: scale(1.1); filter: grayscale(0%); 
        box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
    }

    /* --- SERVICE LIST --- */
    #service-list-container { display: flex; flex-direction: column; gap: 12px; }
    .svc-item {
        background: var(--ios-card); border-radius: var(--radius-m); padding: 16px;
        cursor: pointer; box-shadow: var(--shadow-sm); 
        transition: transform 0.2s;
    }
    .svc-item:active { transform: scale(0.98); }
    .svc-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
    .svc-title { font-size: 15px; font-weight: 600; color: var(--ios-text); line-height: 1.4; flex: 1; }
    .svc-price {
        background: rgba(0, 122, 255, 0.1); color: var(--ios-blue); 
        padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 700; white-space: nowrap;
    }
    .svc-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .tag {
        font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 6px;
        display: inline-flex; align-items: center; gap: 4px; letter-spacing: 0.02em; text-transform: uppercase;
    }
    .tag.green { background: rgba(52, 199, 89, 0.15); color: var(--ios-green); }
    .tag.red { background: rgba(255, 59, 48, 0.15); color: var(--ios-red); }
    .tag.blue { background: rgba(0, 122, 255, 0.15); color: var(--ios-blue); }
    .tag.purple { background: rgba(88, 86, 214, 0.15); color: var(--ios-purple); }

    /* --- 🚀 MODAL REDESIGN --- */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0, 0, 0, 0.3); z-index: 2000;
        justify-content: center; align-items: center;
        opacity: 0; transition: opacity 0.3s; backdrop-filter: blur(4px);
    }
    .modal-overlay.active { display: flex; opacity: 1; }

    .modal-content {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(25px) saturate(180%);
        width: 92%; max-width: 420px;
        border-radius: 30px; 
        box-shadow: var(--shadow-lg);
        border: 1px solid rgba(255, 255, 255, 0.6);
        display: flex; flex-direction: column;
        max-height: 92vh; transform: scale(0.9); opacity: 0;
        transition: transform 0.4s cubic-bezier(0.19, 1, 0.22, 1), opacity 0.3s;
        overflow: hidden;
    }
    .modal-overlay.active .modal-content { transform: scale(1); opacity: 1; }

    .modal-header {
        padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05); background: rgba(255, 255, 255, 0.5);
    }
    .modal-header h3 { margin: 0; font-size: 17px; font-weight: 700; }
    .modal-close {
        background: rgba(118, 118, 128, 0.12); border: none; 
        width: 30px; height: 30px; border-radius: 50%; color: var(--ios-text-secondary); 
        cursor: pointer; display: flex; align-items: center; justify-content: center;
    }

    .modal-body { padding: 20px; overflow-y: auto; -webkit-overflow-scrolling: touch; }

    /* 🔥 THEME CORRECTED ETA BOX (iOS Blue) */
    .eta-box-lavish {
        background: rgba(0, 122, 255, 0.08); /* Soft Blue */
        border-radius: 20px; padding: 15px; margin-bottom: 20px;
        text-align: center; color: var(--ios-text);
        display: none; 
        border: 1px solid rgba(0, 122, 255, 0.2);
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1);
    }
    .eta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: var(--ios-text-secondary); }
    .eta-time { font-size: 20px; font-weight: 800; color: var(--ios-blue); margin-top: 4px; display: block; }
    
    /* Inputs */
    .input-group-ios {
        background: white; border-radius: 18px; padding: 4px 16px; margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .form-row { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--ios-bg); }
    .form-row:last-child { border-bottom: none; }
    .form-label { font-size: 15px; font-weight: 500; width: 80px; flex-shrink: 0; }
    .form-input-ios { flex: 1; border: none; background: transparent; font-size: 15px; text-align: right; font-weight: 600; color: var(--ios-blue); }
    
    .link-wrapper { position: relative; margin-bottom: 16px; }
    .link-input-box {
        width: 100%; padding: 16px 16px 16px 44px; background: #FFFFFF; border: none;
        border-radius: 18px; font-size: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .link-icon { position: absolute; left: 16px; top: 16px; color: var(--ios-text-secondary); }
    .paste-btn-ios {
        position: absolute; right: 8px; top: 8px; background: rgba(0, 122, 255, 0.1); 
        color: var(--ios-blue); font-size: 11px; font-weight: 700; padding: 6px 10px; 
        border-radius: 10px; border: none; cursor: pointer;
    }

    /* 🔥 AUTO SCROLLING FIXED DESCRIPTION BOX */
    .desc-box-scroll {
        background: rgba(255, 255, 255, 0.5); 
        border-radius: 18px; 
        margin-bottom: 16px;
        padding: 15px;
        height: 100px; /* Fixed Height */
        overflow-y: auto; /* Allow manual scroll */
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
        font-size: 13px; color: var(--ios-text-secondary); line-height: 1.5;
    }
    .desc-box-scroll p { margin: 0; }

    /* 🔥 FLOATING FOOTER ISLAND (Submit Area) */
    .modal-footer-island {
        margin: 0 15px 15px 15px; 
        padding: 18px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        display: flex; flex-direction: column; gap: 12px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .total-row { display: flex; justify-content: space-between; align-items: center; }
    .total-label { font-size: 13px; color: var(--ios-text-secondary); font-weight: 600; }
    .total-value { font-size: 22px; color: var(--ios-text); font-weight: 800; letter-spacing: -0.5px; }
    
    .btn-submit-ios {
        width: 100%; padding: 16px;
        background: linear-gradient(135deg, #007AFF 0%, #0055ff 100%);
        color: white; border: none; border-radius: 18px; 
        font-size: 16px; font-weight: 600;
        box-shadow: 0 8px 20px rgba(0, 122, 255, 0.25);
        cursor: pointer; position: relative; overflow: hidden;
    }
    .btn-submit-ios:active { transform: scale(0.97); }

    /* --- ANIMATED FOOTER SECTION --- */
    .lavish-footer {
        text-align: center; padding: 25px; margin-top: 30px;
        background: rgba(255,255,255,0.4); backdrop-filter: blur(10px);
        border-radius: 24px 24px 0 0;
        animation: slideUpFooter 0.8s ease-out;
    }
    @keyframes slideUpFooter { from { transform: translateY(50px); opacity:0; } to { transform: translateY(0); opacity:1; } }
    .footer-brand { font-weight: 800; color: var(--ios-text); font-size: 18px; margin-bottom: 5px; display:block; }
    .footer-text { font-size: 12px; color: var(--ios-text-secondary); }

    /* 🌟 NEW SUCCESS POPUP STYLES (iOS Apple Pay Style) */
    .ios-success-overlay {
        position: fixed; top:0; left:0; width:100%; height:100%;
        background: rgba(242, 242, 247, 0.6); 
        backdrop-filter: blur(20px) saturate(180%);
        z-index: 3000; display:none;
        align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.4s ease;
    }
    .ios-success-overlay.active { display:flex; opacity:1; }

    .ios-success-card {
        background: #FFFFFF;
        width: 320px;
        border-radius: 32px;
        padding: 40px 24px;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        transform: scale(0.8);
        transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }
    .ios-success-overlay.active .ios-success-card { transform: scale(1); }

    /* Animated Checkmark */
    .success-icon-container {
        width: 80px; height: 80px; margin: 0 auto 24px auto;
        border-radius: 50%; background: #007AFF;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 10px 20px rgba(0, 122, 255, 0.3);
    }
    .checkmark-svg { width: 40px; height: 40px; stroke: white; stroke-width: 4; fill: none; stroke-linecap: round; stroke-linejoin: round; }
    .checkmark-path { stroke-dasharray: 100; stroke-dashoffset: 100; }
    .ios-success-overlay.active .checkmark-path { animation: drawCheck 0.8s 0.2s forwards ease-in-out; }

    @keyframes drawCheck { to { stroke-dashoffset: 0; } }

    .success-title { font-size: 24px; font-weight: 800; color: #000; margin-bottom: 8px; letter-spacing: -0.5px; }
    .success-msg { font-size: 15px; color: #8E8E93; line-height: 1.4; margin-bottom: 24px; }
    
    .btn-check-order {
        background: rgba(0, 122, 255, 0.1); color: #007AFF;
        border: none; padding: 12px 24px; border-radius: 20px;
        font-size: 15px; font-weight: 600; cursor: pointer; width: 100%;
        transition: background 0.2s;
    }
    .btn-check-order:hover { background: rgba(0, 122, 255, 0.2); }

    /* Redirect Bar */
    .redirect-bar {
        height: 4px; width: 100%; background: #F2F2F7;
        margin-top: 24px; border-radius: 2px; overflow: hidden;
    }
    .redirect-progress {
        height: 100%; width: 0%; background: #007AFF;
        border-radius: 2px;
    }
    .ios-success-overlay.active .redirect-progress {
        animation: fillProgress 5s linear forwards;
    }
    @keyframes fillProgress { to { width: 100%; } }

    /* Mobile */
    @media (max-width: 480px) {
        .modal-overlay { align-items: flex-end; padding: 0; }
        .modal-content {
            width: 100%; max-width: 100%; border-radius: 32px 32px 0 0;
            max-height: 90vh; transform: translateY(100%);
        }
        .modal-overlay.active .modal-content { transform: translateY(0); }
        .grid-container { grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .app-icon-wrap { width: 56px; height: 56px; }
    }
    </style>
</head>
<body>

<div class="app-wrapper">

    <div class="search-box">
        <i class="fa fa-search search-icon"></i>
        <input type="text" id="global-search" placeholder="Search services...">
    </div>

    <div id="view-home" class="view-section active">
        <h3>Apps</h3>
        <div class="grid-container">
            <?php foreach($grouped_apps as $appName => $data): 
                // FIXED: Check DB for Icon, Else Custom, Else Default
                $finalIconPath = '../assets/img/icons/smm.png';

                // 1. Check Database Uploaded Icon
                if (isset($db_app_icons[$appName])) {
                    $finalIconPath = '../assets/uploads/' . $db_app_icons[$appName];
                }
                // 2. Check System Built-in Icons
                elseif (isset($main_app_icons[$appName])) {
                    $finalIconPath = '../assets/img/icons/' . $main_app_icons[$appName];
                }
            ?>
            <div class="app-card" onclick="goToApp('<?= $appName ?>', '<?= $finalIconPath ?>')">
                <div class="app-icon-wrap">
                    <img src="<?= $finalIconPath ?>" onerror="this.src='../assets/img/icons/smm.png'">
                </div>
                <span class="app-name"><?= $appName ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="view-subcats" class="view-section">
        <div class="nav-header">
            <button class="back-btn" onclick="goBack()"><i class="fa fa-chevron-left"></i></button>
            <img id="subcat-app-icon" src="" class="nav-app-icon" style="display:none;">
            <span id="subcat-title" class="nav-title">App Name</span>
        </div>
        <div class="big-white-box" id="subcat-image-container"></div>
    </div>

    <div id="view-services" class="view-section">
        <div class="nav-header">
            <button class="back-btn" onclick="goBack()"><i class="fa fa-chevron-left"></i></button>
            <img id="svc-app-icon" src="" class="nav-app-icon" style="display:none;">
            <span id="svc-list-title" class="nav-title">Category</span>
        </div>

        <div class="filter-wrapper" id="filter-container">
            <img src="../assets/img/icons/Cheapest.png" class="filter-btn" onclick="applyFilter(this, 'cheapest')">
            <img src="../assets/img/icons/High.png" class="filter-btn" onclick="applyFilter(this, 'high_rate')">
            <img src="../assets/img/icons/Hq.png" class="filter-btn" onclick="applyFilter(this, 'high_quality')">
            <img src="../assets/img/icons/Instant.png" class="filter-btn" onclick="applyFilter(this, 'instant')">
            <img src="../assets/img/icons/Non-drop.png" class="filter-btn" onclick="applyFilter(this, 'non_drop')">
            <img src="../assets/img/icons/Refill.png" class="filter-btn" onclick="applyFilter(this, 'refill')">
            <img src="../assets/img/icons/Norefill.png" class="filter-btn" onclick="applyFilter(this, 'no_refill')">
        </div>
        
        <div id="service-list-container"></div>
        <div id="no-services-msg" style="display:none; text-align:center; padding:40px; color:var(--ios-text-secondary);">
            <i class="fa fa-box-open" style="font-size:32px; margin-bottom:10px; opacity:0.3;"></i>
            <p>No services found here.</p>
        </div>
    </div>

    <div class="lavish-footer">
        <span class="footer-brand">⚡ <?= $site_name ?></span>
        <span class="footer-text">Premium SMM Services • Instant Delivery</span>
    </div>

</div>

<div class="modal-overlay" id="order-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>New Order</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
            
            <div class="eta-box-lavish" id="eta-container">
                <div class="eta-label">Expected Completion</div>
                <div class="eta-time" id="eta-text">Calculating...</div>
            </div>

            <div class="desc-box-scroll" id="m-desc-box">
                <p id="m-desc">Description text...</p>
            </div>
            
            <form action="smm_order_action.php" method="POST" id="order-form">
                <input type="hidden" name="service_id" id="m-id">
                
                <div class="link-wrapper">
                    <i class="fa-solid fa-link link-icon"></i>
                    <input type="text" name="link" id="m-link" class="link-input-box" placeholder="https://link.com" required>
                    <button type="button" class="paste-btn-ios" onclick="pasteLink()">PASTE</button>
                </div>
                
                <div class="input-group-ios">
                    <div id="grp-qty" class="form-row">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="m-qty" class="form-input-ios" placeholder="0" required>
                    </div>
                    <div class="form-row" style="justify-content:space-between; padding:6px 0;">
                        <span style="font-size:11px; color:var(--ios-text-secondary);">Limits</span>
                        <span id="min-max" style="font-size:11px; color:var(--ios-blue); font-weight:600;"></span>
                    </div>
                </div>

                <div id="grp-com" class="input-group-ios" style="display:none; margin-top:10px;">
                    <textarea name="comments" id="m-com" class="form-input-ios" rows="3" style="text-align:left; width:100%; resize:none;" placeholder="Enter comments (1 per line)"></textarea>
                </div>

            </form>
        </div>

        <div class="modal-footer-island">
            <div class="total-row">
                <span class="total-label">Total Amount</span>
                <span id="m-total" class="total-value">0.00</span>
            </div>
            <button type="button" class="btn-submit-ios" onclick="submitOrder()">Place Order</button>
        </div>
    </div>
</div>

<div id="ios-success-modal" class="ios-success-overlay">
    <div class="ios-success-card">
        <div class="success-icon-container">
            <svg class="checkmark-svg" viewBox="0 0 52 52">
                <path class="checkmark-path" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        <h2 class="success-title">Success!</h2>
        <p class="success-msg">Your order has been placed successfully.</p>
        
        <button class="btn-check-order" onclick="window.location.href='https://likexfollow.com/user/smm_history.php'">
            Check Order
        </button>

        <div class="redirect-bar">
            <div class="redirect-progress"></div>
        </div>
        <p style="margin-top:10px; font-size:11px; color:#c7c7cc;">Redirecting in 5s...</p>
    </div>
</div>

<?php include '_smm_footer.php'; ?>

<script>
let historyStack = ['home']; 
let currentApp = null;
let currentAppIcon = null;
let currentCat = null;
let activeServiceList = [];
let currentFilterType = 'all';

// --- NAVIGATION LOGIC ---
function navigateTo(viewId) {
    document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
    document.getElementById(viewId).classList.add('active');
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function goBack() {
    if(historyStack.length <= 1) return;
    historyStack.pop();
    let prevView = historyStack[historyStack.length - 1];
    if(prevView === 'home') navigateTo('view-home');
    if(prevView === 'subcats') navigateTo('view-subcats');
}

function goToApp(appName, iconPath) {
    currentApp = appName;
    currentAppIcon = iconPath;
    historyStack.push('subcats');
    
    // Update Header with Icon
    document.getElementById('subcat-title').innerText = appName;
    let headIcon = document.getElementById('subcat-app-icon');
    if(iconPath && !iconPath.includes('smm.png')) {
        headIcon.src = iconPath;
        headIcon.style.display = 'block';
    } else {
        headIcon.style.display = 'none';
    }
    
    const container = document.getElementById('subcat-image-container');
    container.innerHTML = ''; 
    
    const filters = window.appsData[appName]?.filters || [];
    
    if(filters.length === 0) {
        container.innerHTML = "<div style='color:#999; padding:20px;'>No categories found.</div>";
    }

    filters.forEach((filter, index) => {
        let img = document.createElement('img');
        let imgPath = `../assets/uploads/${filter.icon}`; 
        
        img.onerror = function() {
            this.onerror = null;
            this.src = `../assets/img/icons/${filter.icon}`;
        };
        
        img.src = imgPath;
        img.className = 'ios-img-btn'; 
        img.alt = filter.name;
        img.onclick = () => goToServices(filter.keys, filter.name);
        container.appendChild(img);
    });
    
    navigateTo('view-subcats');
}

function checkKeywords(service, filterType) {
    let rule = window.filterRules[filterType];
    if (!rule) return false;
    let keys = rule.split(',').map(k => k.trim().toLowerCase());
    let sName = service.name.toLowerCase();
    let sCat = service.cat.toLowerCase();
    return keys.some(key => sName.includes(key) || sCat.includes(key));
}

function goToServices(keywords, filterName) {
    currentCat = filterName;
    historyStack.push('services');
    
    // Update Header with Icon
    document.getElementById('svc-list-title').innerText = `${filterName}`;
    let headIcon = document.getElementById('svc-app-icon');
    if(currentAppIcon && !currentAppIcon.includes('smm.png')) {
        headIcon.src = currentAppIcon;
        headIcon.style.display = 'block';
    } else {
        headIcon.style.display = 'none';
    }

    let rawServices = window.appsData[currentApp].services || [];
    let keys = keywords ? keywords.split(',').map(k => k.trim().toLowerCase()) : [];

    activeServiceList = rawServices.map(s => window.svcData[s.id]).filter(s => {
        let serviceName = s.name.toLowerCase();
        let serviceCat = s.cat.toLowerCase();
        let fName = filterName.toLowerCase();
        
        let keywordMatch = false;
        if (keys.length > 0) {
            if (keys.includes('*')) return true;
            for (let key of keys) {
                if (serviceName.includes(key) || serviceCat.includes(key)) {
                    keywordMatch = true;
                    break;
                }
            }
        }
        
        if (keys.length === 0) {
            if(fName === 'followers' && (serviceCat.includes('follow') || serviceCat.includes('sub'))) return true;
            if(serviceCat.includes(fName)) return true;
        }

        return keywordMatch;
    });

    document.querySelectorAll('.filter-btn').forEach(c => c.classList.remove('active'));
    currentFilterType = 'all';
    renderServices();
    navigateTo('view-services');
    startFilterAutoScroll(); // Start scrolling filters
}

function applyFilter(element, type) {
    if (element.classList.contains('active')) {
        element.classList.remove('active');
        currentFilterType = 'all';
    } else {
        document.querySelectorAll('.filter-btn').forEach(c => c.classList.remove('active'));
        element.classList.add('active');
        currentFilterType = type;
    }
    renderServices();
}

function renderServices() {
    const container = document.getElementById('service-list-container');
    const noMsg = document.getElementById('no-services-msg');
    container.innerHTML = '';
    
    let list = [...activeServiceList];

    if(currentFilterType === 'cheapest') list.sort((a, b) => a.rate - b.rate);
    else if (currentFilterType === 'high_rate') list.sort((a, b) => b.rate - a.rate);
    else if (currentFilterType === 'high_quality') list = list.filter(s => checkKeywords(s, 'high_quality'));
    else if (currentFilterType === 'instant') list = list.filter(s => checkKeywords(s, 'instant'));
    else if (currentFilterType === 'non_drop') list = list.filter(s => checkKeywords(s, 'non_drop'));
    else if (currentFilterType === 'refill') list = list.filter(s => s.refill === true || checkKeywords(s, 'refill'));
    else if (currentFilterType === 'no_refill') list = list.filter(s => s.refill === false || checkKeywords(s, 'no_refill'));

    if(list.length === 0) noMsg.style.display = 'block';
    else {
        noMsg.style.display = 'none';
        list.forEach(s => {
            let price = (s.rate * window.currConfig.rate).toFixed(2);
            let tagsHtml = s.refill ? `<span class="tag green">♻️ Refill</span>` : `<span class="tag red">No Refill</span>`;
            
            let item = document.createElement('div');
            item.className = 'svc-item'; 
            item.onclick = () => openModal(s.id);
            item.innerHTML = `
                <div class="svc-header">
                    <span class="svc-title">${s.name}</span>
                    <span class="svc-price">${window.currConfig.sym} ${price}</span>
                </div>
                <div class="svc-tags">
                    ${tagsHtml}
                    <span class="tag blue">⏱ ${s.avg}</span>
                    ${s.cancel ? '<span class="tag purple">Cancel</span>' : ''}
                </div>
            `;
            container.appendChild(item);
        });
    }
}

// --- MODAL LOGIC ---
let currSvc = null;
let descInterval;

function openModal(id) {
    let s = window.svcData[id]; if(!s) return;
    currSvc = s;
    
    document.getElementById('m-id').value = id; 
    document.getElementById('min-max').innerText = `${s.min.toLocaleString()} - ${s.max.toLocaleString()}`;
    document.getElementById('m-qty').setAttribute('min', s.min);
    document.getElementById('m-qty').setAttribute('max', s.max);
    
    // Set Description & Start Auto Scroll
    const dBox = document.getElementById('m-desc-box');
    document.getElementById('m-desc').innerHTML = s.desc; 
    dBox.scrollTop = 0;
    startDescAutoScroll(dBox);

    // ETA CALCULATION
    const etaBox = document.getElementById('eta-container');
    if(s.avg_raw > 0) {
        let now = new Date();
        let completionTime = new Date(now.getTime() + s.avg_raw * 60000); 
        let days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        let dayName = days[completionTime.getDay()];
        let timeStr = completionTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        
        document.getElementById('eta-text').innerText = `Done by ${dayName}, ${timeStr}`;
        etaBox.style.display = 'block';
    } else {
        etaBox.style.display = 'none';
    }

    // Reset Form
    document.getElementById('m-qty').value=''; 
    document.getElementById('m-com').value=''; 
    document.getElementById('m-link').value='';
    document.getElementById('m-total').innerText = window.currConfig.sym + ' 0.00';

    // Handle Type logic
    const qtyGroup = document.getElementById('grp-qty');
    const comGroup = document.getElementById('grp-com');

    if(s.type === 'Package') {
        qtyGroup.style.display='none'; 
        comGroup.style.display='none'; 
        document.getElementById('m-qty').value = '1'; 
        updatePrice(1);
    } else if(s.is_comment || s.type === 'Custom Comments') { 
        qtyGroup.style.display='none'; 
        comGroup.style.display='block'; 
    } else { 
        qtyGroup.style.display='flex'; 
        comGroup.style.display='none'; 
    }
    
    document.getElementById('order-modal').classList.add('active');
}

function closeModal() { 
    document.getElementById('order-modal').classList.remove('active'); 
    clearInterval(descInterval); // Stop scroll
}

// 🔥 AUTO SCROLL LOGIC FOR DESCRIPTION
function startDescAutoScroll(element) {
    clearInterval(descInterval);
    element.onmouseover = () => clearInterval(descInterval);
    element.ontouchstart = () => clearInterval(descInterval);
    element.onmouseout = () => runScroll();
    element.ontouchend = () => runScroll();

    function runScroll() {
        clearInterval(descInterval);
        descInterval = setInterval(() => {
            element.scrollTop += 1;
            // If reached bottom, reset after pause (optional, here just stops or loops)
            if(element.scrollTop + element.clientHeight >= element.scrollHeight) {
                 // element.scrollTop = 0; // Uncomment to loop
            }
        }, 50); // Slow speed
    }
    runScroll();
}

// 🔥 AUTO SCROLL LOGIC FOR FILTERS
let filterInterval;
function startFilterAutoScroll() {
    const el = document.getElementById('filter-container');
    clearInterval(filterInterval);
    
    el.onmouseover = () => clearInterval(filterInterval);
    el.ontouchstart = () => clearInterval(filterInterval);
    
    // Auto scroll slowly
    filterInterval = setInterval(() => {
        el.scrollLeft += 1;
        if(el.scrollLeft + el.clientWidth >= el.scrollWidth) {
             el.scrollLeft = 0; // Infinite loop
        }
    }, 40);
}

function updatePrice(qty) {
    if(!currSvc) return;
    let p = (currSvc.type === 'Package') ? currSvc.rate : (qty/1000)*currSvc.rate;
    if(window.currConfig.code!=='PKR') p*=window.currConfig.rate;
    document.getElementById('m-total').innerText = window.currConfig.sym + ' ' + p.toFixed(2);
}

function submitOrder() {
    document.getElementById('order-form').requestSubmit();
}

async function pasteLink() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('m-link').value = text;
    } catch(err) { 
        Swal.fire({ toast:true, position:'top', icon:'error', title:'Permission required', showConfirmButton:false, timer:1500 });
    }
}

document.getElementById('m-qty').addEventListener('input', function(){ updatePrice(parseInt(this.value)||0) });
document.getElementById('m-com').addEventListener('input', function(){ 
    let c=this.value.split('\n').filter(x=>x.trim()!=='').length; 
    document.getElementById('m-qty').value=c; 
    updatePrice(c); 
});

// 🔥 MODIFIED: NEW iOS SUCCESS HANDLING
document.getElementById('order-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const btn = document.querySelector('.btn-submit-ios');
    
    let oldText = btn.innerText;
    btn.innerText = 'Processing...';
    btn.style.opacity = '0.7';

    fetch('smm_order_action.php', { method:'POST', body: formData })
    .then(r=>r.text())
    .then(res => {
        // 1. Close the order modal
        closeModal();
        
        // 2. Reset Button
        btn.innerText = oldText;
        btn.style.opacity = '1';

        // 3. SHOW THE NEW iOS SUCCESS POPUP
        const successModal = document.getElementById('ios-success-modal');
        successModal.classList.add('active');

        // 4. FIRE CONFETTI (Apple Style Celebration)
        var count = 200;
        var defaults = {
            origin: { y: 0.7 }
        };

        function fire(particleRatio, opts) {
            confetti(Object.assign({}, defaults, opts, {
                particleCount: Math.floor(count * particleRatio)
            }));
        }

        fire(0.25, { spread: 26, startVelocity: 55, colors:['#007AFF','#34C759'] });
        fire(0.2, { spread: 60 });
        fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
        fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
        fire(0.1, { spread: 120, startVelocity: 45 });

        // 5. AUTO REDIRECT AFTER 5 SECONDS
        setTimeout(() => {
            window.location.href = 'https://likexfollow.com/user/smm_history.php';
        }, 5000);
    });
});

document.getElementById('global-search').addEventListener('input', function(e){
    let q=e.target.value.toLowerCase();
    if(q.length > 2) {
        historyStack.push('services');
        navigateTo('view-services');
        document.getElementById('svc-list-title').innerText = "Search Results";
        activeServiceList = Object.values(window.svcData).filter(s => s.name.toLowerCase().includes(q) || s.cat.toLowerCase().includes(q));
        renderServices();
    } else if (q.length === 0 && historyStack.includes('services')) goBack();
});

VanillaTilt.init(document.querySelectorAll(".app-card"), { max: 10, speed: 400, glare: true, "max-glare": 0.2, scale: 1.05 });
</script>
</body>
</html>