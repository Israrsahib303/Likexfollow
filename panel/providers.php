<?php
include '_header.php';
requireAdmin();
require_once __DIR__ . '/../includes/smm_api.class.php';

$error = '';
$success = '';

// --- ACTIONS ---

// 1. Add/Edit Provider
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    $key = sanitize($_POST['key']);
    $margin = (float)$_POST['margin'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE smm_providers SET name=?, api_url=?, api_key=?, profit_margin=?, is_active=? WHERE id=?");
        if ($stmt->execute([$name, $url, $key, $margin, $is_active, $id])) {
            $success = "Provider updated successfully! 🟣";
        } else {
            $error = "Failed to update provider.";
        }
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO smm_providers (name, api_url, api_key, profit_margin, is_active) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $url, $key, $margin, $is_active])) {
            $success = "New provider added successfully! 🚀";
        } else {
            $error = "Failed to add provider.";
        }
    }
}

// 2. Delete Provider (SMART SAFE DELETE)
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    
    try {
        // SAFETY TRICK: Foreign Key Checks ko Temporary Band karein
        $db->query("SET FOREIGN_KEY_CHECKS = 0");

        // Step A: Delete all services linked to this provider
        $db->prepare("DELETE FROM smm_services WHERE provider_id = ?")->execute([$id]);

        // Step B: Clean up Categories
        $db->query("DELETE FROM smm_categories WHERE name NOT IN (SELECT DISTINCT category FROM smm_services)");

        // Step C: Delete the Provider
        $db->prepare("DELETE FROM smm_providers WHERE id = ?")->execute([$id]);

        // Re-enable Foreign Key Checks
        $db->query("SET FOREIGN_KEY_CHECKS = 1");

        $success = "Provider & Services deleted safely. History preserved.";
        
    } catch (Exception $e) {
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        $error = "Error: " . $e->getMessage();
    }
}

// 3. Toggle Status
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $current = $db->query("SELECT is_active FROM smm_providers WHERE id=$id")->fetchColumn();
    $new = $current ? 0 : 1;
    $db->prepare("UPDATE smm_providers SET is_active=? WHERE id=?")->execute([$new, $id]);
    echo "<script>window.location.href='providers.php';</script>";
}

