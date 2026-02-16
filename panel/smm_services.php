<?php
// --- ERROR REPORTING ON (Taaki blank page na aaye) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '_header.php';

// Ensure API class exists
$apiFile = __DIR__ . '/../includes/smm_api.class.php';
if (file_exists($apiFile)) {
    require_once $apiFile;
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = $_GET['success'] ?? '';

// --- SCAN ICONS FOR DROPDOWN ---
$iconDir = __DIR__ . '/../assets/img/icons/';
$iconFiles = [];
if (is_dir($iconDir)) {
    $scanned = scandir($iconDir);
    foreach ($scanned as $file) {
        if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
            $iconFiles[] = $file;
        }
    }
}

// =========================================================
//         🔧 CORE LOGIC (PRESERVED & ENHANCED)
// =========================================================

// --- 0. NEW: BULK ACTIONS HANDLER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['services'] ?? [];
    if (!empty($ids)) {
        $idList = implode(',', array_map('intval', $ids));
        
        if ($_POST['bulk_action'] == 'enable') {
            $db->query("UPDATE smm_services SET is_active=1 WHERE id IN ($idList)");
            $success = "✅ Selected services have been Activated.";
        } elseif ($_POST['bulk_action'] == 'disable') {
            $db->query("UPDATE smm_services SET is_active=0 WHERE id IN ($idList)");
            $success = "⛔ Selected services have been Disabled.";
        } elseif ($_POST['bulk_action'] == 'delete') {
            $db->query("UPDATE smm_services SET manually_deleted=1, is_active=0 WHERE id IN ($idList)");
            $success = "🗑️ Selected services moved to trash.";
        } elseif ($_POST['bulk_action'] == 'price_inc') {
            $percent = (float)$_POST['bulk_amount'];
            if ($percent != 0) {
                $db->query("UPDATE smm_services SET service_rate = service_rate * (1 + $percent/100) WHERE id IN ($idList)");
                $success = "💲 Prices updated by {$percent}% for selected services.";
            }
        }
    }
}

