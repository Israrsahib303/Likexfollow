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
        SELECT s.*, f.id as is_favorite
        FROM smm_services s
        LEFT JOIN user_favorite_services f ON s.id = f.service_id AND f.user_id = $user_id
        WHERE s.is_active = 1
        ORDER BY s.category ASC, s.name ASC
    ");
    $all_services = $stmt->fetchAll();
    
    $grouped_apps = [];
    $services_json = [];
    
    // --- 🛠️ APPS CONFIGURATION (Specific Filters per App) ---
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
        'Twitch' => ['filters' => ['Followers','Views','Live'], 'icon' => 'default.png'],
        'Netflix' => ['filters' => ['Premium','Screens'], 'icon' => 'default.png']
    ];

    foreach ($all_services as $s) {
        $full_cat = trim($s['category']);
        $app_name = 'Others'; 
        $app_filters = ['Followers','Likes','Views']; // Default filters
        $found = false;

        // Auto-Detect App based on Category Name
        foreach ($known_apps as $kApp => $data) {
            if (stripos($full_cat, $kApp) !== false) {
                $app_name = $kApp;
                if(trim($kApp) == 'X ') $app_name = 'Twitter';
                $app_filters = $data['filters'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $parts = explode(' - ', $full_cat);
            $app_name = (count($parts) > 1) ? trim($parts[0]) : $full_cat;
        }
        
        // Grouping Data
        $grouped_apps[$app_name]['services'][$full_cat][] = $s;
        $grouped_apps[$app_name]['filters'] = $app_filters; 

        $is_comment = (stripos($s['name'], 'Comment') !== false || stripos($s['category'], 'Comment') !== false);
        
        // Visual Icons Logic
        $icon_char = '🟢'; 
        if (stripos($s['name'], 'Best') !== false || stripos($s['name'], 'Recommended') !== false || stripos($s['name'], 'VIP') !== false) $icon_char = '🔥';
        elseif (stripos($s['avg_time'], 'Instant') !== false) $icon_char = '⚡';
        elseif (stripos($s['avg_time'], 'hour') !== false) $icon_char = '🟡';

        // Drip Feed Logic (1 = Allowed, 0 = Not Allowed)
        $has_drip = 1; 
        if(isset($s['dripfeed']) && ($s['dripfeed'] == 0 || $s['dripfeed'] == '0')) {
            $has_drip = 0;
        }

        $services_json[$s['id']] = [
            'rate' => (float)$s['service_rate'],
            'min' => (int)$s['min'],
            'max' => (int)$s['max'],
            'avg' => formatSmmAvgTime($s['avg_time']),
            'refill' => (bool)$s['has_refill'],
            'cancel' => (bool)$s['has_cancel'],
            'drip'   => $has_drip, // Sends 1 or 0
            'name' => sanitize($s['name']),
            'category' => sanitize($full_cat), 
            'desc' => nl2br($s['description'] ?? 'No details available.'),
            'is_comment' => $is_comment,
            'icon' => $icon_char,
            'app' => strtolower($app_name)
        ];
    }
    ksort($grouped_apps);

} catch (Exception $e) { $error = $e->getMessage(); }

$logo_url = !empty($site_logo) ? "../assets/img/$site_logo" : "";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    window.currConfig = { code: "<?=$curr_code?>", rate: <?=$curr_rate?>, sym: "<?=$curr_symbol?>" };
    window.svcData = <?= json_encode($services_json) ?>;
    window.siteData = { logo: "<?=$logo_url?>", name: "<?= htmlspecialchars($site_name) ?>", wa: "<?= htmlspecialchars($admin_wa) ?>" };
    
    // Pass Filters Data to JS
    window.appFilters = {};
    <?php foreach($grouped_apps as $name => $data): ?>
    window.appFilters["<?= md5($name) ?>"] = <?= json_encode($data['filters']) ?>;
    <?php endforeach; ?>
</script>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* --- 🎨 MAIN THEME --- */
:root { --primary: #4f46e5; --bg-body: #f8fafc; --card-bg: #ffffff; --text-main: #0f172a; --text-sub: #64748b; --border: #e2e8f0; --radius: 16px; }
body { background-color: var(--bg-body); font-family: 'Outfit', sans-serif; color: var(--text-main); font-size: 15px; overflow-x: hidden; }

/* --- GRID & CARDS --- */
.platform-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; margin-bottom: 30px; animation: fadeIn 0.5s; }
.platform-card {
    background: var(--card-bg); padding: 20px; border-radius: var(--radius); border: 1px solid var(--border);
    text-align: center; cursor: pointer; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;
}
.platform-card:hover { border-color: var(--primary); transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.15); }
.platform-icon { width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px; transition: 0.3s; }
.platform-card:hover .platform-icon { transform: scale(1.1); }
.platform-title { font-weight: 700; font-size: 0.9rem; display: block; }

