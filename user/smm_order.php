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
    // Fetch all active services
    $stmt = $db->query("SELECT s.*, p.api_url as provider_api FROM smm_services s LEFT JOIN smm_providers p ON s.provider_id = p.id WHERE s.is_active = 1 ORDER BY s.category ASC, s.service_rate ASC");
    $all_services = $stmt->fetchAll();
    
    $grouped_apps = [];
    $services_json = [];
    
    // Define Apps, Filters & Icons
    // Note: Icons here are for the Main App Grid (Instagram.png etc)
    $known_apps = [
        'Instagram' => ['filters' => ['Followers','Likes','Views','Comments','Story','Reels'], 'icon' => 'Instagram.png'], 
        'TikTok' => ['filters' => ['Followers','Likes','Views','Comments','Saves'], 'icon' => 'TikTok.png'],
        'Youtube' => ['filters' => ['Subscribers','Views','Likes','Watchtime','Comments','Shorts'], 'icon' => 'Youtube.png'],
        'Facebook' => ['filters' => ['Followers','Page Likes','Views','Comments','Reels'], 'icon' => 'Facebook.png'],
        'Twitter' => ['filters' => ['Followers','Retweets','Likes','Views'], 'icon' => 'Twitter.png'],
        'Spotify' => ['filters' => ['Plays','Followers','Saves'], 'icon' => 'Spotify.png'],
        'Telegram' => ['filters' => ['Members','Views','Reactions'], 'icon' => 'Telegram.png'],
        'Snapchat' => ['filters' => ['Followers','Story Views','Score'], 'icon' => 'Snapchat.png'],
        'Linkedin' => ['filters' => ['Followers','Connections','Likes'], 'icon' => 'default.png'],
        'Website' => ['filters' => ['Traffic'], 'icon' => 'website.png']
    ];

    foreach ($all_services as $s) {
        $full_cat = trim($s['category']);
        $app_name = 'Others'; 
        $app_filters = ['Followers','Likes','Views']; 

        // Auto-detect App Name
        foreach ($known_apps as $kApp => $data) {
            if (stripos($full_cat, $kApp) !== false) {
                $app_name = $kApp;
                $app_filters = $data['filters'];
                break;
            }
        }
        
        // Group services by App
        $grouped_apps[$app_name]['services'][] = $s; 
        $grouped_apps[$app_name]['filters'] = $app_filters;
        
        // --- DATA PREP FOR MODAL ---
        $is_comment = (stripos($s['name'], 'Comment') !== false || stripos($s['category'], 'Comment') !== false);
        $has_drip = (isset($s['dripfeed']) && $s['dripfeed'] == 1) ? 1 : 0;
        
        $services_json[$s['id']] = [
            'id'     => $s['id'],
            'rate'   => (float)$s['service_rate'], 
            'min'    => (int)$s['min'],
            'max'    => (int)$s['max'],
            'avg'    => formatSmmAvgTime($s['avg_time']),
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
    // Passing Data to JS
    window.currConfig = { code: "<?=$curr_code?>", rate: <?=$curr_rate?>, sym: "<?=$curr_symbol?>" };
    window.svcData = <?= json_encode($services_json) ?>;
    window.appsData = <?= json_encode($grouped_apps) ?>;
</script>

<style>
/* --- 1. ANIMATED PURPLE BACKGROUND & VARIABLES --- */
:root {
    --primary: #8b5cf6; /* Violet */
    --primary-dark: #6d28d9;
    --primary-glow: rgba(139, 92, 246, 0.4);
    --glass-bg: rgba(255, 255, 255, 0.9);
    --glass-border: rgba(255, 255, 255, 0.6);
    --text-main: #1e293b;
    --text-sub: #64748b;
    --font: 'Plus Jakarta Sans', sans-serif;
    --radius: 16px; 
}

body {
    margin: 0; padding: 0;
    font-family: var(--font);
    background-color: white;
    background-size: 400% 400%;
    animation: gradientBG 12s ease infinite;
    min-height: 100vh;
    color: var(--text-main);
    overflow-x: hidden;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* --- 2. LAYOUT --- */
.app-wrapper { max-width: 800px; margin: 20px auto; padding: 20px; }

/* Standard Glass Card */
.glass-panel {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25);
    padding: 20px;
    margin-bottom: 20px;
}

/* --- 3. SEARCH HERO --- */
.search-box { position: relative; margin-bottom: 25px; }
.search-box input {
    width: 100%; padding: 18px 25px 18px 55px; border-radius: 20px; border: none;
    background: rgba(255, 255, 255, 0.95); box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    font-size: 1.1rem; font-family: var(--font); transition: 0.3s;
}
.search-box input:focus { transform: scale(1.02); box-shadow: 0 15px 35px rgba(0,0,0,0.2); outline: none; }
.search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: var(--primary); }

/* --- 4. VIEWS & ANIMATIONS --- */
.view-section { display: none; animation: fadeInUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; }
.view-section.active { display: block; }

@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* --- 5. GRID SYSTEM (PLATFORMS) --- */
.grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; }
.app-card {
    background: rgba(255,255,255,0.85); border-radius: 18px; padding: 15px 5px;
    text-align: center; cursor: pointer; transition: all 0.3s;
    border: 1px solid rgba(255,255,255,0.5); position: relative; overflow: hidden;
}
.app-card:hover { background: white; transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: var(--primary); }
.app-card img { width: 45px; height: 45px; object-fit: contain; margin-bottom: 8px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }
.app-name { font-weight: 700; font-size: 0.85rem; display: block; color: black; }

