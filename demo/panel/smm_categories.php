<?php
// ================================================================
// 1. AJAX HANDLER (EXECUTES BEFORE HEADER TO PREVENT HTML LEAK)
// ================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    
    // STOP HTML/WARNING OUTPUT
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    // MANUAL CORE LOAD
    $paths = [
        '../includes/db.php',
        '../includes/helpers.php',
        '../includes/iron_core.php',
        '../includes/smm_api.class.php'
    ];
    
    foreach ($paths as $p) {
        if (file_exists($p)) require_once $p;
    }

    // AUTH CHECK
    if (function_exists('activateIronCore')) activateIronCore();
    if (function_exists('isAdmin') && !isAdmin()) {
        echo json_encode(['error' => 'Authentication Failed']);
        exit;
    }

    // HELPER: ROBUST CURL (FIREWALL BYPASS)
    function robustApiRequest($url, $postData) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Bypass SSL & Firewall
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['status' => 'error', 'message' => "CURL Error: $err"];
        return ['status' => 'success', 'data' => $response];
    }

    // ACTION: FETCH DATA (Gets ALL services to process locally for speed)
    if ($_POST['ajax_action'] == 'get_provider_data') {
        global $db;
        $pid = (int)$_POST['provider_id'];
        
        $stmt = $db->prepare("SELECT * FROM smm_providers WHERE id=?");
        $stmt->execute([$pid]);
        $prov = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$prov) { echo json_encode(['error' => 'Provider Not Found']); exit; }

        $res = robustApiRequest($prov['api_url'], ['key' => $prov['api_key'], 'action' => 'services']);
        
        if ($res['status'] === 'error') {
            echo json_encode(['error' => $res['message']]); 
            exit;
        }

        // Clean Response
        $cleanData = trim($res['data']);
        $services = json_decode($cleanData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $preview = htmlspecialchars(substr($cleanData, 0, 100));
            echo json_encode(['error' => "Provider returned Invalid JSON. Preview: $preview"]);
            exit;
        }

        // Extract Categories
        $categories = [];
        if (is_array($services)) {
            foreach ($services as $s) {
                $cat = isset($s['category']) ? trim($s['category']) : 'Uncategorized';
                if (!in_array($cat, $categories)) $categories[] = $cat;
            }
        }
        
        // Return BOTH categories and full service list (Client side filtering is faster)
        echo json_encode([
            'status' => 'success', 
            'categories' => $categories,
            'services' => $services // Sending all services to JS
        ]);
        exit;
    }
    
    // Safety Exit
    exit;
}

// ================================================================
// 2. STANDARD PAGE LOAD (HTML STARTS HERE)
// ================================================================
include '_header.php';

$error = '';
$success = '';
$cats = [];
$providers = [];

