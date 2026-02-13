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

// --- DATA FETCHING ---
try {
    // 1. Fetch Dynamic Sub-Categories
    $dynamic_sub_cats = [];
    try {
        $stmt_sub = $db->query("SELECT * FROM smm_sub_categories WHERE is_active=1 ORDER BY sort_order ASC");
        $sub_rows = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sub_rows as $row) {
            $mainApp = trim($row['main_app']);
            $dynamic_sub_cats[$mainApp][] = [
                'name' => $row['sub_cat_name'],
                'icon' => $row['sub_cat_icon'], 
                'keys' => strtolower($row['keywords'])
            ];
        }
    } catch (PDOException $e) {
        $dynamic_sub_cats = [];
    }

    // 2. Fetch Services
    $stmt = $db->query("SELECT s.*, p.api_url as provider_api FROM smm_services s LEFT JOIN smm_providers p ON s.provider_id = p.id WHERE s.is_active = 1 ORDER BY s.category ASC, s.service_rate ASC");
    $all_services = $stmt->fetchAll();
    
    $grouped_apps = [];
    $services_json = [];
    
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
        $app_name = 'Others'; 
        
        // Auto-detect App
        if (!empty($dynamic_sub_cats)) {
            foreach ($dynamic_sub_cats as $kApp => $filters) {
                if (stripos($full_cat, $kApp) !== false) {
                    $app_name = $kApp;
                    break;
                }
            }
        } 
        
        if ($app_name === 'Others') {
            foreach ($main_app_icons as $kApp => $icon) {
                if (stripos($full_cat, $kApp) !== false) {
                    $app_name = $kApp;
                    break;
                }
            }
        }
        
        $grouped_apps[$app_name]['services'][] = $s; 
        
        // Assign Filters
        if (isset($dynamic_sub_cats[$app_name])) {
            $grouped_apps[$app_name]['filters'] = $dynamic_sub_cats[$app_name];
        } else {
            $grouped_apps[$app_name]['filters'] = [
                ['name'=>'Followers', 'icon'=>'default.png', 'keys'=>'followers, sub'],
                ['name'=>'Likes', 'icon'=>'default.png', 'keys'=>'likes, heart'],
                ['name'=>'Views', 'icon'=>'default.png', 'keys'=>'views, play']
            ];
        }
        
        // Data for JS
        $is_comment = (stripos($s['name'], 'Comment') !== false || stripos($s['category'], 'Comment') !== false);
        $has_drip = (isset($s['dripfeed']) && $s['dripfeed'] == 1) ? 1 : 0;
        
        $services_json[$s['id']] = [
            'id'     => $s['id'],
            'rate'   => (float)$s['service_rate'], 
            'min'    => (int)$s['min'],
            'max'    => (int)$s['max'],
            'avg'    => formatSmmAvgTime($s['avg_time']),
            'avg_raw' => (int)$s['avg_time'], // Raw minutes for calculations
            'refill' => (bool)$s['has_refill'],
            'cancel' => (bool)$s['has_cancel'],
            'drip'   => $has_drip,
            'type'   => $s['service_type'] ?? 'Default',
            'name'   => sanitize($s['name']),
            'cat'    => sanitize($full_cat),
            'desc'   => nl2br($s['description'] ?? 'No details.'),
            'is_comment' => $is_comment,
            'app'    => $app_name
        ];
    }
    ksort($grouped_apps);

} catch (Exception $e) { $error = $e->getMessage(); }
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>

<script>
    window.currConfig = { code: "<?=$curr_code?>", rate: <?=$curr_rate?>, sym: "<?=$curr_symbol?>" };
    window.svcData = <?= json_encode($services_json) ?>;
    window.appsData = <?= json_encode($grouped_apps) ?>;
    window.mainIcons = <?= json_encode($main_app_icons) ?>;
</script>

<style>
/* --- VARIABLES --- */
:root {
    --primary: #8b5cf6; 
    --primary-dark: #6d28d9;
    --primary-glow: rgba(139, 92, 246, 0.4);
    --glass-bg: rgba(255, 255, 255, 0.9);
    --text-main: #1e293b;
    --text-sub: #64748b;
    --font: 'Plus Jakarta Sans', sans-serif;
}

