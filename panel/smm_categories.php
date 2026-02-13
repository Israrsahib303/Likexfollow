<?php
include '_header.php';

// --- HELPER: SMM API CLASS (Simple inline version if file missing, or include yours) ---
// We try to include your existing class first
if(file_exists('../includes/smm_api.class.php')) {
    require_once '../includes/smm_api.class.php';
}

// --- ERROR HANDLING & INIT ---
$error = '';
$success = '';
$cats = [];
$providers = [];

// --- 1. HANDLE AJAX / API ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // A. FETCH REMOTE CATEGORIES
    if ($_POST['ajax_action'] == 'get_provider_cats') {
        $pid = (int)$_POST['provider_id'];
        $prov = $db->query("SELECT * FROM smm_providers WHERE id=$pid")->fetch(PDO::FETCH_ASSOC);
        
        if(!$prov) { echo json_encode(['error' => 'Provider not found']); exit; }
        
        // Call API
        $url = $prov['api_url']; 
        $key = $prov['api_key'];
        
        $post = ['key' => $key, 'action' => 'services'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $services = json_decode($response, true);
        
        if (!is_array($services)) {
            echo json_encode(['error' => 'Failed to fetch services from API']); exit;
        }
        
        $categories = [];
        foreach ($services as $s) {
            $cat = trim($s['category']);
            if (!in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        
        echo json_encode(['status' => 'success', 'categories' => $categories]);
        exit;
    }

    // B. FETCH REMOTE SERVICES BY CATEGORY
    if ($_POST['ajax_action'] == 'get_provider_services') {
        $pid = (int)$_POST['provider_id'];
        $catName = $_POST['category'];
        $prov = $db->query("SELECT * FROM smm_providers WHERE id=$pid")->fetch(PDO::FETCH_ASSOC);
        
        $url = $prov['api_url']; $key = $prov['api_key'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['key' => $key, 'action' => 'services']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $services = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        $filtered = [];
        foreach ($services as $s) {
            if (trim($s['category']) == $catName) {
                $filtered[] = $s;
            }
        }
        
        echo json_encode(['status' => 'success', 'services' => $filtered]);
        exit;
    }
}

// --- 2. HANDLE FORM ACTIONS (SAVE/DELETE/IMPORT) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // DELETE
    if ($_POST['action'] == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("SELECT sub_cat_icon, main_cat_icon FROM smm_sub_categories WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        $del = $db->prepare("DELETE FROM smm_sub_categories WHERE id=?");
        if ($del->execute([$id])) {
            if (!empty($row['sub_cat_icon']) && $row['sub_cat_icon'] !== 'default.png') {
                @unlink("../assets/uploads/" . $row['sub_cat_icon']); 
            }
            if (!empty($row['main_cat_icon'])) {
                @unlink("../assets/uploads/" . $row['main_cat_icon']); 
            }
            $success = "Category deleted successfully!";
        }
    }

    // SAVE CATEGORY
    if ($_POST['action'] == 'save') {
        $main_app_select = $_POST['main_app_select'] ?? '';
        $main_app_new = trim($_POST['main_app_new'] ?? '');
        $main_app = ($main_app_select === 'NEW' && !empty($main_app_new)) ? $main_app_new : $main_app_select;
        $sub_name = trim($_POST['sub_cat_name']);
        $keys = (isset($_POST['target_all']) && $_POST['target_all'] == '1') ? '*' : trim($_POST['keywords']);
        $sort = (int)$_POST['sort_order'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        // Image Upload Helper
        function uploadIcon($file) {
            if (!empty($file['name'])) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $name = "icon_" . time() . "_" . rand(1000,9999) . "." . $ext;
                if (move_uploaded_file($file['tmp_name'], "../assets/uploads/" . $name)) return $name;
            }
            return null;
        }

        $subIcon = uploadIcon($_FILES['sub_icon']);
        $mainIcon = uploadIcon($_FILES['main_icon']);

        if ($id > 0) {
            // Update
            $sql = "UPDATE smm_sub_categories SET main_app=?, sub_cat_name=?, keywords=?, sort_order=?";
            $params = [$main_app, $sub_name, $keys, $sort];
            if ($subIcon) { $sql .= ", sub_cat_icon=?"; $params[] = $subIcon; }
            if ($mainIcon) { $sql .= ", main_cat_icon=?"; $params[] = $mainIcon; }
            $sql .= " WHERE id=?"; $params[] = $id;
            
            $db->prepare($sql)->execute($params);
        } else {
            // Insert
            if (!$subIcon) $subIcon = 'default.png';
            $stmt = $db->prepare("INSERT INTO smm_sub_categories (main_app, sub_cat_name, sub_cat_icon, main_cat_icon, keywords, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$main_app, $sub_name, $subIcon, $mainIcon, $keys, $sort]);
        }
        $success = "Category saved!";
    }

    // IMPORT SERVICES
    if ($_POST['action'] == 'import_selected') {
        if (!empty($_POST['selected_services'])) {
            $provider_id = (int)$_POST['provider_id'];
            $target_cat_id = (int)$_POST['target_cat_id'];
            
            // Get Sub Cat Name for default category
            $subCat = $db->query("SELECT sub_cat_name, main_app FROM smm_sub_categories WHERE id=$target_cat_id")->fetch();
            $localCatName = $subCat['main_app'] . ' - ' . $subCat['sub_cat_name'];

            $count = 0;
            foreach ($_POST['selected_services'] as $svcJson) {
                $s = json_decode(base64_decode($svcJson), true);
                if ($s) {
                    // Logic to insert into smm_services
                    $stmt = $db->prepare("INSERT INTO smm_services 
                        (service_id, provider_id, name, category, rate, min, max, type, description, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    
                    // Rate calculation (e.g. +20% profit)
                    $rate = $s['rate'] * 1.20; 
                    
                    try {
                        $stmt->execute([
                            $s['service'], // Provider Service ID
                            $provider_id,
                            $s['name'],
                            $localCatName, // Use our category name
                            $rate,
                            $s['min'],
                            $s['max'],
                            $s['type'],
                            'Imported via Category Manager'
                        ]);
                        $count++;
                    } catch (Exception $ex) {
                        // Ignore duplicate entry errors
                    }
                }
            }
            $success = "Successfully imported $count services!";
        }
    }
}

// --- 3. FETCH DATA (FIXED QUERY) ---
try {
    // FIX: Removed "WHERE status=1" to prevent error if column missing
    $providers = $db->query("SELECT * FROM smm_providers")->fetchAll(PDO::FETCH_ASSOC);
    $cats = $db->query("SELECT * FROM smm_sub_categories ORDER BY main_app ASC, sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Database Error: " . $e->getMessage();
}

$mainApps = [];
foreach($cats as $c) { if(!in_array($c['main_app'], $mainApps)) $mainApps[] = $c['main_app']; }
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root { --primary: #4f46e5; --bg: #f8fafc; --text: #334155; }
    body { background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); }
    
    /* Layout */
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .header h2 { margin: 0; font-size: 1.25rem; }
    
    .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: 0.2s; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn-danger { background: #fee2e2; color: #dc2626; }
    .btn-soft { background: #eef2ff; color: var(--primary); }

    /* Table */
    .card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 15px; background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; }
    td { padding: 15px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    
    .icon-img { width: 40px; height: 40px; border-radius: 8px; object-fit: contain; background: #f8fafc; padding: 4px; border: 1px solid #e2e8f0; }
    .badge { background: #e0e7ff; color: #4338ca; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
    
    /* Modal */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; backdrop-filter: blur(2px); justify-content: center; align-items: center; padding: 20px; }
    .modal-overlay.active { display: flex; }
    .modal { background: white; width: 100%; max-width: 600px; border-radius: 16px; padding: 25px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; }
    
    /* API Section */
    .api-box { background: #f0f9ff; border: 1px dashed #0ea5e9; padding: 15px; border-radius: 10px; margin-top: 20px; }
    .service-list { max-height: 200px; overflow-y: auto; background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 10px; padding: 5px; }
    .service-item { display: flex; align-items: center; gap: 10px; padding: 8px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
    .service-item:hover { background: #f8fafc; }
    
    /* Responsive */
    @media(max-width: 768px) {
        .header { flex-direction: column; align-items: flex-start; gap: 10px; }
        th, td { padding: 10px; }
        .desktop-only { display: none; }
    }
</style>

<div class="container">
    
    <div class="header">
        <div>
            <h2>Manage Categories</h2>
            <small style="color:#64748b;">Organize your services into apps & sub-categories</small>
        </div>
        <button onclick="openModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Category
        </button>
    </div>

    <?php if($error): ?>
        <div style="background:#fee2e2; color:#dc2626; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?= $error ?>
        </div>
    <?php endif; ?>
    <?php if($success): ?>
        <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th width="50">Icon</th>
                    <th>Main App</th>
                    <th>Sub Category</th>
                    <th class="desktop-only">Matching</th>
                    <th width="120" style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($cats)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No categories found.</td></tr>
                <?php else: ?>
                    <?php foreach($cats as $cat): 
                        $icon = !empty($cat['sub_cat_icon']) ? "../assets/uploads/".$cat['sub_cat_icon'] : "../assets/img/icons/default.png";
                    ?>
                    <tr>
                        <td><img src="<?= $icon ?>" class="icon-img" onerror="this.src='../assets/img/icons/default.png'"></td>
                        <td>
                            <span class="badge"><?= htmlspecialchars($cat['main_app']) ?></span>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($cat['sub_cat_name']) ?></td>
                        <td class="desktop-only">
                            <?php if($cat['keywords'] === '*'): ?>
                                <span style="color:#16a34a; font-weight:700;">ALL</span>
                            <?php else: ?>
                                <span style="color:#64748b; font-size:0.8rem;"><?= htmlspecialchars($cat['keywords']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right">
                            <button class="btn btn-soft" style="padding:6px 10px;" onclick='editCat(<?= json_encode($cat) ?>)'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 10px;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 id="modalTitle" style="margin:0;">Category Details</h3>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save" id="formAction">
            <input type="hidden" name="id" id="inp_id">
            
            <div class="form-group" style="background:#f1f5f9; padding:15px; border-radius:10px;">
                <label class="form-label">1. Main App (e.g. Instagram)</label>
                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <select name="main_app_select" id="inp_app_select" class="form-control" onchange="toggleNewApp(this.value)">
                            <option value="">Select Existing...</option>
                            <?php foreach($mainApps as $app): ?>
                                <option value="<?= htmlspecialchars($app) ?>"><?= htmlspecialchars($app) ?></option>
                            <?php endforeach; ?>
                            <option value="NEW">+ Create New</option>
                        </select>
                        <input type="text" name="main_app_new" id="inp_app_new" class="form-control" style="display:none; margin-top:10px;" placeholder="App Name">
                    </div>
                    <div>
                        <label class="btn btn-soft" style="height:100%; display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-image"></i>
                            <input type="file" name="main_icon" style="display:none;">
                        </label>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">2. Sub Category Name</label>
                    <input type="text" name="sub_cat_name" id="inp_name" class="form-control" placeholder="e.g. Likes" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Sort</label>
                    <input type="number" name="sort_order" id="inp_sort" class="form-control" value="0" style="width:70px;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Sub Category Icon</label>
                <input type="file" name="sub_icon" class="form-control">
            </div>

            <div class="form-group">
                <div style="display:flex; justify-content:space-between;">
                    <label class="form-label">3. Auto-Matching Keywords</label>
                    <label><input type="checkbox" name="target_all" id="inp_target_all" value="1"> Match All</label>
                </div>
                <input type="text" name="keywords" id="inp_keys" class="form-control" placeholder="e.g. likes, heart">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Save Category</button>
        </form>

        <div class="api-box" id="apiSection" style="display:none;">
            <h4 style="margin:0 0 10px 0; color:#0284c7;"><i class="fa-solid fa-cloud-arrow-down"></i> Import Services from API</h4>
            
            <div class="form-group">
                <label class="form-label">Select API Provider</label>
                <select id="api_provider" class="form-control" onchange="fetchCats(this.value)">
                    <option value="">Select...</option>
                    <?php foreach($providers as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['domain']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="api_cat_loader" style="display:none; color:#64748b;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
            
            <div class="form-group" id="remote_cat_div" style="display:none;">
                <label class="form-label">Select Remote Category</label>
                <select id="api_remote_cat" class="form-control" onchange="fetchServices(this.value)"></select>
            </div>

            <div id="service_selection_div" style="display:none;">
                <label class="form-label">Select Services to Import</label>
                <form method="POST" id="importForm">
                    <input type="hidden" name="action" value="import_selected">
                    <input type="hidden" name="target_cat_id" id="import_target_id">
                    <input type="hidden" name="provider_id" id="import_provider_id">
                    
                    <div class="service-list" id="remote_services_list"></div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">Import Selected Services</button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
const modal = document.getElementById('catModal');
const inpAppSelect = document.getElementById('inp_app_select');
const inpAppNew = document.getElementById('inp_app_new');

function openModal() {
    // Clear form
    document.getElementById('formAction').value = 'save';
    document.getElementById('inp_id').value = '';
    document.getElementById('inp_name').value = '';
    document.getElementById('inp_keys').value = '';
    document.getElementById('inp_sort').value = '0';
    document.getElementById('apiSection').style.display = 'none';
    
    inpAppSelect.value = '';
    toggleNewApp('');
    
    document.querySelector('.modal-overlay').classList.add('active');
}

function editCat(data) {
    document.getElementById('modalTitle').innerText = "Edit: " + data.sub_cat_name;
    document.getElementById('inp_id').value = data.id;
    document.getElementById('inp_name').value = data.sub_cat_name;
    document.getElementById('inp_sort').value = data.sort_order;
    document.getElementById('inp_keys').value = (data.keywords === '*') ? '' : data.keywords;
    document.getElementById('inp_target_all').checked = (data.keywords === '*');
    
    // Set App
    let exists = false;
    for(let i=0; i<inpAppSelect.options.length; i++) {
        if(inpAppSelect.options[i].value === data.main_app) exists = true;
    }
    if(exists) {
        inpAppSelect.value = data.main_app;
        toggleNewApp(data.main_app);
    } else {
        inpAppSelect.value = 'NEW';
        toggleNewApp('NEW');
        inpAppNew.value = data.main_app;
    }

    // Show API Section
    document.getElementById('apiSection').style.display = 'block';
    document.getElementById('import_target_id').value = data.id;
    
    document.querySelector('.modal-overlay').classList.add('active');
}

function closeModal() { document.querySelector('.modal-overlay').classList.remove('active'); }

function toggleNewApp(val) {
    if(val === 'NEW') inpAppNew.style.display = 'block';
    else inpAppNew.style.display = 'none';
}

// --- API AJAX LOGIC ---
function fetchCats(pid) {
    if(!pid) return;
    document.getElementById('import_provider_id').value = pid;
    document.getElementById('api_cat_loader').style.display = 'block';
    
    const fd = new FormData();
    fd.append('ajax_action', 'get_provider_cats');
    fd.append('provider_id', pid);
    
    fetch('smm_categories.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        document.getElementById('api_cat_loader').style.display = 'none';
        const sel = document.getElementById('api_remote_cat');
        sel.innerHTML = '<option value="">Select Category...</option>';
        data.categories.forEach(c => {
            sel.innerHTML += `<option value="${c}">${c}</option>`;
        });
        document.getElementById('remote_cat_div').style.display = 'block';
    });
}

function fetchServices(catName) {
    if(!catName) return;
    document.getElementById('api_cat_loader').style.display = 'block';
    const pid = document.getElementById('api_provider').value;
    
    const fd = new FormData();
    fd.append('ajax_action', 'get_provider_services');
    fd.append('provider_id', pid);
    fd.append('category', catName);
    
    fetch('smm_categories.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        document.getElementById('api_cat_loader').style.display = 'none';
        const list = document.getElementById('remote_services_list');
        list.innerHTML = '';
        
        data.services.forEach(s => {
            // Encode data to pass to PHP
            const json = btoa(JSON.stringify(s));
            list.innerHTML += `
                <div class="service-item">
                    <input type="checkbox" name="selected_services[]" value="${json}" checked>
                    <div>
                        <strong>${s.service} - ${s.name}</strong><br>
                        <small>Rate: ${s.rate} | Min: ${s.min} | Max: ${s.max}</small>
                    </div>
                </div>
            `;
        });
        document.getElementById('service_selection_div').style.display = 'block';
    });
}
</script>

<?php include '_footer.php'; ?>