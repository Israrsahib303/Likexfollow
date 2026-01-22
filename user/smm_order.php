<?php
include '_smm_header.php';
$user_id = (int)$_SESSION['user_id'];

// --- 1. CURRENCY ---
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
$admin_wa = $GLOBALS['settings']['whatsapp_number'] ?? 'Support';

// --- 2. CARD DESIGN ---
$saved_design = $db->query("SELECT setting_value FROM settings WHERE setting_key='card_design_config'")->fetchColumn();
$cardDesign = $saved_design ? json_decode($saved_design, true) : [
    'order' => ['logo','title','price','stats','details','footer'],
    'colors' => ['bg'=>'#ffffff', 'text'=>'#1e293b', 'accent'=>'#4f46e5']
];

// --- 3. DATA FETCHING ---
try {
    $stmt = $db->query("
        SELECT s.*, p.api_url as provider_api
        FROM smm_services s
        LEFT JOIN smm_providers p ON s.provider_id = p.id
        WHERE s.is_active = 1
        ORDER BY s.category ASC, s.name ASC
    ");
    $all_services = $stmt->fetchAll();
    
    $grouped_apps = [];
    $services_json = [];
    
    $known_apps = [
        'Instagram' => ['filters' => ['Followers','Likes','Views','Comments','Story'], 'icon' => 'Instagram.png'], 
        'TikTok' => ['filters' => ['Followers','Likes','Views','Comments','Saves'], 'icon' => 'TikTok.png'],
        'Youtube' => ['filters' => ['Subscribers','Views','Likes','Watchtime','Comments','Shorts'], 'icon' => 'Youtube.png'],
        'Facebook' => ['filters' => ['Followers','Page Likes','Views','Comments','Reels'], 'icon' => 'Facebook.png'],
        'Twitter' => ['filters' => ['Followers','Retweets','Likes','Views'], 'icon' => 'Twitter.png'],
        'X' => ['filters' => ['Followers','Retweets','Likes','Views'], 'icon' => 'Twitter.png'],
        'Spotify' => ['filters' => ['Plays','Followers','Saves'], 'icon' => 'Spotify.png'],
        'Telegram' => ['filters' => ['Members','Views','Reactions'], 'icon' => 'Telegram.png'],
        'Snapchat' => ['filters' => ['Followers','Story Views','Score'], 'icon' => 'Snapchat.png'],
        'Whatsapp' => ['filters' => ['Channel Members','Status Views'], 'icon' => 'Whatsapp.png'], 
        'Linkedin' => ['filters' => ['Followers','Connections','Likes'], 'icon' => 'default.png'],
        'Google' => ['filters' => ['Reviews','Views','Live'], 'icon' => 'Google.png'],
        'Website' => ['filters' => ['Traffic','Screens'], 'icon' => 'website.png']
    ];

    foreach ($all_services as $s) {
        $full_cat = trim($s['category']);
        $app_name = 'Others'; 
        $app_filters = ['Followers','Likes','Views']; 
        $found = false;

        foreach ($known_apps as $kApp => $data) {
            if (stripos($full_cat, $kApp) !== false) {
                $app_name = $kApp;
                if(trim($kApp) == 'X ') $app_name = 'Twitter';
                $app_filters = $data['filters'];
                $found = true;
                $break;
            }
        }
        
        if (!$found) {
            $parts = explode(' - ', $full_cat);
            $app_name = (count($parts) > 1) ? trim($parts[0]) : $full_cat;
        }
        
        $grouped_apps[$app_name]['services'][$full_cat][] = $s;
        $grouped_apps[$app_name]['filters'] = $app_filters; 

        $is_comment = (stripos($s['name'], 'Comment') !== false || stripos($s['category'], 'Comment') !== false);
        $service_type = $s['service_type'] ?? 'Default';
        $is_manual = ((int)$s['provider_id'] === 0 || $s['provider_api'] === 'manual_internal');

        $icon_char = '🟢'; 
        if (stripos($s['name'], 'Best') !== false || stripos($s['name'], 'VIP') !== false) $icon_char = '🔥';
        elseif (stripos($s['avg_time'], 'Instant') !== false) $icon_char = '⚡';

        $has_drip = (isset($s['dripfeed']) && $s['dripfeed'] == 1) ? 1 : 0;

        $services_json[$s['id']] = [
            'rate' => (float)$s['service_rate'],
            'min' => (int)$s['min'],
            'max' => (int)$s['max'],
            'avg' => formatSmmAvgTime($s['avg_time']),
            'refill' => (bool)$s['has_refill'],
            'cancel' => (bool)$s['has_cancel'],
            'drip'   => $has_drip, 
            'type'   => $service_type, 
            'is_manual' => $is_manual,
            'name' => sanitize($s['name']),
            'category' => sanitize($full_cat), 
            'desc' => nl2br($s['description'] ?? 'No details.'),
            'is_comment' => $is_comment,
            'icon' => $icon_char,
            'app' => strtolower($app_name),
            'app_key' => md5($app_name) // Used for linking
        ];
    }
    ksort($grouped_apps);

} catch (Exception $e) { $error = $e->getMessage(); }

$logo_url = !empty($site_logo) ? "../assets/img/$site_logo" : "";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    window.currConfig = { code: "<?=$curr_code?>", rate: <?=$curr_rate?>, sym: "<?=$curr_symbol?>" };
    window.svcData = <?= json_encode($services_json) ?>;
    window.siteData = { logo: "<?=$logo_url?>", name: "<?= htmlspecialchars($site_name) ?>", wa: "<?= htmlspecialchars($admin_wa) ?>" };
    
    window.appFilters = {};
    <?php foreach($grouped_apps as $name => $data): ?>
    window.appFilters["<?= md5($name) ?>"] = <?= json_encode($data['filters']) ?>;
    <?php endforeach; ?>
</script>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* --- MAIN THEME --- */
:root { --primary: #4f46e5; --bg-body: #f8fafc; --card-bg: #ffffff; --text-main: #0f172a; --text-sub: #64748b; --border: #e2e8f0; --radius: 16px; }
body { background-color: var(--bg-body); font-family: 'Outfit', sans-serif; color: var(--text-main); font-size: 15px; overflow-x: hidden; }

/* GRID */
.platform-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; margin-bottom: 30px; animation: fadeIn 0.5s; }
.platform-card {
    background: var(--card-bg); padding: 20px; border-radius: var(--radius); border: 1px solid var(--border);
    text-align: center; cursor: pointer; transition: 0.3s; position: relative; overflow: hidden;
}
.platform-card:hover { border-color: var(--primary); transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.15); }
.platform-icon { width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px; transition: 0.3s; }
.platform-card:hover .platform-icon { transform: scale(1.1); }
.platform-title { font-weight: 700; font-size: 0.9rem; display: block; }