body {
    margin: 0; padding: 0;
    font-family: var(--font);
    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    min-height: 100vh;
    color: var(--text-main);
    overflow-x: hidden;
    position: relative;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* --- FLOATING BACKGROUND ICONS ANIMATION --- */
.bg-animation {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    z-index: -1; overflow: hidden; pointer-events: none;
}
.bg-icon {
    position: absolute; bottom: -50px;
    font-size: 2rem; color: rgba(255, 255, 255, 0.15);
    animation: floatUp 10s linear infinite;
}
@keyframes floatUp {
    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 0.4; }
    90% { opacity: 0.4; }
    100% { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
}

/* --- LAYOUT --- */
.app-wrapper { max-width: 800px; margin: 20px auto; padding: 20px; position: relative; z-index: 1; }

.glass-panel {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.6);
    border-radius: 24px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25);
    padding: 20px;
    margin-bottom: 20px;
}

.search-box { position: relative; margin-bottom: 25px; }
.search-box input {
    width: 100%; padding: 18px 25px 18px 55px; border-radius: 20px; border: none;
    background: rgba(255, 255, 255, 0.95); box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    font-size: 1.1rem; font-family: var(--font); transition: 0.3s;
}
.search-box input:focus { transform: scale(1.02); box-shadow: 0 15px 35px rgba(0,0,0,0.2); outline: none; }
.search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: var(--primary); }

.view-section { display: none; animation: fadeInUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; }
.view-section.active { display: block; }

@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; }
.app-card {
    background: rgba(255,255,255,0.85); border-radius: 18px; padding: 15px 5px;
    text-align: center; cursor: pointer; transition: all 0.3s;
    border: 1px solid rgba(255,255,255,0.5); position: relative; overflow: hidden;
}
.app-card:hover { background: white; transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: var(--primary); }
.app-card img { width: 45px; height: 45px; object-fit: contain; margin-bottom: 8px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }
.app-name { font-weight: 700; font-size: 0.85rem; display: block; color: black; }

.filter-wrapper { 
    overflow-x: auto; white-space: normal; padding-bottom: 10px; margin-bottom: 15px; 
    scrollbar-width: none; -ms-overflow-style: none;
    display: flex; align-items: center; gap: 8px;
}
.filter-wrapper::-webkit-scrollbar { display: none; }

.filter-btn { height: 50px; width: auto; cursor: pointer; transition: 0.2s; border-radius: 8px; opacity: 0.9; }
.filter-btn:hover { transform: translateY(-2px); opacity: 1; }
.filter-btn.active { transform: scale(1.05); opacity: 1; }

/* --- BIG WHITE BOX SUB-CATS --- */
.big-white-box {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 10px 0; 
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0; 
    border: 1px solid rgba(255,255,255,0.8);
}

.ios-img-btn {
    width: 100%;
    max-width: 180px;      
    height: auto;
    object-fit: contain;
    cursor: pointer;
    margin: 0; 
    padding: 2px 0;
    transition: transform 0.2s ease, filter 0.2s;
    opacity: 0; 
}

@keyframes listSlide {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}

.ios-img-btn:hover {
    transform: scale(1.03);
    filter: brightness(1.05);
    z-index: 2;
}

.ios-img-btn:active { transform: scale(0.98); }

