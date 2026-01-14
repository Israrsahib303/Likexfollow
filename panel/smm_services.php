<?php
include '_header.php';
require_once __DIR__ . '/../includes/smm_api.class.php';

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
//       🔧 CORE LOGIC
// =========================================================

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
        
        // Category Logic
        $cat_mode = $_POST['cat_mode']; // 'existing' or 'new'
        $category = ($cat_mode == 'new') ? $_POST['new_category'] : $_POST['existing_category'];
        $cat_icon = $_POST['cat_icon'];

        if (empty($category)) throw new Exception("Category name is required.");

        // 1. Handle Provider (Fix FK Constraint)
        // Find or Create a "Manual Service" provider to satisfy Foreign Key
        $stmt = $db->prepare("SELECT id FROM smm_providers WHERE api_url = 'manual_internal' LIMIT 1");
        $stmt->execute();
        $provider_id = $stmt->fetchColumn();

        if (!$provider_id) {
            $db->prepare("INSERT INTO smm_providers (name, api_url, api_key, profit_margin, is_active) VALUES (?, ?, ?, 0, 1)")
               ->execute(['Manual Service', 'manual_internal', 'manual_key']);
            $provider_id = $db->lastInsertId();
        }

        // 2. Handle Category (Insert or Update Icon)
        $chkCat = $db->prepare("SELECT id FROM smm_categories WHERE name = ?");
        $chkCat->execute([$category]);
        $catExist = $chkCat->fetchColumn();

        if ($catExist) {
            // Update icon if explicitly selected
            if (!empty($cat_icon)) {
                $db->prepare("UPDATE smm_categories SET icon_filename = ? WHERE id = ?")->execute([$cat_icon, $catExist]);
            }
        } else {
            // Insert new category
            $db->prepare("INSERT INTO smm_categories (name, icon_filename, is_active) VALUES (?, ?, 1)")
               ->execute([$category, $cat_icon]);
        }
        
        // 3. Insert Service
        $manual_sid = time(); // Unique Service ID
        
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
                
                // Add Category if new
                $db->prepare("INSERT IGNORE INTO smm_categories (name, is_active) VALUES (?, 1)")->execute([$category]);
                
                // Check if exists
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

// Join to get provider name, use LEFT JOIN so manual services (if any old 0 exist) don't disappear
$services = $db->query("SELECT s.*, p.name as provider_name FROM smm_services s LEFT JOIN smm_providers p ON s.provider_id = p.id $where ORDER BY s.category ASC, s.name ASC")->fetchAll();
$grouped = [];
foreach($services as $s) $grouped[$s['category']][] = $s;