// --- FORM ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Helper: Upload Icon
    function uploadIcon($file) {
        if (!empty($file['name'])) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','svg'];
            if(!in_array($ext, $allowed)) return null;
            $name = "cat_" . uniqid() . "." . $ext;
            if (move_uploaded_file($file['tmp_name'], "../assets/uploads/" . $name)) return $name;
        }
        return null;
    }

    // SAVE CATEGORY (CREATE / EDIT)
    if ($_POST['action'] == 'save') {
        $main_app_select = $_POST['main_app_select'] ?? '';
        $main_app_new = trim($_POST['main_app_new'] ?? '');
        $main_app = ($main_app_select === 'NEW' && !empty($main_app_new)) ? $main_app_new : $main_app_select;
        
        $sub_name = trim($_POST['sub_cat_name']);
        $keywords = trim($_POST['keywords']);
        $sort = (int)$_POST['sort_order'];
        $id = (int)$_POST['id'];
        
        $subIcon = uploadIcon($_FILES['sub_icon']);
        $mainIcon = uploadIcon($_FILES['main_icon']);

        // Inherit Icon Logic
        if (!$mainIcon && $main_app_select !== 'NEW') {
            $stmtIcon = $db->prepare("SELECT main_cat_icon FROM smm_sub_categories WHERE main_app=? LIMIT 1");
            $stmtIcon->execute([$main_app]);
            $existingIcon = $stmtIcon->fetchColumn();
            if ($existingIcon) $mainIcon = $existingIcon;
        }

        // Sync Icon Logic
        if ($mainIcon) {
            $db->prepare("UPDATE smm_sub_categories SET main_cat_icon=? WHERE main_app=?")->execute([$mainIcon, $main_app]);
        }

        if ($id > 0) {
            // Update
            $sql = "UPDATE smm_sub_categories SET main_app=?, sub_cat_name=?, keywords=?, sort_order=?";
            $params = [$main_app, $sub_name, $keywords, $sort];
            
            if ($subIcon) { $sql .= ", sub_cat_icon=?"; $params[] = $subIcon; }
            if ($mainIcon) { $sql .= ", main_cat_icon=?"; $params[] = $mainIcon; }
            
            $sql .= " WHERE id=?"; $params[] = $id;
            $db->prepare($sql)->execute($params);
            
            $success = "Category Updated Successfully! 🚀";
        } else {
            // Insert
            if(!$subIcon) $subIcon = 'default.png';
            $stmt = $db->prepare("INSERT INTO smm_sub_categories (main_app, sub_cat_name, sub_cat_icon, main_cat_icon, keywords, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$main_app, $sub_name, $subIcon, $mainIcon, $keywords, $sort]);
            $id = $db->lastInsertId(); // Capture ID for immediate import
            $success = "New Category Created! 🔥 You can now import services.";
        }
    }

    // DELETE CATEGORY
    if ($_POST['action'] == 'delete') {
        $id = (int)$_POST['id'];
        $cat = $db->query("SELECT * FROM smm_sub_categories WHERE id=$id")->fetch();
        if($cat) {
            $fullCatName = $cat['main_app'] . ' - ' . $cat['sub_cat_name'];
            $db->prepare("DELETE FROM smm_services WHERE category=?")->execute([$fullCatName]);
            $db->prepare("DELETE FROM smm_sub_categories WHERE id=?")->execute([$id]);
            $success = "Category & Linked Services Deleted! 🗑️";
        }
    }

    // IMPORT SERVICES
    if ($_POST['action'] == 'import_selected') {
        $count = 0;
        // 🚀 LIVE PROFIT CAPTURE
        $profit = (float)$_POST['profit_percent']; 
        $target_cat_id = (int)$_POST['target_cat_id'];
        $provider_id = (int)$_POST['provider_id'];
        $last_error = "";
        
        // 🚀 SMART CURRENCY FETCH
        try {
            $stmt_rate = $db->prepare("SELECT setting_value FROM settings WHERE setting_key IN ('currency_conversion_rate', 'exchange_rate', 'currency_rate', 'usd_rate') AND setting_value > 0 LIMIT 1");
            $stmt_rate->execute();
            $db_rate = $stmt_rate->fetchColumn();
            $usd_rate = ($db_rate > 0) ? (float)$db_rate : 1.00;
        } catch (Exception $e) {
            $usd_rate = 1.00; 
        }
        
        // Critical Check: Ensure Target Category Exists
        $localCat = $db->query("SELECT main_app, sub_cat_name FROM smm_sub_categories WHERE id=$target_cat_id")->fetch();
        
        if ($localCat) {
            $finalCatName = $localCat['main_app'] . ' - ' . $localCat['sub_cat_name'];
            $finalCatName = substr($finalCatName, 0, 250);
            
            if (!empty($_POST['selected_services'])) {
                $sql = "INSERT INTO smm_services 
                        (provider_id, service_id, name, category, base_price, service_rate, min, max, avg_time, description, has_refill, has_cancel, service_type, dripfeed, is_active, manually_deleted) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 0)
                        ON DUPLICATE KEY UPDATE 
                        name = VALUES(name),
                        category = VALUES(category),
                        base_price = VALUES(base_price),
                        service_rate = VALUES(service_rate),
                        min = VALUES(min),
                        max = VALUES(max),
                        service_type = VALUES(service_type),
                        is_active = 1,
                        manually_deleted = 0";
                
                $stmt = $db->prepare($sql);
                
                foreach ($_POST['selected_services'] as $svcRaw) {
                    $jsonStr = rawurldecode(base64_decode($svcRaw));
                    $s = json_decode($jsonStr, true);
                    
                    if($s) {
                        // 🚀 DYNAMIC CURRENCY & LIVE PROFIT BOX % APPLIED HERE
                        $providerRateUSD = (float)$s['rate'];
                        $providerRateConverted = $providerRateUSD * $usd_rate; // Cost base in Website Currency
                        $sellingRateConverted = $providerRateConverted * (1 + ($profit / 100)); // Final Selling Price
                        
                        // Safety Checks
                        $sName = substr($s['name'], 0, 250);
                        $sType = !empty($s['type']) ? ucfirst($s['type']) : 'Default';
                        
                        try {
                            $stmt->execute([
                                $provider_id, 
                                $s['service'], 
                                $sName, 
                                $finalCatName, 
                                $providerRateConverted, 
                                $sellingRateConverted,  
                                $s['min'], 
                                $s['max'], 
                                'Instant',
                                '',
                                0,
                                0,
                                $sType
                            ]);
                            $count++;
                        } catch(Exception $e) { 
                            $last_error = $e->getMessage();
                        }
                    }
                }
                
                if($count > 0) {
                    $success = "Successfully Synced/Imported $count Services at 1 USD = $usd_rate Rate! 💰";
                } else {
                    $error = "❌ Import Failed!<br><b>Last Error:</b> " . $last_error;
                }
            }
        } else {
            $error = "Error: Target Category not found. Please save the category first.";
        }
    }
}