/* --- 7. FILTER BUTTONS --- */
.filter-wrapper { 
    overflow-x: auto; white-space: normal; padding-bottom: 10px; margin-bottom: 15px; 
    scrollbar-width: none; -ms-overflow-style: none;
    display: flex; align-items: center; gap: 8px;
}
.filter-wrapper::-webkit-scrollbar { display: none; }

.filter-btn {
    height: 50px; 
    width: auto;
    cursor: pointer;
    transition: 0.2s;
    border-radius: 8px;
    border: 2px solid transparent;
    opacity: 0.85;
}
.filter-btn:hover { transform: translateY(-2px); opacity: 1; }
.filter-btn.active { border-color: var(--primary); opacity: 1; transform: scale(1.05); }

/* --- NEW STYLE: IOS VERTICAL IMAGE LIST (NO BOX, NO TEXT) --- */
.ios-vertical-list {
    display: flex;
    flex-direction: column; /* Vertical Stack */
    align-items: center;    /* Center images */
    gap: 25px;              /* Space between images */
    padding: 20px 10px;
}

.ios-img-btn {
    width: 100%;
    max-width: 200px;      /* Large but constrained width */
    height: auto;
    object-fit: contain;
    cursor: pointer;
    
    /* Transparent / No Box */
    background: transparent;
    border: none;
    box-shadow: none;
    
    /* Animation Initial State */
    opacity: 0;
    transform: translateY(30px) scale(0.9);
    filter: drop-shadow(0 5px 15px rgba(0,0,0,0.1)); /* Soft shadow on the image itself */
    transition: filter 0.3s;
}