// Fetch Local Categories for Dropdowns
$local_cats = $db->query("SELECT name FROM smm_categories ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_COLUMN);
$local_providers = $db->query("SELECT * FROM smm_providers WHERE is_active=1")->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4f46e5;
        --primary-soft: #eef2ff;
        --bg-body: #f8fafc;
        --border: #e2e8f0;
    }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: #1e293b; }
    .alert-box { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    .alert-err { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .alert-suc { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

    /* IMPORT WIZARD */
    .import-container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); border: 1px solid var(--border); overflow: hidden; }
    .import-header { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); padding: 30px; text-align: center; color: white; }
    .import-body { padding: 30px; background: #f8fafc; overflow-x: auto;}
    
    /* MAIN UI */
    .page-title-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    .page-title-bar h1 { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0; }
    
    .controls-panel { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 30px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
    .btn-core { padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: 1px solid transparent; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: #4338ca; }
    .btn-danger { background: #fee2e2; color: #b91c1c; }
    .btn-dark { background: #1e293b; color: #fff; }
    
    /* Category Cards */
    .cat-wrapper { background: #fff; border-radius: 16px; margin-bottom: 25px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .cat-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .cat-name { font-size: 1.1rem; font-weight: 700; color: #334155; }
    
    /* Modern Table */
    .modern-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .modern-table th { text-align: left; padding: 15px; color: #64748b; font-weight: 600; border-bottom: 1px solid var(--border); }
    .modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    /* Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-box { background: white; width: 600px; max-width: 90%; border-radius: 16px; padding: 0; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUp 0.3s ease; display: flex; flex-direction: column; max-height: 90vh; }
    .modal-header { padding: 20px 25px; border-bottom: 1px solid var(--border); background: #f8fafc; border-radius: 16px 16px 0 0; display:flex; justify-content:space-between; align-items:center; }
    .modal-body { padding: 25px; overflow-y: auto; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 0.9rem; }
    .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; outline: none; transition: 0.2s; background: #fff; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
    
    .icon-grid { display: flex; flex-wrap: wrap; gap: 10px; max-height: 150px; overflow-y: auto; border: 1px solid var(--border); padding: 10px; border-radius: 8px; }
    .icon-option { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; border-radius: 6px; cursor: pointer; }
    .icon-option:hover { background: #f1f5f9; }
    .icon-option.selected { border-color: var(--primary); background: var(--primary-soft); }
    .icon-option img { max-width: 24px; max-height: 24px; }

    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<?php if ($action == 'import_step1'): ?>
    <div class="import-container" style="max-width: 600px;">
        <div class="import-header">
            <h2>📥 Step 1: Select Provider</h2>
            <p>Choose the API provider you want to import services from.</p>
        </div>
        <div class="import-body" style="text-align: center;">
            <form action="smm_services.php?action=import_step2" method="POST">
                <div class="form-group">
                    <select name="provider_id" class="form-control" style="font-size: 1.1rem; padding: 15px;">
                        <?php foreach($local_providers as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?> (<?= htmlspecialchars($prov['api_url']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-core btn-primary" style="width: 100%; justify-content: center; padding: 15px;">
                    Next: Fetch Services <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            <br>
            <a href="smm_services.php" style="color: #64748b; text-decoration: none;">Cancel</a>
        </div>
    </div>
<?php return; endif; ?>

<?php if ($action == 'import_step2' && isset($_POST['provider_id'])): 
    $pid = $_POST['provider_id'];
    $stmt = $db->prepare("SELECT * FROM smm_providers WHERE id=?");
    $stmt->execute([$pid]);
    $provider = $stmt->fetch();
    
    if(!$provider) die("Invalid Provider");
    
    // Fetch Services from API
    $api = new SmmApi($provider['api_url'], $provider['api_key']);
    $res = $api->getServices();
    
    if (!$res['success']) die("<div class='alert-box alert-err'>API Error: " . $res['error'] . "</div><a href='smm_services.php'>Back</a>");
    $api_services = $res['services'];
?>
    <div class="import-container">
        <div class="import-header">
            <h2>📥 Step 2: Map & Import</h2>
            <p>Provider: <b><?= htmlspecialchars($provider['name']) ?></b> | Found: <?= count($api_services) ?> Services</p>
        </div>
        
        <form action="smm_services.php?action=import_final" method="POST">
            <input type="hidden" name="provider_id" value="<?= $pid ?>">
            
            <div class="import-body">
                <div style="background: #eef2ff; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                    <b style="color: var(--primary);">⚡ Bulk Action:</b>
                    <select id="bulk_cat" class="form-control" style="width: auto; display: inline-block;">
                        <option value="">-- Select Category to Apply --</option>
                        <?php foreach($local_cats as $lc): ?>
                            <option value="<?= htmlspecialchars($lc) ?>"><?= htmlspecialchars($lc) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-core btn-primary" onclick="applyBulkCategory()">Apply to Selected</button>
                </div>

                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" onchange="toggleAll(this)"></th>
                            <th width="60">ID</th>
                            <th>Service Name (From API)</th>
                            <th>Rate ($)</th>
                            <th width="100">Profit %</th>
                            <th>Your Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($api_services as $s): 
                            $sid = $s['service'];
                            $cat = $s['category'];
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="services[]" value="<?= $sid ?>" class="svc-chk">
                                <input type="hidden" name="name[<?= $sid ?>]" value="<?= htmlspecialchars($s['name']) ?>">
                                <input type="hidden" name="rate[<?= $sid ?>]" value="<?= $s['rate'] ?>">
                                <input type="hidden" name="min[<?= $sid ?>]" value="<?= $s['min'] ?>">
                                <input type="hidden" name="max[<?= $sid ?>]" value="<?= $s['max'] ?>">
                                <input type="hidden" name="type[<?= $sid ?>]" value="<?= $s['type'] ?>">
                            </td>
                            <td><?= $sid ?></td>
                            <td>
                                <div style="font-size:0.9rem; font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
                                <small style="color:#64748b;"><?= htmlspecialchars($cat) ?></small>
                            </td>
                            <td>$<?= $s['rate'] ?></td>
                            <td>
                                <input type="number" name="profit[<?= $sid ?>]" value="<?= $provider['profit_margin'] ?>" class="form-control" style="width: 80px; padding: 5px;">
                            </td>
                            <td>
                                <select name="category[<?= $sid ?>]" class="form-control cat-selector">
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?> (New)</option>
                                    <?php foreach($local_cats as $lc): ?>
                                        <option value="<?= htmlspecialchars($lc) ?>" <?= ($lc == $cat) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lc) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="import-footer" style="padding: 20px; text-align: right; background: #fff; position: sticky; bottom: 0; border-top: 1px solid var(--border);">
                <button type="submit" class="btn-core btn-primary">🚀 Import Selected Services</button>
            </div>
        </form>
    </div>
    
    <script>
        function toggleAll(source) {
            document.querySelectorAll('.svc-chk').forEach(cb => cb.checked = source.checked);
        }
        function applyBulkCategory() {
            let cat = document.getElementById('bulk_cat').value;
            if(!cat) return alert("Please select a category first.");
            document.querySelectorAll('tr').forEach(row => {
                let chk = row.querySelector('.svc-chk');
                if(chk && chk.checked) row.querySelector('.cat-selector').value = cat;
            });
        }
    </script>
<?php return; endif; ?>


<div class="page-title-bar">
    <h1>📦 Services Manager</h1>
    <button onclick="openManualModal()" class="btn-core btn-dark">
        <i class="fa-solid fa-plus"></i> Add Manual Service
    </button>
</div>

<?php if ($error): ?><div class="alert-box alert-err"><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert-box alert-suc"><?= $success ?></div><?php endif; ?>

<div class="controls-panel">
    <div class="btn-group">
        <a href="smm_services.php?action=import_step1" class="btn-core btn-primary">
            <i class="fa-solid fa-cloud-arrow-down"></i> Import Services
        </a>
        <a href="smm_services.php?action=delete_all" class="btn-core btn-danger" onclick="return confirm('⚠️ WARNING: This will DELETE ALL services! Are you sure?')">
            <i class="fa-solid fa-trash-can"></i> Wipe All
        </a>
    </div>

    <div class="btn-group">
        <a href="smm_services.php?show=active" class="btn-core <?= $show=='active'?'btn-primary':'btn-dark' ?>">Active</a>
        <a href="smm_services.php?show=disabled" class="btn-core <?= $show=='disabled'?'btn-primary':'btn-dark' ?>">Disabled</a>
    </div>

    <div class="search-box" style="position:relative;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:10px; top:12px; color:#94a3b8;"></i>
        <form><input type="text" name="search" class="form-control" style="padding-left:30px;" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"></form>
    </div>
</div>

<?php if(empty($grouped)): ?>
    <div style="text-align:center; padding:50px; background:#fff; border-radius:16px; border:1px solid var(--border);">
        <h3 style="color:#64748b;">No Services Found</h3>
        <p style="color:#94a3b8;">Add a manual service or import from a provider.</p>
    </div>
<?php else: ?>
    
    <?php foreach($grouped as $cat => $list): ?>
    <div class="cat-wrapper">
        <div class="cat-header">
            <div class="cat-name">
                <?= htmlspecialchars($cat) ?> <span style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><?= count($list) ?></span>
            </div>
            <a href="smm_services.php?action=delete_category&cat=<?= urlencode($cat) ?>" class="btn-core btn-danger" style="padding:5px 12px; font-size:0.8rem;" onclick="return confirm('Remove entire category?')">Delete</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Service Name</th>
                        <th width="100">Provider</th>
                        <th width="120">Rate</th>
                        <th width="100">Min/Max</th>
                        <th width="80">Status</th>
                        <th width="100" style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list as $s): ?>
                    <tr>
                        <td><b><?= $s['id'] ?></b></td>
                        <td style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></td>
                        <td>
                            <?php if(strpos($s['provider_name'] ?? '', 'Manual') !== false): ?>
                                <span style="background:#f1f5f9; color:#475569; padding:2px 6px; border-radius:4px; font-size:0.75rem; border:1px solid #cbd5e1;">Manual</span>
                            <?php else: ?>
                                <span style="color:#4f46e5; font-weight:600; font-size:0.85rem;"><?= htmlspecialchars($s['provider_name'] ?? 'Unknown') ?></span>
                                <div style="font-size:0.7rem; color:#94a3b8;">ID: <?= $s['service_id'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color:#16a34a; font-weight:700;"><?= formatCurrency($s['service_rate']) ?></span>
                        </td>
                        <td style="font-size:0.85rem;"><?= $s['min'] ?> - <?= $s['max'] ?></td>
                        <td>
                            <span style="padding:4px 8px; border-radius:12px; font-size:0.75rem; font-weight:700; <?= $s['is_active'] ? 'background:#dcfce7; color:#16a34a;' : 'background:#fee2e2; color:#dc2626;' ?>">
                                <?= $s['is_active'] ? 'ACTIVE' : 'OFF' ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a href="smm_edit_service.php?id=<?= $s['id'] ?>" style="color:#3b82f6; margin-right:10px;"><i class="fa-solid fa-pen"></i></a>
                            <a href="smm_services.php?action=delete_service&id=<?= $s['id'] ?>" style="color:#ef4444;" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>

<div id="manualModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 style="margin:0; font-size:1.3rem; color:var(--primary);">Add Manual Service</h2>
            <button onclick="closeManualModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <form action="smm_services.php?action=add_manual_save" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. TikTok Views VIP">
                </div>
                
                <div style="background:#f1f5f9; padding:15px; border-radius:8px; margin-bottom:15px;">
                    <label style="margin-bottom:10px; display:block;">Category Selection</label>
                    
                    <div style="display:flex; gap:15px; margin-bottom:10px;">
                        <label><input type="radio" name="cat_mode" value="existing" checked onclick="toggleCat('existing')"> Existing</label>
                        <label><input type="radio" name="cat_mode" value="new" onclick="toggleCat('new')"> Create New</label>
                    </div>

                    <div id="cat_existing_box">
                        <select name="existing_category" class="form-control">
                            <?php foreach($local_cats as $lc): ?>
                                <option value="<?= htmlspecialchars($lc) ?>"><?= htmlspecialchars($lc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="cat_new_box" style="display:none;">
                        <input type="text" name="new_category" class="form-control" placeholder="Enter New Category Name">
                        <div style="margin-top:10px;">
                            <label style="font-size:0.8rem;">Select Category Icon (Optional)</label>
                            <input type="hidden" name="cat_icon" id="selected_icon">
                            <div class="icon-grid">
                                <?php foreach($iconFiles as $icon): ?>
                                <div class="icon-option" onclick="selectIcon(this, '<?= $icon ?>')">
                                    <img src="../assets/img/icons/<?= $icon ?>" alt="icon">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Rate (per 1000)</label>
                        <input type="number" step="0.0001" name="rate" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Min Order</label>
                        <input type="number" name="min" class="form-control" value="10">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Max Order</label>
                        <input type="number" name="max" class="form-control" value="10000">
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Refill Button?</label>
                        <select name="refill" class="form-control">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Cancel Button?</label>
                        <select name="cancel" class="form-control">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Avg Time</label>
                        <input type="text" name="avg_time" class="form-control" placeholder="e.g. 1 Hour">
                    </div>
                </div>

                <div class="form-group">
                    <label>Service Type</label>
                    <select name="service_type" class="form-control">
                        <option value="Default">Default</option>
                        <option value="Custom Comments">Custom Comments</option>
                        <option value="Package">Package</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="desc" class="form-control" rows="3"></textarea>
                </div>
            </div>
            
            <div style="padding:20px; text-align:right; border-top:1px solid var(--border); background:#f8fafc; border-radius:0 0 16px 16px;">
                <button type="button" onclick="closeManualModal()" class="btn-core btn-danger" style="background:transparent; color:#64748b; border:none;">Cancel</button>
                <button type="submit" class="btn-core btn-primary">Create Service</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openManualModal() { document.getElementById('manualModal').style.display = 'flex'; }
    function closeManualModal() { document.getElementById('manualModal').style.display = 'none'; }
    
    function toggleCat(mode) {
        if(mode === 'existing') {
            document.getElementById('cat_existing_box').style.display = 'block';
            document.getElementById('cat_new_box').style.display = 'none';
        } else {
            document.getElementById('cat_existing_box').style.display = 'none';
            document.getElementById('cat_new_box').style.display = 'block';
        }
    }

    function selectIcon(el, iconName) {
        document.querySelectorAll('.icon-option').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('selected_icon').value = iconName;
    }
</script>

<?php include '_footer.php'; ?>