/* --- SERVICE LIST --- */
.svc-item {
    background: white; border-radius: 18px; padding: 18px; margin-bottom: 12px;
    transition: 0.3s; border-left: 4px solid transparent; box-shadow: 0 2px 8px rgba(0,0,0,0.03); cursor: pointer; border-color: #0B9FF5; 
}
.svc-item:hover { transform: scale(1.01); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-left-color: var(--primary); }
.svc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.svc-title { font-weight: 700; font-size: 0.95rem; color: var(--text-main); line-height: 1.4; flex:1; margin-right: 10px; }
.svc-price {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 5px 10px;
    border-radius: 10px; font-weight: 700; font-size: 0.8rem; white-space: nowrap;
    box-shadow: 0 3px 8px rgba(139, 92, 246, 0.3);
}
.svc-tags { display: flex; gap: 6px; font-size: 0.7rem; font-weight: 600; flex-wrap: wrap; }
.tag { padding: 3px 8px; border-radius: 6px; background: #f1f5f9; color: var(--text-sub); display: flex; align-items: center; gap: 4px; }
.tag.green { background: #dcfce7; color: #166534; }
.tag.red { background: #fee2e2; color: #991b1b; }
.tag.blue { background: #dbeafe; color: #1e40af; }

/* --- NAV HEADER & PURPLE BACK BUTTON --- */
.nav-header {
    display: flex; align-items: center; gap: 15px; margin-bottom: 20px;
    width: 100%;
}
.back-btn {
    width: 42px; height: 42px; border-radius: 50%; border: none;
    background: linear-gradient(135deg, #8b5cf6, #6d28d9); 
    color: white; 
    cursor: pointer;
    transition: 0.3s; font-size: 1.1rem;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    display: flex; align-items: center; justify-content: center;
}
.back-btn:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.6); }

/* =========================================
   --- COMPACT ORDER MODAL WITH LIVE ICONS ---
   ========================================= */
.modal-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 99999;
    justify-content: center; align-items: center; padding: 15px;
}
.modal-overlay.active { display: flex; animation: fadeIn 0.2s ease-out; }

.modal-content {
    background: #fff; width: 100%; max-width: 450px; /* Reduced width */
    border-radius: 20px;
    box-shadow: 0 20px 50px -10px rgba(0,0,0,0.2); 
    overflow: hidden; display: flex; flex-direction: column;
    max-height: 90vh; animation: zoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header { 
    padding: 15px 20px; /* Reduced Padding */
    background: #f8fafc; border-bottom: 1px solid #f1f5f9; 
    display: flex; justify-content: space-between; align-items: center; 
}
.modal-header h3 { font-size: 1.1rem; font-weight: 800; color: #334155; margin: 0; }

.modal-close { 
    background: transparent; border: none; width: 30px; height: 30px; 
    border-radius: 50%; font-size: 1.1rem; cursor: pointer; color: #94a3b8; 
    display: flex; align-items: center; justify-content: center; transition: 0.2s; 
}
.modal-close:hover { background: #fee2e2; color: #ef4444; }

.modal-body { padding: 20px; /* Tighter padding */ overflow-y: auto; }

/* COMPACT LIVE ETA BOX */
.eta-box {
    background: linear-gradient(to right, #ecfdf5, #f0fdf4);
    border: 1px dashed #86efac;
    color: #15803d;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.eta-icon-wrap {
    width: 32px; height: 32px; background: white; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: #16a34a;
    position: relative;
}
.pulse-dot {
    width: 8px; height: 8px; background: #22c55e; border-radius: 50%;
    position: absolute; top: 0; right: 0;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px; }
.stat-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 10px; text-align: center; }
.stat-box small { display: block; font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.stat-box b { font-size: 0.85rem; color: #334155; }

/* COMPACT INPUT FIELDS WITH ICONS */
.form-group { margin-bottom: 12px; } /* Reduced margin */
.form-label { display: block; font-weight: 700; font-size: 0.8rem; margin-bottom: 5px; color: #475569; }

.input-icon-wrap { position: relative; }
.input-icon-wrap i { 
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%); 
    color: #94a3b8; font-size: 0.9rem; pointer-events: none; 
}

.form-input { 
    width: 100%; padding: 12px 12px 12px 40px; /* Left padding for icon */
    border: 2px solid #f1f5f9; border-radius: 12px; font-size: 0.9rem; 
    outline: none; transition: 0.2s; background: #fff; font-family: var(--font); color: #334155; 
}
.form-input:focus { border-color: var(--primary); background: #fff; }

.paste-btn { 
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
    background: #eff6ff; color: var(--primary); border: none; 
    padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; cursor: pointer; 
}

.desc-box { 
    background: #fefce8; border: 1px dashed #fde047; border-radius: 10px; 
    font-size: 0.8rem; color: #854d0e; margin-bottom: 15px; padding: 10px; 
    max-height: 80px; overflow-y: auto; line-height: 1.5; 
}

.total-charge-area {
    display: flex; justify-content: space-between; align-items: center;
    background: #f8fafc; padding: 12px 15px; border-radius: 12px; margin: 10px 0 15px 0; border: 1px solid #e2e8f0;
}

.btn-submit { 
    width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); 
    color: #fff; font-weight: 800; font-size: 0.95rem; border: none; border-radius: 12px; 
    cursor: pointer; box-shadow: 0 5px 15px rgba(139, 92, 246, 0.25); transition: 0.2s; 
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(139, 92, 246, 0.35); }

@keyframes zoomIn { from { transform:scale(0.95); opacity:0; } to { transform:scale(1); opacity:1; } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
</style>

<div class="bg-animation">
    <i class="fa-brands fa-instagram bg-icon" style="left: 10%; animation-duration: 8s;"></i>
    <i class="fa-brands fa-tiktok bg-icon" style="left: 25%; animation-duration: 12s; font-size: 1.5rem;"></i>
    <i class="fa-brands fa-youtube bg-icon" style="left: 40%; animation-duration: 10s;"></i>
    <i class="fa-brands fa-facebook bg-icon" style="left: 60%; animation-duration: 14s; font-size: 2.2rem;"></i>
    <i class="fa-brands fa-spotify bg-icon" style="left: 75%; animation-duration: 9s;"></i>
    <i class="fa-brands fa-twitter bg-icon" style="left: 90%; animation-duration: 11s;"></i>
</div>

<div class="app-wrapper">

    <div class="search-box">
        <i class="fa fa-search search-icon"></i>
        <input type="text" id="global-search" placeholder="Search services (e.g. Likes, Views...)">
    </div>

    <div id="view-home" class="view-section active">
        <h3 style="color:white; text-shadow:0 2px 4px rgba(0,0,0,0.2); margin-bottom:15px; font-weight:800;">
            <i class="fa fa-icons"></i> Select Platform
        </h3>
        <div class="glass-panel">
            <div class="grid-container">
                <?php foreach($grouped_apps as $appName => $data): 
                    $icon = 'smm.png';
                    if(isset($main_app_icons[$appName])) {
                        $icon = $main_app_icons[$appName];
                    }
                ?>
                <div class="app-card" onclick="goToApp('<?= $appName ?>')" data-tilt>
                    <img src="../assets/img/icons/<?= $icon ?>" onerror="this.src='../assets/img/icons/smm.png'">
                    <span class="app-name"><?= $appName ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="view-subcats" class="view-section">
        <div class="nav-header">
            <button class="back-btn" onclick="goBack()"><i class="fa fa-arrow-left"></i></button>
            <h3 style="margin:0; font-size:1.3rem; color:white; font-weight:800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);" id="subcat-title">App Name</h3>
        </div>
        
        <h4 style="color:white; margin-bottom:15px; font-weight:700; text-align:center; text-shadow:0 1px 2px rgba(0,0,0,0.1);">Select Category</h4>
        
        <div class="big-white-box" id="subcat-image-container">
            </div>
    </div>

    <div id="view-services" class="view-section">
        <div class="nav-header" style="margin-bottom:15px;">
            <button class="back-btn" onclick="goBack()"><i class="fa fa-arrow-left"></i></button>
            <div>
                <h3 style="margin:0; font-size:1.1rem; color:white; font-weight:700;" id="svc-list-title">Category</h3>
                <span style="font-size:0.8rem; color: rgba(255,255,255,0.9);">Select a package</span>
            </div>
        </div>

        <div class="filter-wrapper">
            <img src="../assets/img/icons/Cheapest.png" class="filter-btn" onclick="applyFilter(this, 'cheapest')">
            <img src="../assets/img/icons/High.png" class="filter-btn" onclick="applyFilter(this, 'high_rate')">
            <img src="../assets/img/icons/Hq.png" class="filter-btn" onclick="applyFilter(this, 'high_quality')">
            <img src="../assets/img/icons/Instant.png" class="filter-btn" onclick="applyFilter(this, 'instant')">
            <img src="../assets/img/icons/Non-drop.png" class="filter-btn" onclick="applyFilter(this, 'non_drop')">
            <img src="../assets/img/icons/Refill.png" class="filter-btn" onclick="applyFilter(this, 'refill')">
            <img src="../assets/img/icons/Norefill.png" class="filter-btn" onclick="applyFilter(this, 'no_refill')">
        </div>
        
        <div id="service-list-container"></div>
        <div id="no-services-msg" style="display:none; text-align:center; color:white; margin-top:30px;">
            <i class="fa fa-box-open" style="font-size:2rem; opacity:0.7;"></i>
            <p>No services found.</p>
        </div>
    </div>

</div>

<div class="modal-overlay" id="order-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-cart-shopping" style="color:var(--primary); margin-right:8px;"></i> New Order</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            
            <div class="eta-box" id="eta-container" style="display:none;">
                <div class="eta-icon-wrap">
                    <i class="fa-regular fa-clock"></i>
                    <div class="pulse-dot"></div>
                </div>
                <div>
                    <span style="font-size:0.7rem; display:block; opacity:0.8; font-weight:700;">ESTIMATED COMPLETION</span>
                    <span id="eta-text" style="font-size:0.95rem; font-weight:700;">Calculating...</span>
                </div>
            </div>

            <div id="m-stats" class="stats-grid"></div>
            <div id="m-desc" class="desc-box"></div>
            
            <form action="smm_order_action.php" method="POST" id="order-form">
                <input type="hidden" name="service_id" id="m-id">
                
                <div class="form-group">
                    <label class="form-label">Link / Username</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-link"></i>
                        <input type="text" name="link" id="m-link" class="form-input" style="padding-right:70px;" placeholder="https://..." required>
                        <button type="button" class="paste-btn" onclick="pasteLink()">PASTE</button>
                    </div>
                </div>
                
                <div id="grp-qty" class="form-group">
                    <div style="display:flex; justify-content:space-between;">
                        <label class="form-label">Quantity</label>
                        <small id="min-max" style="font-size:0.7rem; color:var(--primary); font-weight:600;"></small>
                    </div>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-hashtag"></i>
                        <input type="number" name="quantity" id="m-qty" class="form-input" placeholder="e.g. 1000" required>
                    </div>
                </div>

                <div id="grp-com" class="form-group" style="display:none">
                    <label class="form-label">Comments (1 per line)</label>
                    <div class="input-icon-wrap">
                        <i class="fa-regular fa-comment-dots" style="top:20px;"></i>
                        <textarea name="comments" id="m-com" class="form-input" rows="3" style="padding-left:40px; height:auto;" placeholder="Nice post!"></textarea>
                    </div>
                </div>

                <div class="total-charge-area">
                    <span style="color:#64748b; font-weight:600; font-size:0.9rem;">Total Charge</span>
                    <span id="m-total" style="color:var(--primary); font-size:1.2rem; font-weight:800;">0.00</span>
                </div>

                <button type="submit" class="btn-submit">CONFIRM ORDER <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
let historyStack = ['home']; 
let currentApp = null;
let currentCat = null;
let activeServiceList = [];
let currentFilterType = 'all';

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

function goToApp(appName) {
    currentApp = appName;
    historyStack.push('subcats');
    document.getElementById('subcat-title').innerText = appName;
    
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
        
        // Stack Animation inside the box
        img.style.animation = `listSlide 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards`;
        img.style.animationDelay = `${index * 0.05}s`; 

        img.onclick = () => goToServices(filter.keys, filter.name);
        container.appendChild(img);
    });
    
    navigateTo('view-subcats');
}

function goToServices(keywords, filterName) {
    currentCat = filterName;
    historyStack.push('services');
    document.getElementById('svc-list-title').innerText = `${currentApp} ${filterName}`;
    
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
    else if (currentFilterType === 'high_quality') list = list.filter(s => s.name.toLowerCase().includes('hq') || s.name.toLowerCase().includes('vip'));
    else if (currentFilterType === 'instant') list = list.filter(s => s.name.toLowerCase().includes('instant') || s.cat.toLowerCase().includes('instant'));
    else if (currentFilterType === 'non_drop') list = list.filter(s => s.name.toLowerCase().includes('non-drop'));
    else if (currentFilterType === 'refill') list = list.filter(s => s.refill === true);
    else if (currentFilterType === 'no_refill') list = list.filter(s => s.refill === false);

    if(list.length === 0) noMsg.style.display = 'block';
    else {
        noMsg.style.display = 'none';
        list.forEach(s => {
            let price = (s.rate * window.currConfig.rate).toFixed(2);
            let tagsHtml = s.refill ? `<span class="tag green">♻️ Refill</span>` : `<span class="tag red">🚫 No Refill</span>`;
            let item = document.createElement('div');
            item.className = 'svc-item'; 
            item.onclick = () => openModal(s.id);
            item.innerHTML = `<div class="svc-header"><span class="svc-title">${s.name}</span><span class="svc-price">${window.currConfig.sym} ${price}</span></div><div class="svc-tags">${tagsHtml}<span class="tag blue">⏱ ${s.avg}</span></div>`;
            container.appendChild(item);
        });
    }
}

let currSvc = null;
function openModal(id) {
    let s = window.svcData[id]; if(!s) return;
    currSvc = s;
    document.getElementById('m-id').value = id; 
    document.getElementById('min-max').innerText = `Min: ${s.min} | Max: ${s.max}`;
    document.getElementById('m-qty').setAttribute('min', s.min);
    document.getElementById('m-qty').setAttribute('max', s.max);
    document.getElementById('m-desc').innerText = s.desc.replace(/<[^>]*>?/gm, ''); 
    
    let rC = s.refill ? '#10b981' : '#ef4444';
    let cC = s.cancel ? '#10b981' : '#ef4444';
    
    // Tighter Stats
    document.getElementById('m-stats').innerHTML = `
        <div class="stat-box"><small>Refill</small><b style="color:${rC}">${s.refill?'Yes':'No'}</b></div>
        <div class="stat-box"><small>Cancel</small><b style="color:${cC}">${s.cancel?'Yes':'No'}</b></div>
    `;

    // --- CALCULATE ETA (LIVE) ---
    if(s.avg_raw > 0) {
        let now = new Date();
        // Add minutes to current time
        let completionTime = new Date(now.getTime() + s.avg_raw * 60000);
        let timeString = completionTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        
        document.getElementById('eta-text').innerText = "Done by " + timeString;
        document.getElementById('eta-container').style.display = 'flex';
    } else {
        document.getElementById('eta-container').style.display = 'none';
    }

    document.getElementById('m-qty').value=''; 
    document.getElementById('m-com').value=''; 
    document.getElementById('m-link').value='';
    document.getElementById('m-total').innerText = window.currConfig.sym + ' 0.00';

    if(s.type === 'Package') {
        document.getElementById('grp-qty').style.display='none'; 
        document.getElementById('grp-com').style.display='none'; 
        document.getElementById('m-qty').value = '1'; 
        updatePrice(1);
    } else if(s.is_comment || s.type === 'Custom Comments') { 
        document.getElementById('grp-qty').style.display='none'; 
        document.getElementById('grp-com').style.display='block'; 
    } else { 
        document.getElementById('grp-qty').style.display='block'; 
        document.getElementById('grp-com').style.display='none'; 
    }
    document.getElementById('order-modal').classList.add('active');
}

function closeModal() { document.getElementById('order-modal').classList.remove('active'); }

function updatePrice(qty) {
    if(!currSvc) return;
    let p = (currSvc.type === 'Package') ? currSvc.rate : (qty/1000)*currSvc.rate;
    if(window.currConfig.code!=='PKR') p*=window.currConfig.rate;
    document.getElementById('m-total').innerText = window.currConfig.sym + ' ' + p.toFixed(2);
}

async function pasteLink() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('m-link').value = text;
    } catch(err) { alert('Clipboard permission denied'); }
}

document.getElementById('m-qty').addEventListener('input', function(){ updatePrice(parseInt(this.value)||0) });
document.getElementById('m-com').addEventListener('input', function(){ 
    let c=this.value.split('\n').filter(x=>x.trim()!=='').length; 
    document.getElementById('m-qty').value=c; 
    updatePrice(c); 
});

document.getElementById('order-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('smm_order_action.php', { method:'POST', body: formData })
    .then(r=>r.text())
    .then(res => {
        closeModal();
        Swal.fire({ title: 'Order Received!', text: 'Your order is being processed.', icon: 'success', confirmButtonColor: '#8b5cf6' });
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

VanillaTilt.init(document.querySelectorAll(".app-card"), { max: 15, speed: 400, glare: true, "max-glare": 0.2 });
</script>
<?php include '_smm_footer.php'; ?>