// --- 1. DELETE ACTIONS ---
if ($action == 'delete_all' && isAdmin()) {
    try {
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->query("TRUNCATE TABLE smm_services");
        $db->query("TRUNCATE TABLE smm_categories");
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        $success = "🗑️ All services & categories wiped successfully.";
        echo "<script>window.location.href='smm_services.php?success=" . urlencode($success) . "';</script>";
        exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}

if ($action == 'delete_service' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE smm_services SET is_active=0, manually_deleted=1 WHERE id=?")->execute([$id]);
    $success = "Service removed.";
}

if ($action == 'delete_category' && isset($_GET['cat'])) {
    $cat = urldecode($_GET['cat']);
    $db->prepare("UPDATE smm_services SET is_active=0, manually_deleted=1 WHERE category=?")->execute([$cat]);
    $success = "Category removed.";
}

// --- 2. ADD MANUAL SERVICE (FIXED SQL ERROR & FEATURES) ---
if ($action == 'add_manual_save' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = $_POST['name'];
        $type = $_POST['service_type'];
        $rate = (float)$_POST['rate'];
        $min = (int)$_POST['min'];
        $max = (int)$_POST['max'];
        $desc = $_POST['desc'];
        
        // New Fields
        $refill = (int)$_POST['refill'];
        $cancel = (int)$_POST['cancel'];
        $avg_time = $_POST['avg_time'] ?: 'Instant';
        
        // Category Logic (Modified to strictly use Existing Sub-Cats)
        $category = $_POST['existing_category'] ?? '';
        $cat_icon = $_POST['cat_icon'] ?? ''; 

        if (empty($category)) throw new Exception("Category is required. Please select one.");

        // 1. Handle Provider (Fix FK Constraint)
        $stmt = $db->prepare("SELECT id FROM smm_providers WHERE api_url = 'manual_internal' LIMIT 1");
        $stmt->execute();
        $provider_id = $stmt->fetchColumn();

        if (!$provider_id) {
            $db->prepare("INSERT INTO smm_providers (name, api_url, api_key, profit_margin, is_active) VALUES (?, ?, ?, 0, 1)")
               ->execute(['Manual Service', 'manual_internal', 'manual_key']);
            $provider_id = $db->lastInsertId();
        }

        // 2. Handle Category (Insert into flat table if needed for compatibility)
        $chkCat = $db->prepare("SELECT id FROM smm_categories WHERE name = ?");
        $chkCat->execute([$category]);
        $catExist = $chkCat->fetchColumn();

        if ($catExist) {
            if (!empty($cat_icon)) {
                $db->prepare("UPDATE smm_categories SET icon_filename = ? WHERE id = ?")->execute([$cat_icon, $catExist]);
            }
        } else {
            $db->prepare("INSERT INTO smm_categories (name, icon_filename, is_active) VALUES (?, ?, 1)")
               ->execute([$category, $cat_icon]);
        }
        
        // 3. Insert Service
        $manual_sid = time(); 
        
        $sql = "INSERT INTO smm_services 
                (provider_id, service_id, name, category, base_price, service_rate, min, max, avg_time, description, has_refill, has_cancel, service_type, dripfeed, is_active, manually_deleted) 
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 0)";
                
        $db->prepare($sql)->execute([$provider_id, $manual_sid, $name, $category, $rate, $min, $max, $avg_time, $desc, $refill, $cancel, $type]);
        
        $success = "✅ Manual Service Created Successfully!";
        echo "<script>window.location.href='smm_services.php?success=" . urlencode($success) . "';</script>";
        exit;
        
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// --- 3. IMPORT PROCESS (STEP 3: CONFIRM & INSERT) ---
if ($action == 'import_final' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $provider_id = $_POST['provider_id'];
        $selection = $_POST['services'] ?? []; 
        
        if (empty($selection)) {
            $error = "No services selected for import.";
        } else {
            $names = $_POST['name'];
            $cats = $_POST['category'];
            $rates = $_POST['rate']; 
            $profits = $_POST['profit']; 
            $mins = $_POST['min'];
            $maxs = $_POST['max'];
            $types = $_POST['type'];
            
            $usd_rate = (float)($GLOBALS['settings']['currency_conversion_rate'] ?? 280.00);
            $cnt = 0;
            
            $db->beginTransaction();
            
            foreach ($selection as $sid) {
                // Calculation
                $base_pkr = (float)$rates[$sid] * $usd_rate;
                $profit_margin = (float)$profits[$sid];
                $selling_price = $base_pkr * (1 + ($profit_margin / 100));
                
                $name = $names[$sid];
                $category = $cats[$sid];
                $min = $mins[$sid];
                $max = $maxs[$sid];
                $type = $types[$sid];
                
                $db->prepare("INSERT IGNORE INTO smm_categories (name, is_active) VALUES (?, 1)")->execute([$category]);
                
                $check = $db->prepare("SELECT id FROM smm_services WHERE provider_id=? AND service_id=?");
                $check->execute([$provider_id, $sid]);
                $exists = $check->fetchColumn();
                
                if ($exists) {
                    $sql = "UPDATE smm_services SET name=?, category=?, base_price=?, service_rate=?, min=?, max=?, service_type=?, is_active=1, manually_deleted=0 WHERE id=?";
                    $db->prepare($sql)->execute([$name, $category, $base_pkr, $selling_price, $min, $max, $type, $exists]);
                } else {
                    $sql = "INSERT INTO smm_services (provider_id, service_id, name, category, base_price, service_rate, min, max, avg_time, description, has_refill, has_cancel, service_type, dripfeed, is_active, manually_deleted) VALUES (?,?,?,?,?,?,?,?,'N/A','',0,0,?,0,1,0)";
                    $db->prepare($sql)->execute([$provider_id, $sid, $name, $category, $base_pkr, $selling_price, $min, $max, $type]);
                }
                $cnt++;
            }
            
            $db->commit();
            $success = "🚀 Imported $cnt Services Successfully!";
            echo "<script>window.location.href='smm_services.php?success=" . urlencode($success) . "';</script>";
            exit;
        }
    } catch (Exception $e) { 
        if($db->inTransaction()) $db->rollBack(); 
        $error = $e->getMessage(); 
    }
}

// --- FETCH DATA FOR LIST ---
$show = $_GET['show'] ?? 'active';
$where = "WHERE s.manually_deleted = 0"; 
if ($show == 'active') $where .= " AND s.is_active = 1";
if ($show == 'disabled') $where .= " AND s.is_active = 0";

$search = $_GET['search'] ?? '';
if ($search) $where .= " AND (s.name LIKE '%$search%' OR s.category LIKE '%$search%')";