// --- FETCH DATA ---
$providers = $db->query("SELECT * FROM smm_providers ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary: #8b5cf6;
        --primary-dark: #7c3aed;
        --primary-light: #ddd6fe;
        --bg-body: #f5f3ff;
        --glass: rgba(255, 255, 255, 0.95);
        --text-main: #1e1b4b;
        --text-sec: #6b7280;
        --border: #e9d5ff;
        --shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.15);
        --radius: 20px;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        margin: 0; padding: 0;
        overflow-x: hidden;
    }

    /* --- ANIMATIONS --- */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulseGlow {
        0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(139, 92, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
    }

    /* --- CONTAINER --- */
    .purple-container {
        max-width: 1400px; margin: 30px auto; padding: 0 20px;
        animation: fadeInUp 0.6s ease-out;
    }

    /* --- HEADER --- */
    .glass-header {
        background: var(--glass);
        backdrop-filter: blur(12px);
        border: 1px solid white;
        padding: 20px 30px;
        border-radius: var(--radius);
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        flex-wrap: wrap; gap: 15px;
    }

    .header-title h2 { margin: 0; font-size: 26px; font-weight: 700; background: linear-gradient(135deg, #6d28d9, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .header-title p { margin: 5px 0 0; color: var(--text-sec); font-size: 14px; }

    .btn-add {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white; border: none; padding: 14px 28px; border-radius: 50px;
        font-weight: 600; font-size: 15px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 10px;
        transition: 0.3s; box-shadow: 0 8px 20px -5px rgba(124, 58, 237, 0.4);
    }
    .btn-add:hover { transform: translateY(-3px); box-shadow: 0 12px 25px -5px rgba(124, 58, 237, 0.5); }
    .btn-add i { font-size: 14px; }

    /* --- GRID LAYOUT (FIXED) --- */
    .provider-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); /* Responsive columns */
        gap: 25px;
    }

    /* --- CARDS --- */
    .prov-card {
        background: white; border-radius: var(--radius);
        padding: 25px; border: 1px solid transparent;
        transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative; overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    
    .prov-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -5px rgba(0,0,0,0.1);
        border-color: var(--primary-light);
    }

    /* Card Top */
    .pc-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .pc-icon {
        width: 50px; height: 50px; background: var(--bg-body);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: var(--primary);
    }
    .status-badge {
        padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.5px; text-decoration: none; transition: 0.2s;
    }
    .st-active { background: #dcfce7; color: #15803d; }
    .st-inactive { background: #fee2e2; color: #b91c1c; }

    /* Card Body */
    .pc-body h3 { margin: 0 0 5px; font-size: 18px; color: var(--text-main); }
    .pc-body .url-link { font-size: 13px; color: var(--primary); text-decoration: none; background: rgba(139, 92, 246, 0.1); padding: 4px 10px; border-radius: 8px; }

    /* Stats Row */
    .pc-stats {
        display: flex; gap: 10px; margin: 20px 0; background: #fafafa; padding: 12px; border-radius: 14px;
    }
    .stat-item { flex: 1; text-align: center; }
    .stat-label { font-size: 10px; color: var(--text-sec); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; }
    .stat-val { font-size: 14px; font-weight: 700; color: #333; }

    /* Card Actions */
    .pc-actions { display: flex; gap: 10px; border-top: 1px dashed var(--border); padding-top: 15px; }
    .btn-action {
        flex: 1; padding: 10px; border-radius: 10px; text-align: center;
        font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
        transition: 0.2s; border: none; display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-edit { background: #eff6ff; color: #2563eb; }
    .btn-edit:hover { background: #dbeafe; }
    .btn-del { background: #fef2f2; color: #dc2626; }
    .btn-del:hover { background: #fee2e2; }
    .btn-bal { background: #f0fdf4; color: #16a34a; }
    .btn-bal:hover { background: #dcfce7; }

    /* --- MODAL (Animated) --- */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px);
        z-index: 999; display: none; justify-content: center; align-items: center;
        opacity: 0; transition: opacity 0.3s ease;
    }
    .modal-overlay.active { display: flex; opacity: 1; }

    .modal-box {
        background: white; width: 500px; max-width: 90%; padding: 35px; border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        transform: scale(0.9) translateY(20px); transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .modal-overlay.active .modal-box { transform: scale(1) translateY(0); }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-sec); margin-bottom: 8px; }
    .form-input {
        width: 100%; padding: 14px; border: 2px solid #e5e7eb; border-radius: 12px;
        font-size: 15px; transition: 0.2s; outline: none; box-sizing: border-box; font-family: inherit;
    }
    .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }

    /* Alerts */
    .msg { padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; font-weight: 500; animation: fadeInUp 0.4s; }
    .msg-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* Responsive Media Queries */
    @media (max-width: 768px) {
        .glass-header { flex-direction: column; align-items: flex-start; }
        .btn-add { width: 100%; justify-content: center; }
        .provider-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="purple-container">
    
    <div class="glass-header">
        <div class="header-title">
            <h2>🔌 Providers Manager</h2>
            <p>Connect & Sync APIs seamlessly</p>
        </div>
        <button class="btn-add" onclick="openModal()">
            <i class="fa-solid fa-plus-circle"></i> Connect New API
        </button>
    </div>

    <?php if ($success): ?><div class="msg msg-success"><i class="fa-solid fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg msg-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div><?php endif; ?>

    <div class="provider-grid">
        <?php if (empty($providers)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-sec);">
                <i class="fa-solid fa-cloud-slash" style="font-size: 40px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                No providers connected yet.
            </div>
        <?php else: ?>
            <?php foreach ($providers as $p): ?>
                <div class="prov-card">
                    <div class="pc-head">
                        <div class="pc-icon"><i class="fa-solid fa-server"></i></div>
                        <a href="providers.php?toggle_id=<?= $p['id'] ?>" class="status-badge <?= $p['is_active'] ? 'st-active' : 'st-inactive' ?>">
                            <?= $p['is_active'] ? 'Active' : 'Disabled' ?>
                        </a>
                    </div>
                    
                    <div class="pc-body">
                        <h3><?= sanitize($p['name']) ?></h3>
                        <span class="url-link"><?= sanitize($p['api_url']) ?></span>
                    </div>

                    <div class="pc-stats">
                        <div class="stat-item">
                            <span class="stat-label">Profit</span>
                            <span class="stat-val" style="color: var(--primary);">+<?= $p['profit_margin'] ?>%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">API Key</span>
                            <span class="stat-val" style="font-family: monospace;">••••<?= substr($p['api_key'], -4) ?></span>
                        </div>
                    </div>

                    <div class="pc-actions">
                        <a href="provider_check.php" class="btn-action btn-bal"><i class="fa-solid fa-wallet"></i> Check</a>
                        <button class="btn-action btn-edit" onclick='editProvider(<?= json_encode($p) ?>)'><i class="fa-solid fa-pen"></i> Edit</button>
                        <a href="providers.php?delete_id=<?= $p['id'] ?>" class="btn-action btn-del" onclick="return confirm('⚠️ Warning: Delete this provider? Order history will be saved.')"><i class="fa-solid fa-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<div class="modal-overlay" id="provModal">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <h3 id="modalTitle" style="margin:0; font-size:22px; font-weight:700; color:var(--text-main);">Add Provider</h3>
            <button onclick="closeModal()" style="border:none; background:#f3f4f6; width:35px; height:35px; border-radius:50%; cursor:pointer; font-size:16px; color:#555;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="id" id="p_id">
            
            <div class="form-group">
                <label class="form-label">Provider Name</label>
                <input type="text" name="name" id="p_name" class="form-input" placeholder="e.g. SMMPanel" required>
            </div>

            <div class="form-group">
                <label class="form-label">API URL</label>
                <input type="url" name="url" id="p_url" class="form-input" placeholder="https://panel.com/api/v2" required>
            </div>

            <div class="form-group">
                <label class="form-label">API Key</label>
                <input type="text" name="key" id="p_key" class="form-input" placeholder="Enter API Key" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Margin (%)</label>
                    <input type="number" name="margin" id="p_margin" class="form-input" value="10" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" id="p_active" class="form-input">
                        <option value="1">Active 🟢</option>
                        <option value="0">Disabled 🔴</option>
                    </select>
                </div>
            </div>

            <button class="btn-add" style="width:100%; justify-content:center; margin-top:10px;">Save Configuration</button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').innerText = 'Connect New Provider';
    document.getElementById('p_id').value = '';
    document.getElementById('p_name').value = '';
    document.getElementById('p_url').value = '';
    document.getElementById('p_key').value = '';
    document.getElementById('p_margin').value = '10';
    document.getElementById('p_active').value = '1';
    
    let m = document.getElementById('provModal');
    m.style.display = 'flex';
    setTimeout(() => m.classList.add('active'), 10);
}

function editProvider(data) {
    document.getElementById('modalTitle').innerText = 'Edit Provider';
    document.getElementById('p_id').value = data.id;
    document.getElementById('p_name').value = data.name;
    document.getElementById('p_url').value = data.api_url;
    document.getElementById('p_key').value = data.api_key;
    document.getElementById('p_margin').value = data.profit_margin;
    document.getElementById('p_active').value = data.is_active;
    
    let m = document.getElementById('provModal');
    m.style.display = 'flex';
    setTimeout(() => m.classList.add('active'), 10);
}

function closeModal() {
    let m = document.getElementById('provModal');
    m.classList.remove('active');
    setTimeout(() => m.style.display = 'none', 300);
}

window.onclick = function(e) {
    if (e.target == document.getElementById('provModal')) closeModal();
}
</script>

<?php include '_footer.php'; ?>