/* The Animation Keyframe */
@keyframes iosSlideUp {
    0% { opacity: 0; transform: translateY(40px) scale(0.85); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

.ios-img-btn:active {
    transform: scale(0.95); /* Touch feedback */
    filter: drop-shadow(0 2px 5px rgba(0,0,0,0.1));
}

.ios-img-btn:hover {
    filter: drop-shadow(0 10px 25px rgba(139, 92, 246, 0.4)); /* Glow on hover */
    transform: scale(1.02);
}

/* --- 8. SERVICE LIST --- */
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
.tag.gold { background: #fef9c3; color: #854d0e; }

/* --- 9. NAV HEADER --- */
.nav-header {
    display: flex; align-items: center; gap: 15px; margin-bottom: 20px;
    background: white; padding: 10px 15px; border-radius: 50px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05); width: fit-content;
}
.back-btn {
    width: 35px; height: 35px; border-radius: 50%; border: none;
    background: #f3f4f6; color: var(--text-main); cursor: pointer;
    transition: 0.2s; font-size: 1rem;
}
.back-btn:hover { background: var(--primary); color: white; }

/* =========================================
   --- 10. MODAL & ORDER STYLES ---
   ========================================= */
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
.form-input { width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 14px; font-size: 1rem; outline: none; transition: 0.2s; background: #fff; color: #0f172a; font-family: var(--font); }
.form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }
.paste-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: #eff6ff; color: var(--primary); border: none; padding: 6px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
.paste-btn:hover { background: var(--primary); color: #fff; }

.btn-submit { width: 100%; padding: 16px; background: var(--primary); color: #fff; font-weight: 800; font-size: 1rem; border: none; border-radius: 14px; cursor: pointer; margin-top: 15px; box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3); transition: 0.3s; }
.btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(139, 92, 246, 0.4); }

/* Drip Feed Styles */
.drip-toggle { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; }
.drip-area { display: none; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; }
.drip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

/* AI Button in Modal */
.btn-ai-gen { border:1px solid var(--primary); color:var(--primary); padding:4px 10px; background: #fff; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
.btn-ai-gen:hover { background: var(--primary); color: #fff; }

@keyframes zoomIn { from { transform:scale(0.95); opacity:0; } to { transform:scale(1); opacity:1; } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
</style>

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
                    $icon = (!empty($known_apps[$appName]['icon'])) ? $known_apps[$appName]['icon'] : 'smm.png';
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
            <h3 style="margin:0; font-size:1.1rem;" id="subcat-title">App Name</h3>
        </div>
        
        <h4 style="color:white; margin-bottom:12px; font-weight:700;">Choose Category</h4>
        
        <div class="ios-vertical-list" id="subcat-image-container">
            </div>
    </div>

    <div id="view-services" class="view-section">
        <div class="nav-header" style="margin-bottom:15px;">
            <button class="back-btn" onclick="goBack()"><i class="fa fa-arrow-left"></i></button>
            <div>
                <h3 style="margin:0; font-size:1rem;" id="svc-list-title">Category</h3>
                <span style="font-size:0.75rem; opacity:0.7">Select a package</span>
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
                        <button type="button" class="btn-ai-gen" onclick="generateAiComments()" id="ai-btn-txt">
                            ✨ Generate with AI
                        </button>
                    </div>
                    <textarea name="comments" id="m-com" class="form-input" rows="4" placeholder="Nice post!"></textarea>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0;">
                    <span style="color:#64748b;font-weight:600;">Total Charge</span>
                    <span id="m-total" style="color:var(--primary);font-size:1.4rem;font-weight:800;">0.00</span>
                </div>

                <button type="submit" class="btn-submit">CONFIRM ORDER</button>
            </form>
        </div>
    </div>
</div>

<script>
// --- STATE MANAGEMENT ---
let historyStack = ['home']; 
let currentApp = null;
let currentCat = null;
let activeServiceList = [];
let currentFilterType = 'all';

// --- NAVIGATION ---
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

// --- OPEN APP (UPDATED: VERTICAL IOS STYLE) ---
function goToApp(appName) {
    currentApp = appName;
    historyStack.push('subcats');
    document.getElementById('subcat-title').innerText = appName;
    
    const container = document.getElementById('subcat-image-container');
    container.innerHTML = ''; // Clear old content
    
    // Default filters if not found
    const filters = window.appsData[appName]?.filters || ['Followers','Likes'];
    
    filters.forEach((filter, index) => {
        // --- IMAGE NAME LOGIC ---
        // Format: AppName-FilterName.png (e.g. Instagram-Followers.png)
        let safeApp = appName.replace(/\s/g, ''); 
        let safeFilter = filter.replace(/\s/g, '');
        let imgName = `${safeApp}-${safeFilter}.png`;
        let imgPath = `../assets/img/icons/${imgName}`;

        // Create the Image (No Div Wrapper, No Text)
        let img = document.createElement('img');
        img.src = imgPath;
        img.className = 'ios-img-btn'; // Use the new Vertical IOS style
        img.alt = filter;
        
        // Add Staggered Animation Delay for iOS feel
        // Each item appears 0.08s after the previous one
        img.style.animation = `iosSlideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards`;
        img.style.animationDelay = `${index * 0.08}s`; 

        img.onerror = function() { 
            console.warn("Missing Image:", imgPath);
        };

        img.onclick = () => goToServices(filter);
        
        // Append directly to vertical container
        container.appendChild(img);
    });
    
    navigateTo('view-subcats');
}

// --- LOAD SERVICES ---
function goToServices(filterName) {
    currentCat = filterName;
    historyStack.push('services');
    document.getElementById('svc-list-title').innerText = `${currentApp} ${filterName}`;
    
    let rawServices = window.appsData[currentApp].services;
    activeServiceList = rawServices.map(s => window.svcData[s.id]).filter(s => {
        let cat = s.cat.toLowerCase();
        let f = filterName.toLowerCase();
        if(f === 'followers' && (cat.includes('follow') || cat.includes('sub'))) return true;
        if(cat.includes(f)) return true;
        return false;
    });

    if(activeServiceList.length === 0) {
        activeServiceList = rawServices.map(s => window.svcData[s.id]);
    }

    // Reset filters visual
    document.querySelectorAll('.filter-btn').forEach(c => c.classList.remove('active'));
    currentFilterType = 'all';

    renderServices();
    navigateTo('view-services');
}

function applyFilter(element, type) {
    // Check if clicking same filter to toggle off
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

    // UPDATED LOGIC FOR ALL FILTERS
    if(currentFilterType === 'cheapest') {
        list.sort((a, b) => a.rate - b.rate);
    } else if (currentFilterType === 'high_rate') {
        list.sort((a, b) => b.rate - a.rate);
    } else if (currentFilterType === 'high_quality') {
        list = list.filter(s => s.name.toLowerCase().includes('hq') || s.name.toLowerCase().includes('vip'));
    } else if (currentFilterType === 'instant') {
        list = list.filter(s => s.name.toLowerCase().includes('instant') || s.cat.toLowerCase().includes('instant'));
    } else if (currentFilterType === 'non_drop') {
        list = list.filter(s => s.name.toLowerCase().includes('non-drop'));
    } else if (currentFilterType === 'refill') {
        list = list.filter(s => s.refill === true);
    } else if (currentFilterType === 'no_refill') {
        list = list.filter(s => s.refill === false);
    }

    if(list.length === 0) {
        noMsg.style.display = 'block';
    } else {
        noMsg.style.display = 'none';
        list.forEach(s => {
            let price = (s.rate * window.currConfig.rate).toFixed(2);
            let tagsHtml = s.refill ? `<span class="tag green">♻️ Refill</span>` : `<span class="tag red">🚫 No Refill</span>`;

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
                </div>
            `;
            container.appendChild(item);
        });
    }
}

// =========================================
// --- MODAL & ORDER LOGIC ---
// =========================================
let currSvc = null;

function openModal(id) {
    let s = window.svcData[id]; if(!s) return;
    currSvc = s;
    document.getElementById('m-id').value = id; 
    document.getElementById('min-max').innerText = `Min: ${s.min} | Max: ${s.max}`;
    document.getElementById('m-qty').setAttribute('min', s.min);
    document.getElementById('m-qty').setAttribute('max', s.max);
    
    // HTML-stripped description
    document.getElementById('m-desc').innerText = s.desc.replace(/<[^>]*>?/gm, ''); 
    
    let rC = s.refill ? '#10b981' : '#ef4444';
    let cC = s.cancel ? '#10b981' : '#ef4444';
    document.getElementById('m-stats').innerHTML = `
        <div class="stat-box"><small>Avg Time</small><b>${s.avg}</b></div>
        <div class="stat-box" style="border-bottom:3px solid ${rC}"><small>Refill</small><b style="color:${rC}">${s.refill?'Yes':'No'}</b></div>
        <div class="stat-box" style="border-bottom:3px solid ${cC}"><small>Cancel</small><b style="color:${cC}">${s.cancel?'Yes':'No'}</b></div>
    `;

    document.getElementById('m-qty').value=''; 
    document.getElementById('m-com').value=''; 
    document.getElementById('m-link').value='';
    document.getElementById('m-total').innerText = window.currConfig.sym + ' 0.00';
    document.getElementById('drip-check').checked = false; 
    document.getElementById('drip-fields').style.display = 'none';

    // Logic for toggling fields
    if(s.type === 'Package') {
        document.getElementById('grp-qty').style.display='none'; 
        document.getElementById('grp-com').style.display='none'; 
        document.getElementById('grp-drip').style.display = 'none'; 
        document.getElementById('m-qty').value = '1'; 
        document.getElementById('m-qty').readOnly = true; 
        updatePrice(1);
    } else if(s.is_comment || s.type === 'Custom Comments') { 
        document.getElementById('grp-qty').style.display='none'; 
        document.getElementById('grp-com').style.display='block'; 
        document.getElementById('m-qty').readOnly=true; 
        document.getElementById('grp-drip').style.display = 'none'; 
    } else { 
        document.getElementById('grp-qty').style.display='block'; 
        document.getElementById('grp-com').style.display='none'; 
        document.getElementById('m-qty').readOnly=false; 
        document.getElementById('grp-drip').style.display = s.drip ? 'block' : 'none';
    }

    document.getElementById('order-modal').classList.add('active');
    if(s.type !== 'Package') updatePrice(0);
}

function closeModal() { 
    document.getElementById('order-modal').classList.remove('active'); 
}

function toggleDrip() {
    const on = document.getElementById('drip-check').checked;
    document.getElementById('drip-fields').style.display = on ? 'grid' : 'none';
    document.getElementById('drip-val').value = on ? '1' : '0';
    updatePrice(parseInt(document.getElementById('m-qty').value)||0);
}

function updatePrice(qty) {
    if(!currSvc) return;
    let multiplier = (document.getElementById('drip-check').checked) ? (parseInt(document.getElementById('m-runs').value)||0) : 1;
    if(multiplier < 1) multiplier = 1;
    let totalQty = qty * multiplier;
    
    let p = (currSvc.type === 'Package') ? currSvc.rate : (totalQty/1000)*currSvc.rate;
    if(window.currConfig.code!=='PKR') p*=window.currConfig.rate;
    
    document.getElementById('m-total').innerText = window.currConfig.sym + ' ' + p.toFixed(2);
}

async function pasteLink() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('m-link').value = text;
    } catch(err) { alert('Clipboard permission denied'); }
}

// EVENTS
document.getElementById('m-qty').addEventListener('input', function(){ updatePrice(parseInt(this.value)||0) });
document.getElementById('m-runs').addEventListener('input', function(){ updatePrice(parseInt(document.getElementById('m-qty').value)||0) });
document.getElementById('m-com').addEventListener('input', function(){ 
    let c=this.value.split('\n').filter(x=>x.trim()!=='').length; 
    document.getElementById('m-qty').value=c; 
    updatePrice(c); 
});

// SUBMIT ORDER
document.getElementById('order-form').addEventListener('submit', function(e) {
    e.preventDefault();
    if(!currSvc) return;
    
    let qty = parseInt(document.getElementById('m-qty').value) || 0;
    if(currSvc.type !== 'Package') {
        if(qty < currSvc.min) { alert(`Min qty: ${currSvc.min}`); return; }
        if(qty > currSvc.max) { alert(`Max qty: ${currSvc.max}`); return; }
    }

    const formData = new FormData(this);
    
    // AJAX Submission
    fetch('smm_order_action.php', { method:'POST', body: formData })
    .then(r=>r.text())
    .then(res => {
        closeModal();
        Swal.fire({
            title: 'Order Received!',
            text: 'Your order is being processed.',
            icon: 'success',
            confirmButtonColor: '#8b5cf6'
        });
    }).catch(err => {
        alert("Error processing order");
    });
});

// AI COMMENT LOGIC
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
                document.getElementById('m-com').dispatchEvent(new Event('input')); // Trigger resize/price update
            } else { alert(d.message); }
            btnText.innerText = originalText;
        })
        .catch(e => { btnText.innerText = originalText; });
    }
}

// SEARCH
document.getElementById('global-search').addEventListener('input', function(e){
    let q=e.target.value.toLowerCase();
    if(q.length > 2) {
        historyStack.push('services');
        navigateTo('view-services');
        document.getElementById('svc-list-title').innerText = "Search Results";
        document.querySelector('.filter-wrapper').style.display = 'none';
        
        activeServiceList = Object.values(window.svcData).filter(s => 
            s.name.toLowerCase().includes(q) || s.cat.toLowerCase().includes(q)
        );
        renderServices();
    } else if (q.length === 0 && historyStack.includes('services')) {
        document.querySelector('.filter-wrapper').style.display = 'flex';
        goBack();
    }
});

VanillaTilt.init(document.querySelectorAll(".app-card"), { max: 15, speed: 400, glare: true, "max-glare": 0.2 });
</script>
<?php include '_smm_footer.php'; ?>