/* APP VIEW */
.app-container { display: none; animation: slideIn 0.3s ease-out; }
.top-nav { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
.back-btn {
    background: #fff; border: 1px solid var(--border); padding: 10px 15px; border-radius: 12px;
    cursor: pointer; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 5px;
    transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.back-btn:hover { border-color: var(--primary); color: var(--primary); transform: translateX(-3px); }

/* FILTERS */
.filter-wrap { overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; scrollbar-width: none; }
.filter-scroll { display: flex; gap: 8px; }
.filter-chip { 
    background: #fff; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 50px; 
    white-space: nowrap; cursor: pointer; font-weight: 700; font-size: 0.85rem; color: var(--text-sub); 
    transition: 0.2s; display: flex; align-items: center; gap: 6px;
}
.filter-chip:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }
.filter-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

/* SERVICES */
.cat-group { margin-bottom: 15px; background: #fff; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: 0.3s; }
.cat-header {
    padding: 16px 20px; cursor: pointer; font-weight: 800; background: #f8fafc; color: #334155;
    display: flex; justify-content: space-between; align-items: center; transition: 0.2s;
}
.cat-header:hover { background: #f1f5f9; color: var(--primary); }
.svc-list { display: none; border-top: 1px solid var(--border); }

.service-item {
    padding: 18px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: 0.2s; position: relative;
}
/* 🔥 HIGHLIGHT ANIMATION */
.service-item.blinking-highlight {
    animation: flash 1s infinite alternate;
    border-left: 5px solid var(--primary);
}
@keyframes flash { from { background: #fff; } to { background: #e0e7ff; } }

.service-item:hover { background: #fcfaff; padding-left: 25px; border-left: 4px solid var(--primary); }
.service-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.service-name { font-weight: 600; font-size: 0.95rem; color: var(--text-main); line-height: 1.4; flex: 1; padding-right: 10px; }
.service-price { background: #e0e7ff; color: var(--primary); padding: 4px 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 800; white-space: nowrap; }

.service-meta { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top:8px; }
.tag { padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
.tag-time { background: #f1f5f9; color: var(--text-sub); }
.tag-refill { background: #dcfce7; color: #166534; }
.tag-cancel { background: #fee2e2; color: #991b1b; }
.tag-manual { background: #f3f4f6; color: #475569; border: 1px solid #cbd5e1; }

.service-actions { margin-left: auto; display: flex; gap: 8px; }
.btn-receipt {
    background: #fff; border: 1px solid #e2e8f0; padding: 5px 10px; border-radius: 8px;
    font-size: 0.75rem; font-weight: 700; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 4px;
}
.btn-receipt:hover { border-color: var(--primary); color: var(--primary); }

.btn-card-maker {
    background: linear-gradient(135deg, #fbbf24, #d97706); color: #fff; 
    padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-decoration: none;
    box-shadow: 0 4px 10px rgba(251, 191, 36, 0.3); transition: 0.2s;
}
.btn-card-maker:hover { transform: scale(1.05); }

/* --- MODAL --- */
.modal-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 99999;
    justify-content: center; align-items: center; padding: 20px;
}
.modal-overlay.active { display: flex; animation: fadeIn 0.2s ease-out; }
.modal-content {
    background: #fff; width: 100%; max-width: 500px; border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column;
    max-height: 90vh; animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid #fff;
}
.modal-header { padding: 20px 25px; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal-close { background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; color: #64748b; transition: 0.2s; }
.modal-close:hover { background: #fee2e2; color: #ef4444; transform: rotate(90deg); }
.modal-body { padding: 25px; overflow-y: auto; }

.stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px; }
.stat-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 12px; text-align: center; }
.stat-box small { display: block; font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
.stat-box b { font-size: 0.9rem; color: #334155; }
.desc-box { background: #fff; border: 1px dashed #cbd5e1; border-radius: 12px; font-size: 0.85rem; color: #64748b; margin-bottom: 20px; padding: 15px; max-height: 100px; overflow-y: auto; line-height: 1.6; }

.form-group { margin-bottom: 18px; }
.form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 8px; color: #334155; }
.input-wrap { position: relative; }
.form-input { width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 14px; font-size: 1rem; outline: none; transition: 0.2s; background: #fff; color: #0f172a; }
.form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
.paste-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: #eff6ff; color: var(--primary); border: none; padding: 6px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
.paste-btn:hover { background: var(--primary); color: #fff; }

.btn-submit { width: 100%; padding: 16px; background: var(--primary); color: #fff; font-weight: 800; font-size: 1rem; border: none; border-radius: 14px; cursor: pointer; margin-top: 15px; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3); transition: 0.3s; }
.btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4); }

/* === 🤖 AI CHATBOT (Redesigned: Centered & Animated) === */

/* Backdrop overlay */
.ai-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9990;
    opacity: 0; transition: opacity 0.3s ease;
}
.ai-overlay.active { display: block; opacity: 1; }

/* The Floating Action Button */
.ai-fab {
    position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px;
    background: linear-gradient(135deg, #a855f7, #6366f1); border-radius: 50%;
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4); cursor: pointer;
    display: flex; align-items: center; justify-content: center; z-index: 9999;
    transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.ai-fab:hover { transform: scale(1.1); box-shadow: 0 15px 35px rgba(99, 102, 241, 0.5); }
.ai-fab i { font-size: 28px; color: white; animation: pulse 2s infinite; }

/* The Chat Box (Centered) */
.ai-box {
    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
    width: 90%; max-width: 500px; height: 75vh; max-height: 700px;
    background: #fff; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    display: flex; flex-direction: column; overflow: hidden; z-index: 9999;
    opacity: 0; pointer-events: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255,255,255,0.5);
}

/* Active State for Box */
.ai-box.active {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1; pointer-events: all;
}

/* Header - Slimmer & Smaller Logo */
.ai-header {
    background: linear-gradient(135deg, #a855f7, #6366f1); 
    padding: 10px 18px; /* Slimmer padding */
    height: 55px; /* Fixed small height */
    color: white; display: flex; align-items: center; gap: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.ai-avatar {
    width: 28px; height: 28px; /* Small icon container */
    background: white; border-radius: 50%; padding: 2px;
    display: flex; justify-content: center; align-items: center;
}
.ai-avatar img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }

.ai-header h4 { margin: 0; font-size: 0.95rem; font-weight: 700; }
.ai-header span { font-size: 0.7rem; opacity: 0.9; }

/* Close & Tools */
.ai-tools { margin-left: auto; display: flex; gap: 15px; align-items: center; }
.ai-tools i, .ai-tools span { cursor: pointer; font-size: 1rem; opacity: 0.8; transition: 0.2s; }
.ai-tools i:hover, .ai-tools span:hover { opacity: 1; transform: scale(1.1); }

/* Body & Messages */
.ai-body { 
    flex: 1; padding: 20px; overflow-y: auto; background: #f3f4f6; 
    display: flex; flex-direction: column; gap: 12px; scroll-behavior: smooth; 
}
.ai-msg { 
    padding: 12px 16px; border-radius: 14px; font-size: 0.95rem; 
    max-width: 80%; line-height: 1.5; word-wrap: break-word; 
    position: relative; animation: msgPopIn 0.3s ease forwards;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
}

/* Bot Message */
.ai-bot { 
    background: #fff; border-bottom-left-radius: 2px; align-self: flex-start; 
    color: #1f2937; border: 1px solid #e5e7eb;
}
/* User Message */
.ai-user { 
    background: linear-gradient(135deg, #6366f1, #4f46e5); 
    color: white; border-bottom-right-radius: 2px; align-self: flex-end; 
    box-shadow: 0 5px 15px rgba(79, 70, 229, 0.2);
}

/* Animations */
@keyframes msgPopIn {
    0% { opacity: 0; transform: translateY(10px) scale(0.95); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

/* Typing Animation (Three dots) */
.typing-dots { display: inline-flex; gap: 4px; padding: 5px 8px; align-items: center; }
.typing-dots span {
    width: 6px; height: 6px; background: #9ca3af; border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out both;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

/* Footer */
.ai-footer { 
    padding: 15px; background: white; border-top: 1px solid #e5e7eb; 
    display: flex; gap: 12px; align-items: center; 
}
.ai-input { 
    flex: 1; padding: 14px 20px; border: 1px solid #e5e7eb; border-radius: 30px; 
    outline: none; background: #f9fafb; font-size: 0.95rem; transition: 0.2s;
}
.ai-input:focus { background: #fff; border-color: #a855f7; box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1); }
.ai-send {
    width: 45px; height: 45px; border-radius: 50%; border: none;
    background: linear-gradient(135deg, #a855f7, #6366f1); color: white;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
    transition: 0.2s;
}
.ai-send:hover { transform: scale(1.05) rotate(-10deg); }

/* View Button in Chat */
.btn-ai-view {
    display: inline-block; margin-top: 10px; padding: 8px 14px; background: #10b981; color: white;
    font-size: 0.8rem; font-weight: 700; border-radius: 10px; text-decoration: none; cursor: pointer;
    box-shadow: 0 3px 6px rgba(16, 185, 129, 0.2); transition: 0.2s; border:none;
}
.btn-ai-view:hover { transform: translateY(-2px); box-shadow: 0 5px 10px rgba(16, 185, 129, 0.3); }

@keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
@keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
@keyframes slideIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
@keyframes pulse { 0% { transform:scale(1); } 50% { transform:scale(1.1); } 100% { transform:scale(1); } }
</style>

<div class="container">
    <div class="search-wrap" style="position:relative; margin-bottom:30px;">
        <input type="text" id="search" class="form-input" style="padding-left:45px;" placeholder="Search services (e.g. Instagram, Likes)...">
        <span style="position:absolute; left:15px; top:50%; transform:translateY(-50%); font-size:1.2rem;">🔍</span>
    </div>

    <div id="platform-grid" class="platform-grid">
        <?php if(empty($grouped_apps)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:#999;">No services found.</div>
        <?php else: ?>
            <?php foreach($grouped_apps as $appName => $data): 
                $iconPath = "../assets/img/icons/" . $data['services'][array_key_first($data['services'])][0]['app'] . ".png";
                if(!file_exists(__DIR__ . '/../assets/img/icons/' . $appName . '.png')) {
                    foreach($known_apps as $k => $v) if(strpos($appName, $k)!==false) $iconPath = "../assets/img/icons/" . $v['icon'];
                } else {
                    $iconPath = "../assets/img/icons/" . $appName . ".png";
                }
            ?>
            <div class="platform-card" onclick="openApp('<?= md5($appName) ?>', '<?= sanitize($appName) ?>')">
                <img src="<?= $iconPath ?>" class="platform-icon" onerror="this.src='../assets/img/icons/smm.png'">
                <span class="platform-title"><?= sanitize($appName) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="apps-container">
        <?php foreach($grouped_apps as $appName => $data): ?>
        <div id="app-<?= md5($appName) ?>" class="app-container" data-app-name="<?= strtolower($appName) ?>">
            
            <div class="top-nav">
                <button class="back-btn" onclick="closeApp()"><i class="fa fa-chevron-left"></i> Back</button>
                <h2 style="margin:0; font-size:1.5rem; display:flex; align-items:center; gap:10px;">
                    <img src="../assets/img/icons/<?= $appName ?>.png" style="width:30px; height:30px; object-fit:contain;" onerror="this.style.display='none'">
                    <?= sanitize($appName) ?> Services
                </h2>
            </div>

            <div class="filter-wrap">
                <div class="filter-scroll" id="filters-<?= md5($appName) ?>"></div>
            </div>
            
            <?php foreach($data['services'] as $catName => $services): ?>
            <div class="cat-group">
                <div class="cat-header" onclick="toggleCat(this)">
                    <span><?= sanitize($catName) ?></span> <i class="fa fa-chevron-down"></i>
                </div>
                <div class="svc-list">
                    <?php foreach($services as $s): 
                        $rate = (float)$s['service_rate'];
                        if($curr_code != 'PKR') $rate *= $curr_rate;
                        $s_icon = $services_json[$s['id']]['icon'];
                        $is_manual = ((int)$s['provider_id'] === 0 || $s['provider_api'] === 'manual_internal');
                    ?>
                    <div class="service-item" id="svc-<?= $s['id'] ?>" data-name="<?= strtolower(sanitize($s['name'])) ?>" onclick="openModal(<?= $s['id'] ?>)">
                        <div class="service-top">
                            <span class="service-name">
                                <span class="svc-icon"><?= $s_icon ?></span> <?= sanitize($s['name']) ?>
                            </span>
                            <span class="service-price"><?= $curr_symbol . ' ' . number_format($rate, 2) ?></span>
                        </div>
                        
                        <div class="service-meta">
                            <span class="tag tag-time">⏱ <?= formatSmmAvgTime($s['avg_time']) ?></span>
                            <?php if($s['has_refill']): ?><span class="tag tag-refill">♻️ Refill</span><?php endif; ?>
                            <?php if($s['has_cancel']): ?><span class="tag tag-cancel">🚫 Cancel</span><?php endif; ?>
                            <?php if($is_manual): ?><span class="tag tag-manual">🛠️ Manual</span><?php endif; ?>
                            
                            <div class="service-actions">
                                <button class="btn-receipt" onclick="event.stopPropagation(); genReceipt(<?= $s['id'] ?>)">📄 Info</button>
                                <a href="service_card.php?id=<?php echo $s['id']; ?>" target="_blank" class="btn-card-maker" onclick="event.stopPropagation();">🎨 Card</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal-overlay" id="order-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;">New Order</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <div id="m-stats" class="stats-grid"></div>
            <div id="m-desc" class="desc-box"></div>
            <form action="smm_order_action.php" method="POST" id="order-form">
                <input type="hidden" name="service_id" id="m-id">
                
                <div class="form-group">
                    <label class="form-label">Link</label>
                    <div class="input-wrap">
                        <input type="text" name="link" id="m-link" class="form-input" style="padding-right:70px;" placeholder="https://..." required>
                        <button type="button" class="paste-btn" onclick="pasteLink()">PASTE</button>
                    </div>
                    <div id="link-err" class="link-err-box">
                        <img id="err-icon" class="link-err-icon" src="">
                        <span class="link-err-text" id="err-text"></span>
                    </div>
                </div>
                
                <div id="grp-qty" class="form-group">
                    <label class="form-label">Quantity <small id="min-max" style="float:right;color:var(--primary)"></small></label>
                    <input type="number" name="quantity" id="m-qty" class="form-input" placeholder="1000" required>
                </div>

                <div id="grp-drip" style="display:none;">
                    <label class="drip-toggle">
                        <input type="checkbox" id="drip-check" onchange="toggleDrip()"> 
                        <span><b>Auto-Likes / Drip-feed</b> (Schedule)</span>
                    </label>
                    <div class="drip-area" id="drip-fields">
                        <input type="hidden" name="dripfeed" id="drip-val" value="0">
                        <div class="drip-grid">
                            <div>
                                <label class="form-label" style="font-size:0.8rem;">Runs</label>
                                <input type="number" name="runs" id="m-runs" class="form-input" min="1">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:0.8rem;">Gap</label>
                                <div style="display:flex; gap:5px;">
                                    <input type="number" id="m-interval-raw" class="form-input" style="flex:1">
                                    <select id="m-interval-unit" class="form-input" style="width:80px; padding:5px;">
                                        <option value="1">Min</option>
                                        <option value="60">Hour</option>
                                        <option value="1440">Day</option>
                                    </select>
                                </div>
                                <input type="hidden" name="interval" id="m-interval">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="grp-com" class="form-group" style="display:none">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label class="form-label" style="margin:0;">Comments (1 per line)</label>
                        <button type="button" class="btn-receipt" onclick="generateAiComments()" id="ai-btn-txt" style="border:1px solid var(--primary); color:var(--primary); padding:4px 10px;">
                            ✨ Generate with AI
                        </button>
                    </div>
                    <textarea name="comments" id="m-com" class="form-input" rows="4" placeholder="Nice post!"></textarea>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                    <span style="color:#64748b;font-weight:600;">Total Charge</span>
                    <span id="m-total" style="color:var(--primary);font-size:1.4rem;font-weight:800;">0.00</span>
                </div>
                <div id="m-hint" class="dynamic-hint"></div>

                <button type="submit" class="btn-submit">CONFIRM ORDER</button>
            </form>
        </div>
    </div>
</div>

<div id="receipt-node"></div>

<div class="ai-overlay" id="ai-overlay" onclick="toggleAiChat()"></div>

<div class="ai-fab" onclick="toggleAiChat()">
    <i class="fa-solid fa-robot"></i>
</div>

<div class="ai-box" id="ai-chat-box">
    <div class="ai-header">
        <div class="ai-avatar"><img src="../assets/img/icons/ai.png" onerror="this.src='../assets/img/logo.png'"></div>
        <div style="flex:1">
            <h4 style="margin:0;">Israr Liaqat Ai</h4>
            <span style="font-size:0.7rem; opacity:0.8; display:block;">● Online & Ready</span>
        </div>
        <div class="ai-tools">
            <i class="fa fa-eraser" onclick="clearChat()" title="Clear Chat"></i>
            <span onclick="toggleAiChat()" style="font-size:1.2rem;">✕</span>
        </div>
    </div>
    <div class="ai-body" id="ai-messages">
        </div>
    <div class="ai-footer">
        <input type="text" id="ai-input" class="ai-input" placeholder="Ask anything..." onkeypress="handleAiEnter(event)">
        <button class="ai-send" onclick="sendAiMessage()"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
</div>

<script>
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

// --- 1. DYNAMIC FILTERS ---
function openApp(appId, appName) {
    $('#platform-grid').style.display='none'; 
    $$('.app-container').forEach(x=>x.style.display='none'); 
    $('#app-'+appId).style.display='block';
    
    const filterBox = $('#filters-'+appId);
    filterBox.innerHTML = ''; 
    let tags = ['All', ...(window.appFilters[appId] || ['Followers', 'Likes', 'Views'])];

    tags.forEach(tag => {
        let icon = getFilterIcon(tag);
        let chip = document.createElement('div');
        chip.className = `filter-chip ${tag === 'All' ? 'active' : ''}`;
        chip.innerHTML = `${icon} ${tag}`;
        chip.onclick = function() { 
            filterBox.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            filterServices(tag, $('#app-'+appId)); 
        };
        filterBox.appendChild(chip);
    });
}

function getFilterIcon(tag) {
    tag = tag.toLowerCase();
    if(tag.includes('like')) return '<i class="fa fa-heart" style="color:#ef4444"></i>';
    if(tag.includes('view')) return '<i class="fa fa-eye" style="color:#3b82f6"></i>';
    if(tag.includes('follow') || tag.includes('sub')) return '<i class="fa fa-user-plus" style="color:#10b981"></i>';
    if(tag.includes('comment')) return '<i class="fa fa-comment" style="color:#f59e0b"></i>';
    return '🔹';
}

function closeApp() { 
    $$('.app-container').forEach(x=>x.style.display='none'); 
    $('#platform-grid').style.display='grid'; 
}

function toggleCat(el) { 
    const l=el.nextElementSibling; 
    l.style.display = (l.style.display === 'block') ? 'none' : 'block'; 
}

function filterServices(tag, container) {
    let key = tag.toLowerCase();
    container.querySelectorAll('.service-item').forEach(item => {
        let name = item.dataset.name;
        if(key === 'all') { item.style.display = 'block'; } 
        else {
            let match = false;
            if(key.includes('sub') && name.includes('sub')) match = true;
            else if(name.includes(key)) match = true;
            item.style.display = match ? 'block' : 'none';
        }
    });
    container.querySelectorAll('.cat-group').forEach(group => {
        let visible = group.querySelectorAll('.service-item[style="display: block;"]').length;
        if(visible > 0) {
            group.style.display = 'block';
            group.querySelector('.svc-list').style.display = (key === 'all') ? 'none' : 'block';
        } else {
            group.style.display = 'none';
        }
    });
}

// --- 2. MODAL & LOGIC ---
let currSvc = null;

function openModal(id) {
    let s = window.svcData[id]; if(!s) return;
    currSvc = s;
    $('#m-id').value=id; 
    $('#min-max').innerText = `Min: ${s.min} | Max: ${s.max}`;
    $('#m-qty').setAttribute('min', s.min);
    $('#m-qty').setAttribute('max', s.max);
    $('#m-desc').innerText = s.desc.replace(/<[^>]*>?/gm, ''); 
    
    let rC=s.refill?'#10b981':'#ef4444', cC=s.cancel?'#10b981':'#ef4444';
    $('#m-stats').innerHTML = `
        <div class="stat-box"><small>Avg Time</small><b>${s.avg}</b></div>
        <div class="stat-box" style="border-bottom:3px solid ${rC}"><small>Refill</small><b style="color:${rC}">${s.refill?'Yes':'No'}</b></div>
        <div class="stat-box" style="border-bottom:3px solid ${cC}"><small>Cancel</small><b style="color:${cC}">${s.cancel?'Yes':'No'}</b></div>
    `;

    $('#m-qty').value=''; $('#m-com').value=''; $('#m-link').value='';
    $('#m-total').innerText = window.currConfig.sym + ' 0.00';
    $('#link-err').style.display='none';
    $('#drip-check').checked = false; $('#drip-fields').style.display = 'none';

    if(s.type === 'Package') {
        $('#grp-qty').style.display='none'; $('#grp-com').style.display='none'; $('#grp-drip').style.display = 'none'; 
        $('#m-qty').value = '1'; $('#m-qty').readOnly = true; updatePrice(1);
    } else if(s.is_comment || s.type === 'Custom Comments') { 
        $('#grp-qty').style.display='none'; $('#grp-com').style.display='block'; $('#m-qty').readOnly=true; $('#grp-drip').style.display = 'none'; 
    } else { 
        $('#grp-qty').style.display='block'; $('#grp-com').style.display='none'; $('#m-qty').readOnly=false; 
        $('#grp-drip').style.display = s.drip ? 'block' : 'none';
    }

    $('.modal-overlay').classList.add('active');
    if(s.type !== 'Package') updatePrice(0);
}
function closeModal() { $('.modal-overlay').classList.remove('active'); }

function toggleDrip() {
    const on = $('#drip-check').checked;
    $('#drip-fields').style.display = on ? 'block' : 'none';
    $('#drip-val').value = on ? '1' : '0';
    updatePrice(parseInt($('#m-qty').value)||0);
}

function updatePrice(qty) {
    if(!currSvc) return;
    let multiplier = ($('#drip-check').checked) ? (parseInt($('#m-runs').value)||0) : 1;
    if(multiplier < 1) multiplier = 1;
    let totalQty = qty * multiplier;
    
    let p = (currSvc.type === 'Package') ? currSvc.rate : (totalQty/1000)*currSvc.rate;
    if(window.currConfig.code!=='PKR') p*=window.currConfig.rate;
    
    $('#m-total').innerText = window.currConfig.sym + ' ' + p.toFixed(2);
}

// Events
$('#m-qty').addEventListener('input', function(){ updatePrice(parseInt(this.value)||0) });
$('#m-runs').addEventListener('input', function(){ updatePrice(parseInt($('#m-qty').value)||0) });
$('#m-com').addEventListener('input', function(){ let c=this.value.split('\n').filter(x=>x.trim()!=='').length; $('#m-qty').value=c; updatePrice(c); });
$('#order-form').addEventListener('submit', function(e) {
    if(!currSvc) return;
    let qty = parseInt($('#m-qty').value) || 0;
    if(currSvc.type !== 'Package') {
        if(qty < currSvc.min) { e.preventDefault(); alert(`Min qty: ${currSvc.min}`); return; }
        if(qty > currSvc.max) { e.preventDefault(); alert(`Max qty: ${currSvc.max}`); return; }
    }
});

// Search
$('#search').addEventListener('input', function(e){
    let q=e.target.value.toLowerCase();
    $$('.service-item').forEach(i=>{ 
        if(i.dataset.name.includes(q)) i.style.display='block'; else i.style.display='none';
    });
    if(q.length>0){ $('#platform-grid').style.display='none'; $$('.app-container,.svc-list').forEach(x=>x.style.display='block'); }
    else { closeApp(); }
});

// AI Logic (Comments & Chat)
async function generateAiComments() {
    if(!currSvc) return;
    let btnText = document.getElementById('ai-btn-txt');
    let originalText = btnText.innerText;
    let link = document.getElementById('m-link').value;
    if(!link) { alert("Please paste the Post Link first!"); return; }

    const { value: mood } = await Swal.fire({
        title: 'Comment Mood?',
        input: 'select',
        inputOptions: { 'Positive': '❤️ Love/Positive', 'Funny': '😂 Funny', 'Savage': '🔥 Savage' },
        showCancelButton: true
    });

    if (mood) {
        btnText.innerText = "⏳ Writing...";
        fetch('ai_helper.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=generate_comments&service_name=${encodeURIComponent(currSvc.name)}&link=${encodeURIComponent(link)}&mood=${encodeURIComponent(mood)}`
        })
        .then(r => r.json())
        .then(d => {
            if(d.status === 'success') {
                document.getElementById('m-com').value = d.data;
                $('#m-com').dispatchEvent(new Event('input'));
            } else { alert(d.message); }
            btnText.innerText = originalText;
        })
        .catch(e => { btnText.innerText = originalText; });
    }
}

// Chat Functions
function toggleAiChat() {
    const box = document.getElementById('ai-chat-box');
    const overlay = document.getElementById('ai-overlay');
    
    // Toggle Active Class for Animation
    if (box.classList.contains('active')) {
        box.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        box.classList.add('active');
        overlay.classList.add('active');
        loadChatHistory();
        setTimeout(() => document.getElementById('ai-input').focus(), 300);
    }
}

function handleAiEnter(e) { if(e.key === 'Enter') sendAiMessage(); }

function appendMessage(sender, text) {
    let chatBody = document.getElementById('ai-messages');
    let div = document.createElement('div');
    div.className = `ai-msg ai-${sender}`;
    
    // Parse [VIEW:ID] to Button
    if(sender === 'bot') {
        text = text.replace(/\[VIEW:(\d+)\]/g, '<button class="btn-ai-view" onclick="locateService($1)">👁 View Service</button>');
    }
    div.innerHTML = text;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
    
    // Save to LocalStorage
    let history = JSON.parse(localStorage.getItem('ai_chat_history') || '[]');
    history.push({sender, text});
    localStorage.setItem('ai_chat_history', JSON.stringify(history));
}

function loadChatHistory() {
    let chatBody = document.getElementById('ai-messages');
    if(chatBody.innerHTML.trim() !== '') return; // Already loaded
    
    let history = JSON.parse(localStorage.getItem('ai_chat_history') || '[]');
    if(history.length === 0) {
        appendMessage('bot', "Assalam-o-Alaikum! 👋<br>Main Israr Liaqat Ai hoon.<br>Bataiye kya seva karoon?");
    } else {
        history.forEach(msg => {
            let div = document.createElement('div');
            div.className = `ai-msg ai-${msg.sender}`;
            div.innerHTML = msg.text.replace(/\[VIEW:(\d+)\]/g, '<button class="btn-ai-view" onclick="locateService($1)">👁 View Service</button>');
            chatBody.appendChild(div);
        });
        chatBody.scrollTop = chatBody.scrollHeight;
    }
}

function clearChat() {
    localStorage.removeItem('ai_chat_history');
    document.getElementById('ai-messages').innerHTML = '';
    appendMessage('bot', "Chat cleared. Nayi shuruat karein! 🚀");
}

function sendAiMessage() {
    let input = document.getElementById('ai-input');
    let msg = input.value.trim();
    if(!msg) return;

    appendMessage('user', msg);
    input.value = '';

    // Show Animated Typing Indicator
    let chatBody = document.getElementById('ai-messages');
    let loadDiv = document.createElement('div');
    loadDiv.className = 'ai-msg ai-bot';
    loadDiv.id = 'ai-loading';
    loadDiv.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
    chatBody.appendChild(loadDiv);
    chatBody.scrollTop = chatBody.scrollHeight;

    fetch('ai_helper.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=ask_assistant&query=${encodeURIComponent(msg)}&curr_code=${window.currConfig.code}&curr_rate=${window.currConfig.rate}&curr_sym=${window.currConfig.sym}`
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('ai-loading').remove();
        if(d.status === 'success') {
            appendMessage('bot', d.reply);
        } else {
            appendMessage('bot', "Error: " + d.message);
        }
    })
    .catch(e => {
        document.getElementById('ai-loading').remove();
        appendMessage('bot', "Network Error.");
    });
}

// Locate & Highlight Service
function locateService(id) {
    toggleAiChat(); // Close Chat
    let s = window.svcData[id];
    if(!s) { alert("Service not found!"); return; }
    
    // 1. Open App Container
    let appKey = s.app_key; 
    let appName = s.app; // e.g. instagram
    
    // Reset view
    $('#platform-grid').style.display='none'; 
    $$('.app-container').forEach(x=>x.style.display='none'); 

    // Find container that has this service
    let targetItem = document.getElementById(`svc-${id}`);
    if(targetItem) {
        let parentApp = targetItem.closest('.app-container');
        parentApp.style.display = 'block';
        
        let parentCat = targetItem.closest('.cat-group');
        parentCat.querySelector('.svc-list').style.display = 'block';
        
        targetItem.scrollIntoView({behavior: "smooth", block: "center"});
        targetItem.classList.add('blinking-highlight');
        setTimeout(() => targetItem.classList.remove('blinking-highlight'), 3000);
    } else {
        alert("Service is currently hidden or disabled.");
    }
}
</script>
<?php include '_smm_footer.php'; ?>