/* --- APP VIEW --- */
.app-container { display: none; animation: slideIn 0.3s ease-out; }
@keyframes slideIn { from { opacity:0; transform: translateX(20px); } to { opacity:1; transform: translateX(0); } }

.top-nav { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
.back-btn {
    background: #fff; border: 1px solid var(--border); padding: 10px 15px; border-radius: 12px;
    cursor: pointer; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 5px;
    transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.back-btn:hover { border-color: var(--primary); color: var(--primary); transform: translateX(-3px); }

/* 🔥 Smart Filter Chips */
.filter-wrap { overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; scrollbar-width: none; }
.filter-scroll { display: flex; gap: 8px; }
.filter-chip { 
    background: #fff; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 50px; 
    white-space: nowrap; cursor: pointer; font-weight: 700; font-size: 0.85rem; color: var(--text-sub); 
    transition: 0.2s; display: flex; align-items: center; gap: 6px;
}
.filter-chip img { width: 16px; height: 16px; object-fit: contain; }
.filter-chip:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }
.filter-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }

/* Categories & Services */
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
.service-item:hover { background: #fcfaff; padding-left: 25px; border-left: 4px solid var(--primary); }
.service-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.service-name { font-weight: 600; font-size: 0.95rem; color: var(--text-main); line-height: 1.4; flex: 1; padding-right: 10px; }
.service-price { background: #e0e7ff; color: var(--primary); padding: 4px 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 800; white-space: nowrap; }

.service-meta { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top:8px; }
.tag { padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
.tag-time { background: #f1f5f9; color: var(--text-sub); }
.tag-refill { background: #dcfce7; color: #166534; }
.tag-cancel { background: #fee2e2; color: #991b1b; }

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
.modal-header {
    padding: 20px 25px; background: #fff; border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-close { background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; color: #64748b; transition: 0.2s; }
.modal-close:hover { background: #fee2e2; color: #ef4444; transform: rotate(90deg); }
.modal-body { padding: 25px; overflow-y: auto; }

/* Stats & Desc */
.stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px; }
.stat-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 12px; text-align: center; }
.stat-box small { display: block; font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
.stat-box b { font-size: 0.9rem; color: #334155; }

.desc-box {
    background: #fff; border: 1px dashed #cbd5e1; border-radius: 12px;
    font-size: 0.85rem; color: #64748b; margin-bottom: 20px; padding: 15px;
    max-height: 100px; overflow-y: auto; line-height: 1.6;
}

/* Form */
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 8px; color: #334155; }
.input-wrap { position: relative; }
.form-input { 
    width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 14px; 
    font-size: 1rem; outline: none; transition: 0.2s; background: #fff; color: #0f172a;
}
.form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
.paste-btn {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: #eff6ff; color: var(--primary); border: none; padding: 6px 10px;
    border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer;
}
.paste-btn:hover { background: var(--primary); color: #fff; }

/* 🛡️ Link Error with Icon */
.link-err-box { 
    margin-top: 8px; padding: 10px; background: #fef2f2; border: 1px solid #fecaca; 
    border-radius: 10px; display: none; align-items: center; gap: 10px; animation: shake 0.4s;
}
.link-err-icon { width: 30px; height: 30px; }
.link-err-text { font-size: 0.85rem; color: #991b1b; font-weight: 600; line-height: 1.3; }

/* 🚀 Improved Drip Feed */
.drip-toggle {
    background: #f0f9ff; border: 1px solid #bae6fd; padding: 12px; border-radius: 12px;
    cursor: pointer; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;
}
.drip-toggle input { accent-color: var(--primary); width: 18px; height: 18px; }
.drip-area { 
    display: none; background: #fff; border: 1px solid #e2e8f0; border-top: none; 
    border-radius: 0 0 12px 12px; padding: 15px; margin-top: -15px; margin-bottom: 20px; 
}
.drip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.drip-guide { font-size: 0.8rem; color: #64748b; background: #f8fafc; padding: 8px; border-radius: 8px; margin-top: 10px; }

/* 💸 Reseller Box */
.reseller-toggle {
    background: #ecfdf5; border: 1px solid #6ee7b7; padding: 12px; border-radius: 12px;
    cursor: pointer; display: flex; align-items: center; justify-content: space-between;
    font-weight: 700; color: #065f46; margin-top: 10px;
}
.reseller-box {
    display: none; background: #fff; border: 1px solid #6ee7b7; border-top: none;
    border-radius: 0 0 12px 12px; padding: 15px; margin-top: -5px; margin-bottom: 10px;
}
.res-input { border-color: #34d399; background: #f0fdf4; font-weight: 700; color: #064e3b; }
.res-result { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-weight: 800; font-size: 1.1rem; color: #059669; }

.btn-submit {
    width: 100%; padding: 16px; background: var(--primary); color: #fff; font-weight: 800; font-size: 1rem;
    border: none; border-radius: 14px; cursor: pointer; margin-top: 15px;
    box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3); transition: 0.3s;
}
.btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4); }

/* === 🔥 FIXED RECEIPT CSS === */
#receipt-node {
    position: absolute;
    left: -9999px; /* Pushes it off-screen, NOT behind */
    top: 0;
    width: 500px;
    background: #fff;
    /* Do NOT use z-index negative or display none */
}

/* Receipt Styles */
.rec-header {
    padding: 40px 30px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: white;
    display: flex; align-items: center; justify-content: space-between;
    position: relative; overflow: hidden;
}
.rec-header::after {
    content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    transform: rotate(30deg);
}
.rec-logo-box { position: relative; z-index: 10; display: flex; align-items: center; gap: 15px; }
.rec-logo { height: 50px; object-fit: contain; background: #fff; padding: 5px; border-radius: 12px; }
.rec-badge {
    position: relative; z-index: 10;
    background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 50px;
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.3);
}
.rec-body { padding: 40px 30px; background: #ffffff; }
.rec-svc-name {
    font-size: 26px; font-weight: 900; color: #1e293b; line-height: 1.3;
    margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px dashed #e2e8f0;
}
.rec-price-box {
    background: #f8fafc; border-radius: 20px; padding: 25px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 30px; border: 2px solid #e2e8f0;
}
.rec-p-lbl { font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; }
.rec-p-val { font-size: 36px; font-weight: 900; color: #4f46e5; }
.rec-stats-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 30px; }
.rec-stat-pill {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
    padding: 15px 10px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
}
.rec-stat-lbl { font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; margin-bottom: 5px; }
.rec-stat-val { font-size: 14px; font-weight: 800; color: #334155; }
.rec-desc-wrap {
    font-size: 14px; line-height: 1.6; padding: 20px;
    background: #f0f9ff; border-radius: 16px; color: #0369a1; border: 1px solid #bae6fd; font-weight: 500;
}
.rec-footer {
    padding: 25px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
}
.rec-footer-text { font-size: 14px; font-weight: 600; color: #64748b; }
.rec-wa { 
    font-weight: 800; font-size: 18px; color: #25D366; 
    display: flex; align-items: center; gap: 10px; 
    background: #dcfce7; padding: 8px 16px; border-radius: 50px;
}

@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes zoomIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
@keyframes shake { 0%, 100% {transform: translateX(0);} 25% {transform: translateX(-5px);} 75% {transform: translateX(5px);} }
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
                <div class="filter-scroll" id="filters-<?= md5($appName) ?>">
                    </div>
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
                    ?>
                    <div class="service-item" data-name="<?= strtolower(sanitize($s['name'])) ?>" onclick="openModal(<?= $s['id'] ?>)">
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
                            
                            <div class="service-actions">
                                <button class="btn-receipt" onclick="event.stopPropagation(); genReceipt(<?= $s['id'] ?>)">
                                    📄 Info
                                </button>
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
                                <label class="form-label" style="font-size:0.8rem;">Runs (Baar)</label>
                                <input type="number" name="runs" id="m-runs" class="form-input" placeholder="e.g. 5" min="1">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:0.8rem;">Gap (Time)</label>
                                <div style="display:flex; gap:5px;">
                                    <input type="number" id="m-interval-raw" class="form-input" placeholder="30" min="1" style="flex:1">
                                    <select id="m-interval-unit" class="form-input" style="width:80px; padding:5px;">
                                        <option value="1">Min</option>
                                        <option value="60">Hour</option>
                                        <option value="1440">Day</option>
                                    </select>
                                </div>
                                <input type="hidden" name="interval" id="m-interval">
                            </div>
                        </div>
                        <div id="drip-guide" class="drip-guide">
                            💡 <b>Example:</b> If Qty 1000 & Runs 5 -> System will send 1000 likes, 5 times. Total: 5000.
                        </div>
                    </div>
                </div>

                <div id="grp-com" class="form-group" style="display:none">
                    <label class="form-label">Comments (1 per line)</label>
                    <textarea name="comments" id="m-com" class="form-input" rows="4" placeholder="Nice post!&#10;Great!"></textarea>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                    <span style="color:#64748b;font-weight:600;">Total Charge</span>
                    <span id="m-total" style="color:var(--primary);font-size:1.4rem;font-weight:800;">0.00</span>
                </div>
                <div id="m-hint" class="dynamic-hint"></div>
                
                <div class="reseller-toggle" onclick="toggleReseller()">
                    <span>💰 Reseller Profit Calculator</span>
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="reseller-box" id="reseller-area">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <div style="flex:1">
                            <label style="font-size:0.75rem; font-weight:700; color:#065f46;">Your Selling Price (Aap kitne ka bechna chahte hain?)</label>
                            <input type="number" id="calc-rate" class="form-input res-input" placeholder="e.g. 500" oninput="calcProfit()">
                        </div>
                    </div>
                    <div class="res-result">
                        <span>Your Profit (Bachat):</span>
                        <span id="profit-res">0.00</span>
                    </div>
                </div>

                <button type="submit" class="btn-submit">CONFIRM ORDER</button>
            </form>
        </div>
    </div>
</div>

<div id="receipt-node"></div>

<script>
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

// --- 1. DYNAMIC FILTERS PER APP ---
function openApp(appId, appName) {
    $('#platform-grid').style.display='none'; 
    $$('.app-container').forEach(x=>x.style.display='none'); 
    
    const appContainer = $('#app-'+appId);
    appContainer.style.display='block';
    
    // Generate Filters dynamically
    const filterBox = $('#filters-'+appId);
    filterBox.innerHTML = ''; // Clear old
    
    // Default filters if not defined
    let tags = window.appFilters[appId] || ['Followers', 'Likes', 'Views'];
    // Add 'All' chip
    tags = ['All', ...tags];

    tags.forEach(tag => {
        let icon = getFilterIcon(tag);
        let chip = document.createElement('div');
        chip.className = `filter-chip ${tag === 'All' ? 'active' : ''}`;
        chip.innerHTML = `${icon} ${tag}`;
        chip.onclick = function() { 
            // Handle Active UI
            filterBox.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            filterServices(tag, appContainer); 
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
    if(tag.includes('watch')) return '<i class="fa fa-clock" style="color:#8b5cf6"></i>';
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

// --- 2. FILTER LOGIC ---
function filterServices(tag, container) {
    let key = tag.toLowerCase();
    
    container.querySelectorAll('.service-item').forEach(item => {
        let name = item.dataset.name;
        if(key === 'all') {
            item.style.display = 'block';
        } else {
            // Smart matching (e.g. 'subs' matches 'subscribers')
            let match = false;
            if(key.includes('sub') && name.includes('sub')) match = true;
            else if(key.includes('page') && name.includes('page')) match = true;
            else if(name.includes(key)) match = true;
            
            item.style.display = match ? 'block' : 'none';
        }
    });

    // Auto-hide empty categories
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

// --- 3. LINK VALIDATOR (Advanced) ---
const appPatterns = {
    'instagram': { regex: /instagram\.com/, name: 'Instagram', icon: 'Instagram.png' },
    'tiktok': { regex: /tiktok\.com/, name: 'TikTok', icon: 'TikTok.png' },
    'youtube': { regex: /(youtube\.com|youtu\.be)/, name: 'Youtube', icon: 'Youtube.png' },
    'facebook': { regex: /(facebook\.com|fb\.watch)/, name: 'Facebook', icon: 'Facebook.png' },
    'twitter': { regex: /(twitter\.com|x\.com)/, name: 'Twitter', icon: 'Twitter.png' },
    'spotify': { regex: /spotify\.com/, name: 'Spotify', icon: 'Spotify.png' },
    'telegram': { regex: /t\.me/, name: 'Telegram', icon: 'Telegram.png' }
};

function validateLink(url) {
    if(!currSvc || !url) return;
    $('#link-err').style.display = 'none';
    
    let currentApp = currSvc.app; // e.g. 'instagram'
    
    // Check if it matches ANY other app
    for (const [key, data] of Object.entries(appPatterns)) {
        if (key !== currentApp && data.regex.test(url)) {
            // FOUND MISMATCH
            $('#err-icon').src = `../assets/img/icons/${data.icon}`;
            $('#err-text').innerHTML = `Wrong Link! You pasted a <b>${data.name}</b> link.<br>This service is for <b>${currentApp.charAt(0).toUpperCase() + currentApp.slice(1)}</b>.`;
            $('#link-err').style.display = 'flex';
            return;
        }
    }
}
$('#m-link').addEventListener('input', function(){ validateLink(this.value); });
async function pasteLink() {
    try { const text = await navigator.clipboard.readText(); $('#m-link').value = text; validateLink(text); } 
    catch (err) { alert('Paste manually.'); }
}

// --- 4. ORDER MODAL LOGIC ---
let currSvc = null;

function openModal(id) {
    let s = window.svcData[id]; if(!s) return;
    currSvc = s;
    $('#m-id').value=id; 
    $('#min-max').innerText = `Min: ${s.min} | Max: ${s.max}`;
    
    // Set browser validation
    $('#m-qty').setAttribute('min', s.min);
    $('#m-qty').setAttribute('max', s.max);
    
    $('#m-desc').innerText = s.desc.replace(/<[^>]*>?/gm, ''); // Clean Text
    
    // Colors
    let rC=s.refill?'#10b981':'#ef4444', cC=s.cancel?'#10b981':'#ef4444';
    $('#m-stats').innerHTML = `
        <div class="stat-box"><small>Start Time</small><b>${s.avg}</b></div>
        <div class="stat-box" style="border-bottom:3px solid ${rC}"><small>Refill</small><b style="color:${rC}">${s.refill?'Available':'No Refill'}</b></div>
        <div class="stat-box" style="border-bottom:3px solid ${cC}"><small>Cancel</small><b style="color:${cC}">${s.cancel?'Available':'No Cancel'}</b></div>
    `;

    // Reset Form
    $('#m-qty').value=''; $('#m-com').value=''; $('#m-link').value='';
    $('#m-total').innerText = window.currConfig.sym + ' 0.00';
    $('#m-hint').innerHTML = '';
    $('#link-err').style.display='none';
    
    // Drip Feed Reset
    $('#drip-check').checked = false;
    $('#drip-fields').style.display = 'none';
    $('#drip-val').value = '0';
    
    // Reseller Box Reset
    $('#reseller-area').style.display = 'none';
    $('#calc-rate').value = '';
    $('#profit-res').innerText = '0.00';

    if(s.is_comment) { 
        $('#grp-qty').style.display='none'; $('#grp-com').style.display='block'; $('#m-qty').readOnly=true; 
        $('#grp-drip').style.display = 'none'; // No drip for comments
    } else { 
        $('#grp-qty').style.display='block'; $('#grp-com').style.display='none'; $('#m-qty').readOnly=false; 
        
        // --- FIXED DRIP CHECK ---
        if(s.drip == 1) {
             $('#grp-drip').style.display = 'block'; 
        } else {
             $('#grp-drip').style.display = 'none'; 
        }
    }

    $('.modal-overlay').classList.add('active');
    updatePrice(0);
}
function closeModal() { $('.modal-overlay').classList.remove('active'); }

// --- 5. DRIP FEED & PRICE ---
function toggleDrip() {
    const on = $('#drip-check').checked;
    $('#drip-fields').style.display = on ? 'block' : 'none';
    $('#drip-val').value = on ? '1' : '0';
    updatePrice(parseInt($('#m-qty').value)||0);
}

function updateDripMath() {
    let raw = parseInt($('#m-interval-raw').value) || 0;
    let unit = parseInt($('#m-interval-unit').value) || 1;
    $('#m-interval').value = raw * unit;
}
$('#m-interval-raw').addEventListener('input', updateDripMath);
$('#m-interval-unit').addEventListener('change', updateDripMath);

function updatePrice(qty) {
    if(!currSvc) return;
    
    let multiplier = 1;
    let runs = 0;
    
    if($('#drip-check').checked) {
        runs = parseInt($('#m-runs').value) || 0;
        if(runs > 0) multiplier = runs;
        
        // Update Guide Text
        let total = qty * multiplier;
        $('#drip-guide').innerHTML = `💡 <b>Calculation:</b> ${qty} (Qty) x ${multiplier} (Runs) = <b>${total} Total Quantity</b>.`;
    }

    let totalQty = qty * multiplier;
    
    // Rate Calc
    let p = (totalQty/1000)*currSvc.rate;
    if(window.currConfig.code!=='PKR') p*=window.currConfig.rate;
    
    $('#m-total').innerText = window.currConfig.sym + ' ' + p.toFixed(2);

    // Hints
    let hints = '';
    if(totalQty >= 1000) hints += `<span class="hint-promo" style="color:#10b981">🚀 Good volume! Priority processing.</span>`;
    $('#m-hint').innerHTML = hints;
    
    calcProfit();
}

// Reseller Calc
function toggleReseller() {
    const el = $('#reseller-area');
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

function calcProfit() {
    if(!currSvc) return;
    let qty = parseInt($('#m-qty').value) || 0;
    let myRate = parseFloat($('#calc-rate').value) || 0;
    
    // Cost per 1000
    let cost = currSvc.rate;
    if(window.currConfig.code !== 'PKR') cost *= window.currConfig.rate;
    
    // Profit = (Selling Price - Cost) * (Qty / 1000)
    let profit = (myRate - cost) * (qty / 1000);
    
    // Drip feed multiplier logic if enabled
    if($('#drip-check').checked) {
        let runs = parseInt($('#m-runs').value) || 1;
        profit = profit * runs; 
    }

    $('#profit-res').innerText = window.currConfig.sym + ' ' + profit.toFixed(2);
    $('#profit-res').style.color = profit >= 0 ? '#059669' : '#dc2626';
}

// Events
$('#m-qty').addEventListener('input', function(){ updatePrice(parseInt(this.value)||0) });
$('#m-runs').addEventListener('input', function(){ updatePrice(parseInt($('#m-qty').value)||0) });
$('#m-com').addEventListener('input', function(){ let c=this.value.split('\n').filter(x=>x.trim()!=='').length; $('#m-qty').value=c; updatePrice(c); });

// Validation on Submit
$('#order-form').addEventListener('submit', function(e) {
    if(!currSvc) return;
    let qty = parseInt($('#m-qty').value) || 0;
    
    if(qty < currSvc.min) { e.preventDefault(); alert(`Minimum quantity is ${currSvc.min}`); return; }
    if(qty > currSvc.max) { e.preventDefault(); alert(`Maximum quantity is ${currSvc.max}`); return; }
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

// --- 🔥 FIXED: DYNAMIC RECEIPT GENERATOR ---
window.genReceipt = function(id) {
    let s = window.svcData[id];
    let d = window.cardDesign || {};

    let container = document.getElementById('receipt-node');
    container.innerHTML = '';
    
    let p = s.rate; 
    if(window.currConfig.code !== 'PKR') p *= window.currConfig.rate;
    
    let displayPrice = (p < 1 && p > 0) ? p.toFixed(4) : p.toFixed(2);
    let priceText = window.currConfig.sym + ' ' + displayPrice + ' ' + window.currConfig.code;
    let waText = window.siteData.wa ? window.siteData.wa : "Contact Support";

    const blocks = {
        'logo': `
            <div class="rec-header">
                <div class="rec-logo-box">
                    ${window.siteData.logo ? `<img src="${window.siteData.logo}" class="rec-logo">` : `<h2 style="margin:0;">${window.siteData.name}</h2>`}
                </div>
                <div class="rec-badge">Trusted ✅</div>
            </div>`,
        'title': `
            <div class="rec-body">
                <div class="rec-svc-name">${s.name}</div>
            </div>`,
        'price': `
            <div style="padding: 0 30px;">
                <div class="rec-price-box">
                    <div class="rec-p-lbl">Rate per 1000</div>
                    <div class="rec-p-val">${priceText}</div>
                </div>
            </div>`,
        'stats': `
            <div style="padding: 0 30px;">
                <div class="rec-stats-row">
                    <div class="rec-stat-pill">
                        <div class="rec-stat-lbl">Time</div>
                        <div class="rec-stat-val">${s.avg}</div>
                    </div>
                    <div class="rec-stat-pill">
                        <div class="rec-stat-lbl">Refill</div>
                        <div class="rec-stat-val" style="color:${s.refill ? '#16a34a' : '#dc2626'}">${s.refill?'Yes':'No'}</div>
                    </div>
                    <div class="rec-stat-pill">
                        <div class="rec-stat-lbl">Cancel</div>
                        <div class="rec-stat-val" style="color:${s.cancel ? '#16a34a' : '#dc2626'}">${s.cancel?'Yes':'No'}</div>
                    </div>
                </div>
            </div>`,
        'details': `
            <div style="padding: 0 30px 30px 30px;">
                <div class="rec-desc-wrap">
                    ${s.desc.replace(/<[^>]*>/g, '').substring(0, 250) + (s.desc.length>250?'...':'')}
                </div>
            </div>`,
        'footer': `
            <div class="rec-footer">
                <div class="rec-footer-text">Order Now on ${window.siteData.name}</div>
                <div class="rec-wa">
                    <img src="../assets/img/icons/Whatsapp.png" style="width:20px;height:20px;">
                    <span>${waText}</span>
                </div>
            </div>`
    };

    container.innerHTML = blocks['logo'] + blocks['title'] + blocks['price'] + blocks['stats'] + blocks['details'] + blocks['footer'];

    // FIXED DOWNLOAD: Using absolute position off-screen instead of z-index
    setTimeout(() => {
        html2canvas($('#receipt-node'), { 
            scale: 3, 
            useCORS: true, 
            allowTaint: true, 
            backgroundColor: '#ffffff'
        }).then(c => {
            let a = document.createElement('a'); a.download = 'Service-Info-' + id + '.png'; a.href = c.toDataURL('image/png'); a.click();
        });
    }, 100);
}
</script>
<?php include '_smm_footer.php'; ?>