// Filter by Provider
$prov_filter = $_GET['provider'] ?? '';
if ($prov_filter) $where .= " AND s.provider_id = " . intval($prov_filter);

// Join to get provider name
try {
    // Check if sort_order exists, otherwise fallback to ID
    $sql = "SELECT s.*, p.name as provider_name FROM smm_services s LEFT JOIN smm_providers p ON s.provider_id = p.id $where ORDER BY s.category ASC, s.name ASC";
    
    $stmt = $db->query($sql);
    if($stmt) {
        $services = $stmt->fetchAll();
    } else {
        $services = [];
        $error = "Database Error: " . implode(" ", $db->errorInfo());
    }
} catch (Exception $e) {
    $services = [];
    $error = "DB Error: " . $e->getMessage();
}

$grouped = [];
if (!empty($services)) {
    foreach($services as $s) $grouped[$s['category']][] = $s;
}

// Count stats
$total_svc = $db->query("SELECT COUNT(*) FROM smm_services WHERE manually_deleted=0")->fetchColumn();
$active_svc = $db->query("SELECT COUNT(*) FROM smm_services WHERE is_active=1 AND manually_deleted=0")->fetchColumn();
$disabled_svc = $total_svc - $active_svc;

// --- UPDATED: Fetch Categories from smm_sub_categories table ---
$local_cats = [];
try {
    // Try to fetch properly structured sub-categories
    $stmtCats = $db->query("SELECT main_app, sub_cat_name FROM smm_sub_categories ORDER BY main_app ASC, sort_order ASC");
    if ($stmtCats) {
        while ($row = $stmtCats->fetch(PDO::FETCH_ASSOC)) {
            // Format: Instagram - Likes
            $local_cats[] = $row['main_app'] . ' - ' . $row['sub_cat_name'];
        }
    }
} catch (Exception $e) {
    // Fallback if table doesn't exist (Unlikely in your system)
    $local_cats = $db->query("SELECT name FROM smm_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch Providers
$local_providers = $db->query("SELECT * FROM smm_providers WHERE is_active=1")->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=San+Francisco+Pro+Display:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --ios-bg: #F5F5F7;
        --ios-card: #FFFFFF;
        --ios-text: #1D1D1F;
        --ios-text-sec: #86868B;
        --ios-blue: #0071E3;
        --ios-red: #FF3B30;
        --ios-green: #34C759;
        --ios-orange: #FF9500;
        --ios-border: #D2D2D7;
        --ios-input: #F5F5F7;
        --glass-bg: rgba(255, 255, 255, 0.85);
        --shadow-sm: 0 4px 12px rgba(0,0,0,0.04);
        --shadow-lg: 0 20px 50px rgba(0,0,0,0.15);
    }
    
    body { 
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif; 
        background-color: var(--ios-bg); 
        color: var(--ios-text); 
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden; 
    }
    
    /* UTILS & BUTTONS */
    .ios-btn { 
        padding: 10px 18px; border-radius: 99px; font-weight: 500; font-size: 13px; cursor: pointer; border: none; transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1); display: inline-flex; align-items: center; gap: 6px; text-decoration: none; white-space: nowrap;
    }
    .ios-btn:active { transform: scale(0.96); opacity: 0.9; }
    .btn-blue { background: var(--ios-blue); color: white; box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3); }
    .btn-red { background: var(--ios-red); color: white; box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3); }
    .btn-green { background: var(--ios-green); color: white; box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3); }
    .btn-light { background: #E8E8ED; color: var(--ios-text); }
    .btn-light:hover { background: #D2D2D7; }

    .ios-input {
        width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid transparent; background: var(--ios-input); font-size: 14px; color: var(--ios-text); transition: 0.2s ease;
    }
    .ios-input:focus { background: white; border-color: var(--ios-blue); box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15); outline: none; }

    .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
    .bg-green-soft { background: rgba(52,199,89,0.12); color: var(--ios-green); }
    .bg-red-soft { background: rgba(255,59,48,0.12); color: var(--ios-red); }
    .bg-blue-soft { background: rgba(0,113,227,0.12); color: var(--ios-blue); }
    .bg-orange-soft { background: rgba(255,149,0,0.12); color: var(--ios-orange); }

    /* STATS HEADER */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--ios-card); border-radius: 20px; padding: 24px; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 18px; transition: transform 0.2s; border: 1px solid rgba(0,0,0,0.02); }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
    .stat-icon { width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    
    /* TABLE RESPONSIVE WRAPPER */
    .table-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch; 
        border-radius: 0 0 20px 20px;
    }

    .service-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; min-width: 800px; }
    .service-table th { text-align: left; padding: 16px; color: var(--ios-text-sec); font-weight: 600; border-bottom: 1px solid var(--ios-border); background: #FAFAFA; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 10; backdrop-filter: blur(10px); background: rgba(250,250,250,0.9); }
    .service-table td { padding: 16px; background: white; border-bottom: 1px solid #F5F5F7; vertical-align: middle; }
    .service-table tr:last-child td { border-bottom: none; }
    .service-table tr:hover td { background: #F5F5F7; }

    /* --- 🍎 IMPROVED MODAL CSS (SCROLL FIX) --- */
    .ios-modal-overlay { 
        position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); z-index: 9999; 
        display: none; align-items: center; justify-content: center; opacity: 0; transition: all 0.3s ease; 
    }
    .ios-modal-overlay.active { opacity: 1; }
    
    .ios-modal { 
        background: var(--ios-card); 
        width: 600px; 
        max-width: 95%; 
        max-height: 85vh; /* 🛑 Limits Height */
        border-radius: 24px; 
        box-shadow: var(--shadow-lg); 
        transform: scale(0.92); 
        transition: 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); 
        display: flex; /* 🛠 Flexbox Layout for scrolling */
        flex-direction: column; 
        overflow: hidden; /* Important for border radius */
    }
    
    .ios-modal-overlay.active .ios-modal { transform: scale(1); }
    
    /* Fixed Header */
    .modal-head { 
        padding: 20px 28px; border-bottom: 1px solid var(--ios-border); 
        display: flex; justify-content: space-between; align-items: center; 
        background: rgba(255,255,255,0.8); backdrop-filter: blur(20px);
        flex-shrink: 0; /* Prevents shrinking */
    }
    
    /* Scrollable Body */
    .modal-body { 
        padding: 28px; 
        overflow-y: auto; /* ✅ Allows Scrolling Here */
        flex-grow: 1; /* Takes remaining space */
        background: #fff;
    }
    
    /* Fixed Footer */
    .modal-foot { 
        padding: 20px 28px; border-top: 1px solid var(--ios-border); background: #F9F9F9; 
        text-align: right; 
        flex-shrink: 0; /* Prevents shrinking */
    }

    /* TEMPLATE TAGS */
    .template-tag { 
        display: inline-flex; align-items: center; padding: 6px 12px; background: #F2F2F7; border-radius: 99px; font-size: 11px; margin-right: 6px; margin-bottom: 8px; cursor: pointer; font-weight: 600; color: var(--ios-text); transition: 0.2s; border: 1px solid transparent;
    }
    .template-tag:hover { background: var(--ios-blue); color: white; border-color: var(--ios-blue); transform: translateY(-1px); }
    .template-tag i { margin-right: 6px; }

    /* ID COPY BOX */
    .id-box { 
        font-family: 'SF Mono', 'Courier New', monospace; background: #F5F5F7; padding: 6px 10px; border-radius: 8px; cursor: pointer; font-weight: 600; color: #333; display: inline-block; border: 1px solid transparent; font-size: 12px;
    }
    .id-box:hover { background: #E8E8ED; border-color: #D2D2D7; }
    
    /* MOBILE TWEAKS */
    @media (max-width: 768px) {
        .page-title-bar { flex-direction: column; align-items: flex-start; gap: 10px; }
        .controls-panel { flex-direction: column; gap: 15px; }
        .controls-panel > div { width: 100%; display: flex; flex-wrap: wrap; gap: 10px; }
        .ios-btn { flex: 1; justify-content: center; }
        .search-box { width: 100%; }
        
        /* 📱 Mobile Full Screen Sheet */
        .ios-modal { 
            width: 100%; 
            height: 100%; 
            max-height: 100vh; /* Force full height */
            border-radius: 0; 
        }
        .modal-head { padding-top: 20px; } /* Safe area */
    }
</style>

<?php if ($action == 'import_step1'): ?>
    <div style="max-width:480px; margin:60px auto; background:white; padding:40px; border-radius:24px; box-shadow:var(--shadow-lg); text-align:center; position: relative; z-index: 10; border: 1px solid rgba(0,0,0,0.05);">
        <div style="font-size:3.5rem; margin-bottom:15px;">🌩️</div>
        <h2 style="margin:0 0 10px; font-weight:700;">Select Provider</h2>
        <p style="color:var(--ios-text-sec); margin-bottom:30px; font-size:15px;">Choose an API to pull services from.</p>
        <form action="smm_services.php?action=import_step2" method="POST">
            <select name="provider_id" class="ios-input" style="padding:16px; font-size:16px; margin-bottom:25px;">
                <?php foreach($local_providers as $prov): ?>
                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ios-btn btn-blue" style="width:100%; justify-content:center; padding:16px; font-size:15px;">Next Step <i class="fa fa-arrow-right"></i></button>
        </form>
        <br><a href="smm_services.php" style="color:var(--ios-text-sec); text-decoration:none; font-size:14px; font-weight:500;">Cancel</a>
    </div>
<?php return; endif; ?>

<?php if ($action == 'import_step2'): ?>
    <?php 
    $pid = $_POST['provider_id'];
    $stmt = $db->prepare("SELECT * FROM smm_providers WHERE id=?");
    $stmt->execute([$pid]);
    $provider = $stmt->fetch();
    $api = new SmmApi($provider['api_url'], $provider['api_key']);
    $res = $api->getServices();
    if(!$res['success']) die("<div style='padding:50px; text-align:center;'><h3>API Error</h3><p>".$res['error']."</p><a href='smm_services.php' class='ios-btn btn-light'>Go Back</a></div>");
    $api_services = $res['services'];
    ?>
    <div style="padding:20px;">
        <form action="smm_services.php?action=import_final" method="POST">
            <input type="hidden" name="provider_id" value="<?= $pid ?>">
            <div style="background:white; border-radius:24px; box-shadow:var(--shadow-lg); overflow:hidden;">
                <div style="padding:24px; border-bottom:1px solid #F5F5F7; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                    <div>
                        <h2 style="margin:0; font-size:20px;">📦 Importing from <?= htmlspecialchars($provider['name']) ?></h2>
                        <span class="badge bg-blue-soft" style="margin-top:5px; display:inline-block;"><?= count($api_services) ?> Services Found</span>
                    </div>
                    <button type="submit" class="ios-btn btn-blue">Confirm Import</button>
                </div>
                <div class="table-container" style="max-height:75vh;">
                    <table class="service-table">
                        <thead><tr><th>Check</th><th>ID</th><th>Name</th><th>Cost</th><th>Profit %</th><th>Category</th></tr></thead>
                        <tbody>
                            <?php foreach($api_services as $s): $sid=$s['service']; ?>
                            <tr>
                                <td><input type="checkbox" name="services[]" value="<?= $sid ?>" checked></td>
                                <td><span class="id-box"><?= $sid ?></span></td>
                                <td>
                                    <b style="color:#333;"><?= htmlspecialchars($s['name']) ?></b>
                                    <input type="hidden" name="name[<?= $sid ?>]" value="<?= htmlspecialchars($s['name']) ?>">
                                    <input type="hidden" name="rate[<?= $sid ?>]" value="<?= $s['rate'] ?>">
                                    <input type="hidden" name="min[<?= $sid ?>]" value="<?= $s['min'] ?>">
                                    <input type="hidden" name="max[<?= $sid ?>]" value="<?= $s['max'] ?>">
                                    <input type="hidden" name="type[<?= $sid ?>]" value="<?= $s['type'] ?>">
                                </td>
                                <td>$<?= $s['rate'] ?></td>
                                <td><input type="number" class="ios-input" name="profit[<?= $sid ?>]" value="<?= $provider['profit_margin'] ?>" style="width:70px;"></td>
                                <td>
                                    <input type="text" class="ios-input" name="category[<?= $sid ?>]" value="<?= htmlspecialchars($s['category']) ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
<?php return; endif; ?>


<div class="page-title-bar" style="margin-bottom: 25px;">
    <h1 style="margin:0; font-weight:800; letter-spacing:-0.5px;">📦 Services Manager</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-blue-soft"><i class="fa-solid fa-layer-group"></i></div>
        <div><h3 style="margin:0; font-size:1.6rem; font-weight:700;"><?= number_format($total_svc) ?></h3><span style="color:var(--ios-text-sec); font-size:13px; font-weight:500;">Total Services</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green-soft"><i class="fa-solid fa-circle-check"></i></div>
        <div><h3 style="margin:0; font-size:1.6rem; font-weight:700;"><?= number_format($active_svc) ?></h3><span style="color:var(--ios-text-sec); font-size:13px; font-weight:500;">Active Services</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red-soft"><i class="fa-solid fa-ban"></i></div>
        <div><h3 style="margin:0; font-size:1.6rem; font-weight:700;"><?= number_format($disabled_svc) ?></h3><span style="color:var(--ios-text-sec); font-size:13px; font-weight:500;">Disabled Services</span></div>
    </div>
</div>

<?php if ($success): ?><div style="background:#ECFDF5; color:#047857; padding:16px; border-radius:16px; margin-bottom:20px; border:1px solid #A7F3D0; font-weight:600; display:flex; align-items:center; gap:10px; box-shadow:var(--shadow-sm);"><i class="fa fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div style="background:#FEF2F2; color:#B91C1C; padding:16px; border-radius:16px; margin-bottom:20px; border:1px solid #FECACA; font-weight:600; display:flex; align-items:center; gap:10px; box-shadow:var(--shadow-sm);"><i class="fa fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

<form method="POST" id="mainForm">
    <div class="controls-panel" style="background:white; padding:18px; border-radius:20px; margin-bottom:30px; box-shadow:var(--shadow-sm); display:flex; flex-wrap:wrap; gap:15px; align-items:center; justify-content:space-between; border:1px solid rgba(0,0,0,0.02);">
        
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" onclick="openModal()" class="ios-btn btn-blue"><i class="fa fa-plus"></i> Manual</button>
            <a href="smm_services.php?action=import_step1" class="ios-btn btn-light"><i class="fa fa-cloud-download"></i> Import</a>
            
            <select name="bulk_action" class="ios-input" style="width:auto; font-weight:600; min-width:160px; padding:10px 14px;" onchange="toggleBulkPrice(this)">
                <option value="">⚡ Bulk Actions</option>
                <option value="enable">✅ Enable Selected</option>
                <option value="disable">⛔ Disable Selected</option>
                <option value="delete">🗑️ Delete Selected</option>
                <option value="price_inc">💲 Increase Price %</option>
            </select>
            <input type="number" name="bulk_amount" id="bulk_price_input" class="ios-input" placeholder="%" style="width:80px; display:none; padding:10px;">
            <button class="ios-btn btn-light" onclick="return confirm('Apply this action to selected services?')">Apply</button>
            
            <a href="smm_services.php?action=delete_all" class="ios-btn btn-red" onclick="return confirm('⚠️ EXTREME WARNING ⚠️\n\nThis will delete ALL Services and ALL Categories permanently.\n\nThis action cannot be undone!\n\nAre you sure?')"><i class="fa fa-trash-can"></i> Delete Everything</a>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; gap:5px; background:#E8E8ED; padding:4px; border-radius:99px;">
                <a href="smm_services.php?show=active" class="ios-btn <?= $show=='active'?'btn-blue':'btn-transparent' ?>" style="border-radius:99px; padding:8px 16px;">Active</a>
                <a href="smm_services.php?show=disabled" class="ios-btn <?= $show=='disabled'?'btn-blue':'btn-transparent' ?>" style="border-radius:99px; padding:8px 16px;">Disabled</a>
            </div>
            
            <select class="ios-input" style="width:auto; padding:10px 14px;" onchange="window.location.href='smm_services.php?provider='+this.value">
                <option value="">All Providers</option>
                <?php foreach($local_providers as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($prov_filter == $p['id'])?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <div style="position:relative; width:220px;" class="search-box">
                <i class="fa fa-search" style="position:absolute; left:14px; top:13px; color:#aaa;"></i>
                <input type="text" class="ios-input" style="padding-left:38px; padding-top:10px; padding-bottom:10px;" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter') window.location.href='smm_services.php?search='+this.value">
            </div>
        </div>
    </div>

    <?php if(empty($grouped)): ?>
        <div style="text-align:center; padding:100px; color:#999;">
            <i class="fa-solid fa-box-open" style="font-size:5rem; margin-bottom:20px; opacity:0.2; color:var(--ios-blue);"></i>
            <h3>No Services Found</h3>
            <p>Try changing your filters or add a new service.</p>
        </div>
    <?php else: ?>
        <?php foreach($grouped as $cat => $list): ?>
        <div style="background:white; border-radius:20px; margin-bottom:30px; box-shadow:var(--shadow-sm); overflow:hidden; border:1px solid var(--ios-border);">
            <div style="padding:18px 24px; background:#F9F9FB; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; font-size:16px; color:#1d1d1f; display:flex; align-items:center; gap:10px;">
                    <?= htmlspecialchars($cat) ?> 
                    <span class="badge bg-blue-soft"><?= count($list) ?></span>
                </div>
                <a href="smm_services.php?action=delete_category&cat=<?= urlencode($cat) ?>" class="ios-btn btn-red" style="padding:8px 14px; font-size:11px;" onclick="return confirm('Delete entire category?')">Delete</a>
            </div>
            
            <div class="table-container">
                <table class="service-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" onchange="toggleCatCheck(this)"></th>
                            <th width="90">ID</th>
                            <th>Service Name</th>
                            <th width="130">Price (PKR)</th>
                            <th width="110">Min/Max</th>
                            <th width="140">Provider</th>
                            <th width="90">Status</th>
                            <th width="80" style="text-align:right;">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($list as $s): ?>
                        <tr style="<?= $s['is_active'] ? '' : 'opacity:0.6; filter:grayscale(1);' ?>">
                            <td><input type="checkbox" name="services[]" value="<?= $s['id'] ?>" class="svc-check"></td>
                            <td>
                                <div class="id-box" onclick="copyToClipboard('<?= $s['id'] ?>')" title="Click to Copy ID">
                                    <?= $s['id'] ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#333; font-size:14px; margin-bottom:4px;"><?= htmlspecialchars($s['name']) ?></div>
                                <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                    <?php if($s['has_refill']): ?><span class="badge bg-green-soft" title="Refill Available">♻️ Refill</span><?php endif; ?>
                                    <?php if($s['has_cancel']): ?><span class="badge bg-red-soft" title="Cancel Button Enabled">❌ Cancel</span><?php endif; ?>
                                    <?php if($s['service_type'] != 'Default'): ?>
                                        <span class="badge bg-blue-soft"><?= $s['service_type'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:var(--ios-green); font-size:15px;"><?= formatCurrency($s['service_rate']) ?></div>
                                <div style="font-size:11px; color:#aaa;">Base: <?= formatCurrency($s['base_price']) ?></div>
                            </td>
                            <td style="font-size:12px; color:#666; font-family:'SF Mono', monospace;">
                                <?= number_format($s['min']) ?> - <?= number_format($s['max']) ?>
                            </td>
                            <td>
                                <span class="badge" style="background:#F2F2F7; color:#555; border:1px solid #E5E5EA;">
                                    <?= htmlspecialchars(substr($s['provider_name'] ?? 'Manual', 0, 15)) ?>
                                </span>
                                <div style="font-size:10px; color:#999; margin-top:3px;">PID: <?= $s['service_id'] ?></div>
                            </td>
                            <td>
                                <?php if($s['is_active']): ?>
                                    <span class="badge bg-green-soft">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-red-soft">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="smm_edit_service.php?id=<?= $s['id'] ?>" class="ios-btn btn-light" style="padding:8px 12px;"><i class="fa fa-pen"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</form>

<div id="addModal" class="ios-modal-overlay">
    <div class="ios-modal">
        <form action="smm_services.php?action=add_manual_save" method="POST" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-head">
                <div>
                    <h3 style="margin:0; font-size:18px; font-weight:700;">✨ Add Manual Service</h3>
                    <p style="margin:2px 0 0 0; color:var(--ios-text-sec); font-size:13px;">Create your own custom service</p>
                </div>
                <button type="button" onclick="closeModal()" style="background:rgba(0,0,0,0.05); border:none; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#555; font-size:16px; transition:0.2s;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-weight:600; font-size:13px; color:var(--ios-text-sec); margin-bottom:6px; display:block;">Service Name</label>
                    <input type="text" name="name" class="ios-input" required placeholder="e.g. VIP Instagram Followers (Non-Drop)">
                </div>
                <br>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label style="font-weight:600; font-size:13px; color:var(--ios-text-sec);">Service Type</label>
                        <select name="service_type" class="ios-input">
                            <option value="Default">Default</option>
                            <option value="Custom Comments">Custom Comments</option>
                            <option value="Package">Package</option>
                            <option value="Custom Comments Package">Custom Comments Package</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600; font-size:13px; color:var(--ios-text-sec);">Rate per 1000</label>
                        <input type="number" step="0.0001" name="rate" class="ios-input" required placeholder="0.00">
                    </div>
                </div>
                <br>
                
                <label style="font-weight:600; font-size:13px; color:var(--ios-text-sec);">Category</label>
                <div style="background:#F5F5F7; padding:15px; border-radius:14px; margin-bottom:15px; border:1px solid #E5E5EA;">
                    <div id="cat_exist">
                        <label style="font-weight:600; font-size:13px; color:var(--ios-text-sec); margin-bottom:6px; display:block;">Select Category</label>
                        <select name="existing_category" class="ios-input" style="background:white;">
                            <option value="">-- Select Category --</option>
                            <?php foreach($local_cats as $lc): ?>
                                <option value="<?= htmlspecialchars($lc) ?>"><?= htmlspecialchars($lc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div><label style="font-size:12px; font-weight:600; color:var(--ios-text-sec);">Min Order</label><input type="number" name="min" class="ios-input" value="10"></div>
                    <div><label style="font-size:12px; font-weight:600; color:var(--ios-text-sec);">Max Order</label><input type="number" name="max" class="ios-input" value="10000"></div>
                </div>
                <br>

                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <label style="font-size:12px; font-weight:600; color:var(--ios-text-sec);">Refill</label>
                        <select name="refill" class="ios-input"><option value="0">No</option><option value="1">Yes</option></select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:12px; font-weight:600; color:var(--ios-text-sec);">Cancel</label>
                        <select name="cancel" class="ios-input"><option value="0">No</option><option value="1">Yes</option></select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:12px; font-weight:600; color:var(--ios-text-sec);">Avg Time</label>
                        <input type="text" name="avg_time" class="ios-input" placeholder="e.g. 1H">
                    </div>
                </div>
                <br>

                <label style="font-weight:600; font-size:13px; color:var(--ios-text-sec); margin-bottom:8px; display:block;">Quick Description</label>
                <div style="margin-bottom:12px; display:flex; flex-wrap:wrap; gap:6px;">
                    <span class="template-tag" onclick="insertDesc('🔥 High Quality\n🚀 Instant Start\n♻️ Non-Drop\n⭐ Best for Ranking')"><i class="fab fa-hotjar"></i> Best Seller</span>
                    <span class="template-tag" onclick="insertDesc('⚡ Super Fast Delivery\n✅ Real Accounts\n🛡️ 30 Days Refill Guarantee')"><i class="fa fa-bolt"></i> Fast Refill</span>
                    <span class="template-tag" onclick="insertDesc('📸 Instagram Followers\n⚡ Speed: 10K/Day\n♻️ Refill: 365 Days\n💧 Drop: Low')"><i class="fab fa-instagram"></i> IG VIP</span>
                    <span class="template-tag" onclick="insertDesc('🎵 TikTok Views\n🚀 Instant\n🌍 Global Reach')"><i class="fab fa-tiktok"></i> TikTok</span>
                </div>
                <textarea name="desc" id="descbox" class="ios-input" rows="5" placeholder="Service description here..." style="font-family:inherit;"></textarea>
            </div>
            
            <div class="modal-foot">
                <button type="submit" class="ios-btn btn-blue" style="width:100%; justify-content:center; padding:16px; font-size:16px; box-shadow:0 4px 15px rgba(0,113,227,0.4);">Create Service</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { 
        document.getElementById('addModal').style.display = 'flex';
        // Small delay to allow display:flex to apply before opacity transition
        setTimeout(() => document.getElementById('addModal').classList.add('active'), 10);
        document.body.style.overflow = 'hidden'; 
    }
    
    function closeModal() { 
        document.getElementById('addModal').classList.remove('active');
        setTimeout(() => {
            document.getElementById('addModal').style.display = 'none';
            document.body.style.overflow = 'auto'; 
        }, 300);
    }
    
    // Close modal if clicked outside
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    function toggleBulkPrice(el) {
        document.getElementById('bulk_price_input').style.display = (el.value === 'price_inc') ? 'inline-block' : 'none';
    }

    function toggleCatCheck(source) {
        let table = source.closest('table');
        let checkboxes = table.querySelectorAll('.svc-check');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }

    function insertDesc(text) {
        document.getElementById('descbox').value = text;
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Simple toast notification could go here, for now alert is fine
            // alert('Service ID ' + text + ' copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>

<?php include '_footer.php'; ?>