// --- FETCH DATA ---
try {
    $providers = $db->query("SELECT * FROM smm_providers")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $providers = []; }

try {
    $cats = $db->query("SELECT * FROM smm_sub_categories ORDER BY main_app ASC, sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $cats = []; }

// 🚀 FETCH GLOBAL PROFIT MARGIN FOR THE INPUT FIELD
$global_profit = 20; 
try {
    $stmt_profit = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'profit_margin'");
    $stmt_profit->execute();
    $fetched_profit = $stmt_profit->fetchColumn();
    if (is_numeric($fetched_profit)) {
        $global_profit = (float)$fetched_profit;
    }
} catch (Exception $e) {}

// 🚀 FETCH GLOBAL EXCHANGE RATE & SYMBOL FOR JAVASCRIPT UI DISPLAY
$js_usd_rate = 1.00;
$js_currency_sym = '₨';
try {
    $stmt_rate_js = $db->prepare("SELECT setting_value FROM settings WHERE setting_key IN ('currency_conversion_rate', 'exchange_rate', 'currency_rate', 'usd_rate') AND setting_value > 0 LIMIT 1");
    $stmt_rate_js->execute();
    $db_rate_js = $stmt_rate_js->fetchColumn();
    
    $js_usd_rate = ($db_rate_js > 0) ? (float)$db_rate_js : 1.00;
} catch (Exception $e) {}

try {
    $stmt_sym = $db->prepare("SELECT setting_value FROM settings WHERE setting_key IN ('currency_symbol', 'currency_code', 'currency') AND setting_value != '' LIMIT 1");
    $stmt_sym->execute();
    $db_sym = $stmt_sym->fetchColumn();
    if($db_sym) $js_currency_sym = $db_sym;
} catch (Exception $e) {}

$builtInApps = [
    'Instagram', 'Facebook', 'TikTok', 'YouTube', 'Twitter', 'Spotify', 
    'Telegram', 'Snapchat', 'LinkedIn', 'Twitch', 'Discord', 'Website Traffic', 
    'Google', 'Threads', 'Pinterest', 'SoundCloud'
];

try {
    $dbApps = $db->query("SELECT DISTINCT main_app FROM smm_sub_categories WHERE main_app != ''")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $dbApps = []; }

$mainApps = array_unique(array_merge($dbApps, $builtInApps));
sort($mainApps);
?>

<style>
    :root { --primary: #6366f1; --glass: rgba(255, 255, 255, 0.95); --border: #e2e8f0; }
    body { background: #f8fafc; font-family: 'Inter', sans-serif; }

    .glass-header {
        background: var(--glass); backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--border); padding: 20px;
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 50;
    }

    .cat-grid { display: grid; gap: 15px; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); padding: 20px; }
    .cat-card {
        background: white; border-radius: 16px; padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid var(--border); transition: 0.3s;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .cat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: var(--primary); }

    .app-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #eef2ff; color: var(--primary);
        padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;
    }

    .keyword-tag { background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; color: #64748b; margin-top: 5px; display: inline-block; }

    .btn { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; }
    .btn-danger { background: #fee2e2; color: #ef4444; }
    .btn-soft { background: #f1f5f9; color: #334155; }

    .search-input {
        padding: 10px 20px; border-radius: 30px; border: 1px solid var(--border);
        width: 250px; outline: none; transition: 0.3s;
    }
    .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
    
    .app-filter {
        padding: 10px; border-radius: 30px; border: 1px solid var(--border);
        outline: none; margin-right: 10px; cursor: pointer;
        min-width: 150px;
    }

    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center;
        z-index: 100; opacity: 0; transition: 0.3s;
    }
    .modal-overlay.active { display: flex; opacity: 1; }
    
    .modal-box {
        background: white; width: 95%; max-width: 700px;
        border-radius: 24px; padding: 30px; max-height: 90vh; overflow-y: auto;
        transform: scale(0.9); transition: 0.3s;
    }
    .modal-overlay.active .modal-box { transform: scale(1); }

    .form-label { font-weight: 700; color: #1e293b; margin-bottom: 5px; display: block; font-size: 0.9rem; }
    .form-control { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); background: #f8fafc; outline: none; }
    .form-control:focus { border-color: var(--primary); background: white; }

    .checkbox-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
        max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; padding: 10px; border-radius: 10px;
    }
    .chk-item {
        display: flex; align-items: center; gap: 8px; font-size: 0.85rem; padding: 5px;
        border-radius: 5px; cursor: pointer; transition: 0.2s;
    }
    .chk-item:hover { background: #f1f5f9; }

    @media (max-width: 768px) {
        .glass-header { flex-direction: column; gap: 15px; align-items: stretch; }
        .search-input { width: 100%; }
        .cat-grid { grid-template-columns: 1fr; }
        .checkbox-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="glass-header">
    <div>
        <h2 style="margin:0; font-size:1.5rem;">📱 Manage Categories</h2>
        <small style="color:#64748b;">Organize Apps, Sub-Cats & Auto-Import</small>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        <select id="appFilter" class="app-filter" onchange="filterCats()">
            <option value="">All Apps</option>
            <?php foreach($mainApps as $app): ?>
                <option value="<?= htmlspecialchars(strtolower($app)) ?>"><?= htmlspecialchars($app) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search..." onkeyup="filterCats()">
        <button onclick="openModal('new')" class="btn btn-primary">
            <i class="fa-solid fa-plus-circle"></i> <span class="desktop-only">Add New</span>
        </button>
    </div>
</div>

<?php if($success): ?>
    <div style="margin:20px; padding:15px; background:#d1fae5; color:#065f46; border-radius:12px; font-weight:600;">
        <?= $success ?>
    </div>
<?php endif; ?>
<?php if($error): ?>
    <div style="margin:20px; padding:15px; background:#fee2e2; color:#b91c1c; border-radius:12px; font-weight:600;">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="cat-grid" id="catGrid">
    <?php if(empty($cats)): ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #94a3b8;">
            <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 10px;"></i><br>
            No categories found. Click <b>"Add New"</b> to create your first category!
        </div>
    <?php else: ?>
        <?php foreach($cats as $c): 
            $mainIcon = !empty($c['main_cat_icon']) ? "../assets/uploads/".$c['main_cat_icon'] : null;
            $subIcon = !empty($c['sub_cat_icon']) ? "../assets/uploads/".$c['sub_cat_icon'] : "../assets/img/icons/default.png";
            $searchString = strtolower($c['main_app'] . ' ' . $c['sub_cat_name']);
            $appFilter = strtolower($c['main_app']);
        ?>
        <div class="cat-card" data-search="<?= $searchString ?>" data-app="<?= $appFilter ?>">
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div class="app-badge">
                    <?php if($mainIcon): ?><img src="<?= $mainIcon ?>" style="width:20px; height:20px; border-radius:50%;"><?php endif; ?>
                    <?= htmlspecialchars($c['main_app']) ?>
                </div>
                <div style="display:flex; gap:5px;">
                    <button onclick='editCat(<?= json_encode($c) ?>)' class="btn btn-soft" style="padding:5px 10px;"><i class="fa fa-pen"></i></button>
                    <form method="POST" onsubmit="return confirm('⚠️ Delete this category AND all its services?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-danger" style="padding:5px 10px;"><i class="fa fa-trash"></i></button>
                    </form>
                </div>
            </div>

            <div style="text-align:center; margin: 20px 0;">
                <img src="<?= $subIcon ?>" style="width:60px; height:60px; object-fit:contain; border-radius:12px; margin-bottom:10px; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
                <h3 style="margin:0; font-size:1.1rem; color:#1e293b;"><?= htmlspecialchars($c['sub_cat_name']) ?></h3>
                <div class="keyword-tag"><i class="fa fa-key"></i> <?= $c['keywords'] ? $c['keywords'] : 'No Auto-Match' ?></div>
            </div>

            <div style="background:#f8fafc; padding:10px; border-radius:10px; text-align:center; font-size:0.8rem; color:#64748b;">
                <i class="fa fa-arrow-down-short-wide"></i> Sort Order: <b><?= $c['sort_order'] ?></b>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="mainModal">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h2 style="margin:0;" id="modalTitle">Add Category</h2>
            <button onclick="closeModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
            <button onclick="switchTab('details')" id="tab_details" class="btn btn-primary" style="padding:8px 15px;">1. Details</button>
            <button onclick="switchTab('import')" id="tab_import" class="btn btn-soft" style="padding:8px 15px;">2. Import Services</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="detailsForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="inp_id" value="0">
            
            <div id="view_details">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label class="form-label">Main App (Instagram, etc)</label>
                        <select name="main_app_select" id="inp_app_select" class="form-control" onchange="toggleNewApp(this.value)">
                            <option value="">-- Select Existing --</option>
                            <?php foreach($mainApps as $app): ?>
                                <option value="<?= htmlspecialchars($app) ?>"><?= htmlspecialchars($app) ?></option>
                            <?php endforeach; ?>
                            <option value="NEW" style="color:var(--primary); font-weight:bold;">+ CREATE NEW APP</option>
                        </select>
                        <input type="text" name="main_app_new" id="inp_app_new" class="form-control" placeholder="Enter New App Name" style="display:none; margin-top:10px; border-color:var(--primary);">
                    </div>
                    <div>
                        <label class="form-label">Main App Icon (Optional)</label>
                        <input type="file" name="main_icon" class="form-control" style="padding:9px;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label class="form-label">Sub-Category Name</label>
                        <input type="text" name="sub_cat_name" id="inp_sub_name" class="form-control" placeholder="e.g. Likes, Followers" required>
                    </div>
                    <div>
                        <label class="form-label">Sub-Category Icon</label>
                        <input type="file" name="sub_icon" class="form-control" style="padding:9px;">
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <label class="form-label">Auto-Match Keywords (Comma separated)</label>
                    <input type="text" name="keywords" id="inp_keywords" class="form-control" placeholder="e.g. likes, heart, instant">
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="inp_sort" class="form-control" value="0">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;">Save Category</button>
            </div>
        </form>

        <div id="view_import" style="display:none;">
            
            <div id="new_cat_warning" style="display:none; padding:10px; background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; border-radius:10px; margin-bottom:15px; font-size:0.9rem;">
                ⚠️ <b>Wait!</b> You must save this category first before importing services. Switch to "Details" and click Save.
            </div>

            <div style="background:#f0f9ff; padding:15px; border-radius:12px; border:1px dashed #0ea5e9; margin-bottom:20px;">
                <h4 style="margin:0 0 10px 0; color:#0284c7;">Fetch from Provider</h4>
                
                <label class="form-label">1. Choose API Provider</label>
                <select id="imp_provider" class="form-control" onchange="fetchProviderData(this)">
                    <option value="" data-profit="<?= $global_profit ?>">Select Provider...</option>
                    <?php if(!empty($providers)): ?>
                        <?php foreach($providers as $p): 
                            $pName = !empty($p['domain']) ? $p['domain'] : (!empty($p['name']) ? $p['name'] : 'Provider #'.$p['id']);
                            $pProfit = (isset($p['profit_margin']) && $p['profit_margin'] != '') ? $p['profit_margin'] : $global_profit;
                        ?>
                            <option value="<?= $p['id'] ?>" data-profit="<?= htmlspecialchars($pProfit) ?>"><?= htmlspecialchars($pName) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No Providers Found!</option>
                    <?php endif; ?>
                </select>

                <div id="imp_cat_wrapper" style="display:none; margin-top:15px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label class="form-label">2. Select Categories (Multiple)</label>
                        <label style="font-size:0.8rem; color:var(--primary); cursor:pointer;"><input type="checkbox" onchange="toggleAllCats(this)"> Select All</label>
                    </div>
                    
                    <input type="text" placeholder="Filter categories..." onkeyup="filterCheckboxList(this.value)" class="form-control" style="padding:6px 10px; margin-bottom:5px; font-size:0.85rem;">

                    <div id="cat_checkboxes" class="checkbox-grid">
                        </div>
                    
                    <button type="button" onclick="renderFilteredServices()" class="btn btn-soft" style="width:100%; margin-top:10px; border:1px solid var(--primary); color:var(--primary);">
                        <i class="fa fa-filter"></i> Show Services from Selected Categories
                    </button>
                </div>
            </div>

            <form method="POST" id="importForm" onsubmit="return validateImport()">
                <input type="hidden" name="action" value="import_selected">
                <input type="hidden" name="target_cat_id" id="imp_target_id">
                <input type="hidden" name="provider_id" id="imp_provider_id_hidden">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                     <h4 style="margin:0; color:#1e293b;">Available Services</h4>
                     <label style="font-size:0.85rem;"><input type="checkbox" onchange="toggleAllServices(this)"> Select All</label>
                </div>

                <div id="imp_services_list" style="max-height:250px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:15px; background:white;">
                    <div style="padding:20px; text-align:center; color:#94a3b8;">Select a category above to load services...</div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <div style="flex:1;">
                        <label class="form-label">Profit %</label>
                        <input type="number" name="profit_percent" value="<?= $global_profit ?>" class="form-control" onkeyup="if(document.querySelectorAll('.cat-chk:checked').length > 0) renderFilteredServices()" onchange="if(document.querySelectorAll('.cat-chk:checked').length > 0) renderFilteredServices()">
                    </div>
                    <div style="flex:2;">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" id="btn_import_submit" class="btn btn-primary" style="width:100%; justify-content:center;">Import Selected</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
// --- GLOBAL DATA STORE ---
let allProviderServices = []; 
let currentCatId = 0;

// 🚀 INJECT PHP EXCHANGE RATE & SYMBOL INTO JS
let panelCurrencyRate = <?= $js_usd_rate ?>; 
let panelCurrencySym = "<?= htmlspecialchars($js_currency_sym) ?>";

// --- UI INTERACTIONS ---
function openModal(mode) {
    document.getElementById('mainModal').classList.add('active');
    
    allProviderServices = [];
    document.getElementById('cat_checkboxes').innerHTML = '';
    document.getElementById('imp_services_list').innerHTML = '<div style="padding:20px; text-align:center; color:#94a3b8;">Select Provider First</div>';
    document.getElementById('imp_provider').value = "";
    document.getElementById('imp_cat_wrapper').style.display = 'none';

    if(mode === 'new') {
        document.getElementById('modalTitle').innerText = 'New Category';
        document.getElementById('inp_id').value = 0;
        document.getElementById('imp_target_id').value = 0;
        currentCatId = 0;
        
        document.getElementById('detailsForm').reset();
        switchTab('details');
        toggleNewApp('');
    }
}

function closeModal() {
    document.getElementById('mainModal').classList.remove('active');
}

function toggleNewApp(val) {
    document.getElementById('inp_app_new').style.display = (val === 'NEW') ? 'block' : 'none';
}

function switchTab(tab) {
    document.getElementById('view_details').style.display = (tab === 'details') ? 'block' : 'none';
    document.getElementById('view_import').style.display = (tab === 'import') ? 'block' : 'none';
    
    if(tab === 'import') {
        if(currentCatId == 0) {
            document.getElementById('new_cat_warning').style.display = 'block';
            document.getElementById('btn_import_submit').disabled = true;
            document.getElementById('btn_import_submit').style.opacity = '0.5';
            document.getElementById('btn_import_submit').innerText = "Save Category First";
        } else {
            document.getElementById('new_cat_warning').style.display = 'none';
            document.getElementById('btn_import_submit').disabled = false;
            document.getElementById('btn_import_submit').style.opacity = '1';
            document.getElementById('btn_import_submit').innerText = "Import Selected";
        }
    }
    
    if(tab === 'details') {
        document.getElementById('tab_details').classList.replace('btn-soft', 'btn-primary');
        document.getElementById('tab_import').classList.replace('btn-primary', 'btn-soft');
    } else {
        document.getElementById('tab_details').classList.replace('btn-primary', 'btn-soft');
        document.getElementById('tab_import').classList.replace('btn-soft', 'btn-primary');
    }
}

function editCat(data) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Edit: ' + data.sub_cat_name;
    document.getElementById('inp_id').value = data.id;
    document.getElementById('imp_target_id').value = data.id;
    currentCatId = data.id;

    document.getElementById('inp_sub_name').value = data.sub_cat_name;
    document.getElementById('inp_keywords').value = data.keywords;
    document.getElementById('inp_sort').value = data.sort_order;

    let sel = document.getElementById('inp_app_select');
    let found = false;
    for(let i=0; i<sel.options.length; i++) {
        if(sel.options[i].value === data.main_app) { sel.selectedIndex = i; found = true; }
    }
    if(!found) {
        sel.value = 'NEW';
        toggleNewApp('NEW');
        document.getElementById('inp_app_new').value = data.main_app;
    } else {
        toggleNewApp(data.main_app);
    }
}

function validateImport() {
    if(currentCatId == 0) {
        alert("Please go to the 'Details' tab and SAVE the category first!");
        switchTab('details');
        return false;
    }
    return true;
}

function filterCats() {
    let q = document.getElementById('searchInput').value.toLowerCase();
    let app = document.getElementById('appFilter').value.toLowerCase();
    let cards = document.querySelectorAll('.cat-card');
    
    cards.forEach(card => {
        let matchText = card.getAttribute('data-search').includes(q);
        let matchApp = app === '' || card.getAttribute('data-app') === app;
        card.style.display = (matchText && matchApp) ? 'flex' : 'none';
    });
}

// --- NEW ROBUST API LOGIC (FETCH ONCE, FILTER LOCALLY) ---
function fetchProviderData(selectElement) {
    let pid = selectElement.value;
    if(!pid) return;
    
    document.getElementById('imp_provider_id_hidden').value = pid;
    
    let selectedOption = selectElement.options[selectElement.selectedIndex];
    let profit = selectedOption.getAttribute('data-profit');
    if(profit) {
        document.querySelector('input[name="profit_percent"]').value = profit;
    }
    
    let fd = new FormData();
    fd.append('ajax_action', 'get_provider_data');
    fd.append('provider_id', pid);

    document.getElementById('imp_cat_wrapper').style.display = 'none';
    document.getElementById('cat_checkboxes').innerHTML = '<div style="grid-column:1/-1; text-align:center;"><i class="fa fa-spinner fa-spin"></i> Loading Data...</div>';

    fetch('', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(text => {
        try { return JSON.parse(text); } 
        catch (e) { console.error("Bad JSON:", text); throw new Error("Invalid JSON from Server"); }
    })
    .then(d => {
        if(d.error) { alert("API Error: " + d.error); return; }
        
        allProviderServices = d.services || [];
        
        let html = '';
        if(d.categories && d.categories.length > 0) {
            d.categories.forEach(c => {
                let cleanName = c.replace(/[^a-zA-Z0-9]/g, '');
                html += `
                <label class="chk-item" data-name="${c.toLowerCase()}">
                    <input type="checkbox" value="${c}" class="cat-chk">
                    <span>${c}</span>
                </label>`;
            });
            document.getElementById('cat_checkboxes').innerHTML = html;
            document.getElementById('imp_cat_wrapper').style.display = 'block';
        } else {
            alert("No categories found!");
        }
    })
    .catch(e => {
        alert("Error loading data: " + e.message);
    });
}

function filterCheckboxList(query) {
    let q = query.toLowerCase();
    document.querySelectorAll('.chk-item').forEach(item => {
        let name = item.getAttribute('data-name');
        item.style.display = name.includes(q) ? 'flex' : 'none';
    });
}

function toggleAllCats(source) {
    document.querySelectorAll('.cat-chk').forEach(c => {
        if(c.closest('.chk-item').style.display !== 'none') {
            c.checked = source.checked;
        }
    });
}

// 🚀 REVERSE CALCULATED DISPLAY (LIVE PROFIT INTEGRATION)
function renderFilteredServices() {
    let selectedCats = [];
    document.querySelectorAll('.cat-chk:checked').forEach(c => selectedCats.push(c.value));

    if(selectedCats.length === 0) {
        document.getElementById('imp_services_list').innerHTML = '<div style="padding:20px; text-align:center; color:red;">Please select at least one category above!</div>';
        return;
    }

    let profitPercent = parseFloat(document.querySelector('input[name="profit_percent"]').value) || 0;
    let html = '';
    let foundCount = 0;

    allProviderServices.forEach(s => {
        let sCat = s.category ? s.category.trim() : 'Uncategorized';
        
        if(selectedCats.includes(sCat)) {
            let safeJson = btoa(encodeURIComponent(JSON.stringify(s)));
            
            // Raw API USD Rate
            let rawRate = parseFloat(s.rate);
            
            // Base Cost in Website Currency (PKR/INR/etc)
            let baseLocalCost = rawRate * panelCurrencyRate;
            
            // Final Selling Price in Website Currency (with profit applied)
            let finalSellingPriceLocal = baseLocalCost * (1 + (profitPercent / 100));
            
            // Custom Reverse-Calculated USD (Dynamically updates with profit!)
            let customUsdDisplay = finalSellingPriceLocal / panelCurrencyRate;
            
            html += `
            <div style="padding:10px; border-bottom:1px solid #f1f5f9; display:flex; gap:10px; align-items:center;">
                <input type="checkbox" name="selected_services[]" value="${safeJson}" class="svc-chk">
                <div style="width:100%;">
                    <div style="font-weight:600; font-size:0.9rem;">${s.service} - ${s.name}</div>
                    <div style="font-size:0.75rem; color:#64748b; display:flex; justify-content:space-between; margin-top:2px;">
                        <span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px;">${sCat}</span>
                        <span style="font-weight:bold; color:#16a34a;">
                            Selling Price: ${panelCurrencySym}${finalSellingPriceLocal.toFixed(4)} 
                            <span style="color:#64748b; font-weight:normal;">(≈ $${customUsdDisplay.toFixed(4)} USD)</span> 
                            | Min: ${s.min} | Max: ${s.max}
                        </span>
                    </div>
                </div>
            </div>`;
            foundCount++;
        }
    });

    if(foundCount === 0) {
        html = '<div style="padding:20px; text-align:center;">No services found for selected categories.</div>';
    }
    
    document.getElementById('imp_services_list').innerHTML = html;
}

function toggleAllServices(source) {
    document.querySelectorAll('.svc-chk').forEach(c => c.checked = source.checked);
}
</script>

<?php include '_footer.php'; ?>